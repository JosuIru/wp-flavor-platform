<?php
/**
 * Visual Builder Pro - Version History
 *
 * Sistema de historial de versiones con diff visual.
 *
 * @package Flavor_Chat_IA
 * @subpackage Visual_Builder_Pro
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase para gestionar el historial de versiones
 *
 * @since 2.0.0
 */
class Flavor_VBP_Version_History {

    /**
     * Meta key unificada del editor.
     *
     * @var string
     */
    const META_DATA = '_flavor_vbp_data';

    /**
     * Número máximo de versiones por post
     *
     * @var int
     */
    const MAX_VERSIONES = 20;

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Version_History|null
     */
    private static $instancia = null;

    /**
     * Nombre de la tabla de versiones
     *
     * @var string
     */
    private $tabla_versiones;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Version_History
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_versiones = $wpdb->prefix . 'vbp_versions';

        $this->crear_tablas();
        $this->registrar_endpoints();
        $this->registrar_hooks();
    }

    /**
     * Crea las tablas necesarias
     */
    private function crear_tablas() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->tabla_versiones} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            version_number int(11) NOT NULL DEFAULT 1,
            content longtext NOT NULL,
            content_hash varchar(64) NOT NULL,
            label varchar(255) DEFAULT '',
            autor_id bigint(20) unsigned NOT NULL,
            fecha_creacion datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY version_number (version_number),
            KEY content_hash (content_hash)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Registra los endpoints de la API REST
     */
    private function registrar_endpoints() {
        add_action( 'rest_api_init', array( $this, 'registrar_rutas' ) );
    }

    /**
     * Registra hooks para guardar automáticamente
     */
    private function registrar_hooks() {
        // Guardar versión automáticamente al guardar desde el editor
        add_action( 'vbp_content_saved', array( $this, 'guardar_version_automatica' ), 10, 2 );
    }

    /**
     * Registra las rutas de la API
     *
     * NOTA: Unificado a flavor-vbp/v1 para consistencia con el resto del sistema.
     */
    public function registrar_rutas() {
        $namespace = 'flavor-vbp/v1';

        // Obtener historial de versiones
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'obtener_versiones' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
                'args'                => array(
                    'post_id' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                ),
            )
        );

        // Obtener una versión específica
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)/(?P<version_id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'obtener_version' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
            )
        );

        // Crear versión manual
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'crear_version' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
            )
        );

        // Restaurar versión
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)/(?P<version_id>\d+)/restore',
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'restaurar_version' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
            )
        );

        // Comparar versiones (diff)
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)/compare',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'comparar_versiones' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
                'args'                => array(
                    'version_a' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                    'version_b' => array(
                        'required'          => true,
                        'validate_callback' => function( $param ) {
                            return is_numeric( $param );
                        },
                    ),
                ),
            )
        );

        // Eliminar versión
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)/(?P<version_id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => array( $this, 'eliminar_version' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
            )
        );

        // Actualizar etiqueta de versión
        register_rest_route(
            $namespace,
            '/versions/(?P<post_id>\d+)/(?P<version_id>\d+)/label',
            array(
                'methods'             => 'PUT',
                'callback'            => array( $this, 'actualizar_etiqueta' ),
                'permission_callback' => array( $this, 'verificar_permisos' ),
            )
        );
    }

    /**
     * Verifica permisos de acceso
     *
     * @return bool
     */
    public function verificar_permisos() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Guarda una versión automáticamente
     *
     * @param int   $post_id ID del post.
     * @param array $content Contenido del VBP.
     */
    public function guardar_version_automatica( $post_id, $content ) {
        $this->guardar_version( $post_id, $content );
    }

    /**
     * Obtiene el documento VBP actual usando la persistencia activa.
     *
     * @param int $post_id ID del post.
     * @return array|null
     */
    private function obtener_documento_actual( $post_id ) {
        if ( class_exists( 'Flavor_VBP_Editor' ) ) {
            return Flavor_VBP_Editor::get_instance()->obtener_datos_documento( $post_id );
        }

        $contenido = get_post_meta( $post_id, self::META_DATA, true );
        if ( is_array( $contenido ) ) {
            return $contenido;
        }

        $legacy = get_post_meta( $post_id, '_vbp_content', true );
        if ( is_array( $legacy ) ) {
            return array(
                'version'  => 'legacy',
                'elements' => $legacy,
                'settings' => array(),
            );
        }

        return null;
    }

    /**
     * Guarda el documento VBP actual usando la persistencia activa.
     *
     * @param int   $post_id   ID del post.
     * @param array $documento Documento a guardar.
     * @return bool
     */
    private function guardar_documento_actual( $post_id, $documento ) {
        if ( class_exists( 'Flavor_VBP_Editor' ) ) {
            return Flavor_VBP_Editor::get_instance()->guardar_datos_documento( $post_id, $documento );
        }

        return false !== update_post_meta( $post_id, self::META_DATA, $documento );
    }

    /**
     * Guarda una nueva versión
     *
     * @param int    $post_id ID del post.
     * @param array  $content Contenido del VBP.
     * @param string $label   Etiqueta opcional.
     * @return int|false ID de la versión o false en error.
     */
    public function guardar_version( $post_id, $content, $label = '' ) {
        global $wpdb;

        $contenido_json = wp_json_encode( $content );
        $content_hash   = hash( 'sha256', $contenido_json );

        // Verificar si ya existe una versión idéntica
        $existe = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$this->tabla_versiones}
                WHERE post_id = %d AND content_hash = %s
                ORDER BY id DESC LIMIT 1",
                $post_id,
                $content_hash
            )
        );

        if ( $existe ) {
            return (int) $existe; // Ya existe, no crear duplicado
        }

        // Obtener el siguiente número de versión
        $ultimo_numero = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT MAX(version_number) FROM {$this->tabla_versiones} WHERE post_id = %d",
                $post_id
            )
        );

        $nuevo_numero = ( $ultimo_numero ? (int) $ultimo_numero : 0 ) + 1;

        // Insertar nueva versión
        $insertado = $wpdb->insert(
            $this->tabla_versiones,
            array(
                'post_id'        => $post_id,
                'version_number' => $nuevo_numero,
                'content'        => $contenido_json,
                'content_hash'   => $content_hash,
                'label'          => $label,
                'autor_id'       => get_current_user_id(),
                'fecha_creacion' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( $insertado ) {
            $version_id = $wpdb->insert_id;
            $this->limpiar_versiones_antiguas( $post_id );
            return $version_id;
        }

        return false;
    }

    /**
     * Limpia versiones antiguas excepto las más recientes
     *
     * @param int $post_id ID del post.
     */
    private function limpiar_versiones_antiguas( $post_id ) {
        global $wpdb;

        // Obtener IDs de versiones a mantener
        $versiones_mantener = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT id FROM {$this->tabla_versiones}
                WHERE post_id = %d
                ORDER BY id DESC
                LIMIT %d",
                $post_id,
                self::MAX_VERSIONES
            )
        );

        if ( empty( $versiones_mantener ) ) {
            return;
        }

        $ids_placeholder = implode( ',', array_fill( 0, count( $versiones_mantener ), '%d' ) );

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$this->tabla_versiones}
                WHERE post_id = %d AND id NOT IN ($ids_placeholder)",
                array_merge( array( $post_id ), $versiones_mantener )
            )
        );
    }

    /**
     * Obtiene las versiones de un post
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function obtener_versiones( $request ) {
        global $wpdb;

        $post_id = (int) $request->get_param( 'post_id' );

        $versiones = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT v.id, v.version_number, v.label, v.autor_id, v.fecha_creacion,
                        u.display_name as autor_nombre
                FROM {$this->tabla_versiones} v
                LEFT JOIN {$wpdb->users} u ON v.autor_id = u.ID
                WHERE v.post_id = %d
                ORDER BY v.version_number DESC",
                $post_id
            ),
            ARRAY_A
        );

        // Calcular resumen de cambios
        foreach ( $versiones as $index => &$version ) {
            $version['fecha_formateada'] = human_time_diff(
                strtotime( $version['fecha_creacion'] ),
                current_time( 'timestamp' )
            ) . ' ' . __( 'atrás', FLAVOR_PLATFORM_TEXT_DOMAIN );

            // Obtener resumen de elementos
            $contenido = $this->obtener_contenido_version( $version['id'] );
            if ( $contenido ) {
                $version['resumen'] = $this->generar_resumen_contenido( $contenido );
            }
        }

        return new WP_REST_Response(
            array(
                'success'   => true,
                'versiones' => $versiones,
            ),
            200
        );
    }

    /**
     * Obtiene una versión específica
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function obtener_version( $request ) {
        global $wpdb;

        $post_id    = (int) $request->get_param( 'post_id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $version = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT v.*, u.display_name as autor_nombre
                FROM {$this->tabla_versiones} v
                LEFT JOIN {$wpdb->users} u ON v.autor_id = u.ID
                WHERE v.id = %d AND v.post_id = %d",
                $version_id,
                $post_id
            ),
            ARRAY_A
        );

        if ( ! $version ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Versión no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                404
            );
        }

        $version['content'] = json_decode( $version['content'], true );

        return new WP_REST_Response(
            array(
                'success' => true,
                'version' => $version,
            ),
            200
        );
    }

    /**
     * Obtiene el contenido de una versión
     *
     * @param int $version_id ID de la versión.
     * @return array|null
     */
    private function obtener_contenido_version( $version_id ) {
        global $wpdb;

        $contenido = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT content FROM {$this->tabla_versiones} WHERE id = %d",
                $version_id
            )
        );

        return $contenido ? json_decode( $contenido, true ) : null;
    }

    /**
     * Genera un resumen del contenido
     *
     * @param array $contenido Contenido del VBP.
     * @return array
     */
    private function generar_resumen_contenido( $contenido ) {
        $resumen = array(
            'total_elementos' => 0,
            'tipos'           => array(),
        );

        if ( ! is_array( $contenido ) ) {
            return $resumen;
        }

        $elementos = isset( $contenido['elements'] ) && is_array( $contenido['elements'] )
            ? $contenido['elements']
            : $contenido;

        if ( ! is_array( $elementos ) ) {
            return $resumen;
        }

        $this->contar_elementos_recursivo( $elementos, $resumen );

        return $resumen;
    }

    /**
     * Cuenta elementos recursivamente
     *
     * @param array $elementos Elementos a contar.
     * @param array $resumen   Referencia al resumen.
     */
    private function contar_elementos_recursivo( $elementos, &$resumen ) {
        foreach ( $elementos as $elemento ) {
            if ( isset( $elemento['type'] ) ) {
                $resumen['total_elementos']++;

                $tipo = $elemento['type'];
                if ( ! isset( $resumen['tipos'][ $tipo ] ) ) {
                    $resumen['tipos'][ $tipo ] = 0;
                }
                $resumen['tipos'][ $tipo ]++;
            }

            if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
                $this->contar_elementos_recursivo( $elemento['children'], $resumen );
            }
        }
    }

    /**
     * Crea una versión manual
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function crear_version( $request ) {
        $post_id = (int) $request->get_param( 'post_id' );
        $label   = sanitize_text_field( $request->get_param( 'label' ) ?? '' );

        // Obtener contenido actual del post
        $contenido = $this->obtener_documento_actual( $post_id );

        if ( empty( $contenido ) ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'No hay contenido VBP para guardar', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                400
            );
        }

        $version_id = $this->guardar_version( $post_id, $contenido, $label );

        if ( $version_id ) {
            return new WP_REST_Response(
                array(
                    'success'    => true,
                    'message'    => __( 'Versión guardada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    'version_id' => $version_id,
                ),
                201
            );
        }

        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => __( 'Error al guardar la versión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            500
        );
    }

    /**
     * Restaura una versión
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function restaurar_version( $request ) {
        global $wpdb;

        $post_id    = (int) $request->get_param( 'post_id' );
        $version_id = (int) $request->get_param( 'version_id' );

        // Obtener la versión
        $contenido = $this->obtener_contenido_version( $version_id );

        if ( ! $contenido ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Versión no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                404
            );
        }

        // Guardar versión actual antes de restaurar
        $contenido_actual = $this->obtener_documento_actual( $post_id );
        if ( $contenido_actual ) {
            $this->guardar_version(
                $post_id,
                $contenido_actual,
                __( 'Antes de restaurar', FLAVOR_PLATFORM_TEXT_DOMAIN )
            );
        }

        // Restaurar contenido
        $this->guardar_documento_actual( $post_id, $contenido );

        // Guardar versión restaurada
        $this->guardar_version(
            $post_id,
            $contenido,
            __( 'Restaurado desde versión', FLAVOR_PLATFORM_TEXT_DOMAIN ) . ' #' . $version_id
        );

        return new WP_REST_Response(
            array(
                'success'  => true,
                'message'  => __( 'Versión restaurada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                'content'  => $contenido,
            ),
            200
        );
    }

    /**
     * Compara dos versiones y genera un diff
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function comparar_versiones( $request ) {
        $post_id   = (int) $request->get_param( 'post_id' );
        $version_a = (int) $request->get_param( 'version_a' );
        $version_b = (int) $request->get_param( 'version_b' );

        $contenido_a = $this->obtener_contenido_version( $version_a );
        $contenido_b = $this->obtener_contenido_version( $version_b );

        if ( ! $contenido_a || ! $contenido_b ) {
            return new WP_REST_Response(
                array(
                    'success' => false,
                    'message' => __( 'Una o ambas versiones no encontradas', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                404
            );
        }

        // Generar diff
        $diff = $this->generar_diff( $contenido_a, $contenido_b );

        return new WP_REST_Response(
            array(
                'success'    => true,
                'version_a'  => $contenido_a,
                'version_b'  => $contenido_b,
                'diff'       => $diff,
                'estadisticas' => array(
                    'elementos_agregados'   => $diff['stats']['added'],
                    'elementos_eliminados'  => $diff['stats']['removed'],
                    'elementos_modificados' => $diff['stats']['modified'],
                ),
            ),
            200
        );
    }

    /**
     * Genera un diff visual entre dos contenidos
     *
     * @param array $contenido_a Contenido antiguo.
     * @param array $contenido_b Contenido nuevo.
     * @return array
     */
    private function generar_diff( $contenido_a, $contenido_b ) {
        $diff = array(
            'changes' => array(),
            'stats'   => array(
                'added'    => 0,
                'removed'  => 0,
                'modified' => 0,
            ),
        );

        // Crear mapas de elementos por ID
        $mapa_a = $this->crear_mapa_elementos( $contenido_a );
        $mapa_b = $this->crear_mapa_elementos( $contenido_b );

        // Encontrar elementos agregados
        foreach ( $mapa_b as $id => $elemento ) {
            if ( ! isset( $mapa_a[ $id ] ) ) {
                $diff['changes'][] = array(
                    'type'     => 'added',
                    'id'       => $id,
                    'elemento' => $elemento,
                    'path'     => $this->obtener_path_elemento( $id, $contenido_b ),
                );
                $diff['stats']['added']++;
            }
        }

        // Encontrar elementos eliminados
        foreach ( $mapa_a as $id => $elemento ) {
            if ( ! isset( $mapa_b[ $id ] ) ) {
                $diff['changes'][] = array(
                    'type'     => 'removed',
                    'id'       => $id,
                    'elemento' => $elemento,
                    'path'     => $this->obtener_path_elemento( $id, $contenido_a ),
                );
                $diff['stats']['removed']++;
            }
        }

        // Encontrar elementos modificados
        foreach ( $mapa_a as $id => $elemento_a ) {
            if ( isset( $mapa_b[ $id ] ) ) {
                $elemento_b = $mapa_b[ $id ];
                $cambios    = $this->comparar_elementos( $elemento_a, $elemento_b );

                if ( ! empty( $cambios ) ) {
                    $diff['changes'][] = array(
                        'type'       => 'modified',
                        'id'         => $id,
                        'elemento_a' => $elemento_a,
                        'elemento_b' => $elemento_b,
                        'cambios'    => $cambios,
                        'path'       => $this->obtener_path_elemento( $id, $contenido_b ),
                    );
                    $diff['stats']['modified']++;
                }
            }
        }

        return $diff;
    }

    /**
     * Crea un mapa plano de elementos por ID
     *
     * @param array  $elementos Elementos.
     * @param string $path      Path actual.
     * @return array
     */
    private function crear_mapa_elementos( $elementos, $path = '' ) {
        $mapa = array();

        if ( ! is_array( $elementos ) ) {
            return $mapa;
        }

        foreach ( $elementos as $index => $elemento ) {
            if ( isset( $elemento['id'] ) ) {
                $mapa[ $elemento['id'] ] = $elemento;
            }

            if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
                $nuevo_path = $path ? "$path/$index" : (string) $index;
                $mapa       = array_merge(
                    $mapa,
                    $this->crear_mapa_elementos( $elemento['children'], $nuevo_path )
                );
            }
        }

        return $mapa;
    }

    /**
     * Obtiene el path de un elemento
     *
     * @param string $id        ID del elemento.
     * @param array  $elementos Elementos.
     * @param array  $path      Path acumulado.
     * @return array
     */
    private function obtener_path_elemento( $id, $elementos, $path = array() ) {
        if ( ! is_array( $elementos ) ) {
            return array();
        }

        foreach ( $elementos as $index => $elemento ) {
            if ( isset( $elemento['id'] ) && $elemento['id'] === $id ) {
                $path[] = array(
                    'index' => $index,
                    'type'  => $elemento['type'] ?? 'unknown',
                    'id'    => $id,
                );
                return $path;
            }

            if ( isset( $elemento['children'] ) && is_array( $elemento['children'] ) ) {
                $nuevo_path   = $path;
                $nuevo_path[] = array(
                    'index' => $index,
                    'type'  => $elemento['type'] ?? 'container',
                    'id'    => $elemento['id'] ?? '',
                );

                $resultado = $this->obtener_path_elemento( $id, $elemento['children'], $nuevo_path );
                if ( ! empty( $resultado ) ) {
                    return $resultado;
                }
            }
        }

        return array();
    }

    /**
     * Compara dos elementos y devuelve los cambios
     *
     * @param array $elemento_a Elemento antiguo.
     * @param array $elemento_b Elemento nuevo.
     * @return array
     */
    private function comparar_elementos( $elemento_a, $elemento_b ) {
        $cambios = array();

        // Comparar propiedades (ignorando children)
        $props_a = $elemento_a;
        $props_b = $elemento_b;
        unset( $props_a['children'], $props_b['children'] );

        // Comparar cada propiedad
        $todas_keys = array_unique( array_merge( array_keys( $props_a ), array_keys( $props_b ) ) );

        foreach ( $todas_keys as $key ) {
            $valor_a = $props_a[ $key ] ?? null;
            $valor_b = $props_b[ $key ] ?? null;

            if ( $valor_a !== $valor_b ) {
                $cambios[] = array(
                    'property'  => $key,
                    'old_value' => $valor_a,
                    'new_value' => $valor_b,
                );
            }
        }

        return $cambios;
    }

    /**
     * Elimina una versión
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function eliminar_version( $request ) {
        global $wpdb;

        $post_id    = (int) $request->get_param( 'post_id' );
        $version_id = (int) $request->get_param( 'version_id' );

        $eliminado = $wpdb->delete(
            $this->tabla_versiones,
            array(
                'id'      => $version_id,
                'post_id' => $post_id,
            ),
            array( '%d', '%d' )
        );

        if ( $eliminado ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Versión eliminada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => __( 'Error al eliminar la versión', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            500
        );
    }

    /**
     * Actualiza la etiqueta de una versión
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function actualizar_etiqueta( $request ) {
        global $wpdb;

        $post_id    = (int) $request->get_param( 'post_id' );
        $version_id = (int) $request->get_param( 'version_id' );
        $label      = sanitize_text_field( $request->get_param( 'label' ) ?? '' );

        $actualizado = $wpdb->update(
            $this->tabla_versiones,
            array( 'label' => $label ),
            array(
                'id'      => $version_id,
                'post_id' => $post_id,
            ),
            array( '%s' ),
            array( '%d', '%d' )
        );

        if ( false !== $actualizado ) {
            return new WP_REST_Response(
                array(
                    'success' => true,
                    'message' => __( 'Etiqueta actualizada correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                ),
                200
            );
        }

        return new WP_REST_Response(
            array(
                'success' => false,
                'message' => __( 'Error al actualizar la etiqueta', FLAVOR_PLATFORM_TEXT_DOMAIN ),
            ),
            500
        );
    }
}
