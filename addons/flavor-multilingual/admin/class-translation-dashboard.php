<?php
/**
 * Dashboard de estadísticas de traducción
 *
 * Panel de control con métricas sobre:
 * - Estado de traducciones por idioma
 * - Uso de traducción IA vs manual
 * - Rendimiento del caché
 * - Actividad de traductores
 * - Contenido pendiente de traducción
 *
 * @package FlavorMultilingual
 * @since 1.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Dashboard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Translation_Dashboard|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Translation_Dashboard
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
        add_action('admin_menu', array($this, 'add_dashboard_page'), 5);
        add_action('wp_ajax_flavor_ml_dashboard_stats', array($this, 'ajax_get_stats'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    /**
     * Añade la página del dashboard
     */
    public function add_dashboard_page() {
        add_submenu_page(
            'flavor-multilingual',
            __('Dashboard', 'flavor-multilingual'),
            __('Dashboard', 'flavor-multilingual'),
            'flavor_view_translation_stats',
            'flavor-ml-dashboard',
            array($this, 'render_dashboard'),
            0
        );
    }

    /**
     * Encola assets del dashboard
     *
     * @param string $hook Hook de la página actual
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'flavor-multilingual_page_flavor-ml-dashboard') {
            return;
        }

        // Chart.js para gráficos
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
            array(),
            '4.4.1',
            true
        );

        // Estilos inline
        wp_add_inline_style('flavor-multilingual-admin', $this->get_dashboard_styles());
    }

    /**
     * Renderiza el dashboard
     */
    public function render_dashboard() {
        $stats = $this->get_all_stats();
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        ?>
        <div class="wrap flavor-ml-dashboard">
            <h1><?php echo esc_html__('Dashboard de Traducciones', 'flavor-multilingual'); ?></h1>

            <!-- Resumen rápido -->
            <div class="flavor-ml-stats-cards">
                <div class="stats-card">
                    <div class="stats-icon" style="background: #0073aa;">
                        <span class="dashicons dashicons-translation"></span>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format_i18n($stats['total_translations']); ?></h3>
                        <p><?php echo esc_html__('Traducciones totales', 'flavor-multilingual'); ?></p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon" style="background: #5cb85c;">
                        <span class="dashicons dashicons-yes-alt"></span>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format_i18n($stats['published_translations']); ?></h3>
                        <p><?php echo esc_html__('Publicadas', 'flavor-multilingual'); ?></p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon" style="background: #f0ad4e;">
                        <span class="dashicons dashicons-clock"></span>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo number_format_i18n($stats['pending_translations']); ?></h3>
                        <p><?php echo esc_html__('Pendientes', 'flavor-multilingual'); ?></p>
                    </div>
                </div>

                <div class="stats-card">
                    <div class="stats-icon" style="background: #9b59b6;">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                    </div>
                    <div class="stats-content">
                        <h3><?php echo count($languages); ?></h3>
                        <p><?php echo esc_html__('Idiomas activos', 'flavor-multilingual'); ?></p>
                    </div>
                </div>
            </div>

            <!-- Gráficos principales -->
            <div class="flavor-ml-charts-row">
                <!-- Traducciones por idioma -->
                <div class="flavor-ml-chart-card">
                    <h2><?php echo esc_html__('Traducciones por idioma', 'flavor-multilingual'); ?></h2>
                    <canvas id="chart-by-language" height="200"></canvas>
                </div>

                <!-- IA vs Manual -->
                <div class="flavor-ml-chart-card">
                    <h2><?php echo esc_html__('Método de traducción', 'flavor-multilingual'); ?></h2>
                    <canvas id="chart-method" height="200"></canvas>
                </div>
            </div>

            <!-- Segunda fila -->
            <div class="flavor-ml-charts-row">
                <!-- Estado de traducciones -->
                <div class="flavor-ml-chart-card">
                    <h2><?php echo esc_html__('Estado de traducciones', 'flavor-multilingual'); ?></h2>
                    <canvas id="chart-status" height="200"></canvas>
                </div>

                <!-- Rendimiento de caché -->
                <div class="flavor-ml-chart-card">
                    <h2><?php echo esc_html__('Rendimiento de caché', 'flavor-multilingual'); ?></h2>
                    <div class="cache-stats">
                        <?php $cache_stats = $this->get_cache_stats(); ?>
                        <div class="cache-stat">
                            <span class="cache-value"><?php echo esc_html($cache_stats['hit_rate']); ?>%</span>
                            <span class="cache-label"><?php echo esc_html__('Hit Rate', 'flavor-multilingual'); ?></span>
                        </div>
                        <div class="cache-stat">
                            <span class="cache-value"><?php echo number_format_i18n($cache_stats['hits']); ?></span>
                            <span class="cache-label"><?php echo esc_html__('Hits', 'flavor-multilingual'); ?></span>
                        </div>
                        <div class="cache-stat">
                            <span class="cache-value"><?php echo number_format_i18n($cache_stats['misses']); ?></span>
                            <span class="cache-label"><?php echo esc_html__('Misses', 'flavor-multilingual'); ?></span>
                        </div>
                        <div class="cache-stat">
                            <span class="cache-value"><?php echo size_format($cache_stats['memory_size']); ?></span>
                            <span class="cache-label"><?php echo esc_html__('Memoria', 'flavor-multilingual'); ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contenido pendiente de traducción -->
            <div class="flavor-ml-pending-section">
                <h2><?php echo esc_html__('Contenido pendiente de traducción', 'flavor-multilingual'); ?></h2>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('Contenido', 'flavor-multilingual'); ?></th>
                            <th><?php echo esc_html__('Tipo', 'flavor-multilingual'); ?></th>
                            <th><?php echo esc_html__('Idiomas faltantes', 'flavor-multilingual'); ?></th>
                            <th><?php echo esc_html__('Acciones', 'flavor-multilingual'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $pending_content = $this->get_pending_content(10);
                        if (empty($pending_content)) :
                        ?>
                            <tr>
                                <td colspan="4" style="text-align: center;">
                                    <?php echo esc_html__('¡Todo el contenido está traducido!', 'flavor-multilingual'); ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ($pending_content as $item) : ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($item['title']); ?></strong>
                                    </td>
                                    <td><?php echo esc_html($item['type_label']); ?></td>
                                    <td>
                                        <?php foreach ($item['missing_langs'] as $lang_code) : ?>
                                            <span class="lang-badge"><?php echo esc_html(strtoupper($lang_code)); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($item['edit_url']); ?>" class="button button-small">
                                            <?php echo esc_html__('Traducir', 'flavor-multilingual'); ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Actividad reciente -->
            <div class="flavor-ml-activity-section">
                <h2><?php echo esc_html__('Actividad reciente', 'flavor-multilingual'); ?></h2>

                <ul class="activity-list">
                    <?php
                    $recent_activity = $this->get_recent_activity(10);
                    foreach ($recent_activity as $activity) :
                    ?>
                        <li class="activity-item">
                            <span class="activity-icon dashicons <?php echo esc_attr($activity['icon']); ?>"></span>
                            <span class="activity-text"><?php echo esc_html($activity['text']); ?></span>
                            <span class="activity-time"><?php echo esc_html($activity['time_ago']); ?></span>
                        </li>
                    <?php endforeach; ?>

                    <?php if (empty($recent_activity)) : ?>
                        <li class="activity-item">
                            <span class="activity-text"><?php echo esc_html__('No hay actividad reciente', 'flavor-multilingual'); ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Memoria de traducción -->
            <div class="flavor-ml-tm-section">
                <h2><?php echo esc_html__('Memoria de traducción', 'flavor-multilingual'); ?></h2>

                <?php $tm_stats = $this->get_tm_stats(); ?>
                <div class="tm-stats-grid">
                    <div class="tm-stat">
                        <span class="tm-value"><?php echo number_format_i18n($tm_stats['total_segments']); ?></span>
                        <span class="tm-label"><?php echo esc_html__('Segmentos', 'flavor-multilingual'); ?></span>
                    </div>
                    <div class="tm-stat">
                        <span class="tm-value"><?php echo number_format_i18n($tm_stats['glossary_terms']); ?></span>
                        <span class="tm-label"><?php echo esc_html__('Términos glosario', 'flavor-multilingual'); ?></span>
                    </div>
                    <div class="tm-stat">
                        <span class="tm-value"><?php echo number_format_i18n($tm_stats['reused_count']); ?></span>
                        <span class="tm-label"><?php echo esc_html__('Reutilizaciones', 'flavor-multilingual'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Datos para los gráficos
            var langData = <?php echo json_encode($stats['by_language']); ?>;
            var methodData = <?php echo json_encode($stats['by_method']); ?>;
            var statusData = <?php echo json_encode($stats['by_status']); ?>;

            // Gráfico por idioma
            new Chart(document.getElementById('chart-by-language'), {
                type: 'bar',
                data: {
                    labels: Object.keys(langData),
                    datasets: [{
                        label: '<?php echo esc_js(__('Traducciones', 'flavor-multilingual')); ?>',
                        data: Object.values(langData),
                        backgroundColor: '#0073aa'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });

            // Gráfico método
            new Chart(document.getElementById('chart-method'), {
                type: 'doughnut',
                data: {
                    labels: ['<?php echo esc_js(__('IA', 'flavor-multilingual')); ?>', '<?php echo esc_js(__('Manual', 'flavor-multilingual')); ?>'],
                    datasets: [{
                        data: [methodData.ai, methodData.manual],
                        backgroundColor: ['#9b59b6', '#3498db']
                    }]
                },
                options: { responsive: true }
            });

            // Gráfico estados
            new Chart(document.getElementById('chart-status'), {
                type: 'pie',
                data: {
                    labels: Object.keys(statusData),
                    datasets: [{
                        data: Object.values(statusData),
                        backgroundColor: ['#f0ad4e', '#5bc0de', '#9b59b6', '#5cb85c', '#d9534f', '#0073aa']
                    }]
                },
                options: { responsive: true }
            });
        });
        </script>
        <?php
    }

    /**
     * Obtiene todas las estadísticas
     *
     * @return array
     */
    private function get_all_stats() {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_translations';

        // Total de traducciones
        $total = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");

        // Publicadas
        $published = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status = 'published'"
        );

        // Pendientes
        $pending = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE status IN ('pending', 'draft')"
        );

        // Por idioma
        $by_language_raw = $wpdb->get_results(
            "SELECT language_code, COUNT(*) as count FROM {$table} GROUP BY language_code",
            ARRAY_A
        );
        $by_language = array();
        foreach ($by_language_raw as $row) {
            $by_language[strtoupper($row['language_code'])] = (int) $row['count'];
        }

        // Por método (IA vs manual)
        $ai_count = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE is_auto_translated = 1"
        );
        $manual_count = $total - $ai_count;

        // Por estado
        $by_status_raw = $wpdb->get_results(
            "SELECT status, COUNT(*) as count FROM {$table} GROUP BY status",
            ARRAY_A
        );
        $by_status = array();
        foreach ($by_status_raw as $row) {
            $status_label = ucfirst($row['status'] ?: 'draft');
            $by_status[$status_label] = (int) $row['count'];
        }

        return array(
            'total_translations'     => $total,
            'published_translations' => $published,
            'pending_translations'   => $pending,
            'by_language'            => $by_language,
            'by_method'              => array(
                'ai'     => $ai_count,
                'manual' => $manual_count,
            ),
            'by_status'              => $by_status,
        );
    }

    /**
     * Obtiene estadísticas de caché
     *
     * @return array
     */
    private function get_cache_stats() {
        if (class_exists('Flavor_Translation_Cache')) {
            $cache = Flavor_Translation_Cache::get_instance();
            $stats = $cache->get_stats();

            return array(
                'hits'        => $stats['hits'],
                'misses'      => $stats['misses'],
                'hit_rate'    => $stats['hit_rate'],
                'memory_size' => $cache->get_memory_size(),
            );
        }

        return array(
            'hits'        => 0,
            'misses'      => 0,
            'hit_rate'    => 0,
            'memory_size' => 0,
        );
    }

    /**
     * Obtiene estadísticas de memoria de traducción
     *
     * @return array
     */
    private function get_tm_stats() {
        global $wpdb;

        $tm_table = $wpdb->prefix . 'flavor_translation_memory';
        $glossary_table = $wpdb->prefix . 'flavor_glossary';

        $total_segments = 0;
        $glossary_terms = 0;
        $reused_count = 0;

        // Verificar si las tablas existen
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tm_table}'") === $tm_table) {
            $total_segments = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tm_table}");
            $reused_count = (int) $wpdb->get_var("SELECT SUM(use_count) FROM {$tm_table}");
        }

        if ($wpdb->get_var("SHOW TABLES LIKE '{$glossary_table}'") === $glossary_table) {
            $glossary_terms = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$glossary_table}");
        }

        return array(
            'total_segments' => $total_segments,
            'glossary_terms' => $glossary_terms,
            'reused_count'   => $reused_count,
        );
    }

    /**
     * Obtiene contenido pendiente de traducción
     *
     * @param int $limit Límite de resultados
     * @return array
     */
    private function get_pending_content($limit = 10) {
        global $wpdb;

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $target_langs = array_diff(array_keys($languages), array($default_lang));

        if (empty($target_langs)) {
            return array();
        }

        $table = $wpdb->prefix . 'flavor_translations';

        // Obtener posts recientes
        $posts = get_posts(array(
            'post_type'      => array('post', 'page'),
            'post_status'    => 'publish',
            'posts_per_page' => $limit * 2,
            'orderby'        => 'modified',
            'order'          => 'DESC',
        ));

        $pending = array();

        foreach ($posts as $post) {
            // Verificar qué idiomas faltan
            $existing = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT language_code FROM {$table}
                 WHERE object_type = 'post' AND object_id = %d AND field_name = 'title'",
                $post->ID
            ));

            $missing = array_diff($target_langs, $existing);

            if (!empty($missing)) {
                $pending[] = array(
                    'id'            => $post->ID,
                    'title'         => $post->post_title,
                    'type'          => $post->post_type,
                    'type_label'    => get_post_type_object($post->post_type)->labels->singular_name,
                    'missing_langs' => $missing,
                    'edit_url'      => admin_url('post.php?post=' . $post->ID . '&action=edit'),
                );

                if (count($pending) >= $limit) {
                    break;
                }
            }
        }

        return $pending;
    }

    /**
     * Obtiene actividad reciente
     *
     * @param int $limit Límite de resultados
     * @return array
     */
    private function get_recent_activity($limit = 10) {
        global $wpdb;
        $table = $wpdb->prefix . 'flavor_translations';

        $recent = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, p.post_title
             FROM {$table} t
             LEFT JOIN {$wpdb->posts} p ON t.object_id = p.ID AND t.object_type = 'post'
             ORDER BY t.updated_at DESC
             LIMIT %d",
            $limit
        ));

        $activity = array();

        foreach ($recent as $item) {
            $time_diff = human_time_diff(strtotime($item->updated_at), current_time('timestamp'));

            $activity[] = array(
                'icon'     => $item->is_auto_translated ? 'dashicons-admin-site-alt3' : 'dashicons-edit',
                'text'     => sprintf(
                    __('%s traducido a %s', 'flavor-multilingual'),
                    $item->post_title ?: __('Contenido', 'flavor-multilingual'),
                    strtoupper($item->language_code)
                ),
                'time_ago' => sprintf(__('hace %s', 'flavor-multilingual'), $time_diff),
            );
        }

        return $activity;
    }

    /**
     * AJAX: Obtener estadísticas
     */
    public function ajax_get_stats() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('flavor_view_translation_stats')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        wp_send_json_success($this->get_all_stats());
    }

    /**
     * Estilos del dashboard
     *
     * @return string
     */
    private function get_dashboard_styles() {
        return '
        .flavor-ml-dashboard { max-width: 1400px; }

        .flavor-ml-stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .stats-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .stats-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stats-icon .dashicons {
            color: #fff;
            font-size: 24px;
            width: 24px;
            height: 24px;
        }

        .stats-content h3 {
            margin: 0;
            font-size: 28px;
            line-height: 1;
        }

        .stats-content p {
            margin: 5px 0 0;
            color: #666;
        }

        .flavor-ml-charts-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }

        .flavor-ml-chart-card {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }

        .flavor-ml-chart-card h2 {
            margin-top: 0;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .cache-stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            padding: 20px 0;
        }

        .cache-stat {
            text-align: center;
        }

        .cache-value {
            display: block;
            font-size: 24px;
            font-weight: bold;
            color: #0073aa;
        }

        .cache-label {
            display: block;
            color: #666;
            font-size: 12px;
            margin-top: 5px;
        }

        .flavor-ml-pending-section,
        .flavor-ml-activity-section,
        .flavor-ml-tm-section {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }

        .lang-badge {
            display: inline-block;
            padding: 2px 8px;
            background: #f0f0f0;
            border-radius: 3px;
            font-size: 11px;
            font-weight: 600;
            margin-right: 4px;
        }

        .activity-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }

        .activity-icon {
            margin-right: 10px;
            color: #0073aa;
        }

        .activity-text {
            flex: 1;
        }

        .activity-time {
            color: #999;
            font-size: 12px;
        }

        .tm-stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            padding: 20px 0;
        }

        .tm-stat {
            text-align: center;
        }

        .tm-value {
            display: block;
            font-size: 32px;
            font-weight: bold;
            color: #9b59b6;
        }

        .tm-label {
            display: block;
            color: #666;
            margin-top: 5px;
        }
        ';
    }
}
