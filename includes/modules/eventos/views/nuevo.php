<?php
/**
 * Vista: Nuevo Evento
 * Formulario para crear un nuevo evento
 *
 * @package FlavorPlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Helper para obtener instancias de módulos.
$module_loader = Flavor_Platform_Module_Loader::get_instance();

// Obtener instancia del módulo
$eventos_module = $module_loader->get_module_instance( 'eventos' );
$settings       = $eventos_module ? $eventos_module->get_settings() : [];

// Obtener categorías disponibles
$categorias = [
	'cultura'   => __( 'Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'deporte'   => __( 'Deporte', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'formacion' => __( 'Formación', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'social'    => __( 'Social', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'asamblea'  => __( 'Asamblea', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'taller'    => __( 'Taller', FLAVOR_PLATFORM_TEXT_DOMAIN ),
	'otro'      => __( 'Otro', FLAVOR_PLATFORM_TEXT_DOMAIN ),
];

// Tipos de evento desde settings.
if ( ! empty( $settings['tipos_evento'] ) && is_array( $settings['tipos_evento'] ) ) {
	$categorias = [];
	foreach ( $settings['tipos_evento'] as $tipo ) {
		$categorias[ $tipo ] = ucfirst( $tipo );
	}
}

// Obtener todas las entidades vinculables (solo si sus módulos están activos).
global $wpdb;

// Comunidades.
$comunidades_disponibles = [];
$comunidades_module      = $module_loader->get_module_instance( 'comunidades' );
if ( $comunidades_module && method_exists( $comunidades_module, 'is_active' ) && $comunidades_module->is_active() ) {
	$tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_comunidades ) ) === $tabla_comunidades ) {
		$comunidades_disponibles = $wpdb->get_results(
			"SELECT id, nombre FROM {$tabla_comunidades} WHERE estado = 'activa' ORDER BY nombre ASC",
			ARRAY_A
		);
	}
}

// Colectivos.
$colectivos_disponibles = [];
$colectivos_module      = $module_loader->get_module_instance( 'colectivos' );
if ( $colectivos_module && method_exists( $colectivos_module, 'is_active' ) && $colectivos_module->is_active() ) {
	$tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_colectivos ) ) === $tabla_colectivos ) {
		$colectivos_disponibles = $wpdb->get_results(
			"SELECT id, nombre FROM {$tabla_colectivos} WHERE estado = 'activo' ORDER BY nombre ASC",
			ARRAY_A
		);
	}
}

// Grupos de consumo.
$grupos_consumo_disponibles = [];
$grupos_consumo_module      = $module_loader->get_module_instance( 'grupos-consumo' );
if ( $grupos_consumo_module && method_exists( $grupos_consumo_module, 'is_active' ) && $grupos_consumo_module->is_active() ) {
	$tabla_grupos = $wpdb->prefix . 'flavor_gc_grupos';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_grupos ) ) === $tabla_grupos ) {
		$grupos_consumo_disponibles = $wpdb->get_results(
			"SELECT id, nombre FROM {$tabla_grupos} WHERE estado = 'activo' ORDER BY nombre ASC",
			ARRAY_A
		);
	}
}

// Proyectos (de colectivos).
$proyectos_disponibles = [];
if ( $colectivos_module && method_exists( $colectivos_module, 'is_active' ) && $colectivos_module->is_active() ) {
	$tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_proyectos ) ) === $tabla_proyectos ) {
		$proyectos_disponibles = $wpdb->get_results(
			"SELECT id, titulo AS nombre FROM {$tabla_proyectos} WHERE estado IN ('planificado', 'en_curso') ORDER BY titulo ASC",
			ARRAY_A
		);
	}
}

// Talleres.
$talleres_disponibles = [];
$talleres_module      = $module_loader->get_module_instance( 'talleres' );
if ( $talleres_module && method_exists( $talleres_module, 'is_active' ) && $talleres_module->is_active() ) {
	$tabla_talleres = $wpdb->prefix . 'flavor_talleres';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $tabla_talleres ) ) === $tabla_talleres ) {
		$talleres_disponibles = $wpdb->get_results(
			"SELECT id, titulo AS nombre FROM {$tabla_talleres} WHERE estado = 'publicado' ORDER BY titulo ASC",
			ARRAY_A
		);
	}
}

// Cursos (CPT).
$cursos_disponibles = [];
$cursos_module      = $module_loader->get_module_instance( 'cursos' );
if ( $cursos_module && method_exists( $cursos_module, 'is_active' ) && $cursos_module->is_active() ) {
	$cursos_query = new WP_Query(
		[
			'post_type'      => 'flavor_curso',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		]
	);
	if ( $cursos_query->have_posts() ) {
		foreach ( $cursos_query->posts as $curso_post ) {
			$cursos_disponibles[] = [
				'id'     => $curso_post->ID,
				'nombre' => $curso_post->post_title,
			];
		}
	}
	wp_reset_postdata();
}

// Verificar si hay alguna vinculación disponible.
$hay_vinculaciones = ! empty( $comunidades_disponibles )
	|| ! empty( $colectivos_disponibles )
	|| ! empty( $grupos_consumo_disponibles )
	|| ! empty( $proyectos_disponibles )
	|| ! empty( $talleres_disponibles )
	|| ! empty( $cursos_disponibles );

// Contextos desde GET (si viene de una entidad específica).
$comunidad_contexto      = absint( $_GET['comunidad_id'] ?? $_GET['comunidad'] ?? 0 );
$colectivo_contexto      = absint( $_GET['colectivo_id'] ?? $_GET['colectivo'] ?? 0 );
$grupo_consumo_contexto  = absint( $_GET['grupo_consumo_id'] ?? $_GET['grupo'] ?? 0 );
$proyecto_contexto       = absint( $_GET['proyecto_id'] ?? $_GET['proyecto'] ?? 0 );
$taller_contexto         = absint( $_GET['taller_id'] ?? $_GET['taller'] ?? 0 );
$curso_contexto          = absint( $_GET['curso_id'] ?? $_GET['curso'] ?? 0 );
?>

<div class="flavor-nuevo-evento">
	<form id="form-nuevo-evento" class="flavor-form">
		<?php wp_nonce_field( 'flavor_eventos_nonce', 'nonce' ); ?>

		<div class="flavor-form-grid">
			<!-- Columna principal -->
			<div class="flavor-form-main">

				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Información básica', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>

					<div class="flavor-form-group">
						<label for="evento-titulo"><?php esc_html_e( 'Título del evento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
						<input type="text" id="evento-titulo" name="titulo" required class="widefat" placeholder="<?php esc_attr_e( 'Ej: Asamblea General de Socios', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
					</div>

					<div class="flavor-form-group">
						<label for="evento-descripcion"><?php esc_html_e( 'Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<textarea id="evento-descripcion" name="descripcion" rows="5" class="widefat" placeholder="<?php esc_attr_e( 'Describe el evento, actividades, qué traer...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>"></textarea>
					</div>

					<div class="flavor-form-row">
						<div class="flavor-form-group">
							<label for="evento-tipo"><?php esc_html_e( 'Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<select id="evento-tipo" name="tipo" class="widefat">
								<?php foreach ( $categorias as $valor => $etiqueta ) : ?>
									<option value="<?php echo esc_attr( $valor ); ?>"><?php echo esc_html( $etiqueta ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
						<div class="flavor-form-group">
							<label for="evento-estado"><?php esc_html_e( 'Estado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<select id="evento-estado" name="estado" class="widefat">
								<option value="borrador"><?php esc_html_e( 'Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
								<option value="publicado"><?php esc_html_e( 'Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							</select>
						</div>
					</div>
				</div>

				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Fecha y hora', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>

					<div class="flavor-form-row">
						<div class="flavor-form-group">
							<label for="evento-fecha-inicio"><?php esc_html_e( 'Fecha de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
							<input type="date" id="evento-fecha-inicio" name="fecha_inicio" required class="widefat">
						</div>
						<div class="flavor-form-group">
							<label for="evento-hora-inicio"><?php esc_html_e( 'Hora de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
							<input type="time" id="evento-hora-inicio" name="hora_inicio" required class="widefat">
						</div>
					</div>

					<div class="flavor-form-row">
						<div class="flavor-form-group">
							<label for="evento-fecha-fin"><?php esc_html_e( 'Fecha de fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<input type="date" id="evento-fecha-fin" name="fecha_fin" class="widefat">
						</div>
						<div class="flavor-form-group">
							<label for="evento-hora-fin"><?php esc_html_e( 'Hora de fin', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<input type="time" id="evento-hora-fin" name="hora_fin" class="widefat">
						</div>
					</div>
				</div>

				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>

					<div class="flavor-form-group">
						<label class="flavor-checkbox-label">
							<input type="checkbox" id="evento-es-online" name="es_online" value="1">
							<?php esc_html_e( 'Este es un evento online', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
						</label>
					</div>

					<div id="ubicacion-fisica">
						<div class="flavor-form-group">
							<label for="evento-ubicacion"><?php esc_html_e( 'Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<div class="flavor-input-with-action">
								<input type="text" id="evento-ubicacion" name="ubicacion" class="widefat" placeholder="<?php esc_attr_e( 'Ej: Calle Mayor 15, Bilbao', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
								<button type="button" id="btn-geocodificar" class="button" title="<?php esc_attr_e( 'Obtener coordenadas', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
									<span class="dashicons dashicons-location"></span>
								</button>
							</div>
							<p class="description"><?php esc_html_e( 'Escribe la dirección y pulsa el icono para obtener las coordenadas automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>
						</div>

						<div class="flavor-form-row">
							<div class="flavor-form-group">
								<label for="evento-lat"><?php esc_html_e( 'Latitud', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
								<input type="text" id="evento-lat" name="coordenadas_lat" class="widefat" placeholder="43.2630" readonly>
							</div>
							<div class="flavor-form-group">
								<label for="evento-lng"><?php esc_html_e( 'Longitud', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
								<input type="text" id="evento-lng" name="coordenadas_lng" class="widefat" placeholder="-2.9350" readonly>
							</div>
						</div>

						<div id="mapa-preview" class="flavor-mapa-preview" style="display: none;">
							<div class="flavor-mapa-placeholder">
								<span class="dashicons dashicons-location-alt"></span>
								<span id="mapa-direccion"></span>
							</div>
						</div>
					</div>

					<div id="ubicacion-online" style="display: none;">
						<div class="flavor-form-group">
							<label for="evento-url-online"><?php esc_html_e( 'Enlace de la reunión', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<input type="url" id="evento-url-online" name="url_online" class="widefat" placeholder="<?php esc_attr_e( 'https://meet.google.com/...', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
						</div>
					</div>
				</div>

				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>

					<div class="flavor-form-row">
						<div class="flavor-form-group">
							<label for="evento-aforo"><?php esc_html_e( 'Aforo máximo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<input type="number" id="evento-aforo" name="aforo_maximo" min="0" class="widefat" placeholder="<?php esc_attr_e( 'Dejar vacío para sin límite', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
						</div>
						<div class="flavor-form-group">
							<label for="evento-precio"><?php esc_html_e( 'Precio (€)', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
							<input type="number" id="evento-precio" name="precio" min="0" step="0.01" class="widefat" placeholder="<?php esc_attr_e( '0 = Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>">
						</div>
					</div>

					<div class="flavor-form-group">
						<label class="flavor-checkbox-label">
							<input type="checkbox" id="evento-requiere-inscripcion" name="requiere_inscripcion" value="1" checked>
							<?php esc_html_e( 'Requiere inscripción previa', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?>
						</label>
					</div>
				</div>

				<?php if ( $hay_vinculaciones ) : ?>
				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Vinculaciones', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
					<p class="description"><?php esc_html_e( 'Asocia este evento a entidades del ecosistema.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>

					<?php if ( ! empty( $comunidades_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-comunidad"><?php esc_html_e( 'Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-comunidad" name="comunidad_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguna —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $comunidades_disponibles as $comunidad ) : ?>
								<option value="<?php echo esc_attr( $comunidad['id'] ); ?>" <?php selected( $comunidad_contexto, $comunidad['id'] ); ?>>
									<?php echo esc_html( $comunidad['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $colectivos_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-colectivo"><?php esc_html_e( 'Colectivo organizador', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-colectivo" name="colectivo_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguno —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $colectivos_disponibles as $colectivo ) : ?>
								<option value="<?php echo esc_attr( $colectivo['id'] ); ?>" <?php selected( $colectivo_contexto, $colectivo['id'] ); ?>>
									<?php echo esc_html( $colectivo['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $grupos_consumo_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-grupo-consumo"><?php esc_html_e( 'Grupo de consumo', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-grupo-consumo" name="grupo_consumo_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguno —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $grupos_consumo_disponibles as $grupo ) : ?>
								<option value="<?php echo esc_attr( $grupo['id'] ); ?>" <?php selected( $grupo_consumo_contexto, $grupo['id'] ); ?>>
									<?php echo esc_html( $grupo['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $proyectos_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-proyecto"><?php esc_html_e( 'Proyecto', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-proyecto" name="proyecto_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguno —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $proyectos_disponibles as $proyecto ) : ?>
								<option value="<?php echo esc_attr( $proyecto['id'] ); ?>" <?php selected( $proyecto_contexto, $proyecto['id'] ); ?>>
									<?php echo esc_html( $proyecto['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $talleres_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-taller"><?php esc_html_e( 'Taller asociado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-taller" name="taller_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguno —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $talleres_disponibles as $taller ) : ?>
								<option value="<?php echo esc_attr( $taller['id'] ); ?>" <?php selected( $taller_contexto, $taller['id'] ); ?>>
									<?php echo esc_html( $taller['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>

					<?php if ( ! empty( $cursos_disponibles ) ) : ?>
					<div class="flavor-form-group">
						<label for="evento-curso"><?php esc_html_e( 'Curso asociado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></label>
						<select id="evento-curso" name="curso_id" class="widefat">
							<option value=""><?php esc_html_e( '— Ninguno —', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></option>
							<?php foreach ( $cursos_disponibles as $curso ) : ?>
								<option value="<?php echo esc_attr( $curso['id'] ); ?>" <?php selected( $curso_contexto, $curso['id'] ); ?>>
									<?php echo esc_html( $curso['nombre'] ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<?php endif; ?>
				</div>
				<?php endif; ?>

			</div>

			<!-- Columna lateral -->
			<div class="flavor-form-sidebar">

				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Imagen destacada', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>

					<div class="flavor-form-group">
						<div id="evento-imagen-preview" class="flavor-image-preview"></div>
						<input type="hidden" id="evento-imagen-id" name="imagen_id">
						<div class="flavor-image-buttons">
							<button type="button" id="btn-seleccionar-imagen" class="button"><?php esc_html_e( 'Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
							<button type="button" id="btn-quitar-imagen" class="button" style="display: none;"><?php esc_html_e( 'Quitar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
						</div>
					</div>
				</div>

				<div class="flavor-form-section flavor-form-actions-box">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=eventos-dashboard' ) ); ?>" class="button button-large"><?php esc_html_e( 'Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></a>
					<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Crear Evento', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></button>
				</div>

			</div>
		</div>
	</form>
</div>

<style>
.flavor-nuevo-evento {
	margin: 20px 0;
}

.flavor-form-grid {
	display: grid;
	grid-template-columns: 1fr 320px;
	gap: 24px;
	align-items: start;
}

.flavor-form-main {
	min-width: 0;
}

.flavor-form-sidebar {
	position: sticky;
	top: 32px;
}

.flavor-form-section {
	background: #fff;
	border: 1px solid #c3c4c7;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.flavor-form-section h2 {
	margin: 0 0 16px 0;
	padding-bottom: 12px;
	border-bottom: 1px solid #eee;
	font-size: 15px;
	font-weight: 600;
	color: #1d2327;
}

.flavor-form-group {
	margin-bottom: 16px;
}

.flavor-form-group:last-child {
	margin-bottom: 0;
}

.flavor-form-group label {
	display: block;
	margin-bottom: 6px;
	font-weight: 600;
	color: #1d2327;
}

.flavor-form-group .required {
	color: #d63638;
}

.flavor-form-group .description {
	font-size: 12px;
	color: #646970;
	margin-top: 4px;
}

.flavor-form-row {
	display: grid;
	grid-template-columns: 1fr 1fr;
	gap: 16px;
}

.flavor-checkbox-label {
	display: flex !important;
	align-items: center;
	gap: 8px;
	font-weight: 400 !important;
	cursor: pointer;
}

.flavor-checkbox-label input[type="checkbox"] {
	margin: 0;
}

.flavor-input-with-action {
	display: flex;
	gap: 8px;
}

.flavor-input-with-action input {
	flex: 1;
}

.flavor-input-with-action .button {
	padding: 0 10px;
	display: flex;
	align-items: center;
}

.flavor-input-with-action .button .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

.flavor-mapa-preview {
	margin-top: 12px;
	border: 1px solid #ddd;
	border-radius: 6px;
	overflow: hidden;
}

.flavor-mapa-placeholder {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 12px 16px;
	background: #f0f6fc;
	color: #2271b1;
}

.flavor-mapa-placeholder .dashicons {
	font-size: 20px;
	width: 20px;
	height: 20px;
}


/* Imagen */
.flavor-image-preview {
	width: 100%;
	height: 180px;
	border: 2px dashed #c3c4c7;
	border-radius: 8px;
	margin-bottom: 12px;
	background-size: cover;
	background-position: center;
	background-color: #f6f7f7;
	display: flex;
	align-items: center;
	justify-content: center;
}

.flavor-image-preview.has-image {
	border-style: solid;
}

.flavor-image-buttons {
	display: flex;
	gap: 8px;
}

/* Acciones */
.flavor-form-actions-box {
	display: flex;
	flex-direction: column;
	gap: 10px;
}

.flavor-form-actions-box .button {
	width: 100%;
	text-align: center;
	justify-content: center;
}

/* Responsive */
@media (max-width: 1200px) {
	.flavor-form-grid {
		grid-template-columns: 1fr 280px;
	}
}

@media (max-width: 960px) {
	.flavor-form-grid {
		grid-template-columns: 1fr;
	}

	.flavor-form-sidebar {
		position: static;
	}
}

@media (max-width: 782px) {
	.flavor-form-row {
		grid-template-columns: 1fr;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	'use strict';

	// Toggle ubicación física/online
	$('#evento-es-online').on('change', function() {
		if ($(this).is(':checked')) {
			$('#ubicacion-fisica').hide();
			$('#ubicacion-online').show();
		} else {
			$('#ubicacion-fisica').show();
			$('#ubicacion-online').hide();
		}
	});

	// Geocodificación con Nominatim (OpenStreetMap)
	$('#btn-geocodificar').on('click', function() {
		var direccion = $('#evento-ubicacion').val().trim();
		if (!direccion) {
			alert('<?php echo esc_js( __( 'Introduce una dirección primero', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
			return;
		}

		var $btn = $(this);
		$btn.prop('disabled', true).find('.dashicons').removeClass('dashicons-location').addClass('dashicons-update spin');

		$.ajax({
			url: 'https://nominatim.openstreetmap.org/search',
			data: {
				q: direccion,
				format: 'json',
				limit: 1
			},
			headers: {
				'Accept-Language': 'es'
			},
			success: function(data) {
				if (data && data.length > 0) {
					var resultado = data[0];
					$('#evento-lat').val(resultado.lat);
					$('#evento-lng').val(resultado.lon);
					$('#mapa-direccion').text(resultado.display_name);
					$('#mapa-preview').slideDown();
				} else {
					alert('<?php echo esc_js( __( 'No se encontraron coordenadas para esa dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'Error al geocodificar la dirección', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
			},
			complete: function() {
				$btn.prop('disabled', false).find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-location');
			}
		});
	});

	// Selector de imagen
	var mediaUploader;
	$('#btn-seleccionar-imagen').on('click', function(e) {
		e.preventDefault();
		if (mediaUploader) {
			mediaUploader.open();
			return;
		}
		mediaUploader = wp.media({
			title: '<?php echo esc_js( __( 'Seleccionar imagen del evento', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>',
			button: { text: '<?php echo esc_js( __( 'Usar esta imagen', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>' },
			multiple: false
		});
		mediaUploader.on('select', function() {
			var attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#evento-imagen-id').val(attachment.id);
			$('#evento-imagen-preview').css('background-image', 'url(' + attachment.url + ')').addClass('has-image');
			$('#btn-quitar-imagen').show();
		});
		mediaUploader.open();
	});

	$('#btn-quitar-imagen').on('click', function() {
		$('#evento-imagen-id').val('');
		$('#evento-imagen-preview').css('background-image', '').removeClass('has-image');
		$(this).hide();
	});

	// Enviar formulario
	$('#form-nuevo-evento').on('submit', function(e) {
		e.preventDefault();

		var $btn = $(this).find('button[type="submit"]');
		var btnText = $btn.text();
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');

		$.ajax({
			url: ajaxurl,
			method: 'POST',
			data: $(this).serialize() + '&action=eventos_guardar_evento',
			success: function(response) {
				if (response.success) {
					window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=eventos-dashboard&mensaje=creado' ) ); ?>';
				} else {
					alert(response.data.message || '<?php echo esc_js( __( 'Error al crear el evento', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
					$btn.prop('disabled', false).text(btnText);
				}
			},
			error: function() {
				alert('<?php echo esc_js( __( 'Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN ) ); ?>');
				$btn.prop('disabled', false).text(btnText);
			}
		});
	});

	// CSS para animación spin
	$('<style>.dashicons.spin { animation: spin 1s linear infinite; } @keyframes spin { 100% { transform: rotate(360deg); } }</style>').appendTo('head');
});
</script>
