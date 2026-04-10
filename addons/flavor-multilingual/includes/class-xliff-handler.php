<?php
/**
 * Manejador de archivos XLIFF
 *
 * Implementa importación y exportación de traducciones en formato XLIFF 1.2/2.0
 * XLIFF (XML Localization Interchange File Format) es el estándar de la industria
 * para intercambio de traducciones entre sistemas CAT y CMS.
 *
 * @package FlavorMultilingual
 * @since 1.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_XLIFF_Handler {

    /**
     * Instancia singleton
     *
     * @var Flavor_XLIFF_Handler|null
     */
    private static $instance = null;

    /**
     * Versión de XLIFF soportada
     *
     * @var string
     */
    private $xliff_version = '1.2';

    /**
     * Namespace XML para XLIFF
     *
     * @var string
     */
    private $xliff_namespace = 'urn:oasis:names:tc:xliff:document:1.2';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_XLIFF_Handler
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // AJAX handlers
        add_action('wp_ajax_flavor_ml_export_xliff', array($this, 'ajax_export'));
        add_action('wp_ajax_flavor_ml_import_xliff', array($this, 'ajax_import'));

        // REST API endpoints
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Página de admin
        add_action('admin_menu', array($this, 'add_admin_page'), 99);
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor-multilingual/v1', '/xliff/export', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'rest_export'),
            'permission_callback' => array($this, 'check_export_permission'),
        ));

        register_rest_route('flavor-multilingual/v1', '/xliff/import', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'rest_import'),
            'permission_callback' => array($this, 'check_import_permission'),
        ));
    }

    /**
     * Verifica permiso para exportar
     *
     * @return bool
     */
    public function check_export_permission() {
        return current_user_can('flavor_import_export_xliff') || current_user_can('manage_options');
    }

    /**
     * Verifica permiso para importar
     *
     * @return bool
     */
    public function check_import_permission() {
        return current_user_can('flavor_import_export_xliff') || current_user_can('manage_options');
    }

    // ================================================================
    // EXPORTACIÓN
    // ================================================================

    /**
     * Exporta contenido a XLIFF
     *
     * @param array $options Opciones de exportación
     * @return string XML XLIFF
     */
    public function export($options = array()) {
        $defaults = array(
            'source_lang'  => '',
            'target_lang'  => '',
            'post_ids'     => array(),
            'post_types'   => array('post', 'page'),
            'include_meta' => true,
            'status'       => array('publish'),
            'date_from'    => '',
            'date_to'      => '',
        );

        $options = wp_parse_args($options, $defaults);

        // Obtener idioma por defecto si no se especifica
        if (empty($options['source_lang'])) {
            $core = Flavor_Multilingual_Core::get_instance();
            $options['source_lang'] = $core->get_default_language();
        }

        // Obtener posts a exportar
        $posts = $this->get_posts_for_export($options);

        // Generar XLIFF
        return $this->generate_xliff($posts, $options);
    }

    /**
     * Obtiene posts para exportar
     *
     * @param array $options Opciones
     * @return array
     */
    private function get_posts_for_export($options) {
        $args = array(
            'post_type'      => $options['post_types'],
            'post_status'    => $options['status'],
            'posts_per_page' => -1,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        );

        if (!empty($options['post_ids'])) {
            $args['post__in'] = $options['post_ids'];
        }

        if (!empty($options['date_from'])) {
            $args['date_query'][] = array(
                'after' => $options['date_from'],
            );
        }

        if (!empty($options['date_to'])) {
            $args['date_query'][] = array(
                'before' => $options['date_to'],
            );
        }

        return get_posts($args);
    }

    /**
     * Genera documento XLIFF
     *
     * @param array $posts   Posts a exportar
     * @param array $options Opciones
     * @return string XML
     */
    private function generate_xliff($posts, $options) {
        $source_lang = $options['source_lang'];
        $target_lang = $options['target_lang'];

        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Elemento raíz xliff
        $xliff = $dom->createElementNS($this->xliff_namespace, 'xliff');
        $xliff->setAttribute('version', $this->xliff_version);
        $xliff->setAttribute('xmlns:flavor', 'https://flavor-platform.com/xliff');
        $dom->appendChild($xliff);

        // Archivo por cada post type
        $grouped_posts = array();
        foreach ($posts as $post) {
            $grouped_posts[$post->post_type][] = $post;
        }

        foreach ($grouped_posts as $post_type => $type_posts) {
            $file = $dom->createElement('file');
            $file->setAttribute('original', get_bloginfo('url') . '/' . $post_type);
            $file->setAttribute('source-language', $this->to_xliff_locale($source_lang));
            if ($target_lang) {
                $file->setAttribute('target-language', $this->to_xliff_locale($target_lang));
            }
            $file->setAttribute('datatype', 'html');
            $file->setAttribute('tool-id', 'flavor-multilingual');
            $file->setAttribute('tool-name', 'Flavor Multilingual');

            // Header con metadatos
            $header = $dom->createElement('header');
            $skl = $dom->createElement('skl');
            $external_file = $dom->createElement('external-file');
            $external_file->setAttribute('href', get_bloginfo('url'));
            $skl->appendChild($external_file);
            $header->appendChild($skl);

            // Información de herramienta
            $tool = $dom->createElement('tool');
            $tool->setAttribute('tool-id', 'flavor-multilingual');
            $tool->setAttribute('tool-name', 'Flavor Multilingual');
            $tool->setAttribute('tool-version', FLAVOR_MULTILINGUAL_VERSION);
            $header->appendChild($tool);

            // Fecha de exportación
            $note = $dom->createElement('note', 'Exported on ' . current_time('c'));
            $header->appendChild($note);

            $file->appendChild($header);

            // Body con unidades de traducción
            $body = $dom->createElement('body');

            foreach ($type_posts as $post) {
                $this->add_post_units($dom, $body, $post, $source_lang, $target_lang, $options);
            }

            $file->appendChild($body);
            $xliff->appendChild($file);
        }

        return $dom->saveXML();
    }

    /**
     * Añade unidades de traducción para un post
     *
     * @param DOMDocument $dom         Documento DOM
     * @param DOMElement  $body        Elemento body
     * @param WP_Post     $post        Post
     * @param string      $source_lang Idioma origen
     * @param string      $target_lang Idioma destino
     * @param array       $options     Opciones
     */
    private function add_post_units($dom, $body, $post, $source_lang, $target_lang, $options) {
        $storage = Flavor_Translation_Storage::get_instance();

        // Group para el post
        $group = $dom->createElement('group');
        $group->setAttribute('id', 'post-' . $post->ID);
        $group->setAttribute('restype', 'x-post');
        $group->setAttributeNS('https://flavor-platform.com/xliff', 'flavor:post-id', $post->ID);
        $group->setAttributeNS('https://flavor-platform.com/xliff', 'flavor:post-type', $post->post_type);

        // Campos a exportar
        $fields = array(
            'title'   => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
        );

        foreach ($fields as $field_name => $source_value) {
            if (empty($source_value)) {
                continue;
            }

            $trans_unit = $dom->createElement('trans-unit');
            $trans_unit->setAttribute('id', $post->ID . '-' . $field_name);
            $trans_unit->setAttribute('resname', $field_name);

            // Segmentar contenido largo
            if ($field_name === 'content' && strlen($source_value) > 5000) {
                $trans_unit->setAttribute('size-unit', 'char');
                $trans_unit->setAttribute('maxwidth', '5000');
            }

            // Source
            $source = $dom->createElement('source');
            $source->setAttribute('xml:lang', $this->to_xliff_locale($source_lang));

            // Usar CDATA para contenido HTML
            if ($field_name === 'content') {
                $cdata = $dom->createCDATASection($source_value);
                $source->appendChild($cdata);
            } else {
                $source->appendChild($dom->createTextNode($source_value));
            }

            $trans_unit->appendChild($source);

            // Target (si hay traducción existente)
            if ($target_lang) {
                $existing = $storage->get_translation('post', $post->ID, $target_lang, $field_name);

                $target = $dom->createElement('target');
                $target->setAttribute('xml:lang', $this->to_xliff_locale($target_lang));

                if ($existing) {
                    $target->setAttribute('state', 'translated');
                    if ($field_name === 'content') {
                        $cdata = $dom->createCDATASection($existing);
                        $target->appendChild($cdata);
                    } else {
                        $target->appendChild($dom->createTextNode($existing));
                    }
                } else {
                    $target->setAttribute('state', 'needs-translation');
                }

                $trans_unit->appendChild($target);
            }

            // Nota con contexto
            $note = $dom->createElement('note');
            $note->setAttribute('from', 'developer');
            $note->appendChild($dom->createTextNode(
                sprintf('Field: %s | Post: %s | Type: %s', $field_name, $post->post_title, $post->post_type)
            ));
            $trans_unit->appendChild($note);

            $group->appendChild($trans_unit);
        }

        // Metadatos si están habilitados
        if ($options['include_meta']) {
            $this->add_meta_units($dom, $group, $post, $source_lang, $target_lang);
        }

        $body->appendChild($group);
    }

    /**
     * Añade unidades de traducción para metadatos
     *
     * @param DOMDocument $dom         Documento DOM
     * @param DOMElement  $group       Elemento group
     * @param WP_Post     $post        Post
     * @param string      $source_lang Idioma origen
     * @param string      $target_lang Idioma destino
     */
    private function add_meta_units($dom, $group, $post, $source_lang, $target_lang) {
        // Meta fields traducibles comunes
        $translatable_meta = apply_filters('flavor_ml_translatable_meta_keys', array(
            '_yoast_wpseo_title',
            '_yoast_wpseo_metadesc',
            '_yoast_wpseo_opengraph-title',
            '_yoast_wpseo_opengraph-description',
            '_yoast_wpseo_twitter-title',
            '_yoast_wpseo_twitter-description',
            'rank_math_title',
            'rank_math_description',
        ), $post);

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($translatable_meta as $meta_key) {
            $value = get_post_meta($post->ID, $meta_key, true);

            if (empty($value)) {
                continue;
            }

            $trans_unit = $dom->createElement('trans-unit');
            $trans_unit->setAttribute('id', $post->ID . '-meta-' . sanitize_key($meta_key));
            $trans_unit->setAttribute('resname', 'meta:' . $meta_key);
            $trans_unit->setAttribute('restype', 'x-metadata');

            $source = $dom->createElement('source');
            $source->setAttribute('xml:lang', $this->to_xliff_locale($source_lang));
            $source->appendChild($dom->createTextNode($value));
            $trans_unit->appendChild($source);

            if ($target_lang) {
                $existing = $storage->get_translation('meta', $post->ID . ':' . $meta_key, $target_lang, 'value');

                $target = $dom->createElement('target');
                $target->setAttribute('xml:lang', $this->to_xliff_locale($target_lang));
                $target->setAttribute('state', $existing ? 'translated' : 'needs-translation');

                if ($existing) {
                    $target->appendChild($dom->createTextNode($existing));
                }

                $trans_unit->appendChild($target);
            }

            $group->appendChild($trans_unit);
        }
    }

    // ================================================================
    // IMPORTACIÓN
    // ================================================================

    /**
     * Importa traducciones desde XLIFF
     *
     * @param string $xliff_content Contenido XLIFF
     * @param array  $options       Opciones de importación
     * @return array Resultado de la importación
     */
    public function import($xliff_content, $options = array()) {
        $defaults = array(
            'overwrite'      => false,  // Sobrescribir traducciones existentes
            'mark_status'    => 'needs_review', // Estado para nuevas traducciones
            'dry_run'        => false,  // Solo simular, no guardar
        );

        $options = wp_parse_args($options, $defaults);

        // Parsear XLIFF
        $parsed = $this->parse_xliff($xliff_content);

        if (is_wp_error($parsed)) {
            return $parsed;
        }

        $results = array(
            'imported'  => 0,
            'skipped'   => 0,
            'errors'    => 0,
            'details'   => array(),
        );

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($parsed['units'] as $unit) {
            // Extraer información
            $post_id = $unit['post_id'] ?? 0;
            $field = $unit['field'] ?? '';
            $target_lang = $unit['target_lang'] ?? '';
            $translation = $unit['target'] ?? '';

            if (!$post_id || !$field || !$target_lang || empty($translation)) {
                $results['skipped']++;
                continue;
            }

            // Verificar si el post existe
            $post = get_post($post_id);
            if (!$post) {
                $results['errors']++;
                $results['details'][] = array(
                    'post_id' => $post_id,
                    'error'   => __('Post no encontrado', 'flavor-multilingual'),
                );
                continue;
            }

            // Verificar si ya existe traducción
            if (!$options['overwrite']) {
                $existing = $storage->get_translation('post', $post_id, $target_lang, $field);
                if ($existing) {
                    $results['skipped']++;
                    $results['details'][] = array(
                        'post_id' => $post_id,
                        'field'   => $field,
                        'status'  => 'skipped',
                        'reason'  => __('Ya existe traducción', 'flavor-multilingual'),
                    );
                    continue;
                }
            }

            // Guardar traducción (si no es dry run)
            if (!$options['dry_run']) {
                $saved = $storage->save_translation('post', $post_id, $target_lang, $field, $translation, array(
                    'status' => $options['mark_status'],
                    'auto'   => false,
                    'source' => 'xliff_import',
                ));

                if ($saved) {
                    $results['imported']++;
                    $results['details'][] = array(
                        'post_id' => $post_id,
                        'field'   => $field,
                        'lang'    => $target_lang,
                        'status'  => 'imported',
                    );
                } else {
                    $results['errors']++;
                }
            } else {
                $results['imported']++;
                $results['details'][] = array(
                    'post_id' => $post_id,
                    'field'   => $field,
                    'lang'    => $target_lang,
                    'status'  => 'would_import',
                );
            }
        }

        return $results;
    }

    /**
     * Parsea documento XLIFF
     *
     * @param string $xliff_content Contenido XLIFF
     * @return array|WP_Error Datos parseados o error
     */
    private function parse_xliff($xliff_content) {
        libxml_use_internal_errors(true);

        // FIX: Deshabilitar entidades externas para prevenir XXE
        $previous_value = libxml_disable_entity_loader(true);

        $dom = new DOMDocument();
        // FIX: Usar flags seguros que previenen XXE
        $loaded = $dom->loadXML($xliff_content, LIBXML_NONET | LIBXML_NOENT);

        // Restaurar valor anterior
        libxml_disable_entity_loader($previous_value);

        if (!$loaded) {
            $errors = libxml_get_errors();
            libxml_clear_errors();

            $error_messages = array();
            foreach ($errors as $error) {
                $error_messages[] = trim($error->message);
            }

            return new WP_Error(
                'xliff_parse_error',
                __('Error al parsear XLIFF: ', 'flavor-multilingual') . implode(', ', $error_messages)
            );
        }

        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('xliff', $this->xliff_namespace);
        $xpath->registerNamespace('flavor', 'https://flavor-platform.com/xliff');

        $result = array(
            'source_lang' => '',
            'target_lang' => '',
            'units'       => array(),
        );

        // Obtener idiomas del primer file
        $files = $xpath->query('//xliff:file');
        if ($files->length > 0) {
            $first_file = $files->item(0);
            $result['source_lang'] = $this->from_xliff_locale($first_file->getAttribute('source-language'));
            $result['target_lang'] = $this->from_xliff_locale($first_file->getAttribute('target-language'));
        }

        // Obtener todas las trans-unit
        $trans_units = $xpath->query('//xliff:trans-unit');

        foreach ($trans_units as $unit) {
            $id = $unit->getAttribute('id');
            $resname = $unit->getAttribute('resname');

            // Extraer post_id del id o del grupo padre
            $post_id = 0;
            $field = $resname;

            // Intentar extraer del ID (formato: post_id-field)
            if (preg_match('/^(\d+)-(.+)$/', $id, $matches)) {
                $post_id = (int) $matches[1];
                if (empty($field)) {
                    $field = $matches[2];
                }
            }

            // Intentar obtener del grupo padre
            if (!$post_id) {
                $group = $xpath->query('ancestor::xliff:group[@flavor:post-id]', $unit);
                if ($group->length > 0) {
                    $post_id = (int) $group->item(0)->getAttributeNS('https://flavor-platform.com/xliff', 'post-id');
                }
            }

            // Obtener source y target
            $source_node = $xpath->query('xliff:source', $unit)->item(0);
            $target_node = $xpath->query('xliff:target', $unit)->item(0);

            $source = $source_node ? $this->get_node_text($source_node) : '';
            $target = $target_node ? $this->get_node_text($target_node) : '';
            $target_lang = $target_node ? $this->from_xliff_locale($target_node->getAttribute('xml:lang')) : $result['target_lang'];

            // Verificar si es meta
            if (strpos($field, 'meta:') === 0) {
                $field = substr($field, 5); // Quitar prefijo 'meta:'
            }

            $result['units'][] = array(
                'id'          => $id,
                'post_id'     => $post_id,
                'field'       => $field,
                'source'      => $source,
                'target'      => $target,
                'target_lang' => $target_lang ?: $result['target_lang'],
            );
        }

        return $result;
    }

    /**
     * Obtiene el texto de un nodo (incluyendo CDATA)
     *
     * @param DOMNode $node Nodo
     * @return string
     */
    private function get_node_text($node) {
        $text = '';

        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_CDATA_SECTION_NODE) {
                $text .= $child->nodeValue;
            } elseif ($child->nodeType === XML_TEXT_NODE) {
                $text .= $child->nodeValue;
            }
        }

        return trim($text);
    }

    // ================================================================
    // CONVERSIÓN DE LOCALES
    // ================================================================

    /**
     * Convierte código de idioma a formato XLIFF (BCP 47)
     *
     * @param string $lang Código de idioma
     * @return string
     */
    private function to_xliff_locale($lang) {
        $mapping = array(
            'es' => 'es-ES',
            'en' => 'en-US',
            'eu' => 'eu-ES',
            'ca' => 'ca-ES',
            'gl' => 'gl-ES',
            'fr' => 'fr-FR',
            'de' => 'de-DE',
            'it' => 'it-IT',
            'pt' => 'pt-PT',
            'zh' => 'zh-CN',
            'ja' => 'ja-JP',
            'ar' => 'ar-SA',
        );

        return isset($mapping[$lang]) ? $mapping[$lang] : $lang;
    }

    /**
     * Convierte código XLIFF a código interno
     *
     * @param string $xliff_locale Código XLIFF
     * @return string
     */
    private function from_xliff_locale($xliff_locale) {
        // Extraer solo la parte del idioma
        $parts = explode('-', $xliff_locale);
        return strtolower($parts[0]);
    }

    // ================================================================
    // AJAX / REST HANDLERS
    // ================================================================

    /**
     * AJAX: Exportar a XLIFF
     */
    public function ajax_export() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!$this->check_export_permission()) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $options = array(
            'source_lang'  => sanitize_key($_POST['source_lang'] ?? ''),
            'target_lang'  => sanitize_key($_POST['target_lang'] ?? ''),
            'post_ids'     => array_map('intval', (array) ($_POST['post_ids'] ?? array())),
            'post_types'   => array_map('sanitize_key', (array) ($_POST['post_types'] ?? array('post', 'page'))),
            'include_meta' => !empty($_POST['include_meta']),
        );

        $xliff = $this->export($options);

        // Generar nombre de archivo
        $filename = sprintf(
            'translations_%s_%s_%s.xliff',
            $options['source_lang'] ?: 'all',
            $options['target_lang'] ?: 'all',
            date('Y-m-d_His')
        );

        wp_send_json_success(array(
            'xliff'    => $xliff,
            'filename' => $filename,
        ));
    }

    /**
     * AJAX: Importar desde XLIFF
     */
    public function ajax_import() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!$this->check_import_permission()) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        if (!isset($_FILES['xliff_file'])) {
            wp_send_json_error(__('No se ha subido ningún archivo', 'flavor-multilingual'));
        }

        $file = $_FILES['xliff_file'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(__('Error al subir el archivo', 'flavor-multilingual'));
        }

        // Verificar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, array('xliff', 'xlf', 'xml'))) {
            wp_send_json_error(__('Formato de archivo no válido. Use .xliff, .xlf o .xml', 'flavor-multilingual'));
        }

        $content = file_get_contents($file['tmp_name']);

        $options = array(
            'overwrite'   => !empty($_POST['overwrite']),
            'mark_status' => sanitize_key($_POST['mark_status'] ?? 'needs_review'),
            'dry_run'     => !empty($_POST['dry_run']),
        );

        $result = $this->import($content, $options);

        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        }

        wp_send_json_success($result);
    }

    /**
     * REST API: Exportar
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_export($request) {
        $options = array(
            'source_lang'  => $request->get_param('source_lang') ?: '',
            'target_lang'  => $request->get_param('target_lang') ?: '',
            'post_ids'     => $request->get_param('post_ids') ?: array(),
            'post_types'   => $request->get_param('post_types') ?: array('post', 'page'),
            'include_meta' => (bool) $request->get_param('include_meta'),
        );

        $xliff = $this->export($options);

        return new WP_REST_Response(array(
            'success' => true,
            'xliff'   => $xliff,
        ));
    }

    /**
     * REST API: Importar
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function rest_import($request) {
        $xliff_content = $request->get_param('xliff');

        if (empty($xliff_content)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => __('No se proporcionó contenido XLIFF', 'flavor-multilingual'),
            ), 400);
        }

        $options = array(
            'overwrite'   => (bool) $request->get_param('overwrite'),
            'mark_status' => $request->get_param('mark_status') ?: 'needs_review',
            'dry_run'     => (bool) $request->get_param('dry_run'),
        );

        $result = $this->import($xliff_content, $options);

        if (is_wp_error($result)) {
            return new WP_REST_Response(array(
                'success' => false,
                'error'   => $result->get_error_message(),
            ), 400);
        }

        return new WP_REST_Response(array(
            'success' => true,
            'result'  => $result,
        ));
    }

    // ================================================================
    // ADMIN UI
    // ================================================================

    /**
     * Añade página de administración
     */
    public function add_admin_page() {
        add_submenu_page(
            'flavor-multilingual',
            __('Importar/Exportar XLIFF', 'flavor-multilingual'),
            __('XLIFF', 'flavor-multilingual'),
            'flavor_import_export_xliff',
            'flavor-ml-xliff',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Renderiza página de administración
     */
    public function render_admin_page() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Importar/Exportar XLIFF', 'flavor-multilingual'); ?></h1>

            <div class="flavor-ml-xliff-container" style="display: flex; gap: 30px; margin-top: 20px;">
                <!-- Exportar -->
                <div class="flavor-ml-xliff-export" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h2><?php echo esc_html__('Exportar traducciones', 'flavor-multilingual'); ?></h2>

                    <form id="flavor-ml-export-form">
                        <table class="form-table">
                            <tr>
                                <th><?php echo esc_html__('Idioma origen', 'flavor-multilingual'); ?></th>
                                <td>
                                    <select name="source_lang" id="xliff-source-lang">
                                        <?php foreach ($languages as $code => $lang) : ?>
                                            <option value="<?php echo esc_attr($code); ?>" <?php selected($code, $default_lang); ?>>
                                                <?php echo esc_html($lang['native_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Idioma destino', 'flavor-multilingual'); ?></th>
                                <td>
                                    <select name="target_lang" id="xliff-target-lang">
                                        <option value=""><?php echo esc_html__('-- Todos --', 'flavor-multilingual'); ?></option>
                                        <?php foreach ($languages as $code => $lang) : ?>
                                            <?php if ($code !== $default_lang) : ?>
                                                <option value="<?php echo esc_attr($code); ?>">
                                                    <?php echo esc_html($lang['native_name']); ?>
                                                </option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Tipos de contenido', 'flavor-multilingual'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="post_types[]" value="post" checked>
                                        <?php echo esc_html__('Entradas', 'flavor-multilingual'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="post_types[]" value="page" checked>
                                        <?php echo esc_html__('Páginas', 'flavor-multilingual'); ?>
                                    </label>
                                    <?php
                                    $custom_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                                    foreach ($custom_types as $type) :
                                    ?>
                                        <br><label>
                                            <input type="checkbox" name="post_types[]" value="<?php echo esc_attr($type->name); ?>">
                                            <?php echo esc_html($type->labels->name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Opciones', 'flavor-multilingual'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="include_meta" value="1" checked>
                                        <?php echo esc_html__('Incluir metadatos SEO', 'flavor-multilingual'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <?php wp_nonce_field('flavor_multilingual', 'nonce'); ?>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Exportar XLIFF', 'flavor-multilingual'); ?>
                            </button>
                        </p>
                    </form>
                </div>

                <!-- Importar -->
                <div class="flavor-ml-xliff-import" style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
                    <h2><?php echo esc_html__('Importar traducciones', 'flavor-multilingual'); ?></h2>

                    <form id="flavor-ml-import-form" enctype="multipart/form-data">
                        <table class="form-table">
                            <tr>
                                <th><?php echo esc_html__('Archivo XLIFF', 'flavor-multilingual'); ?></th>
                                <td>
                                    <input type="file" name="xliff_file" accept=".xliff,.xlf,.xml" required>
                                    <p class="description">
                                        <?php echo esc_html__('Formatos aceptados: .xliff, .xlf, .xml', 'flavor-multilingual'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Estado de traducciones', 'flavor-multilingual'); ?></th>
                                <td>
                                    <select name="mark_status">
                                        <option value="needs_review"><?php echo esc_html__('Necesita revisión', 'flavor-multilingual'); ?></option>
                                        <option value="approved"><?php echo esc_html__('Aprobada', 'flavor-multilingual'); ?></option>
                                        <option value="published"><?php echo esc_html__('Publicada', 'flavor-multilingual'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php echo esc_html__('Opciones', 'flavor-multilingual'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="overwrite" value="1">
                                        <?php echo esc_html__('Sobrescribir traducciones existentes', 'flavor-multilingual'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="dry_run" value="1">
                                        <?php echo esc_html__('Solo simular (no guardar)', 'flavor-multilingual'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>

                        <?php wp_nonce_field('flavor_multilingual', 'nonce'); ?>

                        <p class="submit">
                            <button type="submit" class="button button-primary">
                                <?php echo esc_html__('Importar XLIFF', 'flavor-multilingual'); ?>
                            </button>
                        </p>
                    </form>

                    <div id="flavor-ml-import-results" style="display: none; margin-top: 20px; padding: 15px; background: #f0f0f0;">
                    </div>
                </div>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Exportar
            $('#flavor-ml-export-form').on('submit', function(e) {
                e.preventDefault();

                var $btn = $(this).find('button[type="submit"]');
                $btn.prop('disabled', true).text('<?php echo esc_js(__('Exportando...', 'flavor-multilingual')); ?>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: $(this).serialize() + '&action=flavor_ml_export_xliff',
                    success: function(response) {
                        if (response.success) {
                            // Descargar archivo
                            var blob = new Blob([response.data.xliff], {type: 'application/xml'});
                            var link = document.createElement('a');
                            link.href = window.URL.createObjectURL(blob);
                            link.download = response.data.filename;
                            link.click();
                        } else {
                            alert(response.data || '<?php echo esc_js(__('Error al exportar', 'flavor-multilingual')); ?>');
                        }
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('Error de conexión', 'flavor-multilingual')); ?>');
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Exportar XLIFF', 'flavor-multilingual')); ?>');
                    }
                });
            });

            // Importar
            $('#flavor-ml-import-form').on('submit', function(e) {
                e.preventDefault();

                var $btn = $(this).find('button[type="submit"]');
                var $results = $('#flavor-ml-import-results');

                $btn.prop('disabled', true).text('<?php echo esc_js(__('Importando...', 'flavor-multilingual')); ?>');
                $results.hide();

                var formData = new FormData(this);
                formData.append('action', 'flavor_ml_import_xliff');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        if (response.success) {
                            var html = '<h4><?php echo esc_js(__('Resultado de la importación', 'flavor-multilingual')); ?></h4>';
                            html += '<p><strong><?php echo esc_js(__('Importados:', 'flavor-multilingual')); ?></strong> ' + response.data.imported + '</p>';
                            html += '<p><strong><?php echo esc_js(__('Omitidos:', 'flavor-multilingual')); ?></strong> ' + response.data.skipped + '</p>';
                            html += '<p><strong><?php echo esc_js(__('Errores:', 'flavor-multilingual')); ?></strong> ' + response.data.errors + '</p>';

                            $results.html(html).show();
                        } else {
                            $results.html('<p class="error" style="color:red;">' + (response.data || '<?php echo esc_js(__('Error al importar', 'flavor-multilingual')); ?>') + '</p>').show();
                        }
                    },
                    error: function() {
                        $results.html('<p class="error" style="color:red;"><?php echo esc_js(__('Error de conexión', 'flavor-multilingual')); ?></p>').show();
                    },
                    complete: function() {
                        $btn.prop('disabled', false).text('<?php echo esc_js(__('Importar XLIFF', 'flavor-multilingual')); ?>');
                    }
                });
            });
        });
        </script>
        <?php
    }
}
