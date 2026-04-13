<?php
/**
 * Tests de rendimiento/benchmark para Flavor Platform.
 *
 * @package Flavor_Platform
 * @subpackage Tests\Benchmark
 */

class PerformanceTest extends VBP_UnitTestCase {

    /**
     * Número de iteraciones para benchmarks.
     */
    const ITERATIONS = 1000;

    /**
     * Umbral máximo en milisegundos para operaciones rápidas.
     */
    const FAST_THRESHOLD_MS = 10;

    /**
     * Umbral máximo en milisegundos para operaciones medias.
     */
    const MEDIUM_THRESHOLD_MS = 100;

    /**
     * Test rendimiento de sanitización de texto.
     */
    public function test_text_sanitization_performance() {
        $testString = '<script>alert("XSS")</script><p onclick="evil()">Test content with HTML tags</p>';

        $startTime = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            strip_tags($testString);
        }

        $endTime = microtime(true);
        $totalTimeMs = ($endTime - $startTime) * 1000;
        $avgTimeMs = $totalTimeMs / self::ITERATIONS;

        $this->assertLessThan(
            self::FAST_THRESHOLD_MS,
            $avgTimeMs,
            "Sanitización de texto demasiado lenta: {$avgTimeMs}ms por operación"
        );

        // Log para referencia
        $this->addToAssertionCount(1);
        echo "\n[BENCHMARK] Sanitización texto: {$avgTimeMs}ms/op ({$totalTimeMs}ms total para " . self::ITERATIONS . " ops)\n";
    }

    /**
     * Test rendimiento de validación de email.
     */
    public function test_email_validation_performance() {
        $emails = [
            'valid@example.com',
            'invalid-email',
            'another.valid+tag@domain.org',
        ];

        $startTime = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($emails as $email) {
                filter_var($email, FILTER_VALIDATE_EMAIL);
            }
        }

        $endTime = microtime(true);
        $totalTimeMs = ($endTime - $startTime) * 1000;
        $operationsCount = self::ITERATIONS * count($emails);
        $avgTimeMs = $totalTimeMs / $operationsCount;

        $this->assertLessThan(
            self::FAST_THRESHOLD_MS,
            $avgTimeMs,
            "Validación de email demasiado lenta: {$avgTimeMs}ms por operación"
        );

        echo "\n[BENCHMARK] Validación email: {$avgTimeMs}ms/op ({$totalTimeMs}ms total para {$operationsCount} ops)\n";
    }

    /**
     * Test rendimiento de JSON encode/decode.
     */
    public function test_json_encoding_performance() {
        $testData = [
            'id' => 12345,
            'name' => 'Test Object',
            'properties' => [
                'color' => 'blue',
                'size' => 'large',
                'nested' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'array' => [1, 2, 3, 4, 5],
                ],
            ],
            'tags' => ['tag1', 'tag2', 'tag3'],
            'metadata' => [
                'created' => '2025-01-15T10:00:00Z',
                'modified' => '2025-01-15T12:00:00Z',
            ],
        ];

        // Benchmark encode
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            json_encode($testData);
        }
        $encodeTimeMs = (microtime(true) - $startTime) * 1000;

        // Benchmark decode
        $jsonString = json_encode($testData);
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            json_decode($jsonString, true);
        }
        $decodeTimeMs = (microtime(true) - $startTime) * 1000;

        $avgEncodeMs = $encodeTimeMs / self::ITERATIONS;
        $avgDecodeMs = $decodeTimeMs / self::ITERATIONS;

        $this->assertLessThan(self::FAST_THRESHOLD_MS, $avgEncodeMs);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $avgDecodeMs);

        echo "\n[BENCHMARK] JSON encode: {$avgEncodeMs}ms/op, decode: {$avgDecodeMs}ms/op\n";
    }

    /**
     * Test rendimiento de hash de contraseña.
     */
    public function test_password_hashing_performance() {
        $password = 'SecurePassword123!';
        $iterations = 10; // Menos iteraciones porque es costoso

        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            password_hash($password, PASSWORD_DEFAULT);
        }
        $hashTimeMs = (microtime(true) - $startTime) * 1000;

        // Verificación
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            password_verify($password, $hash);
        }
        $verifyTimeMs = (microtime(true) - $startTime) * 1000;

        $avgHashMs = $hashTimeMs / $iterations;
        $avgVerifyMs = $verifyTimeMs / $iterations;

        // Password hashing debe ser "lento" por seguridad (50-200ms es normal)
        $this->assertGreaterThan(10, $avgHashMs, "Hash demasiado rápido, puede ser inseguro");
        $this->assertLessThan(500, $avgHashMs, "Hash demasiado lento");

        echo "\n[BENCHMARK] Password hash: {$avgHashMs}ms/op, verify: {$avgVerifyMs}ms/op\n";
    }

    /**
     * Test rendimiento de array operations.
     */
    public function test_array_operations_performance() {
        $largeArray = range(1, 10000);

        // array_map
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            array_map(fn($x) => $x * 2, $largeArray);
        }
        $mapTimeMs = (microtime(true) - $startTime) * 1000;

        // array_filter
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            array_filter($largeArray, fn($x) => $x % 2 === 0);
        }
        $filterTimeMs = (microtime(true) - $startTime) * 1000;

        // array_reduce
        $startTime = microtime(true);
        for ($i = 0; $i < 100; $i++) {
            array_reduce($largeArray, fn($carry, $item) => $carry + $item, 0);
        }
        $reduceTimeMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS * 10, $mapTimeMs);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS * 10, $filterTimeMs);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS * 10, $reduceTimeMs);

        echo "\n[BENCHMARK] Array (10k items, 100 iters): map={$mapTimeMs}ms, filter={$filterTimeMs}ms, reduce={$reduceTimeMs}ms\n";
    }

    /**
     * Test rendimiento de regex.
     */
    public function test_regex_performance() {
        $testStrings = [
            'user@example.com',
            'https://www.example.com/path?query=value',
            'The quick brown fox jumps over the lazy dog',
            '<div class="container"><p>Hello World</p></div>',
        ];

        $patterns = [
            'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'url' => '/^https?:\/\/[^\s]+$/',
            'word' => '/\b\w{5,}\b/',
            'html_tag' => '/<[^>]+>/',
        ];

        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($testStrings as $string) {
                foreach ($patterns as $pattern) {
                    preg_match($pattern, $string);
                }
            }
        }
        $totalTimeMs = (microtime(true) - $startTime) * 1000;

        $operationsCount = self::ITERATIONS * count($testStrings) * count($patterns);
        $avgTimeMs = $totalTimeMs / $operationsCount;

        $this->assertLessThan(self::FAST_THRESHOLD_MS, $avgTimeMs);

        echo "\n[BENCHMARK] Regex: {$avgTimeMs}ms/op ({$operationsCount} total ops)\n";
    }

    /**
     * Test rendimiento de datetime operations.
     */
    public function test_datetime_performance() {
        $dateStrings = [
            '2025-01-15 10:30:00',
            '2025-12-31 23:59:59',
            '2024-02-29 12:00:00',
        ];

        // DateTime parsing
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($dateStrings as $dateString) {
                new DateTime($dateString);
            }
        }
        $parseTimeMs = (microtime(true) - $startTime) * 1000;

        // DateTime formatting
        $dates = array_map(fn($d) => new DateTime($d), $dateStrings);
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            foreach ($dates as $date) {
                $date->format('Y-m-d H:i:s');
            }
        }
        $formatTimeMs = (microtime(true) - $startTime) * 1000;

        // DateTime diff
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $dates[0]->diff($dates[1]);
        }
        $diffTimeMs = (microtime(true) - $startTime) * 1000;

        $operationsCount = self::ITERATIONS * count($dateStrings);

        $this->assertLessThan(self::FAST_THRESHOLD_MS, $parseTimeMs / $operationsCount);
        $this->assertLessThan(self::FAST_THRESHOLD_MS, $formatTimeMs / $operationsCount);

        echo "\n[BENCHMARK] DateTime: parse={$parseTimeMs}ms, format={$formatTimeMs}ms, diff={$diffTimeMs}ms\n";
    }

    /**
     * Test rendimiento de string operations.
     */
    public function test_string_operations_performance() {
        $testString = str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 100);

        // strlen
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            strlen($testString);
        }
        $strlenTimeMs = (microtime(true) - $startTime) * 1000;

        // substr
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            substr($testString, 0, 100);
        }
        $substrTimeMs = (microtime(true) - $startTime) * 1000;

        // str_replace
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            str_replace('Lorem', 'Ipsum', $testString);
        }
        $replaceTimeMs = (microtime(true) - $startTime) * 1000;

        // explode
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            explode(' ', $testString);
        }
        $explodeTimeMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::FAST_THRESHOLD_MS, $strlenTimeMs / self::ITERATIONS);

        echo "\n[BENCHMARK] String ops (" . strlen($testString) . " chars): strlen={$strlenTimeMs}ms, substr={$substrTimeMs}ms, replace={$replaceTimeMs}ms, explode={$explodeTimeMs}ms\n";
    }

    /**
     * Test rendimiento de creación de objetos.
     */
    public function test_object_creation_performance() {
        // stdClass
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $obj = new stdClass();
            $obj->id = $i;
            $obj->name = "Object $i";
        }
        $stdClassTimeMs = (microtime(true) - $startTime) * 1000;

        // Array
        $startTime = microtime(true);
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $arr = [
                'id' => $i,
                'name' => "Object $i",
            ];
        }
        $arrayTimeMs = (microtime(true) - $startTime) * 1000;

        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $stdClassTimeMs);
        $this->assertLessThan(self::MEDIUM_THRESHOLD_MS, $arrayTimeMs);

        echo "\n[BENCHMARK] Object creation: stdClass={$stdClassTimeMs}ms, array={$arrayTimeMs}ms\n";
    }

    /**
     * Test rendimiento de memoria.
     */
    public function test_memory_usage() {
        $initialMemory = memory_get_usage(true);

        // Crear estructuras de datos grandes
        $largeArray = [];
        for ($i = 0; $i < 10000; $i++) {
            $largeArray[] = [
                'id' => $i,
                'name' => "Item $i",
                'data' => str_repeat('x', 100),
            ];
        }

        $afterArrayMemory = memory_get_usage(true);
        $arrayMemoryUsedMB = ($afterArrayMemory - $initialMemory) / 1024 / 1024;

        // Limpiar
        unset($largeArray);
        $afterCleanupMemory = memory_get_usage(true);

        $this->assertLessThan(100, $arrayMemoryUsedMB, "Array de 10k items usa más de 100MB");

        echo "\n[BENCHMARK] Memory: 10k items array = {$arrayMemoryUsedMB}MB\n";
    }

    /**
     * Test que genera un resumen de todos los benchmarks.
     */
    public function test_benchmark_summary() {
        $benchmarkResults = [
            'text_sanitization' => $this->measureOperation(fn() => strip_tags('<p>test</p>')),
            'email_validation' => $this->measureOperation(fn() => filter_var('test@example.com', FILTER_VALIDATE_EMAIL)),
            'json_encode' => $this->measureOperation(fn() => json_encode(['key' => 'value'])),
            'json_decode' => $this->measureOperation(fn() => json_decode('{"key":"value"}', true)),
            'regex_match' => $this->measureOperation(fn() => preg_match('/\d+/', '123')),
            'datetime_create' => $this->measureOperation(fn() => new DateTime()),
            'array_map' => $this->measureOperation(fn() => array_map(fn($x) => $x * 2, [1,2,3,4,5])),
        ];

        echo "\n\n=== RESUMEN DE BENCHMARKS ===\n";
        echo str_repeat('-', 50) . "\n";
        foreach ($benchmarkResults as $name => $timeMs) {
            $status = $timeMs < 0.01 ? '✓' : ($timeMs < 0.1 ? '~' : '✗');
            printf("%s %-25s %.4f ms/op\n", $status, $name, $timeMs);
        }
        echo str_repeat('-', 50) . "\n";

        $this->assertTrue(true);
    }

    /**
     * Mide el tiempo promedio de una operación.
     *
     * @param callable $operation Operación a medir.
     * @param int      $iterations Número de iteraciones.
     * @return float Tiempo promedio en ms.
     */
    private function measureOperation(callable $operation, int $iterations = 1000): float {
        $startTime = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $operation();
        }
        $totalTimeMs = (microtime(true) - $startTime) * 1000;
        return $totalTimeMs / $iterations;
    }
}
