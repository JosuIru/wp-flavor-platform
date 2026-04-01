<?php
/**
 * Tests para Flavor_Translation_Roles
 *
 * @package FlavorMultilingual
 */

class TranslationRolesTest extends PHPUnit\Framework\TestCase {

    /**
     * @var Flavor_Translation_Roles
     */
    private $roles;

    /**
     * Setup antes de cada test
     */
    protected function setUp(): void {
        parent::setUp();

        if (function_exists('wp_mock_reset')) {
            wp_mock_reset();
        }

        // Cargar clase si no está cargada
        if (!class_exists('Flavor_Translation_Roles')) {
            require_once dirname(__DIR__, 2) . '/includes/class-translation-roles.php';
        }

        // Resetear singleton
        $reflection = new ReflectionClass('Flavor_Translation_Roles');
        $instance_property = $reflection->getProperty('instance');
        $instance_property->setAccessible(true);
        $instance_property->setValue(null, null);

        $this->roles = Flavor_Translation_Roles::get_instance();
    }

    /**
     * Test: Singleton devuelve la misma instancia
     */
    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_Translation_Roles::get_instance();
        $instance2 = Flavor_Translation_Roles::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    /**
     * Test: Obtener etiquetas de capacidades
     */
    public function test_get_translation_caps_labels() {
        $labels = $this->roles->get_translation_caps_labels();

        $this->assertIsArray($labels);
        $this->assertArrayHasKey('flavor_translate', $labels);
        $this->assertArrayHasKey('flavor_approve_translations', $labels);
        $this->assertArrayHasKey('flavor_import_export_xliff', $labels);
    }

    /**
     * Test: Obtener estados de traducción
     */
    public function test_get_translation_statuses() {
        $statuses = $this->roles->get_translation_statuses();

        $this->assertIsArray($statuses);
        $this->assertArrayHasKey('pending', $statuses);
        $this->assertArrayHasKey('in_progress', $statuses);
        $this->assertArrayHasKey('needs_review', $statuses);
        $this->assertArrayHasKey('approved', $statuses);
        $this->assertArrayHasKey('rejected', $statuses);
        $this->assertArrayHasKey('published', $statuses);

        // Verificar estructura de cada estado
        foreach ($statuses as $status) {
            $this->assertArrayHasKey('label', $status);
            $this->assertArrayHasKey('color', $status);
            $this->assertArrayHasKey('icon', $status);
        }
    }

    /**
     * Test: Estados tienen colores válidos
     */
    public function test_status_colors_are_valid_hex() {
        $statuses = $this->roles->get_translation_statuses();

        foreach ($statuses as $key => $status) {
            $this->assertMatchesRegularExpression(
                '/^#[0-9a-fA-F]{6}$/',
                $status['color'],
                "Color inválido para estado: {$key}"
            );
        }
    }

    /**
     * Test: can_translate con permisos (mock siempre true)
     */
    public function test_can_translate_with_permission() {
        // Con mocks que siempre devuelven true para user_can
        $result = $this->roles->can_translate(false, 1, 0);

        // Con mocks, siempre debe poder traducir
        $this->assertTrue($result);
    }

    /**
     * Test: can_approve con permisos
     */
    public function test_can_approve_with_permission() {
        $result = $this->roles->can_approve(false, 1);

        $this->assertTrue($result);
    }

    /**
     * Test: can_assign con permisos
     */
    public function test_can_assign_with_permission() {
        $result = $this->roles->can_assign(false, 1);

        $this->assertTrue($result);
    }

    /**
     * Test: Obtener traductores asignados vacío
     */
    public function test_get_assigned_translators_empty() {
        $assigned = $this->roles->get_assigned_translators(999, 'post');

        $this->assertIsArray($assigned);
        $this->assertEmpty($assigned);
    }

    /**
     * Test: Obtener estadísticas de usuario
     */
    public function test_get_user_stats() {
        $stats = $this->roles->get_user_stats(1);

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('completed', $stats);
        $this->assertArrayHasKey('pending_review', $stats);
        $this->assertArrayHasKey('in_progress', $stats);
        $this->assertArrayHasKey('words', $stats);

        // Con mocks, todos deben ser 0
        $this->assertEquals(0, $stats['completed']);
        $this->assertEquals(0, $stats['pending_review']);
    }

    /**
     * Test: Historial de estados vacío para nuevo objeto
     */
    public function test_get_status_history_empty() {
        $history = $this->roles->get_status_history(999, 'es');

        $this->assertIsArray($history);
        $this->assertEmpty($history);
    }
}
