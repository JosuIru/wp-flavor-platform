<?php
/**
 * Modelo de Nodo de Red
 *
 * Representa una entidad/comunidad/empresa en la red.
 * Proporciona métodos CRUD y de consulta.
 *
 * @package FlavorPlatform\Network
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
     * Caché estática del nodo local (persiste durante el request)
     */
    private static $local_node_cache = null;

    /**
     * Prefijo para transients de caché
     */
    const CACHE_PREFIX = 'flavor_network_';

    /**
     * Tiempos de expiración de caché (en segundos)
     */
    const CACHE_TTL = [
        'local_node' => 300,      // 5 minutos
        'directory'  => 600,      // 10 minutos
        'map_data'   => 900,      // 15 minutos
        'node_by_id' => 300,      // 5 minutos
        'nearby'     => 600,      // 10 minutos
    ];

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
     * Tipos de contenido compartido válidos
     */
    const TIPOS_CONTENIDO = [
        'producto'   => 'Producto',
        'servicio'   => 'Servicio',
        'espacio'    => 'Espacio',
        'recurso'    => 'Recurso',
        'excedente'  => 'Excedente',
        'necesidad'  => 'Necesidad',
        'saber'      => 'Saber/Formación',
    ];

    /**
     * Tipos de colaboración
     */
    const TIPOS_COLABORACION = [
        'compra_colectiva' => 'Compra Colectiva',
        'proyecto'         => 'Proyecto Conjunto',
        'alianza'          => 'Alianza Temática',
        'hermanamiento'    => 'Hermanamiento',
        'mentoria'         => 'Mentoría',
        'logistica'        => 'Logística Compartida',
    ];

    /**
     * Tipos de publicación en tablón
     */
    const TIPOS_TABLON = [
        'anuncio'    => 'Anuncio',
        'oferta'     => 'Oferta',
        'demanda'    => 'Demanda',
        'noticia'    => 'Noticia',
        'evento'     => 'Evento',
    ];

    /**
     * Niveles de urgencia para alertas
     */
    const NIVELES_URGENCIA = [
        'baja'   => 'Baja',
        'media'  => 'Media',
        'alta'   => 'Alta',
        'critica' => 'Crítica',
    ];

    /**
     * Ámbitos de visibilidad
     */
    const AMBITOS = [
        'privado'   => 'Privado',
        'conectado' => 'Nodos Conectados',
        'red'       => 'Toda la Red',
        'publico'   => 'Público',
    ];

    /**
     * Estados de conexión
     */
    const ESTADOS_CONEXION = [
        'pendiente' => 'Pendiente',
        'aprobada'  => 'Aprobada',
        'rechazada' => 'Rechazada',
        'bloqueada' => 'Bloqueada',
    ];

    /**
     * Modalidades de servicio/evento
     */
    const MODALIDADES = [
        'presencial' => 'Presencial',
        'online'     => 'Online',
        'hibrido'    => 'Híbrido',
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
        unset($datos_publicos['api_key'], $datos_publicos['api_secret'], $datos_publicos['configuracion']);
        // Redactar posibles campos sensibles si existen
        $sensibles = [
            'email',
            'telefono',
            'phone',
            'contacto_email',
            'contacto_telefono',
            'contact_email',
            'contact_phone',
            'direccion',
            'direccion_exacta',
            'address',
        ];
        foreach ($sensibles as $key) {
            if (array_key_exists($key, $datos_publicos)) {
                unset($datos_publicos[$key]);
            }
        }
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
     * Usa caché estática (por request) y transient (persistente)
     */
    public static function get_local_node() {
        // Caché estática por request
        if (self::$local_node_cache !== null) {
            return self::$local_node_cache;
        }

        // Intentar obtener de transient
        $cache_key = self::CACHE_PREFIX . 'local_node';
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            self::$local_node_cache = new self($cached_data);
            return self::$local_node_cache;
        }

        // Consulta a BD
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $resultado = $wpdb->get_row(
            "SELECT * FROM {$tabla} WHERE es_nodo_local = 1 LIMIT 1"
        );

        if (!$resultado) {
            self::$local_node_cache = false;
            return null;
        }

        // Guardar en transient
        set_transient($cache_key, (array) $resultado, self::CACHE_TTL['local_node']);

        self::$local_node_cache = new self($resultado);
        return self::$local_node_cache;
    }

    /**
     * Invalida la caché del nodo local
     */
    public static function invalidate_local_node_cache() {
        self::$local_node_cache = null;
        delete_transient(self::CACHE_PREFIX . 'local_node');
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
            // Invalidar caché
            self::invalidate_local_node_cache();
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
            // Invalidar caché
            self::invalidate_local_node_cache();
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

        // Invalidar cachés de directorio y mapa
        self::invalidate_directory_cache();
        self::invalidate_map_cache();

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

        // Invalidar cachés
        self::invalidate_directory_cache();
        self::invalidate_map_cache();
        delete_transient(self::CACHE_PREFIX . 'node_' . $nodo_id);

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

        $resultado = $wpdb->delete($tabla, ['id' => $nodo_id], ['%d']);

        if ($resultado) {
            // Invalidar cachés
            self::invalidate_directory_cache();
            self::invalidate_map_cache();
            delete_transient(self::CACHE_PREFIX . 'node_' . $nodo_id);
        }

        return $resultado;
    }

    /**
     * Invalida todas las cachés del directorio
     */
    public static function invalidate_directory_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . 'dir_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . self::CACHE_PREFIX . 'dir_%'
            )
        );
    }

    /**
     * Invalida todas las cachés del sistema de red
     */
    public static function invalidate_all_cache() {
        self::invalidate_local_node_cache();
        self::invalidate_directory_cache();
        self::invalidate_map_cache();
    }

    // ─── Consultas ───

    /**
     * Lista nodos activos con filtros (con caché para primeras páginas)
     */
    public static function query($filtros = [], $orden = 'nombre', $direccion = 'ASC', $limite = 50, $offset = 0) {
        // Solo cachear primeras páginas sin filtros complejos para evitar explosión de claves
        $use_cache = ($offset < 100) && empty($filtros['busqueda']);

        if ($use_cache) {
            $cache_params = compact('filtros', 'orden', 'direccion', 'limite', 'offset');
            $cache_key = self::CACHE_PREFIX . 'dir_' . md5(wp_json_encode($cache_params));
            $cached_data = get_transient($cache_key);

            if ($cached_data !== false) {
                return array_map(function($datos) {
                    return new self($datos);
                }, $cached_data);
            }
        }

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

        $nodos = array_map(function($fila) {
            return new self($fila);
        }, $resultados ?: []);

        // Guardar en caché si aplica
        if ($use_cache && !empty($nodos)) {
            $datos_para_cache = array_map(function($nodo) {
                return $nodo->to_array();
            }, $nodos);
            set_transient($cache_key, $datos_para_cache, self::CACHE_TTL['directory']);
        }

        return $nodos;
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
     * Obtiene nodos para el mapa (con caché)
     */
    public static function get_map_data($filtros = []) {
        // Generar clave de caché basada en filtros
        $cache_key = self::CACHE_PREFIX . 'map_' . md5(wp_json_encode($filtros));
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }

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

        $map_data = array_map(function($fila) {
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

        // Guardar en caché
        set_transient($cache_key, $map_data, self::CACHE_TTL['map_data']);

        return $map_data;
    }

    /**
     * Invalida todas las cachés de mapa
     */
    public static function invalidate_map_cache() {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . self::CACHE_PREFIX . 'map_%'
            )
        );
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_timeout_' . self::CACHE_PREFIX . 'map_%'
            )
        );
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

    // ─── Gestión de API Keys ───

    /**
     * Rota la API key de un nodo
     *
     * Genera nuevas credenciales API y establece fecha de expiración.
     * Invalida la caché relacionada para forzar reautenticación.
     *
     * @param int $node_id ID del nodo
     * @param int $expiry_days Días hasta que expire la nueva key (default: 90)
     * @return array|false Nuevas credenciales ['api_key', 'api_secret'] o false si falla
     */
    public static function rotate_api_key($node_id, $expiry_days = 90) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        // Verificar que el nodo existe
        $nodo = self::find($node_id);
        if (!$nodo) {
            return false;
        }

        // Generar nuevas credenciales
        $new_key = wp_generate_password(32, false, false);
        $new_secret = wp_generate_password(64, false, false);

        // Calcular fecha de expiración
        $expires_at = date('Y-m-d H:i:s', strtotime("+{$expiry_days} days"));

        // Actualizar en base de datos
        $resultado = $wpdb->update(
            $tabla,
            [
                'api_key'            => $new_key,
                'api_secret'         => $new_secret,
                'api_key_expires_at' => $expires_at,
                'api_key_rotated_at' => current_time('mysql'),
            ],
            ['id' => $node_id],
            ['%s', '%s', '%s', '%s'],
            ['%d']
        );

        if ($resultado === false) {
            return false;
        }

        // Invalidar cachés relacionadas
        self::invalidate_cache();
        delete_transient(self::CACHE_PREFIX . 'node_' . $node_id);

        // Si es el nodo local, invalidar también su caché específica
        if ($nodo->es_nodo_local) {
            self::invalidate_local_node_cache();
        }

        // Log de la rotación
        if (defined('FLAVOR_PLATFORM_DEBUG') && FLAVOR_PLATFORM_DEBUG) {
            flavor_log_debug(
                sprintf('API key rotada para nodo %d (%s). Expira: %s', $node_id, $nodo->nombre, $expires_at),
                'NetworkNode'
            );
        }

        // Hook para notificaciones o sincronización
        do_action('flavor_network_api_key_rotated', $node_id, [
            'expires_at' => $expires_at,
            'rotated_at' => current_time('mysql'),
        ]);

        return [
            'api_key'    => $new_key,
            'api_secret' => $new_secret,
            'expires_at' => $expires_at,
        ];
    }

    /**
     * Verifica si el token API de un nodo ha expirado
     *
     * @param int $node_id ID del nodo
     * @return bool True si el token está expirado, false si es válido o no tiene fecha de expiración
     */
    public static function check_token_expiry($node_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $expiry = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key_expires_at FROM {$tabla} WHERE id = %d",
            $node_id
        ));

        // Si no hay fecha de expiración, el token no expira
        if (empty($expiry)) {
            return false;
        }

        // Verificar si ha expirado
        return strtotime($expiry) < time();
    }

    /**
     * Verifica si el token API expirará pronto (dentro de X días)
     *
     * @param int $node_id ID del nodo
     * @param int $days_threshold Días de umbral para considerar "pronto" (default: 7)
     * @return bool True si expira pronto
     */
    public static function token_expires_soon($node_id, $days_threshold = 7) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $expiry = $wpdb->get_var($wpdb->prepare(
            "SELECT api_key_expires_at FROM {$tabla} WHERE id = %d",
            $node_id
        ));

        if (empty($expiry)) {
            return false;
        }

        $threshold_date = strtotime("+{$days_threshold} days");
        return strtotime($expiry) < $threshold_date;
    }

    /**
     * Obtiene información sobre el estado del token API de un nodo
     *
     * @param int $node_id ID del nodo
     * @return array Información del token
     */
    public static function get_token_status($node_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT api_key_expires_at, api_key_rotated_at FROM {$tabla} WHERE id = %d",
            $node_id
        ));

        if (!$result) {
            return [
                'exists'       => false,
                'expires_at'   => null,
                'rotated_at'   => null,
                'is_expired'   => false,
                'expires_soon' => false,
                'days_left'    => null,
            ];
        }

        $is_expired = false;
        $expires_soon = false;
        $days_left = null;

        if (!empty($result->api_key_expires_at)) {
            $expiry_time = strtotime($result->api_key_expires_at);
            $current_time = time();
            $is_expired = $expiry_time < $current_time;

            if (!$is_expired) {
                $days_left = ceil(($expiry_time - $current_time) / DAY_IN_SECONDS);
                $expires_soon = $days_left <= 7;
            }
        }

        return [
            'exists'       => true,
            'expires_at'   => $result->api_key_expires_at,
            'rotated_at'   => $result->api_key_rotated_at,
            'is_expired'   => $is_expired,
            'expires_soon' => $expires_soon,
            'days_left'    => $days_left,
        ];
    }

    /**
     * Valida una API key contra un nodo específico
     *
     * @param int    $node_id ID del nodo
     * @param string $api_key API key a validar
     * @param string $api_secret API secret a validar (opcional)
     * @return bool|WP_Error True si es válida, WP_Error si no
     */
    public static function validate_api_key($node_id, $api_key, $api_secret = null) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('nodes');

        // Obtener credenciales del nodo
        $node = $wpdb->get_row($wpdb->prepare(
            "SELECT api_key, api_secret, api_key_expires_at, estado FROM {$tabla} WHERE id = %d",
            $node_id
        ));

        if (!$node) {
            return new WP_Error('node_not_found', __('Nodo no encontrado', 'flavor-network-communities'));
        }

        // Verificar estado del nodo
        if ($node->estado !== 'activo') {
            return new WP_Error('node_inactive', __('El nodo no está activo', 'flavor-network-communities'));
        }

        // Verificar API key
        if (!hash_equals($node->api_key, $api_key)) {
            return new WP_Error('invalid_api_key', __('API key inválida', 'flavor-network-communities'));
        }

        // Verificar API secret si se proporciona
        if ($api_secret !== null && !hash_equals($node->api_secret, $api_secret)) {
            return new WP_Error('invalid_api_secret', __('API secret inválido', 'flavor-network-communities'));
        }

        // Verificar expiración
        if (!empty($node->api_key_expires_at) && strtotime($node->api_key_expires_at) < time()) {
            return new WP_Error('token_expired', __('El token API ha expirado', 'flavor-network-communities'));
        }

        return true;
    }

    /**
     * Invalida todas las cachés del sistema
     */
    public static function invalidate_cache() {
        self::invalidate_local_node_cache();
        self::invalidate_directory_cache();
        self::invalidate_map_cache();
    }

    // ═══════════════════════════════════════════════════════════
    // ACL - PERMISOS POR NODO/TIPO-CONTENIDO
    // ═══════════════════════════════════════════════════════════

    /**
     * Jerarquia de permisos para comparaciones
     */
    const PERMISSION_HIERARCHY = [
        'leer'     => 1,
        'escribir' => 2,
        'admin'    => 3,
    ];

    /**
     * Verifica si un nodo tiene un permiso especifico para un tipo de contenido
     *
     * @param int    $nodo_id           ID del nodo
     * @param string $tipo_contenido    Tipo de contenido (evento, producto, servicio, etc.)
     * @param string $permiso_requerido Permiso requerido: 'leer', 'escribir', 'admin'
     * @return bool True si el nodo tiene el permiso suficiente
     */
    public static function check_permission($nodo_id, $tipo_contenido, $permiso_requerido = 'leer') {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('permissions');

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            // Si no existe la tabla de permisos, permitir lectura por defecto
            return $permiso_requerido === 'leer';
        }

        $permiso_actual = $wpdb->get_var($wpdb->prepare(
            "SELECT permiso FROM {$tabla} WHERE nodo_id = %d AND tipo_contenido = %s",
            $nodo_id,
            $tipo_contenido
        ));

        if (!$permiso_actual) {
            // Sin permiso explicito: solo lectura por defecto
            return $permiso_requerido === 'leer';
        }

        $nivel_actual = self::PERMISSION_HIERARCHY[$permiso_actual] ?? 0;
        $nivel_requerido = self::PERMISSION_HIERARCHY[$permiso_requerido] ?? 0;

        return $nivel_actual >= $nivel_requerido;
    }

    /**
     * Establece un permiso para un nodo sobre un tipo de contenido
     *
     * @param int    $nodo_id        ID del nodo
     * @param string $tipo_contenido Tipo de contenido
     * @param string $permiso        Permiso: 'leer', 'escribir', 'admin'
     * @param int    $otorgado_por   ID del usuario que otorga el permiso (opcional)
     * @param string $notas          Notas sobre el permiso (opcional)
     * @return bool True si se guardo correctamente
     */
    public static function set_permission($nodo_id, $tipo_contenido, $permiso, $otorgado_por = null, $notas = '') {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('permissions');

        // Validar permiso
        if (!array_key_exists($permiso, self::PERMISSION_HIERARCHY)) {
            return false;
        }

        $datos = [
            'nodo_id'        => absint($nodo_id),
            'tipo_contenido' => sanitize_text_field($tipo_contenido),
            'permiso'        => $permiso,
            'updated_at'     => current_time('mysql'),
        ];

        if ($otorgado_por) {
            $datos['otorgado_por'] = absint($otorgado_por);
        }

        if ($notas) {
            $datos['notas'] = sanitize_textarea_field($notas);
        }

        // Usar REPLACE para insertar o actualizar
        $resultado = $wpdb->replace($tabla, $datos);

        // Hook para auditoría
        do_action('flavor_network_permission_changed', $nodo_id, $tipo_contenido, $permiso, $otorgado_por);

        return $resultado !== false;
    }

    /**
     * Elimina un permiso especifico
     *
     * @param int    $nodo_id        ID del nodo
     * @param string $tipo_contenido Tipo de contenido
     * @return bool True si se elimino correctamente
     */
    public static function revoke_permission($nodo_id, $tipo_contenido) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('permissions');

        $resultado = $wpdb->delete(
            $tabla,
            [
                'nodo_id'        => $nodo_id,
                'tipo_contenido' => $tipo_contenido,
            ],
            ['%d', '%s']
        );

        // Hook para auditoría
        do_action('flavor_network_permission_revoked', $nodo_id, $tipo_contenido);

        return $resultado !== false;
    }

    /**
     * Obtiene todos los permisos de un nodo
     *
     * @param int $nodo_id ID del nodo
     * @return array Lista de permisos ['tipo_contenido' => 'permiso', ...]
     */
    public static function get_node_permissions($nodo_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('permissions');

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '{$tabla}'") !== $tabla) {
            return [];
        }

        $permisos = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_contenido, permiso, otorgado_por, notas, created_at, updated_at
             FROM {$tabla} WHERE nodo_id = %d",
            $nodo_id
        ), ARRAY_A);

        $resultado = [];
        foreach ($permisos as $permiso) {
            $resultado[$permiso['tipo_contenido']] = $permiso;
        }

        return $resultado;
    }

    /**
     * Establece permisos en lote para un nodo
     *
     * @param int   $nodo_id  ID del nodo
     * @param array $permisos Array ['tipo_contenido' => 'permiso', ...]
     * @param int   $otorgado_por ID del usuario que otorga los permisos
     * @return int Numero de permisos establecidos correctamente
     */
    public static function set_permissions_bulk($nodo_id, $permisos, $otorgado_por = null) {
        $contador_exitosos = 0;

        foreach ($permisos as $tipo_contenido => $permiso) {
            if (self::set_permission($nodo_id, $tipo_contenido, $permiso, $otorgado_por)) {
                $contador_exitosos++;
            }
        }

        return $contador_exitosos;
    }

    // ═══════════════════════════════════════════════════════════
    // VALIDACION DE TOPOLOGIA - DETECCION DE CICLOS
    // ═══════════════════════════════════════════════════════════

    /**
     * Verifica si crear una conexion de from_node a to_node crearia un ciclo
     *
     * Un ciclo ocurre cuando A -> B y luego B -> A, o cadenas mas largas
     * como A -> B -> C -> A.
     *
     * @param int $from_node_id ID del nodo origen
     * @param int $to_node_id   ID del nodo destino
     * @return bool True si crearia un ciclo, false si es seguro
     */
    public static function would_create_cycle($from_node_id, $to_node_id) {
        // No puede haber ciclo si conectamos a nosotros mismos
        if ($from_node_id === $to_node_id) {
            return true; // Esto es un auto-ciclo, deberia evitarse
        }

        // Verificar si hay un camino de to_node_id a from_node_id
        // Si existe, entonces agregar from -> to crearia un ciclo
        $visitados = [];
        return self::has_path_to($to_node_id, $from_node_id, $visitados);
    }

    /**
     * Verifica recursivamente si existe un camino de un nodo a otro
     *
     * @param int   $desde     Nodo de partida
     * @param int   $hasta     Nodo de destino
     * @param array $visitados Array de nodos ya visitados (para evitar bucles infinitos)
     * @return bool True si existe un camino
     */
    private static function has_path_to($desde, $hasta, &$visitados) {
        // Si llegamos al destino, hay un camino
        if ($desde === $hasta) {
            return true;
        }

        // Si ya visitamos este nodo, no hay que volver a explorarlo
        if (in_array($desde, $visitados, true)) {
            return false;
        }

        // Marcar como visitado
        $visitados[] = $desde;

        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');

        // Obtener todas las conexiones salientes aprobadas desde este nodo
        $conexiones_salientes = $wpdb->get_col($wpdb->prepare(
            "SELECT nodo_destino_id FROM {$tabla}
             WHERE nodo_origen_id = %d AND estado = 'aprobada'",
            $desde
        ));

        // Explorar cada conexion saliente
        foreach ($conexiones_salientes as $siguiente) {
            $siguiente_id = (int) $siguiente;
            if (self::has_path_to($siguiente_id, $hasta, $visitados)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valida si una conexion puede ser aprobada sin crear ciclos
     *
     * @param int $conexion_id ID de la conexion a validar
     * @return array ['valid' => bool, 'message' => string]
     */
    public static function validate_connection_topology($conexion_id) {
        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');

        $conexion = $wpdb->get_row($wpdb->prepare(
            "SELECT nodo_origen_id, nodo_destino_id, estado FROM {$tabla} WHERE id = %d",
            $conexion_id
        ));

        if (!$conexion) {
            return [
                'valid'   => false,
                'message' => __('Conexion no encontrada', 'flavor-network-communities'),
            ];
        }

        // Si ya esta aprobada, no validar de nuevo
        if ($conexion->estado === 'aprobada') {
            return [
                'valid'   => true,
                'message' => __('Conexion ya aprobada', 'flavor-network-communities'),
            ];
        }

        // Verificar si crearia un ciclo
        if (self::would_create_cycle((int) $conexion->nodo_origen_id, (int) $conexion->nodo_destino_id)) {
            return [
                'valid'   => false,
                'message' => __('Aprobar esta conexion crearia un ciclo en la topologia de la red', 'flavor-network-communities'),
            ];
        }

        return [
            'valid'   => true,
            'message' => __('Conexion valida para aprobar', 'flavor-network-communities'),
        ];
    }

    /**
     * Obtiene la profundidad maxima de la red desde un nodo
     *
     * Util para diagnosticos y evitar redes excesivamente profundas.
     *
     * @param int $nodo_id   ID del nodo de partida
     * @param int $max_depth Profundidad maxima a explorar (limite de seguridad)
     * @return int Profundidad maxima encontrada
     */
    public static function get_network_depth($nodo_id, $max_depth = 100) {
        $visitados = [];
        return self::calculate_depth($nodo_id, 0, $visitados, $max_depth);
    }

    /**
     * Calcula recursivamente la profundidad de la red
     *
     * @param int   $nodo_id       Nodo actual
     * @param int   $profundidad   Profundidad actual
     * @param array $visitados     Nodos visitados
     * @param int   $limite        Limite maximo
     * @return int Profundidad maxima encontrada
     */
    private static function calculate_depth($nodo_id, $profundidad, &$visitados, $limite) {
        if ($profundidad >= $limite || in_array($nodo_id, $visitados, true)) {
            return $profundidad;
        }

        $visitados[] = $nodo_id;

        global $wpdb;
        $tabla = Flavor_Network_Installer::get_table_name('connections');

        $conexiones = $wpdb->get_col($wpdb->prepare(
            "SELECT nodo_destino_id FROM {$tabla}
             WHERE nodo_origen_id = %d AND estado = 'aprobada'",
            $nodo_id
        ));

        $profundidad_maxima = $profundidad;

        foreach ($conexiones as $siguiente) {
            $profundidad_hijo = self::calculate_depth((int) $siguiente, $profundidad + 1, $visitados, $limite);
            $profundidad_maxima = max($profundidad_maxima, $profundidad_hijo);
        }

        return $profundidad_maxima;
    }
}
