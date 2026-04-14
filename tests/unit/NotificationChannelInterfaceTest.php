<?php
/**
 * Tests unitarios para la interfaz Flavor_Notification_Channel_Interface.
 *
 * Verifica que la interfaz define correctamente el contrato para canales
 * de notificacion y que los mocks pueden implementarla correctamente.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

require_once dirname( __DIR__ ) . '/bootstrap.php';
require_once FLAVOR_PLUGIN_DIR . '/includes/interfaces/interface-notification-channel.php';

/**
 * Mock implementation of Flavor_Notification_Channel_Interface for testing.
 *
 * Implementacion de prueba que simula un canal de notificacion push.
 */
class Mock_Push_Notification_Channel implements Flavor_Notification_Channel_Interface {

    /**
     * Configuracion del canal.
     *
     * @var array
     */
    private $configuration = [];

    /**
     * Estado de habilitacion.
     *
     * @var bool
     */
    private $enabled = true;

    /**
     * Tipos de notificacion soportados.
     *
     * @var array
     */
    private $supported_types = [ 'alert', 'message', 'update', 'security_alert' ];

    /**
     * Historial de envios para verificacion en tests.
     *
     * @var array
     */
    public $send_history = [];

    /**
     * Usuarios con tokens validos para testing.
     *
     * @var array
     */
    private $users_with_tokens = [ 1, 2, 3, 5, 10 ];

    /**
     * {@inheritDoc}
     */
    public function get_id(): string {
        return 'push';
    }

    /**
     * {@inheritDoc}
     */
    public function get_name(): string {
        return 'Push Notifications';
    }

    /**
     * {@inheritDoc}
     */
    public function get_description(): string {
        return 'Envia notificaciones push a dispositivos moviles y navegadores.';
    }

    /**
     * {@inheritDoc}
     */
    public function get_icon(): string {
        return 'dashicons-bell';
    }

    /**
     * {@inheritDoc}
     */
    public function is_enabled(): bool {
        return $this->enabled && ! empty( $this->configuration['api_key'] );
    }

    /**
     * {@inheritDoc}
     */
    public function supports( string $notification_type ): bool {
        return in_array( $notification_type, $this->supported_types, true );
    }

    /**
     * {@inheritDoc}
     */
    public function send( int $user_id, string $title, string $message, array $data = [] ): array {
        if ( ! $this->is_enabled() ) {
            return [
                'success'  => false,
                'enviados' => 0,
                'fallidos' => 1,
                'error'    => 'Canal no habilitado',
            ];
        }

        if ( ! $this->is_available_for_user( $user_id ) ) {
            return [
                'success'  => false,
                'enviados' => 0,
                'fallidos' => 1,
                'error'    => 'Usuario sin token push registrado',
            ];
        }

        // Registrar envio para verificacion
        $this->send_history[] = [
            'user_id' => $user_id,
            'title'   => $title,
            'message' => $message,
            'data'    => $data,
            'time'    => time(),
        ];

        return [
            'success'        => true,
            'enviados'       => 1,
            'fallidos'       => 0,
            'error'          => null,
            'message_id'     => 'push_' . uniqid(),
            'delivery_token' => 'delivery_' . $user_id,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function send_batch( array $user_ids, string $title, string $message, array $data = [] ): array {
        $total_usuarios = count( $user_ids );
        $enviados = 0;
        $fallidos = 0;
        $sin_destinatario = 0;
        $resultados = [];

        foreach ( $user_ids as $user_id ) {
            if ( ! $this->is_available_for_user( $user_id ) ) {
                $sin_destinatario++;
                $resultados[ $user_id ] = [
                    'success' => false,
                    'error'   => 'Sin token push',
                ];
                continue;
            }

            $result = $this->send( $user_id, $title, $message, $data );
            $resultados[ $user_id ] = $result;

            if ( $result['success'] ) {
                $enviados++;
            } else {
                $fallidos++;
            }
        }

        return [
            'total_usuarios'   => $total_usuarios,
            'enviados'         => $enviados,
            'fallidos'         => $fallidos,
            'sin_destinatario' => $sin_destinatario,
            'resultados'       => $resultados,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function handle_from_queue( array $payload ): bool {
        if ( empty( $payload['user_id'] ) || empty( $payload['title'] ) ) {
            return false;
        }

        $result = $this->send(
            (int) $payload['user_id'],
            $payload['title'],
            $payload['message'] ?? '',
            $payload['data'] ?? []
        );

        return $result['success'];
    }

    /**
     * {@inheritDoc}
     */
    public function get_configuration_fields(): array {
        return [
            'api_key'       => [
                'label'       => 'API Key',
                'type'        => 'password',
                'description' => 'Clave de API del servicio push',
                'required'    => true,
                'default'     => '',
            ],
            'sender_id'     => [
                'label'       => 'Sender ID',
                'type'        => 'text',
                'description' => 'ID del remitente para FCM',
                'required'    => false,
                'default'     => '',
            ],
            'default_icon'  => [
                'label'       => 'Icono por defecto',
                'type'        => 'text',
                'description' => 'URL del icono de notificacion',
                'required'    => false,
                'default'     => '/images/notification-icon.png',
            ],
            'sound_enabled' => [
                'label'       => 'Sonido habilitado',
                'type'        => 'checkbox',
                'description' => 'Reproducir sonido con la notificacion',
                'required'    => false,
                'default'     => true,
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function configure( array $settings ): bool {
        $valid_keys = array_keys( $this->get_configuration_fields() );

        foreach ( $settings as $key => $value ) {
            if ( in_array( $key, $valid_keys, true ) ) {
                $this->configuration[ $key ] = $value;
            }
        }

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function get_configuration(): array {
        return $this->configuration;
    }

    /**
     * {@inheritDoc}
     */
    public function validate_configuration(): array {
        $errors = [];
        $warnings = [];

        if ( empty( $this->configuration['api_key'] ) ) {
            $errors[] = 'API Key es requerida';
        }

        if ( empty( $this->configuration['sender_id'] ) ) {
            $warnings[] = 'Sender ID no configurado, se usara el valor por defecto';
        }

        return [
            'valid'    => empty( $errors ),
            'errors'   => $errors,
            'warnings' => $warnings,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function is_available_for_user( int $user_id ): bool {
        return in_array( $user_id, $this->users_with_tokens, true );
    }

    /**
     * {@inheritDoc}
     */
    public function get_priority(): int {
        return 5;
    }

    /**
     * {@inheritDoc}
     */
    public function get_rate_limit(): array {
        return [
            'requests_per_second' => 100,
            'requests_per_minute' => 5000,
            'batch_size'          => 500,
        ];
    }

    /**
     * Helper para tests: establecer estado enabled.
     *
     * @param bool $enabled Estado.
     */
    public function set_enabled( bool $enabled ): void {
        $this->enabled = $enabled;
    }

    /**
     * Helper para tests: agregar usuario con token.
     *
     * @param int $user_id ID del usuario.
     */
    public function add_user_with_token( int $user_id ): void {
        if ( ! in_array( $user_id, $this->users_with_tokens, true ) ) {
            $this->users_with_tokens[] = $user_id;
        }
    }

    /**
     * Helper para tests: limpiar historial de envios.
     */
    public function clear_send_history(): void {
        $this->send_history = [];
    }
}

/**
 * Test case for Flavor_Notification_Channel_Interface.
 */
class NotificationChannelInterfaceTest extends VBP_UnitTestCase {

    /**
     * Instancia del canal mock para tests.
     *
     * @var Mock_Push_Notification_Channel
     */
    private $channel;

    /**
     * Set up before each test.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->channel = new Mock_Push_Notification_Channel();
        $this->channel->configure( [ 'api_key' => 'test-api-key-12345' ] );
    }

    /**
     * Tear down after each test.
     */
    protected function tearDown(): void {
        $this->channel->clear_send_history();
        parent::tearDown();
    }

    // =========================================================================
    // Tests: Verificar existencia de metodos de la interfaz
    // =========================================================================

    /**
     * Test que la interfaz existe.
     */
    public function test_interface_exists() {
        $this->assertTrue(
            interface_exists( 'Flavor_Notification_Channel_Interface' ),
            'La interfaz Flavor_Notification_Channel_Interface debe existir'
        );
    }

    /**
     * Test que el mock implementa la interfaz correctamente.
     */
    public function test_mock_implements_interface() {
        $this->assertInstanceOf(
            Flavor_Notification_Channel_Interface::class,
            $this->channel,
            'El mock debe implementar Flavor_Notification_Channel_Interface'
        );
    }

    /**
     * Test que todos los metodos requeridos existen en la interfaz.
     */
    public function test_interface_has_all_required_methods() {
        $required_methods = [
            'get_id',
            'get_name',
            'get_description',
            'get_icon',
            'is_enabled',
            'supports',
            'send',
            'send_batch',
            'handle_from_queue',
            'get_configuration_fields',
            'configure',
            'get_configuration',
            'validate_configuration',
            'is_available_for_user',
            'get_priority',
            'get_rate_limit',
        ];

        $reflection = new ReflectionClass( Flavor_Notification_Channel_Interface::class );
        $interface_methods = array_map(
            fn( $method ) => $method->getName(),
            $reflection->getMethods()
        );

        foreach ( $required_methods as $method_name ) {
            $this->assertContains(
                $method_name,
                $interface_methods,
                "La interfaz debe definir el metodo: {$method_name}"
            );
        }
    }

    // =========================================================================
    // Tests: Metodos de identificacion
    // =========================================================================

    /**
     * Test get_id retorna string.
     */
    public function test_get_id_returns_string() {
        $channel_id = $this->channel->get_id();

        $this->assertIsString( $channel_id );
        $this->assertNotEmpty( $channel_id );
        $this->assertEquals( 'push', $channel_id );
    }

    /**
     * Test get_name retorna string.
     */
    public function test_get_name_returns_string() {
        $channel_name = $this->channel->get_name();

        $this->assertIsString( $channel_name );
        $this->assertNotEmpty( $channel_name );
    }

    /**
     * Test get_description retorna string.
     */
    public function test_get_description_returns_string() {
        $description = $this->channel->get_description();

        $this->assertIsString( $description );
        $this->assertNotEmpty( $description );
    }

    /**
     * Test get_icon retorna string.
     */
    public function test_get_icon_returns_string() {
        $icon = $this->channel->get_icon();

        $this->assertIsString( $icon );
        $this->assertNotEmpty( $icon );
    }

    // =========================================================================
    // Tests: Estado del canal
    // =========================================================================

    /**
     * Test is_enabled retorna bool.
     */
    public function test_is_enabled_returns_bool() {
        $this->assertIsBool( $this->channel->is_enabled() );
    }

    /**
     * Test canal habilitado con configuracion valida.
     */
    public function test_channel_enabled_with_valid_config() {
        $this->channel->configure( [ 'api_key' => 'valid-key' ] );
        $this->assertTrue( $this->channel->is_enabled() );
    }

    /**
     * Test canal deshabilitado sin API key.
     */
    public function test_channel_disabled_without_api_key() {
        $new_channel = new Mock_Push_Notification_Channel();
        // Sin configurar api_key
        $this->assertFalse( $new_channel->is_enabled() );
    }

    // =========================================================================
    // Tests: Soporte de tipos de notificacion
    // =========================================================================

    /**
     * Test supports retorna bool.
     */
    public function test_supports_returns_bool() {
        $this->assertIsBool( $this->channel->supports( 'alert' ) );
    }

    /**
     * Test soporta tipos conocidos.
     */
    public function test_supports_known_types() {
        $this->assertTrue( $this->channel->supports( 'alert' ) );
        $this->assertTrue( $this->channel->supports( 'message' ) );
        $this->assertTrue( $this->channel->supports( 'security_alert' ) );
    }

    /**
     * Test no soporta tipos desconocidos.
     */
    public function test_does_not_support_unknown_types() {
        $this->assertFalse( $this->channel->supports( 'video_call' ) );
        $this->assertFalse( $this->channel->supports( 'unknown_type' ) );
    }

    // =========================================================================
    // Tests: Envio de notificaciones
    // =========================================================================

    /**
     * Test send retorna array con estructura esperada.
     */
    public function test_send_returns_array() {
        $result = $this->channel->send( 1, 'Test Title', 'Test Message' );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'success', $result );
        $this->assertArrayHasKey( 'enviados', $result );
        $this->assertArrayHasKey( 'fallidos', $result );
        $this->assertArrayHasKey( 'error', $result );
    }

    /**
     * Test envio exitoso a usuario con token.
     */
    public function test_send_success_for_user_with_token() {
        $result = $this->channel->send( 1, 'Test', 'Message', [ 'link' => '/test' ] );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 1, $result['enviados'] );
        $this->assertEquals( 0, $result['fallidos'] );
        $this->assertNull( $result['error'] );
    }

    /**
     * Test envio fallido a usuario sin token.
     */
    public function test_send_fails_for_user_without_token() {
        $result = $this->channel->send( 999, 'Test', 'Message' );

        $this->assertFalse( $result['success'] );
        $this->assertEquals( 0, $result['enviados'] );
        $this->assertEquals( 1, $result['fallidos'] );
        $this->assertNotNull( $result['error'] );
    }

    /**
     * Test envio fallido cuando canal deshabilitado.
     */
    public function test_send_fails_when_disabled() {
        $this->channel->set_enabled( false );
        $result = $this->channel->send( 1, 'Test', 'Message' );

        $this->assertFalse( $result['success'] );
        $this->assertStringContainsString( 'no habilitado', $result['error'] );
    }

    /**
     * Test historial de envios se registra correctamente.
     */
    public function test_send_history_is_recorded() {
        $this->channel->send( 1, 'Title 1', 'Message 1' );
        $this->channel->send( 2, 'Title 2', 'Message 2' );

        $this->assertCount( 2, $this->channel->send_history );
        $this->assertEquals( 'Title 1', $this->channel->send_history[0]['title'] );
        $this->assertEquals( 2, $this->channel->send_history[1]['user_id'] );
    }

    // =========================================================================
    // Tests: Envio batch
    // =========================================================================

    /**
     * Test send_batch retorna estructura correcta.
     */
    public function test_send_batch_returns_correct_structure() {
        $result = $this->channel->send_batch( [ 1, 2, 999 ], 'Batch Title', 'Batch Message' );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'total_usuarios', $result );
        $this->assertArrayHasKey( 'enviados', $result );
        $this->assertArrayHasKey( 'fallidos', $result );
        $this->assertArrayHasKey( 'sin_destinatario', $result );
        $this->assertArrayHasKey( 'resultados', $result );
    }

    /**
     * Test send_batch cuenta correctamente exitos y fallos.
     */
    public function test_send_batch_counts_correctly() {
        // Usuarios 1, 2, 3 tienen token; 999 no
        $result = $this->channel->send_batch( [ 1, 2, 3, 999 ], 'Test', 'Message' );

        $this->assertEquals( 4, $result['total_usuarios'] );
        $this->assertEquals( 3, $result['enviados'] );
        $this->assertEquals( 0, $result['fallidos'] );
        $this->assertEquals( 1, $result['sin_destinatario'] );
    }

    /**
     * Test send_batch incluye resultados por usuario.
     */
    public function test_send_batch_includes_per_user_results() {
        $result = $this->channel->send_batch( [ 1, 999 ], 'Test', 'Message' );

        $this->assertArrayHasKey( 1, $result['resultados'] );
        $this->assertArrayHasKey( 999, $result['resultados'] );
        $this->assertTrue( $result['resultados'][1]['success'] );
        $this->assertFalse( $result['resultados'][999]['success'] );
    }

    // =========================================================================
    // Tests: Procesamiento de cola
    // =========================================================================

    /**
     * Test handle_from_queue retorna bool.
     */
    public function test_handle_from_queue_returns_bool() {
        $payload = [
            'user_id' => 1,
            'title'   => 'Queue Title',
            'message' => 'Queue Message',
        ];

        $result = $this->channel->handle_from_queue( $payload );
        $this->assertIsBool( $result );
    }

    /**
     * Test handle_from_queue exitoso con payload valido.
     */
    public function test_handle_from_queue_success() {
        $payload = [
            'user_id'         => 1,
            'title'           => 'Queue Title',
            'message'         => 'Queue Message',
            'notification_id' => 123,
            'type'            => 'alert',
        ];

        $result = $this->channel->handle_from_queue( $payload );
        $this->assertTrue( $result );
    }

    /**
     * Test handle_from_queue falla sin user_id.
     */
    public function test_handle_from_queue_fails_without_user_id() {
        $payload = [
            'title'   => 'Queue Title',
            'message' => 'Queue Message',
        ];

        $result = $this->channel->handle_from_queue( $payload );
        $this->assertFalse( $result );
    }

    /**
     * Test handle_from_queue falla sin title.
     */
    public function test_handle_from_queue_fails_without_title() {
        $payload = [
            'user_id' => 1,
            'message' => 'Queue Message',
        ];

        $result = $this->channel->handle_from_queue( $payload );
        $this->assertFalse( $result );
    }

    // =========================================================================
    // Tests: Configuracion
    // =========================================================================

    /**
     * Test get_configuration_fields retorna array.
     */
    public function test_get_configuration_fields_returns_array() {
        $fields = $this->channel->get_configuration_fields();

        $this->assertIsArray( $fields );
        $this->assertNotEmpty( $fields );
    }

    /**
     * Test estructura de campos de configuracion.
     */
    public function test_configuration_fields_structure() {
        $fields = $this->channel->get_configuration_fields();

        foreach ( $fields as $field_id => $field_config ) {
            $this->assertIsString( $field_id );
            $this->assertIsArray( $field_config );
            $this->assertArrayHasKey( 'label', $field_config );
            $this->assertArrayHasKey( 'type', $field_config );
            $this->assertArrayHasKey( 'required', $field_config );
        }
    }

    /**
     * Test configure retorna bool.
     */
    public function test_configure_returns_bool() {
        $result = $this->channel->configure( [ 'api_key' => 'new-key' ] );
        $this->assertIsBool( $result );
    }

    /**
     * Test configure guarda configuracion.
     */
    public function test_configure_stores_settings() {
        $this->channel->configure( [
            'api_key'   => 'my-api-key',
            'sender_id' => 'sender-123',
        ] );

        $config = $this->channel->get_configuration();
        $this->assertEquals( 'my-api-key', $config['api_key'] );
        $this->assertEquals( 'sender-123', $config['sender_id'] );
    }

    /**
     * Test get_configuration retorna array.
     */
    public function test_get_configuration_returns_array() {
        $config = $this->channel->get_configuration();
        $this->assertIsArray( $config );
    }

    // =========================================================================
    // Tests: Validacion de configuracion
    // =========================================================================

    /**
     * Test validate_configuration retorna estructura correcta.
     */
    public function test_validate_configuration_returns_correct_structure() {
        $validation = $this->channel->validate_configuration();

        $this->assertIsArray( $validation );
        $this->assertArrayHasKey( 'valid', $validation );
        $this->assertArrayHasKey( 'errors', $validation );
        $this->assertArrayHasKey( 'warnings', $validation );
        $this->assertIsBool( $validation['valid'] );
        $this->assertIsArray( $validation['errors'] );
        $this->assertIsArray( $validation['warnings'] );
    }

    /**
     * Test validacion exitosa con configuracion completa.
     */
    public function test_validate_configuration_success() {
        $this->channel->configure( [
            'api_key'   => 'valid-key',
            'sender_id' => 'sender-123',
        ] );

        $validation = $this->channel->validate_configuration();
        $this->assertTrue( $validation['valid'] );
        $this->assertEmpty( $validation['errors'] );
    }

    /**
     * Test validacion falla sin API key.
     */
    public function test_validate_configuration_fails_without_api_key() {
        $new_channel = new Mock_Push_Notification_Channel();
        $validation = $new_channel->validate_configuration();

        $this->assertFalse( $validation['valid'] );
        $this->assertNotEmpty( $validation['errors'] );
    }

    // =========================================================================
    // Tests: Disponibilidad por usuario
    // =========================================================================

    /**
     * Test is_available_for_user retorna bool.
     */
    public function test_is_available_for_user_returns_bool() {
        $this->assertIsBool( $this->channel->is_available_for_user( 1 ) );
    }

    /**
     * Test disponibilidad para usuario con token.
     */
    public function test_available_for_user_with_token() {
        $this->assertTrue( $this->channel->is_available_for_user( 1 ) );
        $this->assertTrue( $this->channel->is_available_for_user( 2 ) );
    }

    /**
     * Test no disponible para usuario sin token.
     */
    public function test_not_available_for_user_without_token() {
        $this->assertFalse( $this->channel->is_available_for_user( 999 ) );
        $this->assertFalse( $this->channel->is_available_for_user( 1000 ) );
    }

    // =========================================================================
    // Tests: Prioridad y rate limiting
    // =========================================================================

    /**
     * Test get_priority retorna int.
     */
    public function test_get_priority_returns_int() {
        $priority = $this->channel->get_priority();

        $this->assertIsInt( $priority );
        $this->assertGreaterThanOrEqual( 0, $priority );
    }

    /**
     * Test get_rate_limit retorna estructura correcta.
     */
    public function test_get_rate_limit_returns_correct_structure() {
        $rate_limit = $this->channel->get_rate_limit();

        $this->assertIsArray( $rate_limit );
        $this->assertArrayHasKey( 'requests_per_second', $rate_limit );
        $this->assertArrayHasKey( 'requests_per_minute', $rate_limit );
        $this->assertArrayHasKey( 'batch_size', $rate_limit );
    }

    /**
     * Test rate limit tiene valores numericos.
     */
    public function test_rate_limit_has_numeric_values() {
        $rate_limit = $this->channel->get_rate_limit();

        if ( $rate_limit['requests_per_second'] !== null ) {
            $this->assertIsInt( $rate_limit['requests_per_second'] );
        }
        if ( $rate_limit['requests_per_minute'] !== null ) {
            $this->assertIsInt( $rate_limit['requests_per_minute'] );
        }
        $this->assertIsInt( $rate_limit['batch_size'] );
        $this->assertGreaterThan( 0, $rate_limit['batch_size'] );
    }

    // =========================================================================
    // Tests: Flujo completo is_enabled -> supports -> send
    // =========================================================================

    /**
     * Test flujo completo exitoso.
     */
    public function test_complete_flow_success() {
        $notification_type = 'alert';
        $user_id = 1;
        $title = 'Security Alert';
        $message = 'Unusual login detected';
        $data = [
            'link'     => '/security/alerts/123',
            'priority' => 'high',
        ];

        // Paso 1: Verificar canal habilitado
        $this->assertTrue(
            $this->channel->is_enabled(),
            'Canal debe estar habilitado'
        );

        // Paso 2: Verificar soporte de tipo
        $this->assertTrue(
            $this->channel->supports( $notification_type ),
            'Canal debe soportar tipo de notificacion'
        );

        // Paso 3: Verificar disponibilidad para usuario
        $this->assertTrue(
            $this->channel->is_available_for_user( $user_id ),
            'Canal debe estar disponible para el usuario'
        );

        // Paso 4: Enviar notificacion
        $result = $this->channel->send( $user_id, $title, $message, $data );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( 1, $result['enviados'] );

        // Verificar que se registro en historial
        $this->assertCount( 1, $this->channel->send_history );
        $this->assertEquals( $title, $this->channel->send_history[0]['title'] );
    }

    /**
     * Test flujo falla si canal no habilitado.
     */
    public function test_flow_fails_if_channel_disabled() {
        $this->channel->set_enabled( false );

        // Verificar estado
        $this->assertFalse( $this->channel->is_enabled() );

        // Intento de envio debe fallar
        $result = $this->channel->send( 1, 'Test', 'Message' );
        $this->assertFalse( $result['success'] );
    }

    /**
     * Test flujo falla si tipo no soportado.
     */
    public function test_flow_fails_if_type_not_supported() {
        $unsupported_type = 'video_conference';

        // Verificar que no es soportado
        $this->assertFalse( $this->channel->supports( $unsupported_type ) );

        // En un sistema real, se deberia verificar antes de enviar
        // Este test documenta el comportamiento esperado
    }

    /**
     * Test flujo falla si usuario no disponible.
     */
    public function test_flow_fails_if_user_not_available() {
        $user_without_token = 999;

        // Verificar no disponibilidad
        $this->assertFalse( $this->channel->is_available_for_user( $user_without_token ) );

        // Envio debe fallar
        $result = $this->channel->send( $user_without_token, 'Test', 'Message' );
        $this->assertFalse( $result['success'] );
    }

    // =========================================================================
    // Tests: Casos edge
    // =========================================================================

    /**
     * Test envio con data vacio.
     */
    public function test_send_with_empty_data() {
        $result = $this->channel->send( 1, 'Title', 'Message', [] );

        $this->assertTrue( $result['success'] );
    }

    /**
     * Test envio con mensaje vacio.
     */
    public function test_send_with_empty_message() {
        $result = $this->channel->send( 1, 'Title', '' );

        $this->assertTrue( $result['success'] );
        $this->assertEquals( '', $this->channel->send_history[0]['message'] );
    }

    /**
     * Test batch con array vacio.
     */
    public function test_send_batch_with_empty_array() {
        $result = $this->channel->send_batch( [], 'Title', 'Message' );

        $this->assertEquals( 0, $result['total_usuarios'] );
        $this->assertEquals( 0, $result['enviados'] );
    }

    /**
     * Test batch con un solo usuario.
     */
    public function test_send_batch_with_single_user() {
        $result = $this->channel->send_batch( [ 1 ], 'Title', 'Message' );

        $this->assertEquals( 1, $result['total_usuarios'] );
        $this->assertEquals( 1, $result['enviados'] );
    }

    /**
     * Test configuracion con keys invalidas se ignoran.
     */
    public function test_configure_ignores_invalid_keys() {
        $this->channel->configure( [
            'api_key'     => 'valid-key',
            'invalid_key' => 'should-be-ignored',
        ] );

        $config = $this->channel->get_configuration();
        $this->assertArrayHasKey( 'api_key', $config );
        $this->assertArrayNotHasKey( 'invalid_key', $config );
    }
}
