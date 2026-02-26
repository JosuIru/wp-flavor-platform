<?php
/**
 * Template: Formulario de Suscripcion a Avisos
 *
 * Permite a los usuarios suscribirse para recibir notificaciones de avisos.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_avisos = $wpdb->prefix . 'flavor_avisos_municipales';
$tabla_categorias = $wpdb->prefix . 'flavor_avisos_categorias';
$tabla_zonas = $wpdb->prefix . 'flavor_avisos_zonas';
$tabla_suscripciones = $wpdb->prefix . 'flavor_avisos_suscripciones';

// Verificar si existe la tabla
if (!Flavor_Chat_Helpers::tabla_existe($tabla_avisos)) {
    echo '<div class="avisos-empty"><p>' . esc_html__('El modulo de avisos municipales no esta configurado.', 'flavor-chat-ia') . '</p></div>';
    return;
}

// Parametros del template
$titulo = isset($atts['titulo']) ? sanitize_text_field($atts['titulo']) : __('Suscribete a los avisos', 'flavor-chat-ia');
$descripcion = isset($atts['descripcion']) ? sanitize_text_field($atts['descripcion']) : __('Recibe notificaciones de los avisos que te interesan', 'flavor-chat-ia');
$mostrar_push = isset($atts['push']) ? ($atts['push'] === 'true') : true;
$estilo = isset($atts['estilo']) ? sanitize_text_field($atts['estilo']) : 'card'; // card | inline | minimal

// Usuario actual
$usuario_actual = wp_get_current_user();
$usuario_id = get_current_user_id();

// Verificar si ya esta suscrito
$suscripcion_existente = null;
if ($usuario_id) {
    $suscripcion_existente = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $tabla_suscripciones WHERE usuario_id = %d AND activa = 1 LIMIT 1",
        $usuario_id
    ));
}

// Obtener categorias y zonas disponibles
$categorias_disponibles = $wpdb->get_results("SELECT * FROM $tabla_categorias WHERE activa = 1 ORDER BY orden ASC, nombre ASC");
$zonas_disponibles = $wpdb->get_results("SELECT * FROM $tabla_zonas WHERE activa = 1 ORDER BY tipo ASC, nombre ASC");

// Categorias y zonas suscritas (si ya esta suscrito)
$categorias_suscritas = [];
$zonas_suscritas = [];
if ($suscripcion_existente) {
    $categorias_suscritas = json_decode($suscripcion_existente->categorias_ids, true) ?: [];
    $zonas_suscritas = json_decode($suscripcion_existente->zonas_ids, true) ?: [];
}

// Prioridades disponibles
$prioridades_opciones = [
    'baja'    => __('Todas (incluyendo baja prioridad)', 'flavor-chat-ia'),
    'media'   => __('Media y superiores', 'flavor-chat-ia'),
    'alta'    => __('Alta y urgentes', 'flavor-chat-ia'),
    'urgente' => __('Solo urgentes', 'flavor-chat-ia'),
];

// Nonce para el formulario
$nonce = wp_create_nonce('flavor_avisos_suscripcion_nonce');
?>

<div class="avisos-suscripcion-wrapper avisos-suscripcion--<?php echo esc_attr($estilo); ?>">
    <?php if ($suscripcion_existente): ?>
    <!-- Estado: Ya suscrito -->
    <div class="avisos-suscripcion-activa">
        <div class="suscripcion-activa-icono">
            <span class="dashicons dashicons-yes-alt"></span>
        </div>
        <div class="suscripcion-activa-info">
            <h3><?php esc_html_e('Ya estas suscrito', 'flavor-chat-ia'); ?></h3>
            <p><?php esc_html_e('Recibiras notificaciones de los avisos municipales segun tu configuracion.', 'flavor-chat-ia'); ?></p>
            <button type="button" class="btn btn-outline btn-modificar-suscripcion">
                <span class="dashicons dashicons-edit"></span>
                <?php esc_html_e('Modificar preferencias', 'flavor-chat-ia'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>

    <div class="avisos-suscripcion-form-container <?php echo $suscripcion_existente ? 'oculto' : ''; ?>">
        <header class="avisos-suscripcion-header">
            <div class="suscripcion-icono">
                <span class="dashicons dashicons-bell"></span>
            </div>
            <h3 class="suscripcion-titulo"><?php echo esc_html($titulo); ?></h3>
            <p class="suscripcion-descripcion"><?php echo esc_html($descripcion); ?></p>
        </header>

        <form class="avisos-suscripcion-form" id="avisos-suscripcion-form" method="post">
            <input type="hidden" name="action" value="flavor_avisos_suscribir">
            <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

            <!-- Datos de contacto -->
            <div class="form-seccion">
                <h4 class="form-seccion-titulo">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php esc_html_e('Tus datos', 'flavor-chat-ia'); ?>
                </h4>
                <div class="form-row">
                    <div class="form-grupo">
                        <label for="suscripcion-nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></label>
                        <input type="text" id="suscripcion-nombre" name="nombre"
                               value="<?php echo esc_attr($usuario_actual->display_name ?? ''); ?>"
                               placeholder="<?php esc_attr_e('Tu nombre', 'flavor-chat-ia'); ?>">
                    </div>
                    <div class="form-grupo">
                        <label for="suscripcion-email"><?php esc_html_e('Email', 'flavor-chat-ia'); ?> <span class="requerido">*</span></label>
                        <input type="email" id="suscripcion-email" name="email" required
                               value="<?php echo esc_attr($usuario_actual->user_email ?? ''); ?>"
                               placeholder="<?php esc_attr_e('tu@email.com', 'flavor-chat-ia'); ?>">
                    </div>
                </div>
            </div>

            <!-- Categorias de interes -->
            <div class="form-seccion">
                <h4 class="form-seccion-titulo">
                    <span class="dashicons dashicons-category"></span>
                    <?php esc_html_e('Categorias de interes', 'flavor-chat-ia'); ?>
                </h4>
                <p class="form-seccion-ayuda"><?php esc_html_e('Selecciona las categorias sobre las que deseas recibir notificaciones', 'flavor-chat-ia'); ?></p>
                <div class="form-checkbox-grid">
                    <label class="form-checkbox form-checkbox--todas">
                        <input type="checkbox" id="categorias-todas" checked>
                        <span class="checkbox-label"><?php esc_html_e('Todas las categorias', 'flavor-chat-ia'); ?></span>
                    </label>
                    <?php foreach ($categorias_disponibles as $categoria):
                        $esta_suscrita = empty($categorias_suscritas) || in_array($categoria->id, $categorias_suscritas);
                    ?>
                    <label class="form-checkbox categoria-individual" style="--categoria-color: <?php echo esc_attr($categoria->color ?: '#6b7280'); ?>">
                        <input type="checkbox" name="categorias[]" value="<?php echo esc_attr($categoria->id); ?>"
                               <?php checked($esta_suscrita); ?>>
                        <span class="checkbox-marca"></span>
                        <span class="checkbox-label"><?php echo esc_html($categoria->nombre); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Zonas de interes -->
            <?php if (count($zonas_disponibles) > 1): ?>
            <div class="form-seccion">
                <h4 class="form-seccion-titulo">
                    <span class="dashicons dashicons-location"></span>
                    <?php esc_html_e('Zonas de interes', 'flavor-chat-ia'); ?>
                </h4>
                <p class="form-seccion-ayuda"><?php esc_html_e('Recibe avisos especificos de tu zona', 'flavor-chat-ia'); ?></p>
                <div class="form-checkbox-grid">
                    <label class="form-checkbox form-checkbox--todas">
                        <input type="checkbox" id="zonas-todas" checked>
                        <span class="checkbox-label"><?php esc_html_e('Todas las zonas', 'flavor-chat-ia'); ?></span>
                    </label>
                    <?php foreach ($zonas_disponibles as $zona):
                        $esta_suscrita = empty($zonas_suscritas) || in_array($zona->id, $zonas_suscritas);
                    ?>
                    <label class="form-checkbox zona-individual">
                        <input type="checkbox" name="zonas[]" value="<?php echo esc_attr($zona->id); ?>"
                               <?php checked($esta_suscrita); ?>>
                        <span class="checkbox-marca"></span>
                        <span class="checkbox-label"><?php echo esc_html($zona->nombre); ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Prioridad minima -->
            <div class="form-seccion">
                <h4 class="form-seccion-titulo">
                    <span class="dashicons dashicons-flag"></span>
                    <?php esc_html_e('Prioridad minima', 'flavor-chat-ia'); ?>
                </h4>
                <p class="form-seccion-ayuda"><?php esc_html_e('Elige el nivel minimo de prioridad de los avisos que quieres recibir', 'flavor-chat-ia'); ?></p>
                <div class="form-grupo">
                    <select name="prioridad_minima" id="prioridad-minima">
                        <?php foreach ($prioridades_opciones as $valor => $etiqueta): ?>
                        <option value="<?php echo esc_attr($valor); ?>"
                                <?php selected($suscripcion_existente->prioridad_minima ?? 'baja', $valor); ?>>
                            <?php echo esc_html($etiqueta); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Canales de notificacion -->
            <div class="form-seccion">
                <h4 class="form-seccion-titulo">
                    <span class="dashicons dashicons-email-alt"></span>
                    <?php esc_html_e('Canales de notificacion', 'flavor-chat-ia'); ?>
                </h4>
                <div class="form-canales">
                    <label class="form-canal">
                        <input type="checkbox" name="notificar_email" value="1" checked>
                        <div class="canal-icono">
                            <span class="dashicons dashicons-email"></span>
                        </div>
                        <div class="canal-info">
                            <span class="canal-nombre"><?php esc_html_e('Email', 'flavor-chat-ia'); ?></span>
                            <span class="canal-descripcion"><?php esc_html_e('Recibe avisos en tu correo', 'flavor-chat-ia'); ?></span>
                        </div>
                    </label>

                    <?php if ($mostrar_push): ?>
                    <label class="form-canal">
                        <input type="checkbox" name="notificar_push" value="1" id="notificar-push">
                        <div class="canal-icono">
                            <span class="dashicons dashicons-bell"></span>
                        </div>
                        <div class="canal-info">
                            <span class="canal-nombre"><?php esc_html_e('Notificaciones Push', 'flavor-chat-ia'); ?></span>
                            <span class="canal-descripcion"><?php esc_html_e('Recibe alertas en tu navegador', 'flavor-chat-ia'); ?></span>
                        </div>
                    </label>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Consentimiento -->
            <div class="form-seccion form-seccion--legal">
                <label class="form-checkbox form-checkbox--legal">
                    <input type="checkbox" name="acepta_terminos" value="1" required>
                    <span class="checkbox-marca"></span>
                    <span class="checkbox-label">
                        <?php printf(
                            esc_html__('Acepto recibir comunicaciones y he leido la %spolitica de privacidad%s', 'flavor-chat-ia'),
                            '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">',
                            '</a>'
                        ); ?>
                        <span class="requerido">*</span>
                    </span>
                </label>
            </div>

            <!-- Botones -->
            <div class="form-acciones">
                <button type="submit" class="btn btn-primary btn-lg">
                    <span class="dashicons dashicons-yes"></span>
                    <?php echo $suscripcion_existente
                        ? esc_html__('Actualizar suscripcion', 'flavor-chat-ia')
                        : esc_html__('Suscribirme', 'flavor-chat-ia'); ?>
                </button>
                <?php if ($suscripcion_existente): ?>
                <button type="button" class="btn btn-outline btn-cancelar-edicion">
                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="btn btn-danger btn-cancelar-suscripcion">
                    <span class="dashicons dashicons-no"></span>
                    <?php esc_html_e('Cancelar suscripcion', 'flavor-chat-ia'); ?>
                </button>
                <?php endif; ?>
            </div>

            <div class="form-mensaje" id="suscripcion-mensaje"></div>
        </form>
    </div>
</div>

<style>
.avisos-suscripcion-wrapper {
    max-width: 600px;
    margin: 0 auto;
}

.avisos-suscripcion--card {
    background: white;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
}

.avisos-suscripcion--minimal {
    padding: 1rem 0;
}

/* Estado ya suscrito */
.avisos-suscripcion-activa {
    display: flex;
    gap: 1.25rem;
    padding: 1.5rem;
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    border-radius: 12px;
    margin-bottom: 1.5rem;
}

.suscripcion-activa-icono {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #10b981;
    color: white;
    border-radius: 50%;
    flex-shrink: 0;
}

.suscripcion-activa-icono .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
}

.suscripcion-activa-info h3 {
    margin: 0 0 0.25rem;
    font-size: 1.1rem;
    color: #065f46;
}

.suscripcion-activa-info p {
    margin: 0 0 1rem;
    font-size: 0.9rem;
    color: #047857;
}

.oculto {
    display: none;
}

/* Header del formulario */
.avisos-suscripcion-header {
    text-align: center;
    margin-bottom: 2rem;
}

.suscripcion-icono {
    width: 64px;
    height: 64px;
    margin: 0 auto 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    border-radius: 16px;
}

.suscripcion-icono .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
}

.suscripcion-titulo {
    margin: 0 0 0.5rem;
    font-size: 1.5rem;
    color: #1f2937;
}

.suscripcion-descripcion {
    margin: 0;
    font-size: 0.95rem;
    color: #6b7280;
}

/* Secciones del formulario */
.form-seccion {
    margin-bottom: 1.75rem;
    padding-bottom: 1.75rem;
    border-bottom: 1px solid #f3f4f6;
}

.form-seccion:last-of-type {
    border-bottom: none;
    margin-bottom: 1rem;
}

.form-seccion-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0 0 0.5rem;
    font-size: 1rem;
    font-weight: 600;
    color: #1f2937;
}

.form-seccion-titulo .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
    color: #3b82f6;
}

.form-seccion-ayuda {
    margin: 0 0 1rem;
    font-size: 0.85rem;
    color: #6b7280;
}

.form-seccion--legal {
    padding-bottom: 0;
    border-bottom: none;
}

/* Campos del formulario */
.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.form-grupo {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
}

.form-grupo label {
    font-size: 0.85rem;
    font-weight: 500;
    color: #374151;
}

.form-grupo input,
.form-grupo select {
    padding: 0.75rem 1rem;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    font-size: 0.95rem;
    transition: all 0.2s ease;
}

.form-grupo input:focus,
.form-grupo select:focus {
    outline: none;
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.requerido {
    color: #dc2626;
}

/* Checkboxes grid */
.form-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 0.75rem;
}

.form-checkbox {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.75rem;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-checkbox:hover {
    background: #f3f4f6;
    border-color: #d1d5db;
}

.form-checkbox input {
    display: none;
}

.form-checkbox .checkbox-marca {
    width: 18px;
    height: 18px;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    flex-shrink: 0;
}

.form-checkbox input:checked + .checkbox-marca {
    background: #3b82f6;
    border-color: #3b82f6;
}

.form-checkbox input:checked + .checkbox-marca::after {
    content: '';
    width: 6px;
    height: 10px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    margin-bottom: 2px;
}

.form-checkbox .checkbox-label {
    font-size: 0.85rem;
    color: #374151;
}

.form-checkbox--todas {
    grid-column: 1 / -1;
    background: #eff6ff;
    border-color: #bfdbfe;
}

.form-checkbox--todas .checkbox-label {
    font-weight: 500;
    color: #1d4ed8;
}

.categoria-individual {
    border-left: 3px solid var(--categoria-color, #6b7280);
}

.form-checkbox--legal {
    background: transparent;
    border: none;
    padding: 0;
}

.form-checkbox--legal .checkbox-label {
    font-size: 0.8rem;
    color: #6b7280;
}

.form-checkbox--legal .checkbox-label a {
    color: #3b82f6;
    text-decoration: none;
}

.form-checkbox--legal .checkbox-label a:hover {
    text-decoration: underline;
}

/* Canales de notificacion */
.form-canales {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.form-canal {
    flex: 1;
    min-width: 200px;
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f9fafb;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.form-canal:hover {
    border-color: #d1d5db;
}

.form-canal input {
    display: none;
}

.form-canal input:checked ~ .canal-icono {
    background: #3b82f6;
    color: white;
}

.form-canal input:checked ~ .canal-info .canal-nombre {
    color: #1d4ed8;
}

.form-canal:has(input:checked) {
    background: #eff6ff;
    border-color: #3b82f6;
}

.canal-icono {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e5e7eb;
    color: #6b7280;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.canal-icono .dashicons {
    font-size: 22px;
    width: 22px;
    height: 22px;
}

.canal-info {
    display: flex;
    flex-direction: column;
    gap: 0.125rem;
}

.canal-nombre {
    font-size: 0.95rem;
    font-weight: 600;
    color: #374151;
    transition: color 0.2s ease;
}

.canal-descripcion {
    font-size: 0.8rem;
    color: #6b7280;
}

/* Botones */
.form-acciones {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
    margin-top: 1.5rem;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    font-size: 0.95rem;
    font-weight: 500;
    text-decoration: none;
    cursor: pointer;
    border: none;
    transition: all 0.2s ease;
}

.btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.btn-lg {
    padding: 0.875rem 2rem;
    font-size: 1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: white;
    flex: 1;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
}

.btn-outline {
    background: white;
    border: 1px solid #d1d5db;
    color: #374151;
}

.btn-outline:hover {
    background: #f9fafb;
    border-color: #9ca3af;
}

.btn-danger {
    background: #fef2f2;
    color: #dc2626;
    border: 1px solid #fecaca;
}

.btn-danger:hover {
    background: #fee2e2;
}

/* Mensaje de estado */
.form-mensaje {
    margin-top: 1rem;
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.9rem;
    display: none;
}

.form-mensaje.exito {
    display: block;
    background: #ecfdf5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.form-mensaje.error {
    display: block;
    background: #fef2f2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

@media (max-width: 640px) {
    .avisos-suscripcion--card {
        padding: 1.25rem;
    }

    .suscripcion-icono {
        width: 56px;
        height: 56px;
    }

    .suscripcion-titulo {
        font-size: 1.25rem;
    }

    .form-checkbox-grid {
        grid-template-columns: 1fr;
    }

    .form-canales {
        flex-direction: column;
    }

    .form-canal {
        min-width: 100%;
    }

    .form-acciones {
        flex-direction: column;
    }

    .btn {
        width: 100%;
    }
}
</style>

<script>
(function() {
    const form = document.getElementById('avisos-suscripcion-form');
    const mensajeEl = document.getElementById('suscripcion-mensaje');
    const todasCategorias = document.getElementById('categorias-todas');
    const categoriasIndividuales = document.querySelectorAll('.categoria-individual input');
    const todasZonas = document.getElementById('zonas-todas');
    const zonasIndividuales = document.querySelectorAll('.zona-individual input');
    const btnModificar = document.querySelector('.btn-modificar-suscripcion');
    const btnCancelar = document.querySelector('.btn-cancelar-edicion');
    const formContainer = document.querySelector('.avisos-suscripcion-form-container');
    const suscripcionActiva = document.querySelector('.avisos-suscripcion-activa');

    // Toggle todas las categorias
    if (todasCategorias) {
        todasCategorias.addEventListener('change', function() {
            categoriasIndividuales.forEach(cb => {
                cb.checked = this.checked;
            });
        });

        categoriasIndividuales.forEach(cb => {
            cb.addEventListener('change', function() {
                const todasMarcadas = Array.from(categoriasIndividuales).every(c => c.checked);
                const algunaMarcada = Array.from(categoriasIndividuales).some(c => c.checked);
                todasCategorias.checked = todasMarcadas;
                todasCategorias.indeterminate = algunaMarcada && !todasMarcadas;
            });
        });
    }

    // Toggle todas las zonas
    if (todasZonas) {
        todasZonas.addEventListener('change', function() {
            zonasIndividuales.forEach(cb => {
                cb.checked = this.checked;
            });
        });

        zonasIndividuales.forEach(cb => {
            cb.addEventListener('change', function() {
                const todasMarcadas = Array.from(zonasIndividuales).every(c => c.checked);
                const algunaMarcada = Array.from(zonasIndividuales).some(c => c.checked);
                todasZonas.checked = todasMarcadas;
                todasZonas.indeterminate = algunaMarcada && !todasMarcadas;
            });
        });
    }

    // Mostrar formulario para modificar
    if (btnModificar) {
        btnModificar.addEventListener('click', function() {
            suscripcionActiva.classList.add('oculto');
            formContainer.classList.remove('oculto');
        });
    }

    // Cancelar edicion
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            formContainer.classList.add('oculto');
            suscripcionActiva.classList.remove('oculto');
        });
    }

    // Envio del formulario
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php esc_html_e('Procesando...', 'flavor-chat-ia'); ?>';

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mensajeEl.className = 'form-mensaje exito';
                    mensajeEl.textContent = data.data.message || '<?php esc_html_e('Suscripcion procesada correctamente', 'flavor-chat-ia'); ?>';

                    if (data.data.solicitar_push) {
                        solicitarPermisosPush();
                    }
                } else {
                    mensajeEl.className = 'form-mensaje error';
                    mensajeEl.textContent = data.data.message || '<?php esc_html_e('Error al procesar la suscripcion', 'flavor-chat-ia'); ?>';
                }
            })
            .catch(error => {
                mensajeEl.className = 'form-mensaje error';
                mensajeEl.textContent = '<?php esc_html_e('Error de conexion. Intenta de nuevo.', 'flavor-chat-ia'); ?>';
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
    }

    function solicitarPermisosPush() {
        if ('Notification' in window && 'serviceWorker' in navigator) {
            Notification.requestPermission().then(function(permission) {
                if (permission === 'granted') {
                    console.log('Permisos de notificacion concedidos');
                }
            });
        }
    }
})();
</script>
