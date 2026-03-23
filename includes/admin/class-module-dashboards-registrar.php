<?php
/**
 * Registrador Automático de Páginas de Dashboard de Módulos
 *
 * Detecta módulos activos que tienen vistas de admin (views/dashboard.php)
 * y los registra automáticamente como páginas de WordPress admin.
 *
 * Las páginas se registran de forma "hidden" (sin menú visible) porque
 * la navegación se gestiona a través del Flavor Shell.
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.3.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Module_Dashboards_Registrar {

    /**
     * Instancia singleton
     *
     * @var Flavor_Module_Dashboards_Registrar|null
     */
    private static $instance = null;

    /**
     * Cache de módulos con dashboards
     *
     * @var array
     */
    private $modules_with_dashboards = [];

    /**
     * Mapping de slug de página a configuración de módulo
     *
     * @var array
     */
    private $page_configs = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Module_Dashboards_Registrar
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
        // Detectar módulos con dashboards
        add_action('init', [$this, 'detectar_modulos_con_dashboards'], 15);

        // Registrar páginas de admin
        add_action('admin_menu', [$this, 'registrar_paginas_admin'], 50);

        // Cargar estilos para dashboards de módulos
        add_action('admin_enqueue_scripts', [$this, 'enqueue_dashboard_styles']);
    }

    /**
     * Detecta módulos activos que tienen vistas de dashboard
     */
    public function detectar_modulos_con_dashboards() {
        // Obtener módulos activos
        $modulos_activos = $this->get_active_modules();

        // Directorio base de módulos
        $modules_dir = FLAVOR_CHAT_IA_PATH . 'includes/modules/';

        foreach ($modulos_activos as $module_id) {
            // Normalizar ID (guiones bajos a guiones)
            $module_dir_name = str_replace('_', '-', $module_id);

            // Buscar vistas de dashboard
            $possible_paths = [
                $modules_dir . $module_dir_name . '/views/dashboard.php',
                $modules_dir . $module_id . '/views/dashboard.php',
            ];

            foreach ($possible_paths as $dashboard_path) {
                if (file_exists($dashboard_path)) {
                    $this->modules_with_dashboards[$module_id] = [
                        'module_id' => $module_id,
                        'dashboard_path' => $dashboard_path,
                        'views_dir' => dirname($dashboard_path),
                        'slug' => $this->get_dashboard_slug($module_id),
                        'title' => $this->get_module_title($module_id),
                        'icon' => $this->get_module_icon($module_id),
                    ];
                    break;
                }
            }
        }
    }

    /**
     * Obtiene el slug de dashboard para un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Slug de la página
     */
    private function get_dashboard_slug($module_id) {
        // Mapping de slugs canónico - sincronizado con trait-module-admin-pages.php
        static $slugs_mapping = null;

        if ($slugs_mapping === null) {
            $slugs_mapping = [
                // Administración
                'advertising' => 'advertising-dashboard',

                // Servicios públicos
                'avisos_municipales' => 'avisos-dashboard',
                'presupuestos_participativos' => 'presupuestos-dashboard',
                'transparencia' => 'transparencia-dashboard',
                'seguimiento_denuncias' => 'denuncias-dashboard',
                'documentacion_legal' => 'documentos-dashboard',
                'tramites' => 'tramites-dashboard',
                'incidencias' => 'incidencias-dashboard',
                'participacion' => 'participacion-dashboard',

                // Comunidad
                'socios' => 'socios-dashboard',
                'colectivos' => 'flavor-colectivos-dashboard',
                'comunidades' => 'comunidades-dashboard',
                'foros' => 'foros-dashboard',
                'red_social' => 'flavor-red-social-dashboard',
                'mapa_actores' => 'actores-dashboard',

                // Economía
                'grupos_consumo' => 'gc-dashboard',
                'marketplace' => 'marketplace-dashboard',
                'banco_tiempo' => 'banco-tiempo-dashboard',
                'economia_don' => 'economia-don-dashboard',
                'economia_suficiencia' => 'suficiencia-dashboard',

                // Actividades
                'eventos' => 'eventos-dashboard',
                'cursos' => 'cursos-dashboard',
                'talleres' => 'talleres-dashboard',
                'reservas' => 'reservas-dashboard',

                // Recursos
                'huertos_urbanos' => 'huertos-dashboard',
                'espacios_comunes' => 'espacios-dashboard',
                'biblioteca' => 'biblioteca-dashboard',
                'carpooling' => 'carpooling-dashboard',
                'fichaje_empleados' => 'fichaje-dashboard',
                'parkings' => 'parkings-dashboard',
                'bares' => 'bares-dashboard',
                'recetas' => 'recetas-dashboard',

                // Sostenibilidad
                'reciclaje' => 'reciclaje-dashboard',
                'compostaje' => 'compostaje-dashboard',
                'energia_comunitaria' => 'flavor-energia-dashboard',
                'bicicletas_compartidas' => 'bicicletas-dashboard',
                'biodiversidad_local' => 'biodiversidad-dashboard',
                'huella_ecologica' => 'huella-ecologica-dashboard',
                'saberes_ancestrales' => 'saberes-dashboard',
                'circulos_cuidados' => 'circulos-cuidados-dashboard',
                'trabajo_digno' => 'trabajo-digno-dashboard',
                'justicia_restaurativa' => 'justicia-restaurativa-dashboard',

                // Comunicación
                'multimedia' => 'multimedia-dashboard',
                'radio' => 'flavor-radio-dashboard',
                'podcast' => 'podcast-dashboard',
                'campanias' => 'campanias-dashboard',
                'email_marketing' => 'email-marketing-dashboard',
                'encuestas' => 'encuestas-dashboard',

                // Chat
                'chat_estados' => 'chat-estados-dashboard',
                'chat_grupos' => 'chat-grupos-dashboard',
                'chat_interno' => 'chat-interno-dashboard',

                // Negocios
                'clientes' => 'clientes-dashboard',
                'facturas' => 'facturas-dashboard',
                'contabilidad' => 'contabilidad-dashboard',
                'empresarial' => 'empresarial-dashboard',
                'woocommerce' => 'flavor-woocommerce-dashboard',
                'crowdfunding' => 'crowdfunding-dashboard',
                'themacle' => 'themacle-dashboard',
                'trading_ia' => 'trading-ia-dashboard',
                'dex_solana' => 'dex-solana-dashboard',

                // Social
                'ayuda_vecinal' => 'ayuda-dashboard',
                'sello_conciencia' => 'sello-conciencia-dashboard',

                // Especiales
                'kulturaka' => 'kulturaka-dashboard',
            ];
        }

        if (isset($slugs_mapping[$module_id])) {
            return $slugs_mapping[$module_id];
        }

        // Fallback: generar slug basado en el ID
        $normalized_id = str_replace('_', '-', $module_id);
        return $normalized_id . '-dashboard';
    }

    /**
     * Obtiene el título de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Título legible
     */
    private function get_module_title($module_id) {
        $titles = [
            'socios' => __('Gestión de Miembros', 'flavor-chat-ia'),
            'eventos' => __('Eventos', 'flavor-chat-ia'),
            'reservas' => __('Reservas', 'flavor-chat-ia'),
            'tramites' => __('Trámites', 'flavor-chat-ia'),
            'incidencias' => __('Incidencias', 'flavor-chat-ia'),
            'participacion' => __('Participación', 'flavor-chat-ia'),
            'foros' => __('Foros', 'flavor-chat-ia'),
            'comunidades' => __('Comunidades', 'flavor-chat-ia'),
            'colectivos' => __('Colectivos', 'flavor-chat-ia'),
            'marketplace' => __('Marketplace', 'flavor-chat-ia'),
            'banco_tiempo' => __('Banco de Tiempo', 'flavor-chat-ia'),
            'economia_don' => __('Economía del Don', 'flavor-chat-ia'),
            'grupos_consumo' => __('Grupos de Consumo', 'flavor-chat-ia'),
            'huertos_urbanos' => __('Huertos Urbanos', 'flavor-chat-ia'),
            'espacios_comunes' => __('Espacios Comunes', 'flavor-chat-ia'),
            'biblioteca' => __('Biblioteca', 'flavor-chat-ia'),
            'carpooling' => __('Carpooling', 'flavor-chat-ia'),
            'bicicletas_compartidas' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
            'reciclaje' => __('Reciclaje', 'flavor-chat-ia'),
            'compostaje' => __('Compostaje', 'flavor-chat-ia'),
            'energia_comunitaria' => __('Energía Comunitaria', 'flavor-chat-ia'),
            'radio' => __('Radio', 'flavor-chat-ia'),
            'podcast' => __('Podcast', 'flavor-chat-ia'),
            'multimedia' => __('Multimedia', 'flavor-chat-ia'),
            'chat_grupos' => __('Chat de Grupos', 'flavor-chat-ia'),
            'chat_interno' => __('Chat Interno', 'flavor-chat-ia'),
            'red_social' => __('Red Social', 'flavor-chat-ia'),
            'cursos' => __('Cursos', 'flavor-chat-ia'),
            'talleres' => __('Talleres', 'flavor-chat-ia'),
            'avisos_municipales' => __('Avisos Municipales', 'flavor-chat-ia'),
            'transparencia' => __('Transparencia', 'flavor-chat-ia'),
            'presupuestos_participativos' => __('Presupuestos Participativos', 'flavor-chat-ia'),
        ];

        if (isset($titles[$module_id])) {
            return $titles[$module_id];
        }

        // Fallback: convertir ID a título legible
        return ucwords(str_replace(['_', '-'], ' ', $module_id));
    }

    /**
     * Obtiene el icono de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string Clase de dashicon
     */
    private function get_module_icon($module_id) {
        $icons = [
            'socios' => 'dashicons-groups',
            'eventos' => 'dashicons-calendar-alt',
            'reservas' => 'dashicons-calendar',
            'tramites' => 'dashicons-clipboard',
            'incidencias' => 'dashicons-warning',
            'participacion' => 'dashicons-megaphone',
            'foros' => 'dashicons-format-chat',
            'comunidades' => 'dashicons-networking',
            'colectivos' => 'dashicons-groups',
            'marketplace' => 'dashicons-store',
            'banco_tiempo' => 'dashicons-clock',
            'economia_don' => 'dashicons-heart',
            'grupos_consumo' => 'dashicons-cart',
            'huertos_urbanos' => 'dashicons-carrot',
            'espacios_comunes' => 'dashicons-building',
            'biblioteca' => 'dashicons-book',
            'carpooling' => 'dashicons-car',
            'bicicletas_compartidas' => 'dashicons-performance',
            'reciclaje' => 'dashicons-update',
            'compostaje' => 'dashicons-palmtree',
            'energia_comunitaria' => 'dashicons-lightbulb',
            'radio' => 'dashicons-microphone',
            'podcast' => 'dashicons-playlist-audio',
            'multimedia' => 'dashicons-format-gallery',
            'chat_grupos' => 'dashicons-format-chat',
            'chat_interno' => 'dashicons-email-alt',
            'red_social' => 'dashicons-share',
            'cursos' => 'dashicons-welcome-learn-more',
            'talleres' => 'dashicons-hammer',
            'avisos_municipales' => 'dashicons-megaphone',
            'transparencia' => 'dashicons-visibility',
            'presupuestos_participativos' => 'dashicons-money-alt',
        ];

        return $icons[$module_id] ?? 'dashicons-admin-generic';
    }

    /**
     * Registra las páginas de admin para los módulos detectados
     */
    public function registrar_paginas_admin() {
        foreach ($this->modules_with_dashboards as $module_id => $config) {
            // Registrar página principal de dashboard
            $this->registrar_pagina_dashboard($config);

            // Registrar subpáginas si existen
            $this->registrar_subpaginas($config);
        }
    }

    /**
     * Registra la página de dashboard principal de un módulo
     *
     * @param array $config Configuración del módulo
     */
    private function registrar_pagina_dashboard($config) {
        $slug = $config['slug'];
        $hook_name = 'admin_page_' . $slug;

        if (has_action($hook_name) || $this->is_page_slug_already_registered($slug)) {
            return;
        }

        // Guardar configuración para uso en callback
        $this->page_configs[$slug] = $config;

        // Registrar como página oculta (sin entrada de menú visible)
        // La navegación se maneja a través del Flavor Shell
        add_submenu_page(
            null, // Parent null = página oculta
            $config['title'],
            $config['title'],
            'manage_options',
            $slug,
            [$this, 'render_dashboard_page']
        );
    }

    /**
     * Registra subpáginas adicionales de un módulo
     *
     * @param array $config Configuración del módulo
     */
    private function registrar_subpaginas($config) {
        $views_dir = $config['views_dir'];

        // Buscar otras vistas PHP además del dashboard
        $view_files = glob($views_dir . '/*.php');

        foreach ($view_files as $view_file) {
            $filename = basename($view_file, '.php');

            // Ignorar el dashboard (ya registrado) y archivos internos
            if ($filename === 'dashboard' || strpos($filename, '_') === 0) {
                continue;
            }

            $subpage_slug = str_replace('_', '-', $config['module_id']) . '-' . $filename;

            if ($this->is_page_slug_already_registered($subpage_slug)) {
                continue;
            }

            $this->page_configs[$subpage_slug] = [
                'module_id' => $config['module_id'],
                'dashboard_path' => $view_file,
                'views_dir' => $views_dir,
                'slug' => $subpage_slug,
                'title' => $config['title'] . ' - ' . ucwords(str_replace('-', ' ', $filename)),
                'icon' => $config['icon'],
                'is_subpage' => true,
                'parent_slug' => $config['slug'],
            ];

            if (has_action('admin_page_' . $subpage_slug)) {
                continue;
            }

            add_submenu_page(
                null,
                $this->page_configs[$subpage_slug]['title'],
                $this->page_configs[$subpage_slug]['title'],
                'manage_options',
                $subpage_slug,
                [$this, 'render_dashboard_page']
            );
        }
    }

    /**
     * Verifica si un slug ya fue registrado por otro sistema admin.
     *
     * @param string $slug
     * @return bool
     */
    private function is_page_slug_already_registered($slug) {
        if (empty($slug)) {
            return false;
        }

        global $menu, $submenu, $_registered_pages;

        if (is_array($menu)) {
            foreach ($menu as $item) {
                if (!empty($item[2]) && $item[2] === $slug) {
                    return true;
                }
            }
        }

        if (is_array($submenu)) {
            foreach ($submenu as $subitems) {
                if (!is_array($subitems)) {
                    continue;
                }
                foreach ($subitems as $entry) {
                    if (!empty($entry[2]) && $entry[2] === $slug) {
                        return true;
                    }
                }
            }
        }

        if (is_array($_registered_pages)) {
            foreach (array_keys($_registered_pages) as $hook_name) {
                if ($hook_name === 'admin_page_' . $slug || str_ends_with($hook_name, '_page_' . $slug)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Renderiza una página de dashboard
     */
    public function render_dashboard_page() {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (!isset($this->page_configs[$current_page])) {
            echo '<div class="wrap"><h1>' . esc_html__('Página no encontrada', 'flavor-chat-ia') . '</h1></div>';
            return;
        }

        $config = $this->page_configs[$current_page];

        // Verificar que el archivo existe
        if (!file_exists($config['dashboard_path'])) {
            echo '<div class="wrap"><h1>' . esc_html__('Vista no disponible', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('El archivo de vista no existe.', 'flavor-chat-ia') . '</p></div>';
            return;
        }

        // Preparar variables para la vista
        $module_id = $config['module_id'];
        $module_title = $config['title'];
        $module_icon = $config['icon'];

        ?>
        <div class="wrap flavor-module-dashboard" data-module="<?php echo esc_attr($module_id); ?>">
            <?php
            if (empty($config['is_subpage'])) {
                $this->render_related_module_dashboards_panel($module_id);
            }
            ?>
            <?php
            // Incluir la vista
            include $config['dashboard_path'];
            ?>
        </div>
        <?php
    }

    /**
     * Encola estilos para dashboards de módulos
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_dashboard_styles($hook) {
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $legacy_pages_with_relationship_panel = ['grupos-consumo'];

        // Verificar si es una página de dashboard de módulo
        if (!isset($this->page_configs[$current_page]) && !in_array($current_page, $legacy_pages_with_relationship_panel, true)) {
            return;
        }

        // Estilos base para dashboards de módulos
        wp_enqueue_style(
            'flavor-module-dashboard',
            FLAVOR_CHAT_IA_URL . 'assets/css/layouts/dashboard-module-components.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Chart.js para gráficos
        wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', [], '4.4.0', true);
    }

    /**
     * Obtiene los módulos activos
     *
     * @return array Lista de IDs de módulos activos
     */
    private function get_active_modules() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $settings['active_modules'] ?? [];

        // También buscar en opción legacy
        $modulos_legacy = get_option('flavor_active_modules', []);
        if (!empty($modulos_legacy)) {
            $modulos_activos = array_unique(array_merge($modulos_activos, $modulos_legacy));
        }

        return $modulos_activos;
    }

    /**
     * Obtiene los módulos con dashboards detectados
     *
     * @return array
     */
    public function get_modules_with_dashboards() {
        return $this->modules_with_dashboards;
    }

    /**
     * Verifica si un módulo tiene dashboard registrado
     *
     * @param string $module_id ID del módulo
     * @return bool
     */
    public function has_dashboard($module_id) {
        return isset($this->modules_with_dashboards[$module_id]);
    }

    /**
     * Obtiene la URL del dashboard de un módulo
     *
     * @param string $module_id ID del módulo
     * @return string|null URL o null si no existe
     */
    public function get_dashboard_url($module_id) {
        if (!$this->has_dashboard($module_id)) {
            return null;
        }

        return admin_url('admin.php?page=' . $this->modules_with_dashboards[$module_id]['slug']);
    }

    /**
     * Renderiza un panel con accesos a dashboards de módulos vinculados.
     *
     * @param string $module_id
     * @return void
     */
    private function render_related_module_dashboards_panel($module_id) {
        $related_modules = $this->get_related_modules_with_dashboards($module_id);
        $related_transversals = $this->get_related_transversal_modules_with_dashboards($module_id);

        if (empty($related_modules) && empty($related_transversals)) {
            return;
        }
        ?>
        <section class="dm-card dm-relations-panel" aria-label="<?php esc_attr_e('Módulos relacionados', 'flavor-chat-ia'); ?>">
            <?php if (!empty($related_modules)) : ?>
                <div class="dm-relations-panel__section<?php echo !empty($related_transversals) ? ' dm-relations-panel__section--with-divider' : ''; ?>">
                    <div class="dm-section__header dm-relations-panel__header">
                        <h2 class="dm-section__title">
                            <span class="dashicons dashicons-networking" aria-hidden="true"></span>
                            <?php esc_html_e('Módulos vinculados', 'flavor-chat-ia'); ?>
                        </h2>
                    </div>
                    <p class="dm-relations-panel__description">
                        <?php esc_html_e('Accesos directos a dashboards que dependen de este módulo.', 'flavor-chat-ia'); ?>
                    </p>
                    <div class="dm-action-grid dm-relations-panel__grid">
                    <?php foreach ($related_modules as $related_module) : ?>
                        <a class="dm-action-card dm-relations-panel__link" href="<?php echo esc_url($related_module['url']); ?>">
                            <span class="dashicons <?php echo esc_attr($related_module['icon']); ?> dm-action-card__icon" aria-hidden="true"></span>
                            <span class="dm-action-card__label"><?php echo esc_html($related_module['title']); ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($related_transversals)) : ?>
                <div class="dm-relations-panel__section">
                    <div class="dm-section__header dm-relations-panel__header">
                        <h2 class="dm-section__title">
                            <span class="dashicons dashicons-randomize" aria-hidden="true"></span>
                            <?php esc_html_e('Capas transversales relacionadas', 'flavor-chat-ia'); ?>
                        </h2>
                    </div>
                    <p class="dm-relations-panel__description">
                        <?php esc_html_e('Dashboards transversales que miden, gobiernan, enseñan o dan soporte a este módulo.', 'flavor-chat-ia'); ?>
                    </p>
                    <div class="dm-action-grid dm-relations-panel__grid dm-relations-panel__grid--transversal">
                    <?php foreach ($related_transversals as $transversal_module) : ?>
                        <a class="dm-action-card dm-relations-panel__link dm-relations-panel__link--transversal" href="<?php echo esc_url($transversal_module['url']); ?>">
                            <span class="dashicons <?php echo esc_attr($transversal_module['icon']); ?> dm-action-card__icon" aria-hidden="true"></span>
                            <span class="dm-action-card__label"><?php echo esc_html($transversal_module['title']); ?></span>
                        </a>
                    <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </section>
        <?php
    }

    /**
     * Punto de entrada público para renderizar el panel de relaciones
     * desde dashboards que no usan este registrador como callback.
     *
     * @param string $module_id
     * @return void
     */
    public function render_relationship_panel_for_module($module_id) {
        $this->render_related_module_dashboards_panel($module_id);
    }

    /**
     * Obtiene módulos relacionados (dependientes directos) con dashboard disponible.
     *
     * @param string $module_id
     * @return array
     */
    private function get_related_modules_with_dashboards($module_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $normalized_module_id = $this->normalize_module_id($module_id);
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $registered_modules = $loader->get_registered_modules();
        $current_module_meta = $this->find_registered_module_meta($registered_modules, $normalized_module_id);

        if (!is_array($current_module_meta)) {
            return [];
        }

        $related_map = [];
        $current_ecosystem = is_array($current_module_meta['ecosystem'] ?? null) ? $current_module_meta['ecosystem'] : [];

        // 1) Módulos declarados explícitamente por el módulo actual.
        foreach ((array) ($current_ecosystem['depends_on'] ?? []) as $related_module_id) {
            $normalized_related_id = $this->normalize_module_id($related_module_id);
            if ($normalized_related_id !== '' && $normalized_related_id !== $normalized_module_id) {
                $related_map[$normalized_related_id] = true;
            }
        }
        foreach ((array) ($current_ecosystem['supports_modules'] ?? []) as $related_module_id) {
            $normalized_related_id = $this->normalize_module_id($related_module_id);
            if ($normalized_related_id !== '' && $normalized_related_id !== $normalized_module_id) {
                $related_map[$normalized_related_id] = true;
            }
        }

        // 2) Hijos explícitos declarados por el módulo base.
        foreach ((array) ($current_ecosystem['base_for_modules'] ?? []) as $child_module_id) {
            $normalized_child_id = $this->normalize_module_id($child_module_id);
            if ($normalized_child_id !== '' && $normalized_child_id !== $normalized_module_id) {
                $related_map[$normalized_child_id] = true;
            }
        }

        // 3) Hijos inferidos por dependencia directa.
        foreach ($registered_modules as $candidate_module_id => $candidate_module_data) {
            $normalized_candidate_id = $this->normalize_module_id($candidate_module_id);
            if ($normalized_candidate_id === '' || $normalized_candidate_id === $normalized_module_id) {
                continue;
            }

            $candidate_ecosystem = is_array($candidate_module_data['ecosystem'] ?? null) ? $candidate_module_data['ecosystem'] : [];
            $depends_on = array_map([$this, 'normalize_module_id'], (array) ($candidate_ecosystem['depends_on'] ?? []));

            if (in_array($normalized_module_id, $depends_on, true)) {
                $related_map[$normalized_candidate_id] = true;
            }
        }

        if (empty($related_map)) {
            return [];
        }

        $related_modules = [];
        foreach (array_keys($related_map) as $related_module_id) {
            $dashboard = $this->find_dashboard_config_by_module_id($related_module_id);
            if (!is_array($dashboard)) {
                continue;
            }

            $related_modules[] = [
                'id' => $related_module_id,
                'title' => $dashboard['title'] ?? ucwords(str_replace('_', ' ', $related_module_id)),
                'icon' => $dashboard['icon'] ?? 'dashicons-admin-plugins',
                'url' => admin_url('admin.php?page=' . ($dashboard['slug'] ?? '')),
            ];
        }

        usort($related_modules, static function($a, $b) {
            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $related_modules;
    }

    /**
     * Obtiene módulos transversales relacionados con dashboard disponible.
     *
     * @param string $module_id
     * @return array
     */
    private function get_related_transversal_modules_with_dashboards($module_id) {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return [];
        }

        $normalized_module_id = $this->normalize_module_id($module_id);
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $registered_modules = $loader->get_registered_modules();

        $related_map = [];
        $relation_keys = ['supports_modules', 'measures_modules', 'governs_modules', 'teaches_modules'];

        foreach ($registered_modules as $candidate_module_id => $candidate_module_data) {
            $normalized_candidate_id = $this->normalize_module_id($candidate_module_id);
            if ($normalized_candidate_id === '' || $normalized_candidate_id === $normalized_module_id) {
                continue;
            }

            $candidate_ecosystem = is_array($candidate_module_data['ecosystem'] ?? null) ? $candidate_module_data['ecosystem'] : [];
            $candidate_role = sanitize_key((string) ($candidate_ecosystem['module_role'] ?? 'vertical'));
            if ($candidate_role !== 'transversal') {
                continue;
            }

            foreach ($relation_keys as $relation_key) {
                $related_ids = array_map([$this, 'normalize_module_id'], (array) ($candidate_ecosystem[$relation_key] ?? []));
                if (in_array($normalized_module_id, $related_ids, true)) {
                    $related_map[$normalized_candidate_id] = true;
                    break;
                }
            }
        }

        if (empty($related_map)) {
            return [];
        }

        $related_modules = [];
        foreach (array_keys($related_map) as $related_module_id) {
            $dashboard = $this->find_dashboard_config_by_module_id($related_module_id);
            if (!is_array($dashboard)) {
                continue;
            }

            $related_modules[] = [
                'id' => $related_module_id,
                'title' => $dashboard['title'] ?? ucwords(str_replace('_', ' ', $related_module_id)),
                'icon' => $dashboard['icon'] ?? 'dashicons-admin-plugins',
                'url' => admin_url('admin.php?page=' . ($dashboard['slug'] ?? '')),
            ];
        }

        usort($related_modules, static function($a, $b) {
            return strcasecmp((string) ($a['title'] ?? ''), (string) ($b['title'] ?? ''));
        });

        return $related_modules;
    }

    /**
     * Normaliza un ID de módulo a formato `snake_case`.
     *
     * @param string $module_id
     * @return string
     */
    private function normalize_module_id($module_id) {
        return sanitize_key(str_replace('-', '_', (string) $module_id));
    }

    /**
     * Busca metadatos de módulo en array registrado aceptando snake_case/kebab-case.
     *
     * @param array $registered_modules
     * @param string $module_id
     * @return array|null
     */
    private function find_registered_module_meta(array $registered_modules, $module_id) {
        $normalized_target = $this->normalize_module_id($module_id);

        foreach ($registered_modules as $candidate_id => $candidate_meta) {
            if ($this->normalize_module_id($candidate_id) === $normalized_target) {
                return is_array($candidate_meta) ? $candidate_meta : null;
            }
        }

        return null;
    }

    /**
     * Busca configuración de dashboard aceptando snake_case/kebab-case.
     *
     * @param string $module_id
     * @return array|null
     */
    private function find_dashboard_config_by_module_id($module_id) {
        $normalized_target = $this->normalize_module_id($module_id);

        foreach ($this->modules_with_dashboards as $candidate_id => $dashboard_config) {
            if ($this->normalize_module_id($candidate_id) === $normalized_target) {
                return is_array($dashboard_config) ? $dashboard_config : null;
            }
        }

        return null;
    }
}

// Inicializar el registrador
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_Module_Dashboards_Registrar::get_instance();
    }
}, 20);
