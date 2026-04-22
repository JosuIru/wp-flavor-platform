<?php
/**
 * Breadcrumbs para el Admin de WordPress
 *
 * Muestra navegación de migas de pan en las páginas de edición
 * de los custom post types de Flavor y en las páginas del admin shell.
 *
 * @package FlavorPlatform
 * @since 3.3.0
 * @since 3.5.2 Añadido soporte para páginas del admin shell con jerarquía de módulos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Admin_Breadcrumbs {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Mapeo de post types a módulos
     */
    private $post_type_modules = [];

    /**
     * Cache de información de páginas
     *
     * @var array
     */
    private $page_info_cache = [];

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
        $this->init_post_type_modules();
        add_action('edit_form_top', [$this, 'render_breadcrumbs']);
        add_action('admin_head', [$this, 'add_styles']);
        // Breadcrumbs para páginas del shell
        add_action('in_admin_header', [$this, 'render_shell_breadcrumbs'], 100);
    }

    /**
     * Inicializa el mapeo de post types a módulos
     */
    private function init_post_type_modules() {
        $this->post_type_modules = [
            // Grupos de Consumo
            'gc_ciclo' => [
                'module_name' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-store',
                'module_page' => 'grupos-consumo',
                'section_name' => __('Ciclos de Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=gc_ciclo',
            ],
            'gc_productor' => [
                'module_name' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-store',
                'module_page' => 'grupos-consumo',
                'section_name' => __('Productores', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=gc_productor',
            ],

            // Eventos
            'flavor_evento' => [
                'module_name' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-calendar-alt',
                'module_page' => 'eventos',
                'section_name' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_evento',
            ],

            // Cursos
            'flavor_curso' => [
                'module_name' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-welcome-learn-more',
                'module_page' => 'cursos',
                'section_name' => __('Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_curso',
            ],

            // Talleres
            'flavor_taller' => [
                'module_name' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-hammer',
                'module_page' => 'talleres',
                'section_name' => __('Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_taller',
            ],

            // Biblioteca
            'flavor_libro' => [
                'module_name' => __('Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-book',
                'module_page' => 'biblioteca',
                'section_name' => __('Libros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_libro',
            ],

            // Reservas
            'flavor_recurso' => [
                'module_name' => __('Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-calendar',
                'module_page' => 'reservas',
                'section_name' => __('Recursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_recurso',
            ],

            // Espacios Comunes
            'flavor_espacio' => [
                'module_name' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-admin-home',
                'module_page' => 'espacios-comunes',
                'section_name' => __('Espacios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_espacio',
            ],

            // Podcast
            'flavor_episodio' => [
                'module_name' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-microphone',
                'module_page' => 'podcast',
                'section_name' => __('Episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_episodio',
            ],

            // Radio
            'flavor_programa' => [
                'module_name' => __('Radio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-controls-volumeon',
                'module_page' => 'radio',
                'section_name' => __('Programas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_programa',
            ],

            // Marketplace
            'flavor_producto' => [
                'module_name' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-cart',
                'module_page' => 'marketplace',
                'section_name' => __('Productos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_producto',
            ],

            // Landing Pages
            'flavor_landing' => [
                'module_name' => __('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-welcome-widgets-menus',
                'module_page' => 'flavor-landings',
                'section_name' => __('Landings', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_landing',
            ],

            // Comunidades
            'flavor_comunidad' => [
                'module_name' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-groups',
                'module_page' => 'comunidades',
                'section_name' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_comunidad',
            ],

            // Foros
            'flavor_foro' => [
                'module_name' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-format-chat',
                'module_page' => 'foros',
                'section_name' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_foro',
            ],
            'flavor_tema_foro' => [
                'module_name' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-format-chat',
                'module_page' => 'foros',
                'section_name' => __('Temas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_tema_foro',
            ],

            // Huertos
            'flavor_huerto' => [
                'module_name' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-carrot',
                'module_page' => 'huertos-urbanos',
                'section_name' => __('Huertos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_huerto',
            ],

            // Incidencias
            'flavor_incidencia' => [
                'module_name' => __('Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-warning',
                'module_page' => 'incidencias',
                'section_name' => __('Tickets', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_incidencia',
            ],

            // Campañas
            'flavor_campania' => [
                'module_name' => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-flag',
                'module_page' => 'campanias',
                'section_name' => __('Campañas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_campania',
            ],

            // Participación
            'flavor_propuesta' => [
                'module_name' => __('Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-megaphone',
                'module_page' => 'participacion',
                'section_name' => __('Propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_propuesta',
            ],

            // Presupuestos Participativos
            'flavor_presupuesto' => [
                'module_name' => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-chart-pie',
                'module_page' => 'presupuestos-participativos',
                'section_name' => __('Proyectos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_presupuesto',
            ],

            // Banco de Tiempo
            'flavor_servicio_bt' => [
                'module_name' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-backup',
                'module_page' => 'banco-tiempo',
                'section_name' => __('Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_servicio_bt',
            ],

            // Trabajo Digno
            'flavor_oferta_td' => [
                'module_name' => __('Trabajo Digno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-businessperson',
                'module_page' => 'trabajo-digno',
                'section_name' => __('Ofertas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_oferta_td',
            ],

            // Economía del Don
            'flavor_don' => [
                'module_name' => __('Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-heart',
                'module_page' => 'economia-don',
                'section_name' => __('Dones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_don',
            ],

            // Recetas
            'flavor_receta' => [
                'module_name' => __('Recetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-carrot',
                'module_page' => 'recetas',
                'section_name' => __('Recetas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_receta',
            ],

            // Transparencia
            'flavor_acta' => [
                'module_name' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-visibility',
                'module_page' => 'transparencia',
                'section_name' => __('Actas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_acta',
            ],
            'flavor_contrato' => [
                'module_name' => __('Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'module_icon' => 'dashicons-visibility',
                'module_page' => 'transparencia',
                'section_name' => __('Contratos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'section_page' => 'edit.php?post_type=flavor_contrato',
            ],
        ];

        // Permitir extensión vía filtro
        $this->post_type_modules = apply_filters('flavor_admin_breadcrumbs_post_types', $this->post_type_modules);
    }

    /**
     * Renderiza los breadcrumbs
     */
    public function render_breadcrumbs($post) {
        if (!$post || !isset($post->post_type)) {
            return;
        }

        $post_type = $post->post_type;

        // Verificar si este post type tiene breadcrumbs configurados
        if (!isset($this->post_type_modules[$post_type])) {
            return;
        }

        $config = $this->post_type_modules[$post_type];
        $is_new = !$post->ID || $post->post_status === 'auto-draft';
        $action_label = $is_new ? __('Crear nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Editar', FLAVOR_PLATFORM_TEXT_DOMAIN);

        ?>
        <nav class="flavor-admin-breadcrumbs" aria-label="<?php esc_attr_e('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            <ol class="flavor-admin-breadcrumbs__list">
                <!-- Módulo principal -->
                <li class="flavor-admin-breadcrumbs__item">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $config['module_page'])); ?>" class="flavor-admin-breadcrumbs__link">
                        <span class="dashicons <?php echo esc_attr($config['module_icon']); ?>"></span>
                        <span><?php echo esc_html($config['module_name']); ?></span>
                    </a>
                </li>

                <!-- Separador -->
                <li class="flavor-admin-breadcrumbs__separator" aria-hidden="true">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </li>

                <!-- Sección (listado) -->
                <li class="flavor-admin-breadcrumbs__item">
                    <a href="<?php echo esc_url(admin_url($config['section_page'])); ?>" class="flavor-admin-breadcrumbs__link">
                        <span><?php echo esc_html($config['section_name']); ?></span>
                    </a>
                </li>

                <!-- Separador -->
                <li class="flavor-admin-breadcrumbs__separator" aria-hidden="true">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </li>

                <!-- Acción actual -->
                <li class="flavor-admin-breadcrumbs__item flavor-admin-breadcrumbs__item--current" aria-current="page">
                    <span><?php echo esc_html($action_label); ?></span>
                    <?php if (!$is_new && $post->post_title): ?>
                        <span class="flavor-admin-breadcrumbs__title">: <?php echo esc_html(wp_trim_words($post->post_title, 5)); ?></span>
                    <?php endif; ?>
                </li>
            </ol>
        </nav>
        <?php
    }

    /**
     * Añade estilos CSS
     */
    public function add_styles() {
        global $pagenow, $post;

        // Solo en páginas de edición
        if (!in_array($pagenow, ['post.php', 'post-new.php'])) {
            return;
        }

        // Verificar si es un post type soportado
        $post_type = '';
        if ($pagenow === 'post-new.php') {
            $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'post';
        } elseif ($post) {
            $post_type = $post->post_type;
        }

        if (!isset($this->post_type_modules[$post_type])) {
            return;
        }

        ?>
        <style>
            .flavor-admin-breadcrumbs {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border: 1px solid #e2e8f0;
                border-radius: 8px;
                padding: 12px 16px;
                margin: 0 0 20px 0;
                font-size: 13px;
            }

            .flavor-admin-breadcrumbs__list {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 8px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .flavor-admin-breadcrumbs__item {
                display: flex;
                align-items: center;
                gap: 6px;
            }

            .flavor-admin-breadcrumbs__link {
                display: flex;
                align-items: center;
                gap: 6px;
                color: #2563eb;
                text-decoration: none;
                padding: 4px 8px;
                border-radius: 4px;
                transition: all 0.2s ease;
            }

            .flavor-admin-breadcrumbs__link:hover {
                background: #dbeafe;
                color: #1d4ed8;
            }

            .flavor-admin-breadcrumbs__link .dashicons {
                font-size: 16px;
                width: 16px;
                height: 16px;
            }

            .flavor-admin-breadcrumbs__separator {
                color: #94a3b8;
            }

            .flavor-admin-breadcrumbs__separator .dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
            }

            .flavor-admin-breadcrumbs__item--current {
                color: #475569;
                font-weight: 500;
                padding: 4px 8px;
                background: #e2e8f0;
                border-radius: 4px;
            }

            .flavor-admin-breadcrumbs__title {
                color: #64748b;
                font-weight: 400;
            }

            /* Dark mode */
            .fls-shell-dark .flavor-admin-breadcrumbs,
            body.flavor-dark-mode .flavor-admin-breadcrumbs {
                background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
                border-color: #475569;
            }

            .fls-shell-dark .flavor-admin-breadcrumbs__link,
            body.flavor-dark-mode .flavor-admin-breadcrumbs__link {
                color: #60a5fa;
            }

            .fls-shell-dark .flavor-admin-breadcrumbs__link:hover,
            body.flavor-dark-mode .flavor-admin-breadcrumbs__link:hover {
                background: #1e3a5f;
                color: #93c5fd;
            }

            .fls-shell-dark .flavor-admin-breadcrumbs__separator,
            body.flavor-dark-mode .flavor-admin-breadcrumbs__separator {
                color: #64748b;
            }

            .fls-shell-dark .flavor-admin-breadcrumbs__item--current,
            body.flavor-dark-mode .flavor-admin-breadcrumbs__item--current {
                color: #e2e8f0;
                background: #475569;
            }

            .fls-shell-dark .flavor-admin-breadcrumbs__title,
            body.flavor-dark-mode .flavor-admin-breadcrumbs__title {
                color: #94a3b8;
            }

            /* ============================================
               Page Header del Shell (páginas admin)
               Envuelve breadcrumbs + título del apartado + chips
               ============================================ */
            .fls-page-header {
                --fls-accent: #3b82f6;
                background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
                border-bottom: 1px solid #e2e8f0;
                margin-left: 260px;
                position: relative;
                z-index: 99;
                box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
            }

            body.fls-shell-active .fls-page-header {
                margin-left: 260px;
            }

            body.fls-shell-active.fls-shell--collapsed .fls-page-header {
                margin-left: 60px;
            }

            .fls-page-header__title-row {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 6px 20px 14px;
            }

            .fls-page-header__icon.dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: var(--fls-accent);
                flex-shrink: 0;
            }

            .fls-page-header__title {
                margin: 0;
                padding: 0;
                font-size: 22px;
                line-height: 1.2;
                font-weight: 600;
                color: #0f172a;
                letter-spacing: -0.01em;
            }

            /* Chips / tabs de subpáginas hermanas */
            .fls-page-header__tabs {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                padding: 0 20px 12px;
            }

            .fls-page-header__tab {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 999px;
                background: #f1f5f9;
                color: #475569;
                font-size: 13px;
                font-weight: 500;
                text-decoration: none;
                border: 1px solid transparent;
                transition: background-color 0.15s ease, color 0.15s ease, border-color 0.15s ease;
            }

            .fls-page-header__tab:hover,
            .fls-page-header__tab:focus-visible {
                background: #e2e8f0;
                color: #0f172a;
                outline: none;
            }

            .fls-page-header__tab--active,
            .fls-page-header__tab--active:hover {
                background: var(--fls-accent);
                color: #ffffff;
                border-color: var(--fls-accent);
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            .fls-page-header__tab-icon.dashicons {
                font-size: 14px;
                width: 14px;
                height: 14px;
                opacity: 0.9;
            }

            /* Breadcrumbs dentro del page header (reset de borde/background) */
            .fls-page-header .fls-breadcrumbs {
                background: transparent;
                border-bottom: none;
                margin-left: 0;
                padding: 10px 20px 4px;
                font-size: 13px;
                position: static;
                z-index: auto;
                box-shadow: none;
            }

            /* Compatibilidad: si .fls-breadcrumbs se usara fuera del page header
               (edición CPTs, etc.) conserva su layout previo. */
            .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs) {
                background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
                border-bottom: 1px solid #e2e8f0;
                padding: 10px 20px;
                margin-left: 260px;
                font-size: 13px;
                position: relative;
                z-index: 99;
            }

            .fls-shell--collapsed ~ .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs),
            .fls-shell--collapsed + #wpcontent .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs) {
                margin-left: 60px;
            }

            body.fls-shell-active .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs) {
                margin-left: 260px;
            }

            body.fls-shell-active.fls-shell--collapsed .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs) {
                margin-left: 60px;
            }

            .fls-breadcrumbs__list {
                display: flex;
                align-items: center;
                flex-wrap: wrap;
                gap: 4px;
                margin: 0;
                padding: 0;
                list-style: none;
            }

            .fls-breadcrumbs__item {
                display: flex;
                align-items: center;
            }

            .fls-breadcrumbs__link {
                display: flex;
                align-items: center;
                gap: 5px;
                color: #64748b;
                text-decoration: none;
                padding: 4px 8px;
                border-radius: 4px;
                transition: all 0.15s ease;
            }

            .fls-breadcrumbs__link:hover {
                background: #e2e8f0;
                color: #334155;
            }

            .fls-breadcrumbs__icon {
                font-size: 14px;
                width: 14px;
                height: 14px;
                opacity: 0.7;
            }

            .fls-breadcrumbs__separator {
                color: #cbd5e1;
                display: flex;
                align-items: center;
                margin: 0 2px;
            }

            .fls-breadcrumbs__separator svg {
                width: 14px;
                height: 14px;
            }

            .fls-breadcrumbs__current {
                display: flex;
                align-items: center;
                gap: 5px;
                color: #1e293b;
                font-weight: 500;
                padding: 4px 8px;
            }

            .fls-breadcrumbs__item--current .fls-breadcrumbs__icon {
                opacity: 1;
                color: var(--crumb-color, #3b82f6);
            }

            /* Dark mode para shell breadcrumbs */
            .fls-shell-dark .fls-breadcrumbs,
            body.flavor-dark-mode .fls-breadcrumbs {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                border-color: #334155;
            }

            .fls-shell-dark .fls-breadcrumbs__link,
            body.flavor-dark-mode .fls-breadcrumbs__link {
                color: #94a3b8;
            }

            .fls-shell-dark .fls-breadcrumbs__link:hover,
            body.flavor-dark-mode .fls-breadcrumbs__link:hover {
                background: #334155;
                color: #e2e8f0;
            }

            .fls-shell-dark .fls-breadcrumbs__separator,
            body.flavor-dark-mode .fls-breadcrumbs__separator {
                color: #475569;
            }

            .fls-shell-dark .fls-breadcrumbs__current,
            body.flavor-dark-mode .fls-breadcrumbs__current {
                color: #f1f5f9;
            }

            /* Dark mode para el page header */
            .fls-shell-dark .fls-page-header,
            body.flavor-dark-mode .fls-page-header {
                background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
                border-color: #334155;
                box-shadow: 0 1px 0 rgba(0, 0, 0, 0.2);
            }

            .fls-shell-dark .fls-page-header__title,
            body.flavor-dark-mode .fls-page-header__title {
                color: #f1f5f9;
            }

            .fls-shell-dark .fls-page-header__tab,
            body.flavor-dark-mode .fls-page-header__tab {
                background: #1e293b;
                color: #cbd5e1;
            }

            .fls-shell-dark .fls-page-header__tab:hover,
            .fls-shell-dark .fls-page-header__tab:focus-visible,
            body.flavor-dark-mode .fls-page-header__tab:hover,
            body.flavor-dark-mode .fls-page-header__tab:focus-visible {
                background: #334155;
                color: #f1f5f9;
            }

            /* Responsive */
            @media (max-width: 782px) {
                .fls-page-header,
                body.fls-shell-active .fls-page-header {
                    margin-left: 0;
                }

                .fls-page-header__title-row {
                    padding: 4px 12px 10px;
                }

                .fls-page-header__title {
                    font-size: 18px;
                }

                .fls-page-header__tabs {
                    padding: 0 12px 10px;
                    overflow-x: auto;
                    flex-wrap: nowrap;
                    scrollbar-width: thin;
                    -webkit-overflow-scrolling: touch;
                }

                .fls-page-header__tab {
                    flex-shrink: 0;
                }

                .fls-page-header .fls-breadcrumbs {
                    padding: 8px 12px 2px;
                }

                .fls-breadcrumbs:not(.fls-page-header .fls-breadcrumbs) {
                    margin-left: 0;
                    padding: 8px 12px;
                }

                .fls-breadcrumbs__text {
                    display: none;
                }

                .fls-breadcrumbs__item--current .fls-breadcrumbs__text {
                    display: inline;
                }
            }
        </style>
        <?php
    }

    /**
     * Renderizar page header para páginas del shell
     *
     * Envuelve breadcrumb + título del apartado actual + chips con las
     * subpáginas hermanas (si el apartado tiene subpáginas registradas).
     */
    public function render_shell_breadcrumbs() {
        if (!$this->is_shell_page() || !$this->is_shell_active()) {
            return;
        }

        $breadcrumbs = $this->build_shell_breadcrumbs();

        if (empty($breadcrumbs)) {
            return;
        }

        $current_page_slug = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $page_info = $this->get_shell_page_info($current_page_slug);

        // Datos del apartado (dashboard padre): si estamos en subpágina, el
        // apartado es el padre; si estamos en el dashboard mismo, el apartado
        // es la propia página.
        $apartado_slug = $page_info['parent_slug'] ?? $current_page_slug;
        $apartado_label = $page_info['parent_label'] ?? $page_info['label'];
        $apartado_icon = $page_info['parent_icon'] ?? $page_info['icon'] ?? 'dashicons-admin-page';
        $apartado_color = $page_info['color'] ?? null;

        $siblings = $this->get_apartado_siblings($apartado_slug, $current_page_slug);
        ?>
        <header class="fls-page-header" role="banner">
            <nav class="fls-breadcrumbs" aria-label="<?php esc_attr_e('Navegación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                <ol class="fls-breadcrumbs__list">
                    <?php
                    $count = count($breadcrumbs);
                    $index = 0;

                    foreach ($breadcrumbs as $crumb) :
                        $index++;
                        $is_last = $index === $count;
                        $is_current = !empty($crumb['current']);
                        ?>
                        <li class="fls-breadcrumbs__item<?php echo $is_current ? ' fls-breadcrumbs__item--current' : ''; ?>">
                            <?php if (!empty($crumb['url']) && !$is_current) : ?>
                                <a href="<?php echo esc_url($crumb['url']); ?>" class="fls-breadcrumbs__link">
                                    <?php if (!empty($crumb['icon'])) : ?>
                                        <span class="dashicons <?php echo esc_attr($crumb['icon']); ?> fls-breadcrumbs__icon"></span>
                                    <?php endif; ?>
                                    <span class="fls-breadcrumbs__text"><?php echo esc_html($crumb['label']); ?></span>
                                </a>
                            <?php else : ?>
                                <span class="fls-breadcrumbs__current"
                                    <?php if (!empty($crumb['color'])) : ?>
                                        style="--crumb-color: <?php echo esc_attr($crumb['color']); ?>"
                                    <?php endif; ?>
                                >
                                    <?php if (!empty($crumb['icon'])) : ?>
                                        <span class="dashicons <?php echo esc_attr($crumb['icon']); ?> fls-breadcrumbs__icon"></span>
                                    <?php endif; ?>
                                    <span class="fls-breadcrumbs__text"><?php echo esc_html($crumb['label']); ?></span>
                                </span>
                            <?php endif; ?>

                            <?php if (!$is_last) : ?>
                                <span class="fls-breadcrumbs__separator" aria-hidden="true">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="9 18 15 12 9 6"></polyline>
                                    </svg>
                                </span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <div class="fls-page-header__title-row"
                <?php if ($apartado_color) : ?>style="--fls-accent: <?php echo esc_attr($apartado_color); ?>"<?php endif; ?>
            >
                <?php if (!empty($apartado_icon)) : ?>
                    <span class="dashicons <?php echo esc_attr($apartado_icon); ?> fls-page-header__icon" aria-hidden="true"></span>
                <?php endif; ?>
                <h1 class="fls-page-header__title"><?php echo esc_html($apartado_label); ?></h1>
            </div>

            <?php if (!empty($siblings)) : ?>
                <nav class="fls-page-header__tabs" aria-label="<?php esc_attr_e('Subpáginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <?php foreach ($siblings as $sibling) :
                        $tab_classes = 'fls-page-header__tab';
                        if (!empty($sibling['active'])) {
                            $tab_classes .= ' fls-page-header__tab--active';
                        }
                        ?>
                        <a href="<?php echo esc_url($sibling['url']); ?>"
                            class="<?php echo esc_attr($tab_classes); ?>"
                            <?php if (!empty($sibling['active'])) : ?>aria-current="page"<?php endif; ?>
                        >
                            <?php if (!empty($sibling['icon'])) : ?>
                                <span class="dashicons <?php echo esc_attr($sibling['icon']); ?> fls-page-header__tab-icon" aria-hidden="true"></span>
                            <?php endif; ?>
                            <span class="fls-page-header__tab-label"><?php echo esc_html($sibling['label']); ?></span>
                        </a>
                    <?php endforeach; ?>
                </nav>
            <?php endif; ?>
        </header>
        <?php
    }

    /**
     * Obtener las subpáginas hermanas del apartado activo, marcando la actual.
     *
     * Consulta directamente el registry del shell: si un apartado no tiene
     * subpáginas registradas, devuelve array vacío y los chips no se pintan.
     *
     * @param string $apartado_slug      Slug del dashboard padre del apartado actual
     * @param string $current_page_slug  Slug de la página que se está viendo
     * @return array Lista de siblings con formato [slug,label,icon,url,active]
     */
    private function get_apartado_siblings($apartado_slug, $current_page_slug) {
        if (empty($apartado_slug) || !class_exists('Flavor_Shell_Navigation_Registry')) {
            return [];
        }

        $registry = Flavor_Shell_Navigation_Registry::get_instance();
        $subpages = $registry->get_module_subpages($apartado_slug);

        if (empty($subpages)) {
            return [];
        }

        // Primer chip: el propio dashboard del apartado (vista "raíz" / general)
        $siblings = [[
            'slug'   => $apartado_slug,
            'label'  => __('General', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icon'   => 'dashicons-admin-home',
            'url'    => admin_url('admin.php?page=' . rawurlencode($apartado_slug)),
            'active' => $current_page_slug === $apartado_slug,
        ]];

        foreach ($subpages as $subpage) {
            if (empty($subpage['slug'])) {
                continue;
            }
            $siblings[] = [
                'slug'   => $subpage['slug'],
                'label'  => $subpage['label'] ?? $subpage['slug'],
                'icon'   => $subpage['icon'] ?? 'dashicons-arrow-right-alt2',
                'url'    => admin_url('admin.php?page=' . rawurlencode($subpage['slug'])),
                'active' => $current_page_slug === $subpage['slug'],
            ];
        }

        return $siblings;
    }

    /**
     * Verificar si estamos en una página del shell
     *
     * @return bool
     */
    private function is_shell_page() {
        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (empty($page)) {
            return false;
        }

        // Prefijos conocidos de Flavor
        $flavor_prefixes = ['flavor-', 'gc-'];
        foreach ($flavor_prefixes as $prefix) {
            if (strpos($page, $prefix) === 0) {
                return true;
            }
        }

        // Slugs de módulos conocidos
        $module_slugs = [
            'socios', 'comunidades', 'colectivos', 'foros', 'eventos',
            'cursos', 'talleres', 'reservas', 'tramites', 'incidencias',
            'marketplace', 'banco-tiempo', 'contabilidad', 'huertos',
            'espacios', 'biblioteca', 'carpooling', 'reciclaje', 'compostaje',
            'bicicletas', 'multimedia', 'podcast', 'radio', 'campanias',
            'encuestas', 'chat-grupos', 'chat-interno', 'clientes', 'facturas',
            'empresas', 'crowdfunding', 'presupuestos', 'transparencia',
        ];

        foreach ($module_slugs as $slug) {
            if (strpos($page, $slug) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar si el shell está activo
     *
     * @return bool
     */
    private function is_shell_active() {
        if (!class_exists('Flavor_Admin_Shell')) {
            return false;
        }

        $shell = Flavor_Admin_Shell::get_instance();
        return $shell->should_show_shell();
    }

    /**
     * Construir breadcrumbs para páginas del shell
     *
     * @return array
     */
    private function build_shell_breadcrumbs() {
        $breadcrumbs = [];
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';

        if (empty($current_page)) {
            return $breadcrumbs;
        }

        // Si es el dashboard principal, no mostrar breadcrumbs
        if ($current_page === 'flavor-dashboard') {
            return $breadcrumbs;
        }

        // Siempre empezar con Dashboard
        $breadcrumbs[] = [
            'label' => __('Dashboard', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'url'   => admin_url('admin.php?page=flavor-dashboard'),
            'icon'  => 'dashicons-dashboard',
        ];

        // Obtener información de la página actual
        $page_info = $this->get_shell_page_info($current_page);

        // Añadir categoría si existe
        if (!empty($page_info['category'])) {
            $category_crumb = $this->get_category_breadcrumb($page_info['category']);
            if ($category_crumb) {
                $breadcrumbs[] = $category_crumb;
            }
        }

        // Añadir módulo padre si existe
        if (!empty($page_info['parent_module'])) {
            $parent_crumb = $this->get_module_breadcrumb($page_info['parent_module']);
            if ($parent_crumb) {
                $breadcrumbs[] = $parent_crumb;
            }
        }

        // Añadir página actual
        $breadcrumbs[] = [
            'label'   => $page_info['label'],
            'url'     => null,
            'icon'    => $page_info['icon'],
            'color'   => $page_info['color'] ?? null,
            'current' => true,
        ];

        return $breadcrumbs;
    }

    /**
     * Obtener información de una página del shell
     *
     * @param string $page_slug
     * @return array
     */
    private function get_shell_page_info($page_slug) {
        if (isset($this->page_info_cache[$page_slug])) {
            return $this->page_info_cache[$page_slug];
        }

        $info = [
            'slug'          => $page_slug,
            'label'         => $this->format_page_label($page_slug),
            'icon'          => 'dashicons-admin-page',
            'color'         => null,
            'category'      => null,
            'module_id'     => null,
            'parent_module' => null,
        ];

        // Extraer módulo del slug
        $module_id = $this->extract_module_from_slug($page_slug);

        // Usar jerarquía de módulos si está disponible
        if ($module_id && function_exists('flavor_module_hierarchy')) {
            $hierarchy = flavor_module_hierarchy();
            $module_info = $hierarchy->get_module_info($module_id);

            if ($module_info) {
                $info['module_id'] = $module_id;
                $info['category'] = $module_info['category_id'];
                $info['parent_module'] = $module_info['parent'];
                $info['icon'] = $module_info['category_icon'];
                $info['color'] = $module_info['category_color'];
            }
        }

        // Obtener información del shell
        if (class_exists('Flavor_Admin_Shell')) {
            $shell = Flavor_Admin_Shell::get_instance();
            $navigation = $shell->get_navigation_structure();

            foreach ($navigation as $section_id => $section) {
                if (empty($section['items'])) {
                    continue;
                }

                foreach ($section['items'] as $item) {
                    if (isset($item['slug']) && $item['slug'] === $page_slug) {
                        $info['label'] = $item['label'] ?? $info['label'];
                        $info['icon'] = $item['icon'] ?? $info['icon'];
                        if (empty($info['category'])) {
                            $info['category'] = $section_id;
                        }
                        break 2;
                    }

                    // Buscar en subpáginas
                    if (!empty($item['subpages'])) {
                        foreach ($item['subpages'] as $subpage) {
                            if (isset($subpage['slug']) && $subpage['slug'] === $page_slug) {
                                $info['label'] = $subpage['label'] ?? $info['label'];
                                $info['icon'] = $subpage['icon'] ?? $item['icon'] ?? $info['icon'];
                                $info['parent_slug'] = $item['slug'];
                                $info['parent_label'] = $item['label'];
                                $info['parent_icon'] = $item['icon'] ?? null;
                                if (empty($info['category'])) {
                                    $info['category'] = $section_id;
                                }
                                break 3;
                            }
                        }
                    }
                }
            }
        }

        $this->page_info_cache[$page_slug] = $info;
        return $info;
    }

    /**
     * Extraer ID de módulo de un slug de página
     *
     * @param string $page_slug
     * @return string|null
     */
    private function extract_module_from_slug($page_slug) {
        $patterns = [
            '/^([a-z-]+)-dashboard$/',
            '/^([a-z-]+)-settings$/',
            '/^([a-z-]+)-config$/',
            '/^([a-z-]+)-list$/',
            '/^([a-z-]+)-nuevo$/',
            '/^([a-z-]+)-add$/',
            '/^gc-([a-z-]+)$/',
            '/^flavor-([a-z-]+)$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $page_slug, $matches)) {
                return $matches[1];
            }
        }

        return $page_slug;
    }

    /**
     * Obtener breadcrumb de categoría
     *
     * @param string $category_id
     * @return array|null
     */
    private function get_category_breadcrumb($category_id) {
        // Intentar obtener de jerarquía de módulos
        if (function_exists('flavor_module_hierarchy')) {
            $hierarchy = flavor_module_hierarchy();
            $category = $hierarchy->get_category($category_id);

            if ($category) {
                return [
                    'label' => $category['title'],
                    'url'   => null,
                    'icon'  => $category['icon'] ?? 'dashicons-category',
                    'color' => $category['color'] ?? null,
                ];
            }
        }

        // Fallback: obtener de navegación del shell
        if (class_exists('Flavor_Admin_Shell')) {
            $shell = Flavor_Admin_Shell::get_instance();
            $navigation = $shell->get_navigation_structure();

            if (isset($navigation[$category_id])) {
                return [
                    'label' => $navigation[$category_id]['label'] ?? ucfirst($category_id),
                    'url'   => null,
                    'icon'  => $navigation[$category_id]['icon'] ?? 'dashicons-category',
                ];
            }
        }

        return null;
    }

    /**
     * Obtener breadcrumb de módulo padre
     *
     * @param string $module_id
     * @return array|null
     */
    private function get_module_breadcrumb($module_id) {
        $parent_dashboard = $module_id . '-dashboard';
        $parent_info = $this->get_shell_page_info($parent_dashboard);

        if ($parent_info) {
            return [
                'label' => $parent_info['label'],
                'url'   => admin_url('admin.php?page=' . $parent_dashboard),
                'icon'  => $parent_info['icon'],
            ];
        }

        return null;
    }

    /**
     * Formatear label de página
     *
     * @param string $slug
     * @return string
     */
    private function format_page_label($slug) {
        $label = preg_replace('/^(flavor-|gc-)/', '', $slug);
        $label = preg_replace('/(-dashboard|-settings|-config|-list)$/', '', $label);
        $label = str_replace(['-', '_'], ' ', $label);
        $label = ucwords($label);
        return $label;
    }

    /**
     * Obtener breadcrumbs como array (para uso programático)
     *
     * @return array
     */
    public function get_breadcrumbs() {
        if ($this->is_shell_page() && $this->is_shell_active()) {
            return $this->build_shell_breadcrumbs();
        }
        return [];
    }
}

/**
 * Función helper para obtener la instancia
 *
 * @return Flavor_Admin_Breadcrumbs
 */
function flavor_admin_breadcrumbs() {
    return Flavor_Admin_Breadcrumbs::get_instance();
}
