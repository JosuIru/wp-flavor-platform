<?php
/**
 * Manejador de archivos PO/MO
 *
 * Permite importar y exportar traducciones en formato PO/MO estándar
 * compatible con herramientas profesionales de traducción.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_PO_MO_Handler {

    /**
     * Instancia singleton
     *
     * @var Flavor_PO_MO_Handler|null
     */
    private static $instance = null;

    /**
     * Directorio de traducciones
     *
     * @var string
     */
    private $translations_dir;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_PO_MO_Handler
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
        $upload_dir = wp_upload_dir();
        $this->translations_dir = $upload_dir['basedir'] . '/flavor-translations';

        // Crear directorio si no existe
        if (!file_exists($this->translations_dir)) {
            wp_mkdir_p($this->translations_dir);
        }

        // AJAX handlers
        if (is_admin()) {
            add_action('wp_ajax_flavor_ml_export_po', array($this, 'ajax_export_po'));
            add_action('wp_ajax_flavor_ml_export_mo', array($this, 'ajax_export_mo'));
            add_action('wp_ajax_flavor_ml_import_po', array($this, 'ajax_import_po'));
            add_action('wp_ajax_flavor_ml_import_translations', array($this, 'ajax_import_translations'));
            add_action('wp_ajax_flavor_ml_translate_po_ai', array($this, 'ajax_translate_po_ai'));
        }
    }

    /**
     * Exporta traducciones a formato PO
     *
     * @param string $lang          Código de idioma
     * @param string $type          Tipo de contenido (all, posts, strings, terms)
     * @param bool   $include_empty Incluir cadenas sin traducir
     * @return string Contenido del archivo PO
     */
    public function export_to_po($lang, $type = 'all', $include_empty = true) {
        $core = Flavor_Multilingual_Core::get_instance();
        $language = $core->get_language($lang);

        if (!$language) {
            return '';
        }

        $po_content = $this->generate_po_header($lang, $language);

        // Exportar strings
        if ($type === 'all' || $type === 'strings') {
            $po_content .= $this->export_strings_to_po($lang, $include_empty);
        }

        // Exportar posts
        if ($type === 'all' || $type === 'posts') {
            $po_content .= $this->export_posts_to_po($lang, $include_empty);
        }

        // Exportar términos
        if ($type === 'all' || $type === 'terms') {
            $po_content .= $this->export_terms_to_po($lang, $include_empty);
        }

        return $po_content;
    }

    /**
     * Genera el encabezado del archivo PO
     *
     * @param string $lang     Código de idioma
     * @param array  $language Datos del idioma
     * @return string
     */
    private function generate_po_header($lang, $language) {
        $locale = $language['locale'] ?? $lang;
        $name = $language['name'] ?? $lang;
        $native_name = $language['native_name'] ?? $name;

        $header = '# Flavor Multilingual Translation File' . "\n";
        $header .= '# Language: ' . $name . ' (' . $native_name . ')' . "\n";
        $header .= '# Generated: ' . date('Y-m-d H:i:s') . "\n";
        $header .= '#' . "\n";
        $header .= 'msgid ""' . "\n";
        $header .= 'msgstr ""' . "\n";
        $header .= '"Project-Id-Version: Flavor Multilingual\\n"' . "\n";
        $header .= '"POT-Creation-Date: ' . date('Y-m-d H:i:sO') . '\\n"' . "\n";
        $header .= '"PO-Revision-Date: ' . date('Y-m-d H:i:sO') . '\\n"' . "\n";
        $header .= '"Last-Translator: \\n"' . "\n";
        $header .= '"Language-Team: \\n"' . "\n";
        $header .= '"Language: ' . str_replace('_', '-', $locale) . '\\n"' . "\n";
        $header .= '"MIME-Version: 1.0\\n"' . "\n";
        $header .= '"Content-Type: text/plain; charset=UTF-8\\n"' . "\n";
        $header .= '"Content-Transfer-Encoding: 8bit\\n"' . "\n";
        $header .= '"X-Generator: Flavor Multilingual ' . FLAVOR_MULTILINGUAL_VERSION . '\\n"' . "\n";
        $header .= "\n";

        return $header;
    }

    /**
     * Exporta strings a formato PO
     *
     * @param string $lang          Código de idioma
     * @param bool   $include_empty Incluir cadenas sin traducir
     * @return string
     */
    private function export_strings_to_po($lang, $include_empty) {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_string_translations';
        $po_content = '';

        // Obtener strings del idioma por defecto primero
        $core = Flavor_Multilingual_Core::get_instance();
        $default_lang = $core->get_default_language();

        $strings = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT original_string, domain FROM {$table} WHERE language_code = %s OR language_code = %s",
            $default_lang, $lang
        ), ARRAY_A);

        $processed = array();

        foreach ($strings as $string) {
            $original = $string['original_string'];
            $hash = md5($original);

            if (isset($processed[$hash])) {
                continue;
            }
            $processed[$hash] = true;

            // Obtener traducción
            $translation = $wpdb->get_var($wpdb->prepare(
                "SELECT translation FROM {$table} WHERE string_key = %s AND language_code = %s",
                $hash, $lang
            ));

            if (!$include_empty && empty($translation)) {
                continue;
            }

            $po_content .= $this->format_po_entry($original, $translation ?: '', array(
                'context' => 'string:' . $string['domain'],
            ));
        }

        return $po_content;
    }

    /**
     * Exporta posts a formato PO
     *
     * @param string $lang          Código de idioma
     * @param bool   $include_empty Incluir sin traducir
     * @return string
     */
    private function export_posts_to_po($lang, $include_empty) {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_translations';
        $po_content = '';

        // Obtener posts publicados
        $posts = $wpdb->get_results(
            "SELECT ID, post_title, post_content, post_excerpt, post_type
             FROM {$wpdb->posts}
             WHERE post_status = 'publish'
             AND post_type IN ('post', 'page', 'flavor_landing')
             ORDER BY ID ASC",
            ARRAY_A
        );

        foreach ($posts as $post) {
            $post_id = $post['ID'];

            // Título
            $title_trans = $wpdb->get_var($wpdb->prepare(
                "SELECT translation FROM {$table}
                 WHERE object_type = 'post' AND object_id = %d AND language_code = %s AND field_name = 'title'",
                $post_id, $lang
            ));

            if ($include_empty || !empty($title_trans)) {
                $po_content .= $this->format_po_entry($post['post_title'], $title_trans ?: '', array(
                    'context' => "post:{$post_id}:title",
                    'comment' => "Post Type: {$post['post_type']}",
                ));
            }

            // Contenido
            $content_trans = $wpdb->get_var($wpdb->prepare(
                "SELECT translation FROM {$table}
                 WHERE object_type = 'post' AND object_id = %d AND language_code = %s AND field_name = 'content'",
                $post_id, $lang
            ));

            if ($include_empty || !empty($content_trans)) {
                $po_content .= $this->format_po_entry($post['post_content'], $content_trans ?: '', array(
                    'context' => "post:{$post_id}:content",
                    'flags'   => 'fuzzy, no-wrap',
                ));
            }

            // Extracto
            if (!empty($post['post_excerpt'])) {
                $excerpt_trans = $wpdb->get_var($wpdb->prepare(
                    "SELECT translation FROM {$table}
                     WHERE object_type = 'post' AND object_id = %d AND language_code = %s AND field_name = 'excerpt'",
                    $post_id, $lang
                ));

                if ($include_empty || !empty($excerpt_trans)) {
                    $po_content .= $this->format_po_entry($post['post_excerpt'], $excerpt_trans ?: '', array(
                        'context' => "post:{$post_id}:excerpt",
                    ));
                }
            }
        }

        return $po_content;
    }

    /**
     * Exporta términos a formato PO
     *
     * @param string $lang          Código de idioma
     * @param bool   $include_empty Incluir sin traducir
     * @return string
     */
    private function export_terms_to_po($lang, $include_empty) {
        global $wpdb;

        $table = $wpdb->prefix . 'flavor_translations';
        $po_content = '';

        // Obtener términos
        $terms = $wpdb->get_results(
            "SELECT t.term_id, t.name, t.slug, tt.taxonomy, tt.description
             FROM {$wpdb->terms} t
             INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
             WHERE tt.taxonomy IN ('category', 'post_tag', 'flavor_sector')
             ORDER BY t.term_id ASC",
            ARRAY_A
        );

        foreach ($terms as $term) {
            $term_id = $term['term_id'];

            // Nombre
            $name_trans = $wpdb->get_var($wpdb->prepare(
                "SELECT translation FROM {$table}
                 WHERE object_type = 'term' AND object_id = %d AND language_code = %s AND field_name = 'name'",
                $term_id, $lang
            ));

            if ($include_empty || !empty($name_trans)) {
                $po_content .= $this->format_po_entry($term['name'], $name_trans ?: '', array(
                    'context' => "term:{$term_id}:name",
                    'comment' => "Taxonomy: {$term['taxonomy']}",
                ));
            }

            // Descripción
            if (!empty($term['description'])) {
                $desc_trans = $wpdb->get_var($wpdb->prepare(
                    "SELECT translation FROM {$table}
                     WHERE object_type = 'term' AND object_id = %d AND language_code = %s AND field_name = 'description'",
                    $term_id, $lang
                ));

                if ($include_empty || !empty($desc_trans)) {
                    $po_content .= $this->format_po_entry($term['description'], $desc_trans ?: '', array(
                        'context' => "term:{$term_id}:description",
                    ));
                }
            }
        }

        return $po_content;
    }

    /**
     * Formatea una entrada PO
     *
     * @param string $original    Texto original
     * @param string $translation Traducción
     * @param array  $meta        Metadatos
     * @return string
     */
    private function format_po_entry($original, $translation, $meta = array()) {
        $entry = '';

        // Comentario de traductor
        if (!empty($meta['comment'])) {
            $entry .= '#. ' . $meta['comment'] . "\n";
        }

        // Referencia/contexto
        if (!empty($meta['context'])) {
            $entry .= '#: ' . $meta['context'] . "\n";
        }

        // Flags
        if (!empty($meta['flags'])) {
            $entry .= '#, ' . $meta['flags'] . "\n";
        }

        // Contexto msgctxt (si es diferente del reference)
        if (!empty($meta['msgctxt'])) {
            $entry .= 'msgctxt ' . $this->escape_po_string($meta['msgctxt']) . "\n";
        }

        // Original
        $entry .= 'msgid ' . $this->escape_po_string($original) . "\n";

        // Traducción
        $entry .= 'msgstr ' . $this->escape_po_string($translation) . "\n";

        $entry .= "\n";

        return $entry;
    }

    /**
     * Escapa una cadena para formato PO
     *
     * @param string $string Cadena a escapar
     * @return string
     */
    private function escape_po_string($string) {
        // Dividir en líneas para strings multilínea
        $lines = explode("\n", $string);

        if (count($lines) === 1) {
            return '"' . addcslashes($string, "\"\\\n\r\t") . '"';
        }

        // Multilínea
        $result = '""' . "\n";
        foreach ($lines as $i => $line) {
            $escaped = addcslashes($line, "\"\\\r\t");
            if ($i < count($lines) - 1) {
                $escaped .= "\\n";
            }
            $result .= '"' . $escaped . '"' . "\n";
        }

        return rtrim($result);
    }

    /**
     * Importa un archivo PO
     *
     * @param string $file_path Ruta al archivo
     * @param string $lang      Código de idioma
     * @return array Resultado con estadísticas
     */
    public function import_from_po($file_path, $lang) {
        if (!file_exists($file_path)) {
            return array('success' => false, 'message' => 'Archivo no encontrado');
        }

        $content = file_get_contents($file_path);
        $entries = $this->parse_po_content($content);

        if (empty($entries)) {
            return array('success' => false, 'message' => 'No se encontraron entradas en el archivo');
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $stats = array(
            'total'    => count($entries),
            'imported' => 0,
            'skipped'  => 0,
            'errors'   => 0,
        );

        foreach ($entries as $entry) {
            if (empty($entry['msgstr']) || $entry['msgstr'] === $entry['msgid']) {
                $stats['skipped']++;
                continue;
            }

            // Parsear el contexto para determinar el tipo
            $context = $entry['context'] ?? '';

            if (preg_match('/^post:(\d+):(\w+)$/', $context, $matches)) {
                // Traducción de post
                $post_id = intval($matches[1]);
                $field = $matches[2];

                $result = $storage->save_translation('post', $post_id, $lang, $field, $entry['msgstr'], array(
                    'status' => 'draft',
                    'auto'   => false,
                ));

                if ($result !== false) {
                    $stats['imported']++;
                } else {
                    $stats['errors']++;
                }
            } elseif (preg_match('/^term:(\d+):(\w+)$/', $context, $matches)) {
                // Traducción de término
                $term_id = intval($matches[1]);
                $field = $matches[2];

                $result = $storage->save_translation('term', $term_id, $lang, $field, $entry['msgstr'], array(
                    'status' => 'draft',
                    'auto'   => false,
                ));

                if ($result !== false) {
                    $stats['imported']++;
                } else {
                    $stats['errors']++;
                }
            } elseif (preg_match('/^string:(.+)$/', $context, $matches)) {
                // Traducción de string
                $domain = $matches[1];

                $result = $storage->save_string_translation($entry['msgid'], $lang, $entry['msgstr'], $domain);

                if ($result !== false) {
                    $stats['imported']++;
                } else {
                    $stats['errors']++;
                }
            } else {
                // String genérico
                $result = $storage->save_string_translation($entry['msgid'], $lang, $entry['msgstr']);

                if ($result !== false) {
                    $stats['imported']++;
                } else {
                    $stats['errors']++;
                }
            }
        }

        return array(
            'success' => true,
            'message' => "Importadas {$stats['imported']} traducciones",
            'stats'   => $stats,
        );
    }

    /**
     * Parsea el contenido de un archivo PO
     *
     * @param string $content Contenido del archivo
     * @return array
     */
    private function parse_po_content($content) {
        $entries = array();
        $current = array();

        $lines = explode("\n", $content);
        $in_msgid = false;
        $in_msgstr = false;

        foreach ($lines as $line) {
            $line = trim($line);

            // Comentario de referencia (contexto)
            if (strpos($line, '#:') === 0) {
                $current['context'] = trim(substr($line, 2));
                continue;
            }

            // Ignorar otros comentarios
            if (strpos($line, '#') === 0) {
                continue;
            }

            // msgid
            if (strpos($line, 'msgid ') === 0) {
                // Guardar entrada anterior si existe
                if (!empty($current['msgid'])) {
                    $entries[] = $current;
                }
                $current = array();
                $current['msgid'] = $this->unescape_po_string(substr($line, 6));
                $in_msgid = true;
                $in_msgstr = false;
                continue;
            }

            // msgstr
            if (strpos($line, 'msgstr ') === 0) {
                $current['msgstr'] = $this->unescape_po_string(substr($line, 7));
                $in_msgid = false;
                $in_msgstr = true;
                continue;
            }

            // Continuación de string multilínea
            if (strpos($line, '"') === 0) {
                $value = $this->unescape_po_string($line);
                if ($in_msgid) {
                    $current['msgid'] .= $value;
                } elseif ($in_msgstr) {
                    $current['msgstr'] .= $value;
                }
                continue;
            }

            // Línea vacía - fin de entrada
            if (empty($line)) {
                $in_msgid = false;
                $in_msgstr = false;
            }
        }

        // Guardar última entrada
        if (!empty($current['msgid'])) {
            $entries[] = $current;
        }

        return $entries;
    }

    /**
     * Desescapa una cadena PO
     *
     * @param string $string Cadena escapada
     * @return string
     */
    private function unescape_po_string($string) {
        // Quitar comillas
        $string = trim($string);
        if (substr($string, 0, 1) === '"' && substr($string, -1) === '"') {
            $string = substr($string, 1, -1);
        }

        // Desescapar
        return stripcslashes($string);
    }

    /**
     * Genera archivo MO desde PO
     *
     * @param string $po_content Contenido PO
     * @return string|false Contenido binario MO o false
     */
    public function generate_mo($po_content) {
        $entries = $this->parse_po_content($po_content);

        if (empty($entries)) {
            return false;
        }

        // Preparar datos para MO
        $hash_table_size = 0;
        $originals = array();
        $translations = array();

        foreach ($entries as $entry) {
            if (empty($entry['msgid'])) {
                continue;
            }

            $originals[] = $entry['msgid'];
            $translations[] = $entry['msgstr'] ?? '';
        }

        // Generar archivo MO
        $mo = $this->create_mo_binary($originals, $translations);

        return $mo;
    }

    /**
     * Crea el contenido binario del archivo MO
     *
     * @param array $originals    Textos originales
     * @param array $translations Traducciones
     * @return string
     */
    private function create_mo_binary($originals, $translations) {
        $count = count($originals);

        // Magic number
        $mo = pack('L', 0x950412de);

        // Version
        $mo .= pack('L', 0);

        // Número de strings
        $mo .= pack('L', $count);

        // Offset de tabla de originales
        $mo .= pack('L', 28);

        // Offset de tabla de traducciones
        $mo .= pack('L', 28 + ($count * 8));

        // Tamaño de hash table
        $mo .= pack('L', 0);

        // Offset de hash table
        $mo .= pack('L', 28 + ($count * 16));

        // Calcular strings
        $original_strings = '';
        $translation_strings = '';
        $original_table = '';
        $translation_table = '';

        $original_offset = 28 + ($count * 16);
        $translation_offset = $original_offset;

        // Calcular tamaño total de originales
        foreach ($originals as $orig) {
            $translation_offset += strlen($orig) + 1;
        }

        foreach ($originals as $i => $orig) {
            $trans = $translations[$i];

            // Tabla de originales
            $original_table .= pack('L', strlen($orig));
            $original_table .= pack('L', $original_offset + strlen($original_strings));
            $original_strings .= $orig . "\0";

            // Tabla de traducciones
            $translation_table .= pack('L', strlen($trans));
            $translation_table .= pack('L', $translation_offset + strlen($translation_strings));
            $translation_strings .= $trans . "\0";
        }

        $mo .= $original_table;
        $mo .= $translation_table;
        $mo .= $original_strings;
        $mo .= $translation_strings;

        return $mo;
    }

    /**
     * Traduce un archivo PO con IA
     *
     * @param string $file_path   Ruta al archivo PO
     * @param string $target_lang Idioma destino
     * @return array Resultado
     */
    public function translate_po_with_ai($file_path, $target_lang) {
        if (!class_exists('Flavor_AI_Translator')) {
            return array('success' => false, 'message' => 'Traductor IA no disponible');
        }

        $content = file_get_contents($file_path);
        $entries = $this->parse_po_content($content);

        if (empty($entries)) {
            return array('success' => false, 'message' => 'No se encontraron entradas');
        }

        $ai_translator = Flavor_AI_Translator::get_instance();
        $translated_entries = array();
        $stats = array('total' => 0, 'translated' => 0, 'failed' => 0);

        foreach ($entries as $entry) {
            $stats['total']++;

            if (empty($entry['msgid'])) {
                continue;
            }

            // Si ya tiene traducción, mantenerla
            if (!empty($entry['msgstr']) && $entry['msgstr'] !== $entry['msgid']) {
                $translated_entries[] = $entry;
                continue;
            }

            // Traducir con IA
            $translated = $ai_translator->translate($entry['msgid'], $target_lang);

            if ($translated && !is_wp_error($translated)) {
                $entry['msgstr'] = $translated;
                $stats['translated']++;
            } else {
                $entry['msgstr'] = '';
                $stats['failed']++;
            }

            $translated_entries[] = $entry;
        }

        // Regenerar archivo PO
        $core = Flavor_Multilingual_Core::get_instance();
        $language = $core->get_language($target_lang);

        $new_po = $this->generate_po_header($target_lang, $language ?: array('locale' => $target_lang, 'name' => $target_lang));

        foreach ($translated_entries as $entry) {
            $new_po .= $this->format_po_entry($entry['msgid'], $entry['msgstr'], array(
                'context' => $entry['context'] ?? '',
            ));
        }

        return array(
            'success' => true,
            'content' => $new_po,
            'stats'   => $stats,
        );
    }

    /**
     * AJAX: Exporta a PO
     */
    public function ajax_export_po() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $lang = sanitize_key($_POST['lang'] ?? '');
        $type = sanitize_key($_POST['type'] ?? 'all');
        $include_empty = !empty($_POST['include_empty']);

        if (!$lang) {
            wp_send_json_error(array('message' => 'Idioma requerido'));
        }

        $po_content = $this->export_to_po($lang, $type, $include_empty);

        // Guardar archivo temporal
        $filename = "flavor-{$lang}-" . date('Y-m-d') . ".po";
        $file_path = $this->translations_dir . '/' . $filename;
        file_put_contents($file_path, $po_content);

        wp_send_json_success(array(
            'filename'    => $filename,
            'download_url' => wp_upload_dir()['baseurl'] . '/flavor-translations/' . $filename,
            'content'     => $po_content,
        ));
    }

    /**
     * AJAX: Exporta a MO
     */
    public function ajax_export_mo() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$lang) {
            wp_send_json_error(array('message' => 'Idioma requerido'));
        }

        $po_content = $this->export_to_po($lang, 'all', false);
        $mo_content = $this->generate_mo($po_content);

        if (!$mo_content) {
            wp_send_json_error(array('message' => 'Error generando archivo MO'));
        }

        // Guardar archivo
        $filename = "flavor-{$lang}-" . date('Y-m-d') . ".mo";
        $file_path = $this->translations_dir . '/' . $filename;
        file_put_contents($file_path, $mo_content);

        wp_send_json_success(array(
            'filename'     => $filename,
            'download_url' => wp_upload_dir()['baseurl'] . '/flavor-translations/' . $filename,
        ));
    }

    /**
     * AJAX: Importa PO
     */
    public function ajax_import_po() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        if (empty($_FILES['po_file'])) {
            wp_send_json_error(array('message' => 'No se subió ningún archivo'));
        }

        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$lang) {
            wp_send_json_error(array('message' => 'Idioma requerido'));
        }

        $file = $_FILES['po_file'];

        // Verificar extensión
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if (!in_array($ext, array('po', 'pot'))) {
            wp_send_json_error(array('message' => 'Formato de archivo no válido'));
        }

        $result = $this->import_from_po($file['tmp_name'], $lang);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Traduce PO con IA
     */
    public function ajax_translate_po_ai() {
        check_ajax_referer('flavor_multilingual_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Sin permisos'));
        }

        if (empty($_FILES['po_file'])) {
            wp_send_json_error(array('message' => 'No se subió ningún archivo'));
        }

        $lang = sanitize_key($_POST['lang'] ?? '');

        if (!$lang) {
            wp_send_json_error(array('message' => 'Idioma requerido'));
        }

        $file = $_FILES['po_file'];
        $result = $this->translate_po_with_ai($file['tmp_name'], $lang);

        if ($result['success']) {
            // Guardar archivo traducido
            $filename = "flavor-{$lang}-translated-" . date('Y-m-d-His') . ".po";
            $file_path = $this->translations_dir . '/' . $filename;
            file_put_contents($file_path, $result['content']);

            $result['filename'] = $filename;
            $result['download_url'] = wp_upload_dir()['baseurl'] . '/flavor-translations/' . $filename;

            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
