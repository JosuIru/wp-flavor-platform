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

// Obtener instancia del módulo
$eventos_module = Flavor_Platform_Helpers::get_module_instance( 'eventos' );
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

// Tipos de evento desde settings
if ( ! empty( $settings['tipos_evento'] ) && is_array( $settings['tipos_evento'] ) ) {
	$categorias = [];
	foreach ( $settings['tipos_evento'] as $tipo ) {
		$categorias[ $tipo ] = ucfirst( $tipo );
	}
}

// Integraciones disponibles (módulos que pueden vincularse)
$integraciones_disponibles = [];
$modulos_integrables       = [ 'recetas', 'multimedia', 'podcast' ];

foreach ( $modulos_integrables as $modulo_id ) {
	$modulo = Flavor_Platform_Helpers::get_module_instance( $modulo_id );
	if ( $modulo && method_exists( $modulo, 'is_active' ) && $modulo->is_active() ) {
		$integraciones_disponibles[ $modulo_id ] = [
			'id'    => $modulo_id,
			'label' => ucfirst( $modulo_id ),
			'icon'  => 'dashicons-admin-post',
		];
	}
}
?>

<?php
// Breadcrumbs horizontales
if ( class_exists( 'Flavor_Breadcrumbs' ) ) {
	Flavor_Breadcrumbs::render_module(
		__( 'Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN ),
		admin_url( 'admin.php?page=eventos-dashboard' ),
		__( 'Nuevo evento', FLAVOR_PLATFORM_TEXT_DOMAIN )
	);
}
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

				<?php if ( ! empty( $integraciones_disponibles ) ) : ?>
				<div class="flavor-form-section">
					<h2><?php esc_html_e( 'Contenido vinculado', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></h2>
					<p class="description"><?php esc_html_e( 'Vincula recetas, podcasts u otro contenido relacionado con este evento.', FLAVOR_PLATFORM_TEXT_DOMAIN ); ?></p>

					<div class="flavor-integraciones-grid">
						<?php foreach ( $integraciones_disponibles as $integracion ) : ?>
						<div class="flavor-integracion-box" data-modulo="<?php echo esc_attr( $integracion['id'] ); ?>">
							<h4>
								<span class="dashicons <?php echo esc_attr( $integracion['icon'] ); ?>"></span>
								<?php echo esc_html( $integracion['label'] ); ?>
							</h4>
							<div class="flavor-integracion-items" data-modulo="<?php echo esc_attr( $integracion['id'] ); ?>">
								<p class="flavor-no-items"><?php printf( esc_html__( 'Sin %s vinculados', FLAVOR_PLATFORM_TEXT_DOMAIN ), esc_html( strtolower( $integracion['label'] ) ) ); ?></p>
							</div>
							<div class="flavor-integracion-add">
								<select class="flavor-integracion-selector widefat" data-modulo="<?php echo esc_attr( $integracion['id'] ); ?>">
									<option value=""><?php printf( esc_html__( 'Seleccionar %s...', FLAVOR_PLATFORM_TEXT_DOMAIN ), esc_html( strtolower( $integracion['label'] ) ) ); ?></option>
								</select>
								<button type="button" class="button flavor-btn-add-integracion" data-modulo="<?php echo esc_attr( $integracion['id'] ); ?>">
									<span class="dashicons dashicons-plus-alt2"></span>
								</button>
							</div>
						</div>
						<?php endforeach; ?>
					</div>

					<input type="hidden" id="evento-integraciones" name="integraciones" value="">
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

/* Integraciones */
.flavor-integraciones-grid {
	display: grid;
	gap: 16px;
}

.flavor-integracion-box {
	border: 1px solid #ddd;
	border-radius: 6px;
	padding: 12px;
	background: #f9f9f9;
}

.flavor-integracion-box h4 {
	margin: 0 0 10px 0;
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	color: #1d2327;
}

.flavor-integracion-box h4 .dashicons {
	font-size: 16px;
	width: 16px;
	height: 16px;
	color: #2271b1;
}

.flavor-integracion-items {
	max-height: 150px;
	overflow-y: auto;
	margin-bottom: 10px;
	padding: 8px;
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.flavor-no-items {
	color: #646970;
	font-size: 12px;
	font-style: italic;
	margin: 0;
}

.flavor-integracion-item {
	display: flex;
	justify-content: space-between;
	align-items: center;
	padding: 4px 0;
	border-bottom: 1px solid #eee;
}

.flavor-integracion-item:last-child {
	border-bottom: 0;
}

.flavor-integracion-add {
	display: flex;
	gap: 6px;
}

.flavor-integracion-add select {
	flex: 1;
}

.flavor-integracion-add .button {
	padding: 0 8px;
}

.flavor-integracion-add .button .dashicons {
	vertical-align: middle;
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
