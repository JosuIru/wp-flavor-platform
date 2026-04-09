<?php
/**
 * Perfiles y Layouts del Portal de Usuario
 *
 * Define layouts simples y adaptativos según el tipo de aplicación/comunidad.
 * Evita saturar al usuario mostrando solo lo relevante.
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase de Perfiles de Portal
 */
class Flavor_Portal_Profiles {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Layouts de portal por perfil
     */
    private $portal_layouts = [];

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
     * Constructor privado
     */
    private function __construct() {
        $this->define_portal_layouts();
    }

    /**
     * Define layouts simplificados por perfil de aplicación
     */
    private function define_portal_layouts() {
        $this->portal_layouts = [
            // ====================================================================
            // GRUPO DE CONSUMO - Layout Simple
            // ====================================================================
            'grupo_consumo' => [
                'layout' => 'simple',
                'titulo' => __('Mi Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Gestiona tus pedidos, eventos y participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_simple',
                        'mensaje_bienvenida' => __('¡Hola, {nombre}!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('Bienvenido a tu cooperativa de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '🛒 Hacer Pedido', 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'nuevo-pedido'), 'color' => 'primary'],
                            ['label' => '📅 Próximos Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'proximos'), 'color' => 'secondary'],
                            ['label' => '💶 Mi Cuota', 'url' => Flavor_Chat_Helpers::get_action_url('socios', 'mi-cuota'), 'color' => 'tertiary'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 3, // Solo 3 stats principales
                        'modulos' => ['grupos_consumo', 'socios', 'eventos'],
                    ],
                    'widgets' => [
                        'orden' => ['grupos_consumo', 'eventos', 'socios'],
                        'mostrar_por_defecto' => 2, // Solo 2 widgets visibles
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 5, // Solo últimas 5 actividades
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'tabs', // tabs, sidebar, dropdown
                    'items' => [
                        ['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url()],
                        ['label' => 'Pedidos', 'url' => Flavor_Chat_Helpers::get_action_url('grupos-consumo', 'mis-pedidos')],
                        ['label' => 'Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado')],
                        ['label' => 'Comunidad', 'url' => Flavor_Chat_Helpers::get_action_url('comunidades', '')],
                    ],
                ],
                'busqueda' => false, // No mostrar búsqueda (simplificar)
                'notificaciones' => true,
            ],

            // ====================================================================
            // COMUNIDAD/ASOCIACIÓN - Layout Equilibrado
            // ====================================================================
            'comunidad' => [
                'layout' => 'balanced',
                'titulo' => __('Mi Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tu espacio para participar y conectar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_card',
                        'mensaje_bienvenida' => __('Hola, {nombre}', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('¿Qué quieres hacer hoy?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '📅 Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado'), 'color' => 'primary'],
                            ['label' => '💬 Foros', 'url' => Flavor_Chat_Helpers::get_action_url('foros', ''), 'color' => 'secondary'],
                            ['label' => '🎓 Talleres', 'url' => Flavor_Chat_Helpers::get_action_url('talleres', 'listado'), 'color' => 'tertiary'],
                            ['label' => '🗳️ Participación', 'url' => Flavor_Chat_Helpers::get_action_url('participacion', ''), 'color' => 'success'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 4,
                        'modulos' => ['socios', 'eventos', 'talleres', 'foros'],
                    ],
                    'widgets' => [
                        'orden' => ['eventos', 'talleres', 'foros', 'socios'],
                        'mostrar_por_defecto' => 3,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 8,
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'sidebar',
                    'items' => [
                        ['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url(), 'icon' => 'home'],
                        ['label' => 'Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado'), 'icon' => 'calendar'],
                        ['label' => 'Talleres', 'url' => Flavor_Chat_Helpers::get_action_url('talleres', 'listado'), 'icon' => 'graduation-cap'],
                        ['label' => 'Foros', 'url' => Flavor_Chat_Helpers::get_action_url('foros', ''), 'icon' => 'comments'],
                        ['label' => 'Mi Perfil', 'url' => Flavor_Chat_Helpers::get_action_url('usuario', 'perfil'), 'icon' => 'user'],
                    ],
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],

            // ====================================================================
            // BARRIO/VECINDARIO - Layout Territorial
            // ====================================================================
            'barrio' => [
                'layout' => 'territorial',
                'titulo' => __('Mi Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Ayuda mutua, recursos compartidos y vecindad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_mapa',
                        'mensaje_bienvenida' => __('Hola, vecino/a', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('¿Necesitas ayuda o quieres colaborar?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '🤝 Pedir/Ofrecer Ayuda', 'url' => Flavor_Chat_Helpers::get_action_url('ayuda-vecinal', ''), 'color' => 'primary'],
                            ['label' => '🚲 Reservar Bici', 'url' => Flavor_Chat_Helpers::get_action_url('bicicletas-compartidas', ''), 'color' => 'eco'],
                            ['label' => '🌱 Huertos', 'url' => Flavor_Chat_Helpers::get_action_url('huertos-urbanos', ''), 'color' => 'success'],
                            ['label' => '⚠️ Reportar Incidencia', 'url' => Flavor_Chat_Helpers::get_action_url('incidencias', 'nueva'), 'color' => 'warning'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 4,
                        'modulos' => ['ayuda_vecinal', 'banco_tiempo', 'bicicletas_compartidas', 'huertos_urbanos'],
                    ],
                    'widgets' => [
                        'orden' => ['ayuda_vecinal', 'banco_tiempo', 'bicicletas_compartidas', 'huertos_urbanos', 'incidencias'],
                        'mostrar_por_defecto' => 3,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 6,
                        'agrupada_por' => 'tipo', // agrupar por tipo de actividad
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'tabs',
                    'items' => [
                        ['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url()],
                        ['label' => 'Ayuda', 'url' => Flavor_Chat_Helpers::get_action_url('ayuda-vecinal', '')],
                        ['label' => 'Recursos', 'url' => Flavor_Chat_Helpers::get_action_url('recursos', '')],
                        ['label' => 'Incidencias', 'url' => Flavor_Chat_Helpers::get_action_url('incidencias', '')],
                    ],
                ],
                'busqueda' => false, // Simplificar
                'notificaciones' => true,
            ],

            // ====================================================================
            // COWORKING - Layout Profesional
            // ====================================================================
            'coworking' => [
                'layout' => 'professional',
                'titulo' => __('Mi Espacio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Reservas, accesos y comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_minimal',
                        'mensaje_bienvenida' => __('Bienvenido/a', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '📅 Nueva Reserva', 'url' => Flavor_Chat_Helpers::get_action_url('reservas', 'nueva'), 'color' => 'primary'],
                            ['label' => '🏢 Mis Espacios', 'url' => Flavor_Chat_Helpers::get_action_url('reservas', 'mis-reservas'), 'color' => 'secondary'],
                            ['label' => '📊 Mi Facturación', 'url' => Flavor_Chat_Helpers::get_action_url('facturas', ''), 'color' => 'tertiary'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 3,
                        'modulos' => ['reservas', 'espacios_comunes', 'socios'],
                    ],
                    'widgets' => [
                        'orden' => ['reservas', 'eventos', 'espacios_comunes'],
                        'mostrar_por_defecto' => 2,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => false, // No mostrar actividad en coworking
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'sidebar',
                    'items' => [
                        ['label' => 'Dashboard', 'url' => Flavor_Chat_Helpers::get_portal_url()],
                        ['label' => 'Reservas', 'url' => Flavor_Chat_Helpers::get_action_url('reservas', '')],
                        ['label' => 'Facturación', 'url' => Flavor_Chat_Helpers::get_action_url('facturas', '')],
                        ['label' => 'Comunidad', 'url' => Flavor_Chat_Helpers::get_action_url('comunidades', '')],
                    ],
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],

            // ====================================================================
            // COOPERATIVA - Layout Participativo
            // ====================================================================
            'cooperativa' => [
                'layout' => 'participative',
                'titulo' => __('Mi Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Participación, transparencia y gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_stats',
                        'mensaje_bienvenida' => __('Hola, cooperativista', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('Tu voz cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '🗳️ Votaciones Activas', 'url' => Flavor_Chat_Helpers::get_action_url('participacion', 'votaciones'), 'color' => 'primary'],
                            ['label' => '💰 Presupuestos', 'url' => Flavor_Chat_Helpers::get_action_url('presupuestos-participativos', ''), 'color' => 'success'],
                            ['label' => '📊 Transparencia', 'url' => Flavor_Chat_Helpers::get_action_url('transparencia', ''), 'color' => 'info'],
                            ['label' => '📅 Asambleas', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'asambleas'), 'color' => 'secondary'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 4,
                        'modulos' => ['socios', 'participacion', 'transparencia', 'eventos'],
                    ],
                    'widgets' => [
                        'orden' => ['participacion', 'presupuestos_participativos', 'transparencia', 'eventos', 'socios'],
                        'mostrar_por_defecto' => 3,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 8,
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'sidebar',
                    'items' => [
                        ['label' => 'Inicio', 'url' => Flavor_Chat_Helpers::get_portal_url()],
                        ['label' => 'Participación', 'url' => Flavor_Chat_Helpers::get_action_url('participacion', '')],
                        ['label' => 'Transparencia', 'url' => Flavor_Chat_Helpers::get_action_url('transparencia', '')],
                        ['label' => 'Eventos', 'url' => Flavor_Chat_Helpers::get_action_url('eventos', 'listado')],
                    ],
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],

            // ====================================================================
            // ACADEMIA - Layout Educativo
            // ====================================================================
            'academia' => [
                'layout' => 'educational',
                'titulo' => __('Mi Academia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Cursos, talleres y aprendizaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_progress',
                        'mensaje_bienvenida' => __('Hola, estudiante', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('Continúa aprendiendo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '📚 Mis Cursos', 'url' => Flavor_Chat_Helpers::get_action_url('cursos', 'mis-cursos'), 'color' => 'primary'],
                            ['label' => '🎓 Explorar Cursos', 'url' => Flavor_Chat_Helpers::get_action_url('cursos', 'listado'), 'color' => 'secondary'],
                            ['label' => '🏆 Certificados', 'url' => Flavor_Chat_Helpers::get_action_url('cursos', 'certificados'), 'color' => 'success'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 3,
                        'modulos' => ['cursos', 'talleres'],
                    ],
                    'widgets' => [
                        'orden' => ['cursos', 'talleres', 'biblioteca'],
                        'mostrar_por_defecto' => 2,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 5,
                        'agrupada_por' => 'curso',
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'tabs',
                    'items' => [
                        ['label' => 'Inicio', 'url' => '/mi-portal'],
                        ['label' => 'Mis Cursos', 'url' => '/mis-cursos'],
                        ['label' => 'Explorar', 'url' => '/cursos'],
                        ['label' => 'Certificados', 'url' => '/certificados'],
                    ],
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],

            // ====================================================================
            // MARKETPLACE - Layout Comercial
            // ====================================================================
            'marketplace' => [
                'layout' => 'commercial',
                'titulo' => __('Mi Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Compra, vende, intercambia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_featured',
                        'mensaje_bienvenida' => __('Hola, {nombre}', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        'mensaje_secundario' => __('¿Qué buscas hoy?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => true,
                        'acciones' => [
                            ['label' => '🛍️ Explorar', 'url' => '/marketplace', 'color' => 'primary'],
                            ['label' => '❤️ Favoritos', 'url' => '/favoritos', 'color' => 'error'],
                            ['label' => '📦 Mis Compras', 'url' => '/mis-compras', 'color' => 'success'],
                            ['label' => '📢 Publicar', 'url' => '/nueva-publicacion', 'color' => 'warning'],
                        ],
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 3,
                        'modulos' => ['marketplace'],
                    ],
                    'widgets' => [
                        'orden' => ['marketplace'],
                        'mostrar_por_defecto' => 1,
                        'colapsables' => false,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 6,
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'tabs',
                    'items' => [
                        ['label' => 'Inicio', 'url' => '/mi-portal'],
                        ['label' => 'Explorar', 'url' => '/marketplace'],
                        ['label' => 'Mis Anuncios', 'url' => '/mis-anuncios'],
                        ['label' => 'Favoritos', 'url' => '/favoritos'],
                    ],
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],

            // ====================================================================
            // DEFAULT - Layout por defecto (cuando no hay perfil definido)
            // ====================================================================
            'default' => [
                'layout' => 'adaptive',
                'titulo' => __('Mi Portal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Tu espacio personal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'secciones' => [
                    'hero' => [
                        'tipo' => 'hero_simple',
                        'mensaje_bienvenida' => __('Bienvenido/a', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    ],
                    'acciones_rapidas' => [
                        'mostrar' => false,
                    ],
                    'stats' => [
                        'mostrar' => true,
                        'limite' => 4,
                        'modulos' => 'auto', // Auto-detectar módulos activos
                    ],
                    'widgets' => [
                        'orden' => 'auto',
                        'mostrar_por_defecto' => 4,
                        'colapsables' => true,
                    ],
                    'actividad' => [
                        'mostrar' => true,
                        'limite' => 10,
                    ],
                ],
                'navegacion' => [
                    'estilo' => 'tabs',
                    'items' => 'auto', // Auto-generar según módulos activos
                ],
                'busqueda' => true,
                'notificaciones' => true,
            ],
        ];
    }

    /**
     * Obtiene el layout para un perfil específico
     *
     * @param string $profile_slug Slug del perfil (grupo_consumo, comunidad, etc.)
     * @return array Configuración del layout
     */
    public function get_layout_for_profile($profile_slug) {
        // Si existe layout específico, devolverlo
        if (isset($this->portal_layouts[$profile_slug])) {
            return $this->portal_layouts[$profile_slug];
        }

        // Sino, devolver default
        return $this->portal_layouts['default'];
    }

    /**
     * Obtiene el perfil activo del sitio
     *
     * @return string Slug del perfil activo
     */
    public function get_active_profile() {
        $active_profile = get_option('flavor_active_app_profile', '');

        if (empty($active_profile)) {
            // Intentar auto-detectar según módulos activos
            $active_profile = $this->auto_detect_profile();
        }

        return $active_profile;
    }

    /**
     * Auto-detecta el perfil según los módulos activos
     *
     * @return string Slug del perfil detectado
     */
    private function auto_detect_profile() {
        $active_modules = get_option('flavor_active_modules', []);

        // Patrones de detección
        if (in_array('grupos_consumo', $active_modules)) {
            return 'grupo_consumo';
        }
        if (in_array('ayuda_vecinal', $active_modules)) {
            return 'barrio';
        }
        if (in_array('espacios_comunes', $active_modules) && in_array('reservas', $active_modules)) {
            return 'coworking';
        }
        if (in_array('cursos', $active_modules)) {
            return 'academia';
        }
        if (in_array('marketplace', $active_modules) && count($active_modules) <= 3) {
            return 'marketplace';
        }
        if (in_array('participacion', $active_modules) && in_array('transparencia', $active_modules)) {
            return 'cooperativa';
        }
        if (in_array('socios', $active_modules) && in_array('eventos', $active_modules)) {
            return 'comunidad';
        }

        return 'default';
    }

    /**
     * Obtiene configuración completa del portal para el perfil activo
     *
     * @return array Configuración completa
     */
    public function get_active_portal_config() {
        $profile = $this->get_active_profile();
        $layout = $this->get_layout_for_profile($profile);

        // Añadir información del perfil
        $layout['profile_slug'] = $profile;
        $layout['profile_name'] = $this->get_profile_name($profile);

        return $layout;
    }

    /**
     * Obtiene el nombre del perfil
     *
     * @param string $profile_slug Slug del perfil
     * @return string Nombre del perfil
     */
    private function get_profile_name($profile_slug) {
        $names = [
            'grupo_consumo' => __('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'barrio' => __('Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'coworking' => __('Coworking', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cooperativa' => __('Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'academia' => __('Academia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'marketplace' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'default' => __('Portal', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return isset($names[$profile_slug]) ? $names[$profile_slug] : $names['default'];
    }

    /**
     * Renderiza el hero según el tipo
     *
     * @param array $hero_config Configuración del hero
     * @param object $user Usuario actual
     * @return string HTML del hero
     */
    public function render_hero($hero_config, $user) {
        $tipo = $hero_config['tipo'] ?? 'hero_simple';
        $mensaje_bienvenida = str_replace('{nombre}', esc_html($user->display_name), $hero_config['mensaje_bienvenida'] ?? '');
        $mensaje_secundario = $hero_config['mensaje_secundario'] ?? '';

        switch ($tipo) {
            case 'hero_simple':
                return sprintf(
                    '<div class="portal-hero portal-hero--simple">
                        <h1>%s</h1>
                        <p>%s</p>
                    </div>',
                    $mensaje_bienvenida,
                    $mensaje_secundario
                );

            case 'hero_card':
                return sprintf(
                    '<div class="portal-hero portal-hero--card">
                        <div class="hero-card">
                            <h1>%s</h1>
                            <p>%s</p>
                        </div>
                    </div>',
                    $mensaje_bienvenida,
                    $mensaje_secundario
                );

            case 'hero_minimal':
                return sprintf(
                    '<div class="portal-hero portal-hero--minimal">
                        <h2>%s</h2>
                    </div>',
                    $mensaje_bienvenida
                );

            default:
                return '';
        }
    }

    /**
     * Renderiza acciones rápidas
     *
     * @param array $acciones Configuración de acciones
     * @return string HTML de acciones rápidas
     */
    public function render_acciones_rapidas($acciones) {
        if (empty($acciones) || !isset($acciones['mostrar']) || !$acciones['mostrar']) {
            return '';
        }

        $html = '<div class="portal-quick-actions">';
        foreach ($acciones['acciones'] as $accion) {
            $color = isset($accion['color']) ? 'btn-' . $accion['color'] : 'btn-primary';
            $html .= sprintf(
                '<a href="%s" class="quick-action-btn %s">%s</a>',
                esc_url($accion['url']),
                esc_attr($color),
                esc_html($accion['label'])
            );
        }
        $html .= '</div>';

        return $html;
    }
}

// Inicializar
Flavor_Portal_Profiles::get_instance();
