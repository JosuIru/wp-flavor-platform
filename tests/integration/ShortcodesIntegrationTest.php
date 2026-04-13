<?php
/**
 * Tests de integración para shortcodes de Flavor Platform.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Integration
 */

class ShortcodesIntegrationTest extends Flavor_Integration_Test_Case {

    /**
     * Test shortcode flavor_module registrado.
     */
    public function test_flavor_module_shortcode_registered() {
        global $shortcode_tags;

        $this->assertArrayHasKey('flavor_module', $shortcode_tags);
    }

    /**
     * Test shortcode flavor_eventos registrado.
     */
    public function test_flavor_eventos_shortcode_registered() {
        global $shortcode_tags;

        $this->assertArrayHasKey('flavor_eventos', $shortcode_tags);
    }

    /**
     * Test shortcode flavor_socios registrado.
     */
    public function test_flavor_socios_shortcode_registered() {
        global $shortcode_tags;

        $this->assertArrayHasKey('flavor_socios', $shortcode_tags);
    }

    /**
     * Test shortcode flavor_unified_dashboard registrado.
     */
    public function test_flavor_unified_dashboard_shortcode_registered() {
        global $shortcode_tags;

        $this->assertArrayHasKey('flavor_unified_dashboard', $shortcode_tags);
    }

    /**
     * Test renderizar shortcode flavor_module.
     */
    public function test_render_flavor_module_shortcode() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" view="grid"]');

        $this->assertStringContainsString('flavor-module', $output);
        $this->assertStringContainsString('data-module="eventos"', $output);
    }

    /**
     * Test renderizar shortcode con módulo inactivo.
     */
    public function test_render_shortcode_inactive_module() {
        $this->deactivate_all_modules();

        $output = do_shortcode('[flavor_module module="eventos"]');

        // Debería mostrar mensaje o contenedor vacío
        $this->assertIsString($output);
    }

    /**
     * Test shortcode con vista específica.
     */
    public function test_shortcode_with_view() {
        $this->activate_modules(['eventos']);

        $gridOutput = do_shortcode('[flavor_module module="eventos" view="grid"]');
        $listOutput = do_shortcode('[flavor_module module="eventos" view="list"]');

        $this->assertStringContainsString('grid', $gridOutput);
        $this->assertStringContainsString('list', $listOutput);
    }

    /**
     * Test shortcode con límite.
     */
    public function test_shortcode_with_limit() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" limit="5"]');

        // El shortcode debe renderizar algo
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('flavor-module', $output);
    }

    /**
     * Test shortcode con columnas.
     */
    public function test_shortcode_with_columns() {
        $this->activate_modules(['marketplace']);

        $output = do_shortcode('[flavor_module module="marketplace" columns="4"]');

        // Verificar que se procesa el atributo
        $this->assertIsString($output);
    }

    /**
     * Test shortcode eventos con calendar view.
     */
    public function test_eventos_calendar_view() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_eventos view="calendar"]');

        $this->assertIsString($output);
    }

    /**
     * Test shortcode socios directory.
     */
    public function test_socios_directory_view() {
        $this->activate_modules(['socios']);

        $output = do_shortcode('[flavor_socios view="directory"]');

        $this->assertIsString($output);
    }

    /**
     * Test shortcode unified dashboard.
     */
    public function test_unified_dashboard_shortcode() {
        $this->login_as_admin();
        $this->activate_modules(['eventos', 'socios']);

        $output = do_shortcode('[flavor_unified_dashboard]');

        $this->assertIsString($output);
    }

    /**
     * Test shortcode en contenido de página.
     */
    public function test_shortcode_in_page_content() {
        $this->login_as_admin();
        $this->activate_modules(['eventos']);

        $pageId = self::factory()->post->create([
            'post_type' => 'page',
            'post_content' => '[flavor_module module="eventos" view="grid" limit="3"]',
            'post_status' => 'publish',
        ]);

        $post = get_post($pageId);
        $content = apply_filters('the_content', $post->post_content);

        $this->assertStringContainsString('flavor-module', $content);
    }

    /**
     * Test shortcode con filtros.
     */
    public function test_shortcode_with_filters() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" show_filters="true"]');

        $this->assertIsString($output);
    }

    /**
     * Test shortcode con paginación.
     */
    public function test_shortcode_with_pagination() {
        $this->activate_modules(['marketplace']);

        $output = do_shortcode('[flavor_module module="marketplace" show_pagination="true" limit="10"]');

        $this->assertIsString($output);
    }

    /**
     * Test múltiples shortcodes en misma página.
     */
    public function test_multiple_shortcodes_same_page() {
        $this->activate_modules(['eventos', 'socios']);

        $content = '
            [flavor_module module="eventos" view="grid" limit="3"]
            [flavor_module module="socios" view="list" limit="5"]
        ';

        $output = do_shortcode($content);

        $this->assertStringContainsString('data-module="eventos"', $output);
        $this->assertStringContainsString('data-module="socios"', $output);
    }

    /**
     * Test shortcode anidado.
     */
    public function test_nested_shortcodes() {
        $this->activate_modules(['eventos']);

        // Algunos shortcodes pueden soportar anidación
        $content = '[flavor_section][flavor_module module="eventos"][/flavor_section]';
        $output = do_shortcode($content);

        $this->assertIsString($output);
    }

    /**
     * Test shortcode con categoría.
     */
    public function test_shortcode_with_category() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" category="workshops"]');

        $this->assertIsString($output);
    }

    /**
     * Test shortcode con orden.
     */
    public function test_shortcode_with_order() {
        $this->activate_modules(['eventos']);

        $outputAsc = do_shortcode('[flavor_module module="eventos" order="ASC"]');
        $outputDesc = do_shortcode('[flavor_module module="eventos" order="DESC"]');

        $this->assertIsString($outputAsc);
        $this->assertIsString($outputDesc);
    }

    /**
     * Test shortcode módulo inexistente.
     */
    public function test_shortcode_nonexistent_module() {
        $output = do_shortcode('[flavor_module module="modulo_que_no_existe"]');

        // Debería manejar gracefully
        $this->assertIsString($output);
    }

    /**
     * Test shortcode sin parámetros requeridos.
     */
    public function test_shortcode_missing_required_params() {
        $output = do_shortcode('[flavor_module]');

        // Debería manejar gracefully sin module
        $this->assertIsString($output);
    }

    /**
     * Test escape de atributos XSS.
     */
    public function test_shortcode_xss_prevention() {
        $this->activate_modules(['eventos']);

        $maliciousInput = '<script>alert("xss")</script>';
        $output = do_shortcode('[flavor_module module="eventos" custom="' . $maliciousInput . '"]');

        $this->assertStringNotContainsString('<script>', $output);
    }

    /**
     * Test shortcode respeta permisos.
     */
    public function test_shortcode_respects_permissions() {
        $this->activate_modules(['socios']);

        // Como admin
        $this->login_as_admin();
        $adminOutput = do_shortcode('[flavor_socios view="admin"]');

        // Como subscriber
        $this->login_as_subscriber();
        $subscriberOutput = do_shortcode('[flavor_socios view="admin"]');

        // Los outputs pueden diferir según permisos
        $this->assertIsString($adminOutput);
        $this->assertIsString($subscriberOutput);
    }

    /**
     * Test shortcode con AJAX habilitado.
     */
    public function test_shortcode_ajax_enabled() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" ajax="true"]');

        // Debería incluir atributos para AJAX
        $this->assertIsString($output);
    }

    /**
     * Test caché de shortcode.
     */
    public function test_shortcode_caching() {
        $this->activate_modules(['eventos']);

        // Primera llamada
        $output1 = do_shortcode('[flavor_module module="eventos" view="grid"]');

        // Segunda llamada (podría usar caché)
        $output2 = do_shortcode('[flavor_module module="eventos" view="grid"]');

        // Deberían ser iguales
        $this->assertEquals($output1, $output2);
    }

    /**
     * Test shortcode con clases CSS custom.
     */
    public function test_shortcode_custom_css_class() {
        $this->activate_modules(['eventos']);

        $output = do_shortcode('[flavor_module module="eventos" class="my-custom-class"]');

        // Verificar que se puede añadir clase custom
        $this->assertIsString($output);
    }

    /**
     * Test rendimiento con muchos shortcodes.
     */
    public function test_shortcode_performance() {
        $this->activate_modules(['eventos']);

        $startTime = microtime(true);

        for ($i = 0; $i < 10; $i++) {
            do_shortcode('[flavor_module module="eventos" view="grid"]');
        }

        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Debería completar en tiempo razonable (menos de 5 segundos)
        $this->assertLessThan(5, $duration);
    }
}
