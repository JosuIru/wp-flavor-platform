<?php
/**
 * Almacenamiento de traducciones
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Storage {

    private static $instance = null;
    private $table_translations;
    private $table_strings;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        global $wpdb;
        $this->table_translations = $wpdb->prefix . 'flavor_translations';
        $this->table_strings = $wpdb->prefix . 'flavor_string_translations';
    }

    /**
     * Guarda una traducción
     */
    public function save_translation($type, $id, $lang, $field, $value, $meta = array()) {
        global $wpdb;

        $data = array(
            'object_type'        => $type,
            'object_id'          => $id,
            'language_code'      => $lang,
            'field_name'         => $field,
            'translation'        => $value,
            'is_auto_translated' => isset($meta['auto']) ? 1 : 0,
            'translator'         => $meta['translator'] ?? null,
            'status'             => $meta['status'] ?? 'draft',
            'updated_at'         => current_time('mysql'),
        );

        $existing = $this->get_translation($type, $id, $lang, $field);

        if ($existing !== null) {
            return $wpdb->update(
                $this->table_translations,
                $data,
                array(
                    'object_type'   => $type,
                    'object_id'     => $id,
                    'language_code' => $lang,
                    'field_name'    => $field,
                )
            );
        }

        $data['created_at'] = current_time('mysql');
        return $wpdb->insert($this->table_translations, $data);
    }

    /**
     * Obtiene una traducción
     */
    public function get_translation($type, $id, $lang, $field) {
        global $wpdb;

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT translation FROM {$this->table_translations}
             WHERE object_type = %s AND object_id = %d AND language_code = %s AND field_name = %s",
            $type, $id, $lang, $field
        ));

        return $result;
    }

    /**
     * Obtiene todas las traducciones de un objeto
     */
    public function get_all_translations($type, $id) {
        global $wpdb;

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT language_code, field_name, translation, status, is_auto_translated
             FROM {$this->table_translations}
             WHERE object_type = %s AND object_id = %d",
            $type, $id
        ), ARRAY_A);

        $translations = array();
        foreach ($results as $row) {
            $lang = $row['language_code'];
            if (!isset($translations[$lang])) {
                $translations[$lang] = array();
            }
            $translations[$lang][$row['field_name']] = array(
                'value'  => $row['translation'],
                'status' => $row['status'],
                'auto'   => (bool) $row['is_auto_translated'],
            );
        }

        return $translations;
    }

    /**
     * Elimina traducciones
     */
    public function delete_translations($type, $id, $lang = null) {
        global $wpdb;

        $where = array(
            'object_type' => $type,
            'object_id'   => $id,
        );

        if ($lang) {
            $where['language_code'] = $lang;
        }

        return $wpdb->delete($this->table_translations, $where);
    }

    /**
     * Guarda traducción de string
     */
    public function save_string_translation($original, $lang, $translation, $domain = FLAVOR_PLATFORM_TEXT_DOMAIN) {
        global $wpdb;

        $string_key = md5($original);

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_strings}
             WHERE string_key = %s AND language_code = %s",
            $string_key, $lang
        ));

        if ($existing) {
            return $wpdb->update(
                $this->table_strings,
                array(
                    'translation' => $translation,
                    'updated_at'  => current_time('mysql'),
                ),
                array('id' => $existing)
            );
        }

        return $wpdb->insert($this->table_strings, array(
            'string_key'      => $string_key,
            'original_string' => $original,
            'domain'          => $domain,
            'language_code'   => $lang,
            'translation'     => $translation,
            'created_at'      => current_time('mysql'),
        ));
    }

    /**
     * Obtiene traducción de string
     */
    public function get_string_translation($original, $lang, $domain = FLAVOR_PLATFORM_TEXT_DOMAIN) {
        global $wpdb;

        $string_key = md5($original);

        return $wpdb->get_var($wpdb->prepare(
            "SELECT translation FROM {$this->table_strings}
             WHERE string_key = %s AND language_code = %s AND domain = %s",
            $string_key, $lang, $domain
        ));
    }

    /**
     * Obtiene estadísticas de traducciones
     */
    public function get_translation_stats() {
        global $wpdb;

        $stats = array();

        // Total por idioma
        $by_lang = $wpdb->get_results(
            "SELECT language_code, COUNT(*) as total, status
             FROM {$this->table_translations}
             GROUP BY language_code, status",
            ARRAY_A
        );

        foreach ($by_lang as $row) {
            $lang = $row['language_code'];
            if (!isset($stats[$lang])) {
                $stats[$lang] = array('total' => 0, 'published' => 0, 'draft' => 0);
            }
            $stats[$lang]['total'] += $row['total'];
            $stats[$lang][$row['status']] = $row['total'];
        }

        return $stats;
    }

    /**
     * Obtiene contenido sin traducir
     */
    public function get_untranslated_content($lang, $limit = 50) {
        global $wpdb;

        // Posts que no tienen traducción en el idioma especificado
        $posts = $wpdb->get_results($wpdb->prepare(
            "SELECT p.ID, p.post_title, p.post_type
             FROM {$wpdb->posts} p
             LEFT JOIN {$this->table_translations} t
                 ON t.object_type = 'post'
                 AND t.object_id = p.ID
                 AND t.language_code = %s
                 AND t.field_name = 'title'
             WHERE p.post_status = 'publish'
               AND p.post_type IN ('post', 'page', 'flavor_landing')
               AND t.id IS NULL
             LIMIT %d",
            $lang, $limit
        ), ARRAY_A);

        return $posts;
    }
}
