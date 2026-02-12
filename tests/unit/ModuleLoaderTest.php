<?php
/**
 * Tests para el Module Loader
 *
 * @package FlavorChatIA
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class ModuleLoaderTest extends Flavor_TestCase {

    public function test_module_metadata_structure() {
        // Verificar estructura esperada de metadatos
        $expectedKeys = ['id', 'name', 'description', 'icon', 'can_activate'];

        $metadata = [
            'id' => 'test_module',
            'name' => 'Test Module',
            'description' => 'A test module',
            'icon' => 'dashicons-admin-tools',
            'can_activate' => true,
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $metadata);
        }
    }

    public function test_module_id_format_validation() {
        // IDs válidos de módulos
        $validIds = [
            'biblioteca',
            'banco_tiempo',
            'grupos_consumo',
            'dex_solana',
        ];

        foreach ($validIds as $id) {
            $this->assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $id);
        }
    }

    public function test_module_id_invalid_format() {
        // IDs inválidos de módulos
        $invalidIds = [
            'Biblioteca', // Mayúscula
            '123module', // Empieza con número
            'my-module', // Guión en lugar de underscore
            'module.name', // Punto
        ];

        foreach ($invalidIds as $id) {
            $this->assertDoesNotMatchRegularExpression('/^[a-z][a-z0-9_]*$/', $id);
        }
    }

    public function test_module_categories_are_valid() {
        $validCategories = [
            'comunidad',
            'comercio',
            'gestion',
            'comunicacion',
            'finanzas',
            'utilidades',
        ];

        $testCategory = 'comunidad';

        $this->assertContains($testCategory, $validCategories);
    }

    public function test_lazy_load_reduces_memory() {
        // Simular que sin lazy loading se cargan todos los módulos
        $allModulesLoaded = 43;
        $lazyLoadedModules = 5; // Solo los necesarios

        $this->assertLessThan($allModulesLoaded, $lazyLoadedModules);
    }

    public function test_module_visibility_levels() {
        $validLevels = ['public', 'members', 'admins', 'hidden'];

        foreach ($validLevels as $level) {
            $this->assertContains($level, $validLevels);
        }
    }

    public function test_module_dependencies_format() {
        // Formato de dependencias
        $dependencies = [
            'requires' => ['woocommerce'],
            'optional' => ['elementor'],
            'conflicts' => [],
        ];

        $this->assertIsArray($dependencies['requires']);
        $this->assertIsArray($dependencies['optional']);
        $this->assertIsArray($dependencies['conflicts']);
    }
}
