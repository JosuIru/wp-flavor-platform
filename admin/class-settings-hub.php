<?php
/**
 * Hub de Configuración Unificado
 *
 * Centraliza todas las configuraciones de Flavor Platform en una sola interfaz.
 * Proporciona acceso rápido a todas las áreas de configuración con búsqueda
 * y organización por categorías.
 *
 * @package FlavorPlatform
 * @since 3.5.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clase principal del Hub de Configuración
 */
class Flavor_Settings_Hub {

	/**
	 * Instancia singleton
	 *
	 * @var Flavor_Settings_Hub|null
	 */
	private static $instance = null;

	/**
	 * Slug de la página
	 */
	const PAGE_SLUG = 'flavor-settings-hub';

	/**
	 * Categorías de configuración
	 *
	 * @var array
	 */
	private $categories = [];

	/**
	 * Obtener instancia singleton
	 *
	 * @return Flavor_Settings_Hub
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
		add_action( 'admin_menu', [ $this, 'register_menu' ], 5 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_flavor_hub_search', [ $this, 'ajax_search' ] );

		$this->init_categories();
	}

	/**
	 * Inicializar categorías de configuración
	 */
	private function init_categories() {
		$this->categories = [
			'general'       => [
				'title'       => __( 'General', 'flavor-platform' ),
				'icon'        => 'dashicons-admin-settings',
				'description' => __( 'Configuración básica del sitio y la plataforma', 'flavor-platform' ),
				'color'       => '#3b82f6',
				'items'       => [
					[
						'title'       => __( 'Dashboard', 'flavor-platform' ),
						'description' => __( 'Panel principal y widgets', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-dashboard' ),
						'icon'        => 'dashicons-dashboard',
						'keywords'    => [ 'inicio', 'home', 'principal', 'widgets' ],
					],
					[
						'title'       => __( 'Módulos', 'flavor-platform' ),
						'description' => __( 'Activar y configurar módulos', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-app-composer' ),
						'icon'        => 'dashicons-admin-plugins',
						'keywords'    => [ 'activar', 'desactivar', 'funcionalidades', 'features' ],
					],
					[
						'title'       => __( 'Widgets Unificados', 'flavor-platform' ),
						'description' => __( 'Dashboard de widgets personalizables', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-unified-dashboard' ),
						'icon'        => 'dashicons-grid-view',
						'keywords'    => [ 'widgets', 'dashboard', 'kpis', 'metricas' ],
					],
					[
						'title'       => __( 'Licencia', 'flavor-platform' ),
						'description' => __( 'Estado de la licencia y actualizaciones', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-license' ),
						'icon'        => 'dashicons-admin-network',
						'keywords'    => [ 'licencia', 'license', 'activar', 'pro', 'premium' ],
					],
					[
						'title'       => __( 'Addons', 'flavor-platform' ),
						'description' => __( 'Extensiones y plugins adicionales', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-addons' ),
						'icon'        => 'dashicons-admin-plugins',
						'keywords'    => [ 'addons', 'extensiones', 'plugins', 'marketplace' ],
					],
				],
			],
			'design'        => [
				'title'       => __( 'Diseño', 'flavor-platform' ),
				'icon'        => 'dashicons-art',
				'description' => __( 'Apariencia, colores, tipografía y estilos', 'flavor-platform' ),
				'color'       => '#8b5cf6',
				'items'       => [
					[
						'title'       => __( 'Diseño y Apariencia', 'flavor-platform' ),
						'description' => __( 'Colores, tipografía, espaciado y CSS', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-design-settings' ),
						'icon'        => 'dashicons-admin-appearance',
						'keywords'    => [ 'colores', 'tipografia', 'fuentes', 'css', 'estilos', 'theme' ],
					],
					[
						'title'       => __( 'Layouts', 'flavor-platform' ),
						'description' => __( 'Plantillas de página y estructuras', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-layouts' ),
						'icon'        => 'dashicons-layout',
						'keywords'    => [ 'plantillas', 'templates', 'estructura', 'layout' ],
					],
					[
						'title'       => __( 'Editor Visual', 'flavor-platform' ),
						'description' => __( 'Visual Builder Pro para páginas', 'flavor-platform' ),
						'url'         => admin_url( 'edit.php?post_type=flavor_landing' ),
						'icon'        => 'dashicons-welcome-widgets-menus',
						'keywords'    => [ 'vbp', 'builder', 'editor', 'visual', 'paginas' ],
					],
				],
			],
			'ai'            => [
				'title'       => __( 'Inteligencia Artificial', 'flavor-platform' ),
				'icon'        => 'dashicons-superhero-alt',
				'description' => __( 'Asistente IA, chatbot y automatizaciones', 'flavor-platform' ),
				'color'       => '#10b981',
				'items'       => [
					[
						'title'       => __( 'Configuración IA', 'flavor-platform' ),
						'description' => __( 'Proveedores, modelos y comportamiento', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-settings' ),
						'icon'        => 'dashicons-admin-generic',
						'keywords'    => [ 'openai', 'anthropic', 'claude', 'gpt', 'chatbot', 'asistente' ],
					],
					[
						'title'       => __( 'Base de Conocimiento', 'flavor-platform' ),
						'description' => __( 'Entrenar al asistente con contenido', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-settings&tab=knowledge' ),
						'icon'        => 'dashicons-book-alt',
						'keywords'    => [ 'entrenar', 'conocimiento', 'rag', 'documentos' ],
					],
					[
						'title'       => __( 'Escalados', 'flavor-platform' ),
						'description' => __( 'Gestionar conversaciones escaladas', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-escalations' ),
						'icon'        => 'dashicons-warning',
						'keywords'    => [ 'escalados', 'soporte', 'tickets', 'atencion' ],
					],
					[
						'title'       => __( 'Herramientas IA', 'flavor-platform' ),
						'description' => __( 'Generadores y utilidades con IA', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-ai-tools' ),
						'icon'        => 'dashicons-lightbulb',
						'keywords'    => [ 'generar', 'crear', 'automatizar', 'tools' ],
					],
				],
			],
			'apps'          => [
				'title'       => __( 'Apps Móviles', 'flavor-platform' ),
				'icon'        => 'dashicons-smartphone',
				'description' => __( 'Configuración de aplicaciones móviles', 'flavor-platform' ),
				'color'       => '#f59e0b',
				'items'       => [
					[
						'title'       => __( 'Configuración Apps', 'flavor-platform' ),
						'description' => __( 'Branding, módulos y navegación', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-apps' ),
						'icon'        => 'dashicons-smartphone',
						'keywords'    => [ 'app', 'movil', 'android', 'ios', 'flutter' ],
					],
					[
						'title'       => __( 'Deep Links', 'flavor-platform' ),
						'description' => __( 'Enlaces universales y redirecciones', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-deep-links' ),
						'icon'        => 'dashicons-admin-links',
						'keywords'    => [ 'deeplinks', 'universal', 'enlaces', 'redirect' ],
					],
					[
						'title'       => __( 'Push Notifications', 'flavor-platform' ),
						'description' => __( 'Firebase y notificaciones push', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-settings&tab=firebase_push' ),
						'icon'        => 'dashicons-bell',
						'keywords'    => [ 'push', 'notificaciones', 'firebase', 'fcm' ],
					],
				],
			],
			'permissions'   => [
				'title'       => __( 'Permisos y Acceso', 'flavor-platform' ),
				'icon'        => 'dashicons-lock',
				'description' => __( 'Roles, capacidades y control de acceso', 'flavor-platform' ),
				'color'       => '#ef4444',
				'items'       => [
					[
						'title'       => __( 'Permisos', 'flavor-platform' ),
						'description' => __( 'Configurar acceso por rol', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-permissions' ),
						'icon'        => 'dashicons-admin-users',
						'keywords'    => [ 'roles', 'permisos', 'acceso', 'usuarios', 'capabilities' ],
					],
					[
						'title'       => __( 'Vistas del Shell', 'flavor-platform' ),
						'description' => __( 'Personalizar menús por vista', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-shell-views' ),
						'icon'        => 'dashicons-visibility',
						'keywords'    => [ 'vistas', 'shell', 'menu', 'navegacion' ],
					],
				],
			],
			'communication' => [
				'title'       => __( 'Comunicación', 'flavor-platform' ),
				'icon'        => 'dashicons-email-alt',
				'description' => __( 'Email, notificaciones y mensajería', 'flavor-platform' ),
				'color'       => '#06b6d4',
				'items'       => [
					[
						'title'       => __( 'Email Marketing', 'flavor-platform' ),
						'description' => __( 'Campañas y newsletters', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=email-marketing-dashboard' ),
						'icon'        => 'dashicons-email',
						'keywords'    => [ 'email', 'newsletter', 'campanas', 'mailing' ],
					],
					[
						'title'       => __( 'Notificaciones', 'flavor-platform' ),
						'description' => __( 'Configurar alertas y avisos', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-settings&tab=firebase_push' ),
						'icon'        => 'dashicons-bell',
						'keywords'    => [ 'notificaciones', 'alertas', 'avisos' ],
					],
				],
			],
			'tools'         => [
				'title'       => __( 'Herramientas', 'flavor-platform' ),
				'icon'        => 'dashicons-admin-tools',
				'description' => __( 'Utilidades, diagnóstico y mantenimiento', 'flavor-platform' ),
				'color'       => '#64748b',
				'items'       => [
					[
						'title'       => __( 'Diagnóstico', 'flavor-platform' ),
						'description' => __( 'Estado del sistema y salud', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-health-check' ),
						'icon'        => 'dashicons-heart',
						'keywords'    => [ 'diagnostico', 'salud', 'health', 'check', 'sistema' ],
					],
					[
						'title'       => __( 'Exportar/Importar', 'flavor-platform' ),
						'description' => __( 'Backup y migración de datos', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-export-import' ),
						'icon'        => 'dashicons-migrate',
						'keywords'    => [ 'exportar', 'importar', 'backup', 'migracion' ],
					],
					[
						'title'       => __( 'Datos Demo', 'flavor-platform' ),
						'description' => __( 'Cargar contenido de demostración', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-demo-data' ),
						'icon'        => 'dashicons-database-import',
						'keywords'    => [ 'demo', 'ejemplo', 'muestra', 'contenido' ],
					],
					[
						'title'       => __( 'Registro de Actividad', 'flavor-platform' ),
						'description' => __( 'Historial de cambios y acciones', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-activity-log' ),
						'icon'        => 'dashicons-backup',
						'keywords'    => [ 'log', 'historial', 'actividad', 'auditoria' ],
					],
					[
						'title'       => __( 'API Documentation', 'flavor-platform' ),
						'description' => __( 'Documentación de la API REST', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-api-docs' ),
						'icon'        => 'dashicons-rest-api',
						'keywords'    => [ 'api', 'rest', 'documentacion', 'endpoints' ],
					],
				],
			],
			'moderation'    => [
				'title'       => __( 'Moderación', 'flavor-platform' ),
				'icon'        => 'dashicons-shield',
				'description' => __( 'Moderación de contenido y usuarios', 'flavor-platform' ),
				'color'       => '#dc2626',
				'items'       => [
					[
						'title'       => __( 'Panel de Moderación', 'flavor-platform' ),
						'description' => __( 'Revisar contenido reportado', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-moderation' ),
						'icon'        => 'dashicons-shield',
						'keywords'    => [ 'moderar', 'reportes', 'contenido', 'spam' ],
					],
					[
						'title'       => __( 'Anuncios', 'flavor-platform' ),
						'description' => __( 'Gestionar publicidad y banners', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=advertising-dashboard' ),
						'icon'        => 'dashicons-megaphone',
						'keywords'    => [ 'anuncios', 'publicidad', 'banners', 'ads' ],
					],
				],
			],
			'analytics'     => [
				'title'       => __( 'Analytics', 'flavor-platform' ),
				'icon'        => 'dashicons-chart-area',
				'description' => __( 'Métricas, estadísticas y reportes', 'flavor-platform' ),
				'color'       => '#7c3aed',
				'items'       => [
					[
						'title'       => __( 'Dashboard Analytics', 'flavor-platform' ),
						'description' => __( 'Métricas generales del sitio', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-analytics' ),
						'icon'        => 'dashicons-chart-area',
						'keywords'    => [ 'metricas', 'estadisticas', 'graficos', 'reportes' ],
					],
					[
						'title'       => __( 'Registro de Actividad', 'flavor-platform' ),
						'description' => __( 'Historial de cambios y acciones', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-activity-log' ),
						'icon'        => 'dashicons-backup',
						'keywords'    => [ 'log', 'historial', 'actividad', 'auditoria' ],
					],
				],
			],
			'advanced'      => [
				'title'       => __( 'Avanzado', 'flavor-platform' ),
				'icon'        => 'dashicons-admin-generic',
				'description' => __( 'Opciones avanzadas y desarrollo', 'flavor-platform' ),
				'color'       => '#1f2937',
				'items'       => [
					[
						'title'       => __( 'Opciones Avanzadas', 'flavor-platform' ),
						'description' => __( 'Cache, debug y configuración técnica', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-settings&tab=advanced' ),
						'icon'        => 'dashicons-admin-tools',
						'keywords'    => [ 'cache', 'debug', 'avanzado', 'tecnico' ],
					],
					[
						'title'       => __( 'Red de Nodos', 'flavor-platform' ),
						'description' => __( 'Federación y conexiones entre sitios', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-platform-network' ),
						'icon'        => 'dashicons-networking',
						'keywords'    => [ 'red', 'network', 'federacion', 'nodos', 'peers' ],
					],
					[
						'title'       => __( 'Integraciones', 'flavor-platform' ),
						'description' => __( 'Conexiones con servicios externos', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-integraciones-config' ),
						'icon'        => 'dashicons-admin-links',
						'keywords'    => [ 'integraciones', 'webhooks', 'api', 'externos' ],
					],
					[
						'title'       => __( 'Sistemas V3', 'flavor-platform' ),
						'description' => __( 'Panel de sistemas avanzados', 'flavor-platform' ),
						'url'         => admin_url( 'admin.php?page=flavor-systems-panel' ),
						'icon'        => 'dashicons-admin-generic',
						'keywords'    => [ 'sistemas', 'v3', 'panel', 'avanzado' ],
					],
				],
			],
		];

		// Añadir configuraciones dinámicas de módulos activos
		$this->add_module_settings();

		// Permitir extensión via filtro
		$this->categories = apply_filters( 'flavor_settings_hub_categories', $this->categories );
	}

	/**
	 * Añadir configuraciones dinámicas de módulos activos
	 *
	 * Crea una categoría "modules" con links a la configuración
	 * de cada módulo que está activo y tiene página de settings.
	 */
	private function add_module_settings() {
		$active_modules = get_option( 'flavor_active_modules', [] );

		if ( empty( $active_modules ) || ! is_array( $active_modules ) ) {
			return;
		}

		// Mapeo de módulos a sus páginas de configuración
		$module_settings_map = [
			// Comunidad
			'socios'            => [
				'title'    => __( 'Socios/Miembros', 'flavor-platform' ),
				'url'      => 'socios-dashboard',
				'icon'     => 'dashicons-id-alt',
				'keywords' => [ 'socios', 'miembros', 'usuarios', 'membresia' ],
				'category' => 'comunidad',
			],
			'comunidades'       => [
				'title'    => __( 'Comunidades', 'flavor-platform' ),
				'url'      => 'comunidades-dashboard',
				'icon'     => 'dashicons-admin-multisite',
				'keywords' => [ 'comunidades', 'grupos', 'vecinos' ],
				'category' => 'comunidad',
			],
			'foros'             => [
				'title'    => __( 'Foros', 'flavor-platform' ),
				'url'      => 'foros-dashboard',
				'icon'     => 'dashicons-format-chat',
				'keywords' => [ 'foros', 'discusion', 'temas', 'posts' ],
				'category' => 'comunidad',
			],
			'red-social'        => [
				'title'    => __( 'Red Social', 'flavor-platform' ),
				'url'      => 'flavor-red-social-dashboard',
				'icon'     => 'dashicons-share',
				'keywords' => [ 'red', 'social', 'amigos', 'feed' ],
				'category' => 'comunidad',
			],
			// Economía
			'grupos-consumo'    => [
				'title'    => __( 'Grupos de Consumo', 'flavor-platform' ),
				'url'      => 'gc-dashboard',
				'icon'     => 'dashicons-cart',
				'keywords' => [ 'grupos', 'consumo', 'pedidos', 'productos' ],
				'category' => 'economia',
			],
			'marketplace'       => [
				'title'    => __( 'Marketplace', 'flavor-platform' ),
				'url'      => 'marketplace-dashboard',
				'icon'     => 'dashicons-store',
				'keywords' => [ 'tienda', 'productos', 'vender', 'comprar' ],
				'category' => 'economia',
			],
			'banco-tiempo'      => [
				'title'    => __( 'Banco de Tiempo', 'flavor-platform' ),
				'url'      => 'banco-tiempo-dashboard',
				'icon'     => 'dashicons-clock',
				'keywords' => [ 'banco', 'tiempo', 'intercambio', 'horas' ],
				'category' => 'economia',
			],
			// Actividades
			'eventos'           => [
				'title'    => __( 'Eventos', 'flavor-platform' ),
				'url'      => 'eventos-dashboard',
				'icon'     => 'dashicons-calendar',
				'keywords' => [ 'eventos', 'actividades', 'calendario', 'inscripciones' ],
				'category' => 'actividades',
			],
			'cursos'            => [
				'title'    => __( 'Cursos', 'flavor-platform' ),
				'url'      => 'cursos-dashboard',
				'icon'     => 'dashicons-welcome-learn-more',
				'keywords' => [ 'cursos', 'formacion', 'aprender', 'lecciones' ],
				'category' => 'actividades',
			],
			'talleres'          => [
				'title'    => __( 'Talleres', 'flavor-platform' ),
				'url'      => 'talleres-dashboard',
				'icon'     => 'dashicons-hammer',
				'keywords' => [ 'talleres', 'practico', 'manualidades' ],
				'category' => 'actividades',
			],
			'reservas'          => [
				'title'    => __( 'Reservas', 'flavor-platform' ),
				'url'      => 'reservas-dashboard',
				'icon'     => 'dashicons-tickets-alt',
				'keywords' => [ 'reservas', 'booking', 'disponibilidad' ],
				'category' => 'actividades',
			],
			// Servicios
			'tramites'          => [
				'title'    => __( 'Trámites', 'flavor-platform' ),
				'url'      => 'tramites-dashboard',
				'icon'     => 'dashicons-clipboard',
				'keywords' => [ 'tramites', 'gestiones', 'solicitudes' ],
				'category' => 'servicios',
			],
			'incidencias'       => [
				'title'    => __( 'Incidencias', 'flavor-platform' ),
				'url'      => 'incidencias-dashboard',
				'icon'     => 'dashicons-warning',
				'keywords' => [ 'incidencias', 'problemas', 'reportar' ],
				'category' => 'servicios',
			],
			'participacion'     => [
				'title'    => __( 'Participación', 'flavor-platform' ),
				'url'      => 'participacion-dashboard',
				'icon'     => 'dashicons-megaphone',
				'keywords' => [ 'participacion', 'propuestas', 'votar' ],
				'category' => 'servicios',
			],
			'transparencia'     => [
				'title'    => __( 'Transparencia', 'flavor-platform' ),
				'url'      => 'transparencia-dashboard',
				'icon'     => 'dashicons-visibility',
				'keywords' => [ 'transparencia', 'actas', 'presupuestos' ],
				'category' => 'servicios',
			],
		];

		// Agrupar módulos activos por categoría
		$modules_by_category = [];

		foreach ( $active_modules as $module_id ) {
			if ( ! isset( $module_settings_map[ $module_id ] ) ) {
				continue;
			}

			$module_info = $module_settings_map[ $module_id ];
			$category_key = $module_info['category'];

			if ( ! isset( $modules_by_category[ $category_key ] ) ) {
				$modules_by_category[ $category_key ] = [];
			}

			$modules_by_category[ $category_key ][] = [
				'title'       => $module_info['title'],
				'description' => sprintf(
					/* translators: %s: module name */
					__( 'Configurar %s', 'flavor-platform' ),
					$module_info['title']
				),
				'url'         => admin_url( 'admin.php?page=' . $module_info['url'] ),
				'icon'        => $module_info['icon'],
				'keywords'    => $module_info['keywords'],
			];
		}

		// Crear categorías de módulos si hay módulos activos
		$category_definitions = [
			'comunidad'   => [
				'title'       => __( 'Módulos: Comunidad', 'flavor-platform' ),
				'icon'        => 'dashicons-groups',
				'description' => __( 'Configuración de módulos de comunidad', 'flavor-platform' ),
				'color'       => '#ec4899',
			],
			'economia'    => [
				'title'       => __( 'Módulos: Economía', 'flavor-platform' ),
				'icon'        => 'dashicons-money-alt',
				'description' => __( 'Configuración de módulos económicos', 'flavor-platform' ),
				'color'       => '#22c55e',
			],
			'actividades' => [
				'title'       => __( 'Módulos: Actividades', 'flavor-platform' ),
				'icon'        => 'dashicons-calendar-alt',
				'description' => __( 'Configuración de eventos y formación', 'flavor-platform' ),
				'color'       => '#f97316',
			],
			'servicios'   => [
				'title'       => __( 'Módulos: Servicios', 'flavor-platform' ),
				'icon'        => 'dashicons-admin-tools',
				'description' => __( 'Configuración de servicios ciudadanos', 'flavor-platform' ),
				'color'       => '#14b8a6',
			],
		];

		foreach ( $modules_by_category as $category_key => $items ) {
			if ( empty( $items ) || ! isset( $category_definitions[ $category_key ] ) ) {
				continue;
			}

			$this->categories[ 'mod_' . $category_key ] = [
				'title'       => $category_definitions[ $category_key ]['title'],
				'icon'        => $category_definitions[ $category_key ]['icon'],
				'description' => $category_definitions[ $category_key ]['description'],
				'color'       => $category_definitions[ $category_key ]['color'],
				'items'       => $items,
			];
		}
	}

	/**
	 * Registrar página de menú
	 */
	public function register_menu() {
		add_submenu_page(
			'flavor-dashboard',
			__( 'Configuración', 'flavor-platform' ),
			__( 'Configuración', 'flavor-platform' ),
			'manage_options',
			self::PAGE_SLUG,
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Encolar assets
	 *
	 * @param string $hook Hook de la página actual.
	 */
	public function enqueue_assets( $hook ) {
		if ( strpos( $hook, self::PAGE_SLUG ) === false ) {
			return;
		}

		wp_enqueue_style(
			'flavor-settings-hub',
			FLAVOR_PLATFORM_URL . 'admin/css/settings-hub.css',
			[],
			FLAVOR_PLATFORM_VERSION
		);

		wp_enqueue_script(
			'flavor-settings-hub',
			FLAVOR_PLATFORM_URL . 'admin/js/settings-hub.js',
			[ 'jquery' ],
			FLAVOR_PLATFORM_VERSION,
			true
		);

		wp_localize_script(
			'flavor-settings-hub',
			'flavorSettingsHub',
			[
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'nonce'      => wp_create_nonce( 'flavor_hub_search' ),
				'categories' => $this->categories,
				'i18n'       => [
					'searchPlaceholder' => __( 'Buscar configuración...', 'flavor-platform' ),
					'noResults'         => __( 'No se encontraron resultados', 'flavor-platform' ),
					'quickAccess'       => __( 'Acceso rápido', 'flavor-platform' ),
				],
			]
		);
	}

	/**
	 * Renderizar la página del hub
	 */
	public function render_page() {
		$active_modules = get_option( 'flavor_active_modules', [] );
		$module_count   = is_array( $active_modules ) ? count( $active_modules ) : 0;

		?>
		<div class="wrap flavor-settings-hub">
			<div class="fsh-header">
				<div class="fsh-header__title">
					<h1><?php esc_html_e( 'Configuración', 'flavor-platform' ); ?></h1>
					<p class="fsh-header__subtitle">
						<?php esc_html_e( 'Administra todas las opciones de Flavor Platform desde un solo lugar', 'flavor-platform' ); ?>
					</p>
				</div>
				<div class="fsh-header__search">
					<span class="dashicons dashicons-search"></span>
					<input
						type="text"
						id="fsh-search"
						placeholder="<?php esc_attr_e( 'Buscar configuración... (Ctrl+/)', 'flavor-platform' ); ?>"
						autocomplete="off"
					>
					<kbd>/</kbd>
				</div>
			</div>

			<!-- Quick Stats -->
			<div class="fsh-quick-stats">
				<div class="fsh-stat">
					<span class="fsh-stat__value"><?php echo esc_html( $module_count ); ?></span>
					<span class="fsh-stat__label"><?php esc_html_e( 'Módulos activos', 'flavor-platform' ); ?></span>
				</div>
				<div class="fsh-stat">
					<span class="fsh-stat__value"><?php echo esc_html( count( $this->categories ) ); ?></span>
					<span class="fsh-stat__label"><?php esc_html_e( 'Categorías', 'flavor-platform' ); ?></span>
				</div>
				<div class="fsh-stat">
					<span class="fsh-stat__value"><?php echo esc_html( $this->count_total_items() ); ?></span>
					<span class="fsh-stat__label"><?php esc_html_e( 'Opciones disponibles', 'flavor-platform' ); ?></span>
				</div>
			</div>

			<!-- Search Results (hidden by default) -->
			<div id="fsh-search-results" class="fsh-search-results" style="display: none;">
				<h2 class="fsh-section-title">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Resultados de búsqueda', 'flavor-platform' ); ?>
				</h2>
				<div class="fsh-search-results__grid"></div>
			</div>

			<!-- Categories Grid -->
			<div id="fsh-categories" class="fsh-categories">
				<?php foreach ( $this->categories as $category_id => $category ) : ?>
					<div class="fsh-category" data-category="<?php echo esc_attr( $category_id ); ?>">
						<div class="fsh-category__header" style="--category-color: <?php echo esc_attr( $category['color'] ); ?>">
							<span class="dashicons <?php echo esc_attr( $category['icon'] ); ?>"></span>
							<div class="fsh-category__info">
								<h2><?php echo esc_html( $category['title'] ); ?></h2>
								<p><?php echo esc_html( $category['description'] ); ?></p>
							</div>
							<span class="fsh-category__count"><?php echo count( $category['items'] ); ?></span>
						</div>
						<div class="fsh-category__items">
							<?php foreach ( $category['items'] as $item ) : ?>
								<a href="<?php echo esc_url( $item['url'] ); ?>" class="fsh-item">
									<span class="dashicons <?php echo esc_attr( $item['icon'] ); ?>"></span>
									<div class="fsh-item__content">
										<span class="fsh-item__title"><?php echo esc_html( $item['title'] ); ?></span>
										<span class="fsh-item__desc"><?php echo esc_html( $item['description'] ); ?></span>
									</div>
									<span class="dashicons dashicons-arrow-right-alt2 fsh-item__arrow"></span>
								</a>
							<?php endforeach; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<!-- Quick Links -->
			<div class="fsh-quick-links">
				<h2 class="fsh-section-title">
					<span class="dashicons dashicons-star-filled"></span>
					<?php esc_html_e( 'Acciones rápidas', 'flavor-platform' ); ?>
				</h2>
				<div class="fsh-quick-links__grid">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-app-composer' ) ); ?>" class="fsh-quick-link">
						<span class="dashicons dashicons-plus-alt"></span>
						<?php esc_html_e( 'Activar módulos', 'flavor-platform' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-design-settings' ) ); ?>" class="fsh-quick-link">
						<span class="dashicons dashicons-art"></span>
						<?php esc_html_e( 'Personalizar diseño', 'flavor-platform' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-platform-health-check' ) ); ?>" class="fsh-quick-link">
						<span class="dashicons dashicons-heart"></span>
						<?php esc_html_e( 'Ver diagnóstico', 'flavor-platform' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-platform-docs' ) ); ?>" class="fsh-quick-link">
						<span class="dashicons dashicons-book"></span>
						<?php esc_html_e( 'Ver documentación', 'flavor-platform' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Contar total de items
	 *
	 * @return int
	 */
	private function count_total_items() {
		$total = 0;
		foreach ( $this->categories as $category ) {
			$total += count( $category['items'] );
		}
		return $total;
	}

	/**
	 * AJAX: Buscar configuraciones
	 */
	public function ajax_search() {
		check_ajax_referer( 'flavor_hub_search', 'nonce' );

		$query   = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		$results = [];

		if ( strlen( $query ) < 2 ) {
			wp_send_json_success( [ 'results' => [] ] );
		}

		$query_lower = mb_strtolower( $query );

		foreach ( $this->categories as $category_id => $category ) {
			foreach ( $category['items'] as $item ) {
				$match = false;

				// Buscar en título
				if ( mb_strpos( mb_strtolower( $item['title'] ), $query_lower ) !== false ) {
					$match = true;
				}

				// Buscar en descripción
				if ( ! $match && mb_strpos( mb_strtolower( $item['description'] ), $query_lower ) !== false ) {
					$match = true;
				}

				// Buscar en keywords
				if ( ! $match && isset( $item['keywords'] ) ) {
					foreach ( $item['keywords'] as $keyword ) {
						if ( mb_strpos( mb_strtolower( $keyword ), $query_lower ) !== false ) {
							$match = true;
							break;
						}
					}
				}

				if ( $match ) {
					$results[] = [
						'title'       => $item['title'],
						'description' => $item['description'],
						'url'         => $item['url'],
						'icon'        => $item['icon'],
						'category'    => $category['title'],
						'color'       => $category['color'],
					];
				}
			}
		}

		wp_send_json_success( [ 'results' => $results ] );
	}

	/**
	 * Obtener categorías
	 *
	 * @return array
	 */
	public function get_categories() {
		return $this->categories;
	}
}

// Inicializar
Flavor_Settings_Hub::get_instance();
