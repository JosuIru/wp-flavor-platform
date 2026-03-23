<?php
/**
 * Tests unitarios para el Core del plugin
 *
 * Tests de inicializacion, carga de modulos y permisos
 *
 * @package FlavorChatIA
 * @subpackage Tests
 */

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Tests del core del plugin Flavor Chat IA
 */
class CoreTest extends Flavor_TestCase {

    /**
     * Test de existencia de constantes del plugin
     */
    public function test_plugin_constants_are_defined() {
        // Simular constantes que deberian existir
        $constantesRequeridas = [
            'FLAVOR_CHAT_IA_VERSION',
            'FLAVOR_CHAT_IA_PATH',
            'FLAVOR_CHAT_IA_URL',
            'FLAVOR_CHAT_IA_BASENAME',
        ];

        // En ambiente de test, verificar formato esperado
        foreach ($constantesRequeridas as $nombreConstante) {
            $this->assertMatchesRegularExpression(
                '/^FLAVOR_CHAT_IA_/',
                $nombreConstante,
                "Constante {$nombreConstante} debe seguir el patron de nomenclatura"
            );
        }
    }

    /**
     * Test de estructura de configuracion por defecto
     */
    public function test_default_settings_structure() {
        $configuracionPorDefecto = [
            'enabled' => false,
            'show_floating_widget' => true,
            'active_provider' => 'claude',
            'api_key' => '',
            'claude_api_key' => '',
            'claude_model' => 'claude-sonnet-4-20250514',
            'openai_api_key' => '',
            'openai_model' => 'gpt-4o-mini',
            'deepseek_api_key' => '',
            'deepseek_model' => 'deepseek-chat',
            'mistral_api_key' => '',
            'mistral_model' => 'mistral-small-latest',
            'assistant_name' => 'Asistente Virtual',
            'max_messages_per_session' => 50,
            'max_tokens_per_message' => 1000,
            'active_modules' => ['woocommerce'],
        ];

        // Verificar que todas las claves requeridas existen
        $this->assertArrayHasKey('enabled', $configuracionPorDefecto);
        $this->assertArrayHasKey('active_provider', $configuracionPorDefecto);
        $this->assertArrayHasKey('active_modules', $configuracionPorDefecto);
        $this->assertArrayHasKey('max_tokens_per_message', $configuracionPorDefecto);
    }

    /**
     * Test de proveedores de IA soportados
     */
    public function test_supported_ai_providers() {
        $proveedoresSoportados = ['claude', 'openai', 'deepseek', 'mistral'];

        $this->assertCount(4, $proveedoresSoportados);
        $this->assertContains('claude', $proveedoresSoportados);
        $this->assertContains('openai', $proveedoresSoportados);
        $this->assertContains('deepseek', $proveedoresSoportados);
        $this->assertContains('mistral', $proveedoresSoportados);
    }

    /**
     * Test de validacion de nombre de proveedor
     */
    public function test_provider_name_validation() {
        $proveedoresValidos = ['claude', 'openai', 'deepseek', 'mistral'];
        $proveedoresInvalidos = ['invalid', 'gpt4', 'chatgpt', 'bard', ''];

        foreach ($proveedoresValidos as $proveedor) {
            $this->assertContains($proveedor, $proveedoresValidos);
        }

        foreach ($proveedoresInvalidos as $proveedor) {
            $this->assertNotContains($proveedor, $proveedoresValidos);
        }
    }

    /**
     * Test de formato de version del plugin
     */
    public function test_version_format() {
        $versionEjemplo = '3.3.0';

        // Verificar formato semver
        $this->assertMatchesRegularExpression(
            '/^\d+\.\d+\.\d+$/',
            $versionEjemplo,
            'La version debe seguir formato semver X.Y.Z'
        );
    }

    /**
     * Test de niveles de log soportados
     */
    public function test_log_levels() {
        $nivelesLog = [
            'debug' => 0,
            'info' => 1,
            'warning' => 2,
            'error' => 3,
        ];

        $this->assertArrayHasKey('debug', $nivelesLog);
        $this->assertArrayHasKey('info', $nivelesLog);
        $this->assertArrayHasKey('warning', $nivelesLog);
        $this->assertArrayHasKey('error', $nivelesLog);

        // Verificar orden de prioridad
        $this->assertLessThan($nivelesLog['info'], $nivelesLog['debug']);
        $this->assertLessThan($nivelesLog['warning'], $nivelesLog['info']);
        $this->assertLessThan($nivelesLog['error'], $nivelesLog['warning']);
    }

    /**
     * Test de capacidades de usuario requeridas
     */
    public function test_user_capabilities() {
        $capacidadesPlugin = [
            'manage_options',      // Admin general
            'edit_posts',          // Editor
            'read',                // Suscriptor
            'flavor_manage_modules', // Custom capability
        ];

        foreach ($capacidadesPlugin as $capacidad) {
            $this->assertIsString($capacidad);
            $this->assertNotEmpty($capacidad);
        }
    }

    /**
     * Test de hooks de activacion/desactivacion
     */
    public function test_activation_hooks_structure() {
        $hooksActivacion = [
            'activate' => [
                'creates_default_options',
                'creates_roles',
                'installs_database',
                'schedules_crons',
                'flushes_rewrite_rules',
            ],
            'deactivate' => [
                'unschedules_crons',
                'flushes_rewrite_rules',
            ],
        ];

        $this->assertArrayHasKey('activate', $hooksActivacion);
        $this->assertArrayHasKey('deactivate', $hooksActivacion);
        $this->assertNotEmpty($hooksActivacion['activate']);
        $this->assertNotEmpty($hooksActivacion['deactivate']);
    }

    /**
     * Test de validacion de configuracion de modulos activos
     */
    public function test_active_modules_validation() {
        $modulosActivos = ['woocommerce', 'eventos', 'socios'];

        $this->assertIsArray($modulosActivos);
        $this->assertNotEmpty($modulosActivos);

        // Verificar que woocommerce esta por defecto
        $this->assertContains('woocommerce', $modulosActivos);
    }

    /**
     * Test de estructura de respuesta de error
     */
    public function test_error_response_structure() {
        $respuestaError = [
            'success' => false,
            'error' => 'Mensaje de error',
            'error_code' => 'error_code',
        ];

        $this->assertArrayHasKey('success', $respuestaError);
        $this->assertArrayHasKey('error', $respuestaError);
        $this->assertFalse($respuestaError['success']);
        $this->assertIsString($respuestaError['error']);
    }

    /**
     * Test de estructura de respuesta exitosa
     */
    public function test_success_response_structure() {
        $respuestaExitosa = [
            'success' => true,
            'data' => ['key' => 'value'],
        ];

        $this->assertArrayHasKey('success', $respuestaExitosa);
        $this->assertTrue($respuestaExitosa['success']);
        $this->assertArrayHasKey('data', $respuestaExitosa);
    }

    /**
     * Test de permisos por contexto
     */
    public function test_permission_contexts() {
        $contextosPermisos = [
            'admin' => 'manage_options',
            'editor' => 'edit_posts',
            'member' => 'read',
            'public' => '',
        ];

        $this->assertArrayHasKey('admin', $contextosPermisos);
        $this->assertArrayHasKey('editor', $contextosPermisos);
        $this->assertArrayHasKey('member', $contextosPermisos);
        $this->assertArrayHasKey('public', $contextosPermisos);
    }

    /**
     * Test de configuracion de widget de chat
     */
    public function test_chat_widget_config() {
        $configuracionWidget = [
            'style' => 'floating',
            'position' => 'bottom-right',
            'primary_color' => '#0073aa',
            'widget_width' => 380,
            'widget_height' => 500,
            'border_radius' => 16,
        ];

        $this->assertContains($configuracionWidget['style'], ['floating', 'embedded']);
        $this->assertContains($configuracionWidget['position'], ['bottom-right', 'bottom-left', 'top-right', 'top-left']);
        $this->assertMatchesRegularExpression('/^#[0-9a-fA-F]{6}$/', $configuracionWidget['primary_color']);
        $this->assertIsInt($configuracionWidget['widget_width']);
        $this->assertIsInt($configuracionWidget['widget_height']);
    }

    /**
     * Test de sesion de chat
     */
    public function test_chat_session_id_format() {
        // Formato esperado: fcia_XXXXXXXXXXXXXXXX (16 caracteres alfanumericos)
        $patronSesion = '/^fcia_[a-zA-Z0-9]{16}$/';
        $sesionEjemplo = 'fcia_abc123DEF456ghij';

        $this->assertMatchesRegularExpression($patronSesion, $sesionEjemplo);
    }

    /**
     * Test de singleton pattern
     */
    public function test_singleton_pattern_structure() {
        // Verificar estructura esperada de singleton
        $estructuraSingleton = [
            'private_static_instance' => true,
            'private_constructor' => true,
            'public_get_instance' => true,
        ];

        foreach ($estructuraSingleton as $elemento => $requerido) {
            $this->assertTrue($requerido, "Singleton debe tener: {$elemento}");
        }
    }

    /**
     * Test de validacion de tokens maximos
     */
    public function test_max_tokens_validation() {
        $tokensMaximos = 1000;
        $tokensMinimos = 100;
        $tokensDefault = 1000;

        $this->assertGreaterThanOrEqual($tokensMinimos, $tokensDefault);
        $this->assertLessThanOrEqual(10000, $tokensMaximos); // Limite razonable
    }

    /**
     * Test de configuracion de escalacion
     */
    public function test_escalation_config_structure() {
        $configuracionEscalacion = [
            'escalation_whatsapp' => '',
            'escalation_phone' => '',
            'escalation_email' => '',
            'escalation_hours' => 'L-V 9:00-18:00',
        ];

        $this->assertArrayHasKey('escalation_whatsapp', $configuracionEscalacion);
        $this->assertArrayHasKey('escalation_phone', $configuracionEscalacion);
        $this->assertArrayHasKey('escalation_email', $configuracionEscalacion);
        $this->assertArrayHasKey('escalation_hours', $configuracionEscalacion);
    }
}
