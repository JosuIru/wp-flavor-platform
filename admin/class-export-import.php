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
    const PAGE_SLUG = 'flavor-export-import';

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

        // AJAX handlers
        add_action('wp_ajax_flavor_export_config', array($this, 'ajax_exportar_configuracion'));
        add_action('wp_ajax_flavor_import_config', array($this, 'ajax_importar_configuracion'));
        add_action('wp_ajax_flavor_preview_import', array($this, 'ajax_previsualizar_importacion'));
        add_action('wp_ajax_flavor_download_export', array($this, 'ajax_descargar_exportacion'));
        add_action('wp_ajax_flavor_apply_preset', array($this, 'ajax_aplicar_preset'));
        add_action('wp_ajax_flavor_get_presets', array($this, 'ajax_obtener_presets'));

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
                'nombre' => __('Grupo de Consumo', 'flavor-chat-ia'),
                'descripcion' => __('Configuración optimizada para grupos de consumo agroecológico', 'flavor-chat-ia'),
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
                'nombre' => __('Asociación / ONG', 'flavor-chat-ia'),
                'descripcion' => __('Configuración para asociaciones y organizaciones sin ánimo de lucro', 'flavor-chat-ia'),
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
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'descripcion' => __('Configuración para bancos de tiempo y economía colaborativa', 'flavor-chat-ia'),
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
                'nombre' => __('Comunidad / Vecindario', 'flavor-chat-ia'),
                'descripcion' => __('Configuración para comunidades de vecinos y barrios', 'flavor-chat-ia'),
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
                'nombre' => __('Tienda Online', 'flavor-chat-ia'),
                'descripcion' => __('Configuración para tiendas con WooCommerce', 'flavor-chat-ia'),
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
                'nombre' => __('Configuración Mínima', 'flavor-chat-ia'),
                'descripcion' => __('Solo funcionalidades esenciales', 'flavor-chat-ia'),
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
        require_once FLAVOR_CHAT_IA_PATH . 'includes/cli/class-export-import-command.php';
    }

    /**
     * Registra la página de menú (llamado por el gestor de menú)
     */
    public function registrar_pagina_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Exportar / Importar', 'flavor-chat-ia'),
            __('Exportar / Importar', 'flavor-chat-ia'),
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
            FLAVOR_CHAT_IA_URL . "admin/css/export-import{$sufijo_asset}.css",
            array(),
            FLAVOR_CHAT_IA_VERSION
        );

        // JS
        wp_enqueue_script(
            'flavor-export-import-js',
            FLAVOR_CHAT_IA_URL . "admin/js/export-import{$sufijo_asset}.js",
            array('jquery', 'wp-util'),
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-export-import-js', 'flavorExportImport', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_export_import_nonce'),
            'presets' => $this->presets_disponibles,
            'strings' => array(
                'exportando' => __('Exportando configuración...', 'flavor-chat-ia'),
                'exportCompletada' => __('Exportación completada', 'flavor-chat-ia'),
                'importando' => __('Importando configuración...', 'flavor-chat-ia'),
                'importCompletada' => __('Importación completada correctamente', 'flavor-chat-ia'),
                'errorExport' => __('Error al exportar los datos', 'flavor-chat-ia'),
                'errorImport' => __('Error al importar los datos', 'flavor-chat-ia'),
                'errorArchivo' => __('Por favor, selecciona un archivo JSON válido', 'flavor-chat-ia'),
                'confirmarImport' => __('¿Seguro que deseas aplicar esta importación? Los datos existentes serán modificados.', 'flavor-chat-ia'),
                'confirmarPreset' => __('¿Aplicar este preset? Se modificará la configuración actual.', 'flavor-chat-ia'),
                'previsualizando' => __('Analizando archivo...', 'flavor-chat-ia'),
                'sinSeleccion' => __('Selecciona al menos una opción para exportar', 'flavor-chat-ia'),
                'arrastrarArchivo' => __('Arrastra un archivo JSON aquí', 'flavor-chat-ia'),
                'oSeleccionar' => __('o haz clic para seleccionar', 'flavor-chat-ia'),
                'aplicandoPreset' => __('Aplicando preset...', 'flavor-chat-ia'),
                'presetAplicado' => __('Preset aplicado correctamente', 'flavor-chat-ia'),
                'paso1' => __('Paso 1: Seleccionar archivo', 'flavor-chat-ia'),
                'paso2' => __('Paso 2: Previsualización', 'flavor-chat-ia'),
                'paso3' => __('Paso 3: Opciones de importación', 'flavor-chat-ia'),
                'modoSobrescribir' => __('Sobrescribir todo', 'flavor-chat-ia'),
                'modoMerge' => __('Combinar (merge)', 'flavor-chat-ia'),
                'modoSoloFaltantes' => __('Solo lo que falta', 'flavor-chat-ia'),
            ),
        ));
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
                    __('Importación realizada: %s secciones importadas, %s omitidas', 'flavor-chat-ia'),
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
                    sprintf(__('Falta el campo obligatorio: %s', 'flavor-chat-ia'), $campo)
                );
            }
        }

        // Verificar versión compatible
        if (version_compare($datos_json['version'], '2.0.0', '<')) {
            return new WP_Error(
                'version_incompatible',
                __('La versión del archivo de exportación es demasiado antigua. Se requiere versión 2.0.0 o superior.', 'flavor-chat-ia')
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
                __('El archivo no contiene datos válidos para importar.', 'flavor-chat-ia')
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
                    __('El archivo parece estar corrupto (checksum no coincide).', 'flavor-chat-ia')
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
                'site_url' => isset($datos_json['site_url']) ? $datos_json['site_url'] : __('No especificado', 'flavor-chat-ia'),
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
                __('Esta exportación proviene de otro sitio (%s). Algunas URLs podrían no funcionar correctamente.', 'flavor-chat-ia'),
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
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $secciones = isset($_POST['sections']) ? array_map('sanitize_text_field', (array) $_POST['sections']) : array();

        if (empty($secciones)) {
            wp_send_json_error(array('message' => __('No se seleccionó ninguna sección para exportar.', 'flavor-chat-ia')));
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
                sprintf(__('Exportación realizada: %s', 'flavor-chat-ia'), implode(', ', $secciones))
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
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        // Obtener datos del transient (guardados en preview)
        $clave_transitoria = 'flavor_import_' . get_current_user_id();
        $datos_json = get_transient($clave_transitoria);

        if (false === $datos_json) {
            wp_send_json_error(array('message' => __('Los datos de importación han expirado. Sube el archivo de nuevo.', 'flavor-chat-ia')));
        }

        $secciones = isset($_POST['sections']) ? array_map('sanitize_text_field', (array) $_POST['sections']) : array();
        $modo = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'merge';

        if (empty($secciones)) {
            wp_send_json_error(array('message' => __('No se seleccionó ninguna sección para importar.', 'flavor-chat-ia')));
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
            'message' => __('Importación completada correctamente.', 'flavor-chat-ia'),
            'results' => $resultado,
        ));
    }

    /**
     * AJAX: Previsualizar importación
     */
    public function ajax_previsualizar_importacion() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $datos_json = null;

        // Intentar obtener de archivo subido
        if (!empty($_FILES['import_file'])) {
            $archivo_subido = $_FILES['import_file'];
            $info_archivo = wp_check_filetype($archivo_subido['name'], array('json' => 'application/json'));

            if (empty($info_archivo['ext'])) {
                wp_send_json_error(array('message' => __('El archivo debe ser de tipo JSON.', 'flavor-chat-ia')));
            }

            $contenido_json = file_get_contents($archivo_subido['tmp_name']);
            if (false === $contenido_json) {
                wp_send_json_error(array('message' => __('No se pudo leer el archivo.', 'flavor-chat-ia')));
            }

            $datos_json = json_decode($contenido_json, true);
        }
        // Intentar obtener de JSON pegado
        elseif (!empty($_POST['json_content'])) {
            $contenido_json = wp_unslash($_POST['json_content']);
            $datos_json = json_decode($contenido_json, true);
        }

        if (null === $datos_json) {
            wp_send_json_error(array('message' => __('No se pudo parsear el JSON. Verifica que el formato sea correcto.', 'flavor-chat-ia')));
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
            wp_die(__('No tienes permisos suficientes.', 'flavor-chat-ia'));
        }

        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $datos_exportados = $this->export_full_config();
        $datos_exportados['checksum'] = md5(wp_json_encode($datos_exportados));

        $nombre_archivo = 'flavor-platform-export-' . gmdate('Y-m-d-His') . '.json';
        $contenido_json = wp_json_encode($datos_exportados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Content-Length: ' . strlen($contenido_json));
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
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')));
        }
        check_ajax_referer('flavor_export_import_nonce', 'nonce');

        $preset_id = isset($_POST['preset_id']) ? sanitize_key($_POST['preset_id']) : '';

        if (!isset($this->presets_disponibles[$preset_id])) {
            wp_send_json_error(array('message' => __('Preset no encontrado.', 'flavor-chat-ia')));
        }

        $preset = $this->presets_disponibles[$preset_id];
        $resultados = array();

        // Aplicar configuración del preset
        if (isset($preset['config'])) {
            $ajustes_actuales = get_option('flavor_chat_ia_settings', array());

            if (isset($preset['config']['profile'])) {
                $ajustes_actuales['app_profile'] = $preset['config']['profile'];
            }

            if (isset($preset['config']['active_modules'])) {
                $ajustes_actuales['active_modules'] = $preset['config']['active_modules'];
            }

            update_option('flavor_chat_ia_settings', $ajustes_actuales);
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
                sprintf(__('Preset aplicado: %s', 'flavor-chat-ia'), $preset['nombre'])
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Preset "%s" aplicado correctamente.', 'flavor-chat-ia'), $preset['nombre']),
            'results' => $resultados,
        ));
    }

    /**
     * AJAX: Obtener lista de presets
     */
    public function ajax_obtener_presets() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('No tienes permisos suficientes.', 'flavor-chat-ia')));
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
        $ajustes = get_option('flavor_chat_ia_settings', array());
        return isset($ajustes['app_profile']) ? $ajustes['app_profile'] : 'personalizado';
    }

    /**
     * Obtiene la configuración completa
     *
     * @return array
     */
    private function obtener_configuracion_completa() {
        $ajustes = get_option('flavor_chat_ia_settings', array());
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
        $ajustes = get_option('flavor_chat_ia_settings', array());
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
                return new WP_Error('seccion_desconocida', sprintf(__('Sección desconocida: %s', 'flavor-chat-ia'), $seccion));
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
            $ajustes_actuales = get_option('flavor_chat_ia_settings', array());
            $ajustes_nuevos = $this->aplicar_modo_importacion($ajustes_actuales, $datos['general_settings'], $modo);
            $ajustes_nuevos = $this->fusionar_preservando_secretos($ajustes_actuales, $ajustes_nuevos);
            update_option('flavor_chat_ia_settings', $this->sanitizar_datos_recursivo($ajustes_nuevos));
            $resultados['updated']++;
        }

        // Importar módulos activos
        if (isset($datos['active_modules'])) {
            $ajustes = get_option('flavor_chat_ia_settings', array());
            $ajustes['active_modules'] = array_map('sanitize_text_field', $datos['active_modules']);
            update_option('flavor_chat_ia_settings', $ajustes);
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
                $resultados['errors'][] = __('Página sin título o slug, omitida.', 'flavor-chat-ia');
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
                    $resultados['errors'][] = sprintf(__('Error al actualizar %s.', 'flavor-chat-ia'), $titulo);
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
                    $resultados['errors'][] = sprintf(__('Error al crear %s.', 'flavor-chat-ia'), $titulo);
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
                    $resultados['errors'][] = sprintf(__('Error al actualizar landing %s.', 'flavor-chat-ia'), $titulo);
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
                    $resultados['errors'][] = sprintf(__('Error al crear landing %s.', 'flavor-chat-ia'), $titulo);
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
            'label' => __('Configuración General', 'flavor-chat-ia'),
            'count' => 0,
            'changes' => array(),
        );

        if (isset($datos_nuevos['profile'])) {
            $perfil_actual = $this->obtener_perfil_actual();
            if ($perfil_actual !== $datos_nuevos['profile']) {
                $analisis['changes'][] = sprintf(
                    __('Perfil: %s → %s', 'flavor-chat-ia'),
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
                    __('Nuevos módulos: %s', 'flavor-chat-ia'),
                    implode(', ', $modulos_nuevos)
                );
            }
            if (!empty($modulos_removidos)) {
                $analisis['changes'][] = sprintf(
                    __('Módulos a desactivar: %s', 'flavor-chat-ia'),
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
            'label' => __('Ajustes de Diseño', 'flavor-chat-ia'),
            'count' => 0,
            'changes' => array(),
        );

        $secciones = array(
            'theme' => __('Tema', 'flavor-chat-ia'),
            'colors' => __('Colores', 'flavor-chat-ia'),
            'typography' => __('Tipografía', 'flavor-chat-ia'),
            'spacing' => __('Espaciados', 'flavor-chat-ia'),
            'custom_css' => __('CSS personalizado', 'flavor-chat-ia'),
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
            'label' => __('Páginas', 'flavor-chat-ia'),
            'count' => count($datos_nuevos),
            'changes' => array(),
            'items' => array(),
        );

        foreach ($datos_nuevos as $pagina) {
            $titulo = isset($pagina['title']) ? $pagina['title'] : __('Sin título', 'flavor-chat-ia');
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
            'label' => __('Landing Pages', 'flavor-chat-ia'),
            'count' => count($datos_nuevos),
            'changes' => array(),
            'items' => array(),
        );

        foreach ($datos_nuevos as $landing) {
            $titulo = isset($landing['title']) ? $landing['title'] : __('Sin título', 'flavor-chat-ia');
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
            'label' => __('Roles Personalizados', 'flavor-chat-ia'),
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
                    $analisis['changes'][] = sprintf(__('Nuevo rol: %s', 'flavor-chat-ia'), $slug);
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
            'label' => __('Permisos', 'flavor-chat-ia'),
            'count' => 0,
            'changes' => array(),
        );

        if (isset($datos_nuevos['module_access'])) {
            $analisis['changes'][] = __('Configuración de acceso a módulos', 'flavor-chat-ia');
            $analisis['count']++;
        }

        if (isset($datos_nuevos['role_permissions'])) {
            $analisis['changes'][] = __('Permisos de roles', 'flavor-chat-ia');
            $analisis['count']++;
        }

        if (isset($datos_nuevos['custom_capabilities'])) {
            $analisis['changes'][] = __('Capabilities personalizadas', 'flavor-chat-ia');
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
            'label' => __('Datos (formato anterior)', 'flavor-chat-ia'),
            'sections' => array(),
        );

        $datos = $datos_importar['data'];

        if (isset($datos['settings'])) {
            $resumen['sections']['settings'] = array(
                'label' => __('Ajustes Generales', 'flavor-chat-ia'),
                'count' => count($datos['settings']),
            );
        }

        if (isset($datos['active_modules'])) {
            $resumen['sections']['active_modules'] = array(
                'label' => __('Módulos Activos', 'flavor-chat-ia'),
                'description' => implode(', ', $datos['active_modules']),
                'count' => count($datos['active_modules']),
            );
        }

        if (isset($datos['module_settings'])) {
            $resumen['sections']['module_settings'] = array(
                'label' => __('Configuración de Módulos', 'flavor-chat-ia'),
                'count' => count($datos['module_settings']),
            );
        }

        if (isset($datos['design_settings'])) {
            $resumen['sections']['design_settings'] = array(
                'label' => __('Ajustes de Diseño', 'flavor-chat-ia'),
                'count' => 1,
            );
        }

        if (isset($datos['pages'])) {
            $resumen['sections']['pages'] = array(
                'label' => __('Páginas', 'flavor-chat-ia'),
                'count' => count($datos['pages']),
            );
        }

        return $resumen;
    }

    // =========================================================================
    // RENDERIZADO DE LA PÁGINA
    // =========================================================================

    /**
     * Renderiza la página de exportar/importar
     */
    public function renderizar_pagina() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta página.', 'flavor-chat-ia'));
        }

        // Cargar la vista
        include FLAVOR_CHAT_IA_PATH . 'admin/views/export-import.php';
    }
}
