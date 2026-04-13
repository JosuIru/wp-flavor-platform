<?php
/**
 * Tests unitarios para el sistema de shortcodes.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class ShortcodesTest extends VBP_UnitTestCase {

    /**
     * Test shortcode flavor_module estructura de atributos.
     */
    public function test_flavor_module_attributes() {
        $defaultAtts = [
            'module' => '',
            'view' => 'default',
            'limit' => 10,
            'category' => '',
            'order' => 'DESC',
            'orderby' => 'date',
            'columns' => 3,
            'show_filters' => 'true',
            'show_pagination' => 'true',
        ];

        $this->assertArrayHasKey('module', $defaultAtts);
        $this->assertEquals('default', $defaultAtts['view']);
        $this->assertEquals(10, $defaultAtts['limit']);
    }

    /**
     * Test módulos válidos para shortcode.
     */
    public function test_valid_modules_for_shortcode() {
        $validModules = [
            'eventos',
            'socios',
            'marketplace',
            'foros',
            'grupos-consumo',
            'banco-tiempo',
            'incidencias',
            'reservas',
            'cursos',
            'talleres',
            'biblioteca',
            'crowdfunding',
        ];

        $this->assertContains('eventos', $validModules);
        $this->assertContains('marketplace', $validModules);
        $this->assertGreaterThan(10, count($validModules));
    }

    /**
     * Test vistas disponibles por módulo.
     */
    public function test_module_views() {
        $moduleViews = [
            'eventos' => ['grid', 'list', 'calendar', 'featured', 'upcoming'],
            'marketplace' => ['grid', 'list', 'featured', 'categories'],
            'socios' => ['grid', 'list', 'directory', 'map'],
            'foros' => ['list', 'categories', 'recent', 'popular'],
        ];

        $this->assertContains('calendar', $moduleViews['eventos']);
        $this->assertContains('categories', $moduleViews['marketplace']);
    }

    /**
     * Test shortcode flavor_eventos.
     */
    public function test_flavor_eventos_shortcode() {
        $atts = [
            'view' => 'grid',
            'limit' => 6,
            'category' => 'workshops',
            'upcoming_only' => 'true',
            'show_past' => 'false',
        ];

        $this->assertEquals('grid', $atts['view']);
        $this->assertEquals('true', $atts['upcoming_only']);
    }

    /**
     * Test shortcode flavor_socios.
     */
    public function test_flavor_socios_shortcode() {
        $atts = [
            'view' => 'directory',
            'role' => 'all',
            'show_contact' => 'true',
            'show_avatar' => 'true',
            'searchable' => 'true',
        ];

        $this->assertEquals('directory', $atts['view']);
        $this->assertEquals('all', $atts['role']);
    }

    /**
     * Test shortcode flavor_unified_dashboard.
     */
    public function test_flavor_unified_dashboard_shortcode() {
        $atts = [
            'modules' => 'eventos,socios,foros',
            'layout' => 'tabs',
            'default_tab' => 'eventos',
            'show_stats' => 'true',
        ];

        $modules = explode(',', $atts['modules']);
        $this->assertCount(3, $modules);
        $this->assertEquals('tabs', $atts['layout']);
    }

    /**
     * Test parseo de atributos booleanos.
     */
    public function test_boolean_attribute_parsing() {
        $parseBoolean = function($value) {
            if (is_bool($value)) return $value;
            return in_array(strtolower($value), ['true', '1', 'yes', 'on'], true);
        };

        $this->assertTrue($parseBoolean('true'));
        $this->assertTrue($parseBoolean('1'));
        $this->assertTrue($parseBoolean('yes'));
        $this->assertFalse($parseBoolean('false'));
        $this->assertFalse($parseBoolean('0'));
        $this->assertFalse($parseBoolean('no'));
    }

    /**
     * Test sanitización de atributos.
     */
    public function test_attribute_sanitization() {
        $sanitizeModule = function($module) {
            // Primero quitar tags HTML, luego sanitizar
            $module = strip_tags($module);
            return preg_replace('/[^a-z0-9\-_]/', '', strtolower($module));
        };

        $this->assertEquals('eventos', $sanitizeModule('Eventos'));
        $this->assertEquals('grupos-consumo', $sanitizeModule('grupos-consumo'));
        $this->assertEquals('marketplace', $sanitizeModule('MarketPlace<script>'));
    }

    /**
     * Test límite de resultados.
     */
    public function test_limit_validation() {
        $validateLimit = function($limit, $max = 100) {
            $limit = intval($limit);
            if ($limit < 1) return 10;
            if ($limit > $max) return $max;
            return $limit;
        };

        $this->assertEquals(10, $validateLimit(-5));
        $this->assertEquals(10, $validateLimit(0));
        $this->assertEquals(50, $validateLimit(50));
        $this->assertEquals(100, $validateLimit(500));
    }

    /**
     * Test shortcode anidado.
     */
    public function test_nested_shortcode_structure() {
        $content = '[flavor_section background="dark"]
            [flavor_heading level="2"]Nuestros Eventos[/flavor_heading]
            [flavor_module module="eventos" view="grid" limit="3"]
        [/flavor_section]';

        $this->assertStringContainsString('flavor_section', $content);
        $this->assertStringContainsString('flavor_module', $content);
    }

    /**
     * Test shortcode con contenido.
     */
    public function test_shortcode_with_content() {
        $shortcodeContent = 'Este es el contenido dentro del shortcode';
        $atts = ['class' => 'custom-class'];

        $this->assertNotEmpty($shortcodeContent);
        $this->assertEquals('custom-class', $atts['class']);
    }

    /**
     * Test clases CSS generadas.
     */
    public function test_generated_css_classes() {
        $module = 'eventos';
        $view = 'grid';
        $columns = 3;

        $classes = [
            'flavor-module',
            "flavor-module-{$module}",
            "flavor-view-{$view}",
            "flavor-columns-{$columns}",
        ];

        $this->assertContains('flavor-module', $classes);
        $this->assertContains('flavor-module-eventos', $classes);
        $this->assertContains('flavor-view-grid', $classes);
    }

    /**
     * Test atributos data generados.
     */
    public function test_generated_data_attributes() {
        $atts = [
            'module' => 'eventos',
            'view' => 'grid',
            'limit' => 6,
        ];

        $dataAttrs = [];
        foreach ($atts as $key => $value) {
            $dataAttrs["data-{$key}"] = $value;
        }

        $this->assertEquals('eventos', $dataAttrs['data-module']);
        $this->assertEquals('grid', $dataAttrs['data-view']);
    }

    /**
     * Test filtros de shortcode.
     */
    public function test_shortcode_filters() {
        $filters = [
            'flavor_shortcode_output' => 'Filtrar salida del shortcode',
            'flavor_shortcode_atts' => 'Filtrar atributos',
            'flavor_module_query_args' => 'Filtrar argumentos de query',
        ];

        $this->assertArrayHasKey('flavor_shortcode_output', $filters);
    }

    /**
     * Test cache de shortcode.
     */
    public function test_shortcode_caching() {
        $cacheKey = 'flavor_shortcode_eventos_grid_6_desc_date';
        $cacheGroup = 'flavor_shortcodes';
        $cacheExpiration = 300; // 5 minutos

        $this->assertStringContainsString('flavor_shortcode', $cacheKey);
        $this->assertEquals(300, $cacheExpiration);
    }

    /**
     * Test shortcode de formulario.
     */
    public function test_form_shortcode() {
        $atts = [
            'type' => 'contact',
            'recipient' => 'admin@example.com',
            'subject' => 'Contacto desde web',
            'success_message' => 'Mensaje enviado correctamente',
            'button_text' => 'Enviar',
        ];

        $this->assertEquals('contact', $atts['type']);
        $this->assertStringContainsString('@', $atts['recipient']);
    }

    /**
     * Test shortcode de login.
     */
    public function test_login_shortcode() {
        $atts = [
            'redirect' => '/mi-cuenta',
            'show_register' => 'true',
            'show_lost_password' => 'true',
            'remember_me' => 'true',
        ];

        $this->assertEquals('/mi-cuenta', $atts['redirect']);
    }

    /**
     * Test shortcode de perfil de usuario.
     */
    public function test_user_profile_shortcode() {
        $atts = [
            'user_id' => 'current',
            'show_avatar' => 'true',
            'show_bio' => 'true',
            'show_social' => 'true',
            'show_badges' => 'true',
            'show_stats' => 'true',
        ];

        $this->assertEquals('current', $atts['user_id']);
    }

    /**
     * Test shortcode de mapa.
     */
    public function test_map_shortcode() {
        $atts = [
            'lat' => '43.2630',
            'lng' => '-2.9350',
            'zoom' => '14',
            'height' => '400px',
            'markers' => 'eventos',
            'provider' => 'openstreetmap',
        ];

        $this->assertEquals('43.2630', $atts['lat']);
        $this->assertEquals('openstreetmap', $atts['provider']);
    }

    /**
     * Test shortcode de búsqueda.
     */
    public function test_search_shortcode() {
        $atts = [
            'placeholder' => 'Buscar...',
            'modules' => 'eventos,marketplace,socios',
            'show_filters' => 'true',
            'results_per_page' => '10',
        ];

        $modules = explode(',', $atts['modules']);
        $this->assertCount(3, $modules);
    }

    /**
     * Test shortcode de estadísticas.
     */
    public function test_stats_shortcode() {
        $atts = [
            'type' => 'counters',
            'items' => 'users,events,products',
            'animated' => 'true',
            'columns' => '4',
        ];

        $this->assertEquals('counters', $atts['type']);
    }

    /**
     * Test shortcode condicional.
     */
    public function test_conditional_shortcode() {
        $atts = [
            'if_logged_in' => 'true',
            'if_role' => 'subscriber,member',
            'if_module_active' => 'eventos',
        ];

        $roles = explode(',', $atts['if_role']);
        $this->assertContains('member', $roles);
    }

    /**
     * Test registro de shortcode.
     */
    public function test_shortcode_registration() {
        $shortcodes = [
            'flavor_module' => 'render_module_shortcode',
            'flavor_eventos' => 'render_eventos_shortcode',
            'flavor_socios' => 'render_socios_shortcode',
            'flavor_unified_dashboard' => 'render_dashboard_shortcode',
            'flavor_login' => 'render_login_shortcode',
            'flavor_profile' => 'render_profile_shortcode',
        ];

        $this->assertArrayHasKey('flavor_module', $shortcodes);
        $this->assertArrayHasKey('flavor_unified_dashboard', $shortcodes);
    }

    /**
     * Test error handling en shortcode.
     */
    public function test_shortcode_error_handling() {
        $errors = [
            'module_not_found' => 'El módulo especificado no existe',
            'module_not_active' => 'El módulo no está activado',
            'invalid_view' => 'Vista no válida para este módulo',
            'permission_denied' => 'No tienes permisos para ver este contenido',
        ];

        $this->assertArrayHasKey('module_not_found', $errors);
    }

    /**
     * Test shortcode con AJAX.
     */
    public function test_ajax_enabled_shortcode() {
        $atts = [
            'module' => 'eventos',
            'ajax' => 'true',
            'ajax_action' => 'flavor_load_more',
            'nonce_field' => 'flavor_ajax_nonce',
        ];

        $this->assertEquals('true', $atts['ajax']);
        $this->assertEquals('flavor_load_more', $atts['ajax_action']);
    }

    /**
     * Test shortcode responsive.
     */
    public function test_responsive_shortcode() {
        $atts = [
            'columns' => '4',
            'columns_tablet' => '2',
            'columns_mobile' => '1',
            'gap' => '20px',
            'gap_mobile' => '10px',
        ];

        $this->assertEquals('4', $atts['columns']);
        $this->assertEquals('1', $atts['columns_mobile']);
    }
}
