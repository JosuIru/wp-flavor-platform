<?php
/**
 * Pantalla admin para bundles del ecosistema.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$admin_page_chrome_file = dirname(__FILE__) . '/class-admin-page-chrome.php';
if (!class_exists('Flavor_Admin_Page_Chrome') && file_exists($admin_page_chrome_file)) {
    require_once $admin_page_chrome_file;
}

class Flavor_Bundles_Admin {

    /**
     * Instancia singleton.
     *
     * @var self|null
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton.
     *
     * @return self
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor privado.
     */
    private function __construct() {
        // Sin hooks: la pantalla la invoca Admin_Menu_Manager.
    }

    /**
     * Renderiza la página canónica de bundles.
     *
     * @return void
     */
    public function render_page() {
        $ecosystem_links = class_exists('Flavor_Admin_Page_Chrome')
            ? Flavor_Admin_Page_Chrome::get_section_links('ecosystem', 'flavor-bundles')
            : [];

        $bundles = [
            [
                'title' => __('Comunidad Base', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Asociaciones, colectivos y comunidades pequeñas con foco en pertenencia, eventos y conversación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modules' => ['comunidades', 'socios', 'eventos', 'red-social', 'chat-grupos', 'foros'],
            ],
            [
                'title' => __('Economía Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Intercambio local, grupos de consumo, marketplace y coordinación operativa.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modules' => ['grupos-consumo', 'marketplace', 'banco-tiempo', 'socios', 'chat-grupos', 'facturas'],
            ],
            [
                'title' => __('Gobernanza', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Toma de decisiones, documentación y procesos de participación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modules' => ['participacion', 'transparencia', 'encuestas', 'eventos', 'foros', 'documentacion-legal'],
            ],
            [
                'title' => __('Apps y Red', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Configuración móvil, deep links, navegación y red federada de nodos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modules' => ['apps-config', 'app-menu', 'deep-links', 'network'],
            ],
        ];
        ?>
        <div class="wrap flavor-bundles-admin">
            <?php
            if (class_exists('Flavor_Admin_Page_Chrome')) {
                Flavor_Admin_Page_Chrome::render_breadcrumbs('ecosystem', 'flavor-bundles', __('Bundles', FLAVOR_PLATFORM_TEXT_DOMAIN));
                Flavor_Admin_Page_Chrome::render_compact_nav($ecosystem_links);
            }
            ?>

            <h1><?php esc_html_e('Bundles del Ecosistema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div class="notice notice-info flavor-admin-callout">
                <p><?php esc_html_e('Esta pantalla organiza combinaciones recomendadas de módulos para despliegues reales. Sirve como capa de producto entre el catálogo técnico y la activación funcional.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-admin-card-grid">
                <?php foreach ($bundles as $bundle) : ?>
                    <div class="flavor-admin-card">
                        <h2 class="flavor-admin-card__title"><?php echo esc_html($bundle['title']); ?></h2>
                        <p class="flavor-admin-card__description"><?php echo esc_html($bundle['description']); ?></p>
                        <div class="flavor-admin-pill-row">
                            <?php foreach ($bundle['modules'] as $module) : ?>
                                <span class="flavor-admin-pill">
                                    <?php echo esc_html($module); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="flavor-admin-actions">
                <a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=flavor-app-composer')); ?>">
                    <?php esc_html_e('Ir al Compositor de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-module-relations')); ?>">
                    <?php esc_html_e('Ver Relaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=flavor-module-dashboards')); ?>">
                    <?php esc_html_e('Ver Dashboards', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php
    }
}
