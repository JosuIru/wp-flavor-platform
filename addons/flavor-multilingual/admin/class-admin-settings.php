<?php
/**
 * Página de configuración del sistema multilingüe
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Multilingual_Admin_Settings {

    /**
     * Instancia singleton
     *
     * @var Flavor_Multilingual_Admin_Settings|null
     */
    private static $instance = null;

    /**
     * Slug del menú
     *
     * @var string
     */
    private $menu_slug = 'flavor-multilingual';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Multilingual_Admin_Settings
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_ml_save_language', array($this, 'ajax_save_language'));
        add_action('wp_ajax_flavor_ml_delete_language', array($this, 'ajax_delete_language'));
        add_action('wp_ajax_flavor_ml_reorder_languages', array($this, 'ajax_reorder_languages'));
        add_action('wp_ajax_flavor_ml_translate_post', array($this, 'ajax_translate_post'));

        // AJAX handlers para editor de traducciones
        add_action('wp_ajax_flavor_ml_get_post_data', array($this, 'ajax_get_post_data'));
        add_action('wp_ajax_flavor_ml_get_translation', array($this, 'ajax_get_translation'));
        add_action('wp_ajax_flavor_ml_save_full_translation', array($this, 'ajax_save_full_translation'));
        add_action('wp_ajax_flavor_ml_translate_all_languages', array($this, 'ajax_translate_all_languages'));
    }

    /**
     * Añade el menú de administración
     */
    public function add_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Multiidioma', 'flavor-multilingual'),
            __('Multiidioma', 'flavor-multilingual'),
            'manage_options',
            $this->menu_slug,
            array($this, 'render_page')
        );
    }

    /**
     * Registra la configuración
     */
    public function register_settings() {
        register_setting('flavor_multilingual', 'flavor_multilingual_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));

        // Sección General
        add_settings_section(
            'flavor_ml_general',
            __('Configuración General', 'flavor-multilingual'),
            array($this, 'render_section_general'),
            $this->menu_slug
        );

        // Campo: Idioma por defecto
        add_settings_field(
            'default_language',
            __('Idioma por defecto', 'flavor-multilingual'),
            array($this, 'render_field_default_language'),
            $this->menu_slug,
            'flavor_ml_general'
        );

        // Campo: Modo de URL
        add_settings_field(
            'url_mode',
            __('Formato de URL', 'flavor-multilingual'),
            array($this, 'render_field_url_mode'),
            $this->menu_slug,
            'flavor_ml_general'
        );

        // Campo: Mostrar idioma por defecto en URL
        add_settings_field(
            'show_default_in_url',
            __('Mostrar idioma por defecto en URL', 'flavor-multilingual'),
            array($this, 'render_field_show_default'),
            $this->menu_slug,
            'flavor_ml_general'
        );

        // Sección Detección
        add_settings_section(
            'flavor_ml_detection',
            __('Detección de Idioma', 'flavor-multilingual'),
            array($this, 'render_section_detection'),
            $this->menu_slug
        );

        // Campo: Auto-detectar navegador
        add_settings_field(
            'auto_detect_browser',
            __('Detectar idioma del navegador', 'flavor-multilingual'),
            array($this, 'render_field_auto_detect'),
            $this->menu_slug,
            'flavor_ml_detection'
        );

        // Campo: Recordar preferencia
        add_settings_field(
            'remember_user_lang',
            __('Recordar preferencia del usuario', 'flavor-multilingual'),
            array($this, 'render_field_remember'),
            $this->menu_slug,
            'flavor_ml_detection'
        );

        // Sección SEO
        add_settings_section(
            'flavor_ml_seo',
            __('SEO', 'flavor-multilingual'),
            array($this, 'render_section_seo'),
            $this->menu_slug
        );

        // Campo: Añadir hreflang
        add_settings_field(
            'add_hreflang',
            __('Añadir etiquetas hreflang', 'flavor-multilingual'),
            array($this, 'render_field_hreflang'),
            $this->menu_slug,
            'flavor_ml_seo'
        );

        // Sección IA
        add_settings_section(
            'flavor_ml_ai',
            __('Traducción con IA', 'flavor-multilingual'),
            array($this, 'render_section_ai'),
            $this->menu_slug
        );

        // Campo: Motor de IA
        add_settings_field(
            'ai_engine',
            __('Motor de IA', 'flavor-multilingual'),
            array($this, 'render_field_ai_engine'),
            $this->menu_slug,
            'flavor_ml_ai'
        );
    }

    /**
     * Encola assets
     *
     * @param string $hook Hook actual
     */
    public function enqueue_assets($hook) {
        if (strpos($hook, $this->menu_slug) === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-multilingual-admin',
            FLAVOR_MULTILINGUAL_URL . 'admin/css/admin.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );

        wp_enqueue_script(
            'flavor-multilingual-admin',
            FLAVOR_MULTILINGUAL_URL . 'admin/js/admin.js',
            array('jquery', 'jquery-ui-sortable'),
            FLAVOR_MULTILINGUAL_VERSION,
            true
        );

        wp_localize_script('flavor-multilingual-admin', 'flavorML', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_multilingual'),
            'i18n'    => array(
                'confirmDelete' => __('¿Eliminar este idioma? Se perderán todas las traducciones asociadas.', 'flavor-multilingual'),
                'saving'        => __('Guardando...', 'flavor-multilingual'),
                'saved'         => __('Guardado', 'flavor-multilingual'),
                'error'         => __('Error al guardar', 'flavor-multilingual'),
                'translating'   => __('Traduciendo...', 'flavor-multilingual'),
            ),
        ));
    }

    /**
     * Renderiza la página principal
     */
    public function render_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'settings';
        $tabs = array(
            'settings'     => __('Configuración', 'flavor-multilingual'),
            'languages'    => __('Idiomas', 'flavor-multilingual'),
            'translations' => __('Traducciones', 'flavor-multilingual'),
            'strings'      => __('Cadenas', 'flavor-multilingual'),
            'import_export' => __('Importar/Exportar', 'flavor-multilingual'),
            'stats'        => __('Estadísticas', 'flavor-multilingual'),
        );

        ?>
        <div class="wrap flavor-multilingual-admin">
            <h1><?php esc_html_e('Flavor Multiidioma', 'flavor-multilingual'); ?></h1>

            <?php $this->render_compatibility_notice(); ?>

            <nav class="nav-tab-wrapper">
                <?php foreach ($tabs as $tab_id => $tab_name) : ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_id)); ?>"
                       class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($tab_name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="tab-content">
                <?php
                switch ($active_tab) {
                    case 'languages':
                        $this->render_languages_tab();
                        break;
                    case 'translations':
                        $this->render_translations_tab();
                        break;
                    case 'strings':
                        $this->render_strings_tab();
                        break;
                    case 'import_export':
                        $this->render_import_export_tab();
                        break;
                    case 'stats':
                        $this->render_stats_tab();
                        break;
                    default:
                        $this->render_settings_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza aviso de compatibilidad
     */
    private function render_compatibility_notice() {
        $compat = Flavor_Multilingual_Compatibility::get_instance();

        if ($compat->has_external_multilingual()) {
            $plugin_name = $compat->get_plugin_display_name();
            ?>
            <div class="notice notice-info">
                <p>
                    <strong><?php esc_html_e('Plugin multilingüe detectado:', 'flavor-multilingual'); ?></strong>
                    <?php echo esc_html($plugin_name); ?>
                    -
                    <?php esc_html_e('Flavor Multilingual funcionará en modo puente, sincronizando con el plugin existente.', 'flavor-multilingual'); ?>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Renderiza la pestaña de configuración
     */
    private function render_settings_tab() {
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('flavor_multilingual');
            do_settings_sections($this->menu_slug);
            submit_button();
            ?>
        </form>
        <?php
    }

    /**
     * Renderiza la pestaña de idiomas
     */
    private function render_languages_tab() {
        $manager = Flavor_Language_Manager::get_instance();
        $languages = $manager->get_all();
        $available = $manager->get_available_to_add();

        ?>
        <div class="flavor-ml-languages">
            <h2><?php esc_html_e('Idiomas Activos', 'flavor-multilingual'); ?></h2>

            <table class="wp-list-table widefat fixed striped" id="flavor-ml-languages-table">
                <thead>
                    <tr>
                        <th class="column-order" style="width: 30px;"></th>
                        <th class="column-flag" style="width: 50px;"><?php esc_html_e('Bandera', 'flavor-multilingual'); ?></th>
                        <th class="column-code" style="width: 60px;"><?php esc_html_e('Código', 'flavor-multilingual'); ?></th>
                        <th class="column-name"><?php esc_html_e('Nombre', 'flavor-multilingual'); ?></th>
                        <th class="column-native"><?php esc_html_e('Nombre Nativo', 'flavor-multilingual'); ?></th>
                        <th class="column-status" style="width: 100px;"><?php esc_html_e('Estado', 'flavor-multilingual'); ?></th>
                        <th class="column-actions" style="width: 150px;"><?php esc_html_e('Acciones', 'flavor-multilingual'); ?></th>
                    </tr>
                </thead>
                <tbody id="flavor-ml-languages-list">
                    <?php foreach ($languages as $lang) : ?>
                        <tr data-code="<?php echo esc_attr($lang['code']); ?>">
                            <td class="column-order">
                                <span class="dashicons dashicons-menu flavor-ml-drag-handle"></span>
                            </td>
                            <td class="column-flag">
                                <?php if ($lang['flag']) : ?>
                                    <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                         alt="<?php echo esc_attr($lang['name']); ?>"
                                         width="24" height="16">
                                <?php endif; ?>
                            </td>
                            <td class="column-code">
                                <strong><?php echo esc_html($lang['code']); ?></strong>
                            </td>
                            <td class="column-name"><?php echo esc_html($lang['name']); ?></td>
                            <td class="column-native"><?php echo esc_html($lang['native_name']); ?></td>
                            <td class="column-status">
                                <?php if ($lang['is_default']) : ?>
                                    <span class="flavor-ml-badge flavor-ml-badge-primary">
                                        <?php esc_html_e('Por defecto', 'flavor-multilingual'); ?>
                                    </span>
                                <?php elseif ($lang['is_active']) : ?>
                                    <span class="flavor-ml-badge flavor-ml-badge-success">
                                        <?php esc_html_e('Activo', 'flavor-multilingual'); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="flavor-ml-badge flavor-ml-badge-secondary">
                                        <?php esc_html_e('Inactivo', 'flavor-multilingual'); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <?php if (!$lang['is_default']) : ?>
                                    <button type="button" class="button button-small flavor-ml-toggle-active"
                                            data-code="<?php echo esc_attr($lang['code']); ?>"
                                            data-active="<?php echo $lang['is_active'] ? '1' : '0'; ?>">
                                        <?php echo $lang['is_active']
                                            ? esc_html__('Desactivar', 'flavor-multilingual')
                                            : esc_html__('Activar', 'flavor-multilingual'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete flavor-ml-delete"
                                            data-code="<?php echo esc_attr($lang['code']); ?>">
                                        <?php esc_html_e('Eliminar', 'flavor-multilingual'); ?>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if (!empty($available)) : ?>
                <h3><?php esc_html_e('Añadir Idioma', 'flavor-multilingual'); ?></h3>
                <div class="flavor-ml-add-language">
                    <select id="flavor-ml-add-language-select">
                        <option value=""><?php esc_html_e('Seleccionar idioma...', 'flavor-multilingual'); ?></option>
                        <?php foreach ($available as $code => $lang) : ?>
                            <option value="<?php echo esc_attr($code); ?>">
                                <?php echo esc_html($lang['name'] . ' (' . $lang['native_name'] . ')'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="button" class="button button-primary" id="flavor-ml-add-language-btn">
                        <?php esc_html_e('Añadir', 'flavor-multilingual'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de estadísticas
     */
    private function render_stats_tab() {
        $storage = Flavor_Translation_Storage::get_instance();
        $stats = $storage->get_translation_stats();
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();

        ?>
        <div class="flavor-ml-stats">
            <h2><?php esc_html_e('Estadísticas de Traducción', 'flavor-multilingual'); ?></h2>

            <div class="flavor-ml-stats-grid">
                <?php foreach ($languages as $code => $lang) : ?>
                    <?php
                    $lang_stats = $stats[$code] ?? array('total' => 0, 'published' => 0, 'draft' => 0);
                    ?>
                    <div class="flavor-ml-stat-card">
                        <div class="flavor-ml-stat-header">
                            <?php if ($lang['flag']) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                     alt="" width="24" height="16">
                            <?php endif; ?>
                            <span><?php echo esc_html($lang['name']); ?></span>
                        </div>
                        <div class="flavor-ml-stat-body">
                            <div class="flavor-ml-stat-number">
                                <?php echo esc_html($lang_stats['total']); ?>
                            </div>
                            <div class="flavor-ml-stat-label">
                                <?php esc_html_e('Traducciones totales', 'flavor-multilingual'); ?>
                            </div>
                            <div class="flavor-ml-stat-breakdown">
                                <span class="published">
                                    <?php
                                    printf(
                                        esc_html__('%d publicadas', 'flavor-multilingual'),
                                        $lang_stats['published'] ?? 0
                                    );
                                    ?>
                                </span>
                                <span class="draft">
                                    <?php
                                    printf(
                                        esc_html__('%d borradores', 'flavor-multilingual'),
                                        $lang_stats['draft'] ?? 0
                                    );
                                    ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <h3><?php esc_html_e('Contenido sin traducir', 'flavor-multilingual'); ?></h3>
            <?php
            $default_lang = $core->get_default_language();
            foreach ($languages as $code => $lang) {
                if ($code === $default_lang) {
                    continue;
                }

                $untranslated = $storage->get_untranslated_content($code, 10);
                if (empty($untranslated)) {
                    continue;
                }

                echo '<h4>' . esc_html($lang['name']) . '</h4>';
                echo '<ul class="flavor-ml-untranslated-list">';
                foreach ($untranslated as $item) {
                    printf(
                        '<li><a href="%s">%s</a> <span class="post-type">(%s)</span></li>',
                        esc_url(get_edit_post_link($item['ID'])),
                        esc_html($item['post_title']),
                        esc_html($item['post_type'])
                    );
                }
                echo '</ul>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de traducciones (editor completo)
     */
    private function render_translations_tab() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        // Obtener filtros
        $filter_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : '';
        $filter_lang = isset($_GET['lang']) ? sanitize_key($_GET['lang']) : '';
        $filter_status = isset($_GET['status']) ? sanitize_key($_GET['status']) : '';
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;

        // Obtener CPTs traducibles
        $translatable_types = $this->get_translatable_post_types();

        ?>
        <div class="flavor-ml-translations">
            <h2><?php esc_html_e('Editor de Traducciones', 'flavor-multilingual'); ?></h2>

            <!-- Filtros -->
            <div class="flavor-ml-filters">
                <form method="get" action="">
                    <input type="hidden" name="page" value="flavor-multilingual">
                    <input type="hidden" name="tab" value="translations">

                    <select name="post_type">
                        <option value=""><?php esc_html_e('Todos los tipos', 'flavor-multilingual'); ?></option>
                        <?php foreach ($translatable_types as $type_slug => $type_obj) : ?>
                            <option value="<?php echo esc_attr($type_slug); ?>" <?php selected($filter_type, $type_slug); ?>>
                                <?php echo esc_html($type_obj->labels->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="lang">
                        <option value=""><?php esc_html_e('Todos los idiomas', 'flavor-multilingual'); ?></option>
                        <?php foreach ($languages as $code => $lang) : ?>
                            <?php if ($code === $default_lang) continue; ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($filter_lang, $code); ?>>
                                <?php echo esc_html($lang['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select name="status">
                        <option value=""><?php esc_html_e('Todos los estados', 'flavor-multilingual'); ?></option>
                        <option value="translated" <?php selected($filter_status, 'translated'); ?>>
                            <?php esc_html_e('Traducidos', 'flavor-multilingual'); ?>
                        </option>
                        <option value="untranslated" <?php selected($filter_status, 'untranslated'); ?>>
                            <?php esc_html_e('Sin traducir', 'flavor-multilingual'); ?>
                        </option>
                        <option value="auto" <?php selected($filter_status, 'auto'); ?>>
                            <?php esc_html_e('Traducción automática', 'flavor-multilingual'); ?>
                        </option>
                    </select>

                    <input type="search" name="s" value="<?php echo esc_attr($search); ?>"
                           placeholder="<?php esc_attr_e('Buscar...', 'flavor-multilingual'); ?>">

                    <button type="submit" class="button"><?php esc_html_e('Filtrar', 'flavor-multilingual'); ?></button>
                </form>
            </div>

            <!-- Tabla de contenidos traducibles -->
            <table class="wp-list-table widefat fixed striped" id="flavor-ml-content-table">
                <thead>
                    <tr>
                        <th class="column-title"><?php esc_html_e('Título', 'flavor-multilingual'); ?></th>
                        <th class="column-type" style="width: 120px;"><?php esc_html_e('Tipo', 'flavor-multilingual'); ?></th>
                        <?php foreach ($languages as $code => $lang) : ?>
                            <?php if ($code === $default_lang) continue; ?>
                            <th class="column-lang" style="width: 80px; text-align: center;">
                                <?php if ($lang['flag']) : ?>
                                    <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                         alt="<?php echo esc_attr($lang['name']); ?>" width="20" height="13"
                                         title="<?php echo esc_attr($lang['name']); ?>">
                                <?php else : ?>
                                    <?php echo esc_html(strtoupper($code)); ?>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <th class="column-actions" style="width: 150px;"><?php esc_html_e('Acciones', 'flavor-multilingual'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $posts = $this->get_translatable_content(array(
                        'post_type' => $filter_type,
                        'lang'      => $filter_lang,
                        'status'    => $filter_status,
                        'search'    => $search,
                        'paged'     => $paged,
                    ));

                    if (empty($posts['items'])) :
                    ?>
                        <tr>
                            <td colspan="<?php echo 3 + count($languages) - 1; ?>">
                                <?php esc_html_e('No se encontraron contenidos.', 'flavor-multilingual'); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($posts['items'] as $post) : ?>
                            <tr data-post-id="<?php echo esc_attr($post->ID); ?>">
                                <td class="column-title">
                                    <strong>
                                        <a href="<?php echo esc_url(get_edit_post_link($post->ID)); ?>">
                                            <?php echo esc_html($post->post_title); ?>
                                        </a>
                                    </strong>
                                </td>
                                <td class="column-type">
                                    <?php
                                    $type_obj = get_post_type_object($post->post_type);
                                    echo esc_html($type_obj ? $type_obj->labels->singular_name : $post->post_type);
                                    ?>
                                </td>
                                <?php
                                $storage = Flavor_Translation_Storage::get_instance();
                                $translations = $storage->get_all_translations('post', $post->ID);

                                foreach ($languages as $code => $lang) :
                                    if ($code === $default_lang) continue;
                                    $has_trans = isset($translations[$code]['title']);
                                    $is_auto = $has_trans && ($translations[$code]['title']['auto'] ?? false);
                                ?>
                                    <td class="column-lang" style="text-align: center;">
                                        <?php if ($has_trans) : ?>
                                            <span class="dashicons dashicons-yes-alt" style="color: <?php echo $is_auto ? '#f0ad4e' : '#46b450'; ?>"
                                                  title="<?php echo $is_auto ? esc_attr__('Traducción automática', 'flavor-multilingual') : esc_attr__('Traducido', 'flavor-multilingual'); ?>"></span>
                                        <?php else : ?>
                                            <span class="dashicons dashicons-minus" style="color: #ccc;"
                                                  title="<?php esc_attr_e('Sin traducir', 'flavor-multilingual'); ?>"></span>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td class="column-actions">
                                    <button type="button" class="button button-small flavor-ml-edit-all-translations"
                                            data-post-id="<?php echo esc_attr($post->ID); ?>">
                                        <?php esc_html_e('Editar', 'flavor-multilingual'); ?>
                                    </button>
                                    <button type="button" class="button button-small flavor-ml-translate-all-langs"
                                            data-post-id="<?php echo esc_attr($post->ID); ?>">
                                        <span class="dashicons dashicons-translation" style="margin-top: 3px;"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if (!empty($posts['items'])) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $paged,
                            'total'   => $posts['pages'],
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal de edición completa -->
        <div id="flavor-ml-full-editor-modal" class="flavor-ml-modal flavor-ml-modal-large" style="display: none;">
            <div class="flavor-ml-modal-content">
                <div class="flavor-ml-modal-header">
                    <h3><?php esc_html_e('Editor de Traducciones', 'flavor-multilingual'); ?></h3>
                    <button type="button" class="flavor-ml-modal-close">&times;</button>
                </div>
                <div class="flavor-ml-modal-body">
                    <div class="flavor-ml-editor-layout">
                        <div class="flavor-ml-editor-sidebar">
                            <h4><?php esc_html_e('Idiomas', 'flavor-multilingual'); ?></h4>
                            <ul class="flavor-ml-lang-tabs">
                                <?php foreach ($languages as $code => $lang) : ?>
                                    <?php if ($code === $default_lang) continue; ?>
                                    <li>
                                        <a href="#" data-lang="<?php echo esc_attr($code); ?>">
                                            <?php if ($lang['flag']) : ?>
                                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                                     width="20" height="13">
                                            <?php endif; ?>
                                            <?php echo esc_html($lang['name']); ?>
                                        </a>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="flavor-ml-editor-main">
                            <div class="flavor-ml-editor-columns">
                                <div class="flavor-ml-editor-original">
                                    <h4><?php esc_html_e('Original', 'flavor-multilingual'); ?></h4>
                                    <div class="flavor-ml-field">
                                        <label><?php esc_html_e('Título', 'flavor-multilingual'); ?></label>
                                        <div id="flavor-ml-original-title" class="flavor-ml-original-text"></div>
                                    </div>
                                    <div class="flavor-ml-field">
                                        <label><?php esc_html_e('Contenido', 'flavor-multilingual'); ?></label>
                                        <div id="flavor-ml-original-content" class="flavor-ml-original-text flavor-ml-content-preview"></div>
                                    </div>
                                    <div class="flavor-ml-field">
                                        <label><?php esc_html_e('Extracto', 'flavor-multilingual'); ?></label>
                                        <div id="flavor-ml-original-excerpt" class="flavor-ml-original-text"></div>
                                    </div>
                                </div>
                                <div class="flavor-ml-editor-translation">
                                    <h4><?php esc_html_e('Traducción', 'flavor-multilingual'); ?> - <span id="flavor-ml-current-lang-name"></span></h4>
                                    <input type="hidden" id="flavor-ml-editor-post-id">
                                    <input type="hidden" id="flavor-ml-editor-lang">

                                    <div class="flavor-ml-field">
                                        <label for="flavor-ml-trans-title"><?php esc_html_e('Título', 'flavor-multilingual'); ?></label>
                                        <input type="text" id="flavor-ml-trans-title" class="widefat">
                                    </div>
                                    <div class="flavor-ml-field">
                                        <label for="flavor-ml-trans-content"><?php esc_html_e('Contenido', 'flavor-multilingual'); ?></label>
                                        <?php
                                        wp_editor('', 'flavor-ml-trans-content', array(
                                            'textarea_rows' => 15,
                                            'media_buttons' => true,
                                            'teeny'         => false,
                                        ));
                                        ?>
                                    </div>
                                    <div class="flavor-ml-field">
                                        <label for="flavor-ml-trans-excerpt"><?php esc_html_e('Extracto', 'flavor-multilingual'); ?></label>
                                        <textarea id="flavor-ml-trans-excerpt" class="widefat" rows="3"></textarea>
                                    </div>
                                    <div class="flavor-ml-field">
                                        <label><?php esc_html_e('Estado', 'flavor-multilingual'); ?></label>
                                        <select id="flavor-ml-trans-status">
                                            <option value="draft"><?php esc_html_e('Borrador', 'flavor-multilingual'); ?></option>
                                            <option value="published"><?php esc_html_e('Publicada', 'flavor-multilingual'); ?></option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flavor-ml-modal-footer">
                    <button type="button" class="button" id="flavor-ml-editor-cancel">
                        <?php esc_html_e('Cancelar', 'flavor-multilingual'); ?>
                    </button>
                    <button type="button" class="button flavor-ml-translate-ai-btn" id="flavor-ml-editor-ai-translate">
                        <span class="dashicons dashicons-translation"></span>
                        <?php esc_html_e('Traducir con IA', 'flavor-multilingual'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="flavor-ml-editor-save">
                        <?php esc_html_e('Guardar traducción', 'flavor-multilingual'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de cadenas (strings)
     */
    private function render_strings_tab() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $string_manager = Flavor_String_Manager::get_instance();
        $domains = $string_manager->get_available_domains();

        ?>
        <div class="flavor-ml-strings">
            <h2><?php esc_html_e('Traducción de Cadenas', 'flavor-multilingual'); ?></h2>

            <p class="description">
                <?php esc_html_e('Traduce textos estáticos de la interfaz del tema y plugins.', 'flavor-multilingual'); ?>
            </p>

            <!-- Acciones -->
            <div class="flavor-ml-strings-actions">
                <button type="button" class="button button-primary" id="flavor-ml-scan-strings">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Escanear cadenas', 'flavor-multilingual'); ?>
                </button>
                <button type="button" class="button" id="flavor-ml-translate-all-strings">
                    <span class="dashicons dashicons-translation"></span>
                    <?php esc_html_e('Traducir todas con IA', 'flavor-multilingual'); ?>
                </button>
            </div>

            <!-- Filtros -->
            <div class="flavor-ml-strings-filters">
                <select id="flavor-ml-strings-domain">
                    <option value=""><?php esc_html_e('Todos los dominios', 'flavor-multilingual'); ?></option>
                    <?php foreach ($domains as $domain) : ?>
                        <option value="<?php echo esc_attr($domain); ?>"><?php echo esc_html($domain); ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="flavor-ml-strings-lang">
                    <?php foreach ($languages as $code => $lang) : ?>
                        <?php if ($code === $default_lang) continue; ?>
                        <option value="<?php echo esc_attr($code); ?>">
                            <?php echo esc_html($lang['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select id="flavor-ml-strings-status">
                    <option value=""><?php esc_html_e('Todos', 'flavor-multilingual'); ?></option>
                    <option value="translated"><?php esc_html_e('Traducidas', 'flavor-multilingual'); ?></option>
                    <option value="untranslated"><?php esc_html_e('Sin traducir', 'flavor-multilingual'); ?></option>
                </select>

                <input type="search" id="flavor-ml-strings-search"
                       placeholder="<?php esc_attr_e('Buscar cadenas...', 'flavor-multilingual'); ?>">

                <button type="button" class="button" id="flavor-ml-strings-filter">
                    <?php esc_html_e('Filtrar', 'flavor-multilingual'); ?>
                </button>
            </div>

            <!-- Tabla de cadenas -->
            <table class="wp-list-table widefat fixed striped" id="flavor-ml-strings-table">
                <thead>
                    <tr>
                        <th class="column-original"><?php esc_html_e('Texto Original', 'flavor-multilingual'); ?></th>
                        <th class="column-translation"><?php esc_html_e('Traducción', 'flavor-multilingual'); ?></th>
                        <th class="column-domain" style="width: 150px;"><?php esc_html_e('Dominio', 'flavor-multilingual'); ?></th>
                        <th class="column-actions" style="width: 120px;"><?php esc_html_e('Acciones', 'flavor-multilingual'); ?></th>
                    </tr>
                </thead>
                <tbody id="flavor-ml-strings-list">
                    <tr>
                        <td colspan="4" class="flavor-ml-loading">
                            <?php esc_html_e('Cargando cadenas...', 'flavor-multilingual'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="flavor-ml-strings-pagination" id="flavor-ml-strings-pagination"></div>
        </div>
        <?php
    }

    /**
     * Obtiene los tipos de post traducibles
     *
     * @return array
     */
    private function get_translatable_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');

        // Filtrar tipos excluidos
        $excluded = apply_filters('flavor_multilingual_excluded_post_types', array(
            'attachment',
            'revision',
            'nav_menu_item',
            'custom_css',
            'customize_changeset',
            'oembed_cache',
            'user_request',
            'wp_block',
            'wp_template',
            'wp_template_part',
            'wp_global_styles',
            'wp_navigation',
        ));

        foreach ($excluded as $type) {
            unset($post_types[$type]);
        }

        return apply_filters('flavor_multilingual_translatable_post_types', $post_types);
    }

    /**
     * Obtiene contenido traducible con filtros
     *
     * @param array $args Argumentos
     * @return array
     */
    private function get_translatable_content($args = array()) {
        $defaults = array(
            'post_type' => '',
            'lang'      => '',
            'status'    => '',
            'search'    => '',
            'paged'     => 1,
            'per_page'  => 20,
        );

        $args = wp_parse_args($args, $defaults);

        $query_args = array(
            'post_status'    => 'publish',
            'posts_per_page' => $args['per_page'],
            'paged'          => $args['paged'],
            'orderby'        => 'modified',
            'order'          => 'DESC',
        );

        // Tipo de post
        if (!empty($args['post_type'])) {
            $query_args['post_type'] = $args['post_type'];
        } else {
            $query_args['post_type'] = array_keys($this->get_translatable_post_types());
        }

        // Búsqueda
        if (!empty($args['search'])) {
            $query_args['s'] = $args['search'];
        }

        $query = new WP_Query($query_args);

        // Filtrar por estado de traducción si es necesario
        $items = $query->posts;

        if (!empty($args['lang']) && !empty($args['status'])) {
            $storage = Flavor_Translation_Storage::get_instance();
            $filtered = array();

            foreach ($items as $post) {
                $translations = $storage->get_all_translations('post', $post->ID);
                $has_trans = isset($translations[$args['lang']]['title']);
                $is_auto = $has_trans && ($translations[$args['lang']]['title']['auto'] ?? false);

                if ($args['status'] === 'translated' && $has_trans) {
                    $filtered[] = $post;
                } elseif ($args['status'] === 'untranslated' && !$has_trans) {
                    $filtered[] = $post;
                } elseif ($args['status'] === 'auto' && $is_auto) {
                    $filtered[] = $post;
                }
            }

            $items = $filtered;
        }

        return array(
            'items' => $items,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages,
        );
    }

    // --- Campos de configuración ---

    /**
     * Renderiza sección general
     */
    public function render_section_general() {
        echo '<p>' . esc_html__('Configura las opciones principales del sistema multilingüe.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Renderiza sección detección
     */
    public function render_section_detection() {
        echo '<p>' . esc_html__('Configura cómo se detecta el idioma del visitante.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Renderiza sección SEO
     */
    public function render_section_seo() {
        echo '<p>' . esc_html__('Opciones de SEO para sitios multilingües.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Renderiza sección IA
     */
    public function render_section_ai() {
        echo '<p>' . esc_html__('Configura la traducción automática con inteligencia artificial.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Campo: Idioma por defecto
     */
    public function render_field_default_language() {
        $manager = Flavor_Language_Manager::get_instance();
        $languages = $manager->get_all(true);
        $current = Flavor_Multilingual::get_option('default_language', 'es');

        echo '<select name="flavor_multilingual_settings[default_language]">';
        foreach ($languages as $lang) {
            printf(
                '<option value="%s" %s>%s (%s)</option>',
                esc_attr($lang['code']),
                selected($current, $lang['code'], false),
                esc_html($lang['name']),
                esc_html($lang['code'])
            );
        }
        echo '</select>';
    }

    /**
     * Campo: Modo de URL
     */
    public function render_field_url_mode() {
        $current = Flavor_Multilingual::get_option('url_mode', 'parameter');
        $modes = array(
            'parameter' => __('Parámetro (?lang=es)', 'flavor-multilingual'),
            'directory' => __('Directorio (/es/pagina)', 'flavor-multilingual'),
            'subdomain' => __('Subdominio (es.dominio.com)', 'flavor-multilingual'),
        );

        foreach ($modes as $value => $label) {
            printf(
                '<label><input type="radio" name="flavor_multilingual_settings[url_mode]" value="%s" %s> %s</label><br>',
                esc_attr($value),
                checked($current, $value, false),
                esc_html($label)
            );
        }
        echo '<p class="description">' . esc_html__('Formato de las URLs multilingües. El modo subdominio requiere configuración DNS adicional.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Campo: Mostrar idioma por defecto en URL
     */
    public function render_field_show_default() {
        $checked = Flavor_Multilingual::get_option('show_default_in_url', false);
        printf(
            '<label><input type="checkbox" name="flavor_multilingual_settings[show_default_in_url]" value="1" %s> %s</label>',
            checked($checked, true, false),
            esc_html__('Incluir el código del idioma por defecto en las URLs', 'flavor-multilingual')
        );
    }

    /**
     * Campo: Auto-detectar navegador
     */
    public function render_field_auto_detect() {
        $checked = Flavor_Multilingual::get_option('auto_detect_browser', true);
        printf(
            '<label><input type="checkbox" name="flavor_multilingual_settings[auto_detect_browser]" value="1" %s> %s</label>',
            checked($checked, true, false),
            esc_html__('Detectar automáticamente el idioma preferido del navegador', 'flavor-multilingual')
        );
    }

    /**
     * Campo: Recordar preferencia
     */
    public function render_field_remember() {
        $checked = Flavor_Multilingual::get_option('remember_user_lang', true);
        printf(
            '<label><input type="checkbox" name="flavor_multilingual_settings[remember_user_lang]" value="1" %s> %s</label>',
            checked($checked, true, false),
            esc_html__('Guardar la preferencia de idioma del usuario en una cookie', 'flavor-multilingual')
        );
    }

    /**
     * Campo: hreflang
     */
    public function render_field_hreflang() {
        $checked = Flavor_Multilingual::get_option('add_hreflang', true);
        printf(
            '<label><input type="checkbox" name="flavor_multilingual_settings[add_hreflang]" value="1" %s> %s</label>',
            checked($checked, true, false),
            esc_html__('Añadir etiquetas hreflang automáticamente para SEO', 'flavor-multilingual')
        );
    }

    /**
     * Campo: Motor de IA
     */
    public function render_field_ai_engine() {
        $current = Flavor_Multilingual::get_option('ai_engine', 'claude');
        $engines = array(
            'claude'   => 'Claude (Anthropic)',
            'openai'   => 'OpenAI GPT',
            'deepseek' => 'DeepSeek',
            'mistral'  => 'Mistral',
        );

        echo '<select name="flavor_multilingual_settings[ai_engine]">';
        foreach ($engines as $value => $label) {
            printf(
                '<option value="%s" %s>%s</option>',
                esc_attr($value),
                selected($current, $value, false),
                esc_html($label)
            );
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Motor de IA a usar para traducciones automáticas. Usa la configuración de API del plugin principal.', 'flavor-multilingual') . '</p>';
    }

    /**
     * Sanitiza la configuración
     *
     * @param array $input Datos de entrada
     * @return array
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        $sanitized['default_language'] = sanitize_key($input['default_language'] ?? 'es');
        $sanitized['url_mode'] = in_array($input['url_mode'] ?? '', array('parameter', 'directory', 'subdomain'))
            ? $input['url_mode']
            : 'parameter';
        $sanitized['show_default_in_url'] = !empty($input['show_default_in_url']);
        $sanitized['auto_detect_browser'] = !empty($input['auto_detect_browser']);
        $sanitized['remember_user_lang'] = !empty($input['remember_user_lang']);
        $sanitized['add_hreflang'] = !empty($input['add_hreflang']);
        $sanitized['ai_engine'] = sanitize_key($input['ai_engine'] ?? 'claude');

        // Actualizar idioma por defecto en la tabla
        if (!empty($sanitized['default_language'])) {
            $manager = Flavor_Language_Manager::get_instance();
            $manager->set_default($sanitized['default_language']);
        }

        // Limpiar reglas de rewrite si cambió el modo de URL
        $old_settings = get_option('flavor_multilingual_settings', array());
        if (($old_settings['url_mode'] ?? '') !== $sanitized['url_mode']) {
            flush_rewrite_rules();
        }

        return $sanitized;
    }

    // --- AJAX Handlers ---

    /**
     * AJAX: Guardar idioma
     */
    public function ajax_save_language() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $code = sanitize_key($_POST['code'] ?? '');
        $action_type = sanitize_key($_POST['action_type'] ?? 'add');

        $manager = Flavor_Language_Manager::get_instance();

        if ($action_type === 'add') {
            // Añadir nuevo idioma desde los predefinidos
            $available = Flavor_Multilingual::$default_languages;
            if (!isset($available[$code])) {
                wp_send_json_error(__('Idioma no válido', 'flavor-multilingual'));
            }

            $lang = $available[$code];
            $result = $manager->add(array(
                'code'        => $code,
                'locale'      => $lang['locale'],
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'],
                'is_rtl'      => $lang['rtl'] ?? false,
                'is_active'   => true,
            ));

            if ($result) {
                wp_send_json_success(array('message' => __('Idioma añadido', 'flavor-multilingual')));
            } else {
                wp_send_json_error(__('Error al añadir idioma', 'flavor-multilingual'));
            }
        } elseif ($action_type === 'toggle') {
            $active = !empty($_POST['active']);
            $result = $manager->set_active($code, $active);

            if ($result) {
                wp_send_json_success(array('message' => __('Estado actualizado', 'flavor-multilingual')));
            } else {
                wp_send_json_error(__('Error al actualizar estado', 'flavor-multilingual'));
            }
        }

        wp_send_json_error(__('Acción no válida', 'flavor-multilingual'));
    }

    /**
     * AJAX: Eliminar idioma
     */
    public function ajax_delete_language() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $code = sanitize_key($_POST['code'] ?? '');

        $manager = Flavor_Language_Manager::get_instance();
        $result = $manager->delete($code);

        if ($result) {
            wp_send_json_success(array('message' => __('Idioma eliminado', 'flavor-multilingual')));
        } else {
            wp_send_json_error(__('No se puede eliminar el idioma por defecto', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Reordenar idiomas
     */
    public function ajax_reorder_languages() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $order = isset($_POST['order']) ? array_map('sanitize_key', $_POST['order']) : array();

        $manager = Flavor_Language_Manager::get_instance();
        $manager->reorder($order);

        wp_send_json_success();
    }

    /**
     * AJAX: Traducir post con IA
     */
    public function ajax_translate_post() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $to_lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$to_lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $translator = Flavor_AI_Translator::get_instance();
        $translations = $translator->translate_post($post_id, $to_lang);

        if (is_wp_error($translations)) {
            wp_send_json_error($translations->get_error_message());
        }

        // Guardar traducciones
        $storage = Flavor_Translation_Storage::get_instance();
        foreach ($translations as $field => $value) {
            $storage->save_translation('post', $post_id, $to_lang, $field, $value, array(
                'auto'   => true,
                'status' => 'draft',
            ));
        }

        wp_send_json_success(array(
            'message'      => __('Traducción completada', 'flavor-multilingual'),
            'translations' => $translations,
        ));
    }

    /**
     * AJAX: Obtener datos del post original
     */
    public function ajax_get_post_data() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(__('Post no especificado', 'flavor-multilingual'));
        }

        $post = get_post($post_id);

        if (!$post) {
            wp_send_json_error(__('Post no encontrado', 'flavor-multilingual'));
        }

        wp_send_json_success(array(
            'title'   => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
        ));
    }

    /**
     * AJAX: Obtener traducción de un post
     */
    public function ajax_get_translation() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros incompletos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translations = $storage->get_all_translations('post', $post_id);

        $lang_trans = $translations[$lang] ?? array();

        wp_send_json_success(array(
            'title'   => $lang_trans['title']['value'] ?? '',
            'content' => $lang_trans['content']['value'] ?? '',
            'excerpt' => $lang_trans['excerpt']['value'] ?? '',
            'status'  => $lang_trans['title']['status'] ?? 'draft',
        ));
    }

    /**
     * AJAX: Guardar traducción completa
     */
    public function ajax_save_full_translation() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $title = sanitize_text_field($_POST['title'] ?? '');
        $content = wp_kses_post($_POST['content'] ?? '');
        $excerpt = sanitize_textarea_field($_POST['excerpt'] ?? '');
        $status = sanitize_key($_POST['status'] ?? 'draft');

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros incompletos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $meta = array('status' => $status, 'auto' => false);

        // Guardar cada campo
        if (!empty($title)) {
            $storage->save_translation('post', $post_id, $lang, 'title', $title, $meta);
        }

        if (!empty($content)) {
            $storage->save_translation('post', $post_id, $lang, 'content', $content, $meta);
        }

        if (!empty($excerpt)) {
            $storage->save_translation('post', $post_id, $lang, 'excerpt', $excerpt, $meta);
        }

        wp_send_json_success(array(
            'message' => __('Traducción guardada', 'flavor-multilingual'),
        ));
    }

    /**
     * Renderiza la pestaña de Importar/Exportar
     */
    private function render_import_export_tab() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        ?>
        <div class="flavor-ml-import-export">
            <div class="flavor-ml-ie-grid">
                <!-- Exportar -->
                <div class="flavor-ml-ie-section">
                    <h2><?php esc_html_e('📤 Exportar Traducciones', 'flavor-multilingual'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Exporta tus traducciones a archivos PO/MO para usar con herramientas de traducción profesional o como backup.', 'flavor-multilingual'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Idioma', 'flavor-multilingual'); ?></th>
                            <td>
                                <select id="flavor-ml-export-lang">
                                    <?php foreach ($languages as $code => $lang) : ?>
                                        <?php if ($code === $default_lang) continue; ?>
                                        <option value="<?php echo esc_attr($code); ?>">
                                            <?php echo esc_html($lang['native_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Contenido', 'flavor-multilingual'); ?></th>
                            <td>
                                <select id="flavor-ml-export-type">
                                    <option value="all"><?php esc_html_e('Todo', 'flavor-multilingual'); ?></option>
                                    <option value="posts"><?php esc_html_e('Solo posts/páginas', 'flavor-multilingual'); ?></option>
                                    <option value="strings"><?php esc_html_e('Solo cadenas', 'flavor-multilingual'); ?></option>
                                    <option value="terms"><?php esc_html_e('Solo taxonomías', 'flavor-multilingual'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Incluir vacíos', 'flavor-multilingual'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" id="flavor-ml-export-empty" checked>
                                    <?php esc_html_e('Incluir cadenas sin traducir (para traducir externamente)', 'flavor-multilingual'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button button-primary" id="flavor-ml-export-po">
                            📄 <?php esc_html_e('Exportar PO', 'flavor-multilingual'); ?>
                        </button>
                        <button type="button" class="button" id="flavor-ml-export-mo">
                            📦 <?php esc_html_e('Exportar MO', 'flavor-multilingual'); ?>
                        </button>
                    </p>
                </div>

                <!-- Importar -->
                <div class="flavor-ml-ie-section">
                    <h2><?php esc_html_e('📥 Importar Traducciones', 'flavor-multilingual'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Importa traducciones desde archivos PO. También puedes traducir el archivo completo con IA.', 'flavor-multilingual'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th><?php esc_html_e('Idioma destino', 'flavor-multilingual'); ?></th>
                            <td>
                                <select id="flavor-ml-import-lang">
                                    <?php foreach ($languages as $code => $lang) : ?>
                                        <?php if ($code === $default_lang) continue; ?>
                                        <option value="<?php echo esc_attr($code); ?>">
                                            <?php echo esc_html($lang['native_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th><?php esc_html_e('Archivo PO', 'flavor-multilingual'); ?></th>
                            <td>
                                <input type="file" id="flavor-ml-import-file" accept=".po,.pot">
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <button type="button" class="button button-primary" id="flavor-ml-import-po">
                            📥 <?php esc_html_e('Importar PO', 'flavor-multilingual'); ?>
                        </button>
                        <button type="button" class="button button-secondary" id="flavor-ml-translate-po-ai">
                            🤖 <?php esc_html_e('Traducir PO con IA', 'flavor-multilingual'); ?>
                        </button>
                    </p>
                </div>
            </div>

            <!-- Traducción masiva con IA -->
            <div class="flavor-ml-ie-section flavor-ml-ie-full">
                <h2><?php esc_html_e('🤖 Traducción Masiva con IA', 'flavor-multilingual'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Traduce todo el contenido pendiente de un idioma a otro con inteligencia artificial.', 'flavor-multilingual'); ?>
                </p>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Idioma destino', 'flavor-multilingual'); ?></th>
                        <td>
                            <select id="flavor-ml-bulk-lang">
                                <?php foreach ($languages as $code => $lang) : ?>
                                    <?php if ($code === $default_lang) continue; ?>
                                    <option value="<?php echo esc_attr($code); ?>">
                                        <?php echo esc_html($lang['native_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><?php esc_html_e('Qué traducir', 'flavor-multilingual'); ?></th>
                        <td>
                            <label><input type="checkbox" name="bulk_posts" checked> <?php esc_html_e('Posts y páginas', 'flavor-multilingual'); ?></label><br>
                            <label><input type="checkbox" name="bulk_terms" checked> <?php esc_html_e('Categorías y etiquetas', 'flavor-multilingual'); ?></label><br>
                            <label><input type="checkbox" name="bulk_menus" checked> <?php esc_html_e('Menús', 'flavor-multilingual'); ?></label><br>
                            <label><input type="checkbox" name="bulk_strings"> <?php esc_html_e('Cadenas del tema/plugins', 'flavor-multilingual'); ?></label>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="button" class="button button-primary button-hero" id="flavor-ml-bulk-translate">
                        🚀 <?php esc_html_e('Iniciar Traducción Masiva', 'flavor-multilingual'); ?>
                    </button>
                </p>

                <div class="flavor-ml-bulk-progress" style="display: none;">
                    <div class="flavor-ml-progress-bar">
                        <div class="flavor-ml-progress-fill"></div>
                    </div>
                    <p class="flavor-ml-progress-text"></p>
                </div>
            </div>
        </div>

        <style>
            .flavor-ml-ie-grid {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-bottom: 30px;
            }
            .flavor-ml-ie-section {
                background: #fff;
                border: 1px solid #c3c4c7;
                padding: 20px;
                border-radius: 4px;
            }
            .flavor-ml-ie-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .flavor-ml-ie-full {
                grid-column: 1 / -1;
            }
            .flavor-ml-bulk-progress {
                margin-top: 20px;
                padding: 20px;
                background: #f0f0f1;
                border-radius: 4px;
            }
            .flavor-ml-progress-bar {
                height: 20px;
                background: #ddd;
                border-radius: 10px;
                overflow: hidden;
            }
            .flavor-ml-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #2271b1, #135e96);
                width: 0%;
                transition: width 0.3s;
            }
            .flavor-ml-progress-text {
                text-align: center;
                margin-top: 10px;
                font-weight: 500;
            }
            @media (max-width: 782px) {
                .flavor-ml-ie-grid {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        <?php
    }

    /**
     * AJAX: Traducir post a todos los idiomas
     */
    public function ajax_translate_all_languages() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);

        if (!$post_id) {
            wp_send_json_error(__('Post no especificado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();

        $translated_count = 0;

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            // Verificar si ya tiene traducción
            $existing = $storage->get_translation('post', $post_id, $code, 'title');
            if ($existing) {
                continue;
            }

            $translations = $translator->translate_post($post_id, $code);

            if (!is_wp_error($translations)) {
                foreach ($translations as $field => $value) {
                    $storage->save_translation('post', $post_id, $code, $field, $value, array(
                        'auto'   => true,
                        'status' => 'draft',
                    ));
                }
                $translated_count++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Traducido a %d idiomas', 'flavor-multilingual'), $translated_count),
            'count'   => $translated_count,
        ));
    }
}
