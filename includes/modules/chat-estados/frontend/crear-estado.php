<?php
/**
 * Vista para crear un nuevo estado
 *
 * @package Flavor_Platform
 * @subpackage Modules/Chat_Estados
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$colores_fondo = ['#128C7E', '#25D366', '#075E54', '#34B7F1', '#E91E63', '#9C27B0', '#673AB7', '#3F51B5', '#FF5722', '#795548'];
?>

<div class="flavor-crear-estado" data-flavor-crear-estado style="display:none;">
    <div class="crear-estado-overlay"></div>

    <div class="crear-estado-container">
        <!-- Header -->
        <div class="crear-estado-header">
            <button type="button" class="btn-cerrar-crear" aria-label="<?php esc_attr_e('Cerrar', 'flavor-platform'); ?>">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            <h3><?php esc_html_e('Crear estado', 'flavor-platform'); ?></h3>
            <button type="button" class="btn-publicar-estado" disabled>
                <?php esc_html_e('Publicar', 'flavor-platform'); ?>
            </button>
        </div>

        <!-- Tabs de tipo -->
        <div class="crear-estado-tabs">
            <button type="button" class="tab-tipo active" data-tipo="texto">
                <span class="dashicons dashicons-text"></span>
                <?php esc_html_e('Texto', 'flavor-platform'); ?>
            </button>
            <button type="button" class="tab-tipo" data-tipo="imagen">
                <span class="dashicons dashicons-format-image"></span>
                <?php esc_html_e('Foto', 'flavor-platform'); ?>
            </button>
            <button type="button" class="tab-tipo" data-tipo="video">
                <span class="dashicons dashicons-video-alt3"></span>
                <?php esc_html_e('Video', 'flavor-platform'); ?>
            </button>
        </div>

        <!-- Área de contenido -->
        <div class="crear-estado-content">
            <!-- Estado de texto -->
            <div class="estado-tipo-panel tipo-texto active">
                <div class="estado-texto-preview" style="background-color: #128C7E;">
                    <textarea class="estado-texto-input"
                              placeholder="<?php esc_attr_e('Escribe tu estado...', 'flavor-platform'); ?>"
                              maxlength="700"></textarea>
                </div>

                <!-- Selector de color de fondo -->
                <div class="selector-colores">
                    <?php foreach ($colores_fondo as $color): ?>
                    <button type="button" class="color-opcion <?php echo $color === '#128C7E' ? 'active' : ''; ?>"
                            data-color="<?php echo esc_attr($color); ?>"
                            style="background-color: <?php echo esc_attr($color); ?>;"
                            aria-label="<?php echo esc_attr($color); ?>">
                    </button>
                    <?php endforeach; ?>
                </div>

                <!-- Selector de fuente -->
                <div class="selector-fuentes">
                    <button type="button" class="fuente-opcion active" data-fuente="default">Aa</button>
                    <button type="button" class="fuente-opcion" data-fuente="serif" style="font-family:serif;">Aa</button>
                    <button type="button" class="fuente-opcion" data-fuente="mono" style="font-family:monospace;">Aa</button>
                    <button type="button" class="fuente-opcion" data-fuente="cursive" style="font-family:cursive;">Aa</button>
                </div>
            </div>

            <!-- Estado de imagen -->
            <div class="estado-tipo-panel tipo-imagen">
                <div class="upload-zone" data-tipo="imagen">
                    <input type="file" class="upload-input" accept="image/*" style="display:none;">
                    <div class="upload-placeholder">
                        <span class="dashicons dashicons-format-image"></span>
                        <p><?php esc_html_e('Haz clic o arrastra una imagen', 'flavor-platform'); ?></p>
                        <small><?php esc_html_e('Máximo 5MB', 'flavor-platform'); ?></small>
                    </div>
                    <div class="upload-preview" style="display:none;">
                        <img src="" alt="">
                        <button type="button" class="btn-quitar-media">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                </div>

                <!-- Texto superpuesto opcional -->
                <div class="texto-superpuesto-wrapper" style="display:none;">
                    <input type="text" class="texto-superpuesto-input"
                           placeholder="<?php esc_attr_e('Añadir texto (opcional)', 'flavor-platform'); ?>"
                           maxlength="200">
                </div>
            </div>

            <!-- Estado de video -->
            <div class="estado-tipo-panel tipo-video">
                <div class="upload-zone" data-tipo="video">
                    <input type="file" class="upload-input" accept="video/*" style="display:none;">
                    <div class="upload-placeholder">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <p><?php esc_html_e('Haz clic o arrastra un video', 'flavor-platform'); ?></p>
                        <small><?php esc_html_e('Máximo 30 segundos, 16MB', 'flavor-platform'); ?></small>
                    </div>
                    <div class="upload-preview" style="display:none;">
                        <video src="" controls></video>
                        <button type="button" class="btn-quitar-media">
                            <span class="dashicons dashicons-no"></span>
                        </button>
                    </div>
                </div>

                <!-- Texto superpuesto opcional -->
                <div class="texto-superpuesto-wrapper" style="display:none;">
                    <input type="text" class="texto-superpuesto-input"
                           placeholder="<?php esc_attr_e('Añadir texto (opcional)', 'flavor-platform'); ?>"
                           maxlength="200">
                </div>
            </div>
        </div>

        <!-- Opciones de privacidad -->
        <div class="crear-estado-privacidad">
            <button type="button" class="btn-privacidad">
                <span class="dashicons dashicons-visibility"></span>
                <span class="privacidad-label"><?php esc_html_e('Todos mis contactos', 'flavor-platform'); ?></span>
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>

            <div class="privacidad-opciones" style="display:none;">
                <label class="privacidad-opcion">
                    <input type="radio" name="privacidad" value="todos" checked>
                    <span class="dashicons dashicons-groups"></span>
                    <div class="privacidad-info">
                        <strong><?php esc_html_e('Todos mis contactos', 'flavor-platform'); ?></strong>
                        <small><?php esc_html_e('Todos pueden ver tu estado', 'flavor-platform'); ?></small>
                    </div>
                </label>

                <label class="privacidad-opcion">
                    <input type="radio" name="privacidad" value="contactos_excepto">
                    <span class="dashicons dashicons-hidden"></span>
                    <div class="privacidad-info">
                        <strong><?php esc_html_e('Mis contactos excepto...', 'flavor-platform'); ?></strong>
                        <small><?php esc_html_e('Ocultar a ciertos contactos', 'flavor-platform'); ?></small>
                    </div>
                </label>

                <label class="privacidad-opcion">
                    <input type="radio" name="privacidad" value="solo_compartir">
                    <span class="dashicons dashicons-lock"></span>
                    <div class="privacidad-info">
                        <strong><?php esc_html_e('Solo compartir con...', 'flavor-platform'); ?></strong>
                        <small><?php esc_html_e('Solo ciertos contactos verán tu estado', 'flavor-platform'); ?></small>
                    </div>
                </label>
            </div>
        </div>

        <!-- Loading overlay -->
        <div class="crear-estado-loading" style="display:none;">
            <div class="loading-spinner"></div>
            <p><?php esc_html_e('Publicando estado...', 'flavor-platform'); ?></p>
        </div>
    </div>
</div>

<style>
/* Variables */
:root {
    --crear-estado-bg: #1a1a1a;
    --crear-estado-text: #ffffff;
    --crear-estado-muted: rgba(255, 255, 255, 0.6);
    --crear-estado-border: rgba(255, 255, 255, 0.1);
    --crear-estado-primary: #25D366;
}

/* Contenedor principal */
.flavor-crear-estado {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    z-index: 999998;
    display: flex;
    align-items: center;
    justify-content: center;
}

.crear-estado-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
}

.crear-estado-container {
    position: relative;
    width: 100%;
    max-width: 450px;
    max-height: 90vh;
    background: var(--crear-estado-bg);
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

/* Header */
.crear-estado-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px;
    border-bottom: 1px solid var(--crear-estado-border);
}

.crear-estado-header h3 {
    margin: 0;
    color: var(--crear-estado-text);
    font-size: 16px;
}

.btn-cerrar-crear {
    background: none;
    border: none;
    color: var(--crear-estado-muted);
    font-size: 24px;
    cursor: pointer;
    padding: 4px;
}

.btn-publicar-estado {
    background: var(--crear-estado-primary);
    color: white;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s;
}

.btn-publicar-estado:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* Tabs */
.crear-estado-tabs {
    display: flex;
    border-bottom: 1px solid var(--crear-estado-border);
}

.tab-tipo {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    padding: 12px;
    background: none;
    border: none;
    color: var(--crear-estado-muted);
    cursor: pointer;
    transition: all 0.2s;
    border-bottom: 2px solid transparent;
}

.tab-tipo:hover {
    color: var(--crear-estado-text);
}

.tab-tipo.active {
    color: var(--crear-estado-primary);
    border-bottom-color: var(--crear-estado-primary);
}

.tab-tipo .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* Contenido */
.crear-estado-content {
    flex: 1;
    overflow-y: auto;
    padding: 16px;
}

.estado-tipo-panel {
    display: none;
}

.estado-tipo-panel.active {
    display: block;
}

/* Estado de texto */
.estado-texto-preview {
    border-radius: 12px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    transition: background-color 0.3s;
}

.estado-texto-input {
    width: 100%;
    min-height: 150px;
    background: transparent;
    border: none;
    color: white;
    font-size: 20px;
    text-align: center;
    resize: none;
}

.estado-texto-input::placeholder {
    color: rgba(255, 255, 255, 0.5);
}

.estado-texto-input:focus {
    outline: none;
}

/* Fuentes */
.estado-texto-input.font-serif { font-family: Georgia, serif; }
.estado-texto-input.font-mono { font-family: 'Courier New', monospace; }
.estado-texto-input.font-cursive { font-family: 'Brush Script MT', cursive; }

/* Selector de colores */
.selector-colores {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 16px;
    flex-wrap: wrap;
}

.color-opcion {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.2s, border-color 0.2s;
}

.color-opcion:hover {
    transform: scale(1.1);
}

.color-opcion.active {
    border-color: white;
}

/* Selector de fuentes */
.selector-fuentes {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-top: 12px;
}

.fuente-opcion {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    background: var(--crear-estado-border);
    border: 2px solid transparent;
    color: var(--crear-estado-text);
    font-size: 16px;
    cursor: pointer;
    transition: all 0.2s;
}

.fuente-opcion:hover {
    background: rgba(255, 255, 255, 0.2);
}

.fuente-opcion.active {
    border-color: var(--crear-estado-primary);
    background: rgba(37, 211, 102, 0.2);
}

/* Upload zone */
.upload-zone {
    border: 2px dashed var(--crear-estado-border);
    border-radius: 12px;
    min-height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: border-color 0.2s;
}

.upload-zone:hover,
.upload-zone.dragover {
    border-color: var(--crear-estado-primary);
}

.upload-placeholder {
    text-align: center;
    color: var(--crear-estado-muted);
}

.upload-placeholder .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    margin-bottom: 12px;
}

.upload-placeholder p {
    margin: 8px 0 4px;
}

.upload-placeholder small {
    font-size: 12px;
    opacity: 0.7;
}

.upload-preview {
    position: relative;
    width: 100%;
}

.upload-preview img,
.upload-preview video {
    width: 100%;
    max-height: 300px;
    object-fit: contain;
    border-radius: 8px;
}

.btn-quitar-media {
    position: absolute;
    top: 8px;
    right: 8px;
    background: rgba(0, 0, 0, 0.7);
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Texto superpuesto */
.texto-superpuesto-wrapper {
    margin-top: 12px;
}

.texto-superpuesto-input {
    width: 100%;
    padding: 10px 14px;
    background: var(--crear-estado-border);
    border: none;
    border-radius: 8px;
    color: var(--crear-estado-text);
    font-size: 14px;
}

.texto-superpuesto-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.15);
}

/* Privacidad */
.crear-estado-privacidad {
    padding: 12px 16px;
    border-top: 1px solid var(--crear-estado-border);
}

.btn-privacidad {
    width: 100%;
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    background: var(--crear-estado-border);
    border: none;
    border-radius: 8px;
    color: var(--crear-estado-text);
    cursor: pointer;
}

.btn-privacidad .privacidad-label {
    flex: 1;
    text-align: left;
}

.privacidad-opciones {
    margin-top: 12px;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 8px;
    overflow: hidden;
}

.privacidad-opcion {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 14px;
    cursor: pointer;
    transition: background 0.2s;
}

.privacidad-opcion:hover {
    background: rgba(255, 255, 255, 0.05);
}

.privacidad-opcion input {
    display: none;
}

.privacidad-opcion input:checked + .dashicons {
    color: var(--crear-estado-primary);
}

.privacidad-opcion .dashicons {
    font-size: 20px;
    color: var(--crear-estado-muted);
}

.privacidad-info {
    flex: 1;
}

.privacidad-info strong {
    display: block;
    color: var(--crear-estado-text);
    font-size: 14px;
}

.privacidad-info small {
    color: var(--crear-estado-muted);
    font-size: 12px;
}

/* Loading */
.crear-estado-loading {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 16px;
}

.loading-spinner {
    width: 40px;
    height: 40px;
    border: 3px solid var(--crear-estado-border);
    border-top-color: var(--crear-estado-primary);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.crear-estado-loading p {
    color: var(--crear-estado-text);
    margin: 0;
}

/* Responsive */
@media (max-width: 480px) {
    .crear-estado-container {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }

    .estado-texto-input {
        font-size: 18px;
    }
}
</style>
