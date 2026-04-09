<?php
/**
 * Vista del editor de campanas de Newsletter
 *
 * @package FlavorChatIA
 * @subpackage Newsletter
 * @since 3.1.0
 *
 * Variables disponibles:
 * @var object|null $datos_campana  Datos de la campana existente o null para nueva
 * @var int         $identificador_campana  ID de la campana (0 si nueva)
 */

if (!defined('ABSPATH')) { exit; }

$asunto_actual    = $datos_campana->asunto ?? '';
$contenido_actual = $datos_campana->contenido_html ?? '';
$estado_actual    = $datos_campana->estado ?? 'borrador';
$es_nueva_campana = empty($identificador_campana) || $identificador_campana <= 0;

if ($es_nueva_campana && empty($contenido_actual)) {
    $motor_plantillas = Flavor_Newsletter_Template::get_instance();
    $contenido_actual = $motor_plantillas->obtener_plantilla_por_defecto();
}

$titulo_pagina = $es_nueva_campana
    ? __('Crear campana', FLAVOR_PLATFORM_TEXT_DOMAIN)
    : sprintf(__('Editar campana #%d', FLAVOR_PLATFORM_TEXT_DOMAIN), $identificador_campana);
?>
<div class="wrap">
    <h1><?php echo esc_html($titulo_pagina); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-newsletter')); ?>" class="button">&laquo; <?php esc_html_e('Volver al listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
    <hr class="wp-header-end">

    <form id="flavor-newsletter-editor-form" method="post">
        <input type="hidden" name="campana_id" id="campana_id" value="<?php echo intval($identificador_campana); ?>">

        <table class="form-table">
            <tr>
                <th><label for="campana_asunto"><?php esc_html_e('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td><input type="text" id="campana_asunto" name="asunto" class="regular-text large-text" value="<?php echo esc_attr($asunto_actual); ?>" required></td>
            </tr>
            <tr>
                <th><label for="campana_contenido"><?php esc_html_e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                <td>
                    <?php
                    wp_editor($contenido_actual, 'campana_contenido', [
                        'textarea_name' => 'contenido_html',
                        'textarea_rows' => 20,
                        'media_buttons' => true,
                        'tinymce'        => true,
                        'quicktags'     => true,
                    ]);
                    ?>
                    <p class="description">
                        <?php esc_html_e('Variables disponibles:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <code><?php echo esc_html__('{{nombre}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>, <code><?php echo esc_html__('{{email}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>, <code><?php echo esc_html__('{{sitio_nombre}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>, <code><?php echo esc_html__('{{sitio_url}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>, <code><?php echo esc_html__('{{fecha}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>, <code><?php echo esc_html__('{{enlace_baja}}', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></code>
                    </p>
                </td>
            </tr>
        </table>

        <div class="flavor-newsletter-actions" style="margin:20px 0;display:flex;gap:10px;align-items:center;">
            <button type="button" id="btn-guardar-campana" class="button button-primary">
                <?php esc_html_e('Guardar campana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" id="btn-preview-campana" class="button">
                <?php esc_html_e('Vista previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
<?php if (!$es_nueva_campana && $estado_actual !== 'enviada') : ?>
            <button type="button" id="btn-enviar-campana" class="button button-secondary" style="background:#d63638;color:#fff;border-color:#d63638;">
                <?php esc_html_e('Enviar campana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
<?php endif; ?>
            <span id="estado-guardado" style="color:#00a32a;display:none;"></span>
        </div>

<?php if (!$es_nueva_campana) : ?>
        <div class="flavor-newsletter-info" style="margin:20px 0;padding:12px 16px;background:#f0f6fc;border-left:4px solid #72aee6;">
            <strong><?php esc_html_e('Estado:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <span class="flavor-badge"><?php echo esc_html($estado_actual); ?></span>
            <?php echo esc_html__('&mdash;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            <strong><?php esc_html_e('Creada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
            <?php echo esc_html($datos_campana->created_at ?? ''); ?>
        </div>
<?php endif; ?>
    </form>

    <!-- Dialogo de preview -->
    <div id="flavor-newsletter-preview-dialog" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;z-index:100000;background:rgba(0,0,0,0.7);">
        <div style="position:absolute;top:5%;left:50%;transform:translateX(-50%);width:90%;max-width:700px;max-height:85vh;background:#fff;border-radius:8px;overflow:auto;">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;border-bottom:1px solid #ddd;">
                <strong><?php esc_html_e('Vista previa del newsletter', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <button type="button" id="btn-cerrar-preview" class="button"><?php echo esc_html__('&times;', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
            <div id="flavor-newsletter-preview-content" style="padding:16px;"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    var ajaxUrl = flavorNewsletter.ajaxUrl;
    var nonce = flavorNewsletter.nonce;
    var i18n = flavorNewsletter.i18n;

    $('#btn-guardar-campana').on('click', function() {
        var contenidoEditor = typeof tinyMCE !== 'undefined' && tinyMCE.get('campana_contenido') ? tinyMCE.get('campana_contenido').getContent() : $('#campana_contenido').val();
        var btn = $(this);
        btn.prop('disabled', true).text(i18n.guardando);
        $.post(ajaxUrl, {
            action: 'flavor_newsletter_save',
            nonce: nonce,
            id: $('#campana_id').val(),
            asunto: $('#campana_asunto').val(),
            contenido_html: contenidoEditor
        }, function(resp) {
            btn.prop('disabled', false).text('Guardar campana');
            if (resp.success) {
                $('#estado-guardado').text(i18n.guardado).fadeIn().delay(2000).fadeOut();
                if (resp.data.id && $('#campana_id').val() == '0') {
                    $('#campana_id').val(resp.data.id);
                    window.history.replaceState(null, '', ajaxUrl.replace('admin-ajax.php', 'admin.php?page=flavor-newsletter-editor&id=' + resp.data.id));
                }
            } else {
                alert(resp.data.message || i18n.error_general);
            }
        }).fail(function() { btn.prop('disabled', false).text('Guardar campana'); alert(i18n.error_general); });
    });

    $('#btn-enviar-campana').on('click', function() {
        if (!confirm(i18n.confirmar_envio)) return;
        var btn = $(this);
        btn.prop('disabled', true).text(i18n.enviando);
        $.post(ajaxUrl, { action: 'flavor_newsletter_send', nonce: nonce, campana_id: $('#campana_id').val() }, function(resp) {
            btn.prop('disabled', false).text('Enviar campana');
            alert(resp.data.message || (resp.success ? 'OK' : i18n.error_general));
            if (resp.success) location.reload();
        }).fail(function() { btn.prop('disabled', false); alert(i18n.error_general); });
    });

    $('#btn-preview-campana').on('click', function() {
        var contenido = typeof tinyMCE !== 'undefined' && tinyMCE.get('campana_contenido') ? tinyMCE.get('campana_contenido').getContent() : $('#campana_contenido').val();
        $('#flavor-newsletter-preview-content').html(contenido);
        $('#flavor-newsletter-preview-dialog').fadeIn(200);
    });

    $('#btn-cerrar-preview, #flavor-newsletter-preview-dialog').on('click', function(e) {
        if (e.target === this) $('#flavor-newsletter-preview-dialog').fadeOut(200);
    });
});
</script>
