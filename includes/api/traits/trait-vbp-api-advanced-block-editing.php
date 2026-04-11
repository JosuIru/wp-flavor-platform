<?php
/**
 * Trait para edición avanzada de bloques VBP
 *
 * Este trait contiene métodos para manipulación avanzada
 * de bloques individuales en páginas VBP.
 *
 * @package Flavor_Platform
 * @subpackage API\Traits
 * @since 2.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trait VBP_API_AdvancedBlockEditing
 *
 * Contiene métodos para:
 * - CRUD de bloques individuales
 * - Duplicación y clonación de bloques
 * - Wrap/Unwrap de bloques
 * - Bloqueo de bloques para edición colaborativa
 * - Historial de versiones de bloques
 * - Aplicación de estilos en lote
 * - Buscar/reemplazar en bloques
 */
trait VBP_API_AdvancedBlockEditing {


    /**
     * Obtiene un bloque específico con metadatos
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_single_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $block = $this->find_block_by_id( $elements, $block_id );

        if ( ! $block ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Bloque no encontrado.',
            ), 404 );
        }

        // Obtener metadatos del bloque
        $block_locks = get_post_meta( $page_id, '_flavor_vbp_block_locks', true );
        $block_locks = $block_locks ? json_decode( $block_locks, true ) : array();

        $block_meta = array(
            'locked'    => isset( $block_locks[ $block_id ] ),
            'locked_by' => $block_locks[ $block_id ]['user_id'] ?? null,
            'locked_at' => $block_locks[ $block_id ]['timestamp'] ?? null,
            'path'      => $this->get_block_path( $elements, $block_id ),
            'depth'     => $this->get_block_depth( $elements, $block_id ),
        );

        return new WP_REST_Response( array(
            'success' => true,
            'block'   => $block,
            'meta'    => $block_meta,
        ), 200 );
    }

    /**
     * Busca un bloque por ID recursivamente
     *
     * @param array  $elements Elementos.
     * @param string $block_id ID del bloque.
     * @return array|null
     */
    private function find_block_by_id( $elements, $block_id ) {
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                return $element;
            }
            if ( ! empty( $element['children'] ) ) {
                $found = $this->find_block_by_id( $element['children'], $block_id );
                if ( $found ) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Obtiene la ruta de un bloque (índices de padres)
     *
     * @param array  $elements Elementos.
     * @param string $block_id ID del bloque.
     * @param array  $path     Ruta actual.
     * @return array|null
     */
    private function get_block_path( $elements, $block_id, $path = array() ) {
        foreach ( $elements as $index => $element ) {
            $current_path = array_merge( $path, array( $index ) );
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                return $current_path;
            }
            if ( ! empty( $element['children'] ) ) {
                $found = $this->get_block_path( $element['children'], $block_id, $current_path );
                if ( $found !== null ) {
                    return $found;
                }
            }
        }
        return null;
    }

    /**
     * Obtiene la profundidad de un bloque
     *
     * @param array  $elements Elementos.
     * @param string $block_id ID del bloque.
     * @param int    $depth    Profundidad actual.
     * @return int
     */
    private function get_block_depth( $elements, $block_id, $depth = 0 ) {
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                return $depth;
            }
            if ( ! empty( $element['children'] ) ) {
                $found = $this->get_block_depth( $element['children'], $block_id, $depth + 1 );
                if ( $found !== -1 ) {
                    return $found;
                }
            }
        }
        return -1;
    }

    /**
     * Actualiza un bloque específico
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function update_single_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $new_data = $request->get_param( 'data' );
        $new_styles = $request->get_param( 'styles' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        // Verificar bloqueo
        if ( $this->is_block_locked( $page_id, $block_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El bloque está bloqueado por otro usuario.',
            ), 423 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        // Guardar versión anterior para historial
        $old_block = $this->find_block_by_id( $elements, $block_id );
        if ( $old_block ) {
            $this->save_block_history( $page_id, $block_id, $old_block );
        }

        // Actualizar bloque
        $elements = $this->update_block_in_elements( $elements, $block_id, $new_data, $new_styles );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        $this->log_page_activity( $page_id, 'block_updated', array(
            'block_id' => $block_id,
        ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Bloque actualizado.',
        ), 200 );
    }

    /**
     * Verifica si un bloque está bloqueado
     *
     * @param int    $page_id  ID de la página.
     * @param string $block_id ID del bloque.
     * @return bool
     */
    private function is_block_locked( $page_id, $block_id ) {
        $block_locks = get_post_meta( $page_id, '_flavor_vbp_block_locks', true );
        $block_locks = $block_locks ? json_decode( $block_locks, true ) : array();

        if ( ! isset( $block_locks[ $block_id ] ) ) {
            return false;
        }

        $lock = $block_locks[ $block_id ];
        $lock_time = strtotime( $lock['timestamp'] );
        $expiry = 15 * MINUTE_IN_SECONDS;

        // Verificar si expiró
        if ( time() - $lock_time > $expiry ) {
            unset( $block_locks[ $block_id ] );
            update_post_meta( $page_id, '_flavor_vbp_block_locks', wp_json_encode( $block_locks ) );
            return false;
        }

        // Es el mismo usuario
        if ( $lock['user_id'] === get_current_user_id() ) {
            return false;
        }

        return true;
    }

    /**
     * Guarda historial de un bloque
     *
     * @param int    $page_id  ID de la página.
     * @param string $block_id ID del bloque.
     * @param array  $block    Datos del bloque.
     */
    private function save_block_history( $page_id, $block_id, $block ) {
        $history_key = '_flavor_vbp_block_history_' . $block_id;
        $history_json = get_post_meta( $page_id, $history_key, true );
        $history = $history_json ? json_decode( $history_json, true ) : array();

        $version = array(
            'id'        => 'v_' . uniqid(),
            'block'     => $block,
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time( 'mysql' ),
        );

        array_unshift( $history, $version );

        // Mantener solo las últimas 50 versiones
        $history = array_slice( $history, 0, 50 );

        update_post_meta( $page_id, $history_key, wp_json_encode( $history ) );
    }

    /**
     * Actualiza bloque en array de elementos
     *
     * @param array  $elements   Elementos.
     * @param string $block_id   ID del bloque.
     * @param array  $new_data   Nuevos datos.
     * @param array  $new_styles Nuevos estilos.
     * @return array
     */
    private function update_block_in_elements( $elements, $block_id, $new_data, $new_styles ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                if ( $new_data !== null ) {
                    $element['data'] = array_merge( $element['data'] ?? array(), $new_data );
                }
                if ( $new_styles !== null ) {
                    $element['styles'] = array_merge( $element['styles'] ?? array(), $new_styles );
                }
                return $elements;
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->update_block_in_elements( $element['children'], $block_id, $new_data, $new_styles );
            }
        }
        return $elements;
    }

    /**
     * Duplica un bloque dentro de la página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function duplicate_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $position = $request->get_param( 'position' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $block = $this->find_block_by_id( $elements, $block_id );
        if ( ! $block ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Bloque no encontrado.',
            ), 404 );
        }

        // Crear copia con nuevos IDs
        $new_block = $this->deep_clone_block( $block );
        $new_block_id = $new_block['id'];

        // Insertar en la posición correcta
        $elements = $this->insert_block_near( $elements, $block_id, $new_block, $position );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        $this->log_page_activity( $page_id, 'block_duplicated', array(
            'original_id' => $block_id,
            'new_id'      => $new_block_id,
        ) );

        return new WP_REST_Response( array(
            'success'      => true,
            'message'      => 'Bloque duplicado.',
            'new_block_id' => $new_block_id,
        ), 201 );
    }

    /**
     * Clona un bloque con nuevos IDs
     *
     * @param array $block Bloque original.
     * @return array
     */
    private function deep_clone_block( $block ) {
        $clone = $block;
        $clone['id'] = 'block_' . uniqid();

        if ( ! empty( $clone['children'] ) ) {
            $clone['children'] = array_map( array( $this, 'deep_clone_block' ), $clone['children'] );
        }

        return $clone;
    }

    /**
     * Inserta bloque cerca de otro
     *
     * @param array  $elements Elementos.
     * @param string $target_id ID del bloque objetivo.
     * @param array  $new_block Nuevo bloque.
     * @param string $position 'before' o 'after'.
     * @return array
     */
    private function insert_block_near( $elements, $target_id, $new_block, $position ) {
        $result = array();
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $target_id ) {
                if ( $position === 'before' ) {
                    $result[] = $new_block;
                    $result[] = $element;
                } else {
                    $result[] = $element;
                    $result[] = $new_block;
                }
            } else {
                if ( ! empty( $element['children'] ) ) {
                    $element['children'] = $this->insert_block_near( $element['children'], $target_id, $new_block, $position );
                }
                $result[] = $element;
            }
        }
        return $result;
    }

    /**
     * Envuelve bloque en contenedor
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function wrap_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $wrapper_type = $request->get_param( 'wrapper_type' );
        $wrapper_data = $request->get_param( 'wrapper_data' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $wrapper_id = 'block_' . uniqid();
        $elements = $this->wrap_block_in_elements( $elements, $block_id, $wrapper_type, $wrapper_data, $wrapper_id );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Bloque envuelto.',
            'wrapper_id' => $wrapper_id,
        ), 200 );
    }

    /**
     * Envuelve bloque en elementos
     *
     * @param array  $elements     Elementos.
     * @param string $block_id     ID del bloque.
     * @param string $wrapper_type Tipo de contenedor.
     * @param array  $wrapper_data Datos del contenedor.
     * @param string $wrapper_id   ID del contenedor.
     * @return array
     */
    private function wrap_block_in_elements( $elements, $block_id, $wrapper_type, $wrapper_data, $wrapper_id ) {
        $result = array();
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $wrapper = array(
                    'id'       => $wrapper_id,
                    'type'     => $wrapper_type,
                    'data'     => $wrapper_data,
                    'children' => array( $element ),
                );
                $result[] = $wrapper;
            } else {
                if ( ! empty( $element['children'] ) ) {
                    $element['children'] = $this->wrap_block_in_elements( $element['children'], $block_id, $wrapper_type, $wrapper_data, $wrapper_id );
                }
                $result[] = $element;
            }
        }
        return $result;
    }

    /**
     * Desenvuelve bloque (saca de contenedor)
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function unwrap_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $block = $this->find_block_by_id( $elements, $block_id );
        if ( ! $block || empty( $block['children'] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El bloque no tiene hijos para desenvolver.',
            ), 400 );
        }

        // Reemplazar el contenedor con sus hijos
        $elements = $this->unwrap_block_in_elements( $elements, $block_id );

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Bloque desenvuelto.',
        ), 200 );
    }

    /**
     * Desenvuelve bloque en elementos
     *
     * @param array  $elements Elementos.
     * @param string $block_id ID del bloque.
     * @return array
     */
    private function unwrap_block_in_elements( $elements, $block_id ) {
        $result = array();
        foreach ( $elements as $element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                // Insertar los hijos en lugar del contenedor
                if ( ! empty( $element['children'] ) ) {
                    foreach ( $element['children'] as $child ) {
                        $result[] = $child;
                    }
                }
            } else {
                if ( ! empty( $element['children'] ) ) {
                    $element['children'] = $this->unwrap_block_in_elements( $element['children'], $block_id );
                }
                $result[] = $element;
            }
        }
        return $result;
    }

    /**
     * Bloquea un bloque para edición
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function lock_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        if ( $this->is_block_locked( $page_id, $block_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'El bloque ya está bloqueado por otro usuario.',
            ), 423 );
        }

        $block_locks = get_post_meta( $page_id, '_flavor_vbp_block_locks', true );
        $block_locks = $block_locks ? json_decode( $block_locks, true ) : array();

        $block_locks[ $block_id ] = array(
            'user_id'   => get_current_user_id(),
            'timestamp' => current_time( 'mysql' ),
        );

        update_post_meta( $page_id, '_flavor_vbp_block_locks', wp_json_encode( $block_locks ) );

        return new WP_REST_Response( array(
            'success'    => true,
            'message'    => 'Bloque bloqueado.',
            'expires_in' => 15 * 60,
        ), 200 );
    }

    /**
     * Desbloquea un bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function unlock_block( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );

        $block_locks = get_post_meta( $page_id, '_flavor_vbp_block_locks', true );
        $block_locks = $block_locks ? json_decode( $block_locks, true ) : array();

        if ( isset( $block_locks[ $block_id ] ) ) {
            // Solo el usuario que bloqueó o admin puede desbloquear
            if ( $block_locks[ $block_id ]['user_id'] !== get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
                return new WP_REST_Response( array(
                    'success' => false,
                    'error'   => 'No tienes permiso para desbloquear este bloque.',
                ), 403 );
            }

            unset( $block_locks[ $block_id ] );
            update_post_meta( $page_id, '_flavor_vbp_block_locks', wp_json_encode( $block_locks ) );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Bloque desbloqueado.',
        ), 200 );
    }

    /**
     * Obtiene historial de un bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function get_block_history( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $limit = (int) $request->get_param( 'limit' );

        $history_key = '_flavor_vbp_block_history_' . $block_id;
        $history_json = get_post_meta( $page_id, $history_key, true );
        $history = $history_json ? json_decode( $history_json, true ) : array();

        // Enriquecer con datos de usuario
        foreach ( $history as &$version ) {
            if ( ! empty( $version['user_id'] ) ) {
                $user = get_userdata( $version['user_id'] );
                if ( $user ) {
                    $version['user_name'] = $user->display_name;
                }
            }
        }

        $history = array_slice( $history, 0, $limit );

        return new WP_REST_Response( array(
            'success'  => true,
            'block_id' => $block_id,
            'total'    => count( $history ),
            'history'  => $history,
        ), 200 );
    }

    /**
     * Restaura versión de un bloque
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function restore_block_version( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_id = sanitize_text_field( $request->get_param( 'block_id' ) );
        $version_id = sanitize_text_field( $request->get_param( 'version_id' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $history_key = '_flavor_vbp_block_history_' . $block_id;
        $history_json = get_post_meta( $page_id, $history_key, true );
        $history = $history_json ? json_decode( $history_json, true ) : array();

        $version_to_restore = null;
        foreach ( $history as $version ) {
            if ( $version['id'] === $version_id ) {
                $version_to_restore = $version['block'];
                break;
            }
        }

        if ( ! $version_to_restore ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Versión no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        // Guardar estado actual antes de restaurar
        $current_block = $this->find_block_by_id( $elements, $block_id );
        if ( $current_block ) {
            $this->save_block_history( $page_id, $block_id, $current_block );
        }

        // Restaurar
        $elements = $this->replace_block_in_elements( $elements, $block_id, $version_to_restore );
        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        $this->log_page_activity( $page_id, 'block_restored', array(
            'block_id'   => $block_id,
            'version_id' => $version_id,
        ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => 'Versión restaurada.',
        ), 200 );
    }

    /**
     * Reemplaza bloque en elementos
     *
     * @param array  $elements  Elementos.
     * @param string $block_id  ID del bloque.
     * @param array  $new_block Nuevo bloque.
     * @return array
     */
    private function replace_block_in_elements( $elements, $block_id, $new_block ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                $new_block['id'] = $block_id; // Mantener el ID
                $element = $new_block;
                return $elements;
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->replace_block_in_elements( $element['children'], $block_id, $new_block );
            }
        }
        return $elements;
    }

    /**
     * Aplica estilos a múltiples bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function batch_apply_styles( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $block_ids = $request->get_param( 'block_ids' );
        $styles = $request->get_param( 'styles' );
        $merge = (bool) $request->get_param( 'merge' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $updated = 0;
        foreach ( $block_ids as $block_id ) {
            $elements = $this->apply_styles_to_block( $elements, $block_id, $styles, $merge );
            $updated++;
        }

        update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

        $this->log_page_activity( $page_id, 'batch_styles_applied', array(
            'block_count' => $updated,
        ) );

        return new WP_REST_Response( array(
            'success' => true,
            'message' => "Estilos aplicados a {$updated} bloques.",
            'updated' => $updated,
        ), 200 );
    }

    /**
     * Aplica estilos a un bloque
     *
     * @param array  $elements Elementos.
     * @param string $block_id ID del bloque.
     * @param array  $styles   Estilos.
     * @param bool   $merge    Fusionar o reemplazar.
     * @return array
     */
    private function apply_styles_to_block( $elements, $block_id, $styles, $merge ) {
        foreach ( $elements as &$element ) {
            if ( ( $element['id'] ?? '' ) === $block_id ) {
                if ( $merge ) {
                    $element['styles'] = array_merge( $element['styles'] ?? array(), $styles );
                } else {
                    $element['styles'] = $styles;
                }
                return $elements;
            }
            if ( ! empty( $element['children'] ) ) {
                $element['children'] = $this->apply_styles_to_block( $element['children'], $block_id, $styles, $merge );
            }
        }
        return $elements;
    }

    /**
     * Buscar y reemplazar en bloques
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function find_replace_in_blocks( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $find = $request->get_param( 'find' );
        $replace = $request->get_param( 'replace' );
        $case_sensitive = (bool) $request->get_param( 'case_sensitive' );
        $regex = (bool) $request->get_param( 'regex' );
        $dry_run = (bool) $request->get_param( 'dry_run' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'error'   => 'Página no encontrada.',
            ), 404 );
        }

        $elements_json = get_post_meta( $page_id, '_flavor_vbp_elements', true );
        $elements = $elements_json ? json_decode( $elements_json, true ) : array();

        $matches = array();
        $replacements = 0;

        if ( $dry_run ) {
            // Solo buscar coincidencias
            $matches = $this->find_text_matches( $elements, $find, $case_sensitive, $regex );
        } else {
            // Buscar y reemplazar
            list( $elements, $replacements ) = $this->replace_text_in_elements( $elements, $find, $replace, $case_sensitive, $regex );
            update_post_meta( $page_id, '_flavor_vbp_elements', wp_json_encode( $elements ) );

            $this->log_page_activity( $page_id, 'find_replace', array(
                'find'         => $find,
                'replace'      => $replace,
                'replacements' => $replacements,
            ) );
        }

        return new WP_REST_Response( array(
            'success'      => true,
            'dry_run'      => $dry_run,
            'matches'      => $dry_run ? $matches : null,
            'replacements' => $dry_run ? count( $matches ) : $replacements,
        ), 200 );
    }

    /**
     * Busca coincidencias de texto en elementos
     *
     * @param array  $elements       Elementos.
     * @param string $find           Texto a buscar.
     * @param bool   $case_sensitive Case sensitive.
     * @param bool   $regex          Usar regex.
     * @return array
     */
    private function find_text_matches( $elements, $find, $case_sensitive, $regex ) {
        $matches = array();

        foreach ( $elements as $element ) {
            // Buscar en propiedades de texto
            $text_props = array( 'text', 'content', 'title', 'subtitle', 'label', 'placeholder' );
            foreach ( $text_props as $prop ) {
                if ( ! empty( $element['data'][ $prop ] ) ) {
                    $text = $element['data'][ $prop ];
                    if ( $this->text_contains( $text, $find, $case_sensitive, $regex ) ) {
                        $matches[] = array(
                            'block_id' => $element['id'] ?? '',
                            'property' => $prop,
                            'text'     => $text,
                        );
                    }
                }
            }

            if ( ! empty( $element['children'] ) ) {
                $matches = array_merge( $matches, $this->find_text_matches( $element['children'], $find, $case_sensitive, $regex ) );
            }
        }

        return $matches;
    }

    /**
     * Verifica si texto contiene patrón
     *
     * @param string $text           Texto.
     * @param string $pattern        Patrón.
     * @param bool   $case_sensitive Case sensitive.
     * @param bool   $regex          Usar regex.
     * @return bool
     */
    private function text_contains( $text, $pattern, $case_sensitive, $regex ) {
        if ( $regex ) {
            $flags = $case_sensitive ? '' : 'i';
            return preg_match( '/' . $pattern . '/' . $flags, $text ) === 1;
        }

        if ( $case_sensitive ) {
            return strpos( $text, $pattern ) !== false;
        }

        return stripos( $text, $pattern ) !== false;
    }

    /**
     * Reemplaza texto en elementos
     *
     * @param array  $elements       Elementos.
     * @param string $find           Texto a buscar.
     * @param string $replace        Texto de reemplazo.
     * @param bool   $case_sensitive Case sensitive.
     * @param bool   $regex          Usar regex.
     * @return array
     */
    private function replace_text_in_elements( $elements, $find, $replace, $case_sensitive, $regex ) {
        $total_replacements = 0;

        foreach ( $elements as &$element ) {
            $text_props = array( 'text', 'content', 'title', 'subtitle', 'label', 'placeholder' );
            foreach ( $text_props as $prop ) {
                if ( ! empty( $element['data'][ $prop ] ) ) {
                    $original = $element['data'][ $prop ];
                    $replaced = $this->replace_text( $original, $find, $replace, $case_sensitive, $regex );
                    if ( $replaced !== $original ) {
                        $element['data'][ $prop ] = $replaced;
                        $total_replacements++;
                    }
                }
            }

            if ( ! empty( $element['children'] ) ) {
                list( $element['children'], $child_replacements ) = $this->replace_text_in_elements( $element['children'], $find, $replace, $case_sensitive, $regex );
                $total_replacements += $child_replacements;
            }
        }

        return array( $elements, $total_replacements );
    }

    /**
     * Reemplaza texto
     *
     * @param string $text           Texto.
     * @param string $find           Buscar.
     * @param string $replace        Reemplazar.
     * @param bool   $case_sensitive Case sensitive.
     * @param bool   $regex          Usar regex.
     * @return string
     */
    private function replace_text( $text, $find, $replace, $case_sensitive, $regex ) {
        if ( $regex ) {
            $flags = $case_sensitive ? '' : 'i';
            return preg_replace( '/' . $find . '/' . $flags, $replace, $text );
        }

        if ( $case_sensitive ) {
            return str_replace( $find, $replace, $text );
        }

        return str_ireplace( $find, $replace, $text );
    }
}
