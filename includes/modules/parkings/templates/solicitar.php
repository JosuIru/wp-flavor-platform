<?php
/**
 * Template: Solicitar Plaza de Parking
 *
 * Formulario para solicitar/reservar una plaza de parking.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!is_user_logged_in()) {
    echo '<div class="parkings-login-required">';
    echo '<p>' . esc_html__('Debes iniciar sesión para solicitar una plaza de parking.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(get_permalink())) . '" class="btn btn-primary">' . esc_html__('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

global $wpdb;

$tabla_parkings = $wpdb->prefix . 'flavor_parkings';
$tabla_plazas = $wpdb->prefix . 'flavor_parkings_plazas';
$tabla_reservas = $wpdb->prefix . 'flavor_parkings_reservas';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_parkings)) {
    echo '<div class="parkings-empty"><p>' . esc_html__('El módulo de parkings no está configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

$usuario_actual = wp_get_current_user();

// Parking preseleccionado por URL
$parking_seleccionado = isset($_GET['parking']) ? absint($_GET['parking']) : 0;

// Obtener parkings disponibles con plazas libres
$parkings_disponibles = $wpdb->get_results("
    SELECT
        p.id,
        p.nombre,
        p.direccion,
        p.horario,
        (SELECT COUNT(*) FROM $tabla_plazas pl
         LEFT JOIN $tabla_reservas r ON pl.id = r.plaza_id
            AND r.estado IN ('activa', 'confirmada')
            AND NOW() BETWEEN r.fecha_inicio AND r.fecha_fin
         WHERE pl.parking_id = p.id
           AND pl.estado = 'activa'
           AND r.id IS NULL) AS plazas_libres
    FROM $tabla_parkings p
    WHERE p.estado = 'activo'
    HAVING plazas_libres > 0
    ORDER BY p.nombre
");

// Tipos de plaza disponibles
$tipos_plaza = [
    'normal' => __('Normal', 'flavor-chat-ia'),
    'discapacitado' => __('Movilidad reducida', 'flavor-chat-ia'),
    'moto' => __('Moto', 'flavor-chat-ia'),
    'electrico' => __('Vehículo eléctrico', 'flavor-chat-ia'),
    'grande' => __('Plaza grande', 'flavor-chat-ia'),
];

// Duraciones predefinidas
$duraciones = [
    '1' => __('1 hora', 'flavor-chat-ia'),
    '2' => __('2 horas', 'flavor-chat-ia'),
    '4' => __('4 horas', 'flavor-chat-ia'),
    '8' => __('8 horas (jornada)', 'flavor-chat-ia'),
    '24' => __('24 horas (día completo)', 'flavor-chat-ia'),
    'custom' => __('Personalizado', 'flavor-chat-ia'),
];

$parkings_url = home_url('/mi-portal/parkings/');

// Generar nonce para el formulario
$nonce_reserva = wp_create_nonce('parkings_solicitar_plaza');
?>

<div class="parkings-solicitar">
    <header class="solicitar-header">
        <a href="<?php echo esc_url($parkings_url); ?>" class="btn-volver">
            <span class="dashicons dashicons-arrow-left-alt2"></span>
            <?php esc_html_e('Volver', 'flavor-chat-ia'); ?>
        </a>
        <div class="header-titulo">
            <h2><?php esc_html_e('Solicitar Plaza de Parking', 'flavor-chat-ia'); ?></h2>
            <p><?php esc_html_e('Completa el formulario para reservar tu plaza', 'flavor-chat-ia'); ?></p>
        </div>
    </header>

    <?php if (empty($parkings_disponibles)): ?>
        <div class="solicitar-no-disponible">
            <span class="dashicons dashicons-warning"></span>
            <h3><?php esc_html_e('No hay plazas disponibles', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('En este momento no hay plazas libres en ningún parking. Por favor, inténtalo más tarde.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url($parkings_url . 'ocupacion/'); ?>" class="btn btn-outline">
                <?php esc_html_e('Ver ocupación', 'flavor-chat-ia'); ?>
            </a>
        </div>
    <?php else: ?>
        <form id="form-solicitar-parking" class="formulario-solicitar" method="post">
            <input type="hidden" name="action" value="parkings_solicitar_plaza">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce_reserva); ?>">

            <!-- Paso 1: Seleccionar parking -->
            <section class="form-seccion">
                <h3 class="seccion-titulo">
                    <span class="seccion-numero">1</span>
                    <?php esc_html_e('Selecciona el parking', 'flavor-chat-ia'); ?>
                </h3>

                <div class="parkings-selector">
                    <?php foreach ($parkings_disponibles as $parking): ?>
                        <label class="parking-opcion <?php echo $parking_seleccionado == $parking->id ? 'seleccionado' : ''; ?>">
                            <input type="radio" name="parking_id" value="<?php echo esc_attr($parking->id); ?>"
                                   <?php checked($parking_seleccionado, $parking->id); ?> required>
                            <div class="opcion-contenido">
                                <span class="opcion-nombre"><?php echo esc_html($parking->nombre); ?></span>
                                <span class="opcion-direccion">
                                    <span class="dashicons dashicons-location"></span>
                                    <?php echo esc_html($parking->direccion); ?>
                                </span>
                                <?php if ($parking->horario): ?>
                                    <span class="opcion-horario">
                                        <span class="dashicons dashicons-clock"></span>
                                        <?php echo esc_html($parking->horario); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="opcion-plazas">
                                <span class="plazas-numero"><?php echo esc_html($parking->plazas_libres); ?></span>
                                <span class="plazas-label"><?php esc_html_e('libres', 'flavor-chat-ia'); ?></span>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- Paso 2: Fecha y duración -->
            <section class="form-seccion">
                <h3 class="seccion-titulo">
                    <span class="seccion-numero">2</span>
                    <?php esc_html_e('Fecha y duración', 'flavor-chat-ia'); ?>
                </h3>

                <div class="form-grid">
                    <div class="form-grupo">
                        <label for="fecha_inicio"><?php esc_html_e('Fecha y hora de entrada', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio"
                               min="<?php echo date('Y-m-d\TH:i'); ?>"
                               value="<?php echo date('Y-m-d\TH:i', strtotime('+15 minutes')); ?>" required>
                    </div>

                    <div class="form-grupo">
                        <label for="duracion"><?php esc_html_e('Duración', 'flavor-chat-ia'); ?></label>
                        <select id="duracion" name="duracion" required>
                            <?php foreach ($duraciones as $valor => $texto): ?>
                                <option value="<?php echo esc_attr($valor); ?>" <?php selected($valor, '2'); ?>>
                                    <?php echo esc_html($texto); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-grupo form-grupo--custom-duracion" style="display: none;">
                        <label for="fecha_fin"><?php esc_html_e('Fecha y hora de salida', 'flavor-chat-ia'); ?></label>
                        <input type="datetime-local" id="fecha_fin" name="fecha_fin">
                    </div>
                </div>

                <div class="duracion-preview">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span id="preview-fechas">
                        <?php esc_html_e('Selecciona fecha y duración', 'flavor-chat-ia'); ?>
                    </span>
                </div>
            </section>

            <!-- Paso 3: Datos del vehículo -->
            <section class="form-seccion">
                <h3 class="seccion-titulo">
                    <span class="seccion-numero">3</span>
                    <?php esc_html_e('Datos del vehículo', 'flavor-chat-ia'); ?>
                </h3>

                <div class="form-grid">
                    <div class="form-grupo">
                        <label for="matricula"><?php esc_html_e('Matrícula', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="matricula" name="matricula"
                               placeholder="<?php esc_attr_e('Ej: 1234 ABC', 'flavor-chat-ia'); ?>"
                               pattern="[A-Za-z0-9\s\-]+" maxlength="12" required>
                        <small class="form-ayuda"><?php esc_html_e('Necesaria para el acceso al parking', 'flavor-chat-ia'); ?></small>
                    </div>

                    <div class="form-grupo">
                        <label for="tipo_plaza"><?php esc_html_e('Tipo de plaza', 'flavor-chat-ia'); ?></label>
                        <select id="tipo_plaza" name="tipo_plaza">
                            <?php foreach ($tipos_plaza as $valor => $texto): ?>
                                <option value="<?php echo esc_attr($valor); ?>">
                                    <?php echo esc_html($texto); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Paso 4: Información adicional -->
            <section class="form-seccion">
                <h3 class="seccion-titulo">
                    <span class="seccion-numero">4</span>
                    <?php esc_html_e('Información adicional', 'flavor-chat-ia'); ?>
                </h3>

                <div class="form-grupo">
                    <label for="notas"><?php esc_html_e('Notas (opcional)', 'flavor-chat-ia'); ?></label>
                    <textarea id="notas" name="notas" rows="3"
                              placeholder="<?php esc_attr_e('Información adicional sobre tu reserva...', 'flavor-chat-ia'); ?>"></textarea>
                </div>

                <div class="form-grupo form-grupo--checkbox">
                    <label>
                        <input type="checkbox" name="recordatorio" value="1" checked>
                        <?php esc_html_e('Enviarme un recordatorio 30 minutos antes de que expire mi reserva', 'flavor-chat-ia'); ?>
                    </label>
                </div>
            </section>

            <!-- Resumen y envío -->
            <section class="form-seccion form-seccion--resumen">
                <div class="resumen-reserva" id="resumen-reserva">
                    <h4><?php esc_html_e('Resumen de tu reserva', 'flavor-chat-ia'); ?></h4>
                    <p class="resumen-placeholder"><?php esc_html_e('Completa el formulario para ver el resumen', 'flavor-chat-ia'); ?></p>
                </div>

                <div class="form-acciones">
                    <a href="<?php echo esc_url($parkings_url); ?>" class="btn btn-outline">
                        <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                    <button type="submit" class="btn btn-primary btn-lg">
                        <span class="dashicons dashicons-yes"></span>
                        <?php esc_html_e('Confirmar reserva', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </section>
        </form>
    <?php endif; ?>
</div>

<style>
.parkings-solicitar { max-width: 800px; margin: 0 auto; }

.solicitar-header { margin-bottom: 2rem; }
.btn-volver { display: inline-flex; align-items: center; gap: 0.25rem; color: #6b7280; font-size: 0.875rem; text-decoration: none; margin-bottom: 1rem; }
.btn-volver:hover { color: #3b82f6; }
.header-titulo h2 { margin: 0 0 0.25rem; font-size: 1.5rem; color: #1f2937; }
.header-titulo p { margin: 0; color: #6b7280; }

.solicitar-no-disponible { text-align: center; padding: 3rem 1rem; background: white; border-radius: 12px; }
.solicitar-no-disponible .dashicons { font-size: 3rem; width: auto; height: auto; color: #f59e0b; margin-bottom: 1rem; }
.solicitar-no-disponible h3 { margin: 0 0 0.5rem; color: #1f2937; }
.solicitar-no-disponible p { margin: 0 0 1.5rem; color: #6b7280; }

.formulario-solicitar { display: flex; flex-direction: column; gap: 1.5rem; }

.form-seccion { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
.seccion-titulo { display: flex; align-items: center; gap: 0.75rem; margin: 0 0 1.25rem; font-size: 1.125rem; color: #1f2937; }
.seccion-numero { display: flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #3b82f6; color: white; border-radius: 50%; font-size: 0.875rem; font-weight: 600; }

.parkings-selector { display: flex; flex-direction: column; gap: 0.75rem; }
.parking-opcion { display: flex; align-items: center; gap: 1rem; padding: 1rem; border: 2px solid #e5e7eb; border-radius: 10px; cursor: pointer; transition: all 0.2s; }
.parking-opcion:hover { border-color: #3b82f6; background: #f8fafc; }
.parking-opcion.seleccionado, .parking-opcion:has(input:checked) { border-color: #3b82f6; background: #eff6ff; }
.parking-opcion input { display: none; }
.opcion-contenido { flex: 1; }
.opcion-nombre { display: block; font-weight: 600; color: #1f2937; margin-bottom: 0.25rem; }
.opcion-direccion, .opcion-horario { display: flex; align-items: center; gap: 0.25rem; font-size: 0.8rem; color: #6b7280; }
.opcion-plazas { text-align: center; padding: 0.5rem 0.75rem; background: #d1fae5; border-radius: 8px; }
.plazas-numero { display: block; font-size: 1.25rem; font-weight: 700; color: #059669; line-height: 1; }
.plazas-label { font-size: 0.65rem; color: #059669; text-transform: uppercase; letter-spacing: 0.05em; }

.form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
.form-grupo { display: flex; flex-direction: column; gap: 0.375rem; }
.form-grupo label { font-size: 0.875rem; font-weight: 500; color: #374151; }
.form-grupo input, .form-grupo select, .form-grupo textarea { padding: 0.625rem 0.75rem; border: 1px solid #d1d5db; border-radius: 8px; font-size: 0.9rem; transition: border-color 0.2s; }
.form-grupo input:focus, .form-grupo select:focus, .form-grupo textarea:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.form-ayuda { font-size: 0.75rem; color: #9ca3af; }
.form-grupo--checkbox label { display: flex; align-items: center; gap: 0.5rem; font-weight: 400; cursor: pointer; }
.form-grupo--checkbox input { width: auto; }

.duracion-preview { display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; padding: 0.75rem 1rem; background: #f3f4f6; border-radius: 8px; font-size: 0.875rem; color: #4b5563; }

.form-seccion--resumen { background: #f9fafb; }
.resumen-reserva { padding: 1rem; background: white; border-radius: 8px; margin-bottom: 1rem; }
.resumen-reserva h4 { margin: 0 0 0.75rem; font-size: 1rem; color: #1f2937; }
.resumen-placeholder { margin: 0; color: #9ca3af; font-style: italic; }

.form-acciones { display: flex; justify-content: flex-end; gap: 0.75rem; }

.parkings-login-required { text-align: center; padding: 3rem 1rem; }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.625rem 1.25rem; border-radius: 8px; font-size: 0.875rem; font-weight: 500; text-decoration: none; cursor: pointer; border: none; transition: all 0.2s; }
.btn-primary { background: #3b82f6; color: white; }
.btn-primary:hover { background: #2563eb; }
.btn-outline { background: transparent; border: 1px solid #d1d5db; color: #374151; }
.btn-outline:hover { background: #f3f4f6; }
.btn-lg { padding: 0.75rem 1.5rem; font-size: 1rem; }

@media (max-width: 640px) {
    .form-grid { grid-template-columns: 1fr; }
    .form-acciones { flex-direction: column; }
    .form-acciones .btn { width: 100%; justify-content: center; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selectDuracion = document.getElementById('duracion');
    var grupoCustomDuracion = document.querySelector('.form-grupo--custom-duracion');
    var inputFechaInicio = document.getElementById('fecha_inicio');
    var inputFechaFin = document.getElementById('fecha_fin');
    var previewFechas = document.getElementById('preview-fechas');

    // Mostrar/ocultar campo de fecha fin personalizada
    selectDuracion.addEventListener('change', function() {
        if (this.value === 'custom') {
            grupoCustomDuracion.style.display = 'block';
            inputFechaFin.required = true;
        } else {
            grupoCustomDuracion.style.display = 'none';
            inputFechaFin.required = false;
        }
        actualizarPreview();
    });

    // Actualizar preview de fechas
    function actualizarPreview() {
        var fechaInicio = inputFechaInicio.value;
        var duracion = selectDuracion.value;

        if (!fechaInicio) {
            previewFechas.textContent = '<?php echo esc_js(__('Selecciona fecha y duración', 'flavor-chat-ia')); ?>';
            return;
        }

        var inicio = new Date(fechaInicio);
        var fin;

        if (duracion === 'custom' && inputFechaFin.value) {
            fin = new Date(inputFechaFin.value);
        } else if (duracion !== 'custom') {
            fin = new Date(inicio.getTime() + parseInt(duracion) * 60 * 60 * 1000);
        } else {
            previewFechas.textContent = '<?php echo esc_js(__('Selecciona la fecha de salida', 'flavor-chat-ia')); ?>';
            return;
        }

        var opciones = { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' };
        previewFechas.textContent = inicio.toLocaleDateString('es-ES', opciones) + ' → ' + fin.toLocaleDateString('es-ES', opciones);
    }

    inputFechaInicio.addEventListener('change', actualizarPreview);
    inputFechaFin.addEventListener('change', actualizarPreview);
    selectDuracion.addEventListener('change', actualizarPreview);

    // Selección visual de parking
    document.querySelectorAll('.parking-opcion').forEach(function(opcion) {
        opcion.addEventListener('click', function() {
            document.querySelectorAll('.parking-opcion').forEach(function(el) {
                el.classList.remove('seleccionado');
            });
            this.classList.add('seleccionado');
        });
    });

    // Inicializar preview
    actualizarPreview();
});
</script>
