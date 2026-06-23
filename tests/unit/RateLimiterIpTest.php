<?php
/**
 * Regresión: el rate limiter no debe confiar en cabeceras de IP reenviadas
 * (X-Forwarded-For / CF-Connecting-IP / X-Real-IP) salvo que la conexión
 * venga de un proxy de confianza. Sin esa garantía, el rate limiting era
 * eludible falsificando cabeceras (bug crítico de la auditoría).
 *
 * @package FlavorPlatform
 */

require_once dirname(__DIR__) . '/bootstrap.php';

// wp_unslash no está mockeado en el bootstrap; lo definimos de forma fiel.
if (!function_exists('wp_unslash')) {
    function wp_unslash($value) {
        return is_string($value) ? stripslashes($value) : $value;
    }
}

require_once FLAVOR_PLUGIN_DIR . '/includes/api/class-api-rate-limiter.php';

class RateLimiterIpTest extends Flavor_TestCase {

    protected function tearDown(): void {
        unset(
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_X_FORWARDED_FOR'],
            $_SERVER['HTTP_CF_CONNECTING_IP'],
            $_SERVER['HTTP_X_REAL_IP']
        );
        parent::tearDown();
    }

    /**
     * En el entorno de test no hay proxies de confianza configurados
     * (apply_filters devuelve []), así que las cabeceras reenviadas
     * —aunque vengan spoofeadas— deben ignorarse y usarse REMOTE_ADDR.
     */
    public function test_cabeceras_forwarded_se_ignoran_sin_proxy_de_confianza() {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 8.8.8.8';
        $_SERVER['HTTP_CF_CONNECTING_IP'] = '1.2.3.4';
        $_SERVER['HTTP_X_REAL_IP'] = '5.6.7.8';

        $this->assertSame('203.0.113.7', Flavor_API_Rate_Limiter::obtener_ip_cliente());
    }

    public function test_usa_remote_addr_en_conexion_directa() {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.9';

        $this->assertSame('198.51.100.9', Flavor_API_Rate_Limiter::obtener_ip_cliente());
    }

    public function test_remote_addr_invalido_devuelve_cadena_vacia() {
        $_SERVER['REMOTE_ADDR'] = 'no-es-una-ip';

        $this->assertSame('', Flavor_API_Rate_Limiter::obtener_ip_cliente());
    }
}
