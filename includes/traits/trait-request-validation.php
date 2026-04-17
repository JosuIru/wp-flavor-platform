<?php
/**
 * Trait para validación y sanitización de requests
 *
 * Proporciona métodos comunes para validar y sanitizar
 * parámetros de requests REST y AJAX.
 *
 * @package FlavorPlatform
 * @subpackage Traits
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para validación de requests
 */
trait Flavor_Request_Validation_Trait {

    /**
     * Límites de paginación por defecto
     *
     * @var array
     */
    protected static $pagination_defaults = [
        'min_per_page'     => 1,
        'max_per_page'     => 100,
        'default_per_page' => 20,
        'min_page'         => 1,
    ];

    /**
     * Sanitiza y valida un ID numérico
     *
     * @param mixed $id    Valor a validar.
     * @param bool  $allow_zero Permitir cero como válido.
     * @return int|null ID sanitizado o null si inválido.
     */
    protected function sanitize_id($id, $allow_zero = false) {
        // Rechazar valores negativos o no numéricos
        if (!is_numeric($id) || (int) $id < 0) {
            return null;
        }
        $id = (int) $id;
        if ($id === 0 && !$allow_zero) {
            return null;
        }
        return $id;
    }

    /**
     * Sanitiza un array de IDs
     *
     * @param mixed $ids Array o string de IDs.
     * @return array Array de IDs válidos.
     */
    protected function sanitize_id_array($ids) {
        if (is_string($ids)) {
            $ids = array_map('trim', explode(',', $ids));
        }

        if (!is_array($ids)) {
            return [];
        }

        // Filtrar solo IDs numéricos positivos
        $valid_ids = [];
        foreach ($ids as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $valid_ids[] = (int) $id;
            }
        }

        return array_values($valid_ids);
    }

    /**
     * Normaliza parámetros de paginación
     *
     * @param mixed $page     Página solicitada.
     * @param mixed $per_page Items por página.
     * @param array $limits   Límites personalizados.
     * @return array ['page' => int, 'per_page' => int, 'offset' => int]
     */
    protected function normalize_pagination($page, $per_page, array $limits = []) {
        $limits = wp_parse_args($limits, self::$pagination_defaults);

        $page = max($limits['min_page'], absint($page) ?: 1);
        $per_page = absint($per_page) ?: $limits['default_per_page'];
        $per_page = max($limits['min_per_page'], min($limits['max_per_page'], $per_page));

        return [
            'page'     => $page,
            'per_page' => $per_page,
            'offset'   => ($page - 1) * $per_page,
        ];
    }

    /**
     * Valida ORDER BY contra whitelist
     *
     * @param string $orderby   Columna solicitada.
     * @param array  $whitelist Columnas permitidas.
     * @param string $default   Valor por defecto.
     * @return string Columna validada.
     */
    protected function validate_orderby($orderby, array $whitelist, $default = 'id') {
        $orderby = strtolower(trim($orderby));
        return in_array($orderby, $whitelist, true) ? $orderby : $default;
    }

    /**
     * Valida dirección de ordenamiento
     *
     * @param string $order Dirección solicitada.
     * @return string 'ASC' o 'DESC'.
     */
    protected function validate_order($order) {
        return strtoupper(trim($order)) === 'ASC' ? 'ASC' : 'DESC';
    }

    /**
     * Sanitiza string para búsqueda
     *
     * @param string $search Término de búsqueda.
     * @param int    $min_length Longitud mínima.
     * @param int    $max_length Longitud máxima.
     * @return string|null String sanitizado o null si inválido.
     */
    protected function sanitize_search($search, $min_length = 2, $max_length = 100) {
        $search = sanitize_text_field($search);
        $length = mb_strlen($search);

        if ($length < $min_length || $length > $max_length) {
            return null;
        }

        return $search;
    }

    /**
     * Sanitiza un email
     *
     * @param string $email Email a validar.
     * @return string|null Email sanitizado o null si inválido.
     */
    protected function sanitize_email_param($email) {
        $email = sanitize_email($email);
        return is_email($email) ? $email : null;
    }

    /**
     * Sanitiza una URL
     *
     * @param string $url   URL a validar.
     * @param array  $protocols Protocolos permitidos.
     * @return string|null URL sanitizada o null si inválida.
     */
    protected function sanitize_url_param($url, array $protocols = ['http', 'https']) {
        $url = esc_url_raw($url, $protocols);
        return !empty($url) ? $url : null;
    }

    /**
     * Sanitiza una fecha
     *
     * @param string $date   Fecha a validar.
     * @param string $format Formato esperado.
     * @return string|null Fecha sanitizada o null si inválida.
     */
    protected function sanitize_date($date, $format = 'Y-m-d') {
        $date = sanitize_text_field($date);
        $parsed = DateTime::createFromFormat($format, $date);

        if ($parsed && $parsed->format($format) === $date) {
            return $date;
        }

        return null;
    }

    /**
     * Sanitiza un valor de enum
     *
     * @param string $value   Valor a validar.
     * @param array  $allowed Valores permitidos.
     * @param string $default Valor por defecto.
     * @return string Valor validado.
     */
    protected function sanitize_enum($value, array $allowed, $default = '') {
        $value = sanitize_text_field($value);
        return in_array($value, $allowed, true) ? $value : $default;
    }

    /**
     * Valida campos requeridos
     *
     * @param array $data   Datos a validar.
     * @param array $fields Campos requeridos.
     * @return array Errores encontrados ['campo' => 'mensaje'].
     */
    protected function validate_required(array $data, array $fields) {
        $errors = [];

        foreach ($fields as $field => $label) {
            if (is_int($field)) {
                $field = $label;
                $label = ucfirst(str_replace('_', ' ', $field));
            }

            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
                $errors[$field] = sprintf(__('%s es requerido', FLAVOR_PLATFORM_TEXT_DOMAIN), $label);
            }
        }

        return $errors;
    }

    /**
     * Extrae y sanitiza parámetros de un request
     *
     * @param WP_REST_Request|array $request Request o array de datos.
     * @param array                 $schema  Schema de campos.
     * @return array Datos sanitizados.
     */
    protected function extract_params($request, array $schema) {
        $data = [];

        foreach ($schema as $field => $config) {
            $value = is_array($request) ? ($request[$field] ?? null) : $request->get_param($field);

            if ($value === null && isset($config['default'])) {
                $value = $config['default'];
            }

            if ($value !== null) {
                $data[$field] = $this->sanitize_by_type($value, $config['type'] ?? 'string', $config);
            }
        }

        return $data;
    }

    /**
     * Sanitiza un valor según su tipo
     *
     * @param mixed  $value  Valor a sanitizar.
     * @param string $type   Tipo de dato.
     * @param array  $config Configuración adicional.
     * @return mixed Valor sanitizado.
     */
    protected function sanitize_by_type($value, $type, array $config = []) {
        switch ($type) {
            case 'int':
            case 'integer':
                return absint($value);

            case 'float':
            case 'number':
                return (float) $value;

            case 'bool':
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);

            case 'email':
                return $this->sanitize_email_param($value);

            case 'url':
                return $this->sanitize_url_param($value);

            case 'date':
                return $this->sanitize_date($value, $config['format'] ?? 'Y-m-d');

            case 'enum':
                return $this->sanitize_enum($value, $config['allowed'] ?? [], $config['default'] ?? '');

            case 'id':
                return $this->sanitize_id($value);

            case 'ids':
                return $this->sanitize_id_array($value);

            case 'html':
                return wp_kses_post($value);

            case 'textarea':
                return sanitize_textarea_field($value);

            case 'string':
            default:
                return sanitize_text_field($value);
        }
    }
}
