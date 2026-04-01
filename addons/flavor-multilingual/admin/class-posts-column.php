<?php
/**
 * Columna de traducción en lista de posts
 *
 * Añade una columna que muestra el progreso de traducción en las listas de posts.
 *
 * @package FlavorMultilingual
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Posts_Column {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tipos de post con columna
     */
    private $post_types = array();

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'init'));
    }

    /**
     * Inicializar
     */
    public function init() {
        $this->post_types = apply_filters('flavor_ml_translatable_post_types', array('post', 'page'));

        foreach ($this->post_types as $post_type) {
            add_filter("manage_{$post_type}_posts_columns", array($this, 'add_column'));
            add_action("manage_{$post_type}_posts_custom_column", array($this, 'render_column'), 10, 2);
            add_filter("manage_edit-{$post_type}_sortable_columns", array($this, 'sortable_columns'));
        }

        // Ordenar por progreso de traducción
        add_action('pre_get_posts', array($this, 'orderby_translation'));

        // Filtro por estado de traducción
        add_action('restrict_manage_posts', array($this, 'add_filter_dropdown'));
        add_action('pre_get_posts', array($this, 'filter_by_translation_status'));

        // CSS inline para la columna
        add_action('admin_head', array($this, 'column_styles'));
    }

    /**
     * Añadir columna
     */
    public function add_column($columns) {
        $new_columns = array();

        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;

            // Insertar después de título
            if ($key === 'title') {
                $new_columns['flavor_translation'] = '<span class="dashicons dashicons-translation" title="' . esc_attr__('Traducción', 'flavor-multilingual') . '"></span>';
            }
        }

        return $new_columns;
    }

    /**
     * Renderizar columna
     */
    public function render_column($column, $post_id) {
        if ($column !== 'flavor_translation') {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $languages_html = array();

        foreach ($active_languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            $status = $this->get_translation_status($post_id, $code);
            $has_translation = $this->has_translation($post_id, $code);

            $class = 'lang-indicator';
            $title = $lang['native_name'] . ': ';

            if ($has_translation) {
                switch ($status) {
                    case 'published':
                        $class .= ' status-published';
                        $title .= __('Publicada', 'flavor-multilingual');
                        $icon = '✓';
                        break;
                    case 'approved':
                        $class .= ' status-approved';
                        $title .= __('Aprobada', 'flavor-multilingual');
                        $icon = '✓';
                        break;
                    case 'needs_review':
                        $class .= ' status-review';
                        $title .= __('En revisión', 'flavor-multilingual');
                        $icon = '⏳';
                        break;
                    case 'in_progress':
                        $class .= ' status-progress';
                        $title .= __('En progreso', 'flavor-multilingual');
                        $icon = '✎';
                        break;
                    default:
                        $class .= ' status-draft';
                        $title .= __('Borrador', 'flavor-multilingual');
                        $icon = '◐';
                }
            } else {
                $class .= ' status-none';
                $title .= __('Sin traducir', 'flavor-multilingual');
                $icon = '○';
            }

            $edit_url = admin_url('admin.php?page=flavor-multilingual-translate&post_id=' . $post_id . '&lang=' . $code);

            $languages_html[] = sprintf(
                '<a href="%s" class="%s" title="%s">%s<span class="lang-code">%s</span></a>',
                esc_url($edit_url),
                esc_attr($class),
                esc_attr($title),
                $icon,
                strtoupper($code)
            );
        }

        echo '<div class="flavor-ml-lang-status">' . implode('', $languages_html) . '</div>';
    }

    /**
     * Columnas ordenables
     */
    public function sortable_columns($columns) {
        $columns['flavor_translation'] = 'flavor_translation';
        return $columns;
    }

    /**
     * Ordenar por traducción
     */
    public function orderby_translation($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        if ($query->get('orderby') !== 'flavor_translation') {
            return;
        }

        // Ordenar por meta de estado
        $query->set('meta_key', '_flavor_ml_translation_progress');
        $query->set('orderby', 'meta_value_num');
    }

    /**
     * Añadir dropdown de filtro
     */
    public function add_filter_dropdown($post_type) {
        if (!in_array($post_type, $this->post_types)) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $selected_lang = isset($_GET['flavor_ml_lang']) ? sanitize_text_field($_GET['flavor_ml_lang']) : '';
        $selected_status = isset($_GET['flavor_ml_status']) ? sanitize_text_field($_GET['flavor_ml_status']) : '';
        ?>
        <select name="flavor_ml_lang" class="flavor-ml-filter">
            <option value=""><?php _e('Todos los idiomas', 'flavor-multilingual'); ?></option>
            <?php foreach ($active_languages as $code => $lang) :
                if ($code === $default_lang) continue;
            ?>
                <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_lang, $code); ?>>
                    <?php echo esc_html($lang['native_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="flavor_ml_status" class="flavor-ml-filter">
            <option value=""><?php _e('Todos los estados', 'flavor-multilingual'); ?></option>
            <option value="none" <?php selected($selected_status, 'none'); ?>><?php _e('Sin traducir', 'flavor-multilingual'); ?></option>
            <option value="pending" <?php selected($selected_status, 'pending'); ?>><?php _e('Pendiente', 'flavor-multilingual'); ?></option>
            <option value="in_progress" <?php selected($selected_status, 'in_progress'); ?>><?php _e('En progreso', 'flavor-multilingual'); ?></option>
            <option value="needs_review" <?php selected($selected_status, 'needs_review'); ?>><?php _e('En revisión', 'flavor-multilingual'); ?></option>
            <option value="published" <?php selected($selected_status, 'published'); ?>><?php _e('Publicada', 'flavor-multilingual'); ?></option>
        </select>
        <?php
    }

    /**
     * Filtrar por estado de traducción
     */
    public function filter_by_translation_status($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }

        $post_type = $query->get('post_type');
        if (!in_array($post_type, $this->post_types)) {
            return;
        }

        $lang = isset($_GET['flavor_ml_lang']) ? sanitize_text_field($_GET['flavor_ml_lang']) : '';
        $status = isset($_GET['flavor_ml_status']) ? sanitize_text_field($_GET['flavor_ml_status']) : '';

        if (empty($lang) && empty($status)) {
            return;
        }

        $meta_query = $query->get('meta_query') ?: array();

        if (!empty($lang) && !empty($status)) {
            if ($status === 'none') {
                // Posts sin traducción en este idioma
                $meta_query[] = array(
                    'relation' => 'OR',
                    array(
                        'key'     => '_flavor_ml_status_' . $lang,
                        'compare' => 'NOT EXISTS',
                    ),
                    array(
                        'key'     => '_flavor_ml_status_' . $lang,
                        'value'   => '',
                        'compare' => '=',
                    ),
                );
            } else {
                $meta_query[] = array(
                    'key'   => '_flavor_ml_status_' . $lang,
                    'value' => $status,
                );
            }
        } elseif (!empty($status) && $status !== 'none') {
            // Filtrar por estado en cualquier idioma
            $meta_query[] = array(
                'key'     => '_flavor_ml_status_%',
                'value'   => $status,
                'compare' => 'LIKE',
            );
        }

        if (!empty($meta_query)) {
            $query->set('meta_query', $meta_query);
        }
    }

    /**
     * Verificar si tiene traducción
     */
    private function has_translation($post_id, $lang) {
        global $wpdb;

        $translations_table = $wpdb->prefix . 'flavor_translations';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$translations_table}'") === $translations_table;

        if (!$table_exists) {
            return false;
        }

        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$translations_table}
             WHERE object_type = 'post'
             AND object_id = %d
             AND language_code = %s
             AND field_name = 'title'
             AND translation != ''",
            $post_id,
            $lang
        ));

        return $count > 0;
    }

    /**
     * Obtener estado de traducción
     */
    private function get_translation_status($post_id, $lang) {
        return get_post_meta($post_id, '_flavor_ml_status_' . $lang, true) ?: 'pending';
    }

    /**
     * Estilos CSS
     */
    public function column_styles() {
        $screen = get_current_screen();

        if (!$screen || !in_array($screen->post_type, $this->post_types)) {
            return;
        }
        ?>
        <style>
            .column-flavor_translation {
                width: 100px;
                text-align: center;
            }
            .flavor-ml-lang-status {
                display: flex;
                gap: 4px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .lang-indicator {
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                padding: 2px 4px;
                border-radius: 3px;
                font-size: 10px;
                text-decoration: none;
                line-height: 1;
                transition: all 0.15s;
            }
            .lang-indicator:hover {
                transform: scale(1.1);
            }
            .lang-indicator .lang-code {
                font-size: 8px;
                text-transform: uppercase;
                margin-top: 1px;
            }
            .lang-indicator.status-published {
                background: #d1fae5;
                color: #065f46;
            }
            .lang-indicator.status-approved {
                background: #dbeafe;
                color: #1e40af;
            }
            .lang-indicator.status-review {
                background: #fef3c7;
                color: #92400e;
            }
            .lang-indicator.status-progress {
                background: #e0e7ff;
                color: #4338ca;
            }
            .lang-indicator.status-draft {
                background: #f3f4f6;
                color: #6b7280;
            }
            .lang-indicator.status-none {
                background: #fee2e2;
                color: #991b1b;
            }
            .flavor-ml-filter {
                margin-right: 6px;
            }
        </style>
        <?php
    }
}
