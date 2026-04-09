<?php
/**
 * Template: Iniciar Tramite
 *
 * Formulario multi-paso para iniciar un nuevo tramite
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar login
if (!is_user_logged_in()) {
    echo '<div class="tramites-login-required">';
    echo '<span class="dashicons dashicons-lock"></span>';
    echo '<h3>' . esc_html__('Inicia sesion para realizar un tramite', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
    echo '<p>' . esc_html__('Necesitas una cuenta para poder iniciar y dar seguimiento a tus tramites.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesion', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

global $wpdb;
$tabla_tipos_tramite = $wpdb->prefix . 'flavor_tipos_tramite';
$tabla_campos_formulario = $wpdb->prefix . 'flavor_campos_formulario';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_tipos_tramite)) {
    echo '<div class="tramites-empty"><p>' . esc_html__('El modulo de tramites no esta configurado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p></div>';
    return;
}

// Obtener tipo de tramite
$tipo_tramite_id = isset($_GET['tipo']) ? intval($_GET['tipo']) : 0;

if (!$tipo_tramite_id) {
    // Si no hay tipo seleccionado, mostrar selector
    $tipos_disponibles = $wpdb->get_results(
        "SELECT * FROM $tabla_tipos_tramite WHERE estado = 'activo' ORDER BY categoria ASC, nombre ASC"
    );
    ?>
    <div class="tramites-seleccionar-tipo">
        <h2><?php esc_html_e('Selecciona el tipo de tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="tramites-intro"><?php esc_html_e('Elige el tramite que deseas iniciar para continuar con el proceso.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <?php if ($tipos_disponibles): ?>
        <div class="tipos-tramite-grid">
            <?php foreach ($tipos_disponibles as $tipo): ?>
                <a href="<?php echo esc_url(add_query_arg('tipo', $tipo->id)); ?>" class="tipo-tramite-card">
                    <span class="tipo-icono" style="background: <?php echo esc_attr($tipo->color ?: '#6b7280'); ?>">
                        <span class="dashicons <?php echo esc_attr($tipo->icono ?: 'dashicons-clipboard'); ?>"></span>
                    </span>
                    <div class="tipo-info">
                        <h4><?php echo esc_html($tipo->nombre); ?></h4>
                        <?php if (!empty($tipo->descripcion)): ?>
                            <p><?php echo esc_html(wp_trim_words($tipo->descripcion, 15)); ?></p>
                        <?php endif; ?>
                        <div class="tipo-meta">
                            <?php if ($tipo->permite_online): ?>
                                <span class="badge badge-success"><?php esc_html_e('Online', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                            <?php if ($tipo->precio > 0): ?>
                                <span class="tipo-precio"><?php echo esc_html(number_format($tipo->precio, 2)); ?> &euro;</span>
                            <?php else: ?>
                                <span class="tipo-precio tipo-gratuito"><?php esc_html_e('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="tramites-empty">
            <p><?php esc_html_e('No hay tipos de tramite disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php
    return;
}

// Obtener datos del tipo de tramite
$tipo_tramite = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $tabla_tipos_tramite WHERE id = %d AND estado = 'activo'",
    $tipo_tramite_id
));

if (!$tipo_tramite) {
    echo '<div class="tramites-error">';
    echo '<span class="dashicons dashicons-warning"></span>';
    echo '<h3>' . esc_html__('Tramite no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</h3>';
    echo '<p>' . esc_html__('El tipo de tramite seleccionado no existe o no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    echo '<a href="' . esc_url(Flavor_Chat_Helpers::get_action_url('tramites', '')) . '" class="btn btn-primary">' . esc_html__('Ver catalogo', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</a>';
    echo '</div>';
    return;
}

// Obtener campos personalizados del formulario
$campos_formulario = [];
if (Flavor_Chat_Helpers::tabla_existe($tabla_campos_formulario)) {
    $campos_formulario = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $tabla_campos_formulario WHERE tipo_tramite_id = %d ORDER BY orden ASC",
        $tipo_tramite_id
    ));
}

// Datos del usuario actual
$usuario_actual = wp_get_current_user();
$tramites_base_url = Flavor_Chat_Helpers::get_action_url('tramites', '');
?>

<div class="tramites-iniciar-wrapper">
    <!-- Breadcrumb -->
    <nav class="tramites-breadcrumb">
        <a href="<?php echo esc_url($tramites_base_url); ?>"><?php esc_html_e('Tramites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></a>
        <span class="separator">&rsaquo;</span>
        <span><?php echo esc_html($tipo_tramite->nombre); ?></span>
    </nav>

    <div class="tramites-iniciar-header">
        <div class="tramite-tipo-info">
            <span class="tramite-tipo-icono" style="background: <?php echo esc_attr($tipo_tramite->color ?: '#6b7280'); ?>">
                <span class="dashicons <?php echo esc_attr($tipo_tramite->icono ?: 'dashicons-clipboard'); ?>"></span>
            </span>
            <div>
                <h2><?php echo esc_html($tipo_tramite->nombre); ?></h2>
                <?php if (!empty($tipo_tramite->descripcion)): ?>
                    <p><?php echo esc_html($tipo_tramite->descripcion); ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="tramite-info-resumen">
            <?php if ($tipo_tramite->plazo_resolucion_dias): ?>
                <div class="info-item">
                    <span class="dashicons dashicons-clock"></span>
                    <span><?php echo sprintf(esc_html__('Plazo: %d dias', FLAVOR_PLATFORM_TEXT_DOMAIN), $tipo_tramite->plazo_resolucion_dias); ?></span>
                </div>
            <?php endif; ?>
            <div class="info-item">
                <span class="dashicons dashicons-money-alt"></span>
                <span><?php echo $tipo_tramite->precio > 0 ? esc_html(number_format($tipo_tramite->precio, 2)) . ' &euro;' : esc_html__('Gratuito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
            </div>
        </div>
    </div>

    <!-- Indicador de pasos -->
    <div class="tramites-pasos-indicador">
        <div class="paso-item activo" data-paso="1">
            <span class="paso-numero">1</span>
            <span class="paso-texto"><?php esc_html_e('Datos personales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="paso-item" data-paso="2">
            <span class="paso-numero">2</span>
            <span class="paso-texto"><?php esc_html_e('Documentacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="paso-item" data-paso="3">
            <span class="paso-numero">3</span>
            <span class="paso-texto"><?php esc_html_e('Confirmacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
    </div>

    <!-- Formulario -->
    <form id="form-iniciar-tramite" class="tramites-form" enctype="multipart/form-data">
        <?php wp_nonce_field('flavor_tramites_iniciar', 'tramites_nonce'); ?>
        <input type="hidden" name="action" value="flavor_tramites_iniciar">
        <input type="hidden" name="tipo_tramite_id" value="<?php echo esc_attr($tipo_tramite_id); ?>">

        <!-- Paso 1: Datos personales -->
        <div class="form-paso" data-paso="1">
            <h3><?php esc_html_e('Datos del solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="form-row">
                <div class="form-group">
                    <label for="nombre_completo"><?php esc_html_e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" required
                           value="<?php echo esc_attr($usuario_actual->display_name); ?>">
                </div>
                <div class="form-group">
                    <label for="dni"><?php esc_html_e('DNI/NIE', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="text" id="dni" name="dni" required
                           pattern="[0-9]{8}[A-Za-z]|[XYZ][0-9]{7}[A-Za-z]"
                           placeholder="12345678A">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="email" id="email" name="email" required
                           value="<?php echo esc_attr($usuario_actual->user_email); ?>">
                </div>
                <div class="form-group">
                    <label for="telefono"><?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <input type="tel" id="telefono" name="telefono" required
                           placeholder="600 000 000">
                </div>
            </div>

            <div class="form-group">
                <label for="direccion"><?php esc_html_e('Direccion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                <input type="text" id="direccion" name="direccion" required
                       placeholder="<?php esc_attr_e('Calle, numero, piso...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
            </div>

            <div class="form-group">
                <label for="motivo"><?php esc_html_e('Motivo de la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                <textarea id="motivo" name="motivo" rows="4"
                          placeholder="<?php esc_attr_e('Explica brevemente el motivo de tu solicitud...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
            </div>

            <?php if ($campos_formulario): ?>
                <h4><?php esc_html_e('Informacion adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <?php foreach ($campos_formulario as $campo): ?>
                    <div class="form-group">
                        <label for="campo_<?php echo esc_attr($campo->id); ?>">
                            <?php echo esc_html($campo->etiqueta); ?>
                            <?php if ($campo->requerido): ?> *<?php endif; ?>
                        </label>
                        <?php
                        $campo_id = 'campo_' . $campo->id;
                        $campo_name = 'campos_extra[' . $campo->nombre_campo . ']';
                        $campo_required = $campo->requerido ? 'required' : '';

                        switch ($campo->tipo_campo):
                            case 'textarea':
                                ?>
                                <textarea id="<?php echo esc_attr($campo_id); ?>"
                                          name="<?php echo esc_attr($campo_name); ?>"
                                          <?php echo $campo_required; ?>
                                          placeholder="<?php echo esc_attr($campo->placeholder); ?>"
                                          rows="3"></textarea>
                                <?php
                                break;
                            case 'select':
                                $opciones = json_decode($campo->opciones, true) ?: [];
                                ?>
                                <select id="<?php echo esc_attr($campo_id); ?>"
                                        name="<?php echo esc_attr($campo_name); ?>"
                                        <?php echo $campo_required; ?>>
                                    <option value=""><?php esc_html_e('Selecciona...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    <?php foreach ($opciones as $opcion): ?>
                                        <option value="<?php echo esc_attr($opcion); ?>"><?php echo esc_html($opcion); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <?php
                                break;
                            case 'checkbox':
                                ?>
                                <label class="checkbox-label">
                                    <input type="checkbox" id="<?php echo esc_attr($campo_id); ?>"
                                           name="<?php echo esc_attr($campo_name); ?>"
                                           value="1" <?php echo $campo_required; ?>>
                                    <?php echo esc_html($campo->placeholder ?: $campo->etiqueta); ?>
                                </label>
                                <?php
                                break;
                            case 'date':
                                ?>
                                <input type="date" id="<?php echo esc_attr($campo_id); ?>"
                                       name="<?php echo esc_attr($campo_name); ?>"
                                       <?php echo $campo_required; ?>>
                                <?php
                                break;
                            case 'number':
                                ?>
                                <input type="number" id="<?php echo esc_attr($campo_id); ?>"
                                       name="<?php echo esc_attr($campo_name); ?>"
                                       <?php echo $campo_required; ?>
                                       placeholder="<?php echo esc_attr($campo->placeholder); ?>">
                                <?php
                                break;
                            default:
                                ?>
                                <input type="text" id="<?php echo esc_attr($campo_id); ?>"
                                       name="<?php echo esc_attr($campo_name); ?>"
                                       <?php echo $campo_required; ?>
                                       placeholder="<?php echo esc_attr($campo->placeholder); ?>">
                                <?php
                        endswitch;
                        ?>
                        <?php if (!empty($campo->ayuda)): ?>
                            <small class="form-help"><?php echo esc_html($campo->ayuda); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <div class="form-actions">
                <a href="<?php echo esc_url($tramites_base_url . 'catalogo/'); ?>" class="btn btn-outline">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <button type="button" class="btn btn-primary btn-siguiente" data-siguiente="2">
                    <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>
        </div>

        <!-- Paso 2: Documentacion -->
        <div class="form-paso" data-paso="2" style="display: none;">
            <h3><?php esc_html_e('Documentacion requerida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="documentos-info">
                <span class="dashicons dashicons-info-outline"></span>
                <p><?php esc_html_e('Adjunta los documentos necesarios para procesar tu solicitud. Formatos permitidos: PDF, JPG, PNG, DOC.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div class="documentos-upload-grid">
                <div class="documento-upload-item">
                    <label for="documento_dni"><?php esc_html_e('DNI/NIE (ambas caras)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> *</label>
                    <div class="upload-zona">
                        <input type="file" id="documento_dni" name="documento_dni" required
                               accept=".pdf,.jpg,.jpeg,.png">
                        <label for="documento_dni" class="upload-label">
                            <span class="dashicons dashicons-upload"></span>
                            <span><?php esc_html_e('Seleccionar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <span class="archivo-seleccionado"></span>
                    </div>
                </div>

                <div class="documento-upload-item">
                    <label for="documento_justificante"><?php esc_html_e('Justificante de domicilio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="upload-zona">
                        <input type="file" id="documento_justificante" name="documento_justificante"
                               accept=".pdf,.jpg,.jpeg,.png">
                        <label for="documento_justificante" class="upload-label">
                            <span class="dashicons dashicons-upload"></span>
                            <span><?php esc_html_e('Seleccionar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <span class="archivo-seleccionado"></span>
                    </div>
                    <small class="form-help"><?php esc_html_e('Factura de servicios, contrato de alquiler, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                </div>

                <div class="documento-upload-item">
                    <label for="documento_adicional"><?php esc_html_e('Documentacion adicional', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <div class="upload-zona">
                        <input type="file" id="documento_adicional" name="documento_adicional[]" multiple
                               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <label for="documento_adicional" class="upload-label">
                            <span class="dashicons dashicons-upload"></span>
                            <span><?php esc_html_e('Seleccionar archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <span class="archivo-seleccionado"></span>
                    </div>
                    <small class="form-help"><?php esc_html_e('Puedes adjuntar varios archivos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                </div>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline btn-anterior" data-anterior="1">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="button" class="btn btn-primary btn-siguiente" data-siguiente="3">
                    <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <span class="dashicons dashicons-arrow-right-alt"></span>
                </button>
            </div>
        </div>

        <!-- Paso 3: Confirmacion -->
        <div class="form-paso" data-paso="3" style="display: none;">
            <h3><?php esc_html_e('Confirmar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="resumen-solicitud">
                <div class="resumen-seccion">
                    <h4><?php esc_html_e('Tramite', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p><strong><?php echo esc_html($tipo_tramite->nombre); ?></strong></p>
                    <?php if ($tipo_tramite->precio > 0): ?>
                        <p><?php esc_html_e('Precio:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> <?php echo esc_html(number_format($tipo_tramite->precio, 2)); ?> &euro;</p>
                    <?php endif; ?>
                </div>

                <div class="resumen-seccion">
                    <h4><?php esc_html_e('Datos del solicitante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div id="resumen-datos-personales"></div>
                </div>

                <div class="resumen-seccion">
                    <h4><?php esc_html_e('Documentos adjuntos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div id="resumen-documentos"></div>
                </div>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="acepto_condiciones" required>
                    <?php esc_html_e('He leido y acepto las condiciones del tramite y la politica de privacidad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <div class="form-group checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="declaro_veracidad" required>
                    <?php esc_html_e('Declaro que los datos y documentos aportados son veraces.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </label>
            </div>

            <div class="form-actions">
                <button type="button" class="btn btn-outline btn-anterior" data-anterior="2">
                    <span class="dashicons dashicons-arrow-left-alt"></span>
                    <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="btn btn-primary btn-lg">
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<style>
.tramites-iniciar-wrapper { max-width: 800px; margin: 0 auto; }
.tramites-login-required, .tramites-error { text-align: center; padding: 3rem; background: #f9fafb; border-radius: 12px; }
.tramites-login-required .dashicons, .tramites-error .dashicons { font-size: 56px; width: 56px; height: 56px; color: #9ca3af; display: block; margin: 0 auto 1rem; }
.tramites-error .dashicons { color: #f59e0b; }
.tramites-breadcrumb { margin-bottom: 1.5rem; font-size: 0.9rem; color: #6b7280; }
.tramites-breadcrumb a { color: #3b82f6; text-decoration: none; }
.tramites-breadcrumb .separator { margin: 0 0.5rem; }
.tramites-iniciar-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 1.5rem; margin-bottom: 2rem; padding-bottom: 1.5rem; border-bottom: 1px solid #e5e7eb; flex-wrap: wrap; }
.tramite-tipo-info { display: flex; gap: 1rem; align-items: flex-start; }
.tramite-tipo-icono { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tramite-tipo-icono .dashicons { color: white; font-size: 28px; width: 28px; height: 28px; }
.tramite-tipo-info h2 { margin: 0 0 0.25rem; font-size: 1.5rem; color: #1f2937; }
.tramite-tipo-info p { margin: 0; color: #6b7280; font-size: 0.95rem; }
.tramite-info-resumen { display: flex; gap: 1.5rem; }
.info-item { display: flex; align-items: center; gap: 0.35rem; color: #6b7280; font-size: 0.9rem; }
.info-item .dashicons { font-size: 18px; width: 18px; height: 18px; color: #9ca3af; }
.tramites-pasos-indicador { display: flex; justify-content: space-between; margin-bottom: 2rem; position: relative; }
.tramites-pasos-indicador::before { content: ''; position: absolute; top: 20px; left: 10%; right: 10%; height: 2px; background: #e5e7eb; z-index: 0; }
.paso-item { display: flex; flex-direction: column; align-items: center; gap: 0.5rem; position: relative; z-index: 1; }
.paso-numero { width: 40px; height: 40px; background: #e5e7eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 600; color: #6b7280; transition: all 0.3s; }
.paso-item.activo .paso-numero { background: #3b82f6; color: white; }
.paso-item.completado .paso-numero { background: #10b981; color: white; }
.paso-texto { font-size: 0.85rem; color: #6b7280; }
.paso-item.activo .paso-texto { color: #3b82f6; font-weight: 500; }
.tramites-form { background: white; border-radius: 12px; padding: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.form-paso h3 { margin: 0 0 1.5rem; font-size: 1.25rem; color: #1f2937; }
.form-paso h4 { margin: 1.5rem 0 1rem; font-size: 1rem; color: #374151; padding-top: 1rem; border-top: 1px solid #e5e7eb; }
.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.form-group { margin-bottom: 1.25rem; }
.form-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.9rem; }
.form-group input, .form-group select, .form-group textarea { width: 100%; padding: 0.75rem 1rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.95rem; transition: all 0.2s; }
.form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
.form-help { display: block; margin-top: 0.35rem; font-size: 0.8rem; color: #6b7280; }
.checkbox-label { display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; font-size: 0.9rem; color: #374151; }
.checkbox-label input[type="checkbox"] { margin-top: 0.25rem; width: 18px; height: 18px; }
.documentos-info { display: flex; gap: 0.75rem; padding: 1rem; background: #eff6ff; border-radius: 8px; margin-bottom: 1.5rem; }
.documentos-info .dashicons { color: #3b82f6; flex-shrink: 0; }
.documentos-info p { margin: 0; font-size: 0.9rem; color: #1e40af; }
.documentos-upload-grid { display: flex; flex-direction: column; gap: 1.25rem; }
.documento-upload-item label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #374151; font-size: 0.9rem; }
.upload-zona { position: relative; }
.upload-zona input[type="file"] { position: absolute; opacity: 0; width: 100%; height: 100%; cursor: pointer; }
.upload-label { display: flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 1.25rem; border: 2px dashed #d1d5db; border-radius: 8px; background: #f9fafb; color: #6b7280; cursor: pointer; transition: all 0.2s; }
.upload-label:hover { border-color: #3b82f6; background: #eff6ff; color: #3b82f6; }
.archivo-seleccionado { display: block; margin-top: 0.5rem; font-size: 0.85rem; color: #059669; }
.resumen-solicitud { background: #f9fafb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
.resumen-seccion { margin-bottom: 1.25rem; padding-bottom: 1.25rem; border-bottom: 1px solid #e5e7eb; }
.resumen-seccion:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
.resumen-seccion h4 { margin: 0 0 0.75rem; font-size: 0.95rem; color: #374151; }
.resumen-seccion p { margin: 0.35rem 0; color: #6b7280; font-size: 0.9rem; }
.form-actions { display: flex; justify-content: space-between; gap: 1rem; margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
.tipos-tramite-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1rem; margin-top: 1.5rem; }
.tipo-tramite-card { display: flex; gap: 1rem; padding: 1.25rem; background: white; border-radius: 10px; text-decoration: none; box-shadow: 0 2px 8px rgba(0,0,0,0.06); transition: all 0.2s; }
.tipo-tramite-card:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.1); }
.tipo-icono { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.tipo-icono .dashicons { color: white; font-size: 24px; width: 24px; height: 24px; }
.tipo-info h4 { margin: 0 0 0.35rem; color: #1f2937; font-size: 1rem; }
.tipo-info p { margin: 0 0 0.5rem; color: #6b7280; font-size: 0.85rem; }
.tipo-meta { display: flex; gap: 0.75rem; align-items: center; }
.tipo-precio { font-size: 0.85rem; font-weight: 600; color: #1f2937; }
.tipo-gratuito { color: #059669; }
.badge { padding: 3px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: 500; }
.badge-success { background: #d1fae5; color: #059669; }
.btn { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 8px; font-size: 0.95rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-lg { padding: 1rem 2rem; font-size: 1rem; }
.btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
@media (max-width: 640px) {
    .form-row { grid-template-columns: 1fr; }
    .tramites-pasos-indicador { gap: 0.5rem; }
    .paso-texto { font-size: 0.75rem; }
    .tramites-iniciar-header { flex-direction: column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-iniciar-tramite');
    const pasos = document.querySelectorAll('.form-paso');
    const indicadores = document.querySelectorAll('.paso-item');

    // Navegacion entre pasos
    document.querySelectorAll('.btn-siguiente').forEach(btn => {
        btn.addEventListener('click', function() {
            const siguientePaso = this.dataset.siguiente;
            cambiarPaso(siguientePaso);
        });
    });

    document.querySelectorAll('.btn-anterior').forEach(btn => {
        btn.addEventListener('click', function() {
            const anteriorPaso = this.dataset.anterior;
            cambiarPaso(anteriorPaso);
        });
    });

    function cambiarPaso(numeroPaso) {
        // Ocultar todos los pasos
        pasos.forEach(paso => paso.style.display = 'none');

        // Mostrar paso actual
        const pasoActual = document.querySelector(`.form-paso[data-paso="${numeroPaso}"]`);
        if (pasoActual) pasoActual.style.display = 'block';

        // Actualizar indicadores
        indicadores.forEach(ind => {
            const indPaso = parseInt(ind.dataset.paso);
            ind.classList.remove('activo', 'completado');
            if (indPaso < parseInt(numeroPaso)) {
                ind.classList.add('completado');
            } else if (indPaso == parseInt(numeroPaso)) {
                ind.classList.add('activo');
            }
        });

        // Si es el paso 3, actualizar resumen
        if (numeroPaso == '3') {
            actualizarResumen();
        }

        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function actualizarResumen() {
        // Resumen de datos personales
        const datosPersonales = document.getElementById('resumen-datos-personales');
        const nombre = document.getElementById('nombre_completo').value;
        const dni = document.getElementById('dni').value;
        const email = document.getElementById('email').value;
        const telefono = document.getElementById('telefono').value;

        datosPersonales.innerHTML = `
            <p><strong>Nombre:</strong> ${nombre}</p>
            <p><strong>DNI/NIE:</strong> ${dni}</p>
            <p><strong>Email:</strong> ${email}</p>
            <p><strong>Telefono:</strong> ${telefono}</p>
        `;

        // Resumen de documentos
        const resumenDocs = document.getElementById('resumen-documentos');
        const documentosDni = document.getElementById('documento_dni').files;
        const documentosJustificante = document.getElementById('documento_justificante').files;
        const documentosAdicional = document.getElementById('documento_adicional').files;

        let htmlDocs = '';
        if (documentosDni.length) htmlDocs += `<p>- DNI/NIE: ${documentosDni[0].name}</p>`;
        if (documentosJustificante.length) htmlDocs += `<p>- Justificante: ${documentosJustificante[0].name}</p>`;
        if (documentosAdicional.length) {
            for (let i = 0; i < documentosAdicional.length; i++) {
                htmlDocs += `<p>- Adicional: ${documentosAdicional[i].name}</p>`;
            }
        }
        resumenDocs.innerHTML = htmlDocs || '<p>No se han adjuntado documentos.</p>';
    }

    // Mostrar nombre de archivo seleccionado
    document.querySelectorAll('input[type="file"]').forEach(input => {
        input.addEventListener('change', function() {
            const archivoInfo = this.parentElement.querySelector('.archivo-seleccionado');
            if (this.files.length > 0) {
                const nombres = Array.from(this.files).map(f => f.name).join(', ');
                archivoInfo.textContent = nombres;
            } else {
                archivoInfo.textContent = '';
            }
        });
    });
});
</script>
