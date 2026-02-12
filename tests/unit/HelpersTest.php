<?php
/**
 * Tests para funciones helper
 *
 * @package FlavorChatIA
 */

require_once dirname(__DIR__) . '/bootstrap.php';

class HelpersTest extends Flavor_TestCase {

    public function test_sanitize_text_field_removes_tags() {
        $input = '<script>alert("xss")</script>Hello';
        $expected = 'alert("xss")Hello';

        $result = sanitize_text_field($input);

        $this->assertEquals($expected, $result);
    }

    public function test_sanitize_text_field_trims_whitespace() {
        $input = '  Hello World  ';
        $expected = 'Hello World';

        $result = sanitize_text_field($input);

        $this->assertEquals($expected, $result);
    }

    public function test_absint_returns_absolute_integer() {
        $this->assertEquals(5, absint(5));
        $this->assertEquals(5, absint(-5));
        $this->assertEquals(0, absint(0));
        $this->assertEquals(3, absint(3.7));
        $this->assertEquals(3, absint(-3.7));
    }

    public function test_absint_handles_strings() {
        $this->assertEquals(42, absint('42'));
        $this->assertEquals(42, absint('-42'));
        $this->assertEquals(0, absint('not a number'));
    }

    public function test_esc_html_escapes_special_chars() {
        $input = '<div class="test">Hello & World</div>';
        $expected = '&lt;div class=&quot;test&quot;&gt;Hello &amp; World&lt;/div&gt;';

        $result = esc_html($input);

        $this->assertEquals($expected, $result);
    }

    public function test_esc_attr_escapes_for_attributes() {
        $input = 'value with "quotes" and <tags>';
        $result = esc_attr($input);

        $this->assertStringNotContainsString('"', $result);
        $this->assertStringNotContainsString('<', $result);
    }

    public function test_wp_json_encode_returns_valid_json() {
        $data = ['key' => 'value', 'number' => 42];

        $json = wp_json_encode($data);

        $this->assertJson($json);
        $this->assertEquals($data, json_decode($json, true));
    }

    public function test_current_time_mysql_format() {
        $result = current_time('mysql');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
            $result
        );
    }

    public function test_current_time_timestamp() {
        $result = current_time('timestamp');

        $this->assertIsInt($result);
        $this->assertGreaterThan(0, $result);
    }
}

class ValidationHelpersTest extends Flavor_TestCase {

    public function test_email_validation_pattern() {
        $validEmails = [
            'test@example.com',
            'user.name@domain.org',
            'user+tag@subdomain.domain.com',
        ];

        $invalidEmails = [
            'not-an-email',
            '@nodomain.com',
            'spaces in@email.com',
            '',
        ];

        foreach ($validEmails as $email) {
            $this->assertTrue(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email should be valid: $email"
            );
        }

        foreach ($invalidEmails as $email) {
            $this->assertFalse(
                filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                "Email should be invalid: $email"
            );
        }
    }

    public function test_url_validation_pattern() {
        $validUrls = [
            'https://example.com',
            'https://www.example.com/path',
            'https://sub.domain.com:8080/path?query=1',
        ];

        $invalidUrls = [
            'not-a-url',
            'ftp://example.com', // Solo permitimos http/https
            '',
        ];

        foreach ($validUrls as $url) {
            $this->assertTrue(
                filter_var($url, FILTER_VALIDATE_URL) !== false,
                "URL should be valid: $url"
            );
        }
    }

    public function test_phone_number_sanitization() {
        $inputs = [
            '+34 612 345 678' => '+34612345678',
            '(+34) 612-345-678' => '+34612345678',
            '612 345 678' => '612345678',
        ];

        foreach ($inputs as $input => $expected) {
            $result = preg_replace('/[^0-9+]/', '', $input);
            $this->assertEquals($expected, $result);
        }
    }

    public function test_slug_format_validation() {
        $validSlugs = [
            'my-module',
            'module123',
            'a-very-long-slug-name',
        ];

        $invalidSlugs = [
            'My Module', // Espacios y mayúsculas
            'módulo', // Acentos
            'slug_name', // Underscores (depende del contexto)
        ];

        foreach ($validSlugs as $slug) {
            $this->assertMatchesRegularExpression('/^[a-z0-9\-]+$/', $slug);
        }
    }
}
