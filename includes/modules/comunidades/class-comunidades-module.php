<?php
/**
 * Modulo Comunidades para Chat IA
 *
 * Crea y gestiona comunidades tematicas con miembros, actividades y contenido compartido.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Comunidades - Gestion de comunidades tematicas
 */
class Flavor_Platform_Comunidades_Module extends Flavor_Platform_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;
    use Flavor_Encuestas_Features;

    /**
     * Constructor
     */
    public function __construct() {
        // Auto-registered AJAX handlers
        add_action('wp_ajax_comunidades_unirse', [$this, 'ajax_unirse']);
        add_action('wp_ajax_comunidades_salir', [$this, 'ajax_salir']);
        add_action('wp_ajax_comunidades_publicar', [$this, 'ajax_publicar']);
        add_action('wp_ajax_comunidades_invitar', [$this, 'ajax_invitar']);

        $this->id = 'comunidades';
        $this->name = 'Comunidades'; // Translation loaded on init
        $this->description = 'Crea y gestiona comunidades tematicas con miembros, actividades y contenido compartido'; // Translation loaded on init
        $this->module_role = 'base';
        $this->ecosystem_base_for_modules = ['eventos', 'ayuda_vecinal', 'foros', 'energia_comunitaria'];
        $this->ecosystem_supports_modules = ['eventos', 'ayuda_vecinal', 'foros', 'presupuestos_participativos', 'energia_comunitaria', 'marketplace'];
        $this->dashboard_parent_module = 'comunidades';
        $this->dashboard_satellite_priority = 10;
        $this->dashboard_client_contexts = ['comunidad', 'miembro', 'coordinacion'];
        $this->dashboard_admin_contexts = ['comunidad', 'coordinacion', 'admin'];

        // Principios Gailu: Comunidades es modulo base que soporta todos los principios
        $this->gailu_principios = ['economia_local', 'cuidados', 'gobernanza', 'regeneracion', 'aprendizaje'];
        $this->gailu_contribuye_a = ['autonomia', 'resiliencia', 'cohesion', 'impacto'];

        if (did_action('init')) {
            $this->register_shortcodes();
        } else {
            add_action('init', [$this, 'register_shortcodes']);
        }

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        return Flavor_Platform_Helpers::tabla_existe($tabla_comunidades);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Comunidades no estan creadas. Se crearan automaticamente al activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
        
    return '';
    }

/**
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'maximo_comunidades_por_usuario' => 10,
            'requiere_aprobacion_creacion'   => false,
            'permitir_comunidades_secretas'  => true,
            'categorias_predeterminadas'     => [
                'tecnologia'    => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'deportes'      => __('Deportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cultura'       => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'educacion'     => __('Educacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'medioambiente' => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'salud'         => __('Salud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'ocio'          => __('Ocio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'vecinal'       => __('Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'otros'         => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'podcast', 'articulos_social', 'biblioteca', 'recetas'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_comunidades',
                'context' => 'normal',
                'label'   => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ];
    }

    /**
     * Obtiene la configuracion para el Module Renderer
     *
     * Define tabs, campos, estadisticas y configuracion visual
     * para el sistema de paginas dinamicas.
     *
     * @return array Configuracion del renderer
     */
    public static function get_renderer_config() {
        return [
            'module'   => 'comunidades',
            'title'    => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'subtitle' => __('Gestiona tus comunidades y actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'     => '👥',
            'color'    => 'teal',

            'database' => [
                'table'         => 'flavor_comunidades',
                'status_field'  => 'estado',
                'order_by'      => 'created_at DESC',
                'filter_fields' => ['categoria', 'tipo'],
            ],

            'fields' => [
                'titulo'      => 'nombre',
                'descripcion' => 'descripcion',
                'estado'      => 'estado',
                'imagen'      => 'imagen',
                'categoria'   => 'categoria',
            ],

            'stats' => [
                [
                    'label'       => __('Total Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'        => 'dashicons-admin-multisite',
                    'color'       => '#14b8a6',
                    'count_where' => "estado = 'activa'",
                ],
                [
                    'label'       => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'        => 'dashicons-groups',
                    'color'       => '#0ea5e9',
                    'query'       => "SELECT COUNT(DISTINCT m.comunidad_id) FROM {table}_miembros m WHERE m.user_id = " . get_current_user_id() . " AND m.estado = 'activo'",
                ],
                [
                    'label'       => __('Miembros Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'        => 'dashicons-businessman',
                    'color'       => '#8b5cf6',
                    'query'       => "SELECT COUNT(*) FROM {table}_miembros WHERE estado = 'activo'",
                ],
                [
                    'label'       => __('Publicaciones Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'        => 'dashicons-format-chat',
                    'color'       => '#f59e0b',
                    'query'       => "SELECT COUNT(*) FROM {table}_actividad WHERE DATE(created_at) = CURDATE()",
                ],
            ],

            'tabs' => [
                'comunidades' => [
                    'label'   => __('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-list-view',
                    'content' => 'callback:render_tab_comunidades',
                ],
                'crear' => [
                    'label'   => __('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-plus-alt',
                    'content' => '[comunidades_crear]',
                    'requires_login' => true,
                ],
                'mis-comunidades' => [
                    'label'   => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-admin-multisite',
                    'content' => 'callback:render_tab_mis_comunidades',
                    'requires_login' => true,
                ],
                'miembros' => [
                    'label'   => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-groups',
                    'content' => 'callback:render_tab_miembros',
                    'hidden_nav' => true,
                ],
                'actividad' => [
                    'label'   => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-rss',
                    'content' => '[comunidades_feed_unificado]',
                    'requires_login' => true,
                ],
                // Tabs de integracion
                'foros' => [
                    'label'         => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'          => 'dashicons-admin-comments',
                    'content'       => 'callback:render_tab_foros',
                ],
                'chat' => [
                    'label'         => __('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'          => 'dashicons-format-chat',
                    'content'       => 'callback:render_tab_chat',
                    'requires_login' => true,
                ],
                'multimedia' => [
                    'label'         => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'          => 'dashicons-format-gallery',
                    'content'       => 'callback:render_tab_multimedia',
                ],
                'red-social' => [
                    'label'         => __('Red social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'          => 'dashicons-share',
                    'content'       => 'callback:render_tab_red_social',
                    'requires_login' => true,
                ],
                'eventos' => [
                    'label'   => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-calendar-alt',
                    'content' => 'callback:render_tab_eventos',
                ],
                'grupos-consumo' => [
                    'label'   => __('Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-carrot',
                    'content' => 'callback:render_tab_grupos_consumo',
                    'requires_login' => true,
                ],
                'banco-tiempo' => [
                    'label'   => __('Banco del tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-clock',
                    'content' => 'callback:render_tab_banco_tiempo',
                    'requires_login' => true,
                ],
                'marketplace' => [
                    'label'   => __('Mercadillo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-cart',
                    'content' => 'callback:render_tab_marketplace',
                    'requires_login' => false,
                ],
                'recetas' => [
                    'label'   => __('Recetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-carrot',
                    'content' => 'callback:render_tab_recetas',
                ],
                'biblioteca' => [
                    'label'   => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-book',
                    'content' => 'callback:render_tab_biblioteca',
                ],
                'podcast' => [
                    'label'   => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-microphone',
                    'content' => 'callback:render_tab_podcast',
                ],
                'anuncios' => [
                    'label'   => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-megaphone',
                    'content' => '[comunidades_tablon limite="20" incluir_red="true"]',
                ],
                'recursos' => [
                    'label'   => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'icon'    => 'dashicons-media-document',
                    'content' => '[comunidades_recursos_compartidos]',
                ],
            ],

            'card' => [
                'color'      => 'teal',
                'icon'       => '👥',
                'show_image' => true,
                'layout'     => 'vertical',

                'fields' => [
                    'id'       => 'id',
                    'title'    => 'nombre',
                    'subtitle' => 'descripcion',
                    'image'    => 'imagen',
                ],

                'badge' => [
                    'field'  => 'tipo',
                    'colors' => [
                        'publica'  => 'green',
                        'privada'  => 'blue',
                        'secreta'  => 'purple',
                    ],
                    'labels' => [
                        'publica'  => __('Publica', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'privada'  => __('Privada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'secreta'  => __('Secreta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],

                'meta' => [
                    ['icon' => '👥', 'field' => 'total_miembros', 'suffix' => ' ' . __('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ['icon' => '📁', 'field' => 'categoria_nombre'],
                ],
            ],

            'archive' => [
                'columns'      => 3,
                'filter_field' => 'categoria',
                'cta_text'     => __('Crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_url'      => home_url('/mi-portal/comunidades/crear/'),
                'cta_icon'     => 'dashicons-plus-alt',
                'empty_state'  => [
                    'icon'     => '🏘️',
                    'title'    => __('No hay comunidades todavia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'text'     => __('Se el primero en crear una comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'cta_text' => __('Crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'cta_url'  => home_url('/mi-portal/comunidades/crear/'),
                ],
            ],

            'single' => [
                'meta_fields' => [
                    ['field' => 'categoria_nombre', 'icon' => '📁'],
                    ['field' => 'total_miembros', 'icon' => '👥', 'suffix' => ' ' . __('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ['field' => 'created_at', 'icon' => '📅', 'prefix' => __('Creada el ', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'detail_fields' => [
                    ['field' => 'tipo', 'label' => __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                    ['field' => 'ubicacion', 'label' => __('Ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN)],
                ],
                'actions' => [
                    [
                        'label'   => __('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'icon'    => '🚀',
                        'action'  => 'flavorComunidades.unirse({id})',
                        'primary' => true,
                    ],
                    [
                        'label'  => __('Compartir', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'icon'   => '🔗',
                        'action' => 'flavorComunidades.compartir({id})',
                    ],
                ],
            ],

            'form' => [
                'create_title' => __('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'edit_title'   => __('Editar Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description'  => __('Completa los datos para crear tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fields' => [
                    [
                        'name'        => 'nombre',
                        'label'       => __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'type'        => 'text',
                        'required'    => true,
                        'placeholder' => __('Nombre de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    [
                        'name'        => 'descripcion',
                        'label'       => __('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'type'        => 'textarea',
                        'rows'        => 4,
                        'placeholder' => __('Describe de que trata esta comunidad...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    [
                        'name'    => 'categoria',
                        'label'   => __('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'type'    => 'select',
                        'options' => [
                            'tecnologia'    => __('Tecnologia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'deportes'      => __('Deportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'cultura'       => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'educacion'     => __('Educacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'medioambiente' => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'salud'         => __('Salud', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'ocio'          => __('Ocio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'vecinal'       => __('Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'otros'         => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ],
                    ],
                    [
                        'name'    => 'tipo',
                        'label'   => __('Tipo de comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'type'    => 'select',
                        'options' => [
                            'publica' => __('Publica - Cualquiera puede unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'privada' => __('Privada - Requiere aprobacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            'secreta' => __('Secreta - Solo por invitacion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        ],
                        'help' => __('El tipo determina quien puede ver y unirse a la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    [
                        'name'  => 'imagen_portada',
                        'label' => __('Imagen de portada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'type'  => 'image',
                    ],
                ],
            ],

            'estados' => [
                'activa' => [
                    'label' => __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'green',
                    'icon'  => 'dashicons-yes-alt',
                ],
                'pendiente' => [
                    'label' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'yellow',
                    'icon'  => 'dashicons-clock',
                ],
                'inactiva' => [
                    'label' => __('Inactiva', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'gray',
                    'icon'  => 'dashicons-hidden',
                ],
            ],
        ];
    }

    /**
     * Renderiza el tab de miembros
     *
     * @return string HTML del tab
     */
    public function render_tab_miembros() {
        global $wpdb;

        $usuario_id = get_current_user_id();
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        // Obtener miembros de las comunidades del usuario
        $miembros = [];
        if ($usuario_id) {
            $miembros = $wpdb->get_results($wpdb->prepare(
                "SELECT DISTINCT u.ID, u.display_name, u.user_email,
                        m.rol, m.joined_at,
                        c.nombre AS comunidad_nombre, c.id AS comunidad_id
                 FROM {$wpdb->users} u
                 INNER JOIN $tabla_miembros m ON u.ID = m.user_id
                 INNER JOIN $tabla_comunidades c ON m.comunidad_id = c.id
                 WHERE m.comunidad_id IN (
                     SELECT comunidad_id FROM $tabla_miembros
                     WHERE user_id = %d AND estado = 'activo'
                 )
                 AND m.estado = 'activo'
                 ORDER BY m.joined_at DESC
                 LIMIT 50",
                $usuario_id
            ));
        }

        ob_start();
        ?>
        <div class="flavor-comunidades-miembros">
            <?php if (!is_user_logged_in()): ?>
                <div class="flavor-login-required bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
                    <span class="text-4xl mb-3 block">🔒</span>
                    <p class="text-amber-800 mb-4"><?php esc_html_e('Inicia sesion para ver los miembros de tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(wp_login_url(home_url('/mi-portal/comunidades/'))); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                        <?php esc_html_e('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php elseif (empty($miembros)): ?>
                <div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                    <span class="text-5xl mb-4 block">👥</span>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php esc_html_e('Sin miembros todavia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p class="text-gray-500 mb-4"><?php esc_html_e('Unete a una comunidad para conectar con otros miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition-colors">
                        <span class="dashicons dashicons-search"></span>
                        <?php esc_html_e('Explorar comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($miembros as $miembro): ?>
                        <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-3">
                                <?php echo get_avatar($miembro->ID, 48, '', '', ['class' => 'rounded-full']); ?>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-medium text-gray-900 truncate"><?php echo esc_html($miembro->display_name); ?></h4>
                                    <p class="text-sm text-gray-500 truncate"><?php echo esc_html($miembro->comunidad_nombre); ?></p>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full <?php echo $miembro->rol === 'admin' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'; ?>">
                                    <?php echo esc_html(ucfirst($miembro->rol)); ?>
                                </span>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100 flex items-center justify-between text-sm">
                                <span class="text-gray-400">
                                    <?php echo esc_html(sprintf(__('Desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN), date_i18n(get_option('date_format'), strtotime($miembro->joined_at)))); ?>
                                </span>
                                <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $miembro->comunidad_id . '/')); ?>" class="text-teal-600 hover:text-teal-700">
                                    <?php esc_html_e('Ver comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene los tabs para el dashboard del modulo
     *
     * Este metodo es llamado por class-dynamic-pages.php para
     * renderizar los tabs en la interfaz de usuario.
     *
     * @return array Configuracion de tabs
     */
    public function get_dashboard_tabs() {
        return [
            'comunidades' => [
                'label'   => __('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-list-view',
                'content' => 'callback:render_tab_comunidades',
            ],
            'crear' => [
                'label'   => __('Crear', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-plus-alt',
                'content' => '[comunidades_crear]',
                'requires_login' => true,
            ],
            'mis-comunidades' => [
                'label'   => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-admin-multisite',
                'content' => 'callback:render_tab_mis_comunidades',
                'requires_login' => true,
            ],
            'miembros' => [
                'label'   => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-groups',
                'content' => 'callback:render_tab_miembros',
                'requires_login' => true,
            ],
            'actividad' => [
                'label'   => __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-rss',
                'content' => 'callback:render_tab_actividad',
                'requires_login' => true,
            ],
            // Tabs de integracion con otros modulos
            'foros' => [
                'label'         => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'          => 'dashicons-admin-comments',
                'content'       => 'callback:render_tab_foros',
            ],
            'chat' => [
                'label'         => __('Chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'          => 'dashicons-format-chat',
                'content'       => 'callback:render_tab_chat',
                'requires_login' => true,
            ],
            'multimedia' => [
                'label'         => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'          => 'dashicons-format-gallery',
                'content'       => 'callback:render_tab_multimedia',
            ],
            'red-social' => [
                'label'         => __('Red social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'          => 'dashicons-share',
                'content'       => 'callback:render_tab_red_social',
                'requires_login' => true,
            ],
            'eventos' => [
                'label'   => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-calendar-alt',
                'content' => 'callback:render_tab_eventos',
            ],
            'grupos-consumo' => [
                'label'   => __('Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-carrot',
                'content' => 'callback:render_tab_grupos_consumo',
                'requires_login' => true,
            ],
            'banco-tiempo' => [
                'label'   => __('Banco del tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-clock',
                'content' => 'callback:render_tab_banco_tiempo',
                'requires_login' => true,
            ],
            'marketplace' => [
                'label'   => __('Mercadillo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-cart',
                'content' => 'callback:render_tab_marketplace',
                'requires_login' => false,
            ],
            'recetas' => [
                'label'   => __('Recetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-carrot',
                'content' => 'callback:render_tab_recetas',
            ],
            'biblioteca' => [
                'label'   => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-book',
                'content' => 'callback:render_tab_biblioteca',
            ],
            'podcast' => [
                'label'   => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-microphone',
                'content' => 'callback:render_tab_podcast',
            ],
            'anuncios' => [
                'label'   => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-megaphone',
                'content' => '[comunidades_tablon limite="20" incluir_red="true"]',
            ],
            'recursos' => [
                'label'   => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'    => 'dashicons-media-document',
                'content' => '[comunidades_recursos_compartidos]',
            ],
        ];
    }

    /**
     * Renderiza el tab de listado de comunidades
     *
     * @return string HTML del tab
     */
    public function render_tab_comunidades() {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Obtener comunidades publicas y privadas (no secretas)
        $comunidades = $wpdb->get_results(
            "SELECT c.*,
                    (SELECT COUNT(*) FROM $tabla_miembros m WHERE m.comunidad_id = c.id AND m.estado = 'activo') AS total_miembros
             FROM $tabla_comunidades c
             WHERE c.estado = 'activa'
             AND c.tipo IN ('publica', 'privada')
             ORDER BY total_miembros DESC, c.created_at DESC
             LIMIT 24"
        );

        $categorias = $this->settings['categorias_predeterminadas'] ?? [];
        $usuario_id = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-comunidades-grid">
            <?php if (empty($comunidades)): ?>
                <div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center col-span-full">
                    <span class="text-5xl mb-4 block">🏘️</span>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2"><?php esc_html_e('No hay comunidades todavia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    <p class="text-gray-500 mb-4"><?php esc_html_e('Se el primero en crear una comunidad y conectar con otros.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php if (is_user_logged_in()): ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/crear/')); ?>" class="inline-flex items-center gap-2 px-4 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg transition-colors">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($comunidades as $comunidad):
                        $categoria_nombre = $categorias[$comunidad->categoria] ?? ucfirst($comunidad->categoria);
                        $es_miembro = false;
                        if ($usuario_id) {
                            $es_miembro = $wpdb->get_var($wpdb->prepare(
                                "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
                                $comunidad->id, $usuario_id
                            )) > 0;
                        }
                    ?>
                        <div class="bg-white rounded-xl border border-gray-100 overflow-hidden hover:shadow-lg transition-all group">
                            <?php if ($comunidad->imagen_portada): ?>
                                <div class="h-32 overflow-hidden">
                                    <img src="<?php echo esc_url($comunidad->imagen_portada); ?>" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </div>
                            <?php else: ?>
                                <div class="h-32 bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center">
                                    <span class="text-4xl text-white/80">🏘️</span>
                                </div>
                            <?php endif; ?>

                            <div class="p-4">
                                <div class="flex items-start justify-between gap-2 mb-2">
                                    <h3 class="font-semibold text-gray-900 line-clamp-1"><?php echo esc_html($comunidad->nombre); ?></h3>
                                    <span class="px-2 py-0.5 text-xs rounded-full <?php echo $comunidad->tipo === 'publica' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo $comunidad->tipo === 'publica' ? esc_html__('Publica', FLAVOR_PLATFORM_TEXT_DOMAIN) : esc_html__('Privada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </div>

                                <p class="text-sm text-gray-500 line-clamp-2 mb-3"><?php echo esc_html($comunidad->descripcion); ?></p>

                                <div class="flex items-center gap-4 text-sm text-gray-400 mb-4">
                                    <span class="flex items-center gap-1">
                                        <span class="dashicons dashicons-groups text-sm"></span>
                                        <?php echo esc_html($comunidad->total_miembros); ?>
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="dashicons dashicons-category text-sm"></span>
                                        <?php echo esc_html($categoria_nombre); ?>
                                    </span>
                                </div>

                                <div class="flex gap-2">
                                    <a href="<?php echo esc_url(home_url('/mi-portal/comunidades/' . $comunidad->id . '/')); ?>" class="flex-1 text-center px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg text-sm transition-colors">
                                        <?php esc_html_e('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                    <?php if (!$es_miembro && is_user_logged_in()): ?>
                                        <button onclick="flavorComunidades.unirse(<?php echo (int)$comunidad->id; ?>)" class="flex-1 px-3 py-2 bg-teal-500 hover:bg-teal-600 text-white rounded-lg text-sm transition-colors">
                                            <?php esc_html_e('Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    <?php elseif ($es_miembro): ?>
                                        <span class="flex-1 text-center px-3 py-2 bg-teal-100 text-teal-700 rounded-lg text-sm">
                                            <?php esc_html_e('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza el tab de mis comunidades
     *
     * @return string HTML del tab
     */
    public function render_tab_mis_comunidades() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
                <span class="text-4xl mb-3 block">🔒</span>
                <p class="text-amber-800 mb-4">' . esc_html__('Inicia sesion para ver tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url(home_url('/mi-portal/comunidades/'))) . '" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                    ' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '
                </a>
            </div>';
        }

        // Reutilizar el shortcode existente
        return $this->shortcode_mis_comunidades([]);
    }

    /**
     * Renderiza el tab de actividad
     *
     * @return string HTML del tab
     */
    public function render_tab_actividad() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-required bg-amber-50 border border-amber-200 rounded-xl p-6 text-center">
                <span class="text-4xl mb-3 block">🔒</span>
                <p class="text-amber-800 mb-4">' . esc_html__('Inicia sesion para ver la actividad de tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                <a href="' . esc_url(wp_login_url(home_url('/mi-portal/comunidades/'))) . '" class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition-colors">
                    ' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '
                </a>
            </div>';
        }

        // Usar el shortcode de feed unificado
        return $this->shortcode_feed_unificado([
            'limite'        => 20,
            'mostrar_origen' => 'true',
            'incluir_red'   => 'false',
        ]);
    }

    /**
     * Renderiza eventos vinculados a la comunidad actual o a las comunidades del usuario.
     *
     * @return string HTML del tab
     */
    public function render_tab_eventos() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();

        if (empty($comunidad_ids)) {
            if (!is_user_logged_in()) {
                return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                    <span class="text-5xl mb-4 block">📅</span>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Eventos de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                    <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesion o accede a una comunidad concreta para ver sus eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
                </div>';
            }

            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">📅</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Sin comunidades vinculadas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Aun no perteneces a ninguna comunidad con eventos asociados.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        if (count($comunidad_ids) === 1) {
            $comunidad_id = absint($comunidad_ids[0]);
            $comunidad = $this->obtener_comunidad($comunidad_id);
            $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
            $header .= '<div>';
            $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Eventos de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
            if (!empty($comunidad->nombre)) {
                $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
            }
            $header .= '</div>';
            if (current_user_can('edit_posts')) {
                $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/eventos/crear-evento/'))) . '">';
                $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
                $header .= esc_html__('Crear evento', FLAVOR_PLATFORM_TEXT_DOMAIN);
                $header .= '</a>';
            }
            $header .= '</div>';

            return $header . do_shortcode('[eventos_listado limite="12" mostrar_filtros="false" comunidad_id="' . $comunidad_id . '"]');
        }

        return do_shortcode('[eventos_listado limite="12" mostrar_filtros="false" comunidad_ids="' . esc_attr(implode(',', $comunidad_ids)) . '"]');
    }

    /**
     * Renderiza el chat asociado a una comunidad concreta.
     *
     * @return string HTML del tab
     */
    public function render_tab_chat() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Chat de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para acceder al chat de tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Sin comunidad contextual', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para entrar en su chat.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);
        $grupo_chat_id = $this->obtener_grupo_chat_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Chat de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        if ($grupo_chat_id) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo_id' => $grupo_chat_id], home_url('/mi-portal/chat-grupos/mensajes/'))) . '">';
            $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Abrir chat completo', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor_chat_grupo_integrado entidad="comunidad" entidad_id="' . $comunidad_id . '" altura="560px"]');
    }

    /**
     * Renderiza el foro asociado a una comunidad concreta.
     *
     * @return string HTML del tab
     */
    public function render_tab_foros() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">💭</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Foro de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para ver su foro asociado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100">';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Foro de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor_foros_integrado entidad="comunidad" entidad_id="' . $comunidad_id . '"]');
    }

    /**
     * Renderiza la galería multimedia asociada a una comunidad concreta.
     *
     * @return string HTML del tab
     */
    public function render_tab_multimedia() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();

        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🖼️</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Galería de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para ver su galería multimedia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Galería de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/multimedia/subir/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Subir archivo', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor_multimedia_galeria entidad="comunidad" entidad_id="' . $comunidad_id . '" limite="12" columnas="4" mostrar_filtros="true"]');
    }

    /**
     * Renderiza el feed social asociado a una comunidad concreta.
     *
     * @return string HTML del tab
     */
    public function render_tab_red_social() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🫂</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Actividad social de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para ver las publicaciones y actividad de tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🫂</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Sin comunidad contextual', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para ver su actividad social.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Actividad social de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/red-social/crear/'))) . '">';
        $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Publicar', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $header .= '</a>';
        $header .= '</div>';

        return $header . do_shortcode('[flavor_social_feed entidad="comunidad" entidad_id="' . $comunidad_id . '"]');
    }

    /**
     * Renderiza el grupo de consumo asociado a una comunidad concreta.
     *
     * @return string HTML del tab
     */
    public function render_tab_grupos_consumo() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🥬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Grupo de consumo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para acceder al grupo de consumo de tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🥬</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Sin comunidad contextual', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para ver su grupo de consumo asociado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);
        $grupo = $this->obtener_grupo_consumo_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Grupo de consumo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';

        if ($grupo instanceof WP_Post) {
            $header .= '<div class="flex items-center gap-2 flex-wrap">';
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['grupo' => $grupo->ID], home_url('/mi-portal/grupos-consumo/unirme/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Unirme', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-indigo-700 text-sm font-medium border border-indigo-200 hover:bg-indigo-50" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/grupos-consumo/grupos/'))) . '">';
            $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Abrir módulo completo', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
            $header .= '</div>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[gc_grupos_lista columnas="1" limite="6" comunidad_id="' . $comunidad_id . '"]');
    }

    /**
     * Renderiza el tab contextual de banco del tiempo.
     *
     * @return string
     */
    public function render_tab_banco_tiempo() {
        if (!is_user_logged_in()) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">⏰</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Banco del tiempo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Inicia sesión para acceder a los servicios del banco del tiempo de tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">⏰</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Sin comunidad contextual', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para ver su banco del tiempo asociado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Banco del tiempo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        $header .= '<div class="flex items-center gap-2 flex-wrap">';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/banco-tiempo/ofrecer/'))) . '">';
        $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Ofrecer servicio', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $header .= '</a>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-teal-700 text-sm font-medium border border-teal-200 hover:bg-teal-50" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/banco-tiempo/servicios/'))) . '">';
        $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Abrir módulo completo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $header .= '</a>';
        $header .= '</div>';
        $header .= '</div>';

        return $header . do_shortcode('[banco_tiempo_servicios limite="12" columnas="3" comunidad_id="' . $comunidad_id . '"]');
    }

    /**
     * Renderiza el tab contextual de marketplace (mercadillo de la comunidad).
     *
     * @return string
     */
    public function render_tab_marketplace() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🛒</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Mercadillo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para explorar su mercadillo local.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Mercadillo de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        $header .= '<div class="flex items-center gap-2 flex-wrap">';

        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-lime-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad' => $comunidad_id], home_url('/mi-portal/marketplace/publicar/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Publicar anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
        }

        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-white text-lime-700 text-sm font-medium border border-lime-200 hover:bg-lime-50" href="' . esc_url(home_url('/mi-portal/marketplace/')) . '">';
        $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Ver todo el marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $header .= '</a>';
        $header .= '</div>';
        $header .= '</div>';

        return $header . do_shortcode('[marketplace_catalogo limite="12" columnas="3" comunidad="' . $comunidad_id . '" mostrar_filtros="si"]');
    }

    /**
     * Renderiza el tab contextual de recetas.
     *
     * @return string
     */
    public function render_tab_recetas() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🍳</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Recetas de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para explorar sus recetas compartidas.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Recetas de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-orange-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/recetas/nueva/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Compartir receta', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[flavor module="recetas" view="listado" header="no" limit="12"]');
    }

    /**
     * Renderiza el tab contextual de biblioteca.
     *
     * @return string
     */
    public function render_tab_biblioteca() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">📚</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Biblioteca de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para explorar su biblioteca compartida.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Biblioteca de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        if (is_user_logged_in()) {
            $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-sky-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/biblioteca/anadir/'))) . '">';
            $header .= '<span class="dashicons dashicons-plus-alt" style="font-size:16px;width:16px;height:16px;"></span>';
            $header .= esc_html__('Añadir libro', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $header .= '</a>';
        }
        $header .= '</div>';

        return $header . do_shortcode('[biblioteca_catalogo]');
    }

    /**
     * Renderiza el tab contextual de podcast.
     *
     * @return string
     */
    public function render_tab_podcast() {
        $comunidad_ids = $this->get_contextual_comunidad_ids_for_eventos_tab();
        if (empty($comunidad_ids)) {
            return '<div class="flavor-empty-state bg-gray-50 rounded-xl p-8 text-center">
                <span class="text-5xl mb-4 block">🎙️</span>
                <h3 class="text-lg font-semibold text-gray-900 mb-2">' . esc_html__('Podcast de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>
                <p class="text-gray-500 mb-4">' . esc_html__('Accede a una comunidad concreta para escuchar o descubrir sus programas y episodios.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>
            </div>';
        }

        $comunidad_id = absint($comunidad_ids[0]);
        $comunidad = $this->obtener_comunidad($comunidad_id);

        $header = '<div class="flavor-integrated-tab-header bg-white rounded-xl p-4 mb-4 border border-gray-100 flex items-center justify-between gap-4 flex-wrap">';
        $header .= '<div>';
        $header .= '<h3 class="text-lg font-semibold text-gray-900 mb-1">' . esc_html__('Podcast de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
        if (!empty($comunidad->nombre)) {
            $header .= '<p class="text-sm text-gray-500">' . esc_html($comunidad->nombre) . '</p>';
        }
        $header .= '</div>';
        $header .= '<a class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-violet-600 text-white text-sm font-medium hover:opacity-90" href="' . esc_url(add_query_arg(['comunidad_id' => $comunidad_id], home_url('/mi-portal/podcast/programas/'))) . '">';
        $header .= '<span class="dashicons dashicons-external" style="font-size:16px;width:16px;height:16px;"></span>';
        $header .= esc_html__('Abrir módulo completo', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $header .= '</a>';
        $header .= '</div>';

        return $header . do_shortcode('[podcast_series]');
    }

    /**
     * Obtiene el grupo de consumo principal asociado a una comunidad.
     *
     * @param int $comunidad_id ID de la comunidad.
     * @return WP_Post|null
     */
    private function obtener_grupo_consumo_comunidad($comunidad_id) {
        $comunidad_id = absint($comunidad_id);
        if ($comunidad_id <= 0) {
            return null;
        }

        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            if ($loader && method_exists($loader, 'get_module_instance')) {
                $modulo = $loader->get_module_instance('grupos_consumo');
                if ($modulo && method_exists($modulo, 'obtener_grupo_principal_comunidad')) {
                    $grupo = $modulo->obtener_grupo_principal_comunidad($comunidad_id);
                    if ($grupo instanceof WP_Post) {
                        return $grupo;
                    }
                }
            }
        }

        $grupos = get_posts([
            'post_type' => 'gc_grupo',
            'post_status' => 'publish',
            'posts_per_page' => 1,
            'meta_query' => [
                [
                    'key' => '_flavor_comunidad_id',
                    'value' => $comunidad_id,
                    'compare' => '=',
                    'type' => 'NUMERIC',
                ],
            ],
        ]);

        return !empty($grupos) ? $grupos[0] : null;
    }

    /**
     * Obtiene el contexto de comunidad aplicable al tab de eventos.
     *
     * @return int[]
     */
    private function get_contextual_comunidad_ids_for_eventos_tab() {
        global $wpdb;

        $direct_id = absint($_GET['comunidad_id'] ?? $_GET['comunidad'] ?? $_GET['id'] ?? 0);
        if ($direct_id > 0) {
            return [$direct_id];
        }

        $request_path = (string) wp_parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
        if (preg_match('#/mi-portal/comunidades/(\d+)(?:/|$)#', $request_path, $matches)) {
            return [absint($matches[1])];
        }

        if (!is_user_logged_in()) {
            return [];
        }

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $usuario_id = get_current_user_id();

        return array_map('intval', (array) $wpdb->get_col($wpdb->prepare(
            "SELECT comunidad_id
             FROM {$tabla_miembros}
             WHERE user_id = %d AND estado = 'activo'
             ORDER BY joined_at DESC
             LIMIT 12",
            $usuario_id
        )));
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar como consumer de integraciones
        $this->register_as_integration_consumer();

        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        $this->register_ajax_handlers();

        // Registrar páginas de administración
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);

        // Registrar en Panel Unificado de Gestion
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // Sistema de notificaciones cross-comunidad
        $this->init_cross_community_notifications();

        // Integrar funcionalidades de encuestas
        $this->init_encuestas_features('comunidad');

        // Inicializar Dashboard Tab para el panel de usuario
        $this->init_dashboard_tab();
    }

    /**
     * Inicializa el Dashboard Tab para el panel de usuario
     */
    private function init_dashboard_tab() {
        $dashboard_tab_file = dirname(__FILE__) . '/class-comunidades-dashboard-tab.php';

        if (file_exists($dashboard_tab_file)) {
            require_once $dashboard_tab_file;

            if (class_exists('Flavor_Comunidades_Dashboard_Tab')) {
                $dashboard_tab = Flavor_Comunidades_Dashboard_Tab::get_instance();
                $dashboard_tab->set_module($this);
            }
        }
    }

    /**
     * Inicializa el sistema de notificaciones cross-comunidad
     */
    private function init_cross_community_notifications() {
        // Hooks para disparar notificaciones cross-comunidad
        add_action('flavor_comunidad_nueva_publicacion', [$this, 'notificar_nueva_publicacion'], 10, 3);
        add_action('flavor_comunidad_nuevo_evento', [$this, 'notificar_nuevo_evento'], 10, 3);
        add_action('flavor_comunidad_nuevo_miembro', [$this, 'notificar_nuevo_miembro'], 10, 2);
        add_action('flavor_comunidad_recurso_compartido', [$this, 'notificar_recurso_compartido'], 10, 4);
        add_action('flavor_comunidad_mencion', [$this, 'notificar_mencion'], 10, 4);
        add_action('flavor_red_nuevo_contenido', [$this, 'notificar_contenido_federado'], 10, 2);

        // Hook para contenido cross-posteado
        add_action('flavor_comunidad_crosspost', [$this, 'notificar_crosspost'], 10, 4);

        // AJAX para gestión de preferencias de notificaciones
        add_action('wp_ajax_comunidades_guardar_preferencias_notificaciones', [$this, 'ajax_guardar_preferencias_notificaciones']);
        add_action('wp_ajax_comunidades_obtener_notificaciones', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_comunidades_marcar_notificacion_leida', [$this, 'ajax_marcar_notificacion_leida']);
        add_action('wp_ajax_comunidades_marcar_todas_leidas', [$this, 'ajax_marcar_todas_leidas']);
        add_action('wp_ajax_comunidades_eliminar_notificacion', [$this, 'ajax_eliminar_notificacion']);
    }

    /**
     * Registra los shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('comunidades_listar', [$this, 'shortcode_listado']);
        add_shortcode('comunidades_crear', [$this, 'shortcode_crear']);
        add_shortcode('comunidades_detalle', [$this, 'shortcode_detalle']);
        add_shortcode('comunidades_actividad', [$this, 'shortcode_feed_actividad']);
        add_shortcode('comunidades_mis_comunidades', [$this, 'shortcode_mis_comunidades']);
        // Nuevos shortcodes de interconexión
        add_shortcode('comunidades_feed_unificado', [$this, 'shortcode_feed_unificado']);
        add_shortcode('comunidades_calendario', [$this, 'shortcode_calendario_coordinado']);
        add_shortcode('comunidades_recursos_compartidos', [$this, 'shortcode_recursos_compartidos']);
        add_shortcode('comunidades_notificaciones', [$this, 'shortcode_centro_notificaciones']);
        add_shortcode('comunidades_busqueda', [$this, 'shortcode_busqueda_federada']);
        add_shortcode('comunidades_tablon', [$this, 'shortcode_tablon_anuncios']);
        add_shortcode('comunidades_metricas', [$this, 'shortcode_metricas_colaboracion']);
    }

    /**
     * Shortcode: Métricas de colaboración
     * [comunidades_metricas]
     */
    public function shortcode_metricas_colaboracion($atts) {
        ob_start();
        include dirname(__FILE__) . '/views/metricas-colaboracion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Tablón de anuncios inter-comunidades
     * [comunidades_tablon limite="20" destacados="false" incluir_red="true"]
     */
    public function shortcode_tablon_anuncios($atts) {
        $atributos = shortcode_atts([
            'limite'      => 20,
            'destacados'  => 'false',
            'incluir_red' => 'true',
        ], $atts);

        ob_start();
        include dirname(__FILE__) . '/views/tablon-anuncios.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Búsqueda federada unificada
     * [comunidades_busqueda]
     */
    public function shortcode_busqueda_federada($atts) {
        ob_start();
        include dirname(__FILE__) . '/views/busqueda-federada.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Centro de notificaciones cross-comunidad
     * [comunidades_notificaciones]
     */
    public function shortcode_centro_notificaciones($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-login-requerido">' .
                   '<p>' . __('Inicia sesión para ver tus notificaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>' .
                   '<a href="' . esc_url(wp_login_url(home_url('/mi-portal/comunidades/'))) . '" class="flavor-btn-primario">' .
                   __('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a></div>';
        }

        ob_start();
        include dirname(__FILE__) . '/views/centro-notificaciones.php';
        return ob_get_clean();
    }

    /**
     * Registra los handlers AJAX del módulo
     */
    public function register_ajax_handlers() {
        $acciones_autenticadas = [
            'comunidades_crear',
            'comunidades_unirse',
            'comunidades_salir',
            'comunidades_publicar',
            'comunidades_invitar',
        ];

        foreach ($acciones_autenticadas as $accion) {
            add_action('wp_ajax_' . $accion, [$this, 'ajax_' . str_replace('comunidades_', '', $accion)]);
        }

        add_action('wp_ajax_comunidades_cargar_actividad', [$this, 'ajax_cargar_actividad']);
        add_action('wp_ajax_nopriv_comunidades_cargar_actividad', [$this, 'ajax_cargar_actividad']);
        add_action('wp_ajax_comunidades_like', [$this, 'ajax_like']);

        // Acciones públicas
        add_action('wp_ajax_comunidades_cargar_mas', [$this, 'ajax_cargar_mas']);
        add_action('wp_ajax_nopriv_comunidades_cargar_mas', [$this, 'ajax_cargar_mas']);
        add_action('wp_ajax_comunidades_obtener_comunidad', [$this, 'ajax_obtener_comunidad']);
        add_action('wp_ajax_nopriv_comunidades_obtener_comunidad', [$this, 'ajax_obtener_comunidad']);

        // AJAX para interconexión de comunidades
        add_action('wp_ajax_comunidades_feed_unificado', [$this, 'ajax_feed_unificado']);
        add_action('wp_ajax_comunidades_compartir_publicacion', [$this, 'ajax_compartir_publicacion']);
        add_action('wp_ajax_comunidades_calendario_eventos', [$this, 'ajax_calendario_eventos']);
        add_action('wp_ajax_comunidades_recursos_compartidos', [$this, 'ajax_recursos_compartidos']);

        // AJAX para búsqueda federada (público)
        add_action('wp_ajax_comunidades_busqueda_federada', [$this, 'ajax_busqueda_federada']);
        add_action('wp_ajax_nopriv_comunidades_busqueda_federada', [$this, 'ajax_busqueda_federada']);

        // AJAX para tablón de anuncios
        add_action('wp_ajax_comunidades_obtener_anuncios', [$this, 'ajax_obtener_anuncios']);
        add_action('wp_ajax_nopriv_comunidades_obtener_anuncios', [$this, 'ajax_obtener_anuncios']);
        add_action('wp_ajax_comunidades_crear_anuncio', [$this, 'ajax_crear_anuncio']);
        add_action('wp_ajax_comunidades_mis_comunidades_admin', [$this, 'ajax_mis_comunidades_admin']);

        // AJAX para métricas de colaboración
        add_action('wp_ajax_comunidades_obtener_metricas', [$this, 'ajax_obtener_metricas']);
        add_action('wp_ajax_nopriv_comunidades_obtener_metricas', [$this, 'ajax_obtener_metricas']);
    }

    /**
     * Encola los assets del frontend
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        $ruta_modulo = plugin_dir_url(__FILE__);

        wp_enqueue_style(
            'flavor-comunidades',
            $ruta_modulo . 'assets/css/comunidades.css',
            [],
            '1.0.0'
        );

        wp_enqueue_script(
            'flavor-comunidades',
            $ruta_modulo . 'assets/js/comunidades.js',
            ['jquery'],
            '1.0.0',
            true
        );

        wp_localize_script('flavor-comunidades', 'flavorComunidadesConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_comunidades_nonce'),
            'strings' => [
                'error' => __('Ha ocurrido un error. Inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmUnirse' => __('¿Deseas unirte a esta comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmSalir' => __('¿Estás seguro de que deseas abandonar esta comunidad?', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Verifica si se deben cargar los assets
     */
    private function should_load_assets() {
        // Detectar páginas dinámicas de comunidades
        $flavor_module = get_query_var('flavor_module', '');
        if ($flavor_module === 'comunidades') {
            return true;
        }

        // Detectar por URL (para rutas directas como /mi-portal/comunidades/)
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($request_uri, '/comunidades') !== false || strpos($request_uri, '/mi-portal/comunidades') !== false) {
            return true;
        }

        // Verificar shortcodes en el contenido del post
        global $post;
        if (!$post) {
            return false;
        }

        $shortcodes = [
            'comunidades_listar',
            'comunidades_crear',
            'comunidades_detalle',
            'comunidades_actividad',
            'comunidades_mis_comunidades',
            'comunidades_feed_unificado',
            'comunidades_calendario',
            'comunidades_recursos_compartidos',
            'comunidades_notificaciones',
            'comunidades_busqueda',
            'comunidades_tablon',
            'comunidades_metricas',
        ];

        foreach ($shortcodes as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================================
    // Shortcodes
    // =========================================================================

    /**
     * Shortcode: Listado de comunidades
     */
    public function shortcode_listado($atts) {
        $atributos = shortcode_atts([
            'categoria' => '',
            'tipo' => '',
            'columnas' => 3,
            'limite' => 12,
            'mostrar_filtros' => 'si',
        ], $atts);

        $resultado = $this->action_listar_comunidades([
            'categoria' => $atributos['categoria'],
            'tipo' => $atributos['tipo'],
            'limite' => intval($atributos['limite']),
        ]);

        $comunidades = $resultado['success'] ? $resultado['comunidades'] : [];
        $categorias = $this->settings['categorias_predeterminadas'] ?? [];
        $identificador_usuario = get_current_user_id();

        ob_start();
        include dirname(__FILE__) . '/views/listado-comunidades.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Crear comunidad
     */
    public function shortcode_crear($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-com-notice flavor-com-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para crear una comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                       wp_login_url(home_url('/mi-portal/comunidades/'))
                   ) .
                   '</div>';
        }

        $categorias = $this->settings['categorias_predeterminadas'] ?? [];

        ob_start();
        include dirname(__FILE__) . '/views/crear-comunidad.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Detalle de comunidad
     */
    public function shortcode_detalle($atts) {
        $atributos = shortcode_atts([
            'id' => 0,
        ], $atts);

        $comunidad_id = $atributos['id'] ?: (isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0);

        if (!$comunidad_id) {
            return '<div class="flavor-com-notice flavor-com-notice-warning">' .
                   __('Comunidad no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        $resultado = $this->action_ver_comunidad(['comunidad_id' => $comunidad_id]);

        if (!$resultado['success']) {
            return '<div class="flavor-com-notice flavor-com-notice-error">' .
                   esc_html($resultado['error']) .
                   '</div>';
        }

        $comunidad = $resultado['comunidad'];
        $miembros = $resultado['miembros'];
        $identificador_usuario = get_current_user_id();
        $es_miembro = false;
        $rol_usuario = null;

        foreach ($miembros as $miembro) {
            if ($miembro['user_id'] === $identificador_usuario) {
                $es_miembro = true;
                $rol_usuario = $miembro['rol'];
                break;
            }
        }

        // Obtener grupo de chat de la comunidad (solo si es miembro)
        $grupo_chat_id = $es_miembro ? $this->obtener_grupo_chat_comunidad($comunidad_id) : null;
        $chat_grupos_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Chat_Grupos_Module')
            : 'Flavor_Chat_Chat_Grupos_Module';
        $chat_grupos_activo = class_exists($chat_grupos_module_class) && $grupo_chat_id;

        ob_start();
        include dirname(__FILE__) . '/views/detalle-comunidad.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Feed de actividad
     */
    public function shortcode_feed_actividad($atts) {
        $atributos = shortcode_atts([
            'comunidad_id' => 0,
            'limite' => 20,
        ], $atts);

        $comunidad_id = $atributos['comunidad_id'] ?: (isset($_GET['comunidad']) ? absint($_GET['comunidad']) : 0);

        if (!$comunidad_id) {
            return '<div class="flavor-com-notice flavor-com-notice-info">' .
                   __('Selecciona una comunidad para ver su actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN) .
                   '</div>';
        }

        $resultado = $this->action_feed_actividad([
            'comunidad_id' => $comunidad_id,
            'limite' => intval($atributos['limite']),
        ]);

        $actividades = $resultado['success'] ? ($resultado['actividades'] ?? []) : [];
        return $this->render_feed_html($actividades, $comunidad_id);
    }

    /**
     * Shortcode: Mis comunidades
     */
    public function shortcode_mis_comunidades($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-com-notice flavor-com-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para ver tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                       wp_login_url(home_url('/mi-portal/comunidades/'))
                   ) .
                   '</div>';
        }

        $resultado = $this->action_mis_comunidades([]);

        $comunidades = $resultado['success'] ? $resultado['comunidades'] : [];
        $categorias = $this->settings['categorias_predeterminadas'] ?? [];

        ob_start();
        include dirname(__FILE__) . '/views/mis-comunidades.php';
        return ob_get_clean();
    }

    // =========================================================================
    // Shortcodes de Interconexión Federada
    // =========================================================================

    /**
     * Shortcode: Feed unificado de todas las comunidades del usuario (locales + federadas)
     * [comunidades_feed_unificado limite="20" mostrar_origen="true" incluir_red="true"]
     */
    public function shortcode_feed_unificado($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-com-notice flavor-com-notice-warning">' .
                   sprintf(
                       __('Debes <a href="%s">iniciar sesión</a> para ver el feed unificado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                       wp_login_url(home_url('/mi-portal/comunidades/'))
                   ) .
                   '</div>';
        }

        $atributos = shortcode_atts([
            'limite'        => 30,
            'mostrar_origen' => 'true',
            'incluir_red'   => 'true',
            'filtro_comunidad' => '',
        ], $atts);

        $usuario_id = get_current_user_id();
        $limite = intval($atributos['limite']);
        $incluir_red = $atributos['incluir_red'] === 'true';

        // Obtener actividades de comunidades locales
        $actividades_locales = $this->obtener_feed_unificado_local($usuario_id, $limite);

        // Obtener contenido de la red federada si está habilitado
        $contenido_red = [];
        if ($incluir_red && class_exists('Flavor_Network_Content_Bridge')) {
            $contenido_red = $this->obtener_contenido_red_comunidades($limite);
        }

        // Combinar y ordenar por fecha
        $feed_combinado = $this->combinar_feed_federado($actividades_locales, $contenido_red);

        // Limitar resultados finales
        $feed_combinado = array_slice($feed_combinado, 0, $limite);

        // Obtener comunidades del usuario para el filtro
        $mis_comunidades = $this->action_mis_comunidades([]);
        $comunidades_usuario = $mis_comunidades['success'] ? $mis_comunidades['comunidades'] : [];

        ob_start();
        include dirname(__FILE__) . '/views/feed-unificado.php';
        return ob_get_clean();
    }

    /**
     * Obtiene el feed unificado de comunidades locales del usuario
     */
    private function obtener_feed_unificado_local($usuario_id, $limite = 30) {
        global $wpdb;

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_reacciones = $wpdb->prefix . 'flavor_comunidades_actividad_reacciones';

        // Obtener comunidades del usuario
        $comunidades_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT comunidad_id FROM $tabla_miembros WHERE user_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (empty($comunidades_ids)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($comunidades_ids), '%d'));

        // Obtener actividades de todas sus comunidades
        $query = $wpdb->prepare(
            "SELECT a.*,
                    c.nombre AS comunidad_nombre,
                    c.imagen AS comunidad_imagen,
                    c.categoria AS comunidad_categoria,
                    u.display_name AS autor_nombre,
                    u.ID AS autor_id,
                    'local' AS origen_tipo
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON a.comunidad_id = c.id
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             WHERE a.comunidad_id IN ($placeholders)
             ORDER BY a.es_fijado DESC, a.created_at DESC
             LIMIT %d",
            array_merge($comunidades_ids, [$limite])
        );

        $actividades = $wpdb->get_results($query);

        // Agregar conteo de likes
        foreach ($actividades as &$actividad) {
            $actividad->likes_count = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reacciones WHERE actividad_id = %d",
                $actividad->id
            ));
            $actividad->usuario_dio_like = (bool) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_reacciones WHERE actividad_id = %d AND user_id = %d",
                $actividad->id,
                $usuario_id
            ));
        }

        return $actividades;
    }

    /**
     * Obtiene contenido de comunidades de la red federada
     */
    private function obtener_contenido_red_comunidades($limite = 20) {
        if (!class_exists('Flavor_Network_Content_Bridge')) {
            return [];
        }

        $tipos_comunidad = ['comunidades', 'grupos_consumo', 'banco_tiempo'];
        $contenido = [];

        foreach ($tipos_comunidad as $tipo) {
            $items = apply_filters('flavor_get_network_content', [], $tipo, [
                'limite'        => $limite,
                'excluir_local' => true,
            ]);

            foreach ($items as $item) {
                $item->origen_tipo = 'federado';
                $item->tipo_comunidad = $tipo;
                $contenido[] = $item;
            }
        }

        return $contenido;
    }

    /**
     * Combina actividades locales con contenido federado
     */
    private function combinar_feed_federado($locales, $federados) {
        $combinado = [];

        // Normalizar actividades locales
        foreach ($locales as $local) {
            $combinado[] = (object) [
                'id'                => $local->id,
                'tipo'              => $local->tipo ?? 'publicacion',
                'titulo'            => $local->titulo ?? '',
                'contenido'         => $local->contenido ?? '',
                'imagen'            => $local->imagen ?? '',
                'autor_nombre'      => $local->autor_nombre ?? '',
                'autor_id'          => $local->autor_id ?? 0,
                'comunidad_id'      => $local->comunidad_id ?? 0,
                'comunidad_nombre'  => $local->comunidad_nombre ?? '',
                'comunidad_imagen'  => $local->comunidad_imagen ?? '',
                'comunidad_categoria' => $local->comunidad_categoria ?? '',
                'likes_count'       => $local->likes_count ?? 0,
                'usuario_dio_like'  => $local->usuario_dio_like ?? false,
                'fecha'             => $local->created_at ?? '',
                'origen_tipo'       => 'local',
                'url_externa'       => '',
                'nodo_nombre'       => get_bloginfo('name'),
                'nodo_logo'         => get_site_icon_url(),
            ];
        }

        // Normalizar contenido federado
        foreach ($federados as $fed) {
            $metadata = is_string($fed->metadata ?? '') ? json_decode($fed->metadata, true) : ($fed->metadata ?? []);
            $combinado[] = (object) [
                'id'                => $fed->id ?? 0,
                'tipo'              => 'contenido_red',
                'titulo'            => $fed->titulo ?? '',
                'contenido'         => $fed->descripcion ?? '',
                'imagen'            => $fed->imagen_url ?? '',
                'autor_nombre'      => $metadata['author'] ?? '',
                'autor_id'          => 0,
                'comunidad_id'      => 0,
                'comunidad_nombre'  => $fed->titulo ?? '',
                'comunidad_imagen'  => $fed->imagen_url ?? '',
                'comunidad_categoria' => $fed->tipo_comunidad ?? '',
                'likes_count'       => 0,
                'usuario_dio_like'  => false,
                'fecha'             => $fed->fecha_creacion ?? '',
                'origen_tipo'       => 'federado',
                'url_externa'       => $fed->url_externa ?? '',
                'nodo_nombre'       => $fed->nodo_nombre ?? '',
                'nodo_logo'         => $fed->nodo_logo ?? '',
            ];
        }

        // Ordenar por fecha descendente
        usort($combinado, function($a, $b) {
            return strtotime($b->fecha ?: '1970-01-01') - strtotime($a->fecha ?: '1970-01-01');
        });

        return $combinado;
    }

    /**
     * Shortcode: Calendario coordinado de eventos de comunidades
     * [comunidades_calendario vista="mes" incluir_red="true"]
     */
    public function shortcode_calendario_coordinado($atts) {
        $atributos = shortcode_atts([
            'vista'       => 'mes',
            'incluir_red' => 'true',
            'comunidad_id' => 0,
        ], $atts);

        $usuario_id = get_current_user_id();
        $incluir_red = $atributos['incluir_red'] === 'true';

        // Obtener eventos locales de comunidades
        $eventos_locales = $this->obtener_eventos_comunidades($usuario_id);

        // Obtener eventos de la red federada
        $eventos_red = [];
        if ($incluir_red) {
            $eventos_red = apply_filters('flavor_get_network_content', [], 'eventos', [
                'limite'        => 50,
                'excluir_local' => true,
            ]);
        }

        // Combinar eventos
        $todos_eventos = $this->combinar_eventos_calendario($eventos_locales, $eventos_red);

        ob_start();
        include dirname(__FILE__) . '/views/calendario-coordinado.php';
        return ob_get_clean();
    }

    /**
     * Obtiene eventos de las comunidades del usuario
     */
    private function obtener_eventos_comunidades($usuario_id) {
        global $wpdb;

        // Verificar si el módulo de eventos está activo
        $eventos_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Eventos_Module')
            : 'Flavor_Chat_Eventos_Module';
        if (!class_exists($eventos_module_class)) {
            return [];
        }

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';

        // Obtener comunidades del usuario
        $comunidades_ids = [];
        if ($usuario_id) {
            $comunidades_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT comunidad_id FROM $tabla_miembros WHERE user_id = %d AND estado = 'activo'",
                $usuario_id
            ));
        }

        // Verificar si la tabla de eventos existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") !== $tabla_eventos) {
            return [];
        }

        // Obtener eventos de esas comunidades o eventos públicos
        $where_comunidades = '';
        if (!empty($comunidades_ids)) {
            $placeholders = implode(',', array_fill(0, count($comunidades_ids), '%d'));
            $where_comunidades = $wpdb->prepare(
                "comunidad_id IN ($placeholders) OR",
                $comunidades_ids
            );
        }

        $eventos = $wpdb->get_results(
            "SELECT * FROM $tabla_eventos
             WHERE ($where_comunidades visibilidad = 'publico')
               AND fecha_inicio >= CURDATE()
             ORDER BY fecha_inicio ASC
             LIMIT 100"
        );

        return $eventos ?: [];
    }

    /**
     * Combina eventos locales con eventos de la red
     */
    private function combinar_eventos_calendario($locales, $federados) {
        $eventos = [];

        foreach ($locales as $evento) {
            $eventos[] = [
                'id'          => $evento->id,
                'titulo'      => $evento->titulo ?? $evento->nombre ?? '',
                'descripcion' => $evento->descripcion ?? '',
                'fecha_inicio' => $evento->fecha_inicio ?? '',
                'fecha_fin'   => $evento->fecha_fin ?? '',
                'ubicacion'   => $evento->ubicacion ?? '',
                'origen'      => 'local',
                'url'         => home_url('/mi-portal/eventos/' . intval($evento->id ?? 0) . '/'),
                'color'       => '#3b82f6',
            ];
        }

        foreach ($federados as $evento) {
            $metadata = is_string($evento->metadata ?? '') ? json_decode($evento->metadata, true) : [];
            $eventos[] = [
                'id'          => 'fed_' . ($evento->id ?? 0),
                'titulo'      => $evento->titulo ?? '',
                'descripcion' => $evento->descripcion ?? '',
                'fecha_inicio' => $metadata['fecha_inicio'] ?? $evento->fecha_creacion ?? '',
                'fecha_fin'   => $metadata['fecha_fin'] ?? '',
                'ubicacion'   => $metadata['ubicacion'] ?? '',
                'origen'      => 'federado',
                'url'         => $evento->url_externa ?? '',
                'nodo_nombre' => $evento->nodo_nombre ?? '',
                'color'       => '#10b981',
            ];
        }

        // Ordenar por fecha de inicio
        usort($eventos, function($a, $b) {
            return strtotime($a['fecha_inicio'] ?: '2999-12-31') - strtotime($b['fecha_inicio'] ?: '2999-12-31');
        });

        return $eventos;
    }

    /**
     * Shortcode: Recursos compartidos entre comunidades
     * [comunidades_recursos_compartidos tipos="recetas,biblioteca,multimedia" limite="12"]
     */
    public function shortcode_recursos_compartidos($atts) {
        $atributos = shortcode_atts([
            'tipos'        => 'recetas,biblioteca,multimedia,podcast',
            'limite'       => 12,
            'columnas'     => 4,
            'comunidad_id' => 0,
            'incluir_red'  => 'true',
        ], $atts);

        $tipos = array_map('trim', explode(',', $atributos['tipos']));
        $limite = intval($atributos['limite']);
        $incluir_red = $atributos['incluir_red'] === 'true';

        // Obtener recursos locales integrados
        $recursos_locales = $this->obtener_recursos_integrados($tipos, $limite);

        // Obtener recursos de la red federada
        $recursos_red = [];
        if ($incluir_red) {
            foreach ($tipos as $tipo) {
                $items = apply_filters('flavor_get_network_content', [], $tipo, [
                    'limite'        => $limite,
                    'excluir_local' => true,
                ]);
                foreach ($items as $item) {
                    $item->origen = 'federado';
                    $recursos_red[] = $item;
                }
            }
        }

        // Combinar recursos
        $recursos = array_merge($recursos_locales, $recursos_red);

        // Ordenar por fecha
        usort($recursos, function($a, $b) {
            $fecha_a = $a->fecha ?? $a->fecha_creacion ?? $a->post_date ?? '';
            $fecha_b = $b->fecha ?? $b->fecha_creacion ?? $b->post_date ?? '';
            return strtotime($fecha_b ?: '1970-01-01') - strtotime($fecha_a ?: '1970-01-01');
        });

        $recursos = array_slice($recursos, 0, $limite);

        ob_start();
        include dirname(__FILE__) . '/views/recursos-compartidos.php';
        return ob_get_clean();
    }

    /**
     * Obtiene recursos integrados de los módulos providers
     */
    private function obtener_recursos_integrados($tipos, $limite = 20) {
        $recursos = [];

        // Mapeo de tipos a post_types
        $tipo_a_post_type = [
            'recetas'     => 'flavor_receta',
            'biblioteca'  => 'flavor_biblioteca',
            'multimedia'  => 'flavor_multimedia',
            'podcast'     => 'flavor_podcast',
            'videos'      => 'flavor_video',
        ];

        foreach ($tipos as $tipo) {
            $post_type = $tipo_a_post_type[$tipo] ?? $tipo;

            $posts = get_posts([
                'post_type'      => $post_type,
                'post_status'    => 'publish',
                'posts_per_page' => $limite,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ]);

            foreach ($posts as $post) {
                $recursos[] = (object) [
                    'id'          => $post->ID,
                    'titulo'      => $post->post_title,
                    'descripcion' => wp_trim_words($post->post_content, 20),
                    'imagen'      => get_the_post_thumbnail_url($post->ID, 'medium'),
                    'url'         => $this->obtener_url_recurso_integrado($tipo, $post->ID),
                    'tipo'        => $tipo,
                    'fecha'       => $post->post_date,
                    'autor'       => get_the_author_meta('display_name', $post->post_author),
                    'origen'      => 'local',
                ];
            }
        }

        return $recursos;
    }

    private function obtener_url_recurso_integrado($tipo, $post_id) {
        $post_id = absint($post_id);

        switch ($tipo) {
            case 'multimedia':
                return add_query_arg('archivo_id', $post_id, home_url('/mi-portal/multimedia/mi-galeria/'));

            case 'biblioteca':
                return add_query_arg('libro_id', $post_id, home_url('/mi-portal/biblioteca/'));

            case 'podcast':
                return add_query_arg('episodio_id', $post_id, home_url('/mi-portal/podcast/'));

            case 'recetas':
                return add_query_arg('id', $post_id, home_url('/mi-portal/recetas/'));

            default:
                return get_permalink($post_id);
        }
    }

    // =========================================================================
    // AJAX Handlers para Interconexión
    // =========================================================================

    /**
     * AJAX: Obtener feed unificado
     */
    public function ajax_feed_unificado() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $usuario_id = get_current_user_id();
        $limite = absint($_POST['limite'] ?? 20);
        $offset = absint($_POST['offset'] ?? 0);
        $comunidad_filtro = absint($_POST['comunidad_id'] ?? 0);
        $incluir_red = isset($_POST['incluir_red']) ? $_POST['incluir_red'] === 'true' : true;

        $actividades = $this->obtener_feed_unificado_local($usuario_id, $limite + $offset);
        $actividades = array_slice($actividades, $offset, $limite);

        $contenido_red = [];
        if ($incluir_red) {
            $contenido_red = $this->obtener_contenido_red_comunidades($limite);
        }

        $feed = $this->combinar_feed_federado($actividades, $contenido_red);
        $feed = array_slice($feed, 0, $limite);

        wp_send_json_success([
            'feed'    => $feed,
            'total'   => count($feed),
            'hay_mas' => count($actividades) >= $limite,
        ]);
    }

    /**
     * AJAX: Compartir publicación en otra comunidad (cross-posting)
     */
    public function ajax_compartir_publicacion() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $actividad_id = absint($_POST['actividad_id'] ?? 0);
        $comunidad_destino = absint($_POST['comunidad_destino'] ?? 0);
        $comentario = sanitize_textarea_field($_POST['comentario'] ?? '');
        $usuario_id = get_current_user_id();

        if (!$actividad_id || !$comunidad_destino) {
            wp_send_json_error(['message' => __('Datos incompletos.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        // Verificar que el usuario es miembro de la comunidad destino
        if (!$this->es_miembro_activo($comunidad_destino, $usuario_id)) {
            wp_send_json_error(['message' => __('No eres miembro de la comunidad destino.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
            return;
        }

        global $wpdb;
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        // Obtener publicación original
        $original = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_actividad WHERE id = %d",
            $actividad_id
        ));

        if (!$original) {
            wp_send_json_error(['message' => __('Publicación no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
            return;
        }

        // Crear publicación compartida
        $contenido_compartido = '';
        if ($comentario) {
            $contenido_compartido = $comentario . "\n\n---\n\n";
        }
        $contenido_compartido .= $original->contenido;

        $insertado = $wpdb->insert($tabla_actividad, [
            'comunidad_id'        => $comunidad_destino,
            'user_id'             => $usuario_id,
            'tipo'                => 'compartido',
            'contenido'           => $contenido_compartido,
            'imagen'              => $original->imagen,
            'referencia_original' => $actividad_id,
            'created_at'          => current_time('mysql'),
        ]);

        if ($insertado) {
            // Notificar al autor original
            do_action('flavor_comunidad_publicacion_compartida', $actividad_id, $comunidad_destino, $usuario_id);

            wp_send_json_success([
                'message' => __('Publicación compartida exitosamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'nueva_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al compartir.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 500);
        }
    }

    /**
     * AJAX: Obtener eventos del calendario coordinado
     */
    public function ajax_calendario_eventos() {
        $usuario_id = get_current_user_id();
        $mes = absint($_POST['mes'] ?? date('n'));
        $anio = absint($_POST['anio'] ?? date('Y'));
        $incluir_red = isset($_POST['incluir_red']) ? $_POST['incluir_red'] === 'true' : true;

        $eventos_locales = $this->obtener_eventos_comunidades($usuario_id);

        $eventos_red = [];
        if ($incluir_red) {
            $eventos_red = apply_filters('flavor_get_network_content', [], 'eventos', [
                'limite'        => 50,
                'excluir_local' => true,
            ]);
        }

        $eventos = $this->combinar_eventos_calendario($eventos_locales, $eventos_red);

        // Filtrar por mes/año
        $eventos = array_filter($eventos, function($evento) use ($mes, $anio) {
            $fecha = strtotime($evento['fecha_inicio'] ?? '');
            return $fecha && date('n', $fecha) == $mes && date('Y', $fecha) == $anio;
        });

        wp_send_json_success([
            'eventos' => array_values($eventos),
            'mes'     => $mes,
            'anio'    => $anio,
        ]);
    }

    /**
     * AJAX: Obtener recursos compartidos
     */
    public function ajax_recursos_compartidos() {
        $tipos = isset($_POST['tipos']) ? array_map('sanitize_text_field', (array) $_POST['tipos']) : ['recetas', 'biblioteca'];
        $limite = absint($_POST['limite'] ?? 12);
        $incluir_red = isset($_POST['incluir_red']) ? $_POST['incluir_red'] === 'true' : true;

        $recursos_locales = $this->obtener_recursos_integrados($tipos, $limite);

        $recursos_red = [];
        if ($incluir_red) {
            foreach ($tipos as $tipo) {
                $items = apply_filters('flavor_get_network_content', [], $tipo, [
                    'limite'        => $limite,
                    'excluir_local' => true,
                ]);
                foreach ($items as $item) {
                    $item->origen = 'federado';
                    $recursos_red[] = $item;
                }
            }
        }

        $recursos = array_merge($recursos_locales, $recursos_red);
        $recursos = array_slice($recursos, 0, $limite);

        wp_send_json_success([
            'recursos' => $recursos,
            'total'    => count($recursos),
        ]);
    }

    // =========================================================================
    // AJAX Handlers
    // =========================================================================

    /**
     * Verifica la seguridad de las peticiones AJAX
     */
    private function verificar_seguridad_ajax() {
        if (!check_ajax_referer('flavor_comunidades_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Error de seguridad. Recarga la página.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ], 403);
            return false;
        }
        return true;
    }

    /**
     * AJAX: Crear comunidad
     */
    public function ajax_crear() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $resultado = $this->action_crear_comunidad([
            'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'categoria' => sanitize_text_field($_POST['categoria'] ?? 'otros'),
            'tipo' => sanitize_text_field($_POST['tipo'] ?? 'abierta'),
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 400);
        }
    }

    /**
     * AJAX: Unirse a comunidad
     */
    public function ajax_unirse() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $resultado = $this->action_unirse([
            'comunidad_id' => absint($_POST['comunidad_id'] ?? 0),
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 400);
        }
    }

    /**
     * AJAX: Salir de comunidad
     */
    public function ajax_salir() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $resultado = $this->action_salir([
            'comunidad_id' => absint($_POST['comunidad_id'] ?? 0),
        ]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 400);
        }
    }

    /**
     * AJAX: Publicar en comunidad
     */
    public function ajax_publicar() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $comunidad_id = absint($_POST['comunidad_id'] ?? 0);
        $contenido = sanitize_textarea_field($_POST['contenido'] ?? '');

        if (!$comunidad_id || empty($contenido)) {
            wp_send_json_error(['message' => __('Datos incompletos.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        // Verificar membresía
        if (!$this->es_miembro_activo($comunidad_id, get_current_user_id())) {
            wp_send_json_error(['message' => __('No eres miembro de esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 403);
            return;
        }

        global $wpdb;
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        $insertado = $wpdb->insert($tabla_actividad, [
            'comunidad_id' => $comunidad_id,
            'usuario_id' => get_current_user_id(),
            'tipo' => 'publicacion',
            'contenido' => $contenido,
        ], ['%d', '%d', '%s', '%s']);

        if ($insertado) {
            wp_send_json_success(['message' => __('Publicación creada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 500);
        }
    }

    /**
     * AJAX: Invitar a comunidad
     */
    public function ajax_invitar() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        // Por implementar
        wp_send_json_success(['message' => __('Invitación enviada.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * AJAX: Cargar más comunidades
     */
    public function ajax_cargar_mas() {
        $pagina = absint($_POST['pagina'] ?? 1);
        $limite = absint($_POST['limite'] ?? 12);
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');

        $resultado = $this->action_listar_comunidades([
            'categoria' => $categoria,
            'limite' => $limite,
            'offset' => ($pagina - 1) * $limite,
        ]);

        wp_send_json_success([
            'comunidades' => $resultado['comunidades'] ?? [],
            'hay_mas' => count($resultado['comunidades'] ?? []) >= $limite,
        ]);
    }

    /**
     * AJAX: Obtener detalle de comunidad
     */
    public function ajax_obtener_comunidad() {
        $comunidad_id = absint($_POST['comunidad_id'] ?? $_GET['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            wp_send_json_error(['message' => __('ID no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        $resultado = $this->action_ver_comunidad(['comunidad_id' => $comunidad_id]);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado, 404);
        }
    }

    /**
     * AJAX: Cargar actividad de una comunidad
     */
    public function ajax_cargar_actividad() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        $comunidad_id = absint($_POST['comunidad_id'] ?? $_GET['comunidad_id'] ?? 0);
        if (!$comunidad_id) {
            wp_send_json_error(['message' => __('ID de comunidad no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        $resultado = $this->action_feed_actividad([
            'comunidad_id' => $comunidad_id,
            'limite' => absint($_POST['limite'] ?? 10),
            'offset' => absint($_POST['offset'] ?? 0),
        ]);

        if (!$resultado['success']) {
            wp_send_json_error(['message' => $resultado['error'] ?? __('No se pudo cargar la actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        wp_send_json_success([
            'actividades' => $resultado['actividades'],
            'html' => $this->render_feed_html($resultado['actividades'], $comunidad_id),
            'total' => $resultado['total'] ?? count($resultado['actividades']),
        ]);
    }

    /**
     * AJAX: Marcar / desmarcar like en una actividad
     */
    public function ajax_like() {
        if (!$this->verificar_seguridad_ajax()) {
            return;
        }

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión para reaccionar.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 401);
            return;
        }

        $actividad_id = absint($_POST['actividad_id'] ?? 0);
        if (!$actividad_id) {
            wp_send_json_error(['message' => __('Actividad no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
            return;
        }

        $resultado = $this->action_toggle_like(['actividad_id' => $actividad_id]);
        if ($resultado['success']) {
            wp_send_json_success([
                'likes' => $resultado['likes'],
                'liked' => $resultado['liked'],
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['error'] ?? __('No se pudo reaccionar.', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }
    }

    /**
     * Verifica si un usuario es miembro activo
     */
    private function es_miembro_activo($comunidad_id, $usuario_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_id
        ));
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registra las rutas de la REST API para el modulo de comunidades
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/comunidades - Listar comunidades
        register_rest_route($namespace, '/comunidades', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_listar_comunidades'],
            'permission_callback' => [$this, 'api_verificar_lectura_publica_comunidades'],
            'args'                => [
                'tipo' => [
                    'type'              => 'string',
                    'enum'              => ['abierta', 'cerrada'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'categoria' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'busqueda' => [
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'limite' => [
                    'type'              => 'integer',
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                ],
            ],
        ]);

        // GET /flavor/v1/comunidades/{id} - Obtener una comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_obtener_comunidad'],
            'permission_callback' => [$this, 'api_verificar_lectura_comunidad'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // POST /flavor/v1/comunidades/{id}/unirse - Unirse a comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)/unirse', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_unirse_comunidad'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // POST /flavor/v1/comunidades/{id}/salir - Salir de comunidad
        register_rest_route($namespace, '/comunidades/(?P<id>\d+)/salir', [
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => [$this, 'api_salir_comunidad'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'required'          => true,
                    'type'              => 'integer',
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function ($valor) {
                        return is_numeric($valor) && $valor > 0;
                    },
                ],
            ],
        ]);

        // GET /flavor/v1/comunidades/mis-comunidades - Comunidades del usuario
        register_rest_route($namespace, '/comunidades/mis-comunidades', [
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => [$this, 'api_mis_comunidades'],
            'permission_callback' => [$this, 'api_verificar_usuario_autenticado'],
            'args'                => [
                'rol' => [
                    'type'              => 'string',
                    'enum'              => ['admin', 'moderador', 'miembro'],
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'estado' => [
                    'type'              => 'string',
                    'enum'              => ['activo', 'pendiente'],
                    'default'           => 'activo',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ]);
    }

    /**
     * Verifica si el usuario esta autenticado para la REST API
     *
     * @return bool|WP_Error
     */
    public function api_verificar_usuario_autenticado() {
        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_not_logged_in',
                __('Debes iniciar sesion para realizar esta accion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 401]
            );
        }
        return true;
    }

    /**
     * Permite listar solo comunidades públicas/visibles por diseño.
     *
     * @return bool
     */
    public function api_verificar_lectura_publica_comunidades() {
        return true;
    }

    /**
     * Verifica acceso a una comunidad concreta.
     *
     * Las comunidades secretas no deben exponerse públicamente.
     *
     * @param WP_REST_Request $request
     * @return bool|\WP_Error
     */
    public function api_verificar_lectura_comunidad($request) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad_id = absint($request->get_param('id'));
        if (!$comunidad_id) {
            return new \WP_Error(
                'comunidad_invalida',
                __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 400]
            );
        }

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, tipo, estado FROM {$tabla_comunidades} WHERE id = %d",
            $comunidad_id
        ));

        if (!$comunidad || $comunidad->estado !== 'activa') {
            return new \WP_Error(
                'comunidad_no_encontrada',
                __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 404]
            );
        }

        if ($comunidad->tipo !== 'secreta') {
            return true;
        }

        if (!is_user_logged_in()) {
            return new \WP_Error(
                'rest_forbidden',
                __('No tienes permiso para ver esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 403]
            );
        }

        $es_miembro = (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_miembros} WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            get_current_user_id()
        ));

        if ($es_miembro || current_user_can('manage_options')) {
            return true;
        }

        return new \WP_Error(
            'rest_forbidden',
            __('No tienes permiso para ver esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['status' => 403]
        );
    }

    /**
     * API: Listar comunidades
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_listar_comunidades($request) {
        $parametros = [
            'tipo'     => $request->get_param('tipo'),
            'categoria' => $request->get_param('categoria'),
            'busqueda' => $request->get_param('busqueda'),
            'limite'   => $request->get_param('limite'),
        ];

        $resultado = $this->action_listar_comunidades($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'comunidades_error',
                $resultado['error'],
                ['status' => 400]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener una comunidad especifica
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_obtener_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_ver_comunidad($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'comunidad_no_encontrada',
                $resultado['error'],
                ['status' => 404]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Unirse a una comunidad
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_unirse_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_unirse($parametros);

        if (!$resultado['success']) {
            $codigo_estado = 400;

            // Determinar codigo de estado apropiado
            if (strpos($resultado['error'], 'baneado') !== false) {
                $codigo_estado = 403;
            } elseif (strpos($resultado['error'], 'no encontrada') !== false) {
                $codigo_estado = 404;
            }

            return new \WP_Error(
                'unirse_error',
                $resultado['error'],
                ['status' => $codigo_estado]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Salir de una comunidad
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_salir_comunidad($request) {
        $comunidad_id = $request->get_param('id');

        $parametros = [
            'comunidad_id' => $comunidad_id,
        ];

        $resultado = $this->action_salir($parametros);

        if (!$resultado['success']) {
            $codigo_estado = 400;

            // No es miembro activo
            if (strpos($resultado['error'], 'no eres miembro') !== false) {
                $codigo_estado = 403;
            } elseif (strpos($resultado['error'], 'unico administrador') !== false) {
                $codigo_estado = 409; // Conflict
            }

            return new \WP_Error(
                'salir_error',
                $resultado['error'],
                ['status' => $codigo_estado]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * API: Obtener comunidades del usuario autenticado
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response|WP_Error
     */
    public function api_mis_comunidades($request) {
        $parametros = [
            'rol'    => $request->get_param('rol'),
            'estado' => $request->get_param('estado'),
        ];

        $resultado = $this->action_mis_comunidades($parametros);

        if (!$resultado['success']) {
            return new \WP_Error(
                'mis_comunidades_error',
                $resultado['error'],
                ['status' => 400]
            );
        }

        return rest_ensure_response($resultado);
    }

    /**
     * Configuracion para el Panel Unificado de Gestion
     *
     * @return array Configuracion del modulo
     */
    protected function get_admin_config() {
        return [
            'id' => 'comunidades',
            'label' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon' => 'dashicons-groups',
            'capability' => 'manage_options',
            'categoria' => 'comunidad',
            'paginas' => [
                [
                    'slug' => 'comunidades-dashboard',
                    'titulo' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'comunidades-listado',
                    'titulo' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_listado'],
                    'badge' => [$this, 'contar_comunidades_activas'],
                ],
                [
                    'slug' => 'comunidades-miembros',
                    'titulo' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_miembros'],
                    'badge' => [$this, 'contar_solicitudes_pendientes'],
                ],
                [
                    'slug' => 'comunidades-config',
                    'titulo' => __('Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'callback' => [$this, 'render_admin_config'],
                ],
            ],
            'estadisticas' => [$this, 'get_estadisticas_dashboard'],
        ];
    }

    /**
     * Cuenta comunidades activas
     *
     * @return int
     */
    public function contar_comunidades_activas() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_comunidades)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'"
        );
    }

    /**
     * Cuenta solicitudes de miembros pendientes
     *
     * @return int
     */
    public function contar_solicitudes_pendientes() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            return 0;
        }
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'"
        );
    }

    /**
     * Estadisticas para el dashboard unificado
     *
     * @return array
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $estadisticas = [];

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_comunidades)) {
            return $estadisticas;
        }

        // Total comunidades activas
        $comunidades_activas = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'"
        );
        $estadisticas[] = [
            'icon' => 'dashicons-groups',
            'valor' => $comunidades_activas,
            'label' => __('Comunidades activas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'color' => $comunidades_activas > 0 ? 'blue' : 'gray',
            'enlace' => admin_url('admin.php?page=comunidades-listado'),
        ];

        // Total miembros activos
        if (Flavor_Platform_Helpers::tabla_existe($tabla_miembros)) {
            $miembros_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'activo'"
            );
            $estadisticas[] = [
                'icon' => 'dashicons-admin-users',
                'valor' => $miembros_activos,
                'label' => __('Miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'color' => $miembros_activos > 0 ? 'green' : 'gray',
                'enlace' => admin_url('admin.php?page=comunidades-miembros'),
            ];

            // Solicitudes pendientes
            $solicitudes_pendientes = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'"
            );
            if ($solicitudes_pendientes > 0) {
                $estadisticas[] = [
                    'icon' => 'dashicons-clock',
                    'valor' => $solicitudes_pendientes,
                    'label' => __('Solicitudes pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    'color' => 'orange',
                    'enlace' => admin_url('admin.php?page=comunidades-miembros&estado=pendiente'),
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Renderiza el dashboard de comunidades
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        echo '<div class="wrap flavor-modulo-page">';
        $acciones = $is_dashboard_viewer
            ? [
                ['label' => __('Ver en portal', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => home_url('/mi-portal/comunidades/'), 'class' => ''],
            ]
            : [
                ['label' => __('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado&accion=nueva'), 'class' => 'button-primary'],
            ];
        $this->render_page_header(__('Dashboard de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN), $acciones);

        if ($is_dashboard_viewer) {
            echo '<div class="notice notice-info"><p>' . esc_html__('Vista resumida para gestor de grupos. La creación y gestión detallada de comunidades sigue reservada a administración.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }

        // Estadisticas rapidas
        $total_comunidades = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades WHERE estado = 'activa'");
        $total_miembros = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'activo'");
        $total_actividad = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_actividad");
        $pendientes = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'");

        echo '<div class="flavor-stats-grid">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_comunidades) . '</span><span class="stat-label">' . __('Comunidades Activas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_miembros) . '</span><span class="stat-label">' . __('Miembros Totales', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_actividad) . '</span><span class="stat-label">' . __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        if ($pendientes > 0) {
            echo '<div class="flavor-stat-card flavor-stat-warning"><span class="stat-number">' . esc_html($pendientes) . '</span><span class="stat-label">' . __('Solicitudes Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        }
        echo '</div>';

        // Comunidades mas activas
        echo '<h2>' . __('Comunidades mas activas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2>';
        $comunidades_top = $wpdb->get_results(
            "SELECT id, nombre, tipo, categoria, miembros_count
             FROM $tabla_comunidades
             WHERE estado = 'activa'
             ORDER BY miembros_count DESC
             LIMIT 5"
        );

        if ($comunidades_top) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($comunidades_top as $comunidad) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($comunidad->nombre) . '</strong></td>';
                echo '<td>' . esc_html(ucfirst($comunidad->tipo)) . '</td>';
                echo '<td>' . esc_html(ucfirst($comunidad->categoria)) . '</td>';
                echo '<td>' . esc_html($comunidad->miembros_count) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay comunidades activas todavia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la pagina de listado de comunidades
     */
    public function render_admin_listado() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        // Determinar accion actual
        $accion_actual = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : 'listar';
        $comunidad_id = isset($_GET['id']) ? absint($_GET['id']) : 0;

        // Manejar diferentes acciones
        switch ($accion_actual) {
            case 'nueva':
                $this->render_admin_formulario_comunidad();
                return;

            case 'editar':
                if ($comunidad_id > 0) {
                    $this->render_admin_formulario_comunidad($comunidad_id);
                    return;
                }
                break;

            case 'ver':
                if ($comunidad_id > 0) {
                    $this->render_admin_detalle_comunidad($comunidad_id);
                    return;
                }
                break;
        }

        // Vista de listado por defecto
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestion de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado&accion=nueva'), 'class' => 'button-primary'],
        ]);

        // Tabs de filtro (usar 'tab' que es lo que genera render_page_tabs)
        $estado_filtro = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'activa';
        $this->render_page_tabs([
            ['slug' => 'activa', 'label' => __('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'pausada', 'label' => __('Pausadas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'archivada', 'label' => __('Archivadas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'todas', 'label' => __('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN)],
        ], $estado_filtro);

        // Paginacion
        $pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $por_pagina = 20;
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Contar total
        $condicion_estado = ($estado_filtro !== 'todas') ? $wpdb->prepare("WHERE estado = %s", $estado_filtro) : "";
        $total_comunidades = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades $condicion_estado");
        $total_paginas = ceil($total_comunidades / $por_pagina);

        // Consulta de comunidades con paginacion
        $comunidades = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, creador_id, nombre, tipo, categoria, miembros_count, estado, created_at
                 FROM $tabla_comunidades
                 $condicion_estado
                 ORDER BY created_at DESC LIMIT %d OFFSET %d",
                $por_pagina,
                $offset
            )
        );

        if ($comunidades) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Creada', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($comunidades as $comunidad) {
                $creador = get_userdata($comunidad->creador_id);
                $nombre_creador = $creador ? $creador->display_name : __('Desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                echo '<tr>';
                echo '<td><strong>' . esc_html($comunidad->nombre) . '</strong><br><small>' . esc_html($nombre_creador) . '</small></td>';
                echo '<td>' . esc_html(ucfirst($comunidad->tipo)) . '</td>';
                echo '<td>' . esc_html(ucfirst($comunidad->categoria)) . '</td>';
                echo '<td>' . esc_html($comunidad->miembros_count) . '</td>';
                echo '<td><span class="status-' . esc_attr($comunidad->estado) . '">' . esc_html(ucfirst($comunidad->estado)) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($comunidad->created_at))) . '</td>';
                echo '<td>';
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-listado&accion=ver&id=' . $comunidad->id)) . '" class="button button-small">' . __('Ver', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a> ';
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-listado&accion=editar&id=' . $comunidad->id)) . '" class="button button-small">' . __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';

            // Paginacion
            if ($total_paginas > 1) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo '<span class="displaying-num">' . sprintf(__('%d elementos', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_comunidades) . '</span>';
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $pagina_actual,
                    'total' => $total_paginas,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                echo '</div></div>';
            }
        } else {
            echo '<p>' . __('No hay comunidades en este estado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza el formulario para crear/editar comunidad
     *
     * @param int $comunidad_id ID de la comunidad (0 para nueva)
     */
    private function render_admin_formulario_comunidad($comunidad_id = 0) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidad = null;
        $es_edicion = false;
        $mensaje_exito = '';
        $mensaje_error = '';

        // Cargar comunidad existente si es edicion
        if ($comunidad_id > 0) {
            $comunidad = $wpdb->get_row($wpdb->prepare(
                "SELECT id, nombre, descripcion, tipo, categoria, ubicacion, reglas, estado, imagen_portada
                 FROM $tabla_comunidades
                 WHERE id = %d",
                $comunidad_id
            ));
            if (!$comunidad) {
                echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div></div>';
                return;
            }
            $es_edicion = true;
        }

        // Procesar formulario
        if (isset($_POST['guardar_comunidad']) && isset($_POST['comunidad_nonce'])) {
            if (wp_verify_nonce($_POST['comunidad_nonce'], 'guardar_comunidad_admin')) {
                $datos_comunidad = [
                    'nombre' => sanitize_text_field($_POST['nombre'] ?? ''),
                    'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
                    'tipo' => sanitize_text_field($_POST['tipo'] ?? 'abierta'),
                    'categoria' => sanitize_text_field($_POST['categoria'] ?? 'otros'),
                    'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
                    'reglas' => sanitize_textarea_field($_POST['reglas'] ?? ''),
                    'estado' => sanitize_text_field($_POST['estado'] ?? 'activa'),
                ];

                // Validar nombre
                if (empty($datos_comunidad['nombre'])) {
                    $mensaje_error = __('El nombre de la comunidad es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                } else {
                    if ($es_edicion) {
                        // Actualizar
                        $datos_comunidad['updated_at'] = current_time('mysql');
                        $resultado = $wpdb->update($tabla_comunidades, $datos_comunidad, ['id' => $comunidad_id]);
                        if ($resultado !== false) {
                            $mensaje_exito = __('Comunidad actualizada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            $comunidad = (object) array_merge((array) $comunidad, $datos_comunidad);
                        } else {
                            $mensaje_error = __('Error al actualizar la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                    } else {
                        // Crear nueva
                        $datos_comunidad['creador_id'] = get_current_user_id();
                        $datos_comunidad['slug'] = sanitize_title($datos_comunidad['nombre']);
                        $datos_comunidad['miembros_count'] = 1;
                        $datos_comunidad['created_at'] = current_time('mysql');

                        $resultado = $wpdb->insert($tabla_comunidades, $datos_comunidad);
                        if ($resultado) {
                            $nuevo_id = $wpdb->insert_id;

                            // Agregar creador como admin
                            $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
                            $wpdb->insert($tabla_miembros, [
                                'comunidad_id' => $nuevo_id,
                                'user_id' => get_current_user_id(),
                                'rol' => 'admin',
                                'estado' => 'activo',
                                'joined_at' => current_time('mysql'),
                            ]);

                            wp_redirect(admin_url('admin.php?page=comunidades-listado&accion=editar&id=' . $nuevo_id . '&mensaje=creada'));
                            exit;
                        } else {
                            $mensaje_error = __('Error al crear la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                    }
                }
            } else {
                $mensaje_error = __('Error de seguridad. Recarga la pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
        }

        // Mensaje de creacion exitosa
        if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'creada') {
            $mensaje_exito = __('Comunidad creada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        // Obtener categorias disponibles
        $categorias = $this->get_setting('categorias_predeterminadas', []);

        echo '<div class="wrap flavor-modulo-page">';
        $titulo = $es_edicion ? __('Editar Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $this->render_page_header($titulo, [
            ['label' => __('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado'), 'class' => ''],
        ]);

        // Mostrar mensajes
        if ($mensaje_exito) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($mensaje_exito) . '</p></div>';
        }
        if ($mensaje_error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($mensaje_error) . '</p></div>';
        }

        echo '<form method="post" action="">';
        wp_nonce_field('guardar_comunidad_admin', 'comunidad_nonce');

        echo '<table class="form-table">';

        // Nombre
        echo '<tr><th scope="row"><label for="nombre">' . __('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' *</label></th>';
        echo '<td><input type="text" id="nombre" name="nombre" value="' . esc_attr($comunidad->nombre ?? '') . '" class="regular-text" required></td></tr>';

        // Descripcion
        echo '<tr><th scope="row"><label for="descripcion">' . __('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><textarea id="descripcion" name="descripcion" rows="4" class="large-text">' . esc_textarea($comunidad->descripcion ?? '') . '</textarea></td></tr>';

        // Tipo
        echo '<tr><th scope="row"><label for="tipo">' . __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><select id="tipo" name="tipo">';
        $tipos = ['abierta' => __('Abierta', FLAVOR_PLATFORM_TEXT_DOMAIN), 'cerrada' => __('Cerrada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'secreta' => __('Secreta', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        foreach ($tipos as $valor => $etiqueta) {
            $seleccionado = ($comunidad->tipo ?? 'abierta') === $valor ? 'selected' : '';
            echo '<option value="' . esc_attr($valor) . '" ' . $seleccionado . '>' . esc_html($etiqueta) . '</option>';
        }
        echo '</select></td></tr>';

        // Categoria
        echo '<tr><th scope="row"><label for="categoria">' . __('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><select id="categoria" name="categoria">';
        foreach ($categorias as $slug => $etiqueta) {
            $seleccionado = ($comunidad->categoria ?? 'otros') === $slug ? 'selected' : '';
            echo '<option value="' . esc_attr($slug) . '" ' . $seleccionado . '>' . esc_html($etiqueta) . '</option>';
        }
        echo '</select></td></tr>';

        // Ubicacion
        echo '<tr><th scope="row"><label for="ubicacion">' . __('Ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="text" id="ubicacion" name="ubicacion" value="' . esc_attr($comunidad->ubicacion ?? '') . '" class="regular-text"></td></tr>';

        // Estado (solo en edicion)
        if ($es_edicion) {
            echo '<tr><th scope="row"><label for="estado">' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
            echo '<td><select id="estado" name="estado">';
            $estados = ['activa' => __('Activa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'pausada' => __('Pausada', FLAVOR_PLATFORM_TEXT_DOMAIN), 'archivada' => __('Archivada', FLAVOR_PLATFORM_TEXT_DOMAIN)];
            foreach ($estados as $valor => $etiqueta) {
                $seleccionado = ($comunidad->estado ?? 'activa') === $valor ? 'selected' : '';
                echo '<option value="' . esc_attr($valor) . '" ' . $seleccionado . '>' . esc_html($etiqueta) . '</option>';
            }
            echo '</select></td></tr>';
        }

        // Reglas
        echo '<tr><th scope="row"><label for="reglas">' . __('Reglas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><textarea id="reglas" name="reglas" rows="4" class="large-text">' . esc_textarea($comunidad->reglas ?? '') . '</textarea>';
        echo '<p class="description">' . __('Reglas de la comunidad que los miembros deben aceptar.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></td></tr>';

        echo '</table>';

        echo '<p class="submit">';
        echo '<input type="submit" name="guardar_comunidad" class="button button-primary" value="' . ($es_edicion ? __('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '">';
        echo '</p>';
        echo '</form>';

        echo '</div>';
    }

    /**
     * Renderiza la vista de detalle de una comunidad
     *
     * @param int $comunidad_id ID de la comunidad
     */
    private function render_admin_detalle_comunidad($comunidad_id) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, creador_id, nombre, descripcion, tipo, categoria, ubicacion, reglas, estado, miembros_count, created_at
             FROM $tabla_comunidades
             WHERE id = %d",
            $comunidad_id
        ));

        if (!$comunidad) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div></div>';
            return;
        }

        // Obtener estadisticas
        $total_miembros = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND estado = 'activo'",
            $comunidad_id
        ));
        $total_publicaciones = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_actividad WHERE comunidad_id = %d",
            $comunidad_id
        ));
        $pendientes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND estado = 'pendiente'",
            $comunidad_id
        ));

        // Ultimos miembros
        $ultimos_miembros = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.comunidad_id = %d
             ORDER BY m.joined_at DESC
             LIMIT 10",
            $comunidad_id
        ));

        // Creador
        $creador = get_userdata($comunidad->creador_id);
        $nombre_creador = $creador ? $creador->display_name : __('Desconocido', FLAVOR_PLATFORM_TEXT_DOMAIN);

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(esc_html($comunidad->nombre), [
            ['label' => __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado&accion=editar&id=' . $comunidad_id), 'class' => 'button-primary'],
            ['label' => __('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado'), 'class' => ''],
        ]);

        // Estadisticas
        echo '<div class="flavor-stats-grid" style="margin-bottom: 20px;">';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_miembros) . '</span><span class="stat-label">' . __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        echo '<div class="flavor-stat-card"><span class="stat-number">' . esc_html($total_publicaciones) . '</span><span class="stat-label">' . __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        if ($pendientes > 0) {
            echo '<div class="flavor-stat-card flavor-stat-warning"><span class="stat-number">' . esc_html($pendientes) . '</span><span class="stat-label">' . __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</span></div>';
        }
        echo '</div>';

        // Informacion de la comunidad
        echo '<div class="postbox" style="margin-bottom: 20px;">';
        echo '<div class="postbox-header"><h2>' . __('Informacion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2></div>';
        echo '<div class="inside">';
        echo '<table class="form-table">';
        echo '<tr><th>' . __('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html($comunidad->descripcion ?: '-') . '</td></tr>';
        echo '<tr><th>' . __('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html(ucfirst($comunidad->tipo)) . '</td></tr>';
        echo '<tr><th>' . __('Categoria', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html(ucfirst($comunidad->categoria)) . '</td></tr>';
        echo '<tr><th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><span class="status-' . esc_attr($comunidad->estado) . '">' . esc_html(ucfirst($comunidad->estado)) . '</span></td></tr>';
        echo '<tr><th>' . __('Ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html($comunidad->ubicacion ?: '-') . '</td></tr>';
        echo '<tr><th>' . __('Creador', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html($nombre_creador) . '</td></tr>';
        echo '<tr><th>' . __('Fecha creacion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($comunidad->created_at))) . '</td></tr>';
        echo '</table>';
        echo '</div></div>';

        // Lista de miembros
        echo '<div class="postbox">';
        echo '<div class="postbox-header"><h2>' . __('Ultimos Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2></div>';
        echo '<div class="inside">';
        if ($ultimos_miembros) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>' . __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><th>' . __('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th></tr></thead>';
            echo '<tbody>';
            foreach ($ultimos_miembros as $miembro) {
                echo '<tr>';
                echo '<td>' . esc_html($miembro->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</td>';
                echo '<td>' . esc_html(ucfirst($miembro->rol)) . '</td>';
                echo '<td><span class="status-' . esc_attr($miembro->estado) . '">' . esc_html(ucfirst($miembro->estado)) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($miembro->joined_at))) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('No hay miembros todavia.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }
        echo '</div></div>';

        echo '</div>';
    }

    /**
     * Renderiza la pagina de gestion de miembros
     */
    public function render_admin_miembros() {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        // Determinar accion actual
        $accion_actual = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : 'listar';
        $miembro_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
        $mensaje_exito = '';
        $mensaje_error = '';

        // Mensaje de expulsion exitosa
        if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'expulsado') {
            $mensaje_exito = __('Miembro expulsado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        // Procesar accion de aprobar
        if ($accion_actual === 'aprobar' && $miembro_id > 0) {
            if (wp_verify_nonce($_GET['_wpnonce'] ?? '', 'aprobar_miembro')) {
                $miembro_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT comunidad_id, estado FROM $tabla_miembros WHERE id = %d",
                    $miembro_id
                ));
                if ($miembro_data && $miembro_data->estado === 'pendiente') {
                    $resultado = $wpdb->update(
                        $tabla_miembros,
                        ['estado' => 'activo'],
                        ['id' => $miembro_id]
                    );
                    if ($resultado !== false) {
                        // Actualizar contador de miembros en la comunidad
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1 WHERE id = %d",
                            $miembro_data->comunidad_id
                        ));
                        $mensaje_exito = __('Miembro aprobado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    } else {
                        $mensaje_error = __('Error al aprobar el miembro.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                } else {
                    $mensaje_error = __('Miembro no encontrado o ya aprobado.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
            } else {
                $mensaje_error = __('Error de seguridad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
        }

        // Vista de gestion de miembro individual
        if ($accion_actual === 'gestionar' && $miembro_id > 0) {
            $this->render_admin_gestionar_miembro($miembro_id);
            return;
        }

        // Vista de listado por defecto
        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestion de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN));

        // Mostrar mensajes
        if ($mensaje_exito) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($mensaje_exito) . '</p></div>';
        }
        if ($mensaje_error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($mensaje_error) . '</p></div>';
        }

        // Tabs de filtro (usar 'tab' que es lo que genera render_page_tabs)
        $estado_filtro = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'todos';
        $pendientes_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros WHERE estado = 'pendiente'");

        $this->render_page_tabs([
            ['slug' => 'todos', 'label' => __('Todos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'activo', 'label' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'pendiente', 'label' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'badge' => $pendientes_count],
            ['slug' => 'suspendido', 'label' => __('Suspendidos', FLAVOR_PLATFORM_TEXT_DOMAIN)],
            ['slug' => 'baneado', 'label' => __('Baneados', FLAVOR_PLATFORM_TEXT_DOMAIN)],
        ], $estado_filtro);

        // Paginacion
        $pagina_actual = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $por_pagina = 50;
        $offset = ($pagina_actual - 1) * $por_pagina;

        // Contar total
        $condicion_estado = ($estado_filtro !== 'todos') ? $wpdb->prepare("WHERE m.estado = %s", $estado_filtro) : "";
        $total_miembros = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_miembros m $condicion_estado");
        $total_paginas = ceil($total_miembros / $por_pagina);

        // Consulta de miembros con paginacion
        $miembros = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT m.*, c.nombre as comunidad_nombre, u.display_name, u.user_email
                 FROM $tabla_miembros m
                 LEFT JOIN $tabla_comunidades c ON m.comunidad_id = c.id
                 LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
                 $condicion_estado
                 ORDER BY m.joined_at DESC
                 LIMIT %d OFFSET %d",
                $por_pagina,
                $offset
            )
        );

        if ($miembros) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr>';
            echo '<th>' . __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Fecha Union', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '<th>' . __('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
            echo '</tr></thead>';
            echo '<tbody>';
            foreach ($miembros as $miembro) {
                echo '<tr>';
                echo '<td><strong>' . esc_html($miembro->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</strong><br><small>' . esc_html($miembro->user_email) . '</small></td>';
                echo '<td>' . esc_html($miembro->comunidad_nombre) . '</td>';
                echo '<td>' . esc_html(ucfirst($miembro->rol)) . '</td>';
                echo '<td><span class="status-' . esc_attr($miembro->estado) . '">' . esc_html(ucfirst($miembro->estado)) . '</span></td>';
                echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($miembro->joined_at))) . '</td>';
                echo '<td>';
                if ($miembro->estado === 'pendiente') {
                    echo '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?page=comunidades-miembros&accion=aprobar&id=' . $miembro->id), 'aprobar_miembro')) . '" class="button button-small button-primary">' . __('Aprobar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a> ';
                }
                echo '<a href="' . esc_url(admin_url('admin.php?page=comunidades-miembros&accion=gestionar&id=' . $miembro->id)) . '" class="button button-small">' . __('Gestionar', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';

            // Paginacion
            if ($total_paginas > 1) {
                echo '<div class="tablenav bottom"><div class="tablenav-pages">';
                echo '<span class="displaying-num">' . sprintf(__('%d miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_miembros) . '</span>';
                echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $pagina_actual,
                    'total' => $total_paginas,
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                ]);
                echo '</div></div>';
            }
        } else {
            echo '<p>' . __('No hay miembros con este filtro.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renderiza la vista de gestion de un miembro individual
     *
     * @param int $miembro_id ID del miembro
     */
    private function render_admin_gestionar_miembro($miembro_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $miembro = $wpdb->get_row($wpdb->prepare(
            "SELECT m.*, c.nombre as comunidad_nombre, c.id as comunidad_id, u.display_name, u.user_email
             FROM $tabla_miembros m
             LEFT JOIN $tabla_comunidades c ON m.comunidad_id = c.id
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE m.id = %d",
            $miembro_id
        ));

        if (!$miembro) {
            echo '<div class="wrap"><div class="notice notice-error"><p>' . __('Miembro no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div></div>';
            return;
        }

        $mensaje_exito = '';
        $mensaje_error = '';

        // Procesar formulario de actualizacion
        if (isset($_POST['actualizar_miembro']) && isset($_POST['miembro_nonce'])) {
            if (wp_verify_nonce($_POST['miembro_nonce'], 'actualizar_miembro_admin')) {
                $nuevo_rol = sanitize_text_field($_POST['rol'] ?? 'miembro');
                $nuevo_estado = sanitize_text_field($_POST['estado'] ?? 'activo');

                // Validar valores
                $roles_validos = ['admin', 'moderador', 'miembro'];
                $estados_validos = ['activo', 'pendiente', 'suspendido', 'baneado'];

                if (!in_array($nuevo_rol, $roles_validos)) {
                    $nuevo_rol = 'miembro';
                }
                if (!in_array($nuevo_estado, $estados_validos)) {
                    $nuevo_estado = 'activo';
                }

                $resultado = $wpdb->update(
                    $tabla_miembros,
                    ['rol' => $nuevo_rol, 'estado' => $nuevo_estado],
                    ['id' => $miembro_id]
                );

                if ($resultado !== false) {
                    $mensaje_exito = __('Miembro actualizado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    $miembro->rol = $nuevo_rol;
                    $miembro->estado = $nuevo_estado;
                } else {
                    $mensaje_error = __('Error al actualizar el miembro.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
            } else {
                $mensaje_error = __('Error de seguridad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
        }

        // Procesar expulsion
        if (isset($_POST['expulsar_miembro']) && isset($_POST['expulsar_nonce'])) {
            if (wp_verify_nonce($_POST['expulsar_nonce'], 'expulsar_miembro_admin')) {
                $resultado = $wpdb->delete($tabla_miembros, ['id' => $miembro_id]);
                if ($resultado) {
                    // Actualizar contador
                    if ($miembro->estado === 'activo') {
                        $wpdb->query($wpdb->prepare(
                            "UPDATE $tabla_comunidades SET miembros_count = GREATEST(0, miembros_count - 1) WHERE id = %d",
                            $miembro->comunidad_id
                        ));
                    }
                    wp_redirect(admin_url('admin.php?page=comunidades-miembros&mensaje=expulsado'));
                    exit;
                } else {
                    $mensaje_error = __('Error al expulsar el miembro.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                }
            }
        }

        echo '<div class="wrap flavor-modulo-page">';
        $this->render_page_header(__('Gestionar Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN), [
            ['label' => __('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-miembros'), 'class' => ''],
        ]);

        // Mostrar mensajes
        if ($mensaje_exito) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($mensaje_exito) . '</p></div>';
        }
        if ($mensaje_error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($mensaje_error) . '</p></div>';
        }

        // Informacion del miembro
        echo '<div class="postbox" style="margin-bottom: 20px;">';
        echo '<div class="postbox-header"><h2>' . __('Informacion del Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2></div>';
        echo '<div class="inside">';
        echo '<table class="form-table">';
        echo '<tr><th>' . __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><strong>' . esc_html($miembro->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</strong></td></tr>';
        echo '<tr><th>' . __('Email', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html($miembro->user_email) . '</td></tr>';
        echo '<tr><th>' . __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td><a href="' . esc_url(admin_url('admin.php?page=comunidades-listado&accion=ver&id=' . $miembro->comunidad_id)) . '">' . esc_html($miembro->comunidad_nombre) . '</a></td></tr>';
        echo '<tr><th>' . __('Fecha de union', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th><td>' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($miembro->joined_at))) . '</td></tr>';
        echo '</table>';
        echo '</div></div>';

        // Formulario de edicion
        echo '<div class="postbox" style="margin-bottom: 20px;">';
        echo '<div class="postbox-header"><h2>' . __('Modificar Rol y Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2></div>';
        echo '<div class="inside">';
        echo '<form method="post" action="">';
        wp_nonce_field('actualizar_miembro_admin', 'miembro_nonce');

        echo '<table class="form-table">';

        // Rol
        echo '<tr><th scope="row"><label for="rol">' . __('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><select id="rol" name="rol">';
        $roles = ['miembro' => __('Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'moderador' => __('Moderador', FLAVOR_PLATFORM_TEXT_DOMAIN), 'admin' => __('Admin', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        foreach ($roles as $valor => $etiqueta) {
            $seleccionado = $miembro->rol === $valor ? 'selected' : '';
            echo '<option value="' . esc_attr($valor) . '" ' . $seleccionado . '>' . esc_html($etiqueta) . '</option>';
        }
        echo '</select></td></tr>';

        // Estado
        echo '<tr><th scope="row"><label for="estado">' . __('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><select id="estado" name="estado">';
        $estados = ['activo' => __('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'pendiente' => __('Pendiente', FLAVOR_PLATFORM_TEXT_DOMAIN), 'suspendido' => __('Suspendido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'baneado' => __('Baneado', FLAVOR_PLATFORM_TEXT_DOMAIN)];
        foreach ($estados as $valor => $etiqueta) {
            $seleccionado = $miembro->estado === $valor ? 'selected' : '';
            echo '<option value="' . esc_attr($valor) . '" ' . $seleccionado . '>' . esc_html($etiqueta) . '</option>';
        }
        echo '</select></td></tr>';

        echo '</table>';

        echo '<p class="submit"><input type="submit" name="actualizar_miembro" class="button button-primary" value="' . __('Guardar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"></p>';
        echo '</form>';
        echo '</div></div>';

        // Zona peligrosa - Expulsar
        echo '<div class="postbox" style="border-color: #dc3232;">';
        echo '<div class="postbox-header" style="background: #fef1f1;"><h2 style="color: #dc3232;">' . __('Zona Peligrosa', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h2></div>';
        echo '<div class="inside">';
        echo '<p>' . __('Expulsar al miembro lo eliminara permanentemente de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        echo '<form method="post" action="" class="com-expulsar-miembro-form">';
        wp_nonce_field('expulsar_miembro_admin', 'expulsar_nonce');
        echo '<input type="submit" name="expulsar_miembro" class="button button-link-delete" value="' . __('Expulsar Miembro', FLAVOR_PLATFORM_TEXT_DOMAIN) . '">';
        echo '</form>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll(".com-expulsar-miembro-form").forEach(function(form) {
                form.addEventListener("submit", function(e) {
                    e.preventDefault();
                    var notice = document.createElement("div");
                    notice.className = "notice notice-warning";
                    notice.innerHTML = "<p>' . esc_js(__('Estas seguro de expulsar a este miembro?', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</p><p style=\"display:flex;gap:8px;margin-top:8px;\"><button type=\"button\" class=\"button button-primary com-expulsar-confirmar\">' . esc_js(__('Confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</button><button type=\"button\" class=\"button com-expulsar-cancelar\">' . esc_js(__('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN)) . '</button></p>";
                    var current = form.parentNode.querySelector(".notice.notice-warning");
                    if (current) current.remove();
                    form.parentNode.insertBefore(notice, form);
                    notice.querySelector(".com-expulsar-confirmar").addEventListener("click", function() {
                        notice.remove();
                        form.submit();
                    });
                    notice.querySelector(".com-expulsar-cancelar").addEventListener("click", function() {
                        notice.remove();
                    });
                });
            });
        });
        </script>';
        echo '</div></div>';

        echo '</div>';
    }

    /**
     * Renderiza la pagina de configuracion
     */
    public function render_admin_config() {
        // Procesar guardado de configuracion
        $mensaje_exito = '';
        $mensaje_error = '';

        if (isset($_POST['guardar_config']) && isset($_POST['comunidades_config_nonce'])) {
            if (wp_verify_nonce($_POST['comunidades_config_nonce'], 'guardar_config_comunidades')) {
                $nueva_configuracion = [
                    'maximo_comunidades_por_usuario' => isset($_POST['maximo_comunidades_por_usuario'])
                        ? absint($_POST['maximo_comunidades_por_usuario'])
                        : 10,
                    'requiere_aprobacion_creacion' => isset($_POST['requiere_aprobacion_creacion']),
                    'permitir_comunidades_secretas' => isset($_POST['permitir_comunidades_secretas']),
                    'categorias_predeterminadas' => $this->get_setting('categorias_predeterminadas', []),
                ];

                // Guardar en la base de datos
                update_option('flavor_chat_ia_module_comunidades', $nueva_configuracion);

                // Recargar configuracion
                $this->settings = wp_parse_args($nueva_configuracion, $this->get_default_settings());

                $mensaje_exito = __('Configuracion guardada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            } else {
                $mensaje_error = __('Error de seguridad. Recarga la pagina e intenta de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }
        }

        echo '<div class="wrap flavor-modulo-page">';

        // Migas de pan
        ?>
        <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
            <a href="<?php echo admin_url('admin.php?page=comunidades-dashboard'); ?>" style="color: #2271b1; text-decoration: none;">
                <span class="dashicons dashicons-groups" style="font-size: 14px; vertical-align: middle;"></span>
                <?php _e('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
            <span style="color: #646970; margin: 0 5px;">›</span>
            <span style="color: #1d2327;"><?php _e('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </nav>
        <?php

        $this->render_page_header(__('Configuracion de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN));

        // Mostrar mensajes
        if ($mensaje_exito) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($mensaje_exito) . '</p></div>';
        }
        if ($mensaje_error) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($mensaje_error) . '</p></div>';
        }

        // Obtener configuracion actual
        $configuracion = $this->get_settings();

        echo '<form method="post" action="">';
        wp_nonce_field('guardar_config_comunidades', 'comunidades_config_nonce');

        echo '<table class="form-table">';

        // Maximo comunidades por usuario
        echo '<tr>';
        echo '<th scope="row"><label for="maximo_comunidades">' . __('Max. comunidades por usuario', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></th>';
        echo '<td><input type="number" id="maximo_comunidades" name="maximo_comunidades_por_usuario" value="' . esc_attr($configuracion['maximo_comunidades_por_usuario']) . '" min="1" max="100" class="small-text"></td>';
        echo '</tr>';

        // Requiere aprobacion para crear
        echo '<tr>';
        echo '<th scope="row">' . __('Aprobacion para crear', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
        echo '<td><label><input type="checkbox" name="requiere_aprobacion_creacion" value="1" ' . checked($configuracion['requiere_aprobacion_creacion'], true, false) . '> ' . __('Requiere aprobacion de admin para crear comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td>';
        echo '</tr>';

        // Permitir comunidades secretas
        echo '<tr>';
        echo '<th scope="row">' . __('Comunidades secretas', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</th>';
        echo '<td><label><input type="checkbox" name="permitir_comunidades_secretas" value="1" ' . checked($configuracion['permitir_comunidades_secretas'], true, false) . '> ' . __('Permitir crear comunidades secretas (solo por invitacion)', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</label></td>';
        echo '</tr>';

        echo '</table>';

        echo '<p class="submit"><input type="submit" name="guardar_config" class="button button-primary" value="' . __('Guardar Configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '"></p>';
        echo '</form>';

        echo '</div>';
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        if (!Flavor_Platform_Helpers::tabla_existe($tabla_comunidades)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo de comunidades
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $sql_comunidades = "CREATE TABLE IF NOT EXISTS $tabla_comunidades (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            slug varchar(200) DEFAULT NULL,
            descripcion text DEFAULT NULL,
            imagen varchar(255) DEFAULT NULL,
            tipo enum('abierta','cerrada','secreta') DEFAULT 'abierta',
            categoria varchar(100) DEFAULT 'otros',
            ubicacion varchar(200) DEFAULT NULL,
            reglas text DEFAULT NULL,
            miembros_count int(11) DEFAULT 0,
            creador_id bigint(20) unsigned NOT NULL,
            estado enum('activa','pausada','archivada') DEFAULT 'activa',
            configuracion text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY creador_id (creador_id),
            KEY tipo (tipo),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY slug (slug)
        ) $charset_collate;";

        $sql_miembros = "CREATE TABLE IF NOT EXISTS $tabla_miembros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            rol enum('admin','moderador','miembro') DEFAULT 'miembro',
            estado enum('activo','pendiente','suspendido','baneado') DEFAULT 'activo',
            joined_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comunidad_usuario (comunidad_id, user_id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY rol (rol),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_actividad = "CREATE TABLE IF NOT EXISTS $tabla_actividad (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            tipo enum('publicacion','evento','anuncio','encuesta') DEFAULT 'publicacion',
            titulo varchar(255) DEFAULT NULL,
            contenido longtext DEFAULT NULL,
            adjuntos text DEFAULT NULL,
            reacciones_count int(11) DEFAULT 0,
            comentarios_count int(11) DEFAULT 0,
            es_fijado tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY tipo (tipo),
            KEY es_fijado (es_fijado),
            KEY created_at (created_at)
        ) $charset_collate;";

        $tabla_reacciones = $wpdb->prefix . 'flavor_comunidades_actividad_reacciones';
        $sql_reacciones = "CREATE TABLE IF NOT EXISTS $tabla_reacciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            actividad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            tipo enum('like') DEFAULT 'like',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY actividad_usuario (actividad_id, user_id),
            KEY actividad_id (actividad_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // Tabla de anuncios inter-comunidades
        $tabla_anuncios = $wpdb->prefix . 'flavor_comunidades_anuncios';
        $sql_anuncios = "CREATE TABLE IF NOT EXISTS $tabla_anuncios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            titulo varchar(200) NOT NULL,
            contenido text NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            destacado tinyint(1) DEFAULT 0,
            compartir_red tinyint(1) DEFAULT 0,
            fecha_expiracion date DEFAULT NULL,
            estado enum('borrador','publicado','archivado') DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY destacado (destacado),
            KEY fecha_expiracion (fecha_expiracion)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_comunidades);
        dbDelta($sql_miembros);
        dbDelta($sql_actividad);
        dbDelta($sql_reacciones);
        dbDelta($sql_anuncios);

        // Añadir columna referencia_original si no existe (para cross-posting)
        $columnas_actividad = $wpdb->get_col("SHOW COLUMNS FROM $tabla_actividad");
        if (!in_array('referencia_original', $columnas_actividad)) {
            $wpdb->query("ALTER TABLE $tabla_actividad ADD COLUMN referencia_original bigint(20) unsigned DEFAULT NULL AFTER contenido");
            $wpdb->query("ALTER TABLE $tabla_actividad ADD INDEX idx_referencia (referencia_original)");
        }
        if (!in_array('imagen', $columnas_actividad)) {
            $wpdb->query("ALTER TABLE $tabla_actividad ADD COLUMN imagen varchar(500) DEFAULT NULL AFTER adjuntos");
        }
        if (!in_array('metadata', $columnas_actividad)) {
            $wpdb->query("ALTER TABLE $tabla_actividad ADD COLUMN metadata longtext DEFAULT NULL");
        }

        // Añadir columna slug si no existe
        $columnas_comunidades = $wpdb->get_col("SHOW COLUMNS FROM $tabla_comunidades");
        if (!in_array('slug', $columnas_comunidades)) {
            $wpdb->query("ALTER TABLE $tabla_comunidades ADD COLUMN slug varchar(200) DEFAULT NULL AFTER nombre");
            $wpdb->query("ALTER TABLE $tabla_comunidades ADD INDEX idx_slug (slug)");
            // Generar slugs para comunidades existentes
            $comunidades_sin_slug = $wpdb->get_results("SELECT id, nombre FROM $tabla_comunidades WHERE slug IS NULL OR slug = ''");
            foreach ($comunidades_sin_slug as $comunidad) {
                $slug = sanitize_title($comunidad->nombre);
                $wpdb->update($tabla_comunidades, ['slug' => $slug], ['id' => $comunidad->id]);
            }
        }

        // Insertar datos de ejemplo si las tablas estan vacias
        if ((int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_comunidades") === 0) {
            $this->insertar_datos_ejemplo();
        }
    }

    /**
     * Inserta datos de ejemplo para el modulo de comunidades
     */
    private function insertar_datos_ejemplo() {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $usuarios_admin = get_users(['role' => 'administrator', 'number' => 1]);
        $creador_id     = !empty($usuarios_admin) ? $usuarios_admin[0]->ID : 1;
        $fecha_actual   = current_time('mysql');

        $comunidades_ejemplo = [
            [
                'nombre'      => 'Huertos Urbanos del Barrio',
                'descripcion' => 'Comunidad para compartir experiencias, consejos y semillas entre los hortelanos del barrio. Organizamos jornadas de plantacion y talleres.',
                'tipo'        => 'abierta',
                'categoria'   => 'medioambiente',
                'ubicacion'   => 'Huerto comunitario - Plaza Central',
                'reglas'       => 'Respeto mutuo. Compartir es clave. Cuidamos la tierra juntos.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Club de Lectura Local',
                'descripcion' => 'Nos reunimos mensualmente para comentar libros. Cada mes un miembro elige la lectura. Todos los generos son bienvenidos.',
                'tipo'        => 'abierta',
                'categoria'   => 'cultura',
                'ubicacion'   => 'Biblioteca Municipal',
                'reglas'       => 'Lectura obligatoria antes del encuentro. Respetamos todas las opiniones.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Runners del Parque',
                'descripcion' => 'Grupo de corredores de todos los niveles. Quedamos 3 veces por semana para entrenar juntos. Principiantes bienvenidos.',
                'tipo'        => 'abierta',
                'categoria'   => 'deportes',
                'ubicacion'   => 'Parque Municipal',
                'reglas'       => 'Respeta tu ritmo y el de los demas. Puntualidad en las quedadas.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Desarrolladores Web Local',
                'descripcion' => 'Comunidad para desarrolladores web del barrio. Compartimos recursos, hacemos pair programming y organizamos hackathons.',
                'tipo'        => 'cerrada',
                'categoria'   => 'tecnologia',
                'ubicacion'   => 'Coworking Central',
                'reglas'       => 'Codigo de conducta: inclusividad, respeto y colaboracion.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Padres y Madres Activos',
                'descripcion' => 'Espacio para familias del barrio. Organizamos actividades para los ninos, compartimos recursos y nos apoyamos mutuamente.',
                'tipo'        => 'abierta',
                'categoria'   => 'vecinal',
                'ubicacion'   => 'Centro Civico',
                'reglas'       => 'Entorno seguro para familias. No se comparte informacion de menores en redes.',
                'miembros_count' => 1,
            ],
            [
                'nombre'      => 'Meditacion y Mindfulness',
                'descripcion' => 'Grupo de practica de meditacion y mindfulness. Sesiones guiadas para principiantes y avanzados.',
                'tipo'        => 'abierta',
                'categoria'   => 'salud',
                'ubicacion'   => 'Sala Polivalente - Centro Civico',
                'reglas'       => 'Silencio durante las sesiones. Respeto al espacio compartido.',
                'miembros_count' => 1,
            ],
        ];

        foreach ($comunidades_ejemplo as $comunidad_datos) {
            $configuracion_predeterminada = wp_json_encode([
                'allow_posts'       => true,
                'require_approval'  => false,
                'allow_events'      => true,
                'allow_polls'       => true,
            ]);

            $wpdb->insert(
                $tabla_comunidades,
                array_merge($comunidad_datos, [
                    'creador_id'    => $creador_id,
                    'estado'        => 'activa',
                    'configuracion' => $configuracion_predeterminada,
                    'created_at'    => $fecha_actual,
                    'updated_at'    => $fecha_actual,
                ]),
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
            );

            $comunidad_id = $wpdb->insert_id;

            // Registrar al creador como admin de la comunidad
            $wpdb->insert(
                $tabla_miembros,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $creador_id,
                    'rol'          => 'admin',
                    'estado'       => 'activo',
                    'joined_at'    => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );

            // Crear una publicacion de bienvenida
            $wpdb->insert(
                $tabla_actividad,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $creador_id,
                    'tipo'         => 'anuncio',
                    'titulo'       => 'Bienvenidos a ' . $comunidad_datos['nombre'],
                    'contenido'    => 'Esta comunidad acaba de crearse. Invita a tus amigos y comienza a participar.',
                    'created_at'   => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s', '%s']
            );
        }
    }

    // =========================================================================
    // ACCIONES DEL MODULO
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_comunidades' => [
                'description' => 'Listar comunidades disponibles con filtros opcionales',
                'params'      => ['tipo', 'categoria', 'busqueda', 'limite'],
            ],
            'ver_comunidad' => [
                'description' => 'Ver detalle de una comunidad y su actividad reciente',
                'params'      => ['comunidad_id'],
            ],
            'crear_comunidad' => [
                'description' => 'Crear una nueva comunidad (requiere login)',
                'params'      => ['nombre', 'descripcion', 'tipo', 'categoria', 'ubicacion', 'reglas'],
            ],
            'unirse' => [
                'description' => 'Unirse a una comunidad',
                'params'      => ['comunidad_id'],
            ],
            'salir' => [
                'description' => 'Salir de una comunidad',
                'params'      => ['comunidad_id'],
            ],
            'mis_comunidades' => [
                'description' => 'Ver las comunidades del usuario actual',
                'params'      => ['rol', 'estado'],
            ],
            'publicar' => [
                'description' => 'Publicar contenido en una comunidad (requiere membresia)',
                'params'      => ['comunidad_id', 'tipo', 'titulo', 'contenido'],
            ],
            'miembros' => [
                'description' => 'Listar miembros de una comunidad',
                'params'      => ['comunidad_id', 'rol', 'limite'],
            ],
            'gestionar_miembro' => [
                'description' => 'Cambiar rol o estado de un miembro (requiere ser admin)',
                'params'      => ['comunidad_id', 'user_id', 'accion', 'nuevo_rol'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $aliases = [
            'listar' => 'listar_comunidades',
            'listado' => 'listar_comunidades',
            'explorar' => 'listar_comunidades',
            'buscar' => 'listar_comunidades',
            'actividad' => 'feed_actividad',
            'feed' => 'feed_actividad',
            'crear' => 'render_crear',
            'nueva' => 'render_crear',
            'mis_items' => 'mis_comunidades',
            'mis-comunidades' => 'mis_comunidades',
            'detalle' => 'ver_comunidad',
            'ver' => 'ver_comunidad',
            'miembros' => 'miembros',
            'publicar' => 'publicar',
            'unirse' => 'unirse',
            'salir' => 'salir',
        ];

        $nombre_accion = $aliases[$nombre_accion] ?? $nombre_accion;
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        switch ($nombre_accion) {
            case 'foros':
                $entity_id = absint($parametros['comunidad_id'] ?? $parametros['id'] ?? 0);
                if ($entity_id > 0) {
                    return do_shortcode(sprintf(
                        '[flavor_foros_integrado entidad="comunidad" entidad_id="%d"]',
                        $entity_id
                    ));
                }
                return do_shortcode('[foros_actividad_reciente limit="8"]');
            case 'multimedia':
                return do_shortcode('[flavor module="multimedia" view="galeria" header="no" limit="12"]');
            case 'eventos':
                return $this->render_tab_eventos();
            case 'anuncios':
                return do_shortcode('[comunidades_tablon limite="20" incluir_red="true"]');
            case 'recursos':
                return do_shortcode('[comunidades_recursos_compartidos]');
        }

        return [
            'success' => false,
            'error'   => __('La vista solicitada no está disponible en Comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Render del formulario de creación para el portal.
     */
    private function action_render_crear($parametros) {
        return do_shortcode('[comunidades_crear]');
    }

    // =========================================================================
    // IMPLEMENTACION DE ACCIONES
    // =========================================================================

    /**
     * Accion: Listar comunidades con filtros opcionales
     */
    private function action_listar_comunidades($parametros) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $tipo_filtro      = sanitize_text_field($parametros['tipo'] ?? '');
        $categoria_filtro = sanitize_text_field($parametros['categoria'] ?? '');
        $busqueda_filtro  = sanitize_text_field($parametros['busqueda'] ?? '');
        $limite           = absint($parametros['limite'] ?? 20);

        $condiciones_where   = ["estado = 'activa'", "tipo != 'secreta'"];
        $valores_preparacion = [];

        if (!empty($tipo_filtro)) {
            $condiciones_where[]   = "tipo = %s";
            $valores_preparacion[] = $tipo_filtro;
        }

        if (!empty($categoria_filtro)) {
            $condiciones_where[]   = "categoria = %s";
            $valores_preparacion[] = $categoria_filtro;
        }

        if (!empty($busqueda_filtro)) {
            $condiciones_where[]   = "(nombre LIKE %s OR descripcion LIKE %s)";
            $termino_busqueda      = '%' . $wpdb->esc_like($busqueda_filtro) . '%';
            $valores_preparacion[] = $termino_busqueda;
            $valores_preparacion[] = $termino_busqueda;
        }

        $sql_condiciones     = implode(' AND ', $condiciones_where);
        $sql_consulta        = "SELECT * FROM $tabla_comunidades WHERE $sql_condiciones ORDER BY miembros_count DESC, created_at DESC LIMIT %d";
        $valores_preparacion[] = $limite;

        $comunidades_encontradas = $wpdb->get_results($wpdb->prepare($sql_consulta, ...$valores_preparacion));

        $comunidades_formateadas = array_map(function ($comunidad) {
            $creador_datos = get_userdata($comunidad->creador_id);
            return (object) [
                'id'              => (int) $comunidad->id,
                'nombre'          => $comunidad->nombre,
                'descripcion'     => $comunidad->descripcion,
                'imagen'          => $comunidad->imagen,
                'imagen_portada'  => $comunidad->imagen,
                'tipo'            => $comunidad->tipo,
                'categoria'       => $comunidad->categoria,
                'ubicacion'       => $comunidad->ubicacion,
                'miembros_count'  => (int) $comunidad->miembros_count,
                'total_miembros'  => (int) $comunidad->miembros_count,
                'creador'         => (object) [
                    'id'     => (int) $comunidad->creador_id,
                    'nombre' => $creador_datos ? $creador_datos->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'estado'     => $comunidad->estado,
                'created_at' => $comunidad->created_at,
            ];
        }, $comunidades_encontradas);

        return [
            'success'      => true,
            'total'        => count($comunidades_formateadas),
            'comunidades'  => $comunidades_formateadas,
            'mensaje'      => sprintf(
                __('Se encontraron %d comunidades%s.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($comunidades_formateadas),
                !empty($busqueda_filtro) ? " para '$busqueda_filtro'" : ''
            ),
        ];
    }

    /**
     * Accion: Obtener el feed de actividad de una comunidad
     */
    private function action_feed_actividad($parametros) {
        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error' => __('Comunidad no especificada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $limite = max(1, min(absint($parametros['limite'] ?? 10), 50));
        $offset = absint($parametros['offset'] ?? 0);

        global $wpdb;
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_reacciones = $wpdb->prefix . 'flavor_comunidades_actividad_reacciones';

        $query = $wpdb->prepare(
            "SELECT a.*, u.display_name AS autor_nombre
             FROM $tabla_actividad a
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             WHERE a.comunidad_id = %d
             ORDER BY a.es_fijado DESC, a.created_at DESC
             LIMIT %d OFFSET %d",
            $comunidad_id,
            $limite,
            $offset
        );

        $actividades = $wpdb->get_results($query);

        if (empty($actividades)) {
            return [
                'success' => true,
                'actividades' => [],
                'total' => 0,
                'comunidad_id' => $comunidad_id,
            ];
        }

        $actividad_ids = array_map(function($actividad) {
            return (int)$actividad->id;
        }, $actividades);
        $placeholders = implode(',', array_fill(0, count($actividad_ids), '%d')) ?: 'NULL';

        $likes_map = [];
        if (!empty($actividad_ids)) {
            $likes_query = $wpdb->prepare(
                "SELECT actividad_id, COUNT(*) as total FROM $tabla_reacciones WHERE actividad_id IN ($placeholders) GROUP BY actividad_id",
                ...$actividad_ids
            );
            foreach ($wpdb->get_results($likes_query) as $fila) {
                $likes_map[(int)$fila->actividad_id] = (int)$fila->total;
            }
        }

        $liked_map = [];
        $usuario_actual_id = get_current_user_id();
        if (!empty($actividad_ids) && $usuario_actual_id) {
            $liked_query = $wpdb->prepare(
                "SELECT actividad_id FROM $tabla_reacciones WHERE user_id = %d AND actividad_id IN ($placeholders)",
                $usuario_actual_id,
                ...$actividad_ids
            );
            foreach ($wpdb->get_results($liked_query) as $fila) {
                $liked_map[(int)$fila->actividad_id] = true;
            }
        }

        $actividades_formateadas = array_map(function ($actividad) use ($likes_map, $liked_map) {
            $imagen = '';
            if (!empty($actividad->adjuntos)) {
                $adjuntos = array_filter(array_map('trim', explode(',', $actividad->adjuntos)));
                $imagen = array_shift($adjuntos) ?: '';
            }

            return (object) [
                'id' => (int)$actividad->id,
                'titulo' => $actividad->titulo,
                'contenido' => $actividad->contenido,
                'tipo' => $actividad->tipo,
                'imagen' => $imagen,
                'autor_nombre' => $actividad->autor_nombre ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'fecha' => $actividad->created_at,
                'likes' => $likes_map[$actividad->id] ?? (int)$actividad->reacciones_count,
                'liked' => isset($liked_map[$actividad->id]),
                'comentarios' => (int)$actividad->comentarios_count,
            ];
        }, $actividades);

        return [
            'success' => true,
            'actividades' => $actividades_formateadas,
            'total' => count($actividades_formateadas),
            'comunidad_id' => $comunidad_id,
        ];
    }

    /**
     * Accion: Ver detalle de una comunidad y su actividad reciente
     */
    private function action_ver_comunidad($parametros) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion, tipo, categoria, imagen, slug
             FROM $tabla_comunidades
             WHERE id = %d",
            $comunidad_id
        ));

        if (!$comunidad) {
            return [
                'success' => false,
                'error'   => __('Comunidad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Obtener actividad reciente
        $actividad_reciente = $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, u.display_name as autor_nombre
             FROM $tabla_actividad a
             LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
             WHERE a.comunidad_id = %d
             ORDER BY a.es_fijado DESC, a.created_at DESC
             LIMIT 10",
            $comunidad_id
        ));

        $actividad_formateada = array_map(function ($entrada) {
            return [
                'id'               => (int) $entrada->id,
                'tipo'             => $entrada->tipo,
                'titulo'           => $entrada->titulo,
                'contenido'        => $entrada->contenido,
                'autor'            => $entrada->autor_nombre,
                'reacciones_count' => (int) $entrada->reacciones_count,
                'comentarios_count' => (int) $entrada->comentarios_count,
                'es_fijado'        => (bool) $entrada->es_fijado,
                'created_at'       => $entrada->created_at,
            ];
        }, $actividad_reciente);

        // Verificar si el usuario actual es miembro
        $usuario_actual_id   = get_current_user_id();
        $membresia_usuario   = null;
        if ($usuario_actual_id) {
            $membresia_usuario = $wpdb->get_row($wpdb->prepare(
                "SELECT rol, estado FROM $tabla_miembros WHERE comunidad_id = %d AND user_id = %d",
                $comunidad_id,
                $usuario_actual_id
            ));
        }

        $creador_datos = get_userdata($comunidad->creador_id);
        $configuracion_comunidad = json_decode($comunidad->configuracion, true) ?: [];

        return [
            'success'   => true,
            'comunidad' => [
                'id'             => (int) $comunidad->id,
                'nombre'         => $comunidad->nombre,
                'descripcion'    => $comunidad->descripcion,
                'imagen'         => $comunidad->imagen,
                'tipo'           => $comunidad->tipo,
                'categoria'      => $comunidad->categoria,
                'ubicacion'      => $comunidad->ubicacion,
                'reglas'         => $comunidad->reglas,
                'miembros_count' => (int) $comunidad->miembros_count,
                'creador'        => [
                    'id'     => (int) $comunidad->creador_id,
                    'nombre' => $creador_datos ? $creador_datos->display_name : __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ],
                'estado'        => $comunidad->estado,
                'configuracion' => $configuracion_comunidad,
                'created_at'    => $comunidad->created_at,
            ],
            'actividad_reciente' => $actividad_formateada,
            'membresia_usuario'  => $membresia_usuario ? [
                'rol'    => $membresia_usuario->rol,
                'estado' => $membresia_usuario->estado,
            ] : null,
        ];
    }

    /**
     * Accion: Crear una nueva comunidad
     */
    private function action_crear_comunidad($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para crear una comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $nombre_comunidad      = sanitize_text_field($parametros['nombre'] ?? '');
        $descripcion_comunidad = sanitize_textarea_field($parametros['descripcion'] ?? '');
        $tipo_comunidad        = sanitize_text_field($parametros['tipo'] ?? 'abierta');
        $categoria_comunidad   = sanitize_text_field($parametros['categoria'] ?? 'otros');
        $ubicacion_comunidad   = sanitize_text_field($parametros['ubicacion'] ?? '');
        $reglas_comunidad      = sanitize_textarea_field($parametros['reglas'] ?? '');

        if (empty($nombre_comunidad)) {
            return [
                'success' => false,
                'error'   => __('El nombre de la comunidad es obligatorio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Validar tipo
        $tipos_permitidos = ['abierta', 'cerrada', 'secreta'];
        if (!in_array($tipo_comunidad, $tipos_permitidos, true)) {
            $tipo_comunidad = 'abierta';
        }

        // Verificar limite de comunidades creadas por el usuario
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad   = $wpdb->prefix . 'flavor_comunidades_actividad';

        $maximo_comunidades = $this->get_setting('maximo_comunidades_por_usuario', 10);
        $comunidades_del_usuario = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_comunidades WHERE creador_id = %d AND estado != 'archivada'",
            $usuario_actual_id
        ));

        if ($comunidades_del_usuario >= $maximo_comunidades) {
            return [
                'success' => false,
                'error'   => sprintf(
                    __('Has alcanzado el limite de %d comunidades creadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $maximo_comunidades
                ),
            ];
        }

        $configuracion_predeterminada = wp_json_encode([
            'allow_posts'       => true,
            'require_approval'  => ($tipo_comunidad === 'cerrada'),
            'allow_events'      => true,
            'allow_polls'       => true,
        ]);

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_comunidades,
            [
                'nombre'         => $nombre_comunidad,
                'descripcion'    => $descripcion_comunidad,
                'tipo'           => $tipo_comunidad,
                'categoria'      => $categoria_comunidad,
                'ubicacion'      => $ubicacion_comunidad,
                'reglas'         => $reglas_comunidad,
                'miembros_count' => 1,
                'creador_id'     => $usuario_actual_id,
                'estado'         => 'activa',
                'configuracion'  => $configuracion_predeterminada,
                'created_at'     => $fecha_actual,
                'updated_at'     => $fecha_actual,
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $nueva_comunidad_id = $wpdb->insert_id;

        // Registrar al creador como admin
        $wpdb->insert(
            $tabla_miembros,
            [
                'comunidad_id' => $nueva_comunidad_id,
                'user_id'      => $usuario_actual_id,
                'rol'          => 'admin',
                'estado'       => 'activo',
                'joined_at'    => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s']
        );

        // Crear publicacion de bienvenida
        $wpdb->insert(
            $tabla_actividad,
            [
                'comunidad_id' => $nueva_comunidad_id,
                'user_id'      => $usuario_actual_id,
                'tipo'         => 'anuncio',
                'titulo'       => sprintf(__('Bienvenidos a %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $nombre_comunidad),
                'contenido'    => __('La comunidad acaba de crearse. Invita a tus amigos y comienza a participar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'created_at'   => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        // Crear grupo de chat asociado a la comunidad
        $grupo_chat_id = $this->crear_grupo_chat_comunidad($nueva_comunidad_id, $nombre_comunidad, $descripcion_comunidad, $tipo_comunidad);

        // Hook para extensibilidad
        do_action('flavor_comunidad_creada', $nueva_comunidad_id, [
            'nombre' => $nombre_comunidad,
            'tipo' => $tipo_comunidad,
            'creador_id' => $usuario_actual_id,
            'grupo_chat_id' => $grupo_chat_id,
        ]);

        // Sincronizar al creador con los modulos satelite de la comunidad.
        $this->sincronizar_miembro_grupo_consumo_comunidad($nueva_comunidad_id, $usuario_actual_id, 'unirse', 'admin');

        return [
            'success'       => true,
            'comunidad_id'  => $nueva_comunidad_id,
            'grupo_chat_id' => $grupo_chat_id,
            'mensaje'       => sprintf(
                __('Comunidad "%s" creada con exito. Ya eres administrador.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $nombre_comunidad
            ),
        ];
    }

    /**
     * Crea un grupo de chat asociado a una comunidad
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param string $nombre Nombre de la comunidad
     * @param string $descripcion Descripción de la comunidad
     * @param string $tipo Tipo de comunidad (abierta, cerrada, secreta)
     * @return int|false ID del grupo creado o false si falla
     */
    private function crear_grupo_chat_comunidad($comunidad_id, $nombre, $descripcion, $tipo) {
        global $wpdb;

        // Verificar si el módulo de chat grupos está activo
        $chat_grupos_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Chat_Grupos_Module')
            : 'Flavor_Chat_Chat_Grupos_Module';
        $chat_grupos_activo = class_exists($chat_grupos_module_class);
        if (!$chat_grupos_activo) {
            return false;
        }

        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';

        // Verificar si la tabla existe
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_grupos)) {
            return false;
        }

        // Mapear tipo de comunidad a tipo de grupo
        $tipo_grupo = ($tipo === 'secreta') ? 'privado' : (($tipo === 'cerrada') ? 'privado' : 'publico');

        // Crear el grupo de chat
        $slug_grupo = sanitize_title($nombre . '-' . $comunidad_id);
        $creador_id = get_current_user_id();

        $resultado = $wpdb->insert(
            $tabla_grupos,
            [
                'nombre'       => $nombre,
                'slug'         => $slug_grupo,
                'descripcion'  => $descripcion,
                'tipo'         => $tipo_grupo,
                'creador_id'   => $creador_id,
                'comunidad_id' => $comunidad_id,
                'estado'       => 'activo',
                'created_at'   => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado === false) {
            return false;
        }

        $grupo_id = $wpdb->insert_id;

        // Añadir al creador como admin del grupo
        $tabla_miembros_grupo = $wpdb->prefix . 'flavor_chat_grupos_miembros';
        if (Flavor_Platform_Helpers::tabla_existe($tabla_miembros_grupo)) {
            $wpdb->insert(
                $tabla_miembros_grupo,
                [
                    'grupo_id'  => $grupo_id,
                    'user_id'   => $creador_id,
                    'rol'       => 'admin',
                    'estado'    => 'activo',
                    'joined_at' => current_time('mysql'),
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }

        // Guardar relación comunidad-grupo
        update_post_meta($comunidad_id, '_flavor_grupo_chat_id', $grupo_id);

        return $grupo_id;
    }

    /**
     * Obtiene el grupo de chat asociado a una comunidad
     *
     * @param int $comunidad_id ID de la comunidad
     * @return int|null ID del grupo de chat o null
     */
    public function obtener_grupo_chat_comunidad($comunidad_id) {
        global $wpdb;

        // Primero buscar en meta
        $grupo_id = get_post_meta($comunidad_id, '_flavor_grupo_chat_id', true);
        if ($grupo_id) {
            return (int) $grupo_id;
        }

        // Si no hay meta, buscar por comunidad_id en tabla de grupos
        $tabla_grupos = $wpdb->prefix . 'flavor_chat_grupos';
        if (!Flavor_Platform_Helpers::tabla_existe($tabla_grupos)) {
            return null;
        }

        $grupo_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_grupos} WHERE comunidad_id = %d AND estado = 'activo' LIMIT 1",
            $comunidad_id
        ));

        return $grupo_id ? (int) $grupo_id : null;
    }

    /**
     * Sincroniza la membresía de un usuario con el grupo de chat de la comunidad
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param int    $usuario_id   ID del usuario
     * @param string $accion       'unirse' o 'salir'
     * @return bool True si la sincronización fue exitosa
     */
    private function sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_id, $accion) {
        // Verificar que el módulo de chat-grupos está activo
        $chat_grupos_module_class = function_exists('flavor_get_runtime_class_name')
            ? flavor_get_runtime_class_name('Flavor_Chat_Chat_Grupos_Module')
            : 'Flavor_Chat_Chat_Grupos_Module';
        if (!class_exists($chat_grupos_module_class)) {
            return false;
        }

        // Obtener el grupo de chat de la comunidad
        $grupo_id = $this->obtener_grupo_chat_comunidad($comunidad_id);
        if (!$grupo_id) {
            return false;
        }

        // Obtener instancia del módulo de chat-grupos
        $modulo_chat_grupos = Flavor_Platform_Module_Loader::get_instance()->get_module('chat_grupos');
        if (!$modulo_chat_grupos) {
            return false;
        }

        // Sincronizar según la acción
        if ($accion === 'unirse') {
            $resultado = $modulo_chat_grupos->agregar_miembro_programatico($grupo_id, $usuario_id);
        } else {
            $resultado = $modulo_chat_grupos->quitar_miembro_programatico($grupo_id, $usuario_id);
        }

        return !empty($resultado['success']);
    }

    /**
     * Sincroniza la membresia de un usuario con el grupo principal de consumo de la comunidad.
     *
     * @param int    $comunidad_id ID de la comunidad.
     * @param int    $usuario_id   ID del usuario.
     * @param string $accion       Accion de sincronizacion.
     * @param string $rol_comunidad Rol del usuario dentro de la comunidad.
     * @return bool
     */
    private function sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_id, $accion, $rol_comunidad = 'miembro') {
        if (!class_exists('Flavor_GC_Consumidor_Manager')) {
            return false;
        }

        $module_loader = Flavor_Platform_Module_Loader::get_instance();
        if (!$module_loader) {
            return false;
        }

        $modulo_grupos_consumo = $module_loader->get_module('grupos_consumo');
        if (!$modulo_grupos_consumo || !method_exists($modulo_grupos_consumo, 'obtener_grupo_principal_comunidad')) {
            return false;
        }

        $grupo_principal = $modulo_grupos_consumo->obtener_grupo_principal_comunidad($comunidad_id);
        if (!$grupo_principal instanceof WP_Post) {
            return false;
        }

        $grupo_id = (int) $grupo_principal->ID;
        $consumidor_manager = Flavor_GC_Consumidor_Manager::get_instance();
        $consumidor = $consumidor_manager->obtener_consumidor($usuario_id, $grupo_id);

        $rol_gc = in_array($rol_comunidad, ['admin', 'moderador'], true) ? 'coordinador' : 'consumidor';

        switch ($accion) {
            case 'unirse':
                if (!$consumidor) {
                    $alta = $consumidor_manager->alta_consumidor($usuario_id, $grupo_id, $rol_gc);
                    if (empty($alta['success']) || empty($alta['consumidor_id'])) {
                        return false;
                    }

                    $consumidor_id = (int) $alta['consumidor_id'];
                    $estado = $consumidor_manager->cambiar_estado($consumidor_id, 'activo');
                    if (empty($estado['success'])) {
                        return false;
                    }

                    return true;
                }

                if ($consumidor->estado !== 'activo') {
                    $estado = $consumidor_manager->cambiar_estado((int) $consumidor->id, 'activo');
                    if (empty($estado['success'])) {
                        return false;
                    }
                }

                if ($consumidor->rol !== $rol_gc) {
                    $rol = $consumidor_manager->cambiar_rol((int) $consumidor->id, $rol_gc);
                    if (empty($rol['success'])) {
                        return false;
                    }
                }

                return true;

            case 'suspender':
                if (!$consumidor) {
                    return true;
                }

                $estado = $consumidor_manager->cambiar_estado((int) $consumidor->id, 'suspendido');
                return !empty($estado['success']);

            case 'banear':
            case 'salir':
                if (!$consumidor) {
                    return true;
                }

                $estado = $consumidor_manager->cambiar_estado((int) $consumidor->id, 'baja');
                return !empty($estado['success']);
        }

        return false;
    }

    /**
     * Accion: Unirse a una comunidad
     */
    private function action_unirse($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para unirte a una comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que la comunidad existe y esta activa
        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre, descripcion, tipo, categoria, slug, estado
             FROM $tabla_comunidades
             WHERE id = %d AND estado = 'activa'",
            $comunidad_id
        ));

        if (!$comunidad) {
            return [
                'success' => false,
                'error'   => __('Comunidad no encontrada o no esta activa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar si ya es miembro
        $membresia_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id, estado, rol
             FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d",
            $comunidad_id,
            $usuario_actual_id
        ));

        if ($membresia_existente) {
            if ($membresia_existente->estado === 'activo') {
                return [
                    'success' => false,
                    'error'   => __('Ya eres miembro de esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
            if ($membresia_existente->estado === 'baneado') {
                return [
                    'success' => false,
                    'error'   => __('Has sido baneado de esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
            if ($membresia_existente->estado === 'pendiente') {
                return [
                    'success' => false,
                    'error'   => __('Tu solicitud ya esta pendiente de aprobacion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // Determinar estado segun tipo de comunidad
        $estado_inicial = 'activo';
        if ($comunidad->tipo === 'cerrada') {
            $estado_inicial = 'pendiente';
        }

        $fecha_actual = current_time('mysql');

        if ($membresia_existente) {
            // Reactivar membresia suspendida
            $wpdb->update(
                $tabla_miembros,
                [
                    'estado'    => $estado_inicial,
                    'joined_at' => $fecha_actual,
                ],
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $usuario_actual_id,
                ],
                ['%s', '%s'],
                ['%d', '%d']
            );
        } else {
            $wpdb->insert(
                $tabla_miembros,
                [
                    'comunidad_id' => $comunidad_id,
                    'user_id'      => $usuario_actual_id,
                    'rol'          => 'miembro',
                    'estado'       => $estado_inicial,
                    'joined_at'    => $fecha_actual,
                ],
                ['%d', '%d', '%s', '%s', '%s']
            );
        }

        // Actualizar contador de miembros si es activo directamente
        if ($estado_inicial === 'activo') {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                $fecha_actual,
                $comunidad_id
            ));

            // Sincronizar con grupo de chat de la comunidad
            $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_actual_id, 'unirse');
            $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_actual_id, 'unirse', 'miembro');
        }

        $mensaje_respuesta = ($estado_inicial === 'pendiente')
            ? sprintf(__('Tu solicitud para unirte a "%s" esta pendiente de aprobacion.', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad->nombre)
            : sprintf(__('Te has unido a "%s" correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad->nombre);

        return [
            'success' => true,
            'estado'  => $estado_inicial,
            'mensaje' => $mensaje_respuesta,
        ];
    }

    /**
     * Accion: Salir de una comunidad
     */
    private function action_salir($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que es miembro activo
        $membresia = $wpdb->get_row($wpdb->prepare(
            "SELECT id, rol
             FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia) {
            return [
                'success' => false,
                'error'   => __('No eres miembro activo de esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // No permitir que el unico admin se salga
        if ($membresia->rol === 'admin') {
            $total_admins = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = %d AND rol = 'admin' AND estado = 'activo'",
                $comunidad_id
            ));

            if ($total_admins <= 1) {
                return [
                    'success' => false,
                    'error'   => __('No puedes salir siendo el unico administrador. Asigna otro admin antes de irte.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
            }
        }

        // Eliminar membresia
        $wpdb->delete(
            $tabla_miembros,
            [
                'comunidad_id' => $comunidad_id,
                'user_id'      => $usuario_actual_id,
            ],
            ['%d', '%d']
        );

        // Actualizar contador
        $fecha_actual = current_time('mysql');
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
            $fecha_actual,
            $comunidad_id
        ));

        // Sincronizar con grupo de chat de la comunidad
        $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_actual_id, 'salir');
        $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_actual_id, 'salir', $membresia->rol);

        $comunidad = $wpdb->get_row($wpdb->prepare(
            "SELECT nombre FROM $tabla_comunidades WHERE id = %d",
            $comunidad_id
        ));

        return [
            'success' => true,
            'mensaje' => sprintf(
                __('Has salido de la comunidad "%s".', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $comunidad ? $comunidad->nombre : ''
            ),
        ];
    }

    /**
     * Accion: Mis comunidades
     */
    private function action_mis_comunidades($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para ver tus comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        $rol_filtro    = sanitize_text_field($parametros['rol'] ?? '');
        $estado_filtro = sanitize_text_field($parametros['estado'] ?? 'activo');

        $condiciones_where   = ["m.user_id = %d", "m.estado = %s"];
        $valores_preparacion = [$usuario_actual_id, $estado_filtro];

        if (!empty($rol_filtro)) {
            $condiciones_where[]   = "m.rol = %s";
            $valores_preparacion[] = $rol_filtro;
        }

        $sql_condiciones = implode(' AND ', $condiciones_where);

        $comunidades_del_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, m.rol, m.estado as membresia_estado, m.joined_at
             FROM $tabla_comunidades c
             INNER JOIN $tabla_miembros m ON c.id = m.comunidad_id
             WHERE $sql_condiciones
             ORDER BY m.joined_at DESC",
            ...$valores_preparacion
        ));

        $comunidades_formateadas = array_map(function ($fila) {
            return [
                'id'              => (int) $fila->id,
                'nombre'          => $fila->nombre,
                'descripcion'     => $fila->descripcion,
                'tipo'            => $fila->tipo,
                'categoria'       => $fila->categoria,
                'miembros_count'  => (int) $fila->miembros_count,
                'mi_rol'          => $fila->rol,
                'estado_comunidad' => $fila->estado,
                'joined_at'       => $fila->joined_at,
            ];
        }, $comunidades_del_usuario);

        return [
            'success'     => true,
            'total'       => count($comunidades_formateadas),
            'comunidades' => $comunidades_formateadas,
            'mensaje'     => sprintf(
                __('Perteneces a %d comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($comunidades_formateadas)
            ),
        ];
    }

    /**
     * Accion: Publicar contenido en una comunidad
     */
    private function action_publicar($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $comunidad_id        = absint($parametros['comunidad_id'] ?? 0);
        $tipo_publicacion    = sanitize_text_field($parametros['tipo'] ?? 'publicacion');
        $titulo_publicacion  = sanitize_text_field($parametros['titulo'] ?? '');
        $contenido_publicacion = sanitize_textarea_field($parametros['contenido'] ?? '');

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if (empty($contenido_publicacion) && empty($titulo_publicacion)) {
            return [
                'success' => false,
                'error'   => __('El contenido o titulo son obligatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Validar tipo de publicacion
        $tipos_publicacion_permitidos = ['publicacion', 'evento', 'anuncio', 'encuesta'];
        if (!in_array($tipo_publicacion, $tipos_publicacion_permitidos, true)) {
            $tipo_publicacion = 'publicacion';
        }

        global $wpdb;
        $tabla_miembros  = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';

        // Verificar membresia activa
        $membresia = $wpdb->get_row($wpdb->prepare(
            "SELECT id, rol
             FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia) {
            return [
                'success' => false,
                'error'   => __('Debes ser miembro activo para publicar en esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Solo admins y moderadores pueden publicar anuncios
        if ($tipo_publicacion === 'anuncio' && !in_array($membresia->rol, ['admin', 'moderador'], true)) {
            return [
                'success' => false,
                'error'   => __('Solo administradores y moderadores pueden publicar anuncios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_actividad,
            [
                'comunidad_id' => $comunidad_id,
                'user_id'      => $usuario_actual_id,
                'tipo'         => $tipo_publicacion,
                'titulo'       => $titulo_publicacion,
                'contenido'    => $contenido_publicacion,
                'created_at'   => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear la publicacion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        return [
            'success'        => true,
            'publicacion_id' => $wpdb->insert_id,
            'mensaje'        => __('Publicacion creada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Accion: Marcar o desmarcar like en una actividad
     */
    private function action_toggle_like($parametros) {
        $usuario_id = get_current_user_id();
        $actividad_id = absint($parametros['actividad_id'] ?? 0);

        if (!$usuario_id || !$actividad_id) {
            return [
                'success' => false,
                'error' => __('Datos inválidos para reaccionar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        global $wpdb;
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_reacciones = $wpdb->prefix . 'flavor_comunidades_actividad_reacciones';

        $actividad = $wpdb->get_row($wpdb->prepare(
            "SELECT id, comunidad_id FROM $tabla_actividad WHERE id = %d",
            $actividad_id
        ));

        if (!$actividad) {
            return [
                'success' => false,
                'error' => __('Actividad no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        if (!$this->es_miembro_activo($actividad->comunidad_id, $usuario_id)) {
            return [
                'success' => false,
                'error' => __('Debes ser miembro activo para reaccionar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $reaccion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reacciones WHERE actividad_id = %d AND user_id = %d",
            $actividad_id,
            $usuario_id
        ));

        $delta = 0;
        $liked = false;

        if ($reaccion_existente) {
            $wpdb->delete(
                $tabla_reacciones,
                ['id' => $reaccion_existente],
                ['%d']
            );
            $delta = -1;
            $liked = false;
        } else {
            $wpdb->insert(
                $tabla_reacciones,
                [
                    'actividad_id' => $actividad_id,
                    'user_id' => $usuario_id,
                    'tipo' => 'like',
                ],
                ['%d', '%d', '%s']
            );
            $delta = 1;
            $liked = true;
        }

        if ($delta !== 0) {
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_actividad SET reacciones_count = GREATEST(reacciones_count + %d, 0) WHERE id = %d",
                $delta,
                $actividad_id
            ));
        }

        $likes = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT reacciones_count FROM $tabla_actividad WHERE id = %d",
            $actividad_id
        ));

        return [
            'success' => true,
            'liked' => $liked,
            'likes' => $likes,
        ];
    }

    /**
     * Renderiza el HTML del feed de actividad
     *
     * @param array|object $actividades
     * @param int $comunidad_id
     * @return string
     */
    private function render_feed_html($actividades, $comunidad_id) {
        ob_start();
        include dirname(__FILE__) . '/views/feed-actividad.php';
        return ob_get_clean();
    }

    /**
     * Accion: Listar miembros de una comunidad
     */
    private function action_miembros($parametros) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $comunidad_id = absint($parametros['comunidad_id'] ?? 0);
        $rol_filtro   = sanitize_text_field($parametros['rol'] ?? '');
        $limite       = absint($parametros['limite'] ?? 50);

        if (!$comunidad_id) {
            return [
                'success' => false,
                'error'   => __('ID de comunidad no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $condiciones_where   = ["m.comunidad_id = %d", "m.estado = 'activo'"];
        $valores_preparacion = [$comunidad_id];

        if (!empty($rol_filtro)) {
            $condiciones_where[]   = "m.rol = %s";
            $valores_preparacion[] = $rol_filtro;
        }

        $sql_condiciones       = implode(' AND ', $condiciones_where);
        $valores_preparacion[] = $limite;

        $miembros_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT m.*, u.display_name, u.user_email
             FROM $tabla_miembros m
             LEFT JOIN {$wpdb->users} u ON m.user_id = u.ID
             WHERE $sql_condiciones
             ORDER BY FIELD(m.rol, 'admin', 'moderador', 'miembro'), m.joined_at ASC
             LIMIT %d",
            ...$valores_preparacion
        ));

        $miembros_formateados = array_map(function ($miembro) {
            return [
                'user_id'  => (int) $miembro->user_id,
                'nombre'   => $miembro->display_name ?: __('Usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'email'    => $miembro->user_email,
                'rol'      => $miembro->rol,
                'estado'   => $miembro->estado,
                'joined_at' => $miembro->joined_at,
            ];
        }, $miembros_encontrados);

        return [
            'success'  => true,
            'total'    => count($miembros_formateados),
            'miembros' => $miembros_formateados,
            'mensaje'  => sprintf(
                __('La comunidad tiene %d miembros activos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($miembros_formateados)
            ),
        ];
    }

    /**
     * Accion: Gestionar un miembro (cambiar rol, suspender, banear)
     */
    private function action_gestionar_miembro($parametros) {
        $usuario_actual_id = get_current_user_id();

        if (!$usuario_actual_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $comunidad_id    = absint($parametros['comunidad_id'] ?? 0);
        $usuario_objetivo_id = absint($parametros['user_id'] ?? 0);
        $accion_gestionar    = sanitize_text_field($parametros['accion'] ?? '');
        $nuevo_rol           = sanitize_text_field($parametros['nuevo_rol'] ?? '');

        if (!$comunidad_id || !$usuario_objetivo_id) {
            return [
                'success' => false,
                'error'   => __('Comunidad y usuario son obligatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros    = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar que el usuario actual es admin de la comunidad
        $membresia_admin = $wpdb->get_row($wpdb->prepare(
            "SELECT id
             FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d AND rol = 'admin' AND estado = 'activo'",
            $comunidad_id,
            $usuario_actual_id
        ));

        if (!$membresia_admin) {
            return [
                'success' => false,
                'error'   => __('Solo los administradores pueden gestionar miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // No gestionarse a si mismo
        if ($usuario_actual_id === $usuario_objetivo_id) {
            return [
                'success' => false,
                'error'   => __('No puedes gestionarte a ti mismo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar que el usuario objetivo es miembro
        $membresia_objetivo = $wpdb->get_row($wpdb->prepare(
            "SELECT id, user_id, rol, estado
             FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d",
            $comunidad_id,
            $usuario_objetivo_id
        ));

        if (!$membresia_objetivo) {
            return [
                'success' => false,
                'error'   => __('El usuario no es miembro de esta comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $fecha_actual       = current_time('mysql');
        $mensaje_resultado  = '';

        switch ($accion_gestionar) {
            case 'cambiar_rol':
                $roles_permitidos = ['admin', 'moderador', 'miembro'];
                if (!in_array($nuevo_rol, $roles_permitidos, true)) {
                    return [
                        'success' => false,
                        'error'   => __('Rol no valido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                }
                $wpdb->update(
                    $tabla_miembros,
                    ['rol' => $nuevo_rol],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                if ($membresia_objetivo->estado === 'activo') {
                    $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_objetivo_id, 'unirse', $nuevo_rol);
                }
                $mensaje_resultado = sprintf(__('Rol actualizado a "%s".', FLAVOR_PLATFORM_TEXT_DOMAIN), $nuevo_rol);
                break;

            case 'suspender':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'suspendido'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                // Sincronizar con grupo de chat (quitar acceso)
                $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_objetivo_id, 'salir');
                $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_objetivo_id, 'suspender', $membresia_objetivo->rol);
                $mensaje_resultado = __('Miembro suspendido.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                break;

            case 'banear':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'baneado'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = GREATEST(miembros_count - 1, 0), updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                // Sincronizar con grupo de chat (quitar acceso)
                $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_objetivo_id, 'salir');
                $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_objetivo_id, 'banear', $membresia_objetivo->rol);
                $mensaje_resultado = __('Miembro baneado de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                break;

            case 'aprobar':
                if ($membresia_objetivo->estado !== 'pendiente') {
                    return [
                        'success' => false,
                        'error'   => __('Este miembro no esta pendiente de aprobacion.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ];
                }
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'activo'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                // Sincronizar con grupo de chat
                $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_objetivo_id, 'unirse');
                $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_objetivo_id, 'unirse', $membresia_objetivo->rol);
                $mensaje_resultado = __('Miembro aprobado y activado.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                break;

            case 'reactivar':
                $wpdb->update(
                    $tabla_miembros,
                    ['estado' => 'activo'],
                    ['comunidad_id' => $comunidad_id, 'user_id' => $usuario_objetivo_id],
                    ['%s'],
                    ['%d', '%d']
                );
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_comunidades SET miembros_count = miembros_count + 1, updated_at = %s WHERE id = %d",
                    $fecha_actual,
                    $comunidad_id
                ));
                // Sincronizar con grupo de chat
                $this->sincronizar_miembro_chat_comunidad($comunidad_id, $usuario_objetivo_id, 'unirse');
                $this->sincronizar_miembro_grupo_consumo_comunidad($comunidad_id, $usuario_objetivo_id, 'unirse', $membresia_objetivo->rol);
                $mensaje_resultado = __('Miembro reactivado.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                break;

            default:
                return [
                    'success' => false,
                    'error'   => __('Accion de gestion no valida. Usa: cambiar_rol, suspender, banear, aprobar o reactivar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ];
        }

        return [
            'success' => true,
            'mensaje' => $mensaje_resultado,
        ];
    }

    // =========================================================================
    // TOOL DEFINITIONS PARA CLAUDE
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'comunidades_listar',
                'description'  => 'Lista las comunidades disponibles con filtros opcionales de tipo, categoria y busqueda',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por tipo de comunidad',
                            'enum'        => ['abierta', 'cerrada'],
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por categoria',
                        ],
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda en nombre o descripcion',
                        ],
                        'limite' => [
                            'type'        => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default'     => 20,
                        ],
                    ],
                ],
            ],
            [
                'name'         => 'comunidades_buscar',
                'description'  => 'Busca comunidades por nombre, descripcion o categoria',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda',
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por categoria',
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'comunidades_crear',
                'description'  => 'Crea una nueva comunidad tematica. El usuario debe estar autenticado.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'nombre' => [
                            'type'        => 'string',
                            'description' => 'Nombre de la comunidad',
                        ],
                        'descripcion' => [
                            'type'        => 'string',
                            'description' => 'Descripcion detallada de la comunidad',
                        ],
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Tipo de comunidad',
                            'enum'        => ['abierta', 'cerrada', 'secreta'],
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Categoria de la comunidad',
                        ],
                        'ubicacion' => [
                            'type'        => 'string',
                            'description' => 'Ubicacion fisica de la comunidad',
                        ],
                        'reglas' => [
                            'type'        => 'string',
                            'description' => 'Reglas de la comunidad',
                        ],
                    ],
                    'required' => ['nombre', 'descripcion'],
                ],
            ],
            [
                'name'         => 'comunidades_unirse',
                'description'  => 'Permite al usuario unirse a una comunidad existente',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'comunidad_id' => [
                            'type'        => 'integer',
                            'description' => 'ID de la comunidad a la que unirse',
                        ],
                    ],
                    'required' => ['comunidad_id'],
                ],
            ],
        ];
    }

    // =========================================================================
    // KNOWLEDGE BASE Y FAQS
    // =========================================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Comunidades - Guia de Uso**

Las comunidades son espacios tematicos donde los usuarios se reunen en torno a intereses comunes.
Cada comunidad tiene miembros, actividad compartida y su propia configuracion.

**Tipos de comunidad:**
- Abierta: cualquiera puede unirse libremente
- Cerrada: requiere aprobacion de un administrador
- Secreta: no aparece en listados publicos, solo por invitacion

**Roles de miembros:**
- Admin: control total, puede gestionar miembros y configuracion
- Moderador: puede moderar contenido y aprobar miembros
- Miembro: puede publicar y participar

**Categorias disponibles:**
- Tecnologia, Deportes, Cultura, Educacion, Medio Ambiente, Salud, Ocio, Vecinal, Otros

**Tipos de publicaciones:**
- Publicacion: contenido general
- Evento: actividades programadas
- Anuncio: comunicados oficiales (solo admin/moderador)
- Encuesta: votaciones de la comunidad

**Comandos disponibles:**
- "ver comunidades": lista comunidades disponibles
- "buscar comunidad [tema]": busca comunidades por tema
- "crear comunidad": crea una nueva comunidad
- "unirme a [comunidad]": unirse a una comunidad
- "mis comunidades": ver comunidades donde participo
- "publicar en [comunidad]": crear contenido en una comunidad

**Importante:**
- Los usuarios deben estar autenticados para crear o unirse a comunidades
- Los administradores son responsables de moderar su comunidad
- Las comunidades secretas no aparecen en busquedas publicas
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta'  => 'Como puedo crear una comunidad?',
                'respuesta' => 'Necesitas iniciar sesion y luego usar la opcion de crear comunidad. Elige un nombre, descripcion, tipo y categoria.',
            ],
            [
                'pregunta'  => 'Cual es la diferencia entre comunidad abierta, cerrada y secreta?',
                'respuesta' => 'Las abiertas permiten unirse libremente. Las cerradas requieren aprobacion. Las secretas no aparecen en listados y solo se accede por invitacion.',
            ],
            [
                'pregunta'  => 'Como me uno a una comunidad?',
                'respuesta' => 'Busca la comunidad que te interesa y haz clic en unirte. Si es cerrada, tu solicitud sera revisada por un admin.',
            ],
            [
                'pregunta'  => 'Puedo salir de una comunidad?',
                'respuesta' => 'Si, puedes salir en cualquier momento. Si eres el unico admin, deberas designar otro admin antes de irte.',
            ],
            [
                'pregunta'  => 'Cuantas comunidades puedo crear?',
                'respuesta' => 'Por defecto puedes crear hasta 10 comunidades. Este limite puede variar segun la configuracion del sitio.',
            ],
        ];
    }

    // =========================================================================
    // WEB COMPONENTS
    // =========================================================================

    /**
     * Componentes web del modulo para el constructor de paginas
     */
    public function get_web_components() {
        return [
            'comunidades_hero' => [
                'label'       => __('Hero Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Seccion hero principal para la pagina de comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'hero',
                'icon'        => 'dashicons-groups',
                'fields'      => [
                    'titulo' => [
                        'type'    => 'text',
                        'label'   => __('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'subtitulo' => [
                        'type'    => 'textarea',
                        'label'   => __('Subtitulo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Encuentra tu tribu y conecta con personas que comparten tus intereses', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'imagen_fondo' => [
                        'type'    => 'image',
                        'label'   => __('Imagen de fondo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => '',
                    ],
                ],
                'template' => 'comunidades/hero',
            ],
            'comunidades_grid' => [
                'label'       => __('Grid de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Listado visual de comunidades disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'listings',
                'icon'        => 'dashicons-grid-view',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de la seccion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Explora Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'columnas' => [
                        'type'    => 'select',
                        'label'   => __('Columnas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['2', '3', '4'],
                        'default' => '3',
                    ],
                    'tipo_filtro' => [
                        'type'    => 'select',
                        'label'   => __('Filtrar por tipo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'options' => ['todos', 'abierta', 'cerrada'],
                        'default' => 'todos',
                    ],
                    'mostrar_miembros' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar contador de miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => true,
                    ],
                ],
                'template' => 'comunidades/comunidades-grid',
            ],
            'comunidades_como_unirse' => [
                'label'       => __('Como Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Seccion explicativa de como unirse a comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'category'    => 'features',
                'icon'        => 'dashicons-info',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de la seccion', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'default' => __('Como Unirse', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                ],
                'template' => 'comunidades/como-unirse',
            ],
        ];
    }

    /**
     * Crea páginas frontend automáticamente
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('comunidades');
            return;
        }

        // En frontend: crear páginas si no existen
        $pagina = get_page_by_path('comunidades');
        if (!$pagina && !get_option('flavor_comunidades_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['comunidades']);
            update_option('flavor_comunidades_pages_created', 1, false);
        }
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'comunidades',
                'content' => '<h1>' . __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Descubre y únete a comunidades de tu interés, comparte experiencias y conecta con personas afines.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="comunidades" action="listar" columnas="3" limite="12"]',
                'parent' => 0,
            ],
            [
                'title' => __('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'crear',
                'content' => '<h1>' . __('Crear Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Crea tu propia comunidad y reúne a personas con intereses comunes.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="comunidades" action="crear"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'mis-comunidades',
                'content' => '<h1>' . __('Mis Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>
<p>' . __('Gestiona las comunidades a las que perteneces y las que has creado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>

[flavor_module_listing module="comunidades" action="mis_comunidades" columnas="3" limite="12"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Feed Unificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'feed',
                'content' => '[comunidades_feed_unificado limite="30" incluir_red="true"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Calendario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'calendario',
                'content' => '[comunidades_calendario incluir_red="true"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Recursos Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'recursos',
                'content' => '[comunidades_recursos_compartidos columnas="4" limite="20"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Tablón de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'tablon',
                'content' => '[comunidades_tablon limite="20" incluir_red="true"]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Buscar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'buscar',
                'content' => '[comunidades_busqueda]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'notificaciones',
                'content' => '[comunidades_notificaciones]',
                'parent' => 'comunidades',
            ],
            [
                'title' => __('Métricas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'slug' => 'metricas',
                'content' => '[comunidades_metricas]',
                'parent' => 'comunidades',
            ],
        ];
    }

    // =========================================================================
    // TABLÓN DE ANUNCIOS INTER-COMUNIDADES
    // =========================================================================

    /**
     * AJAX: Obtiene anuncios del tablón
     */
    public function ajax_obtener_anuncios() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'todos';
        $incluir_red = isset($_POST['incluir_red']) && $_POST['incluir_red'] === 'true';
        $limite = isset($_POST['limite']) ? intval($_POST['limite']) : 20;

        $usuario_id = get_current_user_id();
        $anuncios = [];

        global $wpdb;
        $tabla_anuncios = $wpdb->prefix . 'flavor_comunidades_anuncios';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Verificar si la tabla existe, si no, crearla
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            $this->crear_tabla_anuncios();
        }

        // Obtener anuncios locales
        $where_categoria = $categoria !== 'todos'
            ? $wpdb->prepare(" AND a.categoria = %s", $categoria)
            : '';

        // Si el usuario está logueado, mostrar anuncios de sus comunidades + públicos
        if ($usuario_id) {
            $query = $wpdb->prepare(
                "SELECT a.*, c.nombre AS comunidad_nombre, c.imagen AS comunidad_imagen, c.slug AS comunidad_slug,
                        'local' AS origen
                 FROM $tabla_anuncios a
                 INNER JOIN $tabla_comunidades c ON c.id = a.comunidad_id
                 LEFT JOIN $tabla_miembros m ON m.comunidad_id = a.comunidad_id AND m.user_id = %d
                 WHERE a.estado = 'publicado'
                   AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion >= NOW())
                   AND (c.tipo = 'abierta' OR m.user_id IS NOT NULL)
                   {$where_categoria}
                 ORDER BY a.destacado DESC, a.created_at DESC
                 LIMIT %d",
                $usuario_id, $limite
            );
        } else {
            // Solo anuncios de comunidades públicas
            $query = $wpdb->prepare(
                "SELECT a.*, c.nombre AS comunidad_nombre, c.imagen AS comunidad_imagen, c.slug AS comunidad_slug,
                        'local' AS origen
                 FROM $tabla_anuncios a
                 INNER JOIN $tabla_comunidades c ON c.id = a.comunidad_id
                 WHERE a.estado = 'publicado'
                   AND c.tipo = 'abierta'
                   AND (a.fecha_expiracion IS NULL OR a.fecha_expiracion >= NOW())
                   {$where_categoria}
                 ORDER BY a.destacado DESC, a.created_at DESC
                 LIMIT %d",
                $limite
            );
        }

        $anuncios_locales = $wpdb->get_results($query);

        foreach ($anuncios_locales as $anuncio) {
            $anuncios[] = [
                'id'               => $anuncio->id,
                'titulo'           => $anuncio->titulo,
                'contenido'        => $anuncio->contenido,
                'categoria'        => $anuncio->categoria,
                'destacado'        => (bool) $anuncio->destacado,
                'fecha'            => date_i18n('j M Y', strtotime($anuncio->created_at)),
                'comunidad_id'     => $anuncio->comunidad_id,
                'comunidad_nombre' => $anuncio->comunidad_nombre,
                'comunidad_imagen' => $anuncio->comunidad_imagen,
                'url'              => home_url('/mi-portal/comunidades/' . intval($anuncio->comunidad_id) . '/'),
                'origen'           => 'local',
            ];
        }

        // Obtener anuncios de la red federada
        if ($incluir_red && class_exists('Flavor_Network_Content_Bridge')) {
            $anuncios_federados = $this->obtener_anuncios_federados($categoria, $limite);
            $anuncios = array_merge($anuncios, $anuncios_federados);
        }

        // Ordenar: destacados primero, luego por fecha
        usort($anuncios, function($a, $b) {
            if ($a['destacado'] !== $b['destacado']) {
                return $b['destacado'] ? 1 : -1;
            }
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        wp_send_json_success([
            'anuncios' => array_slice($anuncios, 0, $limite),
        ]);
    }

    /**
     * AJAX: Crea un nuevo anuncio
     */
    public function ajax_crear_anuncio() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $comunidad_id = isset($_POST['comunidad_id']) ? intval($_POST['comunidad_id']) : 0;
        $titulo = isset($_POST['titulo']) ? sanitize_text_field($_POST['titulo']) : '';
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';
        $categoria = isset($_POST['categoria']) ? sanitize_text_field($_POST['categoria']) : 'general';
        $destacado = isset($_POST['destacado']) && $_POST['destacado'] === '1';
        $compartir_red = isset($_POST['compartir_red']) && $_POST['compartir_red'] === '1';
        $fecha_expiracion = isset($_POST['fecha_expiracion']) && !empty($_POST['fecha_expiracion'])
            ? sanitize_text_field($_POST['fecha_expiracion'])
            : null;

        // Validaciones
        if (!$comunidad_id || !$titulo || !$contenido) {
            wp_send_json_error(['message' => __('Faltan campos obligatorios', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar que el usuario puede publicar en esta comunidad
        if (!$this->usuario_puede_publicar_anuncio($usuario_id, $comunidad_id)) {
            wp_send_json_error(['message' => __('No tienes permiso para publicar anuncios en esta comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_anuncios = $wpdb->prefix . 'flavor_comunidades_anuncios';

        // Crear tabla si no existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_anuncios'") !== $tabla_anuncios) {
            $this->crear_tabla_anuncios();
        }

        $resultado = $wpdb->insert(
            $tabla_anuncios,
            [
                'comunidad_id'     => $comunidad_id,
                'user_id'          => $usuario_id,
                'titulo'           => $titulo,
                'contenido'        => $contenido,
                'categoria'        => $categoria,
                'destacado'        => $destacado ? 1 : 0,
                'compartir_red'    => $compartir_red ? 1 : 0,
                'fecha_expiracion' => $fecha_expiracion,
                'estado'           => 'publicado',
                'created_at'       => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s']
        );

        if ($resultado) {
            $anuncio_id = $wpdb->insert_id;

            // Compartir en la red federada si está habilitado
            if ($compartir_red && class_exists('Flavor_Network_Content_Bridge')) {
                $this->compartir_anuncio_en_red($anuncio_id);
            }

            // Disparar notificaciones
            $comunidad = $this->obtener_comunidad($comunidad_id);
            do_action('flavor_comunidad_nuevo_anuncio', $comunidad_id, $anuncio_id, (object) [
                'titulo'    => $titulo,
                'categoria' => $categoria,
                'user_id'   => $usuario_id,
            ]);

            wp_send_json_success([
                'message'    => __('Anuncio publicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'anuncio_id' => $anuncio_id,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar el anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Obtiene comunidades donde el usuario puede publicar anuncios
     */
    public function ajax_mis_comunidades_admin() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        // Obtener comunidades donde el usuario es admin o creador
        $comunidades = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.nombre, c.imagen
             FROM $tabla_comunidades c
             INNER JOIN $tabla_miembros m ON m.comunidad_id = c.id
             WHERE m.user_id = %d
               AND m.rol IN ('admin', 'creador', 'moderador')
               AND m.estado = 'activo'
               AND c.estado = 'activa'
             ORDER BY c.nombre ASC",
            $usuario_id
        ));

        wp_send_json_success([
            'comunidades' => $comunidades,
        ]);
    }

    /**
     * Crea la tabla de anuncios si no existe
     */
    private function crear_tabla_anuncios() {
        global $wpdb;
        $tabla_anuncios = $wpdb->prefix . 'flavor_comunidades_anuncios';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $tabla_anuncios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comunidad_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            titulo varchar(200) NOT NULL,
            contenido text NOT NULL,
            categoria varchar(50) DEFAULT 'general',
            destacado tinyint(1) DEFAULT 0,
            compartir_red tinyint(1) DEFAULT 0,
            fecha_expiracion date DEFAULT NULL,
            estado enum('borrador','publicado','archivado') DEFAULT 'publicado',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY comunidad_id (comunidad_id),
            KEY user_id (user_id),
            KEY categoria (categoria),
            KEY estado (estado),
            KEY destacado (destacado)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Verifica si un usuario puede publicar anuncios en una comunidad
     *
     * @param int $usuario_id ID del usuario
     * @param int $comunidad_id ID de la comunidad
     * @return bool
     */
    private function usuario_puede_publicar_anuncio($usuario_id, $comunidad_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $rol = $wpdb->get_var($wpdb->prepare(
            "SELECT rol FROM $tabla_miembros
             WHERE comunidad_id = %d AND user_id = %d AND estado = 'activo'",
            $comunidad_id, $usuario_id
        ));

        return in_array($rol, ['admin', 'creador', 'moderador']);
    }

    /**
     * Obtiene anuncios de la red federada
     *
     * @param string $categoria Filtro de categoría
     * @param int    $limite Límite de resultados
     * @return array
     */
    private function obtener_anuncios_federados($categoria, $limite) {
        global $wpdb;
        $tabla_shared = $wpdb->prefix . 'flavor_network_shared_content';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_shared'") !== $tabla_shared) {
            return [];
        }

        $where_categoria = $categoria !== 'todos'
            ? $wpdb->prepare(" AND JSON_UNQUOTE(JSON_EXTRACT(s.metadata, '$.categoria')) = %s", $categoria)
            : '';

        $query = $wpdb->prepare(
            "SELECT s.*, n.nombre AS nodo_nombre
             FROM $tabla_shared s
             LEFT JOIN {$wpdb->prefix}flavor_network_nodes n ON n.id = s.nodo_id
             WHERE s.tipo_contenido = 'anuncio'
               AND s.estado = 'activo'
               AND s.visible_red = 1
               {$where_categoria}
             ORDER BY s.fecha_creacion DESC
             LIMIT %d",
            $limite
        );

        $anuncios_red = $wpdb->get_results($query);
        $resultado = [];

        foreach ($anuncios_red as $anuncio) {
            $metadata = json_decode($anuncio->metadata ?? '{}', true);

            $resultado[] = [
                'id'               => $anuncio->id,
                'titulo'           => $anuncio->titulo,
                'contenido'        => $anuncio->descripcion,
                'categoria'        => $metadata['categoria'] ?? 'general',
                'destacado'        => (bool) ($metadata['destacado'] ?? false),
                'fecha'            => date_i18n('j M Y', strtotime($anuncio->fecha_creacion)),
                'comunidad_id'     => 0,
                'comunidad_nombre' => $anuncio->nodo_nombre ?? __('Red federada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'comunidad_imagen' => '',
                'url'              => $anuncio->url_externa,
                'origen'           => 'federado',
            ];
        }

        return $resultado;
    }

    /**
     * Comparte un anuncio en la red federada
     *
     * @param int $anuncio_id ID del anuncio
     */
    private function compartir_anuncio_en_red($anuncio_id) {
        global $wpdb;
        $tabla_anuncios = $wpdb->prefix . 'flavor_comunidades_anuncios';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $anuncio = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, c.nombre AS comunidad_nombre, c.slug AS comunidad_slug
             FROM $tabla_anuncios a
             INNER JOIN $tabla_comunidades c ON c.id = a.comunidad_id
             WHERE a.id = %d",
            $anuncio_id
        ));

        if (!$anuncio) {
            return;
        }

        // Usar el Network Content Bridge para compartir
        do_action('flavor_share_to_network', [
            'tipo_contenido' => 'anuncio',
            'titulo'         => $anuncio->titulo,
            'descripcion'    => $anuncio->contenido,
            'url_externa'    => home_url('/mi-portal/comunidades/' . intval($anuncio->comunidad_id) . '/'),
            'metadata'       => [
                'categoria'  => $anuncio->categoria,
                'destacado'  => (bool) $anuncio->destacado,
                'comunidad'  => $anuncio->comunidad_nombre,
            ],
        ]);
    }

    // =========================================================================
    // MÉTRICAS DE COLABORACIÓN
    // =========================================================================

    /**
     * AJAX: Obtiene métricas de colaboración entre comunidades
     */
    public function ajax_obtener_metricas() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $periodo = isset($_POST['periodo']) ? intval($_POST['periodo']) : 30;
        $fecha_inicio = date('Y-m-d', strtotime("-{$periodo} days"));

        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
        $tabla_shared = $wpdb->prefix . 'flavor_network_shared_content';

        // Resumen general
        $comunidades_activas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT c.id)
             FROM $tabla_comunidades c
             INNER JOIN $tabla_actividad a ON a.comunidad_id = c.id
             WHERE c.estado = 'activa' AND a.created_at >= %s",
            $fecha_inicio
        ));

        $total_publicaciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_actividad WHERE created_at >= %s",
            $fecha_inicio
        ));

        // Colaboraciones (cross-posts y recursos compartidos)
        $colaboraciones = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_actividad
             WHERE referencia_original IS NOT NULL AND created_at >= %s",
            $fecha_inicio
        )) ?: 0;

        // Contenido federado
        $contenido_federado = 0;
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_shared'") === $tabla_shared) {
            $contenido_federado = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_shared WHERE fecha_creacion >= %s",
                $fecha_inicio
            )) ?: 0;
        }

        // Top 5 comunidades más activas
        $top_comunidades = $wpdb->get_results($wpdb->prepare(
            "SELECT c.id, c.nombre,
                    (SELECT COUNT(*) FROM $tabla_miembros WHERE comunidad_id = c.id AND estado = 'activo') AS miembros,
                    COUNT(a.id) AS actividad
             FROM $tabla_comunidades c
             LEFT JOIN $tabla_actividad a ON a.comunidad_id = c.id AND a.created_at >= %s
             WHERE c.estado = 'activa'
             GROUP BY c.id
             ORDER BY actividad DESC
             LIMIT 5",
            $fecha_inicio
        ));

        // Tipos de colaboración
        $tipos_colaboracion = [
            ['tipo' => 'publicacion', 'label' => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '📝', 'cantidad' => 0],
            ['tipo' => 'crosspost', 'label' => __('Cross-posts', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '🔄', 'cantidad' => 0],
            ['tipo' => 'evento', 'label' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '📅', 'cantidad' => 0],
            ['tipo' => 'recurso', 'label' => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '📦', 'cantidad' => 0],
            ['tipo' => 'anuncio', 'label' => __('Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icono' => '📢', 'cantidad' => 0],
        ];

        // Contar por tipo
        $conteos_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, COUNT(*) AS cantidad
             FROM $tabla_actividad
             WHERE created_at >= %s
             GROUP BY tipo",
            $fecha_inicio
        ));

        foreach ($conteos_tipo as $conteo) {
            foreach ($tipos_colaboracion as &$tipo) {
                if ($tipo['tipo'] === $conteo->tipo) {
                    $tipo['cantidad'] = (int) $conteo->cantidad;
                    break;
                }
            }
        }

        // Actividad reciente (últimas 10 colaboraciones)
        $actividad_reciente = $wpdb->get_results($wpdb->prepare(
            "SELECT a.tipo, a.titulo, a.created_at,
                    c.nombre AS comunidad_nombre,
                    u.display_name AS usuario
             FROM $tabla_actividad a
             INNER JOIN $tabla_comunidades c ON c.id = a.comunidad_id
             LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
             WHERE a.created_at >= %s
             ORDER BY a.created_at DESC
             LIMIT 10",
            $fecha_inicio
        ));

        $actividad_formateada = array_map(function($act) {
            return [
                'descripcion' => sprintf(
                    __('%s publicó en %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $act->usuario ?: __('Alguien', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $act->comunidad_nombre
                ),
                'fecha' => human_time_diff(strtotime($act->created_at), current_time('timestamp')) . ' ' . __('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }, $actividad_reciente);

        // Recursos más compartidos (simulado basado en actividad)
        $recursos_compartidos = $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, tipo, COUNT(*) AS compartidos
             FROM $tabla_actividad
             WHERE referencia_original IS NOT NULL AND created_at >= %s
             GROUP BY referencia_original
             ORDER BY compartidos DESC
             LIMIT 5",
            $fecha_inicio
        ));

        if (empty($recursos_compartidos)) {
            $recursos_compartidos = [];
        }

        // Conexiones entre comunidades (basado en cross-posts)
        $conexiones = $wpdb->get_results($wpdb->prepare(
            "SELECT
                c1.nombre AS comunidad_a,
                c2.nombre AS comunidad_b,
                COUNT(*) AS interacciones
             FROM $tabla_actividad a1
             INNER JOIN $tabla_actividad a2 ON a1.referencia_original = a2.id
             INNER JOIN $tabla_comunidades c1 ON c1.id = a1.comunidad_id
             INNER JOIN $tabla_comunidades c2 ON c2.id = a2.comunidad_id
             WHERE a1.created_at >= %s AND c1.id != c2.id
             GROUP BY c1.id, c2.id
             ORDER BY interacciones DESC
             LIMIT 5",
            $fecha_inicio
        ));

        if (empty($conexiones)) {
            $conexiones = [];
        }

        // Métricas federadas
        $federado = [
            'nodos_conectados' => 0,
            'contenido_recibido' => 0,
            'contenido_compartido' => 0,
            'puntuacion_nodo' => 0,
        ];

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_shared'") === $tabla_shared) {
            $tabla_nodos = $wpdb->prefix . 'flavor_network_nodes';

            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_nodos'") === $tabla_nodos) {
                $federado['nodos_conectados'] = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM $tabla_nodos WHERE estado = 'activo'"
                );
            }

            $federado['contenido_recibido'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_shared WHERE fecha_creacion >= %s",
                $fecha_inicio
            ));

            // Contenido local compartido a la red
            $federado['contenido_compartido'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_actividad
                 WHERE created_at >= %s
                 AND JSON_EXTRACT(metadata, '$.compartido_red') = true",
                $fecha_inicio
            ));

            // Puntuación del nodo (basada en actividad)
            $federado['puntuacion_nodo'] = min(100, round(
                ($federado['contenido_compartido'] * 2) +
                ($federado['contenido_recibido'] * 1) +
                ($federado['nodos_conectados'] * 5)
            ));
        }

        wp_send_json_success([
            'resumen' => [
                'comunidades_activas' => (int) $comunidades_activas,
                'colaboraciones' => (int) $colaboraciones,
                'publicaciones' => (int) $total_publicaciones,
                'contenido_federado' => (int) $contenido_federado,
            ],
            'top_comunidades' => $top_comunidades,
            'tipos_colaboracion' => $tipos_colaboracion,
            'actividad_reciente' => $actividad_formateada,
            'recursos_compartidos' => $recursos_compartidos,
            'conexiones' => $conexiones,
            'federado' => $federado,
        ]);
    }

    // =========================================================================
    // BÚSQUEDA FEDERADA UNIFICADA
    // =========================================================================

    /**
     * AJAX: Ejecuta búsqueda federada unificada
     */
    public function ajax_busqueda_federada() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'todos';
        $origen = isset($_POST['origen']) ? sanitize_text_field($_POST['origen']) : 'todos';
        $pagina = isset($_POST['pagina']) ? max(1, intval($_POST['pagina'])) : 1;
        $por_pagina = 15;

        if (empty($termino) || strlen($termino) < 2) {
            wp_send_json_error(['message' => __('Término de búsqueda muy corto', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $resultados = [];
        $total = 0;

        // Buscar en contenido local
        if ($origen === 'todos' || $origen === 'local') {
            $resultados_locales = $this->buscar_contenido_local($termino, $tipo, $por_pagina);
            foreach ($resultados_locales as $item) {
                $item->origen = 'local';
                $resultados[] = $item;
            }
        }

        // Buscar en contenido federado
        if ($origen === 'todos' || $origen === 'federado') {
            $resultados_federados = $this->buscar_contenido_federado($termino, $tipo, $por_pagina);
            foreach ($resultados_federados as $item) {
                $item->origen = 'federado';
                $resultados[] = $item;
            }
        }

        // Ordenar por relevancia (fecha más reciente primero)
        usort($resultados, function($a, $b) {
            $fecha_a = strtotime($a->fecha ?? '1970-01-01');
            $fecha_b = strtotime($b->fecha ?? '1970-01-01');
            return $fecha_b - $fecha_a;
        });

        $total = count($resultados);

        // Paginación
        $offset = ($pagina - 1) * $por_pagina;
        $resultados_paginados = array_slice($resultados, $offset, $por_pagina);

        // Formatear resultados
        $resultados_formateados = array_map(function($item) {
            return [
                'id'          => $item->id ?? 0,
                'tipo'        => $item->tipo ?? 'contenido',
                'tipo_label'  => $this->obtener_label_tipo($item->tipo ?? 'contenido'),
                'titulo'      => $item->titulo ?? '',
                'descripcion' => $item->descripcion ?? '',
                'imagen'      => $item->imagen ?? '',
                'url'         => $item->url ?? '',
                'autor'       => $item->autor ?? '',
                'fecha'       => $item->fecha ? date_i18n('j M Y', strtotime($item->fecha)) : '',
                'origen'      => $item->origen ?? 'local',
                'nodo_nombre' => $item->nodo_nombre ?? '',
                'icono'       => $this->obtener_icono_tipo($item->tipo ?? 'contenido'),
            ];
        }, $resultados_paginados);

        wp_send_json_success([
            'resultados' => $resultados_formateados,
            'total'      => $total,
            'pagina'     => $pagina,
            'paginas'    => ceil($total / $por_pagina),
        ]);
    }

    /**
     * Busca contenido en la base de datos local
     *
     * @param string $termino Término de búsqueda
     * @param string $tipo Tipo de contenido
     * @param int    $limite Límite de resultados
     * @return array
     */
    private function buscar_contenido_local($termino, $tipo, $limite = 20) {
        global $wpdb;
        $resultados = [];
        $termino_like = '%' . $wpdb->esc_like($termino) . '%';

        // Buscar comunidades
        if ($tipo === 'todos' || $tipo === 'comunidades') {
            $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
            $comunidades = $wpdb->get_results($wpdb->prepare(
                "SELECT id, nombre AS titulo, descripcion, imagen, slug, created_at AS fecha, 'comunidades' AS tipo
                 FROM $tabla_comunidades
                 WHERE (nombre LIKE %s OR descripcion LIKE %s) AND estado = 'activa'
                 LIMIT %d",
                $termino_like, $termino_like, $limite
            ));

            foreach ($comunidades as $comunidad) {
                $comunidad->url = home_url('/mi-portal/comunidades/' . $comunidad->id . '/');
                $resultados[] = $comunidad;
            }
        }

        // Buscar publicaciones en comunidades
        if ($tipo === 'todos' || $tipo === 'publicaciones') {
            $tabla_actividad = $wpdb->prefix . 'flavor_comunidades_actividad';
            $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

            $publicaciones = $wpdb->get_results($wpdb->prepare(
                "SELECT a.id, a.titulo, a.contenido AS descripcion, a.imagen, a.created_at AS fecha,
                        'publicaciones' AS tipo, c.id AS comunidad_id, c.slug AS comunidad_slug, u.display_name AS autor
                 FROM $tabla_actividad a
                 INNER JOIN $tabla_comunidades c ON c.id = a.comunidad_id
                 LEFT JOIN {$wpdb->users} u ON u.ID = a.user_id
                 WHERE (a.titulo LIKE %s OR a.contenido LIKE %s)
                 ORDER BY a.created_at DESC
                 LIMIT %d",
                $termino_like, $termino_like, $limite
            ));

            foreach ($publicaciones as $pub) {
                $pub->url = home_url('/mi-portal/comunidades/' . $pub->comunidad_id . '/#actividad-' . $pub->id);
                $resultados[] = $pub;
            }
        }

        // Buscar eventos
        if ($tipo === 'todos' || $tipo === 'eventos') {
            $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_eventos'") === $tabla_eventos) {
                $eventos = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, titulo, descripcion, imagen, fecha_inicio AS fecha, 'eventos' AS tipo, slug
                     FROM $tabla_eventos
                     WHERE (titulo LIKE %s OR descripcion LIKE %s) AND estado = 'publicado'
                     ORDER BY fecha_inicio DESC
                     LIMIT %d",
                    $termino_like, $termino_like, $limite
                ));

                foreach ($eventos as $evento) {
                    $evento->url = home_url('/mi-portal/eventos/' . $evento->id . '/');
                    $resultados[] = $evento;
                }
            }
        }

        // Buscar recetas
        if ($tipo === 'todos' || $tipo === 'recetas') {
            $tabla_recetas = $wpdb->prefix . 'flavor_recetas';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_recetas'") === $tabla_recetas) {
                $recetas = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, titulo, descripcion, imagen, created_at AS fecha, 'recetas' AS tipo, slug
                     FROM $tabla_recetas
                     WHERE (titulo LIKE %s OR descripcion LIKE %s OR ingredientes LIKE %s) AND estado = 'publicada'
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $termino_like, $termino_like, $termino_like, $limite
                ));

                foreach ($recetas as $receta) {
                    $receta->url = add_query_arg('id', $receta->id, home_url('/mi-portal/recetas/'));
                    $resultados[] = $receta;
                }
            }
        }

        // Buscar en biblioteca
        if ($tipo === 'todos' || $tipo === 'biblioteca') {
            $tabla_biblioteca = $wpdb->prefix . 'flavor_biblioteca';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_biblioteca'") === $tabla_biblioteca) {
                $documentos = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, titulo, descripcion, NULL AS imagen, created_at AS fecha, 'biblioteca' AS tipo, slug
                     FROM $tabla_biblioteca
                     WHERE (titulo LIKE %s OR descripcion LIKE %s) AND estado = 'publicado'
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $termino_like, $termino_like, $limite
                ));

                foreach ($documentos as $doc) {
                    $doc->url = add_query_arg('libro_id', $doc->id, home_url('/mi-portal/biblioteca/'));
                    $resultados[] = $doc;
                }
            }
        }

        // Buscar multimedia
        if ($tipo === 'todos' || $tipo === 'multimedia') {
            $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_multimedia'") === $tabla_multimedia) {
                $multimedia = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, titulo, descripcion, thumbnail AS imagen, created_at AS fecha, 'multimedia' AS tipo, slug
                     FROM $tabla_multimedia
                     WHERE (titulo LIKE %s OR descripcion LIKE %s) AND estado = 'publicado'
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $termino_like, $termino_like, $limite
                ));

                foreach ($multimedia as $media) {
                    $media->url = add_query_arg(
                        'archivo_id',
                        intval($media->id),
                        home_url('/mi-portal/multimedia/mi-galeria/')
                    );
                    $resultados[] = $media;
                }
            }
        }

        return $resultados;
    }

    /**
     * Busca contenido en la red federada
     *
     * @param string $termino Término de búsqueda
     * @param string $tipo Tipo de contenido
     * @param int    $limite Límite de resultados
     * @return array
     */
    private function buscar_contenido_federado($termino, $tipo, $limite = 20) {
        if (!class_exists('Flavor_Network_Content_Bridge')) {
            return [];
        }

        global $wpdb;
        $resultados = [];
        $tabla_shared = $wpdb->prefix . 'flavor_network_shared_content';

        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla_shared'") !== $tabla_shared) {
            return [];
        }

        $termino_like = '%' . $wpdb->esc_like($termino) . '%';

        // Mapear tipos a tipos de contenido de red
        $tipos_red = [];
        if ($tipo === 'todos') {
            $tipos_red = ['comunidades', 'grupos_consumo', 'banco_tiempo', 'eventos', 'recetas', 'biblioteca', 'multimedia'];
        } else {
            $tipos_red = [$tipo];
        }

        $placeholders = implode(',', array_fill(0, count($tipos_red), '%s'));

        $query = $wpdb->prepare(
            "SELECT s.*, n.nombre AS nodo_nombre, n.logo_url AS nodo_logo
             FROM $tabla_shared s
             LEFT JOIN {$wpdb->prefix}flavor_network_nodes n ON n.id = s.nodo_id
             WHERE (s.titulo LIKE %s OR s.descripcion LIKE %s)
               AND s.tipo_contenido IN ($placeholders)
               AND s.estado = 'activo'
               AND s.visible_red = 1
             ORDER BY s.fecha_creacion DESC
             LIMIT %d",
            array_merge([$termino_like, $termino_like], $tipos_red, [$limite])
        );

        $contenido_red = $wpdb->get_results($query);

        foreach ($contenido_red as $item) {
            $resultados[] = (object) [
                'id'          => $item->id,
                'tipo'        => $item->tipo_contenido,
                'titulo'      => $item->titulo,
                'descripcion' => $item->descripcion,
                'imagen'      => $item->imagen_url,
                'url'         => $item->url_externa,
                'fecha'       => $item->fecha_creacion,
                'autor'       => '',
                'nodo_nombre' => $item->nodo_nombre ?? '',
                'nodo_logo'   => $item->nodo_logo ?? '',
            ];
        }

        return $resultados;
    }

    /**
     * Obtiene el label legible de un tipo de contenido
     *
     * @param string $tipo Tipo de contenido
     * @return string
     */
    private function obtener_label_tipo($tipo) {
        $labels = [
            'comunidades'   => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'publicaciones' => __('Publicación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'eventos'       => __('Evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'recetas'       => __('Receta', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'biblioteca'    => __('Documento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'multimedia'    => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grupos_consumo' => __('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'banco_tiempo'  => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $labels[$tipo] ?? ucfirst($tipo);
    }

    /**
     * Obtiene el icono de un tipo de contenido
     *
     * @param string $tipo Tipo de contenido
     * @return string
     */
    private function obtener_icono_tipo($tipo) {
        $iconos = [
            'comunidades'   => 'groups',
            'publicaciones' => 'admin-post',
            'eventos'       => 'calendar-alt',
            'recetas'       => 'carrot',
            'biblioteca'    => 'book',
            'multimedia'    => 'format-gallery',
            'grupos_consumo' => 'cart',
            'banco_tiempo'  => 'clock',
        ];

        return $iconos[$tipo] ?? 'admin-site';
    }

    // =========================================================================
    // SISTEMA DE NOTIFICACIONES CROSS-COMUNIDAD
    // =========================================================================

    /**
     * Notifica a los miembros de comunidades relacionadas sobre una nueva publicación
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param int    $publicacion_id ID de la publicación
     * @param object $publicacion Datos de la publicación
     */
    public function notificar_nueva_publicacion($comunidad_id, $publicacion_id, $publicacion) {
        if (!class_exists('Flavor_Notifications_System')) {
            return;
        }

        $comunidad = $this->obtener_comunidad($comunidad_id);
        if (!$comunidad) {
            return;
        }

        // Obtener comunidades relacionadas por categoría
        $comunidades_relacionadas = $this->obtener_comunidades_relacionadas($comunidad_id);

        // Obtener miembros que siguen actividad de comunidades relacionadas
        foreach ($comunidades_relacionadas as $comunidad_relacionada) {
            $miembros_interesados = $this->obtener_miembros_con_preferencia(
                $comunidad_relacionada->id,
                'notificar_comunidades_relacionadas'
            );

            foreach ($miembros_interesados as $miembro_id) {
                // Verificar que no sea el autor
                if ($miembro_id == ($publicacion->user_id ?? 0)) {
                    continue;
                }

                $notificacion = Flavor_Notifications_System::get_instance();
                $notificacion->create(
                    $miembro_id,
                    'comunidad_relacionada',
                    sprintf(__('Nueva actividad en %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad->nombre),
                    sprintf(
                        __('Hay una nueva publicación en la comunidad relacionada "%s"', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $comunidad->nombre
                    ),
                    [
                        'link' => home_url('/mi-portal/comunidades/' . intval($comunidad->id) . '/'),
                        'icon' => '🏘️',
                    ]
                );
            }
        }

        // Notificar a todos los miembros de la comunidad (excepto el autor)
        $this->notificar_miembros_comunidad(
            $comunidad_id,
            'nueva_publicacion',
            sprintf(__('Nueva publicación en %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad->nombre),
            sprintf(
                __('%s publicó en %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                get_userdata($publicacion->user_id)->display_name ?? __('Alguien', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $comunidad->nombre
            ),
            [
                'link' => home_url('/mi-portal/comunidades/' . intval($comunidad->id) . '/#actividad-' . intval($publicacion_id)),
                'icon' => '📝',
                'excluir_usuario' => $publicacion->user_id ?? 0,
            ]
        );
    }

    /**
     * Notifica sobre un nuevo evento en una comunidad
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param int    $evento_id ID del evento
     * @param object $evento Datos del evento
     */
    public function notificar_nuevo_evento($comunidad_id, $evento_id, $evento) {
        $comunidad = $this->obtener_comunidad($comunidad_id);
        if (!$comunidad) {
            return;
        }

        $fecha_evento = isset($evento->fecha_inicio)
            ? date_i18n('j M Y H:i', strtotime($evento->fecha_inicio))
            : '';

        // Notificar a miembros de la comunidad
        $this->notificar_miembros_comunidad(
            $comunidad_id,
            'nuevo_evento',
            sprintf(__('Nuevo evento: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $evento->titulo ?? ''),
            sprintf(
                __('Se ha creado el evento "%s" para %s en la comunidad %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $evento->titulo ?? '',
                $fecha_evento,
                $comunidad->nombre
            ),
            [
                'link' => home_url('/mi-portal/eventos/' . intval($evento_id) . '/'),
                'icon' => '📅',
            ]
        );

        // Notificar a comunidades relacionadas con eventos similares
        $comunidades_relacionadas = $this->obtener_comunidades_relacionadas($comunidad_id);
        foreach ($comunidades_relacionadas as $comunidad_relacionada) {
            $miembros = $this->obtener_miembros_con_preferencia(
                $comunidad_relacionada->id,
                'notificar_eventos_red'
            );

            foreach ($miembros as $miembro_id) {
                $this->crear_notificacion_usuario(
                    $miembro_id,
                    'evento_red',
                    sprintf(__('Evento en la red: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $evento->titulo ?? ''),
                    sprintf(
                        __('La comunidad "%s" organiza: %s el %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $comunidad->nombre,
                        $evento->titulo ?? '',
                        $fecha_evento
                    ),
                    [
                        'link' => home_url('/mi-portal/eventos/' . intval($evento_id) . '/'),
                        'icon' => '🗓️',
                    ]
                );
            }
        }
    }

    /**
     * Notifica cuando un nuevo miembro se une a la comunidad
     *
     * @param int $comunidad_id ID de la comunidad
     * @param int $usuario_id ID del nuevo miembro
     */
    public function notificar_nuevo_miembro($comunidad_id, $usuario_id) {
        $comunidad = $this->obtener_comunidad($comunidad_id);
        if (!$comunidad) {
            return;
        }

        $nuevo_miembro = get_userdata($usuario_id);
        if (!$nuevo_miembro) {
            return;
        }

        // Notificar a los administradores de la comunidad
        $admins = $this->obtener_administradores_comunidad($comunidad_id);
        foreach ($admins as $admin_id) {
            if ($admin_id == $usuario_id) {
                continue;
            }

            $this->crear_notificacion_usuario(
                $admin_id,
                'nuevo_miembro',
                __('Nuevo miembro en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                sprintf(
                    __('%s se ha unido a %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $nuevo_miembro->display_name,
                    $comunidad->nombre
                ),
                [
                    'link' => add_query_arg(['comunidad_id' => intval($comunidad->id), 'tab' => 'miembros'], home_url('/mi-portal/comunidades/')),
                    'icon' => '👋',
                ]
            );
        }

        // Notificar al usuario sobre comunidades relacionadas
        $comunidades_relacionadas = $this->obtener_comunidades_relacionadas($comunidad_id);
        if (!empty($comunidades_relacionadas)) {
            $nombres_comunidades = array_slice(array_map(function($c) {
                return $c->nombre;
            }, $comunidades_relacionadas), 0, 3);

            $this->crear_notificacion_usuario(
                $usuario_id,
                'comunidades_sugeridas',
                __('Comunidades que te pueden interesar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                sprintf(
                    __('Como miembro de %s, quizás te interesen: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $comunidad->nombre,
                    implode(', ', $nombres_comunidades)
                ),
                [
                    'link' => home_url('/mi-portal/comunidades/'),
                    'icon' => '🎯',
                ]
            );
        }
    }

    /**
     * Notifica cuando se comparte un recurso entre comunidades
     *
     * @param int    $comunidad_origen_id ID de la comunidad origen
     * @param int    $comunidad_destino_id ID de la comunidad destino
     * @param string $tipo_recurso Tipo de recurso compartido
     * @param object $recurso Datos del recurso
     */
    public function notificar_recurso_compartido($comunidad_origen_id, $comunidad_destino_id, $tipo_recurso, $recurso) {
        $comunidad_origen = $this->obtener_comunidad($comunidad_origen_id);
        $comunidad_destino = $this->obtener_comunidad($comunidad_destino_id);

        if (!$comunidad_origen || !$comunidad_destino) {
            return;
        }

        $tipos_iconos = [
            'receta'     => '🍳',
            'documento'  => '📄',
            'multimedia' => '🎬',
            'podcast'    => '🎙️',
            'evento'     => '📅',
        ];

        $icono = $tipos_iconos[$tipo_recurso] ?? '📦';

        // Notificar a miembros de la comunidad destino
        $this->notificar_miembros_comunidad(
            $comunidad_destino_id,
            'recurso_compartido',
            sprintf(__('Recurso compartido desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad_origen->nombre),
            sprintf(
                __('La comunidad "%s" ha compartido: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $comunidad_origen->nombre,
                $recurso->titulo ?? $tipo_recurso
            ),
            [
                'link' => add_query_arg(['comunidad_id' => intval($comunidad_destino->id), 'tab' => 'recursos'], home_url('/mi-portal/comunidades/')),
                'icon' => $icono,
            ]
        );
    }

    /**
     * Notifica cuando alguien menciona a un usuario
     *
     * @param int    $usuario_mencionado_id Usuario mencionado
     * @param int    $usuario_autor_id Usuario que menciona
     * @param int    $comunidad_id ID de la comunidad
     * @param object $contexto Datos del contexto (publicación, comentario, etc.)
     */
    public function notificar_mencion($usuario_mencionado_id, $usuario_autor_id, $comunidad_id, $contexto) {
        if ($usuario_mencionado_id == $usuario_autor_id) {
            return;
        }

        $comunidad = $this->obtener_comunidad($comunidad_id);
        $autor = get_userdata($usuario_autor_id);

        if (!$comunidad || !$autor) {
            return;
        }

        $this->crear_notificacion_usuario(
            $usuario_mencionado_id,
            'mencion',
            __('Te han mencionado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            sprintf(
                __('%s te ha mencionado en %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $autor->display_name,
                $comunidad->nombre
            ),
            [
                'link' => $contexto->url ?? home_url('/mi-portal/comunidades/' . intval($comunidad->id) . '/'),
                'icon' => '💬',
            ]
        );
    }

    /**
     * Notifica sobre contenido federado relevante
     *
     * @param string $tipo_contenido Tipo de contenido
     * @param object $contenido Datos del contenido federado
     */
    public function notificar_contenido_federado($tipo_contenido, $contenido) {
        // Solo notificar si el contenido es relevante para las comunidades del usuario
        $usuarios_interesados = $this->obtener_usuarios_interesados_en_tipo($tipo_contenido);

        foreach ($usuarios_interesados as $usuario_id) {
            // Verificar preferencias del usuario
            if (!$this->usuario_acepta_notificaciones_federadas($usuario_id)) {
                continue;
            }

            $this->crear_notificacion_usuario(
                $usuario_id,
                'contenido_federado',
                sprintf(__('Nuevo contenido en la red: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $tipo_contenido),
                sprintf(
                    __('"%s" desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $contenido->titulo ?? __('Sin título', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $contenido->nodo_nombre ?? __('Red federada', FLAVOR_PLATFORM_TEXT_DOMAIN)
                ),
                [
                    'link' => $contenido->url_externa ?? '#',
                    'icon' => '🌐',
                ]
            );
        }
    }

    /**
     * Notifica sobre contenido cross-posteado
     *
     * @param int    $publicacion_original_id ID de la publicación original
     * @param int    $comunidad_origen_id Comunidad de origen
     * @param int    $comunidad_destino_id Comunidad de destino
     * @param int    $usuario_id Usuario que hace el crosspost
     */
    public function notificar_crosspost($publicacion_original_id, $comunidad_origen_id, $comunidad_destino_id, $usuario_id) {
        $comunidad_origen = $this->obtener_comunidad($comunidad_origen_id);
        $comunidad_destino = $this->obtener_comunidad($comunidad_destino_id);
        $usuario = get_userdata($usuario_id);

        if (!$comunidad_origen || !$comunidad_destino || !$usuario) {
            return;
        }

        // Notificar a la comunidad destino
        $this->notificar_miembros_comunidad(
            $comunidad_destino_id,
            'crosspost',
            sprintf(__('Contenido compartido desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $comunidad_origen->nombre),
            sprintf(
                __('%s ha compartido una publicación de %s en esta comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $usuario->display_name,
                $comunidad_origen->nombre
            ),
            [
                'link' => home_url('/mi-portal/comunidades/' . intval($comunidad_destino->id) . '/'),
                'icon' => '🔄',
                'excluir_usuario' => $usuario_id,
            ]
        );
    }

    // =========================================================================
    // MÉTODOS AUXILIARES DE NOTIFICACIONES
    // =========================================================================

    /**
     * Obtiene comunidades relacionadas por categoría
     *
     * @param int $comunidad_id ID de la comunidad
     * @return array Comunidades relacionadas
     */
    private function obtener_comunidades_relacionadas($comunidad_id) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        $comunidad = $this->obtener_comunidad($comunidad_id);
        if (!$comunidad || empty($comunidad->categoria)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, nombre, slug, categoria
             FROM $tabla_comunidades
             WHERE categoria = %s
               AND id != %d
               AND estado = 'activa'
             LIMIT 10",
            $comunidad->categoria,
            $comunidad_id
        ));
    }

    /**
     * Obtiene miembros con una preferencia específica activa
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param string $preferencia Clave de preferencia
     * @return array IDs de usuarios
     */
    private function obtener_miembros_con_preferencia($comunidad_id, $preferencia) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $miembros = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $tabla_miembros
             WHERE comunidad_id = %d AND estado = 'activo'",
            $comunidad_id
        ));

        // Filtrar por preferencia
        return array_filter($miembros, function($usuario_id) use ($preferencia) {
            $preferencias = get_user_meta($usuario_id, 'flavor_notificaciones_comunidades', true);
            $preferencias = is_array($preferencias) ? $preferencias : [];
            return !isset($preferencias[$preferencia]) || $preferencias[$preferencia] !== false;
        });
    }

    /**
     * Obtiene administradores de una comunidad
     *
     * @param int $comunidad_id ID de la comunidad
     * @return array IDs de administradores
     */
    private function obtener_administradores_comunidad($comunidad_id) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        return $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $tabla_miembros
             WHERE comunidad_id = %d
               AND rol IN ('admin', 'creador')
               AND estado = 'activo'",
            $comunidad_id
        ));
    }

    /**
     * Notifica a todos los miembros de una comunidad
     *
     * @param int    $comunidad_id ID de la comunidad
     * @param string $tipo Tipo de notificación
     * @param string $titulo Título
     * @param string $mensaje Mensaje
     * @param array  $args Argumentos adicionales
     */
    private function notificar_miembros_comunidad($comunidad_id, $tipo, $titulo, $mensaje, $args = []) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';

        $excluir_usuario = $args['excluir_usuario'] ?? 0;

        $miembros = $wpdb->get_col($wpdb->prepare(
            "SELECT user_id FROM $tabla_miembros
             WHERE comunidad_id = %d AND estado = 'activo'",
            $comunidad_id
        ));

        foreach ($miembros as $miembro_id) {
            if ($miembro_id == $excluir_usuario) {
                continue;
            }

            // Verificar preferencias del usuario para este tipo
            if (!$this->usuario_acepta_notificacion($miembro_id, $tipo)) {
                continue;
            }

            $this->crear_notificacion_usuario($miembro_id, $tipo, $titulo, $mensaje, $args);
        }
    }

    /**
     * Crea una notificación para un usuario
     *
     * @param int    $usuario_id ID del usuario
     * @param string $tipo Tipo de notificación
     * @param string $titulo Título
     * @param string $mensaje Mensaje
     * @param array  $args Argumentos adicionales
     */
    private function crear_notificacion_usuario($usuario_id, $tipo, $titulo, $mensaje, $args = []) {
        if (!class_exists('Flavor_Notifications_System')) {
            return;
        }

        $notificaciones = Flavor_Notifications_System::get_instance();
        $notificaciones->create(
            $usuario_id,
            $tipo,
            $titulo,
            $mensaje,
            [
                'link' => $args['link'] ?? '',
                'icon' => $args['icon'] ?? '🔔',
            ]
        );
    }

    /**
     * Verifica si un usuario acepta un tipo de notificación
     *
     * @param int    $usuario_id ID del usuario
     * @param string $tipo Tipo de notificación
     * @return bool
     */
    private function usuario_acepta_notificacion($usuario_id, $tipo) {
        $preferencias = get_user_meta($usuario_id, 'flavor_notificaciones_comunidades', true);
        $preferencias = is_array($preferencias) ? $preferencias : [];

        // Por defecto acepta todas las notificaciones
        return !isset($preferencias[$tipo]) || $preferencias[$tipo] !== false;
    }

    /**
     * Verifica si un usuario acepta notificaciones federadas
     *
     * @param int $usuario_id ID del usuario
     * @return bool
     */
    private function usuario_acepta_notificaciones_federadas($usuario_id) {
        $preferencias = get_user_meta($usuario_id, 'flavor_notificaciones_comunidades', true);
        $preferencias = is_array($preferencias) ? $preferencias : [];

        return !isset($preferencias['contenido_federado']) || $preferencias['contenido_federado'] !== false;
    }

    /**
     * Obtiene usuarios interesados en un tipo de contenido
     *
     * @param string $tipo_contenido Tipo de contenido
     * @return array IDs de usuarios
     */
    private function obtener_usuarios_interesados_en_tipo($tipo_contenido) {
        global $wpdb;
        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        // Mapear tipos de contenido a categorías de comunidades
        $mapeo_tipos = [
            'grupos_consumo' => 'consumo',
            'banco_tiempo'   => 'servicios',
            'eventos'        => 'eventos',
            'recetas'        => 'gastronomia',
        ];

        $categoria = $mapeo_tipos[$tipo_contenido] ?? null;

        if (!$categoria) {
            return [];
        }

        return $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT m.user_id
             FROM $tabla_miembros m
             INNER JOIN $tabla_comunidades c ON c.id = m.comunidad_id
             WHERE c.categoria = %s
               AND m.estado = 'activo'
             LIMIT 100",
            $categoria
        ));
    }

    // =========================================================================
    // AJAX HANDLERS PARA NOTIFICACIONES
    // =========================================================================

    /**
     * AJAX: Guarda preferencias de notificación del usuario
     */
    public function ajax_guardar_preferencias_notificacion() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $preferencias_raw = isset($_POST['preferencias']) ? $_POST['preferencias'] : [];
        $preferencias = [];

        // Tipos de notificación permitidos
        $tipos_permitidos = [
            'nueva_publicacion',
            'nuevo_evento',
            'nuevo_miembro',
            'recurso_compartido',
            'mencion',
            'contenido_federado',
            'crosspost',
            'notificar_comunidades_relacionadas',
            'notificar_eventos_red',
        ];

        foreach ($tipos_permitidos as $tipo) {
            $preferencias[$tipo] = isset($preferencias_raw[$tipo]) && $preferencias_raw[$tipo] === 'true';
        }

        update_user_meta($usuario_id, 'flavor_notificaciones_comunidades', $preferencias);

        wp_send_json_success([
            'message' => __('Preferencias guardadas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'preferencias' => $preferencias,
        ]);
    }

    /**
     * AJAX: Obtiene notificaciones del usuario
     */
    public function ajax_obtener_notificaciones() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Notifications_System')) {
            wp_send_json_error(['message' => __('Sistema de notificaciones no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $limite = isset($_POST['limite']) ? intval($_POST['limite']) : 20;
        $solo_no_leidas = isset($_POST['solo_no_leidas']) && $_POST['solo_no_leidas'] === 'true';

        $notificaciones_system = Flavor_Notifications_System::get_instance();
        $notificaciones = $notificaciones_system->get_user_notifications($usuario_id, [
            'limit' => $limite,
            'unread_only' => $solo_no_leidas,
        ]);

        // Filtrar solo notificaciones de comunidades
        $tipos_comunidad = [
            'nueva_publicacion',
            'nuevo_evento',
            'nuevo_miembro',
            'recurso_compartido',
            'mencion',
            'contenido_federado',
            'crosspost',
            'comunidad_relacionada',
            'evento_red',
            'comunidades_sugeridas',
        ];

        $notificaciones_comunidad = array_filter($notificaciones, function($n) use ($tipos_comunidad) {
            return in_array($n->type, $tipos_comunidad);
        });

        $contador_no_leidas = $notificaciones_system->get_unread_count($usuario_id);

        wp_send_json_success([
            'notificaciones' => array_values($notificaciones_comunidad),
            'no_leidas' => $contador_no_leidas,
        ]);
    }

    /**
     * AJAX: Marca una notificación como leída
     */
    public function ajax_marcar_notificacion_leida() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Notifications_System')) {
            wp_send_json_error(['message' => __('Sistema de notificaciones no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificacion_id = isset($_POST['notificacion_id']) ? intval($_POST['notificacion_id']) : 0;
        $marcar_todas = isset($_POST['marcar_todas']) && $_POST['marcar_todas'] === 'true';

        $notificaciones_system = Flavor_Notifications_System::get_instance();

        if ($marcar_todas) {
            $resultado = $notificaciones_system->mark_all_as_read($usuario_id);
        } else {
            $resultado = $notificaciones_system->mark_as_read($notificacion_id, $usuario_id);
        }

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Notificación marcada como leída', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'no_leidas' => $notificaciones_system->get_unread_count($usuario_id),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al marcar notificación', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Marca todas las notificaciones como leídas
     */
    public function ajax_marcar_todas_leidas() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Notifications_System')) {
            wp_send_json_error(['message' => __('Sistema de notificaciones no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificaciones_system = Flavor_Notifications_System::get_instance();
        $resultado = $notificaciones_system->mark_all_as_read($usuario_id);

        if ($resultado !== false) {
            wp_send_json_success([
                'message' => __('Todas las notificaciones marcadas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al marcar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Elimina una notificación
     */
    public function ajax_eliminar_notificacion() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificacion_id = isset($_POST['notificacion_id']) ? intval($_POST['notificacion_id']) : 0;
        if (!$notificacion_id) {
            wp_send_json_error(['message' => __('ID de notificación no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Notifications_System')) {
            wp_send_json_error(['message' => __('Sistema de notificaciones no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $notificaciones_system = Flavor_Notifications_System::get_instance();
        $resultado = $notificaciones_system->delete($notificacion_id, $usuario_id);

        if ($resultado) {
            wp_send_json_success([
                'message' => __('Notificación eliminada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al eliminar notificación', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }
    }

    /**
     * AJAX: Guarda las preferencias de notificaciones
     */
    public function ajax_guardar_preferencias_notificaciones() {
        check_ajax_referer('flavor_comunidades_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $preferencias_json = isset($_POST['preferencias']) ? sanitize_text_field($_POST['preferencias']) : '{}';
        $preferencias = json_decode($preferencias_json, true);

        if (!is_array($preferencias)) {
            wp_send_json_error(['message' => __('Preferencias no válidas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Guardar preferencias en user meta
        update_user_meta($usuario_id, 'comunidades_preferencias_notificaciones', $preferencias);

        wp_send_json_success([
            'message' => __('Preferencias guardadas correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Obtiene datos de una comunidad por ID
     *
     * @param int $comunidad_id ID de la comunidad
     * @return object|null
     */
    private function obtener_comunidad($comunidad_id) {
        global $wpdb;
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT id, creador_id, nombre, descripcion, tipo, categoria, slug, imagen, imagen_portada, ubicacion, reglas, estado, miembros_count, created_at
             FROM $tabla_comunidades
             WHERE id = %d",
            $comunidad_id
        ));
    }

    // =========================================================================
    // PÁGINAS DE ADMINISTRACIÓN
    // =========================================================================

    /**
     * Registra las páginas de administración del módulo (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {

        static $registered = false;
        if ($registered) {
            return;
        }
        $registered = true;

        $capability = 'manage_options';

        // Página principal (oculta)
        add_submenu_page(
            null,
            __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades',
            [$this, 'render_pagina_dashboard']
        );

        // Dashboard - página para panel unificado
        add_submenu_page(
            null,
            __('Dashboard Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-dashboard',
            [$this, 'render_pagina_dashboard']
        );

        // Página: Listado (oculta)
        add_submenu_page(
            null,
            __('Todas las Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Listado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-listado',
            [$this, 'render_admin_listado']
        );

        // Página: Feed de Actividad (oculta)
        add_submenu_page(
            null,
            __('Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-actividad',
            [$this, 'render_pagina_actividad']
        );

        // Página: Métricas (oculta)
        add_submenu_page(
            null,
            __('Métricas de Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Métricas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-metricas',
            [$this, 'render_pagina_metricas']
        );

        // Página: Configuración (oculta)
        add_submenu_page(
            null,
            __('Configuración de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-config',
            [$this, 'render_pagina_config']
        );

        // Página: Editar (oculta)
        add_submenu_page(
            null,
            __('Editar Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-editar',
            [$this, 'render_pagina_editar']
        );

        // Página: Miembros (oculta)
        add_submenu_page(
            null,
            __('Miembros de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-miembros',
            [$this, 'render_pagina_miembros']
        );

        // Página: Nueva (oculta)
        add_submenu_page(
            null,
            __('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Nueva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-nueva',
            [$this, 'render_pagina_nueva']
        );

        // Página: Publicaciones (oculta)
        add_submenu_page(
            null,
            __('Publicaciones de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            $capability,
            'comunidades-publicaciones',
            [$this, 'render_pagina_publicaciones']
        );
    }

    /**
     * Renderiza página dashboard
     */
    public function render_pagina_dashboard() {
        $rutaVistaDashboard = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($rutaVistaDashboard)) {
            include $rutaVistaDashboard;
        } else {
            // Fallback si no existe dashboard.php
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Dashboard Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            $views_path = dirname(__FILE__) . '/views/listado-comunidades.php';
            if (file_exists($views_path)) {
                include $views_path;
            }
            echo '</div>';
        }
    }

    /**
     * Renderiza página de listado
     */
    public function render_pagina_listado() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Todas las Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
        $views_path = dirname(__FILE__) . '/views/listado-comunidades.php';
        if (file_exists($views_path)) {
            include $views_path;
        }
        echo '</div>';
    }

    /**
     * Renderiza página de actividad
     */
    public function render_pagina_actividad() {
        // Primero intentar la vista de admin, luego fallback a frontend
        $views_path = dirname(__FILE__) . '/views/actividad.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap">';
            echo '<h1>' . esc_html__('Feed de Actividad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Renderiza página de métricas
     */
    public function render_pagina_metricas() {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Métricas de Colaboración', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
        $views_path = dirname(__FILE__) . '/views/metricas-colaboracion.php';
        if (file_exists($views_path)) {
            include $views_path;
        }
        echo '</div>';
    }

    /**
     * Renderiza página de configuración
     */
    public function render_pagina_config() {
        $views_path = dirname(__FILE__) . '/views/config.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Configuración de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderiza página de editar
     */
    public function render_pagina_editar() {
        $views_path = dirname(__FILE__) . '/views/editar.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Editar Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderiza página de miembros
     */
    public function render_pagina_miembros() {
        $views_path = dirname(__FILE__) . '/views/miembros.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Miembros de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderiza página de nueva comunidad
     */
    public function render_pagina_nueva() {
        $views_path = dirname(__FILE__) . '/views/nueva.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Nueva Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Renderiza página de publicaciones
     */
    public function render_pagina_publicaciones() {
        $views_path = dirname(__FILE__) . '/views/publicaciones.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Publicaciones de Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h1>';
            echo '<p>' . esc_html__('Vista en desarrollo.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
        }
    }

    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-comunidades-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Comunidades_Dashboard_Tab')) {
                Flavor_Comunidades_Dashboard_Tab::get_instance();
            }
        }
    }
}

if (!class_exists('Flavor_Chat_Comunidades_Module', false)) {
    class_alias('Flavor_Platform_Comunidades_Module', 'Flavor_Chat_Comunidades_Module');
}
