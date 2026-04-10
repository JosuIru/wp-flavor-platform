<?php
/**
 * Template: Formulario de Contacto Empresarial
 *
 * @package FlavorPlatform
 * @var array $atts Atributos del shortcode
 */

if (!defined('ABSPATH')) {
    exit;
}

$titulo_seccion = esc_html($atts['titulo']);
$descripcion_seccion = esc_html($atts['descripcion']);
$tipo_layout = sanitize_key($atts['layout']);
$mostrar_info = filter_var($atts['mostrar_info'], FILTER_VALIDATE_BOOLEAN);

// Obtener configuración de contacto
$configuracion_contacto = get_option('flavor_empresarial_settings', []);
$telefono_empresa = $configuracion_contacto['telefono'] ?? '+34 900 000 000';
$email_empresa = $configuracion_contacto['email'] ?? get_option('admin_email');
$direccion_empresa = $configuracion_contacto['direccion'] ?? __('Calle Principal 123, 28001 Madrid, España', FLAVOR_PLATFORM_TEXT_DOMAIN);
$horario_empresa = $configuracion_contacto['horario'] ?? __('Lunes a Viernes, 9:00 - 18:00', FLAVOR_PLATFORM_TEXT_DOMAIN);
?>

<section class="flavor-emp-contacto flavor-emp-contacto-<?php echo esc_attr($tipo_layout); ?>">
    <div class="flavor-emp-contacto-header">
        <?php if ($titulo_seccion): ?>
            <h2 class="flavor-emp-seccion-titulo"><?php echo $titulo_seccion; ?></h2>
        <?php endif; ?>
        <?php if ($descripcion_seccion): ?>
            <p class="flavor-emp-seccion-descripcion"><?php echo $descripcion_seccion; ?></p>
        <?php endif; ?>
    </div>

    <div class="flavor-emp-contacto-contenido">
        <?php if ($mostrar_info && $tipo_layout === 'dos_columnas'): ?>
            <div class="contacto-info">
                <div class="info-item">
                    <span class="info-icono">
                        <span class="dashicons dashicons-phone"></span>
                    </span>
                    <div class="info-contenido">
                        <strong><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $telefono_empresa)); ?>">
                            <?php echo esc_html($telefono_empresa); ?>
                        </a>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icono">
                        <span class="dashicons dashicons-email-alt"></span>
                    </span>
                    <div class="info-contenido">
                        <strong><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <a href="mailto:<?php echo esc_attr($email_empresa); ?>">
                            <?php echo esc_html($email_empresa); ?>
                        </a>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icono">
                        <span class="dashicons dashicons-location"></span>
                    </span>
                    <div class="info-contenido">
                        <strong><?php esc_html_e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php echo esc_html($direccion_empresa); ?></span>
                    </div>
                </div>

                <div class="info-item">
                    <span class="info-icono">
                        <span class="dashicons dashicons-clock"></span>
                    </span>
                    <div class="info-contenido">
                        <strong><?php esc_html_e('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                        <span><?php echo esc_html($horario_empresa); ?></span>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="contacto-formulario">
            <form id="form-contacto-empresarial" class="flavor-emp-form">
                <input type="hidden" name="origen" value="web">

                <div class="form-row form-row-doble">
                    <div class="form-group">
                        <label for="contacto-nombre">
                            <?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="requerido">*</span>
                        </label>
                        <input type="text" id="contacto-nombre" name="nombre" required
                               placeholder="<?php esc_attr_e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="form-group">
                        <label for="contacto-email">
                            <?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="requerido">*</span>
                        </label>
                        <input type="email" id="contacto-email" name="email" required
                               placeholder="<?php esc_attr_e('tu@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>

                <div class="form-row form-row-doble">
                    <div class="form-group">
                        <label for="contacto-telefono">
                            <?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="tel" id="contacto-telefono" name="telefono"
                               placeholder="<?php esc_attr_e('+34 600 000 000', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <div class="form-group">
                        <label for="contacto-empresa">
                            <?php esc_html_e('Empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="text" id="contacto-empresa" name="empresa"
                               placeholder="<?php esc_attr_e('Nombre de tu empresa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contacto-asunto">
                            <?php esc_html_e('Asunto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <input type="text" id="contacto-asunto" name="asunto"
                               placeholder="<?php esc_attr_e('¿En qué podemos ayudarte?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contacto-mensaje">
                            <?php esc_html_e('Mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="requerido">*</span>
                        </label>
                        <textarea id="contacto-mensaje" name="mensaje" rows="5" required
                                  placeholder="<?php esc_attr_e('Cuéntanos más sobre tu proyecto o consulta...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group form-privacidad">
                        <label class="checkbox-label">
                            <input type="checkbox" name="acepta_privacidad" required>
                            <span>
                                <?php
                                printf(
                                    esc_html__('He leído y acepto la %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                    '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">' . esc_html__('política de privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>'
                                );
                                ?> <span class="requerido">*</span>
                            </span>
                        </label>
                    </div>
                </div>

                <div class="form-row form-acciones">
                    <button type="submit" class="flavor-emp-btn flavor-emp-btn-primary">
                        <span class="btn-texto"><?php esc_html_e('Enviar mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        <span class="btn-icono">
                            <span class="dashicons dashicons-arrow-right-alt"></span>
                        </span>
                    </button>
                </div>

                <div class="form-mensaje" style="display: none;"></div>
            </form>
        </div>
    </div>
</section>
