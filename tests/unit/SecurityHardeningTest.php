<?php
/**
 * Tests de endurecimiento de seguridad y contratos públicos.
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Traits requeridos por las clases bajo test
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-rest-response.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-request-validation.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-permission-checks.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/traits/trait-safe-sql.php';

require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-restaurant-ordering/includes/class-reservation-manager.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/class-wp-module-integrations.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/network/class-network-node.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/network/class-network-api.php';
require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-multilingual/api/class-translation-api.php';
require_once FLAVOR_PLUGIN_DIR . '/addons/flavor-multilingual/integrations/class-woocommerce-integration.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/repositories/class-module-integration-repository.php';

class SecurityHardeningTest extends Flavor_TestCase {

    public function test_reservation_orderby_whitelist_rejects_untrusted_input() {
        $this->assertSame('created_at', Flavor_Reservation_Manager::sanitize_orderby('created_at'));
        $this->assertSame('created_at', Flavor_Reservation_Manager::sanitize_orderby('created_at DESC, sleep(5)'));
        $this->assertSame('created_at', Flavor_Reservation_Manager::sanitize_orderby('id; DROP TABLE wp_users'));
    }

    public function test_map_limit_is_bounded() {
        $this->assertSame(50, Flavor_Network_API::normalize_map_limit(null));
        $this->assertSame(1, Flavor_Network_API::normalize_map_limit(0));
        $this->assertSame(100, Flavor_Network_API::normalize_map_limit(9999));
        $this->assertSame(75, Flavor_Network_API::normalize_map_limit(75));
        $this->assertSame(100, Flavor_Network_API::normalize_map_limit(120));
    }

    public function test_module_integrations_public_limit_is_bounded() {
        $this->assertSame(20, Flavor_WP_Module_Integrations::normalize_public_limit(null));
        $this->assertSame(1, Flavor_WP_Module_Integrations::normalize_public_limit(0));
        $this->assertSame(50, Flavor_WP_Module_Integrations::normalize_public_limit(1000));
        $this->assertSame(15, Flavor_WP_Module_Integrations::normalize_public_limit(15));
    }

    public function test_public_product_translations_hide_non_published_metadata() {
        $translations = [
            'es' => [
                'title' => [
                    'value' => 'Producto publicado',
                    'status' => 'published',
                    'auto' => false,
                ],
                'content' => [
                    'value' => 'Borrador interno',
                    'status' => 'draft',
                    'auto' => true,
                ],
            ],
            'fr' => [
                'title' => [
                    'value' => 'Produit brouillon',
                    'status' => 'draft',
                    'auto' => true,
                ],
            ],
        ];

        $filtered = Flavor_ML_WooCommerce_Integration::filter_public_product_translations($translations);

        $this->assertSame(['es' => ['title' => 'Producto publicado']], $filtered);
    }

    public function test_public_post_translations_hide_non_published_metadata() {
        $translations = [
            'en' => [
                'title' => [
                    'value' => 'Published title',
                    'status' => 'published',
                    'auto' => false,
                ],
                'content' => [
                    'value' => 'Internal draft',
                    'status' => 'draft',
                    'auto' => true,
                ],
            ],
            'de' => [
                'title' => [
                    'value' => 'Entwurf',
                    'status' => 'draft',
                    'auto' => false,
                ],
            ],
        ];

        $filtered = Flavor_Translation_API::filter_public_translations($translations);

        $this->assertSame(['en' => ['title' => 'Published title']], $filtered);
    }

    public function test_module_integration_repository_returns_valid_config() {
        $repository = Flavor_Module_Integration_Repository::get_instance();

        // Verificar que devuelve configuración para módulos conocidos
        $config_eventos = $repository->get_module_config('eventos');
        $this->assertNotNull($config_eventos);
        $this->assertSame('eventos_posts', $config_eventos['tabla_relacion']);
        $this->assertSame('evento_id', $config_eventos['campo_fk']);

        // Verificar que devuelve null para módulos desconocidos
        $config_invalido = $repository->get_module_config('modulo_inexistente');
        $this->assertNull($config_invalido);

        // Verificar lista de módulos disponibles
        $modulos = $repository->get_available_modules();
        $this->assertContains('eventos', $modulos);
        $this->assertContains('comunidades', $modulos);
        $this->assertContains('biblioteca', $modulos);
    }
}
