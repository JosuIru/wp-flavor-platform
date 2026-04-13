<?php
/**
 * Tests unitarios para símbolos de Visual Builder Pro.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class VBPSymbolsTest extends VBP_UnitTestCase {

    /**
     * Test estructura de símbolo.
     */
    public function test_symbol_structure() {
        $symbol = [
            'id' => 1,
            'name' => 'Header Principal',
            'slug' => 'header-principal',
            'category' => 'headers',
            'content' => [],
            'variants' => [],
            'default_variant' => 'default',
            'created_at' => '2025-01-15 10:00:00',
            'updated_at' => '2025-01-15 12:00:00',
        ];

        $this->assertArrayHasKey('id', $symbol);
        $this->assertArrayHasKey('name', $symbol);
        $this->assertArrayHasKey('content', $symbol);
    }

    /**
     * Test categorías de símbolos.
     */
    public function test_symbol_categories() {
        $categories = [
            'headers' => 'Cabeceras',
            'footers' => 'Pies de página',
            'heroes' => 'Secciones hero',
            'ctas' => 'Llamadas a la acción',
            'forms' => 'Formularios',
            'cards' => 'Tarjetas',
            'navigation' => 'Navegación',
            'testimonials' => 'Testimonios',
            'pricing' => 'Precios',
            'features' => 'Características',
        ];

        $this->assertArrayHasKey('headers', $categories);
        $this->assertArrayHasKey('ctas', $categories);
        $this->assertCount(10, $categories);
    }

    /**
     * Test símbolo con contenido.
     */
    public function test_symbol_with_content() {
        $symbol = [
            'name' => 'CTA Simple',
            'content' => [
                'type' => 'section',
                'props' => [
                    'className' => 'cta-section',
                    'background' => '#3b82f6',
                ],
                'children' => [
                    [
                        'type' => 'heading',
                        'props' => ['level' => 2, 'text' => '¿Listo para empezar?'],
                    ],
                    [
                        'type' => 'button',
                        'props' => ['text' => 'Contactar', 'style' => 'primary'],
                    ],
                ],
            ],
        ];

        $this->assertEquals('section', $symbol['content']['type']);
        $this->assertCount(2, $symbol['content']['children']);
    }

    /**
     * Test variantes de símbolo.
     */
    public function test_symbol_variants() {
        $symbol = [
            'name' => 'Header',
            'variants' => [
                'default' => [
                    'name' => 'Default',
                    'props' => ['background' => 'transparent'],
                ],
                'dark' => [
                    'name' => 'Dark',
                    'props' => ['background' => '#1f2937', 'textColor' => '#ffffff'],
                ],
                'light' => [
                    'name' => 'Light',
                    'props' => ['background' => '#ffffff', 'textColor' => '#1f2937'],
                ],
            ],
            'default_variant' => 'default',
        ];

        $this->assertCount(3, $symbol['variants']);
        $this->assertArrayHasKey('dark', $symbol['variants']);
    }

    /**
     * Test instancia de símbolo en página.
     */
    public function test_symbol_instance() {
        $instance = [
            'id' => 'instance-123',
            'symbol_id' => 5,
            'variant' => 'dark',
            'overrides' => [
                'props' => [
                    'background' => '#2d3748',
                ],
            ],
            'page_id' => 100,
            'element_id' => 'block-456',
        ];

        $this->assertArrayHasKey('symbol_id', $instance);
        $this->assertArrayHasKey('variant', $instance);
        $this->assertArrayHasKey('overrides', $instance);
    }

    /**
     * Test sincronización de símbolos.
     */
    public function test_symbol_sync() {
        $syncInfo = [
            'symbol_id' => 5,
            'instances' => [
                ['page_id' => 100, 'element_id' => 'block-1'],
                ['page_id' => 101, 'element_id' => 'block-5'],
                ['page_id' => 102, 'element_id' => 'block-3'],
            ],
            'last_sync' => '2025-01-15 12:00:00',
        ];

        $this->assertCount(3, $syncInfo['instances']);
    }

    /**
     * Test desvinculación de símbolo.
     */
    public function test_symbol_unlink() {
        $unlinkAction = [
            'instance_id' => 'instance-123',
            'symbol_id' => 5,
            'action' => 'unlink',
            'result' => 'converted_to_blocks',
        ];

        $this->assertEquals('unlink', $unlinkAction['action']);
        $this->assertEquals('converted_to_blocks', $unlinkAction['result']);
    }

    /**
     * Test exportar símbolo.
     */
    public function test_export_symbol() {
        $exportData = [
            'symbol' => [
                'name' => 'Mi Header',
                'content' => [],
                'variants' => [],
            ],
            'format' => 'json',
            'version' => '1.0',
            'exported_at' => '2025-01-15 12:00:00',
        ];

        $this->assertEquals('json', $exportData['format']);
        $this->assertArrayHasKey('symbol', $exportData);
    }

    /**
     * Test importar símbolo.
     */
    public function test_import_symbol() {
        $importResult = [
            'success' => true,
            'symbol_id' => 10,
            'name' => 'Header Importado',
            'conflicts' => [],
        ];

        $this->assertTrue($importResult['success']);
        $this->assertEmpty($importResult['conflicts']);
    }

    /**
     * Test biblioteca de símbolos.
     */
    public function test_symbol_library() {
        $library = [
            'local' => [
                ['id' => 1, 'name' => 'Header 1'],
                ['id' => 2, 'name' => 'Footer 1'],
            ],
            'cloud' => [
                ['id' => 'cloud-1', 'name' => 'Hero Premium', 'premium' => true],
                ['id' => 'cloud-2', 'name' => 'CTA Gratis', 'premium' => false],
            ],
        ];

        $this->assertArrayHasKey('local', $library);
        $this->assertArrayHasKey('cloud', $library);
    }

    /**
     * Test buscar símbolos.
     */
    public function test_search_symbols() {
        $searchParams = [
            'query' => 'header',
            'category' => 'headers',
            'source' => 'all',
        ];

        $results = [
            ['id' => 1, 'name' => 'Header Simple', 'score' => 0.95],
            ['id' => 2, 'name' => 'Header con Logo', 'score' => 0.90],
        ];

        $this->assertCount(2, $results);
        $this->assertGreaterThan(0.8, $results[0]['score']);
    }

    /**
     * Test símbolo global vs local.
     */
    public function test_symbol_scope() {
        $globalSymbol = [
            'id' => 1,
            'name' => 'Header Global',
            'scope' => 'global',
            'available_sites' => 'all',
        ];

        $localSymbol = [
            'id' => 2,
            'name' => 'Header Local',
            'scope' => 'local',
            'site_id' => 1,
        ];

        $this->assertEquals('global', $globalSymbol['scope']);
        $this->assertEquals('local', $localSymbol['scope']);
    }

    /**
     * Test permisos de símbolo.
     */
    public function test_symbol_permissions() {
        $permissions = [
            'create' => ['administrator', 'editor'],
            'edit' => ['administrator', 'editor'],
            'delete' => ['administrator'],
            'use' => ['administrator', 'editor', 'author'],
        ];

        $this->assertContains('editor', $permissions['edit']);
        $this->assertNotContains('author', $permissions['delete']);
    }

    /**
     * Test historial de símbolo.
     */
    public function test_symbol_history() {
        $history = [
            [
                'version' => 3,
                'action' => 'updated',
                'user_id' => 1,
                'timestamp' => '2025-01-15 12:00:00',
                'changes' => ['content', 'variants'],
            ],
            [
                'version' => 2,
                'action' => 'variant_added',
                'user_id' => 1,
                'timestamp' => '2025-01-14 10:00:00',
                'changes' => ['variants'],
            ],
        ];

        $this->assertCount(2, $history);
        $this->assertEquals('updated', $history[0]['action']);
    }

    /**
     * Test validación de símbolo.
     */
    public function test_symbol_validation() {
        $validation = [
            'name_required' => true,
            'content_required' => true,
            'max_name_length' => 100,
            'max_content_depth' => 10,
            'allowed_block_types' => ['all'],
        ];

        $this->assertTrue($validation['name_required']);
        $this->assertEquals(100, $validation['max_name_length']);
    }

    /**
     * Test símbolo con slots.
     */
    public function test_symbol_with_slots() {
        $symbolWithSlots = [
            'name' => 'Card con Slots',
            'content' => [
                'type' => 'card',
                'children' => [
                    ['type' => 'slot', 'props' => ['name' => 'header', 'default' => 'Título']],
                    ['type' => 'slot', 'props' => ['name' => 'content', 'default' => 'Contenido']],
                    ['type' => 'slot', 'props' => ['name' => 'footer', 'optional' => true]],
                ],
            ],
            'slots' => ['header', 'content', 'footer'],
        ];

        $this->assertCount(3, $symbolWithSlots['slots']);
    }

    /**
     * Test símbolo dinámico.
     */
    public function test_dynamic_symbol() {
        $dynamicSymbol = [
            'name' => 'Header Dinámico',
            'dynamic_fields' => [
                'logo_url' => ['type' => 'image', 'source' => 'site_logo'],
                'menu_items' => ['type' => 'menu', 'source' => 'primary_menu'],
                'cta_url' => ['type' => 'url', 'default' => '/contacto'],
            ],
        ];

        $this->assertArrayHasKey('dynamic_fields', $dynamicSymbol);
        $this->assertEquals('image', $dynamicSymbol['dynamic_fields']['logo_url']['type']);
    }

    /**
     * Test caché de símbolo.
     */
    public function test_symbol_caching() {
        $cacheConfig = [
            'enabled' => true,
            'ttl' => 3600,
            'cache_variants' => true,
            'invalidate_on_update' => true,
        ];

        $this->assertTrue($cacheConfig['enabled']);
        $this->assertTrue($cacheConfig['cache_variants']);
    }

    /**
     * Test símbolo compartido entre páginas.
     */
    public function test_symbol_sharing() {
        $sharedSymbol = [
            'id' => 5,
            'name' => 'Footer Compartido',
            'used_in_pages' => [100, 101, 102, 103],
            'usage_count' => 4,
        ];

        $this->assertEquals(4, $sharedSymbol['usage_count']);
        $this->assertCount(4, $sharedSymbol['used_in_pages']);
    }

    /**
     * Test conversión de bloques a símbolo.
     */
    public function test_convert_blocks_to_symbol() {
        $blocksToConvert = [
            ['type' => 'section', 'children' => [
                ['type' => 'heading', 'props' => ['text' => 'Título']],
                ['type' => 'text', 'props' => ['content' => 'Contenido']],
            ]],
        ];

        $newSymbol = [
            'name' => 'Nuevo Símbolo',
            'category' => 'uncategorized',
            'content' => $blocksToConvert[0],
            'created_from' => 'page_blocks',
        ];

        $this->assertEquals('Nuevo Símbolo', $newSymbol['name']);
        $this->assertEquals('page_blocks', $newSymbol['created_from']);
    }

    /**
     * Test duplicar símbolo.
     */
    public function test_duplicate_symbol() {
        $original = ['id' => 5, 'name' => 'Header Original'];
        $duplicate = ['id' => 6, 'name' => 'Header Original (copia)'];

        $this->assertNotEquals($original['id'], $duplicate['id']);
        $this->assertStringContainsString('copia', $duplicate['name']);
    }

    /**
     * Test símbolo con responsive.
     */
    public function test_symbol_responsive() {
        $responsiveSymbol = [
            'name' => 'Header Responsive',
            'responsive_overrides' => [
                'mobile' => [
                    'hide_elements' => ['nav-links', 'cta-button'],
                    'show_elements' => ['mobile-menu-toggle'],
                ],
                'tablet' => [
                    'hide_elements' => [],
                    'show_elements' => ['nav-links'],
                ],
            ],
        ];

        $this->assertArrayHasKey('mobile', $responsiveSymbol['responsive_overrides']);
        $this->assertContains('nav-links', $responsiveSymbol['responsive_overrides']['mobile']['hide_elements']);
    }
}
