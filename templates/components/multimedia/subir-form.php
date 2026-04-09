<?php
/**
 * Template: Multimedia Upload Form
 * Formulario para subir contenido multimedia con drag & drop
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) exit;

// Extraer variables del array $args con valores por defecto
$titulo_formulario = isset($args['titulo']) ? $args['titulo'] : __('Subir Contenido Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN);
$descripcion_formulario = isset($args['descripcion']) ? $args['descripcion'] : __('Comparte tus fotos y videos con la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN);
$tipos_permitidos = isset($args['tipos_permitidos']) ? $args['tipos_permitidos'] : array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'video/mp4', 'video/webm');
$tamano_maximo = isset($args['tamano_maximo']) ? intval($args['tamano_maximo']) : 50; // MB
$archivos_multiples = isset($args['multiples']) ? $args['multiples'] : true;
$max_archivos = isset($args['max_archivos']) ? intval($args['max_archivos']) : 10;
$mostrar_categorias = isset($args['mostrar_categorias']) ? $args['mostrar_categorias'] : true;
$mostrar_etiquetas = isset($args['mostrar_etiquetas']) ? $args['mostrar_etiquetas'] : true;
$mostrar_privacidad = isset($args['mostrar_privacidad']) ? $args['mostrar_privacidad'] : true;
$requiere_login = isset($args['requiere_login']) ? $args['requiere_login'] : true;
$ajax_url = isset($args['ajax_url']) ? $args['ajax_url'] : admin_url('admin-ajax.php');
$nonce = wp_create_nonce('flavor_upload_multimedia');

// Categorías de demostración
$categorias_disponibles = isset($args['categorias']) ? $args['categorias'] : array(
    'eventos' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'cultura' => __('Cultura', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'comunidad' => __('Comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'naturaleza' => __('Naturaleza', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'deportes' => __('Deportes', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'arte' => __('Arte', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'musica' => __('Música', FLAVOR_PLATFORM_TEXT_DOMAIN),
    'otros' => __('Otros', FLAVOR_PLATFORM_TEXT_DOMAIN)
);

// ID único para el formulario
$form_id = 'flavor-upload-' . uniqid();

// Verificar si el usuario está logueado
$usuario_logueado = is_user_logged_in();
$usuario_actual = $usuario_logueado ? wp_get_current_user() : null;
?>

<section class="flavor-upload-section">
    <div class="flavor-upload-container">

        <!-- Cabecera del formulario -->
        <header class="flavor-upload-header">
            <div class="flavor-upload-header-icon">
                <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                    <polyline points="17 8 12 3 7 8"></polyline>
                    <line x1="12" y1="3" x2="12" y2="15"></line>
                </svg>
            </div>
            <h2 class="flavor-upload-titulo"><?php echo esc_html($titulo_formulario); ?></h2>
            <p class="flavor-upload-descripcion"><?php echo esc_html($descripcion_formulario); ?></p>
        </header>

        <?php if ($requiere_login && !$usuario_logueado): ?>
        <!-- Mensaje de login requerido -->
        <div class="flavor-upload-login-requerido">
            <div class="flavor-upload-login-icon">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
            </div>
            <h3><?php esc_html_e('Inicia sesión para continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Necesitas tener una cuenta para subir contenido multimedia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-upload-login-acciones">
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="flavor-btn flavor-btn--primario">
                    <?php esc_html_e('Iniciar sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="<?php echo esc_url(wp_registration_url()); ?>" class="flavor-btn flavor-btn--secundario">
                    <?php esc_html_e('Crear cuenta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>
        <?php else: ?>

        <!-- Formulario de subida -->
        <form id="<?php echo esc_attr($form_id); ?>"
              class="flavor-upload-form"
              data-ajax-url="<?php echo esc_url($ajax_url); ?>"
              data-nonce="<?php echo esc_attr($nonce); ?>"
              data-max-size="<?php echo esc_attr($tamano_maximo); ?>"
              data-max-files="<?php echo esc_attr($max_archivos); ?>"
              data-tipos="<?php echo esc_attr(implode(',', $tipos_permitidos)); ?>">

            <!-- Zona de drag & drop -->
            <div class="flavor-upload-dropzone" id="<?php echo esc_attr($form_id); ?>-dropzone">
                <input type="file"
                       id="<?php echo esc_attr($form_id); ?>-input"
                       class="flavor-upload-input"
                       name="archivos[]"
                       accept="<?php echo esc_attr(implode(',', $tipos_permitidos)); ?>"
                       <?php echo $archivos_multiples ? 'multiple' : ''; ?>>

                <div class="flavor-upload-dropzone-content">
                    <div class="flavor-upload-dropzone-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                            <polyline points="17 8 12 3 7 8"></polyline>
                            <line x1="12" y1="3" x2="12" y2="15"></line>
                        </svg>
                    </div>
                    <h3 class="flavor-upload-dropzone-titulo">
                        <?php esc_html_e('Arrastra tus archivos aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </h3>
                    <p class="flavor-upload-dropzone-texto">
                        <?php esc_html_e('o', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <button type="button" class="flavor-upload-dropzone-btn">
                            <?php esc_html_e('selecciona archivos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </p>
                    <div class="flavor-upload-dropzone-info">
                        <span class="flavor-upload-info-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <circle cx="8.5" cy="8.5" r="1.5"></circle>
                                <polyline points="21 15 16 10 5 21"></polyline>
                            </svg>
                            <?php esc_html_e('Imágenes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>: JPG, PNG, GIF, WebP
                        </span>
                        <span class="flavor-upload-info-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="23 7 16 12 23 17 23 7"></polygon>
                                <rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect>
                            </svg>
                            <?php esc_html_e('Videos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>: MP4, WebM
                        </span>
                        <span class="flavor-upload-info-item">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path>
                                <polyline points="13 2 13 9 20 9"></polyline>
                            </svg>
                            <?php printf(esc_html__('Máximo %d MB por archivo', FLAVOR_PLATFORM_TEXT_DOMAIN), $tamano_maximo); ?>
                        </span>
                    </div>
                </div>

                <!-- Estado de arrastre -->
                <div class="flavor-upload-dropzone-drag">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <p><?php esc_html_e('Suelta los archivos para subirlos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            </div>

            <!-- Lista de archivos seleccionados -->
            <div class="flavor-upload-archivos" style="display: none;">
                <div class="flavor-upload-archivos-header">
                    <h3 class="flavor-upload-archivos-titulo">
                        <?php esc_html_e('Archivos seleccionados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        (<span class="flavor-upload-archivos-count">0</span>)
                    </h3>
                    <button type="button" class="flavor-upload-archivos-limpiar">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="3 6 5 6 21 6"></polyline>
                            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                        </svg>
                        <?php esc_html_e('Limpiar todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
                <ul class="flavor-upload-archivos-lista"></ul>
            </div>

            <!-- Información adicional del contenido -->
            <div class="flavor-upload-detalles">
                <h3 class="flavor-upload-detalles-titulo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                    <?php esc_html_e('Información del contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h3>

                <!-- Título -->
                <div class="flavor-upload-campo">
                    <label for="<?php echo esc_attr($form_id); ?>-titulo" class="flavor-upload-label">
                        <?php esc_html_e('Título', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-upload-requerido">*</span>
                    </label>
                    <input type="text"
                           id="<?php echo esc_attr($form_id); ?>-titulo"
                           name="titulo"
                           class="flavor-upload-text-input"
                           placeholder="<?php esc_attr_e('Escribe un título descriptivo...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                           required
                           maxlength="100">
                    <span class="flavor-upload-campo-contador">
                        <span class="flavor-upload-contador-actual">0</span>/100
                    </span>
                </div>

                <!-- Descripción -->
                <div class="flavor-upload-campo">
                    <label for="<?php echo esc_attr($form_id); ?>-descripcion" class="flavor-upload-label">
                        <?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <textarea id="<?php echo esc_attr($form_id); ?>-descripcion"
                              name="descripcion"
                              class="flavor-upload-textarea"
                              placeholder="<?php esc_attr_e('Cuéntanos más sobre este contenido...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"
                              rows="3"
                              maxlength="500"></textarea>
                    <span class="flavor-upload-campo-contador">
                        <span class="flavor-upload-contador-actual">0</span>/500
                    </span>
                </div>

                <?php if ($mostrar_categorias): ?>
                <!-- Categoría -->
                <div class="flavor-upload-campo">
                    <label for="<?php echo esc_attr($form_id); ?>-categoria" class="flavor-upload-label">
                        <?php esc_html_e('Categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-upload-requerido">*</span>
                    </label>
                    <div class="flavor-upload-select-wrapper">
                        <select id="<?php echo esc_attr($form_id); ?>-categoria"
                                name="categoria"
                                class="flavor-upload-select"
                                required>
                            <option value=""><?php esc_html_e('Selecciona una categoría', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($categorias_disponibles as $categoria_valor => $categoria_nombre): ?>
                                <option value="<?php echo esc_attr($categoria_valor); ?>">
                                    <?php echo esc_html($categoria_nombre); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <svg class="flavor-upload-select-arrow" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6 9 12 15 18 9"></polyline>
                        </svg>
                    </div>
                </div>
                <?php endif; ?>

                <?php if ($mostrar_etiquetas): ?>
                <!-- Etiquetas -->
                <div class="flavor-upload-campo">
                    <label for="<?php echo esc_attr($form_id); ?>-etiquetas" class="flavor-upload-label">
                        <?php esc_html_e('Etiquetas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="flavor-upload-label-hint"><?php esc_html_e('(separadas por comas)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </label>
                    <div class="flavor-upload-tags-container">
                        <div class="flavor-upload-tags-list"></div>
                        <input type="text"
                               id="<?php echo esc_attr($form_id); ?>-etiquetas"
                               class="flavor-upload-tags-input"
                               placeholder="<?php esc_attr_e('Añade etiquetas...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <input type="hidden" name="etiquetas" class="flavor-upload-tags-hidden">
                    </div>
                    <p class="flavor-upload-campo-ayuda">
                        <?php esc_html_e('Presiona Enter o coma para agregar una etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($mostrar_privacidad): ?>
                <!-- Privacidad -->
                <div class="flavor-upload-campo">
                    <label class="flavor-upload-label">
                        <?php esc_html_e('Privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <div class="flavor-upload-privacidad-opciones">
                        <label class="flavor-upload-privacidad-opcion">
                            <input type="radio" name="privacidad" value="publico" checked>
                            <span class="flavor-upload-privacidad-radio"></span>
                            <span class="flavor-upload-privacidad-contenido">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <circle cx="12" cy="12" r="10"></circle>
                                    <line x1="2" y1="12" x2="22" y2="12"></line>
                                    <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path>
                                </svg>
                                <span>
                                    <strong><?php esc_html_e('Público', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <small><?php esc_html_e('Visible para todos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                                </span>
                            </span>
                        </label>
                        <label class="flavor-upload-privacidad-opcion">
                            <input type="radio" name="privacidad" value="comunidad">
                            <span class="flavor-upload-privacidad-radio"></span>
                            <span class="flavor-upload-privacidad-contenido">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="9" cy="7" r="4"></circle>
                                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                </svg>
                                <span>
                                    <strong><?php esc_html_e('Solo comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <small><?php esc_html_e('Solo miembros registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                                </span>
                            </span>
                        </label>
                        <label class="flavor-upload-privacidad-opcion">
                            <input type="radio" name="privacidad" value="privado">
                            <span class="flavor-upload-privacidad-radio"></span>
                            <span class="flavor-upload-privacidad-contenido">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                                <span>
                                    <strong><?php esc_html_e('Privado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <small><?php esc_html_e('Solo tú puedes verlo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></small>
                                </span>
                            </span>
                        </label>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Términos y condiciones -->
                <div class="flavor-upload-campo flavor-upload-campo--terminos">
                    <label class="flavor-upload-checkbox">
                        <input type="checkbox" name="aceptar_terminos" required>
                        <span class="flavor-upload-checkbox-mark"></span>
                        <span class="flavor-upload-checkbox-texto">
                            <?php printf(
                                esc_html__('Acepto los %stérminos y condiciones%s y confirmo que tengo los derechos necesarios para compartir este contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                '<a href="#" target="_blank">',
                                '</a>'
                            ); ?>
                        </span>
                    </label>
                </div>
            </div>

            <!-- Barra de progreso -->
            <div class="flavor-upload-progreso" style="display: none;">
                <div class="flavor-upload-progreso-header">
                    <span class="flavor-upload-progreso-texto"><?php esc_html_e('Subiendo archivos...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    <span class="flavor-upload-progreso-porcentaje">0%</span>
                </div>
                <div class="flavor-upload-progreso-barra">
                    <div class="flavor-upload-progreso-fill"></div>
                </div>
                <p class="flavor-upload-progreso-archivo"><?php esc_html_e('Preparando...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <!-- Botones de acción -->
            <div class="flavor-upload-acciones">
                <button type="button" class="flavor-btn flavor-btn--secundario flavor-upload-cancelar">
                    <?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button type="submit" class="flavor-btn flavor-btn--primario flavor-upload-enviar" disabled>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                        <polyline points="17 8 12 3 7 8"></polyline>
                        <line x1="12" y1="3" x2="12" y2="15"></line>
                    </svg>
                    <?php esc_html_e('Subir contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </form>

        <!-- Mensaje de éxito -->
        <div class="flavor-upload-exito" style="display: none;">
            <div class="flavor-upload-exito-icon">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                </svg>
            </div>
            <h3><?php esc_html_e('Contenido subido exitosamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Tu contenido ha sido enviado y está pendiente de revisión.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <div class="flavor-upload-exito-acciones">
                <button type="button" class="flavor-btn flavor-btn--primario flavor-upload-nuevo">
                    <?php esc_html_e('Subir más contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <a href="#" class="flavor-btn flavor-btn--secundario">
                    <?php esc_html_e('Ver mi contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        </div>

        <?php endif; ?>
    </div>
</section>

<style>
/* ===== ESTILOS PARA FORMULARIO DE SUBIDA ===== */

.flavor-upload-section {
    --flavor-upload-primary: #4f46e5;
    --flavor-upload-primary-dark: #4338ca;
    --flavor-upload-bg: #ffffff;
    --flavor-upload-bg-secondary: #f8f9fa;
    --flavor-upload-text: #1a1a2e;
    --flavor-upload-text-secondary: #6c757d;
    --flavor-upload-border: #e2e8f0;
    --flavor-upload-success: #10b981;
    --flavor-upload-error: #ef4444;
    --flavor-upload-warning: #f59e0b;

    padding: 2rem 0;
}

.flavor-upload-container {
    max-width: 700px;
    margin: 0 auto;
    padding: 0 1rem;
}

/* Header */
.flavor-upload-header {
    text-align: center;
    margin-bottom: 2rem;
}

.flavor-upload-header-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, var(--flavor-upload-primary), var(--flavor-upload-primary-dark));
    border-radius: 20px;
    color: white;
    margin-bottom: 1rem;
}

.flavor-upload-titulo {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--flavor-upload-text);
    margin: 0 0 0.5rem 0;
}

.flavor-upload-descripcion {
    color: var(--flavor-upload-text-secondary);
    margin: 0;
    font-size: 1rem;
}

/* Login requerido */
.flavor-upload-login-requerido {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--flavor-upload-bg);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.flavor-upload-login-icon {
    color: var(--flavor-upload-text-secondary);
    margin-bottom: 1rem;
}

.flavor-upload-login-requerido h3 {
    font-size: 1.25rem;
    color: var(--flavor-upload-text);
    margin: 0 0 0.5rem 0;
}

.flavor-upload-login-requerido p {
    color: var(--flavor-upload-text-secondary);
    margin: 0 0 1.5rem 0;
}

.flavor-upload-login-acciones {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* Formulario */
.flavor-upload-form {
    background: var(--flavor-upload-bg);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

/* Dropzone */
.flavor-upload-dropzone {
    position: relative;
    border: 2px dashed var(--flavor-upload-border);
    border-radius: 12px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    transition: all 0.3s ease;
    cursor: pointer;
    background: var(--flavor-upload-bg-secondary);
}

.flavor-upload-dropzone:hover {
    border-color: var(--flavor-upload-primary);
    background: rgba(79, 70, 229, 0.05);
}

.flavor-upload-dropzone.flavor-upload-dropzone--arrastrando {
    border-color: var(--flavor-upload-primary);
    background: rgba(79, 70, 229, 0.1);
}

.flavor-upload-dropzone.flavor-upload-dropzone--arrastrando .flavor-upload-dropzone-content {
    opacity: 0;
}

.flavor-upload-dropzone.flavor-upload-dropzone--arrastrando .flavor-upload-dropzone-drag {
    opacity: 1;
}

.flavor-upload-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
    z-index: 2;
}

.flavor-upload-dropzone-content {
    transition: opacity 0.3s ease;
}

.flavor-upload-dropzone-icon {
    color: var(--flavor-upload-primary);
    margin-bottom: 1rem;
}

.flavor-upload-dropzone-titulo {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--flavor-upload-text);
    margin: 0 0 0.5rem 0;
}

.flavor-upload-dropzone-texto {
    color: var(--flavor-upload-text-secondary);
    margin: 0 0 1rem 0;
}

.flavor-upload-dropzone-btn {
    background: none;
    border: none;
    color: var(--flavor-upload-primary);
    font-weight: 600;
    cursor: pointer;
    text-decoration: underline;
}

.flavor-upload-dropzone-info {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: 1rem;
}

.flavor-upload-info-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 0.8125rem;
    color: var(--flavor-upload-text-secondary);
}

.flavor-upload-dropzone-drag {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    opacity: 0;
    color: var(--flavor-upload-primary);
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.flavor-upload-dropzone-drag p {
    margin: 0.5rem 0 0 0;
    font-weight: 600;
}

/* Lista de archivos */
.flavor-upload-archivos {
    margin-top: 1.5rem;
}

.flavor-upload-archivos-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.flavor-upload-archivos-titulo {
    font-size: 1rem;
    font-weight: 600;
    color: var(--flavor-upload-text);
    margin: 0;
}

.flavor-upload-archivos-limpiar {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 0.5rem 0.75rem;
    background: none;
    border: none;
    color: var(--flavor-upload-error);
    font-size: 0.8125rem;
    cursor: pointer;
    border-radius: 6px;
    transition: background 0.2s ease;
}

.flavor-upload-archivos-limpiar:hover {
    background: rgba(239, 68, 68, 0.1);
}

.flavor-upload-archivos-lista {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-upload-archivo-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem;
    background: var(--flavor-upload-bg-secondary);
    border-radius: 8px;
}

.flavor-upload-archivo-preview {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    overflow: hidden;
    background: var(--flavor-upload-border);
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.flavor-upload-archivo-preview img,
.flavor-upload-archivo-preview video {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.flavor-upload-archivo-preview svg {
    color: var(--flavor-upload-text-secondary);
}

.flavor-upload-archivo-info {
    flex: 1;
    min-width: 0;
}

.flavor-upload-archivo-nombre {
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--flavor-upload-text);
    margin: 0 0 0.25rem 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.flavor-upload-archivo-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.75rem;
    color: var(--flavor-upload-text-secondary);
}

.flavor-upload-archivo-eliminar {
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: none;
    border: none;
    color: var(--flavor-upload-text-secondary);
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-upload-archivo-eliminar:hover {
    background: rgba(239, 68, 68, 0.1);
    color: var(--flavor-upload-error);
}

.flavor-upload-archivo-error {
    border: 1px solid var(--flavor-upload-error);
    background: rgba(239, 68, 68, 0.05);
}

.flavor-upload-archivo-error .flavor-upload-archivo-nombre {
    color: var(--flavor-upload-error);
}

/* Detalles del contenido */
.flavor-upload-detalles {
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid var(--flavor-upload-border);
}

.flavor-upload-detalles-titulo {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--flavor-upload-text);
    margin: 0 0 1.5rem 0;
}

/* Campos del formulario */
.flavor-upload-campo {
    margin-bottom: 1.5rem;
}

.flavor-upload-label {
    display: block;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--flavor-upload-text);
    margin-bottom: 0.5rem;
}

.flavor-upload-requerido {
    color: var(--flavor-upload-error);
}

.flavor-upload-label-hint {
    font-weight: 400;
    color: var(--flavor-upload-text-secondary);
}

.flavor-upload-text-input,
.flavor-upload-textarea,
.flavor-upload-select {
    width: 100%;
    padding: 0.75rem 1rem;
    font-size: 0.9375rem;
    color: var(--flavor-upload-text);
    background: var(--flavor-upload-bg);
    border: 1px solid var(--flavor-upload-border);
    border-radius: 8px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.flavor-upload-text-input:focus,
.flavor-upload-textarea:focus,
.flavor-upload-select:focus {
    outline: none;
    border-color: var(--flavor-upload-primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.flavor-upload-textarea {
    resize: vertical;
    min-height: 100px;
}

.flavor-upload-campo-contador {
    display: block;
    text-align: right;
    font-size: 0.75rem;
    color: var(--flavor-upload-text-secondary);
    margin-top: 0.25rem;
}

.flavor-upload-campo-ayuda {
    font-size: 0.75rem;
    color: var(--flavor-upload-text-secondary);
    margin: 0.25rem 0 0 0;
}

/* Select personalizado */
.flavor-upload-select-wrapper {
    position: relative;
}

.flavor-upload-select {
    appearance: none;
    padding-right: 2.5rem;
    cursor: pointer;
}

.flavor-upload-select-arrow {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    color: var(--flavor-upload-text-secondary);
}

/* Tags input */
.flavor-upload-tags-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    padding: 0.5rem;
    background: var(--flavor-upload-bg);
    border: 1px solid var(--flavor-upload-border);
    border-radius: 8px;
    min-height: 46px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.flavor-upload-tags-container:focus-within {
    border-color: var(--flavor-upload-primary);
    box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
}

.flavor-upload-tags-list {
    display: contents;
}

.flavor-upload-tag {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 0.25rem 0.5rem;
    background: var(--flavor-upload-primary);
    color: white;
    font-size: 0.8125rem;
    border-radius: 4px;
}

.flavor-upload-tag-remove {
    background: none;
    border: none;
    padding: 0;
    color: white;
    opacity: 0.7;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

.flavor-upload-tag-remove:hover {
    opacity: 1;
}

.flavor-upload-tags-input {
    flex: 1;
    min-width: 120px;
    border: none;
    background: none;
    padding: 0.25rem;
    font-size: 0.9375rem;
    color: var(--flavor-upload-text);
}

.flavor-upload-tags-input:focus {
    outline: none;
}

/* Opciones de privacidad */
.flavor-upload-privacidad-opciones {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.flavor-upload-privacidad-opcion {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--flavor-upload-bg-secondary);
    border: 2px solid transparent;
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-upload-privacidad-opcion:hover {
    border-color: var(--flavor-upload-border);
}

.flavor-upload-privacidad-opcion input {
    display: none;
}

.flavor-upload-privacidad-opcion input:checked + .flavor-upload-privacidad-radio {
    border-color: var(--flavor-upload-primary);
    background: var(--flavor-upload-primary);
}

.flavor-upload-privacidad-opcion input:checked + .flavor-upload-privacidad-radio::after {
    opacity: 1;
}

.flavor-upload-privacidad-opcion input:checked ~ .flavor-upload-privacidad-contenido {
    color: var(--flavor-upload-primary);
}

.flavor-upload-privacidad-radio {
    position: relative;
    width: 20px;
    height: 20px;
    border: 2px solid var(--flavor-upload-border);
    border-radius: 50%;
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.2s ease;
}

.flavor-upload-privacidad-radio::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 8px;
    height: 8px;
    background: white;
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.flavor-upload-privacidad-contenido {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    color: var(--flavor-upload-text-secondary);
}

.flavor-upload-privacidad-contenido svg {
    flex-shrink: 0;
    margin-top: 2px;
}

.flavor-upload-privacidad-contenido span {
    display: flex;
    flex-direction: column;
}

.flavor-upload-privacidad-contenido strong {
    font-size: 0.9375rem;
    color: var(--flavor-upload-text);
}

.flavor-upload-privacidad-contenido small {
    font-size: 0.8125rem;
}

/* Checkbox de términos */
.flavor-upload-checkbox {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    cursor: pointer;
}

.flavor-upload-checkbox input {
    display: none;
}

.flavor-upload-checkbox input:checked + .flavor-upload-checkbox-mark {
    background: var(--flavor-upload-primary);
    border-color: var(--flavor-upload-primary);
}

.flavor-upload-checkbox input:checked + .flavor-upload-checkbox-mark::after {
    opacity: 1;
}

.flavor-upload-checkbox-mark {
    position: relative;
    width: 20px;
    height: 20px;
    border: 2px solid var(--flavor-upload-border);
    border-radius: 4px;
    flex-shrink: 0;
    margin-top: 2px;
    transition: all 0.2s ease;
}

.flavor-upload-checkbox-mark::after {
    content: '';
    position: absolute;
    top: 3px;
    left: 6px;
    width: 5px;
    height: 9px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
    opacity: 0;
    transition: opacity 0.2s ease;
}

.flavor-upload-checkbox-texto {
    font-size: 0.875rem;
    color: var(--flavor-upload-text-secondary);
    line-height: 1.5;
}

.flavor-upload-checkbox-texto a {
    color: var(--flavor-upload-primary);
}

/* Barra de progreso */
.flavor-upload-progreso {
    margin-top: 1.5rem;
    padding: 1.5rem;
    background: var(--flavor-upload-bg-secondary);
    border-radius: 12px;
}

.flavor-upload-progreso-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
}

.flavor-upload-progreso-texto {
    font-weight: 500;
    color: var(--flavor-upload-text);
}

.flavor-upload-progreso-porcentaje {
    font-weight: 700;
    color: var(--flavor-upload-primary);
}

.flavor-upload-progreso-barra {
    height: 8px;
    background: var(--flavor-upload-border);
    border-radius: 4px;
    overflow: hidden;
}

.flavor-upload-progreso-fill {
    height: 100%;
    background: linear-gradient(90deg, var(--flavor-upload-primary), var(--flavor-upload-primary-dark));
    border-radius: 4px;
    width: 0;
    transition: width 0.3s ease;
}

.flavor-upload-progreso-archivo {
    font-size: 0.8125rem;
    color: var(--flavor-upload-text-secondary);
    margin: 0.75rem 0 0 0;
}

/* Botones de acción */
.flavor-upload-acciones {
    display: flex;
    justify-content: flex-end;
    gap: 1rem;
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid var(--flavor-upload-border);
}

/* Botones generales */
.flavor-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    font-size: 0.9375rem;
    font-weight: 600;
    text-decoration: none;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.flavor-btn--primario {
    background: var(--flavor-upload-primary);
    color: white;
}

.flavor-btn--primario:hover:not(:disabled) {
    background: var(--flavor-upload-primary-dark);
}

.flavor-btn--secundario {
    background: var(--flavor-upload-bg-secondary);
    color: var(--flavor-upload-text);
}

.flavor-btn--secundario:hover:not(:disabled) {
    background: var(--flavor-upload-border);
}

/* Mensaje de éxito */
.flavor-upload-exito {
    text-align: center;
    padding: 3rem 2rem;
    background: var(--flavor-upload-bg);
    border-radius: 16px;
    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}

.flavor-upload-exito-icon {
    color: var(--flavor-upload-success);
    margin-bottom: 1rem;
}

.flavor-upload-exito h3 {
    font-size: 1.25rem;
    color: var(--flavor-upload-text);
    margin: 0 0 0.5rem 0;
}

.flavor-upload-exito p {
    color: var(--flavor-upload-text-secondary);
    margin: 0 0 1.5rem 0;
}

.flavor-upload-exito-acciones {
    display: flex;
    justify-content: center;
    gap: 1rem;
    flex-wrap: wrap;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 640px) {
    .flavor-upload-form {
        padding: 1rem;
    }

    .flavor-upload-dropzone {
        padding: 1.5rem 1rem;
    }

    .flavor-upload-dropzone-info {
        flex-direction: column;
        gap: 0.5rem;
    }

    .flavor-upload-archivo-item {
        flex-wrap: wrap;
    }

    .flavor-upload-archivo-info {
        width: calc(100% - 92px);
    }

    .flavor-upload-privacidad-opcion {
        padding: 0.75rem;
    }

    .flavor-upload-acciones {
        flex-direction: column;
    }

    .flavor-upload-acciones .flavor-btn {
        width: 100%;
    }

    .flavor-upload-login-acciones {
        flex-direction: column;
    }

    .flavor-upload-login-acciones .flavor-btn {
        width: 100%;
    }
}
</style>

<script>
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        const formularios = document.querySelectorAll('.flavor-upload-form');

        formularios.forEach(function(formulario) {
            const dropzone = formulario.querySelector('.flavor-upload-dropzone');
            const inputArchivo = formulario.querySelector('.flavor-upload-input');
            const botonSeleccionar = formulario.querySelector('.flavor-upload-dropzone-btn');
            const listaArchivos = formulario.querySelector('.flavor-upload-archivos');
            const listaArchivosUl = formulario.querySelector('.flavor-upload-archivos-lista');
            const contadorArchivos = formulario.querySelector('.flavor-upload-archivos-count');
            const botonLimpiar = formulario.querySelector('.flavor-upload-archivos-limpiar');
            const botonEnviar = formulario.querySelector('.flavor-upload-enviar');
            const botonCancelar = formulario.querySelector('.flavor-upload-cancelar');
            const contenedorProgreso = formulario.querySelector('.flavor-upload-progreso');
            const barraProgreso = formulario.querySelector('.flavor-upload-progreso-fill');
            const textoPorcentaje = formulario.querySelector('.flavor-upload-progreso-porcentaje');
            const textoArchivo = formulario.querySelector('.flavor-upload-progreso-archivo');
            const seccionExito = formulario.closest('.flavor-upload-section').querySelector('.flavor-upload-exito');
            const botonNuevo = seccionExito ? seccionExito.querySelector('.flavor-upload-nuevo') : null;

            const maxTamano = parseInt(formulario.dataset.maxSize) || 50;
            const maxArchivos = parseInt(formulario.dataset.maxFiles) || 10;
            const tiposPermitidos = (formulario.dataset.tipos || '').split(',');
            const ajaxUrl = formulario.dataset.ajaxUrl;
            const nonce = formulario.dataset.nonce;

            let archivosSeleccionados = [];
            let etiquetas = [];

            // Función para formatear tamaño de archivo
            function formatearTamano(bytes) {
                if (bytes === 0) return '0 Bytes';
                const unidades = ['Bytes', 'KB', 'MB', 'GB'];
                const indice = Math.floor(Math.log(bytes) / Math.log(1024));
                return parseFloat((bytes / Math.pow(1024, indice)).toFixed(2)) + ' ' + unidades[indice];
            }

            // Función para validar archivo
            function validarArchivo(archivo) {
                const errores = [];

                if (!tiposPermitidos.includes(archivo.type)) {
                    errores.push('Tipo de archivo no permitido');
                }

                if (archivo.size > maxTamano * 1024 * 1024) {
                    errores.push('El archivo excede el tamaño máximo de ' + maxTamano + ' MB');
                }

                return errores;
            }

            // Función para agregar archivo a la lista
            function agregarArchivoALista(archivo, errores) {
                const itemLista = document.createElement('li');
                itemLista.className = 'flavor-upload-archivo-item';
                if (errores.length > 0) {
                    itemLista.classList.add('flavor-upload-archivo-error');
                }

                const esImagen = archivo.type.startsWith('image/');
                const esVideo = archivo.type.startsWith('video/');

                let previewHTML = '';
                if (esImagen) {
                    const urlPreview = URL.createObjectURL(archivo);
                    previewHTML = '<img src="' + urlPreview + '" alt="' + archivo.name + '">';
                } else if (esVideo) {
                    previewHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>';
                } else {
                    previewHTML = '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';
                }

                const metaInfo = errores.length > 0
                    ? '<span class="flavor-upload-archivo-error-texto">' + errores[0] + '</span>'
                    : '<span>' + formatearTamano(archivo.size) + '</span><span>' + archivo.type.split('/')[1].toUpperCase() + '</span>';

                itemLista.innerHTML =
                    '<div class="flavor-upload-archivo-preview">' + previewHTML + '</div>' +
                    '<div class="flavor-upload-archivo-info">' +
                        '<p class="flavor-upload-archivo-nombre">' + archivo.name + '</p>' +
                        '<div class="flavor-upload-archivo-meta">' + metaInfo + '</div>' +
                    '</div>' +
                    '<button type="button" class="flavor-upload-archivo-eliminar" aria-label="Eliminar archivo">' +
                        '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                            '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                            '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                        '</svg>' +
                    '</button>';

                // Event listener para eliminar
                const botonEliminar = itemLista.querySelector('.flavor-upload-archivo-eliminar');
                botonEliminar.addEventListener('click', function() {
                    const indice = archivosSeleccionados.indexOf(archivo);
                    if (indice > -1) {
                        archivosSeleccionados.splice(indice, 1);
                    }
                    itemLista.remove();
                    actualizarUI();
                });

                listaArchivosUl.appendChild(itemLista);
            }

            // Función para actualizar la UI
            function actualizarUI() {
                const tieneArchivos = archivosSeleccionados.length > 0;
                listaArchivos.style.display = tieneArchivos ? 'block' : 'none';
                contadorArchivos.textContent = archivosSeleccionados.length;

                // Habilitar/deshabilitar botón de enviar
                const inputTitulo = formulario.querySelector('input[name="titulo"]');
                const inputCategoria = formulario.querySelector('select[name="categoria"]');
                const inputTerminos = formulario.querySelector('input[name="aceptar_terminos"]');

                const formularioValido = tieneArchivos &&
                    inputTitulo && inputTitulo.value.trim() !== '' &&
                    (!inputCategoria || inputCategoria.value !== '') &&
                    inputTerminos && inputTerminos.checked;

                botonEnviar.disabled = !formularioValido;
            }

            // Función para procesar archivos
            function procesarArchivos(archivos) {
                const archivosArray = Array.from(archivos);

                archivosArray.forEach(function(archivo) {
                    if (archivosSeleccionados.length >= maxArchivos) {
                        return;
                    }

                    // Verificar si ya está en la lista
                    const yaExiste = archivosSeleccionados.some(function(archivoExistente) {
                        return archivoExistente.name === archivo.name && archivoExistente.size === archivo.size;
                    });

                    if (!yaExiste) {
                        const errores = validarArchivo(archivo);
                        if (errores.length === 0) {
                            archivosSeleccionados.push(archivo);
                        }
                        agregarArchivoALista(archivo, errores);
                    }
                });

                actualizarUI();
            }

            // Event listeners para drag & drop
            dropzone.addEventListener('dragover', function(evento) {
                evento.preventDefault();
                dropzone.classList.add('flavor-upload-dropzone--arrastrando');
            });

            dropzone.addEventListener('dragleave', function(evento) {
                evento.preventDefault();
                dropzone.classList.remove('flavor-upload-dropzone--arrastrando');
            });

            dropzone.addEventListener('drop', function(evento) {
                evento.preventDefault();
                dropzone.classList.remove('flavor-upload-dropzone--arrastrando');
                const archivos = evento.dataTransfer.files;
                procesarArchivos(archivos);
            });

            // Event listener para input de archivo
            inputArchivo.addEventListener('change', function() {
                procesarArchivos(this.files);
                this.value = ''; // Reset para permitir seleccionar el mismo archivo
            });

            // Event listener para botón de seleccionar
            botonSeleccionar.addEventListener('click', function(evento) {
                evento.preventDefault();
                inputArchivo.click();
            });

            // Event listener para limpiar todo
            botonLimpiar.addEventListener('click', function() {
                archivosSeleccionados = [];
                listaArchivosUl.innerHTML = '';
                actualizarUI();
            });

            // Event listeners para validación en tiempo real
            const inputTitulo = formulario.querySelector('input[name="titulo"]');
            const inputCategoria = formulario.querySelector('select[name="categoria"]');
            const inputTerminos = formulario.querySelector('input[name="aceptar_terminos"]');

            if (inputTitulo) {
                inputTitulo.addEventListener('input', function() {
                    const contador = this.closest('.flavor-upload-campo').querySelector('.flavor-upload-contador-actual');
                    if (contador) {
                        contador.textContent = this.value.length;
                    }
                    actualizarUI();
                });
            }

            const textareaDescripcion = formulario.querySelector('textarea[name="descripcion"]');
            if (textareaDescripcion) {
                textareaDescripcion.addEventListener('input', function() {
                    const contador = this.closest('.flavor-upload-campo').querySelector('.flavor-upload-contador-actual');
                    if (contador) {
                        contador.textContent = this.value.length;
                    }
                });
            }

            if (inputCategoria) {
                inputCategoria.addEventListener('change', actualizarUI);
            }

            if (inputTerminos) {
                inputTerminos.addEventListener('change', actualizarUI);
            }

            // Sistema de etiquetas
            const inputEtiquetas = formulario.querySelector('.flavor-upload-tags-input');
            const listaEtiquetas = formulario.querySelector('.flavor-upload-tags-list');
            const inputEtiquetasHidden = formulario.querySelector('.flavor-upload-tags-hidden');

            if (inputEtiquetas) {
                inputEtiquetas.addEventListener('keydown', function(evento) {
                    if (evento.key === 'Enter' || evento.key === ',') {
                        evento.preventDefault();
                        agregarEtiqueta(this.value.trim());
                        this.value = '';
                    }
                });

                inputEtiquetas.addEventListener('blur', function() {
                    if (this.value.trim()) {
                        agregarEtiqueta(this.value.trim());
                        this.value = '';
                    }
                });
            }

            function agregarEtiqueta(texto) {
                if (!texto || etiquetas.includes(texto.toLowerCase())) return;

                etiquetas.push(texto.toLowerCase());
                actualizarEtiquetasUI();
            }

            function eliminarEtiqueta(texto) {
                const indice = etiquetas.indexOf(texto.toLowerCase());
                if (indice > -1) {
                    etiquetas.splice(indice, 1);
                    actualizarEtiquetasUI();
                }
            }

            function actualizarEtiquetasUI() {
                listaEtiquetas.innerHTML = '';
                etiquetas.forEach(function(etiqueta) {
                    const elementoEtiqueta = document.createElement('span');
                    elementoEtiqueta.className = 'flavor-upload-tag';
                    elementoEtiqueta.innerHTML = etiqueta +
                        '<button type="button" class="flavor-upload-tag-remove" aria-label="Eliminar etiqueta">' +
                            '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">' +
                                '<line x1="18" y1="6" x2="6" y2="18"></line>' +
                                '<line x1="6" y1="6" x2="18" y2="18"></line>' +
                            '</svg>' +
                        '</button>';

                    elementoEtiqueta.querySelector('.flavor-upload-tag-remove').addEventListener('click', function() {
                        eliminarEtiqueta(etiqueta);
                    });

                    listaEtiquetas.appendChild(elementoEtiqueta);
                });

                if (inputEtiquetasHidden) {
                    inputEtiquetasHidden.value = etiquetas.join(',');
                }
            }

            // Event listener para cancelar
            botonCancelar.addEventListener('click', function() {
                archivosSeleccionados = [];
                etiquetas = [];
                listaArchivosUl.innerHTML = '';
                formulario.reset();
                actualizarEtiquetasUI();
                actualizarUI();

                // Reset contadores
                formulario.querySelectorAll('.flavor-upload-contador-actual').forEach(function(contador) {
                    contador.textContent = '0';
                });
            });

            // Event listener para enviar formulario
            formulario.addEventListener('submit', function(evento) {
                evento.preventDefault();

                if (archivosSeleccionados.length === 0) {
                    return;
                }

                const formData = new FormData(formulario);
                formData.append('action', 'flavor_upload_multimedia');
                formData.append('nonce', nonce);

                archivosSeleccionados.forEach(function(archivo, indice) {
                    formData.append('archivos[' + indice + ']', archivo);
                });

                // Mostrar progreso
                contenedorProgreso.style.display = 'block';
                botonEnviar.disabled = true;

                // Simular subida (en producción sería una llamada AJAX real)
                let progresoSimulado = 0;
                const intervaloProgreso = setInterval(function() {
                    progresoSimulado += Math.random() * 15;
                    if (progresoSimulado >= 100) {
                        progresoSimulado = 100;
                        clearInterval(intervaloProgreso);

                        setTimeout(function() {
                            contenedorProgreso.style.display = 'none';
                            formulario.style.display = 'none';
                            if (seccionExito) {
                                seccionExito.style.display = 'block';
                            }
                        }, 500);
                    }

                    barraProgreso.style.width = progresoSimulado + '%';
                    textoPorcentaje.textContent = Math.round(progresoSimulado) + '%';

                    if (progresoSimulado < 100) {
                        const archivoActualIndice = Math.min(
                            Math.floor(progresoSimulado / 100 * archivosSeleccionados.length),
                            archivosSeleccionados.length - 1
                        );
                        textoArchivo.textContent = 'Subiendo: ' + archivosSeleccionados[archivoActualIndice].name;
                    } else {
                        textoArchivo.textContent = 'Completado';
                    }
                }, 200);
            });

            // Event listener para subir más contenido
            if (botonNuevo) {
                botonNuevo.addEventListener('click', function() {
                    archivosSeleccionados = [];
                    etiquetas = [];
                    listaArchivosUl.innerHTML = '';
                    formulario.reset();
                    actualizarEtiquetasUI();
                    actualizarUI();
                    barraProgreso.style.width = '0%';
                    textoPorcentaje.textContent = '0%';

                    formulario.querySelectorAll('.flavor-upload-contador-actual').forEach(function(contador) {
                        contador.textContent = '0';
                    });

                    seccionExito.style.display = 'none';
                    formulario.style.display = 'block';
                });
            }
        });
    });
})();
</script>
