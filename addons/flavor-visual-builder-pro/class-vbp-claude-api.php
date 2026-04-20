<?php
/**
 * Visual Builder Pro - Claude API
 *
 * API REST centralizada para integracion con Claude Code y automatizacion.
 * Proporciona endpoints optimizados para agentes de IA con documentacion contextual.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para la API de Claude en Visual Builder Pro
 *
 * Endpoints bajo /wp-json/flavor-vbp/v1/claude/
 *
 * @since 2.4.0
 */
if ( class_exists( 'Flavor_VBP_Claude_API', false ) ) {
	return;
}

class Flavor_VBP_Claude_API {

	/**
	 * Namespace de la API
	 *
	 * @var string
	 */
	const NAMESPACE = 'flavor-vbp/v1';

	/**
	 * Prefijo de rutas Claude
	 *
	 * @var string
	 */
	const CLAUDE_PREFIX = 'claude';

	/**
	 * Version de la API
	 *
	 * @var string
	 */
	const API_VERSION = '2.4.0';

	/**
	 * Meta key para datos VBP
	 *
	 * @var string
	 */
	const META_DATA = '_flavor_vbp_data';

	/**
	 * Instancia singleton
	 *
	 * @var Flavor_VBP_Claude_API|null
	 */
	private static $instancia = null;

	/**
	 * Documentacion de ayuda contextual
	 *
	 * @var array
	 */
	private $documentacion_ayuda = array();

	/**
	 * Obtiene la instancia singleton
	 *
	 * @return Flavor_VBP_Claude_API
	 */
	public static function get_instance() {
		if ( null === self::$instancia ) {
			self::$instancia = new self();
		}
		return self::$instancia;
	}

	/**
	 * Constructor privado
	 */
	private function __construct() {
		$this->cargar_documentacion();
		add_action( 'rest_api_init', array( $this, 'registrar_rutas' ) );
	}

	/**
	 * Carga la documentacion de ayuda contextual
	 */
	private function cargar_documentacion() {
		$this->documentacion_ayuda = array(
			'getting-started' => array(
				'title'       => 'Empezando con VBP Claude API',
				'description' => 'Guia rapida para comenzar a usar la API de Claude',
				'content'     => $this->get_doc_getting_started(),
			),
			'pages'           => array(
				'title'       => 'Gestion de Paginas',
				'description' => 'Como crear, actualizar y publicar paginas con VBP',
				'content'     => $this->get_doc_pages(),
			),
			'blocks'          => array(
				'title'       => 'Bloques Disponibles',
				'description' => 'Lista de bloques y como usarlos',
				'content'     => $this->get_doc_blocks(),
			),
			'symbols'         => array(
				'title'       => 'Sistema de Simbolos',
				'description' => 'Componentes reutilizables y variantes',
				'content'     => $this->get_doc_symbols(),
			),
			'design-tokens'   => array(
				'title'       => 'Design Tokens',
				'description' => 'Sistema de tokens de diseno',
				'content'     => $this->get_doc_design_tokens(),
			),
			'branching'       => array(
				'title'       => 'Sistema de Ramas',
				'description' => 'Trabajo paralelo con branches',
				'content'     => $this->get_doc_branching(),
			),
			'batch'           => array(
				'title'       => 'Operaciones Batch',
				'description' => 'Ejecutar multiples operaciones en una request',
				'content'     => $this->get_doc_batch(),
			),
			'workflows'       => array(
				'title'       => 'Flujos de Trabajo',
				'description' => 'Flujos comunes y mejores practicas',
				'content'     => $this->get_doc_workflows(),
			),
		);
	}

	/**
	 * Registra todas las rutas Claude
	 */
	public function registrar_rutas() {
		$namespace    = self::NAMESPACE;
		$claude_base = self::CLAUDE_PREFIX;

		// =====================================================
		// ENDPOINTS DE ESTADO Y DESCUBRIMIENTO
		// =====================================================

		// Estado general
		register_rest_route(
			$namespace,
			"/{$claude_base}/status",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_status' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// Capacidades disponibles (mejorado)
		register_rest_route(
			$namespace,
			"/{$claude_base}/capabilities",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_capabilities' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// Schema completo de bloques
		register_rest_route(
			$namespace,
			"/{$claude_base}/schema",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_schema' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// Ayuda contextual
		register_rest_route(
			$namespace,
			"/{$claude_base}/help/(?P<topic>[a-zA-Z0-9_-]+)",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_help' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// Listar temas de ayuda
		register_rest_route(
			$namespace,
			"/{$claude_base}/help",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_help_topics' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE BLOQUES
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/blocks",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_blocks' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE PAGINAS
		// =====================================================

		// Listar y crear paginas
		register_rest_route(
			$namespace,
			"/{$claude_base}/pages",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_pages' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
					'args'                => array(
						'per_page' => array(
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
						'page'     => array(
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'status'   => array(
							'default'           => 'any',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_page' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// Pagina individual
		register_rest_route(
			$namespace,
			"/{$claude_base}/pages/(?P<id>\d+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_page' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_page' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_page' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// Publicar pagina
		register_rest_route(
			$namespace,
			"/{$claude_base}/pages/(?P<id>\d+)/publish",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'publish_page' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// Crear pagina con estilos/preset
		register_rest_route(
			$namespace,
			"/{$claude_base}/pages/styled",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'create_styled_page' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE PLANTILLAS Y PRESETS
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/templates",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_templates' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/section-types",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_section_types' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/design-presets",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_design_presets' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/widgets",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_widgets' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE SIMBOLOS
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/symbols",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_symbols' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_symbol' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/symbols/(?P<id>\d+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_symbol' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_symbol' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_symbol' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/symbols/(?P<id>\d+)/instances",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_symbol_instances' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/symbols/(?P<id>\d+)/variants",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_symbol_variants' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_symbol_variant' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINTS DE ANIMACIONES
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/animations",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_animations' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_animation' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINTS DE ASSETS
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/assets",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_assets' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
					'args'                => array(
						'type'   => array(
							'default'           => 'all',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'search' => array(
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'upload_asset' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINTS DE GLOBAL STYLES
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/global-styles",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_global_styles' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_global_style' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/global-styles/(?P<id>[a-zA-Z0-9_-]+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_global_style' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_global_style' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_global_style' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINTS DE DESIGN TOKENS
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/design-tokens",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_design_tokens' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'sync_design_tokens' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/design-tokens/export",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_design_tokens' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
				'args'                => array(
					'format' => array(
						'default'           => 'json',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/design-tokens/import",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'import_design_tokens' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE BRANCHING
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/branches/(?P<post_id>\d+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_branches' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_branch' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/checkout",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'checkout_branch' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/merge",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'merge_branch' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/diff",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'diff_branches' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE PROTOTYPE/INTERACCIONES
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/prototype/(?P<page_id>\d+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_prototype' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_prototype' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/prototype/(?P<page_id>\d+)/export",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'export_prototype' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE RESPONSIVE
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/responsive/(?P<page_id>\d+)",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_responsive_variants' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'set_responsive_variants' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINTS DE IA
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/ai/generate",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_generate_layout' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		register_rest_route(
			$namespace,
			"/{$claude_base}/ai/suggest",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'ai_suggest' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE COLABORACION
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/collab/status",
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_collab_status' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);

		// =====================================================
		// ENDPOINTS DE CONFIGURACION
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/settings",
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'verificar_api_key' ),
				),
			)
		);

		// =====================================================
		// ENDPOINT DE BATCH
		// =====================================================

		register_rest_route(
			$namespace,
			"/{$claude_base}/batch",
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'execute_batch' ),
				'permission_callback' => array( $this, 'verificar_api_key' ),
			)
		);
	}

	// =========================================================
	// VERIFICACION DE API KEY
	// =========================================================

	/**
	 * Verifica la API Key de Claude
	 *
	 * Usa la función centralizada flavor_verify_vbp_api_key() que incluye:
	 * - Rate limiting (5 intentos fallidos / 5 minutos por IP)
	 * - Comparación timing-safe con hash_equals()
	 * - Logging de intentos fallidos
	 *
	 * @param WP_REST_Request $request Request REST.
	 * @return bool|WP_Error
	 */
	public function verificar_api_key( $request ) {
		$api_key = $request->get_header( 'X-VBP-Key' );

		if ( empty( $api_key ) ) {
			return new WP_Error(
				'missing_api_key',
				__( 'Se requiere el header X-VBP-Key para autenticacion', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 401 )
			);
		}

		// Rate limiting para requests válidos (100 requests/minuto por API key)
		$rate_limit_check = $this->check_request_rate_limit( $api_key );
		if ( is_wp_error( $rate_limit_check ) ) {
			return $rate_limit_check;
		}

		// Usar función centralizada con rate limiting y timing-safe comparison
		if ( function_exists( 'flavor_verify_vbp_api_key' ) && flavor_verify_vbp_api_key( $api_key ) ) {
			return true;
		}

		// Fallback: verificación directa (para compatibilidad)
		$settings  = get_option( 'flavor_vbp_settings', array() );
		$valid_key = isset( $settings['api_key'] ) && ! empty( $settings['api_key'] )
			? $settings['api_key']
			: wp_hash( 'flavor-vbp-' . NONCE_SALT );

		if ( hash_equals( $valid_key, $api_key ) ) {
			return true;
		}

		return new WP_Error(
			'invalid_api_key',
			__( 'API Key invalida', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Verifica el rate limit de requests por API key
	 *
	 * Limita a 100 requests por minuto por API key para prevenir abuso.
	 *
	 * @since 3.5.1
	 * @param string $api_key API key a verificar.
	 * @return true|WP_Error True si está dentro del límite, WP_Error si excede.
	 */
	private function check_request_rate_limit( $api_key ) {
		$key_hash      = md5( $api_key );
		$transient_key = 'vbp_claude_rate_' . $key_hash;
		$count         = (int) get_transient( $transient_key );
		$limit         = apply_filters( 'flavor_vbp_claude_api_rate_limit', 100 );

		if ( $count >= $limit ) {
			flavor_log_warning( 'VBP Claude API: Rate limit alcanzado para key ' . substr( $api_key, 0, 8 ) . '...', 'Security' );
			return new WP_Error(
				'rate_limit_exceeded',
				__( 'Rate limit excedido. Máximo 100 requests por minuto.', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 429 )
			);
		}

		set_transient( $transient_key, $count + 1, MINUTE_IN_SECONDS );
		return true;
	}

	// =========================================================
	// ENDPOINTS DE ESTADO Y DESCUBRIMIENTO
	// =========================================================

	/**
	 * Obtiene el estado general del sistema
	 *
	 * @return WP_REST_Response
	 */
	public function get_status() {
		$estado = array(
			'status'      => 'ok',
			'version'     => self::API_VERSION,
			'vbp_version' => defined( 'Flavor_VBP_Editor::VERSION' ) ? Flavor_VBP_Editor::VERSION : '2.2.4',
			'timestamp'   => current_time( 'c' ),
			'site_url'    => home_url(),
			'api_base'    => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX ),
			'features'    => array(
				'symbols'       => class_exists( 'Flavor_VBP_Symbols' ),
				'branching'     => class_exists( 'Flavor_VBP_Branching' ),
				'ai_layout'     => class_exists( 'Flavor_VBP_AI_Layout' ),
				'global_styles' => class_exists( 'Flavor_VBP_Global_Styles' ),
				'design_tokens' => class_exists( 'VBP_Figma_Tokens' ),
				'collaboration' => class_exists( 'Flavor_VBP_Collaboration_API' ),
				'asset_manager' => class_exists( 'Flavor_VBP_Asset_Manager' ),
			),
			'endpoints'   => array(
				'help'    => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/help' ),
				'schema'  => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/schema' ),
				'blocks'  => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/blocks' ),
				'pages'   => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/pages' ),
				'symbols' => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/symbols' ),
			),
		);

		return new WP_REST_Response( $estado, 200 );
	}

	/**
	 * Obtiene las capacidades disponibles (mejorado)
	 *
	 * @return WP_REST_Response
	 */
	public function get_capabilities() {
		$capabilities = array(
			'version'   => self::API_VERSION,
			'features'  => array(
				'pages'         => array(
					'enabled'     => true,
					'description' => 'CRUD de paginas VBP',
					'endpoints'   => array(
						'GET /claude/pages'              => 'Listar paginas',
						'POST /claude/pages'             => 'Crear pagina',
						'GET /claude/pages/{id}'         => 'Obtener pagina',
						'PUT /claude/pages/{id}'         => 'Actualizar pagina',
						'DELETE /claude/pages/{id}'      => 'Eliminar pagina',
						'POST /claude/pages/{id}/publish' => 'Publicar pagina',
						'POST /claude/pages/styled'      => 'Crear pagina con preset',
					),
				),
				'symbols'       => array(
					'enabled'     => class_exists( 'Flavor_VBP_Symbols' ),
					'description' => 'Componentes reutilizables con variantes',
					'endpoints'   => array(
						'GET /claude/symbols'                 => 'Listar simbolos',
						'POST /claude/symbols'                => 'Crear simbolo',
						'GET /claude/symbols/{id}'            => 'Obtener simbolo',
						'PUT /claude/symbols/{id}'            => 'Actualizar simbolo',
						'DELETE /claude/symbols/{id}'         => 'Eliminar simbolo',
						'GET /claude/symbols/{id}/instances'  => 'Ver instancias',
						'GET /claude/symbols/{id}/variants'   => 'Listar variantes',
						'POST /claude/symbols/{id}/variants'  => 'Crear variante',
					),
				),
				'branching'     => array(
					'enabled'     => class_exists( 'Flavor_VBP_Branching' ),
					'description' => 'Sistema de ramas para trabajo paralelo',
					'endpoints'   => array(
						'GET /claude/branches/{post_id}'                       => 'Listar ramas',
						'POST /claude/branches/{post_id}'                      => 'Crear rama',
						'POST /claude/branches/{post_id}/{branch_id}/checkout' => 'Cambiar a rama',
						'POST /claude/branches/{post_id}/{branch_id}/merge'    => 'Fusionar rama',
						'POST /claude/branches/{post_id}/{branch_id}/diff'     => 'Ver diferencias',
					),
				),
				'ai_layout'     => array(
					'enabled'     => class_exists( 'Flavor_VBP_AI_Layout' ),
					'description' => 'Generacion de layouts con IA',
					'endpoints'   => array(
						'POST /claude/ai/generate' => 'Generar layout',
						'POST /claude/ai/suggest'  => 'Obtener sugerencias',
					),
				),
				'global_styles' => array(
					'enabled'     => class_exists( 'Flavor_VBP_Global_Styles' ),
					'description' => 'Estilos CSS globales reutilizables',
					'endpoints'   => array(
						'GET /claude/global-styles'      => 'Listar estilos',
						'POST /claude/global-styles'     => 'Crear estilo',
						'GET /claude/global-styles/{id}' => 'Obtener estilo',
						'PUT /claude/global-styles/{id}' => 'Actualizar estilo',
						'DELETE /claude/global-styles/{id}' => 'Eliminar estilo',
					),
				),
				'design_tokens' => array(
					'enabled'     => class_exists( 'VBP_Figma_Tokens' ),
					'description' => 'Design tokens sincronizables con Figma',
					'endpoints'   => array(
						'GET /claude/design-tokens'        => 'Obtener tokens',
						'POST /claude/design-tokens'       => 'Sincronizar tokens',
						'GET /claude/design-tokens/export' => 'Exportar tokens',
						'POST /claude/design-tokens/import' => 'Importar tokens',
					),
				),
				'assets'        => array(
					'enabled'     => class_exists( 'Flavor_VBP_Asset_Manager' ),
					'description' => 'Gestion de medios e iconos',
					'endpoints'   => array(
						'GET /claude/assets'  => 'Listar assets',
						'POST /claude/assets' => 'Subir asset',
					),
				),
				'batch'         => array(
					'enabled'     => true,
					'description' => 'Ejecutar multiples operaciones',
					'endpoints'   => array(
						'POST /claude/batch' => 'Ejecutar batch',
					),
				),
			),
			'workflows' => array(
				array(
					'name'        => 'crear_landing',
					'description' => 'Crear una landing page completa',
					'steps'       => array(
						'1. GET /claude/design-presets (elegir preset)',
						'2. GET /claude/section-types (ver secciones disponibles)',
						'3. POST /claude/pages/styled (crear pagina)',
						'4. POST /claude/pages/{id}/publish (publicar)',
					),
					'example'     => array(
						'method' => 'POST',
						'path'   => '/claude/pages/styled',
						'body'   => array(
							'title'   => 'Mi Landing',
							'preset'  => 'modern',
							'sections' => array( 'hero', 'features', 'cta' ),
							'status'  => 'publish',
						),
					),
				),
				array(
					'name'        => 'crear_componente_reutilizable',
					'description' => 'Crear un simbolo con variantes',
					'steps'       => array(
						'1. POST /claude/symbols (crear simbolo base)',
						'2. POST /claude/symbols/{id}/variants (crear variantes)',
						'3. POST /claude/pages (usar instancia en pagina)',
					),
				),
				array(
					'name'        => 'experimentar_diseno',
					'description' => 'Crear rama para probar cambios',
					'steps'       => array(
						'1. POST /claude/branches/{post_id} (crear rama)',
						'2. PUT /claude/pages/{id} (hacer cambios)',
						'3. POST /claude/branches/{post_id}/{branch_id}/diff (ver diferencias)',
						'4. POST /claude/branches/{post_id}/{branch_id}/merge (fusionar si ok)',
					),
				),
			),
			'help_url'  => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/help' ),
		);

		return new WP_REST_Response( $capabilities, 200 );
	}

	/**
	 * Obtiene el schema completo de bloques
	 *
	 * @return WP_REST_Response
	 */
	public function get_schema() {
		$schema = array(
			'version'       => self::API_VERSION,
			'blocks'        => $this->get_blocks_with_schema(),
			'section_types' => $this->get_available_section_types(),
			'presets'       => $this->get_available_presets(),
			'animations'    => $this->get_available_animations(),
			'breakpoints'   => array(
				'desktop' => array( 'min' => 1200, 'label' => 'Desktop' ),
				'tablet'  => array( 'min' => 768, 'max' => 1199, 'label' => 'Tablet' ),
				'mobile'  => array( 'max' => 767, 'label' => 'Mobile' ),
			),
		);

		return new WP_REST_Response( $schema, 200 );
	}

	/**
	 * Obtiene ayuda contextual
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_help( $request ) {
		$topic = $request->get_param( 'topic' );

		if ( ! isset( $this->documentacion_ayuda[ $topic ] ) ) {
			return new WP_Error(
				'topic_not_found',
				sprintf( __( 'Tema de ayuda "%s" no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN ), $topic ),
				array(
					'status'           => 404,
					'available_topics' => array_keys( $this->documentacion_ayuda ),
				)
			);
		}

		return new WP_REST_Response( $this->documentacion_ayuda[ $topic ], 200 );
	}

	/**
	 * Lista los temas de ayuda disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_help_topics() {
		$topics = array();

		foreach ( $this->documentacion_ayuda as $id => $doc ) {
			$topics[] = array(
				'id'          => $id,
				'title'       => $doc['title'],
				'description' => $doc['description'],
				'url'         => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/help/' . $id ),
			);
		}

		return new WP_REST_Response(
			array(
				'topics'     => $topics,
				'quick_start' => 'Use GET /claude/help/getting-started para comenzar',
			),
			200
		);
	}

	// =========================================================
	// ENDPOINTS DE BLOQUES
	// =========================================================

	/**
	 * Obtiene los bloques disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_blocks() {
		$blocks = $this->get_blocks_with_schema();

		return new WP_REST_Response(
			array(
				'blocks' => $blocks,
				'total'  => count( $blocks ),
				'categories' => $this->get_block_categories(),
			),
			200
		);
	}

	// =========================================================
	// ENDPOINTS DE PAGINAS
	// =========================================================

	/**
	 * Lista las paginas VBP
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_pages( $request ) {
		$per_page = min( 50, max( 1, $request->get_param( 'per_page' ) ) );
		$page     = max( 1, $request->get_param( 'page' ) );
		$status   = $request->get_param( 'status' );

		$args = array(
			'post_type'      => array( 'page', 'flavor_landing' ),
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'meta_query'     => array(
				array(
					'key'     => self::META_DATA,
					'compare' => 'EXISTS',
				),
			),
		);

		if ( 'any' !== $status ) {
			$args['post_status'] = $status;
		} else {
			$args['post_status'] = array( 'publish', 'draft', 'pending' );
		}

		$query = new WP_Query( $args );
		$pages = array();

		foreach ( $query->posts as $post ) {
			$pages[] = $this->format_page_response( $post );
		}

		return new WP_REST_Response(
			array(
				'pages'       => $pages,
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'page'        => $page,
				'per_page'    => $per_page,
			),
			200
		);
	}

	/**
	 * Obtiene una pagina especifica
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_page( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response( $this->format_page_response( $post, true ), 200 );
	}

	/**
	 * Crea una nueva pagina
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_page( $request ) {
		$data = $request->get_json_params();

		$title    = sanitize_text_field( $data['title'] ?? '' );
		$slug     = sanitize_title( $data['slug'] ?? $title );
		$status   = sanitize_text_field( $data['status'] ?? 'draft' );
		$blocks   = $data['blocks'] ?? array();
		$settings = $data['settings'] ?? array();

		if ( empty( $title ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Se requiere un titulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 400 )
			);
		}

		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_type'   => 'flavor_landing',
				'post_status' => $status,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Guardar datos VBP
		$documento = array(
			'version'   => defined( 'Flavor_VBP_Editor::VERSION' ) ? Flavor_VBP_Editor::VERSION : '2.2.4',
			'elements'  => $blocks,
			'settings'  => wp_parse_args( $settings, array(
				'pageWidth'       => 1200,
				'backgroundColor' => '#ffffff',
				'customCss'       => '',
			) ),
			'updatedAt' => current_time( 'mysql' ),
		);

		update_post_meta( $post_id, self::META_DATA, $documento );
		update_post_meta( $post_id, '_flavor_vbp_version', $documento['version'] );

		$post = get_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'page'    => $this->format_page_response( $post, true ),
				'message' => __( 'Pagina creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'next_actions' => array(
					'publish' => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/pages/' . $post_id . '/publish' ),
					'edit'    => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/pages/' . $post_id ),
				),
			),
			201
		);
	}

	/**
	 * Actualiza una pagina
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_page( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$data = $request->get_json_params();

		// Actualizar campos de post
		$update_post = array( 'ID' => $post_id );
		if ( isset( $data['title'] ) ) {
			$update_post['post_title'] = sanitize_text_field( $data['title'] );
		}
		if ( isset( $data['slug'] ) ) {
			$update_post['post_name'] = sanitize_title( $data['slug'] );
		}
		if ( isset( $data['status'] ) ) {
			$update_post['post_status'] = sanitize_text_field( $data['status'] );
		}

		if ( count( $update_post ) > 1 ) {
			wp_update_post( $update_post );
		}

		// Actualizar documento VBP
		if ( isset( $data['blocks'] ) || isset( $data['settings'] ) ) {
			$documento = get_post_meta( $post_id, self::META_DATA, true );
			if ( ! is_array( $documento ) ) {
				$documento = array(
					'version'  => '2.2.4',
					'elements' => array(),
					'settings' => array(),
				);
			}

			if ( isset( $data['blocks'] ) ) {
				$documento['elements'] = $data['blocks'];
			}
			if ( isset( $data['settings'] ) ) {
				$documento['settings'] = array_merge( $documento['settings'], $data['settings'] );
			}

			$documento['updatedAt'] = current_time( 'mysql' );
			update_post_meta( $post_id, self::META_DATA, $documento );

			do_action( 'vbp_content_saved', $post_id, $documento );
		}

		$post = get_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'page'    => $this->format_page_response( $post, true ),
				'message' => __( 'Pagina actualizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			),
			200
		);
	}

	/**
	 * Elimina una pagina
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_page( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$deleted = wp_trash_post( $post_id );

		if ( ! $deleted ) {
			return new WP_Error(
				'delete_failed',
				__( 'No se pudo eliminar la pagina', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 500 )
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Pagina movida a papelera', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			),
			200
		);
	}

	/**
	 * Publica una pagina
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function publish_page( $request ) {
		$post_id = absint( $request->get_param( 'id' ) );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$data = $request->get_json_params();

		// Opcionalmente establecer como homepage
		$set_as_homepage = $data['set_as_homepage'] ?? false;

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);

		if ( $set_as_homepage ) {
			update_option( 'page_on_front', $post_id );
			update_option( 'show_on_front', 'page' );
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'page_id'   => $post_id,
				'url'       => get_permalink( $post_id ),
				'is_homepage' => $set_as_homepage,
				'message'   => __( 'Pagina publicada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			),
			200
		);
	}

	/**
	 * Crea una pagina con preset de estilos
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_styled_page( $request ) {
		$data = $request->get_json_params();

		$title          = sanitize_text_field( $data['title'] ?? '' );
		$slug           = sanitize_title( $data['slug'] ?? $title );
		$preset         = sanitize_text_field( $data['preset'] ?? 'modern' );
		$sections       = $data['sections'] ?? array( 'hero', 'features', 'cta' );
		$context        = $data['context'] ?? array();
		$status         = sanitize_text_field( $data['status'] ?? 'draft' );
		$set_as_homepage = $data['set_as_homepage'] ?? false;

		if ( empty( $title ) ) {
			return new WP_Error(
				'missing_title',
				__( 'Se requiere un titulo', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 400 )
			);
		}

		// Obtener preset de colores
		$preset_data = $this->get_preset_data( $preset );
		if ( ! $preset_data ) {
			$preset_data = $this->get_preset_data( 'modern' );
		}

		// Generar bloques basados en secciones
		$blocks = $this->generate_blocks_from_sections( $sections, $preset_data, $context );

		// Crear la pagina
		$post_id = wp_insert_post(
			array(
				'post_title'  => $title,
				'post_name'   => $slug,
				'post_type'   => 'flavor_landing',
				'post_status' => $status,
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}

		// Guardar datos VBP
		$documento = array(
			'version'   => defined( 'Flavor_VBP_Editor::VERSION' ) ? Flavor_VBP_Editor::VERSION : '2.2.4',
			'elements'  => $blocks,
			'settings'  => array(
				'pageWidth'       => 1200,
				'backgroundColor' => $preset_data['colores']['background'] ?? '#ffffff',
				'customCss'       => '',
				'preset'          => $preset,
			),
			'updatedAt' => current_time( 'mysql' ),
		);

		update_post_meta( $post_id, self::META_DATA, $documento );
		update_post_meta( $post_id, '_flavor_vbp_version', $documento['version'] );

		// Establecer como homepage si se solicita
		if ( $set_as_homepage ) {
			update_option( 'page_on_front', $post_id );
			update_option( 'show_on_front', 'page' );
		}

		$post = get_post( $post_id );

		return new WP_REST_Response(
			array(
				'success' => true,
				'page'    => $this->format_page_response( $post, true ),
				'preset_applied' => $preset,
				'sections_created' => $sections,
				'is_homepage' => $set_as_homepage,
				'message' => __( 'Pagina con estilos creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'next_actions' => array(
					'view'    => get_permalink( $post_id ),
					'publish' => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/pages/' . $post_id . '/publish' ),
					'edit'    => rest_url( self::NAMESPACE . '/' . self::CLAUDE_PREFIX . '/pages/' . $post_id ),
				),
			),
			201
		);
	}

	// =========================================================
	// ENDPOINTS DE PLANTILLAS Y PRESETS
	// =========================================================

	/**
	 * Obtiene las plantillas disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_templates() {
		$templates = array();

		// Templates de libreria
		if ( class_exists( 'Flavor_VBP_REST_API' ) ) {
			$api_instance    = Flavor_VBP_REST_API::get_instance();
			$library         = $api_instance->get_library_templates();
			$templates       = array_merge( $templates, $library );
		}

		// Templates de usuario
		$user_templates = get_posts(
			array(
				'post_type'      => 'vbp_template',
				'posts_per_page' => 50,
				'post_status'    => 'publish',
			)
		);

		foreach ( $user_templates as $tmpl ) {
			$templates[] = array(
				'id'          => $tmpl->ID,
				'title'       => $tmpl->post_title,
				'category'    => 'user',
				'description' => $tmpl->post_excerpt,
				'type'        => 'user',
			);
		}

		return new WP_REST_Response(
			array(
				'templates' => $templates,
				'total'     => count( $templates ),
			),
			200
		);
	}

	/**
	 * Obtiene los tipos de seccion disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_section_types() {
		$sections = $this->get_available_section_types();

		return new WP_REST_Response(
			array(
				'section_types' => $sections,
				'total'         => count( $sections ),
			),
			200
		);
	}

	/**
	 * Obtiene los presets de diseno
	 *
	 * @return WP_REST_Response
	 */
	public function get_design_presets() {
		$presets = $this->get_available_presets();

		return new WP_REST_Response(
			array(
				'presets' => $presets,
				'total'   => count( $presets ),
			),
			200
		);
	}

	/**
	 * Obtiene los widgets disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_widgets() {
		$widgets = array();

		// Widgets globales de VBP
		if ( class_exists( 'Flavor_VBP_Global_Widgets' ) ) {
			$global_widgets  = Flavor_VBP_Global_Widgets::get_instance();
			$available       = $global_widgets->obtener_widgets_disponibles();
			$widgets         = array_merge( $widgets, $available );
		}

		// Widgets de WordPress
		global $wp_widget_factory;
		if ( $wp_widget_factory ) {
			foreach ( $wp_widget_factory->widgets as $id => $widget ) {
				$widgets[] = array(
					'id'          => $id,
					'name'        => $widget->name,
					'description' => $widget->widget_options['description'] ?? '',
					'type'        => 'wordpress',
				);
			}
		}

		return new WP_REST_Response(
			array(
				'widgets' => $widgets,
				'total'   => count( $widgets ),
			),
			200
		);
	}

	// =========================================================
	// ENDPOINTS DE SIMBOLOS - Delegados a Symbols API
	// =========================================================

	/**
	 * Lista los simbolos
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_symbols() {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->listar_simbolos( new WP_REST_Request() );
	}

	/**
	 * Crea un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_symbol( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->crear_simbolo( $request );
	}

	/**
	 * Obtiene un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_symbol( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->obtener_simbolo( $request );
	}

	/**
	 * Actualiza un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_symbol( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->actualizar_simbolo( $request );
	}

	/**
	 * Elimina un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_symbol( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->eliminar_simbolo( $request );
	}

	/**
	 * Obtiene instancias de un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_symbol_instances( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->listar_instancias( $request );
	}

	/**
	 * Obtiene variantes de un simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_symbol_variants( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->listar_variantes( $request );
	}

	/**
	 * Crea una variante de simbolo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_symbol_variant( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Symbols_API' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de simbolos no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$symbols_api = Flavor_VBP_Symbols_API::get_instance();
		return $symbols_api->crear_variante( $request );
	}

	// =========================================================
	// ENDPOINTS DE ANIMACIONES
	// =========================================================

	/**
	 * Lista las animaciones disponibles
	 *
	 * @return WP_REST_Response
	 */
	public function get_animations() {
		$animations = $this->get_available_animations();

		return new WP_REST_Response(
			array(
				'animations' => $animations,
				'total'      => count( $animations ),
			),
			200
		);
	}

	/**
	 * Crea una animacion personalizada
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function create_animation( $request ) {
		$data = $request->get_json_params();

		$animations = get_option( 'vbp_custom_animations', array() );

		$animation_id = 'anim_' . wp_generate_uuid4();
		$animation    = array(
			'id'         => $animation_id,
			'name'       => sanitize_text_field( $data['name'] ?? 'Custom Animation' ),
			'keyframes'  => $data['keyframes'] ?? array(),
			'duration'   => floatval( $data['duration'] ?? 0.3 ),
			'easing'     => sanitize_text_field( $data['easing'] ?? 'ease-out' ),
			'created_at' => current_time( 'mysql' ),
		);

		$animations[ $animation_id ] = $animation;
		update_option( 'vbp_custom_animations', $animations );

		return new WP_REST_Response(
			array(
				'success'   => true,
				'animation' => $animation,
			),
			201
		);
	}

	// =========================================================
	// ENDPOINTS DE ASSETS - Delegados a Asset Manager
	// =========================================================

	/**
	 * Lista los assets
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_assets( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Asset_Manager' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El gestor de assets no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$asset_manager = Flavor_VBP_Asset_Manager::get_instance();
		return $asset_manager->rest_listar_assets( $request );
	}

	/**
	 * Sube un asset
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function upload_asset( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Asset_Manager' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El gestor de assets no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$asset_manager = Flavor_VBP_Asset_Manager::get_instance();
		return $asset_manager->rest_subir_asset( $request );
	}

	// =========================================================
	// ENDPOINTS DE GLOBAL STYLES - Delegados
	// =========================================================

	/**
	 * Lista los estilos globales
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_global_styles() {
		if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los estilos globales no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$styles = Flavor_VBP_Global_Styles::get_instance();
		return $styles->rest_obtener_estilos( new WP_REST_Request() );
	}

	/**
	 * Crea un estilo global
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_global_style( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los estilos globales no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$styles = Flavor_VBP_Global_Styles::get_instance();
		return $styles->rest_crear_estilo( $request );
	}

	/**
	 * Obtiene un estilo global
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_global_style( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los estilos globales no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$styles = Flavor_VBP_Global_Styles::get_instance();
		return $styles->rest_obtener_estilo( $request );
	}

	/**
	 * Actualiza un estilo global
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_global_style( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los estilos globales no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$styles = Flavor_VBP_Global_Styles::get_instance();
		return $styles->rest_actualizar_estilo( $request );
	}

	/**
	 * Elimina un estilo global
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_global_style( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Global_Styles' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los estilos globales no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$styles = Flavor_VBP_Global_Styles::get_instance();
		return $styles->rest_eliminar_estilo( $request );
	}

	// =========================================================
	// ENDPOINTS DE DESIGN TOKENS - Delegados
	// =========================================================

	/**
	 * Obtiene los design tokens
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_design_tokens() {
		if ( ! class_exists( 'VBP_Figma_Tokens' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los design tokens no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$tokens = VBP_Figma_Tokens::instance();
		return $tokens->get_tokens( new WP_REST_Request() );
	}

	/**
	 * Sincroniza design tokens
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function sync_design_tokens( $request ) {
		if ( ! class_exists( 'VBP_Figma_Tokens' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los design tokens no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$tokens = VBP_Figma_Tokens::instance();
		return $tokens->sync_from_figma( $request );
	}

	/**
	 * Exporta design tokens
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function export_design_tokens( $request ) {
		if ( ! class_exists( 'VBP_Figma_Tokens' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los design tokens no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$tokens = VBP_Figma_Tokens::instance();
		return $tokens->export_tokens( $request );
	}

	/**
	 * Importa design tokens
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function import_design_tokens( $request ) {
		if ( ! class_exists( 'VBP_Figma_Tokens' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'Los design tokens no estan disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$tokens = VBP_Figma_Tokens::instance();
		return $tokens->import_tokens( $request );
	}

	// =========================================================
	// ENDPOINTS DE BRANCHING - Delegados
	// =========================================================

	/**
	 * Lista las ramas de un post
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_branches( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Branching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de branching no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$branching = Flavor_VBP_Branching::get_instance();
		return $branching->listar_branches( $request );
	}

	/**
	 * Crea una rama
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function create_branch( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Branching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de branching no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$branching = Flavor_VBP_Branching::get_instance();
		return $branching->crear_branch( $request );
	}

	/**
	 * Cambia a una rama
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function checkout_branch( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Branching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de branching no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$branching = Flavor_VBP_Branching::get_instance();
		return $branching->checkout_branch( $request );
	}

	/**
	 * Fusiona una rama
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function merge_branch( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Branching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de branching no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$branching = Flavor_VBP_Branching::get_instance();
		return $branching->merge_branches( $request );
	}

	/**
	 * Obtiene diferencias entre ramas
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function diff_branches( $request ) {
		if ( ! class_exists( 'Flavor_VBP_Branching' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El sistema de branching no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$branching = Flavor_VBP_Branching::get_instance();
		return $branching->diff_branches( $request );
	}

	// =========================================================
	// ENDPOINTS DE PROTOTYPE
	// =========================================================

	/**
	 * Obtiene datos de prototipo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function get_prototype( $request ) {
		$page_id   = absint( $request->get_param( 'page_id' ) );
		$documento = get_post_meta( $page_id, self::META_DATA, true );

		if ( ! $documento ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$interactions = $documento['interactions'] ?? array();

		return new WP_REST_Response(
			array(
				'page_id'      => $page_id,
				'interactions' => $interactions,
				'preview_url'  => add_query_arg( 'vbp-prototype', '1', get_permalink( $page_id ) ),
			),
			200
		);
	}

	/**
	 * Actualiza datos de prototipo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function update_prototype( $request ) {
		$page_id   = absint( $request->get_param( 'page_id' ) );
		$data      = $request->get_json_params();
		$documento = get_post_meta( $page_id, self::META_DATA, true );

		if ( ! $documento ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$documento['interactions'] = $data['interactions'] ?? array();
		$documento['updatedAt']    = current_time( 'mysql' );

		update_post_meta( $page_id, self::META_DATA, $documento );

		return new WP_REST_Response(
			array(
				'success'      => true,
				'interactions' => $documento['interactions'],
			),
			200
		);
	}

	/**
	 * Exporta prototipo
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function export_prototype( $request ) {
		$page_id   = absint( $request->get_param( 'page_id' ) );
		$documento = get_post_meta( $page_id, self::META_DATA, true );

		if ( ! $documento ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		return new WP_REST_Response(
			array(
				'page_id'      => $page_id,
				'title'        => get_the_title( $page_id ),
				'elements'     => $documento['elements'] ?? array(),
				'interactions' => $documento['interactions'] ?? array(),
				'settings'     => $documento['settings'] ?? array(),
				'exported_at'  => current_time( 'c' ),
			),
			200
		);
	}

	// =========================================================
	// ENDPOINTS DE RESPONSIVE
	// =========================================================

	/**
	 * Obtiene variantes responsive
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function get_responsive_variants( $request ) {
		$page_id   = absint( $request->get_param( 'page_id' ) );
		$documento = get_post_meta( $page_id, self::META_DATA, true );

		if ( ! $documento ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$responsive = $documento['responsive'] ?? array(
			'desktop' => $documento['elements'] ?? array(),
			'tablet'  => array(),
			'mobile'  => array(),
		);

		return new WP_REST_Response(
			array(
				'page_id'     => $page_id,
				'breakpoints' => array(
					'desktop' => array( 'min' => 1200 ),
					'tablet'  => array( 'min' => 768, 'max' => 1199 ),
					'mobile'  => array( 'max' => 767 ),
				),
				'variants'    => $responsive,
			),
			200
		);
	}

	/**
	 * Establece variantes responsive
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function set_responsive_variants( $request ) {
		$page_id   = absint( $request->get_param( 'page_id' ) );
		$data      = $request->get_json_params();
		$documento = get_post_meta( $page_id, self::META_DATA, true );

		if ( ! $documento ) {
			return new WP_Error(
				'page_not_found',
				__( 'Pagina no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 404 )
			);
		}

		$documento['responsive'] = array(
			'desktop' => $data['desktop'] ?? $documento['elements'] ?? array(),
			'tablet'  => $data['tablet'] ?? array(),
			'mobile'  => $data['mobile'] ?? array(),
		);
		$documento['updatedAt'] = current_time( 'mysql' );

		update_post_meta( $page_id, self::META_DATA, $documento );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'variants' => $documento['responsive'],
			),
			200
		);
	}

	// =========================================================
	// ENDPOINTS DE IA - Delegados
	// =========================================================

	/**
	 * Genera layout con IA
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function ai_generate_layout( $request ) {
		if ( ! class_exists( 'Flavor_VBP_AI_Layout' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El asistente de IA no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$ai_layout = Flavor_VBP_AI_Layout::get_instance();
		return $ai_layout->generate_layout( $request );
	}

	/**
	 * Obtiene sugerencias de IA
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function ai_suggest( $request ) {
		if ( ! class_exists( 'Flavor_VBP_AI_Layout' ) ) {
			return new WP_Error(
				'feature_not_available',
				__( 'El asistente de IA no esta disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 501 )
			);
		}

		$ai_layout = Flavor_VBP_AI_Layout::get_instance();
		return $ai_layout->suggest_improvements( $request );
	}

	// =========================================================
	// ENDPOINTS DE COLABORACION
	// =========================================================

	/**
	 * Obtiene estado de colaboracion
	 *
	 * @return WP_REST_Response
	 */
	public function get_collab_status() {
		$status = array(
			'enabled'          => class_exists( 'Flavor_VBP_Collaboration_API' ),
			'realtime_enabled' => class_exists( 'Flavor_VBP_Realtime_Server' ),
		);

		if ( class_exists( 'Flavor_VBP_Collaboration_API' ) ) {
			$collab           = Flavor_VBP_Collaboration_API::get_instance();
			$status['users']  = $collab->obtener_usuarios_activos();
		}

		return new WP_REST_Response( $status, 200 );
	}

	// =========================================================
	// ENDPOINTS DE CONFIGURACION
	// =========================================================

	/**
	 * Obtiene la configuracion de VBP
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings() {
		$settings = get_option( 'flavor_vbp_settings', array() );

		// No exponer la API key completa
		if ( isset( $settings['api_key'] ) ) {
			$settings['api_key_set'] = true;
			unset( $settings['api_key'] );
		}

		$settings['defaults'] = array(
			'page_width'       => 1200,
			'background_color' => '#ffffff',
			'gutenberg_replacement' => get_option( 'vbp_replace_gutenberg', false ),
			'version_history'  => get_option( 'vbp_enable_versions', true ),
		);

		return new WP_REST_Response( $settings, 200 );
	}

	/**
	 * Actualiza la configuracion de VBP
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function update_settings( $request ) {
		$data     = $request->get_json_params();
		$settings = get_option( 'flavor_vbp_settings', array() );

		// Campos actualizables
		$allowed_fields = array(
			'default_preset',
			'default_page_width',
			'enable_ai',
			'enable_collaboration',
		);

		foreach ( $allowed_fields as $field ) {
			if ( isset( $data[ $field ] ) ) {
				$settings[ $field ] = sanitize_text_field( $data[ $field ] );
			}
		}

		update_option( 'flavor_vbp_settings', $settings );

		return new WP_REST_Response(
			array(
				'success'  => true,
				'settings' => $settings,
			),
			200
		);
	}

	// =========================================================
	// ENDPOINT DE BATCH
	// =========================================================

	/**
	 * Ejecuta operaciones batch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public function execute_batch( $request ) {
		$data          = $request->get_json_params();
		$operations    = $data['operations'] ?? array();
		$stop_on_error = $data['stop_on_error'] ?? false;

		if ( empty( $operations ) || ! is_array( $operations ) ) {
			return new WP_Error(
				'invalid_operations',
				__( 'Se requiere un array de operaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				array( 'status' => 400 )
			);
		}

		$results        = array();
		$success_count  = 0;
		$error_count    = 0;

		foreach ( $operations as $index => $op ) {
			$method = strtoupper( $op['method'] ?? 'GET' );
			$path   = $op['path'] ?? '';
			$body   = $op['body'] ?? array();
			$op_id  = $op['id'] ?? "op_$index";

			// Crear request interno
			$internal_request = new WP_REST_Request( $method, '/' . self::NAMESPACE . '/' . ltrim( $path, '/' ) );
			$internal_request->set_body_params( $body );

			// Ejecutar
			$response = rest_do_request( $internal_request );

			$result = array(
				'id'      => $op_id,
				'method'  => $method,
				'path'    => $path,
				'success' => ! $response->is_error(),
				'status'  => $response->get_status(),
				'data'    => $response->get_data(),
			);

			if ( $response->is_error() ) {
				$error_count++;
				if ( $stop_on_error ) {
					$result['stopped_at'] = $index;
					$results[]            = $result;
					break;
				}
			} else {
				$success_count++;
			}

			$results[] = $result;
		}

		return new WP_REST_Response(
			array(
				'success' => 0 === $error_count,
				'summary' => array(
					'total'     => count( $operations ),
					'success'   => $success_count,
					'failed'    => $error_count,
					'processed' => count( $results ),
				),
				'results' => $results,
			),
			200
		);
	}

	// =========================================================
	// METODOS AUXILIARES
	// =========================================================

	/**
	 * Formatea la respuesta de una pagina
	 *
	 * @param WP_Post $post         Post de WordPress.
	 * @param bool    $include_data Incluir datos VBP completos.
	 * @return array
	 */
	private function format_page_response( $post, $include_data = false ) {
		$response = array(
			'id'           => $post->ID,
			'title'        => $post->post_title,
			'slug'         => $post->post_name,
			'status'       => $post->post_status,
			'type'         => $post->post_type,
			'url'          => get_permalink( $post->ID ),
			'edit_url'     => admin_url( "post.php?post={$post->ID}&action=edit" ),
			'created_at'   => $post->post_date,
			'modified_at'  => $post->post_modified,
			'is_homepage'  => (int) get_option( 'page_on_front' ) === $post->ID,
		);

		if ( $include_data ) {
			$documento = get_post_meta( $post->ID, self::META_DATA, true );
			$response['vbp_data'] = $documento ?: array(
				'version'  => '2.2.4',
				'elements' => array(),
				'settings' => array(),
			);
		}

		return $response;
	}

	/**
	 * Obtiene bloques con schema
	 *
	 * @return array
	 */
	private function get_blocks_with_schema() {
		$blocks = array();

		if ( class_exists( 'Flavor_VBP_Block_Library' ) ) {
			$library        = Flavor_VBP_Block_Library::get_instance();
			$bloques        = $library->obtener_bloques();

			foreach ( $bloques as $id => $bloque ) {
				$blocks[] = array(
					'id'          => $id,
					'name'        => $bloque['nombre'] ?? $id,
					'category'    => $bloque['categoria'] ?? 'general',
					'icon'        => $bloque['icono'] ?? '',
					'description' => $bloque['descripcion'] ?? '',
					'props'       => $bloque['propiedades'] ?? array(),
					'supports'    => $bloque['soporta'] ?? array(),
				);
			}
		}

		return $blocks;
	}

	/**
	 * Obtiene categorias de bloques
	 *
	 * @return array
	 */
	private function get_block_categories() {
		return array(
			'layout'    => __( 'Layout', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'content'   => __( 'Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'media'     => __( 'Media', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'forms'     => __( 'Formularios', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'commerce'  => __( 'Comercio', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'social'    => __( 'Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'modules'   => __( 'Modulos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			'advanced'  => __( 'Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN ),
		);
	}

	/**
	 * Obtiene tipos de seccion disponibles
	 *
	 * @return array
	 */
	private function get_available_section_types() {
		return array(
			'hero'                 => array(
				'id'          => 'hero',
				'name'        => 'Hero Section',
				'description' => 'Seccion principal con titulo, subtitulo y CTA',
			),
			'features'             => array(
				'id'          => 'features',
				'name'        => 'Features/Caracteristicas',
				'description' => 'Grid de caracteristicas con iconos',
			),
			'cta'                  => array(
				'id'          => 'cta',
				'name'        => 'Call to Action',
				'description' => 'Seccion de conversion con boton principal',
			),
			'testimonials'         => array(
				'id'          => 'testimonials',
				'name'        => 'Testimonios',
				'description' => 'Carousel o grid de testimonios',
			),
			'faq'                  => array(
				'id'          => 'faq',
				'name'        => 'FAQ/Preguntas Frecuentes',
				'description' => 'Acordeon de preguntas y respuestas',
			),
			'pricing'              => array(
				'id'          => 'pricing',
				'name'        => 'Tabla de Precios',
				'description' => 'Comparativa de planes/precios',
			),
			'gallery'              => array(
				'id'          => 'gallery',
				'name'        => 'Galeria',
				'description' => 'Grid de imagenes',
			),
			'team'                 => array(
				'id'          => 'team',
				'name'        => 'Equipo',
				'description' => 'Grid de miembros del equipo',
			),
			'contact'              => array(
				'id'          => 'contact',
				'name'        => 'Contacto',
				'description' => 'Formulario de contacto',
			),
			'stats'                => array(
				'id'          => 'stats',
				'name'        => 'Estadisticas',
				'description' => 'Numeros y metricas destacadas',
			),
			'module_crowdfunding'  => array(
				'id'          => 'module_crowdfunding',
				'name'        => 'Modulo Crowdfunding',
				'description' => 'Grid de proyectos de financiacion',
			),
			'module_eventos'       => array(
				'id'          => 'module_eventos',
				'name'        => 'Modulo Eventos',
				'description' => 'Listado de eventos',
			),
			'module_socios'        => array(
				'id'          => 'module_socios',
				'name'        => 'Modulo Socios',
				'description' => 'Formulario de captacion',
			),
			'module_participacion' => array(
				'id'          => 'module_participacion',
				'name'        => 'Modulo Participacion',
				'description' => 'Propuestas y votaciones',
			),
			'module_marketplace'   => array(
				'id'          => 'module_marketplace',
				'name'        => 'Modulo Marketplace',
				'description' => 'Grid de productos',
			),
		);
	}

	/**
	 * Obtiene presets disponibles
	 *
	 * @return array
	 */
	private function get_available_presets() {
		$presets = array();

		if ( class_exists( 'Flavor_VBP_Design_Presets' ) ) {
			$design_presets = Flavor_VBP_Design_Presets::get_instance();
			$all_presets    = $design_presets->obtener_todos_presets();

			foreach ( $all_presets as $id => $preset ) {
				$presets[] = array(
					'id'          => $id,
					'name'        => $preset['nombre'] ?? $id,
					'description' => $preset['descripcion'] ?? '',
					'colors'      => $preset['colores'] ?? array(),
				);
			}
		} else {
			// Presets por defecto
			$presets = array(
				array(
					'id'     => 'modern',
					'name'   => 'Modern Blue',
					'colors' => array(
						'primary'   => '#3b82f6',
						'secondary' => '#1e40af',
					),
				),
				array(
					'id'     => 'nature',
					'name'   => 'Nature Green',
					'colors' => array(
						'primary'   => '#22c55e',
						'secondary' => '#16a34a',
					),
				),
				array(
					'id'     => 'community',
					'name'   => 'Community',
					'colors' => array(
						'primary'   => '#8b5cf6',
						'secondary' => '#f97316',
					),
				),
			);
		}

		return $presets;
	}

	/**
	 * Obtiene animaciones disponibles
	 *
	 * @return array
	 */
	private function get_available_animations() {
		$built_in = array(
			'fade-in'        => array( 'id' => 'fade-in', 'name' => 'Fade In', 'type' => 'entrance' ),
			'fade-out'       => array( 'id' => 'fade-out', 'name' => 'Fade Out', 'type' => 'exit' ),
			'slide-up'       => array( 'id' => 'slide-up', 'name' => 'Slide Up', 'type' => 'entrance' ),
			'slide-down'     => array( 'id' => 'slide-down', 'name' => 'Slide Down', 'type' => 'entrance' ),
			'slide-left'     => array( 'id' => 'slide-left', 'name' => 'Slide Left', 'type' => 'entrance' ),
			'slide-right'    => array( 'id' => 'slide-right', 'name' => 'Slide Right', 'type' => 'entrance' ),
			'scale-up'       => array( 'id' => 'scale-up', 'name' => 'Scale Up', 'type' => 'entrance' ),
			'scale-down'     => array( 'id' => 'scale-down', 'name' => 'Scale Down', 'type' => 'exit' ),
			'bounce'         => array( 'id' => 'bounce', 'name' => 'Bounce', 'type' => 'emphasis' ),
			'pulse'          => array( 'id' => 'pulse', 'name' => 'Pulse', 'type' => 'emphasis' ),
			'shake'          => array( 'id' => 'shake', 'name' => 'Shake', 'type' => 'emphasis' ),
		);

		$custom = get_option( 'vbp_custom_animations', array() );

		return array_merge( array_values( $built_in ), array_values( $custom ) );
	}

	/**
	 * Obtiene datos de un preset
	 *
	 * @param string $preset_id ID del preset.
	 * @return array|null
	 */
	private function get_preset_data( $preset_id ) {
		if ( class_exists( 'Flavor_VBP_Design_Presets' ) ) {
			$design_presets = Flavor_VBP_Design_Presets::get_instance();
			return $design_presets->obtener_preset( $preset_id );
		}

		// Presets por defecto
		$defaults = array(
			'modern' => array(
				'colores' => array(
					'primary'    => '#3b82f6',
					'secondary'  => '#1e40af',
					'background' => '#ffffff',
					'text'       => '#1f2937',
				),
			),
			'nature' => array(
				'colores' => array(
					'primary'    => '#22c55e',
					'secondary'  => '#16a34a',
					'background' => '#f0fdf4',
					'text'       => '#14532d',
				),
			),
			'community' => array(
				'colores' => array(
					'primary'    => '#8b5cf6',
					'secondary'  => '#f97316',
					'background' => '#faf5ff',
					'text'       => '#581c87',
				),
			),
		);

		return $defaults[ $preset_id ] ?? null;
	}

	/**
	 * Genera bloques desde secciones
	 *
	 * @param array $sections    Tipos de seccion.
	 * @param array $preset_data Datos del preset.
	 * @param array $context     Contexto adicional.
	 * @return array
	 */
	private function generate_blocks_from_sections( $sections, $preset_data, $context ) {
		$blocks = array();
		$colors = $preset_data['colores'] ?? array();

		foreach ( $sections as $section_type ) {
			$block = $this->generate_section_block( $section_type, $colors, $context );
			if ( $block ) {
				$blocks[] = $block;
			}
		}

		return $blocks;
	}

	/**
	 * Genera un bloque de seccion
	 *
	 * @param string $type    Tipo de seccion.
	 * @param array  $colors  Colores del preset.
	 * @param array  $context Contexto.
	 * @return array|null
	 */
	private function generate_section_block( $type, $colors, $context ) {
		$topic = $context['topic'] ?? 'Nuestros Servicios';

		switch ( $type ) {
			case 'hero':
				return array(
					'type'     => 'section',
					'id'       => 'section_hero_' . wp_generate_uuid4(),
					'props'    => array(
						'className'  => 'hero-section',
						'background' => array(
							'type'  => 'gradient',
							'from'  => $colors['primary'] ?? '#3b82f6',
							'to'    => $colors['secondary'] ?? '#1e40af',
						),
						'padding'    => '80px 0',
					),
					'children' => array(
						array(
							'type'  => 'heading',
							'props' => array(
								'level' => 1,
								'text'  => $topic,
								'align' => 'center',
								'color' => '#ffffff',
							),
						),
						array(
							'type'  => 'text',
							'props' => array(
								'content' => 'Descubre todo lo que podemos ofrecerte',
								'align'   => 'center',
								'color'   => '#ffffff',
							),
						),
						array(
							'type'  => 'button',
							'props' => array(
								'text'  => 'Comenzar',
								'style' => 'primary',
								'size'  => 'large',
								'align' => 'center',
							),
						),
					),
				);

			case 'features':
				return array(
					'type'     => 'section',
					'id'       => 'section_features_' . wp_generate_uuid4(),
					'props'    => array(
						'className' => 'features-section',
						'padding'   => '60px 0',
					),
					'children' => array(
						array(
							'type'  => 'heading',
							'props' => array(
								'level' => 2,
								'text'  => 'Caracteristicas',
								'align' => 'center',
							),
						),
						array(
							'type'     => 'columns',
							'props'    => array(
								'columns' => 3,
								'gap'     => '30px',
							),
							'children' => array(
								array(
									'type'  => 'feature-card',
									'props' => array(
										'icon'  => 'star',
										'title' => 'Calidad',
										'text'  => 'Los mejores estandares',
									),
								),
								array(
									'type'  => 'feature-card',
									'props' => array(
										'icon'  => 'clock',
										'title' => 'Rapido',
										'text'  => 'Resultados inmediatos',
									),
								),
								array(
									'type'  => 'feature-card',
									'props' => array(
										'icon'  => 'shield',
										'title' => 'Seguro',
										'text'  => 'Tu tranquilidad primero',
									),
								),
							),
						),
					),
				);

			case 'cta':
				return array(
					'type'     => 'section',
					'id'       => 'section_cta_' . wp_generate_uuid4(),
					'props'    => array(
						'className'  => 'cta-section',
						'background' => $colors['primary'] ?? '#3b82f6',
						'padding'    => '60px 0',
					),
					'children' => array(
						array(
							'type'  => 'heading',
							'props' => array(
								'level' => 2,
								'text'  => 'Listo para empezar?',
								'align' => 'center',
								'color' => '#ffffff',
							),
						),
						array(
							'type'  => 'button',
							'props' => array(
								'text'  => 'Contactanos',
								'style' => 'secondary',
								'size'  => 'large',
								'align' => 'center',
							),
						),
					),
				);

			default:
				return null;
		}
	}

	// =========================================================
	// DOCUMENTACION DE AYUDA
	// =========================================================

	/**
	 * Documentacion: Getting Started
	 *
	 * @return array
	 */
	private function get_doc_getting_started() {
		return array(
			'steps' => array(
				'1. Verificar status' => 'GET /claude/status',
				'2. Ver capacidades'  => 'GET /claude/capabilities',
				'3. Obtener bloques'  => 'GET /claude/blocks',
				'4. Crear pagina'     => 'POST /claude/pages',
				'5. Publicar'         => 'POST /claude/pages/{id}/publish',
			),
			'authentication' => array(
				'header' => 'X-VBP-Key: <tu-api-key>',
				'example' => 'curl -H "X-VBP-Key: abc123" https://sitio.com/wp-json/flavor-vbp/v1/claude/status',
			),
			'quick_example' => array(
				'method' => 'POST',
				'url'    => '/wp-json/flavor-vbp/v1/claude/pages/styled',
				'body'   => array(
					'title'   => 'Mi Primera Pagina',
					'preset'  => 'modern',
					'sections' => array( 'hero', 'features', 'cta' ),
					'status'  => 'publish',
				),
			),
		);
	}

	/**
	 * Documentacion: Pages
	 *
	 * @return array
	 */
	private function get_doc_pages() {
		return array(
			'endpoints' => array(
				'list'    => 'GET /claude/pages',
				'create'  => 'POST /claude/pages',
				'get'     => 'GET /claude/pages/{id}',
				'update'  => 'PUT /claude/pages/{id}',
				'delete'  => 'DELETE /claude/pages/{id}',
				'publish' => 'POST /claude/pages/{id}/publish',
				'styled'  => 'POST /claude/pages/styled',
			),
			'create_example' => array(
				'title'    => 'Mi Pagina',
				'slug'     => 'mi-pagina',
				'status'   => 'draft',
				'blocks'   => array(),
				'settings' => array(
					'pageWidth'       => 1200,
					'backgroundColor' => '#ffffff',
				),
			),
			'styled_example' => array(
				'title'          => 'Landing Page',
				'preset'         => 'modern',
				'sections'       => array( 'hero', 'features', 'testimonials', 'cta' ),
				'set_as_homepage' => true,
				'status'         => 'publish',
			),
		);
	}

	/**
	 * Documentacion: Blocks
	 *
	 * @return array
	 */
	private function get_doc_blocks() {
		return array(
			'endpoint' => 'GET /claude/blocks',
			'schema'   => 'GET /claude/schema',
			'categories' => $this->get_block_categories(),
			'usage' => 'Los bloques se usan dentro del array "blocks" al crear/actualizar paginas',
		);
	}

	/**
	 * Documentacion: Symbols
	 *
	 * @return array
	 */
	private function get_doc_symbols() {
		return array(
			'description' => 'Los simbolos son componentes reutilizables. Cuando actualizas un simbolo, todas sus instancias se actualizan.',
			'endpoints' => array(
				'list'      => 'GET /claude/symbols',
				'create'    => 'POST /claude/symbols',
				'get'       => 'GET /claude/symbols/{id}',
				'update'    => 'PUT /claude/symbols/{id}',
				'delete'    => 'DELETE /claude/symbols/{id}',
				'instances' => 'GET /claude/symbols/{id}/instances',
				'variants'  => 'GET /claude/symbols/{id}/variants',
			),
			'create_example' => array(
				'name'     => 'Card Component',
				'category' => 'components',
				'elements' => array( /* elementos del simbolo */ ),
			),
		);
	}

	/**
	 * Documentacion: Design Tokens
	 *
	 * @return array
	 */
	private function get_doc_design_tokens() {
		return array(
			'description' => 'Los design tokens son variables de diseno (colores, espaciados, tipografia) que se pueden sincronizar con Figma.',
			'endpoints' => array(
				'get'    => 'GET /claude/design-tokens',
				'sync'   => 'POST /claude/design-tokens',
				'export' => 'GET /claude/design-tokens/export?format=css',
				'import' => 'POST /claude/design-tokens/import',
			),
			'formats' => array( 'json', 'css', 'scss', 'tailwind', 'figma', 'w3c' ),
		);
	}

	/**
	 * Documentacion: Branching
	 *
	 * @return array
	 */
	private function get_doc_branching() {
		return array(
			'description' => 'El sistema de branching permite crear versiones paralelas de una pagina para experimentar sin afectar la version principal.',
			'workflow' => array(
				'1. Crear rama'    => 'POST /claude/branches/{post_id}',
				'2. Hacer cambios' => 'PUT /claude/pages/{post_id}',
				'3. Ver diff'      => 'POST /claude/branches/{post_id}/{branch_id}/diff',
				'4. Merge'         => 'POST /claude/branches/{post_id}/{branch_id}/merge',
			),
		);
	}

	/**
	 * Documentacion: Batch
	 *
	 * @return array
	 */
	private function get_doc_batch() {
		return array(
			'description' => 'El endpoint batch permite ejecutar multiples operaciones en una sola request.',
			'endpoint'    => 'POST /claude/batch',
			'example'     => array(
				'operations' => array(
					array(
						'id'     => 'op1',
						'method' => 'POST',
						'path'   => '/claude/pages',
						'body'   => array( 'title' => 'Pagina 1' ),
					),
					array(
						'id'     => 'op2',
						'method' => 'POST',
						'path'   => '/claude/pages',
						'body'   => array( 'title' => 'Pagina 2' ),
					),
				),
				'stop_on_error' => false,
			),
		);
	}

	/**
	 * Documentacion: Workflows
	 *
	 * @return array
	 */
	private function get_doc_workflows() {
		return array(
			'crear_sitio_completo' => array(
				'description' => 'Workflow para crear un sitio completo con VBP',
				'steps'       => array(
					'1. GET /claude/design-presets (elegir preset)',
					'2. POST /claude/pages/styled (crear homepage)',
					'3. POST /claude/pages/styled (crear otras paginas)',
					'4. POST /claude/symbols (crear componentes reutilizables)',
				),
			),
			'actualizar_componente_global' => array(
				'description' => 'Actualizar un componente en todas las paginas',
				'steps'       => array(
					'1. GET /claude/symbols (encontrar simbolo)',
					'2. PUT /claude/symbols/{id} (actualizar)',
					'3. Todas las instancias se actualizan automaticamente',
				),
			),
		);
	}

	// =========================================================
	// METODOS PUBLICOS PARA HELPERS
	// =========================================================

	/**
	 * Obtiene la API key publica (ofuscada para mostrar)
	 *
	 * @return string
	 */
	public function obtener_api_key_publica() {
		$settings = get_option( 'flavor_vbp_settings', array() );

		if ( isset( $settings['api_key'] ) && ! empty( $settings['api_key'] ) ) {
			return $settings['api_key'];
		}

		// Generar key por defecto basada en NONCE_SALT
		return wp_hash( 'flavor-vbp-' . NONCE_SALT );
	}
}

// Funciones helper globales
if ( ! function_exists( 'flavor_get_vbp_api_key' ) ) {
	/**
	 * Obtiene la API key de VBP
	 *
	 * @return string
	 */
	function flavor_get_vbp_api_key() {
		return Flavor_VBP_Claude_API::get_instance()->obtener_api_key_publica();
	}
}

if ( ! function_exists( 'flavor_regenerate_vbp_api_key' ) ) {
	/**
	 * Regenera la API key de VBP
	 *
	 * @return string
	 */
	function flavor_regenerate_vbp_api_key() {
		if ( class_exists( 'Flavor_VBP_REST_API' ) ) {
			return Flavor_VBP_REST_API::regenerar_api_key();
		}
		return '';
	}
}

if ( ! function_exists( 'flavor_verify_vbp_api_key' ) ) {
	/**
	 * Verifica una API key de VBP
	 *
	 * @param string $key Key a verificar.
	 * @return bool
	 */
	function flavor_verify_vbp_api_key( $key ) {
		$settings  = get_option( 'flavor_vbp_settings', array() );
		$valid_key = isset( $settings['api_key'] ) && ! empty( $settings['api_key'] )
			? $settings['api_key']
			: wp_hash( 'flavor-vbp-' . NONCE_SALT );

		return $key === $valid_key;
	}
}
