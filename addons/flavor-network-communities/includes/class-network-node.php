<?php
/**
 * Modelo de Nodo de Red
 *
 * Representa una entidad/comunidad/empresa en la red.
 * Proporciona métodos CRUD y de consulta.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Node {

    /**
     * Datos del nodo
     */
    private $datos = [];

    /**
     * Tipos de entidad válidos
     */
    const TIPOS_ENTIDAD = [
        'comunidad'    => 'Comunidad',
        'cooperativa'  => 'Cooperativa',
        'asociacion'   => 'Asociación',
        'empresa'      => 'Empresa',
        'colectivo'    => 'Colectivo',
        'fundacion'    => 'Fundación',
        'particular'   => 'Particular',
        'administracion' => 'Administración Pública',
        'educacion'    => 'Centro Educativo',
        'otro'         => 'Otro',
    ];

    /**
     * Niveles de consciencia
     */
    const NIVELES_CONSCIENCIA = [
        'basico'      => 'Básico',
        'transicion'  => 'En Transición',
        'consciente'  => 'Consciente',
        'referente'   => 'Referente',
    ];

    /**
     * Niveles de conexión
     */
    const NIVELES_CONEXION = [
        'visible'   => 'Visible',
        'conectado' => 'Conectado',
        'federado'  => 'Federado',
    ];

    /**
     * Estados de nodo
     */
    const ESTADOS = [
        'activo'    => 'Activo',
        'inactivo'  => 'Inactivo',
        'pendiente' => 'Pendiente',
        'suspendido' => 'Suspendido',
    ];

    /**
     * Constructor
     *
     * @param array|object $datos Datos del nodo
     */
    public function __construct($datos = []) {
        if (is_object($datos)) {
            $datos = (array) $datos;
        }
        $this->datos = $datos;
    }

    /**
     * Obtiene un valor del nodo
     */
    public function __get($propiedad) {
        return $this->datos[$propiedad] ?? null;
    }

    /**
     * Establece un valor del nodo
     */
    public function __set($propiedad, $valor) {
        $this->datos[$propiedad] = $valor;
    }

    /**
     * Comprueba si existe una propiedad
     */
    public function __isset($propiedad) {
        return isset($this->datos[$propiedad]);
    }

    /**
     * Obtiene todos los datos como array
     */
    public function to_array() {
        return $this->datos;
    }

    /**
     * Obtiene datos para la API pública (sin secretos)
     */
    public function to_public_array() {
        $datos_publicos = $this->datos;
        unset($datos_publicos['api_key']);
        unset($datos_publicos['api_secret']);
        unset($datos_publicos['configuracion']);
        return $datos_publicos;
    }

    /**
     * Obtiene datos para tarjeta del directorio
     */
    public function to_card_array() {
        return [
            'id'                => (int) ($this->datos['id'] ?? 0),
            'nombre'            => $this->datos['nombre'] ?? '',
            'slug'              => $this->datos['slug'] ?? '',
            'descripcion_corta' => $this->datos['descripcion_corta'] ?? '',
            'logo_url'          => $this->datos['logo_url'] ?? '',
            'tipo_entidad'      => $this->datos['tipo_entidad'] ?? 'comunidad',
            'sector'            => $this->datos['sector'] ?? '',
            'nivel_consciencia' => $this->datos['nivel_consciencia'] ?? 'basico',
            'ciudad'            => $this->datos['ciudad'] ?? '',
            'pais'              => $this->datos['pais'] ?? 'ES',
            'latitud'           => $this->datos['latitud'] ?? null,
            'longitud'          => $this->datos['longitud'] ?? null,
            'verificado'        => (bool) ($this->datos['verificado'] ?? false),
        ];
    }

    // ─── Métodos estáticos CRUD ───

    /**
     * Busca un nodo por ID
     */
    public static function find($nodo_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE id = %d",
            $nodo_id
        ));

        if (!$resultado) {
            return null;
        }

        return new self($resultado);
    }

    /**
     * Busca un nodo por slug
     */
    public static function find_by_slug($slug) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $resultado = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE slug = %s",
            $slug
        ));

        if (!$resultado) {
            return null;
        }

        return new self($resultado);
    }

    /**
     * Obtiene el nodo local (esta instalación)
     */
    public static function get_local_node() {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $resultado = $wpdb->get_row(
            "SELECT * FROM {$tabla} WHERE es_nodo_local = 1 LIMIT 1"
        );

        if (!$resultado) {
            return null;
        }

        return new self($resultado);
    }

    /**
     * Crea o actualiza el nodo local
     */
    public static function save_local_node($datos_nodo) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $nodo_existente = self::get_local_node();

        $datos_sanitizados = self::sanitize_node_data($datos_nodo);
        $datos_sanitizados['es_nodo_local'] = 1;

        if ($nodo_existente) {
            $wpdb->update(
                $tabla,
                $datos_sanitizados,
                ['id' => $nodo_existente->id]
            );
            return self::find($nodo_existente->id);
        } else {
            // Generar API key y secret para el nodo local
            if (empty($datos_sanitizados['api_key'])) {
                $datos_sanitizados['api_key'] = wp_generate_password(32, false, false);
            }
            if (empty($datos_sanitizados['api_secret'])) {
                $datos_sanitizados['api_secret'] = wp_generate_password(64, false, false);
            }

            $wpdb->insert($tabla, $datos_sanitizados);
            return self::find($wpdb->insert_id);
        }
    }

    /**
     * Crea un nodo remoto
     */
    public static function create($datos_nodo) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $datos_sanitizados = self::sanitize_node_data($datos_nodo);
        $datos_sanitizados['es_nodo_local'] = 0;

        $resultado = $wpdb->insert($tabla, $datos_sanitizados);

        if ($resultado === false) {
            return null;
        }

        return self::find($wpdb->insert_id);
    }

    /**
     * Actualiza un nodo
     */
    public static function update_node($nodo_id, $datos_nodo) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $datos_sanitizados = self::sanitize_node_data($datos_nodo);

        $resultado = $wpdb->update(
            $tabla,
            $datos_sanitizados,
            ['id' => $nodo_id]
        );

        if ($resultado === false) {
            return null;
        }

        return self::find($nodo_id);
    }

    /**
     * Elimina un nodo
     */
    public static function delete_node($nodo_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        // No permitir eliminar nodo local
        $nodo = self::find($nodo_id);
        if ($nodo && $nodo->es_nodo_local) {
            return false;
        }

        return $wpdb->delete($tabla, ['id' => $nodo_id], ['%d']);
    }

    // ─── Consultas ───

    /**
     * Lista nodos activos con filtros
     */
    public static function query($filtros = [], $orden = 'nombre', $direccion = 'ASC', $limite = 50, $offset = 0) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $where_clauses = ["estado = 'activo'"];
        $where_values = [];

        if (!empty($filtros['tipo_entidad'])) {
            $where_clauses[] = 'tipo_entidad = %s';
            $where_values[] = $filtros['tipo_entidad'];
        }

        if (!empty($filtros['sector'])) {
            $where_clauses[] = 'sector = %s';
            $where_values[] = $filtros['sector'];
        }

        if (!empty($filtros['nivel_consciencia'])) {
            $where_clauses[] = 'nivel_consciencia = %s';
            $where_values[] = $filtros['nivel_consciencia'];
        }

        if (!empty($filtros['pais'])) {
            $where_clauses[] = 'pais = %s';
            $where_values[] = $filtros['pais'];
        }

        if (!empty($filtros['ciudad'])) {
            $where_clauses[] = 'ciudad LIKE %s';
            $where_values[] = '%' . $wpdb->esc_like($filtros['ciudad']) . '%';
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda_like = '%' . $wpdb->esc_like($filtros['busqueda']) . '%';
            $where_clauses[] = '(nombre LIKE %s OR descripcion LIKE %s OR tags LIKE %s)';
            $where_values[] = $busqueda_like;
            $where_values[] = $busqueda_like;
            $where_values[] = $busqueda_like;
        }

        if (isset($filtros['verificado'])) {
            $where_clauses[] = 'verificado = %d';
            $where_values[] = (int) $filtros['verificado'];
        }

        if (isset($filtros['es_nodo_local'])) {
            $where_clauses[] = 'es_nodo_local = %d';
            $where_values[] = (int) $filtros['es_nodo_local'];
        }

        $where_sql = implode(' AND ', $where_clauses);

        // Orden seguro
        $ordenes_validos = ['nombre', 'fecha_registro', 'ciudad', 'tipo_entidad', 'nivel_consciencia'];
        if (!in_array($orden, $ordenes_validos)) {
            $orden = 'nombre';
        }
        $direccion = strtoupper($direccion) === 'DESC' ? 'DESC' : 'ASC';

        $sql = "SELECT * FROM {$tabla} WHERE {$where_sql} ORDER BY {$orden} {$direccion} LIMIT %d OFFSET %d";
        $where_values[] = $limite;
        $where_values[] = $offset;

        $resultados = $wpdb->get_results($wpdb->prepare($sql, $where_values));

        return array_map(function($fila) {
            return new self($fila);
        }, $resultados ?: []);
    }

    /**
     * Cuenta nodos con filtros
     */
    public static function count($filtros = []) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $where_clauses = ["estado = 'activo'"];
        $where_values = [];

        if (!empty($filtros['tipo_entidad'])) {
            $where_clauses[] = 'tipo_entidad = %s';
            $where_values[] = $filtros['tipo_entidad'];
        }

        if (!empty($filtros['busqueda'])) {
            $busqueda_like = '%' . $wpdb->esc_like($filtros['busqueda']) . '%';
            $where_clauses[] = '(nombre LIKE %s OR descripcion LIKE %s OR tags LIKE %s)';
            $where_values[] = $busqueda_like;
            $where_values[] = $busqueda_like;
            $where_values[] = $busqueda_like;
        }

        $where_sql = implode(' AND ', $where_clauses);

        if (!empty($where_values)) {
            return (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla} WHERE {$where_sql}",
                $where_values
            ));
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE {$where_sql}");
    }

    /**
     * Búsqueda por proximidad geográfica
     */
    public static function find_nearby($latitud, $longitud, $radio_km = 50, $limite = 20) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        // Fórmula Haversine para distancia en km
        $sql = $wpdb->prepare(
            "SELECT *,
                (6371 * acos(
                    cos(radians(%f)) * cos(radians(latitud)) *
                    cos(radians(longitud) - radians(%f)) +
                    sin(radians(%f)) * sin(radians(latitud))
                )) AS distancia_km
            FROM {$tabla}
            WHERE estado = 'activo'
                AND latitud IS NOT NULL
                AND longitud IS NOT NULL
            HAVING distancia_km <= %f
            ORDER BY distancia_km ASC
            LIMIT %d",
            $latitud, $longitud, $latitud, $radio_km, $limite
        );

        $resultados = $wpdb->get_results($sql);

        return array_map(function($fila) {
            $nodo = new self($fila);
            $nodo->distancia_km = round($fila->distancia_km, 2);
            return $nodo;
        }, $resultados ?: []);
    }

    /**
     * Obtiene nodos para el mapa
     */
    public static function get_map_data($filtros = []) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $where_clauses = [
            "estado = 'activo'",
            "latitud IS NOT NULL",
            "longitud IS NOT NULL",
        ];
        $where_values = [];

        if (!empty($filtros['tipo_entidad'])) {
            $where_clauses[] = 'tipo_entidad = %s';
            $where_values[] = $filtros['tipo_entidad'];
        }

        if (!empty($filtros['nivel_consciencia'])) {
            $where_clauses[] = 'nivel_consciencia = %s';
            $where_values[] = $filtros['nivel_consciencia'];
        }

        if (!empty($filtros['sector'])) {
            $where_clauses[] = 'sector = %s';
            $where_values[] = $filtros['sector'];
        }

        $where_sql = implode(' AND ', $where_clauses);

        $sql = "SELECT id, nombre, slug, descripcion_corta, logo_url, tipo_entidad,
                       sector, nivel_consciencia, latitud, longitud, ciudad, pais, verificado
                FROM {$tabla} WHERE {$where_sql} ORDER BY nombre ASC";

        if (!empty($where_values)) {
            $resultados = $wpdb->get_results($wpdb->prepare($sql, $where_values));
        } else {
            $resultados = $wpdb->get_results($sql);
        }

        return array_map(function($fila) {
            return [
                'id'                => (int) $fila->id,
                'nombre'            => $fila->nombre,
                'slug'              => $fila->slug,
                'descripcion_corta' => $fila->descripcion_corta,
                'logo_url'          => $fila->logo_url,
                'tipo_entidad'      => $fila->tipo_entidad,
                'sector'            => $fila->sector,
                'nivel_consciencia' => $fila->nivel_consciencia,
                'lat'               => (float) $fila->latitud,
                'lng'               => (float) $fila->longitud,
                'ciudad'            => $fila->ciudad,
                'pais'              => $fila->pais,
                'verificado'        => (bool) $fila->verificado,
            ];
        }, $resultados ?: []);
    }

    // ─── Helpers ───

    /**
     * Sanitiza datos de un nodo
     */
    private static function sanitize_node_data($datos) {
        $sanitizados = [];

        $campos_texto = ['nombre', 'descripcion_corta', 'tipo_entidad', 'sector',
                         'nivel_consciencia', 'nivel_sello', 'direccion', 'ciudad',
                         'provincia', 'pais', 'codigo_postal', 'telefono', 'estado'];
        foreach ($campos_texto as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = sanitize_text_field($datos[$campo]);
            }
        }

        if (isset($datos['nombre'])) {
            $sanitizados['nombre'] = sanitize_text_field($datos['nombre']);
        }

        if (isset($datos['slug'])) {
            $sanitizados['slug'] = sanitize_title($datos['slug']);
        }

        if (isset($datos['descripcion'])) {
            $sanitizados['descripcion'] = wp_kses_post($datos['descripcion']);
        }

        $campos_url = ['site_url', 'logo_url', 'banner_url', 'web'];
        foreach ($campos_url as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = esc_url_raw($datos[$campo]);
            }
        }

        if (isset($datos['email'])) {
            $sanitizados['email'] = sanitize_email($datos['email']);
        }

        if (isset($datos['latitud'])) {
            $sanitizados['latitud'] = floatval($datos['latitud']);
        }

        if (isset($datos['longitud'])) {
            $sanitizados['longitud'] = floatval($datos['longitud']);
        }

        if (isset($datos['tags'])) {
            $tags = is_array($datos['tags']) ? $datos['tags'] : explode(',', $datos['tags']);
            $sanitizados['tags'] = wp_json_encode(array_map('sanitize_text_field', $tags));
        }

        if (isset($datos['redes_sociales'])) {
            $redes = is_array($datos['redes_sociales']) ? $datos['redes_sociales'] : [];
            $sanitizados['redes_sociales'] = wp_json_encode($redes);
        }

        if (isset($datos['modulos_activos'])) {
            $modulos = is_array($datos['modulos_activos']) ? $datos['modulos_activos'] : [];
            $sanitizados['modulos_activos'] = wp_json_encode($modulos);
        }

        if (isset($datos['configuracion'])) {
            $config = is_array($datos['configuracion']) ? $datos['configuracion'] : [];
            $sanitizados['configuracion'] = wp_json_encode($config);
        }

        $campos_int = ['miembros_count', 'verificado', 'es_nodo_local'];
        foreach ($campos_int as $campo) {
            if (isset($datos[$campo])) {
                $sanitizados[$campo] = intval($datos[$campo]);
            }
        }

        return $sanitizados;
    }

    /**
     * Obtiene los tags como array
     */
    public function get_tags() {
        if (empty($this->datos['tags'])) {
            return [];
        }
        $decoded = json_decode($this->datos['tags'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene las redes sociales como array
     */
    public function get_redes_sociales() {
        if (empty($this->datos['redes_sociales'])) {
            return [];
        }
        $decoded = json_decode($this->datos['redes_sociales'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene los módulos activos como array
     */
    public function get_modulos_activos() {
        if (empty($this->datos['modulos_activos'])) {
            return [];
        }
        $decoded = json_decode($this->datos['modulos_activos'], true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Obtiene la etiqueta del tipo de entidad
     */
    public function get_tipo_label() {
        return self::TIPOS_ENTIDAD[$this->datos['tipo_entidad'] ?? 'comunidad'] ?? 'Otro';
    }

    /**
     * Obtiene la etiqueta del nivel de consciencia
     */
    public function get_nivel_label() {
        return self::NIVELES_CONSCIENCIA[$this->datos['nivel_consciencia'] ?? 'basico'] ?? 'Básico';
    }
}
