<?php
/**
 * Definiciones de Plantillas
 *
 * Contiene las definiciones expandidas de todas las plantillas disponibles
 * incluyendo modulos, paginas, landing pages, configuracion y datos demo
 *
 * @package FlavorChatIA
 * @subpackage Orchestrator
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Template_Definitions
 *
 * Gestiona las definiciones completas de plantillas
 */
class Flavor_Template_Definitions {

    /**
     * Instancia singleton
     *
     * @var Flavor_Template_Definitions|null
     */
    private static $instancia = null;

    /**
     * Definiciones de plantillas registradas
     *
     * @var array
     */
    private $definiciones = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Template_Definitions
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->cargar_definiciones_base();
    }

    /**
     * Obtiene una definicion de plantilla expandida
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array|null Definicion completa o null si no existe
     */
    public function obtener_definicion($plantilla_id) {
        $definiciones = $this->obtener_todas();
        return $definiciones[$plantilla_id] ?? null;
    }

    /**
     * Obtiene todas las definiciones de plantillas
     *
     * @return array Todas las definiciones
     */
    public function obtener_todas() {
        /**
         * Filtro para que otros plugins puedan agregar o modificar definiciones
         *
         * @param array $definiciones Definiciones actuales
         */
        return apply_filters('flavor_template_definitions', $this->definiciones);
    }

    /**
     * Registra una nueva definicion de plantilla
     *
     * @param string $plantilla_id   ID unico de la plantilla
     * @param array  $definicion     Definicion completa
     * @return bool True si se registro correctamente
     */
    public function registrar_definicion($plantilla_id, $definicion) {
        if (empty($plantilla_id) || !is_array($definicion)) {
            return false;
        }

        // Validar campos requeridos
        $campos_requeridos = ['nombre', 'descripcion', 'modulos'];
        foreach ($campos_requeridos as $campo) {
            if (!isset($definicion[$campo])) {
                return false;
            }
        }

        // Normalizar definicion
        $definicion_normalizada = $this->normalizar_definicion($definicion);
        $this->definiciones[$plantilla_id] = $definicion_normalizada;

        return true;
    }

    /**
     * Verifica si una plantilla existe
     *
     * @param string $plantilla_id ID de la plantilla
     * @return bool
     */
    public function existe($plantilla_id) {
        return isset($this->definiciones[$plantilla_id]);
    }

    /**
     * Obtiene los IDs de todas las plantillas disponibles
     *
     * @return array Lista de IDs
     */
    public function obtener_ids() {
        return array_keys($this->definiciones);
    }

    /**
     * Normaliza una definicion asegurando que tiene todos los campos
     *
     * @param array $definicion Definicion a normalizar
     * @return array Definicion normalizada
     */
    private function normalizar_definicion($definicion) {
        $estructura_base = [
            'nombre' => '',
            'descripcion' => '',
            'icono' => 'dashicons-admin-generic',
            'color' => '#3b82f6',
            'modulos' => [
                'requeridos' => [],
                'opcionales' => [],
                'sugeridos' => [],
            ],
            'paginas' => [],
            'landing' => [
                'activa' => false,
                'secciones' => [],
            ],
            'configuracion' => [],
            'demo' => [
                'disponible' => false,
                'descripcion' => '',
            ],
        ];

        return array_replace_recursive($estructura_base, $definicion);
    }

    /**
     * Carga las definiciones base de plantillas
     */
    private function cargar_definiciones_base() {
        $this->definiciones = [
            // =========================================================
            // GRUPO DE CONSUMO
            // =========================================================
            'grupo_consumo' => [
                'nombre' => __('Grupo de Consumo', 'flavor-chat-ia'),
                'descripcion' => __('Gestion completa de pedidos colectivos, productores locales y repartos para grupos de consumo ecologico.', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'color' => '#84cc16',
                'modulos' => [
                    'requeridos' => ['grupos_consumo'],
                    'opcionales' => ['eventos', 'socios', 'marketplace', 'chat_grupos'],
                    'sugeridos' => ['eventos', 'socios'],
                ],
                'paginas' => [
                    // === PÁGINA PRINCIPAL ===
                    [
                        'titulo' => __('Inicio', 'flavor-chat-ia'),
                        'slug' => 'inicio',
                        'contenido' => '[flavor_landing module="grupos-consumo"]',
                        'parent' => 0,
                        'es_home' => true,
                        'template' => 'flavor-fullwidth',
                    ],
                    // === SECCIÓN: CATÁLOGO ===
                    [
                        'titulo' => __('Catálogo', 'flavor-chat-ia'),
                        'slug' => 'catalogo',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Nuestro Catálogo', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Productos', 'flavor-chat-ia'),
                        'slug' => 'productos',
                        'contenido' => '[gc_catalogo]',
                        'parent' => 'catalogo',
                    ],
                    [
                        'titulo' => __('Productores', 'flavor-chat-ia'),
                        'slug' => 'productores',
                        'contenido' => '[gc_productores]',
                        'parent' => 'catalogo',
                    ],
                    [
                        'titulo' => __('Ciclo Actual', 'flavor-chat-ia'),
                        'slug' => 'ciclo-actual',
                        'contenido' => '[gc_ciclo_actual]',
                        'parent' => 'catalogo',
                    ],
                    // === SECCIÓN: MI CUENTA ===
                    [
                        'titulo' => __('Mi Cuenta', 'flavor-chat-ia'),
                        'slug' => 'mi-cuenta',
                        'contenido' => '[flavor_user_dashboard]',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Mis Pedidos', 'flavor-chat-ia'),
                        'slug' => 'mis-pedidos',
                        'contenido' => '[gc_mis_pedidos]',
                        'parent' => 'mi-cuenta',
                    ],
                    [
                        'titulo' => __('Mi Cesta', 'flavor-chat-ia'),
                        'slug' => 'mi-cesta',
                        'contenido' => '[gc_cesta]',
                        'parent' => 'mi-cuenta',
                    ],
                ],
                'menu' => [
                    'nombre' => __('Menu Grupo Consumo', 'flavor-chat-ia'),
                    'ubicacion' => 'primary',
                    'items' => [
                        ['titulo' => __('Inicio', 'flavor-chat-ia'), 'url' => '/', 'icono' => 'home'],
                        [
                            'titulo' => __('Catálogo', 'flavor-chat-ia'),
                            'url' => '/catalogo/',
                            'icono' => 'carrot',
                            'hijos' => [
                                ['titulo' => __('Productos', 'flavor-chat-ia'), 'url' => '/catalogo/productos/'],
                                ['titulo' => __('Productores', 'flavor-chat-ia'), 'url' => '/catalogo/productores/'],
                                ['titulo' => __('Ciclo Actual', 'flavor-chat-ia'), 'url' => '/catalogo/ciclo-actual/'],
                            ],
                        ],
                        [
                            'titulo' => __('Mi Cuenta', 'flavor-chat-ia'),
                            'url' => '/mi-cuenta/',
                            'icono' => 'user',
                            'hijos' => [
                                ['titulo' => __('Mis Pedidos', 'flavor-chat-ia'), 'url' => '/mi-cuenta/mis-pedidos/'],
                                ['titulo' => __('Mi Cesta', 'flavor-chat-ia'), 'url' => '/mi-cuenta/mi-cesta/'],
                            ],
                        ],
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'slug' => 'inicio',
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'imagen-derecha',
                            'datos' => [
                                'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                                'subtitulo' => __('Productos locales, frescos y de temporada directamente del productor', 'flavor-chat-ia'),
                                'cta_texto' => __('Ver Productores', 'flavor-chat-ia'),
                                'cta_url' => '#productores',
                                'imagen' => '',
                            ],
                        ],
                        [
                            'tipo' => 'features',
                            'variante' => 'iconos-3-columnas',
                            'datos' => [
                                'titulo' => __('Por que unirte', 'flavor-chat-ia'),
                                'items' => [
                                    ['icono' => 'carrot', 'titulo' => __('Productos Frescos', 'flavor-chat-ia'), 'descripcion' => __('Directos del campo a tu mesa', 'flavor-chat-ia')],
                                    ['icono' => 'groups', 'titulo' => __('Comunidad', 'flavor-chat-ia'), 'descripcion' => __('Forma parte de un grupo comprometido', 'flavor-chat-ia')],
                                    ['icono' => 'location-alt', 'titulo' => __('Km 0', 'flavor-chat-ia'), 'descripcion' => __('Apoya a productores locales', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'grid',
                            'variante' => 'productos',
                            'datos' => [
                                'titulo' => __('Productos de Temporada', 'flavor-chat-ia'),
                                'shortcode' => '[flavor_module_listing module="grupos_consumo" action="listar_productos" columnas="4" limite="8"]',
                            ],
                        ],
                        [
                            'tipo' => 'listing',
                            'variante' => 'cards-horizontales',
                            'datos' => [
                                'titulo' => __('Nuestros Productores', 'flavor-chat-ia'),
                                'shortcode' => '[flavor_module_listing module="grupos_consumo" action="listar_productores" columnas="2" limite="4"]',
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'centrado',
                            'datos' => [
                                'titulo' => __('Unete a un grupo de consumo', 'flavor-chat-ia'),
                                'descripcion' => __('Apoya a los productores locales y disfruta de alimentos frescos y sostenibles', 'flavor-chat-ia'),
                                'boton_texto' => __('Registrarse', 'flavor-chat-ia'),
                                'boton_url' => '/registro/',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'grupos_consumo' => [
                        'ciclo_duracion_dias' => 7,
                        'dia_cierre_pedidos' => 'miercoles',
                        'hora_cierre' => '23:59',
                        'dia_entrega' => 'sabado',
                        'hora_entrega_inicio' => '10:00',
                        'hora_entrega_fin' => '13:00',
                        'minimo_pedido' => 15,
                        'notificar_cierre' => true,
                        'notificar_24h_antes' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye 3 productores de ejemplo, 15 productos y un ciclo de pedidos abierto', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // COMUNIDAD / ASOCIACION
            // =========================================================
            'comunidad' => [
                'nombre' => __('Comunidad / Asociacion', 'flavor-chat-ia'),
                'descripcion' => __('Gestion integral de una comunidad o asociacion: miembros, eventos, foros y recursos compartidos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'color' => '#e91e63',
                'modulos' => [
                    'requeridos' => ['socios', 'eventos'],
                    'opcionales' => ['talleres', 'marketplace', 'participacion', 'chat_grupos', 'chat_interno', 'multimedia', 'cursos', 'red_social', 'reservas', 'foros'],
                    'sugeridos' => ['foros', 'chat_grupos', 'talleres'],
                ],
                'paginas' => [
                    [
                        'titulo' => __('Comunidad', 'flavor-chat-ia'),
                        'slug' => 'comunidad',
                        'contenido' => '[flavor_landing module="comunidades"]',
                        'parent' => 0,
                        'es_landing' => true,
                    ],
                    [
                        'titulo' => __('Miembros', 'flavor-chat-ia'),
                        'slug' => 'socios',
                        'contenido' => '<h1>Unete a Nuestra Comunidad</h1>
<p>Descubre los beneficios de ser miembro</p>

<a href="/comunidad/socios/unirme/" class="flavor-button flavor-button-primary">Hacerse Miembro</a>',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Hacerse Miembro', 'flavor-chat-ia'),
                        'slug' => 'unirme',
                        'contenido' => '<h1>Unete como Socio</h1>

[flavor_module_form module="socios" action="dar_alta_socio"]',
                        'parent' => 'socios',
                    ],
                    [
                        'titulo' => __('Mi Perfil de Miembro', 'flavor-chat-ia'),
                        'slug' => 'mi-perfil',
                        'contenido' => '<h1>Mi Perfil de Miembro</h1>

[flavor_module_dashboard module="socios"]',
                        'parent' => 'socios',
                    ],
                    [
                        'titulo' => __('Eventos', 'flavor-chat-ia'),
                        'slug' => 'eventos',
                        'contenido' => '<h1>Eventos de la Comunidad</h1>

[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Crear Evento', 'flavor-chat-ia'),
                        'slug' => 'crear-evento',
                        'contenido' => '<h1>Organiza un Evento</h1>
<p>Crea encuentros para la comunidad</p>

[flavor_module_form module="eventos" action="crear_evento"]',
                        'parent' => 'eventos',
                    ],
                    [
                        'titulo' => __('Foros', 'flavor-chat-ia'),
                        'slug' => 'foros',
                        'contenido' => '<h1>Foros de la Comunidad</h1>
<p>Participa en las discusiones</p>

[flavor_module_listing module="foros" action="listar_temas" columnas="1"]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Nuevo Tema', 'flavor-chat-ia'),
                        'slug' => 'nuevo-tema',
                        'contenido' => '<h1>Crear Nuevo Tema</h1>
<p>Inicia una nueva discusion</p>

[flavor_module_form module="foros" action="crear_tema"]',
                        'parent' => 'foros',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'centrado',
                            'datos' => [
                                'titulo' => __('Nuestra Comunidad', 'flavor-chat-ia'),
                                'subtitulo' => __('Conecta, participa y crece junto a nosotros', 'flavor-chat-ia'),
                                'cta_texto' => __('Unirse', 'flavor-chat-ia'),
                                'cta_url' => '#socios',
                            ],
                        ],
                        [
                            'tipo' => 'stats',
                            'variante' => '4-columnas',
                            'datos' => [
                                'items' => [
                                    ['numero' => '250+', 'etiqueta' => __('Miembros', 'flavor-chat-ia')],
                                    ['numero' => '50+', 'etiqueta' => __('Eventos/ano', 'flavor-chat-ia')],
                                    ['numero' => '15', 'etiqueta' => __('Anos activos', 'flavor-chat-ia')],
                                    ['numero' => '100%', 'etiqueta' => __('Participativo', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'grid',
                            'variante' => 'eventos',
                            'datos' => [
                                'titulo' => __('Proximos Eventos', 'flavor-chat-ia'),
                                'shortcode' => '[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="6"]',
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'con-imagen',
                            'datos' => [
                                'titulo' => __('Forma parte de nuestra comunidad', 'flavor-chat-ia'),
                                'descripcion' => __('Hazte miembro y disfruta de todos los beneficios', 'flavor-chat-ia'),
                                'boton_texto' => __('Hacerse Miembro', 'flavor-chat-ia'),
                                'boton_url' => '/comunidad/socios/unirme/',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'socios' => [
                        'cuota_anual' => 30,
                        'cuota_mensual' => 0,
                        'periodo_prueba_dias' => 30,
                        'renovacion_automatica' => false,
                        'recordar_renovacion_dias' => 30,
                        'campos_obligatorios' => ['nombre', 'email', 'telefono'],
                    ],
                    'eventos' => [
                        'requiere_inscripcion' => true,
                        'maximo_asistentes_defecto' => 50,
                        'notificar_organizador' => true,
                        'permitir_comentarios' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye 6 eventos de ejemplo y configuracion de tipos de miembros', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // BANCO DE TIEMPO
            // =========================================================
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'descripcion' => __('Intercambio de servicios por horas entre miembros de la comunidad. Tu tiempo vale igual que el de cualquiera.', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'color' => '#9b59b6',
                'modulos' => [
                    'requeridos' => ['banco_tiempo', 'socios'],
                    'opcionales' => ['eventos', 'talleres', 'ayuda_vecinal', 'chat_grupos'],
                    'sugeridos' => ['chat_grupos', 'eventos'],
                ],
                'paginas' => [
                    [
                        'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                        'slug' => 'banco-tiempo',
                        'contenido' => '[flavor_landing module="banco-tiempo"]',
                        'parent' => 0,
                        'es_landing' => true,
                    ],
                    [
                        'titulo' => __('Servicios Disponibles', 'flavor-chat-ia'),
                        'slug' => 'servicios',
                        'contenido' => '<h1>Servicios Disponibles</h1>
<p>Descubre lo que la comunidad puede ofrecerte</p>

[flavor_module_listing module="banco_tiempo" action="listar_servicios" columnas="3" limite="12"]',
                        'parent' => 'banco-tiempo',
                    ],
                    [
                        'titulo' => __('Ofrecer Servicio', 'flavor-chat-ia'),
                        'slug' => 'ofrecer',
                        'contenido' => '<h1>Ofrecer un Servicio</h1>
<p>Comparte tus habilidades con la comunidad</p>

[flavor_module_form module="banco_tiempo" action="crear_servicio"]',
                        'parent' => 'banco-tiempo',
                    ],
                    [
                        'titulo' => __('Solicitar Servicio', 'flavor-chat-ia'),
                        'slug' => 'solicitar',
                        'contenido' => '<h1>Solicitar un Servicio</h1>
<p>Encuentra ayuda en tu comunidad</p>

[flavor_module_form module="banco_tiempo" action="solicitar_servicio"]',
                        'parent' => 'banco-tiempo',
                    ],
                    [
                        'titulo' => __('Mis Intercambios', 'flavor-chat-ia'),
                        'slug' => 'mis-intercambios',
                        'contenido' => '<h1>Mis Intercambios</h1>

[flavor_module_dashboard module="banco_tiempo"]',
                        'parent' => 'banco-tiempo',
                    ],
                    [
                        'titulo' => __('Mi Saldo', 'flavor-chat-ia'),
                        'slug' => 'mi-saldo',
                        'contenido' => '<h1>Mi Saldo de Horas</h1>
<p>Consulta tu balance de tiempo</p>

[flavor_module_listing module="banco_tiempo" action="mi_saldo"]',
                        'parent' => 'banco-tiempo',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'gradiente',
                            'datos' => [
                                'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                                'subtitulo' => __('Intercambia servicios con tus vecinos. Tu tiempo vale tanto como el de cualquiera.', 'flavor-chat-ia'),
                                'cta_texto' => __('Ver Servicios', 'flavor-chat-ia'),
                                'cta_url' => '#servicios',
                            ],
                        ],
                        [
                            'tipo' => 'como-funciona',
                            'variante' => 'pasos-horizontales',
                            'datos' => [
                                'titulo' => __('Como funciona', 'flavor-chat-ia'),
                                'pasos' => [
                                    ['numero' => '1', 'titulo' => __('Registrate', 'flavor-chat-ia'), 'descripcion' => __('Crea tu cuenta y recibe horas de bienvenida', 'flavor-chat-ia')],
                                    ['numero' => '2', 'titulo' => __('Ofrece', 'flavor-chat-ia'), 'descripcion' => __('Publica los servicios que puedes ofrecer', 'flavor-chat-ia')],
                                    ['numero' => '3', 'titulo' => __('Intercambia', 'flavor-chat-ia'), 'descripcion' => __('Da y recibe servicios, acumulando horas', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'grid',
                            'variante' => 'servicios',
                            'datos' => [
                                'titulo' => __('Servicios Populares', 'flavor-chat-ia'),
                                'shortcode' => '[flavor_module_listing module="banco_tiempo" action="listar_servicios" columnas="4" limite="8"]',
                            ],
                        ],
                        [
                            'tipo' => 'categorias',
                            'variante' => 'iconos-grid',
                            'datos' => [
                                'titulo' => __('Categorias', 'flavor-chat-ia'),
                                'items' => [
                                    ['icono' => 'translation', 'titulo' => __('Idiomas', 'flavor-chat-ia')],
                                    ['icono' => 'admin-tools', 'titulo' => __('Reparaciones', 'flavor-chat-ia')],
                                    ['icono' => 'heart', 'titulo' => __('Cuidados', 'flavor-chat-ia')],
                                    ['icono' => 'laptop', 'titulo' => __('Tecnologia', 'flavor-chat-ia')],
                                    ['icono' => 'car', 'titulo' => __('Transporte', 'flavor-chat-ia')],
                                    ['icono' => 'carrot', 'titulo' => __('Jardineria', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'centrado',
                            'datos' => [
                                'titulo' => __('Empieza a intercambiar tiempo', 'flavor-chat-ia'),
                                'descripcion' => __('Ofrece lo que sabes hacer y aprende de los demas', 'flavor-chat-ia'),
                                'boton_texto' => __('Crear cuenta', 'flavor-chat-ia'),
                                'boton_url' => '/registro/',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'banco_tiempo' => [
                        'horas_bienvenida' => 3,
                        'horas_maximas_negativas' => -5,
                        'horas_maximas_positivas' => 50,
                        'duracion_minima_servicio' => 0.5,
                        'duracion_maxima_servicio' => 8,
                        'categorias' => ['educacion', 'tecnologia', 'cuidados', 'bricolaje', 'transporte', 'otros'],
                        'requiere_validacion' => false,
                        'notificar_nuevos_servicios' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye 8 servicios de ejemplo en diferentes categorias', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // BARRIO / VECINDARIO
            // =========================================================
            'barrio' => [
                'nombre' => __('Barrio / Vecindario', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma vecinal completa con ayuda mutua, huertos, bicicletas y recursos compartidos entre vecinos.', 'flavor-chat-ia'),
                'icono' => 'dashicons-location',
                'color' => '#22c55e',
                'modulos' => [
                    'requeridos' => ['ayuda_vecinal'],
                    'opcionales' => ['huertos_urbanos', 'bicicletas_compartidas', 'espacios_comunes', 'banco_tiempo', 'incidencias', 'reciclaje', 'compostaje', 'carpooling', 'talleres', 'eventos', 'chat_grupos', 'reservas'],
                    'sugeridos' => ['huertos_urbanos', 'incidencias', 'eventos'],
                ],
                'paginas' => [
                    [
                        'titulo' => __('Mi Barrio', 'flavor-chat-ia'),
                        'slug' => 'mi-barrio',
                        'contenido' => '[flavor_landing module="ayuda-vecinal"]',
                        'parent' => 0,
                        'es_landing' => true,
                    ],
                    [
                        'titulo' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                        'slug' => 'ayuda-vecinal',
                        'contenido' => '<h1>Ayuda Vecinal</h1>
<p>Red de ayuda mutua entre vecinos</p>

[flavor_module_listing module="ayuda_vecinal" action="listar_solicitudes" columnas="2" limite="12"]',
                        'parent' => 'mi-barrio',
                    ],
                    [
                        'titulo' => __('Pedir Ayuda', 'flavor-chat-ia'),
                        'slug' => 'pedir',
                        'contenido' => '<h1>Solicitar Ayuda</h1>
<p>Necesitas ayuda? Tu comunidad esta aqui</p>

[flavor_module_form module="ayuda_vecinal" action="solicitar_ayuda"]',
                        'parent' => 'ayuda-vecinal',
                    ],
                    [
                        'titulo' => __('Ofrecer Ayuda', 'flavor-chat-ia'),
                        'slug' => 'ofrecer',
                        'contenido' => '<h1>Ofrecer Ayuda</h1>
<p>Ayuda a un vecino que lo necesita</p>

[flavor_module_form module="ayuda_vecinal" action="ofrecer_ayuda"]',
                        'parent' => 'ayuda-vecinal',
                    ],
                    [
                        'titulo' => __('Huertos Urbanos', 'flavor-chat-ia'),
                        'slug' => 'huertos',
                        'contenido' => '<h1>Huertos Urbanos</h1>
<p>Gestion de parcelas y cosechas comunitarias</p>

[flavor_module_listing module="huertos_urbanos" action="listar_huertos" columnas="3" limite="12"]',
                        'parent' => 'mi-barrio',
                    ],
                    [
                        'titulo' => __('Solicitar Parcela', 'flavor-chat-ia'),
                        'slug' => 'solicitar-parcela',
                        'contenido' => '<h1>Solicitar Parcela</h1>
<p>Solicita una parcela en los huertos comunitarios</p>

[flavor_module_form module="huertos_urbanos" action="solicitar_parcela"]',
                        'parent' => 'huertos',
                    ],
                    [
                        'titulo' => __('Incidencias', 'flavor-chat-ia'),
                        'slug' => 'incidencias',
                        'contenido' => '<h1>Incidencias del Barrio</h1>
<p>Reporta y consulta incidencias</p>

[flavor_module_listing module="incidencias" action="listar_incidencias" columnas="2" limite="12"]',
                        'parent' => 'mi-barrio',
                    ],
                    [
                        'titulo' => __('Reportar Incidencia', 'flavor-chat-ia'),
                        'slug' => 'reportar',
                        'contenido' => '<h1>Reportar Incidencia</h1>
<p>Informa de un problema en tu barrio</p>

[flavor_module_form module="incidencias" action="reportar_incidencia"]',
                        'parent' => 'incidencias',
                    ],
                    [
                        'titulo' => __('Eventos del Barrio', 'flavor-chat-ia'),
                        'slug' => 'eventos',
                        'contenido' => '<h1>Eventos del Barrio</h1>

[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]',
                        'parent' => 'mi-barrio',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'mapa-fondo',
                            'datos' => [
                                'titulo' => __('Tu Barrio, Tu Comunidad', 'flavor-chat-ia'),
                                'subtitulo' => __('Conecta con tus vecinos, comparte recursos y haz barrio', 'flavor-chat-ia'),
                                'cta_texto' => __('Explorar', 'flavor-chat-ia'),
                                'cta_url' => '#servicios',
                            ],
                        ],
                        [
                            'tipo' => 'servicios-barrio',
                            'variante' => 'cards-iconos',
                            'datos' => [
                                'titulo' => __('Que puedes hacer', 'flavor-chat-ia'),
                                'items' => [
                                    ['icono' => 'heart', 'titulo' => __('Ayuda Vecinal', 'flavor-chat-ia'), 'descripcion' => __('Pide o ofrece ayuda', 'flavor-chat-ia'), 'url' => '/mi-barrio/ayuda-vecinal/'],
                                    ['icono' => 'carrot', 'titulo' => __('Huertos', 'flavor-chat-ia'), 'descripcion' => __('Cultiva en comunidad', 'flavor-chat-ia'), 'url' => '/mi-barrio/huertos/'],
                                    ['icono' => 'warning', 'titulo' => __('Incidencias', 'flavor-chat-ia'), 'descripcion' => __('Reporta problemas', 'flavor-chat-ia'), 'url' => '/mi-barrio/incidencias/'],
                                    ['icono' => 'calendar', 'titulo' => __('Eventos', 'flavor-chat-ia'), 'descripcion' => __('Participa y organiza', 'flavor-chat-ia'), 'url' => '/mi-barrio/eventos/'],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'grid',
                            'variante' => 'ayuda-urgente',
                            'datos' => [
                                'titulo' => __('Solicitudes de Ayuda Recientes', 'flavor-chat-ia'),
                                'shortcode' => '[flavor_module_listing module="ayuda_vecinal" action="listar_solicitudes" columnas="2" limite="4"]',
                            ],
                        ],
                        [
                            'tipo' => 'mapa',
                            'variante' => 'interactivo',
                            'datos' => [
                                'titulo' => __('Mapa del Barrio', 'flavor-chat-ia'),
                                'capas' => ['huertos', 'incidencias', 'puntos_interes'],
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'doble-boton',
                            'datos' => [
                                'titulo' => __('Participa en tu barrio', 'flavor-chat-ia'),
                                'descripcion' => __('Haz de tu barrio un lugar mejor para vivir', 'flavor-chat-ia'),
                                'boton_primario_texto' => __('Pedir Ayuda', 'flavor-chat-ia'),
                                'boton_primario_url' => '/mi-barrio/ayuda-vecinal/pedir/',
                                'boton_secundario_texto' => __('Ofrecer Ayuda', 'flavor-chat-ia'),
                                'boton_secundario_url' => '/mi-barrio/ayuda-vecinal/ofrecer/',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'ayuda_vecinal' => [
                        'categorias' => ['compras', 'mascotas', 'tecnologia', 'acompanamiento', 'bricolaje', 'otros'],
                        'niveles_urgencia' => ['normal', 'alta', 'urgente'],
                        'duracion_solicitud_dias' => 30,
                        'notificar_nuevas_solicitudes' => true,
                        'radio_notificacion_km' => 2,
                    ],
                    'huertos_urbanos' => [
                        'tamano_parcela_m2' => 15,
                        'precio_anual' => 50,
                        'lista_espera' => true,
                        'duracion_asignacion_anos' => 2,
                    ],
                    'incidencias' => [
                        'categorias' => ['alumbrado', 'limpieza', 'vias_publicas', 'ruidos', 'otros'],
                        'requiere_foto' => false,
                        'requiere_ubicacion' => true,
                        'notificar_admin' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye 5 solicitudes de ayuda, parcelas de huerto y algunas incidencias de ejemplo', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // TIENDA ONLINE
            // =========================================================
            'tienda' => [
                'nombre' => __('Tienda Online', 'flavor-chat-ia'),
                'descripcion' => __('Tienda online completa con carrito, productos, pedidos y chat de atencion al cliente integrado.', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'color' => '#00a0d2',
                'modulos' => [
                    'requeridos' => ['woocommerce'],
                    'opcionales' => ['marketplace', 'facturas', 'advertising', 'chat_interno', 'clientes'],
                    'sugeridos' => ['facturas', 'clientes'],
                ],
                'paginas' => [
                    [
                        'titulo' => __('Tienda', 'flavor-chat-ia'),
                        'slug' => 'tienda',
                        'contenido' => '[flavor_landing module="tienda"]',
                        'parent' => 0,
                        'es_landing' => true,
                    ],
                    [
                        'titulo' => __('Productos', 'flavor-chat-ia'),
                        'slug' => 'productos',
                        'contenido' => '<h1>Nuestros Productos</h1>

[products limit="12" columns="4" paginate="true"]',
                        'parent' => 'tienda',
                    ],
                    [
                        'titulo' => __('Ofertas', 'flavor-chat-ia'),
                        'slug' => 'ofertas',
                        'contenido' => '<h1>Ofertas Especiales</h1>

[products limit="12" columns="4" on_sale="true"]',
                        'parent' => 'tienda',
                    ],
                    [
                        'titulo' => __('Mi Cuenta', 'flavor-chat-ia'),
                        'slug' => 'mi-cuenta-tienda',
                        'contenido' => '[woocommerce_my_account]',
                        'parent' => 'tienda',
                    ],
                    [
                        'titulo' => __('Carrito', 'flavor-chat-ia'),
                        'slug' => 'carrito',
                        'contenido' => '[woocommerce_cart]',
                        'parent' => 'tienda',
                    ],
                    [
                        'titulo' => __('Finalizar Compra', 'flavor-chat-ia'),
                        'slug' => 'checkout',
                        'contenido' => '[woocommerce_checkout]',
                        'parent' => 'tienda',
                    ],
                    [
                        'titulo' => __('Mis Pedidos', 'flavor-chat-ia'),
                        'slug' => 'mis-pedidos',
                        'contenido' => '<h1>Mis Pedidos</h1>

[woocommerce_order_tracking]',
                        'parent' => 'tienda',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'slider-productos',
                            'datos' => [
                                'titulo' => __('Bienvenido a Nuestra Tienda', 'flavor-chat-ia'),
                                'subtitulo' => __('Los mejores productos al mejor precio', 'flavor-chat-ia'),
                                'cta_texto' => __('Ver Productos', 'flavor-chat-ia'),
                                'cta_url' => '/tienda/productos/',
                            ],
                        ],
                        [
                            'tipo' => 'categorias',
                            'variante' => 'cards-imagen',
                            'datos' => [
                                'titulo' => __('Categorias', 'flavor-chat-ia'),
                                'shortcode' => '[product_categories number="6" columns="3"]',
                            ],
                        ],
                        [
                            'tipo' => 'productos-destacados',
                            'variante' => 'carrusel',
                            'datos' => [
                                'titulo' => __('Productos Destacados', 'flavor-chat-ia'),
                                'shortcode' => '[products limit="8" columns="4" best_selling="true"]',
                            ],
                        ],
                        [
                            'tipo' => 'ofertas',
                            'variante' => 'banner-countdown',
                            'datos' => [
                                'titulo' => __('Ofertas Especiales', 'flavor-chat-ia'),
                                'shortcode' => '[products limit="4" columns="4" on_sale="true"]',
                            ],
                        ],
                        [
                            'tipo' => 'testimonios',
                            'variante' => 'carrusel',
                            'datos' => [
                                'titulo' => __('Lo que dicen nuestros clientes', 'flavor-chat-ia'),
                            ],
                        ],
                        [
                            'tipo' => 'newsletter',
                            'variante' => 'simple',
                            'datos' => [
                                'titulo' => __('Suscribete a nuestra newsletter', 'flavor-chat-ia'),
                                'descripcion' => __('Recibe ofertas exclusivas y novedades', 'flavor-chat-ia'),
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'woocommerce' => [
                        'moneda' => 'EUR',
                        'posicion_moneda' => 'right_space',
                        'decimales' => 2,
                        'separador_miles' => '.',
                        'separador_decimal' => ',',
                        'habilitar_cupones' => true,
                        'habilitar_resenas' => true,
                        'gestion_stock' => true,
                        'umbral_stock_bajo' => 5,
                    ],
                    'chat_interno' => [
                        'mostrar_en_producto' => true,
                        'mostrar_en_carrito' => true,
                        'mensaje_bienvenida' => __('Hola! Como podemos ayudarte?', 'flavor-chat-ia'),
                    ],
                ],
                'demo' => [
                    'disponible' => false,
                    'descripcion' => __('Requiere WooCommerce activo. Usa el importador de WooCommerce para datos de ejemplo.', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // EMPRESA LOCAL / PYME
            // =========================================================
            'empresa_local' => [
                'nombre' => __('Empresa Local / PYME', 'flavor-chat-ia'),
                'descripcion' => __('Herramientas para pequeños negocios y autónomos: gestión de clientes, facturación, presencia online y conexión con el tejido local.', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'color' => '#0ea5e9',
                'modulos' => [
                    'requeridos' => ['clientes', 'facturas'],
                    'opcionales' => ['marketplace', 'bares', 'eventos', 'chat_interno', 'encuestas', 'campanias'],
                    'sugeridos' => ['marketplace', 'bares'],
                ],
                'paginas' => [
                    // === PÁGINA PRINCIPAL ===
                    [
                        'titulo' => __('Inicio', 'flavor-chat-ia'),
                        'slug' => 'inicio',
                        'contenido' => '[flavor_landing module="empresa"]',
                        'parent' => 0,
                        'es_home' => true,
                        'template' => 'flavor-fullwidth',
                    ],
                    // === SECCIÓN: NEGOCIO ===
                    [
                        'titulo' => __('Negocio', 'flavor-chat-ia'),
                        'slug' => 'negocio',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Nuestro Negocio', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Conoce lo que ofrecemos.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Servicios', 'flavor-chat-ia'),
                        'slug' => 'servicios',
                        'contenido' => '[empresa_servicios]',
                        'parent' => 'negocio',
                    ],
                    [
                        'titulo' => __('Productos', 'flavor-chat-ia'),
                        'slug' => 'productos',
                        'contenido' => '[marketplace_listado usuario_actual="admin"]',
                        'parent' => 'negocio',
                    ],
                    [
                        'titulo' => __('Contacto', 'flavor-chat-ia'),
                        'slug' => 'contacto',
                        'contenido' => '[flavor_contacto]',
                        'parent' => 'negocio',
                    ],
                    // === SECCIÓN: GESTIÓN ===
                    [
                        'titulo' => __('Mi Portal', 'flavor-chat-ia'),
                        'slug' => 'mi-portal',
                        'contenido' => '[flavor_portal_usuario]',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Mis Clientes', 'flavor-chat-ia'),
                        'slug' => 'mis-clientes',
                        'contenido' => '[clientes_listado]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Facturación', 'flavor-chat-ia'),
                        'slug' => 'facturacion',
                        'contenido' => '[facturas_listado]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Nuevo Cliente', 'flavor-chat-ia'),
                        'slug' => 'nuevo-cliente',
                        'contenido' => '[clientes_formulario_alta]',
                        'parent' => 'mi-portal',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'empresa',
                            'datos' => [
                                'titulo' => __('Tu Negocio, Más Cerca', 'flavor-chat-ia'),
                                'subtitulo' => __('Conectamos tu empresa con clientes del territorio', 'flavor-chat-ia'),
                                'cta_texto' => __('Conoce nuestros servicios', 'flavor-chat-ia'),
                                'cta_url' => '/servicios',
                            ],
                        ],
                        [
                            'tipo' => 'servicios',
                            'variante' => 'grid',
                            'datos' => [
                                'titulo' => __('Nuestros Servicios', 'flavor-chat-ia'),
                            ],
                        ],
                        [
                            'tipo' => 'contacto',
                            'variante' => 'mapa',
                            'datos' => [
                                'titulo' => __('Visítanos', 'flavor-chat-ia'),
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'clientes' => [
                        'campos_personalizados' => true,
                        'historial_compras' => true,
                        'notas_internas' => true,
                    ],
                    'facturas' => [
                        'numeracion_automatica' => true,
                        'plantilla_factura' => 'moderna',
                        'incluir_logo' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye clientes de ejemplo, facturas modelo y configuración básica de negocio.', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // EMPRESA ÉTICA / COOPERATIVA
            // =========================================================
            'empresa_etica' => [
                'nombre' => __('Empresa Ética / Cooperativa', 'flavor-chat-ia'),
                'descripcion' => __('Para empresas con valores sociales: economía social, cooperativas, B Corps. Incluye transparencia, participación y medición de impacto.', 'flavor-chat-ia'),
                'icono' => 'dashicons-heart',
                'color' => '#10b981',
                'modulos' => [
                    'requeridos' => ['socios', 'transparencia', 'participacion'],
                    'opcionales' => ['clientes', 'facturas', 'marketplace', 'eventos', 'foros', 'presupuestos_participativos', 'huella_ecologica'],
                    'sugeridos' => ['clientes', 'eventos', 'foros'],
                ],
                'paginas' => [
                    // === PÁGINA PRINCIPAL ===
                    [
                        'titulo' => __('Inicio', 'flavor-chat-ia'),
                        'slug' => 'inicio',
                        'contenido' => '[flavor_landing module="empresa-etica"]',
                        'parent' => 0,
                        'es_home' => true,
                        'template' => 'flavor-fullwidth',
                    ],
                    // === SECCIÓN: SOBRE NOSOTROS ===
                    [
                        'titulo' => __('Conocenos', 'flavor-chat-ia'),
                        'slug' => 'conocenos',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Conoce Nuestra Organización', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Somos una empresa comprometida con los valores sociales y ambientales.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Quiénes Somos', 'flavor-chat-ia'),
                        'slug' => 'quienes-somos',
                        'contenido' => '[empresa_etica_sobre_nosotros]',
                        'parent' => 'conocenos',
                    ],
                    [
                        'titulo' => __('Nuestro Impacto', 'flavor-chat-ia'),
                        'slug' => 'impacto',
                        'contenido' => '[empresa_etica_impacto]',
                        'parent' => 'conocenos',
                    ],
                    [
                        'titulo' => __('Transparencia', 'flavor-chat-ia'),
                        'slug' => 'transparencia',
                        'contenido' => '[transparencia_portal]',
                        'parent' => 'conocenos',
                    ],
                    // === SECCIÓN: PARTICIPACIÓN ===
                    [
                        'titulo' => __('Participación', 'flavor-chat-ia'),
                        'slug' => 'participacion',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Participa en la Cooperativa', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Tu voz importa. Involúcrate en las decisiones.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Propuestas', 'flavor-chat-ia'),
                        'slug' => 'propuestas',
                        'contenido' => '[participacion_propuestas]',
                        'parent' => 'participacion',
                    ],
                    [
                        'titulo' => __('Votaciones', 'flavor-chat-ia'),
                        'slug' => 'votaciones',
                        'contenido' => '[participacion_votaciones]',
                        'parent' => 'participacion',
                    ],
                    [
                        'titulo' => __('Hazte Miembro', 'flavor-chat-ia'),
                        'slug' => 'hazte-socio',
                        'contenido' => '[socios_formulario_alta]',
                        'parent' => 'participacion',
                    ],
                    // === SECCIÓN: ÁREA MIEMBROS ===
                    [
                        'titulo' => __('Mi Portal', 'flavor-chat-ia'),
                        'slug' => 'mi-portal',
                        'contenido' => '[flavor_portal_usuario]',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Mi Perfil de Miembro', 'flavor-chat-ia'),
                        'slug' => 'mi-perfil',
                        'contenido' => '[socios_mi_perfil]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Mis Aportaciones', 'flavor-chat-ia'),
                        'slug' => 'mis-aportaciones',
                        'contenido' => '[socios_mis_aportaciones]',
                        'parent' => 'mi-portal',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'impacto',
                            'datos' => [
                                'titulo' => __('Empresa con Propósito', 'flavor-chat-ia'),
                                'subtitulo' => __('Generamos valor económico, social y ambiental', 'flavor-chat-ia'),
                                'cta_texto' => __('Conoce nuestro impacto', 'flavor-chat-ia'),
                                'cta_url' => '/impacto',
                            ],
                        ],
                        [
                            'tipo' => 'metricas-impacto',
                            'variante' => 'dashboard',
                            'datos' => [
                                'titulo' => __('Nuestro Impacto en Números', 'flavor-chat-ia'),
                                'shortcode' => '[empresa_etica_metricas]',
                            ],
                        ],
                        [
                            'tipo' => 'valores',
                            'variante' => 'iconos',
                            'datos' => [
                                'titulo' => __('Nuestros Valores', 'flavor-chat-ia'),
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'doble',
                            'datos' => [
                                'titulo' => __('Únete al cambio', 'flavor-chat-ia'),
                                'cta1_texto' => __('Hazte miembro', 'flavor-chat-ia'),
                                'cta1_url' => '/hazte-socio',
                                'cta2_texto' => __('Colabora', 'flavor-chat-ia'),
                                'cta2_url' => '/participa',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'transparencia' => [
                        'publicar_cuentas' => true,
                        'publicar_actas' => true,
                        'publicar_estatutos' => true,
                    ],
                    'participacion' => [
                        'propuestas_abiertas' => true,
                        'votaciones_socios' => true,
                        'debates_publicos' => false,
                    ],
                    'socios' => [
                        'tipos_socio' => ['trabajador', 'colaborador', 'consumidor'],
                        'cuotas_diferenciadas' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Datos de ejemplo para cooperativa: miembros, asambleas, métricas de impacto y documentos de transparencia.', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // HUB DE EMPRENDEDORES / COWORKING
            // =========================================================
            'hub_emprendedores' => [
                'nombre' => __('Hub de Emprendedores', 'flavor-chat-ia'),
                'descripcion' => __('Para espacios de coworking, incubadoras y comunidades de emprendedores. Networking, eventos, recursos compartidos y mentoría.', 'flavor-chat-ia'),
                'icono' => 'dashicons-networking',
                'color' => '#8b5cf6',
                'modulos' => [
                    'requeridos' => ['comunidades', 'eventos', 'red_social'],
                    'opcionales' => ['reservas', 'marketplace', 'banco_tiempo', 'foros', 'biblioteca', 'cursos', 'chat_grupos', 'crowdfunding'],
                    'sugeridos' => ['reservas', 'banco_tiempo', 'foros'],
                ],
                'paginas' => [
                    // === PÁGINA PRINCIPAL ===
                    [
                        'titulo' => __('Inicio', 'flavor-chat-ia'),
                        'slug' => 'inicio',
                        'contenido' => '[flavor_landing module="hub-emprendedores"]',
                        'parent' => 0,
                        'es_home' => true,
                        'template' => 'flavor-fullwidth',
                    ],
                    // === SECCIÓN: COMUNIDAD ===
                    [
                        'titulo' => __('Comunidad', 'flavor-chat-ia'),
                        'slug' => 'comunidad',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Nuestra Comunidad', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Conecta con otros emprendedores y haz crecer tu red.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Directorio', 'flavor-chat-ia'),
                        'slug' => 'directorio',
                        'contenido' => '[hub_directorio_emprendedores]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Grupos', 'flavor-chat-ia'),
                        'slug' => 'grupos',
                        'contenido' => '[comunidades_directorio]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Networking', 'flavor-chat-ia'),
                        'slug' => 'networking',
                        'contenido' => '[red_social_feed]',
                        'parent' => 'comunidad',
                    ],
                    // === SECCIÓN: ACTIVIDADES ===
                    [
                        'titulo' => __('Actividades', 'flavor-chat-ia'),
                        'slug' => 'actividades',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Eventos y Formación', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Workshops, pitchs, meetups y más.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Eventos', 'flavor-chat-ia'),
                        'slug' => 'eventos',
                        'contenido' => '[eventos_listado tipo="networking"]',
                        'parent' => 'actividades',
                    ],
                    [
                        'titulo' => __('Proyectos', 'flavor-chat-ia'),
                        'slug' => 'proyectos',
                        'contenido' => '[crowdfunding_proyectos]',
                        'parent' => 'actividades',
                    ],
                    [
                        'titulo' => __('Cursos', 'flavor-chat-ia'),
                        'slug' => 'cursos',
                        'contenido' => '[cursos_listado]',
                        'parent' => 'actividades',
                    ],
                    // === SECCIÓN: RECURSOS ===
                    [
                        'titulo' => __('Recursos', 'flavor-chat-ia'),
                        'slug' => 'recursos',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Recursos Compartidos', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Espacios, servicios e intercambio entre emprendedores.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Espacios', 'flavor-chat-ia'),
                        'slug' => 'espacios',
                        'contenido' => '[reservas_espacios]',
                        'parent' => 'recursos',
                    ],
                    [
                        'titulo' => __('Intercambio', 'flavor-chat-ia'),
                        'slug' => 'intercambio',
                        'contenido' => '[banco_tiempo_servicios]',
                        'parent' => 'recursos',
                    ],
                    [
                        'titulo' => __('Biblioteca', 'flavor-chat-ia'),
                        'slug' => 'biblioteca',
                        'contenido' => '[biblioteca_recursos]',
                        'parent' => 'recursos',
                    ],
                    // === SECCIÓN: MI PORTAL ===
                    [
                        'titulo' => __('Mi Portal', 'flavor-chat-ia'),
                        'slug' => 'mi-portal',
                        'contenido' => '[flavor_portal_usuario]',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Mi Perfil', 'flavor-chat-ia'),
                        'slug' => 'mi-perfil',
                        'contenido' => '[hub_mi_perfil_emprendedor]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Mis Reservas', 'flavor-chat-ia'),
                        'slug' => 'mis-reservas',
                        'contenido' => '[reservas_mis_reservas]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Mis Servicios', 'flavor-chat-ia'),
                        'slug' => 'mis-servicios',
                        'contenido' => '[banco_tiempo_mis_servicios]',
                        'parent' => 'mi-portal',
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'comunidad',
                            'datos' => [
                                'titulo' => __('Donde Nacen las Ideas', 'flavor-chat-ia'),
                                'subtitulo' => __('Comunidad de emprendedores que colaboran, aprenden y crecen juntos', 'flavor-chat-ia'),
                                'cta_texto' => __('Únete a la comunidad', 'flavor-chat-ia'),
                                'cta_url' => '/comunidad',
                            ],
                        ],
                        [
                            'tipo' => 'estadisticas',
                            'variante' => 'contador',
                            'datos' => [
                                'items' => [
                                    ['numero' => '150+', 'label' => __('Emprendedores', 'flavor-chat-ia')],
                                    ['numero' => '45', 'label' => __('Startups', 'flavor-chat-ia')],
                                    ['numero' => '20+', 'label' => __('Eventos/mes', 'flavor-chat-ia')],
                                    ['numero' => '€2M', 'label' => __('Inversión captada', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'servicios',
                            'variante' => 'iconos',
                            'datos' => [
                                'titulo' => __('Qué Ofrecemos', 'flavor-chat-ia'),
                                'items' => [
                                    ['icono' => 'dashicons-groups', 'titulo' => __('Networking', 'flavor-chat-ia'), 'descripcion' => __('Conecta con otros emprendedores', 'flavor-chat-ia')],
                                    ['icono' => 'dashicons-calendar', 'titulo' => __('Eventos', 'flavor-chat-ia'), 'descripcion' => __('Workshops, pitchs y meetups', 'flavor-chat-ia')],
                                    ['icono' => 'dashicons-building', 'titulo' => __('Espacios', 'flavor-chat-ia'), 'descripcion' => __('Coworking y salas de reuniones', 'flavor-chat-ia')],
                                    ['icono' => 'dashicons-lightbulb', 'titulo' => __('Mentoría', 'flavor-chat-ia'), 'descripcion' => __('Aprende de expertos', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'eventos',
                            'variante' => 'proximos',
                            'datos' => [
                                'titulo' => __('Próximos Eventos', 'flavor-chat-ia'),
                                'shortcode' => '[eventos_proximos limit="3"]',
                            ],
                        ],
                        [
                            'tipo' => 'miembros-destacados',
                            'variante' => 'carrusel',
                            'datos' => [
                                'titulo' => __('Emprendedores Destacados', 'flavor-chat-ia'),
                                'shortcode' => '[hub_emprendedores_destacados]',
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'simple',
                            'datos' => [
                                'titulo' => __('¿Tienes una idea?', 'flavor-chat-ia'),
                                'descripcion' => __('Únete a nuestra comunidad y hazla realidad', 'flavor-chat-ia'),
                                'cta_texto' => __('Empieza ahora', 'flavor-chat-ia'),
                                'cta_url' => '/mi-portal',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'comunidades' => [
                        'tipos' => ['sector', 'interes', 'proyecto'],
                        'crear_automaticamente' => ['tecnologia', 'sostenibilidad', 'impacto-social'],
                    ],
                    'eventos' => [
                        'tipos_predefinidos' => ['pitch', 'workshop', 'networking', 'masterclass', 'demo-day'],
                        'inscripcion_abierta' => true,
                    ],
                    'reservas' => [
                        'recursos' => ['sala-reuniones', 'puesto-coworking', 'sala-eventos'],
                        'anticipacion_maxima' => 30,
                    ],
                    'banco_tiempo' => [
                        'habilitar' => true,
                        'categorias' => ['diseño', 'desarrollo', 'marketing', 'legal', 'finanzas', 'mentoria'],
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Comunidad de ejemplo con emprendedores, eventos de networking, espacios de coworking e intercambio de servicios.', 'flavor-chat-ia'),
                ],
            ],

            // =========================================================
            // COOPERATIVA DE EMPRESAS
            // =========================================================
            'cooperativa_empresas' => [
                'nombre' => __('Cooperativa de Empresas', 'flavor-chat-ia'),
                'descripcion' => __('Cluster de pequeños negocios locales que colaboran: directorio compartido, marketplace conjunto, facturación cruzada y banco de tiempo empresarial.', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'color' => '#0d9488',
                'modulos' => [
                    'requeridos' => ['socios', 'clientes', 'marketplace'],
                    'opcionales' => ['facturas', 'banco_tiempo', 'bares', 'grupos_consumo', 'crowdfunding', 'eventos', 'chat_grupos', 'chat_interno', 'talleres', 'transparencia', 'participacion', 'trabajo_digno'],
                    'sugeridos' => ['facturas', 'banco_tiempo', 'eventos', 'bares'],
                ],
                'paginas' => [
                    // === PÁGINA PRINCIPAL ===
                    [
                        'titulo' => __('Ecosistema Empresarial', 'flavor-chat-ia'),
                        'slug' => 'ecosistema',
                        'contenido' => '[flavor_landing module="cooperativa-empresas"]',
                        'parent' => 0,
                        'es_home' => true,
                        'template' => 'flavor-fullwidth',
                    ],

                    // === SECCIÓN: DIRECTORIO Y NEGOCIOS ===
                    [
                        'titulo' => __('Negocios', 'flavor-chat-ia'),
                        'slug' => 'negocios',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Directorio de Negocios Locales', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Descubre todos los negocios de nuestra cooperativa.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Directorio', 'flavor-chat-ia'),
                        'slug' => 'directorio',
                        'contenido' => '[bares_mapa] [bares_listado]',
                        'parent' => 'negocios',
                    ],
                    [
                        'titulo' => __('Marketplace', 'flavor-chat-ia'),
                        'slug' => 'marketplace',
                        'contenido' => '[marketplace_listado categorias="productos,servicios"]',
                        'parent' => 'negocios',
                    ],
                    [
                        'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                        'slug' => 'banco-tiempo',
                        'contenido' => '[banco_tiempo_servicios]',
                        'parent' => 'negocios',
                    ],
                    [
                        'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                        'slug' => 'grupos-consumo',
                        'contenido' => '[gc_catalogo]',
                        'parent' => 'negocios',
                    ],

                    // === SECCIÓN: FORMACIÓN Y EVENTOS ===
                    [
                        'titulo' => __('Actividades', 'flavor-chat-ia'),
                        'slug' => 'actividades',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Eventos y Formación', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Networking, talleres y eventos para emprendedores.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Eventos', 'flavor-chat-ia'),
                        'slug' => 'eventos',
                        'contenido' => '[eventos_calendario] [eventos_proximos]',
                        'parent' => 'actividades',
                    ],
                    [
                        'titulo' => __('Talleres', 'flavor-chat-ia'),
                        'slug' => 'talleres',
                        'contenido' => '[talleres_listado]',
                        'parent' => 'actividades',
                    ],
                    [
                        'titulo' => __('Crowdfunding', 'flavor-chat-ia'),
                        'slug' => 'crowdfunding',
                        'contenido' => '[crowdfunding_proyectos]',
                        'parent' => 'actividades',
                    ],

                    // === SECCIÓN: ÁREA DE MIEMBRO ===
                    [
                        'titulo' => __('Mi Portal', 'flavor-chat-ia'),
                        'slug' => 'mi-portal',
                        'contenido' => '[flavor_user_dashboard]',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Mi Negocio', 'flavor-chat-ia'),
                        'slug' => 'mi-negocio',
                        'contenido' => '[perfil_negocio_editar]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Mis Clientes', 'flavor-chat-ia'),
                        'slug' => 'mis-clientes',
                        'contenido' => '[clientes_listado usuario_actual="true"]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Facturación', 'flavor-chat-ia'),
                        'slug' => 'facturacion',
                        'contenido' => '[facturas_listado usuario_actual="true"]',
                        'parent' => 'mi-portal',
                    ],
                    [
                        'titulo' => __('Mis Servicios', 'flavor-chat-ia'),
                        'slug' => 'mis-servicios',
                        'contenido' => '[marketplace_mis_anuncios] [banco_tiempo_mis_servicios]',
                        'parent' => 'mi-portal',
                    ],

                    // === SECCIÓN: COMUNIDAD ===
                    [
                        'titulo' => __('Comunidad', 'flavor-chat-ia'),
                        'slug' => 'comunidad',
                        'contenido' => '<!-- wp:heading --><h2>' . __('Nuestra Comunidad Empresarial', 'flavor-chat-ia') . '</h2><!-- /wp:heading -->
<!-- wp:paragraph --><p>' . __('Espacio de colaboración entre negocios del territorio.', 'flavor-chat-ia') . '</p><!-- /wp:paragraph -->',
                        'parent' => 0,
                    ],
                    [
                        'titulo' => __('Grupos de Interés', 'flavor-chat-ia'),
                        'slug' => 'grupos',
                        'contenido' => '[comunidades_listado]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Transparencia', 'flavor-chat-ia'),
                        'slug' => 'transparencia',
                        'contenido' => '[transparencia_portal]',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Noticias', 'flavor-chat-ia'),
                        'slug' => 'noticias',
                        'contenido' => '[avisos_listado categoria="noticias-empresas"]',
                        'parent' => 'comunidad',
                    ],
                ],
                'menu' => [
                    'nombre' => __('Menu Cooperativa Empresas', 'flavor-chat-ia'),
                    'ubicacion' => 'primary',
                    'items' => [
                        ['titulo' => __('Inicio', 'flavor-chat-ia'), 'url' => '/', 'icono' => 'home'],
                        [
                            'titulo' => __('Negocios', 'flavor-chat-ia'),
                            'url' => '/negocios/',
                            'icono' => 'store',
                            'hijos' => [
                                ['titulo' => __('Directorio', 'flavor-chat-ia'), 'url' => '/negocios/directorio/'],
                                ['titulo' => __('Marketplace', 'flavor-chat-ia'), 'url' => '/negocios/marketplace/'],
                                ['titulo' => __('Banco de Tiempo', 'flavor-chat-ia'), 'url' => '/negocios/banco-tiempo/'],
                                ['titulo' => __('Grupos de Consumo', 'flavor-chat-ia'), 'url' => '/negocios/grupos-consumo/'],
                            ],
                        ],
                        [
                            'titulo' => __('Actividades', 'flavor-chat-ia'),
                            'url' => '/actividades/',
                            'icono' => 'calendar',
                            'hijos' => [
                                ['titulo' => __('Eventos', 'flavor-chat-ia'), 'url' => '/actividades/eventos/'],
                                ['titulo' => __('Talleres', 'flavor-chat-ia'), 'url' => '/actividades/talleres/'],
                                ['titulo' => __('Crowdfunding', 'flavor-chat-ia'), 'url' => '/actividades/crowdfunding/'],
                            ],
                        ],
                        [
                            'titulo' => __('Mi Portal', 'flavor-chat-ia'),
                            'url' => '/mi-portal/',
                            'icono' => 'user',
                            'hijos' => [
                                ['titulo' => __('Mi Negocio', 'flavor-chat-ia'), 'url' => '/mi-portal/mi-negocio/'],
                                ['titulo' => __('Mis Clientes', 'flavor-chat-ia'), 'url' => '/mi-portal/mis-clientes/'],
                                ['titulo' => __('Facturación', 'flavor-chat-ia'), 'url' => '/mi-portal/facturacion/'],
                                ['titulo' => __('Mis Servicios', 'flavor-chat-ia'), 'url' => '/mi-portal/mis-servicios/'],
                            ],
                        ],
                        [
                            'titulo' => __('Comunidad', 'flavor-chat-ia'),
                            'url' => '/comunidad/',
                            'icono' => 'groups',
                            'hijos' => [
                                ['titulo' => __('Grupos', 'flavor-chat-ia'), 'url' => '/comunidad/grupos/'],
                                ['titulo' => __('Transparencia', 'flavor-chat-ia'), 'url' => '/comunidad/transparencia/'],
                                ['titulo' => __('Noticias', 'flavor-chat-ia'), 'url' => '/comunidad/noticias/'],
                            ],
                        ],
                    ],
                ],
                'landing' => [
                    'activa' => true,
                    'slug' => 'ecosistema',
                    'secciones' => [
                        [
                            'tipo' => 'hero',
                            'variante' => 'imagen-fondo',
                            'datos' => [
                                'titulo' => __('Cooperativa de Empresas Locales', 'flavor-chat-ia'),
                                'subtitulo' => __('Unidos para fortalecer la economía del territorio', 'flavor-chat-ia'),
                                'cta_texto' => __('Ver Negocios', 'flavor-chat-ia'),
                                'cta_url' => '/negocios/directorio/',
                                'imagen' => '',
                            ],
                        ],
                        [
                            'tipo' => 'stats',
                            'variante' => 'contador',
                            'datos' => [
                                'items' => [
                                    ['numero' => '50+', 'label' => __('Negocios', 'flavor-chat-ia')],
                                    ['numero' => '200+', 'label' => __('Productos', 'flavor-chat-ia')],
                                    ['numero' => '1000+', 'label' => __('Clientes', 'flavor-chat-ia')],
                                    ['numero' => '€100K', 'label' => __('Facturado', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'features',
                            'variante' => 'iconos-4-columnas',
                            'datos' => [
                                'titulo' => __('Qué Ofrecemos', 'flavor-chat-ia'),
                                'items' => [
                                    ['icono' => 'store', 'titulo' => __('Directorio', 'flavor-chat-ia'), 'descripcion' => __('Todos los negocios locales en un mapa', 'flavor-chat-ia')],
                                    ['icono' => 'cart', 'titulo' => __('Marketplace', 'flavor-chat-ia'), 'descripcion' => __('Compra productos y servicios locales', 'flavor-chat-ia')],
                                    ['icono' => 'clock', 'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'), 'descripcion' => __('Intercambia servicios entre negocios', 'flavor-chat-ia')],
                                    ['icono' => 'groups', 'titulo' => __('Comunidad', 'flavor-chat-ia'), 'descripcion' => __('Networking y colaboración empresarial', 'flavor-chat-ia')],
                                ],
                            ],
                        ],
                        [
                            'tipo' => 'grid',
                            'variante' => 'negocios-destacados',
                            'datos' => [
                                'titulo' => __('Negocios Destacados', 'flavor-chat-ia'),
                                'shortcode' => '[bares_destacados limite="6"]',
                            ],
                        ],
                        [
                            'tipo' => 'listing',
                            'variante' => 'productos',
                            'datos' => [
                                'titulo' => __('Últimos Productos', 'flavor-chat-ia'),
                                'shortcode' => '[marketplace_ultimos limite="4"]',
                            ],
                        ],
                        [
                            'tipo' => 'eventos',
                            'variante' => 'proximos',
                            'datos' => [
                                'titulo' => __('Próximos Eventos', 'flavor-chat-ia'),
                                'shortcode' => '[eventos_proximos limit="3"]',
                            ],
                        ],
                        [
                            'tipo' => 'cta',
                            'variante' => 'centrado',
                            'datos' => [
                                'titulo' => __('¿Tienes un negocio local?', 'flavor-chat-ia'),
                                'descripcion' => __('Únete a nuestra cooperativa y forma parte de la economía del territorio', 'flavor-chat-ia'),
                                'boton_texto' => __('Inscribir mi negocio', 'flavor-chat-ia'),
                                'boton_url' => '/mi-portal/mi-negocio/',
                            ],
                        ],
                    ],
                ],
                'configuracion' => [
                    'socios' => [
                        'tipo_principal' => 'empresa',
                        'campos_extra' => ['cif', 'sector', 'web', 'horario'],
                    ],
                    'marketplace' => [
                        'categorias' => ['productos', 'servicios', 'ofertas'],
                        'moderacion' => false,
                    ],
                    'banco_tiempo' => [
                        'habilitar' => true,
                        'categorias' => ['diseño', 'marketing', 'legal', 'contabilidad', 'logistica', 'formacion'],
                    ],
                    'bares' => [
                        'tipos' => ['comercio', 'hosteleria', 'servicios', 'artesania', 'alimentacion'],
                        'mostrar_mapa' => true,
                    ],
                ],
                'demo' => [
                    'disponible' => true,
                    'descripcion' => __('Incluye 10 negocios de ejemplo, productos en marketplace, servicios en banco de tiempo y eventos de networking.', 'flavor-chat-ia'),
                ],
            ],
        ];
    }
}
