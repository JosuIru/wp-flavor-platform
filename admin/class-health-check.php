<?php
/**
 * Health Check / Diagnostic admin page for Flavor Platform
 *
 * @package FlavorPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Flavor_Health_Check {

	private static $instance = null;
	const PARENT_MENU_SLUG = FLAVOR_PLATFORM_TEXT_DOMAIN;
	const PAGE_SLUG = 'flavor-platform-health-check';

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		// Menú registrado centralmente por Flavor_Admin_Menu_Manager
	}

	public function register_admin_page() {
		add_submenu_page(
			self::PARENT_MENU_SLUG,
			'Diagnostico',
			'Diagnostico',
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	private function check_modules() {
		$module_check_results = array();
		$module_loader      = Flavor_Platform_Module_Loader::get_instance();
		$loaded_modules     = $module_loader->get_loaded_modules();
		$plugin_settings    = flavor_get_main_settings();
		$active_module_ids  = isset( $plugin_settings['active_modules'] )
			? $plugin_settings['active_modules']
			: array( 'woocommerce' );
		$registered_module_map = $this->build_registered_module_map(
			FLAVOR_PLATFORM_PATH . 'includes/modules/'
		);

		foreach ( $registered_module_map as $module_identifier => $module_definition ) {
			$module_file_path      = $module_definition['file'];
			$module_class_name     = $module_definition['class'];
			$file_exists           = file_exists( $module_file_path );
			$class_is_loadable     = false;
			$is_active             = in_array( $module_identifier, $active_module_ids, true );
			$is_loaded             = isset( $loaded_modules[ $module_identifier ] );
			$has_web_components    = false;
			$module_display_name   = ucfirst( str_replace( '_', ' ', $module_identifier ) );
			$module_status         = 'ok';
			$module_status_message = '';

			if ( $file_exists ) {
				if ( ! class_exists( $module_class_name ) ) {
					@include_once $module_file_path;
				}
				$class_is_loadable = class_exists( $module_class_name );
				if ( $class_is_loadable ) {
					$has_web_components = method_exists( $module_class_name, 'get_web_components' );
					if ( $is_loaded && isset( $loaded_modules[ $module_identifier ] ) ) {
						$module_display_name = $loaded_modules[ $module_identifier ]->get_name();
					} else {
						try {
							$temporary_instance = new $module_class_name();
							$module_display_name = $temporary_instance->get_name();
						} catch ( \Exception $exception ) {}
					}
				}
			}

			if ( ! $file_exists ) {
				$module_status = 'error';
				$module_status_message = 'Archivo no encontrado';
			} elseif ( ! $class_is_loadable ) {
				$module_status = 'error';
				$module_status_message = 'Clase no cargable';
			} elseif ( $is_active && ! $is_loaded ) {
				$module_status = 'warning';
				$module_status_message = 'Activo pero no cargado (posible error de dependencias)';
			} elseif ( ! $is_active ) {
				$module_status = 'warning';
				$module_status_message = 'Modulo desactivado';
			} else {
				$module_status_message = 'Funcionando correctamente';
			}

			$module_check_results[ $module_identifier ] = array(
				'name'              => $module_display_name,
				'file_exists'       => $file_exists,
				'class_loadable'    => $class_is_loadable,
				'is_active'         => $is_active,
				'is_loaded'         => $is_loaded,
				'has_web_components' => $has_web_components,
				'status'            => $module_status,
				'status_message'    => $module_status_message,
			);
		}
		return $module_check_results;
	}

	private function build_registered_module_map( $modules_base_path ) {
		return array(
			'woocommerce'                 => array( 'file' => $modules_base_path . 'woocommerce/class-woocommerce-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_WooCommerce_Module' ) ),
			'banco_tiempo'                => array( 'file' => $modules_base_path . 'banco-tiempo/class-banco-tiempo-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Banco_Tiempo_Module' ) ),
			'marketplace'                 => array( 'file' => $modules_base_path . 'marketplace/class-marketplace-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Marketplace_Module' ) ),
			'grupos_consumo'              => array( 'file' => $modules_base_path . 'grupos-consumo/class-grupos-consumo-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Grupos_Consumo_Module' ) ),
			'facturas'                    => array( 'file' => $modules_base_path . 'facturas/class-facturas-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Facturas_Module' ) ),
			'fichaje_empleados'           => array( 'file' => $modules_base_path . 'fichaje-empleados/class-fichaje-empleados-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Fichaje_Empleados_Module' ) ),
			'eventos'                     => array( 'file' => $modules_base_path . 'eventos/class-eventos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Eventos_Module' ) ),
			'socios'                      => array( 'file' => $modules_base_path . 'socios/class-socios-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Socios_Module' ) ),
			'incidencias'                 => array( 'file' => $modules_base_path . 'incidencias/class-incidencias-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Incidencias_Module' ) ),
			'participacion'               => array( 'file' => $modules_base_path . 'participacion/class-participacion-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Participacion_Module' ) ),
			'presupuestos_participativos' => array( 'file' => $modules_base_path . 'presupuestos-participativos/class-presupuestos-participativos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Presupuestos_Participativos_Module' ) ),
			'avisos_municipales'          => array( 'file' => $modules_base_path . 'avisos-municipales/class-avisos-municipales-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Avisos_Municipales_Module' ) ),
			'advertising'                 => array( 'file' => $modules_base_path . 'advertising/class-advertising-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Advertising_Module' ) ),
			'ayuda_vecinal'               => array( 'file' => $modules_base_path . 'ayuda-vecinal/class-ayuda-vecinal-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Ayuda_Vecinal_Module' ) ),
			'biblioteca'                  => array( 'file' => $modules_base_path . 'biblioteca/class-biblioteca-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Biblioteca_Module' ) ),
			'bicicletas_compartidas'      => array( 'file' => $modules_base_path . 'bicicletas-compartidas/class-bicicletas-compartidas-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Bicicletas_Compartidas_Module' ) ),
			'carpooling'                  => array( 'file' => $modules_base_path . 'carpooling/class-carpooling-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Carpooling_Module' ) ),
			'chat_grupos'                 => array( 'file' => $modules_base_path . 'chat-grupos/class-chat-grupos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Chat_Grupos_Module' ) ),
			'chat_interno'                => array( 'file' => $modules_base_path . 'chat-interno/class-chat-interno-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Chat_Interno_Module' ) ),
			'compostaje'                  => array( 'file' => $modules_base_path . 'compostaje/class-compostaje-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Compostaje_Module' ) ),
			'cursos'                      => array( 'file' => $modules_base_path . 'cursos/class-cursos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Cursos_Module' ) ),
			'empresarial'                 => array( 'file' => $modules_base_path . 'empresarial/class-empresarial-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Empresarial_Module' ) ),
			'espacios_comunes'            => array( 'file' => $modules_base_path . 'espacios-comunes/class-espacios-comunes-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Espacios_Comunes_Module' ) ),
			'huertos_urbanos'             => array( 'file' => $modules_base_path . 'huertos-urbanos/class-huertos-urbanos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Huertos_Urbanos_Module' ) ),
			'multimedia'                  => array( 'file' => $modules_base_path . 'multimedia/class-multimedia-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Multimedia_Module' ) ),
			'parkings'                    => array( 'file' => $modules_base_path . 'parkings/class-parkings-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Parkings_Module' ) ),
			'podcast'                     => array( 'file' => $modules_base_path . 'podcast/class-podcast-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Podcast_Module' ) ),
			'radio'                       => array( 'file' => $modules_base_path . 'radio/class-radio-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Radio_Module' ) ),
			'reciclaje'                   => array( 'file' => $modules_base_path . 'reciclaje/class-reciclaje-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Reciclaje_Module' ) ),
			'red_social'                  => array( 'file' => $modules_base_path . 'red-social/class-red-social-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Red_Social_Module' ) ),
			'talleres'                    => array( 'file' => $modules_base_path . 'talleres/class-talleres-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Talleres_Module' ) ),
			'tramites'                    => array( 'file' => $modules_base_path . 'tramites/class-tramites-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Tramites_Module' ) ),
			'transparencia'               => array( 'file' => $modules_base_path . 'transparencia/class-transparencia-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Transparencia_Module' ) ),
			'colectivos'                  => array( 'file' => $modules_base_path . 'colectivos/class-colectivos-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Colectivos_Module' ) ),
			'foros'                       => array( 'file' => $modules_base_path . 'foros/class-foros-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Foros_Module' ) ),
			'clientes'                    => array( 'file' => $modules_base_path . 'clientes/class-clientes-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Clientes_Module' ) ),
			'comunidades'                 => array( 'file' => $modules_base_path . 'comunidades/class-comunidades-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Comunidades_Module' ) ),
			'bares'                       => array( 'file' => $modules_base_path . 'bares/class-bares-module.php', 'class' => flavor_get_runtime_class_name( 'Flavor_Chat_Bares_Module' ) ),
		);
	}

	private function check_database_tables() {
		global $wpdb;
		$database_table_results = array();
		$flavor_tables_query = $wpdb->get_results(
			$wpdb->prepare( "SHOW TABLES LIKE %s", $wpdb->esc_like( $wpdb->prefix . 'flavor_' ) . '%' )
		);
		if ( ! empty( $flavor_tables_query ) ) {
			foreach ( $flavor_tables_query as $table_row ) {
				$table_name_values = array_values( get_object_vars( $table_row ) );
				$full_table_name   = $table_name_values[0];
				$row_count_result = $wpdb->get_var( "SELECT COUNT(*) FROM `{$full_table_name}`" );
				$database_table_results[] = array(
					'table_name' => $full_table_name,
					'row_count'  => intval( $row_count_result ),
				);
			}
		}
		return $database_table_results;
	}

	private function check_template_files( $module_check_results ) {
		$template_check_results = array();
		$templates_base_path    = FLAVOR_PLATFORM_PATH . 'templates/components/';
		$registered_module_map  = $this->build_registered_module_map( FLAVOR_PLATFORM_PATH . 'includes/modules/' );

		foreach ( $module_check_results as $module_identifier => $module_data ) {
			if ( ! $module_data['has_web_components'] || ! $module_data['class_loadable'] ) {
				continue;
			}
			if ( ! isset( $registered_module_map[ $module_identifier ] ) ) {
				continue;
			}
			$class_name_string = $registered_module_map[ $module_identifier ]['class'];
			if ( ! class_exists( $class_name_string ) ) {
				continue;
			}
			try {
				$module_instance = new $class_name_string();
				$web_components  = $module_instance->get_web_components();
				if ( ! is_array( $web_components ) ) {
					continue;
				}
				foreach ( $web_components as $component_key => $component_definition ) {
					$template_relative_path = isset( $component_definition['template'] ) ? $component_definition['template'] : '';
					if ( empty( $template_relative_path ) ) {
						continue;
					}
					$full_template_path   = $templates_base_path . $template_relative_path . '.php';
					$template_file_exists = file_exists( $full_template_path );
					$component_label      = isset( $component_definition['label'] ) ? $component_definition['label'] : $component_key;
					$template_check_results[] = array(
						'module_name'     => $module_data['name'],
						'component_label' => $component_label,
						'template_path'   => $template_relative_path,
						'file_exists'     => $template_file_exists,
						'full_path'       => $full_template_path,
					);
				}
			} catch ( \Exception $exception ) {
				$template_check_results[] = array(
					'module_name'     => $module_data['name'],
					'component_label' => 'Error al leer componentes',
					'template_path'   => '',
					'file_exists'     => false,
					'full_path'       => '',
				);
			}
		}
		return $template_check_results;
	}

	private function get_system_info( $module_check_results, $database_table_results ) {
		$active_module_count = 0;
		foreach ( $module_check_results as $module_data ) {
			if ( $module_data['is_active'] && $module_data['is_loaded'] ) {
				$active_module_count++;
			}
		}
		return array(
			array( 'label' => 'PHP Version',           'value' => phpversion() ),
			array( 'label' => 'WordPress Version',     'value' => get_bloginfo( 'version' ) ),
			array( 'label' => 'Plugin Version',        'value' => defined( 'FLAVOR_PLATFORM_VERSION' ) ? FLAVOR_PLATFORM_VERSION : 'Unknown' ),
			array( 'label' => 'PHP Memory Limit',      'value' => ini_get( 'memory_limit' ) ),
			array( 'label' => 'Max Execution Time',    'value' => ini_get( 'max_execution_time' ) . 's' ),
			array( 'label' => 'Active Modules',        'value' => $active_module_count ),
			array( 'label' => 'Database Tables',       'value' => count( $database_table_results ) ),
		);
	}

	private function check_api_endpoints() {
		$api_check_results = [];
		$modules_base_path = FLAVOR_PLATFORM_PATH . 'includes/modules/';
		$registered_module_map = $this->build_registered_module_map($modules_base_path);

		foreach ($registered_module_map as $module_identifier => $module_definition) {
			$module_file_path = $module_definition['file'];
			$module_class_name = $module_definition['class'];
			$has_rest_api = false;
			$endpoints_count = 0;
			$api_namespace = '';
			$api_status = 'warning';
			$api_status_message = 'Sin REST API';

			if (file_exists($module_file_path)) {
				// Leer el contenido del archivo para buscar register_rest_route
				$file_content = file_get_contents($module_file_path);

				if (strpos($file_content, 'register_rest_route') !== false) {
					$has_rest_api = true;

					// Contar endpoints aproximados
					$endpoints_count = substr_count($file_content, 'register_rest_route');

					// Extraer namespace si existe
					if (preg_match("/register_rest_route\s*\(\s*['\"]([^'\"]+)['\"]/", $file_content, $matches)) {
						$api_namespace = $matches[1];
					}

					$api_status = 'ok';
					$api_status_message = sprintf('%d endpoint(s) en %s', $endpoints_count, $api_namespace ?: 'flavor/v1');
				}

				// Verificar si tiene el hook rest_api_init
				if (!$has_rest_api && strpos($file_content, 'rest_api_init') !== false) {
					$api_status = 'warning';
					$api_status_message = 'Hook registrado pero sin endpoints';
				}
			}

			$module_display_name = ucfirst(str_replace('_', ' ', $module_identifier));

			$api_check_results[$module_identifier] = [
				'name' => $module_display_name,
				'has_rest_api' => $has_rest_api,
				'endpoints_count' => $endpoints_count,
				'namespace' => $api_namespace,
				'status' => $api_status,
				'status_message' => $api_status_message,
			];
		}

		return $api_check_results;
	}

	private function check_ai_configuration() {
		$plugin_settings    = get_option( 'flavor_chat_ia_settings', array() );
		$active_ai_provider = isset( $plugin_settings['active_provider'] ) ? $plugin_settings['active_provider'] : 'claude';
		$ai_configuration_checks = array();
		$ai_provider_definitions = array(
			'claude'   => array( 'label' => 'Claude (Anthropic)', 'key_name' => 'claude_api_key', 'fallback' => 'api_key' ),
			'openai'   => array( 'label' => 'OpenAI',            'key_name' => 'openai_api_key',  'fallback' => '' ),
			'deepseek' => array( 'label' => 'DeepSeek',          'key_name' => 'deepseek_api_key','fallback' => '' ),
			'mistral'  => array( 'label' => 'Mistral',           'key_name' => 'mistral_api_key', 'fallback' => '' ),
		);
		foreach ( $ai_provider_definitions as $provider_identifier => $provider_definition ) {
			$api_key_value = '';
			if ( ! empty( $plugin_settings[ $provider_definition['key_name'] ] ) ) {
				$api_key_value = $plugin_settings[ $provider_definition['key_name'] ];
			} elseif ( ! empty( $provider_definition['fallback'] ) && ! empty( $plugin_settings[ $provider_definition['fallback'] ] ) ) {
				$api_key_value = $plugin_settings[ $provider_definition['fallback'] ];
			}
			$is_configured      = ! empty( $api_key_value );
			$is_active_provider = ( $provider_identifier === $active_ai_provider );
			if ( $is_active_provider && ! $is_configured ) {
				$provider_status = 'error';
				$status_message  = 'Proveedor activo sin API key configurada';
			} elseif ( $is_configured ) {
				$provider_status = 'ok';
				$masked_key      = substr( $api_key_value, 0, 8 ) . '...' . substr( $api_key_value, -4 );
				$status_message  = 'Configurada (' . $masked_key . ')';
			} else {
				$provider_status = 'warning';
				$status_message  = 'No configurada';
			}
			$ai_configuration_checks[ $provider_identifier ] = array(
				'label'         => $provider_definition['label'],
				'is_configured' => $is_configured,
				'is_active'     => $is_active_provider,
				'status'        => $provider_status,
				'message'       => $status_message,
			);
		}
		return $ai_configuration_checks;
	}

	private function calculate_summary_counts( $module_check_results, $template_check_results, $ai_configuration_checks, $api_check_results = [] ) {
		$ok_count = 0; $warning_count = 0; $error_count = 0;
		foreach ( $module_check_results as $module_data ) {
			if ( $module_data['status'] === 'ok' ) { $ok_count++; }
			elseif ( $module_data['status'] === 'warning' ) { $warning_count++; }
			elseif ( $module_data['status'] === 'error' ) { $error_count++; }
		}
		foreach ( $template_check_results as $template_data ) {
			if ( $template_data['file_exists'] ) { $ok_count++; } else { $warning_count++; }
		}
		foreach ( $ai_configuration_checks as $ai_data ) {
			if ( $ai_data['status'] === 'ok' ) { $ok_count++; }
			elseif ( $ai_data['status'] === 'warning' ) { $warning_count++; }
			elseif ( $ai_data['status'] === 'error' ) { $error_count++; }
		}
		foreach ( $api_check_results as $api_data ) {
			if ( $api_data['status'] === 'ok' ) { $ok_count++; }
			elseif ( $api_data['status'] === 'warning' ) { $warning_count++; }
			elseif ( $api_data['status'] === 'error' ) { $error_count++; }
		}
		return array( 'ok' => $ok_count, 'warnings' => $warning_count, 'errors' => $error_count );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __('No tienes permisos para acceder a esta pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN) );
		}
		$module_check_results    = $this->check_modules();
		$database_table_results  = $this->check_database_tables();
		$template_check_results  = $this->check_template_files( $module_check_results );
		$system_information      = $this->get_system_info( $module_check_results, $database_table_results );
		$ai_configuration_checks = $this->check_ai_configuration();
		$api_check_results       = $this->check_api_endpoints();
		$summary_counts          = $this->calculate_summary_counts( $module_check_results, $template_check_results, $ai_configuration_checks, $api_check_results );
		?>
		<div class="wrap">
			<h1>Diagnostico - Flavor Platform</h1>
			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE_SLUG ) ); ?>" class="button button-secondary">
					&#x21bb; Verificar de nuevo
				</a>
			</p>
			<?php $this->render_summary_card( $summary_counts ); ?>
			<h2>Modulos</h2>
			<?php $this->render_modules_table( $module_check_results ); ?>
			<h2>REST API</h2>
			<?php $this->render_api_table( $api_check_results ); ?>
			<h2>Base de Datos</h2>
			<?php $this->render_database_table( $database_table_results ); ?>
			<h2>Templates</h2>
			<?php $this->render_templates_table( $template_check_results ); ?>
			<h2>Sistema</h2>
			<?php $this->render_system_table( $system_information ); ?>
			<h2>IA</h2>
			<?php $this->render_ai_table( $ai_configuration_checks ); ?>
		</div>
		<?php
	}

	private function render_summary_card( $summary_counts ) {
		$card_background_color = '#f0f0f1';
		if ( $summary_counts['errors'] > 0 ) {
			$card_background_color = '#fcf0f1';
		} elseif ( $summary_counts['warnings'] > 0 ) {
			$card_background_color = '#fcf9e8';
		}
		?>
		<div style="background: <?php echo esc_attr( $card_background_color ); ?>; border: 1px solid #c3c4c7; border-radius: 4px; padding: 16px 24px; margin: 16px 0 24px 0; display: flex; gap: 32px; align-items: center;">
			<div style="display: flex; align-items: center; gap: 8px;">
				<?php echo $this->get_status_badge( 'ok' ); ?>
				<strong style="font-size: 20px;"><?php echo intval( $summary_counts['ok'] ); ?></strong>
				<span>OK</span>
			</div>
			<div style="display: flex; align-items: center; gap: 8px;">
				<?php echo $this->get_status_badge( 'warning' ); ?>
				<strong style="font-size: 20px;"><?php echo intval( $summary_counts['warnings'] ); ?></strong>
				<span>Warnings</span>
			</div>
			<div style="display: flex; align-items: center; gap: 8px;">
				<?php echo $this->get_status_badge( 'error' ); ?>
				<strong style="font-size: 20px;"><?php echo intval( $summary_counts['errors'] ); ?></strong>
				<span>Errores</span>
			</div>
		</div>
		<?php
	}

	private function render_modules_table( $module_check_results ) {
		if ( empty( $module_check_results ) ) {
			echo '<p>No se encontraron modulos registrados.</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Modulo</th><th>Archivo</th><th>Clase</th><th>Activo</th><th>Web Components</th><th>Estado</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $module_check_results as $module_identifier => $module_data ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $module_data['name'] ); ?></strong><br><code style="font-size: 11px;"><?php echo esc_html( $module_identifier ); ?></code></td>
					<td><?php echo $module_data['file_exists'] ? $this->get_status_badge( 'ok' ) : $this->get_status_badge( 'error' ); ?></td>
					<td><?php echo $module_data['class_loadable'] ? $this->get_status_badge( 'ok' ) : $this->get_status_badge( 'error' ); ?></td>
					<td><?php echo $module_data['is_active'] ? $this->get_status_badge( 'ok' ) : $this->get_status_badge( 'warning' ); ?></td>
					<td><?php echo $module_data['has_web_components'] ? 'Si' : '<span style="color:#999">No</span>'; ?></td>
					<td><?php echo $this->get_status_badge( $module_data['status'] ); ?> <?php echo esc_html( $module_data['status_message'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_database_table( $database_table_results ) {
		if ( empty( $database_table_results ) ) {
			echo '<p>No se encontraron tablas de Flavor en la base de datos.</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead><tr><th>Tabla</th><th>Filas</th></tr></thead>
			<tbody>
				<?php foreach ( $database_table_results as $table_data ) : ?>
				<tr>
					<td><code><?php echo esc_html( $table_data['table_name'] ); ?></code></td>
					<td><?php echo intval( $table_data['row_count'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_templates_table( $template_check_results ) {
		if ( empty( $template_check_results ) ) {
			echo '<p>No se encontraron modulos con componentes web que requieran plantillas.</p>';
			return;
		}
		?>
		<table class="widefat striped">
			<thead><tr><th>Modulo</th><th>Componente</th><th>Plantilla</th><th>Estado</th></tr></thead>
			<tbody>
				<?php foreach ( $template_check_results as $template_data ) : ?>
				<tr>
					<td><?php echo esc_html( $template_data['module_name'] ); ?></td>
					<td><?php echo esc_html( $template_data['component_label'] ); ?></td>
					<td><code><?php echo esc_html( $template_data['template_path'] ); ?></code></td>
					<td>
						<?php if ( $template_data['file_exists'] ) : ?>
							<?php echo $this->get_status_badge( 'ok' ); ?> Existe
						<?php else : ?>
							<?php echo $this->get_status_badge( 'warning' ); ?> No encontrada
						<?php endif; ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_system_table( $system_information ) {
		?>
		<table class="widefat striped">
			<thead><tr><th>Parametro</th><th>Valor</th></tr></thead>
			<tbody>
				<?php foreach ( $system_information as $system_item ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $system_item['label'] ); ?></strong></td>
					<td><?php echo esc_html( $system_item['value'] ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_ai_table( $ai_configuration_checks ) {
		?>
		<table class="widefat striped">
			<thead><tr><th>Proveedor</th><th>Activo</th><th>Estado</th></tr></thead>
			<tbody>
				<?php foreach ( $ai_configuration_checks as $provider_data ) : ?>
				<tr>
					<td><strong><?php echo esc_html( $provider_data['label'] ); ?></strong></td>
					<td>
						<?php if ( $provider_data['is_active'] ) : ?>
							<span style="background: #2271b1; color: #fff; padding: 2px 8px; border-radius: 3px; font-size: 12px;">Activo</span>
						<?php else : ?>
							<span style="color: #999;">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $this->get_status_badge( $provider_data['status'] ); ?>
						<?php echo esc_html( $provider_data['message'] ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function render_api_table( $api_check_results ) {
		if ( empty( $api_check_results ) ) {
			echo '<p>No se encontraron modulos para verificar.</p>';
			return;
		}
		$total_endpoints = 0;
		$modules_with_api = 0;
		foreach ( $api_check_results as $api_data ) {
			if ( $api_data['has_rest_api'] ) {
				$modules_with_api++;
				$total_endpoints += $api_data['endpoints_count'];
			}
		}
		?>
		<div style="background: #f0f6fc; border: 1px solid #c3c4c7; border-radius: 4px; padding: 12px 16px; margin-bottom: 16px; display: flex; gap: 24px;">
			<div>
				<strong style="font-size: 18px;"><?php echo intval( $modules_with_api ); ?></strong>
				<span style="color: #666;"> / <?php echo count( $api_check_results ); ?> modulos con API</span>
			</div>
			<div>
				<strong style="font-size: 18px;"><?php echo intval( $total_endpoints ); ?></strong>
				<span style="color: #666;"> endpoints totales</span>
			</div>
			<div style="margin-left: auto;">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=flavor-api-docs' ) ); ?>" class="button button-secondary" style="margin: 0;">
					Ver documentacion API
				</a>
			</div>
		</div>
		<table class="widefat striped">
			<thead>
				<tr>
					<th>Modulo</th>
					<th>REST API</th>
					<th>Endpoints</th>
					<th>Namespace</th>
					<th>Estado</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $api_check_results as $module_identifier => $api_data ) : ?>
				<tr>
					<td>
						<strong><?php echo esc_html( $api_data['name'] ); ?></strong>
						<br><code style="font-size: 11px;"><?php echo esc_html( $module_identifier ); ?></code>
					</td>
					<td>
						<?php if ( $api_data['has_rest_api'] ) : ?>
							<?php echo $this->get_status_badge( 'ok' ); ?> Si
						<?php else : ?>
							<?php echo $this->get_status_badge( 'warning' ); ?> No
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $api_data['endpoints_count'] > 0 ) : ?>
							<strong><?php echo intval( $api_data['endpoints_count'] ); ?></strong>
						<?php else : ?>
							<span style="color: #999;">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! empty( $api_data['namespace'] ) ) : ?>
							<code><?php echo esc_html( $api_data['namespace'] ); ?></code>
						<?php else : ?>
							<span style="color: #999;">-</span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo $this->get_status_badge( $api_data['status'] ); ?>
						<?php echo esc_html( $api_data['status_message'] ); ?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	private function get_status_badge( $status ) {
		$badge_colors = array(
			'ok'      => '#00a32a',
			'warning' => '#dba617',
			'error'   => '#d63638',
		);
		$badge_color = isset( $badge_colors[ $status ] ) ? $badge_colors[ $status ] : '#999';
		return sprintf(
			'<span style="display: inline-block; width: 12px; height: 12px; border-radius: 50%%; background: %s; vertical-align: middle; margin-right: 4px;" title="%s"></span>',
			esc_attr( $badge_color ),
			esc_attr( strtoupper( $status ) )
		);
	}
}
