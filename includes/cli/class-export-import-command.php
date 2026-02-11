<?php
/**
 * Comandos WP-CLI para exportación/importación de configuración
 *
 * @package FlavorPlatform
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

/**
 * Gestiona la exportación e importación de configuración de Flavor Platform.
 *
 * ## EXAMPLES
 *
 *     # Exportar toda la configuración a un archivo
 *     wp flavor export --output=config.json
 *
 *     # Exportar solo configuración y diseño
 *     wp flavor export --sections=config,design --output=config-design.json
 *
 *     # Importar configuración
 *     wp flavor import config.json
 *
 *     # Importar en modo merge (combinar)
 *     wp flavor import config.json --mode=merge
 *
 *     # Importar solo lo que falta
 *     wp flavor import config.json --mode=only_missing
 *
 *     # Preview antes de importar
 *     wp flavor import config.json --dry-run
 *
 *     # Listar presets disponibles
 *     wp flavor presets
 *
 *     # Aplicar un preset
 *     wp flavor apply-preset grupo_consumo
 *
 * @package FlavorPlatform
 */
class Flavor_Export_Import_Command {

    /**
     * Exporta la configuración de Flavor Platform a un archivo JSON.
     *
     * ## OPTIONS
     *
     * [--output=<file>]
     * : Archivo de destino. Si no se especifica, se imprime en stdout.
     *
     * [--sections=<sections>]
     * : Secciones a exportar separadas por coma.
     * Opciones: config,design,pages,landings,roles,permissions
     * Default: todas las secciones.
     *
     * [--pretty]
     * : Formatear JSON con indentación.
     *
     * ## EXAMPLES
     *
     *     # Exportar todo a archivo
     *     wp flavor export --output=flavor-config.json
     *
     *     # Exportar solo configuración y diseño
     *     wp flavor export --sections=config,design --output=config.json --pretty
     *
     *     # Exportar a stdout
     *     wp flavor export
     *
     * @param array $argumentos
     * @param array $argumentos_asociativos
     */
    public function export($argumentos, $argumentos_asociativos) {
        $export_import = Flavor_Export_Import::get_instance();

        // Determinar secciones a exportar
        $secciones_solicitadas = isset($argumentos_asociativos['sections'])
            ? array_map('trim', explode(',', $argumentos_asociativos['sections']))
            : array('config', 'design', 'pages', 'landings', 'roles', 'permissions');

        $secciones_validas = array('config', 'design', 'pages', 'landings', 'roles', 'permissions');
        $secciones_exportar = array_intersect($secciones_solicitadas, $secciones_validas);

        if (empty($secciones_exportar)) {
            WP_CLI::error(__('No se especificaron secciones válidas para exportar.', 'flavor-chat-ia'));
        }

        WP_CLI::log(sprintf(
            __('Exportando secciones: %s', 'flavor-chat-ia'),
            implode(', ', $secciones_exportar)
        ));

        // Realizar exportación
        $datos_exportados = $this->crear_estructura_base_exportacion();

        foreach ($secciones_exportar as $seccion) {
            switch ($seccion) {
                case 'config':
                    $datos_exportados['config'] = $this->obtener_configuracion_completa();
                    break;
                case 'design':
                    $datos_exportados['design'] = $this->obtener_ajustes_diseno();
                    break;
                case 'pages':
                    $datos_exportados['pages'] = $this->obtener_paginas_builder();
                    break;
                case 'landings':
                    $datos_exportados['landings'] = $this->obtener_landings();
                    break;
                case 'roles':
                    $datos_exportados['roles'] = get_option('flavor_custom_roles', array());
                    break;
                case 'permissions':
                    $datos_exportados['permissions'] = $this->obtener_configuracion_permisos();
                    break;
            }
        }

        // Añadir checksum
        $datos_exportados['checksum'] = md5(wp_json_encode($datos_exportados));

        // Formatear JSON
        $formato_json_flags = JSON_UNESCAPED_UNICODE;
        if (isset($argumentos_asociativos['pretty'])) {
            $formato_json_flags |= JSON_PRETTY_PRINT;
        }
        $contenido_json = wp_json_encode($datos_exportados, $formato_json_flags);

        // Guardar o imprimir
        if (isset($argumentos_asociativos['output'])) {
            $ruta_archivo = $argumentos_asociativos['output'];

            // Crear directorio si no existe
            $directorio = dirname($ruta_archivo);
            if ($directorio !== '.' && !is_dir($directorio)) {
                wp_mkdir_p($directorio);
            }

            $bytes_escritos = file_put_contents($ruta_archivo, $contenido_json);

            if ($bytes_escritos === false) {
                WP_CLI::error(sprintf(
                    __('No se pudo escribir el archivo: %s', 'flavor-chat-ia'),
                    $ruta_archivo
                ));
            }

            WP_CLI::success(sprintf(
                __('Configuración exportada a: %s (%s bytes)', 'flavor-chat-ia'),
                $ruta_archivo,
                number_format($bytes_escritos)
            ));
        } else {
            WP_CLI::line($contenido_json);
        }
    }

    /**
     * Importa configuración desde un archivo JSON.
     *
     * ## OPTIONS
     *
     * <file>
     * : Archivo JSON a importar.
     *
     * [--mode=<mode>]
     * : Modo de importación.
     * ---
     * default: merge
     * options:
     *   - overwrite
     *   - merge
     *   - only_missing
     * ---
     *
     * [--sections=<sections>]
     * : Secciones a importar separadas por coma.
     * Default: todas las secciones disponibles en el archivo.
     *
     * [--dry-run]
     * : Solo mostrar qué cambios se harían, sin aplicarlos.
     *
     * [--yes]
     * : Confirmar importación sin preguntar.
     *
     * ## EXAMPLES
     *
     *     # Importar todo en modo merge
     *     wp flavor import config.json
     *
     *     # Preview de cambios
     *     wp flavor import config.json --dry-run
     *
     *     # Sobrescribir todo
     *     wp flavor import config.json --mode=overwrite --yes
     *
     *     # Importar solo diseño
     *     wp flavor import config.json --sections=design
     *
     * @param array $argumentos
     * @param array $argumentos_asociativos
     */
    public function import($argumentos, $argumentos_asociativos) {
        if (empty($argumentos[0])) {
            WP_CLI::error(__('Debes especificar un archivo JSON para importar.', 'flavor-chat-ia'));
        }

        $ruta_archivo = $argumentos[0];

        if (!file_exists($ruta_archivo)) {
            WP_CLI::error(sprintf(__('Archivo no encontrado: %s', 'flavor-chat-ia'), $ruta_archivo));
        }

        // Leer archivo
        $contenido_json = file_get_contents($ruta_archivo);
        if ($contenido_json === false) {
            WP_CLI::error(__('No se pudo leer el archivo.', 'flavor-chat-ia'));
        }

        $datos_json = json_decode($contenido_json, true);
        if ($datos_json === null) {
            WP_CLI::error(__('El archivo no contiene JSON válido.', 'flavor-chat-ia'));
        }

        $export_import = Flavor_Export_Import::get_instance();

        // Validar
        $resultado_validacion = $export_import->validate_import($datos_json);
        if (is_wp_error($resultado_validacion)) {
            WP_CLI::error($resultado_validacion->get_error_message());
        }

        // Obtener preview
        $datos_preview = $export_import->preview_import($datos_json);
        if (is_wp_error($datos_preview)) {
            WP_CLI::error($datos_preview->get_error_message());
        }

        // Mostrar información del archivo
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%GInformación del archivo:%n'));
        WP_CLI::log(sprintf('  Versión: %s', $datos_preview['metadata']['version']));
        WP_CLI::log(sprintf('  Exportado: %s', $datos_preview['metadata']['exported_at']));
        WP_CLI::log(sprintf('  Sitio origen: %s', $datos_preview['metadata']['site_url']));
        WP_CLI::log('');

        // Mostrar warnings
        if (!empty($datos_preview['warnings'])) {
            WP_CLI::log(WP_CLI::colorize('%YAdvertencias:%n'));
            foreach ($datos_preview['warnings'] as $aviso) {
                WP_CLI::log('  - ' . $aviso);
            }
            WP_CLI::log('');
        }

        // Determinar secciones a importar
        $secciones_disponibles = array_keys($datos_preview['sections']);
        $secciones_importar = $secciones_disponibles;

        if (isset($argumentos_asociativos['sections'])) {
            $secciones_solicitadas = array_map('trim', explode(',', $argumentos_asociativos['sections']));
            $secciones_importar = array_intersect($secciones_solicitadas, $secciones_disponibles);

            if (empty($secciones_importar)) {
                WP_CLI::error(__('Ninguna de las secciones especificadas está disponible en el archivo.', 'flavor-chat-ia'));
            }
        }

        // Mostrar secciones a importar
        WP_CLI::log(WP_CLI::colorize('%GSecciones a importar:%n'));
        foreach ($secciones_importar as $clave_seccion) {
            if (isset($datos_preview['sections'][$clave_seccion])) {
                $info_seccion = $datos_preview['sections'][$clave_seccion];
                WP_CLI::log(sprintf(
                    '  [%s] %s (%d elementos)',
                    $clave_seccion,
                    $info_seccion['label'],
                    $info_seccion['count']
                ));

                if (!empty($info_seccion['changes'])) {
                    foreach ($info_seccion['changes'] as $cambio) {
                        WP_CLI::log('      - ' . $cambio);
                    }
                }

                if (!empty($info_seccion['items'])) {
                    $items_mostrados = 0;
                    foreach ($info_seccion['items'] as $item) {
                        if ($items_mostrados >= 5) {
                            $restantes = count($info_seccion['items']) - 5;
                            WP_CLI::log(sprintf('      ... y %d más', $restantes));
                            break;
                        }
                        $icono_accion = $item['action'] === 'create' ? '+' : '~';
                        WP_CLI::log(sprintf('      %s %s', $icono_accion, $item['title']));
                        $items_mostrados++;
                    }
                }
            }
        }
        WP_CLI::log('');

        $modo_importacion = isset($argumentos_asociativos['mode']) ? $argumentos_asociativos['mode'] : 'merge';
        $modos_validos = array('overwrite', 'merge', 'only_missing');

        if (!in_array($modo_importacion, $modos_validos, true)) {
            WP_CLI::error(sprintf(
                __('Modo inválido. Opciones: %s', 'flavor-chat-ia'),
                implode(', ', $modos_validos)
            ));
        }

        WP_CLI::log(sprintf('Modo de importación: %s', $modo_importacion));
        WP_CLI::log('');

        // Dry run
        if (isset($argumentos_asociativos['dry-run'])) {
            WP_CLI::success(__('Dry-run completado. No se realizaron cambios.', 'flavor-chat-ia'));
            return;
        }

        // Confirmación
        WP_CLI::confirm(__('¿Deseas proceder con la importación?', 'flavor-chat-ia'), $argumentos_asociativos);

        // Ejecutar importación
        WP_CLI::log(__('Importando...', 'flavor-chat-ia'));

        $resultado = $export_import->import_config($datos_json, array(
            'mode' => $modo_importacion,
            'sections' => $secciones_importar,
        ));

        if (is_wp_error($resultado)) {
            WP_CLI::error($resultado->get_error_message());
        }

        // Mostrar resultados
        WP_CLI::log('');
        WP_CLI::log(WP_CLI::colorize('%GResultados:%n'));

        if (!empty($resultado['imported'])) {
            foreach ($resultado['imported'] as $seccion => $stats) {
                $detalles = array();
                if (isset($stats['created']) && $stats['created'] > 0) {
                    $detalles[] = sprintf('%d creados', $stats['created']);
                }
                if (isset($stats['updated']) && $stats['updated'] > 0) {
                    $detalles[] = sprintf('%d actualizados', $stats['updated']);
                }
                if (isset($stats['skipped']) && $stats['skipped'] > 0) {
                    $detalles[] = sprintf('%d omitidos', $stats['skipped']);
                }

                WP_CLI::log(sprintf('  %s: %s', $seccion, implode(', ', $detalles) ?: 'OK'));
            }
        }

        if (!empty($resultado['skipped'])) {
            WP_CLI::log('');
            WP_CLI::log(WP_CLI::colorize('%YSecciones omitidas (no disponibles en archivo):%n'));
            WP_CLI::log('  ' . implode(', ', $resultado['skipped']));
        }

        if (!empty($resultado['errors'])) {
            WP_CLI::log('');
            WP_CLI::log(WP_CLI::colorize('%RErrores:%n'));
            foreach ($resultado['errors'] as $seccion => $mensaje_error) {
                WP_CLI::log(sprintf('  %s: %s', $seccion, $mensaje_error));
            }
        }

        WP_CLI::log('');

        if ($resultado['success']) {
            WP_CLI::success(__('Importación completada correctamente.', 'flavor-chat-ia'));
        } else {
            WP_CLI::warning(__('Importación completada con algunos errores.', 'flavor-chat-ia'));
        }
    }

    /**
     * Lista los presets disponibles.
     *
     * ## OPTIONS
     *
     * [--format=<format>]
     * : Formato de salida (table, json, csv, yaml). Default: table.
     *
     * ## EXAMPLES
     *
     *     wp flavor presets
     *     wp flavor presets --format=json
     *
     * @param array $argumentos
     * @param array $argumentos_asociativos
     */
    public function presets($argumentos, $argumentos_asociativos) {
        $export_import = Flavor_Export_Import::get_instance();

        // Obtener presets via reflection ya que son privados
        $reflector = new ReflectionClass($export_import);
        $propiedad = $reflector->getProperty('presets_disponibles');
        $propiedad->setAccessible(true);
        $presets_disponibles = $propiedad->getValue($export_import);

        if (empty($presets_disponibles)) {
            WP_CLI::log(__('No hay presets disponibles.', 'flavor-chat-ia'));
            return;
        }

        $formato = isset($argumentos_asociativos['format']) ? $argumentos_asociativos['format'] : 'table';

        $datos_tabla = array();
        foreach ($presets_disponibles as $id_preset => $datos_preset) {
            $modulos_activos = isset($datos_preset['config']['active_modules'])
                ? implode(', ', $datos_preset['config']['active_modules'])
                : '';

            $datos_tabla[] = array(
                'id' => $id_preset,
                'nombre' => $datos_preset['nombre'],
                'descripcion' => $datos_preset['descripcion'],
                'modulos' => $modulos_activos,
            );
        }

        WP_CLI\Utils\format_items($formato, $datos_tabla, array('id', 'nombre', 'descripcion', 'modulos'));
    }

    /**
     * Aplica un preset de configuración.
     *
     * ## OPTIONS
     *
     * <preset_id>
     * : ID del preset a aplicar.
     *
     * [--yes]
     * : Confirmar aplicación sin preguntar.
     *
     * ## EXAMPLES
     *
     *     wp flavor apply-preset grupo_consumo
     *     wp flavor apply-preset asociacion --yes
     *
     * @param array $argumentos
     * @param array $argumentos_asociativos
     */
    public function apply_preset($argumentos, $argumentos_asociativos) {
        if (empty($argumentos[0])) {
            WP_CLI::error(__('Debes especificar el ID del preset.', 'flavor-chat-ia'));
        }

        $id_preset = sanitize_key($argumentos[0]);
        $export_import = Flavor_Export_Import::get_instance();

        // Obtener presets
        $reflector = new ReflectionClass($export_import);
        $propiedad = $reflector->getProperty('presets_disponibles');
        $propiedad->setAccessible(true);
        $presets_disponibles = $propiedad->getValue($export_import);

        if (!isset($presets_disponibles[$id_preset])) {
            WP_CLI::error(sprintf(
                __('Preset "%s" no encontrado. Usa "wp flavor presets" para ver los disponibles.', 'flavor-chat-ia'),
                $id_preset
            ));
        }

        $datos_preset = $presets_disponibles[$id_preset];

        WP_CLI::log(sprintf(__('Preset: %s', 'flavor-chat-ia'), $datos_preset['nombre']));
        WP_CLI::log(sprintf(__('Descripción: %s', 'flavor-chat-ia'), $datos_preset['descripcion']));

        if (isset($datos_preset['config']['active_modules'])) {
            WP_CLI::log(sprintf(
                __('Módulos: %s', 'flavor-chat-ia'),
                implode(', ', $datos_preset['config']['active_modules'])
            ));
        }

        WP_CLI::log('');
        WP_CLI::confirm(__('¿Deseas aplicar este preset?', 'flavor-chat-ia'), $argumentos_asociativos);

        // Aplicar preset
        WP_CLI::log(__('Aplicando preset...', 'flavor-chat-ia'));

        // Aplicar configuración
        if (isset($datos_preset['config'])) {
            $ajustes_actuales = get_option('flavor_chat_ia_settings', array());

            if (isset($datos_preset['config']['profile'])) {
                $ajustes_actuales['app_profile'] = $datos_preset['config']['profile'];
            }

            if (isset($datos_preset['config']['active_modules'])) {
                $ajustes_actuales['active_modules'] = $datos_preset['config']['active_modules'];
            }

            update_option('flavor_chat_ia_settings', $ajustes_actuales);
            WP_CLI::log('  - Configuración general actualizada');
        }

        // Aplicar diseño
        if (isset($datos_preset['design'])) {
            $ajustes_diseno = get_option('flavor_design_settings', array());
            $ajustes_diseno = array_merge($ajustes_diseno, $datos_preset['design']);
            update_option('flavor_design_settings', $ajustes_diseno);
            WP_CLI::log('  - Ajustes de diseño actualizados');
        }

        // Aplicar visibilidad de módulos
        if (isset($datos_preset['config']['modules_visibility'])) {
            update_option('flavor_modules_visibility', $datos_preset['config']['modules_visibility']);
            WP_CLI::log('  - Visibilidad de módulos actualizada');
        }

        WP_CLI::success(sprintf(__('Preset "%s" aplicado correctamente.', 'flavor-chat-ia'), $datos_preset['nombre']));
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Crea la estructura base de exportación
     *
     * @return array
     */
    private function crear_estructura_base_exportacion() {
        return array(
            'version' => Flavor_Export_Import::EXPORT_FORMAT_VERSION,
            'plugin' => 'flavor-platform',
            'exported_at' => current_time('c'),
            'site_url' => get_site_url(),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'php_version' => phpversion(),
        );
    }

    /**
     * Obtiene la configuración completa
     *
     * @return array
     */
    private function obtener_configuracion_completa() {
        $ajustes = get_option('flavor_chat_ia_settings', array());

        return array(
            'profile' => isset($ajustes['app_profile']) ? $ajustes['app_profile'] : 'personalizado',
            'active_modules' => isset($ajustes['active_modules']) ? $ajustes['active_modules'] : array(),
            'modules_visibility' => get_option('flavor_modules_visibility', array()),
            'modules_settings' => $this->obtener_configuracion_modulos(),
            'general_settings' => $this->eliminar_claves_secretas($ajustes),
        );
    }

    /**
     * Obtiene la configuración de módulos
     *
     * @return array
     */
    private function obtener_configuracion_modulos() {
        global $wpdb;

        $prefijo_escapado = $wpdb->esc_like(Flavor_Export_Import::MODULE_OPTIONS_PREFIX) . '%';
        $resultados = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $prefijo_escapado
            )
        );

        $configuracion = array();
        foreach ($resultados as $opcion) {
            $nombre_modulo = str_replace(Flavor_Export_Import::MODULE_OPTIONS_PREFIX, '', $opcion->option_name);
            $valor = maybe_unserialize($opcion->option_value);

            if (is_array($valor)) {
                $valor = $this->eliminar_claves_secretas($valor);
            }

            $configuracion[$nombre_modulo] = $valor;
        }

        return $configuracion;
    }

    /**
     * Obtiene los ajustes de diseño
     *
     * @return array
     */
    private function obtener_ajustes_diseno() {
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

        $consulta = new WP_Query($argumentos_consulta);
        $paginas = array();

        if ($consulta->have_posts()) {
            while ($consulta->have_posts()) {
                $consulta->the_post();
                $id_post = get_the_ID();

                $paginas[] = array(
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name', $id_post),
                    'status' => get_post_status(),
                    'content' => get_the_content(),
                    'template' => get_page_template_slug($id_post),
                    'post_type' => get_post_type(),
                );
            }
            wp_reset_postdata();
        }

        return $paginas;
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

        $consulta = new WP_Query($argumentos);
        $landings = array();

        if ($consulta->have_posts()) {
            while ($consulta->have_posts()) {
                $consulta->the_post();
                $id_landing = get_the_ID();

                $landings[] = array(
                    'id' => $id_landing,
                    'title' => get_the_title(),
                    'slug' => get_post_field('post_name', $id_landing),
                    'status' => get_post_status(),
                    'structure' => get_post_meta($id_landing, '_web_builder_content', true),
                );
            }
            wp_reset_postdata();
        }

        return $landings;
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

        $claves_secretas = array(
            'api_key', 'claude_api_key', 'openai_api_key', 'deepseek_api_key',
            'mistral_api_key', 'password', 'secret', 'token', 'private_key', 'client_secret'
        );

        $datos_limpios = array();

        foreach ($datos as $clave => $valor) {
            $es_secreto = false;

            foreach ($claves_secretas as $patron) {
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
}

// Registrar comandos
if (defined('WP_CLI') && WP_CLI) {
    WP_CLI::add_command('flavor export', array('Flavor_Export_Import_Command', 'export'));
    WP_CLI::add_command('flavor import', array('Flavor_Export_Import_Command', 'import'));
    WP_CLI::add_command('flavor presets', array('Flavor_Export_Import_Command', 'presets'));
    WP_CLI::add_command('flavor apply-preset', array('Flavor_Export_Import_Command', 'apply_preset'));
}
