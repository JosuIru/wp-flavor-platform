<?php
/**
 * Exportar / Importar configuraciones del plugin Flavor Chat IA
 *
 * @package FlavorChatIA
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_Export_Import {

    private static $instance = null;
    const PAGE_SLUG = 'flavor-export-import';
    const EXPORT_FORMAT_VERSION = '2.0.0';
    const MODULE_OPTIONS_PREFIX = 'flavor_chat_ia_module_';

    private $claves_secretas = array(
        'api_key',
        'claude_api_key',
        'openai_api_key',
        'deepseek_api_key',
        'mistral_api_key',
        'password',
        'secret',
        'token',
    );

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action( 'admin_enqueue_scripts', array( $this, 'cargar_assets_admin' ) );
        add_action( 'wp_ajax_flavor_export_data', array( $this, 'ajax_exportar_datos' ) );
        add_action( 'wp_ajax_flavor_import_preview', array( $this, 'ajax_previsualizar_importacion' ) );
        add_action( 'wp_ajax_flavor_import_apply', array( $this, 'ajax_aplicar_importacion' ) );
    }

    public function registrar_pagina_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            'Exportar / Importar',
            'Exportar / Importar',
            'manage_options',
            self::PAGE_SLUG,
            array( $this, 'renderizar_pagina' )
        );
    }

    public function cargar_assets_admin( $sufijo_hook ) {
        $sufijo_hook = (string) $sufijo_hook;
        if ( strpos( $sufijo_hook, self::PAGE_SLUG ) === false ) {
            return;
        }
        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style( 'flavor-export-import-css', FLAVOR_CHAT_IA_URL . "admin/css/export-import{$sufijo_asset}.css", array(), FLAVOR_CHAT_IA_VERSION );
        wp_enqueue_script( 'flavor-export-import-js', FLAVOR_CHAT_IA_URL . "admin/js/export-import{$sufijo_asset}.js", array( 'jquery' ), FLAVOR_CHAT_IA_VERSION, true );
        wp_localize_script( 'flavor-export-import-js', 'flavorExportImport', array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'flavor_export_import_nonce' ),
            'strings' => array(
                'exportando'       => 'Exportando datos...',
                'exportCompletada' => 'Exportacion completada',
                'importando'       => 'Importando datos...',
                'importCompletada' => 'Importacion completada correctamente',
                'errorExport'      => 'Error al exportar los datos',
                'errorImport'      => 'Error al importar los datos',
                'errorArchivo'     => 'Por favor, selecciona un archivo JSON',
                'confirmarImport'  => 'Seguro que deseas aplicar esta importacion? Los datos existentes seran sobrescritos.',
                'previsualizando'  => 'Analizando archivo...',
                'sinSeleccion'     => 'Selecciona al menos una opcion para exportar',
            ),
        ) );
    }

    // =========================================================================
    // EXPORTACION
    // =========================================================================

    public function ajax_exportar_datos() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos suficientes.' ) );
        }
        check_ajax_referer( 'flavor_export_import_nonce', 'nonce' );

        $secciones_exportar = isset( $_POST['secciones'] ) ? array_map( 'sanitize_text_field', (array) $_POST['secciones'] ) : array();
        if ( empty( $secciones_exportar ) ) {
            wp_send_json_error( array( 'message' => 'No se selecciono ninguna seccion para exportar.' ) );
        }

        $datos_exportados = array(
            'version'     => self::EXPORT_FORMAT_VERSION,
            'plugin'      => 'flavor-chat-ia',
            'exported_at' => current_time( 'c' ),
            'site_url'    => get_site_url(),
            'data'        => array(),
        );

        foreach ( $secciones_exportar as $nombre_seccion ) {
            switch ( $nombre_seccion ) {
                case 'settings':
                    $datos_exportados['data']['settings'] = $this->obtener_ajustes_generales();
                    break;
                case 'active_modules':
                    $datos_exportados['data']['active_modules'] = $this->obtener_modulos_activos();
                    break;
                case 'module_settings':
                    $datos_exportados['data']['module_settings'] = $this->obtener_configuracion_modulos();
                    break;
                case 'design_settings':
                    $datos_exportados['data']['design_settings'] = $this->obtener_ajustes_diseno();
                    break;
                case 'pages':
                    $datos_exportados['data']['pages'] = $this->obtener_paginas_builder();
                    break;
            }
        }

        wp_send_json_success( array(
            'data'     => $datos_exportados,
            'filename' => 'flavor-chat-ia-export-' . gmdate( 'Y-m-d-His' ) . '.json',
        ) );
    }

    private function obtener_ajustes_generales() {
        $ajustes_completos = get_option( 'flavor_chat_ia_settings', array() );
        return $this->eliminar_claves_secretas( $ajustes_completos );
    }

    private function obtener_modulos_activos() {
        $ajustes_plugin = get_option( 'flavor_chat_ia_settings', array() );
        return isset( $ajustes_plugin['active_modules'] ) ? $ajustes_plugin['active_modules'] : array();
    }

    private function obtener_configuracion_modulos() {
        global $wpdb;
        $nombre_prefijo_escapado = $wpdb->esc_like( self::MODULE_OPTIONS_PREFIX ) . '%';
        $opciones_modulos_raw = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s",
                $nombre_prefijo_escapado
            )
        );
        $configuracion_modulos = array();
        foreach ( $opciones_modulos_raw as $fila_opcion ) {
            $nombre_modulo = str_replace( self::MODULE_OPTIONS_PREFIX, '', $fila_opcion->option_name );
            $valor_deserializado = maybe_unserialize( $fila_opcion->option_value );
            if ( is_array( $valor_deserializado ) ) {
                $valor_deserializado = $this->eliminar_claves_secretas( $valor_deserializado );
            }
            $configuracion_modulos[ $nombre_modulo ] = $valor_deserializado;
        }
        $configuracion_granular = get_option( 'flavor_module_config', array() );
        if ( ! empty( $configuracion_granular ) && is_array( $configuracion_granular ) ) {
            $configuracion_modulos['_module_config'] = $this->eliminar_claves_secretas( $configuracion_granular );
        }
        return $configuracion_modulos;
    }

    private function obtener_ajustes_diseno() {
        $ajustes_diseno = get_option( 'flavor_design_settings', array() );
        $ajustes_tema = get_option( 'flavor_theme_settings', array() );
        return array( 'design' => $ajustes_diseno, 'theme' => $ajustes_tema );
    }

    private function obtener_paginas_builder() {
        $argumentos_consulta = array(
            'post_type'      => 'flavor_landing',
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'draft', 'private' ),
        );
        $paginas_query = new WP_Query( $argumentos_consulta );
        $datos_paginas = array();
        if ( $paginas_query->have_posts() ) {
            while ( $paginas_query->have_posts() ) {
                $paginas_query->the_post();
                $identificador_pagina = get_the_ID();
                $meta_completa = get_post_meta( $identificador_pagina );
                $meta_limpia = array();
                foreach ( $meta_completa as $clave_meta => $valor_meta ) {
                    if ( strpos( $clave_meta, '_flavor' ) === 0
                        || strpos( $clave_meta, 'flavor_' ) === 0
                        || strpos( $clave_meta, '_page_builder' ) === 0
                        || strpos( $clave_meta, 'page_builder' ) === 0
                        || strpos( $clave_meta, '_web_builder' ) === 0
                        || strpos( $clave_meta, 'web_builder' ) === 0
                        || $clave_meta === '_thumbnail_id'
                    ) {
                        $meta_limpia[ $clave_meta ] = maybe_unserialize( $valor_meta[0] );
                    }
                }
                $datos_paginas[] = array(
                    'title'     => get_the_title(),
                    'slug'      => get_post_field( 'post_name', $identificador_pagina ),
                    'status'    => get_post_status(),
                    'content'   => get_the_content(),
                    'post_meta' => $meta_limpia,
                    'post_type' => 'flavor_landing',
                );
            }
            wp_reset_postdata();
        }
        return $datos_paginas;
    }

    private function eliminar_claves_secretas( $datos ) {
        if ( ! is_array( $datos ) ) { return $datos; }
        $datos_limpios = array();
        foreach ( $datos as $clave => $valor ) {
            $es_secreto = false;
            foreach ( $this->claves_secretas as $patron_secreto ) {
                if ( stripos( $clave, $patron_secreto ) !== false ) {
                    $es_secreto = true;
                    break;
                }
            }
            if ( $es_secreto ) {
                $datos_limpios[ $clave ] = '***REDACTED***';
                continue;
            }
            if ( is_array( $valor ) ) {
                $datos_limpios[ $clave ] = $this->eliminar_claves_secretas( $valor );
            } else {
                $datos_limpios[ $clave ] = $valor;
            }
        }
        return $datos_limpios;
    }

    // =========================================================================
    // IMPORTACION - Previsualizacion
    // =========================================================================

    public function ajax_previsualizar_importacion() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos suficientes.' ) );
        }
        check_ajax_referer( 'flavor_export_import_nonce', 'nonce' );
        if ( empty( $_FILES['archivo_importacion'] ) ) {
            wp_send_json_error( array( 'message' => 'No se recibio ningun archivo.' ) );
        }
        $archivo_subido = $_FILES['archivo_importacion'];
        $informacion_archivo = wp_check_filetype( $archivo_subido['name'], array( 'json' => 'application/json' ) );
        if ( empty( $informacion_archivo['ext'] ) ) {
            wp_send_json_error( array( 'message' => 'El archivo debe ser de tipo JSON.' ) );
        }
        $contenido_json = file_get_contents( $archivo_subido['tmp_name'] );
        if ( false === $contenido_json ) {
            wp_send_json_error( array( 'message' => 'No se pudo leer el archivo.' ) );
        }
        $datos_importacion = json_decode( $contenido_json, true );
        if ( null === $datos_importacion ) {
            wp_send_json_error( array( 'message' => 'El archivo JSON no es valido.' ) );
        }
        $resultado_validacion = $this->validar_estructura_json( $datos_importacion );
        if ( is_wp_error( $resultado_validacion ) ) {
            wp_send_json_error( array( 'message' => $resultado_validacion->get_error_message() ) );
        }
        $resumen_previo = $this->generar_resumen_importacion( $datos_importacion );
        $clave_transitoria = 'flavor_import_' . get_current_user_id();
        set_transient( $clave_transitoria, $datos_importacion, HOUR_IN_SECONDS );
        wp_send_json_success( array(
            'resumen'  => $resumen_previo,
            'metadata' => array(
                'version'     => $datos_importacion['version'],
                'exported_at' => $datos_importacion['exported_at'],
                'site_url'    => $datos_importacion['site_url'],
            ),
        ) );
    }

    private function validar_estructura_json( $datos_json ) {
        $campos_requeridos = array( 'version', 'plugin', 'exported_at', 'data' );
        foreach ( $campos_requeridos as $campo_obligatorio ) {
            if ( ! isset( $datos_json[ $campo_obligatorio ] ) ) {
                return new WP_Error( 'estructura_invalida', sprintf( 'Falta el campo obligatorio: %s', $campo_obligatorio ) );
            }
        }
        if ( 'flavor-chat-ia' !== $datos_json['plugin'] ) {
            return new WP_Error( 'plugin_incorrecto', 'El archivo no corresponde a Flavor Chat IA.' );
        }
        if ( version_compare( $datos_json['version'], '1.0.0', '<' ) ) {
            return new WP_Error( 'version_incompatible', 'Version del archivo no compatible.' );
        }
        if ( ! is_array( $datos_json['data'] ) ) {
            return new WP_Error( 'datos_invalidos', 'El campo data debe ser un objeto valido.' );
        }
        return true;
    }

    private function generar_resumen_importacion( $datos_importar ) {
        $resumen = array();
        $datos_contenido = $datos_importar['data'];
        if ( isset( $datos_contenido['settings'] ) && ! empty( $datos_contenido['settings'] ) ) {
            $cantidad_ajustes = count( $datos_contenido['settings'] );
            $resumen['settings'] = array( 'label' => 'Ajustes Generales', 'description' => sprintf( '%d ajustes del plugin (sin claves API)', $cantidad_ajustes ), 'count' => $cantidad_ajustes );
        }
        if ( isset( $datos_contenido['active_modules'] ) && ! empty( $datos_contenido['active_modules'] ) ) {
            $resumen['active_modules'] = array( 'label' => 'Modulos Activos', 'description' => sprintf( 'Modulos: %s', implode( ', ', $datos_contenido['active_modules'] ) ), 'count' => count( $datos_contenido['active_modules'] ) );
        }
        if ( isset( $datos_contenido['module_settings'] ) && ! empty( $datos_contenido['module_settings'] ) ) {
            $nombres_modulos = array_keys( $datos_contenido['module_settings'] );
            $resumen['module_settings'] = array( 'label' => 'Configuracion de Modulos', 'description' => sprintf( '%d modulos: %s', count( $datos_contenido['module_settings'] ), implode( ', ', $nombres_modulos ) ), 'count' => count( $datos_contenido['module_settings'] ) );
        }
        if ( isset( $datos_contenido['design_settings'] ) && ! empty( $datos_contenido['design_settings'] ) ) {
            $partes = array();
            if ( ! empty( $datos_contenido['design_settings']['design'] ) ) { $partes[] = 'diseno'; }
            if ( ! empty( $datos_contenido['design_settings']['theme'] ) ) { $partes[] = 'tema'; }
            $resumen['design_settings'] = array( 'label' => 'Ajustes de Diseno', 'description' => sprintf( 'Incluye: %s', implode( ', ', $partes ) ), 'count' => count( $partes ) );
        }
        if ( isset( $datos_contenido['pages'] ) && ! empty( $datos_contenido['pages'] ) ) {
            $titulos_paginas = wp_list_pluck( $datos_contenido['pages'], 'title' );
            $resumen['pages'] = array( 'label' => 'Paginas del Page Builder', 'description' => sprintf( '%d paginas: %s', count( $datos_contenido['pages'] ), implode( ', ', array_slice( $titulos_paginas, 0, 5 ) ) ), 'count' => count( $datos_contenido['pages'] ) );
        }
        return $resumen;
    }

    // =========================================================================
    // IMPORTACION - Aplicar
    // =========================================================================

    public function ajax_aplicar_importacion() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'No tienes permisos suficientes.' ) );
        }
        check_ajax_referer( 'flavor_export_import_nonce', 'nonce' );
        $clave_transitoria = 'flavor_import_' . get_current_user_id();
        $datos_para_importar = get_transient( $clave_transitoria );
        if ( false === $datos_para_importar ) {
            wp_send_json_error( array( 'message' => 'Los datos de importacion han expirado. Sube el archivo de nuevo.' ) );
        }
        $secciones_a_importar = isset( $_POST['secciones'] ) ? array_map( 'sanitize_text_field', (array) $_POST['secciones'] ) : array();
        if ( empty( $secciones_a_importar ) ) {
            wp_send_json_error( array( 'message' => 'No se selecciono ninguna seccion para importar.' ) );
        }
        $resultados_importacion = array();
        $datos_contenido = $datos_para_importar['data'];
        foreach ( $secciones_a_importar as $nombre_seccion ) {
            if ( ! isset( $datos_contenido[ $nombre_seccion ] ) ) { continue; }
            switch ( $nombre_seccion ) {
                case 'settings':
                    $resultados_importacion['settings'] = $this->importar_ajustes_generales( $datos_contenido['settings'] );
                    break;
                case 'active_modules':
                    $resultados_importacion['active_modules'] = $this->importar_modulos_activos( $datos_contenido['active_modules'] );
                    break;
                case 'module_settings':
                    $resultados_importacion['module_settings'] = $this->importar_configuracion_modulos( $datos_contenido['module_settings'] );
                    break;
                case 'design_settings':
                    $resultados_importacion['design_settings'] = $this->importar_ajustes_diseno( $datos_contenido['design_settings'] );
                    break;
                case 'pages':
                    $resultados_importacion['pages'] = $this->importar_paginas_builder( $datos_contenido['pages'] );
                    break;
            }
        }
        delete_transient( $clave_transitoria );
        wp_send_json_success( array( 'message' => 'Importacion completada correctamente.', 'resultados' => $resultados_importacion ) );
    }

    private function importar_ajustes_generales( $ajustes_nuevos ) {
        $ajustes_existentes = get_option( 'flavor_chat_ia_settings', array() );
        $ajustes_fusionados = $this->fusionar_preservando_secretos( $ajustes_existentes, $ajustes_nuevos );
        $ajustes_sanitizados = $this->sanitizar_datos_recursivo( $ajustes_fusionados );
        update_option( 'flavor_chat_ia_settings', $ajustes_sanitizados );
        return array( 'status' => 'success', 'message' => 'Ajustes generales importados correctamente.', 'count' => count( $ajustes_nuevos ) );
    }

    private function importar_modulos_activos( $modulos_nuevos ) {
        if ( ! is_array( $modulos_nuevos ) ) { return array( 'status' => 'error', 'message' => 'Formato no valido.' ); }
        $ajustes_actuales = get_option( 'flavor_chat_ia_settings', array() );
        $modulos_sanitizados = array_map( 'sanitize_text_field', $modulos_nuevos );
        $ajustes_actuales['active_modules'] = $modulos_sanitizados;
        update_option( 'flavor_chat_ia_settings', $ajustes_actuales );
        return array( 'status' => 'success', 'message' => sprintf( '%d modulos activados.', count( $modulos_sanitizados ) ), 'count' => count( $modulos_sanitizados ) );
    }

    private function importar_configuracion_modulos( $configuraciones_modulos ) {
        if ( ! is_array( $configuraciones_modulos ) ) { return array( 'status' => 'error', 'message' => 'Formato no valido.' ); }
        $contador_importados = 0;
        foreach ( $configuraciones_modulos as $nombre_modulo => $valor_configuracion ) {
            $nombre_modulo_sanitizado = sanitize_text_field( $nombre_modulo );
            if ( '_module_config' === $nombre_modulo_sanitizado ) {
                $config_existente = get_option( 'flavor_module_config', array() );
                $config_fusionada = $this->fusionar_preservando_secretos( $config_existente, $valor_configuracion );
                update_option( 'flavor_module_config', $this->sanitizar_datos_recursivo( $config_fusionada ) );
                $contador_importados++;
                continue;
            }
            $nombre_opcion = self::MODULE_OPTIONS_PREFIX . $nombre_modulo_sanitizado;
            $valor_existente = get_option( $nombre_opcion, array() );
            if ( is_array( $valor_configuracion ) && is_array( $valor_existente ) ) {
                $valor_configuracion = $this->fusionar_preservando_secretos( $valor_existente, $valor_configuracion );
            }
            update_option( $nombre_opcion, $this->sanitizar_datos_recursivo( $valor_configuracion ) );
            $contador_importados++;
        }
        return array( 'status' => 'success', 'message' => sprintf( '%d configuraciones de modulos importadas.', $contador_importados ), 'count' => $contador_importados );
    }

    private function importar_ajustes_diseno( $ajustes_diseno_nuevos ) {
        if ( ! is_array( $ajustes_diseno_nuevos ) ) { return array( 'status' => 'error', 'message' => 'Formato no valido.' ); }
        $contador_actualizados = 0;
        if ( isset( $ajustes_diseno_nuevos['design'] ) && ! empty( $ajustes_diseno_nuevos['design'] ) ) {
            update_option( 'flavor_design_settings', $this->sanitizar_datos_recursivo( $ajustes_diseno_nuevos['design'] ) );
            $contador_actualizados++;
        }
        if ( isset( $ajustes_diseno_nuevos['theme'] ) && ! empty( $ajustes_diseno_nuevos['theme'] ) ) {
            update_option( 'flavor_theme_settings', $this->sanitizar_datos_recursivo( $ajustes_diseno_nuevos['theme'] ) );
            $contador_actualizados++;
        }
        return array( 'status' => 'success', 'message' => 'Ajustes de diseno importados correctamente.', 'count' => $contador_actualizados );
    }

    private function importar_paginas_builder( $paginas_importar ) {
        if ( ! is_array( $paginas_importar ) ) { return array( 'status' => 'error', 'message' => 'Formato no valido.' ); }
        $paginas_creadas = 0;
        $paginas_actualizadas = 0;
        $errores_paginas = array();
        foreach ( $paginas_importar as $datos_pagina ) {
            if ( ! isset( $datos_pagina['title'] ) || ! isset( $datos_pagina['slug'] ) ) {
                $errores_paginas[] = 'Pagina sin titulo o slug, omitida.';
                continue;
            }
            $titulo_sanitizado = sanitize_text_field( $datos_pagina['title'] );
            $slug_sanitizado = sanitize_title( $datos_pagina['slug'] );
            $estado_publicacion = isset( $datos_pagina['status'] ) ? sanitize_text_field( $datos_pagina['status'] ) : 'draft';
            $contenido_pagina = isset( $datos_pagina['content'] ) ? wp_kses_post( $datos_pagina['content'] ) : '';
            $pagina_existente = get_page_by_path( $slug_sanitizado, OBJECT, 'flavor_landing' );
            if ( $pagina_existente ) {
                $resultado = wp_update_post( array(
                    'ID'           => $pagina_existente->ID,
                    'post_title'   => $titulo_sanitizado,
                    'post_content' => $contenido_pagina,
                    'post_status'  => $estado_publicacion,
                ), true );
                if ( is_wp_error( $resultado ) ) {
                    $errores_paginas[] = sprintf( 'Error al actualizar %s.', $titulo_sanitizado );
                    continue;
                }
                $identificador_pagina = $pagina_existente->ID;
                $paginas_actualizadas++;
            } else {
                $identificador_pagina = wp_insert_post( array(
                    'post_title'   => $titulo_sanitizado,
                    'post_name'    => $slug_sanitizado,
                    'post_content' => $contenido_pagina,
                    'post_status'  => $estado_publicacion,
                    'post_type'    => 'flavor_landing',
                ), true );
                if ( is_wp_error( $identificador_pagina ) ) {
                    $errores_paginas[] = sprintf( 'Error al crear %s.', $titulo_sanitizado );
                    continue;
                }
                $paginas_creadas++;
            }
            if ( isset( $datos_pagina['post_meta'] ) && is_array( $datos_pagina['post_meta'] ) ) {
                foreach ( $datos_pagina['post_meta'] as $clave_meta => $valor_meta ) {
                    $clave_meta_sanitizada = sanitize_key( $clave_meta );
                    update_post_meta( $identificador_pagina, $clave_meta_sanitizada, $this->sanitizar_datos_recursivo( $valor_meta ) );
                }
            }
        }
        $mensaje_resultado = sprintf( '%d paginas creadas, %d actualizadas.', $paginas_creadas, $paginas_actualizadas );
        if ( ! empty( $errores_paginas ) ) { $mensaje_resultado .= ' ' . implode( ' ', $errores_paginas ); }
        return array( 'status' => 'success', 'message' => $mensaje_resultado, 'created' => $paginas_creadas, 'updated' => $paginas_actualizadas, 'errors' => $errores_paginas );
    }

    private function fusionar_preservando_secretos( $datos_existentes, $datos_nuevos ) {
        if ( ! is_array( $datos_nuevos ) ) { return $datos_existentes; }
        $datos_fusionados = $datos_existentes;
        foreach ( $datos_nuevos as $clave => $valor ) {
            if ( '***REDACTED***' === $valor ) { continue; }
            if ( is_array( $valor ) && isset( $datos_fusionados[ $clave ] ) && is_array( $datos_fusionados[ $clave ] ) ) {
                $datos_fusionados[ $clave ] = $this->fusionar_preservando_secretos( $datos_fusionados[ $clave ], $valor );
            } else {
                $datos_fusionados[ $clave ] = $valor;
            }
        }
        return $datos_fusionados;
    }

    private function sanitizar_datos_recursivo( $datos ) {
        if ( is_array( $datos ) ) {
            $datos_sanitizados = array();
            foreach ( $datos as $clave => $valor ) {
                $clave_sanitizada = sanitize_text_field( $clave );
                $datos_sanitizados[ $clave_sanitizada ] = $this->sanitizar_datos_recursivo( $valor );
            }
            return $datos_sanitizados;
        }
        if ( is_string( $datos ) ) { return sanitize_text_field( $datos ); }
        if ( is_bool( $datos ) || is_int( $datos ) || is_float( $datos ) ) { return $datos; }
        return $datos;
    }

    // =========================================================================
    // RENDERIZADO DE LA PAGINA
    // =========================================================================

    public function renderizar_pagina() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'No tienes permisos para acceder a esta pagina.' );
        }
        ?>
        <div class="wrap flavor-export-import-wrap">
            <h1>Exportar / Importar</h1>
            <p class="description">Exporta o importa la configuracion del plugin Flavor Chat IA. Las claves API y datos sensibles nunca se incluyen en las exportaciones.</p>
            <div class="flavor-export-import-columns">
                <div class="flavor-export-import-section flavor-export-section">
                    <h2><span class="dashicons dashicons-download"></span> Exportar Configuracion</h2>
                    <p class="description">Selecciona que datos deseas exportar y descarga el archivo JSON.</p>
                    <form id="flavor-export-form" method="post">
                        <fieldset>
                            <legend class="screen-reader-text">Opciones de exportacion</legend>
                            <label class="flavor-checkbox-option">
                                <input type="checkbox" name="export_secciones[]" value="settings" checked>
                                <span class="flavor-checkbox-label"><strong>Ajustes Generales</strong><span class="flavor-checkbox-desc">Configuracion general del chat, asistente, tono, idiomas (sin claves API)</span></span>
                            </label>
                            <label class="flavor-checkbox-option">
                                <input type="checkbox" name="export_secciones[]" value="active_modules" checked>
                                <span class="flavor-checkbox-label"><strong>Modulos Activos</strong><span class="flavor-checkbox-desc">Lista de modulos activados en el plugin</span></span>
                            </label>
                            <label class="flavor-checkbox-option">
                                <input type="checkbox" name="export_secciones[]" value="module_settings" checked>
                                <span class="flavor-checkbox-label"><strong>Configuracion de Modulos</strong><span class="flavor-checkbox-desc">Ajustes individuales de cada modulo</span></span>
                            </label>
                            <label class="flavor-checkbox-option">
                                <input type="checkbox" name="export_secciones[]" value="design_settings" checked>
                                <span class="flavor-checkbox-label"><strong>Ajustes de Diseno</strong><span class="flavor-checkbox-desc">Colores, tipografias, espaciados y tema</span></span>
                            </label>
                            <label class="flavor-checkbox-option">
                                <input type="checkbox" name="export_secciones[]" value="pages">
                                <span class="flavor-checkbox-label"><strong>Paginas del Page Builder</strong><span class="flavor-checkbox-desc">Landing pages creadas con el constructor de paginas</span></span>
                            </label>
                        </fieldset>
                        <p class="submit">
                            <button type="submit" id="flavor-export-btn" class="button button-primary button-hero">
                                <span class="dashicons dashicons-download"></span> Descargar Exportacion
                            </button>
                        </p>
                    </form>
                    <div id="flavor-export-status" class="flavor-status-message hidden"></div>
                </div>

                <div class="flavor-export-import-section flavor-import-section">
                    <h2><span class="dashicons dashicons-upload"></span> Importar Configuracion</h2>
                    <p class="description">Sube un archivo JSON de exportacion para importar. Primero se mostrara una previsualizacion.</p>
                    <div id="flavor-import-step-upload" class="flavor-import-step">
                        <h3>Paso 1: Seleccionar archivo</h3>
                        <form id="flavor-import-upload-form" enctype="multipart/form-data">
                            <div class="flavor-file-upload-area">
                                <input type="file" name="archivo_importacion" id="flavor-import-file" accept=".json" class="flavor-file-input">
                                <label for="flavor-import-file" class="flavor-file-label">
                                    <span class="dashicons dashicons-media-code"></span>
                                    <span>Seleccionar archivo JSON</span>
                                </label>
                                <span id="flavor-import-filename" class="flavor-filename"></span>
                            </div>
                            <p class="submit">
                                <button type="submit" id="flavor-import-preview-btn" class="button button-secondary">
                                    <span class="dashicons dashicons-visibility"></span> Previsualizar Importacion
                                </button>
                            </p>
                        </form>
                    </div>
                    <div id="flavor-import-step-preview" class="flavor-import-step hidden">
                        <h3>Paso 2: Previsualizacion</h3>
                        <div id="flavor-import-metadata" class="flavor-import-info"></div>
                        <div id="flavor-import-summary" class="flavor-import-summary"></div>
                        <form id="flavor-import-apply-form">
                            <div id="flavor-import-checkboxes"></div>
                            <p class="submit">
                                <button type="submit" id="flavor-import-apply-btn" class="button button-primary button-hero">
                                    <span class="dashicons dashicons-yes-alt"></span> Aplicar Importacion
                                </button>
                                <button type="button" id="flavor-import-cancel-btn" class="button button-secondary">Cancelar</button>
                            </p>
                        </form>
                    </div>
                    <div id="flavor-import-status" class="flavor-status-message hidden"></div>
                </div>
            </div>
        </div>
        <?php
    }
}
