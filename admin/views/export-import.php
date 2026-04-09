<?php
/**
 * Vista de Exportar / Importar configuración
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap flavor-export-import-wrap">
    <h1 class="wp-heading-inline">
        <span class="dashicons dashicons-download" style="margin-right: 8px;"></span>
        <?php esc_html_e('Exportar / Importar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
    </h1>

    <p class="description" style="margin-bottom: 20px;">
        <?php esc_html_e('Gestiona la configuración de Flavor Platform: exporta para hacer backups, importa desde otro sitio, o aplica presets predefinidos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        <strong><?php esc_html_e('Las claves API y datos sensibles nunca se incluyen en las exportaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
    </p>

    <!-- Tabs de navegación -->
    <nav class="nav-tab-wrapper flavor-export-import-tabs">
        <a href="#tab-exportar" class="nav-tab nav-tab-active" data-tab="exportar">
            <span class="dashicons dashicons-download"></span>
            <?php esc_html_e('Exportar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="#tab-importar" class="nav-tab" data-tab="importar">
            <span class="dashicons dashicons-upload"></span>
            <?php esc_html_e('Importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
        <a href="#tab-presets" class="nav-tab" data-tab="presets">
            <span class="dashicons dashicons-admin-appearance"></span>
            <?php esc_html_e('Presets', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </a>
    </nav>

    <!-- Tab: Exportar -->
    <div id="tab-exportar" class="flavor-tab-content active">
        <div class="flavor-export-import-card">
            <div class="flavor-card-header">
                <h2>
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Exportar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('Selecciona qué datos deseas exportar. Se generará un archivo JSON que podrás descargar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <form id="flavor-export-form" method="post">
                <div class="flavor-export-sections">
                    <h3><?php esc_html_e('Secciones a exportar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                    <div class="flavor-checkbox-grid">
                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="config" checked>
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-admin-settings"></span>
                                <strong><?php esc_html_e('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Ajustes del plugin, módulos activos, perfil de aplicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>

                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="design" checked>
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-art"></span>
                                <strong><?php esc_html_e('Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Tema, colores, tipografía, espaciados, CSS personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>

                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="pages">
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-admin-page"></span>
                                <strong><?php esc_html_e('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Páginas creadas con el Page Builder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>

                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="landings">
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-welcome-widgets-menus"></span>
                                <strong><?php esc_html_e('Landings', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Landing pages con estructura del builder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>

                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="roles">
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-groups"></span>
                                <strong><?php esc_html_e('Roles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Roles personalizados creados para el sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>

                        <label class="flavor-checkbox-card">
                            <input type="checkbox" name="export_sections[]" value="permissions">
                            <div class="flavor-checkbox-card-content">
                                <span class="dashicons dashicons-lock"></span>
                                <strong><?php esc_html_e('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span class="flavor-checkbox-desc"><?php esc_html_e('Configuración de acceso y capabilities', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flavor-export-actions">
                    <button type="button" id="flavor-select-all-export" class="button">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Seleccionar todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" id="flavor-deselect-all-export" class="button">
                        <span class="dashicons dashicons-dismiss"></span>
                        <?php esc_html_e('Deseleccionar todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>

                <div class="flavor-export-submit">
                    <button type="submit" id="flavor-export-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Generar y Descargar JSON', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>

            <div id="flavor-export-result" class="flavor-result-area hidden">
                <h4><?php esc_html_e('Exportación generada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <div class="flavor-export-preview">
                    <textarea id="flavor-export-json" readonly rows="10"></textarea>
                </div>
                <div class="flavor-export-result-actions">
                    <button type="button" id="flavor-copy-export" class="button">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e('Copiar al portapapeles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" id="flavor-download-export" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Descargar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab: Importar -->
    <div id="tab-importar" class="flavor-tab-content">
        <div class="flavor-export-import-card">
            <div class="flavor-card-header">
                <h2>
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Importar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('Sube un archivo JSON de exportación o pega el contenido directamente. Se mostrará una previsualización antes de aplicar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <!-- Paso 1: Seleccionar archivo -->
            <div id="flavor-import-step-1" class="flavor-import-step active">
                <div class="flavor-step-header">
                    <span class="flavor-step-number">1</span>
                    <h3><?php esc_html_e('Seleccionar origen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>

                <div class="flavor-import-methods">
                    <div class="flavor-import-method" id="flavor-import-file-method">
                        <div class="flavor-dropzone" id="flavor-import-dropzone">
                            <input type="file" id="flavor-import-file" accept=".json" class="flavor-file-input">
                            <div class="flavor-dropzone-content">
                                <span class="dashicons dashicons-upload"></span>
                                <p class="flavor-dropzone-text"><?php esc_html_e('Arrastra un archivo JSON aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                <p class="flavor-dropzone-subtext"><?php esc_html_e('o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            </div>
                            <div class="flavor-dropzone-file hidden">
                                <span class="dashicons dashicons-media-code"></span>
                                <span class="flavor-filename"></span>
                                <button type="button" class="flavor-remove-file" title="<?php esc_attr_e('Quitar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                    <span class="dashicons dashicons-no-alt"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flavor-import-separator">
                        <span><?php esc_html_e('o', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>

                    <div class="flavor-import-method" id="flavor-import-paste-method">
                        <label for="flavor-import-json-paste">
                            <span class="dashicons dashicons-editor-paste-text"></span>
                            <?php esc_html_e('Pegar JSON directamente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <textarea id="flavor-import-json-paste" rows="6" placeholder='{"version": "3.1.0", ...}'></textarea>
                    </div>
                </div>

                <div class="flavor-import-step-actions">
                    <button type="button" id="flavor-preview-import-btn" class="button button-primary" disabled>
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Analizar y Previsualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <!-- Paso 2: Previsualización -->
            <div id="flavor-import-step-2" class="flavor-import-step">
                <div class="flavor-step-header">
                    <span class="flavor-step-number">2</span>
                    <h3><?php esc_html_e('Previsualización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>

                <div id="flavor-import-warnings" class="flavor-import-warnings hidden"></div>

                <div class="flavor-import-metadata" id="flavor-import-metadata">
                    <!-- Se llena con JS -->
                </div>

                <div class="flavor-import-sections-preview" id="flavor-import-sections-preview">
                    <!-- Se llena con JS -->
                </div>

                <div class="flavor-import-step-actions">
                    <button type="button" id="flavor-back-step-1" class="button">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        <?php esc_html_e('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" id="flavor-continue-step-3" class="button button-primary">
                        <?php esc_html_e('Continuar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <span class="dashicons dashicons-arrow-right-alt"></span>
                    </button>
                </div>
            </div>

            <!-- Paso 3: Opciones de importación -->
            <div id="flavor-import-step-3" class="flavor-import-step">
                <div class="flavor-step-header">
                    <span class="flavor-step-number">3</span>
                    <h3><?php esc_html_e('Opciones de importación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                </div>

                <form id="flavor-import-form">
                    <div class="flavor-import-mode">
                        <h4><?php esc_html_e('Modo de importación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="flavor-radio-cards">
                            <label class="flavor-radio-card">
                                <input type="radio" name="import_mode" value="merge" checked>
                                <div class="flavor-radio-card-content">
                                    <span class="dashicons dashicons-randomize"></span>
                                    <strong><?php esc_html_e('Combinar (merge)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <span class="flavor-radio-desc"><?php esc_html_e('Fusiona con la configuración existente. Los valores nuevos sobrescriben los antiguos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </label>

                            <label class="flavor-radio-card">
                                <input type="radio" name="import_mode" value="overwrite">
                                <div class="flavor-radio-card-content">
                                    <span class="dashicons dashicons-update"></span>
                                    <strong><?php esc_html_e('Sobrescribir todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <span class="flavor-radio-desc"><?php esc_html_e('Reemplaza completamente la configuración actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </label>

                            <label class="flavor-radio-card">
                                <input type="radio" name="import_mode" value="only_missing">
                                <div class="flavor-radio-card-content">
                                    <span class="dashicons dashicons-plus-alt2"></span>
                                    <strong><?php esc_html_e('Solo lo que falta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                    <span class="flavor-radio-desc"><?php esc_html_e('Solo importa ajustes que no existan actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="flavor-import-sections-select">
                        <h4><?php esc_html_e('Secciones a importar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div id="flavor-import-sections-checkboxes" class="flavor-checkbox-list">
                            <!-- Se llena con JS basado en el preview -->
                        </div>
                    </div>

                    <div class="flavor-import-step-actions">
                        <button type="button" id="flavor-back-step-2" class="button">
                            <span class="dashicons dashicons-arrow-left-alt"></span>
                            <?php esc_html_e('Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <button type="submit" id="flavor-apply-import-btn" class="button button-primary button-hero">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Aplicar Importación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Progreso y resultado -->
            <div id="flavor-import-progress" class="flavor-import-progress hidden">
                <div class="flavor-progress-bar">
                    <div class="flavor-progress-fill"></div>
                </div>
                <p class="flavor-progress-text"><?php esc_html_e('Importando configuración...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>

            <div id="flavor-import-result" class="flavor-result-area hidden"></div>
        </div>
    </div>

    <!-- Tab: Presets -->
    <div id="tab-presets" class="flavor-tab-content">
        <div class="flavor-export-import-card">
            <div class="flavor-card-header">
                <h2>
                    <span class="dashicons dashicons-admin-appearance"></span>
                    <?php esc_html_e('Presets de Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </h2>
                <p class="description">
                    <?php esc_html_e('Aplica una configuración predefinida según el tipo de proyecto. Esto modificará los módulos activos y ajustes de diseño.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <div class="flavor-presets-grid" id="flavor-presets-grid">
                <!-- Se llena con JS -->
            </div>
        </div>
    </div>

    <!-- Notificaciones -->
    <div id="flavor-export-import-notices" class="flavor-notices"></div>
</div>

<style>
/* Estilos base */
.flavor-export-import-wrap {
    max-width: 1200px;
}

.flavor-export-import-tabs {
    margin-bottom: 20px;
}

.flavor-export-import-tabs .nav-tab .dashicons {
    margin-right: 5px;
    vertical-align: middle;
}

.flavor-tab-content {
    display: none;
}

.flavor-tab-content.active {
    display: block;
}

/* Cards */
.flavor-export-import-card {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 20px;
}

.flavor-card-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e0e0e0;
}

.flavor-card-header h2 {
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-card-header .description {
    margin: 0;
    color: #646970;
}

/* Checkbox Grid */
.flavor-checkbox-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.flavor-checkbox-card {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border: 2px solid #dcdcde;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.flavor-checkbox-card:hover {
    border-color: #2271b1;
    background: #f6f7f7;
}

.flavor-checkbox-card input[type="checkbox"] {
    margin: 4px 12px 0 0;
    flex-shrink: 0;
}

.flavor-checkbox-card input[type="checkbox"]:checked + .flavor-checkbox-card-content {
    color: #2271b1;
}

.flavor-checkbox-card-content {
    display: flex;
    flex-direction: column;
}

.flavor-checkbox-card-content .dashicons {
    font-size: 24px;
    width: 24px;
    height: 24px;
    margin-bottom: 8px;
    color: #2271b1;
}

.flavor-checkbox-card-content strong {
    display: block;
    margin-bottom: 4px;
}

.flavor-checkbox-desc {
    font-size: 12px;
    color: #646970;
}

/* Export actions */
.flavor-export-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.flavor-export-submit {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.flavor-export-submit .button-hero {
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

/* Result area */
.flavor-result-area {
    margin-top: 20px;
    padding: 20px;
    background: #f6f7f7;
    border-radius: 4px;
}

.flavor-result-area.hidden {
    display: none;
}

.flavor-result-area.success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
}

.flavor-result-area.error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
}

.flavor-export-preview textarea {
    width: 100%;
    font-family: monospace;
    font-size: 12px;
    background: #fff;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    padding: 10px;
}

.flavor-export-result-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Import steps */
.flavor-import-step {
    display: none;
}

.flavor-import-step.active {
    display: block;
}

.flavor-step-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
}

.flavor-step-number {
    width: 32px;
    height: 32px;
    background: #2271b1;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
}

.flavor-step-header h3 {
    margin: 0;
}

/* Dropzone */
.flavor-dropzone {
    border: 2px dashed #c3c4c7;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
    position: relative;
}

.flavor-dropzone:hover,
.flavor-dropzone.dragover {
    border-color: #2271b1;
    background: #f0f6fc;
}

.flavor-dropzone .flavor-file-input {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    cursor: pointer;
}

.flavor-dropzone-content .dashicons {
    font-size: 48px;
    width: 48px;
    height: 48px;
    color: #c3c4c7;
    margin-bottom: 10px;
}

.flavor-dropzone-text {
    font-size: 16px;
    font-weight: 500;
    margin: 0 0 5px 0;
}

.flavor-dropzone-subtext {
    color: #646970;
    margin: 0;
}

.flavor-dropzone-file {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.flavor-dropzone-file .dashicons {
    color: #2271b1;
}

.flavor-dropzone-file .flavor-filename {
    font-weight: 500;
}

.flavor-remove-file {
    background: none;
    border: none;
    cursor: pointer;
    color: #cc1818;
    padding: 0;
}

/* Import methods */
.flavor-import-methods {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    gap: 20px;
    align-items: start;
}

.flavor-import-separator {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px 0;
}

.flavor-import-separator span {
    background: #fff;
    padding: 5px 15px;
    color: #646970;
    font-style: italic;
}

.flavor-import-method label {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 10px;
    font-weight: 500;
}

.flavor-import-method textarea {
    width: 100%;
    font-family: monospace;
    font-size: 12px;
}

/* Import step actions */
.flavor-import-step-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e0e0e0;
}

.flavor-import-step-actions .button .dashicons {
    margin-right: 4px;
    vertical-align: middle;
}

/* Warnings */
.flavor-import-warnings {
    background: #fcf9e8;
    border: 1px solid #f0c33c;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
}

.flavor-import-warnings .dashicons {
    color: #dba617;
    margin-right: 8px;
}

/* Metadata */
.flavor-import-metadata {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    background: #f6f7f7;
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
}

.flavor-metadata-item {
    display: flex;
    flex-direction: column;
}

.flavor-metadata-item label {
    font-size: 11px;
    text-transform: uppercase;
    color: #646970;
    margin-bottom: 4px;
}

.flavor-metadata-item span {
    font-weight: 500;
}

/* Sections preview */
.flavor-import-sections-preview {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
}

.flavor-section-preview-card {
    background: #f6f7f7;
    border-radius: 4px;
    padding: 15px;
    border-left: 4px solid #2271b1;
}

.flavor-section-preview-card h4 {
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.flavor-section-preview-card .count {
    background: #2271b1;
    color: #fff;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.flavor-section-preview-card .changes {
    font-size: 13px;
    color: #646970;
}

.flavor-section-preview-card .items {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #e0e0e0;
}

.flavor-section-preview-card .item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
    font-size: 13px;
}

.flavor-section-preview-card .item .action-create {
    color: #00a32a;
}

.flavor-section-preview-card .item .action-update {
    color: #dba617;
}

/* Radio cards */
.flavor-radio-cards {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}

.flavor-radio-card {
    display: flex;
    align-items: flex-start;
    padding: 15px;
    border: 2px solid #dcdcde;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.flavor-radio-card:hover {
    border-color: #2271b1;
}

.flavor-radio-card input[type="radio"] {
    margin: 4px 12px 0 0;
}

.flavor-radio-card input[type="radio"]:checked + .flavor-radio-card-content {
    color: #2271b1;
}

.flavor-radio-card-content {
    display: flex;
    flex-direction: column;
}

.flavor-radio-card-content .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
    margin-bottom: 6px;
    color: #2271b1;
}

.flavor-radio-desc {
    font-size: 12px;
    color: #646970;
}

/* Checkbox list */
.flavor-checkbox-list {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
}

.flavor-checkbox-list label {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px;
    background: #f6f7f7;
    border-radius: 4px;
    cursor: pointer;
}

.flavor-checkbox-list label:hover {
    background: #e9ecef;
}

/* Progress */
.flavor-import-progress {
    text-align: center;
    padding: 30px;
}

.flavor-progress-bar {
    height: 20px;
    background: #e0e0e0;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 15px;
}

.flavor-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #2271b1, #135e96);
    width: 0%;
    transition: width 0.3s ease;
    animation: progress-animation 1.5s infinite;
}

@keyframes progress-animation {
    0% { width: 0%; }
    50% { width: 70%; }
    100% { width: 100%; }
}

/* Presets grid */
.flavor-presets-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.flavor-preset-card {
    background: #fff;
    border: 2px solid #dcdcde;
    border-radius: 8px;
    padding: 20px;
    transition: all 0.2s ease;
    cursor: pointer;
}

.flavor-preset-card:hover {
    border-color: #2271b1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.flavor-preset-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 12px;
}

.flavor-preset-card-header .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: #2271b1;
}

.flavor-preset-card-header h3 {
    margin: 0;
}

.flavor-preset-card p {
    color: #646970;
    margin: 0 0 15px 0;
    font-size: 13px;
}

.flavor-preset-card-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 15px;
}

.flavor-preset-card-meta .tag {
    background: #f0f6fc;
    color: #2271b1;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
}

.flavor-preset-card .button {
    width: 100%;
    justify-content: center;
}

/* Notices */
.flavor-notices {
    position: fixed;
    top: 50px;
    right: 20px;
    z-index: 100000;
}

.flavor-notice {
    background: #fff;
    border-left: 4px solid #00a32a;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    padding: 12px 16px;
    margin-bottom: 10px;
    border-radius: 4px;
    animation: slideIn 0.3s ease;
}

.flavor-notice.error {
    border-left-color: #d63638;
}

.flavor-notice.warning {
    border-left-color: #dba617;
}

@keyframes slideIn {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

/* Responsive */
@media (max-width: 782px) {
    .flavor-import-methods {
        grid-template-columns: 1fr;
    }

    .flavor-import-separator {
        padding: 10px 0;
    }

    .flavor-checkbox-grid,
    .flavor-radio-cards,
    .flavor-presets-grid {
        grid-template-columns: 1fr;
    }
}
</style>
