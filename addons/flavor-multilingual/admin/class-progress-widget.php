<?php
/**
 * Widget de progreso de traducción para el dashboard de WordPress
 *
 * Muestra estadísticas rápidas de traducción en el dashboard de admin.
 *
 * @package FlavorMultilingual
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Progress_Widget {

    /**
     * Instancia singleton
     */
    private static $instance = null;

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
        add_action('wp_dashboard_setup', array($this, 'register_widget'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('wp_ajax_flavor_ml_refresh_progress', array($this, 'ajax_refresh_progress'));
    }

    /**
     * Registrar widget en dashboard
     */
    public function register_widget() {
        if (!current_user_can('flavor_translate')) {
            return;
        }

        wp_add_dashboard_widget(
            'flavor_ml_progress_widget',
            __('Progreso de Traducción', 'flavor-multilingual'),
            array($this, 'render_widget'),
            array($this, 'configure_widget')
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'index.php') {
            return;
        }

        wp_enqueue_style(
            'flavor-ml-progress-widget',
            FLAVOR_MULTILINGUAL_URL . 'assets/css/progress-widget.css',
            array(),
            FLAVOR_MULTILINGUAL_VERSION
        );
    }

    /**
     * Renderizar widget
     */
    public function render_widget() {
        $stats = $this->get_translation_stats();
        $languages = $this->get_language_progress();
        ?>
        <div class="flavor-ml-progress-widget">
            <!-- Resumen global -->
            <div class="progress-summary">
                <div class="progress-circle" data-progress="<?php echo esc_attr($stats['global_progress']); ?>">
                    <svg viewBox="0 0 36 36">
                        <path class="circle-bg" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="circle-progress" stroke-dasharray="<?php echo esc_attr($stats['global_progress']); ?>, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                    </svg>
                    <span class="progress-value"><?php echo esc_html($stats['global_progress']); ?>%</span>
                </div>
                <div class="progress-info">
                    <span class="progress-label"><?php _e('Progreso global', 'flavor-multilingual'); ?></span>
                    <span class="progress-detail">
                        <?php printf(
                            /* translators: %1$d: translated items, %2$d: total items */
                            __('%1$d de %2$d elementos', 'flavor-multilingual'),
                            $stats['translated_items'],
                            $stats['total_items']
                        ); ?>
                    </span>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <div class="quick-stats">
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['pending_count']); ?></span>
                    <span class="stat-label"><?php _e('Pendientes', 'flavor-multilingual'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['review_count']); ?></span>
                    <span class="stat-label"><?php _e('En revisión', 'flavor-multilingual'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($stats['published_today']); ?></span>
                    <span class="stat-label"><?php _e('Hoy', 'flavor-multilingual'); ?></span>
                </div>
            </div>

            <!-- Progreso por idioma -->
            <div class="language-progress">
                <h4><?php _e('Por idioma', 'flavor-multilingual'); ?></h4>
                <?php foreach ($languages as $code => $lang) : ?>
                    <div class="lang-row">
                        <div class="lang-info">
                            <?php if (!empty($lang['flag'])) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>" alt="" class="lang-flag">
                            <?php endif; ?>
                            <span class="lang-name"><?php echo esc_html($lang['native_name']); ?></span>
                        </div>
                        <div class="lang-bar">
                            <div class="bar-fill" style="width: <?php echo esc_attr($lang['progress']); ?>%"></div>
                        </div>
                        <span class="lang-percent"><?php echo esc_html($lang['progress']); ?>%</span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Acciones rápidas -->
            <div class="quick-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-multilingual-translations')); ?>" class="button">
                    <?php _e('Ver traducciones', 'flavor-multilingual'); ?>
                </a>
                <?php if ($stats['pending_count'] > 0) : ?>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-multilingual-translations&status=pending')); ?>" class="button button-primary">
                        <?php _e('Traducir pendientes', 'flavor-multilingual'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Configurar widget
     */
    public function configure_widget() {
        $options = get_option('flavor_ml_widget_options', array());

        if (isset($_POST['flavor_ml_widget_submit'])) {
            $options['show_languages'] = isset($_POST['flavor_ml_show_languages']) ? absint($_POST['flavor_ml_show_languages']) : 5;
            update_option('flavor_ml_widget_options', $options);
        }

        $show_languages = $options['show_languages'] ?? 5;
        ?>
        <p>
            <label for="flavor_ml_show_languages">
                <?php _e('Idiomas a mostrar:', 'flavor-multilingual'); ?>
            </label>
            <select id="flavor_ml_show_languages" name="flavor_ml_show_languages">
                <?php for ($i = 3; $i <= 10; $i++) : ?>
                    <option value="<?php echo $i; ?>" <?php selected($show_languages, $i); ?>><?php echo $i; ?></option>
                <?php endfor; ?>
            </select>
        </p>
        <input type="hidden" name="flavor_ml_widget_submit" value="1">
        <?php
    }

    /**
     * Obtener estadísticas de traducción
     */
    private function get_translation_stats() {
        global $wpdb;

        $cache_key = 'flavor_ml_progress_stats';
        $stats = wp_cache_get($cache_key, 'flavor_multilingual');

        if (false !== $stats) {
            return $stats;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        // Contar posts/páginas traducibles
        $translatable_types = apply_filters('flavor_ml_translatable_post_types', array('post', 'page'));
        $types_placeholder = implode("','", array_map('esc_sql', $translatable_types));

        $total_posts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type IN ('{$types_placeholder}')
             AND post_status = 'publish'"
        );

        // Total de traducciones posibles (posts x idiomas activos - 1)
        $num_target_languages = count($active_languages) - 1;
        $total_items = $total_posts * max(1, $num_target_languages);

        // Traducciones completadas
        $translations_table = $wpdb->prefix . 'flavor_translations';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$translations_table}'") === $translations_table;

        $translated_items = 0;
        $pending_count = 0;
        $review_count = 0;

        if ($table_exists) {
            $translated_items = $wpdb->get_var(
                "SELECT COUNT(DISTINCT CONCAT(object_id, '-', language_code))
                 FROM {$translations_table}
                 WHERE object_type = 'post'
                 AND field_name = 'title'
                 AND translation != ''"
            );

            // Estados
            $pending_count = $wpdb->get_var(
                "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
                 WHERE pm.meta_key LIKE '_flavor_ml_status_%'
                 AND pm.meta_value = 'pending'"
            ) ?: 0;

            $review_count = $wpdb->get_var(
                "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm
                 WHERE pm.meta_key LIKE '_flavor_ml_status_%'
                 AND pm.meta_value = 'needs_review'"
            ) ?: 0;
        }

        // Publicados hoy
        $today = date('Y-m-d');
        $published_today = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta}
             WHERE meta_key LIKE '_flavor_ml_published_%'
             AND meta_value LIKE %s",
            $today . '%'
        )) ?: 0;

        // Calcular progreso global
        $global_progress = $total_items > 0 ? round(($translated_items / $total_items) * 100) : 0;

        $stats = array(
            'total_items'      => $total_items,
            'translated_items' => $translated_items,
            'global_progress'  => min(100, $global_progress),
            'pending_count'    => $pending_count,
            'review_count'     => $review_count,
            'published_today'  => $published_today,
        );

        wp_cache_set($cache_key, $stats, 'flavor_multilingual', 300);

        return $stats;
    }

    /**
     * Obtener progreso por idioma
     */
    private function get_language_progress() {
        global $wpdb;

        $options = get_option('flavor_ml_widget_options', array());
        $max_languages = $options['show_languages'] ?? 5;

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $translations_table = $wpdb->prefix . 'flavor_translations';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$translations_table}'") === $translations_table;

        // Total de posts traducibles
        $total_posts = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type IN ('post', 'page')
             AND post_status = 'publish'"
        );

        $languages = array();
        $count = 0;

        foreach ($active_languages as $code => $lang) {
            if ($code === $default_lang) {
                continue;
            }

            if ($count >= $max_languages) {
                break;
            }

            $translated = 0;

            if ($table_exists) {
                $translated = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT object_id)
                     FROM {$translations_table}
                     WHERE object_type = 'post'
                     AND language_code = %s
                     AND field_name = 'title'
                     AND translation != ''",
                    $code
                ));
            }

            $progress = $total_posts > 0 ? round(($translated / $total_posts) * 100) : 0;

            $languages[$code] = array(
                'name'        => $lang['name'],
                'native_name' => $lang['native_name'],
                'flag'        => $lang['flag'] ?? '',
                'translated'  => $translated,
                'total'       => $total_posts,
                'progress'    => min(100, $progress),
            );

            $count++;
        }

        // Ordenar por progreso descendente
        uasort($languages, function($a, $b) {
            return $b['progress'] - $a['progress'];
        });

        return $languages;
    }

    /**
     * AJAX: Refrescar progreso
     */
    public function ajax_refresh_progress() {
        check_ajax_referer('flavor_ml_widget', 'nonce');

        // Limpiar caché
        wp_cache_delete('flavor_ml_progress_stats', 'flavor_multilingual');

        wp_send_json_success(array(
            'stats'     => $this->get_translation_stats(),
            'languages' => $this->get_language_progress(),
        ));
    }

    /**
     * Obtener progreso de un post específico
     */
    public function get_post_progress($post_id) {
        global $wpdb;

        $core = Flavor_Multilingual_Core::get_instance();
        $active_languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $translations_table = $wpdb->prefix . 'flavor_translations';

        $num_target_languages = count($active_languages) - 1;

        if ($num_target_languages <= 0) {
            return 100;
        }

        $translated_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT language_code)
             FROM {$translations_table}
             WHERE object_type = 'post'
             AND object_id = %d
             AND field_name = 'title'
             AND translation != ''",
            $post_id
        ));

        return round(($translated_count / $num_target_languages) * 100);
    }

    /**
     * Renderizar mini-badge de progreso (para listas de posts)
     */
    public function render_progress_badge($post_id) {
        $progress = $this->get_post_progress($post_id);

        $class = 'progress-low';
        if ($progress >= 100) {
            $class = 'progress-complete';
        } elseif ($progress >= 50) {
            $class = 'progress-medium';
        }

        return sprintf(
            '<span class="flavor-ml-progress-badge %s" title="%s">%d%%</span>',
            esc_attr($class),
            esc_attr__('Progreso de traducción', 'flavor-multilingual'),
            $progress
        );
    }
}
