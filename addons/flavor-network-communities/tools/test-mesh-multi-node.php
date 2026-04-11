<?php
/**
 * Script de Testing Multi-Nodo para Red Mesh P2P
 *
 * Este script prueba la comunicación entre múltiples instancias WordPress
 * conectadas en una red mesh.
 *
 * USO:
 *   php test-mesh-multi-node.php --local-url=http://sitio1.local --peer-url=http://sitio2.local
 *
 * REQUISITOS:
 *   - Dos o más instalaciones WordPress con Flavor Network Communities
 *   - Ambas deben tener el sistema mesh activado
 *   - Acceso HTTP entre las instancias
 *
 * @package FlavorPlatform\Network\Mesh
 * @since 1.5.0
 */

// Colores para terminal
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_CYAN', "\033[36m");
define('COLOR_RESET', "\033[0m");

/**
 * Clase principal de testing
 */
class Mesh_Multi_Node_Tester {

    private $local_url;
    private $peer_url;
    private $local_peer_id;
    private $remote_peer_id;
    private $results = [];
    private $verbose = false;

    /**
     * Constructor
     */
    public function __construct($local_url, $peer_url, $verbose = false) {
        $this->local_url = rtrim($local_url, '/');
        $this->peer_url = rtrim($peer_url, '/');
        $this->verbose = $verbose;
    }

    /**
     * Ejecuta todas las pruebas
     */
    public function run_all_tests() {
        $this->print_header();

        // Fase 1: Verificar conectividad básica
        $this->section("FASE 1: Conectividad Básica");
        $this->test_health_check($this->local_url, "Local");
        $this->test_health_check($this->peer_url, "Remoto");

        // Fase 2: Verificar identidad de peers
        $this->section("FASE 2: Identidad de Peers");
        $this->test_peer_identity($this->local_url, "Local");
        $this->test_peer_identity($this->peer_url, "Remoto");

        // Fase 3: Establecer conexión mesh
        $this->section("FASE 3: Conexión Mesh");
        $this->test_mesh_handshake();

        // Fase 4: Probar Gossip Protocol
        $this->section("FASE 4: Gossip Protocol");
        $this->test_gossip_send();
        $this->test_gossip_receive();

        // Fase 5: Probar Peer Discovery
        $this->section("FASE 5: Peer Discovery");
        $this->test_peer_list();
        $this->test_peer_exchange();

        // Fase 6: Probar CRDT Sync
        $this->section("FASE 6: CRDT Sync");
        $this->test_crdt_merge();

        // Fase 7: Probar propagación de contenido
        $this->section("FASE 7: Propagación de Contenido");
        $this->test_content_propagation();

        // Resumen final
        $this->print_summary();
    }

    /**
     * Test de health check
     */
    private function test_health_check($url, $name) {
        $endpoint = "{$url}/wp-json/flavor-mesh/v1/health";
        $response = $this->http_get($endpoint);

        if ($response && isset($response['status']) && $response['status'] === 'ok') {
            $this->pass("Health check {$name}", "Versión: " . ($response['version'] ?? 'N/A'));
            return true;
        }

        $this->fail("Health check {$name}", "No responde o error");
        return false;
    }

    /**
     * Test de identidad de peer
     */
    private function test_peer_identity($url, $name) {
        $endpoint = "{$url}/wp-json/flavor-mesh/v1/peers/local";
        $response = $this->http_get($endpoint);

        if ($response && isset($response['peer_id'])) {
            if ($name === "Local") {
                $this->local_peer_id = $response['peer_id'];
            } else {
                $this->remote_peer_id = $response['peer_id'];
            }
            $this->pass("Identidad {$name}", "peer_id: " . substr($response['peer_id'], 0, 16) . "...");
            return true;
        }

        $this->fail("Identidad {$name}", "No tiene peer_id configurado");
        return false;
    }

    /**
     * Test de handshake mesh
     */
    private function test_mesh_handshake() {
        if (!$this->local_peer_id || !$this->remote_peer_id) {
            $this->skip("Handshake Mesh", "Faltan peer_ids");
            return false;
        }

        $endpoint = "{$this->peer_url}/wp-json/flavor-mesh/v1/mesh/connect";
        $data = [
            'peer_id'    => $this->local_peer_id,
            'site_url'   => $this->local_url,
            'public_key' => $this->get_local_public_key(),
        ];

        $response = $this->http_post($endpoint, $data);

        if ($response && isset($response['status']) && $response['status'] === 'accepted') {
            $this->pass("Handshake Mesh", "Conexión establecida");
            return true;
        }

        if ($response && isset($response['status']) && $response['status'] === 'already_connected') {
            $this->pass("Handshake Mesh", "Ya conectados previamente");
            return true;
        }

        $this->fail("Handshake Mesh", $response['message'] ?? "Error desconocido");
        return false;
    }

    /**
     * Test de envío gossip
     */
    private function test_gossip_send() {
        $endpoint = "{$this->local_url}/wp-json/flavor-mesh/v1/gossip/send";
        $data = [
            'type'    => 'test_message',
            'payload' => [
                'test_id'   => uniqid('test_'),
                'timestamp' => time(),
                'message'   => 'Test de propagación gossip',
            ],
            'ttl' => 3,
        ];

        $response = $this->http_post($endpoint, $data);

        if ($response && isset($response['message_id'])) {
            $this->pass("Envío Gossip", "message_id: " . substr($response['message_id'], 0, 16) . "...");
            return $response['message_id'];
        }

        $this->fail("Envío Gossip", $response['message'] ?? "Error al crear mensaje");
        return false;
    }

    /**
     * Test de recepción gossip
     */
    private function test_gossip_receive() {
        // Esperar un momento para propagación
        sleep(2);

        $endpoint = "{$this->peer_url}/wp-json/flavor-mesh/v1/gossip/stats";
        $response = $this->http_get($endpoint);

        if ($response && isset($response['total_messages'])) {
            $this->pass("Recepción Gossip", "Total mensajes: " . $response['total_messages']);
            return true;
        }

        $this->warn("Recepción Gossip", "No se pudo verificar recepción");
        return false;
    }

    /**
     * Test de lista de peers
     */
    private function test_peer_list() {
        $endpoint = "{$this->local_url}/wp-json/flavor-mesh/v1/peers/list";
        $response = $this->http_get($endpoint);

        if ($response && isset($response['peers']) && is_array($response['peers'])) {
            $count = count($response['peers']);
            $this->pass("Lista de Peers", "Peers conocidos: {$count}");
            return true;
        }

        $this->fail("Lista de Peers", "No se pudo obtener lista");
        return false;
    }

    /**
     * Test de peer exchange
     */
    private function test_peer_exchange() {
        $endpoint = "{$this->peer_url}/wp-json/flavor-mesh/v1/peers/exchange";
        $data = [
            'peers' => [
                [
                    'peer_id'  => $this->local_peer_id,
                    'site_url' => $this->local_url,
                ],
            ],
        ];

        $response = $this->http_post($endpoint, $data);

        if ($response && isset($response['received'])) {
            $this->pass("Peer Exchange", "Intercambiados: " . ($response['new_peers'] ?? 0) . " nuevos");
            return true;
        }

        $this->warn("Peer Exchange", "Respuesta inesperada");
        return false;
    }

    /**
     * Test de CRDT merge
     */
    private function test_crdt_merge() {
        $endpoint = "{$this->peer_url}/wp-json/flavor-mesh/v1/crdt/merge";
        $data = [
            'doc_type'   => 'test_doc',
            'doc_id'     => 'test_' . time(),
            'field_name' => 'test_field',
            'crdt_type'  => 'lww_register',
            'state'      => [
                'value'     => 'test_value',
                'timestamp' => microtime(true),
                'peer_id'   => $this->local_peer_id,
            ],
        ];

        $response = $this->http_post($endpoint, $data);

        if ($response && isset($response['merged'])) {
            $this->pass("CRDT Merge", "Merge exitoso");
            return true;
        }

        $this->warn("CRDT Merge", "No se pudo verificar merge");
        return false;
    }

    /**
     * Test de propagación de contenido
     */
    private function test_content_propagation() {
        // Crear contenido en local
        $endpoint = "{$this->local_url}/wp-json/flavor-mesh/v1/sync/push";
        $data = [
            'type'    => 'shared_content',
            'content' => [
                'title'       => 'Test de propagación ' . date('Y-m-d H:i:s'),
                'description' => 'Contenido de prueba para verificar propagación mesh',
                'origin_peer' => $this->local_peer_id,
            ],
        ];

        $response = $this->http_post($endpoint, $data);

        if ($response && (isset($response['synced']) || isset($response['queued']))) {
            $this->pass("Propagación Contenido", "Contenido enviado a la red");
            return true;
        }

        $this->warn("Propagación Contenido", "No se pudo verificar propagación");
        return false;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPERS
    // ─────────────────────────────────────────────────────────────────────────

    private function get_local_public_key() {
        $endpoint = "{$this->local_url}/wp-json/flavor-mesh/v1/peers/local";
        $response = $this->http_get($endpoint);
        return $response['public_key'] ?? '';
    }

    private function http_get($url) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            if ($this->verbose) {
                echo "  HTTP Error: {$error}\n";
            }
            return false;
        }

        return json_decode($response, true);
    }

    private function http_post($url, $data) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            if ($this->verbose) {
                echo "  HTTP Error: {$error}\n";
            }
            return false;
        }

        return json_decode($response, true);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // OUTPUT
    // ─────────────────────────────────────────────────────────────────────────

    private function print_header() {
        echo "\n";
        echo COLOR_CYAN . "═══════════════════════════════════════════════════════════════════\n";
        echo "  TEST MULTI-NODO - Red Mesh P2P Flavor Network Communities\n";
        echo "═══════════════════════════════════════════════════════════════════\n" . COLOR_RESET;
        echo "  Local:  {$this->local_url}\n";
        echo "  Remoto: {$this->peer_url}\n";
        echo "\n";
    }

    private function section($title) {
        echo "\n" . COLOR_CYAN . "▶ {$title}\n" . COLOR_RESET;
        echo str_repeat("-", 67) . "\n";
    }

    private function pass($test, $detail = "") {
        $this->results[] = ['status' => 'pass', 'test' => $test];
        echo COLOR_GREEN . "✓ " . COLOR_RESET . "{$test}";
        if ($detail) {
            echo " - " . COLOR_YELLOW . $detail . COLOR_RESET;
        }
        echo "\n";
    }

    private function fail($test, $detail = "") {
        $this->results[] = ['status' => 'fail', 'test' => $test];
        echo COLOR_RED . "✗ " . COLOR_RESET . "{$test}";
        if ($detail) {
            echo " - " . COLOR_RED . $detail . COLOR_RESET;
        }
        echo "\n";
    }

    private function warn($test, $detail = "") {
        $this->results[] = ['status' => 'warn', 'test' => $test];
        echo COLOR_YELLOW . "⚠ " . COLOR_RESET . "{$test}";
        if ($detail) {
            echo " - " . COLOR_YELLOW . $detail . COLOR_RESET;
        }
        echo "\n";
    }

    private function skip($test, $reason = "") {
        $this->results[] = ['status' => 'skip', 'test' => $test];
        echo COLOR_YELLOW . "○ " . COLOR_RESET . "{$test} (omitido)";
        if ($reason) {
            echo " - {$reason}";
        }
        echo "\n";
    }

    private function print_summary() {
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'pass'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'fail'));
        $warned = count(array_filter($this->results, fn($r) => $r['status'] === 'warn'));
        $total = count($this->results);

        echo "\n";
        echo COLOR_CYAN . "═══════════════════════════════════════════════════════════════════\n";
        echo "                         RESUMEN\n";
        echo "═══════════════════════════════════════════════════════════════════\n" . COLOR_RESET;
        echo "  " . COLOR_GREEN . "Pasados: {$passed}" . COLOR_RESET . "\n";
        echo "  " . COLOR_RED . "Fallidos: {$failed}" . COLOR_RESET . "\n";
        echo "  " . COLOR_YELLOW . "Advertencias: {$warned}" . COLOR_RESET . "\n";
        echo "  Total: {$total}\n";
        echo COLOR_CYAN . "═══════════════════════════════════════════════════════════════════\n" . COLOR_RESET;

        if ($failed === 0) {
            echo "\n" . COLOR_GREEN . "✓ TODOS LOS TESTS CRÍTICOS PASARON" . COLOR_RESET . "\n\n";
        } else {
            echo "\n" . COLOR_RED . "✗ HAY TESTS FALLIDOS - Revisar configuración" . COLOR_RESET . "\n\n";
        }
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// MAIN
// ─────────────────────────────────────────────────────────────────────────────

// Parsear argumentos
$options = getopt('', ['local-url:', 'peer-url:', 'verbose', 'help']);

if (isset($options['help']) || !isset($options['local-url']) || !isset($options['peer-url'])) {
    echo <<<HELP

Uso: php test-mesh-multi-node.php --local-url=URL --peer-url=URL [--verbose]

Opciones:
  --local-url=URL   URL del sitio WordPress local
  --peer-url=URL    URL del sitio WordPress remoto (peer)
  --verbose         Mostrar información detallada de errores
  --help            Mostrar esta ayuda

Ejemplo:
  php test-mesh-multi-node.php \\
    --local-url=http://sitio1.local \\
    --peer-url=http://sitio2.local

HELP;
    exit(0);
}

$tester = new Mesh_Multi_Node_Tester(
    $options['local-url'],
    $options['peer-url'],
    isset($options['verbose'])
);

$tester->run_all_tests();
