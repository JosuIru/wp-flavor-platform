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
                    [
                        'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                        'slug' => 'grupos-consumo',
                        'contenido' => '[flavor_landing module="grupos-consumo"]',
                        'parent' => 0,
                        'es_landing' => true,
                    ],
                    [
                        'titulo' => __('Productores', 'flavor-chat-ia'),
                        'slug' => 'productores',
                        'contenido' => '<h1>Nuestros Productores</h1>
<p>Conoce a quienes cultivan y elaboran los productos que llegan a tu mesa.</p>

[flavor_module_listing module="grupos_consumo" action="listar_productores" columnas="3" limite="12"]',
                        'parent' => 'grupos-consumo',
                    ],
                    [
                        'titulo' => __('Productos', 'flavor-chat-ia'),
                        'slug' => 'productos',
                        'contenido' => '<h1>Productos Disponibles</h1>
<p>Productos frescos, locales y de temporada.</p>

[flavor_module_listing module="grupos_consumo" action="listar_productos" columnas="3" limite="12"]',
                        'parent' => 'grupos-consumo',
                    ],
                    [
                        'titulo' => __('Mi Pedido', 'flavor-chat-ia'),
                        'slug' => 'mi-pedido',
                        'contenido' => '<h1>Mi Pedido</h1>

[flavor_module_form module="grupos_consumo" action="hacer_pedido"]',
                        'parent' => 'grupos-consumo',
                    ],
                    [
                        'titulo' => __('Ciclo Actual', 'flavor-chat-ia'),
                        'slug' => 'ciclo',
                        'contenido' => '<h1>Ciclo de Pedidos Actual</h1>
<p>Estado del ciclo de pedidos en curso.</p>

[flavor_module_listing module="grupos_consumo" action="ciclo_actual"]',
                        'parent' => 'grupos-consumo',
                    ],
                    [
                        'titulo' => __('Mis Pedidos', 'flavor-chat-ia'),
                        'slug' => 'mis-pedidos',
                        'contenido' => '<h1>Mis Pedidos</h1>

[flavor_module_dashboard module="grupos_consumo"]',
                        'parent' => 'grupos-consumo',
                    ],
                ],
                'landing' => [
                    'activa' => true,
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
                'descripcion' => __('Gestion integral de una comunidad o asociacion: socios, eventos, foros y recursos compartidos.', 'flavor-chat-ia'),
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
                        'titulo' => __('Socios', 'flavor-chat-ia'),
                        'slug' => 'socios',
                        'contenido' => '<h1>Unete a Nuestra Comunidad</h1>
<p>Descubre los beneficios de ser socio</p>

<a href="/comunidad/socios/unirme/" class="flavor-button flavor-button-primary">Hacerse Socio</a>',
                        'parent' => 'comunidad',
                    ],
                    [
                        'titulo' => __('Hacerse Socio', 'flavor-chat-ia'),
                        'slug' => 'unirme',
                        'contenido' => '<h1>Unete como Socio</h1>

[flavor_module_form module="socios" action="dar_alta_socio"]',
                        'parent' => 'socios',
                    ],
                    [
                        'titulo' => __('Mi Perfil de Socio', 'flavor-chat-ia'),
                        'slug' => 'mi-perfil',
                        'contenido' => '<h1>Mi Perfil de Socio</h1>

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
                                    ['numero' => '250+', 'etiqueta' => __('Socios', 'flavor-chat-ia')],
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
                                'descripcion' => __('Hazte socio y disfruta de todos los beneficios', 'flavor-chat-ia'),
                                'boton_texto' => __('Hacerse Socio', 'flavor-chat-ia'),
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
                    'descripcion' => __('Incluye 6 eventos de ejemplo y configuracion de tipos de socios', 'flavor-chat-ia'),
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
        ];
    }
}
