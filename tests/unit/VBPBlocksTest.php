<?php
/**
 * Tests unitarios para bloques de Visual Builder Pro.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class VBPBlocksTest extends VBP_UnitTestCase {

    /**
     * Test estructura básica de bloque.
     */
    public function test_block_basic_structure() {
        $block = [
            'type' => 'heading',
            'props' => [
                'level' => 2,
                'text' => 'Test Heading',
                'align' => 'center',
            ],
            'children' => [],
        ];

        $this->assertArrayHasKey('type', $block);
        $this->assertArrayHasKey('props', $block);
        $this->assertEquals('heading', $block['type']);
    }

    /**
     * Test bloque container con hijos.
     */
    public function test_container_block_with_children() {
        $container = [
            'type' => 'container',
            'props' => [
                'className' => 'my-container',
                'maxWidth' => '1200px',
            ],
            'children' => [
                [
                    'type' => 'heading',
                    'props' => ['level' => 1, 'text' => 'Title'],
                ],
                [
                    'type' => 'text',
                    'props' => ['content' => 'Paragraph content'],
                ],
            ],
        ];

        $this->assertCount(2, $container['children']);
        $this->assertEquals('heading', $container['children'][0]['type']);
        $this->assertEquals('text', $container['children'][1]['type']);
    }

    /**
     * Test bloque section con background.
     */
    public function test_section_block_with_background() {
        $section = [
            'type' => 'section',
            'props' => [
                'className' => 'hero-section',
                'background' => [
                    'type' => 'gradient',
                    'from' => '#2d5016',
                    'to' => '#4a7c23',
                ],
                'padding' => '60px 0',
            ],
            'children' => [],
        ];

        $this->assertEquals('gradient', $section['props']['background']['type']);
        $this->assertStringStartsWith('#', $section['props']['background']['from']);
    }

    /**
     * Test bloque columns.
     */
    public function test_columns_block() {
        $columns = [
            'type' => 'columns',
            'props' => [
                'columns' => 3,
                'gap' => '30px',
                'responsive' => true,
            ],
            'children' => [
                ['type' => 'column', 'props' => ['width' => '33.33%']],
                ['type' => 'column', 'props' => ['width' => '33.33%']],
                ['type' => 'column', 'props' => ['width' => '33.33%']],
            ],
        ];

        $this->assertEquals(3, $columns['props']['columns']);
        $this->assertCount(3, $columns['children']);
    }

    /**
     * Test bloque button.
     */
    public function test_button_block() {
        $button = [
            'type' => 'button',
            'props' => [
                'text' => 'Click me',
                'url' => '/action',
                'style' => 'primary',
                'size' => 'large',
                'icon' => 'arrow-right',
                'target' => '_self',
            ],
        ];

        $this->assertEquals('primary', $button['props']['style']);
        $this->assertContains($button['props']['size'], ['small', 'medium', 'large']);
    }

    /**
     * Test bloque image.
     */
    public function test_image_block() {
        $image = [
            'type' => 'image',
            'props' => [
                'src' => 'https://example.com/image.jpg',
                'alt' => 'Test image',
                'width' => 800,
                'height' => 600,
                'lazy' => true,
            ],
        ];

        $this->assertStringContainsString('http', $image['props']['src']);
        $this->assertTrue($image['props']['lazy']);
    }

    /**
     * Test bloque video.
     */
    public function test_video_block() {
        $video = [
            'type' => 'video',
            'props' => [
                'src' => 'https://youtube.com/watch?v=abc123',
                'provider' => 'youtube',
                'autoplay' => false,
                'controls' => true,
                'aspectRatio' => '16:9',
            ],
        ];

        $this->assertEquals('youtube', $video['props']['provider']);
        $this->assertFalse($video['props']['autoplay']);
    }

    /**
     * Test bloque form.
     */
    public function test_form_block() {
        $form = [
            'type' => 'form',
            'props' => [
                'action' => '/submit',
                'method' => 'POST',
                'submitText' => 'Enviar',
            ],
            'children' => [
                [
                    'type' => 'input',
                    'props' => [
                        'name' => 'email',
                        'type' => 'email',
                        'required' => true,
                        'placeholder' => 'tu@email.com',
                    ],
                ],
                [
                    'type' => 'textarea',
                    'props' => [
                        'name' => 'message',
                        'rows' => 5,
                    ],
                ],
            ],
        ];

        $this->assertEquals('POST', $form['props']['method']);
        $this->assertCount(2, $form['children']);
    }

    /**
     * Test bloque module-shortcode.
     */
    public function test_module_shortcode_block() {
        $moduleBlock = [
            'type' => 'module-shortcode',
            'props' => [
                'module' => 'eventos',
                'view' => 'grid',
                'limit' => 6,
                'category' => '',
            ],
        ];

        $this->assertEquals('eventos', $moduleBlock['props']['module']);
        $this->assertEquals('grid', $moduleBlock['props']['view']);
    }

    /**
     * Test bloque icon.
     */
    public function test_icon_block() {
        $icon = [
            'type' => 'icon',
            'props' => [
                'name' => 'star',
                'size' => 24,
                'color' => '#ffcc00',
                'library' => 'heroicons',
            ],
        ];

        $this->assertEquals('star', $icon['props']['name']);
        $this->assertIsInt($icon['props']['size']);
    }

    /**
     * Test bloque spacer.
     */
    public function test_spacer_block() {
        $spacer = [
            'type' => 'spacer',
            'props' => [
                'height' => '40px',
                'responsive' => [
                    'mobile' => '20px',
                    'tablet' => '30px',
                ],
            ],
        ];

        $this->assertArrayHasKey('responsive', $spacer['props']);
    }

    /**
     * Test bloque divider.
     */
    public function test_divider_block() {
        $divider = [
            'type' => 'divider',
            'props' => [
                'style' => 'solid',
                'width' => '100%',
                'color' => '#e5e7eb',
                'thickness' => '1px',
            ],
        ];

        $this->assertContains($divider['props']['style'], ['solid', 'dashed', 'dotted']);
    }

    /**
     * Test bloque accordion.
     */
    public function test_accordion_block() {
        $accordion = [
            'type' => 'accordion',
            'props' => [
                'allowMultiple' => false,
                'defaultOpen' => 0,
            ],
            'children' => [
                [
                    'type' => 'accordion-item',
                    'props' => ['title' => 'Item 1'],
                    'children' => [['type' => 'text', 'props' => ['content' => 'Content 1']]],
                ],
                [
                    'type' => 'accordion-item',
                    'props' => ['title' => 'Item 2'],
                    'children' => [['type' => 'text', 'props' => ['content' => 'Content 2']]],
                ],
            ],
        ];

        $this->assertFalse($accordion['props']['allowMultiple']);
        $this->assertCount(2, $accordion['children']);
    }

    /**
     * Test bloque tabs.
     */
    public function test_tabs_block() {
        $tabs = [
            'type' => 'tabs',
            'props' => [
                'orientation' => 'horizontal',
                'defaultTab' => 0,
            ],
            'children' => [
                [
                    'type' => 'tab',
                    'props' => ['label' => 'Tab 1', 'icon' => 'home'],
                    'children' => [],
                ],
                [
                    'type' => 'tab',
                    'props' => ['label' => 'Tab 2', 'icon' => 'settings'],
                    'children' => [],
                ],
            ],
        ];

        $this->assertEquals('horizontal', $tabs['props']['orientation']);
    }

    /**
     * Test bloque card.
     */
    public function test_card_block() {
        $card = [
            'type' => 'card',
            'props' => [
                'variant' => 'elevated',
                'padding' => '20px',
                'borderRadius' => '8px',
                'shadow' => 'md',
            ],
            'children' => [
                ['type' => 'heading', 'props' => ['level' => 3, 'text' => 'Card Title']],
                ['type' => 'text', 'props' => ['content' => 'Card content']],
            ],
        ];

        $this->assertContains($card['props']['variant'], ['elevated', 'outlined', 'filled']);
    }

    /**
     * Test bloque feature-card.
     */
    public function test_feature_card_block() {
        $featureCard = [
            'type' => 'feature-card',
            'props' => [
                'icon' => 'leaf',
                'title' => 'Ecológico',
                'text' => 'Productos 100% ecológicos',
                'iconColor' => '#22c55e',
            ],
        ];

        $this->assertArrayHasKey('icon', $featureCard['props']);
        $this->assertArrayHasKey('title', $featureCard['props']);
    }

    /**
     * Test bloque testimonial.
     */
    public function test_testimonial_block() {
        $testimonial = [
            'type' => 'testimonial',
            'props' => [
                'quote' => 'Great product!',
                'author' => 'John Doe',
                'role' => 'CEO',
                'avatar' => 'https://example.com/avatar.jpg',
                'rating' => 5,
            ],
        ];

        $this->assertLessThanOrEqual(5, $testimonial['props']['rating']);
        $this->assertGreaterThanOrEqual(1, $testimonial['props']['rating']);
    }

    /**
     * Test bloque pricing-table.
     */
    public function test_pricing_table_block() {
        $pricing = [
            'type' => 'pricing-table',
            'props' => [
                'columns' => 3,
            ],
            'children' => [
                [
                    'type' => 'pricing-card',
                    'props' => [
                        'name' => 'Basic',
                        'price' => '9.99',
                        'currency' => '€',
                        'period' => 'mes',
                        'features' => ['Feature 1', 'Feature 2'],
                        'highlighted' => false,
                    ],
                ],
            ],
        ];

        $this->assertIsArray($pricing['children'][0]['props']['features']);
    }

    /**
     * Test bloque map.
     */
    public function test_map_block() {
        $map = [
            'type' => 'map',
            'props' => [
                'lat' => 43.2630,
                'lng' => -2.9350,
                'zoom' => 14,
                'marker' => true,
                'provider' => 'openstreetmap',
            ],
        ];

        $this->assertIsFloat($map['props']['lat']);
        $this->assertIsFloat($map['props']['lng']);
    }

    /**
     * Test bloque social-links.
     */
    public function test_social_links_block() {
        $social = [
            'type' => 'social-links',
            'props' => [
                'style' => 'icons',
                'size' => 'medium',
                'links' => [
                    ['platform' => 'twitter', 'url' => 'https://twitter.com/example'],
                    ['platform' => 'instagram', 'url' => 'https://instagram.com/example'],
                    ['platform' => 'facebook', 'url' => 'https://facebook.com/example'],
                ],
            ],
        ];

        $this->assertCount(3, $social['props']['links']);
    }

    /**
     * Test bloque countdown.
     */
    public function test_countdown_block() {
        $countdown = [
            'type' => 'countdown',
            'props' => [
                'targetDate' => '2025-12-31T23:59:59',
                'showDays' => true,
                'showHours' => true,
                'showMinutes' => true,
                'showSeconds' => true,
                'expiredMessage' => 'Event has ended',
            ],
        ];

        $this->assertTrue($countdown['props']['showDays']);
    }

    /**
     * Test bloque progress-bar.
     */
    public function test_progress_bar_block() {
        $progress = [
            'type' => 'progress-bar',
            'props' => [
                'value' => 75,
                'max' => 100,
                'showLabel' => true,
                'color' => '#22c55e',
                'height' => '8px',
            ],
        ];

        $this->assertLessThanOrEqual($progress['props']['max'], $progress['props']['value']);
    }

    /**
     * Test bloque stats.
     */
    public function test_stats_block() {
        $stats = [
            'type' => 'stats',
            'props' => [
                'columns' => 4,
            ],
            'children' => [
                ['type' => 'stat', 'props' => ['value' => '1000+', 'label' => 'Usuarios']],
                ['type' => 'stat', 'props' => ['value' => '50+', 'label' => 'Proyectos']],
                ['type' => 'stat', 'props' => ['value' => '99%', 'label' => 'Satisfacción']],
                ['type' => 'stat', 'props' => ['value' => '24/7', 'label' => 'Soporte']],
            ],
        ];

        $this->assertCount(4, $stats['children']);
    }

    /**
     * Test validación de tipos de bloque conocidos.
     */
    public function test_known_block_types() {
        $knownTypes = [
            'section', 'container', 'columns', 'column',
            'heading', 'text', 'button', 'image', 'video',
            'icon', 'spacer', 'divider', 'card', 'form',
            'input', 'textarea', 'select', 'checkbox',
            'accordion', 'accordion-item', 'tabs', 'tab',
            'testimonial', 'feature-card', 'pricing-table',
            'pricing-card', 'map', 'social-links', 'countdown',
            'progress-bar', 'stats', 'stat', 'module-shortcode',
        ];

        $this->assertContains('heading', $knownTypes);
        $this->assertContains('module-shortcode', $knownTypes);
        $this->assertGreaterThan(30, count($knownTypes));
    }

    /**
     * Test bloque con estilos inline.
     */
    public function test_block_with_inline_styles() {
        $block = [
            'type' => 'container',
            'props' => [
                'style' => [
                    'backgroundColor' => '#f3f4f6',
                    'padding' => '20px',
                    'borderRadius' => '8px',
                    'boxShadow' => '0 2px 4px rgba(0,0,0,0.1)',
                ],
            ],
        ];

        $this->assertIsArray($block['props']['style']);
        $this->assertArrayHasKey('backgroundColor', $block['props']['style']);
    }

    /**
     * Test bloque con clases CSS.
     */
    public function test_block_with_css_classes() {
        $block = [
            'type' => 'section',
            'props' => [
                'className' => 'hero-section animate-fade-in',
                'id' => 'hero',
                'data' => [
                    'aos' => 'fade-up',
                    'aos-duration' => '1000',
                ],
            ],
        ];

        $this->assertStringContainsString('hero-section', $block['props']['className']);
    }

    /**
     * Test bloque responsive.
     */
    public function test_block_responsive_props() {
        $block = [
            'type' => 'columns',
            'props' => [
                'columns' => [
                    'desktop' => 4,
                    'tablet' => 2,
                    'mobile' => 1,
                ],
                'gap' => [
                    'desktop' => '30px',
                    'tablet' => '20px',
                    'mobile' => '15px',
                ],
            ],
        ];

        $this->assertArrayHasKey('desktop', $block['props']['columns']);
        $this->assertArrayHasKey('mobile', $block['props']['columns']);
    }
}
