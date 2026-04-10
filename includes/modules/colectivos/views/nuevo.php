<?php
/**
 * Vista admin: Nuevo Colectivo
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener módulo colectivos
$modulo_colectivos = Flavor_Platform_Helpers::get_module_instance('colectivos');

// Variables requeridas por crear-colectivo.php
$identificador_usuario = get_current_user_id();

// Tipos de colectivo
$tipos_disponibles = [
    'asociacion'  => __('Asociación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cooperativa' => __('Cooperativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'ong'         => __('ONG', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'colectivo'   => __('Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'plataforma'  => __('Plataforma', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'fundacion'   => __('Fundación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'sindicato'   => __('Sindicato', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'vecinal'     => __('Asociación Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
];

if ($modulo_colectivos) {
    $settings = $modulo_colectivos->get_settings();
    if (!empty($settings['tipos_permitidos'])) {
        $tipos_filtrados = [];
        foreach ($settings['tipos_permitidos'] as $tipo_key) {
            if (isset($tipos_disponibles[$tipo_key])) {
                $tipos_filtrados[$tipo_key] = $tipos_disponibles[$tipo_key];
            }
        }
        if (!empty($tipos_filtrados)) {
            $tipos_disponibles = $tipos_filtrados;
        }
    }
}

// Sectores de actividad
$sectores_disponibles = [
    'cultura'           => __('Cultura y Arte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'social'            => __('Acción Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'medioambiente'     => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'educacion'         => __('Educación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'deporte'           => __('Deporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'salud'             => __('Salud', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'tecnologia'        => __('Tecnología', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'comercio'          => __('Comercio y Servicios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'agricultura'       => __('Agricultura y Alimentación', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'vivienda'          => __('Vivienda', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'comunicacion'      => __('Comunicación y Medios', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'feminismo'         => __('Feminismo e Igualdad', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'migraciones'       => __('Migraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'memoria'           => __('Memoria Histórica', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'diversidad'        => __('Diversidad LGBTIQ+', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'antimilitarismo'   => __('Paz y Antimilitarismo', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'laboral'           => __('Derechos Laborales', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'juventud'          => __('Juventud', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'mayores'           => __('Personas Mayores', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'discapacidad'      => __('Discapacidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otro'              => __('Otro', FLAVOR_PLATFORM_TEXT_DOMAIN),
];
?>

<div class="wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-groups" style="margin-right: 8px;"></span>
        <?php esc_html_e('Nuevo Colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <a href="<?php echo esc_url(admin_url('admin.php?page=colectivos')); ?>" class="page-title-action">
        <?php esc_html_e('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </a>

    <hr class="wp-header-end">

    <div class="flavor-admin-card" style="background: #fff; padding: 20px; border-radius: 8px; margin-top: 20px; border: 1px solid #c3c4c7;">
        <?php include dirname(__FILE__) . '/crear-colectivo.php'; ?>
    </div>
</div>

<style>
.flavor-col-crear {
    max-width: 800px;
}

.flavor-col-form-section {
    background: #f9f9f9;
    padding: 20px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.flavor-col-form-section h3 {
    margin-top: 0;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
    color: #1d2327;
}

.flavor-col-campo {
    margin-bottom: 15px;
}

.flavor-col-campo label {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
    color: #1d2327;
}

.flavor-col-campo-doble {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

@media (max-width: 782px) {
    .flavor-col-campo-doble {
        grid-template-columns: 1fr;
    }
}

.flavor-col-input,
.flavor-col-select,
.flavor-col-textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #8c8f94;
    border-radius: 4px;
    font-size: 14px;
    box-sizing: border-box;
}

.flavor-col-input:focus,
.flavor-col-select:focus,
.flavor-col-textarea:focus {
    border-color: #2271b1;
    box-shadow: 0 0 0 1px #2271b1;
    outline: none;
}

.flavor-col-textarea {
    resize: vertical;
    min-height: 80px;
}

.flavor-col-form-acciones {
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}

.flavor-col-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
}

.flavor-col-btn-primary {
    background: #2271b1;
    color: #fff;
}

.flavor-col-btn-primary:hover {
    background: #135e96;
    color: #fff;
}

.flavor-col-btn-lg {
    padding: 12px 24px;
    font-size: 15px;
}

.flavor-col-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-col-exito {
    text-align: center;
    padding: 40px;
    background: #d4edda;
    border: 1px solid #c3e6cb;
    border-radius: 8px;
}

.flavor-col-exito .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #28a745;
}

.flavor-col-exito h3 {
    color: #155724;
    margin: 15px 0 10px;
}

.flavor-col-exito p {
    color: #155724;
    margin-bottom: 20px;
}

.flavor-col-login-requerido {
    text-align: center;
    padding: 40px;
    background: #f8f9fa;
    border-radius: 8px;
}

.flavor-col-login-requerido .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #6c757d;
}
</style>

<script>
jQuery(document).ready(function($) {
    var $form = $('#flavor-col-form-crear');
    var $btnCrear = $('#col-crear-btn');
    var $mensajeExito = $('#col-mensaje-exito');

    $form.on('submit', function(e) {
        e.preventDefault();

        var formData = {
            nombre: $('#col-nombre').val().trim(),
            tipo: $('#col-tipo').val(),
            sector: $('#col-sector').val(),
            descripcion: $('#col-descripcion').val().trim(),
            email_contacto: $('#col-email').val().trim(),
            telefono: $('#col-telefono').val().trim(),
            direccion: $('#col-direccion').val().trim(),
            web: $('#col-web').val().trim()
        };

        // Validación básica
        if (!formData.nombre || !formData.tipo || !formData.descripcion) {
            alert('<?php echo esc_js(__('Por favor, completa los campos obligatorios.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
            return;
        }

        $btnCrear.prop('disabled', true).html(
            '<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Creando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
        );

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'colectivos_crear',
                nonce: '<?php echo wp_create_nonce('flavor_colectivos_nonce'); ?>',
                ...formData
            },
            success: function(response) {
                if (response.success) {
                    $form.hide();
                    $mensajeExito.show();

                    window.colectivoCreado = {
                        id: response.data.colectivo_id,
                        url: '<?php echo esc_js(admin_url('admin.php?page=colectivos-detalle&colectivo_id=')); ?>' + response.data.colectivo_id
                    };
                } else {
                    alert(response.data.message || '<?php echo esc_js(__('Error al crear el colectivo.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                    $btnCrear.prop('disabled', false).html(
                        '<span class="dashicons dashicons-networking"></span> <?php echo esc_js(__('Crear colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
                    );
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexión. Inténtalo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>');
                $btnCrear.prop('disabled', false).html(
                    '<span class="dashicons dashicons-networking"></span> <?php echo esc_js(__('Crear colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
                );
            }
        });
    });
});
</script>
