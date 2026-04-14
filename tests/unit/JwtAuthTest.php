<?php
/**
 * Tests unitarios para la clase Flavor_JWT_Auth
 *
 * Verifica la generación y verificación de tokens JWT,
 * codificación base64url y manejo de tokens inválidos.
 *
 * @package FlavorPlatform
 * @subpackage Tests\Unit
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Constante AUTH_KEY para tests JWT (si no está definida)
if (!defined('AUTH_KEY')) {
    define('AUTH_KEY', 'test-jwt-secret-key-for-unit-testing-32-chars-minimum');
}

/**
 * Mock de get_user_by para tests JWT
 *
 * @param string     $field Campo de búsqueda (id, email, login, etc.)
 * @param string|int $value Valor a buscar.
 * @return object|false Objeto usuario mock o false si no existe.
 */
if (!function_exists('get_user_by')) {
    function get_user_by($field, $value) {
        // Simular usuarios existentes para tests
        $validUserIds = [1, 2, 3, 100, 999];

        if ($field === 'id' && in_array((int) $value, $validUserIds, true)) {
            return (object) [
                'ID'           => (int) $value,
                'user_login'   => 'testuser' . $value,
                'user_email'   => 'test' . $value . '@example.com',
                'display_name' => 'Test User ' . $value,
            ];
        }

        return false;
    }
}

/**
 * Clase de tests para Flavor_JWT_Auth
 *
 * @covers Flavor_JWT_Auth
 */
class JwtAuthTest extends Flavor_TestCase {

    /**
     * Instancia de la clase JWT Auth para tests
     *
     * @var Flavor_JWT_Auth
     */
    private $jwtAuth;

    /**
     * Configuración antes de cada test.
     *
     * Carga la clase JWT Auth y obtiene la instancia singleton.
     *
     * @return void
     */
    protected function setUp(): void {
        parent::setUp();

        // Cargar la clase si no existe
        if (!class_exists('Flavor_JWT_Auth', false)) {
            require_once FLAVOR_PLUGIN_DIR . '/includes/class-jwt-auth.php';
        }

        $this->jwtAuth = Flavor_JWT_Auth::get_instance();
    }

    /**
     * Test que verify_token rechaza tokens con formato inválido (menos de 3 partes).
     *
     * Un token JWT válido debe tener exactamente 3 partes separadas por puntos:
     * header.payload.signature
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @return void
     */
    public function test_verify_token_rejects_invalid_format(): void {
        // Token con solo 1 parte
        $tokenOnePart = 'single_part_token';
        $resultOnePart = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$tokenOnePart]);
        $this->assertFalse($resultOnePart, 'Token con 1 parte debe ser rechazado');

        // Token con 2 partes
        $tokenTwoParts = 'header.payload';
        $resultTwoParts = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$tokenTwoParts]);
        $this->assertFalse($resultTwoParts, 'Token con 2 partes debe ser rechazado');

        // Token con 4 partes
        $tokenFourParts = 'header.payload.signature.extra';
        $resultFourParts = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$tokenFourParts]);
        $this->assertFalse($resultFourParts, 'Token con 4 partes debe ser rechazado');
    }

    /**
     * Test que verify_token rechaza tokens vacíos.
     *
     * Un token vacío no puede ser un JWT válido.
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @return void
     */
    public function test_verify_token_rejects_empty_token(): void {
        // Token vacío
        $emptyToken = '';
        $resultEmpty = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$emptyToken]);
        $this->assertFalse($resultEmpty, 'Token vacío debe ser rechazado');

        // Token con solo espacios
        $whitespaceToken = '   ';
        $resultWhitespace = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$whitespaceToken]);
        $this->assertFalse($resultWhitespace, 'Token con solo espacios debe ser rechazado');

        // Token con solo puntos
        $dotsOnlyToken = '..';
        $resultDots = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$dotsOnlyToken]);
        $this->assertFalse($resultDots, 'Token con solo puntos debe ser rechazado');
    }

    /**
     * Test que verify_token acepta tokens con formato válido de 3 partes.
     *
     * Un token generado por generate_token debe tener el formato correcto
     * y ser verificable si el usuario existe.
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @covers Flavor_JWT_Auth::generate_token
     * @return void
     */
    public function test_verify_token_accepts_valid_format(): void {
        // Generar un token válido para un usuario existente
        $validUserId = 1;
        $token = Flavor_JWT_Auth::generate_token($validUserId);

        // Verificar que tiene 3 partes
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'Token generado debe tener exactamente 3 partes');

        // Verificar que el token es válido
        $resultUserId = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$token]);
        $this->assertEquals($validUserId, $resultUserId, 'Token válido debe devolver el user_id correcto');
    }

    /**
     * Test que generate_token crea un token con estructura JWT válida.
     *
     * El token debe tener formato header.payload.signature donde:
     * - header: contiene alg y typ
     * - payload: contiene user_id, iat, exp, iss
     * - signature: firma HMAC-SHA256
     *
     * @covers Flavor_JWT_Auth::generate_token
     * @return void
     */
    public function test_generate_token_creates_valid_structure(): void {
        $userId = 100;
        $token = Flavor_JWT_Auth::generate_token($userId);

        // Verificar estructura de 3 partes
        $parts = explode('.', $token);
        $this->assertCount(3, $parts, 'Token debe tener 3 partes');

        // Decodificar y verificar header
        $headerJson = $this->base64UrlDecode($parts[0]);
        $header = json_decode($headerJson, true);

        $this->assertIsArray($header, 'Header debe ser un array válido');
        $this->assertArrayHasKey('alg', $header, 'Header debe contener alg');
        $this->assertArrayHasKey('typ', $header, 'Header debe contener typ');
        $this->assertEquals('HS256', $header['alg'], 'Algoritmo debe ser HS256');
        $this->assertEquals('JWT', $header['typ'], 'Tipo debe ser JWT');

        // Decodificar y verificar payload
        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        $this->assertIsArray($payload, 'Payload debe ser un array válido');
        $this->assertArrayHasKey('user_id', $payload, 'Payload debe contener user_id');
        $this->assertArrayHasKey('iat', $payload, 'Payload debe contener iat (issued at)');
        $this->assertArrayHasKey('exp', $payload, 'Payload debe contener exp (expiration)');
        $this->assertArrayHasKey('iss', $payload, 'Payload debe contener iss (issuer)');

        // Verificar valores del payload
        $this->assertEquals($userId, $payload['user_id'], 'user_id debe coincidir');
        $this->assertGreaterThan(0, $payload['iat'], 'iat debe ser timestamp válido');
        $this->assertGreaterThan($payload['iat'], $payload['exp'], 'exp debe ser mayor que iat');
    }

    /**
     * Test que base64url_encode y base64url_decode son operaciones inversas.
     *
     * La codificación y decodificación deben ser reversibles sin pérdida de datos.
     *
     * @covers Flavor_JWT_Auth::base64url_encode
     * @covers Flavor_JWT_Auth::base64url_decode
     * @return void
     */
    public function test_base64url_encode_decode_roundtrip(): void {
        $testCases = [
            'Hello, World!',
            '{"user_id": 123, "exp": 1234567890}',
            'Special chars: +/= and spaces',
            '',
            str_repeat('A', 1000), // String largo
            "Unicode: áéíóú 日本語 🎉",
            hex2bin('deadbeef'), // Datos binarios
        ];

        foreach ($testCases as $originalData) {
            // Codificar usando el método privado de la clase
            $encoded = $this->invokePrivateStaticMethod('Flavor_JWT_Auth', 'base64url_encode', [$originalData]);

            // Decodificar usando el método privado de la clase
            $decoded = $this->invokePrivateMethod($this->jwtAuth, 'base64url_decode', [$encoded]);

            $this->assertEquals(
                $originalData,
                $decoded,
                'Roundtrip encode/decode debe preservar los datos originales'
            );

            // Verificar que el encoding no contiene caracteres no URL-safe
            $this->assertStringNotContainsString('+', $encoded, 'Base64url no debe contener +');
            $this->assertStringNotContainsString('/', $encoded, 'Base64url no debe contener /');
            $this->assertStringNotContainsString('=', $encoded, 'Base64url no debe contener padding =');
        }
    }

    /**
     * Test que tokens expirados son rechazados.
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @return void
     */
    public function test_verify_token_rejects_expired_token(): void {
        // Generar token con expiración negativa (ya expirado)
        $userId = 1;
        $expiredToken = Flavor_JWT_Auth::generate_token($userId, -3600); // Expiró hace 1 hora

        $result = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$expiredToken]);
        $this->assertFalse($result, 'Token expirado debe ser rechazado');
    }

    /**
     * Test que tokens con firma manipulada son rechazados.
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @return void
     */
    public function test_verify_token_rejects_tampered_signature(): void {
        $userId = 1;
        $validToken = Flavor_JWT_Auth::generate_token($userId);

        // Manipular la firma (última parte)
        $parts = explode('.', $validToken);
        $parts[2] = 'tampered_signature_xyz123';
        $tamperedToken = implode('.', $parts);

        $result = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$tamperedToken]);
        $this->assertFalse($result, 'Token con firma manipulada debe ser rechazado');
    }

    /**
     * Test que tokens para usuarios inexistentes son rechazados.
     *
     * @covers Flavor_JWT_Auth::verify_token
     * @return void
     */
    public function test_verify_token_rejects_nonexistent_user(): void {
        // Usuario que no existe en el mock (ID 99999)
        $nonExistentUserId = 99999;
        $token = Flavor_JWT_Auth::generate_token($nonExistentUserId);

        $result = $this->invokePrivateMethod($this->jwtAuth, 'verify_token', [$nonExistentUserId]);
        $this->assertFalse($result, 'Token para usuario inexistente debe ser rechazado');
    }

    /**
     * Test que el singleton retorna siempre la misma instancia.
     *
     * @covers Flavor_JWT_Auth::get_instance
     * @return void
     */
    public function test_singleton_returns_same_instance(): void {
        $instance1 = Flavor_JWT_Auth::get_instance();
        $instance2 = Flavor_JWT_Auth::get_instance();

        $this->assertSame($instance1, $instance2, 'Singleton debe retornar la misma instancia');
    }

    /**
     * Test que generate_token usa el tiempo de expiración personalizado.
     *
     * @covers Flavor_JWT_Auth::generate_token
     * @return void
     */
    public function test_generate_token_respects_custom_expiration(): void {
        $userId = 1;
        $customExpiration = 3600; // 1 hora

        $token = Flavor_JWT_Auth::generate_token($userId, $customExpiration);

        // Decodificar payload
        $parts = explode('.', $token);
        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        $expectedExp = $payload['iat'] + $customExpiration;
        $this->assertEquals(
            $expectedExp,
            $payload['exp'],
            'El tiempo de expiración debe ser iat + expiración personalizada'
        );
    }

    /**
     * Test que el token incluye el issuer correcto.
     *
     * @covers Flavor_JWT_Auth::generate_token
     * @return void
     */
    public function test_generate_token_includes_correct_issuer(): void {
        $userId = 1;
        $token = Flavor_JWT_Auth::generate_token($userId);

        // Decodificar payload
        $parts = explode('.', $token);
        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        $expectedIssuer = get_site_url();
        $this->assertEquals(
            $expectedIssuer,
            $payload['iss'],
            'El issuer debe ser la URL del sitio'
        );
    }

    // =========================================================================
    // Métodos auxiliares para tests
    // =========================================================================

    /**
     * Invocar método privado de una instancia.
     *
     * @param object $instance   Instancia del objeto.
     * @param string $methodName Nombre del método.
     * @param array  $parameters Parámetros del método.
     * @return mixed Resultado del método.
     */
    private function invokePrivateMethod(object $instance, string $methodName, array $parameters = []) {
        $reflection = new ReflectionClass(get_class($instance));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($instance, $parameters);
    }

    /**
     * Invocar método estático privado.
     *
     * @param string $className  Nombre de la clase.
     * @param string $methodName Nombre del método.
     * @param array  $parameters Parámetros del método.
     * @return mixed Resultado del método.
     */
    private function invokePrivateStaticMethod(string $className, string $methodName, array $parameters = []) {
        $reflection = new ReflectionClass($className);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs(null, $parameters);
    }

    /**
     * Decodificar base64url (helper para tests).
     *
     * @param string $data Datos codificados.
     * @return string Datos decodificados.
     */
    private function base64UrlDecode(string $data): string {
        $base64 = strtr($data, '-_', '+/');
        $padding = strlen($base64) % 4;

        if ($padding > 0) {
            $base64 .= str_repeat('=', 4 - $padding);
        }

        return base64_decode($base64, true) ?: '';
    }
}
