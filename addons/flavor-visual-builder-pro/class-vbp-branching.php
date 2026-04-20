<?php
/**
 * Visual Builder Pro - Branching System
 *
 * Sistema de ramas de diseño para trabajo paralelo y experimentación.
 *
 * @package Flavor_Platform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase para gestionar el sistema de branching
 *
 * @since 2.1.0
 */
class Flavor_VBP_Branching {

	/**
	 * Namespace de la API REST
	 *
	 * @var string
	 */
	const API_NAMESPACE = 'flavor-vbp/v1';

	/**
	 * Meta key unificada del editor
	 *
	 * @var string
	 */
	const META_DATA = '_flavor_vbp_data';

	/**
	 * Meta key para almacenar la rama activa de un post
	 *
	 * @var string
	 */
	const META_ACTIVE_BRANCH = '_flavor_vbp_active_branch';

	/**
	 * Estado de branch: activa
	 *
	 * @var string
	 */
	const STATUS_ACTIVE = 'active';

	/**
	 * Estado de branch: fusionada
	 *
	 * @var string
	 */
	const STATUS_MERGED = 'merged';

	/**
	 * Estado de branch: archivada
	 *
	 * @var string
	 */
	const STATUS_ARCHIVED = 'archived';

	/**
	 * Instancia singleton
	 *
	 * @var Flavor_VBP_Branching|null
	 */
	private static $instancia = null;

	/**
	 * Nombre de la tabla de ramas
	 *
	 * @var string
	 */
	private $tabla_branches;

	/**
	 * Nombre de la tabla de versiones de rama
	 *
	 * @var string
	 */
	private $tabla_branch_versions;

	/**
	 * Obtiene la instancia singleton
	 *
	 * @return Flavor_VBP_Branching
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
		global $wpdb;
		$this->tabla_branches        = $wpdb->prefix . 'vbp_branches';
		$this->tabla_branch_versions = $wpdb->prefix . 'vbp_branch_versions';

		$this->crear_tablas();
		add_action( 'rest_api_init', array( $this, 'registrar_rutas' ) );
		add_action( 'vbp_content_saved', array( $this, 'guardar_version_en_branch' ), 10, 2 );
	}

	/**
	 * Crea las tablas necesarias para el sistema de branching
	 */
	private function crear_tablas() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Tabla de ramas
		$sql_branches = "CREATE TABLE IF NOT EXISTS {$this->tabla_branches} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			post_id bigint(20) unsigned NOT NULL,
			branch_name varchar(255) NOT NULL,
			branch_slug varchar(255) NOT NULL,
			parent_branch_id bigint(20) unsigned DEFAULT NULL,
			created_from_version_id bigint(20) unsigned DEFAULT NULL,
			created_by bigint(20) unsigned NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			description text DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			merged_into_branch_id bigint(20) unsigned DEFAULT NULL,
			merged_at datetime DEFAULT NULL,
			merged_by bigint(20) unsigned DEFAULT NULL,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY parent_branch_id (parent_branch_id),
			KEY status (status),
			UNIQUE KEY post_branch_slug (post_id, branch_slug)
		) $charset_collate;";

		// Tabla de versiones de rama
		$sql_branch_versions = "CREATE TABLE IF NOT EXISTS {$this->tabla_branch_versions} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			branch_id bigint(20) unsigned NOT NULL,
			version_data longtext NOT NULL,
			version_hash varchar(64) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			created_by bigint(20) unsigned NOT NULL,
			message varchar(500) DEFAULT '',
			PRIMARY KEY (id),
			KEY branch_id (branch_id),
			KEY version_hash (version_hash),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql_branches );
		dbDelta( $sql_branch_versions );
	}

	/**
	 * Registra las rutas REST para branching
	 */
	public function registrar_rutas() {
		$namespace = self::API_NAMESPACE;

		// Listar branches de un post
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'listar_branches' ),
				'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
				'args'                => array(
					'post_id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'status'  => array(
						'default'           => 'all',
						'validate_callback' => function ( $param ) {
							return in_array( $param, array( 'all', 'active', 'merged', 'archived' ), true );
						},
					),
				),
			)
		);

		// Crear branch
		register_rest_route(
			$namespace,
			'/branches',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'crear_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
				'args'                => array(
					'post_id'     => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'name'        => array(
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
					'description' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_textarea_field',
					),
					'from_branch' => array(
						'default'           => null,
						'validate_callback' => function ( $param ) {
							return is_null( $param ) || is_numeric( $param );
						},
					),
				),
			)
		);

		// Obtener branch específica con versión actual
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'obtener_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
			)
		);

		// Actualizar branch (nombre, descripción)
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)',
			array(
				'methods'             => 'PUT',
				'callback'            => array( $this, 'actualizar_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
			)
		);

		// Checkout (cambiar a una branch)
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/checkout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'checkout_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
			)
		);

		// Merge de branches
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/merge',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'merge_branches' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
				'args'                => array(
					'target_branch_id' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
					'conflict_resolutions' => array(
						'default' => array(),
					),
				),
			)
		);

		// Diff entre branches
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/diff',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'diff_branches' ),
				'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
				'args'                => array(
					'compare_with' => array(
						'required'          => true,
						'validate_callback' => function ( $param ) {
							return is_numeric( $param );
						},
					),
				),
			)
		);

		// Archivar branch
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => array( $this, 'archivar_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
			)
		);

		// Guardar versión en branch
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/save',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'guardar_en_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
				'args'                => array(
					'content' => array(
						'required' => true,
					),
					'message' => array(
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Historial de versiones de una branch
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/history',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'historial_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
			)
		);

		// Restaurar versión de branch
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/(?P<branch_id>\d+)/restore/(?P<version_id>\d+)',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'restaurar_version_branch' ),
				'permission_callback' => array( $this, 'verificar_permisos_escritura' ),
			)
		);

		// Obtener branch activa del usuario actual
		register_rest_route(
			$namespace,
			'/branches/(?P<post_id>\d+)/active',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'obtener_branch_activa' ),
				'permission_callback' => array( $this, 'verificar_permisos_lectura' ),
			)
		);
	}

	/**
	 * Verifica permisos de lectura
	 *
	 * @return bool
	 */
	public function verificar_permisos_lectura() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Verifica permisos de escritura
	 *
	 * @return bool
	 */
	public function verificar_permisos_escritura() {
		return current_user_can( 'edit_posts' );
	}

	/**
	 * Lista las branches de un post
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function listar_branches( $request ) {
		global $wpdb;

		$post_id = (int) $request->get_param( 'post_id' );
		$status  = $request->get_param( 'status' );

		// Asegurar que existe la branch main
		$this->asegurar_branch_main( $post_id );

		$where_status = '';
		if ( 'all' !== $status ) {
			$where_status = $wpdb->prepare( ' AND b.status = %s', $status );
		}

		$branches = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					b.*,
					u.display_name as created_by_name,
					parent.branch_name as parent_branch_name,
					(SELECT COUNT(*) FROM {$this->tabla_branch_versions} WHERE branch_id = b.id) as version_count,
					(SELECT MAX(created_at) FROM {$this->tabla_branch_versions} WHERE branch_id = b.id) as last_updated
				FROM {$this->tabla_branches} b
				LEFT JOIN {$wpdb->users} u ON b.created_by = u.ID
				LEFT JOIN {$this->tabla_branches} parent ON b.parent_branch_id = parent.id
				WHERE b.post_id = %d {$where_status}
				ORDER BY
					CASE WHEN b.branch_slug = 'main' THEN 0 ELSE 1 END,
					b.created_at DESC",
				$post_id
			),
			ARRAY_A
		);

		// Obtener branch activa para el usuario actual
		$usuario_actual   = get_current_user_id();
		$branch_activa_id = $this->obtener_id_branch_activa( $post_id, $usuario_actual );

		foreach ( $branches as &$branch ) {
			$branch['is_active']  = ( (int) $branch['id'] === $branch_activa_id );
			$branch['is_main']    = ( 'main' === $branch['branch_slug'] );
			$branch['can_delete'] = ( 'main' !== $branch['branch_slug'] && self::STATUS_ACTIVE === $branch['status'] );
		}

		return new WP_REST_Response(
			array(
				'success'  => true,
				'branches' => $branches,
				'active'   => $branch_activa_id,
			),
			200
		);
	}

	/**
	 * Asegura que existe la branch main para un post
	 *
	 * @param int $post_id ID del post.
	 * @return int ID de la branch main.
	 */
	private function asegurar_branch_main( $post_id ) {
		global $wpdb;

		$main_branch = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->tabla_branches} WHERE post_id = %d AND branch_slug = 'main'",
				$post_id
			)
		);

		if ( $main_branch ) {
			return (int) $main_branch;
		}

		// Crear branch main
		$usuario_actual = get_current_user_id();

		$wpdb->insert(
			$this->tabla_branches,
			array(
				'post_id'     => $post_id,
				'branch_name' => 'main',
				'branch_slug' => 'main',
				'created_by'  => $usuario_actual,
				'description' => __( 'Rama principal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'status'      => self::STATUS_ACTIVE,
			),
			array( '%d', '%s', '%s', '%d', '%s', '%s' )
		);

		$branch_id = $wpdb->insert_id;

		// Si hay contenido VBP existente, guardarlo como primera versión
		$contenido_actual = $this->obtener_contenido_actual( $post_id );
		if ( ! empty( $contenido_actual ) ) {
			$this->guardar_version_branch_interna( $branch_id, $contenido_actual, __( 'Versión inicial', FLAVOR_PLATFORM_TEXT_DOMAIN ) );
		}

		return $branch_id;
	}

	/**
	 * Crea una nueva branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function crear_branch( $request ) {
		global $wpdb;

		$post_id          = (int) $request->get_param( 'post_id' );
		$nombre_branch    = $request->get_param( 'name' );
		$descripcion      = $request->get_param( 'description' );
		$desde_branch_id  = $request->get_param( 'from_branch' );
		$usuario_actual   = get_current_user_id();

		// Generar slug único
		$branch_slug = sanitize_title( $nombre_branch );
		$slug_base   = $branch_slug;
		$contador    = 1;

		while ( $this->existe_branch_slug( $post_id, $branch_slug ) ) {
			$branch_slug = $slug_base . '-' . $contador;
			$contador++;
		}

		// Determinar branch padre
		$parent_branch_id = null;
		$version_origen   = null;

		if ( $desde_branch_id ) {
			$parent_branch_id = (int) $desde_branch_id;
		} else {
			// Usar la branch activa actual o main
			$branch_activa_id = $this->obtener_id_branch_activa( $post_id, $usuario_actual );
			if ( $branch_activa_id ) {
				$parent_branch_id = $branch_activa_id;
			} else {
				$parent_branch_id = $this->asegurar_branch_main( $post_id );
			}
		}

		// Obtener última versión de la branch padre
		$ultima_version_padre = $this->obtener_ultima_version_branch( $parent_branch_id );
		if ( $ultima_version_padre ) {
			$version_origen = $ultima_version_padre['id'];
		}

		// Crear la branch
		$insertado = $wpdb->insert(
			$this->tabla_branches,
			array(
				'post_id'                 => $post_id,
				'branch_name'             => $nombre_branch,
				'branch_slug'             => $branch_slug,
				'parent_branch_id'        => $parent_branch_id,
				'created_from_version_id' => $version_origen,
				'created_by'              => $usuario_actual,
				'description'             => $descripcion,
				'status'                  => self::STATUS_ACTIVE,
			),
			array( '%d', '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
		);

		if ( ! $insertado ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error al crear la rama', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				500
			);
		}

		$nueva_branch_id = $wpdb->insert_id;

		// Copiar contenido de la branch padre
		if ( $ultima_version_padre ) {
			$contenido_padre = json_decode( $ultima_version_padre['version_data'], true );
			$this->guardar_version_branch_interna(
				$nueva_branch_id,
				$contenido_padre,
				sprintf(
					/* translators: %s: nombre de la branch padre */
					__( 'Creada desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
					$this->obtener_nombre_branch( $parent_branch_id )
				)
			);
		}

		// Cambiar automáticamente a la nueva branch
		$this->establecer_branch_activa( $post_id, $nueva_branch_id, $usuario_actual );

		return new WP_REST_Response(
			array(
				'success'   => true,
				'message'   => __( 'Rama creada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'branch_id' => $nueva_branch_id,
				'branch'    => $this->obtener_datos_branch( $nueva_branch_id ),
			),
			201
		);
	}

	/**
	 * Verifica si existe un slug de branch para un post
	 *
	 * @param int    $post_id ID del post.
	 * @param string $slug    Slug a verificar.
	 * @return bool
	 */
	private function existe_branch_slug( $post_id, $slug ) {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->tabla_branches} WHERE post_id = %d AND branch_slug = %s",
				$post_id,
				$slug
			)
		);
	}

	/**
	 * Obtiene una branch específica con su versión actual
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function obtener_branch( $request ) {
		$post_id   = (int) $request->get_param( 'post_id' );
		$branch_id = (int) $request->get_param( 'branch_id' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		// Obtener versión actual
		$version_actual = $this->obtener_ultima_version_branch( $branch_id );
		if ( $version_actual ) {
			$branch['current_version'] = array(
				'id'         => $version_actual['id'],
				'created_at' => $version_actual['created_at'],
				'message'    => $version_actual['message'],
				'content'    => json_decode( $version_actual['version_data'], true ),
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'branch'  => $branch,
			),
			200
		);
	}

	/**
	 * Actualiza los datos de una branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function actualizar_branch( $request ) {
		global $wpdb;

		$post_id   = (int) $request->get_param( 'post_id' );
		$branch_id = (int) $request->get_param( 'branch_id' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		$datos_actualizar = array();
		$formatos         = array();

		if ( $request->has_param( 'name' ) ) {
			$datos_actualizar['branch_name'] = sanitize_text_field( $request->get_param( 'name' ) );
			$formatos[]                      = '%s';
		}

		if ( $request->has_param( 'description' ) ) {
			$datos_actualizar['description'] = sanitize_textarea_field( $request->get_param( 'description' ) );
			$formatos[]                      = '%s';
		}

		if ( empty( $datos_actualizar ) ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		$actualizado = $wpdb->update(
			$this->tabla_branches,
			$datos_actualizar,
			array( 'id' => $branch_id ),
			$formatos,
			array( '%d' )
		);

		if ( false === $actualizado ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error al actualizar la rama', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Rama actualizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'branch'  => $this->obtener_datos_branch( $branch_id ),
			),
			200
		);
	}

	/**
	 * Realiza checkout a una branch (cambia la branch activa)
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function checkout_branch( $request ) {
		$post_id        = (int) $request->get_param( 'post_id' );
		$branch_id      = (int) $request->get_param( 'branch_id' );
		$usuario_actual = get_current_user_id();

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		if ( self::STATUS_ACTIVE !== $branch['status'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No se puede cambiar a una rama archivada o fusionada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		// Guardar cambios actuales en la branch actual si hay cambios pendientes
		$branch_activa_actual = $this->obtener_id_branch_activa( $post_id, $usuario_actual );
		if ( $branch_activa_actual && $request->has_param( 'current_content' ) ) {
			$contenido_actual = $request->get_param( 'current_content' );
			if ( ! empty( $contenido_actual ) ) {
				$this->guardar_version_branch_interna(
					$branch_activa_actual,
					$contenido_actual,
					__( 'Guardado automático antes de cambiar de rama', FLAVOR_PLATFORM_TEXT_DOMAIN )
				);
			}
		}

		// Cambiar a la nueva branch
		$this->establecer_branch_activa( $post_id, $branch_id, $usuario_actual );

		// Obtener contenido de la nueva branch
		$version_actual = $this->obtener_ultima_version_branch( $branch_id );
		$contenido      = $version_actual ? json_decode( $version_actual['version_data'], true ) : array();

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => sprintf(
					/* translators: %s: nombre de la branch */
					__( 'Cambiado a la rama %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
					$branch['branch_name']
				),
				'branch'  => $branch,
				'content' => $contenido,
			),
			200
		);
	}

	/**
	 * Realiza merge de una branch a otra
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function merge_branches( $request ) {
		global $wpdb;

		$post_id              = (int) $request->get_param( 'post_id' );
		$source_branch_id     = (int) $request->get_param( 'branch_id' );
		$target_branch_id     = (int) $request->get_param( 'target_branch_id' );
		$conflict_resolutions = $request->get_param( 'conflict_resolutions' );
		$usuario_actual       = get_current_user_id();

		// Verificar que ambas branches existen
		$source_branch = $this->obtener_datos_branch( $source_branch_id );
		$target_branch = $this->obtener_datos_branch( $target_branch_id );

		if ( ! $source_branch || ! $target_branch ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Una de las ramas no existe', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		if ( (int) $source_branch['post_id'] !== $post_id || (int) $target_branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Las ramas no pertenecen al mismo post', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		// Obtener contenido de ambas branches
		$source_version = $this->obtener_ultima_version_branch( $source_branch_id );
		$target_version = $this->obtener_ultima_version_branch( $target_branch_id );

		if ( ! $source_version ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'La rama origen no tiene contenido', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		$source_content = json_decode( $source_version['version_data'], true );
		$target_content = $target_version ? json_decode( $target_version['version_data'], true ) : array();

		// Detectar conflictos
		$resultado_merge = $this->realizar_merge( $source_content, $target_content, $conflict_resolutions );

		if ( ! empty( $resultado_merge['conflicts'] ) && empty( $conflict_resolutions ) ) {
			// Hay conflictos sin resolver
			return new WP_REST_Response(
				array(
					'success'   => false,
					'message'   => __( 'Hay conflictos que necesitan resolución', FLAVOR_PLATFORM_TEXT_DOMAIN ),
					'conflicts' => $resultado_merge['conflicts'],
				),
				409
			);
		}

		// Guardar el resultado del merge en la branch destino
		$this->guardar_version_branch_interna(
			$target_branch_id,
			$resultado_merge['content'],
			sprintf(
				/* translators: %s: nombre de la branch origen */
				__( 'Merge desde %s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				$source_branch['branch_name']
			)
		);

		// Marcar la branch origen como fusionada
		$wpdb->update(
			$this->tabla_branches,
			array(
				'status'               => self::STATUS_MERGED,
				'merged_into_branch_id' => $target_branch_id,
				'merged_at'            => current_time( 'mysql' ),
				'merged_by'            => $usuario_actual,
			),
			array( 'id' => $source_branch_id ),
			array( '%s', '%d', '%s', '%d' ),
			array( '%d' )
		);

		return new WP_REST_Response(
			array(
				'success'        => true,
				'message'        => sprintf(
					/* translators: %1$s: nombre de la branch origen, %2$s: nombre de la branch destino */
					__( 'Rama %1$s fusionada en %2$s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
					$source_branch['branch_name'],
					$target_branch['branch_name']
				),
				'merged_content' => $resultado_merge['content'],
			),
			200
		);
	}

	/**
	 * Realiza el merge de dos contenidos
	 *
	 * @param array $source_content       Contenido origen.
	 * @param array $target_content       Contenido destino.
	 * @param array $conflict_resolutions Resoluciones de conflictos.
	 * @return array
	 */
	private function realizar_merge( $source_content, $target_content, $conflict_resolutions = array() ) {
		$resultado = array(
			'content'   => array(),
			'conflicts' => array(),
		);

		// Crear mapas de elementos por ID
		$source_elements = $this->crear_mapa_elementos( $source_content );
		$target_elements = $this->crear_mapa_elementos( $target_content );

		$elementos_resultado = array();
		$conflictos          = array();

		// Procesar todos los elementos
		$todos_los_ids = array_unique( array_merge( array_keys( $source_elements ), array_keys( $target_elements ) ) );

		foreach ( $todos_los_ids as $element_id ) {
			$source_element = isset( $source_elements[ $element_id ] ) ? $source_elements[ $element_id ] : null;
			$target_element = isset( $target_elements[ $element_id ] ) ? $target_elements[ $element_id ] : null;

			// Si solo existe en source, añadir
			if ( $source_element && ! $target_element ) {
				$elementos_resultado[ $element_id ] = $source_element;
				continue;
			}

			// Si solo existe en target, mantener
			if ( ! $source_element && $target_element ) {
				$elementos_resultado[ $element_id ] = $target_element;
				continue;
			}

			// Si existe en ambos, verificar si hay conflicto
			if ( $source_element && $target_element ) {
				$cambios = $this->detectar_cambios_elemento( $source_element, $target_element );

				if ( empty( $cambios ) ) {
					// Sin cambios, usar cualquiera
					$elementos_resultado[ $element_id ] = $target_element;
				} else {
					// Verificar si hay resolución para este conflicto
					if ( isset( $conflict_resolutions[ $element_id ] ) ) {
						$resolution = $conflict_resolutions[ $element_id ];
						if ( 'source' === $resolution ) {
							$elementos_resultado[ $element_id ] = $source_element;
						} elseif ( 'target' === $resolution ) {
							$elementos_resultado[ $element_id ] = $target_element;
						} elseif ( is_array( $resolution ) ) {
							// Resolución personalizada
							$elementos_resultado[ $element_id ] = $resolution;
						}
					} else {
						// Registrar conflicto
						$conflictos[] = array(
							'element_id'    => $element_id,
							'conflict_type' => 'property_change',
							'source_value'  => $source_element,
							'target_value'  => $target_element,
							'changes'       => $cambios,
						);
						// Usar temporalmente target
						$elementos_resultado[ $element_id ] = $target_element;
					}
				}
			}
		}

		// Reconstruir estructura de contenido
		$resultado['content']   = $this->reconstruir_estructura( $source_content, $elementos_resultado );
		$resultado['conflicts'] = $conflictos;

		return $resultado;
	}

	/**
	 * Crea un mapa plano de elementos por ID
	 *
	 * @param array $content Contenido.
	 * @return array
	 */
	private function crear_mapa_elementos( $content ) {
		$mapa = array();

		if ( ! is_array( $content ) ) {
			return $mapa;
		}

		$elementos = isset( $content['elements'] ) ? $content['elements'] : $content;

		if ( ! is_array( $elementos ) ) {
			return $mapa;
		}

		$this->mapear_elementos_recursivo( $elementos, $mapa );

		return $mapa;
	}

	/**
	 * Mapea elementos recursivamente
	 *
	 * @param array $elementos Elementos.
	 * @param array $mapa      Mapa de referencia.
	 */
	private function mapear_elementos_recursivo( $elementos, &$mapa ) {
		foreach ( $elementos as $elemento ) {
			if ( isset( $elemento['id'] ) ) {
				$mapa[ $elemento['id'] ] = $elemento;
			}

			if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
				$this->mapear_elementos_recursivo( $elemento['children'], $mapa );
			}
		}
	}

	/**
	 * Detecta cambios entre dos elementos
	 *
	 * @param array $elemento_a Elemento A.
	 * @param array $elemento_b Elemento B.
	 * @return array
	 */
	private function detectar_cambios_elemento( $elemento_a, $elemento_b ) {
		$cambios = array();

		$props_a = $elemento_a;
		$props_b = $elemento_b;
		unset( $props_a['children'], $props_b['children'] );

		$todas_keys = array_unique( array_merge( array_keys( $props_a ), array_keys( $props_b ) ) );

		foreach ( $todas_keys as $key ) {
			$valor_a = isset( $props_a[ $key ] ) ? $props_a[ $key ] : null;
			$valor_b = isset( $props_b[ $key ] ) ? $props_b[ $key ] : null;

			if ( $valor_a !== $valor_b ) {
				$cambios[] = array(
					'property'  => $key,
					'old_value' => $valor_a,
					'new_value' => $valor_b,
				);
			}
		}

		return $cambios;
	}

	/**
	 * Reconstruye la estructura del contenido con los elementos actualizados
	 *
	 * @param array $estructura_original   Estructura original.
	 * @param array $elementos_actualizados Elementos actualizados.
	 * @return array
	 */
	private function reconstruir_estructura( $estructura_original, $elementos_actualizados ) {
		if ( ! is_array( $estructura_original ) ) {
			return $estructura_original;
		}

		$resultado = $estructura_original;

		if ( isset( $resultado['elements'] ) && is_array( $resultado['elements'] ) ) {
			$resultado['elements'] = $this->reconstruir_elementos( $resultado['elements'], $elementos_actualizados );
		} elseif ( is_array( $resultado ) ) {
			$resultado = $this->reconstruir_elementos( $resultado, $elementos_actualizados );
		}

		return $resultado;
	}

	/**
	 * Reconstruye elementos recursivamente
	 *
	 * @param array $elementos              Elementos originales.
	 * @param array $elementos_actualizados Mapa de elementos actualizados.
	 * @return array
	 */
	private function reconstruir_elementos( $elementos, $elementos_actualizados ) {
		$resultado = array();

		foreach ( $elementos as $elemento ) {
			if ( isset( $elemento['id'] ) && isset( $elementos_actualizados[ $elemento['id'] ] ) ) {
				$elemento_actualizado = $elementos_actualizados[ $elemento['id'] ];

				if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
					$elemento_actualizado['children'] = $this->reconstruir_elementos(
						$elemento['children'],
						$elementos_actualizados
					);
				}

				$resultado[] = $elemento_actualizado;
			} else {
				$resultado[] = $elemento;
			}
		}

		return $resultado;
	}

	/**
	 * Genera diff entre dos branches
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function diff_branches( $request ) {
		$post_id         = (int) $request->get_param( 'post_id' );
		$branch_id       = (int) $request->get_param( 'branch_id' );
		$compare_with_id = (int) $request->get_param( 'compare_with' );

		$branch_a = $this->obtener_datos_branch( $branch_id );
		$branch_b = $this->obtener_datos_branch( $compare_with_id );

		if ( ! $branch_a || ! $branch_b ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Una de las ramas no existe', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		$version_a = $this->obtener_ultima_version_branch( $branch_id );
		$version_b = $this->obtener_ultima_version_branch( $compare_with_id );

		$content_a = $version_a ? json_decode( $version_a['version_data'], true ) : array();
		$content_b = $version_b ? json_decode( $version_b['version_data'], true ) : array();

		$diff = $this->generar_diff_detallado( $content_a, $content_b );

		return new WP_REST_Response(
			array(
				'success'     => true,
				'branch_a'    => array(
					'id'      => $branch_id,
					'name'    => $branch_a['branch_name'],
					'content' => $content_a,
				),
				'branch_b'    => array(
					'id'      => $compare_with_id,
					'name'    => $branch_b['branch_name'],
					'content' => $content_b,
				),
				'diff'        => $diff,
				'stats'       => array(
					'added'    => count( array_filter( $diff['changes'], function ( $change ) {
						return 'added' === $change['type'];
					} ) ),
					'removed'  => count( array_filter( $diff['changes'], function ( $change ) {
						return 'removed' === $change['type'];
					} ) ),
					'modified' => count( array_filter( $diff['changes'], function ( $change ) {
						return 'modified' === $change['type'];
					} ) ),
				),
			),
			200
		);
	}

	/**
	 * Genera diff detallado entre dos contenidos
	 *
	 * @param array $content_a Contenido A.
	 * @param array $content_b Contenido B.
	 * @return array
	 */
	private function generar_diff_detallado( $content_a, $content_b ) {
		$diff = array(
			'changes' => array(),
		);

		$elements_a = $this->crear_mapa_elementos( $content_a );
		$elements_b = $this->crear_mapa_elementos( $content_b );

		// Elementos añadidos (en B pero no en A)
		foreach ( $elements_b as $id => $elemento ) {
			if ( ! isset( $elements_a[ $id ] ) ) {
				$diff['changes'][] = array(
					'type'    => 'added',
					'id'      => $id,
					'element' => $elemento,
					'path'    => $this->obtener_path_elemento( $id, $content_b ),
				);
			}
		}

		// Elementos eliminados (en A pero no en B)
		foreach ( $elements_a as $id => $elemento ) {
			if ( ! isset( $elements_b[ $id ] ) ) {
				$diff['changes'][] = array(
					'type'    => 'removed',
					'id'      => $id,
					'element' => $elemento,
					'path'    => $this->obtener_path_elemento( $id, $content_a ),
				);
			}
		}

		// Elementos modificados
		foreach ( $elements_a as $id => $elemento_a ) {
			if ( isset( $elements_b[ $id ] ) ) {
				$elemento_b = $elements_b[ $id ];
				$cambios    = $this->detectar_cambios_elemento( $elemento_a, $elemento_b );

				if ( ! empty( $cambios ) ) {
					$diff['changes'][] = array(
						'type'       => 'modified',
						'id'         => $id,
						'element_a'  => $elemento_a,
						'element_b'  => $elemento_b,
						'changes'    => $cambios,
						'path'       => $this->obtener_path_elemento( $id, $content_b ),
					);
				}
			}
		}

		return $diff;
	}

	/**
	 * Obtiene el path de un elemento en la estructura
	 *
	 * @param string $element_id ID del elemento.
	 * @param array  $content    Contenido.
	 * @return array
	 */
	private function obtener_path_elemento( $element_id, $content ) {
		if ( ! is_array( $content ) ) {
			return array();
		}

		$elementos = isset( $content['elements'] ) ? $content['elements'] : $content;

		return $this->buscar_path_recursivo( $element_id, $elementos, array() );
	}

	/**
	 * Busca el path de un elemento recursivamente
	 *
	 * @param string $element_id ID del elemento.
	 * @param array  $elementos  Elementos.
	 * @param array  $path       Path actual.
	 * @return array
	 */
	private function buscar_path_recursivo( $element_id, $elementos, $path ) {
		if ( ! is_array( $elementos ) ) {
			return array();
		}

		foreach ( $elementos as $index => $elemento ) {
			if ( isset( $elemento['id'] ) && $elemento['id'] === $element_id ) {
				$path[] = array(
					'index' => $index,
					'type'  => isset( $elemento['type'] ) ? $elemento['type'] : 'unknown',
					'id'    => $element_id,
				);
				return $path;
			}

			if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
				$nuevo_path   = $path;
				$nuevo_path[] = array(
					'index' => $index,
					'type'  => isset( $elemento['type'] ) ? $elemento['type'] : 'container',
					'id'    => isset( $elemento['id'] ) ? $elemento['id'] : '',
				);

				$resultado = $this->buscar_path_recursivo( $element_id, $elemento['children'], $nuevo_path );
				if ( ! empty( $resultado ) ) {
					return $resultado;
				}
			}
		}

		return array();
	}

	/**
	 * Archiva una branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function archivar_branch( $request ) {
		global $wpdb;

		$post_id   = (int) $request->get_param( 'post_id' );
		$branch_id = (int) $request->get_param( 'branch_id' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		if ( 'main' === $branch['branch_slug'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No se puede archivar la rama principal', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		$actualizado = $wpdb->update(
			$this->tabla_branches,
			array( 'status' => self::STATUS_ARCHIVED ),
			array( 'id' => $branch_id ),
			array( '%s' ),
			array( '%d' )
		);

		if ( false === $actualizado ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error al archivar la rama', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				500
			);
		}

		// Si esta era la branch activa, cambiar a main
		$usuario_actual   = get_current_user_id();
		$branch_activa_id = $this->obtener_id_branch_activa( $post_id, $usuario_actual );

		if ( $branch_activa_id === $branch_id ) {
			$main_branch_id = $this->asegurar_branch_main( $post_id );
			$this->establecer_branch_activa( $post_id, $main_branch_id, $usuario_actual );
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Rama archivada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
			),
			200
		);
	}

	/**
	 * Guarda contenido en una branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function guardar_en_branch( $request ) {
		$post_id   = (int) $request->get_param( 'post_id' );
		$branch_id = (int) $request->get_param( 'branch_id' );
		$contenido = $request->get_param( 'content' );
		$mensaje   = $request->get_param( 'message' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		if ( self::STATUS_ACTIVE !== $branch['status'] ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'No se puede guardar en una rama archivada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				400
			);
		}

		$version_id = $this->guardar_version_branch_interna( $branch_id, $contenido, $mensaje );

		if ( ! $version_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Error al guardar en la rama', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				500
			);
		}

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'version_id' => $version_id,
			),
			200
		);
	}

	/**
	 * Obtiene el historial de versiones de una branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function historial_branch( $request ) {
		global $wpdb;

		$post_id   = (int) $request->get_param( 'post_id' );
		$branch_id = (int) $request->get_param( 'branch_id' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		$versiones = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					v.id,
					v.created_at,
					v.message,
					v.version_hash,
					u.display_name as created_by_name
				FROM {$this->tabla_branch_versions} v
				LEFT JOIN {$wpdb->users} u ON v.created_by = u.ID
				WHERE v.branch_id = %d
				ORDER BY v.created_at DESC
				LIMIT 50",
				$branch_id
			),
			ARRAY_A
		);

		foreach ( $versiones as &$version ) {
			$version['relative_time'] = human_time_diff(
				strtotime( $version['created_at'] ),
				current_time( 'timestamp' )
			) . ' ' . __( 'atrás', FLAVOR_PLATFORM_TEXT_DOMAIN );
		}

		return new WP_REST_Response(
			array(
				'success'   => true,
				'branch'    => $branch,
				'versions'  => $versiones,
			),
			200
		);
	}

	/**
	 * Restaura una versión específica de una branch
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function restaurar_version_branch( $request ) {
		global $wpdb;

		$post_id    = (int) $request->get_param( 'post_id' );
		$branch_id  = (int) $request->get_param( 'branch_id' );
		$version_id = (int) $request->get_param( 'version_id' );

		$branch = $this->obtener_datos_branch( $branch_id );

		if ( ! $branch || (int) $branch['post_id'] !== $post_id ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Rama no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		// Obtener la versión
		$version = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->tabla_branch_versions} WHERE id = %d AND branch_id = %d",
				$version_id,
				$branch_id
			),
			ARRAY_A
		);

		if ( ! $version ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Versión no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				),
				404
			);
		}

		$contenido = json_decode( $version['version_data'], true );

		// Guardar el estado actual antes de restaurar
		$version_actual = $this->obtener_ultima_version_branch( $branch_id );
		if ( $version_actual && $version_actual['id'] !== $version_id ) {
			$this->guardar_version_branch_interna(
				$branch_id,
				json_decode( $version_actual['version_data'], true ),
				__( 'Antes de restaurar', FLAVOR_PLATFORM_TEXT_DOMAIN )
			);
		}

		// Guardar la versión restaurada como nueva versión
		$nuevo_version_id = $this->guardar_version_branch_interna(
			$branch_id,
			$contenido,
			sprintf(
				/* translators: %s: ID de la versión restaurada */
				__( 'Restaurado desde versión #%s', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				$version_id
			)
		);

		return new WP_REST_Response(
			array(
				'success'    => true,
				'message'    => __( 'Versión restaurada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
				'content'    => $contenido,
				'version_id' => $nuevo_version_id,
			),
			200
		);
	}

	/**
	 * Obtiene la branch activa para el usuario actual
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function obtener_branch_activa( $request ) {
		$post_id        = (int) $request->get_param( 'post_id' );
		$usuario_actual = get_current_user_id();

		// Asegurar que existe main
		$this->asegurar_branch_main( $post_id );

		$branch_id = $this->obtener_id_branch_activa( $post_id, $usuario_actual );

		if ( ! $branch_id ) {
			// Usar main por defecto
			$branch_id = $this->obtener_id_branch_main( $post_id );
			$this->establecer_branch_activa( $post_id, $branch_id, $usuario_actual );
		}

		$branch = $this->obtener_datos_branch( $branch_id );

		// Obtener contenido actual
		$version = $this->obtener_ultima_version_branch( $branch_id );
		$contenido = $version ? json_decode( $version['version_data'], true ) : array();

		return new WP_REST_Response(
			array(
				'success' => true,
				'branch'  => $branch,
				'content' => $contenido,
			),
			200
		);
	}

	/**
	 * Guarda versión automática al guardar desde el editor
	 *
	 * @param int   $post_id  ID del post.
	 * @param array $contenido Contenido guardado.
	 */
	public function guardar_version_en_branch( $post_id, $contenido ) {
		$usuario_actual = get_current_user_id();
		$branch_id      = $this->obtener_id_branch_activa( $post_id, $usuario_actual );

		if ( ! $branch_id ) {
			$branch_id = $this->asegurar_branch_main( $post_id );
			$this->establecer_branch_activa( $post_id, $branch_id, $usuario_actual );
		}

		$this->guardar_version_branch_interna( $branch_id, $contenido, '' );
	}

	/**
	 * Guarda una versión internamente en una branch
	 *
	 * @param int    $branch_id ID de la branch.
	 * @param array  $contenido Contenido.
	 * @param string $mensaje   Mensaje de la versión.
	 * @return int|false
	 */
	private function guardar_version_branch_interna( $branch_id, $contenido, $mensaje = '' ) {
		global $wpdb;

		$contenido_json = wp_json_encode( $contenido );
		$version_hash   = hash( 'sha256', $contenido_json );

		// Verificar si ya existe esta versión exacta
		$existe = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->tabla_branch_versions}
				WHERE branch_id = %d AND version_hash = %s
				ORDER BY id DESC LIMIT 1",
				$branch_id,
				$version_hash
			)
		);

		if ( $existe ) {
			return (int) $existe;
		}

		$insertado = $wpdb->insert(
			$this->tabla_branch_versions,
			array(
				'branch_id'    => $branch_id,
				'version_data' => $contenido_json,
				'version_hash' => $version_hash,
				'created_by'   => get_current_user_id(),
				'message'      => $mensaje,
			),
			array( '%d', '%s', '%s', '%d', '%s' )
		);

		if ( $insertado ) {
			// Limpiar versiones antiguas (mantener últimas 30)
			$this->limpiar_versiones_antiguas_branch( $branch_id );
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Limpia versiones antiguas de una branch
	 *
	 * @param int $branch_id ID de la branch.
	 */
	private function limpiar_versiones_antiguas_branch( $branch_id ) {
		global $wpdb;

		$max_versiones = 30;

		$versiones_mantener = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT id FROM {$this->tabla_branch_versions}
				WHERE branch_id = %d
				ORDER BY id DESC
				LIMIT %d",
				$branch_id,
				$max_versiones
			)
		);

		if ( empty( $versiones_mantener ) ) {
			return;
		}

		$ids_placeholder = implode( ',', array_fill( 0, count( $versiones_mantener ), '%d' ) );

		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->tabla_branch_versions}
				WHERE branch_id = %d AND id NOT IN ($ids_placeholder)",
				array_merge( array( $branch_id ), $versiones_mantener )
			)
		);
	}

	/**
	 * Obtiene los datos de una branch
	 *
	 * @param int $branch_id ID de la branch.
	 * @return array|null
	 */
	private function obtener_datos_branch( $branch_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT b.*, u.display_name as created_by_name
				FROM {$this->tabla_branches} b
				LEFT JOIN {$wpdb->users} u ON b.created_by = u.ID
				WHERE b.id = %d",
				$branch_id
			),
			ARRAY_A
		);
	}

	/**
	 * Obtiene el nombre de una branch
	 *
	 * @param int $branch_id ID de la branch.
	 * @return string
	 */
	private function obtener_nombre_branch( $branch_id ) {
		global $wpdb;

		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT branch_name FROM {$this->tabla_branches} WHERE id = %d",
				$branch_id
			)
		) ?: '';
	}

	/**
	 * Obtiene la última versión de una branch
	 *
	 * @param int $branch_id ID de la branch.
	 * @return array|null
	 */
	private function obtener_ultima_version_branch( $branch_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->tabla_branch_versions}
				WHERE branch_id = %d
				ORDER BY id DESC
				LIMIT 1",
				$branch_id
			),
			ARRAY_A
		);
	}

	/**
	 * Obtiene el ID de la branch activa para un usuario
	 *
	 * @param int $post_id    ID del post.
	 * @param int $usuario_id ID del usuario.
	 * @return int|null
	 */
	private function obtener_id_branch_activa( $post_id, $usuario_id ) {
		$meta_key      = self::META_ACTIVE_BRANCH . '_' . $usuario_id;
		$branch_id     = get_post_meta( $post_id, $meta_key, true );

		if ( $branch_id ) {
			// Verificar que la branch existe y está activa
			$branch = $this->obtener_datos_branch( (int) $branch_id );
			if ( $branch && self::STATUS_ACTIVE === $branch['status'] ) {
				return (int) $branch_id;
			}
		}

		return null;
	}

	/**
	 * Establece la branch activa para un usuario
	 *
	 * @param int $post_id    ID del post.
	 * @param int $branch_id  ID de la branch.
	 * @param int $usuario_id ID del usuario.
	 */
	private function establecer_branch_activa( $post_id, $branch_id, $usuario_id ) {
		$meta_key = self::META_ACTIVE_BRANCH . '_' . $usuario_id;
		update_post_meta( $post_id, $meta_key, $branch_id );
	}

	/**
	 * Obtiene el ID de la branch main
	 *
	 * @param int $post_id ID del post.
	 * @return int|null
	 */
	private function obtener_id_branch_main( $post_id ) {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$this->tabla_branches} WHERE post_id = %d AND branch_slug = 'main'",
				$post_id
			)
		);
	}

	/**
	 * Obtiene el contenido actual del documento VBP
	 *
	 * @param int $post_id ID del post.
	 * @return array|null
	 */
	private function obtener_contenido_actual( $post_id ) {
		if ( class_exists( 'Flavor_VBP_Editor' ) ) {
			return Flavor_VBP_Editor::get_instance()->obtener_datos_documento( $post_id );
		}

		$contenido = get_post_meta( $post_id, self::META_DATA, true );
		return is_array( $contenido ) ? $contenido : null;
	}
}
