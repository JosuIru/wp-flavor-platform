<?php
/**
 * Modulo de Bares y Hosteleria para Chat IA
 *
 * Directorio y gestion de bares, restaurantes y locales de hosteleria
 * con carta, eventos y reservas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo: Bares y Hosteleria
 *
 * Gestiona un directorio completo de establecimientos de hosteleria,
 * incluyendo cartas/menus, reservas, valoraciones y estadisticas.
 */
class Flavor_Chat_Bares_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'bares';
        $this->name = __('Bares y Hosteleria', 'flavor-chat-ia');
        $this->description = __('Directorio y gestion de bares, restaurantes y locales de hosteleria con carta, eventos y reservas', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_bares = $wpdb->prefix . 'flavor_bares';

        return Flavor_Chat_Helpers::tabla_existe($tabla_bares);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Bares no estan creadas. Activa el modulo para crearlas automaticamente.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'permitir_reservas'       => true,
            'permitir_valoraciones'   => true,
            'requiere_login_reserva'  => true,
            'limite_resultados'       => 12,
            'tipos_establecimiento'   => [
                'bar'         => __('Bar', 'flavor-chat-ia'),
                'restaurante' => __('Restaurante', 'flavor-chat-ia'),
                'cafeteria'   => __('Cafeteria', 'flavor-chat-ia'),
                'pub'         => __('Pub', 'flavor-chat-ia'),
                'terraza'     => __('Terraza', 'flavor-chat-ia'),
                'cocteleria'  => __('Cocteleria', 'flavor-chat-ia'),
            ],
            'categorias_carta' => [
                'tapas'    => __('Tapas', 'flavor-chat-ia'),
                'raciones' => __('Raciones', 'flavor-chat-ia'),
                'entrantes'=> __('Entrantes', 'flavor-chat-ia'),
                'carnes'   => __('Carnes', 'flavor-chat-ia'),
                'pescados' => __('Pescados', 'flavor-chat-ia'),
                'bebidas'  => __('Bebidas', 'flavor-chat-ia'),
                'postres'  => __('Postres', 'flavor-chat-ia'),
                'cocktails'=> __('Cocktails', 'flavor-chat-ia'),
                'vinos'    => __('Vinos', 'flavor-chat-ia'),
                'cafes'    => __('Cafes', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_bares = $wpdb->prefix . 'flavor_bares';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_bares)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el modulo
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_bares        = $wpdb->prefix . 'flavor_bares';
        $tabla_carta        = $wpdb->prefix . 'flavor_bares_carta';
        $tabla_reservas     = $wpdb->prefix . 'flavor_bares_reservas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';

        $sql_bares = "CREATE TABLE IF NOT EXISTS $tabla_bares (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('bar','restaurante','cafeteria','pub','terraza','cocteleria') DEFAULT 'bar',
            imagen varchar(255) DEFAULT NULL,
            galeria text DEFAULT NULL,
            direccion text DEFAULT NULL,
            latitud decimal(10,8) DEFAULT NULL,
            longitud decimal(11,8) DEFAULT NULL,
            telefono varchar(50) DEFAULT NULL,
            email varchar(200) DEFAULT NULL,
            web varchar(255) DEFAULT NULL,
            redes_sociales text DEFAULT NULL,
            horario text DEFAULT NULL,
            propietario_id bigint(20) unsigned DEFAULT NULL,
            valoracion_media decimal(3,2) DEFAULT 0.00,
            valoraciones_count int(11) DEFAULT 0,
            caracteristicas text DEFAULT NULL,
            estado enum('activo','cerrado_temporal','cerrado') DEFAULT 'activo',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY estado (estado),
            KEY propietario_id (propietario_id),
            KEY valoracion_media (valoracion_media)
        ) $charset_collate;";

        $sql_carta = "CREATE TABLE IF NOT EXISTS $tabla_carta (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bar_id bigint(20) unsigned NOT NULL,
            categoria varchar(100) DEFAULT NULL,
            nombre varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            precio decimal(8,2) NOT NULL DEFAULT 0.00,
            imagen varchar(255) DEFAULT NULL,
            alergenos text DEFAULT NULL,
            es_destacado tinyint(1) DEFAULT 0,
            disponible tinyint(1) DEFAULT 1,
            orden int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bar_id (bar_id),
            KEY categoria (categoria),
            KEY es_destacado (es_destacado),
            KEY disponible (disponible)
        ) $charset_collate;";

        $sql_reservas = "CREATE TABLE IF NOT EXISTS $tabla_reservas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bar_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            nombre_reserva varchar(200) NOT NULL,
            telefono varchar(50) DEFAULT NULL,
            fecha date NOT NULL,
            hora time NOT NULL,
            comensales int(11) NOT NULL DEFAULT 2,
            notas text DEFAULT NULL,
            estado enum('pendiente','confirmada','cancelada','completada','no_show') DEFAULT 'pendiente',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY bar_id (bar_id),
            KEY user_id (user_id),
            KEY fecha (fecha),
            KEY estado (estado)
        ) $charset_collate;";

        $sql_valoraciones = "CREATE TABLE IF NOT EXISTS $tabla_valoraciones (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            bar_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            puntuacion tinyint(3) unsigned NOT NULL DEFAULT 5,
            comentario text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY bar_usuario (bar_id, user_id),
            KEY bar_id (bar_id),
            KEY user_id (user_id),
            KEY puntuacion (puntuacion)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_bares);
        dbDelta($sql_carta);
        dbDelta($sql_reservas);
        dbDelta($sql_valoraciones);
    }

    // =========================================================
    // ACCIONES DEL MODULO
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_bares' => [
                'description' => 'Listar y filtrar bares por tipo, valoracion o ubicacion',
                'params'      => ['tipo', 'valoracion_minima', 'latitud', 'longitud', 'radio_km', 'limite', 'pagina'],
            ],
            'ver_bar' => [
                'description' => 'Ver detalle completo de un bar con carta y horario',
                'params'      => ['bar_id'],
            ],
            'buscar_bares' => [
                'description' => 'Buscar bares por nombre o descripcion',
                'params'      => ['busqueda', 'tipo', 'limite'],
            ],
            'ver_carta' => [
                'description' => 'Ver la carta/menu de un bar',
                'params'      => ['bar_id', 'categoria'],
            ],
            'reservar' => [
                'description' => 'Realizar una reserva en un bar',
                'params'      => ['bar_id', 'nombre_reserva', 'telefono', 'fecha', 'hora', 'comensales', 'notas'],
            ],
            'mis_reservas' => [
                'description' => 'Ver las reservas del usuario actual',
                'params'      => ['estado'],
            ],
            'cancelar_reserva' => [
                'description' => 'Cancelar una reserva existente',
                'params'      => ['reserva_id'],
            ],
            'valorar' => [
                'description' => 'Valorar un bar con puntuacion y comentario',
                'params'      => ['bar_id', 'puntuacion', 'comentario'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadisticas del dashboard de bares',
                'params'      => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($nombre_accion, $parametros) {
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error'   => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    /**
     * Accion: Listar bares con filtros
     */
    private function action_listar_bares($parametros) {
        global $wpdb;
        $tabla_bares = $wpdb->prefix . 'flavor_bares';

        $condiciones_where   = ["estado = 'activo'"];
        $valores_preparacion = [];

        // Filtro por tipo de establecimiento
        if (!empty($parametros['tipo'])) {
            $condiciones_where[]   = 'tipo = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['tipo']);
        }

        // Filtro por valoracion minima
        if (!empty($parametros['valoracion_minima'])) {
            $condiciones_where[]   = 'valoracion_media >= %f';
            $valores_preparacion[] = floatval($parametros['valoracion_minima']);
        }

        // Filtro por ubicacion (radio en km)
        $ordenar_por_distancia = false;
        if (!empty($parametros['latitud']) && !empty($parametros['longitud'])) {
            $latitud_usuario  = floatval($parametros['latitud']);
            $longitud_usuario = floatval($parametros['longitud']);
            $radio_km         = floatval($parametros['radio_km'] ?? 10);

            $condiciones_where[]   = 'latitud IS NOT NULL AND longitud IS NOT NULL';
            $condiciones_where[]   = "(6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) <= %f";
            $valores_preparacion[] = $latitud_usuario;
            $valores_preparacion[] = $longitud_usuario;
            $valores_preparacion[] = $latitud_usuario;
            $valores_preparacion[] = $radio_km;
            $ordenar_por_distancia = true;
        }

        $limite = absint($parametros['limite'] ?? $this->get_setting('limite_resultados', 12));
        $pagina = max(1, absint($parametros['pagina'] ?? 1));
        $offset = ($pagina - 1) * $limite;

        $sql_where = implode(' AND ', $condiciones_where);

        // Contar total
        $sql_total = "SELECT COUNT(*) FROM $tabla_bares WHERE $sql_where";
        if (!empty($valores_preparacion)) {
            $total_resultados = (int) $wpdb->get_var($wpdb->prepare($sql_total, ...$valores_preparacion));
        } else {
            $total_resultados = (int) $wpdb->get_var($sql_total);
        }

        // Ordenamiento
        $orden_sql = 'valoracion_media DESC, nombre ASC';
        if ($ordenar_por_distancia) {
            $orden_sql = sprintf(
                "(6371 * acos(cos(radians(%f)) * cos(radians(latitud)) * cos(radians(longitud) - radians(%f)) + sin(radians(%f)) * sin(radians(latitud)))) ASC",
                $latitud_usuario,
                $longitud_usuario,
                $latitud_usuario
            );
        }

        $sql = "SELECT * FROM $tabla_bares WHERE $sql_where ORDER BY $orden_sql LIMIT %d OFFSET %d";
        $valores_preparacion[] = $limite;
        $valores_preparacion[] = $offset;

        $bares = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparacion));

        return [
            'success'        => true,
            'total'          => $total_resultados,
            'pagina'         => $pagina,
            'paginas_totales'=> ceil($total_resultados / $limite),
            'bares'          => array_map(function ($registro_bar) {
                return $this->formatear_bar_resumen($registro_bar);
            }, $bares),
        ];
    }

    /**
     * Accion: Ver detalle completo de un bar
     */
    private function action_ver_bar($parametros) {
        global $wpdb;
        $tabla_bares        = $wpdb->prefix . 'flavor_bares';
        $tabla_carta        = $wpdb->prefix . 'flavor_bares_carta';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';

        $bar_id = absint($parametros['bar_id'] ?? 0);

        if (!$bar_id) {
            return [
                'success' => false,
                'error'   => __('ID de bar no valido.', 'flavor-chat-ia'),
            ];
        }

        $registro_bar = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_bares WHERE id = %d AND estado = 'activo'",
            $bar_id
        ));

        if (!$registro_bar) {
            return [
                'success' => false,
                'error'   => __('Bar no encontrado o no disponible.', 'flavor-chat-ia'),
            ];
        }

        // Obtener carta del bar
        $items_carta = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_carta WHERE bar_id = %d AND disponible = 1 ORDER BY categoria ASC, orden ASC, nombre ASC",
            $bar_id
        ));

        // Agrupar carta por categoria
        $carta_agrupada = [];
        foreach ($items_carta as $item_carta) {
            $nombre_categoria = $item_carta->categoria ?: __('Otros', 'flavor-chat-ia');
            if (!isset($carta_agrupada[$nombre_categoria])) {
                $carta_agrupada[$nombre_categoria] = [];
            }
            $carta_agrupada[$nombre_categoria][] = [
                'id'          => (int) $item_carta->id,
                'nombre'      => $item_carta->nombre,
                'descripcion' => $item_carta->descripcion,
                'precio'      => $this->format_price(floatval($item_carta->precio)),
                'precio_raw'  => floatval($item_carta->precio),
                'imagen'      => $item_carta->imagen,
                'alergenos'   => json_decode($item_carta->alergenos, true) ?: [],
                'es_destacado'=> (bool) $item_carta->es_destacado,
            ];
        }

        // Obtener ultimas valoraciones
        $valoraciones_recientes = $wpdb->get_results($wpdb->prepare(
            "SELECT v.*, u.display_name as nombre_usuario
             FROM $tabla_valoraciones v
             LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
             WHERE v.bar_id = %d
             ORDER BY v.created_at DESC
             LIMIT 5",
            $bar_id
        ));

        $lista_valoraciones = array_map(function ($valoracion) {
            return [
                'puntuacion'     => (int) $valoracion->puntuacion,
                'comentario'     => $valoracion->comentario,
                'nombre_usuario' => $valoracion->nombre_usuario ?: __('Anonimo', 'flavor-chat-ia'),
                'fecha'          => date_i18n('d/m/Y', strtotime($valoracion->created_at)),
            ];
        }, $valoraciones_recientes);

        return [
            'success' => true,
            'bar'     => [
                'id'                => (int) $registro_bar->id,
                'nombre'            => $registro_bar->nombre,
                'descripcion'       => $registro_bar->descripcion,
                'tipo'              => $registro_bar->tipo,
                'tipo_label'        => $this->obtener_etiqueta_tipo($registro_bar->tipo),
                'imagen'            => $registro_bar->imagen,
                'galeria'           => json_decode($registro_bar->galeria, true) ?: [],
                'direccion'         => $registro_bar->direccion,
                'coordenadas'       => ($registro_bar->latitud && $registro_bar->longitud) ? [
                    'latitud'  => floatval($registro_bar->latitud),
                    'longitud' => floatval($registro_bar->longitud),
                ] : null,
                'telefono'          => $registro_bar->telefono,
                'email'             => $registro_bar->email,
                'web'               => $registro_bar->web,
                'redes_sociales'    => json_decode($registro_bar->redes_sociales, true) ?: [],
                'horario'           => json_decode($registro_bar->horario, true) ?: [],
                'valoracion_media'  => floatval($registro_bar->valoracion_media),
                'valoraciones_count'=> (int) $registro_bar->valoraciones_count,
                'caracteristicas'   => json_decode($registro_bar->caracteristicas, true) ?: [],
                'estado'            => $registro_bar->estado,
                'carta'             => $carta_agrupada,
                'valoraciones'      => $lista_valoraciones,
            ],
        ];
    }

    /**
     * Accion: Buscar bares por texto
     */
    private function action_buscar_bares($parametros) {
        global $wpdb;
        $tabla_bares = $wpdb->prefix . 'flavor_bares';

        $termino_busqueda = sanitize_text_field($parametros['busqueda'] ?? '');

        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error'   => __('Debes indicar un termino de busqueda.', 'flavor-chat-ia'),
            ];
        }

        $condiciones_where   = ["estado = 'activo'"];
        $valores_preparacion = [];

        $patron_busqueda       = '%' . $wpdb->esc_like($termino_busqueda) . '%';
        $condiciones_where[]   = '(nombre LIKE %s OR descripcion LIKE %s OR direccion LIKE %s)';
        $valores_preparacion[] = $patron_busqueda;
        $valores_preparacion[] = $patron_busqueda;
        $valores_preparacion[] = $patron_busqueda;

        if (!empty($parametros['tipo'])) {
            $condiciones_where[]   = 'tipo = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['tipo']);
        }

        $limite = absint($parametros['limite'] ?? $this->get_setting('limite_resultados', 12));
        $sql_where = implode(' AND ', $condiciones_where);

        $sql = "SELECT * FROM $tabla_bares WHERE $sql_where ORDER BY valoracion_media DESC, nombre ASC LIMIT %d";
        $valores_preparacion[] = $limite;

        $bares = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparacion));

        return [
            'success' => true,
            'total'   => count($bares),
            'busqueda'=> $termino_busqueda,
            'bares'   => array_map(function ($registro_bar) {
                return $this->formatear_bar_resumen($registro_bar);
            }, $bares),
        ];
    }

    /**
     * Accion: Ver carta/menu de un bar
     */
    private function action_ver_carta($parametros) {
        global $wpdb;
        $tabla_bares = $wpdb->prefix . 'flavor_bares';
        $tabla_carta = $wpdb->prefix . 'flavor_bares_carta';

        $bar_id = absint($parametros['bar_id'] ?? 0);

        if (!$bar_id) {
            return [
                'success' => false,
                'error'   => __('ID de bar no valido.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el bar existe
        $nombre_bar = $wpdb->get_var($wpdb->prepare(
            "SELECT nombre FROM $tabla_bares WHERE id = %d AND estado = 'activo'",
            $bar_id
        ));

        if (!$nombre_bar) {
            return [
                'success' => false,
                'error'   => __('Bar no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $condiciones_where   = ['bar_id = %d', 'disponible = 1'];
        $valores_preparacion = [$bar_id];

        // Filtro por categoria de la carta
        if (!empty($parametros['categoria'])) {
            $condiciones_where[]   = 'categoria = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['categoria']);
        }

        $sql_where = implode(' AND ', $condiciones_where);
        $sql = "SELECT * FROM $tabla_carta WHERE $sql_where ORDER BY categoria ASC, orden ASC, nombre ASC";

        $items_carta = $wpdb->get_results($wpdb->prepare($sql, ...$valores_preparacion));

        // Agrupar por categoria
        $carta_agrupada = [];
        foreach ($items_carta as $item_carta) {
            $nombre_categoria = $item_carta->categoria ?: __('Otros', 'flavor-chat-ia');
            if (!isset($carta_agrupada[$nombre_categoria])) {
                $carta_agrupada[$nombre_categoria] = [];
            }
            $carta_agrupada[$nombre_categoria][] = [
                'id'          => (int) $item_carta->id,
                'nombre'      => $item_carta->nombre,
                'descripcion' => $item_carta->descripcion,
                'precio'      => $this->format_price(floatval($item_carta->precio)),
                'precio_raw'  => floatval($item_carta->precio),
                'imagen'      => $item_carta->imagen,
                'alergenos'   => json_decode($item_carta->alergenos, true) ?: [],
                'es_destacado'=> (bool) $item_carta->es_destacado,
            ];
        }

        // Obtener categorias disponibles
        $categorias_disponibles = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT categoria FROM $tabla_carta WHERE bar_id = %d AND disponible = 1 ORDER BY categoria ASC",
            $bar_id
        ));

        return [
            'success'    => true,
            'bar_nombre' => $nombre_bar,
            'categorias' => $categorias_disponibles,
            'total_items'=> count($items_carta),
            'carta'      => $carta_agrupada,
        ];
    }

    /**
     * Accion: Realizar una reserva
     */
    private function action_reservar($parametros) {
        if (!$this->get_setting('permitir_reservas', true)) {
            return [
                'success' => false,
                'error'   => __('Las reservas no estan habilitadas.', 'flavor-chat-ia'),
            ];
        }

        $usuario_id = get_current_user_id();

        if ($this->get_setting('requiere_login_reserva', true) && !$usuario_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para realizar una reserva.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_bares    = $wpdb->prefix . 'flavor_bares';
        $tabla_reservas = $wpdb->prefix . 'flavor_bares_reservas';

        $bar_id          = absint($parametros['bar_id'] ?? 0);
        $nombre_reserva  = sanitize_text_field($parametros['nombre_reserva'] ?? '');
        $telefono        = sanitize_text_field($parametros['telefono'] ?? '');
        $fecha_reserva   = sanitize_text_field($parametros['fecha'] ?? '');
        $hora_reserva    = sanitize_text_field($parametros['hora'] ?? '');
        $comensales      = absint($parametros['comensales'] ?? 2);
        $notas_reserva   = sanitize_textarea_field($parametros['notas'] ?? '');

        // Validaciones
        if (!$bar_id) {
            return ['success' => false, 'error' => __('Debes indicar el bar.', 'flavor-chat-ia')];
        }
        if (empty($nombre_reserva)) {
            return ['success' => false, 'error' => __('El nombre de la reserva es obligatorio.', 'flavor-chat-ia')];
        }
        if (empty($fecha_reserva)) {
            return ['success' => false, 'error' => __('La fecha es obligatoria.', 'flavor-chat-ia')];
        }
        if (empty($hora_reserva)) {
            return ['success' => false, 'error' => __('La hora es obligatoria.', 'flavor-chat-ia')];
        }
        if ($comensales < 1) {
            return ['success' => false, 'error' => __('El numero de comensales debe ser al menos 1.', 'flavor-chat-ia')];
        }

        // Validar fecha futura
        $timestamp_reserva = strtotime($fecha_reserva . ' ' . $hora_reserva);
        if ($timestamp_reserva <= time()) {
            return [
                'success' => false,
                'error'   => __('La fecha y hora de la reserva deben ser futuras.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el bar existe y esta activo
        $registro_bar = $wpdb->get_row($wpdb->prepare(
            "SELECT id, nombre FROM $tabla_bares WHERE id = %d AND estado = 'activo'",
            $bar_id
        ));

        if (!$registro_bar) {
            return [
                'success' => false,
                'error'   => __('Bar no encontrado o no disponible.', 'flavor-chat-ia'),
            ];
        }

        // Crear la reserva
        $resultado_insercion = $wpdb->insert(
            $tabla_reservas,
            [
                'bar_id'          => $bar_id,
                'user_id'         => $usuario_id ?: null,
                'nombre_reserva'  => $nombre_reserva,
                'telefono'        => $telefono,
                'fecha'           => $fecha_reserva,
                'hora'            => $hora_reserva,
                'comensales'      => $comensales,
                'notas'           => $notas_reserva,
                'estado'          => 'pendiente',
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear la reserva. Intentalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        $reserva_id = $wpdb->insert_id;

        return [
            'success'    => true,
            'reserva_id' => $reserva_id,
            'mensaje'    => sprintf(
                __('Reserva creada correctamente en "%s" para %d persona(s) el %s a las %s. Estado: pendiente de confirmacion.', 'flavor-chat-ia'),
                $registro_bar->nombre,
                $comensales,
                date_i18n('d/m/Y', strtotime($fecha_reserva)),
                $hora_reserva
            ),
        ];
    }

    /**
     * Accion: Ver reservas del usuario actual
     */
    private function action_mis_reservas($parametros) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para ver tus reservas.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_bares_reservas';
        $tabla_bares    = $wpdb->prefix . 'flavor_bares';

        $condiciones_where   = ['r.user_id = %d'];
        $valores_preparacion = [$usuario_id];

        if (!empty($parametros['estado'])) {
            $condiciones_where[]   = 'r.estado = %s';
            $valores_preparacion[] = sanitize_text_field($parametros['estado']);
        }

        $sql_where = implode(' AND ', $condiciones_where);

        $reservas_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, b.nombre as bar_nombre, b.direccion as bar_direccion, b.telefono as bar_telefono
             FROM $tabla_reservas r
             LEFT JOIN $tabla_bares b ON r.bar_id = b.id
             WHERE $sql_where
             ORDER BY r.fecha DESC, r.hora DESC",
            ...$valores_preparacion
        ));

        $lista_reservas = array_map(function ($reserva) {
            return [
                'id'              => (int) $reserva->id,
                'bar_nombre'      => $reserva->bar_nombre,
                'bar_direccion'   => $reserva->bar_direccion,
                'bar_telefono'    => $reserva->bar_telefono,
                'nombre_reserva'  => $reserva->nombre_reserva,
                'fecha'           => date_i18n('d/m/Y', strtotime($reserva->fecha)),
                'hora'            => substr($reserva->hora, 0, 5),
                'comensales'      => (int) $reserva->comensales,
                'estado'          => $reserva->estado,
                'estado_label'    => $this->obtener_etiqueta_estado_reserva($reserva->estado),
                'notas'           => $reserva->notas,
                'creada'          => date_i18n('d/m/Y H:i', strtotime($reserva->created_at)),
            ];
        }, $reservas_usuario);

        return [
            'success'  => true,
            'total'    => count($lista_reservas),
            'reservas' => $lista_reservas,
        ];
    }

    /**
     * Accion: Cancelar una reserva
     */
    private function action_cancelar_reserva($parametros) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para cancelar una reserva.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_reservas = $wpdb->prefix . 'flavor_bares_reservas';

        $reserva_id = absint($parametros['reserva_id'] ?? 0);

        if (!$reserva_id) {
            return [
                'success' => false,
                'error'   => __('ID de reserva no valido.', 'flavor-chat-ia'),
            ];
        }

        // Verificar propiedad y estado
        $reserva_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_reservas WHERE id = %d AND user_id = %d",
            $reserva_id,
            $usuario_id
        ));

        if (!$reserva_existente) {
            return [
                'success' => false,
                'error'   => __('Reserva no encontrada o no te pertenece.', 'flavor-chat-ia'),
            ];
        }

        if (in_array($reserva_existente->estado, ['cancelada', 'completada', 'no_show'], true)) {
            return [
                'success' => false,
                'error'   => sprintf(
                    __('No se puede cancelar una reserva con estado "%s".', 'flavor-chat-ia'),
                    $this->obtener_etiqueta_estado_reserva($reserva_existente->estado)
                ),
            ];
        }

        $resultado_actualizacion = $wpdb->update(
            $tabla_reservas,
            ['estado' => 'cancelada'],
            ['id' => $reserva_id],
            ['%s'],
            ['%d']
        );

        if ($resultado_actualizacion === false) {
            return [
                'success' => false,
                'error'   => __('Error al cancelar la reserva.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => __('Reserva cancelada correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Accion: Valorar un bar
     */
    private function action_valorar($parametros) {
        if (!$this->get_setting('permitir_valoraciones', true)) {
            return [
                'success' => false,
                'error'   => __('Las valoraciones no estan habilitadas.', 'flavor-chat-ia'),
            ];
        }

        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error'   => __('Debes iniciar sesion para valorar un bar.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_bares        = $wpdb->prefix . 'flavor_bares';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';

        $bar_id             = absint($parametros['bar_id'] ?? 0);
        $puntuacion_dada    = absint($parametros['puntuacion'] ?? 0);
        $comentario_usuario = sanitize_textarea_field($parametros['comentario'] ?? '');

        if (!$bar_id) {
            return ['success' => false, 'error' => __('ID de bar no valido.', 'flavor-chat-ia')];
        }
        if ($puntuacion_dada < 1 || $puntuacion_dada > 5) {
            return ['success' => false, 'error' => __('La puntuacion debe estar entre 1 y 5.', 'flavor-chat-ia')];
        }

        // Verificar que el bar existe
        $existe_bar = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_bares WHERE id = %d AND estado = 'activo'",
            $bar_id
        ));

        if (!$existe_bar) {
            return [
                'success' => false,
                'error'   => __('Bar no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Verificar si ya ha valorado (UNIQUE KEY previene duplicados, pero manejamos con REPLACE)
        $valoracion_previa = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_valoraciones WHERE bar_id = %d AND user_id = %d",
            $bar_id,
            $usuario_id
        ));

        if ($valoracion_previa) {
            // Actualizar valoracion existente
            $wpdb->update(
                $tabla_valoraciones,
                [
                    'puntuacion' => $puntuacion_dada,
                    'comentario' => $comentario_usuario,
                    'created_at' => current_time('mysql'),
                ],
                [
                    'bar_id'  => $bar_id,
                    'user_id' => $usuario_id,
                ],
                ['%d', '%s', '%s'],
                ['%d', '%d']
            );
            $mensaje_resultado = __('Valoracion actualizada correctamente.', 'flavor-chat-ia');
        } else {
            // Insertar nueva valoracion
            $wpdb->insert(
                $tabla_valoraciones,
                [
                    'bar_id'     => $bar_id,
                    'user_id'    => $usuario_id,
                    'puntuacion' => $puntuacion_dada,
                    'comentario' => $comentario_usuario,
                ],
                ['%d', '%d', '%d', '%s']
            );
            $mensaje_resultado = __('Valoracion enviada correctamente.', 'flavor-chat-ia');
        }

        // Recalcular media del bar
        $estadisticas_valoracion = $wpdb->get_row($wpdb->prepare(
            "SELECT AVG(puntuacion) as media, COUNT(*) as total FROM $tabla_valoraciones WHERE bar_id = %d",
            $bar_id
        ));

        $wpdb->update(
            $tabla_bares,
            [
                'valoracion_media'   => round(floatval($estadisticas_valoracion->media), 2),
                'valoraciones_count' => (int) $estadisticas_valoracion->total,
            ],
            ['id' => $bar_id],
            ['%f', '%d'],
            ['%d']
        );

        return [
            'success'           => true,
            'mensaje'           => $mensaje_resultado,
            'valoracion_media'  => round(floatval($estadisticas_valoracion->media), 2),
            'valoraciones_count'=> (int) $estadisticas_valoracion->total,
        ];
    }

    /**
     * Accion: Estadisticas del dashboard
     */
    private function action_estadisticas($parametros) {
        global $wpdb;
        $tabla_bares        = $wpdb->prefix . 'flavor_bares';
        $tabla_carta        = $wpdb->prefix . 'flavor_bares_carta';
        $tabla_reservas     = $wpdb->prefix . 'flavor_bares_reservas';
        $tabla_valoraciones = $wpdb->prefix . 'flavor_bares_valoraciones';

        $total_bares_activos = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_bares WHERE estado = 'activo'"
        );

        $total_items_carta = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_carta WHERE disponible = 1"
        );

        $reservas_pendientes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE estado = 'pendiente'"
        );

        $reservas_este_mes = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_reservas WHERE MONTH(fecha) = MONTH(NOW()) AND YEAR(fecha) = YEAR(NOW())"
        );

        $total_valoraciones = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_valoraciones"
        );

        $valoracion_media_global = floatval($wpdb->get_var(
            "SELECT AVG(valoracion_media) FROM $tabla_bares WHERE estado = 'activo' AND valoraciones_count > 0"
        ) ?: 0);

        // Distribucion por tipo
        $distribucion_por_tipo = $wpdb->get_results(
            "SELECT tipo, COUNT(*) as cantidad FROM $tabla_bares WHERE estado = 'activo' GROUP BY tipo ORDER BY cantidad DESC"
        );

        // Top bares mejor valorados
        $top_bares_valorados = $wpdb->get_results(
            "SELECT id, nombre, tipo, valoracion_media, valoraciones_count
             FROM $tabla_bares
             WHERE estado = 'activo' AND valoraciones_count >= 1
             ORDER BY valoracion_media DESC, valoraciones_count DESC
             LIMIT 5"
        );

        return [
            'success'      => true,
            'estadisticas' => [
                'total_bares'            => $total_bares_activos,
                'total_items_carta'      => $total_items_carta,
                'reservas_pendientes'    => $reservas_pendientes,
                'reservas_este_mes'      => $reservas_este_mes,
                'total_valoraciones'     => $total_valoraciones,
                'valoracion_media_global'=> round($valoracion_media_global, 2),
                'distribucion_tipo'      => array_map(function ($fila) {
                    return [
                        'tipo'     => $fila->tipo,
                        'label'    => $this->obtener_etiqueta_tipo($fila->tipo),
                        'cantidad' => (int) $fila->cantidad,
                    ];
                }, $distribucion_por_tipo),
                'top_bares' => array_map(function ($bar) {
                    return [
                        'id'                => (int) $bar->id,
                        'nombre'            => $bar->nombre,
                        'tipo'              => $bar->tipo,
                        'valoracion_media'  => floatval($bar->valoracion_media),
                        'valoraciones_count'=> (int) $bar->valoraciones_count,
                    ];
                }, $top_bares_valorados),
            ],
        ];
    }

    // =========================================================
    // AI TOOLS
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name'         => 'bares_listar',
                'description'  => 'Lista los bares y restaurantes disponibles con filtros opcionales por tipo, valoracion y ubicacion',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Tipo de establecimiento: bar, restaurante, cafeteria, pub, terraza, cocteleria',
                            'enum'        => ['bar', 'restaurante', 'cafeteria', 'pub', 'terraza', 'cocteleria'],
                        ],
                        'valoracion_minima' => [
                            'type'        => 'number',
                            'description' => 'Valoracion minima (1-5)',
                        ],
                        'limite' => [
                            'type'        => 'integer',
                            'description' => 'Numero maximo de resultados',
                            'default'     => 12,
                        ],
                    ],
                ],
            ],
            [
                'name'         => 'bares_buscar',
                'description'  => 'Busca bares y restaurantes por nombre, descripcion o ubicacion',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type'        => 'string',
                            'description' => 'Termino de busqueda (nombre, tipo de comida, zona...)',
                        ],
                        'tipo' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por tipo de establecimiento',
                            'enum'        => ['bar', 'restaurante', 'cafeteria', 'pub', 'terraza', 'cocteleria'],
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name'         => 'bares_reservar',
                'description'  => 'Realiza una reserva en un bar o restaurante',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'bar_id' => [
                            'type'        => 'integer',
                            'description' => 'ID del bar o restaurante',
                        ],
                        'nombre_reserva' => [
                            'type'        => 'string',
                            'description' => 'Nombre para la reserva',
                        ],
                        'fecha' => [
                            'type'        => 'string',
                            'description' => 'Fecha de la reserva (YYYY-MM-DD)',
                        ],
                        'hora' => [
                            'type'        => 'string',
                            'description' => 'Hora de la reserva (HH:MM)',
                        ],
                        'comensales' => [
                            'type'        => 'integer',
                            'description' => 'Numero de comensales',
                            'default'     => 2,
                        ],
                    ],
                    'required' => ['bar_id', 'nombre_reserva', 'fecha', 'hora'],
                ],
            ],
            [
                'name'         => 'bares_ver_carta',
                'description'  => 'Muestra la carta o menu de un bar o restaurante',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'bar_id' => [
                            'type'        => 'integer',
                            'description' => 'ID del bar o restaurante',
                        ],
                        'categoria' => [
                            'type'        => 'string',
                            'description' => 'Filtrar por categoria (tapas, raciones, bebidas, postres...)',
                        ],
                    ],
                    'required' => ['bar_id'],
                ],
            ],
        ];
    }

    // =========================================================
    // WEB COMPONENTS
    // =========================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'bares_hero' => [
                'label'       => __('Hero Bares', 'flavor-chat-ia'),
                'description' => __('Seccion hero con titulo y buscador de bares y restaurantes', 'flavor-chat-ia'),
                'category'    => 'hero',
                'icon'        => 'dashicons-food',
                'fields'      => [
                    'titulo' => [
                        'type'    => 'text',
                        'label'   => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Bares y Restaurantes', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type'    => 'textarea',
                        'label'   => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('Descubre los mejores locales de hosteleria de tu zona', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type'  => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                    ],
                ],
                'template' => 'bares/hero',
            ],
            'bares_grid' => [
                'label'       => __('Grid de Bares', 'flavor-chat-ia'),
                'description' => __('Listado de bares en tarjetas con filtros', 'flavor-chat-ia'),
                'category'    => 'listings',
                'icon'        => 'dashicons-grid-view',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de seccion', 'flavor-chat-ia'),
                        'default' => __('Establecimientos Destacados', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type'    => 'select',
                        'label'   => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3, 4],
                        'default' => 3,
                    ],
                    'tipo_filtro' => [
                        'type'    => 'select',
                        'label'   => __('Filtrar por tipo', 'flavor-chat-ia'),
                        'options' => ['todos', 'bar', 'restaurante', 'cafeteria', 'pub', 'terraza', 'cocteleria'],
                        'default' => 'todos',
                    ],
                    'mostrar_valoracion' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar valoraciones', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'bares/bares-grid',
            ],
            'bares_carta' => [
                'label'       => __('Carta / Menu', 'flavor-chat-ia'),
                'description' => __('Carta completa de un establecimiento con categorias y precios', 'flavor-chat-ia'),
                'category'    => 'content',
                'icon'        => 'dashicons-list-view',
                'fields'      => [
                    'titulo_seccion' => [
                        'type'    => 'text',
                        'label'   => __('Titulo de seccion', 'flavor-chat-ia'),
                        'default' => __('Nuestra Carta', 'flavor-chat-ia'),
                    ],
                    'mostrar_precios' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar precios', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                    'mostrar_alergenos' => [
                        'type'    => 'toggle',
                        'label'   => __('Mostrar alergenos', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'bares/carta',
            ],
        ];
    }

    // =========================================================
    // KNOWLEDGE BASE Y FAQs
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Sistema de Bares y Hosteleria**

Directorio completo de bares, restaurantes y locales de hosteleria con gestion de cartas, reservas y valoraciones.

**Funcionalidades:**
- Directorio de establecimientos (bar, restaurante, cafeteria, pub, terraza, cocteleria)
- Carta/menu completo con categorias, precios y alergenos
- Sistema de reservas online
- Valoraciones y resenas de usuarios
- Busqueda por nombre, tipo y ubicacion
- Horarios de apertura por dia de la semana
- Galeria de imagenes
- Informacion de contacto y redes sociales
- Caracteristicas del local (terraza, wifi, accesible, mascotas)
- Estadisticas de uso

**Tipos de establecimiento:**
- Bar: establecimiento de bebidas y tapas
- Restaurante: establecimiento de comidas completas
- Cafeteria: cafe, desayunos y meriendas
- Pub: bar nocturno con musica
- Terraza: establecimiento al aire libre
- Cocteleria: bar de cocktails

**Comandos disponibles:**
- "buscar bares [termino]": busca establecimientos
- "ver bar [ID]": muestra detalles del bar
- "carta del bar [ID]": muestra el menu
- "reservar en [bar]": realiza una reserva
- "mis reservas": muestra las reservas del usuario
- "cancelar reserva [ID]": cancela una reserva
- "valorar bar [ID]": deja una valoracion
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta'  => 'Como puedo reservar mesa en un bar o restaurante?',
                'respuesta' => 'Busca el establecimiento, seleccionalo y usa la opcion de reservar indicando fecha, hora y numero de comensales.',
            ],
            [
                'pregunta'  => 'Puedo cancelar una reserva?',
                'respuesta' => 'Si, puedes cancelar reservas que esten en estado pendiente o confirmada desde la seccion "Mis reservas".',
            ],
            [
                'pregunta'  => 'Como valoro un bar?',
                'respuesta' => 'Entra en el detalle del bar y dejale una puntuacion del 1 al 5 con un comentario opcional. Solo puedes valorar una vez por bar.',
            ],
            [
                'pregunta'  => 'Puedo ver la carta antes de ir?',
                'respuesta' => 'Si, cada bar tiene su carta publicada con categorias, precios y alergenos. Usa "ver carta" para consultarla.',
            ],
            [
                'pregunta'  => 'Que tipos de establecimientos hay?',
                'respuesta' => 'Hay bares, restaurantes, cafeterias, pubs, terrazas y coctelerias. Puedes filtrar por tipo en la busqueda.',
            ],
        ];
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Formatea un bar como resumen para listados
     *
     * @param object $registro_bar Fila de la base de datos
     * @return array
     */
    private function formatear_bar_resumen($registro_bar) {
        return [
            'id'                => (int) $registro_bar->id,
            'nombre'            => $registro_bar->nombre,
            'tipo'              => $registro_bar->tipo,
            'tipo_label'        => $this->obtener_etiqueta_tipo($registro_bar->tipo),
            'imagen'            => $registro_bar->imagen,
            'direccion'         => $registro_bar->direccion,
            'telefono'          => $registro_bar->telefono,
            'valoracion_media'  => floatval($registro_bar->valoracion_media),
            'valoraciones_count'=> (int) $registro_bar->valoraciones_count,
            'caracteristicas'   => json_decode($registro_bar->caracteristicas, true) ?: [],
            'estado'            => $registro_bar->estado,
            'extracto'          => wp_trim_words($registro_bar->descripcion, 20),
        ];
    }

    /**
     * Obtiene la etiqueta traducida de un tipo de establecimiento
     *
     * @param string $tipo_establecimiento
     * @return string
     */
    private function obtener_etiqueta_tipo($tipo_establecimiento) {
        $tipos_disponibles = $this->get_setting('tipos_establecimiento', []);
        return $tipos_disponibles[$tipo_establecimiento] ?? ucfirst($tipo_establecimiento);
    }

    /**
     * Obtiene la etiqueta traducida de un estado de reserva
     *
     * @param string $estado_reserva
     * @return string
     */
    private function obtener_etiqueta_estado_reserva($estado_reserva) {
        $mapa_estados = [
            'pendiente'  => __('Pendiente', 'flavor-chat-ia'),
            'confirmada' => __('Confirmada', 'flavor-chat-ia'),
            'cancelada'  => __('Cancelada', 'flavor-chat-ia'),
            'completada' => __('Completada', 'flavor-chat-ia'),
            'no_show'    => __('No presentado', 'flavor-chat-ia'),
        ];
        return $mapa_estados[$estado_reserva] ?? ucfirst($estado_reserva);
    }

    /**
     * Obtiene el icono para un tipo de establecimiento
     *
     * @param string $tipo_establecimiento
     * @return string
     */
    private function obtener_icono_tipo($tipo_establecimiento) {
        $mapa_iconos = [
            'bar'         => 'local_bar',
            'restaurante' => 'restaurant',
            'cafeteria'   => 'local_cafe',
            'pub'         => 'nightlife',
            'terraza'     => 'deck',
            'cocteleria'  => 'wine_bar',
        ];
        return $mapa_iconos[$tipo_establecimiento] ?? 'restaurant';
    }

    /**
     * Obtiene el color para un tipo de establecimiento
     *
     * @param string $tipo_establecimiento
     * @return string
     */
    private function obtener_color_tipo($tipo_establecimiento) {
        $mapa_colores = [
            'bar'         => '#F59E0B',
            'restaurante' => '#EF4444',
            'cafeteria'   => '#8B5CF6',
            'pub'         => '#3B82F6',
            'terraza'     => '#10B981',
            'cocteleria'  => '#EC4899',
        ];
        return $mapa_colores[$tipo_establecimiento] ?? '#6B7280';
    }
}
