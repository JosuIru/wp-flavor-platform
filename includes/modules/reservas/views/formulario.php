<?php
/**
 * Formulario para hacer una reserva
 *
 * @package FlavorChatIA
 * @subpackage Reservas
 */

if (!defined('ABSPATH')) {
    exit;
}

// Obtener configuración del módulo
$module = Flavor_Chat_Module_Loader::get_instance()->get_module('reservas');
$settings = $module ? $module->get_settings() : [];

$hora_apertura = $settings['hora_apertura'] ?? '09:00';
$hora_cierre = $settings['hora_cierre'] ?? '22:00';
$dias_antelacion = $settings['dias_antelacion'] ?? 30;
$tipos_servicio = $settings['tipos_servicio'] ?? [
    'mesa_restaurante' => __('Mesa de Restaurante', 'flavor-chat-ia'),
    'espacio_coworking' => __('Espacio Coworking', 'flavor-chat-ia'),
    'clase_deportiva' => __('Clase Deportiva', 'flavor-chat-ia'),
];

// Usuario actual
$current_user = wp_get_current_user();
$user_name = is_user_logged_in() ? $current_user->display_name : '';
$user_email = is_user_logged_in() ? $current_user->user_email : '';

// Tipo de servicio preseleccionado
$tipo_preseleccionado = isset($_GET['tipo']) ? sanitize_text_field($_GET['tipo']) : '';

// Fecha mínima y máxima
$fecha_minima = date('Y-m-d');
$fecha_maxima = date('Y-m-d', strtotime("+{$dias_antelacion} days"));
?>

<div class="flavor-reservas-formulario">
    <header class="flavor-form-header">
        <h2><?php _e('Nueva Reserva', 'flavor-chat-ia'); ?></h2>
        <p class="flavor-form-subtitle">
            <?php _e('Completa el formulario para solicitar tu reserva', 'flavor-chat-ia'); ?>
        </p>
    </header>

    <form id="form-reserva" class="flavor-form" method="post">
        <?php wp_nonce_field('flavor_reserva_form', 'reserva_nonce'); ?>
        <input type="hidden" name="action" value="flavor_crear_reserva">

        <!-- Tipo de servicio -->
        <section class="flavor-form-section">
            <h3><?php _e('Tipo de Reserva', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-service-grid">
                <?php foreach ($tipos_servicio as $key => $label): ?>
                    <label class="flavor-service-card <?php echo $tipo_preseleccionado === $key ? 'selected' : ''; ?>">
                        <input type="radio" name="tipo_servicio" value="<?php echo esc_attr($key); ?>"
                               <?php checked($tipo_preseleccionado, $key); ?> required>
                        <span class="service-icon">
                            <?php
                            $icons = [
                                'mesa_restaurante' => '🍽️',
                                'espacio_coworking' => '💻',
                                'clase_deportiva' => '🏋️',
                            ];
                            echo $icons[$key] ?? '📅';
                            ?>
                        </span>
                        <span class="service-name"><?php echo esc_html($label); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- Fecha y hora -->
        <section class="flavor-form-section">
            <h3><?php _e('Fecha y Hora', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="fecha_reserva" class="required"><?php _e('Fecha', 'flavor-chat-ia'); ?></label>
                    <input type="date" id="fecha_reserva" name="fecha_reserva"
                           min="<?php echo esc_attr($fecha_minima); ?>"
                           max="<?php echo esc_attr($fecha_maxima); ?>"
                           required class="flavor-input">
                    <small class="flavor-form-hint">
                        <?php printf(__('Puedes reservar hasta %d días de antelación', 'flavor-chat-ia'), $dias_antelacion); ?>
                    </small>
                </div>

                <div class="flavor-form-group">
                    <label for="hora_inicio" class="required"><?php _e('Hora de inicio', 'flavor-chat-ia'); ?></label>
                    <input type="time" id="hora_inicio" name="hora_inicio"
                           min="<?php echo esc_attr($hora_apertura); ?>"
                           max="<?php echo esc_attr($hora_cierre); ?>"
                           required class="flavor-input">
                </div>

                <div class="flavor-form-group">
                    <label for="hora_fin" class="required"><?php _e('Hora de fin', 'flavor-chat-ia'); ?></label>
                    <input type="time" id="hora_fin" name="hora_fin"
                           min="<?php echo esc_attr($hora_apertura); ?>"
                           max="<?php echo esc_attr($hora_cierre); ?>"
                           required class="flavor-input">
                </div>
            </div>

            <div class="flavor-form-group">
                <label for="num_personas" class="required"><?php _e('Número de personas', 'flavor-chat-ia'); ?></label>
                <input type="number" id="num_personas" name="num_personas"
                       min="1" max="50" value="1" required class="flavor-input"
                       style="max-width: 120px;">
            </div>
        </section>

        <!-- Datos de contacto -->
        <section class="flavor-form-section">
            <h3><?php _e('Datos de contacto', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="nombre_cliente" class="required"><?php _e('Nombre completo', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="nombre_cliente" name="nombre_cliente"
                           value="<?php echo esc_attr($user_name); ?>"
                           required maxlength="200" class="flavor-input"
                           placeholder="<?php esc_attr_e('Tu nombre', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="email_cliente" class="required"><?php _e('Email', 'flavor-chat-ia'); ?></label>
                    <input type="email" id="email_cliente" name="email_cliente"
                           value="<?php echo esc_attr($user_email); ?>"
                           required maxlength="200" class="flavor-input"
                           placeholder="<?php esc_attr_e('tu@email.com', 'flavor-chat-ia'); ?>">
                </div>
            </div>

            <div class="flavor-form-group">
                <label for="telefono_cliente"><?php _e('Teléfono', 'flavor-chat-ia'); ?></label>
                <input type="tel" id="telefono_cliente" name="telefono_cliente"
                       maxlength="50" class="flavor-input"
                       placeholder="<?php esc_attr_e('+34 600 000 000', 'flavor-chat-ia'); ?>">
            </div>
        </section>

        <!-- Notas -->
        <section class="flavor-form-section">
            <h3><?php _e('Información adicional', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-group">
                <label for="notas"><?php _e('Notas o peticiones especiales', 'flavor-chat-ia'); ?></label>
                <textarea id="notas" name="notas" rows="3" class="flavor-textarea"
                          placeholder="<?php esc_attr_e('Alergias, necesidades especiales, etc.', 'flavor-chat-ia'); ?>"></textarea>
            </div>
        </section>

        <!-- Resumen -->
        <section class="flavor-form-section flavor-reserva-resumen" id="resumen-reserva" style="display: none;">
            <h3><?php _e('Resumen de tu reserva', 'flavor-chat-ia'); ?></h3>
            <div class="resumen-content">
                <p><strong><?php _e('Tipo:', 'flavor-chat-ia'); ?></strong> <span id="resumen-tipo"></span></p>
                <p><strong><?php _e('Fecha:', 'flavor-chat-ia'); ?></strong> <span id="resumen-fecha"></span></p>
                <p><strong><?php _e('Horario:', 'flavor-chat-ia'); ?></strong> <span id="resumen-horario"></span></p>
                <p><strong><?php _e('Personas:', 'flavor-chat-ia'); ?></strong> <span id="resumen-personas"></span></p>
            </div>
        </section>

        <!-- Botones -->
        <div class="flavor-form-actions">
            <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                <?php _e('Solicitar Reserva', 'flavor-chat-ia'); ?>
            </button>
            <a href="<?php echo esc_url(home_url('/mi-portal/reservas/')); ?>" class="flavor-btn flavor-btn-secondary flavor-btn-lg">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
        </div>

        <p class="flavor-form-disclaimer">
            <?php _e('Al solicitar la reserva, recibirás un email de confirmación. La reserva queda pendiente hasta que sea confirmada.', 'flavor-chat-ia'); ?>
        </p>
    </form>
</div>

<style>
.flavor-reservas-formulario {
    max-width: 700px;
    margin: 0 auto;
    padding: var(--flavor-spacing-lg, 1.5rem);
}

.flavor-form-header {
    text-align: center;
    margin-bottom: var(--flavor-spacing-xl, 2rem);
}

.flavor-form-header h2 {
    margin: 0 0 var(--flavor-spacing-sm, 0.5rem);
    font-size: 1.75rem;
    color: var(--flavor-text, #1f2937);
}

.flavor-form-subtitle {
    color: var(--flavor-text-secondary, #6b7280);
    margin: 0;
}

.flavor-form-section {
    background: var(--flavor-bg, #fff);
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: var(--flavor-radius-lg, 0.75rem);
    padding: var(--flavor-spacing-lg, 1.5rem);
    margin-bottom: var(--flavor-spacing-lg, 1.5rem);
}

.flavor-form-section h3 {
    margin: 0 0 var(--flavor-spacing-md, 1rem);
    font-size: 1.1rem;
    color: var(--flavor-text, #1f2937);
    padding-bottom: var(--flavor-spacing-sm, 0.5rem);
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.flavor-service-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--flavor-spacing-md, 1rem);
}

.flavor-service-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--flavor-spacing-lg, 1.5rem);
    border: 2px solid var(--flavor-border, #e5e7eb);
    border-radius: var(--flavor-radius-lg, 0.75rem);
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
}

.flavor-service-card:hover {
    border-color: var(--flavor-primary, #3b82f6);
    background: rgba(59, 130, 246, 0.05);
}

.flavor-service-card.selected,
.flavor-service-card:has(input:checked) {
    border-color: var(--flavor-primary, #3b82f6);
    background: rgba(59, 130, 246, 0.1);
}

.flavor-service-card input {
    position: absolute;
    opacity: 0;
}

.service-icon {
    font-size: 2rem;
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.service-name {
    font-weight: 500;
    color: var(--flavor-text, #1f2937);
}

.flavor-form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: var(--flavor-spacing-md, 1rem);
}

.flavor-form-group {
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.flavor-form-group label {
    display: block;
    margin-bottom: var(--flavor-spacing-xs, 0.25rem);
    font-weight: 500;
    color: var(--flavor-text, #1f2937);
}

.flavor-form-group label.required::after {
    content: " *";
    color: var(--flavor-danger, #ef4444);
}

.flavor-input,
.flavor-textarea {
    width: 100%;
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: var(--flavor-radius-md, 0.5rem);
    font-size: 1rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.flavor-input:focus,
.flavor-textarea:focus {
    outline: none;
    border-color: var(--flavor-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flavor-form-hint {
    display: block;
    margin-top: var(--flavor-spacing-xs, 0.25rem);
    font-size: 0.875rem;
    color: var(--flavor-text-muted, #9ca3af);
}

.flavor-reserva-resumen {
    background: var(--flavor-bg-secondary, #f3f4f6);
}

.flavor-reserva-resumen .resumen-content p {
    margin: var(--flavor-spacing-xs, 0.25rem) 0;
}

.flavor-form-actions {
    display: flex;
    gap: var(--flavor-spacing-md, 1rem);
    justify-content: center;
    padding-top: var(--flavor-spacing-lg, 1.5rem);
}

.flavor-form-disclaimer {
    text-align: center;
    font-size: 0.875rem;
    color: var(--flavor-text-muted, #9ca3af);
    margin-top: var(--flavor-spacing-md, 1rem);
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border: none;
    border-radius: var(--flavor-radius-md, 0.5rem);
    font-weight: 500;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.15s ease;
}

.flavor-btn-primary {
    background: var(--flavor-primary, #3b82f6);
    color: #fff;
}

.flavor-btn-primary:hover {
    background: var(--flavor-primary-dark, #2563eb);
}

.flavor-btn-secondary {
    background: var(--flavor-bg-secondary, #f3f4f6);
    color: var(--flavor-text, #1f2937);
    border: 1px solid var(--flavor-border, #e5e7eb);
}

.flavor-btn-lg {
    padding: var(--flavor-spacing-md, 1rem) var(--flavor-spacing-xl, 2rem);
    font-size: 1rem;
}

@media (max-width: 600px) {
    .flavor-form-actions {
        flex-direction: column;
    }

    .flavor-btn-lg {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('form-reserva');
    const resumenSection = document.getElementById('resumen-reserva');

    // Servicios disponibles
    const servicios = <?php echo json_encode($tipos_servicio); ?>;

    // Actualizar resumen
    function actualizarResumen() {
        const tipoInput = document.querySelector('input[name="tipo_servicio"]:checked');
        const fecha = document.getElementById('fecha_reserva').value;
        const horaInicio = document.getElementById('hora_inicio').value;
        const horaFin = document.getElementById('hora_fin').value;
        const personas = document.getElementById('num_personas').value;

        if (tipoInput && fecha && horaInicio) {
            resumenSection.style.display = 'block';
            document.getElementById('resumen-tipo').textContent = servicios[tipoInput.value] || tipoInput.value;
            document.getElementById('resumen-fecha').textContent = new Date(fecha).toLocaleDateString('es-ES', {
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
            });
            document.getElementById('resumen-horario').textContent = horaInicio + (horaFin ? ' - ' + horaFin : '');
            document.getElementById('resumen-personas').textContent = personas + ' persona(s)';
        }
    }

    // Eventos para actualizar resumen
    form.querySelectorAll('input, select').forEach(function(el) {
        el.addEventListener('change', actualizarResumen);
    });

    // Validación de horas
    document.getElementById('hora_inicio').addEventListener('change', function() {
        const horaFin = document.getElementById('hora_fin');
        horaFin.min = this.value;
    });

    // Submit via AJAX
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;

        submitBtn.disabled = true;
        submitBtn.textContent = '<?php _e("Procesando...", "flavor-chat-ia"); ?>';

        fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.message || '<?php _e("Reserva solicitada correctamente", "flavor-chat-ia"); ?>');
                if (data.data.redirect) {
                    window.location.href = data.data.redirect;
                }
            } else {
                alert(data.data.message || '<?php _e("Error al procesar la reserva", "flavor-chat-ia"); ?>');
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('<?php _e("Error de conexión", "flavor-chat-ia"); ?>');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
});
</script>
