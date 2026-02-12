<?php
/**
 * Admin Pages Manager V2
 *
 * Página de administración mejorada para gestionar páginas con nuevos componentes
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Pages_Admin_V2 {

    private static $instance = null;

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_post_flavor_create_pages_v2', [$this, 'handle_create_pages']);
        add_action('admin_post_flavor_migrate_pages', [$this, 'handle_migrate_pages']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets($hook) {
        if ($hook !== 'flavor_landing_page_flavor-create-pages') {
            return;
        }

        wp_enqueue_style('flavor-pages-admin', FLAVOR_CHAT_IA_URL . 'admin/css/pages-admin.css', [], FLAVOR_CHAT_IA_VERSION);
    }

    /**
     * Renderiza la página mejorada
     */
    public function render_admin_page() {
        // Obtener estado desde Page Creator V2
        $pages_v2 = $this->get_v2_pages_status();
        $pages_old = $this->get_old_pages_status();

        ?>
        <div class="wrap flavor-pages-admin">
            <h1>
                <?php _e('Gestión de Páginas', 'flavor-chat-ia'); ?>
                <span class="flavor-badge flavor-badge--new">V2</span>
            </h1>

            <!-- Tabs -->
            <nav class="nav-tab-wrapper">
                <a href="#tab-create" class="nav-tab nav-tab-active"><?php _e('Crear/Actualizar', 'flavor-chat-ia'); ?></a>
                <a href="#tab-migrate" class="nav-tab"><?php _e('Migrar a V2', 'flavor-chat-ia'); ?></a>
                <a href="#tab-status" class="nav-tab"><?php _e('Estado Actual', 'flavor-chat-ia'); ?></a>
            </nav>

            <!-- Tab 1: Crear/Actualizar -->
            <div id="tab-create" class="flavor-tab-content flavor-tab-content--active">
                <?php $this->render_create_tab($pages_v2); ?>
            </div>

            <!-- Tab 2: Migrar -->
            <div id="tab-migrate" class="flavor-tab-content">
                <?php $this->render_migrate_tab($pages_old); ?>
            </div>

            <!-- Tab 3: Estado -->
            <div id="tab-status" class="flavor-tab-content">
                <?php $this->render_status_tab($pages_old, $pages_v2); ?>
            </div>
        </div>

        <style>
        .flavor-pages-admin {
            max-width: 1200px;
        }
        .flavor-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .flavor-badge--new {
            background: #10b981;
            color: white;
        }
        .flavor-tab-content {
            display: none;
            padding: 20px 0;
        }
        .flavor-tab-content--active {
            display: block;
        }
        .flavor-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .flavor-stat-box {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e5e7eb;
            text-align: center;
        }
        .flavor-stat-box__value {
            font-size: 48px;
            font-weight: 700;
            color: #3b82f6;
            margin: 0 0 8px;
        }
        .flavor-stat-box__label {
            font-size: 14px;
            color: #6b7280;
            margin: 0;
        }
        .flavor-stat-box--success .flavor-stat-box__value {
            color: #10b981;
        }
        .flavor-stat-box--warning .flavor-stat-box__value {
            color: #f59e0b;
        }
        .flavor-feature-list {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 20px;
            margin: 20px 0;
        }
        .flavor-feature-list h3 {
            margin: 0 0 15px;
            color: #1e40af;
        }
        .flavor-feature-list ul {
            margin: 0;
            padding-left: 20px;
        }
        .flavor-feature-list li {
            margin-bottom: 8px;
        }
        </style>

        <script>
        jQuery(document).ready(function($) {
            $('.nav-tab').on('click', function(e) {
                e.preventDefault();
                var target = $(this).attr('href');

                $('.nav-tab').removeClass('nav-tab-active');
                $(this).addClass('nav-tab-active');

                $('.flavor-tab-content').removeClass('flavor-tab-content--active');
                $(target).addClass('flavor-tab-content--active');
            });
        });
        </script>
        <?php
    }

    /**
     * Tab 1: Crear/Actualizar
     */
    private function render_create_tab($pages_v2) {
        ?>
        <div class="card">
            <h2><?php _e('Page Creator V2 - Con Componentes Modernos', 'flavor-chat-ia'); ?></h2>

            <div class="flavor-feature-list">
                <h3>✨ <?php _e('Nuevas Características', 'flavor-chat-ia'); ?></h3>
                <ul>
                    <li>✅ <strong>Headers estandarizados</strong> con <code>[flavor_page_header]</code></li>
                    <li>✅ <strong>Breadcrumbs automáticos</strong> en todas las páginas</li>
                    <li>✅ <strong>Navegación de módulo</strong> integrada en pestañas</li>
                    <li>✅ <strong>Background adaptativo</strong> (gradient para principales, white para internas)</li>
                    <li>✅ <strong>Templates full-width</strong> automáticos</li>
                    <li>✅ <strong>Detección inteligente</strong> de módulos y slugs</li>
                </ul>
            </div>

            <?php
            // Mensajes de éxito
            if (isset($_GET['created_v2'])) {
                echo '<div class="notice notice-success"><p>';
                printf(__('✅ Se crearon %d páginas con el nuevo formato V2', 'flavor-chat-ia'), intval($_GET['created_v2']));
                echo '</p></div>';
            }
            if (isset($_GET['updated_v2'])) {
                echo '<div class="notice notice-info"><p>';
                printf(__('🔄 Se actualizaron %d páginas existentes al formato V2', 'flavor-chat-ia'), intval($_GET['updated_v2']));
                echo '</p></div>';
            }
            ?>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-box flavor-stat-box--success">
                    <div class="flavor-stat-box__value"><?php echo count($pages_v2['with_v2']); ?></div>
                    <div class="flavor-stat-box__label"><?php _e('Páginas V2', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="flavor-stat-box flavor-stat-box--warning">
                    <div class="flavor-stat-box__value"><?php echo count($pages_v2['without_v2']); ?></div>
                    <div class="flavor-stat-box__label"><?php _e('Páginas Antiguas', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="flavor_create_pages_v2">
                <?php wp_nonce_field('flavor_create_pages_v2_nonce'); ?>

                <p>
                    <button type="submit" class="button button-primary button-hero">
                        🚀 <?php _e('Crear/Actualizar Todas las Páginas con V2', 'flavor-chat-ia'); ?>
                    </button>
                </p>

                <p class="description">
                    <?php _e('Crea páginas nuevas o actualiza las existentes al formato V2 con todos los componentes modernos.', 'flavor-chat-ia'); ?>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Tab 2: Migrar
     */
    private function render_migrate_tab($pages_old) {
        ?>
        <div class="card">
            <h2><?php _e('Migrador Inteligente', 'flavor-chat-ia'); ?></h2>

            <p class="description">
                <?php _e('Convierte páginas antiguas con HTML (<h1>, <p>) al nuevo formato con shortcodes modernos.', 'flavor-chat-ia'); ?>
            </p>

            <?php
            if (isset($_GET['migrated'])) {
                echo '<div class="notice notice-success"><p>';
                printf(__('✅ Se migraron %d páginas al nuevo formato', 'flavor-chat-ia'), intval($_GET['migrated']));
                echo '</p></div>';

                if (isset($_GET['migrated_list'])) {
                    echo '<ul>';
                    foreach (explode(',', sanitize_text_field($_GET['migrated_list'])) as $title) {
                        echo '<li>' . esc_html($title) . '</li>';
                    }
                    echo '</ul>';
                }
            }

            if (isset($_GET['skipped'])) {
                echo '<div class="notice notice-info"><p>';
                printf(__('ℹ️ Se omitieron %d páginas (ya estaban migradas)', 'flavor-chat-ia'), intval($_GET['skipped']));
                echo '</p></div>';
            }
            ?>

            <div class="flavor-feature-list">
                <h3>🔄 <?php _e('El Migrador Hace:', 'flavor-chat-ia'); ?></h3>
                <ul>
                    <li>✅ Extrae títulos y subtítulos del HTML antiguo</li>
                    <li>✅ Detecta el módulo desde los shortcodes</li>
                    <li>✅ Determina el slug "current" automáticamente</li>
                    <li>✅ Genera <code>[flavor_page_header]</code> correcto</li>
                    <li>✅ Preserva el contenido restante</li>
                    <li>✅ Asigna template full-width</li>
                </ul>
            </div>

            <div class="flavor-stats-grid">
                <div class="flavor-stat-box flavor-stat-box--warning">
                    <div class="flavor-stat-box__value"><?php echo count($pages_old); ?></div>
                    <div class="flavor-stat-box__label"><?php _e('Páginas a Migrar', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <?php if (count($pages_old) > 0): ?>
                <h3><?php _e('Páginas que se migrarán:', 'flavor-chat-ia'); ?></h3>
                <ul>
                    <?php foreach (array_slice($pages_old, 0, 10) as $page): ?>
                        <li><strong><?php echo esc_html($page->post_title); ?></strong> - <code><?php echo esc_html($page->post_name); ?></code></li>
                    <?php endforeach; ?>
                    <?php if (count($pages_old) > 10): ?>
                        <li><em><?php printf(__('... y %d más', 'flavor-chat-ia'), count($pages_old) - 10); ?></em></li>
                    <?php endif; ?>
                </ul>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('<?php esc_attr_e('¿Migrar todas las páginas al nuevo formato? Se modificará el contenido de las páginas.', 'flavor-chat-ia'); ?>');">
                    <input type="hidden" name="action" value="flavor_migrate_pages">
                    <?php wp_nonce_field('flavor_migrate_pages_nonce'); ?>

                    <p>
                        <button type="submit" class="button button-primary button-hero">
                            🔄 <?php printf(__('Migrar %d Páginas al Formato V2', 'flavor-chat-ia'), count($pages_old)); ?>
                        </button>
                    </p>
                </form>
            <?php else: ?>
                <div class="notice notice-success inline">
                    <p><?php _e('✅ Todas las páginas ya están en el formato V2', 'flavor-chat-ia'); ?></p>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Tab 3: Estado
     */
    private function render_status_tab($pages_old, $pages_v2) {
        ?>
        <div class="card">
            <h2><?php _e('Estado de Páginas', 'flavor-chat-ia'); ?></h2>

            <h3><?php _e('Páginas con Formato V2', 'flavor-chat-ia'); ?></h3>
            <?php if (count($pages_v2['with_v2']) > 0): ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Título', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Módulo', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Componentes', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages_v2['with_v2'] as $page): ?>
                            <tr>
                                <td><strong><?php echo esc_html($page->post_title); ?></strong></td>
                                <td><?php echo $this->detect_module($page->post_content); ?></td>
                                <td>
                                    <?php if (strpos($page->post_content, '[flavor_page_header') !== false): ?>
                                        <span style="color: #10b981;">✓ Header</span>
                                    <?php endif; ?>
                                    <?php if (strpos($page->post_content, 'breadcrumbs="yes"') !== false): ?>
                                        <span style="color: #10b981;">✓ Breadcrumbs</span>
                                    <?php endif; ?>
                                    <?php if (strpos($page->post_content, 'module=') !== false): ?>
                                        <span style="color: #10b981;">✓ Navegación</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo esc_url(get_permalink($page->ID)); ?>" target="_blank"><?php _e('Ver', 'flavor-chat-ia'); ?></a> |
                                    <a href="<?php echo esc_url(get_edit_post_link($page->ID)); ?>"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p><?php _e('No hay páginas con formato V2 todavía.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Detecta módulo del contenido
     */
    private function detect_module($content) {
        if (preg_match('/module=["\']([^"\']+)["\']/', $content, $matches)) {
            return '<code>' . esc_html($matches[1]) . '</code>';
        }
        return '<span style="color: #9ca3af;">—</span>';
    }

    /**
     * Obtiene estado de páginas V2
     */
    private function get_v2_pages_status() {
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_flavor_auto_page',
                    'value' => '1',
                ],
            ],
        ]);

        $with_v2 = [];
        $without_v2 = [];

        foreach ($pages as $page) {
            if (strpos($page->post_content, '[flavor_page_header') !== false) {
                $with_v2[] = $page;
            } else {
                $without_v2[] = $page;
            }
        }

        return [
            'with_v2' => $with_v2,
            'without_v2' => $without_v2,
        ];
    }

    /**
     * Obtiene páginas antiguas (sin migrar)
     */
    private function get_old_pages_status() {
        $pages = get_posts([
            'post_type' => 'page',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => '_flavor_auto_page',
                    'value' => '1',
                ],
            ],
        ]);

        $old_pages = [];
        foreach ($pages as $page) {
            // Si NO tiene flavor_page_header, es antigua
            if (strpos($page->post_content, '[flavor_page_header') === false) {
                $old_pages[] = $page;
            }
        }

        return $old_pages;
    }

    /**
     * Handler: Crear/Actualizar con V2
     */
    public function handle_create_pages() {
        check_admin_referer('flavor_create_pages_v2_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos', 'flavor-chat-ia'));
        }

        $result = Flavor_Page_Creator_V2::create_or_update_pages();

        wp_redirect(add_query_arg([
            'page' => 'flavor-create-pages',
            'created_v2' => count($result['created']),
            'updated_v2' => count($result['updated']),
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Handler: Migrar páginas
     */
    public function handle_migrate_pages() {
        check_admin_referer('flavor_migrate_pages_nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos', 'flavor-chat-ia'));
        }

        $result = Flavor_Page_Migrator::migrate_all_pages();

        wp_redirect(add_query_arg([
            'page' => 'flavor-create-pages',
            'migrated' => count($result['migrated']),
            'skipped' => count($result['skipped']),
            'migrated_list' => implode(',', array_slice($result['migrated'], 0, 5)),
        ], admin_url('admin.php')));
        exit;
    }
}

// Inicializar
if (is_admin()) {
    Flavor_Pages_Admin_V2::get_instance();
}
