<?php
/**
 * Modulo de Foros de Discusion para Chat IA
 *
 * Sistema completo de foros comunitarios con categorias, hilos y respuestas.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Foros - Sistema de foros comunitarios
 */
class Flavor_Chat_Foros_Module extends Flavor_Chat_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'foros';
        $this->name = __('Foros de Discusion', 'flavor-chat-ia');
        $this->description = __('Sistema de foros comunitarios con categorias, hilos y respuestas', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        return Flavor_Chat_Helpers::tabla_existe($tabla_foros);
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Las tablas de Foros no estan creadas. Se crearan automaticamente al activar.', 'flavor-chat-ia');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'hilos_por_pagina' => 20,
            'respuestas_por_pagina' => 25,
            'permitir_respuestas_anidadas' => true,
            'profundidad_maxima_anidamiento' => 3,
            'requiere_moderacion' => false,
            'permitir_votos' => true,
            'permitir_marcar_solucion' => true,
            'notificar_respuestas' => true,
            'minimo_caracteres_contenido' => 10,
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
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el sistema de foros
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $sql_foros = "CREATE TABLE IF NOT EXISTS $tabla_foros (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            descripcion text DEFAULT NULL,
            icono varchar(100) DEFAULT 'forum',
            orden int(11) DEFAULT 0,
            estado enum('activo','cerrado','archivado') DEFAULT 'activo',
            moderadores text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY orden (orden)
        ) $charset_collate;";

        $sql_hilos = "CREATE TABLE IF NOT EXISTS $tabla_hilos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            foro_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            titulo varchar(255) NOT NULL,
            contenido longtext NOT NULL,
            estado enum('abierto','cerrado','fijado','eliminado') DEFAULT 'abierto',
            es_fijado tinyint(1) DEFAULT 0,
            es_destacado tinyint(1) DEFAULT 0,
            vistas int(11) DEFAULT 0,
            respuestas_count int(11) DEFAULT 0,
            ultima_actividad datetime DEFAULT CURRENT_TIMESTAMP,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY foro_id (foro_id),
            KEY autor_id (autor_id),
            KEY estado (estado),
            KEY es_fijado (es_fijado),
            KEY ultima_actividad (ultima_actividad)
        ) $charset_collate;";

        $sql_respuestas = "CREATE TABLE IF NOT EXISTS $tabla_respuestas (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            hilo_id bigint(20) unsigned NOT NULL,
            autor_id bigint(20) unsigned NOT NULL,
            contenido longtext NOT NULL,
            parent_id bigint(20) unsigned DEFAULT 0,
            es_solucion tinyint(1) DEFAULT 0,
            votos int(11) DEFAULT 0,
            estado enum('visible','oculto','eliminado') DEFAULT 'visible',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY hilo_id (hilo_id),
            KEY autor_id (autor_id),
            KEY parent_id (parent_id),
            KEY estado (estado),
            KEY es_solucion (es_solucion)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_foros);
        dbDelta($sql_hilos);
        dbDelta($sql_respuestas);
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'listar_foros' => [
                'description' => 'Listar todas las categorias de foros disponibles',
                'params' => [],
            ],
            'ver_foro' => [
                'description' => 'Ver los hilos de un foro especifico',
                'params' => ['foro_id', 'pagina', 'orden'],
            ],
            'crear_hilo' => [
                'description' => 'Crear un nuevo hilo de discusion (requiere login)',
                'params' => ['foro_id', 'titulo', 'contenido'],
            ],
            'ver_hilo' => [
                'description' => 'Ver un hilo con sus respuestas',
                'params' => ['hilo_id', 'pagina'],
            ],
            'responder' => [
                'description' => 'Responder a un hilo de discusion (requiere login)',
                'params' => ['hilo_id', 'contenido', 'parent_id'],
            ],
            'buscar' => [
                'description' => 'Buscar hilos por titulo o contenido',
                'params' => ['busqueda', 'foro_id', 'limite'],
            ],
            'mis_hilos' => [
                'description' => 'Ver los hilos creados por el usuario actual',
                'params' => ['pagina'],
            ],
            'moderar' => [
                'description' => 'Acciones de moderacion sobre hilos y respuestas',
                'params' => ['accion_moderacion', 'tipo', 'id_elemento'],
            ],
            'listar_temas' => [
                'description' => 'Listar temas del foro (alias de listar_foros)',
                'params' => [],
            ],
            'crear_tema' => [
                'description' => 'Crear un nuevo tema de discusion (alias de crear_hilo)',
                'params' => ['categoria_id', 'titulo', 'contenido', 'etiquetas'],
            ],
            'responder_tema' => [
                'description' => 'Responder a un tema (alias de responder)',
                'params' => ['tema_id', 'contenido'],
            ],
            'editar_mensaje' => [
                'description' => 'Editar un mensaje existente',
                'params' => ['mensaje_id', 'contenido', 'motivo_edicion'],
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
            'error' => sprintf(__('Accion no implementada: %s', 'flavor-chat-ia'), $nombre_accion),
        ];
    }

    // =========================================================
    // Acciones del modulo
    // =========================================================

    /**
     * Accion: Listar foros (categorias)
     */
    private function action_listar_foros($parametros) {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $foros = $wpdb->get_results(
            "SELECT f.*,
                    COALESCE(COUNT(DISTINCT h.id), 0) AS total_hilos,
                    COALESCE(SUM(h.respuestas_count), 0) AS total_respuestas
             FROM $tabla_foros f
             LEFT JOIN $tabla_hilos h ON h.foro_id = f.id AND h.estado != 'eliminado'
             WHERE f.estado = 'activo'
             GROUP BY f.id
             ORDER BY f.orden ASC, f.nombre ASC"
        );

        return [
            'success' => true,
            'total' => count($foros),
            'foros' => array_map(function($foro) {
                return [
                    'id' => intval($foro->id),
                    'nombre' => $foro->nombre,
                    'descripcion' => $foro->descripcion,
                    'icono' => $foro->icono,
                    'total_hilos' => intval($foro->total_hilos),
                    'total_respuestas' => intval($foro->total_respuestas),
                    'estado' => $foro->estado,
                ];
            }, $foros),
        ];
    }

    /**
     * Accion: Ver hilos de un foro
     */
    private function action_ver_foro($parametros) {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        $foro_id = absint($parametros['foro_id'] ?? 0);
        if (!$foro_id) {
            return [
                'success' => false,
                'error' => __('ID de foro no valido.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el foro existe y esta activo
        $foro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_foros WHERE id = %d AND estado = 'activo'",
            $foro_id
        ));

        if (!$foro) {
            return [
                'success' => false,
                'error' => __('Foro no encontrado o no disponible.', 'flavor-chat-ia'),
            ];
        }

        $pagina_actual = max(1, absint($parametros['pagina'] ?? 1));
        $hilos_por_pagina = absint($this->get_setting('hilos_por_pagina', 20));
        $desplazamiento = ($pagina_actual - 1) * $hilos_por_pagina;

        $orden_campo = 'ultima_actividad';
        $orden_direccion = 'DESC';
        if (!empty($parametros['orden'])) {
            switch ($parametros['orden']) {
                case 'recientes':
                    $orden_campo = 'created_at';
                    $orden_direccion = 'DESC';
                    break;
                case 'mas_vistos':
                    $orden_campo = 'vistas';
                    $orden_direccion = 'DESC';
                    break;
                case 'mas_respuestas':
                    $orden_campo = 'respuestas_count';
                    $orden_direccion = 'DESC';
                    break;
                default:
                    $orden_campo = 'ultima_actividad';
                    $orden_direccion = 'DESC';
            }
        }

        $total_hilos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_hilos WHERE foro_id = %d AND estado != 'eliminado'",
            $foro_id
        ));

        $hilos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_hilos
             WHERE foro_id = %d AND estado != 'eliminado'
             ORDER BY es_fijado DESC, $orden_campo $orden_direccion
             LIMIT %d OFFSET %d",
            $foro_id,
            $hilos_por_pagina,
            $desplazamiento
        ));

        return [
            'success' => true,
            'foro' => [
                'id' => intval($foro->id),
                'nombre' => $foro->nombre,
                'descripcion' => $foro->descripcion,
            ],
            'total' => intval($total_hilos),
            'pagina' => $pagina_actual,
            'total_paginas' => ceil($total_hilos / $hilos_por_pagina),
            'hilos' => array_map(function($hilo) {
                return $this->formatear_hilo_resumen($hilo);
            }, $hilos),
        ];
    }

    /**
     * Accion: Crear nuevo hilo
     */
    private function action_crear_hilo($parametros) {
        $usuario_id_actual = get_current_user_id();

        if (!$usuario_id_actual) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para crear un hilo.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        $foro_id = absint($parametros['foro_id'] ?? 0);
        $titulo_hilo = sanitize_text_field($parametros['titulo'] ?? '');
        $contenido_hilo = sanitize_textarea_field($parametros['contenido'] ?? '');

        if (!$foro_id) {
            return [
                'success' => false,
                'error' => __('Debes seleccionar un foro.', 'flavor-chat-ia'),
            ];
        }

        if (empty($titulo_hilo)) {
            return [
                'success' => false,
                'error' => __('El titulo es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        $longitud_minima_contenido = absint($this->get_setting('minimo_caracteres_contenido', 10));
        if (strlen($contenido_hilo) < $longitud_minima_contenido) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('El contenido debe tener al menos %d caracteres.', 'flavor-chat-ia'),
                    $longitud_minima_contenido
                ),
            ];
        }

        // Verificar que el foro existe y esta activo
        $foro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_foros WHERE id = %d AND estado = 'activo'",
            $foro_id
        ));

        if (!$foro) {
            return [
                'success' => false,
                'error' => __('El foro seleccionado no existe o esta cerrado.', 'flavor-chat-ia'),
            ];
        }

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_hilos,
            [
                'foro_id' => $foro_id,
                'autor_id' => $usuario_id_actual,
                'titulo' => $titulo_hilo,
                'contenido' => $contenido_hilo,
                'estado' => 'abierto',
                'ultima_actividad' => $fecha_actual,
                'created_at' => $fecha_actual,
                'updated_at' => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al crear el hilo. Intentalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        $hilo_id_nuevo = $wpdb->insert_id;

        return [
            'success' => true,
            'hilo_id' => $hilo_id_nuevo,
            'mensaje' => sprintf(
                __('Hilo "%s" creado correctamente en el foro "%s".', 'flavor-chat-ia'),
                $titulo_hilo,
                $foro->nombre
            ),
        ];
    }

    /**
     * Accion: Ver un hilo con sus respuestas
     */
    private function action_ver_hilo($parametros) {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $hilo_id = absint($parametros['hilo_id'] ?? 0);
        if (!$hilo_id) {
            return [
                'success' => false,
                'error' => __('ID de hilo no valido.', 'flavor-chat-ia'),
            ];
        }

        $hilo = $wpdb->get_row($wpdb->prepare(
            "SELECT h.*, f.nombre AS nombre_foro
             FROM $tabla_hilos h
             LEFT JOIN $tabla_foros f ON f.id = h.foro_id
             WHERE h.id = %d AND h.estado != 'eliminado'",
            $hilo_id
        ));

        if (!$hilo) {
            return [
                'success' => false,
                'error' => __('Hilo no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Incrementar contador de vistas
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_hilos SET vistas = vistas + 1 WHERE id = %d",
            $hilo_id
        ));

        // Obtener respuestas paginadas
        $pagina_actual = max(1, absint($parametros['pagina'] ?? 1));
        $respuestas_por_pagina = absint($this->get_setting('respuestas_por_pagina', 25));
        $desplazamiento = ($pagina_actual - 1) * $respuestas_por_pagina;

        $total_respuestas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_respuestas WHERE hilo_id = %d AND estado = 'visible'",
            $hilo_id
        ));

        $respuestas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_respuestas
             WHERE hilo_id = %d AND estado = 'visible'
             ORDER BY es_solucion DESC, created_at ASC
             LIMIT %d OFFSET %d",
            $hilo_id,
            $respuestas_por_pagina,
            $desplazamiento
        ));

        $datos_autor_hilo = get_user_by('ID', $hilo->autor_id);

        return [
            'success' => true,
            'hilo' => [
                'id' => intval($hilo->id),
                'foro_id' => intval($hilo->foro_id),
                'nombre_foro' => $hilo->nombre_foro,
                'titulo' => $hilo->titulo,
                'contenido' => $hilo->contenido,
                'autor' => $datos_autor_hilo ? [
                    'id' => $datos_autor_hilo->ID,
                    'nombre' => $datos_autor_hilo->display_name,
                    'avatar' => get_avatar_url($datos_autor_hilo->ID, ['size' => 96]),
                ] : null,
                'estado' => $hilo->estado,
                'es_fijado' => (bool) $hilo->es_fijado,
                'es_destacado' => (bool) $hilo->es_destacado,
                'vistas' => intval($hilo->vistas) + 1,
                'respuestas_count' => intval($hilo->respuestas_count),
                'fecha_creacion' => $hilo->created_at,
                'ultima_actividad' => $hilo->ultima_actividad,
            ],
            'total_respuestas' => intval($total_respuestas),
            'pagina' => $pagina_actual,
            'total_paginas' => ceil($total_respuestas / $respuestas_por_pagina),
            'respuestas' => array_map(function($respuesta) {
                return $this->formatear_respuesta($respuesta);
            }, $respuestas),
        ];
    }

    /**
     * Accion: Responder a un hilo
     */
    private function action_responder($parametros) {
        $usuario_id_actual = get_current_user_id();

        if (!$usuario_id_actual) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para responder.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $hilo_id = absint($parametros['hilo_id'] ?? 0);
        $contenido_respuesta = sanitize_textarea_field($parametros['contenido'] ?? '');
        $parent_id_respuesta = absint($parametros['parent_id'] ?? 0);

        if (!$hilo_id) {
            return [
                'success' => false,
                'error' => __('ID de hilo no valido.', 'flavor-chat-ia'),
            ];
        }

        $longitud_minima_contenido = absint($this->get_setting('minimo_caracteres_contenido', 10));
        if (strlen($contenido_respuesta) < $longitud_minima_contenido) {
            return [
                'success' => false,
                'error' => sprintf(
                    __('La respuesta debe tener al menos %d caracteres.', 'flavor-chat-ia'),
                    $longitud_minima_contenido
                ),
            ];
        }

        // Verificar que el hilo existe y esta abierto
        $hilo = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_hilos WHERE id = %d AND estado IN ('abierto', 'fijado')",
            $hilo_id
        ));

        if (!$hilo) {
            return [
                'success' => false,
                'error' => __('El hilo no existe o esta cerrado para respuestas.', 'flavor-chat-ia'),
            ];
        }

        // Validar parent_id si es respuesta anidada
        if ($parent_id_respuesta > 0) {
            $respuesta_padre_existe = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_respuestas WHERE id = %d AND hilo_id = %d AND estado = 'visible'",
                $parent_id_respuesta,
                $hilo_id
            ));

            if (!$respuesta_padre_existe) {
                return [
                    'success' => false,
                    'error' => __('La respuesta padre no existe.', 'flavor-chat-ia'),
                ];
            }
        }

        $fecha_actual = current_time('mysql');

        $resultado_insercion = $wpdb->insert(
            $tabla_respuestas,
            [
                'hilo_id' => $hilo_id,
                'autor_id' => $usuario_id_actual,
                'contenido' => $contenido_respuesta,
                'parent_id' => $parent_id_respuesta,
                'created_at' => $fecha_actual,
                'updated_at' => $fecha_actual,
            ],
            ['%d', '%d', '%s', '%d', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error' => __('Error al publicar la respuesta.', 'flavor-chat-ia'),
            ];
        }

        // Actualizar contador de respuestas y ultima actividad del hilo
        $wpdb->query($wpdb->prepare(
            "UPDATE $tabla_hilos
             SET respuestas_count = respuestas_count + 1,
                 ultima_actividad = %s,
                 updated_at = %s
             WHERE id = %d",
            $fecha_actual,
            $fecha_actual,
            $hilo_id
        ));

        return [
            'success' => true,
            'respuesta_id' => $wpdb->insert_id,
            'mensaje' => sprintf(
                __('Respuesta publicada en el hilo "%s".', 'flavor-chat-ia'),
                $hilo->titulo
            ),
        ];
    }

    /**
     * Accion: Buscar en los foros
     */
    private function action_buscar($parametros) {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $termino_busqueda = sanitize_text_field($parametros['busqueda'] ?? '');
        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error' => __('Introduce un termino de busqueda.', 'flavor-chat-ia'),
            ];
        }

        $limite_resultados = absint($parametros['limite'] ?? 20);
        $foro_id_filtro = absint($parametros['foro_id'] ?? 0);

        $clausulas_where = ["h.estado != 'eliminado'"];
        $valores_preparados = [];

        $patron_busqueda = '%' . $wpdb->esc_like($termino_busqueda) . '%';
        $clausulas_where[] = '(h.titulo LIKE %s OR h.contenido LIKE %s)';
        $valores_preparados[] = $patron_busqueda;
        $valores_preparados[] = $patron_busqueda;

        if ($foro_id_filtro > 0) {
            $clausulas_where[] = 'h.foro_id = %d';
            $valores_preparados[] = $foro_id_filtro;
        }

        $sql_where = implode(' AND ', $clausulas_where);
        $valores_preparados[] = $limite_resultados;

        $hilos_encontrados = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, f.nombre AS nombre_foro
             FROM $tabla_hilos h
             LEFT JOIN $tabla_foros f ON f.id = h.foro_id
             WHERE $sql_where
             ORDER BY h.ultima_actividad DESC
             LIMIT %d",
            ...$valores_preparados
        ));

        return [
            'success' => true,
            'busqueda' => $termino_busqueda,
            'total' => count($hilos_encontrados),
            'hilos' => array_map(function($hilo) {
                $datos_resumen = $this->formatear_hilo_resumen($hilo);
                $datos_resumen['nombre_foro'] = $hilo->nombre_foro ?? '';
                return $datos_resumen;
            }, $hilos_encontrados),
        ];
    }

    /**
     * Accion: Ver hilos del usuario actual
     */
    private function action_mis_hilos($parametros) {
        $usuario_id_actual = get_current_user_id();

        if (!$usuario_id_actual) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para ver tus hilos.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $pagina_actual = max(1, absint($parametros['pagina'] ?? 1));
        $hilos_por_pagina = absint($this->get_setting('hilos_por_pagina', 20));
        $desplazamiento = ($pagina_actual - 1) * $hilos_por_pagina;

        $total_hilos_usuario = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_hilos WHERE autor_id = %d AND estado != 'eliminado'",
            $usuario_id_actual
        ));

        $hilos_del_usuario = $wpdb->get_results($wpdb->prepare(
            "SELECT h.*, f.nombre AS nombre_foro
             FROM $tabla_hilos h
             LEFT JOIN $tabla_foros f ON f.id = h.foro_id
             WHERE h.autor_id = %d AND h.estado != 'eliminado'
             ORDER BY h.updated_at DESC
             LIMIT %d OFFSET %d",
            $usuario_id_actual,
            $hilos_por_pagina,
            $desplazamiento
        ));

        return [
            'success' => true,
            'total' => intval($total_hilos_usuario),
            'pagina' => $pagina_actual,
            'total_paginas' => ceil($total_hilos_usuario / $hilos_por_pagina),
            'hilos' => array_map(function($hilo) {
                $datos_resumen = $this->formatear_hilo_resumen($hilo);
                $datos_resumen['nombre_foro'] = $hilo->nombre_foro ?? '';
                return $datos_resumen;
            }, $hilos_del_usuario),
        ];
    }

    /**
     * Accion: Moderar contenido
     */
    private function action_moderar($parametros) {
        $usuario_id_actual = get_current_user_id();

        if (!$usuario_id_actual) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion.', 'flavor-chat-ia'),
            ];
        }

        // Verificar que el usuario tiene capacidad de moderacion
        if (!$this->usuario_es_moderador($usuario_id_actual)) {
            return [
                'success' => false,
                'error' => __('No tienes permisos de moderacion.', 'flavor-chat-ia'),
            ];
        }

        global $wpdb;

        $accion_moderacion = sanitize_text_field($parametros['accion_moderacion'] ?? '');
        $tipo_elemento = sanitize_text_field($parametros['tipo'] ?? '');
        $id_elemento = absint($parametros['id_elemento'] ?? 0);

        if (empty($accion_moderacion) || empty($tipo_elemento) || !$id_elemento) {
            return [
                'success' => false,
                'error' => __('Parametros de moderacion incompletos.', 'flavor-chat-ia'),
            ];
        }

        $acciones_validas_hilos = ['cerrar', 'abrir', 'fijar', 'desfijar', 'eliminar'];
        $acciones_validas_respuestas = ['ocultar', 'mostrar', 'eliminar'];

        if ($tipo_elemento === 'hilo') {
            if (!in_array($accion_moderacion, $acciones_validas_hilos, true)) {
                return [
                    'success' => false,
                    'error' => __('Accion de moderacion no valida para hilos.', 'flavor-chat-ia'),
                ];
            }

            return $this->moderar_hilo($id_elemento, $accion_moderacion);
        }

        if ($tipo_elemento === 'respuesta') {
            if (!in_array($accion_moderacion, $acciones_validas_respuestas, true)) {
                return [
                    'success' => false,
                    'error' => __('Accion de moderacion no valida para respuestas.', 'flavor-chat-ia'),
                ];
            }

            return $this->moderar_respuesta($id_elemento, $accion_moderacion);
        }

        return [
            'success' => false,
            'error' => __('Tipo de elemento no valido. Use "hilo" o "respuesta".', 'flavor-chat-ia'),
        ];
    }

    // =========================================================
    // Metodos de moderacion
    // =========================================================

    /**
     * Moderar un hilo
     */
    private function moderar_hilo($hilo_id, $accion_moderacion) {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        $datos_actualizacion = [];
        $mensaje_confirmacion = '';

        switch ($accion_moderacion) {
            case 'cerrar':
                $datos_actualizacion = ['estado' => 'cerrado'];
                $mensaje_confirmacion = __('Hilo cerrado correctamente.', 'flavor-chat-ia');
                break;
            case 'abrir':
                $datos_actualizacion = ['estado' => 'abierto'];
                $mensaje_confirmacion = __('Hilo reabierto correctamente.', 'flavor-chat-ia');
                break;
            case 'fijar':
                $datos_actualizacion = ['es_fijado' => 1, 'estado' => 'fijado'];
                $mensaje_confirmacion = __('Hilo fijado correctamente.', 'flavor-chat-ia');
                break;
            case 'desfijar':
                $datos_actualizacion = ['es_fijado' => 0, 'estado' => 'abierto'];
                $mensaje_confirmacion = __('Hilo desfijado correctamente.', 'flavor-chat-ia');
                break;
            case 'eliminar':
                $datos_actualizacion = ['estado' => 'eliminado'];
                $mensaje_confirmacion = __('Hilo eliminado correctamente.', 'flavor-chat-ia');
                break;
        }

        $resultado = $wpdb->update(
            $tabla_hilos,
            $datos_actualizacion,
            ['id' => $hilo_id]
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al moderar el hilo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => $mensaje_confirmacion,
        ];
    }

    /**
     * Moderar una respuesta
     */
    private function moderar_respuesta($respuesta_id, $accion_moderacion) {
        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $datos_actualizacion = [];
        $mensaje_confirmacion = '';

        switch ($accion_moderacion) {
            case 'ocultar':
                $datos_actualizacion = ['estado' => 'oculto'];
                $mensaje_confirmacion = __('Respuesta ocultada correctamente.', 'flavor-chat-ia');
                break;
            case 'mostrar':
                $datos_actualizacion = ['estado' => 'visible'];
                $mensaje_confirmacion = __('Respuesta mostrada correctamente.', 'flavor-chat-ia');
                break;
            case 'eliminar':
                $datos_actualizacion = ['estado' => 'eliminado'];
                $mensaje_confirmacion = __('Respuesta eliminada correctamente.', 'flavor-chat-ia');
                break;
        }

        $resultado = $wpdb->update(
            $tabla_respuestas,
            $datos_actualizacion,
            ['id' => $respuesta_id]
        );

        if ($resultado === false) {
            return [
                'success' => false,
                'error' => __('Error al moderar la respuesta.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success' => true,
            'mensaje' => $mensaje_confirmacion,
        ];
    }

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Formatea un hilo para respuesta resumida
     */
    private function formatear_hilo_resumen($hilo) {
        $datos_autor = get_user_by('ID', $hilo->autor_id);

        return [
            'id' => intval($hilo->id),
            'titulo' => $hilo->titulo,
            'extracto' => wp_trim_words($hilo->contenido, 30),
            'autor' => $datos_autor ? [
                'id' => $datos_autor->ID,
                'nombre' => $datos_autor->display_name,
                'avatar' => get_avatar_url($datos_autor->ID, ['size' => 64]),
            ] : null,
            'estado' => $hilo->estado,
            'es_fijado' => (bool) $hilo->es_fijado,
            'es_destacado' => (bool) $hilo->es_destacado,
            'vistas' => intval($hilo->vistas),
            'respuestas_count' => intval($hilo->respuestas_count),
            'fecha_creacion' => $hilo->created_at,
            'ultima_actividad' => $hilo->ultima_actividad,
        ];
    }

    /**
     * Formatea una respuesta para la salida
     */
    private function formatear_respuesta($respuesta) {
        $datos_autor = get_user_by('ID', $respuesta->autor_id);

        return [
            'id' => intval($respuesta->id),
            'contenido' => $respuesta->contenido,
            'autor' => $datos_autor ? [
                'id' => $datos_autor->ID,
                'nombre' => $datos_autor->display_name,
                'avatar' => get_avatar_url($datos_autor->ID, ['size' => 64]),
            ] : null,
            'parent_id' => intval($respuesta->parent_id),
            'es_solucion' => (bool) $respuesta->es_solucion,
            'votos' => intval($respuesta->votos),
            'fecha_creacion' => $respuesta->created_at,
        ];
    }

    /**
     * Verifica si un usuario es moderador
     */
    private function usuario_es_moderador($usuario_id) {
        // Los administradores siempre son moderadores
        if (user_can($usuario_id, 'manage_options')) {
            return true;
        }

        // Verificar si el usuario esta en la lista de moderadores de algun foro
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $foros_con_moderadores = $wpdb->get_results(
            "SELECT moderadores FROM $tabla_foros WHERE moderadores IS NOT NULL AND moderadores != ''"
        );

        foreach ($foros_con_moderadores as $foro) {
            $lista_moderadores = json_decode($foro->moderadores, true);
            if (is_array($lista_moderadores) && in_array($usuario_id, $lista_moderadores)) {
                return true;
            }
        }

        return false;
    }

    // =========================================================
    // Definiciones de Tools para IA
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'foros_listar',
                'description' => 'Lista todas las categorias de foros disponibles con estadisticas de hilos y respuestas',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
            [
                'name' => 'foros_buscar',
                'description' => 'Busca hilos de discusion por titulo o contenido en los foros',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Termino de busqueda para encontrar hilos',
                        ],
                        'foro_id' => [
                            'type' => 'integer',
                            'description' => 'ID del foro para filtrar la busqueda (opcional)',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Numero maximo de resultados a devolver',
                            'default' => 20,
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name' => 'foros_crear_hilo',
                'description' => 'Crea un nuevo hilo de discusion en un foro (el usuario debe estar autenticado)',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'foro_id' => [
                            'type' => 'integer',
                            'description' => 'ID del foro donde crear el hilo',
                        ],
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Titulo del hilo de discusion',
                        ],
                        'contenido' => [
                            'type' => 'string',
                            'description' => 'Contenido o mensaje inicial del hilo',
                        ],
                    ],
                    'required' => ['foro_id', 'titulo', 'contenido'],
                ],
            ],
        ];
    }

    // =========================================================
    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción
     * @return array Configuración del formulario
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_tema' => [
                'title' => __('Crear Nuevo Tema', 'flavor-chat-ia'),
                'description' => __('Inicia un nuevo hilo de discusión', 'flavor-chat-ia'),
                'fields' => [
                    'categoria_id' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'general' => __('General', 'flavor-chat-ia'),
                            'anuncios' => __('Anuncios', 'flavor-chat-ia'),
                            'dudas' => __('Dudas y preguntas', 'flavor-chat-ia'),
                            'propuestas' => __('Propuestas', 'flavor-chat-ia'),
                            'quejas' => __('Quejas y sugerencias', 'flavor-chat-ia'),
                        ],
                    ],
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título del tema', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Resume el tema en pocas palabras', 'flavor-chat-ia'),
                    ],
                    'contenido' => [
                        'type' => 'textarea',
                        'label' => __('Mensaje', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 6,
                        'placeholder' => __('Desarrolla tu mensaje...', 'flavor-chat-ia'),
                    ],
                    'etiquetas' => [
                        'type' => 'text',
                        'label' => __('Etiquetas', 'flavor-chat-ia'),
                        'placeholder' => __('Separadas por comas: urgente, grupo-consumo', 'flavor-chat-ia'),
                        'description' => __('Ayuda a otros a encontrar tu tema', 'flavor-chat-ia'),
                    ],
                    'permitir_respuestas' => [
                        'type' => 'checkbox',
                        'label' => __('Permitir respuestas', 'flavor-chat-ia'),
                        'checkbox_label' => __('Permitir que otros respondan a este tema', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'submit_text' => __('Publicar Tema', 'flavor-chat-ia'),
                'success_message' => __('Tema publicado correctamente', 'flavor-chat-ia'),
                'redirect_url' => '/foros/',
            ],
            'responder_tema' => [
                'title' => __('Responder al Tema', 'flavor-chat-ia'),
                'fields' => [
                    'tema_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'contenido' => [
                        'type' => 'textarea',
                        'label' => __('Tu respuesta', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                        'placeholder' => __('Escribe tu respuesta...', 'flavor-chat-ia'),
                    ],
                    'notificar_respuestas' => [
                        'type' => 'checkbox',
                        'label' => __('Notificaciones', 'flavor-chat-ia'),
                        'checkbox_label' => __('Recibir notificaciones de nuevas respuestas', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Publicar Respuesta', 'flavor-chat-ia'),
                'success_message' => __('Respuesta publicada', 'flavor-chat-ia'),
            ],
            'editar_mensaje' => [
                'title' => __('Editar Mensaje', 'flavor-chat-ia'),
                'fields' => [
                    'mensaje_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'contenido' => [
                        'type' => 'textarea',
                        'label' => __('Contenido', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 5,
                    ],
                    'motivo_edicion' => [
                        'type' => 'text',
                        'label' => __('Motivo de la edición (opcional)', 'flavor-chat-ia'),
                        'placeholder' => __('Corrección, añadir información...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Guardar Cambios', 'flavor-chat-ia'),
                'success_message' => __('Mensaje actualizado', 'flavor-chat-ia'),
            ],
            'reportar_mensaje' => [
                'title' => __('Reportar Mensaje', 'flavor-chat-ia'),
                'description' => __('Ayúdanos a mantener un ambiente respetuoso', 'flavor-chat-ia'),
                'fields' => [
                    'mensaje_id' => [
                        'type' => 'hidden',
                        'required' => true,
                    ],
                    'motivo' => [
                        'type' => 'select',
                        'label' => __('Motivo del reporte', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'spam' => __('Spam o publicidad', 'flavor-chat-ia'),
                            'ofensivo' => __('Contenido ofensivo', 'flavor-chat-ia'),
                            'acoso' => __('Acoso o insultos', 'flavor-chat-ia'),
                            'desinformacion' => __('Desinformación', 'flavor-chat-ia'),
                            'otro' => __('Otro motivo', 'flavor-chat-ia'),
                        ],
                    ],
                    'detalles' => [
                        'type' => 'textarea',
                        'label' => __('Detalles adicionales', 'flavor-chat-ia'),
                        'rows' => 3,
                        'placeholder' => __('Explica el problema con más detalle...', 'flavor-chat-ia'),
                    ],
                ],
                'submit_text' => __('Enviar Reporte', 'flavor-chat-ia'),
                'success_message' => __('Reporte enviado. Lo revisaremos pronto.', 'flavor-chat-ia'),
            ],
        ];

        return $configs[$action_name] ?? [];
    }

    // Componentes Web
    // =========================================================

    /**
     * Componentes web del modulo
     */
    public function get_web_components() {
        return [
            'foros_hero' => [
                'label' => __('Hero Foros', 'flavor-chat-ia'),
                'description' => __('Seccion hero para la pagina principal de foros', 'flavor-chat-ia'),
                'category' => 'hero',
                'icon' => 'dashicons-format-chat',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Titulo', 'flavor-chat-ia'),
                        'default' => __('Foros de la Comunidad', 'flavor-chat-ia'),
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtitulo', 'flavor-chat-ia'),
                        'default' => __('Participa en las discusiones, comparte conocimiento y conecta con tu comunidad', 'flavor-chat-ia'),
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de fondo', 'flavor-chat-ia'),
                        'default' => '',
                    ],
                ],
                'template' => 'foros/hero',
            ],
            'foros_lista' => [
                'label' => __('Lista de Foros', 'flavor-chat-ia'),
                'description' => __('Grid de categorias de foros con estadisticas', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-list-view',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de seccion', 'flavor-chat-ia'),
                        'default' => __('Categorias de Foros', 'flavor-chat-ia'),
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => [2, 3],
                        'default' => 2,
                    ],
                    'mostrar_estadisticas' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar estadisticas', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'foros/foros-lista',
            ],
            'foros_ultimos_temas' => [
                'label' => __('Ultimos Temas', 'flavor-chat-ia'),
                'description' => __('Lista de los ultimos temas publicados en los foros', 'flavor-chat-ia'),
                'category' => 'listings',
                'icon' => 'dashicons-editor-ul',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Titulo de seccion', 'flavor-chat-ia'),
                        'default' => __('Ultimos Temas', 'flavor-chat-ia'),
                    ],
                    'limite' => [
                        'type' => 'number',
                        'label' => __('Numero de temas', 'flavor-chat-ia'),
                        'default' => 10,
                    ],
                    'mostrar_foro' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar nombre del foro', 'flavor-chat-ia'),
                        'default' => true,
                    ],
                ],
                'template' => 'foros/ultimos-temas',
            ],
        ];
    }

    // =========================================================
    // Knowledge Base y FAQs
    // =========================================================

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Foros de Discusion Comunitarios**

Sistema completo de foros para la comunidad donde los miembros pueden crear hilos de discusion, responder y participar en conversaciones organizadas por categorias.

**Funcionalidades principales:**
- Categorias de foros organizadas tematicamente
- Creacion de hilos de discusion con titulo y contenido
- Respuestas a hilos, incluyendo respuestas anidadas
- Sistema de busqueda por titulo y contenido
- Contador de vistas y respuestas por hilo
- Hilos fijados y destacados
- Marcar respuestas como solucion
- Sistema de votos en respuestas
- Moderacion: cerrar, fijar, eliminar hilos y ocultar respuestas

**Como participar:**
- Para ver los foros y leer los hilos no necesitas cuenta
- Para crear un hilo o responder debes estar registrado e iniciar sesion
- Escribe un titulo descriptivo para que otros encuentren tu tema
- Utiliza la busqueda para encontrar hilos sobre tu duda antes de crear uno nuevo
- Puedes ver tus propios hilos en la seccion "Mis hilos"

**Moderacion:**
- Los administradores y moderadores designados pueden moderar contenido
- Se puede cerrar, fijar o eliminar hilos
- Se pueden ocultar o eliminar respuestas inapropiadas
- Los moderadores se asignan por foro

**Comandos disponibles:**
- "ver foros": muestra la lista de categorias de foros
- "buscar [termino]": busca hilos por titulo o contenido
- "crear hilo": inicia la creacion de un nuevo hilo de discusion
- "mis hilos": muestra los hilos que has creado
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => 'Como puedo crear un nuevo hilo en los foros?',
                'respuesta' => 'Inicia sesion, ve a la seccion de Foros, selecciona la categoria adecuada y haz clic en "Nuevo Hilo". Rellena el titulo y el contenido de tu mensaje.',
            ],
            [
                'pregunta' => 'Puedo editar o eliminar mis propias publicaciones?',
                'respuesta' => 'Actualmente la edicion y eliminacion de publicaciones esta gestionada por los moderadores. Si necesitas modificar algo, contacta con un moderador.',
            ],
            [
                'pregunta' => 'Como puedo buscar un tema especifico?',
                'respuesta' => 'Utiliza la funcion de busqueda dentro de los foros. Puedes buscar por palabras clave en el titulo o contenido de los hilos.',
            ],
            [
                'pregunta' => 'Que significa que un hilo este fijado?',
                'respuesta' => 'Los hilos fijados aparecen siempre en la parte superior de la lista. Son temas importantes que los moderadores quieren mantener visibles.',
            ],
            [
                'pregunta' => 'Necesito registrarme para leer los foros?',
                'respuesta' => 'No, puedes leer los foros sin necesidad de tener cuenta. Solo necesitas registrarte para crear hilos o responder.',
            ],
        ];
    }

    // =========================================================
    // Acciones delegadas para formularios frontend
    // =========================================================

    private function action_listar_temas($parametros) {
        return $this->action_listar_foros($parametros);
    }

    private function action_crear_tema($parametros) {
        // Mapear campo categoria_id a foro_id
        if (!empty($parametros['categoria_id']) && empty($parametros['foro_id'])) {
            $parametros['foro_id'] = $parametros['categoria_id'];
        }
        return $this->action_crear_hilo($parametros);
    }

    private function action_responder_tema($parametros) {
        // Mapear campo tema_id a hilo_id
        if (!empty($parametros['tema_id']) && empty($parametros['hilo_id'])) {
            $parametros['hilo_id'] = $parametros['tema_id'];
        }
        return $this->action_responder($parametros);
    }

    private function action_editar_mensaje($parametros) {
        $usuario_id = get_current_user_id();

        if (!$usuario_id) {
            return [
                'success' => false,
                'error' => 'Debes iniciar sesion para editar.',
            ];
        }

        $mensaje_id = absint($parametros['mensaje_id'] ?? 0);
        $contenido = sanitize_textarea_field($parametros['contenido'] ?? '');
        $motivo_edicion = sanitize_text_field($parametros['motivo_edicion'] ?? '');

        if (!$mensaje_id || empty($contenido)) {
            return [
                'success' => false,
                'error' => 'Se requiere ID del mensaje y contenido.',
            ];
        }

        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        // Verificar que el mensaje pertenece al usuario
        $mensaje = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_respuestas WHERE id = %d",
            $mensaje_id
        ));

        if (!$mensaje) {
            return ['success' => false, 'error' => 'Mensaje no encontrado.'];
        }

        if ((int) $mensaje->autor_id !== $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => 'No tienes permiso para editar este mensaje.'];
        }

        $datos_actualizar = [
            'contenido' => $contenido,
            'editado' => 1,
            'fecha_edicion' => current_time('mysql'),
        ];

        if (!empty($motivo_edicion)) {
            $datos_actualizar['motivo_edicion'] = $motivo_edicion;
        }

        $resultado = $wpdb->update(
            $tabla_respuestas,
            $datos_actualizar,
            ['id' => $mensaje_id]
        );

        if ($resultado === false) {
            return ['success' => false, 'error' => 'Error al actualizar el mensaje.'];
        }

        return [
            'success' => true,
            'mensaje' => 'Mensaje actualizado correctamente.',
        ];
    }
}
