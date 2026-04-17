<?php
/**
 * Trait para consultas SQL seguras
 *
 * Proporciona métodos helper para construir consultas SQL
 * de forma segura evitando inyecciones.
 *
 * @package FlavorPlatform
 * @subpackage Traits
 * @since 3.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para SQL seguro
 */
trait Flavor_Safe_SQL_Trait {

    /**
     * Construye cláusula WHERE IN segura
     *
     * @param array  $ids    Array de IDs.
     * @param string $column Nombre de la columna.
     * @return string Cláusula SQL o '1=0' si array vacío.
     */
    protected function build_where_in(array $ids, $column = 'id') {
        // Filtrar solo IDs numéricos positivos
        $valid_ids = [];
        foreach ($ids as $id) {
            if (is_numeric($id) && (int) $id > 0) {
                $valid_ids[] = (int) $id;
            }
        }

        if (empty($valid_ids)) {
            return '1=0';
        }

        $placeholders = implode(',', array_fill(0, count($valid_ids), '%d'));
        global $wpdb;
        return $wpdb->prepare("{$column} IN ({$placeholders})", $valid_ids);
    }

    /**
     * Construye cláusula ORDER BY segura
     *
     * @param string $orderby   Columna solicitada.
     * @param string $order     Dirección (ASC/DESC).
     * @param array  $whitelist Columnas permitidas.
     * @param string $default   Columna por defecto.
     * @return string Cláusula ORDER BY.
     */
    protected function build_order_by($orderby, $order, array $whitelist, $default = 'id') {
        $orderby = strtolower(trim($orderby));
        $order = strtoupper(trim($order)) === 'ASC' ? 'ASC' : 'DESC';

        if (!in_array($orderby, $whitelist, true)) {
            $orderby = $default;
        }

        return "ORDER BY {$orderby} {$order}";
    }

    /**
     * Construye cláusula LIMIT/OFFSET segura
     *
     * @param int $limit  Límite.
     * @param int $offset Offset.
     * @return string Cláusula LIMIT OFFSET.
     */
    protected function build_limit_offset($limit, $offset = 0) {
        // Convertir a entero y rechazar negativos
        $limit = (int) $limit;
        $offset = (int) $offset;

        // Aplicar mínimos para valores inválidos o negativos
        $limit = $limit > 0 ? $limit : 1;
        $offset = $offset > 0 ? $offset : 0;

        return sprintf('LIMIT %d OFFSET %d', $limit, $offset);
    }

    /**
     * Construye búsqueda LIKE segura
     *
     * @param string $search  Término de búsqueda.
     * @param array  $columns Columnas donde buscar.
     * @return string Cláusula WHERE para búsqueda.
     */
    protected function build_search_where($search, array $columns) {
        global $wpdb;

        if (empty($search) || empty($columns)) {
            return '1=1';
        }

        $search = '%' . $wpdb->esc_like($search) . '%';
        $conditions = [];

        foreach ($columns as $column) {
            $conditions[] = $wpdb->prepare("{$column} LIKE %s", $search);
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    /**
     * Construye condición de rango de fechas segura
     *
     * @param string      $column     Columna de fecha.
     * @param string|null $date_from  Fecha desde (Y-m-d).
     * @param string|null $date_to    Fecha hasta (Y-m-d).
     * @return string Cláusula WHERE para rango.
     */
    protected function build_date_range($column, $date_from = null, $date_to = null) {
        global $wpdb;
        $conditions = [];

        if (!empty($date_from)) {
            $conditions[] = $wpdb->prepare("{$column} >= %s", $date_from);
        }

        if (!empty($date_to)) {
            $conditions[] = $wpdb->prepare("{$column} <= %s", $date_to);
        }

        return empty($conditions) ? '1=1' : implode(' AND ', $conditions);
    }

    /**
     * Obtiene el conteo total para paginación
     *
     * @param string $table       Tabla (sin prefijo).
     * @param string $where       Cláusula WHERE.
     * @param array  $where_args  Argumentos para prepare.
     * @return int Conteo total.
     */
    protected function get_total_count($table, $where = '1=1', array $where_args = []) {
        global $wpdb;
        $full_table = $wpdb->prefix . $table;

        $sql = "SELECT COUNT(*) FROM {$full_table} WHERE {$where}";

        if (!empty($where_args)) {
            $sql = $wpdb->prepare($sql, $where_args);
        }

        return (int) $wpdb->get_var($sql);
    }

    /**
     * Verifica si una tabla existe
     *
     * @param string $table Nombre de la tabla (sin prefijo).
     * @return bool
     */
    protected function table_exists($table) {
        global $wpdb;
        static $cache = [];

        $full_table = $wpdb->prefix . $table;

        if (!isset($cache[$full_table])) {
            $cache[$full_table] = $wpdb->get_var(
                $wpdb->prepare('SHOW TABLES LIKE %s', $full_table)
            ) === $full_table;
        }

        return $cache[$full_table];
    }

    /**
     * Ejecuta una transacción segura
     *
     * @param callable $callback Función a ejecutar dentro de la transacción.
     * @return mixed Resultado del callback o WP_Error si falla.
     */
    protected function run_transaction(callable $callback) {
        global $wpdb;

        $wpdb->query('START TRANSACTION');

        try {
            $result = $callback();

            if (is_wp_error($result)) {
                $wpdb->query('ROLLBACK');
                return $result;
            }

            $wpdb->query('COMMIT');
            return $result;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error(
                'transaction_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Inserta un registro de forma segura
     *
     * @param string $table  Tabla (sin prefijo).
     * @param array  $data   Datos a insertar.
     * @param array  $format Formatos (%s, %d, %f).
     * @return int|WP_Error ID insertado o error.
     */
    protected function safe_insert($table, array $data, array $format = []) {
        global $wpdb;
        $full_table = $wpdb->prefix . $table;

        $result = $wpdb->insert($full_table, $data, $format);

        if ($result === false) {
            return new WP_Error(
                'insert_failed',
                $wpdb->last_error ?: __('Error al insertar registro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 500]
            );
        }

        return $wpdb->insert_id;
    }

    /**
     * Actualiza un registro de forma segura
     *
     * @param string $table  Tabla (sin prefijo).
     * @param array  $data   Datos a actualizar.
     * @param array  $where  Condiciones WHERE.
     * @param array  $format Formatos de datos.
     * @param array  $where_format Formatos de WHERE.
     * @return int|WP_Error Filas afectadas o error.
     */
    protected function safe_update($table, array $data, array $where, array $format = [], array $where_format = []) {
        global $wpdb;
        $full_table = $wpdb->prefix . $table;

        $result = $wpdb->update($full_table, $data, $where, $format, $where_format);

        if ($result === false) {
            return new WP_Error(
                'update_failed',
                $wpdb->last_error ?: __('Error al actualizar registro', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['status' => 500]
            );
        }

        return $result;
    }

    /**
     * Obtiene un registro por ID
     *
     * @param string $table Tabla (sin prefijo).
     * @param int    $id    ID del registro.
     * @return object|null Registro o null.
     */
    protected function get_by_id($table, $id) {
        global $wpdb;
        $full_table = $wpdb->prefix . $table;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$full_table} WHERE id = %d",
            absint($id)
        ));
    }
}
