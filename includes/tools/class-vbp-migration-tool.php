<?php
/**
 * Herramienta de migración para VBP
 *
 * Migra contenido del editor legacy a VBP.
 *
 * @package Flavor_Chat_IA
 * @since 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Clase de migración VBP
 */
class Flavor_VBP_Migration_Tool {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Meta keys de editores legacy
     */
    const LEGACY_LANDING_META = '_flavor_landing_structure';
    const LEGACY_BUILDER_META = '_flavor_page_layout';

    /**
     * Meta key de VBP
     */
    const VBP_META = '_flavor_vbp_data';

    /**
     * Obtiene la instancia
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', array( $this, 'registrar_menu' ) );
        add_action( 'wp_ajax_vbp_migration_scan', array( $this, 'ajax_scan' ) );
        add_action( 'wp_ajax_vbp_migration_migrate', array( $this, 'ajax_migrate' ) );
        add_action( 'wp_ajax_vbp_migration_migrate_all', array( $this, 'ajax_migrate_all' ) );
    }

    /**
     * Registra página de migración en el menú
     */
    public function registrar_menu() {
        add_submenu_page(
            null, // Oculto del menú
            __( 'Migración VBP', 'flavor-chat-ia' ),
            __( 'Migración VBP', 'flavor-chat-ia' ),
            'manage_options',
            'vbp-migration',
            array( $this, 'renderizar_pagina' )
        );
    }

    /**
     * Escanea contenido legacy
     */
    public function ajax_scan() {
        check_ajax_referer( 'vbp_migration_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos' );
        }

        global $wpdb;

        // Buscar landings con estructura legacy
        $legacy_landings = $wpdb->get_results( $wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type, p.post_status, pm.meta_key
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key IN (%s, %s)
             AND p.post_status != 'trash'
             ORDER BY p.post_modified DESC",
            self::LEGACY_LANDING_META,
            self::LEGACY_BUILDER_META
        ) );

        // Verificar cuáles ya tienen datos VBP
        $results = array();
        foreach ( $legacy_landings as $post ) {
            $has_vbp = get_post_meta( $post->ID, self::VBP_META, true );
            $results[] = array(
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'post_type'   => $post->post_type,
                'status'      => $post->post_status,
                'legacy_type' => $post->meta_key === self::LEGACY_LANDING_META ? 'landing_editor' : 'page_builder',
                'has_vbp'     => ! empty( $has_vbp ),
                'edit_url'    => admin_url( 'admin.php?page=vbp-editor&post_id=' . $post->ID ),
            );
        }

        wp_send_json_success( array(
            'total'   => count( $results ),
            'items'   => $results,
            'summary' => array(
                'landing_editor' => count( array_filter( $results, function( $r ) { return $r['legacy_type'] === 'landing_editor'; } ) ),
                'page_builder'   => count( array_filter( $results, function( $r ) { return $r['legacy_type'] === 'page_builder'; } ) ),
                'already_vbp'    => count( array_filter( $results, function( $r ) { return $r['has_vbp']; } ) ),
            ),
        ) );
    }

    /**
     * Migra un post individual
     */
    public function ajax_migrate() {
        check_ajax_referer( 'vbp_migration_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos' );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );
        if ( ! $post_id ) {
            wp_send_json_error( 'ID de post inválido' );
        }

        $result = $this->migrar_post( $post_id );

        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }

        wp_send_json_success( $result );
    }

    /**
     * Migra todos los posts legacy
     */
    public function ajax_migrate_all() {
        check_ajax_referer( 'vbp_migration_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Sin permisos' );
        }

        global $wpdb;

        $legacy_posts = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT p.ID
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key IN (%s, %s)
             AND p.post_status != 'trash'",
            self::LEGACY_LANDING_META,
            self::LEGACY_BUILDER_META
        ) );

        $migrated = 0;
        $errors = array();

        foreach ( $legacy_posts as $post_id ) {
            $result = $this->migrar_post( intval( $post_id ) );
            if ( is_wp_error( $result ) ) {
                $errors[] = array(
                    'post_id' => $post_id,
                    'error'   => $result->get_error_message(),
                );
            } else {
                $migrated++;
            }
        }

        wp_send_json_success( array(
            'total'    => count( $legacy_posts ),
            'migrated' => $migrated,
            'errors'   => $errors,
        ) );
    }

    /**
     * Migra un post de formato legacy a VBP
     *
     * @param int $post_id ID del post.
     * @return array|WP_Error
     */
    public function migrar_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'not_found', 'Post no encontrado' );
        }

        // Verificar si ya tiene VBP
        $vbp_existente = get_post_meta( $post_id, self::VBP_META, true );
        if ( ! empty( $vbp_existente ) ) {
            return array(
                'status'  => 'skipped',
                'message' => 'Ya tiene datos VBP',
                'post_id' => $post_id,
            );
        }

        // Obtener datos legacy
        $landing_structure = get_post_meta( $post_id, self::LEGACY_LANDING_META, true );
        $page_layout = get_post_meta( $post_id, self::LEGACY_BUILDER_META, true );

        $legacy_data = null;
        $legacy_type = null;

        if ( ! empty( $landing_structure ) ) {
            $legacy_data = $landing_structure;
            $legacy_type = 'landing_editor';
        } elseif ( ! empty( $page_layout ) ) {
            $legacy_data = $page_layout;
            $legacy_type = 'page_builder';
        }

        if ( empty( $legacy_data ) ) {
            return new WP_Error( 'no_legacy_data', 'No hay datos legacy para migrar' );
        }

        // Convertir a formato VBP
        $vbp_data = $this->convertir_a_vbp( $legacy_data, $legacy_type );

        // Guardar en formato VBP
        update_post_meta( $post_id, self::VBP_META, $vbp_data );

        // Marcar legacy como migrado (añadir sufijo)
        $legacy_meta_key = $legacy_type === 'landing_editor' ? self::LEGACY_LANDING_META : self::LEGACY_BUILDER_META;
        update_post_meta( $post_id, $legacy_meta_key . '_migrated', $legacy_data );

        return array(
            'status'      => 'migrated',
            'post_id'     => $post_id,
            'legacy_type' => $legacy_type,
            'elements'    => count( $vbp_data['elements'] ?? array() ),
        );
    }

    /**
     * Convierte datos legacy a formato VBP
     *
     * @param mixed  $legacy_data Datos legacy.
     * @param string $legacy_type Tipo de editor legacy.
     * @return array
     */
    private function convertir_a_vbp( $legacy_data, $legacy_type ) {
        $elements = array();
        $settings = array(
            'pageWidth'       => 1200,
            'backgroundColor' => '#ffffff',
            'migrated_from'   => $legacy_type,
            'migrated_at'     => current_time( 'mysql' ),
        );

        if ( is_string( $legacy_data ) ) {
            $legacy_data = json_decode( $legacy_data, true );
        }

        if ( ! is_array( $legacy_data ) ) {
            $legacy_data = array();
        }

        // Convertir secciones según el tipo de editor
        if ( 'landing_editor' === $legacy_type ) {
            $elements = $this->convertir_landing_editor( $legacy_data );
        } else {
            $elements = $this->convertir_page_builder( $legacy_data );
        }

        return array(
            'elements' => $elements,
            'settings' => $settings,
            'version'  => '2.3.0',
        );
    }

    /**
     * Convierte estructura del Landing Editor a VBP
     *
     * @param array $data Datos del landing editor.
     * @return array
     */
    private function convertir_landing_editor( $data ) {
        $elements = array();
        $sections = $data['sections'] ?? $data;

        if ( ! is_array( $sections ) ) {
            return $elements;
        }

        foreach ( $sections as $index => $section ) {
            $type = $section['type'] ?? 'section';
            $element = array(
                'id'       => 'migrated_' . $index . '_' . uniqid(),
                'type'     => $this->mapear_tipo_seccion( $type ),
                'data'     => $this->convertir_datos_seccion( $section ),
                'styles'   => $section['styles'] ?? array(),
                'children' => array(),
            );

            // Convertir contenido anidado
            if ( ! empty( $section['content'] ) ) {
                $element['children'] = $this->convertir_contenido_anidado( $section['content'] );
            }

            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * Convierte estructura del Page Builder a VBP
     *
     * @param array $data Datos del page builder.
     * @return array
     */
    private function convertir_page_builder( $data ) {
        $elements = array();
        $rows = $data['rows'] ?? $data;

        if ( ! is_array( $rows ) ) {
            return $elements;
        }

        foreach ( $rows as $index => $row ) {
            $element = array(
                'id'       => 'migrated_row_' . $index . '_' . uniqid(),
                'type'     => 'row',
                'data'     => array(),
                'styles'   => $row['styles'] ?? array(),
                'children' => array(),
            );

            // Convertir columnas
            if ( ! empty( $row['columns'] ) ) {
                foreach ( $row['columns'] as $col_index => $column ) {
                    $col_element = array(
                        'id'       => 'migrated_col_' . $col_index . '_' . uniqid(),
                        'type'     => 'column',
                        'data'     => array( 'width' => $column['width'] ?? '100%' ),
                        'styles'   => $column['styles'] ?? array(),
                        'children' => $this->convertir_contenido_anidado( $column['elements'] ?? array() ),
                    );
                    $element['children'][] = $col_element;
                }
            }

            $elements[] = $element;
        }

        return $elements;
    }

    /**
     * Convierte contenido anidado
     *
     * @param array $content Contenido.
     * @return array
     */
    private function convertir_contenido_anidado( $content ) {
        $children = array();

        if ( ! is_array( $content ) ) {
            return $children;
        }

        foreach ( $content as $index => $item ) {
            $type = $item['type'] ?? 'text';
            $children[] = array(
                'id'       => 'migrated_child_' . $index . '_' . uniqid(),
                'type'     => $this->mapear_tipo_elemento( $type ),
                'data'     => $this->convertir_datos_elemento( $item ),
                'styles'   => $item['styles'] ?? array(),
                'children' => array(),
            );
        }

        return $children;
    }

    /**
     * Mapea tipo de sección legacy a VBP
     *
     * @param string $type Tipo legacy.
     * @return string
     */
    private function mapear_tipo_seccion( $type ) {
        $map = array(
            'hero'        => 'section',
            'features'    => 'section',
            'cta'         => 'section',
            'testimonials'=> 'section',
            'pricing'     => 'section',
            'faq'         => 'section',
            'contact'     => 'section',
            'gallery'     => 'section',
        );
        return $map[ $type ] ?? 'section';
    }

    /**
     * Mapea tipo de elemento legacy a VBP
     *
     * @param string $type Tipo legacy.
     * @return string
     */
    private function mapear_tipo_elemento( $type ) {
        $map = array(
            'titulo'     => 'heading',
            'texto'      => 'text',
            'parrafo'    => 'text',
            'imagen'     => 'image',
            'boton'      => 'button',
            'video'      => 'video',
            'formulario' => 'form',
            'lista'      => 'list',
            'separador'  => 'divider',
            'espacio'    => 'spacer',
            'icono'      => 'icon',
        );
        return $map[ $type ] ?? $type;
    }

    /**
     * Convierte datos de sección
     *
     * @param array $section Sección.
     * @return array
     */
    private function convertir_datos_seccion( $section ) {
        $data = array();

        if ( isset( $section['title'] ) || isset( $section['titulo'] ) ) {
            $data['title'] = $section['title'] ?? $section['titulo'] ?? '';
        }
        if ( isset( $section['subtitle'] ) || isset( $section['subtitulo'] ) ) {
            $data['subtitle'] = $section['subtitle'] ?? $section['subtitulo'] ?? '';
        }
        if ( isset( $section['background'] ) ) {
            $data['background'] = $section['background'];
        }

        return $data;
    }

    /**
     * Convierte datos de elemento
     *
     * @param array $item Elemento.
     * @return array
     */
    private function convertir_datos_elemento( $item ) {
        $data = array();

        // Mapear campos comunes
        $field_map = array(
            'texto'     => 'text',
            'text'      => 'text',
            'content'   => 'text',
            'contenido' => 'text',
            'titulo'    => 'title',
            'title'     => 'title',
            'url'       => 'url',
            'link'      => 'url',
            'src'       => 'src',
            'imagen'    => 'src',
            'image'     => 'src',
        );

        foreach ( $field_map as $legacy_key => $vbp_key ) {
            if ( isset( $item[ $legacy_key ] ) ) {
                $data[ $vbp_key ] = $item[ $legacy_key ];
            }
        }

        return $data;
    }

    /**
     * Renderiza página de migración
     */
    public function renderizar_pagina() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Migración a Visual Builder Pro', 'flavor-chat-ia' ); ?></h1>

            <div class="notice notice-info">
                <p><?php esc_html_e( 'Esta herramienta migra contenido creado con editores legacy al formato VBP.', 'flavor-chat-ia' ); ?></p>
            </div>

            <div id="vbp-migration-app">
                <div class="vbp-migration-actions" style="margin: 20px 0;">
                    <button type="button" class="button button-primary" id="vbp-scan-btn">
                        <?php esc_html_e( 'Escanear contenido legacy', 'flavor-chat-ia' ); ?>
                    </button>
                    <button type="button" class="button" id="vbp-migrate-all-btn" disabled>
                        <?php esc_html_e( 'Migrar todo', 'flavor-chat-ia' ); ?>
                    </button>
                </div>

                <div id="vbp-migration-results" style="display: none;">
                    <h2><?php esc_html_e( 'Resultados del escaneo', 'flavor-chat-ia' ); ?></h2>
                    <div id="vbp-migration-summary"></div>
                    <table class="wp-list-table widefat fixed striped" id="vbp-migration-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'ID', 'flavor-chat-ia' ); ?></th>
                                <th><?php esc_html_e( 'Título', 'flavor-chat-ia' ); ?></th>
                                <th><?php esc_html_e( 'Tipo', 'flavor-chat-ia' ); ?></th>
                                <th><?php esc_html_e( 'Editor Legacy', 'flavor-chat-ia' ); ?></th>
                                <th><?php esc_html_e( 'VBP', 'flavor-chat-ia' ); ?></th>
                                <th><?php esc_html_e( 'Acciones', 'flavor-chat-ia' ); ?></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div id="vbp-migration-log" style="display: none; margin-top: 20px;">
                    <h3><?php esc_html_e( 'Log de migración', 'flavor-chat-ia' ); ?></h3>
                    <pre style="background: #f1f1f1; padding: 15px; max-height: 300px; overflow: auto;"></pre>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            var nonce = '<?php echo wp_create_nonce( 'vbp_migration_nonce' ); ?>';

            $('#vbp-scan-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true).text('Escaneando...');

                $.post(ajaxurl, {
                    action: 'vbp_migration_scan',
                    nonce: nonce
                }, function(response) {
                    $btn.prop('disabled', false).text('Escanear contenido legacy');

                    if (response.success) {
                        renderResults(response.data);
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            $('#vbp-migrate-all-btn').on('click', function() {
                if (!confirm('¿Migrar todo el contenido legacy a VBP?')) return;

                var $btn = $(this);
                $btn.prop('disabled', true).text('Migrando...');

                $.post(ajaxurl, {
                    action: 'vbp_migration_migrate_all',
                    nonce: nonce
                }, function(response) {
                    $btn.prop('disabled', false).text('Migrar todo');

                    if (response.success) {
                        var data = response.data;
                        log('Migración completada: ' + data.migrated + '/' + data.total);
                        if (data.errors.length > 0) {
                            log('Errores: ' + JSON.stringify(data.errors));
                        }
                        $('#vbp-scan-btn').click(); // Refrescar
                    } else {
                        alert('Error: ' + response.data);
                    }
                });
            });

            function renderResults(data) {
                $('#vbp-migration-results').show();
                $('#vbp-migrate-all-btn').prop('disabled', data.total === 0);

                var summaryHtml = '<p><strong>Total:</strong> ' + data.total + ' | ';
                summaryHtml += '<strong>Landing Editor:</strong> ' + data.summary.landing_editor + ' | ';
                summaryHtml += '<strong>Page Builder:</strong> ' + data.summary.page_builder + ' | ';
                summaryHtml += '<strong>Ya en VBP:</strong> ' + data.summary.already_vbp + '</p>';
                $('#vbp-migration-summary').html(summaryHtml);

                var tbody = $('#vbp-migration-table tbody');
                tbody.empty();

                data.items.forEach(function(item) {
                    var row = '<tr>';
                    row += '<td>' + item.id + '</td>';
                    row += '<td><a href="' + item.edit_url + '">' + item.title + '</a></td>';
                    row += '<td>' + item.post_type + '</td>';
                    row += '<td>' + item.legacy_type + '</td>';
                    row += '<td>' + (item.has_vbp ? '✅' : '❌') + '</td>';
                    row += '<td>';
                    if (!item.has_vbp) {
                        row += '<button class="button button-small migrate-single" data-id="' + item.id + '">Migrar</button>';
                    } else {
                        row += '<span class="dashicons dashicons-yes-alt" style="color: green;"></span>';
                    }
                    row += '</td>';
                    row += '</tr>';
                    tbody.append(row);
                });

                // Handler para migrar individual
                $('.migrate-single').on('click', function() {
                    var $btn = $(this);
                    var postId = $btn.data('id');
                    $btn.prop('disabled', true).text('...');

                    $.post(ajaxurl, {
                        action: 'vbp_migration_migrate',
                        nonce: nonce,
                        post_id: postId
                    }, function(response) {
                        if (response.success) {
                            log('Migrado: ' + postId + ' - ' + response.data.status);
                            $btn.replaceWith('<span class="dashicons dashicons-yes-alt" style="color: green;"></span>');
                        } else {
                            log('Error en ' + postId + ': ' + response.data);
                            $btn.prop('disabled', false).text('Reintentar');
                        }
                    });
                });
            }

            function log(msg) {
                var $log = $('#vbp-migration-log');
                $log.show();
                $log.find('pre').append(new Date().toLocaleTimeString() + ' - ' + msg + '\n');
            }
        });
        </script>
        <?php
    }
}

// Inicializar si estamos en admin
if ( is_admin() ) {
    Flavor_VBP_Migration_Tool::get_instance();
}
