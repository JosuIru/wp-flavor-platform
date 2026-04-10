<?php
/**
 * Sistema de Exportar / Importar configuraciones de Flavor Platform
 *
 * Proporciona exportación/importación completa de configuración,
 * diseño, módulos, páginas, roles y permisos.
 *
 * @package FlavorPlatform
 * @since 2.0.0
 * @version 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Export_Import {

    /**
     * Instancia singleton
     *
     * @var Flavor_Export_Import|null
     */
    private static $instance = null;

    /**
     * Slug de la página
     */
    const PAGE_SLUG = 'flavor-platform-export-import';

    /**
     * Versión del formato de exportación
     */
    const EXPORT_FORMAT_VERSION = '3.1.0';

    /**
     * Prefijo para opciones de módulos
     */
    const MODULE_OPTIONS_PREFIX = 'flavor_chat_ia_module_';

    /**
     * Claves que contienen datos sensibles (no se exportan)
     *
     * @var array
     */
    private $claves_secretas = array(
        'api_key',
        'claude_api_key',
        'openai_api_key',
        'deepseek_api_key',
        'mistral_api_key',
        'password',
        'secret',
        'token',
        'private_key',
        'client_secret',
    );

    /**
     * Presets predefinidos por perfil de aplicación
     *
     * @var array
     */
    private $presets_disponibles = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Export_Import
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->definir_presets();
        add_action('admin_enqueue_scripts', array($this, 'cargar_assets_admin'));

        // AJAX handlers - Exportar/Importar
        add_action('wp_ajax_flavor_export_config', array($this, 'ajax_exportar_configuracion'));
        add_action('wp_ajax_flavor_import_config', array($this, 'ajax_importar_configuracion'));
        add_action('wp_ajax_flavor_preview_import', array($this, 'ajax_previsualizar_importacion'));
        add_action('wp_ajax_flavor_download_export', array($this, 'ajax_descargar_exportacion'));
        add_action('wp_ajax_flavor_apply_preset', array($this, 'ajax_aplicar_preset'));
        add_action('wp_ajax_flavor_get_presets', array($this, 'ajax_obtener_presets'));

        // AJAX handlers - Migración
        add_action('wp_ajax_flavor_export_full_site', array($this, 'ajax_exportar_sitio_completo'));
        add_action('wp_ajax_flavor_import_full_site', array($this, 'ajax_importar_sitio_completo'));
        add_action('wp_ajax_flavor_preview_search_replace', array($this, 'ajax_preview_search_replace'));
        add_action('wp_ajax_flavor_apply_search_replace', array($this, 'ajax_apply_search_replace'));

        // AJAX handlers - Backups
        add_action('wp_ajax_flavor_create_backup', array($this, 'ajax_crear_backup'));
        add_action('wp_ajax_flavor_restore_backup', array($this, 'ajax_restaurar_backup'));
        add_action('wp_ajax_flavor_delete_backup', array($this, 'ajax_eliminar_backup'));
        add_action('wp_ajax_flavor_download_backup', array($this, 'ajax_descargar_backup'));
        add_action('wp_ajax_flavor_save_backup_schedule', array($this, 'ajax_guardar_config_backup'));

        // Cron para backups programados
        add_action('flavor_scheduled_backup', array($this, 'ejecutar_backup_programado'));

        // Registrar comandos WP-CLI
        if (defined('WP_CLI') && WP_CLI) {
            $this->registrar_comandos_cli();
        }
    }

    /**
     * Define los presets predefinidos por perfil
     */
    private function definir_presets() {
        $this->presets_disponibles = array(
            'grupo_consumo' => array(
                'nombre' => __('Grupo de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Configuración optimizada para grupos de consumo agroecológico', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-cart',
                'config' => array(
                    'profile' => 'grupo_consumo',
                    'active_modules' => array('grupos-consumo', 'socios', 'eventos', 'newsletter'),
                    'modules_visibility' => array(
                        'grupos-consumo' => true,
                        'socios' => true,
                        'eventos' => true,
                        'newsletter' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'grupos-consumo',
                    'colors' => array(
                        'primary' => '#4CAF50',
                        'secondary' => '#8BC34A',
                        'accent' => '#FF9800',
                    ),
                ),
            ),
            'asociacion' => array(
                'nombre' => __('Asociación / ONG', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Configuración para asociaciones y organizaciones sin ánimo de lucro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-groups',
                'config' => array(
                    'profile' => 'asociacion',
                    'active_modules' => array('socios', 'eventos', 'incidencias', 'newsletter', 'reservas'),
                    'modules_visibility' => array(
                        'socios' => true,
                        'eventos' => true,
                        'incidencias' => true,
                        'newsletter' => true,
                        'reservas' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'asociacion',
                    'colors' => array(
                        'primary' => '#2196F3',
                        'secondary' => '#03A9F4',
                        'accent' => '#FFC107',
                    ),
                ),
            ),
            'banco_tiempo' => array(
                'nombre' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Configuración para bancos de tiempo y economía colaborativa', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-clock',
                'config' => array(
                    'profile' => 'banco_tiempo',
                    'active_modules' => array('banco-tiempo', 'socios', 'eventos'),
                    'modules_visibility' => array(
                        'banco-tiempo' => true,
                        'socios' => true,
                        'eventos' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'banco-tiempo',
                    'colors' => array(
                        'primary' => '#9C27B0',
                        'secondary' => '#E91E63',
                        'accent' => '#00BCD4',
                    ),
                ),
            ),
            'comunidad' => array(
                'nombre' => __('Comunidad / Vecindario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Configuración para comunidades de vecinos y barrios', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-admin-home',
                'config' => array(
                    'profile' => 'comunidad',
                    'active_modules' => array('socios', 'eventos', 'incidencias', 'reservas', 'tablero'),
                    'modules_visibility' => array(
                        'socios' => true,
                        'eventos' => true,
                        'incidencias' => true,
                        'reservas' => true,
                        'tablero' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'comunidad',
                    'colors' => array(
                        'primary' => '#607D8B',
                        'secondary' => '#795548',
                        'accent' => '#FF5722',
                    ),
                ),
            ),
            'ecommerce' => array(
                'nombre' => __('Tienda Online', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Configuración para tiendas con WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-store',
                'config' => array(
                    'profile' => 'ecommerce',
                    'active_modules' => array('woocommerce', 'newsletter', 'chat'),
                    'modules_visibility' => array(
                        'woocommerce' => true,
                        'newsletter' => true,
                        'chat' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'ecommerce',
                    'colors' => array(
                        'primary' => '#673AB7',
                        'secondary' => '#3F51B5',
                        'accent' => '#E91E63',
                    ),
                ),
            ),
            'minimalista' => array(
                'nombre' => __('Configuración Mínima', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'descripcion' => __('Solo funcionalidades esenciales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icono' => 'dashicons-editor-removeformatting',
                'config' => array(
                    'profile' => 'minimalista',
                    'active_modules' => array('chat'),
                    'modules_visibility' => array(
                        'chat' => true,
                    ),
                ),
                'design' => array(
                    'theme' => 'minimal',
                    'colors' => array(
                        'primary' => '#212121',
                        'secondary' => '#424242',
                        'accent' => '#757575',
                    ),
                ),
            ),
        );

        // Permitir añadir presets personalizados
        $this->presets_disponibles = apply_filters('flavor_export_import_presets', $this->presets_disponibles);
    }

    /**
     * Registra comandos WP-CLI
     */
    private function registrar_comandos_cli() {
        require_once FLAVOR_PLATFORM_PATH . 'includes/cli/class-export-import-command.php';
    }

    /**
     * Registra la página de menú (llamado por el gestor de menú)
     */
    public function registrar_pagina_menu() {
        add_submenu_page(
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            __('Exportar / Importar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Exportar / Importar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::PAGE_SLUG,
            array($this, 'renderizar_pagina')
        );
    }

    /**
     * Carga los assets de admin
     *
     * @param string $sufijo_hook Hook de la página actual
     */
    public function cargar_assets_admin($sufijo_hook) {
        $sufijo_hook = (string) $sufijo_hook;
        if (strpos($sufijo_hook, self::PAGE_SLUG) === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS
        wp_enqueue_style(
            'flavor-export-import-css',
            FLAVOR_PLATFORM_URL . "admin/css/export-import{$sufijo_asset}.css",
            array(),
            FLAVOR_PLATFORM_VERSION
        );

        // JS
        wp_enqueue_script(
            'flavor-export-import-js',
            FLAVOR_PLATFORM_URL . "admin/js/export-import{$sufijo_asset}.js",
            array('jquery', 'wp-util'),
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-export-import-js', 'flavorExportImport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_export_import_nonce'),
            'presets' => $this->presets_disponibles,
            'strings' => array(
                // Export/Import
                'exportando' => __('Exportando configuración...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'exportCompletada' => __('Exportación completada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'importando' => __('Importando configuración...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'importCompletada' => __('Importación completada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorExport' => __('Error al exportar los datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorImport' => __('Error al importar los datos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorArchivo' => __('Por favor, selecciona un archivo JSON válido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarImport' => __('¿Seguro que deseas aplicar esta importación? Los datos existentes serán modificados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarPreset' => __('¿Aplicar este preset? Se modificará la configuración actual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'previsualizando' => __('Analizando archivo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'sinSeleccion' => __('Selecciona al menos una opción para exportar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'arrastrarArchivo' => __('Arrastra un archivo JSON aquí', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'oSeleccionar' => __('o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'aplicandoPreset' => __('Aplicando preset...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'presetAplicado' => __('Preset aplicado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'paso1' => __('Paso 1: Seleccionar archivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'paso2' => __('Paso 2: Previsualización', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'paso3' => __('Paso 3: Opciones de importación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modoSobrescribir' => __('Sobrescribir todo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modoMerge' => __('Combinar (merge)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'modoSoloFaltantes' => __('Solo lo que falta', FLAVOR_PLATFORM_TEXT_DOMAIN),
                // Migración
                'generandoPaquete' => __('Generando paquete de migración...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'paqueteListo' => __('¡Paquete listo!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'subiendoArchivo' => __('Subiendo archivo...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'migracionImportada' => __('Migración importada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'errorMigracion' => __('Error en la migración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'analizandoDB' => __('Analizando base de datos...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'aplicandoCambios' => __('Aplicando cambios...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'coincidenciasEncontradas' => __('Se encontraron %d coincidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'reemplazosAplicados' => __('Se realizaron %d reemplazos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarReemplazo' => __('¿Estás seguro? Esta acción modificará la base de datos. Se creará un backup automático antes de continuar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                // Backups
                'creandoBackup' => __('Creando backup...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'backupCreado' => __('Backup creado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'restaurandoBackup' => __('Restaurando backup...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'backupRestaurado' => __('Backup restaurado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'eliminandoBackup' => __('Eliminando backup...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'backupEliminado' => __('Backup eliminado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarRestaurar' => __('¿Estás seguro de restaurar este backup? Los datos actuales serán reemplazados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmarEliminar' => __('¿Estás seguro de eliminar este backup? Esta acción no se puede deshacer.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'configGuardada' => __('Configuración de backups guardada', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ),
        ));
    }

    /**
     * Renderiza la página de administración
     */
    public function renderizar_pagina() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $tabs = array(
            'export'    => __('Exportar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'import'    => __('Importar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'migration' => __('Migración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'backup'    => __('Backups', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'presets'   => __('Presets', FLAVOR_PLATFORM_TEXT_DOMAIN),
        );

        $current_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'export';
        if (!isset($tabs[$current_tab])) {
            $current_tab = 'export';
        }
        ?>
        <div class="wrap flavor-export-import-wrap">
            <h1><?php esc_html_e('Exportar / Importar y Migración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <div id="flavor-export-import-notices"></div>

            <nav class="nav-tab-wrapper flavor-export-import-tabs">
                <?php foreach ($tabs as $tab_slug => $tab_label) : ?>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_slug)); ?>"
                       class="nav-tab <?php echo $current_tab === $tab_slug ? 'nav-tab-active' : ''; ?>"
                       data-tab="<?php echo esc_attr($tab_slug); ?>">
                        <?php echo esc_html($tab_label); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="flavor-tab-panels">
                <!-- TAB: EXPORTAR -->
                <div id="tab-export" class="flavor-tab-content <?php echo $current_tab === 'export' ? 'active' : ''; ?>">
                    <?php $this->renderizar_tab_exportar(); ?>
                </div>

                <!-- TAB: IMPORTAR -->
                <div id="tab-import" class="flavor-tab-content <?php echo $current_tab === 'import' ? 'active' : ''; ?>">
                    <?php $this->renderizar_tab_importar(); ?>
                </div>

                <!-- TAB: MIGRACIÓN -->
                <div id="tab-migration" class="flavor-tab-content <?php echo $current_tab === 'migration' ? 'active' : ''; ?>">
                    <?php $this->renderizar_tab_migracion(); ?>
                </div>

                <!-- TAB: BACKUPS -->
                <div id="tab-backup" class="flavor-tab-content <?php echo $current_tab === 'backup' ? 'active' : ''; ?>">
                    <?php $this->renderizar_tab_backups(); ?>
                </div>

                <!-- TAB: PRESETS -->
                <div id="tab-presets" class="flavor-tab-content <?php echo $current_tab === 'presets' ? 'active' : ''; ?>">
                    <?php $this->renderizar_tab_presets(); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de exportar
     */
    private function renderizar_tab_exportar() {
        ?>
        <div class="flavor-export-section">
            <h2><?php esc_html_e('Exportar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Exporta la configuración del plugin para transferirla a otro sitio o como backup.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <form id="flavor-export-form" class="flavor-export-form">
                <div class="flavor-export-options">
                    <h3><?php esc_html_e('¿Qué deseas exportar?', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                    <div class="flavor-checkbox-grid">
                        <label>
                            <input type="checkbox" name="export_sections[]" value="config" checked>
                            <span class="dashicons dashicons-admin-settings"></span>
                            <?php esc_html_e('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="export_sections[]" value="modules" checked>
                            <span class="dashicons dashicons-grid-view"></span>
                            <?php esc_html_e('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="export_sections[]" value="design" checked>
                            <span class="dashicons dashicons-art"></span>
                            <?php esc_html_e('Diseño y Estilos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="export_sections[]" value="pages">
                            <span class="dashicons dashicons-admin-page"></span>
                            <?php esc_html_e('Páginas del Builder', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="export_sections[]" value="landings">
                            <span class="dashicons dashicons-welcome-widgets-menus"></span>
                            <?php esc_html_e('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="export_sections[]" value="roles">
                            <span class="dashicons dashicons-groups"></span>
                            <?php esc_html_e('Roles y Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </div>

                    <div class="flavor-export-actions">
                        <button type="button" id="flavor-select-all-export" class="button"><?php esc_html_e('Seleccionar Todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <button type="button" id="flavor-deselect-all-export" class="button"><?php esc_html_e('Deseleccionar Todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </div>
                </div>

                <div class="flavor-export-submit">
                    <button type="submit" id="flavor-export-btn" class="button button-primary button-hero">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Generar y Descargar JSON', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </form>

            <div id="flavor-export-result" class="flavor-export-result hidden">
                <h3><?php esc_html_e('Exportación Generada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <textarea id="flavor-export-json" readonly rows="10"></textarea>
                <div class="flavor-export-result-actions">
                    <button type="button" id="flavor-copy-export" class="button">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e('Copiar al Portapapeles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" id="flavor-download-export" class="button button-primary">
                        <span class="dashicons dashicons-download"></span>
                        <?php esc_html_e('Descargar Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de importar
     */
    private function renderizar_tab_importar() {
        ?>
        <div class="flavor-import-section">
            <h2><?php esc_html_e('Importar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Importa una configuración previamente exportada desde otro sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <!-- Paso 1: Seleccionar archivo -->
            <div id="flavor-import-step-1" class="flavor-import-step active">
                <h3><?php esc_html_e('Paso 1: Seleccionar Archivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div id="flavor-import-dropzone" class="flavor-dropzone">
                    <div class="flavor-dropzone-content">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php esc_html_e('Arrastra un archivo JSON aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <p class="small"><?php esc_html_e('o haz clic para seleccionar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                        <input type="file" id="flavor-import-file" accept=".json">
                    </div>
                    <div class="flavor-dropzone-file hidden">
                        <span class="dashicons dashicons-media-default"></span>
                        <span class="flavor-filename"></span>
                        <button type="button" class="flavor-remove-file">&times;</button>
                    </div>
                </div>

                <div class="flavor-import-or">
                    <span><?php esc_html_e('— o pegar JSON directamente —', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </div>

                <textarea id="flavor-import-json-paste" placeholder="<?php esc_attr_e('Pega aquí el contenido JSON...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" rows="6"></textarea>

                <button type="button" id="flavor-preview-import-btn" class="button button-primary" disabled>
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Analizar y Previsualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- Paso 2: Previsualización -->
            <div id="flavor-import-step-2" class="flavor-import-step">
                <h3><?php esc_html_e('Paso 2: Previsualización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <div id="flavor-import-metadata" class="flavor-import-metadata"></div>
                <div id="flavor-import-warnings" class="flavor-import-warnings hidden"></div>
                <div id="flavor-import-sections-preview" class="flavor-import-sections-preview"></div>

                <div class="flavor-import-nav">
                    <button type="button" id="flavor-back-step-1" class="button"><?php esc_html_e('← Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" id="flavor-continue-step-3" class="button button-primary"><?php esc_html_e('Continuar →', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
            </div>

            <!-- Paso 3: Opciones e importar -->
            <div id="flavor-import-step-3" class="flavor-import-step">
                <h3><?php esc_html_e('Paso 3: Opciones de Importación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                <form id="flavor-import-form">
                    <div class="flavor-import-mode">
                        <label>
                            <input type="radio" name="import_mode" value="overwrite" checked>
                            <strong><?php esc_html_e('Sobrescribir todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <span class="description"><?php esc_html_e('Reemplaza la configuración actual con la importada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="import_mode" value="merge">
                            <strong><?php esc_html_e('Combinar (merge)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <span class="description"><?php esc_html_e('Fusiona con la configuración existente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="import_mode" value="missing_only">
                            <strong><?php esc_html_e('Solo lo que falta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                            <span class="description"><?php esc_html_e('Importa solo los elementos que no existen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>

                    <h4><?php esc_html_e('Secciones a importar:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <div id="flavor-import-sections-checkboxes" class="flavor-checkbox-grid"></div>

                    <div class="flavor-import-nav">
                        <button type="button" id="flavor-back-step-2" class="button"><?php esc_html_e('← Volver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <button type="submit" id="flavor-apply-import-btn" class="button button-primary button-hero">
                            <span class="dashicons dashicons-upload"></span>
                            <?php esc_html_e('Aplicar Importación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                </form>

                <div id="flavor-import-progress" class="flavor-import-progress hidden">
                    <span class="spinner is-active"></span>
                    <p><?php esc_html_e('Importando configuración...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>

                <div id="flavor-import-result" class="flavor-import-result hidden"></div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de migración completa
     */
    private function renderizar_tab_migracion() {
        $upload_dir = wp_upload_dir();
        $site_url = get_site_url();
        ?>
        <div class="flavor-migration-section">
            <h2><?php esc_html_e('Migración Completa del Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Herramientas para migrar tu sitio completo entre servidores, incluyendo base de datos, archivos y configuraciones.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-migration-info-card">
                <h3><span class="dashicons dashicons-info"></span> <?php esc_html_e('Información del Sitio Actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <table class="widefat striped">
                    <tr>
                        <td><strong><?php esc_html_e('URL del Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><code><?php echo esc_html($site_url); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Directorio de Uploads', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><code><?php echo esc_html($upload_dir['basedir']); ?></code></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Versión de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Versión de PHP', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                    </tr>
                </table>
            </div>

            <div class="flavor-migration-cards">
                <!-- Exportar Sitio Completo -->
                <div class="flavor-migration-card">
                    <div class="flavor-migration-card-header">
                        <span class="dashicons dashicons-download"></span>
                        <h3><?php esc_html_e('Exportar Sitio Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <p><?php esc_html_e('Genera un paquete completo con base de datos, archivos de medios y configuraciones para migrar a otro servidor.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                    <form id="flavor-full-export-form">
                        <div class="flavor-checkbox-list">
                            <label><input type="checkbox" name="export_full[]" value="database" checked> <?php esc_html_e('Base de datos completa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <label><input type="checkbox" name="export_full[]" value="uploads" checked> <?php esc_html_e('Archivos de medios (uploads)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <label><input type="checkbox" name="export_full[]" value="plugins" checked> <?php esc_html_e('Configuración de plugins', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <label><input type="checkbox" name="export_full[]" value="themes"> <?php esc_html_e('Tema activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <label><input type="checkbox" name="export_full[]" value="flavor_data" checked> <?php esc_html_e('Datos de Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        </div>
                        <button type="submit" class="button button-primary">
                            <span class="dashicons dashicons-migrate"></span>
                            <?php esc_html_e('Generar Paquete de Migración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                </div>

                <!-- Importar Sitio -->
                <div class="flavor-migration-card">
                    <div class="flavor-migration-card-header">
                        <span class="dashicons dashicons-upload"></span>
                        <h3><?php esc_html_e('Importar Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <p><?php esc_html_e('Restaura un sitio desde un paquete de migración. Útil para migrar desde Local by Flywheel u otro servidor.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                    <div class="flavor-dropzone" id="flavor-migration-dropzone">
                        <div class="flavor-dropzone-content">
                            <span class="dashicons dashicons-upload"></span>
                            <p><?php esc_html_e('Arrastra el paquete de migración (.zip) aquí', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                            <input type="file" id="flavor-migration-file" accept=".zip">
                        </div>
                    </div>
                </div>

                <!-- Búsqueda y Reemplazo de URLs -->
                <div class="flavor-migration-card">
                    <div class="flavor-migration-card-header">
                        <span class="dashicons dashicons-admin-links"></span>
                        <h3><?php esc_html_e('Búsqueda y Reemplazo de URLs', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>
                    <p><?php esc_html_e('Actualiza todas las URLs en la base de datos después de migrar. Soporta datos serializados.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                    <form id="flavor-search-replace-form">
                        <table class="form-table">
                            <tr>
                                <th><label for="old_url"><?php esc_html_e('URL Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <input type="url" id="old_url" name="old_url" class="regular-text" placeholder="https://old-site.local">
                                    <p class="description"><?php esc_html_e('La URL del sitio original (ej: Local by Flywheel)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th><label for="new_url"><?php esc_html_e('URL Nueva', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label></th>
                                <td>
                                    <input type="url" id="new_url" name="new_url" class="regular-text" value="<?php echo esc_attr($site_url); ?>">
                                    <p class="description"><?php esc_html_e('La URL del nuevo servidor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                                </td>
                            </tr>
                        </table>
                        <p class="flavor-warning">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('¡Cuidado! Esta operación modifica la base de datos. Haz un backup antes de continuar.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <button type="button" id="flavor-preview-replace" class="button"><?php esc_html_e('Previsualizar Cambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                        <button type="submit" class="button button-primary" disabled id="flavor-apply-replace"><?php esc_html_e('Aplicar Reemplazo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de backups
     */
    private function renderizar_tab_backups() {
        $backups = $this->obtener_backups_disponibles();
        ?>
        <div class="flavor-backup-section">
            <h2><?php esc_html_e('Sistema de Backups', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Gestiona copias de seguridad automáticas y manuales de tu sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div class="flavor-backup-cards">
                <!-- Crear Backup -->
                <div class="flavor-backup-card flavor-backup-create">
                    <div class="flavor-backup-card-header">
                        <span class="dashicons dashicons-backup"></span>
                        <h3><?php esc_html_e('Crear Backup', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>

                    <form id="flavor-create-backup-form">
                        <div class="flavor-backup-options">
                            <label>
                                <input type="checkbox" name="backup_type[]" value="database" checked>
                                <span class="dashicons dashicons-database"></span>
                                <?php esc_html_e('Base de Datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="backup_type[]" value="uploads" checked>
                                <span class="dashicons dashicons-images-alt2"></span>
                                <?php esc_html_e('Archivos (Uploads)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="backup_type[]" value="flavor_config" checked>
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php esc_html_e('Configuración Flavor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="backup_type[]" value="plugins">
                                <span class="dashicons dashicons-plugins-checked"></span>
                                <?php esc_html_e('Plugins', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                            <label>
                                <input type="checkbox" name="backup_type[]" value="themes">
                                <span class="dashicons dashicons-admin-appearance"></span>
                                <?php esc_html_e('Temas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </label>
                        </div>

                        <div class="flavor-backup-name">
                            <label for="backup_name"><?php esc_html_e('Nombre del backup (opcional):', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                            <input type="text" id="backup_name" name="backup_name" placeholder="<?php echo esc_attr(date('Y-m-d_H-i')); ?>">
                        </div>

                        <button type="submit" class="button button-primary button-hero">
                            <span class="dashicons dashicons-backup"></span>
                            <?php esc_html_e('Crear Backup Ahora', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </form>
                </div>

                <!-- Backups Programados -->
                <div class="flavor-backup-card">
                    <div class="flavor-backup-card-header">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3><?php esc_html_e('Backups Programados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                    </div>

                    <form id="flavor-schedule-backup-form">
                        <table class="form-table">
                            <tr>
                                <th><?php esc_html_e('Frecuencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <td>
                                    <select name="backup_frequency">
                                        <option value="disabled"><?php esc_html_e('Desactivado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="daily"><?php esc_html_e('Diario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="weekly"><?php esc_html_e('Semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                        <option value="monthly"><?php esc_html_e('Mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php esc_html_e('Retención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <td>
                                    <input type="number" name="backup_retention" value="5" min="1" max="30">
                                    <span class="description"><?php esc_html_e('Número de backups a conservar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                                </td>
                            </tr>
                        </table>
                        <button type="submit" class="button"><?php esc_html_e('Guardar Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    </form>
                </div>
            </div>

            <!-- Lista de Backups -->
            <div class="flavor-backups-list">
                <h3><?php esc_html_e('Backups Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <?php if (empty($backups)) : ?>
                    <p class="flavor-no-backups"><?php esc_html_e('No hay backups disponibles. Crea tu primer backup arriba.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <?php else : ?>
                    <table id="flavor-backups-table" class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                                <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($backups as $backup) : ?>
                                <tr>
                                    <td><?php echo esc_html($backup['name']); ?></td>
                                    <td><?php echo esc_html($backup['date']); ?></td>
                                    <td class="backup-size"><?php echo esc_html($backup['size']); ?></td>
                                    <td><?php echo esc_html($backup['contents']); ?></td>
                                    <td class="backup-actions">
                                        <button class="button button-small flavor-backup-restore" data-backup-id="<?php echo esc_attr($backup['id']); ?>">
                                            <?php esc_html_e('Restaurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                        <button class="button button-small flavor-backup-download" data-backup-id="<?php echo esc_attr($backup['id']); ?>">
                                            <?php esc_html_e('Descargar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                        <button class="button button-small button-link-delete flavor-backup-delete" data-backup-id="<?php echo esc_attr($backup['id']); ?>">
                                            <?php esc_html_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de presets
     */
    private function renderizar_tab_presets() {
        ?>
        <div class="flavor-presets-section">
            <h2><?php esc_html_e('Configuraciones Predefinidas (Presets)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Aplica configuraciones optimizadas según el tipo de proyecto.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <div id="flavor-presets-grid" class="flavor-presets-grid">
                <?php foreach ($this->presets_disponibles as $preset_id => $preset) : ?>
                    <div class="flavor-preset-card" data-preset-id="<?php echo esc_attr($preset_id); ?>">
                        <div class="flavor-preset-card-header">
                            <span class="dashicons <?php echo esc_attr($preset['icono'] ?? 'dashicons-admin-generic'); ?>"></span>
                            <h3><?php echo esc_html($preset['nombre']); ?></h3>
                        </div>
                        <p><?php echo esc_html($preset['descripcion']); ?></p>
                        <?php if (!empty($preset['config']['active_modules'])) : ?>
                            <div class="flavor-preset-card-meta">
                                <?php foreach ($preset['config']['active_modules'] as $module) : ?>
                                    <span class="tag"><?php echo esc_html($module); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="button button-primary">
                            <span class="dashicons dashicons-yes"></span>
                            <?php esc_html_e('Aplicar Preset', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene los backups disponibles
     *
     * @return array
     */
    private function obtener_backups_disponibles() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/flavor-backups/';
        $backups = array();

        if (!is_dir($backup_dir)) {
            return $backups;
        }

        $files = glob($backup_dir . '*.zip');
        if (empty($files)) {
            return $backups;
        }

        foreach ($files as $file) {
            $filename = basename($file);
            $backup_id = pathinfo($filename, PATHINFO_FILENAME);

            // Intentar leer manifest para más información
            $contenido = __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $zip = new ZipArchive();
            if ($zip->open($file) === true) {
                $manifest_content = $zip->getFromName('manifest.json');
                if ($manifest_content) {
                    $manifest = json_decode($manifest_content, true);
                    $partes = array();
                    if (!empty($manifest['contenido']['database'])) {
                        $partes[] = __('BD', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                    if (!empty($manifest['contenido']['uploads'])) {
                        $partes[] = __('Archivos', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                    if (!empty($manifest['contenido']['config'])) {
                        $partes[] = __('Config', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                    if (!empty($partes)) {
                        $contenido = implode(' + ', $partes);
                    }
                }
                $zip->close();
            }

            $backups[] = array(
                'id'           => $backup_id,
                'name'         => $backup_id,
                'file'         => $filename,
                'date'         => date('Y-m-d H:i:s', filemtime($file)),
                'size'         => size_format(filesize($file)),
                'contents'     => $contenido,
                'download_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $file),
            );
        }

        // Ordenar por fecha descendente
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    // =========================================================================
    // MÉTODOS PÚBLICOS DE EXPORTACIÓN
    // =========================================================================

    /**
     * Exporta la configuración completa
     *
     * @return array Datos exportados
     */
    public function export_full_config() {
        $datos_exportados = $this->crear_estructura_base_exportacion();

        $datos_exportados['config'] = $this->obtener_configuracion_completa();
        $datos_exportados['design'] = $this->obtener_ajustes_diseno_completos();
        $datos_exportados['pages'] = $this->obtener_paginas_builder();
        $datos_exportados['landings'] = $this->obtener_landings();
        $datos_exportados['roles'] = $this->obtener_roles_personalizados();
        $datos_exportados['permissions'] = $this->obtener_configuracion_permisos();

        return apply_filters('flavor_export_full_config', $datos_exportados);
    }

    /**
     * Exporta solo la configuración de diseño
     *
     * @return array Datos de diseño exportados
     */
    public function export_design_config() {
        $datos_exportados = $this->crear_estructura_base_exportacion();
        $datos_exportados['design'] = $this->obtener_ajustes_diseno_completos();

        return apply_filters('flavor_export_design_config', $datos_exportados);
    }

    /**
     * Exporta la configuración de módulos
     *
     * @return array Datos de módulos exportados
     */
    public function export_modules_config() {
        $datos_exportados = $this->crear_estructura_base_exportacion();

        $datos_exportados['config'] = array(
            'profile' => $this->obtener_perfil_actual(),
            'active_modules' => $this->obtener_modulos_activos(),
            'modules_visibility' => $this->obtener_visibilidad_modulos(),
            'modules_settings' => $this->obtener_configuracion_modulos(),
        );

        return apply_filters('flavor_export_modules_config', $datos_exportados);
    }

    /**
     * Exporta páginas y landings
     *
     * @return array Datos de páginas exportados
     */
    public function export_pages() {
        $datos_exportados = $this->crear_estructura_base_exportacion();

        $datos_exportados['pages'] = $this->obtener_paginas_builder();
        $datos_exportados['landings'] = $this->obtener_landings();

        return apply_filters('flavor_export_pages', $datos_exportados);
    }

    // =========================================================================
    // MÉTODOS PÚBLICOS DE IMPORTACIÓN
    // =========================================================================

    /**
     * Importa configuración desde JSON
     *
     * @param array $datos_json Datos JSON decodificados
     * @param array $opciones Opciones de importación (mode: overwrite|merge|only_missing, sections: array)
     * @return array|WP_Error Resultado de la importación
     */
    public function import_config($datos_json, $opciones = array()) {
        $opciones_por_defecto = array(
            'mode' => 'merge', // overwrite, merge, only_missing
            'sections' => array('config', 'design', 'pages', 'landings', 'roles', 'permissions'),
        );
        $opciones = wp_parse_args($opciones, $opciones_por_defecto);

        // Validar estructura
        $resultado_validacion = $this->validate_import($datos_json);
        if (is_wp_error($resultado_validacion)) {
            return $resultado_validacion;
        }

        $resultados_importacion = array(
            'success' => true,
            'imported' => array(),
            'skipped' => array(),
            'errors' => array(),
        );

        // Importar cada sección seleccionada
        foreach ($opciones['sections'] as $seccion) {
            if (!isset($datos_json[$seccion])) {
                $resultados_importacion['skipped'][] = $seccion;
                continue;
            }

            $resultado_seccion = $this->importar_seccion($seccion, $datos_json[$seccion], $opciones['mode']);

            if (is_wp_error($resultado_seccion)) {
                $resultados_importacion['errors'][$seccion] = $resultado_seccion->get_error_message();
                $resultados_importacion['success'] = false;
            } else {
                $resultados_importacion['imported'][$seccion] = $resultado_seccion;
            }
        }

        // Registrar en el log de actividad
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance()->registrar(
                'export_import',
                'import',
                sprintf(
                    __('Importación realizada: %s secciones importadas, %s omitidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    count($resultados_importacion['imported']),
                    count($resultados_importacion['skipped'])
                ),
                array('resultados' => $resultados_importacion)
            );
        }

        return $resultados_importacion;
    }

    /**
     * Valida un JSON antes de importar
     *
     * @param array $datos_json Datos JSON decodificados
     * @return true|WP_Error True si es válido, WP_Error si no
     */
    public function validate_import($datos_json) {
        // Verificar estructura básica
        $campos_requeridos = array('version', 'exported_at');
        foreach ($campos_requeridos as $campo) {
            if (!isset($datos_json[$campo])) {
                return new WP_Error(
                    'estructura_invalida',
                    sprintf(__('Falta el campo obligatorio: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $campo)
                );
            }
        }

        // Verificar versión compatible
        if (version_compare($datos_json['version'], '2.0.0', '<')) {
            return new WP_Error(
                'version_incompatible',
                __('La versión del archivo de exportación es demasiado antigua. Se requiere versión 2.0.0 o superior.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        // Verificar que hay al menos una sección de datos
        $secciones_validas = array('config', 'design', 'pages', 'landings', 'roles', 'permissions', 'data');
        $tiene_datos = false;
        foreach ($secciones_validas as $seccion) {
            if (isset($datos_json[$seccion]) && !empty($datos_json[$seccion])) {
                $tiene_datos = true;
                break;
            }
        }

        if (!$tiene_datos) {
            return new WP_Error(
                'sin_datos',
                __('El archivo no contiene datos válidos para importar.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        // Validar JSON integrity
        if (isset($datos_json['checksum'])) {
            $contenido_sin_checksum = $datos_json;
            unset($contenido_sin_checksum['checksum']);
            $checksum_calculado = md5(wp_json_encode($contenido_sin_checksum));
            if ($checksum_calculado !== $datos_json['checksum']) {
                return new WP_Error(
                    'checksum_invalido',
                    __('El archivo parece estar corrupto (checksum no coincide).', FLAVOR_PLATFORM_TEXT_DOMAIN)
                );
            }
        }

        return true;
    }

    /**
     * Genera un preview de los cambios antes de importar
     *
     * @param array $datos_json Datos JSON decodificados
     * @return array|WP_Error Preview de cambios
     */
    public function preview_import($datos_json) {
        $resultado_validacion = $this->validate_import($datos_json);
        if (is_wp_error($resultado_validacion)) {
            return $resultado_validacion;
        }

        $preview = array(
            'metadata' => array(
                'version' => $datos_json['version'],
                'exported_at' => $datos_json['exported_at'],
                'site_url' => isset($datos_json['site_url']) ? $datos_json['site_url'] : __('No especificado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ),
            'sections' => array(),
            'changes' => array(),
            'warnings' => array(),
        );

        // Analizar cada sección disponible
        if (isset($datos_json['config'])) {
            $preview['sections']['config'] = $this->analizar_cambios_config($datos_json['config']);
        }

        if (isset($datos_json['design'])) {
            $preview['sections']['design'] = $this->analizar_cambios_design($datos_json['design']);
        }

        if (isset($datos_json['pages'])) {
            $preview['sections']['pages'] = $this->analizar_cambios_pages($datos_json['pages']);
        }

        if (isset($datos_json['landings'])) {
            $preview['sections']['landings'] = $this->analizar_cambios_landings($datos_json['landings']);
        }

        if (isset($datos_json['roles'])) {
            $preview['sections']['roles'] = $this->analizar_cambios_roles($datos_json['roles']);
        }

        if (isset($datos_json['permissions'])) {
            $preview['sections']['permissions'] = $this->analizar_cambios_permissions($datos_json['permissions']);
        }

        // Compatibilidad con formato anterior (data)
        if (isset($datos_json['data'])) {
            $preview['sections']['legacy_data'] = $this->generar_resumen_importacion_legacy($datos_json);
        }

        // Advertencias
        if (isset($datos_json['site_url']) && $datos_json['site_url'] !== get_site_url()) {
            $preview['warnings'][] = sprintf(
                __('Esta exportación proviene de otro sitio (%s). Algunas URLs podrían no funcionar correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                esc_html($datos_json['site_url'])
            );
        }

        return $preview;
    }

    // =========================================================================
    // HANDLERS AJAX
    // =========================================================================

    /**
     * AJAX: Exportar configuración
     */
    public function ajax_exportar_configuracion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $secciones = isset($_POST['sections']) ? array_map('sanitize_text_field', (array) $_POST['sections']) : array();

        if (empty($secciones)) {
            wp_send_json_error(array('message' => __('No se seleccionó ninguna sección para exportar.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $datos_exportados = $this->crear_estructura_base_exportacion();

        foreach ($secciones as $seccion) {
            switch ($seccion) {
                case 'config':
                    $datos_exportados['config'] = $this->obtener_configuracion_completa();
                    break;
                case 'design':
                    $datos_exportados['design'] = $this->obtener_ajustes_diseno_completos();
                    break;
                case 'pages':
                    $datos_exportados['pages'] = $this->obtener_paginas_builder();
                    break;
                case 'landings':
                    $datos_exportados['landings'] = $this->obtener_landings();
                    break;
                case 'roles':
                    $datos_exportados['roles'] = $this->obtener_roles_personalizados();
                    break;
                case 'permissions':
                    $datos_exportados['permissions'] = $this->obtener_configuracion_permisos();
                    break;
            }
        }

        // Añadir checksum para integridad
        $datos_exportados['checksum'] = md5(wp_json_encode($datos_exportados));

        // Registrar en log de actividad
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance()->registrar(
                'export_import',
                'export',
                sprintf(__('Exportación realizada: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), implode(', ', $secciones))
            );
        }

        wp_send_json_success(array(
            'data' => $datos_exportados,
            'filename' => 'flavor-platform-export-' . gmdate('Y-m-d-His') . '.json',
        ));
    }

    /**
     * AJAX: Importar configuración
     */
    public function ajax_importar_configuracion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        // Obtener datos del transient (guardados en preview)
        $clave_transitoria = 'flavor_import_' . get_current_user_id();
        $datos_json = get_transient($clave_transitoria);

        if (false === $datos_json) {
            wp_send_json_error(array('message' => __('Los datos de importación han expirado. Sube el archivo de nuevo.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $secciones = isset($_POST['sections']) ? array_map('sanitize_text_field', (array) $_POST['sections']) : array();
        $modo = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'merge';

        if (empty($secciones)) {
            wp_send_json_error(array('message' => __('No se seleccionó ninguna sección para importar.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $resultado = $this->import_config($datos_json, array(
            'mode' => $modo,
            'sections' => $secciones,
        ));

        // Limpiar transient
        delete_transient($clave_transitoria);

        if (is_wp_error($resultado)) {
            wp_send_json_error(array('message' => $resultado->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Importación completada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'results' => $resultado,
        ));
    }

    /**
     * AJAX: Previsualizar importación
     */
    public function ajax_previsualizar_importacion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $datos_json = null;

        // Intentar obtener de archivo subido
        if (!empty($_FILES['import_file'])) {
            $archivo_subido = $_FILES['import_file'];

            // Validar extensión
            $info_archivo = wp_check_filetype($archivo_subido['name'], array('json' => 'application/json'));
            if (empty($info_archivo['ext'])) {
                wp_send_json_error(array('message' => __('El archivo debe ser de tipo JSON.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
            }

            // Validar tamaño máximo (5MB)
            $max_size = 5 * MB_IN_BYTES;
            if ($archivo_subido['size'] > $max_size) {
                wp_send_json_error(array('message' => sprintf(
                    __('El archivo es demasiado grande. Máximo permitido: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    size_format($max_size)
                )));
            }

            // Validar MIME type real del archivo
            if (function_exists('finfo_open')) {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_real = finfo_file($finfo, $archivo_subido['tmp_name']);
                finfo_close($finfo);

                $mimes_permitidos = ['application/json', 'text/plain', 'text/json'];
                if (!in_array($mime_real, $mimes_permitidos)) {
                    wp_send_json_error(array('message' => __('El contenido del archivo no es JSON válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
                }
            }

            $contenido_json = file_get_contents($archivo_subido['tmp_name']);
            if (false === $contenido_json) {
                wp_send_json_error(array('message' => __('No se pudo leer el archivo.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
            }

            // Limpiar archivo temporal
            @unlink($archivo_subido['tmp_name']);

            $datos_json = json_decode($contenido_json, true);

            // Validar que sea JSON válido
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => sprintf(
                    __('Error de JSON: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    json_last_error_msg()
                )));
            }
        }
        // Intentar obtener de JSON pegado
        elseif (!empty($_POST['json_content'])) {
            $contenido_json = wp_unslash($_POST['json_content']);
            $datos_json = json_decode($contenido_json, true);
        }

        if (null === $datos_json) {
            wp_send_json_error(array('message' => __('No se pudo parsear el JSON. Verifica que el formato sea correcto.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $preview = $this->preview_import($datos_json);

        if (is_wp_error($preview)) {
            wp_send_json_error(array('message' => $preview->get_error_message()));
        }

        // Guardar en transient para la importación
        $clave_transitoria = 'flavor_import_' . get_current_user_id();
        set_transient($clave_transitoria, $datos_json, HOUR_IN_SECONDS);

        wp_send_json_success($preview);
    }

    /**
     * AJAX: Descargar exportación (genera archivo descargable)
     */
    public function ajax_descargar_exportacion() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $datos_exportados = $this->export_full_config();

        // Usar SHA-256 en lugar de MD5 para checksum (más seguro)
        $datos_sin_checksum = $datos_exportados;
        $datos_exportados['checksum'] = hash('sha256', wp_json_encode($datos_sin_checksum));
        $datos_exportados['export_timestamp'] = time();
        $datos_exportados['export_site'] = get_site_url();

        // Sanitizar nombre de archivo
        $nombre_archivo = sanitize_file_name('flavor-platform-export-' . gmdate('Y-m-d-His') . '.json');
        $contenido_json = wp_json_encode($datos_exportados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        // Headers de seguridad mejorados
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . strlen($contenido_json));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo $contenido_json;
        exit;
    }

    /**
     * AJAX: Aplicar preset
     */
    public function ajax_aplicar_preset() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $preset_id = isset($_POST['preset_id']) ? sanitize_key($_POST['preset_id']) : '';

        if (!isset($this->presets_disponibles[$preset_id])) {
            wp_send_json_error(array('message' => __('Preset no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $preset = $this->presets_disponibles[$preset_id];
        $resultados = array();

        // Aplicar configuración del preset
        if (isset($preset['config'])) {
            $ajustes_actuales = flavor_get_main_settings();

            if (isset($preset['config']['profile'])) {
                $ajustes_actuales['app_profile'] = $preset['config']['profile'];
            }

            if (isset($preset['config']['active_modules'])) {
                $ajustes_actuales['active_modules'] = $preset['config']['active_modules'];
            }

            flavor_update_main_settings($ajustes_actuales);
            $resultados['config'] = true;
        }

        // Aplicar diseño del preset
        if (isset($preset['design'])) {
            $ajustes_diseno = get_option('flavor_design_settings', array());
            $ajustes_diseno = array_merge($ajustes_diseno, $preset['design']);
            update_option('flavor_design_settings', $ajustes_diseno);
            $resultados['design'] = true;
        }

        // Aplicar visibilidad de módulos
        if (isset($preset['config']['modules_visibility'])) {
            update_option('flavor_modules_visibility', $preset['config']['modules_visibility']);
            $resultados['modules_visibility'] = true;
        }

        // Registrar en log
        if (class_exists('Flavor_Activity_Log')) {
            Flavor_Activity_Log::get_instance()->registrar(
                'export_import',
                'apply_preset',
                sprintf(__('Preset aplicado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $preset['nombre'])
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Preset "%s" aplicado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN), $preset['nombre']),
            'results' => $resultados,
        ));
    }

    /**
     * AJAX: Obtener lista de presets
     */
    public function ajax_obtener_presets() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        wp_send_json_success(array('presets' => $this->presets_disponibles));
    }

    // =========================================================================
    // MÉTODOS PRIVADOS DE EXPORTACIÓN
    // =========================================================================

    /**
     * Crea la estructura base de exportación
     *
     * @return array
     */
    private function crear_estructura_base_exportacion() {
        return array(
            'version' => self::EXPORT_FORMAT_VERSION,
            'plugin' => 'flavor-platform',
            'exported_at' => current_time('c'),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
        );
    }

    /**
     * Obtiene el perfil actual
     *
     * @return string
     */
    private function obtener_perfil_actual() {
        $ajustes = flavor_get_main_settings();
        return isset($ajustes['app_profile']) ? $ajustes['app_profile'] : 'personalizado';
    }

    /**
     * Obtiene la configuración completa
     *
     * @return array
     */
    private function obtener_configuracion_completa() {
        $ajustes = flavor_get_main_settings();
        $ajustes_limpios = $this->eliminar_claves_secretas($ajustes);

        return array(
            'profile' => $this->obtener_perfil_actual(),
            'active_modules' => $this->obtener_modulos_activos(),
            'modules_visibility' => $this->obtener_visibilidad_modulos(),
            'modules_settings' => $this->obtener_configuracion_modulos(),
            'general_settings' => $ajustes_limpios,
        );
    }

    /**
     * Obtiene los módulos activos
     *
     * @return array
     */
    private function obtener_modulos_activos() {
        $ajustes = flavor_get_main_settings();
        return isset($ajustes['active_modules']) ? $ajustes['active_modules'] : array();
    }

    /**
     * Obtiene la visibilidad de módulos
     *
     * @return array
     */
    private function obtener_visibilidad_modulos() {
        return get_option('flavor_modules_visibility', array());
    }

    /**
     * Obtiene la configuración de módulos
     *
     * @return array
     */
    private function obtener_configuracion_modulos() {
        global $wpdb;

        $prefijo_escapado = $wpdb->esc_like(self::MODULE_OPTIONS_PREFIX) . '%';
        $opciones_modulos = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefijo_escapado
            )
        );

        $configuracion = array();
        foreach ($opciones_modulos as $opcion) {
            $nombre_modulo = str_replace(self::MODULE_OPTIONS_PREFIX, '', $opcion->option_name);
            $valor = maybe_unserialize($opcion->option_value);

            if (is_array($valor)) {
                $valor = $this->eliminar_claves_secretas($valor);
            }

            $configuracion[$nombre_modulo] = $valor;
        }

        // Incluir configuración granular de módulos
        $config_granular = get_option('flavor_module_config', array());
        if (!empty($config_granular)) {
            $configuracion['_module_config'] = $this->eliminar_claves_secretas($config_granular);
        }

        return $configuracion;
    }

    /**
     * Obtiene los ajustes de diseño completos
     *
     * @return array
     */
    private function obtener_ajustes_diseno_completos() {
        return array(
            'theme' => get_option('flavor_theme_settings', array()),
            'colors' => get_option('flavor_design_colors', array()),
            'typography' => get_option('flavor_design_typography', array()),
            'spacing' => get_option('flavor_design_spacing', array()),
            'design_settings' => get_option('flavor_design_settings', array()),
            'custom_css' => get_option('flavor_custom_css', ''),
        );
    }

    /**
     * Obtiene las páginas del page builder
     *
     * @return array
     */
    private function obtener_paginas_builder() {
        $argumentos_consulta = array(
            'post_type' => array('page', 'flavor_landing'),
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft', 'private'),
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_flavor_page_builder',
                    'compare' => 'EXISTS',
                ),
                array(
                    'key' => '_web_builder_content',
                    'compare' => 'EXISTS',
                ),
            ),
        );

        $paginas_query = new WP_Query($argumentos_consulta);
        $datos_paginas = array();

        if ($paginas_query->have_posts()) {
            while ($paginas_query->have_posts()) {
                $paginas_query->the_post();
                $id_pagina = get_the_ID();

                // Obtener meta relevante
                $meta_completa = get_post_meta($id_pagina);
                $meta_limpia = array();

                foreach ($meta_completa as $clave => $valor) {
                    if ($this->es_meta_relevante($clave)) {
                        $meta_limpia[$clave] = maybe_unserialize($valor[0]);
                    }
                }

                $datos_paginas[] = array(
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name', $id_pagina),
                    'status' => get_post_status(),
                    'content' => get_the_content(),
                    'excerpt' => get_the_excerpt(),
                    'template' => get_page_template_slug($id_pagina),
                    'post_type' => get_post_type(),
                    'post_meta' => $meta_limpia,
                    'menu_order' => get_post_field('menu_order', $id_pagina),
                );
            }
            wp_reset_postdata();
        }

        return $datos_paginas;
    }

    /**
     * Verifica si una clave de meta es relevante para exportar
     *
     * @param string $clave Clave de meta
     * @return bool
     */
    private function es_meta_relevante($clave) {
        $patrones_relevantes = array(
            '_flavor',
            'flavor_',
            '_page_builder',
            'page_builder',
            '_web_builder',
            'web_builder',
            '_thumbnail_id',
            '_wp_page_template',
        );

        foreach ($patrones_relevantes as $patron) {
            if (strpos($clave, $patron) === 0 || strpos($clave, $patron) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene las landings del builder
     *
     * @return array
     */
    private function obtener_landings() {
        $argumentos = array(
            'post_type' => 'flavor_landing',
            'posts_per_page' => -1,
            'post_status' => array('publish', 'draft', 'private'),
        );

        $landings_query = new WP_Query($argumentos);
        $landings = array();

        if ($landings_query->have_posts()) {
            while ($landings_query->have_posts()) {
                $landings_query->the_post();
                $id_landing = get_the_ID();

                $estructura = get_post_meta($id_landing, '_web_builder_content', true);

                $landings[] = array(
                    'id' => $id_landing,
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name', $id_landing),
                    'status' => get_post_status(),
                    'structure' => $estructura,
                    'settings' => get_post_meta($id_landing, '_flavor_landing_settings', true),
                );
            }
            wp_reset_postdata();
        }

        return $landings;
    }

    /**
     * Obtiene los roles personalizados
     *
     * @return array
     */
    private function obtener_roles_personalizados() {
        $roles_personalizados = get_option('flavor_custom_roles', array());

        // Incluir configuración de roles por módulo
        if (class_exists('Flavor_Role_Manager')) {
            $role_manager = Flavor_Role_Manager::get_instance();
            $roles_por_modulo = $role_manager->obtener_roles_modulo();
            $roles_personalizados['module_roles'] = $roles_por_modulo;
        }

        return $roles_personalizados;
    }

    /**
     * Obtiene la configuración de permisos
     *
     * @return array
     */
    private function obtener_configuracion_permisos() {
        return array(
            'module_access' => get_option('flavor_module_access_config', array()),
            'role_permissions' => get_option('flavor_role_permissions', array()),
            'custom_capabilities' => get_option('flavor_custom_capabilities', array()),
        );
    }

    /**
     * Elimina claves que contienen datos sensibles
     *
     * @param array $datos Datos a limpiar
     * @return array Datos sin claves sensibles
     */
    private function eliminar_claves_secretas($datos) {
        if (!is_array($datos)) {
            return $datos;
        }

        $datos_limpios = array();

        foreach ($datos as $clave => $valor) {
            $es_secreto = false;

            foreach ($this->claves_secretas as $patron) {
                if (stripos($clave, $patron) !== false) {
                    $es_secreto = true;
                    break;
                }
            }

            if ($es_secreto) {
                $datos_limpios[$clave] = '***REDACTED***';
                continue;
            }

            if (is_array($valor)) {
                $datos_limpios[$clave] = $this->eliminar_claves_secretas($valor);
            } else {
                $datos_limpios[$clave] = $valor;
            }
        }

        return $datos_limpios;
    }

    // =========================================================================
    // MÉTODOS PRIVADOS DE IMPORTACIÓN
    // =========================================================================

    /**
     * Importa una sección específica
     *
     * @param string $seccion Nombre de la sección
     * @param array $datos Datos a importar
     * @param string $modo Modo de importación (overwrite, merge, only_missing)
     * @return array|WP_Error Resultado
     */
    private function importar_seccion($seccion, $datos, $modo) {
        switch ($seccion) {
            case 'config':
                return $this->importar_configuracion($datos, $modo);

            case 'design':
                return $this->importar_diseno($datos, $modo);

            case 'pages':
                return $this->importar_paginas($datos, $modo);

            case 'landings':
                return $this->importar_landings($datos, $modo);

            case 'roles':
                return $this->importar_roles($datos, $modo);

            case 'permissions':
                return $this->importar_permisos($datos, $modo);

            // Compatibilidad con formato antiguo
            case 'data':
            case 'legacy_data':
                return $this->importar_formato_legacy($datos, $modo);

            default:
                return new WP_Error('seccion_desconocida', sprintf(__('Sección desconocida: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $seccion));
        }
    }

    /**
     * Importa la configuración
     *
     * @param array $datos Datos de configuración
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_configuracion($datos, $modo) {
        $resultados = array('updated' => 0, 'skipped' => 0);

        // Importar ajustes generales
        if (isset($datos['general_settings'])) {
            $ajustes_actuales = flavor_get_main_settings();
            $ajustes_nuevos = $this->aplicar_modo_importacion($ajustes_actuales, $datos['general_settings'], $modo);
            $ajustes_nuevos = $this->fusionar_preservando_secretos($ajustes_actuales, $ajustes_nuevos);
            flavor_update_main_settings($this->sanitizar_datos_recursivo($ajustes_nuevos));
            $resultados['updated']++;
        }

        // Importar módulos activos
        if (isset($datos['active_modules'])) {
            $ajustes = flavor_get_main_settings();
            $ajustes['active_modules'] = array_map('sanitize_text_field', $datos['active_modules']);
            flavor_update_main_settings($ajustes);
            $resultados['updated']++;
        }

        // Importar visibilidad de módulos
        if (isset($datos['modules_visibility'])) {
            $visibilidad_actual = get_option('flavor_modules_visibility', array());
            $visibilidad_nueva = $this->aplicar_modo_importacion($visibilidad_actual, $datos['modules_visibility'], $modo);
            update_option('flavor_modules_visibility', $this->sanitizar_datos_recursivo($visibilidad_nueva));
            $resultados['updated']++;
        }

        // Importar configuración de módulos
        if (isset($datos['modules_settings'])) {
            foreach ($datos['modules_settings'] as $nombre_modulo => $config_modulo) {
                if ('_module_config' === $nombre_modulo) {
                    $config_actual = get_option('flavor_module_config', array());
                    $config_nueva = $this->aplicar_modo_importacion($config_actual, $config_modulo, $modo);
                    $config_nueva = $this->fusionar_preservando_secretos($config_actual, $config_nueva);
                    update_option('flavor_module_config', $this->sanitizar_datos_recursivo($config_nueva));
                } else {
                    $nombre_opcion = self::MODULE_OPTIONS_PREFIX . sanitize_key($nombre_modulo);
                    $valor_actual = get_option($nombre_opcion, array());

                    if (is_array($config_modulo) && is_array($valor_actual)) {
                        $config_nueva = $this->aplicar_modo_importacion($valor_actual, $config_modulo, $modo);
                        $config_nueva = $this->fusionar_preservando_secretos($valor_actual, $config_nueva);
                    } else {
                        $config_nueva = $config_modulo;
                    }

                    update_option($nombre_opcion, $this->sanitizar_datos_recursivo($config_nueva));
                }
                $resultados['updated']++;
            }
        }

        return $resultados;
    }

    /**
     * Importa ajustes de diseño
     *
     * @param array $datos Datos de diseño
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_diseno($datos, $modo) {
        $resultados = array('updated' => 0);

        $mapeo_opciones = array(
            'theme' => 'flavor_theme_settings',
            'colors' => 'flavor_design_colors',
            'typography' => 'flavor_design_typography',
            'spacing' => 'flavor_design_spacing',
            'design_settings' => 'flavor_design_settings',
            'custom_css' => 'flavor_custom_css',
        );

        foreach ($mapeo_opciones as $clave => $nombre_opcion) {
            if (isset($datos[$clave])) {
                $valor_actual = get_option($nombre_opcion, is_string($datos[$clave]) ? '' : array());
                $valor_nuevo = $this->aplicar_modo_importacion($valor_actual, $datos[$clave], $modo);
                update_option($nombre_opcion, $this->sanitizar_datos_recursivo($valor_nuevo));
                $resultados['updated']++;
            }
        }

        return $resultados;
    }

    /**
     * Importa páginas
     *
     * @param array $datos Datos de páginas
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_paginas($datos, $modo) {
        $resultados = array('created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array());

        foreach ($datos as $datos_pagina) {
            if (!isset($datos_pagina['title']) || !isset($datos_pagina['slug'])) {
                $resultados['errors'][] = __('Página sin título o slug, omitida.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                continue;
            }

            $titulo = sanitize_text_field($datos_pagina['title']);
            $slug = sanitize_title($datos_pagina['slug']);
            $tipo_post = isset($datos_pagina['post_type']) ? sanitize_key($datos_pagina['post_type']) : 'page';

            // Buscar si existe
            $pagina_existente = get_page_by_path($slug, OBJECT, $tipo_post);

            if ($pagina_existente) {
                if ('only_missing' === $modo) {
                    $resultados['skipped']++;
                    continue;
                }

                $resultado_actualizacion = wp_update_post(array(
                    'ID' => $pagina_existente->ID,
                    'post_title' => $titulo,
                    'post_content' => isset($datos_pagina['content']) ? wp_kses_post($datos_pagina['content']) : '',
                    'post_excerpt' => isset($datos_pagina['excerpt']) ? sanitize_textarea_field($datos_pagina['excerpt']) : '',
                    'post_status' => isset($datos_pagina['status']) ? sanitize_key($datos_pagina['status']) : 'draft',
                    'menu_order' => isset($datos_pagina['menu_order']) ? intval($datos_pagina['menu_order']) : 0,
                ), true);

                if (is_wp_error($resultado_actualizacion)) {
                    $resultados['errors'][] = sprintf(__('Error al actualizar %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo);
                    continue;
                }

                $id_pagina = $pagina_existente->ID;
                $resultados['updated']++;
            } else {
                $id_pagina = wp_insert_post(array(
                    'post_title' => $titulo,
                    'post_name' => $slug,
                    'post_content' => isset($datos_pagina['content']) ? wp_kses_post($datos_pagina['content']) : '',
                    'post_excerpt' => isset($datos_pagina['excerpt']) ? sanitize_textarea_field($datos_pagina['excerpt']) : '',
                    'post_status' => isset($datos_pagina['status']) ? sanitize_key($datos_pagina['status']) : 'draft',
                    'post_type' => $tipo_post,
                    'menu_order' => isset($datos_pagina['menu_order']) ? intval($datos_pagina['menu_order']) : 0,
                ), true);

                if (is_wp_error($id_pagina)) {
                    $resultados['errors'][] = sprintf(__('Error al crear %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo);
                    continue;
                }

                $resultados['created']++;
            }

            // Actualizar template
            if (isset($datos_pagina['template']) && !empty($datos_pagina['template'])) {
                update_post_meta($id_pagina, '_wp_page_template', sanitize_file_name($datos_pagina['template']));
            }

            // Actualizar meta
            if (isset($datos_pagina['post_meta']) && is_array($datos_pagina['post_meta'])) {
                foreach ($datos_pagina['post_meta'] as $clave_meta => $valor_meta) {
                    update_post_meta($id_pagina, sanitize_key($clave_meta), $this->sanitizar_datos_recursivo($valor_meta));
                }
            }
        }

        return $resultados;
    }

    /**
     * Importa landings
     *
     * @param array $datos Datos de landings
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_landings($datos, $modo) {
        $resultados = array('created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => array());

        foreach ($datos as $landing_data) {
            if (!isset($landing_data['title']) || !isset($landing_data['slug'])) {
                continue;
            }

            $titulo = sanitize_text_field($landing_data['title']);
            $slug = sanitize_title($landing_data['slug']);

            $landing_existente = get_page_by_path($slug, OBJECT, 'flavor_landing');

            if ($landing_existente) {
                if ('only_missing' === $modo) {
                    $resultados['skipped']++;
                    continue;
                }

                $resultado = wp_update_post(array(
                    'ID' => $landing_existente->ID,
                    'post_title' => $titulo,
                    'post_status' => isset($landing_data['status']) ? sanitize_key($landing_data['status']) : 'draft',
                ), true);

                if (is_wp_error($resultado)) {
                    $resultados['errors'][] = sprintf(__('Error al actualizar landing %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo);
                    continue;
                }

                $id_landing = $landing_existente->ID;
                $resultados['updated']++;
            } else {
                $id_landing = wp_insert_post(array(
                    'post_title' => $titulo,
                    'post_name' => $slug,
                    'post_status' => isset($landing_data['status']) ? sanitize_key($landing_data['status']) : 'draft',
                    'post_type' => 'flavor_landing',
                ), true);

                if (is_wp_error($id_landing)) {
                    $resultados['errors'][] = sprintf(__('Error al crear landing %s.', FLAVOR_PLATFORM_TEXT_DOMAIN), $titulo);
                    continue;
                }

                $resultados['created']++;
            }

            // Guardar estructura
            if (isset($landing_data['structure'])) {
                update_post_meta($id_landing, '_web_builder_content', $landing_data['structure']);
            }

            // Guardar settings
            if (isset($landing_data['settings'])) {
                update_post_meta($id_landing, '_flavor_landing_settings', $this->sanitizar_datos_recursivo($landing_data['settings']));
            }
        }

        return $resultados;
    }

    /**
     * Importa roles personalizados
     *
     * @param array $datos Datos de roles
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_roles($datos, $modo) {
        $resultados = array('created' => 0, 'updated' => 0);

        $roles_actuales = get_option('flavor_custom_roles', array());
        $roles_nuevos = $this->aplicar_modo_importacion($roles_actuales, $datos, $modo);

        // No importar module_roles directamente, es información de solo lectura
        unset($roles_nuevos['module_roles']);

        update_option('flavor_custom_roles', $this->sanitizar_datos_recursivo($roles_nuevos));
        $resultados['updated'] = count($roles_nuevos);

        // Sincronizar roles con WordPress si Role Manager está disponible
        if (class_exists('Flavor_Role_Manager')) {
            Flavor_Role_Manager::create_roles();
        }

        return $resultados;
    }

    /**
     * Importa permisos
     *
     * @param array $datos Datos de permisos
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_permisos($datos, $modo) {
        $resultados = array('updated' => 0);

        if (isset($datos['module_access'])) {
            $actual = get_option('flavor_module_access_config', array());
            $nuevo = $this->aplicar_modo_importacion($actual, $datos['module_access'], $modo);
            update_option('flavor_module_access_config', $this->sanitizar_datos_recursivo($nuevo));
            $resultados['updated']++;
        }

        if (isset($datos['role_permissions'])) {
            $actual = get_option('flavor_role_permissions', array());
            $nuevo = $this->aplicar_modo_importacion($actual, $datos['role_permissions'], $modo);
            update_option('flavor_role_permissions', $this->sanitizar_datos_recursivo($nuevo));
            $resultados['updated']++;
        }

        if (isset($datos['custom_capabilities'])) {
            $actual = get_option('flavor_custom_capabilities', array());
            $nuevo = $this->aplicar_modo_importacion($actual, $datos['custom_capabilities'], $modo);
            update_option('flavor_custom_capabilities', $this->sanitizar_datos_recursivo($nuevo));
            $resultados['updated']++;
        }

        return $resultados;
    }

    /**
     * Importa formato legacy (compatibilidad con versiones anteriores)
     *
     * @param array $datos Datos en formato legacy
     * @param string $modo Modo de importación
     * @return array Resultado
     */
    private function importar_formato_legacy($datos, $modo) {
        $resultados = array('updated' => 0, 'skipped' => 0);

        // Mapear formato antiguo al nuevo
        if (isset($datos['settings'])) {
            $this->importar_configuracion(array('general_settings' => $datos['settings']), $modo);
            $resultados['updated']++;
        }

        if (isset($datos['active_modules'])) {
            $this->importar_configuracion(array('active_modules' => $datos['active_modules']), $modo);
            $resultados['updated']++;
        }

        if (isset($datos['module_settings'])) {
            $this->importar_configuracion(array('modules_settings' => $datos['module_settings']), $modo);
            $resultados['updated']++;
        }

        if (isset($datos['design_settings'])) {
            $this->importar_diseno($datos['design_settings'], $modo);
            $resultados['updated']++;
        }

        if (isset($datos['pages'])) {
            $this->importar_paginas($datos['pages'], $modo);
            $resultados['updated']++;
        }

        return $resultados;
    }

    /**
     * Aplica el modo de importación a los datos
     *
     * @param mixed $actual Valor actual
     * @param mixed $nuevo Valor nuevo
     * @param string $modo Modo (overwrite, merge, only_missing)
     * @return mixed Valor resultante
     */
    private function aplicar_modo_importacion($actual, $nuevo, $modo) {
        switch ($modo) {
            case 'overwrite':
                return $nuevo;

            case 'only_missing':
                if (!is_array($actual) || !is_array($nuevo)) {
                    return empty($actual) ? $nuevo : $actual;
                }
                foreach ($nuevo as $clave => $valor) {
                    if (!isset($actual[$clave]) || empty($actual[$clave])) {
                        $actual[$clave] = $valor;
                    }
                }
                return $actual;

            case 'merge':
            default:
                if (!is_array($actual) || !is_array($nuevo)) {
                    return $nuevo;
                }
                return array_replace_recursive($actual, $nuevo);
        }
    }

    /**
     * Fusiona datos preservando secretos existentes
     *
     * @param array $existentes Datos existentes
     * @param array $nuevos Datos nuevos
     * @return array Datos fusionados
     */
    private function fusionar_preservando_secretos($existentes, $nuevos) {
        if (!is_array($nuevos)) {
            return $existentes;
        }

        $fusionados = $existentes;

        foreach ($nuevos as $clave => $valor) {
            // No sobrescribir valores REDACTED
            if ('***REDACTED***' === $valor) {
                continue;
            }

            if (is_array($valor) && isset($fusionados[$clave]) && is_array($fusionados[$clave])) {
                $fusionados[$clave] = $this->fusionar_preservando_secretos($fusionados[$clave], $valor);
            } else {
                $fusionados[$clave] = $valor;
            }
        }

        return $fusionados;
    }

    /**
     * Sanitiza datos recursivamente
     *
     * @param mixed $datos Datos a sanitizar
     * @return mixed Datos sanitizados
     */
    private function sanitizar_datos_recursivo($datos) {
        if (is_array($datos)) {
            $sanitizados = array();
            foreach ($datos as $clave => $valor) {
                $clave_sanitizada = sanitize_text_field($clave);
                $sanitizados[$clave_sanitizada] = $this->sanitizar_datos_recursivo($valor);
            }
            return $sanitizados;
        }

        if (is_string($datos)) {
            // Permitir HTML en ciertos campos
            if (strlen($datos) > 500 || strpos($datos, '<') !== false) {
                return wp_kses_post($datos);
            }
            return sanitize_text_field($datos);
        }

        if (is_bool($datos) || is_int($datos) || is_float($datos)) {
            return $datos;
        }

        return $datos;
    }

    // =========================================================================
    // MÉTODOS DE ANÁLISIS PARA PREVIEW
    // =========================================================================

    /**
     * Analiza cambios en configuración
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_config($datos_nuevos) {
        $analisis = array(
            'label' => __('Configuración General', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => 0,
            'changes' => array(),
        );

        if (isset($datos_nuevos['profile'])) {
            $perfil_actual = $this->obtener_perfil_actual();
            if ($perfil_actual !== $datos_nuevos['profile']) {
                $analisis['changes'][] = sprintf(
                    __('Perfil: %s → %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $perfil_actual,
                    $datos_nuevos['profile']
                );
            }
        }

        if (isset($datos_nuevos['active_modules'])) {
            $modulos_actuales = $this->obtener_modulos_activos();
            $modulos_nuevos = array_diff($datos_nuevos['active_modules'], $modulos_actuales);
            $modulos_removidos = array_diff($modulos_actuales, $datos_nuevos['active_modules']);

            if (!empty($modulos_nuevos)) {
                $analisis['changes'][] = sprintf(
                    __('Nuevos módulos: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    implode(', ', $modulos_nuevos)
                );
            }
            if (!empty($modulos_removidos)) {
                $analisis['changes'][] = sprintf(
                    __('Módulos a desactivar: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    implode(', ', $modulos_removidos)
                );
            }
        }

        $analisis['count'] = count($analisis['changes']);
        return $analisis;
    }

    /**
     * Analiza cambios en diseño
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_design($datos_nuevos) {
        $analisis = array(
            'label' => __('Ajustes de Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => 0,
            'changes' => array(),
        );

        $secciones = array(
            'theme' => __('Tema', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'colors' => __('Colores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'typography' => __('Tipografía', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'spacing' => __('Espaciados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'custom_css' => __('CSS personalizado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        );

        foreach ($secciones as $clave => $label) {
            if (isset($datos_nuevos[$clave]) && !empty($datos_nuevos[$clave])) {
                $analisis['changes'][] = $label;
                $analisis['count']++;
            }
        }

        return $analisis;
    }

    /**
     * Analiza cambios en páginas
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_pages($datos_nuevos) {
        $analisis = array(
            'label' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => count($datos_nuevos),
            'changes' => array(),
            'items' => array(),
        );

        foreach ($datos_nuevos as $pagina) {
            $titulo = isset($pagina['title']) ? $pagina['title'] : __('Sin título', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $slug = isset($pagina['slug']) ? $pagina['slug'] : '';
            $tipo = isset($pagina['post_type']) ? $pagina['post_type'] : 'page';

            $existe = $slug ? get_page_by_path($slug, OBJECT, $tipo) : null;

            $analisis['items'][] = array(
                'title' => $titulo,
                'slug' => $slug,
                'action' => $existe ? 'update' : 'create',
            );
        }

        return $analisis;
    }

    /**
     * Analiza cambios en landings
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_landings($datos_nuevos) {
        $analisis = array(
            'label' => __('Landing Pages', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => count($datos_nuevos),
            'changes' => array(),
            'items' => array(),
        );

        foreach ($datos_nuevos as $landing) {
            $titulo = isset($landing['title']) ? $landing['title'] : __('Sin título', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $slug = isset($landing['slug']) ? $landing['slug'] : '';

            $existe = $slug ? get_page_by_path($slug, OBJECT, 'flavor_landing') : null;

            $analisis['items'][] = array(
                'title' => $titulo,
                'slug' => $slug,
                'action' => $existe ? 'update' : 'create',
            );
        }

        return $analisis;
    }

    /**
     * Analiza cambios en roles
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_roles($datos_nuevos) {
        $analisis = array(
            'label' => __('Roles Personalizados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => is_array($datos_nuevos) ? count($datos_nuevos) : 0,
            'changes' => array(),
        );

        if (is_array($datos_nuevos)) {
            $roles_actuales = get_option('flavor_custom_roles', array());
            foreach ($datos_nuevos as $slug => $info) {
                if ('module_roles' === $slug) {
                    continue;
                }
                if (!isset($roles_actuales[$slug])) {
                    $analisis['changes'][] = sprintf(__('Nuevo rol: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $slug);
                }
            }
        }

        return $analisis;
    }

    /**
     * Analiza cambios en permisos
     *
     * @param array $datos_nuevos Datos nuevos
     * @return array Análisis
     */
    private function analizar_cambios_permissions($datos_nuevos) {
        $analisis = array(
            'label' => __('Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'count' => 0,
            'changes' => array(),
        );

        if (isset($datos_nuevos['module_access'])) {
            $analisis['changes'][] = __('Configuración de acceso a módulos', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $analisis['count']++;
        }

        if (isset($datos_nuevos['role_permissions'])) {
            $analisis['changes'][] = __('Permisos de roles', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $analisis['count']++;
        }

        if (isset($datos_nuevos['custom_capabilities'])) {
            $analisis['changes'][] = __('Capabilities personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN);
            $analisis['count']++;
        }

        return $analisis;
    }

    /**
     * Genera resumen para formato legacy
     *
     * @param array $datos_importar Datos a importar
     * @return array Resumen
     */
    private function generar_resumen_importacion_legacy($datos_importar) {
        $resumen = array(
            'label' => __('Datos (formato anterior)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sections' => array(),
        );

        $datos = $datos_importar['data'];

        if (isset($datos['settings'])) {
            $resumen['sections']['settings'] = array(
                'label' => __('Ajustes Generales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'count' => count($datos['settings']),
            );
        }

        if (isset($datos['active_modules'])) {
            $resumen['sections']['active_modules'] = array(
                'label' => __('Módulos Activos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => implode(', ', $datos['active_modules']),
                'count' => count($datos['active_modules']),
            );
        }

        if (isset($datos['module_settings'])) {
            $resumen['sections']['module_settings'] = array(
                'label' => __('Configuración de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'count' => count($datos['module_settings']),
            );
        }

        if (isset($datos['design_settings'])) {
            $resumen['sections']['design_settings'] = array(
                'label' => __('Ajustes de Diseño', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'count' => 1,
            );
        }

        if (isset($datos['pages'])) {
            $resumen['sections']['pages'] = array(
                'label' => __('Páginas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'count' => count($datos['pages']),
            );
        }

        return $resumen;
    }

    // =========================================================================
    // AJAX HANDLERS - MIGRACIÓN COMPLETA DEL SITIO
    // =========================================================================

    /**
     * Exporta el sitio completo (base de datos + archivos)
     */
    public function ajax_exportar_sitio_completo() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $incluir_db = isset($_POST['include_database']) && $_POST['include_database'] === 'true';
        $incluir_uploads = isset($_POST['include_uploads']) && $_POST['include_uploads'] === 'true';
        $incluir_plugins = isset($_POST['include_plugins']) && $_POST['include_plugins'] === 'true';
        $incluir_themes = isset($_POST['include_themes']) && $_POST['include_themes'] === 'true';

        try {
            $backup_dir = $this->obtener_directorio_backups();
            $timestamp = current_time('Y-m-d_H-i-s');
            $nombre_archivo = 'flavor-migration-' . $timestamp;
            $ruta_zip = $backup_dir . $nombre_archivo . '.zip';

            // Crear archivo ZIP
            $zip = new ZipArchive();
            if ($zip->open($ruta_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception(__('No se pudo crear el archivo de migración.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            // Exportar base de datos
            if ($incluir_db) {
                $sql_content = $this->exportar_base_datos();
                $zip->addFromString('database.sql', $sql_content);
            }

            // Exportar configuración del plugin
            $config = $this->export_full_config();
            $zip->addFromString('flavor-config.json', wp_json_encode($config, JSON_PRETTY_PRINT));

            // Exportar uploads
            if ($incluir_uploads) {
                $uploads_dir = wp_upload_dir();
                $this->agregar_directorio_a_zip($zip, $uploads_dir['basedir'], 'wp-content/uploads');
            }

            // Exportar plugins activos
            if ($incluir_plugins) {
                $plugins_activos = get_option('active_plugins', array());
                $plugins_dir = WP_PLUGIN_DIR;
                $plugins_exportados = array();

                foreach ($plugins_activos as $plugin_path) {
                    // Obtener el directorio del plugin desde plugin_basename().
                    $partes = explode('/', $plugin_path);
                    if (count($partes) > 1) {
                        $carpeta_plugin = $partes[0];
                        $ruta_completa = $plugins_dir . '/' . $carpeta_plugin;

                        // Evitar duplicados y verificar que existe
                        if (!in_array($carpeta_plugin, $plugins_exportados) && is_dir($ruta_completa)) {
                            $this->agregar_directorio_a_zip($zip, $ruta_completa, 'wp-content/plugins/' . $carpeta_plugin);
                            $plugins_exportados[] = $carpeta_plugin;
                        }
                    } else {
                        // Plugin de archivo único (ej: "hello.php")
                        $archivo_plugin = $plugins_dir . '/' . $plugin_path;
                        if (file_exists($archivo_plugin)) {
                            $zip->addFile($archivo_plugin, 'wp-content/plugins/' . $plugin_path);
                        }
                    }
                }
            }

            // Exportar tema activo (tema hijo y tema padre si aplica)
            if ($incluir_themes) {
                // Tema activo (puede ser hijo)
                $tema_activo = get_stylesheet_directory();
                $nombre_tema = basename($tema_activo);
                $this->agregar_directorio_a_zip($zip, $tema_activo, 'wp-content/themes/' . $nombre_tema);

                // Si es tema hijo, también exportar el tema padre
                $tema_padre = get_template_directory();
                if ($tema_padre !== $tema_activo) {
                    $nombre_tema_padre = basename($tema_padre);
                    $this->agregar_directorio_a_zip($zip, $tema_padre, 'wp-content/themes/' . $nombre_tema_padre);
                }
            }

            // Añadir manifest
            $manifest = array(
                'version' => self::EXPORT_FORMAT_VERSION,
                'fecha' => current_time('mysql'),
                'sitio_origen' => home_url(),
                'contenido' => array(
                    'database' => $incluir_db,
                    'uploads' => $incluir_uploads,
                    'plugins' => $incluir_plugins,
                    'themes' => $incluir_themes,
                ),
            );
            $zip->addFromString('manifest.json', wp_json_encode($manifest, JSON_PRETTY_PRINT));

            $zip->close();

            // Generar URL de descarga
            $upload_dir = wp_upload_dir();
            $url_descarga = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $ruta_zip);

            wp_send_json_success(array(
                'message' => __('Migración exportada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'download_url' => $url_descarga,
                'filename' => $nombre_archivo . '.zip',
                'size' => size_format(filesize($ruta_zip)),
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Importa un paquete de migración completo
     */
    public function ajax_importar_sitio_completo() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        if (!isset($_FILES['migration_file']) || $_FILES['migration_file']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('No se recibió el archivo correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        try {
            $archivo = $_FILES['migration_file'];
            $backup_dir = $this->obtener_directorio_backups();
            $ruta_zip = $backup_dir . 'import-' . time() . '.zip';

            // Mover archivo subido
            if (!move_uploaded_file($archivo['tmp_name'], $ruta_zip)) {
                throw new Exception(__('Error al procesar el archivo subido.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            // Abrir ZIP
            $zip = new ZipArchive();
            if ($zip->open($ruta_zip) !== true) {
                throw new Exception(__('El archivo no es un ZIP válido.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            // Leer manifest
            $manifest_content = $zip->getFromName('manifest.json');
            if (!$manifest_content) {
                throw new Exception(__('El archivo no contiene un manifest válido.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }
            $manifest = json_decode($manifest_content, true);

            $resultados = array();

            // Importar configuración del plugin
            $config_content = $zip->getFromName('flavor-config.json');
            if ($config_content) {
                $config = json_decode($config_content, true);
                $this->import_config($config);
                $resultados[] = __('Configuración del plugin importada.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            // Importar base de datos (con precaución)
            if (!empty($manifest['contenido']['database'])) {
                $sql_content = $zip->getFromName('database.sql');
                if ($sql_content) {
                    // Solo importamos si el usuario lo confirma explícitamente
                    if (isset($_POST['confirm_database']) && $_POST['confirm_database'] === 'true') {
                        $this->importar_base_datos($sql_content);
                        $resultados[] = __('Base de datos importada.', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    } else {
                        $resultados[] = __('Base de datos detectada (requiere confirmación adicional).', FLAVOR_PLATFORM_TEXT_DOMAIN);
                    }
                }
            }

            // Extraer archivos
            $extraer_tipos = array();
            if (!empty($manifest['contenido']['uploads']) && isset($_POST['import_uploads']) && $_POST['import_uploads'] === 'true') {
                $extraer_tipos[] = 'wp-content/uploads';
            }
            if (!empty($manifest['contenido']['plugins']) && isset($_POST['import_plugins']) && $_POST['import_plugins'] === 'true') {
                $extraer_tipos[] = 'wp-content/plugins';
            }
            if (!empty($manifest['contenido']['themes']) && isset($_POST['import_themes']) && $_POST['import_themes'] === 'true') {
                $extraer_tipos[] = 'wp-content/themes';
            }

            foreach ($extraer_tipos as $tipo) {
                $this->extraer_archivos_zip($zip, $tipo, ABSPATH);
                $resultados[] = sprintf(__('%s extraídos.', FLAVOR_PLATFORM_TEXT_DOMAIN), ucfirst(basename($tipo)));
            }

            $zip->close();

            // Limpiar archivo temporal
            @unlink($ruta_zip);

            wp_send_json_success(array(
                'message' => __('Migración importada correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'details' => $resultados,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Previsualiza los cambios de buscar/reemplazar
     */
    public function ajax_preview_search_replace() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $buscar = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $reemplazar = isset($_POST['replace']) ? sanitize_text_field($_POST['replace']) : '';

        if (empty($buscar)) {
            wp_send_json_error(array('message' => __('Debes especificar un texto a buscar.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        global $wpdb;

        $preview = array();
        $total_encontrados = 0;

        // Tablas a buscar
        $tablas = array(
            $wpdb->posts => array('post_content', 'post_excerpt', 'guid'),
            $wpdb->postmeta => array('meta_value'),
            $wpdb->options => array('option_value'),
            $wpdb->comments => array('comment_content', 'comment_author_url'),
            $wpdb->usermeta => array('meta_value'),
        );

        foreach ($tablas as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                $count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla} WHERE {$columna} LIKE %s",
                    '%' . $wpdb->esc_like($buscar) . '%'
                ));

                if ($count > 0) {
                    $total_encontrados += $count;
                    $preview[] = array(
                        'tabla' => str_replace($wpdb->prefix, '', $tabla),
                        'columna' => $columna,
                        'coincidencias' => (int) $count,
                    );
                }
            }
        }

        wp_send_json_success(array(
            'total' => $total_encontrados,
            'preview' => $preview,
            'buscar' => $buscar,
            'reemplazar' => $reemplazar,
        ));
    }

    /**
     * Aplica buscar/reemplazar en la base de datos
     */
    public function ajax_apply_search_replace() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $buscar = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        $reemplazar = isset($_POST['replace']) ? sanitize_text_field($_POST['replace']) : '';

        if (empty($buscar)) {
            wp_send_json_error(array('message' => __('Debes especificar un texto a buscar.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        global $wpdb;

        // Crear backup antes de aplicar cambios
        $this->crear_backup_automatico('pre-search-replace');

        $total_reemplazos = 0;
        $resultados = array();

        // Tablas y columnas a procesar
        $tablas = array(
            $wpdb->posts => array('post_content', 'post_excerpt', 'guid'),
            $wpdb->postmeta => array('meta_value'),
            $wpdb->options => array('option_value'),
            $wpdb->comments => array('comment_content', 'comment_author_url'),
            $wpdb->usermeta => array('meta_value'),
        );

        foreach ($tablas as $tabla => $columnas) {
            foreach ($columnas as $columna) {
                // Para postmeta, options y usermeta necesitamos manejar datos serializados
                if (in_array($tabla, array($wpdb->postmeta, $wpdb->options, $wpdb->usermeta))) {
                    $filas = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$tabla} WHERE {$columna} LIKE %s",
                            '%' . $wpdb->esc_like($buscar) . '%'
                        ),
                        ARRAY_A
                    );

                    foreach ($filas as $fila) {
                        $valor_original = $fila[$columna];
                        $valor_nuevo = $this->buscar_reemplazar_serializado($valor_original, $buscar, $reemplazar);

                        if ($valor_nuevo !== $valor_original) {
                            $pk = $tabla === $wpdb->options ? 'option_id' : ($tabla === $wpdb->usermeta ? 'umeta_id' : 'meta_id');
                            $wpdb->update(
                                $tabla,
                                array($columna => $valor_nuevo),
                                array($pk => $fila[$pk])
                            );
                            $total_reemplazos++;
                        }
                    }
                } else {
                    // Reemplazo directo para posts y comments
                    $count = $wpdb->query($wpdb->prepare(
                        "UPDATE {$tabla} SET {$columna} = REPLACE({$columna}, %s, %s) WHERE {$columna} LIKE %s",
                        $buscar,
                        $reemplazar,
                        '%' . $wpdb->esc_like($buscar) . '%'
                    ));
                    $total_reemplazos += $count;
                }

                if ($count > 0) {
                    $resultados[] = array(
                        'tabla' => str_replace($wpdb->prefix, '', $tabla),
                        'columna' => $columna,
                        'reemplazos' => $count,
                    );
                }
            }
        }

        // Limpiar cache
        wp_cache_flush();

        wp_send_json_success(array(
            'message' => sprintf(__('Se realizaron %d reemplazos.', FLAVOR_PLATFORM_TEXT_DOMAIN), $total_reemplazos),
            'total' => $total_reemplazos,
            'detalles' => $resultados,
        ));
    }

    /**
     * Buscar y reemplazar en datos serializados
     *
     * @param string $data Datos originales
     * @param string $buscar Texto a buscar
     * @param string $reemplazar Texto de reemplazo
     * @return string Datos con reemplazos
     */
    private function buscar_reemplazar_serializado($data, $buscar, $reemplazar) {
        // Intentar deserializar
        $unserialized = @unserialize($data);

        if ($unserialized !== false || $data === 'b:0;') {
            // Es dato serializado
            $unserialized = $this->buscar_reemplazar_recursivo($unserialized, $buscar, $reemplazar);
            return serialize($unserialized);
        }

        // No es serializado, reemplazar directamente
        return str_replace($buscar, $reemplazar, $data);
    }

    /**
     * Buscar y reemplazar recursivamente en arrays/objetos
     *
     * @param mixed $data Datos
     * @param string $buscar Texto a buscar
     * @param string $reemplazar Texto de reemplazo
     * @return mixed Datos modificados
     */
    private function buscar_reemplazar_recursivo($data, $buscar, $reemplazar) {
        if (is_string($data)) {
            return str_replace($buscar, $reemplazar, $data);
        }

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->buscar_reemplazar_recursivo($value, $buscar, $reemplazar);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach ($data as $key => $value) {
                $data->$key = $this->buscar_reemplazar_recursivo($value, $buscar, $reemplazar);
            }
            return $data;
        }

        return $data;
    }

    // =========================================================================
    // AJAX HANDLERS - SISTEMA DE BACKUPS
    // =========================================================================

    /**
     * Crea un backup manual
     */
    public function ajax_crear_backup() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $nombre = isset($_POST['backup_name']) ? sanitize_file_name($_POST['backup_name']) : '';
        $incluir_db = isset($_POST['include_database']) && $_POST['include_database'] === 'true';
        $incluir_uploads = isset($_POST['include_uploads']) && $_POST['include_uploads'] === 'true';
        $incluir_plugins = isset($_POST['include_plugins']) && $_POST['include_plugins'] === 'true';
        $incluir_themes = isset($_POST['include_themes']) && $_POST['include_themes'] === 'true';

        try {
            $resultado = $this->crear_backup($nombre, $incluir_db, $incluir_uploads, $incluir_plugins, $incluir_themes);

            wp_send_json_success(array(
                'message' => __('Backup creado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'backup' => $resultado,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Restaura un backup existente
     */
    public function ajax_restaurar_backup() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $backup_id = isset($_POST['backup_id']) ? sanitize_text_field($_POST['backup_id']) : '';

        if (empty($backup_id)) {
            wp_send_json_error(array('message' => __('ID de backup no proporcionado.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        try {
            $backup_dir = $this->obtener_directorio_backups();
            $ruta_zip = $backup_dir . $backup_id . '.zip';

            if (!file_exists($ruta_zip)) {
                throw new Exception(__('El backup no existe.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            // Crear backup de seguridad antes de restaurar
            $this->crear_backup_automatico('pre-restore');

            $zip = new ZipArchive();
            if ($zip->open($ruta_zip) !== true) {
                throw new Exception(__('No se pudo abrir el backup.', FLAVOR_PLATFORM_TEXT_DOMAIN));
            }

            $resultados = array();

            // Restaurar configuración
            $config_content = $zip->getFromName('flavor-config.json');
            if ($config_content) {
                $config = json_decode($config_content, true);
                $this->import_config($config);
                $resultados[] = __('Configuración restaurada.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            // Restaurar base de datos si existe
            $sql_content = $zip->getFromName('database.sql');
            if ($sql_content && isset($_POST['restore_database']) && $_POST['restore_database'] === 'true') {
                $this->importar_base_datos($sql_content);
                $resultados[] = __('Base de datos restaurada.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            // Restaurar uploads si existe
            if (isset($_POST['restore_uploads']) && $_POST['restore_uploads'] === 'true') {
                $this->extraer_archivos_zip($zip, 'wp-content/uploads', ABSPATH);
                $resultados[] = __('Archivos de uploads restaurados.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            $zip->close();

            wp_cache_flush();

            wp_send_json_success(array(
                'message' => __('Backup restaurado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'details' => $resultados,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Elimina un backup
     */
    public function ajax_eliminar_backup() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $backup_id = isset($_POST['backup_id']) ? sanitize_text_field($_POST['backup_id']) : '';

        if (empty($backup_id)) {
            wp_send_json_error(array('message' => __('ID de backup no proporcionado.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $backup_dir = $this->obtener_directorio_backups();
        $ruta_zip = $backup_dir . $backup_id . '.zip';

        if (!file_exists($ruta_zip)) {
            wp_send_json_error(array('message' => __('El backup no existe.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        if (!@unlink($ruta_zip)) {
            wp_send_json_error(array('message' => __('No se pudo eliminar el backup.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        wp_send_json_success(array(
            'message' => __('Backup eliminado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ));
    }

    /**
     * Descarga un backup
     */
    public function ajax_descargar_backup() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $backup_id = isset($_POST['backup_id']) ? sanitize_text_field($_POST['backup_id']) : '';

        if (empty($backup_id)) {
            wp_send_json_error(array('message' => __('ID de backup no proporcionado.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $backup_dir = $this->obtener_directorio_backups();
        $ruta_zip = $backup_dir . $backup_id . '.zip';

        if (!file_exists($ruta_zip)) {
            wp_send_json_error(array('message' => __('El backup no existe.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        // Generar URL temporal de descarga
        $upload_dir = wp_upload_dir();
        $url_descarga = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $ruta_zip);

        wp_send_json_success(array(
            'download_url' => $url_descarga,
            'filename' => $backup_id . '.zip',
        ));
    }

    /**
     * Guarda la configuración de backups programados
     */
    public function ajax_guardar_config_backup() {
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', FLAVOR_PLATFORM_TEXT_DOMAIN)));
        }

        $config = array(
            'enabled' => isset($_POST['backup_enabled']) && $_POST['backup_enabled'] === 'true',
            'frequency' => isset($_POST['backup_frequency']) ? sanitize_text_field($_POST['backup_frequency']) : 'weekly',
            'retain_count' => isset($_POST['backup_retain']) ? absint($_POST['backup_retain']) : 5,
            'include_database' => isset($_POST['backup_database']) && $_POST['backup_database'] === 'true',
            'include_uploads' => isset($_POST['backup_uploads']) && $_POST['backup_uploads'] === 'true',
            'email_notify' => isset($_POST['backup_email']) ? sanitize_email($_POST['backup_email']) : '',
        );

        update_option('flavor_backup_schedule_config', $config);

        // Programar o desprogramar cron
        $timestamp = wp_next_scheduled('flavor_scheduled_backup');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'flavor_scheduled_backup');
        }

        if ($config['enabled']) {
            $recurrencia = $config['frequency'] === 'daily' ? 'daily' : 'weekly';
            wp_schedule_event(time() + 3600, $recurrencia, 'flavor_scheduled_backup');
        }

        wp_send_json_success(array(
            'message' => __('Configuración de backups guardada.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'config' => $config,
        ));
    }

    /**
     * Ejecuta backup programado (llamado por cron)
     */
    public function ejecutar_backup_programado() {
        $config = get_option('flavor_backup_schedule_config', array());

        if (empty($config['enabled'])) {
            return;
        }

        try {
            $incluir_db = !empty($config['include_database']);
            $incluir_uploads = !empty($config['include_uploads']);

            $resultado = $this->crear_backup('scheduled-' . current_time('Y-m-d'), $incluir_db, $incluir_uploads);

            // Limpiar backups antiguos
            $this->limpiar_backups_antiguos($config['retain_count'] ?? 5);

            // Notificar por email si está configurado
            if (!empty($config['email_notify'])) {
                $this->notificar_backup_completado($resultado, $config['email_notify']);
            }

        } catch (Exception $e) {
            error_log('Flavor Backup Scheduled Error: ' . $e->getMessage());

            if (!empty($config['email_notify'])) {
                wp_mail(
                    $config['email_notify'],
                    __('[Flavor Platform] Error en backup programado', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    sprintf(__('Se produjo un error durante el backup programado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $e->getMessage())
                );
            }
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES - BACKUPS Y MIGRACIÓN
    // =========================================================================

    /**
     * Obtiene el directorio de backups
     *
     * @return string Ruta al directorio
     */
    private function obtener_directorio_backups() {
        $upload_dir = wp_upload_dir();
        $backup_dir = $upload_dir['basedir'] . '/flavor-backups/';

        if (!file_exists($backup_dir)) {
            wp_mkdir_p($backup_dir);
            // Proteger directorio
            file_put_contents($backup_dir . '.htaccess', 'deny from all');
            file_put_contents($backup_dir . 'index.php', '<?php // Silence is golden');
        }

        return $backup_dir;
    }

    /**
     * Crea un backup
     *
     * @param string $nombre Nombre del backup
     * @param bool $incluir_db Incluir base de datos
     * @param bool $incluir_uploads Incluir uploads
     * @param bool $incluir_plugins Incluir plugins activos
     * @param bool $incluir_themes Incluir tema activo
     * @return array Información del backup
     */
    private function crear_backup($nombre = '', $incluir_db = true, $incluir_uploads = false, $incluir_plugins = false, $incluir_themes = false) {
        $backup_dir = $this->obtener_directorio_backups();
        $timestamp = current_time('Y-m-d_H-i-s');
        $nombre_archivo = 'flavor-backup-' . ($nombre ? sanitize_file_name($nombre) . '-' : '') . $timestamp;
        $ruta_zip = $backup_dir . $nombre_archivo . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($ruta_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new Exception(__('No se pudo crear el archivo de backup.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Exportar configuración del plugin
        $config = $this->export_full_config();
        $zip->addFromString('flavor-config.json', wp_json_encode($config, JSON_PRETTY_PRINT));

        // Exportar base de datos
        if ($incluir_db) {
            $sql_content = $this->exportar_base_datos();
            $zip->addFromString('database.sql', $sql_content);
        }

        // Exportar uploads
        if ($incluir_uploads) {
            $uploads_dir = wp_upload_dir();
            $this->agregar_directorio_a_zip($zip, $uploads_dir['basedir'], 'wp-content/uploads');
        }

        // Exportar plugins activos
        if ($incluir_plugins) {
            $plugins_activos = get_option('active_plugins', array());
            $plugins_dir = WP_PLUGIN_DIR;
            $plugins_exportados = array();

            foreach ($plugins_activos as $plugin_path) {
                $partes = explode('/', $plugin_path);
                if (count($partes) > 1) {
                    $carpeta_plugin = $partes[0];
                    $ruta_completa = $plugins_dir . '/' . $carpeta_plugin;

                    if (!in_array($carpeta_plugin, $plugins_exportados) && is_dir($ruta_completa)) {
                        $this->agregar_directorio_a_zip($zip, $ruta_completa, 'wp-content/plugins/' . $carpeta_plugin);
                        $plugins_exportados[] = $carpeta_plugin;
                    }
                } else {
                    $archivo_plugin = $plugins_dir . '/' . $plugin_path;
                    if (file_exists($archivo_plugin)) {
                        $zip->addFile($archivo_plugin, 'wp-content/plugins/' . $plugin_path);
                    }
                }
            }
        }

        // Exportar tema activo
        if ($incluir_themes) {
            $tema_activo = get_stylesheet_directory();
            $nombre_tema = basename($tema_activo);
            $this->agregar_directorio_a_zip($zip, $tema_activo, 'wp-content/themes/' . $nombre_tema);

            // Tema padre si existe
            $tema_padre = get_template_directory();
            if ($tema_padre !== $tema_activo) {
                $nombre_tema_padre = basename($tema_padre);
                $this->agregar_directorio_a_zip($zip, $tema_padre, 'wp-content/themes/' . $nombre_tema_padre);
            }
        }

        // Añadir manifest
        $manifest = array(
            'version' => self::EXPORT_FORMAT_VERSION,
            'fecha' => current_time('mysql'),
            'sitio' => home_url(),
            'tipo' => 'backup',
            'contenido' => array(
                'config' => true,
                'database' => $incluir_db,
                'uploads' => $incluir_uploads,
                'plugins' => $incluir_plugins,
                'themes' => $incluir_themes,
            ),
        );
        $zip->addFromString('manifest.json', wp_json_encode($manifest, JSON_PRETTY_PRINT));

        $zip->close();

        return array(
            'id' => $nombre_archivo,
            'fecha' => current_time('mysql'),
            'size' => size_format(filesize($ruta_zip)),
            'contenido' => $manifest['contenido'],
        );
    }

    /**
     * Crea un backup automático antes de operaciones críticas
     *
     * @param string $prefijo Prefijo para el nombre
     * @return array|false Información del backup o false si falla
     */
    private function crear_backup_automatico($prefijo = 'auto') {
        try {
            return $this->crear_backup($prefijo, true, false);
        } catch (Exception $e) {
            error_log('Flavor Auto-Backup Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Exporta la base de datos a SQL
     *
     * @return string Contenido SQL
     */
    private function exportar_base_datos() {
        global $wpdb;

        $sql = "-- Flavor Platform Database Export\n";
        $sql .= "-- Generado: " . current_time('mysql') . "\n";
        $sql .= "-- Sitio: " . home_url() . "\n\n";

        // Obtener tablas
        $tablas = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");

        foreach ($tablas as $tabla) {
            // Estructura de la tabla
            $create = $wpdb->get_row("SHOW CREATE TABLE `{$tabla}`", ARRAY_N);
            $sql .= "\n-- Tabla: {$tabla}\n";
            $sql .= "DROP TABLE IF EXISTS `{$tabla}`;\n";
            $sql .= $create[1] . ";\n\n";

            // Datos de la tabla
            $filas = $wpdb->get_results("SELECT * FROM `{$tabla}`", ARRAY_A);
            foreach ($filas as $fila) {
                $valores = array_map(function($v) use ($wpdb) {
                    if (is_null($v)) {
                        return 'NULL';
                    }
                    return "'" . $wpdb->_real_escape($v) . "'";
                }, array_values($fila));

                $sql .= "INSERT INTO `{$tabla}` VALUES(" . implode(',', $valores) . ");\n";
            }
        }

        return $sql;
    }

    /**
     * Importa base de datos desde SQL
     *
     * @param string $sql_content Contenido SQL
     */
    private function importar_base_datos($sql_content) {
        global $wpdb;

        // Dividir en sentencias
        $sentencias = preg_split('/;\s*\n/', $sql_content);

        foreach ($sentencias as $sentencia) {
            $sentencia = trim($sentencia);
            if (empty($sentencia) || strpos($sentencia, '--') === 0) {
                continue;
            }
            $wpdb->query($sentencia);
        }
    }

    /**
     * Agrega un directorio al ZIP
     *
     * @param ZipArchive $zip Objeto ZIP
     * @param string $directorio Directorio origen
     * @param string $ruta_zip Ruta dentro del ZIP
     */
    private function agregar_directorio_a_zip($zip, $directorio, $ruta_zip) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directorio, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $archivo) {
            if ($archivo->isFile()) {
                $ruta_real = $archivo->getRealPath();
                $ruta_relativa = $ruta_zip . '/' . substr($ruta_real, strlen($directorio) + 1);
                $zip->addFile($ruta_real, $ruta_relativa);
            }
        }
    }

    /**
     * Extrae archivos del ZIP
     *
     * @param ZipArchive $zip Objeto ZIP
     * @param string $prefijo Prefijo de archivos a extraer
     * @param string $destino Directorio destino
     */
    private function extraer_archivos_zip($zip, $prefijo, $destino) {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nombre = $zip->getNameIndex($i);
            if (strpos($nombre, $prefijo) === 0) {
                $zip->extractTo($destino, $nombre);
            }
        }
    }

    /**
     * Lista los backups disponibles
     *
     * @return array Lista de backups
     */
    public function listar_backups() {
        $backup_dir = $this->obtener_directorio_backups();
        $backups = array();

        foreach (glob($backup_dir . 'flavor-backup-*.zip') as $archivo) {
            $nombre = basename($archivo, '.zip');
            $manifest_content = null;

            // Intentar leer el manifest
            $zip = new ZipArchive();
            if ($zip->open($archivo) === true) {
                $manifest_content = $zip->getFromName('manifest.json');
                $zip->close();
            }

            $manifest = $manifest_content ? json_decode($manifest_content, true) : array();

            $backups[] = array(
                'id' => $nombre,
                'fecha' => isset($manifest['fecha']) ? $manifest['fecha'] : date('Y-m-d H:i:s', filemtime($archivo)),
                'size' => size_format(filesize($archivo)),
                'contenido' => isset($manifest['contenido']) ? $manifest['contenido'] : array(),
            );
        }

        // Ordenar por fecha descendente
        usort($backups, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        return $backups;
    }

    /**
     * Limpia backups antiguos
     *
     * @param int $retener Cantidad a retener
     */
    private function limpiar_backups_antiguos($retener = 5) {
        $backups = $this->listar_backups();

        // Solo eliminar backups programados
        $backups_programados = array_filter($backups, function($b) {
            return strpos($b['id'], 'scheduled') !== false;
        });

        if (count($backups_programados) > $retener) {
            $a_eliminar = array_slice($backups_programados, $retener);
            $backup_dir = $this->obtener_directorio_backups();

            foreach ($a_eliminar as $backup) {
                @unlink($backup_dir . $backup['id'] . '.zip');
            }
        }
    }

    /**
     * Notifica backup completado por email
     *
     * @param array $backup Info del backup
     * @param string $email Email destino
     */
    private function notificar_backup_completado($backup, $email) {
        $asunto = sprintf(__('[Flavor Platform] Backup completado - %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $backup['fecha']);
        $mensaje = sprintf(
            __("Se ha completado el backup programado.\n\nFecha: %s\nTamaño: %s\nSitio: %s", FLAVOR_PLATFORM_TEXT_DOMAIN),
            $backup['fecha'],
            $backup['size'],
            home_url()
        );

        wp_mail($email, $asunto, $mensaje);
    }
}
