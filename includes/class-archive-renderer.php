<?php
/**
 * Clase Archive Renderer
 *
 * Renderiza páginas de archivo de módulos usando los componentes shared,
 * eliminando la duplicación de código entre módulos.
 *
 * @package FlavorChatIA
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Flavor_Archive_Renderer
 *
 * Proporciona una forma unificada de renderizar páginas de archivo
 * para cualquier módulo del sistema.
 *
 * Uso básico:
 *   $renderer = new Flavor_Archive_Renderer();
 *   echo $renderer->render([
 *       'module'  => 'incidencias',
 *       'title'   => 'Incidencias del Barrio',
 *       'color'   => 'red',
 *       'items'   => $incidencias,
 *       'stats'   => [...],
 *   ]);
 *
 * @since 5.0.0
 */
class Flavor_Archive_Renderer {

    /**
     * Ruta base de componentes
     *
     * @var string
     */
    private $components_path;

    /**
     * Configuración por defecto
     *
     * @var array
     */
    private $defaults = [
        'module'           => '',
        'title'            => '',
        'subtitle'         => '',
        'icon'             => '',
        'color'            => 'blue',
        'items'            => [],
        'total'            => 0,
        'per_page'         => 12,
        'current_page'     => 1,
        'stats'            => [],
        'filters'          => [],
        'columns'          => 3,
        'layout'           => 'grid',
        'show_header'      => true,
        'show_stats'       => true,
        'show_filters'     => true,
        'show_pagination'  => true,
        'stats_layout'     => 'horizontal',
        'card_config'      => null,    // Configuración para card genérica (PREFERIDO)
        'card_template'    => '',      // Template legacy (fallback)
        'card_callback'    => null,
        'cta_text'         => '',
        'cta_action'       => '',
        'cta_url'          => '',
        'cta_icon'         => '',
        'badge'            => '',
        'filter_data_attr' => 'filter',
        'empty_state'      => [],
        'extra_content'    => '',
        'wrapper_class'    => '',
        'base_url'         => '',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->components_path = FLAVOR_CHAT_IA_PATH . 'templates/components/shared/';

        // Cargar funciones helper
        if (!function_exists('flavor_render_component')) {
            require_once $this->components_path . '_functions.php';
        }
    }

    /**
     * Renderiza una página de archivo completa
     *
     * @param array $config Configuración del archive
     * @return string HTML del archive
     */
    public function render(array $config): string {
        $config = wp_parse_args($config, $this->defaults);

        // Si no se especifica total, usar count de items
        if (empty($config['total']) && !empty($config['items'])) {
            $config['total'] = count($config['items']);
        }

        // Si no se especifica badge, generarlo automáticamente
        if (empty($config['badge']) && $config['total'] > 0) {
            $config['badge'] = sprintf(
                _n('%d registrado', '%d registrados', $config['total'], FLAVOR_PLATFORM_TEXT_DOMAIN),
                $config['total']
            );
        }

        // Card config: si no hay, intentar obtener del módulo
        if (empty($config['card_config']) && !empty($config['module'])) {
            $module_cfg = self::get_module_config($config['module']);
            if (!empty($module_cfg['card_config'])) {
                $config['card_config'] = $module_cfg['card_config'];
            }
        }

        // Fallback legacy: Template de card si no hay card_config
        if (empty($config['card_config']) && empty($config['card_template']) && !empty($config['module'])) {
            $config['card_template'] = $config['module'] . '/card';
        }

        ob_start();

        // Wrapper principal
        $wrapper_classes = 'flavor-frontend flavor-' . sanitize_html_class($config['module']) . '-archive';
        if ($config['wrapper_class']) {
            $wrapper_classes .= ' ' . esc_attr($config['wrapper_class']);
        }
        ?>
        <div class="<?php echo esc_attr($wrapper_classes); ?>">

            <?php
            // Header
            if ($config['show_header']) {
                $this->render_component('archive-header', [
                    'title'      => $config['title'],
                    'subtitle'   => $config['subtitle'],
                    'icon'       => $config['icon'],
                    'color'      => $config['color'],
                    'badge'      => $config['badge'],
                    'cta_text'   => $config['cta_text'],
                    'cta_action' => $config['cta_action'],
                    'cta_url'    => $config['cta_url'],
                    'cta_icon'   => $config['cta_icon'],
                ]);
            }

            // Stats
            if ($config['show_stats'] && !empty($config['stats'])) {
                $this->render_component('stats-grid', [
                    'stats'   => $config['stats'],
                    'columns' => count($config['stats']) <= 2 ? 2 : 4,
                    'layout'  => $config['stats_layout'],
                ]);
            }

            // Contenido extra (ej: "Cómo funciona" en marketplace)
            if (!empty($config['extra_content'])) {
                if (is_callable($config['extra_content'])) {
                    call_user_func($config['extra_content']);
                } else {
                    echo wp_kses_post($config['extra_content']);
                }
            }

            // Filtros
            if ($config['show_filters'] && !empty($config['filters'])) {
                $this->render_component('filter-pills', [
                    'filters'   => $config['filters'],
                    'color'     => $config['color'],
                    'data_attr' => $config['filter_data_attr'],
                    'target'    => '.flavor-items-grid',
                ]);
            }

            // Grid de items
            $this->render_component('items-grid', [
                'items'         => $config['items'],
                'columns'       => $config['columns'],
                'layout'        => $config['layout'],
                'card_config'   => $config['card_config'],   // Card genérica (preferido)
                'card_template' => $config['card_template'], // Legacy fallback
                'card_callback' => $config['card_callback'],
                'data_attr'     => $config['filter_data_attr'],
                'empty_state'   => wp_parse_args($config['empty_state'], [
                    'icon'       => $config['icon'] ?: '📭',
                    'title'      => sprintf(__('No hay %s', FLAVOR_PLATFORM_TEXT_DOMAIN), strtolower($config['title'] ?: 'elementos')),
                    'cta_text'   => $config['cta_text'],
                    'cta_action' => $config['cta_action'],
                    'cta_url'    => $config['cta_url'],
                    'color'      => $config['color'],
                ]),
            ]);

            // Paginación
            if ($config['show_pagination'] && $config['total'] > $config['per_page']) {
                $this->render_component('pagination', [
                    'total'      => $config['total'],
                    'per_page'   => $config['per_page'],
                    'current'    => $config['current_page'],
                    'color'      => $config['color'],
                    'base_url'   => $config['base_url'],
                ]);
            }
            ?>

        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Renderiza un componente shared
     *
     * @param string $component Nombre del componente
     * @param array  $args      Argumentos del componente
     * @return void
     */
    protected function render_component(string $component, array $args = []): void {
        $file = $this->components_path . $component . '.php';

        if (!file_exists($file)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo "<!-- Componente no encontrado: {$component} -->";
            }
            return;
        }

        extract($args, EXTR_SKIP);
        include $file;
    }

    /**
     * Obtiene la configuración para un módulo específico
     *
     * Proporciona valores predefinidos para módulos conocidos,
     * incluyendo card_config para usar la card genérica.
     *
     * @param string $module ID del módulo
     * @return array Configuración base del módulo
     */
    public static function get_module_config(string $module): array {
        $configs = [
            'incidencias' => [
                'title'     => __('Incidencias del Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Reporta y consulta problemas en espacios públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '⚠️',
                'color'     => 'red',
                'cta_text'  => __('Reportar incidencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_icon'  => '📝',
                'filters'   => [
                    ['id' => 'todos', 'label' => __('Todas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'active' => true],
                    ['id' => 'pendiente', 'label' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔴'],
                    ['id' => 'en_proceso', 'label' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟡'],
                    ['id' => 'resuelto', 'label' => __('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟢'],
                ],
                'filter_data_attr' => 'estado',
                'card_config' => [
                    'color'  => 'red',
                    'icon'   => '⚠️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'pendiente'  => 'red',
                            'en_proceso' => 'yellow',
                            'resuelto'   => 'green',
                        ],
                        'icons' => [
                            'pendiente'  => '🔴',
                            'en_proceso' => '🟡',
                            'resuelto'   => '🟢',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '📅', 'field' => 'fecha'],
                    ],
                    'data_attrs' => ['estado', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver detalle', 'icon' => '👁️', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'marketplace' => [
                'title'     => __('Marketplace Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Compra, vende e intercambia productos en tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🛒',
                'color'     => 'green',
                'cta_text'  => __('Publicar Anuncio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_icon'  => '📢',
                'stats_layout' => 'vertical',
                'card_config' => [
                    'color'  => 'green',
                    'icon'   => '🛒',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'venta'       => 'green',
                            'intercambio' => 'blue',
                            'regalo'      => 'purple',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '👤', 'field' => 'autor'],
                    ],
                    'data_attrs' => ['tipo', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Contactar', 'icon' => '💬', 'action' => 'flavorMarketplace.contactar({id})'],
                    ],
                ],
            ],

            'eventos' => [
                'title'     => __('Eventos del Barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Descubre y participa en actividades locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📅',
                'color'     => 'purple',
                'cta_text'  => __('Crear evento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_icon'  => '➕',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '📅',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'cultural'   => 'purple',
                            'deportivo'  => 'blue',
                            'social'     => 'green',
                            'formacion'  => 'orange',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '👥', 'field' => 'asistentes', 'suffix' => ' inscritos'],
                    ],
                    'data_attrs' => ['tipo', 'fecha'],
                    'actions' => [
                        ['label' => 'Ver evento', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Inscribirse', 'icon' => '✋', 'action' => 'flavorEventos.inscribirse({id})'],
                    ],
                ],
            ],

            'comunidades' => [
                'title'     => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Conecta con grupos de tu zona', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '👥',
                'color'     => 'blue',
                'cta_text'  => __('Crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_icon'  => '➕',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '👥',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'vecinal'     => 'blue',
                            'interes'     => 'purple',
                            'profesional' => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' miembros'],
                        ['icon' => '📍', 'field' => 'zona'],
                    ],
                    'data_attrs' => ['tipo', 'zona'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Unirse', 'icon' => '➕', 'action' => 'flavorComunidades.unirse({id})'],
                    ],
                ],
            ],

            'colectivos' => [
                'title'     => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Organizaciones y grupos del territorio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🏛️',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '🏛️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'cultural'    => 'purple',
                            'social'      => 'blue',
                            'deportivo'   => 'green',
                            'ambiental'   => 'lime',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' miembros'],
                        ['icon' => '📍', 'field' => 'sede'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Ver perfil', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'banco-tiempo' => [
                'title'     => __('Banco del Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Intercambia servicios con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '⏰',
                'color'     => 'teal',
                'cta_text'  => __('Ofrecer servicio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cta_icon'  => '🤝',
                'card_config' => [
                    'color'  => 'teal',
                    'icon'   => '⏰',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'oferta'   => 'teal',
                            'demanda'  => 'orange',
                        ],
                        'labels' => [
                            'oferta'  => 'Ofrezco',
                            'demanda' => 'Busco',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'horas',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '👤', 'field' => 'autor'],
                        ['icon' => '📍', 'field' => 'zona'],
                    ],
                    'data_attrs' => ['tipo', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Contactar', 'icon' => '💬', 'action' => 'flavorBancoTiempo.contactar({id})'],
                    ],
                ],
            ],

            'cursos' => [
                'title'     => __('Cursos y Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Aprende nuevas habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📚',
                'color'     => 'orange',
                'card_config' => [
                    'color'  => 'orange',
                    'icon'   => '📚',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'modalidad',
                        'colors' => [
                            'presencial' => 'orange',
                            'online'     => 'blue',
                            'hibrido'    => 'purple',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_inicio'],
                        ['icon' => '⏱️', 'field' => 'duracion'],
                        ['icon' => '👥', 'field' => 'plazas', 'suffix' => ' plazas'],
                    ],
                    'progress' => [
                        'field' => 'ocupacion',
                        'label' => 'Plazas ocupadas',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['modalidad', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver curso', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Inscribirse', 'icon' => '✋', 'action' => 'flavorCursos.inscribirse({id})'],
                    ],
                ],
            ],

            'reciclaje' => [
                'title'     => __('Puntos de Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Encuentra dónde reciclar cerca de ti', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '♻️',
                'color'     => 'green',
                'card_config' => [
                    'color'  => 'green',
                    'icon'   => '♻️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'direccion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'punto_limpio' => 'green',
                            'contenedor'   => 'blue',
                            'ropa'         => 'purple',
                            'electronico'  => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'direccion'],
                        ['icon' => '🕐', 'field' => 'horario'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Ver mapa', 'icon' => '🗺️', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'biodiversidad' => [
                'title'     => __('Biodiversidad Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Flora y fauna de nuestro entorno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌿',
                'color'     => 'lime',
                'card_config' => [
                    'color'  => 'lime',
                    'icon'   => '🌿',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre_comun',
                        'subtitle' => 'nombre_cientifico',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'flora' => 'lime',
                            'fauna' => 'amber',
                        ],
                        'icons' => [
                            'flora' => '🌱',
                            'fauna' => '🦋',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'habitat'],
                        ['icon' => '📊', 'field' => 'estado_conservacion'],
                    ],
                    'data_attrs' => ['tipo', 'habitat'],
                    'actions' => [
                        ['label' => 'Ver ficha', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'grupos-consumo' => [
                'title'     => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Consume productos locales, ecológicos y de temporada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🥕',
                'color'     => 'lime',
                'card_config' => [
                    'color'  => 'lime',
                    'icon'   => '🥕',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'abierto'  => 'green',
                            'cerrado'  => 'gray',
                            'completo' => 'amber',
                        ],
                        'labels' => [
                            'abierto'  => 'Admite socios',
                            'cerrado'  => 'Cerrado',
                            'completo' => 'Completo',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' familias'],
                        ['icon' => '📍', 'field' => 'zona'],
                        ['icon' => '🚚', 'field' => 'dia_reparto'],
                    ],
                    'data_attrs' => ['estado', 'zona'],
                    'actions' => [
                        ['label' => 'Ver grupo', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Unirse', 'icon' => '➕', 'action' => 'flavorGruposConsumo.unirse({id})'],
                    ],
                ],
            ],

            'socios' => [
                'title'     => __('Directorio de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Conecta con nuestra comunidad de miembros activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '👥',
                'color'     => 'rose',
                'card_config' => [
                    'color'  => 'rose',
                    'icon'   => '👤',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'bio',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'nivel',
                        'colors' => [
                            'premium' => 'amber',
                            'pro'     => 'blue',
                            'basico'  => 'gray',
                        ],
                        'icons' => [
                            'premium' => '⭐',
                            'pro'     => '🔷',
                            'basico'  => '🔘',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'miembro_desde', 'prefix' => 'Desde '],
                    ],
                    'data_attrs' => ['nivel'],
                    'actions' => [
                        ['label' => 'Ver perfil', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'radio' => [
                'title'     => __('Radio Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('La voz de tu barrio, 24 horas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📻',
                'color'     => 'red',
                'card_config' => [
                    'color'  => 'red',
                    'icon'   => '📻',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'noticias'    => 'red',
                            'musica'      => 'purple',
                            'entrevistas' => 'blue',
                            'deportes'    => 'green',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '🎤', 'field' => 'locutor'],
                        ['icon' => '🕐', 'field' => 'horario'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Escuchar', 'icon' => '▶️', 'action' => 'flavorRadio.play({id})', 'primary' => true],
                        ['label' => 'Ver más', 'url_field' => 'url'],
                    ],
                ],
            ],

            'presupuestos-participativos' => [
                'title'     => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Decide cómo se invierte el presupuesto de tu municipio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💰',
                'color'     => 'amber',
                'card_config' => [
                    'color'  => 'amber',
                    'icon'   => '💰',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'fase',
                        'colors' => [
                            'propuestas' => 'blue',
                            'evaluacion' => 'purple',
                            'votacion'   => 'amber',
                            'ejecucion'  => 'orange',
                            'completado' => 'green',
                        ],
                        'icons' => [
                            'propuestas' => '💡',
                            'evaluacion' => '🔍',
                            'votacion'   => '🗳️',
                            'ejecucion'  => '🔨',
                            'completado' => '✅',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'presupuesto',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '🗳️', 'field' => 'votos', 'suffix' => ' votos'],
                        ['icon' => '👤', 'field' => 'autor'],
                    ],
                    'progress' => [
                        'field' => 'porcentaje_votos',
                        'label' => 'Apoyo ciudadano',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['fase', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver proyecto', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Votar', 'icon' => '🗳️', 'action' => 'flavorPresupuestos.votar({id})'],
                    ],
                ],
            ],

            'transparencia' => [
                'title'     => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Acceso a toda la información pública del municipio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🏛️',
                'color'     => 'teal',
                'card_config' => [
                    'color'  => 'teal',
                    'icon'   => '📄',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'presupuestos' => 'amber',
                            'contratos'    => 'blue',
                            'personal'     => 'purple',
                            'subvenciones' => 'green',
                            'plenos'       => 'gray',
                        ],
                        'icons' => [
                            'presupuestos' => '💰',
                            'contratos'    => '📝',
                            'personal'     => '👥',
                            'subvenciones' => '🎁',
                            'plenos'       => '🏛️',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'formato',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_publicacion'],
                        ['icon' => '📁', 'field' => 'tamano'],
                    ],
                    'data_attrs' => ['categoria', 'formato'],
                    'actions' => [
                        ['label' => 'Descargar', 'icon' => '📥', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'avisos-municipales' => [
                'title'     => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Información oficial del ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📢',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '📢',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'resumen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'urgencia',
                        'colors' => [
                            'informativo' => 'blue',
                            'importante'  => 'amber',
                            'urgente'     => 'red',
                        ],
                        'icons' => [
                            'informativo' => 'ℹ️',
                            'importante'  => '⚠️',
                            'urgente'     => '🚨',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '🏛️', 'field' => 'departamento'],
                    ],
                    'data_attrs' => ['urgencia', 'departamento'],
                    'actions' => [
                        ['label' => 'Leer más', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'biblioteca' => [
                'title'     => __('Biblioteca Municipal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Catálogo de libros y recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📚',
                'color'     => 'amber',
                'card_config' => [
                    'color'  => 'amber',
                    'icon'   => '📖',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'autor',
                        'image'    => 'portada',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'disponibilidad',
                        'colors' => [
                            'disponible' => 'green',
                            'prestado'   => 'red',
                            'reservado'  => 'amber',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📚', 'field' => 'genero'],
                        ['icon' => '📅', 'field' => 'año'],
                    ],
                    'data_attrs' => ['disponibilidad', 'genero'],
                    'actions' => [
                        ['label' => 'Ver ficha', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Reservar', 'icon' => '📋', 'action' => 'flavorBiblioteca.reservar({id})'],
                    ],
                ],
            ],

            'bares' => [
                'title'     => __('Bares y Restaurantes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Descubre dónde comer en el barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🍽️',
                'color'     => 'orange',
                'card_config' => [
                    'color'  => 'orange',
                    'icon'   => '🍽️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'abierto',
                        'colors' => [
                            '1' => 'green',
                            '0' => 'gray',
                        ],
                        'labels' => [
                            '1' => 'Abierto',
                            '0' => 'Cerrado',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio_medio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '⭐', 'field' => 'valoracion'],
                        ['icon' => '📍', 'field' => 'direccion'],
                        ['icon' => '🕐', 'field' => 'horario'],
                    ],
                    'data_attrs' => ['tipo', 'precio'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Reservar', 'icon' => '📞', 'action' => 'flavorBares.reservar({id})'],
                    ],
                ],
            ],

            'parkings' => [
                'title'     => __('Parkings', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Encuentra aparcamiento cerca de ti', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🅿️',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '🅿️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'direccion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'disponible' => 'green',
                            'lleno'      => 'red',
                            'casi_lleno' => 'amber',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'tarifa',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '🚗', 'field' => 'plazas_libres', 'suffix' => ' libres'],
                        ['icon' => '🕐', 'field' => 'horario'],
                    ],
                    'progress' => [
                        'field' => 'ocupacion',
                        'label' => 'Ocupación',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Ver mapa', 'icon' => '🗺️', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'espacios-comunes' => [
                'title'     => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Reserva espacios para actividades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🏢',
                'color'     => 'purple',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '🏢',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'disponible',
                        'colors' => [
                            '1' => 'green',
                            '0' => 'red',
                        ],
                        'labels' => [
                            '1' => 'Disponible',
                            '0' => 'Ocupado',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'capacidad', 'suffix' => ' personas'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '🔧', 'field' => 'equipamiento'],
                    ],
                    'data_attrs' => ['tipo', 'disponible'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Reservar', 'icon' => '📅', 'action' => 'flavorEspacios.reservar({id})'],
                    ],
                ],
            ],

            'tramites' => [
                'title'     => __('Trámites Online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Gestiona tus trámites con el ayuntamiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📋',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '📋',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'modalidad',
                        'colors' => [
                            'online'     => 'green',
                            'presencial' => 'blue',
                            'mixto'      => 'purple',
                        ],
                        'icons' => [
                            'online'     => '💻',
                            'presencial' => '🏛️',
                            'mixto'      => '🔄',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '⏱️', 'field' => 'tiempo_estimado'],
                        ['icon' => '💰', 'field' => 'coste'],
                    ],
                    'data_attrs' => ['modalidad', 'categoria'],
                    'actions' => [
                        ['label' => 'Iniciar trámite', 'icon' => '▶️', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'compostaje' => [
                'title'     => __('Compostaje Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Reduce residuos y genera compost', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌱',
                'color'     => 'lime',
                'card_config' => [
                    'color'  => 'lime',
                    'icon'   => '🌱',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activa'       => 'green',
                            'llena'        => 'amber',
                            'mantenimiento'=> 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '👥', 'field' => 'participantes', 'suffix' => ' vecinos'],
                    ],
                    'progress' => [
                        'field' => 'capacidad_usada',
                        'label' => 'Capacidad',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['estado', 'zona'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Participar', 'icon' => '➕', 'action' => 'flavorCompostaje.participar({id})'],
                    ],
                ],
            ],

            'huertos-urbanos' => [
                'title'     => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Cultiva en tu parcela comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🥬',
                'color'     => 'green',
                'card_config' => [
                    'color'  => 'green',
                    'icon'   => '🥬',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'disponible' => 'green',
                            'ocupada'    => 'gray',
                            'lista_espera' => 'amber',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📐', 'field' => 'tamano', 'suffix' => ' m²'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '💧', 'field' => 'riego'],
                    ],
                    'data_attrs' => ['estado', 'zona'],
                    'actions' => [
                        ['label' => 'Ver parcela', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Solicitar', 'icon' => '📝', 'action' => 'flavorHuertos.solicitar({id})'],
                    ],
                ],
            ],

            'talleres' => [
                'title'     => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Aprende nuevas habilidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🎨',
                'color'     => 'pink',
                'card_config' => [
                    'color'  => 'pink',
                    'icon'   => '🎨',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'nivel',
                        'colors' => [
                            'principiante'  => 'green',
                            'intermedio'    => 'amber',
                            'avanzado'      => 'red',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '⏱️', 'field' => 'duracion'],
                        ['icon' => '👥', 'field' => 'plazas', 'suffix' => ' plazas'],
                    ],
                    'progress' => [
                        'field' => 'ocupacion',
                        'label' => 'Plazas ocupadas',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['nivel', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver taller', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Inscribirse', 'icon' => '✋', 'action' => 'flavorTalleres.inscribirse({id})'],
                    ],
                ],
            ],

            'carpooling' => [
                'title'     => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Comparte coche con tus vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🚗',
                'color'     => 'cyan',
                'card_config' => [
                    'color'  => 'cyan',
                    'icon'   => '🚗',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'ruta',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'oferta'   => 'green',
                            'demanda'  => 'blue',
                        ],
                        'labels' => [
                            'oferta'  => 'Ofrece plaza',
                            'demanda' => 'Busca plaza',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '🕐', 'field' => 'hora'],
                        ['icon' => '💺', 'field' => 'plazas', 'suffix' => ' plazas'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Ver viaje', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Contactar', 'icon' => '💬', 'action' => 'flavorCarpooling.contactar({id})'],
                    ],
                ],
            ],

            'participacion' => [
                'title'     => __('Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Tu voz cuenta en las decisiones del barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🗳️',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '🗳️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activa'   => 'green',
                            'cerrada'  => 'gray',
                            'proxima'  => 'blue',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_limite'],
                        ['icon' => '🗳️', 'field' => 'votos', 'suffix' => ' votos'],
                    ],
                    'progress' => [
                        'field' => 'participacion',
                        'label' => 'Participación',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Participar', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'multimedia' => [
                'title'     => __('Galería Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Fotos y vídeos de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📷',
                'color'     => 'rose',
                'card_config' => [
                    'color'  => 'rose',
                    'icon'   => '📷',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'thumbnail',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'foto'  => 'rose',
                            'video' => 'blue',
                            'audio' => 'purple',
                        ],
                        'icons' => [
                            'foto'  => '📷',
                            'video' => '🎬',
                            'audio' => '🎵',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '👤', 'field' => 'autor'],
                    ],
                    'data_attrs' => ['tipo', 'album'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'podcast' => [
                'title'     => __('Podcast Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Escucha las voces del barrio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🎙️',
                'color'     => 'purple',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '🎙️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'portada',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'entrevistas' => 'purple',
                            'reportajes'  => 'blue',
                            'debates'     => 'orange',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '⏱️', 'field' => 'duracion'],
                        ['icon' => '🎧', 'field' => 'reproducciones', 'suffix' => ' escuchas'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Escuchar', 'icon' => '▶️', 'action' => 'flavorPodcast.play({id})', 'primary' => true],
                        ['label' => 'Descargar', 'icon' => '📥', 'url_field' => 'url_descarga'],
                    ],
                ],
            ],

            'reservas' => [
                'title'     => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Reserva equipamiento y espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📅',
                'color'     => 'cyan',
                'card_config' => [
                    'color'  => 'cyan',
                    'icon'   => '📅',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'disponible',
                        'colors' => [
                            '1' => 'green',
                            '0' => 'red',
                        ],
                        'labels' => [
                            '1' => 'Disponible',
                            '0' => 'Reservado',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '🕐', 'field' => 'horario'],
                    ],
                    'data_attrs' => ['tipo', 'disponible'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Reservar', 'icon' => '📅', 'action' => 'flavorReservas.reservar({id})'],
                    ],
                ],
            ],

            'advertising' => [
                'title'     => __('Publicidad Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Espacios publicitarios del municipio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📣',
                'color'     => 'orange',
                'card_config' => [
                    'color'  => 'orange',
                    'icon'   => '📣',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activo'    => 'green',
                            'pausado'   => 'amber',
                            'finalizado'=> 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👁️', 'field' => 'impresiones', 'suffix' => ' vistas'],
                        ['icon' => '📅', 'field' => 'fecha_fin'],
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Ver campaña', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'ayuda-vecinal' => [
                'title'     => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Red de apoyo entre vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🤝',
                'color'     => 'rose',
                'card_config' => [
                    'color'  => 'rose',
                    'icon'   => '🤝',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'ofrezco' => 'green',
                            'necesito'=> 'blue',
                        ],
                        'labels' => [
                            'ofrezco' => 'Ofrezco ayuda',
                            'necesito'=> 'Necesito ayuda',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👤', 'field' => 'autor'],
                        ['icon' => '📍', 'field' => 'zona'],
                        ['icon' => '📅', 'field' => 'fecha'],
                    ],
                    'data_attrs' => ['tipo', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Contactar', 'icon' => '💬', 'action' => 'flavorAyudaVecinal.contactar({id})'],
                    ],
                ],
            ],

            'bicicletas-compartidas' => [
                'title'     => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Sistema de préstamo de bicis', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🚲',
                'color'     => 'lime',
                'card_config' => [
                    'color'  => 'lime',
                    'icon'   => '🚲',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'disponible',
                        'colors' => [
                            '1' => 'green',
                            '0' => 'red',
                        ],
                        'labels' => [
                            '1' => 'Disponible',
                            '0' => 'En uso',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'estacion'],
                        ['icon' => '🔋', 'field' => 'bateria', 'suffix' => '%'],
                    ],
                    'data_attrs' => ['disponible', 'tipo'],
                    'actions' => [
                        ['label' => 'Reservar', 'icon' => '🚲', 'action' => 'flavorBicis.reservar({id})', 'primary' => true],
                    ],
                ],
            ],

            'circulos-cuidados' => [
                'title'     => __('Círculos de Cuidados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Redes de apoyo y cuidado mutuo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💜',
                'color'     => 'purple',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '💜',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'acompanamiento' => 'purple',
                            'cuidado_ninos'  => 'pink',
                            'mayores'        => 'blue',
                            'salud'          => 'green',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' personas'],
                        ['icon' => '📍', 'field' => 'zona'],
                    ],
                    'data_attrs' => ['tipo', 'zona'],
                    'actions' => [
                        ['label' => 'Ver círculo', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Unirse', 'icon' => '➕', 'action' => 'flavorCirculos.unirse({id})'],
                    ],
                ],
            ],

            'economia-don' => [
                'title'     => __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Dar sin esperar nada a cambio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🎁',
                'color'     => 'pink',
                'card_config' => [
                    'color'  => 'pink',
                    'icon'   => '🎁',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'disponible' => 'green',
                            'reservado'  => 'amber',
                            'entregado'  => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👤', 'field' => 'donante'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                    ],
                    'data_attrs' => ['estado', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Solicitar', 'icon' => '🙋', 'action' => 'flavorDon.solicitar({id})'],
                    ],
                ],
            ],

            'economia-suficiencia' => [
                'title'     => __('Economía de Suficiencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Vivir con lo necesario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌱',
                'color'     => 'teal',
                'card_config' => [
                    'color'  => 'teal',
                    'icon'   => '🌱',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'articulo'  => 'teal',
                            'recurso'   => 'blue',
                            'taller'    => 'purple',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '👁️', 'field' => 'lecturas', 'suffix' => ' lecturas'],
                    ],
                    'data_attrs' => ['tipo', 'tema'],
                    'actions' => [
                        ['label' => 'Leer', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'encuestas' => [
                'title'     => __('Encuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Tu opinión importa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📊',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '📊',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activa'   => 'green',
                            'cerrada'  => 'gray',
                            'proxima'  => 'blue',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_limite'],
                        ['icon' => '👥', 'field' => 'respuestas', 'suffix' => ' respuestas'],
                    ],
                    'progress' => [
                        'field' => 'participacion',
                        'label' => 'Participación',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Participar', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'foros' => [
                'title'     => __('Foros de Discusión', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Debate y comparte ideas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💬',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '💬',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'ultimo_mensaje',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'general'     => 'blue',
                            'propuestas'  => 'green',
                            'ayuda'       => 'amber',
                            'off_topic'   => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '💬', 'field' => 'respuestas', 'suffix' => ' respuestas'],
                        ['icon' => '👁️', 'field' => 'vistas', 'suffix' => ' vistas'],
                        ['icon' => '📅', 'field' => 'ultima_actividad'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Ver tema', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'huella-ecologica' => [
                'title'     => __('Huella Ecológica', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Mide y reduce tu impacto ambiental', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌍',
                'color'     => 'green',
                'card_config' => [
                    'color'  => 'green',
                    'icon'   => '🌍',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'consejo'    => 'green',
                            'calculadora'=> 'blue',
                            'reto'       => 'purple',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '🌱', 'field' => 'ahorro_co2', 'suffix' => ' kg CO₂'],
                    ],
                    'data_attrs' => ['tipo', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'justicia-restaurativa' => [
                'title'     => __('Justicia Restaurativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Resolución pacífica de conflictos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '⚖️',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '⚖️',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'abierto'   => 'blue',
                            'mediacion' => 'amber',
                            'resuelto'  => 'green',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '👥', 'field' => 'partes', 'suffix' => ' partes'],
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Ver caso', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'saberes-ancestrales' => [
                'title'     => __('Saberes Ancestrales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Conocimientos tradicionales de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📜',
                'color'     => 'amber',
                'card_config' => [
                    'color'  => 'amber',
                    'icon'   => '📜',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'categoria',
                        'colors' => [
                            'agricultura' => 'green',
                            'artesania'   => 'amber',
                            'medicina'    => 'red',
                            'tradiciones' => 'purple',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👤', 'field' => 'autor'],
                        ['icon' => '📍', 'field' => 'origen'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Leer', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'sello-conciencia' => [
                'title'     => __('Sello Conciencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Comercios y servicios con impacto positivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🏅',
                'color'     => 'amber',
                'card_config' => [
                    'color'  => 'amber',
                    'icon'   => '🏅',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'nivel',
                        'colors' => [
                            'oro'    => 'amber',
                            'plata'  => 'gray',
                            'bronce' => 'orange',
                        ],
                        'icons' => [
                            'oro'    => '🥇',
                            'plata'  => '🥈',
                            'bronce' => '🥉',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'direccion'],
                        ['icon' => '🏷️', 'field' => 'categoria'],
                    ],
                    'data_attrs' => ['nivel', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver perfil', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'trabajo-digno' => [
                'title'     => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Empleo con condiciones justas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💼',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '💼',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo_empresa',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo_contrato',
                        'colors' => [
                            'indefinido' => 'green',
                            'temporal'   => 'amber',
                            'practicas'  => 'blue',
                            'autonomo'   => 'purple',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'salario',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '🏢', 'field' => 'empresa'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '⏰', 'field' => 'jornada'],
                    ],
                    'data_attrs' => ['tipo_contrato', 'sector'],
                    'actions' => [
                        ['label' => 'Ver oferta', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Aplicar', 'icon' => '📝', 'action' => 'flavorTrabajo.aplicar({id})'],
                    ],
                ],
            ],

            'recetas' => [
                'title'     => __('Recetas Comunitarias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Cocina local y tradicional', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🍳',
                'color'     => 'orange',
                'card_config' => [
                    'color'  => 'orange',
                    'icon'   => '🍳',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'dificultad',
                        'colors' => [
                            'facil'  => 'green',
                            'media'  => 'amber',
                            'dificil'=> 'red',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '⏱️', 'field' => 'tiempo', 'suffix' => ' min'],
                        ['icon' => '👥', 'field' => 'porciones', 'suffix' => ' raciones'],
                        ['icon' => '⭐', 'field' => 'valoracion'],
                    ],
                    'data_attrs' => ['dificultad', 'categoria'],
                    'actions' => [
                        ['label' => 'Ver receta', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'red-social' => [
                'title'     => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Conecta con tu comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌐',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '👤',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'bio',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'seguidores', 'suffix' => ' seguidores'],
                        ['icon' => '📍', 'field' => 'ubicacion'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Ver perfil', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Seguir', 'icon' => '➕', 'action' => 'flavorRedSocial.seguir({id})'],
                    ],
                ],
            ],

            'mapa-actores' => [
                'title'     => __('Mapa de Actores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Organizaciones y personas clave', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🗺️',
                'color'     => 'teal',
                'card_config' => [
                    'color'  => 'teal',
                    'icon'   => '📍',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'institucion' => 'blue',
                            'ong'         => 'green',
                            'empresa'     => 'gray',
                            'colectivo'   => 'purple',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'ubicacion'],
                        ['icon' => '🔗', 'field' => 'conexiones', 'suffix' => ' conexiones'],
                    ],
                    'data_attrs' => ['tipo', 'sector'],
                    'actions' => [
                        ['label' => 'Ver ficha', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'seguimiento-denuncias' => [
                'title'     => __('Seguimiento de Denuncias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Consulta el estado de tus denuncias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📋',
                'color'     => 'red',
                'card_config' => [
                    'color'  => 'red',
                    'icon'   => '📋',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'asunto',
                        'subtitle' => 'descripcion',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'recibida'    => 'blue',
                            'en_tramite'  => 'amber',
                            'resuelta'    => 'green',
                            'archivada'   => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '🔢', 'field' => 'numero_expediente'],
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Ver detalle', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'email-marketing' => [
                'title'     => __('Campañas de Email', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Gestión de newsletters y comunicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📧',
                'color'     => 'cyan',
                'card_config' => [
                    'color'  => 'cyan',
                    'icon'   => '📧',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'asunto',
                        'subtitle' => 'preview',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'borrador'  => 'gray',
                            'programado'=> 'blue',
                            'enviado'   => 'green',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'destinatarios', 'suffix' => ' destinatarios'],
                        ['icon' => '📊', 'field' => 'apertura', 'suffix' => '% apertura'],
                    ],
                    'data_attrs' => ['estado', 'lista'],
                    'actions' => [
                        ['label' => 'Ver campaña', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'empresarial' => [
                'title'     => __('Directorio Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Empresas y negocios locales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🏢',
                'color'     => 'gray',
                'card_config' => [
                    'color'  => 'gray',
                    'icon'   => '🏢',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'sector',
                        'colors' => [
                            'servicios'  => 'blue',
                            'comercio'   => 'green',
                            'industria'  => 'gray',
                            'tecnologia' => 'purple',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'direccion'],
                        ['icon' => '📞', 'field' => 'telefono'],
                        ['icon' => '👥', 'field' => 'empleados', 'suffix' => ' empleados'],
                    ],
                    'data_attrs' => ['sector', 'tamano'],
                    'actions' => [
                        ['label' => 'Ver empresa', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Contactar', 'icon' => '📞', 'action' => 'flavorEmpresarial.contactar({id})'],
                    ],
                ],
            ],

            'clientes' => [
                'title'     => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Gestión de clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '👥',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '👤',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'email',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activo'   => 'green',
                            'inactivo' => 'gray',
                            'potencial'=> 'blue',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📞', 'field' => 'telefono'],
                        ['icon' => '📅', 'field' => 'ultimo_contacto'],
                    ],
                    'data_attrs' => ['estado', 'segmento'],
                    'actions' => [
                        ['label' => 'Ver ficha', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'facturas' => [
                'title'     => __('Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Gestión de facturación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🧾',
                'color'     => 'green',
                'card_config' => [
                    'color'  => 'green',
                    'icon'   => '🧾',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'numero',
                        'subtitle' => 'cliente',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'pagada'    => 'green',
                            'pendiente' => 'amber',
                            'vencida'   => 'red',
                            'borrador'  => 'gray',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'total',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha'],
                        ['icon' => '📅', 'field' => 'vencimiento', 'prefix' => 'Vence: '],
                    ],
                    'data_attrs' => ['estado'],
                    'actions' => [
                        ['label' => 'Ver factura', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Descargar', 'icon' => '📥', 'url_field' => 'url_pdf'],
                    ],
                ],
            ],

            'fichaje-empleados' => [
                'title'     => __('Control de Fichaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Registro de jornada laboral', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '⏰',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '👤',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'empleado',
                        'subtitle' => 'puesto',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'trabajando' => 'green',
                            'ausente'    => 'gray',
                            'descanso'   => 'amber',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '🕐', 'field' => 'hora_entrada'],
                        ['icon' => '⏱️', 'field' => 'horas_hoy', 'suffix' => 'h hoy'],
                    ],
                    'data_attrs' => ['estado', 'departamento'],
                    'actions' => [
                        ['label' => 'Ver registro', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'documentacion-legal' => [
                'title'     => __('Documentación Legal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Normativa y documentos oficiales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '⚖️',
                'color'     => 'gray',
                'card_config' => [
                    'color'  => 'gray',
                    'icon'   => '📄',
                    'show_image' => false,
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'resumen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'ley'        => 'blue',
                            'ordenanza'  => 'purple',
                            'reglamento' => 'gray',
                            'acuerdo'    => 'green',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_publicacion'],
                        ['icon' => '📄', 'field' => 'formato'],
                    ],
                    'data_attrs' => ['tipo', 'ambito'],
                    'actions' => [
                        ['label' => 'Descargar', 'icon' => '📥', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'dex-solana' => [
                'title'     => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Exchange descentralizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💱',
                'color'     => 'purple',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '💱',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'par',
                        'subtitle' => 'descripcion',
                        'image'    => 'logo',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'cambio_24h',
                        'colors' => [
                            'positivo' => 'green',
                            'negativo' => 'red',
                            'neutro'   => 'gray',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '📊', 'field' => 'volumen_24h'],
                        ['icon' => '💧', 'field' => 'liquidez'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Operar', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'trading-ia' => [
                'title'     => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Trading automatizado con IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🤖',
                'color'     => 'cyan',
                'card_config' => [
                    'color'  => 'cyan',
                    'icon'   => '🤖',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activo'   => 'green',
                            'pausado'  => 'amber',
                            'inactivo' => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📈', 'field' => 'rendimiento', 'suffix' => '%'],
                        ['icon' => '💰', 'field' => 'operaciones', 'suffix' => ' ops'],
                    ],
                    'data_attrs' => ['estado', 'estrategia'],
                    'actions' => [
                        ['label' => 'Ver bot', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'woocommerce' => [
                'title'     => __('Tienda Online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Productos y servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🛍️',
                'color'     => 'purple',
                'card_config' => [
                    'color'  => 'purple',
                    'icon'   => '🛍️',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion_corta',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'en_oferta',
                        'colors' => [
                            '1' => 'red',
                            '0' => 'gray',
                        ],
                        'labels' => [
                            '1' => 'Oferta',
                            '0' => '',
                        ],
                    ],
                    'secondary_badge' => [
                        'field' => 'precio',
                        'color' => 'gray',
                    ],
                    'meta' => [
                        ['icon' => '⭐', 'field' => 'valoracion'],
                        ['icon' => '📦', 'field' => 'stock', 'suffix' => ' en stock'],
                    ],
                    'data_attrs' => ['categoria'],
                    'actions' => [
                        ['label' => 'Ver producto', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Añadir', 'icon' => '🛒', 'action' => 'flavorWoo.addToCart({id})'],
                    ],
                ],
            ],

            // Alias: biodiversidad-local usa la misma config que biodiversidad
            'biodiversidad-local' => [
                'title'     => __('Biodiversidad Local', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Flora y fauna de nuestro entorno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '🌿',
                'color'     => 'lime',
                'card_config' => [
                    'color'  => 'lime',
                    'icon'   => '🌿',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre_comun',
                        'subtitle' => 'nombre_cientifico',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'flora' => 'lime',
                            'fauna' => 'amber',
                        ],
                        'icons' => [
                            'flora' => '🌱',
                            'fauna' => '🦋',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📍', 'field' => 'habitat'],
                        ['icon' => '📊', 'field' => 'estado_conservacion'],
                    ],
                    'data_attrs' => ['tipo', 'habitat'],
                    'actions' => [
                        ['label' => 'Ver ficha', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'campanias' => [
                'title'     => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Campañas y acciones colectivas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📢',
                'color'     => 'orange',
                'card_config' => [
                    'color'  => 'orange',
                    'icon'   => '📢',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'titulo',
                        'subtitle' => 'descripcion',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'estado',
                        'colors' => [
                            'activa'    => 'green',
                            'proxima'   => 'blue',
                            'finalizada'=> 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '📅', 'field' => 'fecha_inicio'],
                        ['icon' => '👥', 'field' => 'participantes', 'suffix' => ' participantes'],
                    ],
                    'progress' => [
                        'field' => 'progreso',
                        'label' => 'Objetivo',
                        'max'   => 100,
                    ],
                    'data_attrs' => ['estado', 'tipo'],
                    'actions' => [
                        ['label' => 'Ver campaña', 'url_field' => 'url', 'primary' => true],
                        ['label' => 'Participar', 'icon' => '✋', 'action' => 'flavorCampanias.participar({id})'],
                    ],
                ],
            ],

            'chat-grupos' => [
                'title'     => __('Grupos de Chat', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Conversaciones grupales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💬',
                'color'     => 'blue',
                'card_config' => [
                    'color'  => 'blue',
                    'icon'   => '💬',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'descripcion',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'tipo',
                        'colors' => [
                            'publico' => 'green',
                            'privado' => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '👥', 'field' => 'miembros', 'suffix' => ' miembros'],
                        ['icon' => '💬', 'field' => 'mensajes_hoy', 'suffix' => ' mensajes hoy'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Abrir chat', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'chat-interno' => [
                'title'     => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Mensajería privada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '💬',
                'color'     => 'indigo',
                'card_config' => [
                    'color'  => 'indigo',
                    'icon'   => '👤',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'nombre',
                        'subtitle' => 'ultimo_mensaje',
                        'image'    => 'avatar',
                        'url'      => 'url',
                    ],
                    'badge' => [
                        'field'  => 'sin_leer',
                        'colors' => [
                            '1' => 'red',
                            '0' => 'gray',
                        ],
                    ],
                    'meta' => [
                        ['icon' => '🕐', 'field' => 'ultima_actividad'],
                    ],
                    'data_attrs' => ['estado'],
                    'actions' => [
                        ['label' => 'Abrir', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],

            'chat-estados' => [
                'title'     => __('Estados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subtitle'  => __('Comparte tu momento', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon'      => '📸',
                'color'     => 'pink',
                'card_config' => [
                    'color'  => 'pink',
                    'icon'   => '📸',
                    'image_aspect' => 'aspect-square',
                    'fields' => [
                        'id'       => 'id',
                        'title'    => 'autor',
                        'subtitle' => 'texto',
                        'image'    => 'imagen',
                        'url'      => 'url',
                    ],
                    'meta' => [
                        ['icon' => '🕐', 'field' => 'hace'],
                        ['icon' => '👁️', 'field' => 'vistas', 'suffix' => ' vistas'],
                    ],
                    'data_attrs' => ['tipo'],
                    'actions' => [
                        ['label' => 'Ver', 'url_field' => 'url', 'primary' => true],
                    ],
                ],
            ],
        ];

        return $configs[$module] ?? [];
    }

    /**
     * Renderiza un archive de módulo con configuración automática
     *
     * @param string $module ID del módulo
     * @param array  $data   Datos del módulo (items, stats, etc.)
     * @param array  $config Configuración adicional (sobrescribe defaults)
     * @return string HTML del archive
     */
    public function render_module(string $module, array $data = [], array $config = []): string {
        // Obtener config base del módulo
        $module_config = self::get_module_config($module);

        // Merge: defaults < module_config < data < config
        $final_config = wp_parse_args($config, $data);
        $final_config = wp_parse_args($final_config, $module_config);
        $final_config['module'] = $module;

        return $this->render($final_config);
    }

    /**
     * Renderiza un módulo obteniendo datos automáticamente de la BD
     *
     * Este es el método principal para usar desde templates.
     * Obtiene items, estadísticas y configuración automáticamente.
     *
     * @param string $module ID del módulo (ej: 'incidencias', 'marketplace')
     * @param array  $config Configuración adicional para sobreescribir
     * @return string HTML del archive
     */
    public function render_auto(string $module, array $config = []): string {
        // Obtener datos del módulo (pasar config para filtros como user_id)
        $data = $this->get_module_data($module, $config);

        // Renderizar con los datos obtenidos
        return $this->render_module($module, $data, $config);
    }

    /**
     * Obtiene datos de un módulo desde la BD
     *
     * Retorna items, stats, paginación, etc. según la configuración
     * de tablas definida para cada módulo.
     *
     * @param string $module ID del módulo
     * @param array  $config Configuración adicional (user_id, per_page, etc.)
     * @return array ['items' => [...], 'stats' => [...], 'total' => int, ...]
     */
    public function get_module_data(string $module, array $config = []): array {
        global $wpdb;

        // Configuración de tablas por módulo
        $tables_config = $this->get_module_table_config($module);

        if (empty($tables_config)) {
            return ['items' => [], 'stats' => [], 'total' => 0];
        }

        $table = $wpdb->prefix . $tables_config['table'];

        // Verificar si la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($table)) {
            return ['items' => [], 'stats' => [], 'total' => 0];
        }

        // Parámetros de paginación
        $per_page = intval($_GET['per_page'] ?? 12);
        $current_page = max(1, intval($_GET['pag'] ?? 1));
        $offset = ($current_page - 1) * $per_page;

        // Filtros desde URL
        $filter_field = $tables_config['filter_field'] ?? 'estado';
        $filter_value = isset($_GET[$filter_field]) ? sanitize_text_field($_GET[$filter_field]) : '';

        // Construir query
        $where = [];
        $params = [];

        // Excluir eliminados si aplica
        if (!empty($tables_config['exclude_status'])) {
            $where[] = "{$filter_field} != %s";
            $params[] = $tables_config['exclude_status'];
        }

        // Filtro por URL
        if ($filter_value && $filter_value !== 'todos') {
            $where[] = "{$filter_field} = %s";
            $params[] = $filter_value;
        }

        // Filtro por usuario (para vistas "mis-*")
        if (!empty($config['user_id'])) {
            $user_field = $tables_config['user_field'] ?? 'user_id';
            $where[] = "{$user_field} = %d";
            $params[] = intval($config['user_id']);
        }

        $where_sql = !empty($where) ? implode(' AND ', $where) : '1=1';

        // Obtener total
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_sql";
        $total = !empty($params)
            ? (int) $wpdb->get_var($wpdb->prepare($count_query, $params))
            : (int) $wpdb->get_var($count_query);

        // Obtener items
        $order_by = $tables_config['order_by'] ?? 'created_at DESC';
        $query = "SELECT * FROM $table WHERE $where_sql ORDER BY $order_by LIMIT %d OFFSET %d";
        $query_params = array_merge($params, [$per_page, $offset]);
        $rows = $wpdb->get_results($wpdb->prepare($query, $query_params));

        // Transformar a formato de items
        $items = $this->transform_rows_to_items($rows, $tables_config, $module);

        // Obtener estadísticas
        $stats = $this->get_module_stats($table, $tables_config);

        return [
            'items'        => $items,
            'stats'        => $stats,
            'total'        => $total,
            'per_page'     => $per_page,
            'current_page' => $current_page,
        ];
    }

    /**
     * Transforma filas de BD al formato de items para el renderer
     *
     * @param array  $rows          Filas de la BD
     * @param array  $tables_config Configuración de la tabla
     * @param string $module        ID del módulo
     * @return array Items formateados
     */
    private function transform_rows_to_items(array $rows, array $tables_config, string $module): array {
        $items = [];
        $fields = $tables_config['fields'] ?? [];
        $base_url = home_url("/mi-portal/{$module}/");

        foreach ($rows as $row) {
            $item = [
                'id' => $row->{$fields['id'] ?? 'id'} ?? $row->id ?? 0,
            ];

            // Mapear campos configurados
            foreach ($fields as $target => $source) {
                if (isset($row->$source)) {
                    $item[$target] = $row->$source;
                }
            }

            // Procesar campos especiales
            if (isset($item['descripcion'])) {
                $item['descripcion'] = wp_trim_words($item['descripcion'], 25);
            }

            // URL por defecto
            if (!isset($item['url'])) {
                $item['url'] = $base_url . $item['id'] . '/';
            }

            // Fecha formateada
            if (isset($row->created_at)) {
                $item['fecha'] = date_i18n(get_option('date_format'), strtotime($row->created_at));
            } elseif (isset($row->fecha_creacion)) {
                $item['fecha'] = date_i18n(get_option('date_format'), strtotime($row->fecha_creacion));
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * Obtiene estadísticas de un módulo
     *
     * @param string $table         Nombre completo de la tabla
     * @param array  $tables_config Configuración de la tabla
     * @return array Estadísticas formateadas para stats-grid
     */
    private function get_module_stats(string $table, array $tables_config): array {
        global $wpdb;

        if (empty($tables_config['stats'])) {
            return [];
        }

        $stats = [];
        $count_where_stats = [];
        $custom_query_stats = [];

        // Separar stats por tipo para optimizar
        foreach ($tables_config['stats'] as $index => $stat_config) {
            if (!empty($stat_config['query'])) {
                $custom_query_stats[$index] = $stat_config;
            } elseif (!empty($stat_config['count_where'])) {
                $count_where_stats[$index] = $stat_config;
            }
        }

        // Consolidar count_where en una sola query usando CASE WHEN
        $count_results = [];
        if (!empty($count_where_stats)) {
            $case_parts = [];
            foreach ($count_where_stats as $index => $stat_config) {
                $case_parts[] = "SUM(CASE WHEN {$stat_config['count_where']} THEN 1 ELSE 0 END) AS stat_{$index}";
            }

            $consolidated_query = "SELECT " . implode(', ', $case_parts) . " FROM {$table}";
            $result = $wpdb->get_row($consolidated_query);

            if ($result) {
                foreach ($count_where_stats as $index => $stat_config) {
                    $count_results[$index] = (int) ($result->{"stat_{$index}"} ?? 0);
                }
            }
        }

        // Ejecutar queries personalizadas (no consolidables)
        $custom_results = [];
        foreach ($custom_query_stats as $index => $stat_config) {
            $query = str_replace(
                ['{table}', '{prefix}'],
                [$table, $wpdb->prefix],
                $stat_config['query']
            );
            $custom_results[$index] = $wpdb->get_var($query);
        }

        // Reconstruir stats en orden original
        foreach ($tables_config['stats'] as $index => $stat_config) {
            $value = 0;
            if (isset($count_results[$index])) {
                $value = $count_results[$index];
            } elseif (isset($custom_results[$index])) {
                $value = $custom_results[$index];
            }

            $stats[] = [
                'value' => $value ?? 0,
                'label' => $stat_config['label'],
                'icon'  => $stat_config['icon'] ?? '',
                'color' => $stat_config['color'] ?? 'blue',
            ];
        }

        return $stats;
    }

    /**
     * Configuración de tablas por módulo
     *
     * Define tabla, campos, filtros y estadísticas para cada módulo.
     *
     * @param string $module ID del módulo
     * @return array Configuración de la tabla
     */
    private function get_module_table_config(string $module): array {
        global $wpdb;

        $configs = [
            'incidencias' => [
                'table'          => 'flavor_incidencias',
                'filter_field'   => 'estado',
                'exclude_status' => 'eliminada',
                'order_by'       => 'created_at DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'tipo'        => 'tipo',
                    'ubicacion'   => 'ubicacion',
                    'categoria'   => 'categoria',
                ],
                'stats' => [
                    ['label' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔴', 'color' => 'red', 'count_where' => "estado = 'pendiente'"],
                    ['label' => __('En proceso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟡', 'color' => 'yellow', 'count_where' => "estado IN ('en_proceso', 'validada')"],
                    ['label' => __('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟢', 'color' => 'green', 'count_where' => "estado = 'resuelta'"],
                    ['label' => __('Días promedio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📊', 'color' => 'blue', 'query' => "SELECT COALESCE(ROUND(AVG(DATEDIFF(fecha_resolucion, created_at)), 1), 0) FROM {table} WHERE estado = 'resuelta' AND fecha_resolucion IS NOT NULL"],
                ],
            ],

            'marketplace' => [
                'table'          => 'flavor_anuncios',
                'filter_field'   => 'estado',
                'exclude_status' => 'eliminado',
                'order_by'       => 'created_at DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen_principal',
                    'precio'      => 'precio',
                    'estado'      => 'estado',
                    'categoria'   => 'categoria',
                ],
                'stats' => [
                    ['label' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🟢', 'color' => 'green', 'count_where' => "estado = 'activo'"],
                    ['label' => __('Vendidos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅', 'color' => 'blue', 'count_where' => "estado = 'vendido'"],
                    ['label' => __('Reservados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔒', 'color' => 'yellow', 'count_where' => "estado = 'reservado'"],
                ],
            ],

            'eventos' => [
                'table'          => 'flavor_eventos',
                'filter_field'   => 'estado',
                'order_by'       => 'fecha_inicio ASC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'fecha_inicio'=> 'fecha_inicio',
                    'ubicacion'   => 'ubicacion',
                    'categoria'   => 'categoria',
                ],
                'stats' => [
                    ['label' => __('Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📅', 'color' => 'blue', 'count_where' => "fecha_inicio > NOW()"],
                    ['label' => __('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🎯', 'color' => 'green', 'count_where' => "DATE(fecha_inicio) = CURDATE()"],
                    ['label' => __('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📆', 'color' => 'purple', 'count_where' => "fecha_inicio BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 7 DAY)"],
                ],
            ],

            'banco-tiempo' => [
                'table'          => 'flavor_banco_tiempo_servicios',
                'filter_field'   => 'categoria',
                'order_by'       => 'fecha_publicacion DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'tipo'        => 'categoria',
                    'horas'       => 'horas_estimadas',
                    'categoria'   => 'categoria',
                ],
                'stats' => [
                    ['label' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🤝', 'color' => 'green', 'count_where' => "estado = 'activo'"],
                    ['label' => __('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🗂️', 'color' => 'blue', 'query' => "SELECT COUNT(DISTINCT categoria) FROM {table} WHERE estado = 'activo'"],
                    ['label' => __('Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🔄', 'color' => 'purple', 'query' => "SELECT COUNT(*) FROM {prefix}flavor_banco_tiempo_transacciones WHERE estado = 'completado'"],
                ],
            ],

            'presupuestos-participativos' => [
                'table'          => 'flavor_pp_proyectos',
                'filter_field'   => 'estado',
                'user_field'     => 'proponente_id',
                'order_by'       => 'votos_recibidos DESC, fecha_creacion DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'categoria'   => 'categoria',
                    'presupuesto' => 'presupuesto_solicitado',
                    'votos'       => 'votos_recibidos',
                    'ubicacion'   => 'ubicacion',
                ],
                'stats' => [
                    ['label' => __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '💡', 'color' => 'blue', 'count_where' => "1=1"],
                    ['label' => __('Validados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅', 'color' => 'green', 'count_where' => "estado = 'validado'"],
                    ['label' => __('En votación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🗳️', 'color' => 'amber', 'count_where' => "estado = 'en_votacion'"],
                    ['label' => __('Seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🏆', 'color' => 'purple', 'count_where' => "estado IN ('seleccionado', 'en_ejecucion', 'ejecutado')"],
                ],
            ],

            'comunidades' => [
                'table'          => 'flavor_comunidades',
                'filter_field'   => 'estado',
                'order_by'       => 'miembros_count DESC, created_at DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'categoria'   => 'tipo',
                    'miembros'    => 'miembros_count',
                ],
                'stats' => [
                    ['label' => __('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🏘️', 'color' => 'green', 'count_where' => "estado = 'activa'"],
                    ['label' => __('Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👥', 'color' => 'blue', 'query' => "SELECT COALESCE(SUM(miembros_count), 0) FROM {table}"],
                ],
            ],

            'cursos' => [
                'table'          => 'flavor_cursos',
                'filter_field'   => 'estado',
                'order_by'       => 'fecha_inicio DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'categoria'   => 'categoria',
                    'precio'      => 'precio',
                    'duracion'    => 'duracion',
                ],
                'stats' => [
                    ['label' => __('Activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📚', 'color' => 'green', 'count_where' => "estado = 'activo'"],
                    ['label' => __('Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '📅', 'color' => 'blue', 'count_where' => "fecha_inicio > NOW()"],
                ],
            ],

            'talleres' => [
                'table'          => 'flavor_talleres',
                'filter_field'   => 'estado',
                'order_by'       => 'fecha DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'categoria'   => 'categoria',
                    'fecha'       => 'fecha',
                    'ubicacion'   => 'ubicacion',
                ],
                'stats' => [
                    ['label' => __('Próximos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🎨', 'color' => 'green', 'count_where' => "fecha > NOW()"],
                    ['label' => __('Plazas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👥', 'color' => 'blue', 'query' => "SELECT COALESCE(SUM(plazas - inscritos), 0) FROM {table} WHERE fecha > NOW()"],
                ],
            ],

            'reservas' => [
                'table'          => 'flavor_reservas',
                'filter_field'   => 'estado',
                'user_field'     => 'user_id',
                'order_by'       => 'fecha_inicio DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'estado'      => 'estado',
                    'fecha_inicio'=> 'fecha_inicio',
                    'fecha_fin'   => 'fecha_fin',
                    'recurso'     => 'recurso_nombre',
                ],
                'stats' => [
                    ['label' => __('Pendientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🕐', 'color' => 'amber', 'count_where' => "estado = 'pendiente'"],
                    ['label' => __('Confirmadas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅', 'color' => 'green', 'count_where' => "estado = 'confirmada'"],
                ],
            ],

            'grupos-consumo' => [
                'table'          => 'flavor_gc_grupos',
                'filter_field'   => 'estado',
                'order_by'       => 'fecha_creacion DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'nombre',
                    'descripcion' => 'descripcion',
                    'imagen'      => 'imagen',
                    'estado'      => 'estado',
                    'miembros'    => 'max_miembros',
                    'fecha'       => 'fecha_creacion',
                ],
                'stats' => [
                    ['label' => __('Grupos activos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🥬', 'color' => 'green', 'count_where' => "estado = 'activo'"],
                    ['label' => __('Consumidores', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '👥', 'color' => 'blue', 'query' => "SELECT COUNT(*) FROM {prefix}flavor_gc_consumidores WHERE estado = 'activo'"],
                ],
            ],

            'participacion' => [
                'table'          => 'flavor_votaciones',
                'filter_field'   => 'estado',
                'order_by'       => 'fecha_fin DESC',
                'fields'         => [
                    'id'          => 'id',
                    'titulo'      => 'titulo',
                    'descripcion' => 'descripcion',
                    'estado'      => 'estado',
                    'tipo'        => 'tipo',
                    'fecha_inicio'=> 'fecha_inicio',
                    'fecha_fin'   => 'fecha_fin',
                ],
                'stats' => [
                    ['label' => __('Activas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '🗳️', 'color' => 'green', 'count_where' => "estado = 'activa' AND fecha_fin > NOW()"],
                    ['label' => __('Finalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'icon' => '✅', 'color' => 'blue', 'count_where' => "estado = 'finalizada'"],
                ],
            ],

            // Añadir más módulos según se necesite...
        ];

        return $configs[$module] ?? [];
    }

    /**
     * Renderiza un single/detalle de un módulo automáticamente
     *
     * Usa la configuración del módulo y obtiene el item de la BD.
     *
     * @param string $module ID del módulo
     * @param int|null $id ID del item (si null, se obtiene de $_GET['id'] o URL)
     * @param array $config Configuración adicional
     * @return string HTML del single
     */
    public function render_single_auto(string $module, ?int $id = null, array $config = []): string {
        // Obtener ID del item
        if ($id === null) {
            $id = intval($_GET['id'] ?? get_query_var('flavor_item_id', 0));
        }

        if (empty($id)) {
            return $this->render_single_error(__('Item no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener datos del item
        $item = $this->get_single_item($module, $id);

        if (empty($item)) {
            return $this->render_single_error(__('Item no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener configuración del módulo
        $module_config = $this->get_renderer_config_for_module($module);

        // Renderizar con layout estándar
        return $this->render_single_layout($module, $item, array_merge($module_config, $config));
    }

    /**
     * Obtiene un item individual de la BD
     */
    protected function get_single_item(string $module, int $id): ?array {
        global $wpdb;

        $tables_config = $this->get_module_table_config($module);
        if (empty($tables_config)) {
            return null;
        }

        $table = $wpdb->prefix . $tables_config['table'];
        $pk = $tables_config['primary_key'] ?? 'id';

        if (!Flavor_Chat_Helpers::tabla_existe($table)) {
            return null;
        }

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE $pk = %d",
            $id
        ), ARRAY_A);

        if (!$row) {
            return null;
        }

        // Transformar campos según configuración
        $fields = $tables_config['fields'] ?? [];
        $item = $row;

        // Mapear campos si hay configuración
        if (!empty($fields)) {
            foreach ($fields as $dest => $source) {
                if (is_string($source) && isset($row[$source])) {
                    $item[$dest] = $row[$source];
                }
            }
        }

        // Generar URL si no existe
        if (empty($item['url'])) {
            $item['url'] = home_url("/{$module}/?id={$id}");
        }

        return $item;
    }

    /**
     * Renderiza el layout de un single
     */
    protected function render_single_layout(string $module, array $item, array $config): string {
        ob_start();

        // Variables para el template
        $title = $item['titulo'] ?? $item['nombre'] ?? $item['title'] ?? '';
        $subtitle = $item['subtitulo'] ?? $item['descripcion_corta'] ?? '';
        $description = $item['descripcion'] ?? $item['contenido'] ?? $item['description'] ?? '';
        $image = $item['imagen'] ?? $item['foto'] ?? $item['image'] ?? '';
        $estado = $item['estado'] ?? $item['status'] ?? '';
        $color = $config['color'] ?? 'primary';
        $icon = $config['icon'] ?? '';
        $module_title = $config['title'] ?? ucfirst($module);

        // Cargar funciones helper si no están cargadas
        if (!function_exists('flavor_get_gradient_classes')) {
            require_once $this->components_path . '_functions.php';
        }

        // Resolver color semántico
        $resolved_color = function_exists('flavor_resolve_theme_color')
            ? flavor_resolve_theme_color($color)
            : $color;

        // Estados del módulo
        $estados = $config['estados'] ?? [];
        $estado_config = $estados[$estado] ?? [];
        ?>
        <div class="flavor-single flavor-<?php echo esc_attr($module); ?>-single">
            <?php
            // Breadcrumb
            $this->render_component('breadcrumb', [
                'items' => [
                    ['label' => __('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => home_url('/')],
                    ['label' => $module_title, 'url' => home_url("/{$module}/")],
                    ['label' => $title, 'current' => true],
                ],
            ]);
            ?>

            <div class="container mx-auto max-w-5xl px-4 py-8">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="sticky top-4 space-y-4">
                            <?php if ($image): ?>
                            <div class="relative aspect-[4/3] rounded-2xl overflow-hidden shadow-xl bg-gray-100">
                                <img src="<?php echo esc_url($image); ?>"
                                     alt="<?php echo esc_attr($title); ?>"
                                     class="w-full h-full object-cover">
                                <?php if ($estado && !empty($estado_config)): ?>
                                <span class="absolute top-3 right-3 px-3 py-1 rounded-full text-sm font-bold bg-<?php echo esc_attr($estado_config['color'] ?? 'gray'); ?>-500 text-white">
                                    <?php echo esc_html(($estado_config['icon'] ?? '') . ' ' . ($estado_config['label'] ?? $estado)); ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($config['single_cta'])): ?>
                            <button class="w-full py-4 rounded-xl text-lg font-semibold text-white transition-all hover:scale-105 bg-<?php echo esc_attr($resolved_color); ?>-600 hover:bg-<?php echo esc_attr($resolved_color); ?>-700"
                                    onclick="<?php echo esc_attr($config['single_cta']['action'] ?? ''); ?>">
                                <?php echo esc_html($config['single_cta']['text'] ?? __('Acción', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                            </button>
                            <?php endif; ?>

                            <?php
                            // Meta info sidebar
                            if (!empty($config['single_meta'])):
                                foreach ($config['single_meta'] as $meta):
                                    $value = $item[$meta['field'] ?? ''] ?? '';
                                    if ($value):
                            ?>
                            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                                <div class="flex items-center gap-3">
                                    <span class="text-2xl"><?php echo esc_html($meta['icon'] ?? '📌'); ?></span>
                                    <div>
                                        <p class="text-sm text-gray-500"><?php echo esc_html($meta['label'] ?? ''); ?></p>
                                        <p class="font-medium text-gray-900"><?php echo esc_html($value); ?></p>
                                    </div>
                                </div>
                            </div>
                            <?php
                                    endif;
                                endforeach;
                            endif;
                            ?>
                        </div>
                    </div>

                    <!-- Contenido principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <div class="bg-white rounded-2xl p-6 shadow-md">
                            <?php if ($estado && !empty($estado_config)): ?>
                            <span class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-<?php echo esc_attr($estado_config['color'] ?? 'gray'); ?>-100 text-<?php echo esc_attr($estado_config['color'] ?? 'gray'); ?>-700 mb-4">
                                <?php echo esc_html(($estado_config['icon'] ?? '') . ' ' . ($estado_config['label'] ?? $estado)); ?>
                            </span>
                            <?php endif; ?>

                            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                                <?php echo esc_html($icon . ' ' . $title); ?>
                            </h1>

                            <?php if ($subtitle): ?>
                            <p class="text-xl text-gray-600 mb-6"><?php echo esc_html($subtitle); ?></p>
                            <?php endif; ?>

                            <?php if ($description): ?>
                            <div class="prose max-w-none text-gray-700">
                                <?php echo wp_kses_post($description); ?>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php
                        // Secciones adicionales configurables
                        if (!empty($config['single_sections'])):
                            foreach ($config['single_sections'] as $section):
                                $section_data = [];
                                if (!empty($section['data_field']) && isset($item[$section['data_field']])) {
                                    $section_data = $item[$section['data_field']];
                                }
                        ?>
                        <div class="bg-white rounded-2xl p-6 shadow-md">
                            <h2 class="text-lg font-bold text-gray-900 mb-4">
                                <?php echo esc_html(($section['icon'] ?? '') . ' ' . ($section['title'] ?? '')); ?>
                            </h2>
                            <?php
                            if (!empty($section['component'])) {
                                $this->render_component($section['component'], array_merge(
                                    $section['props'] ?? [],
                                    ['items' => $section_data, 'data' => $item]
                                ));
                            } elseif (!empty($section['content'])) {
                                echo wp_kses_post($section['content']);
                            }
                            ?>
                        </div>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza error de single
     */
    protected function render_single_error(string $message): string {
        ob_start();
        $this->render_component('empty-state', [
            'icon'    => '❌',
            'title'   => $message,
            'message' => __('El elemento que buscas no existe o ha sido eliminado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'cta'     => [
                'text' => __('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url'  => 'javascript:history.back()',
            ],
        ]);
        return ob_get_clean();
    }

    /**
     * Obtiene la configuración del renderer para un módulo
     */
    protected function get_renderer_config_for_module(string $module): array {
        // Normalizar nombre del módulo a formato de clase
        $module_normalized = str_replace('-', '_', $module);
        $class_name = 'Flavor_' . str_replace(' ', '_', ucwords(str_replace('_', ' ', $module_normalized))) . '_Module';

        // Intentar cargar configuración del módulo
        if (class_exists($class_name) && method_exists($class_name, 'get_renderer_config')) {
            return call_user_func([$class_name, 'get_renderer_config']);
        }

        // Fallback a configuración estática
        return self::get_module_config($module);
    }
}

/**
 * Función helper para acceso rápido al renderer
 *
 * @param array $config Configuración del archive
 * @return string HTML del archive
 */
function flavor_render_archive(array $config): string {
    static $renderer = null;

    if ($renderer === null) {
        $renderer = new Flavor_Archive_Renderer();
    }

    return $renderer->render($config);
}

/**
 * Función helper para renderizar archive de un módulo
 *
 * @param string $module ID del módulo
 * @param array  $data   Datos del módulo
 * @param array  $config Configuración adicional
 * @return string HTML del archive
 */
function flavor_render_module_archive(string $module, array $data = [], array $config = []): string {
    static $renderer = null;

    if ($renderer === null) {
        $renderer = new Flavor_Archive_Renderer();
    }

    return $renderer->render_module($module, $data, $config);
}

/**
 * Función helper para renderizar single de un módulo automáticamente
 *
 * @param string $module ID del módulo
 * @param int|null $id ID del item (opcional, se detecta automáticamente)
 * @param array $config Configuración adicional
 * @return string HTML del single
 */
function flavor_render_single_auto(string $module, ?int $id = null, array $config = []): string {
    static $renderer = null;

    if ($renderer === null) {
        $renderer = new Flavor_Archive_Renderer();
    }

    return $renderer->render_single_auto($module, $id, $config);
}
