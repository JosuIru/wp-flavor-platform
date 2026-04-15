<?php
/**
 * Vista: Nuevo Evento
 * Formulario para crear un nuevo evento
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener categorías disponibles
$categorias = [
    'cultura' => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'deporte' => __('Deporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'formacion' => __('Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'social' => __('Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'asamblea' => __('Asamblea', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'taller' => __('Taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otro' => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="flavor-nuevo-evento">
    <form id="form-nuevo-evento" class="flavor-form">
        <?php wp_nonce_field('flavor_eventos_nonce', 'nonce'); ?>

        <div class="flavor-form-section">
            <h2><?php esc_html_e('Información básica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-form-group">
                <label for="evento-titulo"><?php esc_html_e('Título del evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                <input type="text" id="evento-titulo" name="titulo" required class="widefat" placeholder="<?php esc_attr_e('Ej: Asamblea General de Socios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="flavor-form-group">
                <label for="evento-descripcion"><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="evento-descripcion" name="descripcion" rows="5" class="widefat" placeholder="<?php esc_attr_e('Describe el evento, actividades, qué traer...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="evento-tipo"><?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="evento-tipo" name="tipo" class="widefat">
                        <?php foreach ($categorias as $value => $label) : ?>
                            <option value="<?php echo esc_attr($value); ?>"><?php echo esc_html($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="flavor-form-group">
                    <label for="evento-estado"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <select id="evento-estado" name="estado" class="widefat">
                        <option value="borrador"><?php esc_html_e('Borrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="publicado"><?php esc_html_e('Publicado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>
            </div>
        </div>

        <div class="flavor-form-section">
            <h2><?php esc_html_e('Fecha y hora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="evento-fecha-inicio"><?php esc_html_e('Fecha de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <input type="date" id="evento-fecha-inicio" name="fecha_inicio" required class="widefat">
                </div>
                <div class="flavor-form-group">
                    <label for="evento-hora-inicio"><?php esc_html_e('Hora de inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="required">*</span></label>
                    <input type="time" id="evento-hora-inicio" name="hora_inicio" required class="widefat">
                </div>
            </div>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="evento-fecha-fin"><?php esc_html_e('Fecha de fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="date" id="evento-fecha-fin" name="fecha_fin" class="widefat">
                </div>
                <div class="flavor-form-group">
                    <label for="evento-hora-fin"><?php esc_html_e('Hora de fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="time" id="evento-hora-fin" name="hora_fin" class="widefat">
                </div>
            </div>
        </div>

        <div class="flavor-form-section">
            <h2><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-form-group">
                <label>
                    <input type="checkbox" id="evento-es-online" name="es_online" value="1">
                    <?php esc_html_e('Este es un evento online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <div id="ubicacion-fisica">
                <div class="flavor-form-group">
                    <label for="evento-ubicacion"><?php esc_html_e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" id="evento-ubicacion" name="ubicacion" class="widefat" placeholder="<?php esc_attr_e('Ej: Calle Mayor 15, Local 2', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div id="ubicacion-online" style="display:none;">
                <div class="flavor-form-group">
                    <label for="evento-url-online"><?php esc_html_e('Enlace de la reunión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="url" id="evento-url-online" name="url_online" class="widefat" placeholder="<?php esc_attr_e('https://meet.google.com/...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>
        </div>

        <div class="flavor-form-section">
            <h2><?php esc_html_e('Inscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="evento-aforo"><?php esc_html_e('Aforo máximo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="evento-aforo" name="aforo_maximo" min="0" class="widefat" placeholder="<?php esc_attr_e('Dejar vacío para sin límite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
                <div class="flavor-form-group">
                    <label for="evento-precio"><?php esc_html_e('Precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="evento-precio" name="precio" min="0" step="0.01" class="widefat" placeholder="<?php esc_attr_e('0 = Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                </div>
            </div>

            <div class="flavor-form-group">
                <label>
                    <input type="checkbox" id="evento-requiere-inscripcion" name="requiere_inscripcion" value="1" checked>
                    <?php esc_html_e('Requiere inscripción previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>
        </div>

        <div class="flavor-form-section">
            <h2><?php esc_html_e('Imagen destacada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <div class="flavor-form-group">
                <div id="evento-imagen-preview" class="flavor-image-preview"></div>
                <input type="hidden" id="evento-imagen-id" name="imagen_id">
                <button type="button" id="btn-seleccionar-imagen" class="button"><?php esc_html_e('Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                <button type="button" id="btn-quitar-imagen" class="button" style="display:none;"><?php esc_html_e('Quitar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        </div>

        <div class="flavor-form-actions">
            <a href="<?php echo esc_url(admin_url('admin.php?page=eventos-dashboard')); ?>" class="button"><?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
            <button type="submit" class="button button-primary button-large"><?php esc_html_e('Crear Evento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>
    </form>
</div>

<style>
.flavor-nuevo-evento {
    max-width: 800px;
    margin: 20px 0;
}
.flavor-form-section {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
}
.flavor-form-section h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
    font-size: 16px;
}
.flavor-form-group {
    margin-bottom: 15px;
}
.flavor-form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.flavor-form-group .required {
    color: #d63638;
}
.flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}
.flavor-form-actions {
    display: flex;
    gap: 10px;
    justify-content: flex-end;
    padding: 20px 0;
}
.flavor-image-preview {
    width: 200px;
    height: 150px;
    border: 2px dashed #ddd;
    border-radius: 8px;
    margin-bottom: 10px;
    background-size: cover;
    background-position: center;
}
.flavor-image-preview.has-image {
    border-style: solid;
}
@media (max-width: 782px) {
    .flavor-form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
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

    // Selector de imagen
    var mediaUploader;
    $('#btn-seleccionar-imagen').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media({
            title: '<?php echo esc_js(__('Seleccionar imagen del evento', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
            button: { text: '<?php echo esc_js(__('Usar esta imagen', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>' },
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
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: $(this).serialize() + '&action=eventos_guardar_evento',
            success: function(response) {
                if (response.success) {
                    window.location.href = '<?php echo esc_url(admin_url('admin.php?page=eventos-dashboard&mensaje=creado')); ?>';
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error al crear el evento', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $btn.prop('disabled', false).text(btnText);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $btn.prop('disabled', false).text(btnText);
            }
        });
    });
});
</script>
