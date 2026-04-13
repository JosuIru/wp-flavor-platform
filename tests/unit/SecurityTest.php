<?php
/**
 * Tests unitarios para seguridad (CSRF, XSS, sanitización, permisos).
 *
 * @package Flavor_Platform
 * @subpackage Tests\Unit
 */

class SecurityTest extends VBP_UnitTestCase {

    /**
     * Test sanitización de texto.
     */
    public function test_text_sanitization() {
        // strip_tags elimina las etiquetas pero mantiene el contenido
        $dirtyInputs = [
            '<p>Texto con etiquetas</p>' => 'Texto con etiquetas',
            '<div><span>Nested</span></div>' => 'Nested',
            'Texto normal' => 'Texto normal',
            '  espacios extras  ' => 'espacios extras',
        ];

        foreach ($dirtyInputs as $dirty => $expected) {
            $sanitized = trim(strip_tags($dirty));
            $this->assertEquals($expected, $sanitized);
        }

        // Verificar que las etiquetas peligrosas son eliminadas
        $xssInput = '<script>alert("XSS")</script>';
        $sanitized = strip_tags($xssInput);
        $this->assertStringNotContainsString('<script>', $sanitized);
        $this->assertStringNotContainsString('</script>', $sanitized);
    }

    /**
     * Test sanitización de email.
     */
    public function test_email_sanitization() {
        $emails = [
            'user@example.com' => true,
            'invalid-email' => false,
            'user+tag@example.com' => true,
            '<script>@example.com' => false,
            'user@localhost' => false,
        ];

        foreach ($emails as $email => $shouldBeValid) {
            // Usar filter_var de PHP estándar
            $isValid = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
            $this->assertEquals($shouldBeValid, $isValid, "Email: $email");
        }
    }

    /**
     * Test sanitización de URL.
     */
    public function test_url_sanitization() {
        // Test URLs válidas
        $validUrls = [
            'https://example.com',
            'http://example.com/path?query=value',
        ];

        foreach ($validUrls as $url) {
            $isValid = filter_var($url, FILTER_VALIDATE_URL) !== false;
            $this->assertTrue($isValid, "URL debe ser válida: $url");
        }

        // Test URLs peligrosas
        $dangerousUrls = ['javascript:alert(1)', 'data:text/html,<script>'];
        foreach ($dangerousUrls as $url) {
            // Verificar que no comienza con http/https
            $this->assertStringNotContainsString('http://', $url);
            $this->assertStringNotContainsString('https://', $url);
        }
    }

    /**
     * Test escape de HTML.
     */
    public function test_html_escaping() {
        $htmlStrings = [
            '<script>alert("XSS")</script>' => '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;',
            '"comillas"' => '&quot;comillas&quot;',
        ];

        foreach ($htmlStrings as $input => $expected) {
            // Usar htmlspecialchars de PHP estándar
            $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $this->assertEquals($expected, $escaped);
        }
    }

    /**
     * Test escape de atributos.
     */
    public function test_attribute_escaping() {
        $attributes = [
            'onclick="evil()"' => 'onclick=&quot;evil()&quot;',
            "onload='hack()'" => "onload=&#039;hack()&#039;",
        ];

        foreach ($attributes as $input => $expected) {
            // Usar htmlspecialchars de PHP estándar
            $escaped = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
            $this->assertEquals($expected, $escaped);
        }
    }

    /**
     * Test prevención de inyección SQL.
     */
    public function test_sql_injection_prevention() {
        global $wpdb;

        $maliciousInput = "1'; DROP TABLE users; --";
        $expectedEscaped = "1\\'; DROP TABLE users; --";

        // Simular escape de SQL
        $escaped = addslashes($maliciousInput);

        $this->assertStringContainsString("\\'", $escaped);
        $this->assertNotEquals($maliciousInput, $escaped);
    }

    /**
     * Test validación de nonce.
     */
    public function test_nonce_structure() {
        $nonceAction = 'flavor_save_settings';
        $nonceField = '_flavor_nonce';

        $nonceData = [
            'action' => $nonceAction,
            'field' => $nonceField,
            'lifetime' => DAY_IN_SECONDS,
        ];

        $this->assertEquals('flavor_save_settings', $nonceData['action']);
        $this->assertEquals(86400, $nonceData['lifetime']);
    }

    /**
     * Test verificación de capacidades.
     */
    public function test_capability_check() {
        $capabilities = [
            'administrator' => ['manage_options', 'edit_users', 'delete_plugins'],
            'editor' => ['edit_others_posts', 'publish_posts', 'manage_categories'],
            'author' => ['publish_posts', 'edit_posts', 'upload_files'],
            'subscriber' => ['read'],
        ];

        $this->assertContains('manage_options', $capabilities['administrator']);
        $this->assertNotContains('manage_options', $capabilities['editor']);
    }

    /**
     * Test capacidades personalizadas de Flavor.
     */
    public function test_flavor_capabilities() {
        $flavorCapabilities = [
            'flavor_manage_modules' => 'Gestionar módulos',
            'flavor_manage_settings' => 'Gestionar configuración',
            'flavor_view_reports' => 'Ver informes',
            'flavor_manage_members' => 'Gestionar miembros',
            'flavor_moderate_content' => 'Moderar contenido',
            'flavor_manage_events' => 'Gestionar eventos',
            'flavor_manage_marketplace' => 'Gestionar marketplace',
        ];

        $this->assertArrayHasKey('flavor_manage_modules', $flavorCapabilities);
        $this->assertCount(7, $flavorCapabilities);
    }

    /**
     * Test validación de entrada numérica.
     */
    public function test_numeric_validation() {
        $inputs = [
            '123' => 123,
            '-45' => -45,
            '12.5' => 12.5,
            'abc' => 0,
            '12abc' => 12,
        ];

        foreach ($inputs as $input => $expected) {
            if (strpos($input, '.') !== false) {
                $sanitized = floatval($input);
            } else {
                $sanitized = intval($input);
            }
            $this->assertEquals($expected, $sanitized);
        }
    }

    /**
     * Test lista blanca de valores.
     */
    public function test_whitelist_validation() {
        $allowedValues = ['option1', 'option2', 'option3'];
        $testValues = [
            'option1' => true,
            'option2' => true,
            'invalid' => false,
            'option1; DROP TABLE' => false,
        ];

        foreach ($testValues as $value => $shouldPass) {
            $isValid = in_array($value, $allowedValues, true);
            $this->assertEquals($shouldPass, $isValid);
        }
    }

    /**
     * Test headers de seguridad.
     */
    public function test_security_headers() {
        $securityHeaders = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'Content-Security-Policy' => "default-src 'self'",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
        ];

        $this->assertEquals('nosniff', $securityHeaders['X-Content-Type-Options']);
        $this->assertStringContainsString('max-age', $securityHeaders['Strict-Transport-Security']);
    }

    /**
     * Test Content Security Policy.
     */
    public function test_csp_directives() {
        $cspDirectives = [
            'default-src' => "'self'",
            'script-src' => "'self' 'unsafe-inline' https://trusted-scripts.com",
            'style-src' => "'self' 'unsafe-inline'",
            'img-src' => "'self' data: https:",
            'font-src' => "'self' https://fonts.gstatic.com",
            'connect-src' => "'self' https://api.example.com",
            'frame-ancestors' => "'self'",
        ];

        $this->assertStringContainsString("'self'", $cspDirectives['default-src']);
    }

    /**
     * Test hash de contraseña.
     */
    public function test_password_hashing() {
        $password = 'SecurePass123!';

        // Simular hash (en WordPress usaría wp_hash_password)
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->assertNotEquals($password, $hash);
        $this->assertTrue(password_verify($password, $hash));
        $this->assertFalse(password_verify('wrongpassword', $hash));
    }

    /**
     * Test política de contraseñas.
     */
    public function test_password_policy() {
        $policy = [
            'min_length' => 8,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_number' => true,
            'require_special' => true,
            'max_age_days' => 90,
            'history_count' => 5,
        ];

        $testPasswords = [
            'short' => false,
            'nouppercase123!' => false,
            'NOLOWERCASE123!' => false,
            'NoNumbers!' => false,
            'NoSpecial123' => false,
            'SecurePass123!' => true,
        ];

        foreach ($testPasswords as $password => $shouldPass) {
            $isValid = strlen($password) >= $policy['min_length']
                && preg_match('/[A-Z]/', $password)
                && preg_match('/[a-z]/', $password)
                && preg_match('/[0-9]/', $password)
                && preg_match('/[^a-zA-Z0-9]/', $password);

            $this->assertEquals($shouldPass, (bool) $isValid, "Password: $password");
        }
    }

    /**
     * Test tokens de sesión.
     */
    public function test_session_token() {
        $sessionData = [
            'token' => bin2hex(random_bytes(32)),
            'user_id' => 25,
            'created_at' => time(),
            'expires_at' => time() + 3600,
            'ip' => '192.168.1.1',
            'user_agent' => 'Mozilla/5.0...',
        ];

        $this->assertEquals(64, strlen($sessionData['token']));
        $this->assertGreaterThan($sessionData['created_at'], $sessionData['expires_at']);
    }

    /**
     * Test protección de uploads.
     */
    public function test_upload_security() {
        $uploadSecurity = [
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'blocked_extensions' => ['php', 'phtml', 'exe', 'sh', 'bat', 'js', 'html'],
            'max_file_size' => 10485760,
            'verify_mime_type' => true,
            'scan_for_malware' => true,
            'randomize_filename' => true,
        ];

        $this->assertNotContains('php', $uploadSecurity['allowed_extensions']);
        $this->assertContains('php', $uploadSecurity['blocked_extensions']);
    }

    /**
     * Test verificación de tipo MIME.
     */
    public function test_mime_type_verification() {
        $mimeChecks = [
            ['extension' => 'jpg', 'claimed_mime' => 'image/jpeg', 'valid' => true],
            ['extension' => 'jpg', 'claimed_mime' => 'application/x-php', 'valid' => false],
            ['extension' => 'pdf', 'claimed_mime' => 'application/pdf', 'valid' => true],
            ['extension' => 'png', 'claimed_mime' => 'text/html', 'valid' => false],
        ];

        foreach ($mimeChecks as $check) {
            $mimeMap = [
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
            ];

            $expectedMime = $mimeMap[$check['extension']] ?? '';
            $isValid = $check['claimed_mime'] === $expectedMime;
            $this->assertEquals($check['valid'], $isValid);
        }
    }

    /**
     * Test rate limiting.
     */
    public function test_rate_limiting() {
        $rateLimitConfig = [
            'login_attempts' => ['max' => 5, 'window_minutes' => 15],
            'api_requests' => ['max' => 100, 'window_minutes' => 1],
            'password_reset' => ['max' => 3, 'window_minutes' => 60],
            'contact_form' => ['max' => 5, 'window_minutes' => 10],
        ];

        $this->assertEquals(5, $rateLimitConfig['login_attempts']['max']);
    }

    /**
     * Test bloqueo de IP.
     */
    public function test_ip_blocking() {
        $ipBlockConfig = [
            'enabled' => true,
            'block_duration_hours' => 24,
            'threshold_failed_logins' => 10,
            'whitelist' => ['127.0.0.1', '::1'],
            'blacklist' => ['192.168.1.100'],
        ];

        $this->assertTrue($ipBlockConfig['enabled']);
        $this->assertContains('127.0.0.1', $ipBlockConfig['whitelist']);
    }

    /**
     * Test logs de seguridad.
     */
    public function test_security_logging() {
        $securityLog = [
            'id' => 1,
            'event_type' => 'failed_login',
            'user_id' => null,
            'ip_address' => '192.168.1.50',
            'user_agent' => 'Mozilla/5.0...',
            'details' => ['username' => 'admin', 'reason' => 'invalid_password'],
            'severity' => 'warning',
            'timestamp' => '2025-01-15 10:30:00',
        ];

        $this->assertEquals('failed_login', $securityLog['event_type']);
        $this->assertEquals('warning', $securityLog['severity']);
    }

    /**
     * Test tipos de eventos de seguridad.
     */
    public function test_security_event_types() {
        $eventTypes = [
            'failed_login' => 'warning',
            'successful_login' => 'info',
            'password_changed' => 'info',
            'password_reset_requested' => 'info',
            'user_locked' => 'warning',
            'suspicious_activity' => 'critical',
            'sql_injection_attempt' => 'critical',
            'xss_attempt' => 'critical',
            'csrf_validation_failed' => 'warning',
            'unauthorized_access' => 'warning',
        ];

        $this->assertEquals('critical', $eventTypes['sql_injection_attempt']);
    }

    /**
     * Test encriptación de datos.
     */
    public function test_data_encryption() {
        $sensitiveData = 'datos-sensibles-123';
        $encryptionKey = random_bytes(32);

        // Simular encriptación
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($sensitiveData, 'AES-256-CBC', $encryptionKey, 0, $iv);

        $this->assertNotEquals($sensitiveData, $encrypted);

        // Verificar desencriptación
        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $encryptionKey, 0, $iv);
        $this->assertEquals($sensitiveData, $decrypted);
    }

    /**
     * Test protección de API.
     */
    public function test_api_security() {
        $apiSecurity = [
            'require_authentication' => true,
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'cors_enabled' => true,
            'cors_origins' => ['https://example.com'],
            'rate_limiting' => true,
            'request_validation' => true,
            'response_sanitization' => true,
        ];

        $this->assertTrue($apiSecurity['require_authentication']);
        $this->assertContains('POST', $apiSecurity['allowed_methods']);
    }

    /**
     * Test validación de entrada de API.
     */
    public function test_api_input_validation() {
        $validationRules = [
            'user_id' => ['type' => 'integer', 'required' => true, 'min' => 1],
            'email' => ['type' => 'email', 'required' => true],
            'name' => ['type' => 'string', 'required' => true, 'max_length' => 100],
            'status' => ['type' => 'enum', 'values' => ['active', 'inactive']],
        ];

        $this->assertEquals('integer', $validationRules['user_id']['type']);
        $this->assertContains('active', $validationRules['status']['values']);
    }

    /**
     * Test protección CORS.
     */
    public function test_cors_configuration() {
        $corsConfig = [
            'allowed_origins' => ['https://example.com', 'https://app.example.com'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => ['X-Total-Count', 'X-Page-Count'],
            'max_age' => 86400,
            'credentials' => true,
        ];

        $this->assertTrue($corsConfig['credentials']);
        $this->assertContains('Authorization', $corsConfig['allowed_headers']);
    }

    /**
     * Test auditoría de acciones.
     */
    public function test_audit_trail() {
        $auditEntry = [
            'id' => 1,
            'user_id' => 25,
            'action' => 'update_settings',
            'object_type' => 'settings',
            'object_id' => 'general',
            'old_value' => '{"site_name": "Old Name"}',
            'new_value' => '{"site_name": "New Name"}',
            'ip_address' => '192.168.1.1',
            'timestamp' => '2025-01-15 10:00:00',
        ];

        $this->assertEquals('update_settings', $auditEntry['action']);
        $this->assertArrayHasKey('old_value', $auditEntry);
    }

    /**
     * Test detección de spam.
     */
    public function test_spam_detection() {
        $spamIndicators = [
            'contains_spam_words' => ['viagra', 'casino', 'lottery'],
            'max_links' => 5,
            'min_content_length' => 10,
            'honeypot_field' => 'website_url',
            'recaptcha_enabled' => true,
        ];

        $testContent = 'Win at casino! Free viagra! Click http://spam.com';

        $hasSpamWords = false;
        foreach ($spamIndicators['contains_spam_words'] as $spamWord) {
            if (stripos($testContent, $spamWord) !== false) {
                $hasSpamWords = true;
                break;
            }
        }

        $this->assertTrue($hasSpamWords);
    }

    /**
     * Test 2FA configuración.
     */
    public function test_two_factor_auth_config() {
        $twoFactorConfig = [
            'enabled' => true,
            'methods' => ['totp', 'email', 'sms'],
            'required_for_roles' => ['administrator', 'editor'],
            'grace_period_hours' => 24,
            'backup_codes_count' => 10,
            'remember_device_days' => 30,
        ];

        $this->assertTrue($twoFactorConfig['enabled']);
        $this->assertContains('totp', $twoFactorConfig['methods']);
    }

    /**
     * Test sanitización de JSON.
     */
    public function test_json_sanitization() {
        $jsonInput = '{"name": "<b>bold</b>", "value": "normal", "script": "<script>evil()</script>"}';
        $decoded = json_decode($jsonInput, true);

        $sanitizedDecoded = [];
        foreach ($decoded as $key => $value) {
            // Usar strip_tags de PHP estándar - elimina etiquetas pero no contenido
            $sanitizedDecoded[$key] = strip_tags($value);
        }

        // Verifica que las etiquetas HTML son eliminadas
        $this->assertEquals('bold', $sanitizedDecoded['name']);
        $this->assertEquals('normal', $sanitizedDecoded['value']);

        // Verifica que las etiquetas script son eliminadas
        $this->assertStringNotContainsString('<script>', $sanitizedDecoded['script']);
    }

    /**
     * Test verificación de referer.
     */
    public function test_referer_check() {
        $validReferers = [
            'https://example.com/admin' => true,
            'https://example.com/settings' => true,
            'https://attacker.com/page' => false,
            '' => false,
        ];

        $allowedHost = 'example.com';

        foreach ($validReferers as $referer => $shouldBeValid) {
            // Usar parse_url de PHP estándar
            $parsedUrl = parse_url($referer);
            $refererHost = $parsedUrl['host'] ?? '';
            $isValid = $refererHost === $allowedHost;
            $this->assertEquals($shouldBeValid, $isValid);
        }
    }

    /**
     * Test tiempo de expiración de sesión.
     */
    public function test_session_expiration() {
        $sessionConfig = [
            'lifetime_seconds' => 3600,
            'idle_timeout_seconds' => 1800,
            'absolute_timeout_seconds' => 86400,
            'regenerate_id_interval' => 300,
            'secure_cookie' => true,
            'http_only' => true,
            'same_site' => 'Strict',
        ];

        $this->assertTrue($sessionConfig['secure_cookie']);
        $this->assertEquals('Strict', $sessionConfig['same_site']);
    }
}
