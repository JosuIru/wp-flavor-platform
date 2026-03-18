<?php
/**
 * Gestor de idiomas
 *
 * Operaciones CRUD para los idiomas del sistema.
 *
 * @package FlavorMultilingual
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Language_Manager {

    /**
     * Instancia singleton
     *
     * @var Flavor_Language_Manager|null
     */
    private static $instance = null;

    /**
     * Nombre de la tabla
     *
     * @var string
     */
    private $table;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Language_Manager
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
        $this->table = $wpdb->prefix . 'flavor_languages';
    }

    /**
     * Obtiene todos los idiomas
     *
     * @param bool $only_active Solo idiomas activos
     * @return array
     */
    public function get_all($only_active = false) {
        global $wpdb;

        $where = $only_active ? 'WHERE is_active = 1' : '';
        $results = $wpdb->get_results(
            "SELECT * FROM {$this->table} {$where} ORDER BY sort_order ASC",
            ARRAY_A
        );

        return $results ?: array();
    }

    /**
     * Obtiene un idioma por código
     *
     * @param string $code Código del idioma
     * @return array|null
     */
    public function get_by_code($code) {
        global $wpdb;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table} WHERE code = %s",
            $code
        ), ARRAY_A);
    }

    /**
     * Obtiene el idioma por defecto
     *
     * @return array|null
     */
    public function get_default() {
        global $wpdb;

        return $wpdb->get_row(
            "SELECT * FROM {$this->table} WHERE is_default = 1",
            ARRAY_A
        );
    }

    /**
     * Añade un nuevo idioma
     *
     * @param array $data Datos del idioma
     * @return int|false ID insertado o false
     */
    public function add($data) {
        global $wpdb;

        $defaults = array(
            'code'        => '',
            'locale'      => '',
            'name'        => '',
            'native_name' => '',
            'flag'        => '',
            'is_rtl'      => 0,
            'is_active'   => 1,
            'is_default'  => 0,
            'sort_order'  => $this->get_max_sort_order() + 1,
        );

        $data = wp_parse_args($data, $defaults);

        // Validar código único
        if ($this->get_by_code($data['code'])) {
            return false;
        }

        // Si es default, quitar default a los demás
        if ($data['is_default']) {
            $this->unset_all_defaults();
        }

        $result = $wpdb->insert($this->table, array(
            'code'        => sanitize_key($data['code']),
            'locale'      => sanitize_text_field($data['locale']),
            'name'        => sanitize_text_field($data['name']),
            'native_name' => sanitize_text_field($data['native_name']),
            'flag'        => sanitize_text_field($data['flag']),
            'is_rtl'      => (int) $data['is_rtl'],
            'is_active'   => (int) $data['is_active'],
            'is_default'  => (int) $data['is_default'],
            'sort_order'  => (int) $data['sort_order'],
        ));

        if ($result) {
            $this->clear_cache();
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Actualiza un idioma
     *
     * @param string $code Código del idioma
     * @param array  $data Datos a actualizar
     * @return bool
     */
    public function update($code, $data) {
        global $wpdb;

        $existing = $this->get_by_code($code);
        if (!$existing) {
            return false;
        }

        // Si se está estableciendo como default
        if (isset($data['is_default']) && $data['is_default']) {
            $this->unset_all_defaults();
        }

        $update_data = array();
        $allowed_fields = array('locale', 'name', 'native_name', 'flag', 'is_rtl', 'is_active', 'is_default', 'sort_order');

        foreach ($allowed_fields as $field) {
            if (isset($data[$field])) {
                if (in_array($field, array('is_rtl', 'is_active', 'is_default', 'sort_order'))) {
                    $update_data[$field] = (int) $data[$field];
                } else {
                    $update_data[$field] = sanitize_text_field($data[$field]);
                }
            }
        }

        if (empty($update_data)) {
            return false;
        }

        $result = $wpdb->update($this->table, $update_data, array('code' => $code));

        if ($result !== false) {
            $this->clear_cache();
            return true;
        }

        return false;
    }

    /**
     * Elimina un idioma
     *
     * @param string $code Código del idioma
     * @return bool
     */
    public function delete($code) {
        global $wpdb;

        $language = $this->get_by_code($code);
        if (!$language) {
            return false;
        }

        // No permitir eliminar el idioma por defecto
        if ($language['is_default']) {
            return false;
        }

        $result = $wpdb->delete($this->table, array('code' => $code));

        if ($result) {
            // Eliminar traducciones de este idioma
            $storage = Flavor_Translation_Storage::get_instance();
            $storage->delete_translations(null, null, $code);

            $this->clear_cache();
            return true;
        }

        return false;
    }

    /**
     * Activa o desactiva un idioma
     *
     * @param string $code   Código del idioma
     * @param bool   $active Estado
     * @return bool
     */
    public function set_active($code, $active) {
        $language = $this->get_by_code($code);
        if (!$language) {
            return false;
        }

        // No permitir desactivar el idioma por defecto
        if (!$active && $language['is_default']) {
            return false;
        }

        return $this->update($code, array('is_active' => $active ? 1 : 0));
    }

    /**
     * Establece un idioma como por defecto
     *
     * @param string $code Código del idioma
     * @return bool
     */
    public function set_default($code) {
        $language = $this->get_by_code($code);
        if (!$language) {
            return false;
        }

        // Debe estar activo para ser default
        if (!$language['is_active']) {
            return false;
        }

        return $this->update($code, array('is_default' => 1));
    }

    /**
     * Reordena los idiomas
     *
     * @param array $order Array de códigos en el orden deseado
     * @return bool
     */
    public function reorder($order) {
        global $wpdb;

        if (!is_array($order)) {
            return false;
        }

        foreach ($order as $position => $code) {
            $wpdb->update(
                $this->table,
                array('sort_order' => $position),
                array('code' => $code)
            );
        }

        $this->clear_cache();
        return true;
    }

    /**
     * Obtiene el máximo sort_order actual
     *
     * @return int
     */
    private function get_max_sort_order() {
        global $wpdb;

        $max = $wpdb->get_var("SELECT MAX(sort_order) FROM {$this->table}");
        return $max ? (int) $max : 0;
    }

    /**
     * Quita el flag is_default de todos los idiomas
     */
    private function unset_all_defaults() {
        global $wpdb;
        $wpdb->update($this->table, array('is_default' => 0), array('is_default' => 1));
    }

    /**
     * Inicializa los idiomas por defecto
     *
     * Se ejecuta en la activación del addon.
     */
    public function initialize_default_languages() {
        $default_languages = Flavor_Multilingual::$default_languages;

        foreach ($default_languages as $code => $lang) {
            if (!$this->get_by_code($code)) {
                $this->add(array(
                    'code'        => $code,
                    'locale'      => $lang['locale'],
                    'name'        => $lang['name'],
                    'native_name' => $lang['native_name'],
                    'flag'        => $lang['flag'],
                    'is_rtl'      => $lang['rtl'] ?? false,
                    'is_active'   => in_array($code, array('es', 'en', 'eu')), // Solo estos activos por defecto
                    'is_default'  => ($code === 'es'),
                ));
            }
        }
    }

    /**
     * Obtiene idiomas disponibles para añadir
     *
     * @return array
     */
    public function get_available_to_add() {
        $all_available = Flavor_Multilingual::$default_languages;
        $existing = $this->get_all();

        $existing_codes = array_column($existing, 'code');

        $available = array();
        foreach ($all_available as $code => $lang) {
            if (!in_array($code, $existing_codes)) {
                $available[$code] = $lang;
            }
        }

        return $available;
    }

    /**
     * Limpia la cache
     */
    private function clear_cache() {
        wp_cache_delete('flavor_multilingual_languages');

        // Notificar al core que recargue
        $core = Flavor_Multilingual_Core::get_instance();
        $core->clear_cache();
    }
}
