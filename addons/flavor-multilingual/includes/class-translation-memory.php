<?php
/**
 * Memoria de traducción y glosario
 *
 * Sistema para reutilizar traducciones anteriores y mantener consistencia
 * terminológica a través de un glosario de términos.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Translation_Memory {

    /**
     * Instancia singleton
     *
     * @var Flavor_Translation_Memory|null
     */
    private static $instance = null;

    /**
     * Tabla de memoria de traducción
     *
     * @var string
     */
    private $memory_table;

    /**
     * Tabla de glosario
     *
     * @var string
     */
    private $glossary_table;

    /**
     * Umbral de similitud para coincidencias (0-100)
     *
     * @var int
     */
    private $similarity_threshold = 70;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Translation_Memory
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
        global $wpdb;
        $this->memory_table = $wpdb->prefix . 'flavor_translation_memory';
        $this->glossary_table = $wpdb->prefix . 'flavor_glossary';

        // Crear tablas si no existen
        add_action('init', array($this, 'maybe_create_tables'), 5);

        // Hooks para alimentar la memoria
        add_action('flavor_multilingual_translation_saved', array($this, 'add_to_memory'), 10, 5);

        // Hooks para consultar memoria antes de traducir
        add_filter('flavor_multilingual_before_ai_translate', array($this, 'check_memory'), 10, 4);
        add_filter('flavor_multilingual_ai_prompt', array($this, 'inject_glossary'), 10, 4);

        // Admin
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_admin_menu'));
            add_action('wp_ajax_flavor_ml_add_glossary_term', array($this, 'ajax_add_glossary_term'));
            add_action('wp_ajax_flavor_ml_delete_glossary_term', array($this, 'ajax_delete_glossary_term'));
            add_action('wp_ajax_flavor_ml_import_glossary', array($this, 'ajax_import_glossary'));
            add_action('wp_ajax_flavor_ml_search_memory', array($this, 'ajax_search_memory'));
        }

        // API REST
        add_action('rest_api_init', array($this, 'register_rest_endpoints'));
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de memoria de traducción
        $sql_memory = "CREATE TABLE IF NOT EXISTS {$this->memory_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            source_text TEXT NOT NULL,
            source_hash VARCHAR(32) NOT NULL,
            source_lang VARCHAR(10) NOT NULL,
            target_text TEXT NOT NULL,
            target_lang VARCHAR(10) NOT NULL,
            context VARCHAR(255) DEFAULT NULL,
            quality_score TINYINT DEFAULT 0,
            use_count INT DEFAULT 1,
            last_used DATETIME DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY source_hash (source_hash),
            KEY source_lang (source_lang),
            KEY target_lang (target_lang),
            KEY quality_score (quality_score),
            KEY use_count (use_count)
        ) {$charset_collate};";

        // Tabla de glosario
        $sql_glossary = "CREATE TABLE IF NOT EXISTS {$this->glossary_table} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            term VARCHAR(255) NOT NULL,
            term_normalized VARCHAR(255) NOT NULL,
            source_lang VARCHAR(10) NOT NULL,
            translation VARCHAR(255) NOT NULL,
            target_lang VARCHAR(10) NOT NULL,
            context VARCHAR(255) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            is_case_sensitive TINYINT(1) DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY term_langs (term_normalized, source_lang, target_lang),
            KEY source_lang (source_lang),
            KEY target_lang (target_lang)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_memory);
        dbDelta($sql_glossary);
    }

    // ================================================================
    // MEMORIA DE TRADUCCIÓN
    // ================================================================

    /**
     * Añade una traducción a la memoria
     *
     * @param string $type        Tipo de objeto
     * @param int    $object_id   ID del objeto
     * @param string $lang        Idioma destino
     * @param string $field       Campo
     * @param string $translation Traducción
     */
    public function add_to_memory($type, $object_id, $lang, $field, $translation) {
        global $wpdb;

        // Obtener el texto original
        $original = $this->get_original_text($type, $object_id, $field);

        if (empty($original) || empty($translation)) {
            return;
        }

        // No guardar textos muy cortos (menos de 10 caracteres)
        if (mb_strlen($original) < 10) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $source_lang = $core->get_default_language();
        $source_hash = md5(mb_strtolower(trim($original)));

        // Verificar si ya existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->memory_table}
            WHERE source_hash = %s AND source_lang = %s AND target_lang = %s",
            $source_hash, $source_lang, $lang
        ));

        if ($existing) {
            // Actualizar uso
            $wpdb->update(
                $this->memory_table,
                array(
                    'target_text' => $translation,
                    'use_count'   => $wpdb->prepare('use_count + 1'),
                    'last_used'   => current_time('mysql'),
                ),
                array('id' => $existing)
            );
        } else {
            // Insertar nuevo
            $wpdb->insert($this->memory_table, array(
                'source_text'   => $original,
                'source_hash'   => $source_hash,
                'source_lang'   => $source_lang,
                'target_text'   => $translation,
                'target_lang'   => $lang,
                'context'       => "{$type}:{$field}",
                'quality_score' => 80, // Puntuación base
            ));
        }
    }

    /**
     * Busca en la memoria de traducción
     *
     * @param string $text        Texto a buscar
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @return array Coincidencias encontradas
     */
    public function search_memory($text, $source_lang, $target_lang) {
        global $wpdb;

        $text_hash = md5(mb_strtolower(trim($text)));
        $results = array();

        // Búsqueda exacta primero
        $exact = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->memory_table}
            WHERE source_hash = %s AND source_lang = %s AND target_lang = %s
            ORDER BY quality_score DESC, use_count DESC
            LIMIT 1",
            $text_hash, $source_lang, $target_lang
        ), ARRAY_A);

        if ($exact) {
            $exact['match_type'] = 'exact';
            $exact['similarity'] = 100;
            $results[] = $exact;

            // Incrementar contador de uso
            $wpdb->update(
                $this->memory_table,
                array(
                    'use_count' => $exact['use_count'] + 1,
                    'last_used' => current_time('mysql'),
                ),
                array('id' => $exact['id'])
            );

            return $results;
        }

        // Búsqueda por similitud (fuzzy matching)
        $candidates = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->memory_table}
            WHERE source_lang = %s AND target_lang = %s
            AND CHAR_LENGTH(source_text) BETWEEN %d AND %d
            ORDER BY quality_score DESC, use_count DESC
            LIMIT 100",
            $source_lang, $target_lang,
            mb_strlen($text) * 0.7,
            mb_strlen($text) * 1.3
        ), ARRAY_A);

        foreach ($candidates as $candidate) {
            $similarity = $this->calculate_similarity($text, $candidate['source_text']);

            if ($similarity >= $this->similarity_threshold) {
                $candidate['match_type'] = 'fuzzy';
                $candidate['similarity'] = $similarity;
                $results[] = $candidate;
            }
        }

        // Ordenar por similitud
        usort($results, function($a, $b) {
            return $b['similarity'] - $a['similarity'];
        });

        return array_slice($results, 0, 5);
    }

    /**
     * Verifica la memoria antes de traducir
     *
     * @param string $text        Texto a traducir
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @param string $context     Contexto
     * @return string|null Traducción de memoria o null
     */
    public function check_memory($text, $source_lang, $target_lang, $context = '') {
        $matches = $this->search_memory($text, $source_lang, $target_lang);

        if (!empty($matches) && $matches[0]['match_type'] === 'exact') {
            // Devolver traducción exacta
            return $matches[0]['target_text'];
        }

        // Para fuzzy matches, devolver null y dejar que la IA traduzca
        // pero podríamos sugerir al usuario
        return null;
    }

    /**
     * Calcula la similitud entre dos textos
     *
     * @param string $text1 Texto 1
     * @param string $text2 Texto 2
     * @return int Porcentaje de similitud (0-100)
     */
    private function calculate_similarity($text1, $text2) {
        $text1 = mb_strtolower(trim($text1));
        $text2 = mb_strtolower(trim($text2));

        // Usar Levenshtein para textos cortos
        if (mb_strlen($text1) <= 255 && mb_strlen($text2) <= 255) {
            $lev = levenshtein($text1, $text2);
            $max_len = max(mb_strlen($text1), mb_strlen($text2));
            return $max_len > 0 ? round((1 - $lev / $max_len) * 100) : 100;
        }

        // Para textos largos, usar similar_text
        similar_text($text1, $text2, $percent);
        return round($percent);
    }

    /**
     * Obtiene el texto original de un objeto
     *
     * @param string $type      Tipo de objeto
     * @param int    $object_id ID del objeto
     * @param string $field     Campo
     * @return string|null
     */
    private function get_original_text($type, $object_id, $field) {
        switch ($type) {
            case 'post':
            case 'page':
            case 'product':
                $post = get_post($object_id);
                if (!$post) return null;

                switch ($field) {
                    case 'title':
                        return $post->post_title;
                    case 'content':
                        return $post->post_content;
                    case 'excerpt':
                    case 'short_description':
                        return $post->post_excerpt;
                    default:
                        return get_post_meta($object_id, $field, true);
                }

            case 'term':
                $term = get_term($object_id);
                if (!$term || is_wp_error($term)) return null;

                switch ($field) {
                    case 'name':
                        return $term->name;
                    case 'description':
                        return $term->description;
                    default:
                        return get_term_meta($object_id, $field, true);
                }

            default:
                return null;
        }
    }

    // ================================================================
    // GLOSARIO
    // ================================================================

    /**
     * Añade un término al glosario
     *
     * @param string $term        Término original
     * @param string $translation Traducción
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @param array  $options     Opciones adicionales
     * @return int|false ID del término o false
     */
    public function add_glossary_term($term, $translation, $source_lang, $target_lang, $options = array()) {
        global $wpdb;

        $term_normalized = mb_strtolower(trim($term));

        // Verificar si existe
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->glossary_table}
            WHERE term_normalized = %s AND source_lang = %s AND target_lang = %s",
            $term_normalized, $source_lang, $target_lang
        ));

        $data = array(
            'term'              => trim($term),
            'term_normalized'   => $term_normalized,
            'source_lang'       => $source_lang,
            'translation'       => trim($translation),
            'target_lang'       => $target_lang,
            'context'           => $options['context'] ?? null,
            'notes'             => $options['notes'] ?? null,
            'is_case_sensitive' => !empty($options['case_sensitive']) ? 1 : 0,
        );

        if ($existing) {
            $wpdb->update($this->glossary_table, $data, array('id' => $existing));
            return $existing;
        } else {
            $wpdb->insert($this->glossary_table, $data);
            return $wpdb->insert_id;
        }
    }

    /**
     * Obtiene términos del glosario
     *
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @return array
     */
    public function get_glossary_terms($source_lang, $target_lang) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->glossary_table}
            WHERE source_lang = %s AND target_lang = %s
            ORDER BY term ASC",
            $source_lang, $target_lang
        ), ARRAY_A);
    }

    /**
     * Busca términos del glosario en un texto
     *
     * @param string $text        Texto donde buscar
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @return array Términos encontrados con su posición
     */
    public function find_glossary_terms($text, $source_lang, $target_lang) {
        $terms = $this->get_glossary_terms($source_lang, $target_lang);
        $found = array();

        foreach ($terms as $term) {
            $search = $term['is_case_sensitive'] ? $term['term'] : mb_strtolower($term['term']);
            $haystack = $term['is_case_sensitive'] ? $text : mb_strtolower($text);

            if (mb_strpos($haystack, $search) !== false) {
                $found[] = array(
                    'term'        => $term['term'],
                    'translation' => $term['translation'],
                    'context'     => $term['context'],
                );
            }
        }

        return $found;
    }

    /**
     * Inyecta el glosario en el prompt de IA
     *
     * @param string $prompt      Prompt original
     * @param string $text        Texto a traducir
     * @param string $source_lang Idioma origen
     * @param string $target_lang Idioma destino
     * @return string Prompt modificado
     */
    public function inject_glossary($prompt, $text, $source_lang, $target_lang) {
        $found_terms = $this->find_glossary_terms($text, $source_lang, $target_lang);

        if (empty($found_terms)) {
            return $prompt;
        }

        // Construir sección de glosario
        $glossary_section = "\n\nGLOSARIO (usa estas traducciones obligatoriamente):\n";

        foreach ($found_terms as $item) {
            $glossary_section .= sprintf(
                "- \"%s\" → \"%s\"%s\n",
                $item['term'],
                $item['translation'],
                $item['context'] ? " (contexto: {$item['context']})" : ''
            );
        }

        // Insertar antes del texto a traducir
        $prompt = str_replace(
            'CONTENIDO A TRADUCIR:',
            $glossary_section . "\nCONTENIDO A TRADUCIR:",
            $prompt
        );

        return $prompt;
    }

    /**
     * Elimina un término del glosario
     *
     * @param int $term_id ID del término
     * @return bool
     */
    public function delete_glossary_term($term_id) {
        global $wpdb;
        return $wpdb->delete($this->glossary_table, array('id' => $term_id)) !== false;
    }

    // ================================================================
    // ADMIN
    // ================================================================

    /**
     * Añade menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-multilingual',
            __('Memoria y Glosario', 'flavor-multilingual'),
            __('Memoria/Glosario', 'flavor-multilingual'),
            'manage_options',
            'flavor-ml-memory',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Renderiza la página de admin
     */
    public function render_admin_page() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'glossary';
        ?>
        <div class="wrap">
            <h1><?php _e('Memoria de Traducción y Glosario', 'flavor-multilingual'); ?></h1>

            <nav class="nav-tab-wrapper">
                <a href="<?php echo add_query_arg('tab', 'glossary'); ?>"
                   class="nav-tab <?php echo $tab === 'glossary' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Glosario', 'flavor-multilingual'); ?>
                </a>
                <a href="<?php echo add_query_arg('tab', 'memory'); ?>"
                   class="nav-tab <?php echo $tab === 'memory' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Memoria de Traducción', 'flavor-multilingual'); ?>
                </a>
                <a href="<?php echo add_query_arg('tab', 'stats'); ?>"
                   class="nav-tab <?php echo $tab === 'stats' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Estadísticas', 'flavor-multilingual'); ?>
                </a>
            </nav>

            <div class="tab-content" style="margin-top: 20px;">
                <?php
                switch ($tab) {
                    case 'memory':
                        $this->render_memory_tab($languages, $default_lang);
                        break;
                    case 'stats':
                        $this->render_stats_tab();
                        break;
                    default:
                        $this->render_glossary_tab($languages, $default_lang);
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña del glosario
     */
    private function render_glossary_tab($languages, $default_lang) {
        $selected_lang = isset($_GET['lang']) ? sanitize_key($_GET['lang']) : array_keys($languages)[1] ?? 'en';
        $terms = $this->get_glossary_terms($default_lang, $selected_lang);
        ?>
        <div class="flavor-ml-glossary">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <label><?php _e('Idioma destino:', 'flavor-multilingual'); ?></label>
                    <select id="flavor-ml-glossary-lang" onchange="location.href='<?php echo add_query_arg('tab', 'glossary'); ?>&lang=' + this.value">
                        <?php foreach ($languages as $code => $lang) : ?>
                            <?php if ($code === $default_lang) continue; ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_lang, $code); ?>>
                                <?php echo esc_html($lang['native_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <button type="button" class="button" id="flavor-ml-import-glossary">
                        <?php _e('Importar CSV', 'flavor-multilingual'); ?>
                    </button>
                    <button type="button" class="button" id="flavor-ml-export-glossary">
                        <?php _e('Exportar CSV', 'flavor-multilingual'); ?>
                    </button>
                </div>
            </div>

            <!-- Formulario para añadir término -->
            <div class="flavor-ml-add-term" style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px;">
                <h3 style="margin-top: 0;"><?php _e('Añadir término', 'flavor-multilingual'); ?></h3>
                <form id="flavor-ml-add-term-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <input type="hidden" name="source_lang" value="<?php echo esc_attr($default_lang); ?>">
                    <input type="hidden" name="target_lang" value="<?php echo esc_attr($selected_lang); ?>">

                    <input type="text" name="term" placeholder="<?php _e('Término original', 'flavor-multilingual'); ?>" required style="flex: 1; min-width: 150px;">
                    <input type="text" name="translation" placeholder="<?php _e('Traducción', 'flavor-multilingual'); ?>" required style="flex: 1; min-width: 150px;">
                    <input type="text" name="context" placeholder="<?php _e('Contexto (opcional)', 'flavor-multilingual'); ?>" style="flex: 1; min-width: 150px;">
                    <label style="display: flex; align-items: center; gap: 5px;">
                        <input type="checkbox" name="case_sensitive">
                        <?php _e('Distinguir mayúsculas', 'flavor-multilingual'); ?>
                    </label>
                    <button type="submit" class="button button-primary"><?php _e('Añadir', 'flavor-multilingual'); ?></button>
                </form>
            </div>

            <!-- Lista de términos -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Término', 'flavor-multilingual'); ?></th>
                        <th><?php _e('Traducción', 'flavor-multilingual'); ?></th>
                        <th><?php _e('Contexto', 'flavor-multilingual'); ?></th>
                        <th style="width: 100px;"><?php _e('Acciones', 'flavor-multilingual'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($terms)) : ?>
                        <tr>
                            <td colspan="4"><?php _e('No hay términos en el glosario.', 'flavor-multilingual'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($terms as $term) : ?>
                            <tr data-id="<?php echo esc_attr($term['id']); ?>">
                                <td>
                                    <strong><?php echo esc_html($term['term']); ?></strong>
                                    <?php if ($term['is_case_sensitive']) : ?>
                                        <span class="dashicons dashicons-editor-textcolor" title="<?php _e('Distingue mayúsculas', 'flavor-multilingual'); ?>" style="font-size: 14px; color: #666;"></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html($term['translation']); ?></td>
                                <td><?php echo esc_html($term['context'] ?? '—'); ?></td>
                                <td>
                                    <button type="button" class="button button-small button-link-delete flavor-ml-delete-term" data-id="<?php echo esc_attr($term['id']); ?>">
                                        <?php _e('Eliminar', 'flavor-multilingual'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(function($) {
            // Añadir término
            $('#flavor-ml-add-term-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);

                $.post(ajaxurl, {
                    action: 'flavor_ml_add_glossary_term',
                    nonce: '<?php echo wp_create_nonce('flavor_multilingual'); ?>',
                    term: $form.find('[name="term"]').val(),
                    translation: $form.find('[name="translation"]').val(),
                    source_lang: $form.find('[name="source_lang"]').val(),
                    target_lang: $form.find('[name="target_lang"]').val(),
                    context: $form.find('[name="context"]').val(),
                    case_sensitive: $form.find('[name="case_sensitive"]').is(':checked') ? 1 : 0
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data || '<?php _e('Error', 'flavor-multilingual'); ?>');
                    }
                });
            });

            // Eliminar término
            $('.flavor-ml-delete-term').on('click', function() {
                if (!confirm('<?php _e('¿Eliminar este término?', 'flavor-multilingual'); ?>')) return;

                var $btn = $(this);
                var id = $btn.data('id');

                $.post(ajaxurl, {
                    action: 'flavor_ml_delete_glossary_term',
                    nonce: '<?php echo wp_create_nonce('flavor_multilingual'); ?>',
                    id: id
                }, function(response) {
                    if (response.success) {
                        $btn.closest('tr').fadeOut();
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Renderiza la pestaña de memoria
     */
    private function render_memory_tab($languages, $default_lang) {
        global $wpdb;

        $selected_lang = isset($_GET['lang']) ? sanitize_key($_GET['lang']) : array_keys($languages)[1] ?? 'en';
        $page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $per_page = 50;
        $offset = ($page - 1) * $per_page;

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->memory_table} WHERE source_lang = %s AND target_lang = %s",
            $default_lang, $selected_lang
        ));

        $entries = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->memory_table}
            WHERE source_lang = %s AND target_lang = %s
            ORDER BY use_count DESC, last_used DESC
            LIMIT %d OFFSET %d",
            $default_lang, $selected_lang, $per_page, $offset
        ), ARRAY_A);

        ?>
        <div class="flavor-ml-memory">
            <div style="margin-bottom: 20px;">
                <label><?php _e('Idioma destino:', 'flavor-multilingual'); ?></label>
                <select onchange="location.href='<?php echo add_query_arg('tab', 'memory'); ?>&lang=' + this.value">
                    <?php foreach ($languages as $code => $lang) : ?>
                        <?php if ($code === $default_lang) continue; ?>
                        <option value="<?php echo esc_attr($code); ?>" <?php selected($selected_lang, $code); ?>>
                            <?php echo esc_html($lang['native_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span style="margin-left: 20px;">
                    <?php printf(__('Total: %d entradas', 'flavor-multilingual'), $total); ?>
                </span>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Texto Original', 'flavor-multilingual'); ?></th>
                        <th><?php _e('Traducción', 'flavor-multilingual'); ?></th>
                        <th style="width: 80px;"><?php _e('Usos', 'flavor-multilingual'); ?></th>
                        <th style="width: 120px;"><?php _e('Último uso', 'flavor-multilingual'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($entries)) : ?>
                        <tr>
                            <td colspan="4"><?php _e('No hay entradas en la memoria.', 'flavor-multilingual'); ?></td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ($entries as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html(mb_substr($entry['source_text'], 0, 200)); ?><?php echo mb_strlen($entry['source_text']) > 200 ? '...' : ''; ?></td>
                                <td><?php echo esc_html(mb_substr($entry['target_text'], 0, 200)); ?><?php echo mb_strlen($entry['target_text']) > 200 ? '...' : ''; ?></td>
                                <td><?php echo esc_html($entry['use_count']); ?></td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($entry['last_used']))); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total > $per_page) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        echo paginate_links(array(
                            'base'    => add_query_arg('paged', '%#%'),
                            'format'  => '',
                            'current' => $page,
                            'total'   => ceil($total / $per_page),
                        ));
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pestaña de estadísticas
     */
    private function render_stats_tab() {
        global $wpdb;

        $memory_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->memory_table}");
        $glossary_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->glossary_table}");
        $total_uses = $wpdb->get_var("SELECT SUM(use_count) FROM {$this->memory_table}");
        $avg_quality = $wpdb->get_var("SELECT AVG(quality_score) FROM {$this->memory_table}");
        ?>
        <div class="flavor-ml-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($memory_count); ?></div>
                <div><?php _e('Entradas en memoria', 'flavor-multilingual'); ?></div>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($glossary_count); ?></div>
                <div><?php _e('Términos en glosario', 'flavor-multilingual'); ?></div>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #46b450;"><?php echo number_format($total_uses ?? 0); ?></div>
                <div><?php _e('Reutilizaciones totales', 'flavor-multilingual'); ?></div>
            </div>
            <div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                <div style="font-size: 36px; font-weight: bold; color: #dba617;"><?php echo round($avg_quality ?? 0); ?>%</div>
                <div><?php _e('Calidad promedio', 'flavor-multilingual'); ?></div>
            </div>
        </div>
        <?php
    }

    // ================================================================
    // AJAX
    // ================================================================

    /**
     * AJAX: Añadir término al glosario
     */
    public function ajax_add_glossary_term() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $term = sanitize_text_field($_POST['term'] ?? '');
        $translation = sanitize_text_field($_POST['translation'] ?? '');
        $source_lang = sanitize_key($_POST['source_lang'] ?? '');
        $target_lang = sanitize_key($_POST['target_lang'] ?? '');

        if (empty($term) || empty($translation)) {
            wp_send_json_error(__('Término y traducción son requeridos', 'flavor-multilingual'));
        }

        $result = $this->add_glossary_term($term, $translation, $source_lang, $target_lang, array(
            'context'        => sanitize_text_field($_POST['context'] ?? ''),
            'case_sensitive' => !empty($_POST['case_sensitive']),
        ));

        if ($result) {
            wp_send_json_success(array('id' => $result));
        } else {
            wp_send_json_error(__('Error al guardar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Eliminar término del glosario
     */
    public function ajax_delete_glossary_term() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $id = intval($_POST['id'] ?? 0);

        if ($this->delete_glossary_term($id)) {
            wp_send_json_success();
        } else {
            wp_send_json_error(__('Error al eliminar', 'flavor-multilingual'));
        }
    }

    /**
     * AJAX: Importar glosario desde CSV
     */
    public function ajax_import_glossary() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        // TODO: Implementar importación de CSV
        wp_send_json_error(__('Función en desarrollo', 'flavor-multilingual'));
    }

    /**
     * AJAX: Buscar en memoria
     */
    public function ajax_search_memory() {
        check_ajax_referer('flavor_multilingual', 'nonce');

        $text = sanitize_text_field($_POST['text'] ?? '');
        $source_lang = sanitize_key($_POST['source_lang'] ?? '');
        $target_lang = sanitize_key($_POST['target_lang'] ?? '');

        if (empty($text)) {
            wp_send_json_error(__('Texto requerido', 'flavor-multilingual'));
        }

        $results = $this->search_memory($text, $source_lang, $target_lang);

        wp_send_json_success(array('results' => $results));
    }

    // ================================================================
    // API REST
    // ================================================================

    /**
     * Registra endpoints de API
     */
    public function register_rest_endpoints() {
        register_rest_route('flavor-multilingual/v1', '/glossary', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'api_get_glossary'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_add_glossary_term'),
                'permission_callback' => function() {
                    return current_user_can('manage_options');
                },
            ),
        ));

        register_rest_route('flavor-multilingual/v1', '/memory/search', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array($this, 'api_search_memory'),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * API: Obtener glosario
     */
    public function api_get_glossary($request) {
        $source_lang = $request->get_param('source_lang');
        $target_lang = $request->get_param('target_lang');

        if (!$source_lang || !$target_lang) {
            return new WP_Error('missing_params', __('Idiomas requeridos', 'flavor-multilingual'), array('status' => 400));
        }

        return rest_ensure_response($this->get_glossary_terms($source_lang, $target_lang));
    }

    /**
     * API: Añadir término al glosario
     */
    public function api_add_glossary_term($request) {
        $result = $this->add_glossary_term(
            $request->get_param('term'),
            $request->get_param('translation'),
            $request->get_param('source_lang'),
            $request->get_param('target_lang'),
            array(
                'context' => $request->get_param('context'),
                'case_sensitive' => $request->get_param('case_sensitive'),
            )
        );

        if ($result) {
            return rest_ensure_response(array('id' => $result));
        }

        return new WP_Error('save_failed', __('Error al guardar', 'flavor-multilingual'), array('status' => 500));
    }

    /**
     * API: Buscar en memoria
     */
    public function api_search_memory($request) {
        $results = $this->search_memory(
            $request->get_param('text'),
            $request->get_param('source_lang'),
            $request->get_param('target_lang')
        );

        return rest_ensure_response(array('results' => $results));
    }
}
