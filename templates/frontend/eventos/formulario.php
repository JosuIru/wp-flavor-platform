<?php
/**
 * Formulario para crear/editar eventos (Frontend)
 *
 * @package FlavorChatIA
 * @subpackage Eventos
 */

if (!defined('ABSPATH')) {
    exit;
}

// Verificar permisos
if (!is_user_logged_in()) {
    echo '<div class="flavor-alert flavor-alert-warning">';
    echo '<p>' . __('Debes iniciar sesión para crear eventos.', 'flavor-chat-ia') . '</p>';
    echo '<a href="' . esc_url(wp_login_url(flavor_current_request_url())) . '" class="flavor-btn flavor-btn-primary">' . __('Iniciar sesión', 'flavor-chat-ia') . '</a>';
    echo '</div>';
    return;
}

// Verificar capacidad
if (!current_user_can('edit_posts')) {
    echo '<div class="flavor-alert flavor-alert-error">';
    echo '<p>' . __('No tienes permisos para crear eventos.', 'flavor-chat-ia') . '</p>';
    echo '</div>';
    return;
}

// Obtener evento existente si estamos editando
$evento_id = isset($_GET['id']) ? absint($_GET['id']) : 0;
$evento = null;
$is_edit = false;

if ($evento_id) {
    global $wpdb;
    $tabla = $wpdb->prefix . 'flavor_eventos';
    $evento = $wpdb->get_row($wpdb->prepare("SELECT * FROM $tabla WHERE id = %d", $evento_id), ARRAY_A);

    if ($evento) {
        // Verificar que el usuario sea el organizador
        if ($evento['organizador_id'] != get_current_user_id() && !current_user_can('manage_options')) {
            echo '<div class="flavor-alert flavor-alert-error">';
            echo '<p>' . __('No tienes permisos para editar este evento.', 'flavor-chat-ia') . '</p>';
            echo '</div>';
            return;
        }
        $is_edit = true;
    }
}

// Tipos de evento
$tipos_evento = [
    'conferencia' => __('Conferencia', 'flavor-chat-ia'),
    'taller'      => __('Taller', 'flavor-chat-ia'),
    'charla'      => __('Charla', 'flavor-chat-ia'),
    'festival'    => __('Festival', 'flavor-chat-ia'),
    'deportivo'   => __('Deportivo', 'flavor-chat-ia'),
    'cultural'    => __('Cultural', 'flavor-chat-ia'),
    'social'      => __('Social', 'flavor-chat-ia'),
    'networking'  => __('Networking', 'flavor-chat-ia'),
];

// Valores del formulario
$titulo = $evento['titulo'] ?? '';
$descripcion = $evento['descripcion'] ?? '';
$contenido = $evento['contenido'] ?? '';
$tipo = $evento['tipo'] ?? 'social';
$fecha_inicio = $evento['fecha_inicio'] ?? '';
$fecha_fin = $evento['fecha_fin'] ?? '';
$ubicacion = $evento['ubicacion'] ?? '';
$direccion = $evento['direccion'] ?? '';
$precio = $evento['precio'] ?? 0;
$precio_socios = $evento['precio_socios'] ?? 0;
$aforo_maximo = $evento['aforo_maximo'] ?? 0;
$es_online = $evento['es_online'] ?? 0;
$url_online = $evento['url_online'] ?? '';
$imagen = $evento['imagen'] ?? '';
$estado = $evento['estado'] ?? 'borrador';
?>

<div class="flavor-eventos-formulario">
    <header class="flavor-form-header">
        <h2>
            <?php echo $is_edit ? __('Editar Evento', 'flavor-chat-ia') : __('Crear Nuevo Evento', 'flavor-chat-ia'); ?>
        </h2>
        <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="flavor-btn flavor-btn-secondary">
            <?php _e('Volver', 'flavor-chat-ia'); ?>
        </a>
    </header>

    <form id="form-evento" class="flavor-form" method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('flavor_evento_form', 'evento_nonce'); ?>
        <input type="hidden" name="action" value="flavor_guardar_evento">
        <input type="hidden" name="evento_id" value="<?php echo esc_attr($evento_id); ?>">

        <!-- Información básica -->
        <section class="flavor-form-section">
            <h3><?php _e('Información básica', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-group">
                <label for="titulo" class="required"><?php _e('Título del evento', 'flavor-chat-ia'); ?></label>
                <input type="text" id="titulo" name="titulo" value="<?php echo esc_attr($titulo); ?>"
                       required maxlength="255" class="flavor-input"
                       placeholder="<?php esc_attr_e('Nombre del evento', 'flavor-chat-ia'); ?>">
            </div>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="tipo" class="required"><?php _e('Tipo de evento', 'flavor-chat-ia'); ?></label>
                    <select id="tipo" name="tipo" required class="flavor-select">
                        <?php foreach ($tipos_evento as $value => $label): ?>
                            <option value="<?php echo esc_attr($value); ?>" <?php selected($tipo, $value); ?>>
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="estado"><?php _e('Estado', 'flavor-chat-ia'); ?></label>
                    <select id="estado" name="estado" class="flavor-select">
                        <option value="borrador" <?php selected($estado, 'borrador'); ?>><?php _e('Borrador', 'flavor-chat-ia'); ?></option>
                        <option value="publicado" <?php selected($estado, 'publicado'); ?>><?php _e('Publicado', 'flavor-chat-ia'); ?></option>
                    </select>
                </div>
            </div>

            <div class="flavor-form-group">
                <label for="descripcion"><?php _e('Descripción breve', 'flavor-chat-ia'); ?></label>
                <textarea id="descripcion" name="descripcion" rows="3" class="flavor-textarea"
                          placeholder="<?php esc_attr_e('Resumen del evento (máx. 300 caracteres)', 'flavor-chat-ia'); ?>"
                          maxlength="500"><?php echo esc_textarea($descripcion); ?></textarea>
            </div>

            <div class="flavor-form-group">
                <label for="contenido"><?php _e('Descripción completa', 'flavor-chat-ia'); ?></label>
                <textarea id="contenido" name="contenido" rows="8" class="flavor-textarea"
                          placeholder="<?php esc_attr_e('Descripción detallada del evento...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($contenido); ?></textarea>
            </div>
        </section>

        <!-- Fecha y hora -->
        <section class="flavor-form-section">
            <h3><?php _e('Fecha y hora', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="fecha_inicio" class="required"><?php _e('Fecha y hora de inicio', 'flavor-chat-ia'); ?></label>
                    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio"
                           value="<?php echo esc_attr($fecha_inicio ? date('Y-m-d\TH:i', strtotime($fecha_inicio)) : ''); ?>"
                           required class="flavor-input">
                </div>

                <div class="flavor-form-group">
                    <label for="fecha_fin"><?php _e('Fecha y hora de fin', 'flavor-chat-ia'); ?></label>
                    <input type="datetime-local" id="fecha_fin" name="fecha_fin"
                           value="<?php echo esc_attr($fecha_fin ? date('Y-m-d\TH:i', strtotime($fecha_fin)) : ''); ?>"
                           class="flavor-input">
                </div>
            </div>
        </section>

        <!-- Ubicación -->
        <section class="flavor-form-section">
            <h3><?php _e('Ubicación', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-group">
                <label class="flavor-checkbox-label">
                    <input type="checkbox" id="es_online" name="es_online" value="1" <?php checked($es_online, 1); ?>>
                    <?php _e('Es un evento online', 'flavor-chat-ia'); ?>
                </label>
            </div>

            <div id="ubicacion-presencial" class="<?php echo $es_online ? 'hidden' : ''; ?>">
                <div class="flavor-form-group">
                    <label for="ubicacion"><?php _e('Nombre del lugar', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="ubicacion" name="ubicacion" value="<?php echo esc_attr($ubicacion); ?>"
                           class="flavor-input" placeholder="<?php esc_attr_e('Ej: Centro Cultural, Sala de reuniones...', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-group">
                    <label for="direccion"><?php _e('Dirección', 'flavor-chat-ia'); ?></label>
                    <input type="text" id="direccion" name="direccion" value="<?php echo esc_attr($direccion); ?>"
                           class="flavor-input" placeholder="<?php esc_attr_e('Calle, número, ciudad...', 'flavor-chat-ia'); ?>">
                </div>
            </div>

            <div id="ubicacion-online" class="<?php echo !$es_online ? 'hidden' : ''; ?>">
                <div class="flavor-form-group">
                    <label for="url_online"><?php _e('Enlace de la reunión', 'flavor-chat-ia'); ?></label>
                    <input type="url" id="url_online" name="url_online" value="<?php echo esc_attr($url_online); ?>"
                           class="flavor-input" placeholder="<?php esc_attr_e('https://meet.google.com/...', 'flavor-chat-ia'); ?>">
                </div>
            </div>
        </section>

        <!-- Precio y aforo -->
        <section class="flavor-form-section">
            <h3><?php _e('Precio y aforo', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-row">
                <div class="flavor-form-group">
                    <label for="precio"><?php _e('Precio general', 'flavor-chat-ia'); ?></label>
                    <div class="flavor-input-group">
                        <input type="number" id="precio" name="precio" value="<?php echo esc_attr($precio); ?>"
                               min="0" step="0.01" class="flavor-input">
                        <span class="flavor-input-addon">&euro;</span>
                    </div>
                    <small class="flavor-form-hint"><?php _e('0 = Gratuito', 'flavor-chat-ia'); ?></small>
                </div>

                <div class="flavor-form-group">
                    <label for="precio_socios"><?php _e('Precio para miembros', 'flavor-chat-ia'); ?></label>
                    <div class="flavor-input-group">
                        <input type="number" id="precio_socios" name="precio_socios" value="<?php echo esc_attr($precio_socios); ?>"
                               min="0" step="0.01" class="flavor-input">
                        <span class="flavor-input-addon">&euro;</span>
                    </div>
                </div>
            </div>

            <div class="flavor-form-group">
                <label for="aforo_maximo"><?php _e('Aforo máximo', 'flavor-chat-ia'); ?></label>
                <input type="number" id="aforo_maximo" name="aforo_maximo" value="<?php echo esc_attr($aforo_maximo); ?>"
                       min="0" class="flavor-input" style="max-width: 150px;">
                <small class="flavor-form-hint"><?php _e('0 = Sin límite', 'flavor-chat-ia'); ?></small>
            </div>
        </section>

        <!-- Imagen -->
        <section class="flavor-form-section">
            <h3><?php _e('Imagen del evento', 'flavor-chat-ia'); ?></h3>

            <div class="flavor-form-group">
                <label for="imagen"><?php _e('Imagen destacada', 'flavor-chat-ia'); ?></label>

                <?php if ($imagen): ?>
                    <div class="flavor-image-preview">
                        <img src="<?php echo esc_url($imagen); ?>" alt="" style="max-width: 300px; border-radius: 8px;">
                        <button type="button" class="flavor-btn flavor-btn-small flavor-btn-danger" id="btn-eliminar-imagen">
                            <?php _e('Eliminar', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                    <input type="hidden" name="imagen_actual" value="<?php echo esc_attr($imagen); ?>">
                <?php endif; ?>

                <input type="file" id="imagen" name="imagen" accept="image/*" class="flavor-input-file">
                <small class="flavor-form-hint"><?php _e('Formatos: JPG, PNG, GIF. Máx: 2MB', 'flavor-chat-ia'); ?></small>
            </div>
        </section>

        <!-- Botones -->
        <div class="flavor-form-actions">
            <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                <?php echo $is_edit ? __('Guardar cambios', 'flavor-chat-ia') : __('Crear evento', 'flavor-chat-ia'); ?>
            </button>

            <a href="<?php echo esc_url(home_url('/eventos/')); ?>" class="flavor-btn flavor-btn-secondary flavor-btn-lg">
                <?php _e('Cancelar', 'flavor-chat-ia'); ?>
            </a>
        </div>
    </form>
</div>

<style>
.flavor-eventos-formulario {
    max-width: 800px;
    margin: 0 auto;
    padding: var(--flavor-spacing-lg, 1.5rem);
}

.flavor-form-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--flavor-spacing-xl, 2rem);
    padding-bottom: var(--flavor-spacing-md, 1rem);
    border-bottom: 1px solid var(--flavor-border, #e5e7eb);
}

.flavor-form-header h2 {
    margin: 0;
    font-size: 1.5rem;
    color: var(--flavor-text, #1f2937);
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

.flavor-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: var(--flavor-spacing-md, 1rem);
}

@media (max-width: 600px) {
    .flavor-form-row {
        grid-template-columns: 1fr;
    }
}

.flavor-input,
.flavor-select,
.flavor-textarea {
    width: 100%;
    padding: var(--flavor-spacing-sm, 0.5rem) var(--flavor-spacing-md, 1rem);
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-radius: var(--flavor-radius-md, 0.5rem);
    font-size: 1rem;
    transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.flavor-input:focus,
.flavor-select:focus,
.flavor-textarea:focus {
    outline: none;
    border-color: var(--flavor-primary, #3b82f6);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flavor-input-group {
    display: flex;
    align-items: stretch;
}

.flavor-input-group .flavor-input {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.flavor-input-addon {
    display: flex;
    align-items: center;
    padding: 0 var(--flavor-spacing-md, 1rem);
    background: var(--flavor-bg-secondary, #f3f4f6);
    border: 1px solid var(--flavor-border, #e5e7eb);
    border-left: 0;
    border-radius: 0 var(--flavor-radius-md, 0.5rem) var(--flavor-radius-md, 0.5rem) 0;
    color: var(--flavor-text-secondary, #6b7280);
}

.flavor-form-hint {
    display: block;
    margin-top: var(--flavor-spacing-xs, 0.25rem);
    font-size: 0.875rem;
    color: var(--flavor-text-muted, #9ca3af);
}

.flavor-checkbox-label {
    display: flex;
    align-items: center;
    gap: var(--flavor-spacing-sm, 0.5rem);
    cursor: pointer;
}

.flavor-checkbox-label input[type="checkbox"] {
    width: 18px;
    height: 18px;
}

.flavor-input-file {
    padding: var(--flavor-spacing-sm, 0.5rem);
}

.flavor-image-preview {
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.flavor-image-preview img {
    display: block;
    margin-bottom: var(--flavor-spacing-sm, 0.5rem);
}

.flavor-form-actions {
    display: flex;
    gap: var(--flavor-spacing-md, 1rem);
    padding-top: var(--flavor-spacing-lg, 1.5rem);
    border-top: 1px solid var(--flavor-border, #e5e7eb);
}

.hidden {
    display: none !important;
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

.flavor-btn-secondary:hover {
    background: var(--flavor-bg-tertiary, #e5e7eb);
}

.flavor-btn-lg {
    padding: var(--flavor-spacing-md, 1rem) var(--flavor-spacing-xl, 2rem);
    font-size: 1rem;
}

.flavor-btn-small {
    padding: var(--flavor-spacing-xs, 0.25rem) var(--flavor-spacing-sm, 0.5rem);
    font-size: 0.875rem;
}

.flavor-btn-danger {
    background: var(--flavor-danger, #ef4444);
    color: #fff;
}

.flavor-alert {
    padding: var(--flavor-spacing-md, 1rem);
    border-radius: var(--flavor-radius-md, 0.5rem);
    margin-bottom: var(--flavor-spacing-md, 1rem);
}

.flavor-alert-warning {
    background: #fef3c7;
    border: 1px solid #f59e0b;
    color: #92400e;
}

.flavor-alert-error {
    background: #fee2e2;
    border: 1px solid #ef4444;
    color: #991b1b;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const esOnlineCheckbox = document.getElementById('es_online');
    const ubicacionPresencial = document.getElementById('ubicacion-presencial');
    const ubicacionOnline = document.getElementById('ubicacion-online');

    if (esOnlineCheckbox) {
        esOnlineCheckbox.addEventListener('change', function() {
            if (this.checked) {
                ubicacionPresencial.classList.add('hidden');
                ubicacionOnline.classList.remove('hidden');
            } else {
                ubicacionPresencial.classList.remove('hidden');
                ubicacionOnline.classList.add('hidden');
            }
        });
    }

    // Eliminar imagen
    const btnEliminarImagen = document.getElementById('btn-eliminar-imagen');
    if (btnEliminarImagen) {
        btnEliminarImagen.addEventListener('click', function() {
            const preview = this.closest('.flavor-image-preview');
            if (preview) {
                preview.remove();
            }
            const imagenActual = document.querySelector('input[name="imagen_actual"]');
            if (imagenActual) {
                imagenActual.value = '';
            }
        });
    }

    // Form submit via AJAX
    const form = document.getElementById('form-evento');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;

            submitBtn.disabled = true;
            submitBtn.textContent = '<?php _e("Guardando...", "flavor-chat-ia"); ?>';

            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.data.redirect) {
                        window.location.href = data.data.redirect;
                    } else {
                        alert(data.data.message || '<?php _e("Evento guardado correctamente", "flavor-chat-ia"); ?>');
                    }
                } else {
                    alert(data.data.message || '<?php _e("Error al guardar el evento", "flavor-chat-ia"); ?>');
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
    }
});
</script>
