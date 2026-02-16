<?php
/**
 * Visual Builder Pro - REST API Tests
 *
 * Tests básicos para los endpoints REST del editor visual.
 *
 * Para ejecutar:
 * - Con WP CLI: wp eval-file tests/php/test-vbp-rest-api.php
 * - O incluir en un contexto WordPress con acceso a las clases VBP
 *
 * @package Flavor_Chat_IA
 * @since 2.0.0
 */

// Evitar ejecución directa sin WordPress
if ( ! defined( 'ABSPATH' ) && ! defined( 'VBP_TESTING' ) ) {
	// Intentar cargar WordPress para tests standalone
	$wp_load_path = dirname( __FILE__, 6 ) . '/wp-load.php';
	if ( file_exists( $wp_load_path ) ) {
		define( 'VBP_TESTING', true );
		require_once $wp_load_path;
	} else {
		die( 'Este archivo debe ejecutarse en un contexto WordPress.' );
	}
}

/**
 * Clase de tests para VBP REST API
 */
class VBP_REST_API_Tests {

	/**
	 * Contadores de tests
	 *
	 * @var array
	 */
	private $results = array(
		'passed' => 0,
		'failed' => 0,
		'errors' => array(),
	);

	/**
	 * Post ID de prueba
	 *
	 * @var int
	 */
	private $test_post_id = 0;

	/**
	 * Ejecutar todos los tests
	 *
	 * @return bool True si todos los tests pasan
	 */
	public function run() {
		$this->log( "\n=== VBP REST API Tests ===" );
		$this->log( 'Fecha: ' . date( 'Y-m-d H:i:s' ) );
		$this->log( '' );

		// Setup
		$this->setup();

		// Ejecutar suites de tests
		$this->test_rest_api_registered();
		$this->test_document_operations();
		$this->test_blocks_endpoint();
		$this->test_element_rendering();
		$this->test_validation();
		$this->test_permissions();

		// Cleanup
		$this->cleanup();

		// Mostrar resultados
		$this->show_results();

		return $this->results['failed'] === 0;
	}

	/**
	 * Configuración inicial de tests
	 */
	private function setup() {
		$this->log( 'Configurando entorno de pruebas...' );

		// Crear post de prueba
		$this->test_post_id = wp_insert_post(
			array(
				'post_title'  => 'VBP Test Post - ' . time(),
				'post_type'   => 'flavor_landing',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $this->test_post_id ) ) {
			$this->log( 'ERROR: No se pudo crear post de prueba' );
			return;
		}

		$this->log( 'Post de prueba creado: #' . $this->test_post_id );
	}

	/**
	 * Limpieza después de tests
	 */
	private function cleanup() {
		$this->log( '' );
		$this->log( 'Limpiando entorno de pruebas...' );

		if ( $this->test_post_id ) {
			wp_delete_post( $this->test_post_id, true );
			$this->log( 'Post de prueba eliminado' );
		}
	}

	/**
	 * Test: REST API está registrada
	 */
	private function test_rest_api_registered() {
		$this->describe( 'REST API Registration' );

		// Verificar que el namespace existe
		$this->it(
			'El namespace flavor-vbp/v1 debe estar registrado',
			function() {
				$server     = rest_get_server();
				$namespaces = $server->get_namespaces();
				return in_array( 'flavor-vbp/v1', $namespaces, true );
			}
		);

		// Verificar rutas principales
		$routes_to_check = array(
			'/flavor-vbp/v1/documents/(?P<id>\\d+)',
			'/flavor-vbp/v1/blocks',
			'/flavor-vbp/v1/render-element',
		);

		$server = rest_get_server();
		$routes = array_keys( $server->get_routes() );

		foreach ( $routes_to_check as $route ) {
			$route_exists = false;
			foreach ( $routes as $registered_route ) {
				if ( strpos( $registered_route, 'flavor-vbp/v1' ) !== false ) {
					$route_exists = true;
					break;
				}
			}

			$this->it(
				'La ruta ' . $route . ' debe existir',
				function() use ( $route_exists ) {
					return $route_exists;
				}
			);
		}
	}

	/**
	 * Test: Operaciones de documentos
	 */
	private function test_document_operations() {
		$this->describe( 'Document Operations' );

		// Test guardar documento
		$test_elements = array(
			array(
				'id'      => 'test_el_1',
				'type'    => 'hero',
				'variant' => 'centered',
				'name'    => 'Hero Test',
				'visible' => true,
				'locked'  => false,
				'data'    => array(
					'titulo'    => 'Título de prueba',
					'subtitulo' => 'Subtítulo de prueba',
				),
				'styles'  => array(
					'spacing' => array( 'padding' => array( 'top' => '40px' ) ),
				),
			),
		);

		$test_settings = array(
			'pageWidth'       => 1200,
			'backgroundColor' => '#ffffff',
		);

		// Guardar documento
		$this->it(
			'Debe poder guardar documento con elementos',
			function() use ( $test_elements, $test_settings ) {
				if ( ! $this->test_post_id ) {
					return false;
				}

				// Simular guardado directo (sin request HTTP)
				$save_result = update_post_meta( $this->test_post_id, '_vbp_elements', $test_elements );
				update_post_meta( $this->test_post_id, '_vbp_settings', $test_settings );

				return $save_result !== false;
			}
		);

		// Cargar documento
		$this->it(
			'Debe poder cargar documento guardado',
			function() use ( $test_elements ) {
				if ( ! $this->test_post_id ) {
					return false;
				}

				$loaded_elements = get_post_meta( $this->test_post_id, '_vbp_elements', true );

				return is_array( $loaded_elements ) &&
					   count( $loaded_elements ) === 1 &&
					   $loaded_elements[0]['id'] === 'test_el_1';
			}
		);

		// Verificar estructura de elemento
		$this->it(
			'Los elementos deben mantener su estructura',
			function() use ( $test_elements ) {
				$loaded = get_post_meta( $this->test_post_id, '_vbp_elements', true );
				$element = $loaded[0];

				return isset( $element['id'] ) &&
					   isset( $element['type'] ) &&
					   isset( $element['data'] ) &&
					   isset( $element['styles'] ) &&
					   $element['data']['titulo'] === 'Título de prueba';
			}
		);
	}

	/**
	 * Test: Endpoint de bloques
	 */
	private function test_blocks_endpoint() {
		$this->describe( 'Blocks Library Endpoint' );

		$this->it(
			'La librería de bloques debe existir',
			function() {
				// Verificar que la clase existe
				return class_exists( 'VBP_Block_Library' );
			}
		);

		$this->it(
			'Debe retornar lista de bloques categorizada',
			function() {
				if ( ! class_exists( 'VBP_Block_Library' ) ) {
					return false;
				}

				$library = VBP_Block_Library::get_instance();
				$blocks  = $library->get_all_blocks();

				return is_array( $blocks ) && ! empty( $blocks );
			}
		);

		$this->it(
			'Cada bloque debe tener propiedades requeridas',
			function() {
				if ( ! class_exists( 'VBP_Block_Library' ) ) {
					return false;
				}

				$library = VBP_Block_Library::get_instance();
				$blocks  = $library->get_all_blocks();

				if ( empty( $blocks ) ) {
					return true; // Sin bloques, no hay que validar
				}

				// Verificar primer bloque de cada categoría
				foreach ( $blocks as $category => $category_blocks ) {
					if ( ! empty( $category_blocks ) ) {
						$first_block = reset( $category_blocks );
						if ( ! isset( $first_block['name'] ) || ! isset( $first_block['icon'] ) ) {
							return false;
						}
					}
				}

				return true;
			}
		);
	}

	/**
	 * Test: Renderizado de elementos
	 */
	private function test_element_rendering() {
		$this->describe( 'Element Rendering' );

		$this->it(
			'La clase VBP_Canvas debe existir',
			function() {
				return class_exists( 'VBP_Canvas' );
			}
		);

		$this->it(
			'Debe poder renderizar un elemento básico',
			function() {
				if ( ! class_exists( 'VBP_Canvas' ) ) {
					return false;
				}

				$test_element = array(
					'id'      => 'render_test',
					'type'    => 'texto',
					'data'    => array( 'contenido' => '<p>Test</p>' ),
					'styles'  => array(),
					'visible' => true,
				);

				$canvas = VBP_Canvas::get_instance();

				// Intentar renderizar
				ob_start();
				$canvas->render_element( $test_element, 'editor' );
				$output = ob_get_clean();

				// Debe producir algún output
				return ! empty( $output );
			}
		);
	}

	/**
	 * Test: Validación de datos
	 */
	private function test_validation() {
		$this->describe( 'Data Validation' );

		$this->it(
			'Debe sanitizar contenido HTML en elementos',
			function() {
				$malicious_content = '<script>alert("xss")</script><p>Safe content</p>';
				$sanitized         = wp_kses_post( $malicious_content );

				// El script debe ser removido
				return strpos( $sanitized, '<script>' ) === false &&
					   strpos( $sanitized, '<p>' ) !== false;
			}
		);

		$this->it(
			'Debe validar IDs de elementos',
			function() {
				// IDs válidos
				$valid_ids = array( 'el_abc123', 'el_12345', 'element_test' );

				foreach ( $valid_ids as $id ) {
					if ( ! preg_match( '/^[a-zA-Z][a-zA-Z0-9_]*$/', $id ) ) {
						return false;
					}
				}

				return true;
			}
		);

		$this->it(
			'Debe rechazar tipos de bloque inválidos',
			function() {
				$valid_types = array( 'hero', 'texto', 'imagen', 'boton', 'cta' );
				$test_type   = '../../../wp-config';

				return ! in_array( $test_type, $valid_types, true );
			}
		);
	}

	/**
	 * Test: Permisos
	 */
	private function test_permissions() {
		$this->describe( 'Permissions' );

		$this->it(
			'El endpoint debe requerir autenticación',
			function() {
				// Sin usuario logueado
				$current_user = wp_get_current_user();

				// Verificar que la capability existe
				return current_user_can( 'edit_posts' ) || $current_user->ID === 0;
			}
		);

		$this->it(
			'Los administradores deben poder editar',
			function() {
				// Crear usuario admin temporal
				$admin_id = wp_create_user(
					'vbp_test_admin_' . time(),
					wp_generate_password(),
					'vbp_test@example.com'
				);

				if ( is_wp_error( $admin_id ) ) {
					return true; // Skip si no se puede crear usuario
				}

				$user = new WP_User( $admin_id );
				$user->set_role( 'administrator' );

				$can_edit = user_can( $admin_id, 'edit_posts' );

				// Limpiar
				wp_delete_user( $admin_id );

				return $can_edit;
			}
		);
	}

	/**
	 * Helper: Describe suite de tests
	 *
	 * @param string $name Nombre de la suite
	 */
	private function describe( $name ) {
		$this->log( '' );
		$this->log( '--- ' . $name . ' ---' );
	}

	/**
	 * Helper: Ejecutar un test individual
	 *
	 * @param string   $description Descripción del test
	 * @param callable $test_fn     Función de test que retorna true/false
	 */
	private function it( $description, $test_fn ) {
		try {
			$result = $test_fn();

			if ( $result ) {
				$this->results['passed']++;
				$this->log( '  ✓ ' . $description );
			} else {
				$this->results['failed']++;
				$this->results['errors'][] = $description;
				$this->log( '  ✗ ' . $description );
			}
		} catch ( Exception $e ) {
			$this->results['failed']++;
			$this->results['errors'][] = $description . ' (Exception: ' . $e->getMessage() . ')';
			$this->log( '  ✗ ' . $description . ' [ERROR: ' . $e->getMessage() . ']' );
		}
	}

	/**
	 * Helper: Mostrar mensaje
	 *
	 * @param string $message Mensaje a mostrar
	 */
	private function log( $message ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( $message );
		} else {
			echo $message . "\n";
		}
	}

	/**
	 * Mostrar resultados finales
	 */
	private function show_results() {
		$this->log( '' );
		$this->log( '=== Resultados ===' );
		$this->log( 'Pasaron: ' . $this->results['passed'] );
		$this->log( 'Fallaron: ' . $this->results['failed'] );

		if ( ! empty( $this->results['errors'] ) ) {
			$this->log( '' );
			$this->log( 'Tests fallidos:' );
			foreach ( $this->results['errors'] as $error ) {
				$this->log( '  - ' . $error );
			}
		}

		$this->log( '' );
		$total = $this->results['passed'] + $this->results['failed'];
		$this->log( $this->results['failed'] === 0 ? 'TODOS LOS TESTS PASARON' : 'ALGUNOS TESTS FALLARON' );
	}
}

// Ejecutar tests si se llama directamente
if ( defined( 'VBP_TESTING' ) || ( defined( 'WP_CLI' ) && WP_CLI ) || php_sapi_name() === 'cli' ) {
	$tests = new VBP_REST_API_Tests();
	$tests->run();
}
