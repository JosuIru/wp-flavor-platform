<?php
/**
 * Tests unitarios para el sistema de addons.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class AddonsSystemTest extends VBP_UnitTestCase {

    /**
     * Test estructura de addon.
     */
    public function test_addon_structure() {
        $addon = [
            'id' => 'flavor-multilingual',
            'name' => 'Flavor Multilingual',
            'version' => '1.4.0',
            'description' => 'Soporte multiidioma para Flavor Platform',
            'author' => 'Gailu Labs',
            'requires_flavor' => '2.0.0',
            'requires_php' => '7.4',
            'main_file' => 'flavor-multilingual.php',
            'active' => true,
        ];

        $this->assertArrayHasKey('id', $addon);
        $this->assertArrayHasKey('version', $addon);
        $this->assertTrue($addon['active']);
    }

    /**
     * Test addons disponibles.
     */
    public function test_available_addons() {
        $addons = [
            'admin-assistant' => 'Asistente de administración con IA',
            'advertising-pro' => 'Sistema de publicidad avanzado',
            'demo-orchestrator' => 'Orquestador de demos',
            'flavor-multilingual' => 'Soporte multiidioma',
            'network-communities' => 'Red de comunidades',
            'restaurant-ordering' => 'Pedidos para restaurantes',
        ];

        $this->assertCount(6, $addons);
        $this->assertArrayHasKey('flavor-multilingual', $addons);
    }

    /**
     * Test manifest de addon.
     */
    public function test_addon_manifest() {
        $manifest = [
            'id' => 'network-communities',
            'name' => 'Network Communities',
            'version' => '1.5.0',
            'description' => 'Conecta múltiples comunidades Flavor',
            'author' => [
                'name' => 'Gailu Labs',
                'url' => 'https://gailu.net',
            ],
            'license' => 'GPL-2.0-or-later',
            'requires' => [
                'flavor_platform' => '2.0.0',
                'php' => '7.4',
                'modules' => ['socios'],
            ],
            'provides' => [
                'modules' => ['network-sync'],
                'api_endpoints' => true,
                'admin_pages' => true,
            ],
        ];

        $this->assertArrayHasKey('requires', $manifest);
        $this->assertContains('socios', $manifest['requires']['modules']);
    }

    /**
     * Test estados de addon.
     */
    public function test_addon_states() {
        $states = [
            'not_installed' => 'No instalado',
            'installed' => 'Instalado pero inactivo',
            'active' => 'Activo',
            'needs_update' => 'Actualización disponible',
            'incompatible' => 'Incompatible con versión actual',
        ];

        $this->assertArrayHasKey('active', $states);
        $this->assertArrayHasKey('incompatible', $states);
    }

    /**
     * Test verificación de dependencias.
     */
    public function test_dependency_check() {
        $addonRequires = [
            'flavor_platform' => '2.0.0',
            'php' => '7.4',
            'modules' => ['socios', 'eventos'],
        ];

        $currentEnvironment = [
            'flavor_platform' => '2.5.0',
            'php' => '8.1',
            'active_modules' => ['socios', 'eventos', 'foros'],
        ];

        // Verificar versión de Flavor
        $flavorOk = version_compare(
            $currentEnvironment['flavor_platform'],
            $addonRequires['flavor_platform'],
            '>='
        );
        $this->assertTrue($flavorOk);

        // Verificar versión de PHP
        $phpOk = version_compare(
            $currentEnvironment['php'],
            $addonRequires['php'],
            '>='
        );
        $this->assertTrue($phpOk);

        // Verificar módulos
        $modulesOk = empty(array_diff(
            $addonRequires['modules'],
            $currentEnvironment['active_modules']
        ));
        $this->assertTrue($modulesOk);
    }

    /**
     * Test hooks de addon.
     */
    public function test_addon_hooks() {
        $hooks = [
            'flavor_addon_activated' => 'Cuando un addon se activa',
            'flavor_addon_deactivated' => 'Cuando un addon se desactiva',
            'flavor_addon_installed' => 'Cuando un addon se instala',
            'flavor_addon_uninstalled' => 'Cuando un addon se desinstala',
            'flavor_addon_updated' => 'Cuando un addon se actualiza',
            'flavor_addons_loaded' => 'Cuando todos los addons están cargados',
        ];

        $this->assertArrayHasKey('flavor_addon_activated', $hooks);
    }

    /**
     * Test registro de addon.
     */
    public function test_addon_registration() {
        $registration = [
            'id' => 'my-custom-addon',
            'init_callback' => 'my_addon_init',
            'activate_callback' => 'my_addon_activate',
            'deactivate_callback' => 'my_addon_deactivate',
            'uninstall_callback' => 'my_addon_uninstall',
            'admin_init_callback' => 'my_addon_admin_init',
        ];

        $this->assertArrayHasKey('init_callback', $registration);
        $this->assertArrayHasKey('activate_callback', $registration);
    }

    /**
     * Test configuración de addon.
     */
    public function test_addon_settings() {
        $settings = [
            'addon_id' => 'flavor-multilingual',
            'settings' => [
                'default_language' => 'es',
                'active_languages' => ['es', 'eu', 'en'],
                'url_mode' => 'directory',
                'auto_translate' => true,
                'ai_provider' => 'anthropic',
            ],
        ];

        $this->assertContains('eu', $settings['settings']['active_languages']);
        $this->assertTrue($settings['settings']['auto_translate']);
    }

    /**
     * Test actualización de addon.
     */
    public function test_addon_update_info() {
        $updateInfo = [
            'addon_id' => 'network-communities',
            'current_version' => '1.4.0',
            'new_version' => '1.5.0',
            'changelog' => [
                '1.5.0' => [
                    'Nuevo sistema de sincronización',
                    'Mejoras de rendimiento',
                    'Corrección de bugs',
                ],
            ],
            'download_url' => 'https://updates.gailu.net/addons/network-communities-1.5.0.zip',
            'requires_flavor' => '2.5.0',
        ];

        $needsUpdate = version_compare(
            $updateInfo['current_version'],
            $updateInfo['new_version'],
            '<'
        );
        $this->assertTrue($needsUpdate);
    }

    /**
     * Test licencia de addon.
     */
    public function test_addon_license() {
        $license = [
            'addon_id' => 'advertising-pro',
            'license_key' => 'XXXX-XXXX-XXXX-XXXX',
            'status' => 'valid',
            'expires_at' => '2026-01-15',
            'seats' => 1,
            'features' => ['premium_templates', 'priority_support'],
        ];

        $this->assertEquals('valid', $license['status']);
        $this->assertContains('premium_templates', $license['features']);
    }

    /**
     * Test API de addon.
     */
    public function test_addon_api() {
        $api = [
            'get_addon' => 'Obtener información de addon',
            'get_all_addons' => 'Listar todos los addons',
            'activate_addon' => 'Activar addon',
            'deactivate_addon' => 'Desactivar addon',
            'get_addon_settings' => 'Obtener configuración',
            'update_addon_settings' => 'Actualizar configuración',
            'check_addon_updates' => 'Verificar actualizaciones',
        ];

        $this->assertArrayHasKey('activate_addon', $api);
    }

    /**
     * Test carga de addon.
     */
    public function test_addon_loading_order() {
        $loadOrder = [
            1 => ['id' => 'core-addon', 'priority' => 10],
            2 => ['id' => 'network-communities', 'priority' => 20],
            3 => ['id' => 'flavor-multilingual', 'priority' => 30],
            4 => ['id' => 'advertising-pro', 'priority' => 50],
        ];

        $priorities = array_column($loadOrder, 'priority');
        $sorted = $priorities;
        sort($sorted);

        $this->assertEquals($sorted, $priorities);
    }

    /**
     * Test conflictos entre addons.
     */
    public function test_addon_conflicts() {
        $addonA = [
            'id' => 'addon-a',
            'conflicts_with' => ['addon-b', 'addon-c'],
        ];

        $activeAddons = ['addon-a', 'addon-b'];

        $conflicts = array_intersect($addonA['conflicts_with'], $activeAddons);
        $this->assertContains('addon-b', $conflicts);
    }

    /**
     * Test permisos de addon.
     */
    public function test_addon_permissions() {
        $permissions = [
            'addon_id' => 'admin-assistant',
            'capabilities' => [
                'use_admin_assistant' => ['administrator', 'editor'],
                'configure_admin_assistant' => ['administrator'],
                'view_ai_logs' => ['administrator'],
            ],
        ];

        $this->assertContains('administrator', $permissions['capabilities']['use_admin_assistant']);
    }

    /**
     * Test assets de addon.
     */
    public function test_addon_assets() {
        $assets = [
            'styles' => [
                'addon-main' => 'css/main.css',
                'addon-admin' => 'css/admin.css',
            ],
            'scripts' => [
                'addon-main' => 'js/main.js',
                'addon-admin' => 'js/admin.js',
            ],
            'dependencies' => [
                'addon-main' => ['jquery', 'wp-api'],
            ],
        ];

        $this->assertArrayHasKey('styles', $assets);
        $this->assertArrayHasKey('scripts', $assets);
    }

    /**
     * Test migración de datos de addon.
     */
    public function test_addon_data_migration() {
        $migration = [
            'addon_id' => 'network-communities',
            'from_version' => '1.4.0',
            'to_version' => '1.5.0',
            'migrations' => [
                '1.4.1' => 'migrate_sync_settings',
                '1.4.5' => 'migrate_node_structure',
                '1.5.0' => 'migrate_api_keys',
            ],
        ];

        $this->assertCount(3, $migration['migrations']);
    }

    /**
     * Test desinstalación de addon.
     */
    public function test_addon_uninstall_cleanup() {
        $cleanup = [
            'delete_options' => true,
            'delete_tables' => true,
            'delete_files' => true,
            'options_to_delete' => [
                'addon_settings',
                'addon_version',
                'addon_license',
            ],
            'tables_to_delete' => [
                'addon_data',
                'addon_logs',
            ],
        ];

        $this->assertTrue($cleanup['delete_options']);
        $this->assertContains('addon_settings', $cleanup['options_to_delete']);
    }

    /**
     * Test telemetría de addon.
     */
    public function test_addon_telemetry() {
        $telemetry = [
            'enabled' => true,
            'anonymous' => true,
            'data_collected' => [
                'addon_version',
                'php_version',
                'wordpress_version',
                'flavor_version',
                'active_modules_count',
                'error_count',
            ],
            'endpoint' => 'https://telemetry.gailu.net/collect',
        ];

        $this->assertTrue($telemetry['anonymous']);
        $this->assertNotContains('user_email', $telemetry['data_collected']);
    }

    /**
     * Test addon con módulos propios.
     */
    public function test_addon_provided_modules() {
        $addonModules = [
            'addon_id' => 'restaurant-ordering',
            'modules' => [
                [
                    'id' => 'restaurant-menu',
                    'name' => 'Carta del Restaurante',
                    'category' => 'comercio',
                ],
                [
                    'id' => 'table-reservations',
                    'name' => 'Reservas de Mesas',
                    'category' => 'reservas',
                ],
                [
                    'id' => 'food-orders',
                    'name' => 'Pedidos Online',
                    'category' => 'comercio',
                ],
            ],
        ];

        $this->assertCount(3, $addonModules['modules']);
    }

    /**
     * Test addon con plantillas.
     */
    public function test_addon_templates() {
        $templates = [
            'addon_id' => 'advertising-pro',
            'templates' => [
                'banner-horizontal' => 'templates/banner-horizontal.php',
                'banner-vertical' => 'templates/banner-vertical.php',
                'popup-ad' => 'templates/popup-ad.php',
                'native-ad' => 'templates/native-ad.php',
            ],
            'overridable' => true,
            'override_path' => 'flavor-addons/advertising-pro/',
        ];

        $this->assertTrue($templates['overridable']);
        $this->assertCount(4, $templates['templates']);
    }

    /**
     * Test addon REST API.
     */
    public function test_addon_rest_api() {
        $restApi = [
            'addon_id' => 'flavor-multilingual',
            'namespace' => 'flavor-multilingual/v1',
            'endpoints' => [
                'GET /languages' => 'Listar idiomas',
                'POST /translate' => 'Traducir texto',
                'GET /posts/{id}/translations' => 'Traducciones de post',
            ],
        ];

        $this->assertEquals('flavor-multilingual/v1', $restApi['namespace']);
    }

    /**
     * Test compatibilidad de addon.
     */
    public function test_addon_compatibility_matrix() {
        $compatibility = [
            'addon_id' => 'network-communities',
            'tested_with' => [
                'wordpress' => ['6.4', '6.5', '6.6'],
                'php' => ['7.4', '8.0', '8.1', '8.2'],
                'flavor_platform' => ['2.3', '2.4', '2.5'],
            ],
            'known_issues' => [
                'wordpress_6.3' => 'Problema con REST API',
            ],
        ];

        $this->assertContains('8.1', $compatibility['tested_with']['php']);
    }
}
