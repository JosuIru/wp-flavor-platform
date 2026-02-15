<?php
/**
 * Sistema de Breadcrumbs para el Dashboard
 *
 * Genera navegacion de migas de pan accesible con soporte para:
 * - Schema.org markup
 * - ARIA attributes
 * - Integracion con paginas dinamicas del portal
 *
 * @package FlavorChatIA
 * @subpackage Frontend
 * @since 4.1.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase Flavor_Dashboard_Breadcrumbs
 *
 * @since 4.1.0
 */
class Flavor_Dashboard_Breadcrumbs {

    /**
     * Instancia singleton
     *
     * @var Flavor_Dashboard_Breadcrumbs|null
     */
    private static $instance = null;

    /**
     * Items del breadcrumb
     *
     * @var array
     */
    private $items = [];

    /**
     * Separador entre items
     *
     * @var string
     */
    private $separator = '';

    /**
     * Configuracion
     *
     * @var array
     */
    private $config = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Dashboard_Breadcrumbs
     */
    public static function get_instance(): Flavor_Dashboard_Breadcrumbs {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->config = [
            'home_label'    => __('Inicio', 'flavor-chat-ia'),
            'home_icon'     => 'dashicons-admin-home',
            'portal_label'  => __('Mi Portal', 'flavor-chat-ia'),
            'portal_url'    => home_url('/mi-portal/'),
            'show_home'     => true,
            'show_current'  => true,
            'schema_type'   => 'BreadcrumbList',
            'max_items'     => 5,
        ];

        // Configurar separador con icono
        $this->separator = '<span class="fl-breadcrumb__separator" aria-hidden="true">'
                         . '<span class="dashicons dashicons-arrow-right-alt2"></span>'
                         . '</span>';

        add_action('init', [$this, 'init_hooks']);
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    public function init_hooks(): void {
        // Shortcode para mostrar breadcrumbs
        add_shortcode('flavor_breadcrumbs', [$this, 'render_shortcode']);

        // Accion para templates
        add_action('flavor_breadcrumbs', [$this, 'render']);

        // Filtro para modificar configuracion
        $this->config = apply_filters('flavor_breadcrumbs_config', $this->config);
    }

    /**
     * Renderiza los breadcrumbs
     *
     * @param array $args Argumentos opcionales
     * @return void
     */
    public static function render(array $args = []): void {
        $instance = self::get_instance();
        echo $instance->get_breadcrumbs($args);
    }

    /**
     * Shortcode para renderizar breadcrumbs
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML de breadcrumbs
     */
    public function render_shortcode($atts = []): string {
        $atts = shortcode_atts([
            'show_home'    => 'true',
            'show_current' => 'true',
            'class'        => '',
        ], $atts, 'flavor_breadcrumbs');

        return $this->get_breadcrumbs([
            'show_home'    => $atts['show_home'] === 'true',
            'show_current' => $atts['show_current'] === 'true',
            'extra_class'  => sanitize_html_class($atts['class']),
        ]);
    }

    /**
     * Obtiene el HTML de breadcrumbs
     *
     * @param array $args Argumentos
     * @return string HTML
     */
    public function get_breadcrumbs(array $args = []): string {
        $args = wp_parse_args($args, [
            'show_home'    => $this->config['show_home'],
            'show_current' => $this->config['show_current'],
            'extra_class'  => '',
        ]);

        // Construir items
        $this->build_items($args);

        // Si no hay items, no mostrar nada
        if (empty($this->items)) {
            return '';
        }

        // Generar HTML
        return $this->generate_html($args);
    }

    /**
     * Construye los items del breadcrumb
     *
     * @param array $args Argumentos
     * @return void
     */
    private function build_items(array $args): void {
        $this->items = [];

        // 1. Inicio (Home)
        if ($args['show_home']) {
            $this->items[] = [
                'label' => $this->config['home_label'],
                'url'   => home_url('/'),
                'icon'  => $this->config['home_icon'],
                'type'  => 'home',
            ];
        }

        // 2. Mi Portal (si estamos en el portal)
        if ($this->is_portal_page()) {
            $this->items[] = [
                'label' => $this->config['portal_label'],
                'url'   => $this->config['portal_url'],
                'icon'  => 'dashicons-dashboard',
                'type'  => 'portal',
            ];

            // 3. Modulo actual (si aplica)
            $modulo_actual = $this->get_current_module();
            if ($modulo_actual) {
                $this->items[] = [
                    'label' => $modulo_actual['label'],
                    'url'   => $modulo_actual['url'],
                    'icon'  => $modulo_actual['icon'],
                    'type'  => 'module',
                ];

                // 4. Vista actual dentro del modulo (si aplica)
                $vista_actual = $this->get_current_view();
                if ($vista_actual) {
                    $this->items[] = [
                        'label' => $vista_actual['label'],
                        'url'   => $vista_actual['url'],
                        'icon'  => $vista_actual['icon'] ?? '',
                        'type'  => 'view',
                    ];
                }
            }
        } else {
            // Paginas normales de WordPress
            $this->build_wordpress_items();
        }

        // Marcar el ultimo como actual
        if (!empty($this->items) && $args['show_current']) {
            $ultimo_indice = count($this->items) - 1;
            $this->items[$ultimo_indice]['is_current'] = true;
            $this->items[$ultimo_indice]['url'] = ''; // El actual no tiene link
        }

        // Truncar si hay demasiados items
        if (count($this->items) > $this->config['max_items']) {
            $this->truncate_items();
        }

        /**
         * Filtro para modificar los items del breadcrumb
         *
         * @param array $items Items del breadcrumb
         */
        $this->items = apply_filters('flavor_breadcrumb_items', $this->items);
    }

    /**
     * Verifica si estamos en una pagina del portal
     *
     * @return bool
     */
    private function is_portal_page(): bool {
        $url_actual = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($url_actual, '/mi-portal') !== false;
    }

    /**
     * Obtiene el modulo actual basado en la URL
     *
     * @return array|null
     */
    private function get_current_module(): ?array {
        $url_actual = $_SERVER['REQUEST_URI'] ?? '';

        // Extraer el slug del modulo de la URL
        // Formato: /mi-portal/{modulo}/ o /mi-portal/{modulo}/{vista}/
        if (preg_match('#/mi-portal/([^/]+)/?#', $url_actual, $matches)) {
            $slug_modulo = sanitize_title($matches[1]);

            // Obtener informacion del modulo
            $modulo_info = $this->get_module_info($slug_modulo);

            if ($modulo_info) {
                return [
                    'label' => $modulo_info['nombre'],
                    'url'   => home_url('/mi-portal/' . $slug_modulo . '/'),
                    'icon'  => $modulo_info['icono'] ?? 'dashicons-admin-generic',
                    'id'    => $slug_modulo,
                ];
            }
        }

        return null;
    }

    /**
     * Obtiene la vista actual dentro de un modulo
     *
     * @return array|null
     */
    private function get_current_view(): ?array {
        $url_actual = $_SERVER['REQUEST_URI'] ?? '';

        // Formato: /mi-portal/{modulo}/{vista}/
        if (preg_match('#/mi-portal/[^/]+/([^/]+)/?#', $url_actual, $matches)) {
            $slug_vista = sanitize_title($matches[1]);

            // Mapear vistas comunes
            $vistas_comunes = [
                'listado'       => __('Listado', 'flavor-chat-ia'),
                'nuevo'         => __('Nuevo', 'flavor-chat-ia'),
                'crear'         => __('Crear', 'flavor-chat-ia'),
                'editar'        => __('Editar', 'flavor-chat-ia'),
                'detalle'       => __('Detalle', 'flavor-chat-ia'),
                'ver'           => __('Ver', 'flavor-chat-ia'),
                'mis-reservas'  => __('Mis Reservas', 'flavor-chat-ia'),
                'mis-eventos'   => __('Mis Eventos', 'flavor-chat-ia'),
                'calendario'    => __('Calendario', 'flavor-chat-ia'),
                'mapa'          => __('Mapa', 'flavor-chat-ia'),
                'estadisticas'  => __('Estadisticas', 'flavor-chat-ia'),
                'configuracion' => __('Configuracion', 'flavor-chat-ia'),
            ];

            $label_vista = $vistas_comunes[$slug_vista] ?? ucfirst(str_replace('-', ' ', $slug_vista));

            return [
                'label' => $label_vista,
                'url'   => '', // Vista actual, sin link
                'icon'  => '',
            ];
        }

        return null;
    }

    /**
     * Obtiene informacion de un modulo
     *
     * @param string $slug_modulo Slug del modulo
     * @return array|null
     */
    private function get_module_info(string $slug_modulo): ?array {
        // Mapeo de slugs a nombres e iconos
        $modulos = [
            'eventos'               => ['nombre' => __('Eventos', 'flavor-chat-ia'), 'icono' => 'dashicons-calendar-alt'],
            'reservas'              => ['nombre' => __('Reservas', 'flavor-chat-ia'), 'icono' => 'dashicons-calendar'],
            'espacios-comunes'      => ['nombre' => __('Espacios Comunes', 'flavor-chat-ia'), 'icono' => 'dashicons-admin-home'],
            'incidencias'           => ['nombre' => __('Incidencias', 'flavor-chat-ia'), 'icono' => 'dashicons-warning'],
            'fichaje'               => ['nombre' => __('Fichaje', 'flavor-chat-ia'), 'icono' => 'dashicons-clock'],
            'fichaje-empleados'     => ['nombre' => __('Fichaje', 'flavor-chat-ia'), 'icono' => 'dashicons-clock'],
            'tramites'              => ['nombre' => __('Tramites', 'flavor-chat-ia'), 'icono' => 'dashicons-clipboard'],
            'marketplace'           => ['nombre' => __('Marketplace', 'flavor-chat-ia'), 'icono' => 'dashicons-cart'],
            'banco-tiempo'          => ['nombre' => __('Banco de Tiempo', 'flavor-chat-ia'), 'icono' => 'dashicons-backup'],
            'grupos-consumo'        => ['nombre' => __('Grupos de Consumo', 'flavor-chat-ia'), 'icono' => 'dashicons-store'],
            'cursos'                => ['nombre' => __('Cursos', 'flavor-chat-ia'), 'icono' => 'dashicons-welcome-learn-more'],
            'talleres'              => ['nombre' => __('Talleres', 'flavor-chat-ia'), 'icono' => 'dashicons-hammer'],
            'biblioteca'            => ['nombre' => __('Biblioteca', 'flavor-chat-ia'), 'icono' => 'dashicons-book'],
            'foros'                 => ['nombre' => __('Foros', 'flavor-chat-ia'), 'icono' => 'dashicons-format-chat'],
            'podcast'               => ['nombre' => __('Podcast', 'flavor-chat-ia'), 'icono' => 'dashicons-microphone'],
            'huertos-urbanos'       => ['nombre' => __('Huertos Urbanos', 'flavor-chat-ia'), 'icono' => 'dashicons-carrot'],
            'reciclaje'             => ['nombre' => __('Reciclaje', 'flavor-chat-ia'), 'icono' => 'dashicons-update'],
            'bicicletas-compartidas' => ['nombre' => __('Bicicletas', 'flavor-chat-ia'), 'icono' => 'dashicons-admin-site'],
            'carpooling'            => ['nombre' => __('Carpooling', 'flavor-chat-ia'), 'icono' => 'dashicons-car'],
            'participacion'         => ['nombre' => __('Participacion', 'flavor-chat-ia'), 'icono' => 'dashicons-megaphone'],
            'comunidades'           => ['nombre' => __('Comunidades', 'flavor-chat-ia'), 'icono' => 'dashicons-groups'],
            'socios'                => ['nombre' => __('Socios', 'flavor-chat-ia'), 'icono' => 'dashicons-id-alt'],
            'advertising'           => ['nombre' => __('Publicidad', 'flavor-chat-ia'), 'icono' => 'dashicons-megaphone'],
        ];

        return $modulos[$slug_modulo] ?? null;
    }

    /**
     * Construye items para paginas normales de WordPress
     *
     * @return void
     */
    private function build_wordpress_items(): void {
        global $post;

        // Si es una pagina con padre
        if (is_page() && $post && $post->post_parent) {
            $ancestros = get_post_ancestors($post->ID);
            $ancestros = array_reverse($ancestros);

            foreach ($ancestros as $ancestro_id) {
                $ancestro = get_post($ancestro_id);
                if ($ancestro) {
                    $this->items[] = [
                        'label' => $ancestro->post_title,
                        'url'   => get_permalink($ancestro_id),
                        'type'  => 'page',
                    ];
                }
            }
        }

        // Pagina o entrada actual
        if (is_singular() && $post) {
            $this->items[] = [
                'label' => $post->post_title,
                'url'   => '',
                'type'  => 'current',
            ];
        }

        // Archivo (categoria, tag, etc.)
        if (is_archive()) {
            $this->items[] = [
                'label' => get_the_archive_title(),
                'url'   => '',
                'type'  => 'archive',
            ];
        }

        // Busqueda
        if (is_search()) {
            $this->items[] = [
                'label' => sprintf(__('Busqueda: %s', 'flavor-chat-ia'), get_search_query()),
                'url'   => '',
                'type'  => 'search',
            ];
        }

        // 404
        if (is_404()) {
            $this->items[] = [
                'label' => __('Pagina no encontrada', 'flavor-chat-ia'),
                'url'   => '',
                'type'  => '404',
            ];
        }
    }

    /**
     * Trunca items si hay demasiados
     *
     * @return void
     */
    private function truncate_items(): void {
        $total = count($this->items);
        $max = $this->config['max_items'];

        if ($total <= $max) {
            return;
        }

        // Mantener primero, ultimo y agregar "..." en medio
        $primero = array_shift($this->items);
        $ultimo = array_pop($this->items);

        // Tomar solo los ultimos items antes del actual
        $items_medio = array_slice($this->items, -($max - 3));

        // Reconstruir
        $this->items = [$primero];
        $this->items[] = [
            'label'      => '...',
            'url'        => '',
            'type'       => 'ellipsis',
            'is_ellipsis' => true,
        ];
        $this->items = array_merge($this->items, $items_medio);
        $this->items[] = $ultimo;
    }

    /**
     * Genera el HTML del breadcrumb
     *
     * @param array $args Argumentos
     * @return string HTML
     */
    private function generate_html(array $args): string {
        $clases = ['fl-breadcrumbs'];
        if (!empty($args['extra_class'])) {
            $clases[] = $args['extra_class'];
        }

        $html = '<nav class="' . esc_attr(implode(' ', $clases)) . '" aria-label="' . esc_attr__('Navegacion de migas de pan', 'flavor-chat-ia') . '">';

        // Lista con schema.org
        $html .= '<ol class="fl-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">';

        $posicion = 1;
        $total_items = count($this->items);

        foreach ($this->items as $indice => $item) {
            $es_ultimo = ($indice === $total_items - 1);
            $es_actual = !empty($item['is_current']);
            $es_ellipsis = !empty($item['is_ellipsis']);

            $item_classes = ['fl-breadcrumbs__item'];
            if ($es_actual) {
                $item_classes[] = 'fl-breadcrumbs__item--current';
            }
            if ($es_ellipsis) {
                $item_classes[] = 'fl-breadcrumbs__item--ellipsis';
            }

            $html .= '<li class="' . esc_attr(implode(' ', $item_classes)) . '"';

            if (!$es_ellipsis) {
                $html .= ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"';
            }

            $html .= '>';

            // Contenido del item
            if ($es_ellipsis) {
                $html .= '<span class="fl-breadcrumbs__ellipsis" aria-hidden="true">' . esc_html($item['label']) . '</span>';
            } elseif (!empty($item['url']) && !$es_actual) {
                $html .= '<a href="' . esc_url($item['url']) . '" class="fl-breadcrumbs__link" itemprop="item">';

                if (!empty($item['icon'])) {
                    $html .= '<span class="fl-breadcrumbs__icon dashicons ' . esc_attr($item['icon']) . '" aria-hidden="true"></span>';
                }

                $html .= '<span itemprop="name">' . esc_html($item['label']) . '</span>';
                $html .= '</a>';
                $html .= '<meta itemprop="position" content="' . $posicion . '">';
            } else {
                $html .= '<span class="fl-breadcrumbs__current" aria-current="page">';

                if (!empty($item['icon'])) {
                    $html .= '<span class="fl-breadcrumbs__icon dashicons ' . esc_attr($item['icon']) . '" aria-hidden="true"></span>';
                }

                $html .= '<span itemprop="name">' . esc_html($item['label']) . '</span>';
                $html .= '</span>';
                $html .= '<meta itemprop="position" content="' . $posicion . '">';
            }

            // Separador (excepto en el ultimo)
            if (!$es_ultimo) {
                $html .= $this->separator;
            }

            $html .= '</li>';

            if (!$es_ellipsis) {
                $posicion++;
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Agrega un item al breadcrumb manualmente
     *
     * @param string $label Etiqueta
     * @param string $url URL (vacio para actual)
     * @param string $icon Icono dashicons
     * @return self
     */
    public function add_item(string $label, string $url = '', string $icon = ''): self {
        $this->items[] = [
            'label' => $label,
            'url'   => $url,
            'icon'  => $icon,
            'type'  => 'custom',
        ];

        return $this;
    }

    /**
     * Limpia los items del breadcrumb
     *
     * @return self
     */
    public function clear(): self {
        $this->items = [];
        return $this;
    }

    /**
     * Configura una opcion
     *
     * @param string $key Clave
     * @param mixed $value Valor
     * @return self
     */
    public function set_option(string $key, $value): self {
        $this->config[$key] = $value;
        return $this;
    }
}

/**
 * Funcion helper para obtener la instancia
 *
 * @return Flavor_Dashboard_Breadcrumbs
 */
function flavor_breadcrumbs(): Flavor_Dashboard_Breadcrumbs {
    return Flavor_Dashboard_Breadcrumbs::get_instance();
}

/**
 * Funcion helper para renderizar breadcrumbs
 *
 * @param array $args Argumentos opcionales
 * @return void
 */
function flavor_the_breadcrumbs(array $args = []): void {
    Flavor_Dashboard_Breadcrumbs::render($args);
}
