<?php
/**
 * Interfaz para Widgets del Dashboard Unificado
 *
 * Define el contrato que deben cumplir los widgets de modulos
 * para integrarse en el Dashboard Unificado.
 *
 * @package FlavorChatIA
 * @subpackage Dashboard
 * @since 4.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interfaz que deben implementar todos los widgets del dashboard
 *
 * @since 4.0.0
 */
interface Flavor_Dashboard_Widget_Interface {

    /**
     * Obtiene el identificador unico del widget
     *
     * @return string ID del widget (ej: 'eventos', 'fichaje', 'reservas')
     */
    public function get_widget_id(): string;

    /**
     * Obtiene la configuracion del widget
     *
     * @return array {
     *     Configuracion del widget
     *
     *     @type string $title       Titulo visible del widget
     *     @type string $icon        Clase dashicons (ej: 'dashicons-calendar')
     *     @type string $size        Tamano: 'small', 'medium', 'large'
     *     @type string $category    Categoria: 'gestion', 'comunicacion', 'economia', 'comunidad', 'sistema'
     *     @type int    $priority    Orden de aparicion (menor = primero)
     *     @type bool   $refreshable Si soporta actualizacion AJAX
     *     @type int    $cache_time  Segundos de cache (0 = sin cache)
     *     @type array  $actions     Acciones rapidas del header del widget
     * }
     */
    public function get_widget_config(): array;

    /**
     * Obtiene los datos del widget para mostrar
     *
     * @return array {
     *     Datos del widget
     *
     *     @type array $stats        Estadisticas principales [{icon, valor, label, color, url}]
     *     @type array $items        Lista de elementos a mostrar
     *     @type array $chart_data   Datos para graficos (opcional)
     *     @type array $actions      Acciones rapidas
     *     @type string $empty_state Mensaje cuando no hay datos
     *     @type array $footer       Contenido del footer [{label, url, icon}]
     * }
     */
    public function get_widget_data(): array;

    /**
     * Renderiza el contenido HTML del widget
     *
     * @return void Imprime el HTML directamente
     */
    public function render_widget(): void;
}

/**
 * Constantes de nivel de widget
 *
 * Los niveles determinan la prominencia visual del widget:
 * - FEATURED (1): Grande, prominente, ocupa mas espacio, ideal para KPIs principales
 * - STANDARD (2): Tamano normal, comportamiento por defecto
 * - COMPACT (3): Pequeno, sin header completo, para info secundaria
 *
 * @since 4.1.0
 */
const FLAVOR_WIDGET_LEVEL_FEATURED = 1;
const FLAVOR_WIDGET_LEVEL_STANDARD = 2;
const FLAVOR_WIDGET_LEVEL_COMPACT = 3;

/**
 * Clase base abstracta para widgets del dashboard
 *
 * Proporciona implementacion base comun para todos los widgets.
 * Los modulos pueden extender esta clase en lugar de implementar
 * la interfaz directamente.
 *
 * @since 4.0.0
 */
abstract class Flavor_Dashboard_Widget_Base implements Flavor_Dashboard_Widget_Interface {

    /**
     * ID del widget
     *
     * @var string
     */
    protected $widget_id;

    /**
     * Titulo del widget
     *
     * @var string
     */
    protected $title;

    /**
     * Icono del widget
     *
     * @var string
     */
    protected $icon = 'dashicons-admin-generic';

    /**
     * Tamano del widget
     *
     * @var string
     */
    protected $size = 'medium';

    /**
     * Categoria del widget
     *
     * @var string
     */
    protected $category = 'gestion';

    /**
     * Prioridad de orden
     *
     * @var int
     */
    protected $priority = 50;

    /**
     * Si el widget puede refrescarse via AJAX
     *
     * @var bool
     */
    protected $refreshable = true;

    /**
     * Tiempo de cache en segundos
     *
     * @var int
     */
    protected $cache_time = 300;

    /**
     * Referencia al modulo propietario
     *
     * @var object|null
     */
    protected $module = null;

    /**
     * Nivel de prominencia del widget
     *
     * 1 = Featured (destacado, grande)
     * 2 = Standard (normal, por defecto)
     * 3 = Compact (compacto, pequeño)
     *
     * @var int
     * @since 4.1.0
     */
    protected $level = FLAVOR_WIDGET_LEVEL_STANDARD;

    /**
     * Descripcion corta del widget para accesibilidad
     *
     * @var string
     * @since 4.1.0
     */
    protected $description = '';

    /**
     * Constructor
     *
     * @param array $config Configuracion inicial
     */
    public function __construct(array $config = []) {
        if (!empty($config['id'])) {
            $this->widget_id = sanitize_key($config['id']);
        }
        if (!empty($config['title'])) {
            $this->title = sanitize_text_field($config['title']);
        }
        if (!empty($config['icon'])) {
            $this->icon = sanitize_html_class($config['icon']);
        }
        if (!empty($config['size']) && in_array($config['size'], ['small', 'medium', 'large'], true)) {
            $this->size = $config['size'];
        }
        if (!empty($config['category'])) {
            $this->category = sanitize_key($config['category']);
        }
        if (isset($config['priority'])) {
            $this->priority = absint($config['priority']);
        }
        if (isset($config['refreshable'])) {
            $this->refreshable = (bool) $config['refreshable'];
        }
        if (isset($config['cache_time'])) {
            $this->cache_time = absint($config['cache_time']);
        }
        if (!empty($config['module'])) {
            $this->module = $config['module'];
        }
        // Nuevas propiedades v4.1.0
        if (isset($config['level'])) {
            $nivel_valido = absint($config['level']);
            if ($nivel_valido >= FLAVOR_WIDGET_LEVEL_FEATURED && $nivel_valido <= FLAVOR_WIDGET_LEVEL_COMPACT) {
                $this->level = $nivel_valido;
            }
        }
        if (!empty($config['description'])) {
            $this->description = sanitize_text_field($config['description']);
        }
    }

    /**
     * Obtiene el ID del widget
     *
     * @return string
     */
    public function get_widget_id(): string {
        return $this->widget_id ?? '';
    }

    /**
     * Obtiene la configuracion del widget
     *
     * @return array
     */
    public function get_widget_config(): array {
        $module_id = '';
        if (is_object($this->module)) {
            if (method_exists($this->module, 'get_id')) {
                $module_id = (string) $this->module->get_id();
            } elseif (method_exists($this->module, 'get_module_id')) {
                $module_id = (string) $this->module->get_module_id();
            }
        } elseif (is_string($this->module)) {
            $module_id = $this->module;
        }

        return [
            'id'          => $this->widget_id,
            'title'       => $this->title,
            'icon'        => $this->icon,
            'size'        => $this->size,
            'category'    => $this->category,
            'priority'    => $this->priority,
            'refreshable' => $this->refreshable,
            'cache_time'  => $this->cache_time,
            'module'      => $module_id,
            'actions'     => $this->get_header_actions(),
            'level'       => $this->level,
            'description' => $this->description,
            'level_class' => $this->get_level_class(),
        ];
    }

    /**
     * Obtiene la clase CSS segun el nivel del widget
     *
     * @return string
     * @since 4.1.0
     */
    protected function get_level_class(): string {
        $clases_nivel = [
            FLAVOR_WIDGET_LEVEL_FEATURED => 'fl-widget--featured',
            FLAVOR_WIDGET_LEVEL_STANDARD => 'fl-widget--standard',
            FLAVOR_WIDGET_LEVEL_COMPACT  => 'fl-widget--compact',
        ];

        return $clases_nivel[$this->level] ?? $clases_nivel[FLAVOR_WIDGET_LEVEL_STANDARD];
    }

    /**
     * Establece el nivel del widget
     *
     * @param int $level Nivel (1=Featured, 2=Standard, 3=Compact)
     * @return self
     * @since 4.1.0
     */
    public function set_level(int $level): self {
        if ($level >= FLAVOR_WIDGET_LEVEL_FEATURED && $level <= FLAVOR_WIDGET_LEVEL_COMPACT) {
            $this->level = $level;
        }
        return $this;
    }

    /**
     * Obtiene el nivel actual del widget
     *
     * @return int
     * @since 4.1.0
     */
    public function get_level(): int {
        return $this->level;
    }

    /**
     * Obtiene acciones del header del widget
     *
     * @return array
     */
    protected function get_header_actions(): array {
        $actions = [];

        // Acción "Ver más" que lleva a la página dinámica del módulo
        $module_url = $this->get_module_url();
        if ($module_url) {
            $actions[] = [
                'id'    => 'view-more',
                'icon'  => 'dashicons-external',
                'title' => __('Ver más', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type'  => 'link',
                'url'   => $module_url,
            ];
        }

        if ($this->refreshable) {
            $actions[] = [
                'id'    => 'refresh',
                'icon'  => 'dashicons-update',
                'title' => __('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'type'  => 'refresh',
            ];
        }

        return $actions;
    }

    /**
     * Obtiene la URL de la página dinámica del módulo
     *
     * En contexto admin, devuelve la URL de la página de administración.
     * En contexto frontend, devuelve la URL del portal del usuario.
     *
     * @return string URL del módulo o vacío si no aplica
     */
    protected function get_module_url(): string {
        if (empty($this->widget_id)) {
            return '';
        }

        // En contexto admin, usar URL de administración
        if (is_admin() && !wp_doing_ajax()) {
            return $this->get_admin_url();
        }

        // En contexto frontend, usar URL del portal
        return Flavor_Chat_Helpers::get_action_url(str_replace('-', '_', $this->widget_id), '');
    }

    /**
     * Obtiene la URL de la página de administración del módulo
     *
     * Los widgets pueden sobrescribir este método para usar una URL personalizada.
     * Por defecto intenta usar 'flavor-{widget_id}' como slug de página.
     *
     * Soporta el prefijo especial '_frontend_:' para módulos que no tienen
     * página de admin y deben apuntar al portal del usuario.
     *
     * @return string URL de admin del módulo
     * @since 4.2.0
     */
    protected function get_admin_url(): string {
        $admin_page_slug = $this->get_admin_page_slug();
        $module_id = sanitize_key(str_replace('-', '_', (string) $this->widget_id));
        $canonical_admin_url = class_exists('Flavor_Module_Admin_Pages_Trait')
            ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($module_id)
            : null;

        if (empty($admin_page_slug)) {
            // Fallback a la URL de frontend si no hay página de admin
            return $canonical_admin_url ?: Flavor_Chat_Helpers::get_action_url(str_replace('-', '_', $this->widget_id), '');
        }

        // Si el slug comienza con '_frontend_:', es una URL de frontend
        if (strpos($admin_page_slug, '_frontend_:') === 0) {
            $ruta_frontend = substr($admin_page_slug, strlen('_frontend_:'));
            return home_url($ruta_frontend);
        }

        return $canonical_admin_url ?: admin_url('admin.php?page=' . $admin_page_slug);
    }

    /**
     * Obtiene el slug de la página de administración del módulo
     *
     * Los widgets pueden sobrescribir este método para devolver
     * un slug personalizado si no sigue el patrón 'flavor-{widget_id}'.
     *
     * @return string Slug de la página de admin (ej: 'flavor-parkings-reservas')
     * @since 4.2.0
     */
    protected function get_admin_page_slug(): string {
        // Mapeo de widget_id a slug de página de admin
        $mapeo_paginas_admin = $this->get_admin_pages_mapping();

        if (isset($mapeo_paginas_admin[$this->widget_id])) {
            return $mapeo_paginas_admin[$this->widget_id];
        }

        // Slug por defecto: flavor-{widget_id}
        return 'flavor-' . $this->widget_id;
    }

    /**
     * Obtiene el mapeo de widget_id a slug de página de admin
     *
     * Este mapeo permite que los widgets apunten a sus páginas de admin
     * correctas cuando el slug no sigue el patrón por defecto.
     *
     * @return array Mapeo ['widget_id' => 'admin-page-slug']
     * @since 4.2.0
     */
    protected function get_admin_pages_mapping(): array {
        $mapeo = [
            // ═══════════════════════════════════════════════════════════════
            // Módulos con dashboards de admin completos
            // ═══════════════════════════════════════════════════════════════
            'banco-tiempo'      => 'banco-tiempo',
            'comunidades'       => 'comunidades',
            'colectivos'        => 'colectivos',
            'eventos'           => 'eventos',
            'cursos'            => 'cursos',
            'marketplace'       => 'marketplace',
            'talleres'          => 'talleres',
            'reservas'          => 'reservas',
            'socios'            => 'socios',
            'incidencias'       => 'incidencias',
            'foros'             => 'foros',
            'podcast'           => 'podcast',
            'multimedia'        => 'multimedia',
            'red-social'        => 'red-social',
            'ayuda-vecinal'     => 'ayuda-vecinal',
            'espacios-comunes'  => 'espacios-comunes',
            'huertos-urbanos'   => 'huertos-urbanos',
            'participacion'     => 'participacion',
            'presupuestos-participativos' => 'presupuestos-participativos',
            'grupos-consumo'    => 'grupos-consumo',

            // ═══════════════════════════════════════════════════════════════
            // Módulos con formatos específicos de admin
            // ═══════════════════════════════════════════════════════════════
            'carpooling'            => 'flavor-carpooling-viajes',
            'parkings'              => 'flavor-parkings-reservas',
            'bicicletas'            => 'flavor-bicicletas-prestamos',
            'bicicletas-compartidas' => 'flavor-bicicletas-prestamos',
            'compostaje'            => 'flavor-compostaje-composteras',
            'reciclaje'             => 'flavor-reciclaje-puntos',
            'biblioteca'            => 'flavor-chat-biblioteca',
            'radio'                 => 'flavor-radio',
            'email-marketing'       => 'flavor-email-marketing',
            'newsletter'            => 'flavor-newsletter',
            'advertising'           => 'flavor-advertising-dashboard',
            'empresarial'           => 'flavor-empresarial-empresas',
            'tramites'              => 'flavor-tramites-solicitudes',
            'sello-conciencia'      => 'sello-conciencia',
            'network'               => 'flavor-platform-network',
            'economia-circular'     => 'flavor-ec-settings',

            // ═══════════════════════════════════════════════════════════════
            // Módulos con frontend como destino principal (sin admin dedicado)
            // Apuntan al portal del usuario en frontend
            // ═══════════════════════════════════════════════════════════════
            'economia-don'          => '_frontend_:/mi-portal/economia-don/',
            'economia-suficiencia'  => '_frontend_:/mi-portal/economia-suficiencia/',
            'energia-comunitaria'   => '_frontend_:/mi-portal/energia-comunitaria/',
            'saberes-ancestrales'   => '_frontend_:/mi-portal/saberes-ancestrales/',
            'justicia-restaurativa' => '_frontend_:/mi-portal/justicia-restaurativa/',
            'huella-ecologica'      => '_frontend_:/mi-portal/huella-ecologica/',
            'circulos-cuidados'     => '_frontend_:/mi-portal/circulos-cuidados/',
            'biodiversidad-local'   => '_frontend_:/mi-portal/biodiversidad-local/',
            'trabajo-digno'         => '_frontend_:/mi-portal/trabajo-digno/',
            'avisos-municipales'    => '_frontend_:/mi-portal/avisos-municipales/',
            'bares'                 => '_frontend_:/mi-portal/bares/',
            'chat-grupos'           => '_frontend_:/mi-portal/chat-grupos/',
            'chat-interno'          => '_frontend_:/mi-portal/chat-interno/',
            'fichaje-empleados'     => '_frontend_:/mi-portal/fichaje/',
            'facturas'              => '_frontend_:/mi-portal/facturas/',
            'clientes'              => '_frontend_:/mi-portal/clientes/',
            'woocommerce'           => '_frontend_:/mi-portal/tienda/',
            'transparencia'         => '_frontend_:/mi-portal/transparencia/',
            'campanias'             => '_frontend_:/mi-portal/campanias/',
            'documentacion-legal'   => '_frontend_:/mi-portal/documentacion-legal/',
            'seguimiento-denuncias' => '_frontend_:/mi-portal/seguimiento-denuncias/',
            'mapa-actores'          => '_frontend_:/mi-portal/mapa-actores/',
            'recetas'               => '_frontend_:/mi-portal/recetas/',
        ];

        /**
         * Filtro para modificar el mapeo de páginas de admin
         *
         * @param array $mapeo Mapeo actual
         * @since 4.2.0
         */
        return apply_filters('flavor_dashboard_admin_pages_mapping', $mapeo);
    }

    /**
     * Genera una URL sensible al contexto (admin vs frontend)
     *
     * Método helper para que los widgets generen URLs que apunten
     * a la ubicación correcta según el contexto actual.
     *
     * Si el módulo está mapeado a frontend (prefijo '_frontend_:'), siempre
     * devuelve la URL de frontend incluso en contexto admin.
     *
     * @param string $ruta_frontend Ruta del frontend (ej: '/mi-portal/modulo/accion/')
     * @param string $pagina_admin  Slug de página admin (ej: 'flavor-modulo-accion')
     * @return string URL completa según el contexto
     * @since 4.2.0
     */
    protected function get_context_url(string $ruta_frontend, string $pagina_admin = ''): string {
        $module_id = sanitize_key(str_replace('-', '_', (string) $this->widget_id));
        $canonical_admin_url = class_exists('Flavor_Module_Admin_Pages_Trait')
            ? Flavor_Module_Admin_Pages_Helper::get_module_dashboard_url($module_id)
            : null;

        // Consultar el mapeo para obtener el slug correcto
        $mapeo = $this->get_admin_pages_mapping();

        // Convertir slug de admin a formato de widget_id para buscar en mapeo
        // Por ejemplo: 'flavor-economia-don' -> 'economia-don'
        $widget_id_from_slug = preg_replace('/^flavor-/', '', $pagina_admin);

        // Verificar si hay un mapeo específico para este módulo
        $slug_mapeado = $mapeo[$widget_id_from_slug] ?? $pagina_admin;

        // Si el mapeo indica frontend, usar URL de frontend
        if (strpos($slug_mapeado, '_frontend_:') === 0) {
            $ruta_mapeada = substr($slug_mapeado, strlen('_frontend_:'));
            return home_url($ruta_mapeada);
        }

        // Detectar contexto
        if (is_admin() && !wp_doing_ajax()) {
            // En admin: usar slug mapeado
            if (!empty($canonical_admin_url)) {
                return $canonical_admin_url;
            }

            if (!empty($slug_mapeado)) {
                return admin_url('admin.php?page=' . $slug_mapeado);
            }
        }

        // En frontend o sin página admin: usar URL de frontend
        return home_url($ruta_frontend);
    }

    /**
     * Verifica si estamos en contexto de administración
     *
     * @return bool True si estamos en admin (excluyendo AJAX)
     * @since 4.2.0
     */
    protected function is_admin_context(): bool {
        return is_admin() && !wp_doing_ajax();
    }

    /**
     * Obtiene datos del widget (implementacion base)
     *
     * Los widgets concretos deben sobrescribir este metodo
     *
     * @return array
     */
    public function get_widget_data(): array {
        return [
            'stats'       => [],
            'items'       => [],
            'actions'     => [],
            'empty_state' => __('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'footer'      => $this->get_default_footer(),
        ];
    }

    /**
     * Obtiene el footer por defecto con enlace a la página del módulo
     *
     * @return array
     */
    protected function get_default_footer(): array {
        $module_url = $this->get_module_url();

        if (empty($module_url)) {
            return [];
        }

        return [
            [
                'label' => __('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'url'   => $module_url,
                'icon'  => 'dashicons-arrow-right-alt2',
            ],
        ];
    }

    /**
     * Renderiza el widget usando el renderer estandar
     *
     * @return void
     */
    public function render_widget(): void {
        $data = $this->get_widget_data();
        $this->render_widget_content($data);
    }

    /**
     * Renderiza el contenido del widget
     *
     * @param array $data Datos del widget
     * @return void
     */
    protected function render_widget_content(array $data): void {
        // Estadisticas principales
        if (!empty($data['stats'])) {
            $this->render_stats($data['stats']);
        }

        // Items/lista
        if (!empty($data['items'])) {
            $this->render_items($data['items']);
        } elseif (empty($data['stats'])) {
            $this->render_empty_state($data['empty_state'] ?? '');
        }

        // Footer con enlaces
        if (!empty($data['footer'])) {
            $this->render_footer($data['footer']);
        }
    }

    /**
     * Renderiza las estadisticas del widget
     *
     * @param array $stats Estadisticas
     * @return void
     */
    protected function render_stats(array $stats): void {
        echo '<div class="fud-widget-stats">';
        foreach ($stats as $stat) {
            $icon  = esc_attr($stat['icon'] ?? 'dashicons-chart-bar');
            $valor = esc_html($stat['valor'] ?? '0');
            $label = esc_html($stat['label'] ?? '');
            $color = esc_attr($stat['color'] ?? 'primary');
            $url   = !empty($stat['url']) ? esc_url($stat['url']) : '';

            $stat_html = sprintf(
                '<div class="fud-stat-item fud-stat--%s">
                    <span class="fud-stat-icon dashicons %s"></span>
                    <span class="fud-stat-value">%s</span>
                    <span class="fud-stat-label">%s</span>
                </div>',
                $color,
                $icon,
                $valor,
                $label
            );

            if ($url) {
                echo '<a href="' . $url . '" class="fud-stat-link">' . $stat_html . '</a>';
            } else {
                echo $stat_html;
            }
        }
        echo '</div>';
    }

    /**
     * Renderiza la lista de items del widget
     *
     * @param array $items Items
     * @return void
     */
    protected function render_items(array $items): void {
        echo '<ul class="fud-widget-items">';
        foreach (array_slice($items, 0, 5) as $item) {
            $icon  = esc_attr($item['icon'] ?? 'dashicons-marker');
            $title = esc_html($item['title'] ?? '');
            $meta  = esc_html($item['meta'] ?? '');
            $url   = !empty($item['url']) ? esc_url($item['url']) : '#';
            $badge = !empty($item['badge']) ? '<span class="fud-item-badge">' . esc_html($item['badge']) . '</span>' : '';

            printf(
                '<li class="fud-widget-item">
                    <a href="%s">
                        <span class="fud-item-icon dashicons %s"></span>
                        <span class="fud-item-content">
                            <span class="fud-item-title">%s</span>
                            <span class="fud-item-meta">%s</span>
                        </span>
                        %s
                    </a>
                </li>',
                $url,
                $icon,
                $title,
                $meta,
                $badge
            );
        }
        echo '</ul>';
    }

    /**
     * Renderiza estado vacio
     *
     * @param string $message Mensaje
     * @return void
     */
    protected function render_empty_state(string $message = ''): void {
        if (empty($message)) {
            $message = __('No hay datos disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
        printf(
            '<div class="fud-empty-state">
                <span class="dashicons dashicons-info"></span>
                <p>%s</p>
            </div>',
            esc_html($message)
        );
    }

    /**
     * Renderiza el footer del widget
     *
     * @param array $footer Datos del footer
     * @return void
     */
    protected function render_footer(array $footer): void {
        echo '<div class="fud-widget-footer">';
        foreach ($footer as $link) {
            $label = esc_html($link['label'] ?? __('Ver todo', FLAVOR_PLATFORM_TEXT_DOMAIN));
            $url   = esc_url($link['url'] ?? '#');
            $icon  = esc_attr($link['icon'] ?? 'dashicons-arrow-right-alt2');

            printf(
                '<a href="%s" class="fud-footer-link">
                    %s
                    <span class="dashicons %s"></span>
                </a>',
                $url,
                $label,
                $icon
            );
        }
        echo '</div>';
    }

    /**
     * Obtiene datos cacheados o frescos
     *
     * @param callable $callback Funcion para obtener datos frescos
     * @return mixed
     */
    protected function get_cached_data(callable $callback) {
        if ($this->cache_time <= 0) {
            return $callback();
        }

        $cache_key = 'fud_widget_' . $this->widget_id . '_' . get_current_user_id();
        $cached    = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $data = $callback();
        set_transient($cache_key, $data, $this->cache_time);

        return $data;
    }

    /**
     * Limpia la cache del widget
     *
     * @return void
     */
    public function clear_cache(): void {
        $cache_key = 'fud_widget_' . $this->widget_id . '_' . get_current_user_id();
        delete_transient($cache_key);
    }
}

/**
 * Widget generico basado en callback
 *
 * Permite crear widgets de forma sencilla pasando configuracion y callbacks
 *
 * @since 4.0.0
 */
class Flavor_Module_Widget extends Flavor_Dashboard_Widget_Base {

    /**
     * Callback para obtener datos
     *
     * @var callable|null
     */
    private $data_callback = null;

    /**
     * Callback para renderizar
     *
     * @var callable|null
     */
    private $render_callback = null;

    /**
     * Datos estaticos del widget
     *
     * @var array
     */
    private $static_data = [];

    /**
     * Constructor
     *
     * @param array $config Configuracion del widget
     */
    public function __construct(array $config = []) {
        parent::__construct($config);

        if (!empty($config['data_callback']) && is_callable($config['data_callback'])) {
            $this->data_callback = $config['data_callback'];
        }

        if (!empty($config['render_callback']) && is_callable($config['render_callback'])) {
            $this->render_callback = $config['render_callback'];
        }

        if (!empty($config['stats'])) {
            $this->static_data['stats'] = $config['stats'];
        }

        if (!empty($config['actions'])) {
            $this->static_data['actions'] = $config['actions'];
        }

        if (!empty($config['footer'])) {
            $this->static_data['footer'] = $config['footer'];
        }

        if (!empty($config['empty_state'])) {
            $this->static_data['empty_state'] = $config['empty_state'];
        }
    }

    /**
     * Obtiene datos del widget
     *
     * @return array
     */
    public function get_widget_data(): array {
        // Si hay callback de datos, usarlo
        if ($this->data_callback !== null) {
            return $this->get_cached_data($this->data_callback);
        }

        // Si hay datos estaticos, devolverlos
        if (!empty($this->static_data)) {
            return array_merge(parent::get_widget_data(), $this->static_data);
        }

        return parent::get_widget_data();
    }

    /**
     * Renderiza el widget
     *
     * @return void
     */
    public function render_widget(): void {
        // Si hay callback de renderizado personalizado, usarlo
        if ($this->render_callback !== null) {
            call_user_func($this->render_callback, $this->get_widget_data(), $this);
            return;
        }

        // Usar renderizado estandar
        parent::render_widget();
    }
}
