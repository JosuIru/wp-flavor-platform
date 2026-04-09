<?php
/**
 * Template: Formulario de Suscripción a Avisos Municipales
 *
 * Variables disponibles en $args:
 * - titulo: Título del formulario
 * - descripcion: Descripción del formulario
 * - categorias: Array de categorías disponibles
 * - frecuencias: Array de frecuencias de notificación
 * - mostrar_telefono: Si mostrar campo de teléfono
 * - texto_boton: Texto del botón de envío
 * - clase_adicional: Clases CSS adicionales
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables con valores por defecto
$titulo_formulario = isset($args['titulo']) ? $args['titulo'] : __('Suscríbete a Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN);
$descripcion_formulario = isset($args['descripcion']) ? $args['descripcion'] : __('Recibe notificaciones sobre los temas que te interesan directamente en tu correo o teléfono.', FLAVOR_PLATFORM_TEXT_DOMAIN);
$mostrar_campo_telefono = isset($args['mostrar_telefono']) ? (bool) $args['mostrar_telefono'] : true;
$texto_boton_enviar = isset($args['texto_boton']) ? $args['texto_boton'] : __('Suscribirme', FLAVOR_PLATFORM_TEXT_DOMAIN);
$clase_css_adicional = isset($args['clase_adicional']) ? sanitize_html_class($args['clase_adicional']) : '';

// Categorías de avisos municipales (datos de demostración si no hay datos reales)
$categorias_avisos = isset($args['categorias']) && !empty($args['categorias']) ? $args['categorias'] : array(
    array(
        'id' => 'obras-publicas',
        'nombre' => __('Obras Públicas', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-hammer',
        'descripcion' => __('Cortes de calles, obras en curso, desvíos de tráfico', FLAVOR_PLATFORM_TEXT_DOMAIN)
    ),
    array(
        'id' => 'emergencias',
        'nombre' => __('Emergencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-warning',
        'descripcion' => __('Alertas climáticas, emergencias sanitarias, avisos urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN)
    ),
    array(
        'id' => 'servicios-municipales',
        'nombre' => __('Servicios Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-admin-multisite',
        'descripcion' => __('Cambios en horarios, nuevos servicios, mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN)
    ),
    array(
        'id' => 'cultura-eventos',
        'nombre' => __('Cultura y Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-tickets-alt',
        'descripcion' => __('Festivales, conciertos, actividades culturales', FLAVOR_PLATFORM_TEXT_DOMAIN)
    ),
    array(
        'id' => 'medio-ambiente',
        'nombre' => __('Medio Ambiente', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-palmtree',
        'descripcion' => __('Calidad del aire, reciclaje, espacios verdes', FLAVOR_PLATFORM_TEXT_DOMAIN)
    ),
    array(
        'id' => 'transporte',
        'nombre' => __('Transporte Público', FLAVOR_PLATFORM_TEXT_DOMAIN),
        'icono' => 'dashicons-car',
        'descripcion' => __('Cambios en rutas, horarios, incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN)
    )
);

// Frecuencias de notificación
$frecuencias_notificacion = isset($args['frecuencias']) && !empty($args['frecuencias']) ? $args['frecuencias'] : array(
    'inmediato' => __('Inmediato (cada aviso)', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'diario' => __('Resumen diario', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'semanal' => __('Resumen semanal', FLAVOR_PLATFORM_TEXT_DOMAIN)
);

// Generar ID único para el formulario
$id_formulario = 'flavor-suscripcion-avisos-' . wp_rand(1000, 9999);
?>

<div class="flavor-suscripcion-avisos <?php echo esc_attr($clase_css_adicional); ?>">

    <!-- Encabezado del formulario -->
    <div class="flavor-suscripcion-header">
        <div class="flavor-suscripcion-icono">
            <span class="dashicons dashicons-megaphone"></span>
        </div>
        <h2 class="flavor-suscripcion-titulo"><?php echo esc_html($titulo_formulario); ?></h2>
        <p class="flavor-suscripcion-descripcion"><?php echo esc_html($descripcion_formulario); ?></p>
    </div>

    <!-- Formulario de suscripción -->
    <form id="<?php echo esc_attr($id_formulario); ?>" class="flavor-suscripcion-form" method="post">

        <?php wp_nonce_field('flavor_suscripcion_avisos', 'flavor_nonce'); ?>

        <!-- Datos de contacto -->
        <div class="flavor-form-seccion">
            <h3 class="flavor-form-seccion-titulo">
                <span class="dashicons dashicons-admin-users"></span>
                <?php esc_html_e('Datos de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>

            <div class="flavor-form-grid">
                <div class="flavor-form-campo">
                    <label for="<?php echo esc_attr($id_formulario); ?>-nombre" class="flavor-form-label">
                        <?php esc_html_e('Nombre completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-required">*</span>
                    </label>
                    <input
                        type="text"
                        id="<?php echo esc_attr($id_formulario); ?>-nombre"
                        name="nombre_suscriptor"
                        class="flavor-form-input"
                        required
                        placeholder="<?php esc_attr_e('Tu nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <div class="flavor-form-campo">
                    <label for="<?php echo esc_attr($id_formulario); ?>-email" class="flavor-form-label">
                        <?php esc_html_e('Correo electrónico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-required">*</span>
                    </label>
                    <input
                        type="email"
                        id="<?php echo esc_attr($id_formulario); ?>-email"
                        name="email_suscriptor"
                        class="flavor-form-input"
                        required
                        placeholder="<?php esc_attr_e('tu@email.com', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                </div>

                <?php if ($mostrar_campo_telefono) : ?>
                <div class="flavor-form-campo">
                    <label for="<?php echo esc_attr($id_formulario); ?>-telefono" class="flavor-form-label">
                        <?php esc_html_e('Teléfono móvil', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-opcional"><?php esc_html_e('(opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>
                    <input
                        type="tel"
                        id="<?php echo esc_attr($id_formulario); ?>-telefono"
                        name="telefono_suscriptor"
                        class="flavor-form-input"
                        placeholder="<?php esc_attr_e('+34 600 000 000', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                    >
                    <span class="flavor-form-ayuda">
                        <?php esc_html_e('Para recibir SMS en caso de emergencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Selección de categorías -->
        <div class="flavor-form-seccion">
            <h3 class="flavor-form-seccion-titulo">
                <span class="dashicons dashicons-category"></span>
                <?php esc_html_e('Categorías de interés', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p class="flavor-form-seccion-descripcion">
                <?php esc_html_e('Selecciona las categorías sobre las que deseas recibir avisos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <div class="flavor-categorias-grid">
                <?php foreach ($categorias_avisos as $indice_categoria => $categoria) :
                    $id_categoria = isset($categoria['id']) ? $categoria['id'] : 'cat-' . $indice_categoria;
                    $nombre_categoria = isset($categoria['nombre']) ? $categoria['nombre'] : '';
                    $icono_categoria = isset($categoria['icono']) ? $categoria['icono'] : 'dashicons-category';
                    $descripcion_categoria = isset($categoria['descripcion']) ? $categoria['descripcion'] : '';
                ?>
                <label class="flavor-categoria-item" for="<?php echo esc_attr($id_formulario . '-' . $id_categoria); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id_formulario . '-' . $id_categoria); ?>"
                        name="categorias_seleccionadas[]"
                        value="<?php echo esc_attr($id_categoria); ?>"
                        class="flavor-categoria-checkbox"
                    >
                    <div class="flavor-categoria-contenido">
                        <span class="flavor-categoria-icono dashicons <?php echo esc_attr($icono_categoria); ?>"></span>
                        <span class="flavor-categoria-nombre"><?php echo esc_html($nombre_categoria); ?></span>
                        <?php if ($descripcion_categoria) : ?>
                        <span class="flavor-categoria-descripcion"><?php echo esc_html($descripcion_categoria); ?></span>
                        <?php endif; ?>
                        <span class="flavor-categoria-check">
                            <span class="dashicons dashicons-yes-alt"></span>
                        </span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <div class="flavor-categorias-acciones">
                <button type="button" class="flavor-btn-link flavor-seleccionar-todas">
                    <?php esc_html_e('Seleccionar todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <span class="flavor-separator">|</span>
                <button type="button" class="flavor-btn-link flavor-deseleccionar-todas">
                    <?php esc_html_e('Deseleccionar todas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>

        <!-- Frecuencia de notificaciones -->
        <div class="flavor-form-seccion">
            <h3 class="flavor-form-seccion-titulo">
                <span class="dashicons dashicons-clock"></span>
                <?php esc_html_e('Frecuencia de notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>

            <div class="flavor-frecuencias-lista">
                <?php foreach ($frecuencias_notificacion as $valor_frecuencia => $etiqueta_frecuencia) : ?>
                <label class="flavor-frecuencia-item" for="<?php echo esc_attr($id_formulario . '-freq-' . $valor_frecuencia); ?>">
                    <input
                        type="radio"
                        id="<?php echo esc_attr($id_formulario . '-freq-' . $valor_frecuencia); ?>"
                        name="frecuencia_notificacion"
                        value="<?php echo esc_attr($valor_frecuencia); ?>"
                        class="flavor-frecuencia-radio"
                        <?php echo ($valor_frecuencia === 'diario') ? 'checked' : ''; ?>
                    >
                    <span class="flavor-frecuencia-label"><?php echo esc_html($etiqueta_frecuencia); ?></span>
                    <span class="flavor-frecuencia-check"></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Aceptación de términos -->
        <div class="flavor-form-seccion flavor-terminos">
            <label class="flavor-checkbox-label" for="<?php echo esc_attr($id_formulario); ?>-terminos">
                <input
                    type="checkbox"
                    id="<?php echo esc_attr($id_formulario); ?>-terminos"
                    name="acepta_terminos"
                    required
                    class="flavor-checkbox"
                >
                <span class="flavor-checkbox-text">
                    <?php
                    printf(
                        esc_html__('Acepto la %spolítica de privacidad%s y autorizo el tratamiento de mis datos para recibir avisos municipales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        '<a href="#" class="flavor-link" target="_blank">',
                        '</a>'
                    );
                    ?>
                    <span class="flavor-required">*</span>
                </span>
            </label>
        </div>

        <!-- Botón de envío -->
        <div class="flavor-form-acciones">
            <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-suscribir">
                <span class="flavor-btn-icono dashicons dashicons-email-alt"></span>
                <span class="flavor-btn-texto"><?php echo esc_html($texto_boton_enviar); ?></span>
            </button>
        </div>

        <!-- Mensaje de estado -->
        <div class="flavor-form-mensaje" role="alert" aria-live="polite"></div>

    </form>

</div>

<style>
/* Contenedor principal */
.flavor-suscripcion-avisos {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border-radius: 16px;
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}

/* Encabezado */
.flavor-suscripcion-header {
    text-align: center;
    margin-bottom: 2rem;
    padding-bottom: 1.5rem;
    border-bottom: 2px solid #e2e8f0;
}

.flavor-suscripcion-icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 64px;
    height: 64px;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    border-radius: 16px;
    margin-bottom: 1rem;
}

.flavor-suscripcion-icono .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #ffffff;
}

.flavor-suscripcion-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: #1e293b;
    margin: 0 0 0.5rem 0;
}

.flavor-suscripcion-descripcion {
    font-size: 1rem;
    color: #64748b;
    margin: 0;
    max-width: 500px;
    margin: 0 auto;
}

/* Secciones del formulario */
.flavor-form-seccion {
    margin-bottom: 2rem;
    padding: 1.5rem;
    background: #ffffff;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
}

.flavor-form-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: #334155;
    margin: 0 0 1rem 0;
}

.flavor-form-seccion-titulo .dashicons {
    color: #3b82f6;
}

.flavor-form-seccion-descripcion {
    font-size: 0.875rem;
    color: #64748b;
    margin: 0 0 1rem 0;
}

/* Grid del formulario */
.flavor-form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

/* Campos del formulario */
.flavor-form-campo {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.flavor-form-label {
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
}

.flavor-required {
    color: #ef4444;
    margin-left: 2px;
}

.flavor-opcional {
    font-weight: 400;
    color: #94a3b8;
    font-size: 0.75rem;
}

.flavor-form-input {
    padding: 0.75rem 1rem;
    font-size: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: all 0.2s ease;
    background: #ffffff;
}

.flavor-form-input:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flavor-form-input::placeholder {
    color: #94a3b8;
}

.flavor-form-ayuda {
    font-size: 0.75rem;
    color: #94a3b8;
}

/* Grid de categorías */
.flavor-categorias-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 0.75rem;
}

.flavor-categoria-item {
    position: relative;
    cursor: pointer;
}

.flavor-categoria-checkbox {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.flavor-categoria-contenido {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 1.25rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    text-align: center;
    transition: all 0.2s ease;
    position: relative;
}

.flavor-categoria-item:hover .flavor-categoria-contenido {
    border-color: #3b82f6;
    background: #eff6ff;
}

.flavor-categoria-checkbox:checked + .flavor-categoria-contenido {
    border-color: #3b82f6;
    background: #eff6ff;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.flavor-categoria-icono {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #3b82f6;
    margin-bottom: 0.5rem;
}

.flavor-categoria-nombre {
    font-weight: 600;
    color: #334155;
    font-size: 0.9rem;
}

.flavor-categoria-descripcion {
    font-size: 0.75rem;
    color: #64748b;
    margin-top: 0.25rem;
    line-height: 1.3;
}

.flavor-categoria-check {
    position: absolute;
    top: 8px;
    right: 8px;
    opacity: 0;
    transform: scale(0.5);
    transition: all 0.2s ease;
}

.flavor-categoria-check .dashicons {
    color: #3b82f6;
    font-size: 20px;
}

.flavor-categoria-checkbox:checked + .flavor-categoria-contenido .flavor-categoria-check {
    opacity: 1;
    transform: scale(1);
}

/* Acciones de categorías */
.flavor-categorias-acciones {
    margin-top: 1rem;
    text-align: center;
}

.flavor-btn-link {
    background: none;
    border: none;
    color: #3b82f6;
    font-size: 0.875rem;
    cursor: pointer;
    padding: 0;
    text-decoration: underline;
}

.flavor-btn-link:hover {
    color: #1d4ed8;
}

.flavor-separator {
    color: #cbd5e1;
    margin: 0 0.5rem;
}

/* Frecuencias */
.flavor-frecuencias-lista {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.flavor-frecuencia-item {
    display: flex;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-frecuencia-item:hover {
    border-color: #3b82f6;
}

.flavor-frecuencia-radio {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.flavor-frecuencia-label {
    flex: 1;
    font-size: 0.9rem;
    color: #475569;
}

.flavor-frecuencia-check {
    width: 20px;
    height: 20px;
    border: 2px solid #cbd5e1;
    border-radius: 50%;
    position: relative;
    transition: all 0.2s ease;
}

.flavor-frecuencia-radio:checked + .flavor-frecuencia-label + .flavor-frecuencia-check {
    border-color: #3b82f6;
    background: #3b82f6;
}

.flavor-frecuencia-radio:checked + .flavor-frecuencia-label + .flavor-frecuencia-check::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: #ffffff;
    border-radius: 50%;
}

/* Términos y condiciones */
.flavor-terminos {
    background: #fef3c7;
    border-color: #fbbf24;
}

.flavor-checkbox-label {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.flavor-checkbox {
    width: 20px;
    height: 20px;
    margin-top: 2px;
    accent-color: #3b82f6;
}

.flavor-checkbox-text {
    font-size: 0.875rem;
    color: #78350f;
    line-height: 1.5;
}

.flavor-link {
    color: #1d4ed8;
    text-decoration: underline;
}

/* Botón de envío */
.flavor-form-acciones {
    text-align: center;
    margin-top: 1.5rem;
}

.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1rem 2rem;
    font-size: 1rem;
    font-weight: 600;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    box-shadow: 0 4px 14px 0 rgba(59, 130, 246, 0.4);
}

.flavor-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px 0 rgba(59, 130, 246, 0.5);
}

.flavor-btn-primary:active {
    transform: translateY(0);
}

.flavor-btn-icono {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

/* Mensaje de estado */
.flavor-form-mensaje {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    text-align: center;
    display: none;
}

.flavor-form-mensaje.flavor-success {
    display: block;
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #6ee7b7;
}

.flavor-form-mensaje.flavor-error {
    display: block;
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Responsive */
@media (max-width: 640px) {
    .flavor-suscripcion-avisos {
        padding: 1rem;
        border-radius: 12px;
    }

    .flavor-suscripcion-titulo {
        font-size: 1.5rem;
    }

    .flavor-form-seccion {
        padding: 1rem;
    }

    .flavor-categorias-grid {
        grid-template-columns: 1fr;
    }

    .flavor-btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const formularioSuscripcion = document.getElementById('<?php echo esc_js($id_formulario); ?>');

    if (formularioSuscripcion) {
        // Seleccionar/deseleccionar todas las categorías
        const botonSeleccionarTodas = formularioSuscripcion.querySelector('.flavor-seleccionar-todas');
        const botonDeseleccionarTodas = formularioSuscripcion.querySelector('.flavor-deseleccionar-todas');
        const checkboxesCategorias = formularioSuscripcion.querySelectorAll('.flavor-categoria-checkbox');

        if (botonSeleccionarTodas) {
            botonSeleccionarTodas.addEventListener('click', function() {
                checkboxesCategorias.forEach(function(checkbox) {
                    checkbox.checked = true;
                });
            });
        }

        if (botonDeseleccionarTodas) {
            botonDeseleccionarTodas.addEventListener('click', function() {
                checkboxesCategorias.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            });
        }

        // Manejo del envío del formulario
        formularioSuscripcion.addEventListener('submit', function(evento) {
            evento.preventDefault();

            const contenedorMensaje = formularioSuscripcion.querySelector('.flavor-form-mensaje');
            const botonEnviar = formularioSuscripcion.querySelector('.flavor-btn-suscribir');

            // Validar que al menos una categoría esté seleccionada
            const categoriasSeleccionadas = formularioSuscripcion.querySelectorAll('.flavor-categoria-checkbox:checked');

            if (categoriasSeleccionadas.length === 0) {
                contenedorMensaje.className = 'flavor-form-mensaje flavor-error';
                contenedorMensaje.textContent = '<?php echo esc_js(__('Por favor, selecciona al menos una categoría de avisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                return;
            }

            // Deshabilitar botón durante el envío
            botonEnviar.disabled = true;
            botonEnviar.querySelector('.flavor-btn-texto').textContent = '<?php echo esc_js(__('Enviando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

            // Simular envío (aquí se integraría con AJAX real)
            setTimeout(function() {
                contenedorMensaje.className = 'flavor-form-mensaje flavor-success';
                contenedorMensaje.textContent = '<?php echo esc_js(__('¡Suscripción realizada con éxito! Recibirás un correo de confirmación en breve.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

                botonEnviar.disabled = false;
                botonEnviar.querySelector('.flavor-btn-texto').textContent = '<?php echo esc_js($texto_boton_enviar); ?>';

                // Reset del formulario
                formularioSuscripcion.reset();
            }, 1500);
        });
    }
});
</script>
