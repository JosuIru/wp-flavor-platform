<?php
/**
 * Jerarquía de Módulos
 *
 * Define las categorías y relaciones entre módulos.
 * Usado por el menú de navegación, Settings Hub y otras partes del sistema.
 *
 * @package FlavorPlatform
 * @since 3.5.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase que gestiona la jerarquía de módulos
 */
class Flavor_Module_Hierarchy {

	/**
	 * Instancia singleton
	 *
	 * @var Flavor_Module_Hierarchy|null
	 */
	private static $instance = null;

	/**
	 * Categorías de módulos
	 *
	 * @var array
	 */
	private $categories = [];

	/**
	 * Mapeo de módulo a categoría (cache)
	 *
	 * @var array
	 */
	private $module_to_category = [];

	/**
	 * Obtener instancia singleton
	 *
	 * @return Flavor_Module_Hierarchy
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_categories();
		$this->build_module_map();
	}

	/**
	 * Inicializar categorías y sus módulos
	 */
	private function init_categories() {
		$this->categories = [
			// =================================================================
			// COMUNIDAD - Personas, grupos y relaciones sociales
			// =================================================================
			'comunidad'     => [
				'title'       => __( 'Comunidad', 'flavor-platform' ),
				'description' => __( 'Gestión de personas, grupos y relaciones sociales', 'flavor-platform' ),
				'icon'        => 'dashicons-groups',
				'color'       => '#ec4899',
				'modules'     => [
					// Core
					'socios'      => [ 'priority' => 10, 'core' => true ],
					'comunidades' => [ 'priority' => 20 ],
					'colectivos'  => [ 'priority' => 30 ],
					// Interacción
					'foros'       => [ 'priority' => 40 ],
					'red_social'  => [ 'priority' => 50 ],
					// Chat
					'chat_grupos'  => [ 'priority' => 60 ],
					'chat_interno' => [ 'priority' => 70 ],
					'chat_estados' => [ 'priority' => 80 ],
				],
			],

			// =================================================================
			// ECONOMÍA - Intercambio, comercio y finanzas
			// =================================================================
			'economia'      => [
				'title'       => __( 'Economía', 'flavor-platform' ),
				'description' => __( 'Comercio, intercambio y gestión económica', 'flavor-platform' ),
				'icon'        => 'dashicons-money-alt',
				'color'       => '#22c55e',
				'modules'     => [
					// Economía social
					'grupos_consumo' => [ 'priority' => 10, 'core' => true ],
					'marketplace'    => [ 'priority' => 20 ],
					'banco_tiempo'   => [ 'priority' => 30 ],
					'economia_don'   => [ 'priority' => 40 ],
					// Empresarial
					'empresas'       => [ 'priority' => 50 ],
					'clientes'       => [ 'priority' => 60, 'parent' => 'empresas' ],
					'facturas'       => [ 'priority' => 70, 'parent' => 'empresas' ],
					'contabilidad'   => [ 'priority' => 80, 'parent' => 'empresas' ],
					// Integraciones
					'woocommerce'    => [ 'priority' => 90 ],
					// Avanzado
					'trading_ia'     => [ 'priority' => 100 ],
					'dex_solana'     => [ 'priority' => 110 ],
					'themacle'       => [ 'priority' => 120 ],
				],
			],

			// =================================================================
			// ACTIVIDADES - Eventos, formación y reservas
			// =================================================================
			'actividades'   => [
				'title'       => __( 'Actividades', 'flavor-platform' ),
				'description' => __( 'Eventos, formación y gestión de tiempo', 'flavor-platform' ),
				'icon'        => 'dashicons-calendar-alt',
				'color'       => '#f97316',
				'modules'     => [
					'eventos'           => [ 'priority' => 10, 'core' => true ],
					'cursos'            => [ 'priority' => 20 ],
					'talleres'          => [ 'priority' => 30 ],
					'reservas'          => [ 'priority' => 40 ],
					'fichaje_empleados' => [ 'priority' => 50 ],
				],
			],

			// =================================================================
			// SERVICIOS - Gestión ciudadana y recursos compartidos
			// =================================================================
			'servicios'     => [
				'title'       => __( 'Servicios', 'flavor-platform' ),
				'description' => __( 'Trámites, participación y recursos compartidos', 'flavor-platform' ),
				'icon'        => 'dashicons-admin-tools',
				'color'       => '#14b8a6',
				'modules'     => [
					// Gestión ciudadana
					'tramites'      => [ 'priority' => 10 ],
					'incidencias'   => [ 'priority' => 20, 'core' => true ],
					'ayuda_vecinal' => [ 'priority' => 30 ],
					'participacion' => [ 'priority' => 40 ],
					'presupuestos_participativos' => [ 'priority' => 50, 'parent' => 'participacion' ],
					'transparencia' => [ 'priority' => 60 ],
					// Comunicación oficial
					'avisos_municipales' => [ 'priority' => 70 ],
					// Denuncias
					'seguimiento_denuncias' => [ 'priority' => 80 ],
					'justicia_restaurativa' => [ 'priority' => 90 ],
					// Documentación
					'documentacion_legal' => [ 'priority' => 100 ],
				],
			],

			// =================================================================
			// RECURSOS - Espacios y bienes compartidos
			// =================================================================
			'recursos'      => [
				'title'       => __( 'Recursos', 'flavor-platform' ),
				'description' => __( 'Espacios, equipamiento y bienes compartidos', 'flavor-platform' ),
				'icon'        => 'dashicons-building',
				'color'       => '#8b5cf6',
				'modules'     => [
					'espacios_comunes' => [ 'priority' => 10, 'core' => true ],
					'biblioteca'       => [ 'priority' => 20 ],
					'huertos_urbanos'  => [ 'priority' => 30 ],
					// Movilidad
					'carpooling'            => [ 'priority' => 40 ],
					'bicicletas_compartidas' => [ 'priority' => 50 ],
					'parkings'              => [ 'priority' => 60 ],
					// Locales
					'bares'   => [ 'priority' => 70 ],
					'recetas' => [ 'priority' => 80 ],
				],
			],

			// =================================================================
			// SOSTENIBILIDAD - Ecología y cuidados
			// =================================================================
			'sostenibilidad' => [
				'title'       => __( 'Sostenibilidad', 'flavor-platform' ),
				'description' => __( 'Ecología, medio ambiente y cuidados', 'flavor-platform' ),
				'icon'        => 'dashicons-palmtree',
				'color'       => '#059669',
				'modules'     => [
					// Ecología
					'reciclaje'           => [ 'priority' => 10 ],
					'compostaje'          => [ 'priority' => 20 ],
					'energia_comunitaria' => [ 'priority' => 30, 'core' => true ],
					'biodiversidad_local' => [ 'priority' => 40 ],
					'huella_ecologica'    => [ 'priority' => 50 ],
					// Filosofía
					'economia_suficiencia' => [ 'priority' => 60 ],
					'saberes_ancestrales'  => [ 'priority' => 70 ],
					// Cuidados
					'circulos_cuidados' => [ 'priority' => 80 ],
					'trabajo_digno'     => [ 'priority' => 90 ],
				],
			],

			// =================================================================
			// COMUNICACIÓN - Medios y difusión
			// =================================================================
			'comunicacion'  => [
				'title'       => __( 'Comunicación', 'flavor-platform' ),
				'description' => __( 'Medios, marketing y difusión', 'flavor-platform' ),
				'icon'        => 'dashicons-megaphone',
				'color'       => '#0ea5e9',
				'modules'     => [
					// Medios
					'multimedia' => [ 'priority' => 10, 'core' => true ],
					'radio'      => [ 'priority' => 20 ],
					'podcast'    => [ 'priority' => 30 ],
					// Marketing
					'campanias'       => [ 'priority' => 40 ],
					'email_marketing' => [ 'priority' => 50 ],
					'encuestas'       => [ 'priority' => 60 ],
					// Contenido
					'agregador_contenido' => [ 'priority' => 70 ],
					// Publicidad
					'advertising' => [ 'priority' => 80 ],
				],
			],

			// =================================================================
			// MAPEO - Geolocalización y actores
			// =================================================================
			'mapeo'         => [
				'title'       => __( 'Mapeo', 'flavor-platform' ),
				'description' => __( 'Geolocalización y mapa de actores', 'flavor-platform' ),
				'icon'        => 'dashicons-location-alt',
				'color'       => '#6366f1',
				'modules'     => [
					'mapa_actores' => [ 'priority' => 10, 'core' => true ],
				],
			],

			// =================================================================
			// CERTIFICACIÓN - Sellos y validaciones
			// =================================================================
			'certificacion' => [
				'title'       => __( 'Certificación', 'flavor-platform' ),
				'description' => __( 'Sellos de calidad y validaciones', 'flavor-platform' ),
				'icon'        => 'dashicons-awards',
				'color'       => '#eab308',
				'modules'     => [
					'sello_conciencia' => [ 'priority' => 10, 'core' => true ],
				],
			],

			// =================================================================
			// DESARROLLO - Herramientas técnicas
			// =================================================================
			'desarrollo'    => [
				'title'       => __( 'Desarrollo', 'flavor-platform' ),
				'description' => __( 'Herramientas para desarrolladores', 'flavor-platform' ),
				'icon'        => 'dashicons-editor-code',
				'color'       => '#64748b',
				'modules'     => [
					'bug_tracker' => [ 'priority' => 10, 'core' => true ],
					'empresarial' => [ 'priority' => 20 ],
				],
			],
		];

		// Permitir extensión via filtro
		$this->categories = apply_filters( 'flavor_module_hierarchy_categories', $this->categories );
	}

	/**
	 * Construir mapeo inverso de módulo a categoría
	 */
	private function build_module_map() {
		foreach ( $this->categories as $category_id => $category ) {
			if ( empty( $category['modules'] ) ) {
				continue;
			}

			foreach ( $category['modules'] as $module_id => $module_config ) {
				$this->module_to_category[ $module_id ] = [
					'category' => $category_id,
					'config'   => $module_config,
				];
			}
		}
	}

	/**
	 * Obtener todas las categorías
	 *
	 * @return array
	 */
	public function get_categories() {
		return $this->categories;
	}

	/**
	 * Obtener una categoría específica
	 *
	 * @param string $category_id ID de la categoría.
	 * @return array|null
	 */
	public function get_category( $category_id ) {
		return $this->categories[ $category_id ] ?? null;
	}

	/**
	 * Obtener la categoría de un módulo
	 *
	 * @param string $module_id ID del módulo.
	 * @return string|null ID de la categoría o null si no está categorizado.
	 */
	public function get_module_category( $module_id ) {
		// Normalizar ID (algunos usan guiones, otros guiones bajos)
		$normalized_id = str_replace( '-', '_', $module_id );

		if ( isset( $this->module_to_category[ $normalized_id ] ) ) {
			return $this->module_to_category[ $normalized_id ]['category'];
		}

		if ( isset( $this->module_to_category[ $module_id ] ) ) {
			return $this->module_to_category[ $module_id ]['category'];
		}

		return null;
	}

	/**
	 * Obtener el módulo padre de un módulo
	 *
	 * @param string $module_id ID del módulo.
	 * @return string|null ID del módulo padre o null si no tiene.
	 */
	public function get_module_parent( $module_id ) {
		$normalized_id = str_replace( '-', '_', $module_id );
		$config = $this->module_to_category[ $normalized_id ]['config'] ?? null;

		if ( $config && isset( $config['parent'] ) ) {
			return $config['parent'];
		}

		return null;
	}

	/**
	 * Obtener los módulos hijos de un módulo
	 *
	 * @param string $parent_id ID del módulo padre.
	 * @return array Array de IDs de módulos hijos.
	 */
	public function get_module_children( $parent_id ) {
		$children = [];
		$normalized_parent = str_replace( '-', '_', $parent_id );

		foreach ( $this->module_to_category as $module_id => $info ) {
			if ( isset( $info['config']['parent'] ) && $info['config']['parent'] === $normalized_parent ) {
				$children[] = $module_id;
			}
		}

		return $children;
	}

	/**
	 * Verificar si un módulo es "core" (principal) de su categoría
	 *
	 * @param string $module_id ID del módulo.
	 * @return bool
	 */
	public function is_core_module( $module_id ) {
		$normalized_id = str_replace( '-', '_', $module_id );
		$config = $this->module_to_category[ $normalized_id ]['config'] ?? null;

		return $config && ! empty( $config['core'] );
	}

	/**
	 * Obtener la prioridad de un módulo dentro de su categoría
	 *
	 * @param string $module_id ID del módulo.
	 * @return int Prioridad (menor = más importante). 999 si no está definida.
	 */
	public function get_module_priority( $module_id ) {
		$normalized_id = str_replace( '-', '_', $module_id );
		$config = $this->module_to_category[ $normalized_id ]['config'] ?? null;

		return $config['priority'] ?? 999;
	}

	/**
	 * Obtener módulos de una categoría ordenados por prioridad
	 *
	 * @param string $category_id ID de la categoría.
	 * @param bool   $only_active Solo módulos activos (default: false).
	 * @return array Array de IDs de módulos ordenados.
	 */
	public function get_category_modules( $category_id, $only_active = false ) {
		$category = $this->get_category( $category_id );
		if ( ! $category || empty( $category['modules'] ) ) {
			return [];
		}

		$modules = [];
		$active_modules = [];

		if ( $only_active && class_exists( 'Flavor_Platform_Module_Loader' ) ) {
			$active_modules = Flavor_Platform_Module_Loader::get_active_modules_cached();
		}

		foreach ( $category['modules'] as $module_id => $config ) {
			if ( $only_active && ! in_array( $module_id, $active_modules, true ) ) {
				continue;
			}

			$modules[ $module_id ] = $config['priority'] ?? 999;
		}

		// Ordenar por prioridad
		asort( $modules );

		return array_keys( $modules );
	}

	/**
	 * Obtener categorías que tienen módulos activos
	 *
	 * @return array Array de categorías con al menos un módulo activo.
	 */
	public function get_active_categories() {
		$active_categories = [];
		$active_modules = [];

		if ( class_exists( 'Flavor_Platform_Module_Loader' ) ) {
			$active_modules = Flavor_Platform_Module_Loader::get_active_modules_cached();
		}

		foreach ( $this->categories as $category_id => $category ) {
			if ( empty( $category['modules'] ) ) {
				continue;
			}

			foreach ( array_keys( $category['modules'] ) as $module_id ) {
				if ( in_array( $module_id, $active_modules, true ) ) {
					$active_categories[] = $category_id;
					break;
				}
			}
		}

		return $active_categories;
	}

	/**
	 * Obtener estructura de navegación basada en módulos activos
	 *
	 * Genera una estructura de menú filtrada por módulos activos
	 * y organizada por categorías.
	 *
	 * @return array Estructura de navegación.
	 */
	public function get_navigation_structure() {
		$navigation = [];
		$active_categories = $this->get_active_categories();

		foreach ( $active_categories as $category_id ) {
			$category = $this->get_category( $category_id );
			$modules = $this->get_category_modules( $category_id, true );

			if ( empty( $modules ) ) {
				continue;
			}

			$navigation[ $category_id ] = [
				'title'       => $category['title'],
				'description' => $category['description'],
				'icon'        => $category['icon'],
				'color'       => $category['color'],
				'modules'     => $modules,
			];
		}

		return $navigation;
	}

	/**
	 * Obtener información completa de un módulo incluyendo jerarquía
	 *
	 * @param string $module_id ID del módulo.
	 * @return array|null Información del módulo o null si no existe.
	 */
	public function get_module_info( $module_id ) {
		$normalized_id = str_replace( '-', '_', $module_id );

		if ( ! isset( $this->module_to_category[ $normalized_id ] ) ) {
			return null;
		}

		$info = $this->module_to_category[ $normalized_id ];
		$category = $this->get_category( $info['category'] );

		return [
			'id'             => $normalized_id,
			'category_id'    => $info['category'],
			'category_title' => $category['title'] ?? '',
			'category_color' => $category['color'] ?? '#3b82f6',
			'category_icon'  => $category['icon'] ?? 'dashicons-admin-plugins',
			'priority'       => $info['config']['priority'] ?? 999,
			'is_core'        => ! empty( $info['config']['core'] ),
			'parent'         => $info['config']['parent'] ?? null,
			'children'       => $this->get_module_children( $normalized_id ),
		];
	}
}

/**
 * Función helper para obtener la instancia
 *
 * @return Flavor_Module_Hierarchy
 */
function flavor_module_hierarchy() {
	return Flavor_Module_Hierarchy::get_instance();
}
