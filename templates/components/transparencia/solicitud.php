<?php
/**
 * Template: Formulario de solicitud de información pública
 *
 * Variables disponibles en $args:
 * - titulo: Título del formulario
 * - descripcion: Descripción del formulario
 * - categorias: Array de categorías de solicitud
 * - mostrar_urgencia: Boolean para mostrar selector de urgencia
 * - mostrar_adjuntos: Boolean para permitir adjuntar archivos
 * - terminos_url: URL de los términos y condiciones
 * - action_url: URL de envío del formulario
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_formulario = isset($args['titulo']) ? $args['titulo'] : __('Solicitud de Acceso a Información Pública', FLAVOR_PLATFORM_TEXT_DOMAIN);
$descripcion_formulario = isset($args['descripcion']) ? $args['descripcion'] : __('Complete el siguiente formulario para solicitar información pública según la Ley de Transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN);
$mostrar_urgencia = isset($args['mostrar_urgencia']) ? $args['mostrar_urgencia'] : true;
$mostrar_adjuntos = isset($args['mostrar_adjuntos']) ? $args['mostrar_adjuntos'] : true;
$url_terminos = isset($args['terminos_url']) ? $args['terminos_url'] : '#';
$url_accion = isset($args['action_url']) ? $args['action_url'] : '';
$nonce_field = wp_nonce_field('flavor_solicitud_transparencia', 'flavor_solicitud_nonce', true, false);

// Categorías de demostración
$categorias_solicitud = isset($args['categorias']) ? $args['categorias'] : array(
    'contratos' => __('Contratos y licitaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'presupuestos' => __('Presupuestos y cuentas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'subvenciones' => __('Subvenciones y ayudas', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'urbanismo' => __('Urbanismo y obras', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'personal' => __('Personal y retribuciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'medioambiente' => __('Medio ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'servicios' => __('Servicios públicos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)
);

// Niveles de urgencia
$niveles_urgencia = array(
    'normal' => __('Normal (20 días hábiles)', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'preferente' => __('Preferente (10 días hábiles)', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'urgente' => __('Urgente (5 días hábiles)', FLAVOR_PLATFORM_TEXT_DOMAIN)
);

// Tipos de identificación
$tipos_identificacion = array(
    'dni' => __('DNI/NIE', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'pasaporte' => __('Pasaporte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cif' => __('CIF (Personas jurídicas)', FLAVOR_PLATFORM_TEXT_DOMAIN)
);

// Formatos de respuesta preferidos
$formatos_respuesta = array(
    'electronico' => __('Electrónico (email/descarga)', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'papel' => __('Copia en papel', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'presencial' => __('Consulta presencial', FLAVOR_PLATFORM_TEXT_DOMAIN)
);
?>

<div class="flavor-solicitud-widget">
    <header class="flavor-solicitud-header">
        <div class="flavor-solicitud-icono">
            <span class="dashicons dashicons-clipboard"></span>
        </div>
        <h2 class="flavor-solicitud-titulo"><?php echo esc_html($titulo_formulario); ?></h2>
        <p class="flavor-solicitud-descripcion"><?php echo esc_html($descripcion_formulario); ?></p>
    </header>

    <form class="flavor-solicitud-form" method="post" action="<?php echo esc_url($url_accion); ?>" enctype="multipart/form-data">
        <?php echo $nonce_field; ?>

        <!-- Información del solicitante -->
        <fieldset class="flavor-solicitud-fieldset">
            <legend class="flavor-solicitud-legend">
                <span class="flavor-solicitud-step">1</span>
                <?php esc_html_e('Datos del solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="flavor-solicitud-grid">
                <div class="flavor-solicitud-campo">
                    <label for="flavor-nombre" class="flavor-solicitud-label">
                        <?php esc_html_e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                    </label>
                    <input
                        type="text"
                        id="flavor-nombre"
                        name="solicitante_nombre"
                        class="flavor-solicitud-input"
                        required
                        placeholder="<?php esc_attr_e('Nombre y apellidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <div class="flavor-solicitud-campo">
                    <label for="flavor-tipo-id" class="flavor-solicitud-label">
                        <?php esc_html_e('Tipo de identificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                    </label>
                    <select id="flavor-tipo-id" name="solicitante_tipo_id" class="flavor-solicitud-select" required>
                        <option value=""><?php esc_html_e('Seleccione...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($tipos_identificacion as $clave_tipo => $nombre_tipo) : ?>
                        <option value="<?php echo esc_attr($clave_tipo); ?>"><?php echo esc_html($nombre_tipo); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-solicitud-campo">
                    <label for="flavor-numero-id" class="flavor-solicitud-label">
                        <?php esc_html_e('Número de identificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                    </label>
                    <input
                        type="text"
                        id="flavor-numero-id"
                        name="solicitante_numero_id"
                        class="flavor-solicitud-input"
                        required
                        placeholder="<?php esc_attr_e('Ej: 12345678A', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <div class="flavor-solicitud-campo">
                    <label for="flavor-email" class="flavor-solicitud-label">
                        <?php esc_html_e('Correo electrónico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                    </label>
                    <input
                        type="email"
                        id="flavor-email"
                        name="solicitante_email"
                        class="flavor-solicitud-input"
                        required
                        placeholder="<?php esc_attr_e('correo@ejemplo.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <div class="flavor-solicitud-campo">
                    <label for="flavor-telefono" class="flavor-solicitud-label">
                        <?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <input
                        type="tel"
                        id="flavor-telefono"
                        name="solicitante_telefono"
                        class="flavor-solicitud-input"
                        placeholder="<?php esc_attr_e('Ej: 600123456', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <div class="flavor-solicitud-campo">
                    <label for="flavor-direccion" class="flavor-solicitud-label">
                        <?php esc_html_e('Dirección postal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <input
                        type="text"
                        id="flavor-direccion"
                        name="solicitante_direccion"
                        class="flavor-solicitud-input"
                        placeholder="<?php esc_attr_e('Calle, número, ciudad, CP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>
            </div>
        </fieldset>

        <!-- Información solicitada -->
        <fieldset class="flavor-solicitud-fieldset">
            <legend class="flavor-solicitud-legend">
                <span class="flavor-solicitud-step">2</span>
                <?php esc_html_e('Información solicitada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label for="flavor-categoria" class="flavor-solicitud-label">
                    <?php esc_html_e('Categoría de la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                </label>
                <select id="flavor-categoria" name="solicitud_categoria" class="flavor-solicitud-select" required>
                    <option value=""><?php esc_html_e('Seleccione una categoría...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <?php foreach ($categorias_solicitud as $clave_categoria => $nombre_categoria) : ?>
                    <option value="<?php echo esc_attr($clave_categoria); ?>"><?php echo esc_html($nombre_categoria); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label for="flavor-asunto" class="flavor-solicitud-label">
                    <?php esc_html_e('Asunto de la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                </label>
                <input
                    type="text"
                    id="flavor-asunto"
                    name="solicitud_asunto"
                    class="flavor-solicitud-input"
                    required
                    placeholder="<?php esc_attr_e('Breve descripción de la información solicitada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                >
            </div>

            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label for="flavor-descripcion" class="flavor-solicitud-label">
                    <?php esc_html_e('Descripción detallada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                </label>
                <textarea
                    id="flavor-descripcion"
                    name="solicitud_descripcion"
                    class="flavor-solicitud-textarea"
                    rows="5"
                    required
                    placeholder="<?php esc_attr_e('Describa con el mayor detalle posible la información que desea obtener, incluyendo fechas, referencias, o cualquier dato que facilite su localización.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                ></textarea>
                <p class="flavor-solicitud-ayuda"><?php esc_html_e('Sea lo más específico posible para agilizar la tramitación de su solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label for="flavor-motivo" class="flavor-solicitud-label">
                    <?php esc_html_e('Motivo o finalidad (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <textarea
                    id="flavor-motivo"
                    name="solicitud_motivo"
                    class="flavor-solicitud-textarea"
                    rows="2"
                    placeholder="<?php esc_attr_e('Indique el motivo de su solicitud si lo considera relevante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                ></textarea>
                <p class="flavor-solicitud-ayuda"><?php esc_html_e('No es obligatorio indicar el motivo de su solicitud según la Ley de Transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </fieldset>

        <!-- Opciones de entrega -->
        <fieldset class="flavor-solicitud-fieldset">
            <legend class="flavor-solicitud-legend">
                <span class="flavor-solicitud-step">3</span>
                <?php esc_html_e('Opciones de entrega', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </legend>

            <div class="flavor-solicitud-grid">
                <div class="flavor-solicitud-campo">
                    <label for="flavor-formato" class="flavor-solicitud-label">
                        <?php esc_html_e('Formato de respuesta preferido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <span class="flavor-requerido">*</span>
                    </label>
                    <select id="flavor-formato" name="solicitud_formato" class="flavor-solicitud-select" required>
                        <?php foreach ($formatos_respuesta as $clave_formato => $nombre_formato) : ?>
                        <option value="<?php echo esc_attr($clave_formato); ?>"><?php echo esc_html($nombre_formato); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($mostrar_urgencia) : ?>
                <div class="flavor-solicitud-campo">
                    <label for="flavor-urgencia" class="flavor-solicitud-label">
                        <?php esc_html_e('Nivel de urgencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <select id="flavor-urgencia" name="solicitud_urgencia" class="flavor-solicitud-select">
                        <?php foreach ($niveles_urgencia as $clave_urgencia => $nombre_urgencia) : ?>
                        <option value="<?php echo esc_attr($clave_urgencia); ?>"><?php echo esc_html($nombre_urgencia); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($mostrar_adjuntos) : ?>
            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label for="flavor-adjuntos" class="flavor-solicitud-label">
                    <?php esc_html_e('Documentación adjunta (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
                <div class="flavor-solicitud-dropzone" id="flavor-dropzone">
                    <input
                        type="file"
                        id="flavor-adjuntos"
                        name="solicitud_adjuntos[]"
                        class="flavor-solicitud-file"
                        multiple
                        accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"
                    >
                    <div class="flavor-dropzone-contenido">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php esc_html_e('Arrastre archivos aquí o haga clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <span class="flavor-dropzone-formatos"><?php esc_html_e('PDF, DOC, DOCX, JPG, PNG (máx. 10MB por archivo)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>
                <div class="flavor-solicitud-archivos-lista" id="flavor-archivos-lista"></div>
            </div>
            <?php endif; ?>
        </fieldset>

        <!-- Consentimiento y envío -->
        <fieldset class="flavor-solicitud-fieldset flavor-solicitud-fieldset-final">
            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label class="flavor-solicitud-checkbox-label">
                    <input
                        type="checkbox"
                        name="solicitud_privacidad"
                        class="flavor-solicitud-checkbox"
                        required
                    >
                    <span class="flavor-checkbox-texto">
                        <?php
                        printf(
                            esc_html__('He leído y acepto la %spolítica de privacidad%s y el tratamiento de mis datos personales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            '<a href="' . esc_url($url_terminos) . '" target="_blank">',
                            '</a>'
                        );
                        ?>
                        <span class="flavor-requerido">*</span>
                    </span>
                </label>
            </div>

            <div class="flavor-solicitud-campo flavor-solicitud-campo-full">
                <label class="flavor-solicitud-checkbox-label">
                    <input
                        type="checkbox"
                        name="solicitud_notificaciones"
                        class="flavor-solicitud-checkbox"
                    >
                    <span class="flavor-checkbox-texto">
                        <?php esc_html_e('Deseo recibir notificaciones sobre el estado de mi solicitud por correo electrónico.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </label>
            </div>

            <div class="flavor-solicitud-acciones">
                <button type="reset" class="flavor-solicitud-btn flavor-solicitud-btn-secundario">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php esc_html_e('Limpiar formulario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="flavor-solicitud-btn flavor-solicitud-btn-primario">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </fieldset>
    </form>

    <aside class="flavor-solicitud-info-legal">
        <h3 class="flavor-info-legal-titulo">
            <span class="dashicons dashicons-info-outline"></span>
            <?php esc_html_e('Información importante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </h3>
        <ul class="flavor-info-legal-lista">
            <li><?php esc_html_e('El plazo máximo de respuesta es de un mes desde la recepción de la solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Recibirá un acuse de recibo con el número de expediente asignado.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Puede consultar el estado de su solicitud en cualquier momento.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            <li><?php esc_html_e('Los datos personales serán tratados conforme al RGPD y la LOPDGDD.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
        </ul>
    </aside>
</div>

<style>
.flavor-solicitud-widget {
    background: #ffffff;
    border-radius: 12px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    overflow: hidden;
}

.flavor-solicitud-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    padding: 32px 24px;
    text-align: center;
    color: #ffffff;
}

.flavor-solicitud-icono {
    width: 64px;
    height: 64px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.flavor-solicitud-icono .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.flavor-solicitud-titulo {
    margin: 0 0 8px;
    font-size: 1.5rem;
    font-weight: 600;
}

.flavor-solicitud-descripcion {
    margin: 0;
    opacity: 0.9;
    font-size: 0.9375rem;
}

.flavor-solicitud-form {
    padding: 24px;
}

.flavor-solicitud-fieldset {
    border: none;
    padding: 0;
    margin: 0 0 32px;
}

.flavor-solicitud-fieldset-final {
    margin-bottom: 0;
}

.flavor-solicitud-legend {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.125rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
    width: 100%;
}

.flavor-solicitud-step {
    width: 28px;
    height: 28px;
    background: #3b82f6;
    color: #ffffff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 700;
}

.flavor-solicitud-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.flavor-solicitud-campo {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.flavor-solicitud-campo-full {
    grid-column: 1 / -1;
}

.flavor-solicitud-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
}

.flavor-requerido {
    color: #dc2626;
}

.flavor-solicitud-input,
.flavor-solicitud-select,
.flavor-solicitud-textarea {
    padding: 10px 14px;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.9375rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    background: #ffffff;
}

.flavor-solicitud-input:focus,
.flavor-solicitud-select:focus,
.flavor-solicitud-textarea:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.flavor-solicitud-input::placeholder,
.flavor-solicitud-textarea::placeholder {
    color: #9ca3af;
}

.flavor-solicitud-textarea {
    resize: vertical;
    min-height: 100px;
}

.flavor-solicitud-ayuda {
    margin: 4px 0 0;
    font-size: 0.8125rem;
    color: #6b7280;
}

/* Dropzone */
.flavor-solicitud-dropzone {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 8px;
    padding: 32px;
    text-align: center;
    transition: border-color 0.2s, background-color 0.2s;
    cursor: pointer;
}

.flavor-solicitud-dropzone:hover,
.flavor-solicitud-dropzone.flavor-dropzone-active {
    border-color: #3b82f6;
    background: #eff6ff;
}

.flavor-solicitud-file {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.flavor-dropzone-contenido .dashicons {
    font-size: 40px;
    width: 40px;
    height: 40px;
    color: #9ca3af;
    margin-bottom: 8px;
}

.flavor-dropzone-contenido p {
    margin: 0 0 4px;
    color: #374151;
    font-weight: 500;
}

.flavor-dropzone-formatos {
    font-size: 0.8125rem;
    color: #6b7280;
}

.flavor-solicitud-archivos-lista {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 8px;
}

/* Checkboxes */
.flavor-solicitud-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 10px;
    cursor: pointer;
    padding: 8px 0;
}

.flavor-solicitud-checkbox {
    width: 18px;
    height: 18px;
    margin-top: 2px;
    accent-color: #3b82f6;
}

.flavor-checkbox-texto {
    font-size: 0.9375rem;
    color: #374151;
    line-height: 1.5;
}

.flavor-checkbox-texto a {
    color: #2563eb;
    text-decoration: underline;
}

/* Acciones */
.flavor-solicitud-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.flavor-solicitud-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 0.9375rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.flavor-solicitud-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.flavor-solicitud-btn-primario {
    background: #3b82f6;
    color: #ffffff;
}

.flavor-solicitud-btn-primario:hover {
    background: #2563eb;
}

.flavor-solicitud-btn-secundario {
    background: #f3f4f6;
    color: #4b5563;
}

.flavor-solicitud-btn-secundario:hover {
    background: #e5e7eb;
}

/* Info Legal */
.flavor-solicitud-info-legal {
    background: #f0fdf4;
    border-top: 1px solid #bbf7d0;
    padding: 20px 24px;
}

.flavor-info-legal-titulo {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 0 0 12px;
    font-size: 0.9375rem;
    font-weight: 600;
    color: #166534;
}

.flavor-info-legal-titulo .dashicons {
    color: #22c55e;
}

.flavor-info-legal-lista {
    margin: 0;
    padding-left: 20px;
    font-size: 0.875rem;
    color: #166534;
}

.flavor-info-legal-lista li {
    margin-bottom: 6px;
}

.flavor-info-legal-lista li:last-child {
    margin-bottom: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .flavor-solicitud-grid {
        grid-template-columns: 1fr;
    }

    .flavor-solicitud-acciones {
        flex-direction: column;
    }

    .flavor-solicitud-btn {
        justify-content: center;
    }
}

@media (max-width: 480px) {
    .flavor-solicitud-header {
        padding: 24px 16px;
    }

    .flavor-solicitud-form {
        padding: 16px;
    }

    .flavor-solicitud-titulo {
        font-size: 1.25rem;
    }

    .flavor-solicitud-dropzone {
        padding: 20px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const zonaArrastre = document.getElementById('flavor-dropzone');
    const inputArchivo = document.getElementById('flavor-adjuntos');
    const listaArchivos = document.getElementById('flavor-archivos-lista');

    if (zonaArrastre && inputArchivo) {
        // Eventos de drag and drop
        ['dragenter', 'dragover'].forEach(function(nombreEvento) {
            zonaArrastre.addEventListener(nombreEvento, function(evento) {
                evento.preventDefault();
                zonaArrastre.classList.add('flavor-dropzone-active');
            });
        });

        ['dragleave', 'drop'].forEach(function(nombreEvento) {
            zonaArrastre.addEventListener(nombreEvento, function(evento) {
                evento.preventDefault();
                zonaArrastre.classList.remove('flavor-dropzone-active');
            });
        });

        // Mostrar archivos seleccionados
        inputArchivo.addEventListener('change', function() {
            actualizarListaArchivos(this.files);
        });
    }

    function actualizarListaArchivos(archivos) {
        if (!listaArchivos) return;
        listaArchivos.innerHTML = '';

        Array.from(archivos).forEach(function(archivo) {
            const elementoArchivo = document.createElement('div');
            elementoArchivo.className = 'flavor-archivo-item';
            elementoArchivo.style.cssText = 'display: flex; align-items: center; gap: 8px; padding: 8px 12px; background: #f3f4f6; border-radius: 6px; font-size: 0.875rem;';
            elementoArchivo.innerHTML = '<span class="dashicons dashicons-media-default" style="color: #6b7280;"></span>' +
                '<span style="flex: 1;">' + archivo.name + '</span>' +
                '<span style="color: #6b7280;">' + formatearTamanoArchivo(archivo.size) + '</span>';
            listaArchivos.appendChild(elementoArchivo);
        });
    }

    function formatearTamanoArchivo(bytes) {
        if (bytes === 0) return '0 Bytes';
        const kilobyte = 1024;
        const unidades = ['Bytes', 'KB', 'MB', 'GB'];
        const indiceUnidad = Math.floor(Math.log(bytes) / Math.log(kilobyte));
        return parseFloat((bytes / Math.pow(kilobyte, indiceUnidad)).toFixed(2)) + ' ' + unidades[indiceUnidad];
    }
});
</script>
