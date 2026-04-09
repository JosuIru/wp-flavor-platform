<?php
/**
 * Breadcrumbs para el Admin de WordPress
 *
 * Muestra navegación de migas de pan en las páginas de edición
 * de los custom post types de Flavor.
 *
 * @package FlavorChatIA
 * @since 3.3.0
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
        </style>
        <?php
    }
}
