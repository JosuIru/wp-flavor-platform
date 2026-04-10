<?php
/**
 * Template: Solicitar Reserva de Espacio
 * Formulario para crear una nueva solicitud de reserva
 *
 * @package FlavorPlatform
 * @subpackage Modules\EspaciosComunes
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="espacios-empty"><span class="dashicons dashicons-lock"></span><h3>' . esc_html__('Inicia sesion para solicitar una reserva', 'flavor-platform') . '</h3></div>';
    return;
}

global $wpdb;
$tabla_espacios = $wpdb->prefix . 'flavor_espacios_comunes';
$usuario_actual = wp_get_current_user();

// Obtener espacios disponibles
$espacios_disponibles = $wpdb->get_results(
    "SELECT id, nombre, tipo, capacidad_personas, precio_hora, precio_dia,
            requiere_fianza, importe_fianza, horario_apertura, horario_cierre,
            dias_disponibles, ubicacion, fotos
     FROM $tabla_espacios
     WHERE estado = 'disponible'
     ORDER BY nombre ASC"
);

// Espacio preseleccionado desde URL
$espacio_preseleccionado = isset($_GET['espacio_id']) ? intval($_GET['espacio_id']) : 0;

// Tipos de evento comunes
$tipos_evento = [
    'reunion'      => __('Reunion', 'flavor-platform'),
    'taller'       => __('Taller/Curso', 'flavor-platform'),
    'celebracion'  => __('Celebracion/Fiesta', 'flavor-platform'),
    'comunidad'    => __('Evento comunitario', 'flavor-platform'),
    'deportivo'    => __('Actividad deportiva', 'flavor-platform'),
    'cultural'     => __('Evento cultural', 'flavor-platform'),
    'formacion'    => __('Formacion/Charla', 'flavor-platform'),
    'otro'         => __('Otro', 'flavor-platform'),
];

// Funcion helper para obtener la primera imagen
if (!function_exists('espacios_get_primera_imagen')) {
    function espacios_get_primera_imagen($fotos_json) {
        if (empty($fotos_json)) return '';
        $fotos = json_decode($fotos_json, true);
        if (is_array($fotos) && !empty($fotos)) {
            return $fotos[0];
        }
        return '';
    }
}

// Configuracion del modulo
$espacios_comunes_module_class = function_exists('flavor_get_runtime_class_name')
    ? flavor_get_runtime_class_name('Flavor_Chat_Espacios_Comunes_Module')
    : 'Flavor_Chat_Espacios_Comunes_Module';
$modulo = class_exists($espacios_comunes_module_class)
    ? $espacios_comunes_module_class::get_instance()
    : null;
$settings = $modulo ? $modulo->get_settings() : [];

$anticipacion_minima_horas = $settings['horas_anticipacion_minima'] ?? 24;
$anticipacion_maxima_dias = $settings['dias_anticipacion_maxima'] ?? 90;
$duracion_maxima_horas = $settings['duracion_maxima_horas'] ?? 8;

// Calcular fecha minima y maxima
$fecha_minima = date('Y-m-d', strtotime("+{$anticipacion_minima_horas} hours"));
$fecha_maxima = date('Y-m-d', strtotime("+{$anticipacion_maxima_dias} days"));
?>

<div class="espacios-wrapper espacios-solicitar">
    <div class="espacios-header">
        <h2 class="espacios-titulo">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php esc_html_e('Solicitar Reserva', 'flavor-platform'); ?>
        </h2>
        <a href="<?php echo esc_url(remove_query_arg(['tab', 'espacio_id'])); ?>" class="btn btn-outline">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Ver espacios', 'flavor-platform'); ?>
        </a>
    </div>

    <?php if (empty($espacios_disponibles)): ?>
        <div class="espacios-empty">
            <span class="dashicons dashicons-warning"></span>
            <h3><?php esc_html_e('No hay espacios disponibles', 'flavor-platform'); ?></h3>
            <p><?php esc_html_e('En este momento no hay espacios habilitados para reservar.', 'flavor-platform'); ?></p>
        </div>
    <?php else: ?>

    <div class="solicitar-container">
        <!-- Informacion del proceso -->
        <div class="solicitar-info-box">
            <h4><span class="dashicons dashicons-info"></span> <?php esc_html_e('Proceso de reserva', 'flavor-platform'); ?></h4>
            <ol>
                <li><?php esc_html_e('Selecciona el espacio y la fecha', 'flavor-platform'); ?></li>
                <li><?php esc_html_e('Completa los detalles del evento', 'flavor-platform'); ?></li>
                <li><?php esc_html_e('Envia tu solicitud', 'flavor-platform'); ?></li>
                <li><?php esc_html_e('Espera confirmacion (24-48h)', 'flavor-platform'); ?></li>
            </ol>
        </div>

        <form id="form-solicitar-reserva" class="solicitar-form" method="post">
            <?php wp_nonce_field('espacios_crear_reserva', 'espacios_nonce'); ?>

            <!-- Paso 1: Seleccion de espacio -->
            <div class="solicitar-seccion">
                <h3 class="solicitar-seccion-titulo">
                    <span class="paso-numero">1</span>
                    <?php esc_html_e('Selecciona el espacio', 'flavor-platform'); ?>
                </h3>

                <div class="espacios-selector">
                    <?php foreach ($espacios_disponibles as $espacio): ?>
                        <?php
                        $imagen_espacio = espacios_get_primera_imagen($espacio->fotos);
                        $seleccionado = ($espacio->id == $espacio_preseleccionado);
                        ?>
                        <label class="espacio-opcion <?php echo $seleccionado ? 'seleccionado' : ''; ?>">
                            <input type="radio"
                                   name="espacio_id"
                                   value="<?php echo esc_attr($espacio->id); ?>"
                                   data-precio-hora="<?php echo esc_attr($espacio->precio_hora); ?>"
                                   data-precio-dia="<?php echo esc_attr($espacio->precio_dia); ?>"
                                   data-capacidad="<?php echo esc_attr($espacio->capacidad_personas); ?>"
                                   data-fianza="<?php echo esc_attr($espacio->requiere_fianza ? $espacio->importe_fianza : 0); ?>"
                                   data-horario-apertura="<?php echo esc_attr($espacio->horario_apertura); ?>"
                                   data-horario-cierre="<?php echo esc_attr($espacio->horario_cierre); ?>"
                                   <?php checked($seleccionado); ?>
                                   required>
                            <div class="espacio-opcion-contenido">
                                <div class="espacio-opcion-imagen">
                                    <?php if ($imagen_espacio): ?>
                                        <img src="<?php echo esc_url($imagen_espacio); ?>" alt="<?php echo esc_attr($espacio->nombre); ?>">
                                    <?php else: ?>
                                        <span class="dashicons dashicons-building"></span>
                                    <?php endif; ?>
                                </div>
                                <div class="espacio-opcion-info">
                                    <strong><?php echo esc_html($espacio->nombre); ?></strong>
                                    <span class="espacio-opcion-meta">
                                        <span class="dashicons dashicons-groups"></span>
                                        <?php printf(esc_html__('%d personas', 'flavor-platform'), $espacio->capacidad_personas); ?>
                                    </span>
                                    <span class="espacio-opcion-precio">
                                        <?php if ($espacio->precio_hora > 0): ?>
                                            <?php echo number_format($espacio->precio_hora, 2); ?>€/h
                                        <?php else: ?>
                                            <span class="gratuito"><?php esc_html_e('Gratuito', 'flavor-platform'); ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <span class="espacio-opcion-check">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                </span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Paso 2: Fecha y hora -->
            <div class="solicitar-seccion">
                <h3 class="solicitar-seccion-titulo">
                    <span class="paso-numero">2</span>
                    <?php esc_html_e('Fecha y horario', 'flavor-platform'); ?>
                </h3>

                <div class="solicitar-fila">
                    <div class="solicitar-campo">
                        <label for="fecha"><?php esc_html_e('Fecha', 'flavor-platform'); ?> <span class="requerido">*</span></label>
                        <input type="date"
                               id="fecha"
                               name="fecha"
                               min="<?php echo esc_attr($fecha_minima); ?>"
                               max="<?php echo esc_attr($fecha_maxima); ?>"
                               required>
                        <small><?php printf(esc_html__('Reserva con al menos %d horas de anticipacion', 'flavor-platform'), $anticipacion_minima_horas); ?></small>
                    </div>
                </div>

                <div class="solicitar-fila solicitar-fila-doble">
                    <div class="solicitar-campo">
                        <label for="hora_inicio"><?php esc_html_e('Hora inicio', 'flavor-platform'); ?> <span class="requerido">*</span></label>
                        <select id="hora_inicio" name="hora_inicio" required>
                            <option value=""><?php esc_html_e('Seleccionar...', 'flavor-platform'); ?></option>
                            <?php for ($hora = 8; $hora <= 21; $hora++): ?>
                                <option value="<?php echo sprintf('%02d:00', $hora); ?>"><?php echo sprintf('%02d:00', $hora); ?></option>
                                <option value="<?php echo sprintf('%02d:30', $hora); ?>"><?php echo sprintf('%02d:30', $hora); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="solicitar-campo">
                        <label for="hora_fin"><?php esc_html_e('Hora fin', 'flavor-platform'); ?> <span class="requerido">*</span></label>
                        <select id="hora_fin" name="hora_fin" required>
                            <option value=""><?php esc_html_e('Seleccionar...', 'flavor-platform'); ?></option>
                            <?php for ($hora = 9; $hora <= 22; $hora++): ?>
                                <option value="<?php echo sprintf('%02d:00', $hora); ?>"><?php echo sprintf('%02d:00', $hora); ?></option>
                                <?php if ($hora < 22): ?>
                                    <option value="<?php echo sprintf('%02d:30', $hora); ?>"><?php echo sprintf('%02d:30', $hora); ?></option>
                                <?php endif; ?>
                            <?php endfor; ?>
                        </select>
                        <small><?php printf(esc_html__('Duracion maxima: %d horas', 'flavor-platform'), $duracion_maxima_horas); ?></small>
                    </div>
                </div>

                <!-- Indicador de duracion y precio estimado -->
                <div id="resumen-horario" class="resumen-horario" style="display: none;">
                    <div class="resumen-item">
                        <span class="dashicons dashicons-clock"></span>
                        <span id="duracion-texto">-</span>
                    </div>
                    <div class="resumen-item" id="precio-estimado-container" style="display: none;">
                        <span class="dashicons dashicons-money-alt"></span>
                        <span><?php esc_html_e('Precio estimado:', 'flavor-platform'); ?> <strong id="precio-estimado">0€</strong></span>
                    </div>
                </div>
            </div>

            <!-- Paso 3: Detalles del evento -->
            <div class="solicitar-seccion">
                <h3 class="solicitar-seccion-titulo">
                    <span class="paso-numero">3</span>
                    <?php esc_html_e('Detalles del evento', 'flavor-platform'); ?>
                </h3>

                <div class="solicitar-fila solicitar-fila-doble">
                    <div class="solicitar-campo">
                        <label for="tipo_evento"><?php esc_html_e('Tipo de evento', 'flavor-platform'); ?></label>
                        <select id="tipo_evento" name="tipo_evento">
                            <option value=""><?php esc_html_e('Seleccionar...', 'flavor-platform'); ?></option>
                            <?php foreach ($tipos_evento as $tipo_valor => $tipo_label): ?>
                                <option value="<?php echo esc_attr($tipo_valor); ?>"><?php echo esc_html($tipo_label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="solicitar-campo">
                        <label for="num_asistentes"><?php esc_html_e('Numero de asistentes', 'flavor-platform'); ?></label>
                        <input type="number" id="num_asistentes" name="num_asistentes" min="1" max="500" placeholder="Ej: 15">
                        <small id="capacidad-aviso" style="display: none; color: #dc2626;"></small>
                    </div>
                </div>

                <div class="solicitar-campo">
                    <label for="motivo"><?php esc_html_e('Motivo / Descripcion', 'flavor-platform'); ?></label>
                    <textarea id="motivo"
                              name="motivo"
                              rows="3"
                              placeholder="<?php esc_attr_e('Describe brevemente el uso que le daras al espacio...', 'flavor-platform'); ?>"></textarea>
                </div>

                <div class="solicitar-campo">
                    <label for="instrucciones"><?php esc_html_e('Instrucciones especiales', 'flavor-platform'); ?></label>
                    <textarea id="instrucciones"
                              name="instrucciones"
                              rows="2"
                              placeholder="<?php esc_attr_e('Necesidades especiales, montaje, equipamiento adicional...', 'flavor-platform'); ?>"></textarea>
                </div>
            </div>

            <!-- Resumen y envio -->
            <div class="solicitar-seccion solicitar-resumen">
                <h3 class="solicitar-seccion-titulo">
                    <span class="paso-numero">4</span>
                    <?php esc_html_e('Confirmar solicitud', 'flavor-platform'); ?>
                </h3>

                <div id="resumen-reserva" class="resumen-reserva">
                    <p class="resumen-placeholder"><?php esc_html_e('Completa los campos anteriores para ver el resumen', 'flavor-platform'); ?></p>
                </div>

                <div class="solicitar-terminos">
                    <label>
                        <input type="checkbox" name="acepta_normas" value="1" required>
                        <?php printf(
                            esc_html__('He leido y acepto las %snormas de uso%s de los espacios comunes', 'flavor-platform'),
                            '<a href="' . esc_url(home_url('/espacios-comunes/normas/')) . '" target="_blank">',
                            '</a>'
                        ); ?>
                    </label>
                </div>

                <div class="solicitar-acciones">
                    <button type="submit" class="btn btn-primary btn-lg" id="btn-enviar-solicitud">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Enviar Solicitud', 'flavor-platform'); ?>
                    </button>
                </div>

                <p class="solicitar-nota">
                    <span class="dashicons dashicons-info"></span>
                    <?php esc_html_e('Recibiras una notificacion cuando tu solicitud sea revisada.', 'flavor-platform'); ?>
                </p>
            </div>
        </form>
    </div>

    <?php endif; ?>
</div>

<style>
.espacios-solicitar {
    max-width: 800px;
    margin: 0 auto;
}

.solicitar-container {
    background: #fff;
    border-radius: 12px;
    padding: 2rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.solicitar-info-box {
    background: #f0f9ff;
    border: 1px solid #bae6fd;
    border-radius: 8px;
    padding: 1rem 1.5rem;
    margin-bottom: 2rem;
}

.solicitar-info-box h4 {
    margin: 0 0 0.5rem 0;
    color: #0369a1;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.solicitar-info-box ol {
    margin: 0;
    padding-left: 1.25rem;
    color: #0c4a6e;
    font-size: 0.9rem;
}

.solicitar-info-box li {
    margin-bottom: 0.25rem;
}

.solicitar-seccion {
    margin-bottom: 2rem;
    padding-bottom: 2rem;
    border-bottom: 1px solid #e5e7eb;
}

.solicitar-seccion:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.solicitar-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin: 0 0 1.5rem 0;
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
}

.paso-numero {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: #fff;
    border-radius: 50%;
    font-size: 0.85rem;
    font-weight: 700;
}

/* Selector de espacios */
.espacios-selector {
    display: grid;
    gap: 0.75rem;
}

.espacio-opcion {
    cursor: pointer;
}

.espacio-opcion input[type="radio"] {
    display: none;
}

.espacio-opcion-contenido {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 10px;
    transition: all 0.2s;
}

.espacio-opcion:hover .espacio-opcion-contenido {
    border-color: #06b6d4;
    background: #f0fdfa;
}

.espacio-opcion input:checked + .espacio-opcion-contenido {
    border-color: #06b6d4;
    background: #ecfeff;
}

.espacio-opcion-imagen {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: #f3f4f6;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.espacio-opcion-imagen img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.espacio-opcion-imagen .dashicons {
    font-size: 28px;
    color: #9ca3af;
}

.espacio-opcion-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.espacio-opcion-info strong {
    font-size: 1rem;
    color: #1f2937;
}

.espacio-opcion-meta {
    font-size: 0.85rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.espacio-opcion-meta .dashicons {
    font-size: 14px;
    width: 14px;
    height: 14px;
}

.espacio-opcion-precio {
    font-size: 0.9rem;
    font-weight: 600;
    color: #0891b2;
}

.espacio-opcion-precio .gratuito {
    color: #059669;
}

.espacio-opcion-check {
    opacity: 0;
    transition: opacity 0.2s;
}

.espacio-opcion input:checked + .espacio-opcion-contenido .espacio-opcion-check {
    opacity: 1;
}

.espacio-opcion-check .dashicons {
    color: #06b6d4;
    font-size: 24px;
}

/* Campos del formulario */
.solicitar-fila {
    margin-bottom: 1rem;
}

.solicitar-fila-doble {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

@media (max-width: 600px) {
    .solicitar-fila-doble {
        grid-template-columns: 1fr;
    }
}

.solicitar-campo {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.solicitar-campo label {
    font-weight: 500;
    font-size: 0.9rem;
    color: #374151;
}

.solicitar-campo .requerido {
    color: #dc2626;
}

.solicitar-campo input,
.solicitar-campo select,
.solicitar-campo textarea {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.solicitar-campo input:focus,
.solicitar-campo select:focus,
.solicitar-campo textarea:focus {
    outline: none;
    border-color: #06b6d4;
    box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
}

.solicitar-campo small {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Resumen horario */
.resumen-horario {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
    padding: 1rem;
    background: #f9fafb;
    border-radius: 8px;
    margin-top: 1rem;
}

.resumen-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.95rem;
    color: #374151;
}

.resumen-item .dashicons {
    color: #6b7280;
}

/* Resumen final */
.resumen-reserva {
    background: #f0fdfa;
    border: 1px solid #99f6e4;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.resumen-placeholder {
    color: #6b7280;
    text-align: center;
    margin: 0;
}

/* Terminos */
.solicitar-terminos {
    margin-bottom: 1.5rem;
}

.solicitar-terminos label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    font-size: 0.9rem;
    color: #374151;
    cursor: pointer;
}

.solicitar-terminos input[type="checkbox"] {
    margin-top: 0.2rem;
}

.solicitar-terminos a {
    color: #0891b2;
    text-decoration: underline;
}

/* Acciones */
.solicitar-acciones {
    display: flex;
    justify-content: center;
    margin-bottom: 1rem;
}

.btn-lg {
    padding: 1rem 2rem;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.solicitar-nota {
    text-align: center;
    font-size: 0.85rem;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    margin: 0;
}

.solicitar-nota .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}
</style>

<script>
jQuery(document).ready(function($) {
    var espacioSeleccionado = null;
    var precioHora = 0;
    var capacidadMax = 0;
    var fianza = 0;

    // Al seleccionar espacio
    $('input[name="espacio_id"]').on('change', function() {
        var $input = $(this);
        espacioSeleccionado = $input.val();
        precioHora = parseFloat($input.data('precio-hora')) || 0;
        capacidadMax = parseInt($input.data('capacidad')) || 0;
        fianza = parseFloat($input.data('fianza')) || 0;

        $('.espacio-opcion').removeClass('seleccionado');
        $input.closest('.espacio-opcion').addClass('seleccionado');

        actualizarResumen();
    });

    // Al cambiar fechas/horas
    $('#fecha, #hora_inicio, #hora_fin').on('change', function() {
        actualizarResumen();
    });

    // Al cambiar numero de asistentes
    $('#num_asistentes').on('input', function() {
        var numAsistentes = parseInt($(this).val()) || 0;
        var $aviso = $('#capacidad-aviso');

        if (capacidadMax > 0 && numAsistentes > capacidadMax) {
            $aviso.text('<?php echo esc_js(__('Supera la capacidad maxima del espacio', 'flavor-platform')); ?> (' + capacidadMax + ')').show();
        } else {
            $aviso.hide();
        }
        actualizarResumen();
    });

    function actualizarResumen() {
        var fecha = $('#fecha').val();
        var horaInicio = $('#hora_inicio').val();
        var horaFin = $('#hora_fin').val();

        if (!fecha || !horaInicio || !horaFin) {
            $('#resumen-horario').hide();
            return;
        }

        // Calcular duracion
        var inicio = new Date('2000-01-01T' + horaInicio);
        var fin = new Date('2000-01-01T' + horaFin);
        var duracionMs = fin - inicio;

        if (duracionMs <= 0) {
            $('#resumen-horario').hide();
            return;
        }

        var duracionHoras = duracionMs / (1000 * 60 * 60);
        var duracionTexto = duracionHoras + ' <?php echo esc_js(__('hora(s)', 'flavor-platform')); ?>';

        $('#duracion-texto').text(duracionTexto);

        // Calcular precio
        if (precioHora > 0) {
            var precioTotal = duracionHoras * precioHora;
            $('#precio-estimado').text(precioTotal.toFixed(2) + '€');
            $('#precio-estimado-container').show();
        } else {
            $('#precio-estimado-container').hide();
        }

        $('#resumen-horario').show();

        // Actualizar resumen final
        actualizarResumenFinal();
    }

    function actualizarResumenFinal() {
        var $espacioCheck = $('input[name="espacio_id"]:checked');
        if (!$espacioCheck.length) {
            return;
        }

        var espacioNombre = $espacioCheck.closest('.espacio-opcion').find('.espacio-opcion-info strong').text();
        var fecha = $('#fecha').val();
        var horaInicio = $('#hora_inicio').val();
        var horaFin = $('#hora_fin').val();

        if (!fecha || !horaInicio || !horaFin) {
            return;
        }

        var fechaFormateada = new Date(fecha).toLocaleDateString('es-ES', {
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });

        var html = '<div class="resumen-detalle">';
        html += '<p><strong><?php echo esc_js(__('Espacio:', 'flavor-platform')); ?></strong> ' + espacioNombre + '</p>';
        html += '<p><strong><?php echo esc_js(__('Fecha:', 'flavor-platform')); ?></strong> ' + fechaFormateada + '</p>';
        html += '<p><strong><?php echo esc_js(__('Horario:', 'flavor-platform')); ?></strong> ' + horaInicio + ' - ' + horaFin + '</p>';

        if (fianza > 0) {
            html += '<p class="resumen-fianza"><strong><?php echo esc_js(__('Fianza requerida:', 'flavor-platform')); ?></strong> ' + fianza.toFixed(2) + '€</p>';
        }

        html += '</div>';

        $('#resumen-reserva').html(html);
    }

    // Envio del formulario
    $('#form-solicitar-reserva').on('submit', function(e) {
        e.preventDefault();

        var $form = $(this);
        var $btn = $('#btn-enviar-solicitud');
        var textoOriginal = $btn.html();

        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php echo esc_js(__('Enviando...', 'flavor-platform')); ?>');

        var formData = $form.serialize();
        formData += '&action=espacios_crear_reserva';
        formData += '&fecha_inicio=' + $('#fecha').val() + ' ' + $('#hora_inicio').val();
        formData += '&fecha_fin=' + $('#fecha').val() + ' ' + $('#hora_fin').val();

        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    // Mostrar mensaje de exito
                    $form.html('<div class="solicitar-exito">' +
                        '<span class="dashicons dashicons-yes-alt"></span>' +
                        '<h3><?php echo esc_js(__('Solicitud enviada', 'flavor-platform')); ?></h3>' +
                        '<p>' + (response.mensaje || '<?php echo esc_js(__('Tu solicitud ha sido enviada. Te notificaremos cuando sea revisada.', 'flavor-platform')); ?>') + '</p>' +
                        '<a href="?tab=mis-reservas" class="btn btn-primary"><?php echo esc_js(__('Ver mis reservas', 'flavor-platform')); ?></a>' +
                    '</div>');
                } else {
                    alert(response.error || '<?php echo esc_js(__('Error al enviar la solicitud', 'flavor-platform')); ?>');
                    $btn.prop('disabled', false).html(textoOriginal);
                }
            },
            error: function() {
                alert('<?php echo esc_js(__('Error de conexion', 'flavor-platform')); ?>');
                $btn.prop('disabled', false).html(textoOriginal);
            }
        });
    });

    // Inicializar si hay espacio preseleccionado
    if ($('input[name="espacio_id"]:checked').length) {
        $('input[name="espacio_id"]:checked').trigger('change');
    }
});
</script>
