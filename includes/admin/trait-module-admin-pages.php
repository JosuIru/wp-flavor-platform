<?php
/**
 * Trait para registrar páginas de admin en el Panel Unificado
 *
 * Los módulos pueden usar este trait para registrar fácilmente
 * sus páginas de administración en el panel de gestión unificado.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

trait Flavor_Module_Admin_Pages_Trait {

    /**
     * Mapping de IDs de módulo a sus páginas de dashboard principal
     * Permite a otros sistemas saber cuál es la página de entrada de cada módulo
     *
     * @var array
     */
    protected static $module_dashboard_pages = [
        'grupos_consumo' => 'gc-dashboard',
        'eventos' => 'eventos-dashboard',
        'presupuestos_participativos' => 'pp-dashboard',
        'mapa_actores' => 'actores-dashboard',
        'incidencias' => 'incidencias-dashboard',
        'foros' => 'foros-dashboard',
        'participacion' => 'participacion-dashboard',
        'tramites' => 'tramites-dashboard',
        'socios' => 'socios-dashboard',
        'reservas' => 'reservas-dashboard',
        'facturas' => 'facturas-dashboard',
        'clientes' => 'clientes-dashboard',
        'reciclaje' => 'reciclaje-dashboard',
        'huertos_urbanos' => 'huertos-dashboard',
        'banco_tiempo' => 'banco-tiempo-dashboard',
        'ayuda_vecinal' => 'ayuda-dashboard',
        'carpooling' => 'carpooling-dashboard',
        'bicicletas_compartidas' => 'bicicletas-dashboard',
        'compostaje' => 'compostaje-dashboard',
        'espacios_comunes' => 'espacios-dashboard',
        'marketplace' => 'marketplace-dashboard',
        'cursos' => 'cursos-dashboard',
        'talleres' => 'talleres-dashboard',
        'biblioteca' => 'biblioteca-dashboard',
        'multimedia' => 'multimedia-dashboard',
        'podcast' => 'podcast-dashboard',
        'radio' => 'radio-dashboard',
    ];

    /**
     * Obtiene el slug de la página dashboard de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string|null Slug de la página o null si no existe
     */
    public static function get_module_dashboard_page($module_id) {
        return self::$module_dashboard_pages[$module_id] ?? null;
    }

    /**
     * Obtiene todos los mappings de módulos a dashboards
     *
     * @return array
     */
    public static function get_all_module_dashboard_pages() {
        return self::$module_dashboard_pages;
    }

    /**
     * Registra las páginas de admin del módulo en el panel unificado
     * Llamar desde el método init() del módulo
     */
    protected function registrar_en_panel_unificado() {
        add_filter('flavor_admin_panel_modules', [$this, 'registrar_modulo_admin']);
    }

    /**
     * Callback del filtro para registrar el módulo
     * Los módulos deben implementar get_admin_config()
     *
     * @param array $modulos Módulos registrados
     * @return array
     */
    public function registrar_modulo_admin($modulos) {
        if (!method_exists($this, 'get_admin_config')) {
            return $modulos;
        }

        $config = $this->get_admin_config();
        if (!empty($config) && !empty($config['id'])) {
            $modulos[$config['id']] = $config;
        }

        return $modulos;
    }

    /**
     * Los módulos deben implementar este método para definir su configuración de admin
     *
     * Ejemplo de implementación:
     *
     * protected function get_admin_config() {
     *     return [
     *         'id' => 'mi_modulo',
     *         'label' => __('Mi Módulo', 'flavor-chat-ia'),
     *         'icon' => 'dashicons-admin-generic',
     *         'capability' => 'manage_options',
     *         'categoria' => 'operaciones', // personas|economia|operaciones|recursos|comunicacion|actividades|servicios|comunidad|sostenibilidad
     *         'paginas' => [
     *             [
     *                 'slug' => 'mi-modulo-dashboard',
     *                 'titulo' => __('Dashboard', 'flavor-chat-ia'),
     *                 'callback' => [$this, 'render_dashboard'],
     *                 'badge' => [$this, 'contar_pendientes'], // opcional
     *             ],
     *             [
     *                 'slug' => 'mi-modulo-listado',
     *                 'titulo' => __('Listado', 'flavor-chat-ia'),
     *                 'callback' => [$this, 'render_listado'],
     *             ],
     *         ],
     *         'dashboard_widget' => [$this, 'render_widget'], // opcional
     *         'estadisticas' => [$this, 'get_estadisticas'], // opcional
     *     ];
     * }
     *
     * @return array Configuración del módulo
     */
    // abstract protected function get_admin_config();

    /**
     * Helper: Genera la URL de una página de admin del módulo
     *
     * @param string $slug Slug de la página
     * @return string URL completa
     */
    protected function admin_page_url($slug) {
        return admin_url('admin.php?page=' . $slug);
    }

    /**
     * Helper: Verifica si estamos en una página de admin del módulo
     *
     * @param string|array $slugs Slug(s) a verificar
     * @return bool
     */
    protected function is_admin_page($slugs = null) {
        if (!is_admin()) {
            return false;
        }

        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if ($slugs === null) {
            // Verificar cualquier página del módulo
            $config = method_exists($this, 'get_admin_config') ? $this->get_admin_config() : [];
            if (empty($config['paginas'])) {
                return false;
            }
            foreach ($config['paginas'] as $pagina) {
                if ($current_page === $pagina['slug']) {
                    return true;
                }
            }
            return false;
        }

        $slugs = (array) $slugs;
        return in_array($current_page, $slugs, true);
    }

    /**
     * Helper: Renderiza el header estándar de una página de módulo con breadcrumbs
     *
     * @param string $titulo Título de la página
     * @param array $acciones Botones de acción [['label' => '', 'url' => '', 'class' => '']]
     */
    protected function render_page_header($titulo, $acciones = []) {
        $config = method_exists($this, 'get_admin_config') ? $this->get_admin_config() : [];
        $icon = $config['icon'] ?? 'dashicons-admin-generic';
        $module_label = $config['label'] ?? '';

        // Determinar la página principal del módulo para el breadcrumb
        $main_page_url = '';
        if (!empty($config['paginas']) && is_array($config['paginas'])) {
            $main_page_url = admin_url('admin.php?page=' . $config['paginas'][0]['slug']);
        }

        // Verificar si estamos en una subpágina (no el dashboard principal)
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $is_subpage = !empty($config['paginas']) && $current_page !== $config['paginas'][0]['slug'];
        ?>

        <?php if ($is_subpage && !empty($main_page_url) && !empty($module_label)): ?>
        <!-- Migas de pan -->
        <nav class="flavor-breadcrumbs" style="margin-bottom: 15px; font-size: 13px;">
            <a href="<?php echo esc_url($main_page_url); ?>" style="color: #2271b1; text-decoration: none;">
                <span class="dashicons <?php echo esc_attr($icon); ?>" style="font-size: 14px; vertical-align: middle;"></span>
                <?php echo esc_html($module_label); ?>
            </a>
            <span style="color: #646970; margin: 0 5px;">›</span>
            <span style="color: #1d2327;"><?php echo esc_html($titulo); ?></span>
        </nav>
        <?php endif; ?>

        <div class="page-header">
            <h1>
                <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                <?php echo esc_html($titulo); ?>
            </h1>
            <?php if (!empty($acciones)): ?>
                <div class="page-actions">
                    <?php foreach ($acciones as $accion): ?>
                        <a href="<?php echo esc_url($accion['url']); ?>"
                           class="button <?php echo esc_attr($accion['class'] ?? ''); ?>">
                            <?php echo esc_html($accion['label']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Helper: Renderiza tabs de navegación dentro de una página
     *
     * @param array $tabs [['slug' => '', 'label' => '', 'badge' => 0]]
     * @param string $current Tab actual
     */
    protected function render_page_tabs($tabs, $current) {
        echo '<nav class="nav-tab-wrapper wp-clearfix">';
        foreach ($tabs as $tab) {
            $class = 'nav-tab';
            if ($tab['slug'] === $current) {
                $class .= ' nav-tab-active';
            }
            $url = add_query_arg('tab', $tab['slug']);
            printf(
                '<a href="%s" class="%s">%s%s</a>',
                esc_url($url),
                esc_attr($class),
                esc_html($tab['label']),
                !empty($tab['badge']) ? sprintf(' <span class="count">(%d)</span>', intval($tab['badge'])) : ''
            );
        }
        echo '</nav>';
    }
}
