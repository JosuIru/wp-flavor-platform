<?php
/**
 * Tests para la clase de encriptación de API Keys
 *
 * @package FlavorChatIA
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// Mocks adicionales para este test
if (!defined('AUTH_KEY')) {
    define('AUTH_KEY', 'test-auth-key-32-characters-long');
}
if (!defined('AUTH_SALT')) {
    define('AUTH_SALT', 'test-auth-salt-32-characters-lng');
}

class ApiKeyEncryptionTest extends Flavor_TestCase {

    private $encryption;

    protected function setUp(): void {
        parent::setUp();

        // Cargar la clase
        require_once FLAVOR_PLUGIN_DIR . '/includes/security/class-api-key-encryption.php';

        $this->encryption = Flavor_Api_Key_Encryption::get_instance();
    }

    public function test_singleton_returns_same_instance() {
        $instance1 = Flavor_Api_Key_Encryption::get_instance();
        $instance2 = Flavor_Api_Key_Encryption::get_instance();

        $this->assertSame($instance1, $instance2);
    }

    public function test_encrypt_returns_prefixed_string() {
        $apiKey = 'sk-test-api-key-12345';

        $encrypted = $this->encryption->encrypt($apiKey);

        $this->assertStringStartsWith('enc:v1:', $encrypted);
    }

    public function test_decrypt_returns_original_value() {
        $original = 'sk-test-api-key-12345';

        $encrypted = $this->encryption->encrypt($original);
        $decrypted = $this->encryption->decrypt($encrypted);

        $this->assertEquals($original, $decrypted);
    }

    public function test_decrypt_unencrypted_returns_original() {
        $plaintext = 'sk-plain-api-key';

        $result = $this->encryption->decrypt($plaintext);

        $this->assertEquals($plaintext, $result);
    }

    public function test_encrypt_empty_string_returns_empty() {
        $encrypted = $this->encryption->encrypt('');

        $this->assertEquals('', $encrypted);
    }

    public function test_encrypt_different_keys_produce_different_ciphertext() {
        $key1 = 'sk-api-key-one';
        $key2 = 'sk-api-key-two';

        $encrypted1 = $this->encryption->encrypt($key1);
        $encrypted2 = $this->encryption->encrypt($key2);

        $this->assertNotEquals($encrypted1, $encrypted2);
    }

    public function test_same_key_encrypts_to_different_values() {
        // IV aleatorio significa que el mismo valor produce diferentes ciphertexts
        $key = 'sk-api-key-test';

        $encrypted1 = $this->encryption->encrypt($key);
        $encrypted2 = $this->encryption->encrypt($key);

        // Los valores cifrados deben ser diferentes (IV aleatorio)
        $this->assertNotEquals($encrypted1, $encrypted2);

        // Pero ambos deben descifrar al mismo valor
        $this->assertEquals($key, $this->encryption->decrypt($encrypted1));
        $this->assertEquals($key, $this->encryption->decrypt($encrypted2));
    }

    public function test_mask_key_hides_middle_characters() {
        $key = 'sk-abcdefghij1234567890';

        $masked = $this->encryption->mask_key($key);

        $this->assertStringStartsWith('sk-', $masked);
        $this->assertStringContainsString('****', $masked);
        $this->assertStringEndsWith('890', $masked);
    }

    public function test_mask_short_key_returns_asterisks() {
        $shortKey = 'abc';

        $masked = $this->encryption->mask_key($shortKey);

        $this->assertEquals('****', $masked);
    }

    public function test_is_encrypted_detects_encrypted_values() {
        $encrypted = 'enc:v1:base64encodeddata';
        $plain = 'sk-plain-key';

        $this->assertTrue($this->encryption->is_encrypted($encrypted));
        $this->assertFalse($this->encryption->is_encrypted($plain));
    }
}
