<?php
/**
 * Gestión de perfiles de Artista (extensión Kulturaka)
 *
 * @package FlavorPlatform
 * @subpackage Modules\Socios\Artistas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar perfiles de artista
 */
class Flavor_Socios_Artista_Profile {

    /**
     * Instancia singleton
     *
     * @var Flavor_Socios_Artista_Profile|null
     */
    private static $instancia = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Socios_Artista_Profile
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializa hooks y acciones
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Shortcodes
        add_shortcode('artista_perfil', [$this, 'shortcode_perfil_artista']);
        add_shortcode('artistas_listado', [$this, 'shortcode_listado_artistas']);
        add_shortcode('artistas_destacados', [$this, 'shortcode_artistas_destacados']);
    }

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        if (!get_option('flavor_socios_artistas_db_version')) {
            require_once dirname(__FILE__) . '/install-artistas.php';
            flavor_socios_artistas_crear_tablas();
        }
    }

    // =========================================================
    // CRUD de Perfiles de Artista
    // =========================================================

    /**
     * Obtiene un perfil de artista por ID o slug
     *
     * @param int|string $identificador ID numérico o slug
     * @return object|null
     */
    public function get_artista($identificador) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        if (is_numeric($identificador)) {
            $artista = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE id = %d AND activo = 1",
                $identificador
            ));
        } else {
            $artista = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla WHERE slug = %s AND activo = 1",
                $identificador
            ));
        }

        if ($artista) {
            $artista = $this->enriquecer_artista($artista);
        }

        return $artista;
    }

    /**
     * Obtiene un artista por ID de usuario
     *
     * @param int $usuario_id
     * @return object|null
     */
    public function get_artista_by_usuario($usuario_id) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        $artista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d AND activo = 1",
            $usuario_id
        ));

        if ($artista) {
            $artista = $this->enriquecer_artista($artista);
        }

        return $artista;
    }

    /**
     * Enriquece un objeto artista con datos adicionales
     *
     * @param object $artista
     * @return object
     */
    private function enriquecer_artista($artista) {
        // Decodificar JSON
        $artista->disciplinas = json_decode($artista->disciplinas, true) ?: [];
        $artista->generos = json_decode($artista->generos, true) ?: [];
        $artista->idiomas = json_decode($artista->idiomas, true) ?: [];
        $artista->portfolio_videos = json_decode($artista->portfolio_videos, true) ?: [];
        $artista->portfolio_audio = json_decode($artista->portfolio_audio, true) ?: [];
        $artista->portfolio_imagenes = json_decode($artista->portfolio_imagenes, true) ?: [];
        $artista->portfolio_prensa = json_decode($artista->portfolio_prensa, true) ?: [];
        $artista->metadata = json_decode($artista->metadata, true) ?: [];

        // Añadir datos del usuario
        $usuario = get_userdata($artista->usuario_id);
        if ($usuario) {
            $artista->email = $usuario->user_email;
            $artista->avatar = get_avatar_url($artista->usuario_id, ['size' => 200]);
        }

        // Añadir labels de nivel
        $artista->nivel_label = $this->get_nivel_label($artista->nivel_artista);

        // Añadir disciplinas con detalles
        $artista->disciplinas_detalle = $this->get_disciplinas_detalle($artista->disciplinas);

        return $artista;
    }

    /**
     * Crea un nuevo perfil de artista
     *
     * @param array $datos
     * @return int|WP_Error ID del artista o error
     */
    public function crear_artista($datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        $usuario_id = $datos['usuario_id'] ?? get_current_user_id();

        if (!$usuario_id) {
            return new WP_Error('sin_usuario', __('Se requiere un usuario para crear el perfil de artista.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar que no existe ya un perfil
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d",
            $usuario_id
        ));

        if ($existe) {
            return new WP_Error('artista_existe', __('Ya existe un perfil de artista para este usuario.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener o crear socio
        $socio_id = $this->get_or_create_socio_artista($usuario_id);

        // Generar slug único
        $nombre_artistico = sanitize_text_field($datos['nombre_artistico'] ?? '');
        if (empty($nombre_artistico)) {
            $usuario = get_userdata($usuario_id);
            $nombre_artistico = $usuario ? $usuario->display_name : 'Artista';
        }
        $slug = $this->generar_slug_unico($nombre_artistico);

        $datos_insertar = [
            'socio_id' => $socio_id,
            'usuario_id' => $usuario_id,
            'nombre_artistico' => $nombre_artistico,
            'slug' => $slug,
            'bio_artistica' => sanitize_textarea_field($datos['bio_artistica'] ?? ''),
            'bio_corta' => sanitize_text_field($datos['bio_corta'] ?? ''),
            'disciplinas' => wp_json_encode($datos['disciplinas'] ?? []),
            'generos' => wp_json_encode($datos['generos'] ?? []),
            'idiomas' => wp_json_encode($datos['idiomas'] ?? ['es']),
            'website' => esc_url_raw($datos['website'] ?? ''),
            'instagram' => sanitize_text_field($datos['instagram'] ?? ''),
            'bandcamp' => sanitize_text_field($datos['bandcamp'] ?? ''),
            'youtube' => sanitize_text_field($datos['youtube'] ?? ''),
            'nivel_artista' => 'emergente',
            'activo' => 1,
        ];

        $insertado = $wpdb->insert($tabla, $datos_insertar);

        if (!$insertado) {
            return new WP_Error('error_insertar', __('Error al crear el perfil de artista.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $artista_id = $wpdb->insert_id;

        /**
         * Acción después de crear un perfil de artista
         *
         * @param int $artista_id ID del artista creado
         * @param array $datos Datos del artista
         */
        do_action('flavor_artista_creado', $artista_id, $datos);

        return $artista_id;
    }

    /**
     * Actualiza un perfil de artista
     *
     * @param int $artista_id
     * @param array $datos
     * @return bool|WP_Error
     */
    public function actualizar_artista($artista_id, $datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        $campos_permitidos = [
            'nombre_artistico', 'bio_artistica', 'bio_corta',
            'disciplinas', 'generos', 'idiomas',
            'portfolio_videos', 'portfolio_audio', 'portfolio_imagenes', 'portfolio_prensa',
            'video_destacado', 'audio_destacado',
            'website', 'instagram', 'bandcamp', 'spotify', 'youtube', 'soundcloud', 'tiktok',
            'disponible_giras', 'radio_actuacion_km', 'requiere_cachette', 'cache_minimo',
            'acepta_semilla', 'acepta_hours',
            'rider_tecnico', 'necesidades_escenario', 'duracion_tipica_min',
        ];

        $datos_actualizar = [];

        foreach ($campos_permitidos as $campo) {
            if (!isset($datos[$campo])) {
                continue;
            }

            $valor = $datos[$campo];

            // Campos JSON
            if (in_array($campo, ['disciplinas', 'generos', 'idiomas', 'portfolio_videos', 'portfolio_audio', 'portfolio_imagenes', 'portfolio_prensa'])) {
                $valor = wp_json_encode(is_array($valor) ? $valor : []);
            }
            // Campos de texto largo
            elseif (in_array($campo, ['bio_artistica', 'rider_tecnico', 'necesidades_escenario'])) {
                $valor = sanitize_textarea_field($valor);
            }
            // URLs
            elseif (in_array($campo, ['website', 'video_destacado', 'audio_destacado'])) {
                $valor = esc_url_raw($valor);
            }
            // Campos numéricos
            elseif (in_array($campo, ['radio_actuacion_km', 'duracion_tipica_min'])) {
                $valor = absint($valor);
            }
            elseif (in_array($campo, ['cache_minimo'])) {
                $valor = floatval($valor);
            }
            // Booleanos
            elseif (in_array($campo, ['disponible_giras', 'requiere_cachette', 'acepta_semilla', 'acepta_hours'])) {
                $valor = $valor ? 1 : 0;
            }
            // Texto simple
            else {
                $valor = sanitize_text_field($valor);
            }

            $datos_actualizar[$campo] = $valor;
        }

        // Actualizar slug si cambia el nombre
        if (isset($datos_actualizar['nombre_artistico'])) {
            $artista_actual = $this->get_artista($artista_id);
            if ($artista_actual && $artista_actual->nombre_artistico !== $datos_actualizar['nombre_artistico']) {
                $datos_actualizar['slug'] = $this->generar_slug_unico($datos_actualizar['nombre_artistico'], $artista_id);
            }
        }

        if (empty($datos_actualizar)) {
            return new WP_Error('sin_cambios', __('No hay datos para actualizar.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $actualizado = $wpdb->update(
            $tabla,
            $datos_actualizar,
            ['id' => $artista_id]
        );

        if (false === $actualizado) {
            return new WP_Error('error_actualizar', __('Error al actualizar el perfil.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        /**
         * Acción después de actualizar un perfil de artista
         *
         * @param int $artista_id ID del artista
         * @param array $datos Datos actualizados
         */
        do_action('flavor_artista_actualizado', $artista_id, $datos_actualizar);

        return true;
    }

    /**
     * Lista artistas con filtros
     *
     * @param array $argumentos
     * @return array
     */
    public function listar_artistas($argumentos = []) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        $defaults = [
            'limite' => 20,
            'offset' => 0,
            'orderby' => 'rating',
            'order' => 'DESC',
            'nivel' => '',
            'disciplina' => '',
            'genero' => '',
            'verificado' => null,
            'busqueda' => '',
            'solo_disponibles' => false,
            'destacados' => false,
        ];

        $argumentos = wp_parse_args($argumentos, $defaults);

        $where = ['activo = 1'];
        $valores = [];

        if (!empty($argumentos['nivel'])) {
            $where[] = 'nivel_artista = %s';
            $valores[] = $argumentos['nivel'];
        }

        if (!empty($argumentos['disciplina'])) {
            $where[] = 'JSON_CONTAINS(disciplinas, %s)';
            $valores[] = '"' . $argumentos['disciplina'] . '"';
        }

        if (!empty($argumentos['genero'])) {
            $where[] = 'JSON_CONTAINS(generos, %s)';
            $valores[] = '"' . $argumentos['genero'] . '"';
        }

        if (null !== $argumentos['verificado']) {
            $where[] = 'verificado = %d';
            $valores[] = $argumentos['verificado'] ? 1 : 0;
        }

        if ($argumentos['solo_disponibles']) {
            $where[] = 'disponible_giras = 1';
        }

        if ($argumentos['destacados']) {
            $where[] = 'destacado = 1';
        }

        if (!empty($argumentos['busqueda'])) {
            $where[] = '(nombre_artistico LIKE %s OR bio_artistica LIKE %s OR bio_corta LIKE %s)';
            $busqueda_like = '%' . $wpdb->esc_like($argumentos['busqueda']) . '%';
            $valores[] = $busqueda_like;
            $valores[] = $busqueda_like;
            $valores[] = $busqueda_like;
        }

        $where_sql = implode(' AND ', $where);

        // Validar orderby
        $columnas_validas = ['rating', 'eventos_realizados', 'seguidores_count', 'created_at', 'nombre_artistico'];
        $orderby = in_array($argumentos['orderby'], $columnas_validas) ? $argumentos['orderby'] : 'rating';
        $order = strtoupper($argumentos['order']) === 'ASC' ? 'ASC' : 'DESC';

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM $tabla WHERE $where_sql";
        if (!empty($valores)) {
            $sql_count = $wpdb->prepare($sql_count, $valores);
        }
        $total = (int) $wpdb->get_var($sql_count);

        // Obtener artistas
        $sql = "SELECT * FROM $tabla WHERE $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $valores[] = $argumentos['limite'];
        $valores[] = $argumentos['offset'];

        $artistas = $wpdb->get_results($wpdb->prepare($sql, $valores));

        // Enriquecer resultados
        foreach ($artistas as &$artista) {
            $artista = $this->enriquecer_artista($artista);
        }

        return [
            'artistas' => $artistas,
            'total' => $total,
            'paginas' => ceil($total / $argumentos['limite']),
            'pagina_actual' => floor($argumentos['offset'] / $argumentos['limite']) + 1,
        ];
    }

    // =========================================================
    // Seguidores
    // =========================================================

    /**
     * Sigue a un artista
     *
     * @param int $artista_id
     * @param int $seguidor_id
     * @return bool|WP_Error
     */
    public function seguir_artista($artista_id, $seguidor_id = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_seguidores';
        $tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';

        $seguidor_id = $seguidor_id ?: get_current_user_id();

        if (!$seguidor_id) {
            return new WP_Error('sin_usuario', __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Verificar que no se sigue a sí mismo
        $artista = $this->get_artista($artista_id);
        if ($artista && $artista->usuario_id == $seguidor_id) {
            return new WP_Error('auto_seguimiento', __('No puedes seguirte a ti mismo.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $insertado = $wpdb->insert($tabla, [
            'artista_id' => $artista_id,
            'seguidor_id' => $seguidor_id,
            'notificaciones' => 1,
        ]);

        if ($insertado) {
            // Actualizar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_artistas SET seguidores_count = seguidores_count + 1 WHERE id = %d",
                $artista_id
            ));

            do_action('flavor_artista_seguido', $artista_id, $seguidor_id);
        }

        return (bool) $insertado;
    }

    /**
     * Deja de seguir a un artista
     *
     * @param int $artista_id
     * @param int $seguidor_id
     * @return bool
     */
    public function dejar_de_seguir($artista_id, $seguidor_id = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_seguidores';
        $tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';

        $seguidor_id = $seguidor_id ?: get_current_user_id();

        $eliminado = $wpdb->delete($tabla, [
            'artista_id' => $artista_id,
            'seguidor_id' => $seguidor_id,
        ]);

        if ($eliminado) {
            // Actualizar contador
            $wpdb->query($wpdb->prepare(
                "UPDATE $tabla_artistas SET seguidores_count = GREATEST(seguidores_count - 1, 0) WHERE id = %d",
                $artista_id
            ));
        }

        return (bool) $eliminado;
    }

    /**
     * Verifica si un usuario sigue a un artista
     *
     * @param int $artista_id
     * @param int $seguidor_id
     * @return bool
     */
    public function esta_siguiendo($artista_id, $seguidor_id = null) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_seguidores';

        $seguidor_id = $seguidor_id ?: get_current_user_id();

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE artista_id = %d AND seguidor_id = %d",
            $artista_id,
            $seguidor_id
        ));
    }

    /**
     * Obtiene los seguidores de un artista
     *
     * @param int $artista_id
     * @param int $limite
     * @return array
     */
    public function get_seguidores($artista_id, $limite = 20) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_seguidores';

        return $wpdb->get_results($wpdb->prepare("
            SELECT s.*, u.display_name, u.user_email
            FROM $tabla s
            LEFT JOIN {$wpdb->users} u ON s.seguidor_id = u.ID
            WHERE s.artista_id = %d
            ORDER BY s.created_at DESC
            LIMIT %d
        ", $artista_id, $limite));
    }

    // =========================================================
    // Valoraciones
    // =========================================================

    /**
     * Añade una valoración a un artista
     *
     * @param int $artista_id
     * @param array $datos
     * @return int|WP_Error
     */
    public function valorar_artista($artista_id, $datos) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_valoraciones';
        $tabla_artistas = $wpdb->prefix . 'flavor_socios_artistas';

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            return new WP_Error('sin_usuario', __('Debes iniciar sesión para valorar.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $puntuacion = absint($datos['puntuacion'] ?? 0);
        if ($puntuacion < 1 || $puntuacion > 5) {
            return new WP_Error('puntuacion_invalida', __('La puntuación debe estar entre 1 y 5.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $datos_insertar = [
            'artista_id' => $artista_id,
            'usuario_id' => $usuario_id,
            'evento_id' => absint($datos['evento_id'] ?? 0) ?: null,
            'puntuacion' => $puntuacion,
            'comentario' => sanitize_textarea_field($datos['comentario'] ?? ''),
            'puntualidad' => isset($datos['puntualidad']) ? absint($datos['puntualidad']) : null,
            'calidad_artistica' => isset($datos['calidad_artistica']) ? absint($datos['calidad_artistica']) : null,
            'profesionalidad' => isset($datos['profesionalidad']) ? absint($datos['profesionalidad']) : null,
            'trato_publico' => isset($datos['trato_publico']) ? absint($datos['trato_publico']) : null,
            'visible' => 1,
        ];

        // Usar REPLACE para permitir actualizar valoración existente
        $insertado = $wpdb->replace($tabla, $datos_insertar);

        if ($insertado) {
            // Recalcular rating promedio
            $nuevo_rating = $wpdb->get_var($wpdb->prepare(
                "SELECT AVG(puntuacion) FROM $tabla WHERE artista_id = %d AND visible = 1",
                $artista_id
            ));

            $total_valoraciones = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE artista_id = %d AND visible = 1",
                $artista_id
            ));

            $wpdb->update($tabla_artistas, [
                'rating' => round($nuevo_rating, 2),
                'total_valoraciones' => $total_valoraciones,
            ], ['id' => $artista_id]);
        }

        return $insertado ? $wpdb->insert_id : new WP_Error('error_valorar', __('Error al guardar la valoración.', FLAVOR_PLATFORM_TEXT_DOMAIN));
    }

    // =========================================================
    // Helpers
    // =========================================================

    /**
     * Genera un slug único para el artista
     *
     * @param string $nombre
     * @param int $excluir_id
     * @return string
     */
    private function generar_slug_unico($nombre, $excluir_id = 0) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_socios_artistas';

        $slug_base = sanitize_title($nombre);
        $slug = $slug_base;
        $contador = 1;

        while (true) {
            $existe = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $tabla WHERE slug = %s AND id != %d",
                $slug,
                $excluir_id
            ));

            if (!$existe) {
                break;
            }

            $slug = $slug_base . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    /**
     * Obtiene o crea un registro de socio para el artista
     *
     * @param int $usuario_id
     * @return int ID del socio
     */
    private function get_or_create_socio_artista($usuario_id) {
        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_socios WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio_id) {
            $usuario = get_userdata($usuario_id);
            $numero_socio = 'ART-' . str_pad($usuario_id, 5, '0', STR_PAD_LEFT);

            $wpdb->insert($tabla_socios, [
                'numero_socio' => $numero_socio,
                'usuario_id' => $usuario_id,
                'nombre' => $usuario->first_name ?: $usuario->display_name,
                'apellidos' => $usuario->last_name ?: '',
                'email' => $usuario->user_email,
                'tipo_socio' => 'artista',
                'fecha_alta' => current_time('Y-m-d'),
                'estado' => 'activo',
                'cuota_importe' => 0,
            ]);

            $socio_id = $wpdb->insert_id;
        }

        return $socio_id;
    }

    /**
     * Obtiene el label del nivel de artista
     *
     * @param string $nivel
     * @return string
     */
    private function get_nivel_label($nivel) {
        $niveles = [
            'emergente' => __('Emergente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'establecido' => __('Establecido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'profesional' => __('Profesional', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'consagrado' => __('Consagrado', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        return $niveles[$nivel] ?? $nivel;
    }

    /**
     * Obtiene detalles de las disciplinas
     *
     * @param array $slugs
     * @return array
     */
    private function get_disciplinas_detalle($slugs) {
        if (empty($slugs)) {
            return [];
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_disciplinas';

        $placeholders = implode(',', array_fill(0, count($slugs), '%s'));

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla WHERE slug IN ($placeholders) ORDER BY orden",
            $slugs
        ));
    }

    /**
     * Obtiene todas las disciplinas activas
     *
     * @return array
     */
    public function get_disciplinas() {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_artistas_disciplinas';

        return $wpdb->get_results("SELECT * FROM $tabla WHERE activa = 1 ORDER BY categoria, orden");
    }

    // =========================================================
    // REST API
    // =========================================================

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor/v1', '/artistas', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_listar_artistas'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/artistas/(?P<id>[\w-]+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_artista'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('flavor/v1', '/artistas/(?P<id>\d+)/seguir', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_seguir_artista'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    /**
     * REST: Listar artistas
     */
    public function rest_listar_artistas($request) {
        $resultado = $this->listar_artistas([
            'limite' => $request->get_param('per_page') ?: 20,
            'offset' => (($request->get_param('page') ?: 1) - 1) * ($request->get_param('per_page') ?: 20),
            'disciplina' => $request->get_param('disciplina'),
            'genero' => $request->get_param('genero'),
            'nivel' => $request->get_param('nivel'),
            'busqueda' => $request->get_param('search'),
            'verificado' => $request->get_param('verificado'),
        ]);

        return rest_ensure_response($resultado);
    }

    /**
     * REST: Obtener artista
     */
    public function rest_get_artista($request) {
        $artista = $this->get_artista($request->get_param('id'));

        if (!$artista) {
            return new WP_Error('no_encontrado', __('Artista no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN), ['status' => 404]);
        }

        return rest_ensure_response($artista);
    }

    /**
     * REST: Seguir artista
     */
    public function rest_seguir_artista($request) {
        $artista_id = $request->get_param('id');

        if ($this->esta_siguiendo($artista_id)) {
            $resultado = $this->dejar_de_seguir($artista_id);
            $siguiendo = false;
        } else {
            $resultado = $this->seguir_artista($artista_id);
            $siguiendo = true;
        }

        if (is_wp_error($resultado)) {
            return $resultado;
        }

        return rest_ensure_response([
            'success' => true,
            'siguiendo' => $siguiendo,
        ]);
    }

    // =========================================================
    // Shortcodes
    // =========================================================

    /**
     * Shortcode: Perfil de artista
     */
    public function shortcode_perfil_artista($atributos) {
        $atributos = shortcode_atts([
            'id' => '',
            'slug' => '',
        ], $atributos);

        $identificador = $atributos['id'] ?: $atributos['slug'];

        if (empty($identificador)) {
            // Intentar obtener del query string
            $identificador = get_query_var('artista') ?: (isset($_GET['artista']) ? sanitize_text_field($_GET['artista']) : '');
        }

        if (empty($identificador)) {
            return '<p class="flavor-error">' . __('No se especificó ningún artista.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $artista = $this->get_artista($identificador);

        if (!$artista) {
            return '<p class="flavor-error">' . __('Artista no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
        }

        $esta_siguiendo = is_user_logged_in() ? $this->esta_siguiendo($artista->id) : false;
        $es_propio = is_user_logged_in() && get_current_user_id() == $artista->usuario_id;

        ob_start();
        include dirname(__FILE__) . '/views/perfil-artista.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Listado de artistas
     */
    public function shortcode_listado_artistas($atributos) {
        $atributos = shortcode_atts([
            'limite' => 12,
            'disciplina' => '',
            'nivel' => '',
            'verificados' => '',
        ], $atributos);

        $resultado = $this->listar_artistas([
            'limite' => absint($atributos['limite']),
            'disciplina' => $atributos['disciplina'],
            'nivel' => $atributos['nivel'],
            'verificado' => $atributos['verificados'] === 'si' ? true : null,
        ]);

        $artistas = $resultado['artistas'];
        $disciplinas = $this->get_disciplinas();

        ob_start();
        include dirname(__FILE__) . '/views/listado.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Artistas destacados
     */
    public function shortcode_artistas_destacados($atributos) {
        $atributos = shortcode_atts([
            'limite' => 6,
        ], $atributos);

        $resultado = $this->listar_artistas([
            'limite' => absint($atributos['limite']),
            'destacados' => true,
            'orderby' => 'rating',
        ]);

        $artistas = $resultado['artistas'];

        ob_start();
        include dirname(__FILE__) . '/views/listado.php';
        return ob_get_clean();
    }
}
