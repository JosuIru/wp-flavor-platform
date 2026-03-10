<?php
/**
 * Mi Red Social - Interfaz Unificada de Módulos Sociales
 *
 * Unifica todos los módulos sociales del plugin en una interfaz tipo red social moderna.
 * Combina contenido de: Red Social, Podcast, Multimedia, Radio, Comunidades, Foros,
 * Colectivos, Círculos de Cuidados, Ayuda Vecinal, Chat Grupos y Chat Interno.
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Mi_Red_Social {

    /**
     * Instancia singleton
     * @var Flavor_Mi_Red_Social|null
     */
    private static $instance = null;

    /**
     * Versión del módulo
     */
    const VERSION = '1.0.0';

    /**
     * Prefijo base para URLs
     */
    const BASE_PATH = 'mi-portal/mi-red';

    /**
     * Usuario actual
     * @var int
     */
    private $current_user_id = 0;

    /**
     * Vista actual
     * @var string
     */
    private $current_view = 'feed';

    /**
     * Parámetros de la URL actual
     * @var array
     */
    private $current_params = [];

    /**
     * Tipos de contenido soportados
     * @var array
     */
    private $content_types = [
        'publicacion' => [
            'label' => 'Publicaciones',
            'icon' => '📝',
            'module' => 'red_social',
            'color' => '#ec4899',
        ],
        'podcast' => [
            'label' => 'Podcasts',
            'icon' => '🎙️',
            'module' => 'podcast',
            'color' => '#8b5cf6',
        ],
        'video' => [
            'label' => 'Videos',
            'icon' => '🎬',
            'module' => 'multimedia',
            'color' => '#ef4444',
        ],
        'imagen' => [
            'label' => 'Imágenes',
            'icon' => '📷',
            'module' => 'multimedia',
            'color' => '#3b82f6',
        ],
        'audio' => [
            'label' => 'Audios',
            'icon' => '🎵',
            'module' => 'multimedia',
            'color' => '#10b981',
        ],
        'foro' => [
            'label' => 'Foros',
            'icon' => '💬',
            'module' => 'foros',
            'color' => '#f59e0b',
        ],
        'ayuda' => [
            'label' => 'Ayuda Vecinal',
            'icon' => '🤝',
            'module' => 'ayuda_vecinal',
            'color' => '#06b6d4',
        ],
        'comunidad' => [
            'label' => 'Comunidades',
            'icon' => '👥',
            'module' => 'comunidades',
            'color' => '#84cc16',
        ],
        'radio' => [
            'label' => 'Radio',
            'icon' => '📻',
            'module' => 'radio',
            'color' => '#f97316',
        ],
        'colectivo' => [
            'label' => 'Colectivos',
            'icon' => '✊',
            'module' => 'colectivos',
            'color' => '#7c3aed',
        ],
        'circulo' => [
            'label' => 'Círculos de Cuidados',
            'icon' => '💚',
            'module' => 'circulos_cuidados',
            'color' => '#059669',
        ],
    ];

    /**
     * Vistas disponibles
     * @var array
     */
    private $views = [
        'feed' => [
            'slug' => '',
            'label' => 'Feed',
            'icon' => '🏠',
            'template' => 'feed.php',
        ],
        'publicar' => [
            'slug' => 'publicar',
            'label' => 'Publicar',
            'icon' => '➕',
            'template' => 'publicar.php',
        ],
        'explorar' => [
            'slug' => 'explorar',
            'label' => 'Explorar',
            'icon' => '🔍',
            'template' => 'explorar.php',
        ],
        'mensajes' => [
            'slug' => 'mensajes',
            'label' => 'Mensajes',
            'icon' => '💬',
            'template' => 'mensajes.php',
        ],
        'notificaciones' => [
            'slug' => 'notificaciones',
            'label' => 'Notificaciones',
            'icon' => '🔔',
            'template' => 'notificaciones.php',
        ],
        'multimedia' => [
            'slug' => 'multimedia',
            'label' => 'Multimedia',
            'icon' => '📸',
            'template' => 'multimedia.php',
        ],
        'perfil' => [
            'slug' => 'perfil',
            'label' => 'Perfil',
            'icon' => '👤',
            'template' => 'perfil.php',
        ],
        'buscar' => [
            'slug' => 'buscar',
            'label' => 'Buscar',
            'icon' => '🔎',
            'template' => 'buscar.php',
        ],
    ];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Mi_Red_Social
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->current_user_id = get_current_user_id();

        // Registrar AJAX handlers
        add_action('wp_ajax_mi_red_cargar_feed', [$this, 'ajax_cargar_feed']);
        add_action('wp_ajax_mi_red_crear_publicacion', [$this, 'ajax_crear_publicacion']);
        add_action('wp_ajax_mi_red_toggle_like', [$this, 'ajax_toggle_like']);
        add_action('wp_ajax_mi_red_crear_comentario', [$this, 'ajax_crear_comentario']);
        add_action('wp_ajax_mi_red_obtener_comentarios', [$this, 'ajax_obtener_comentarios']);
        add_action('wp_ajax_mi_red_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_mi_red_obtener_notificaciones', [$this, 'ajax_obtener_notificaciones']);
        add_action('wp_ajax_mi_red_marcar_notificacion', [$this, 'ajax_marcar_notificacion']);
        add_action('wp_ajax_mi_red_save_push_subscription', [$this, 'ajax_save_push_subscription']);

        // Registrar REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Hooks para invalidar caché cuando se crea contenido en otros módulos
        add_action('flavor_publicacion_creada', [$this, 'on_contenido_creado'], 10, 2);
        add_action('flavor_podcast_episodio_creado', [$this, 'on_contenido_creado'], 10, 2);
        add_action('flavor_multimedia_creado', [$this, 'on_contenido_creado'], 10, 2);
        add_action('flavor_foro_tema_creado', [$this, 'on_contenido_creado'], 10, 2);
        add_action('flavor_ayuda_solicitud_creada', [$this, 'on_contenido_creado'], 10, 2);
    }

    /**
     * Callback cuando se crea contenido en cualquier módulo
     *
     * @param int $item_id ID del item creado
     * @param int $autor_id ID del autor
     */
    public function on_contenido_creado($item_id, $autor_id = 0) {
        if ($autor_id > 0) {
            $this->invalidar_cache_feed($autor_id);
        } else {
            // Si no hay autor específico, invalidar todo
            $this->invalidar_cache_feed(0);
        }
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $tabla Nombre completo de la tabla (con prefijo)
     * @return bool
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla
        ));
        return $resultado === $tabla;
    }

    /**
     * Verifica si una columna existe en una tabla.
     *
     * @param string $tabla
     * @param string $columna
     * @return bool
     */
    private function columna_existe($tabla, $columna) {
        global $wpdb;

        if (!$this->tabla_existe($tabla)) {
            return false;
        }

        $resultado = $wpdb->get_var($wpdb->prepare(
            "SHOW COLUMNS FROM {$tabla} LIKE %s",
            $columna
        ));

        return $resultado === $columna;
    }

    /**
     * Obtiene la primera columna existente de una lista de candidatas.
     *
     * @param string $tabla
     * @param array  $columnas
     * @return string
     */
    private function obtener_primera_columna_existente($tabla, array $columnas) {
        foreach ($columnas as $columna) {
            if ($this->columna_existe($tabla, $columna)) {
                return $columna;
            }
        }

        return '';
    }

    /**
     * Invalida el caché del feed para un usuario
     *
     * @param int $usuario_id ID del usuario (0 para invalidar todos)
     */
    public function invalidar_cache_feed($usuario_id = 0) {
        global $wpdb;

        // Tipos de filtro posibles
        $tipos = ['todos', 'publicacion', 'podcast', 'video', 'imagen', 'audio', 'radio', 'comunidad', 'foro', 'ayuda'];
        $limites = [10, 20, 50];
        $offsets = [0, 20, 40, 60];

        if ($usuario_id > 0) {
            // Invalidar solo las claves del usuario específico
            foreach ($tipos as $tipo) {
                foreach ($offsets as $offset) {
                    foreach ($limites as $limite) {
                        delete_transient("mi_red_feed_{$usuario_id}_{$tipo}_{$offset}_{$limite}");
                    }
                }
            }
        } else {
            // Invalidar todos los cachés de feed usando SQL directo
            $wpdb->query(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mi_red_feed_%' OR option_name LIKE '_transient_timeout_mi_red_feed_%'"
            );
        }
    }

    /**
     * Registra rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('flavor-chat/v1', '/mi-red/feed', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_feed'],
            'permission_callback' => 'is_user_logged_in',
            'args' => [
                'limite' => [
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                ],
                'offset' => [
                    'default' => 0,
                    'sanitize_callback' => 'absint',
                ],
                'tipo' => [
                    'default' => 'todos',
                    'sanitize_callback' => 'sanitize_key',
                ],
            ],
        ]);

        register_rest_route('flavor-chat/v1', '/mi-red/publicar', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_crear_publicacion'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor-chat/v1', '/mi-red/perfil/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_perfil'],
            'permission_callback' => 'is_user_logged_in',
        ]);

        register_rest_route('flavor-chat/v1', '/mi-red/trending', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_obtener_trending'],
            'permission_callback' => 'is_user_logged_in',
        ]);
    }

    /**
     * Renderiza la vista actual
     *
     * @param string $view Vista a renderizar
     * @param array $params Parámetros adicionales
     */
    public function render($view = 'feed', $params = []) {
        $this->current_view = $view;
        $this->current_params = $params;

        // Verificar autenticación
        if (!is_user_logged_in()) {
            $this->render_login_required();
            return;
        }

        // Encolar assets
        $this->enqueue_assets();

        // Obtener datos comunes
        $datos_comunes = $this->get_common_data();

        // Incluir template base
        $this->render_layout($datos_comunes);
    }

    /**
     * Encola assets CSS y JS
     */
    public function enqueue_assets() {
        $version = defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : self::VERSION;
        $base_url = plugin_dir_url(dirname(dirname(__FILE__)));

        // Precarga de CSS crítico
        add_action('wp_head', function() use ($base_url, $version) {
            echo '<link rel="preload" href="' . esc_url($base_url . 'assets/css/modules/mi-red-social.css') . '?ver=' . esc_attr($version) . '" as="style">' . "\n";
        }, 1);

        // CSS principal
        wp_enqueue_style(
            'flavor-mi-red-social',
            $base_url . 'assets/css/modules/mi-red-social.css',
            ['flavor-portal'],
            $version
        );

        // JS principal (defer para no bloquear renderizado)
        wp_enqueue_script(
            'flavor-mi-red-social',
            $base_url . 'assets/js/mi-red-social.js',
            ['jquery'],
            $version,
            true
        );

        // Agregar atributo defer al script
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'flavor-mi-red-social') {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);

        // Localizar script
        wp_localize_script('flavor-mi-red-social', 'flavorMiRed', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor-chat/v1/mi-red/'),
            'nonce' => wp_create_nonce('mi_red_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'userId' => $this->current_user_id,
            'i18n' => [
                'cargando' => __('Cargando...', 'flavor-chat-ia'),
                'error' => __('Error al cargar', 'flavor-chat-ia'),
                'sinResultados' => __('No hay resultados', 'flavor-chat-ia'),
                'publicado' => __('Publicado correctamente', 'flavor-chat-ia'),
                'comentarioEnviado' => __('Comentario enviado', 'flavor-chat-ia'),
                'meGusta' => __('Me gusta', 'flavor-chat-ia'),
                'comentar' => __('Comentar', 'flavor-chat-ia'),
                'compartir' => __('Compartir', 'flavor-chat-ia'),
                'guardar' => __('Guardar', 'flavor-chat-ia'),
                'verMas' => __('Ver más', 'flavor-chat-ia'),
                'cargarMas' => __('Cargar más', 'flavor-chat-ia'),
            ],
            'contentTypes' => $this->content_types,
        ]);
    }

    /**
     * Obtiene datos comunes para todas las vistas
     *
     * @return array
     */
    private function get_common_data() {
        $usuario_id = $this->current_user_id;
        $usuario = get_userdata($usuario_id);

        return [
            'usuario' => [
                'id' => $usuario_id,
                'nombre' => $usuario->display_name,
                'avatar' => get_avatar_url($usuario_id, ['size' => 96]),
                'perfil_url' => home_url('/mi-portal/mi-red/perfil/'),
            ],
            'vista_actual' => $this->current_view,
            'vistas' => $this->views,
            'content_types' => $this->content_types,
            'notificaciones_no_leidas' => $this->get_unread_notifications_count($usuario_id),
            'mensajes_no_leidos' => $this->get_unread_messages_count($usuario_id),
            'base_url' => home_url('/mi-portal/mi-red/'),
        ];
    }

    /**
     * Renderiza el layout principal con la vista actual
     *
     * @param array $datos Datos comunes
     */
    private function render_layout($datos) {
        // Variables disponibles en los templates
        $usuario = $datos['usuario'];
        $vista_actual = $datos['vista_actual'];
        $vistas = $datos['vistas'];
        $content_types = $datos['content_types'];
        $notificaciones_no_leidas = $datos['notificaciones_no_leidas'];
        $mensajes_no_leidos = $datos['mensajes_no_leidos'];
        $base_url = $datos['base_url'];

        // Obtener datos específicos de la vista
        $datos_vista = $this->get_view_data($vista_actual);

        // Ruta del template
        $template_dir = FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/';
        $template_file = $this->views[$vista_actual]['template'] ?? 'feed.php';
        $template_path = $template_dir . $template_file;

        // Verificar que existe el template
        if (!file_exists($template_path)) {
            $template_path = $template_dir . 'feed.php';
        }

        // Incluir el layout base con el contenido
        include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/layout.php';
    }

    /**
     * Obtiene datos específicos para cada vista
     *
     * @param string $vista
     * @return array
     */
    private function get_view_data($vista) {
        switch ($vista) {
            case 'feed':
                return [
                    'feed' => $this->obtener_feed_unificado($this->current_user_id, 20, 0),
                    'trending' => $this->obtener_trending(),
                    'sugerencias' => $this->obtener_sugerencias_usuarios($this->current_user_id, 5),
                ];

            case 'explorar':
                return [
                    'categorias' => $this->content_types,
                    'destacados' => $this->obtener_contenido_destacado(12),
                    'populares' => $this->obtener_usuarios_populares(10),
                ];

            case 'mensajes':
                return [
                    'conversaciones' => $this->obtener_conversaciones($this->current_user_id),
                    'grupos' => $this->obtener_grupos_chat($this->current_user_id),
                ];

            case 'notificaciones':
                return [
                    'notificaciones' => $this->obtener_notificaciones($this->current_user_id, 30),
                ];

            case 'multimedia':
                return [
                    'galeria' => $this->obtener_galeria_usuario($this->current_user_id),
                    'albumes' => $this->obtener_albumes_usuario($this->current_user_id),
                ];

            case 'perfil':
                $perfil_id = $this->current_params['id'] ?? $this->current_user_id;
                return [
                    'perfil' => $this->obtener_perfil_usuario($perfil_id),
                    'publicaciones' => $this->obtener_publicaciones_usuario($perfil_id, 20),
                    'estadisticas' => $this->obtener_estadisticas_usuario($perfil_id),
                ];

            case 'buscar':
                $termino = $this->current_params['q'] ?? '';
                return [
                    'termino' => $termino,
                    'resultados' => !empty($termino) ? $this->buscar_contenido($termino) : [],
                ];

            case 'publicar':
                return [
                    'tipos_permitidos' => $this->get_allowed_post_types(),
                    'comunidades' => $this->obtener_comunidades_usuario($this->current_user_id),
                ];

            default:
                return [];
        }
    }

    /**
     * Obtiene el feed unificado combinando contenido de todos los módulos
     *
     * @param int $usuario_id
     * @param int $limite
     * @param int $offset
     * @param string $tipo_filtro Filtro por tipo de contenido (todos, publicacion, podcast, etc.)
     * @return array
     */
    public function obtener_feed_unificado($usuario_id, $limite = 20, $offset = 0, $tipo_filtro = 'todos') {
        $items = [];

        // Cache key
        $cache_key = "mi_red_feed_{$usuario_id}_{$tipo_filtro}_{$offset}_{$limite}";
        $cached = get_transient($cache_key);

        if ($cached !== false && !defined('FLAVOR_CHAT_IA_DEBUG')) {
            return $cached;
        }

        // 1. Red Social - publicaciones
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'publicacion') {
            $items = array_merge($items, $this->get_red_social_items($usuario_id, $limite * 2));
        }

        // 2. Podcast - episodios recientes
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'podcast') {
            $items = array_merge($items, $this->get_podcast_items($usuario_id, $limite));
        }

        // 3. Multimedia - contenido nuevo
        if ($tipo_filtro === 'todos' || in_array($tipo_filtro, ['video', 'imagen', 'audio'])) {
            $items = array_merge($items, $this->get_multimedia_items($usuario_id, $limite, $tipo_filtro));
        }

        // 4. Radio - programas/podcasts
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'radio') {
            $items = array_merge($items, $this->get_radio_items($usuario_id, $limite));
        }

        // 5. Comunidades - actividad de comunidades seguidas
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'comunidad') {
            $items = array_merge($items, $this->get_comunidades_items($usuario_id, $limite));
        }

        // 6. Foros - temas con actividad
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'foro') {
            $items = array_merge($items, $this->get_foros_items($usuario_id, $limite));
        }

        // 7. Ayuda Vecinal - solicitudes cercanas
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'ayuda') {
            $items = array_merge($items, $this->get_ayuda_items($usuario_id, $limite));
        }

        // 8. Colectivos - proyectos y asambleas de mis colectivos
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'colectivo') {
            $items = array_merge($items, $this->get_colectivos_items($usuario_id, $limite));
        }

        // 9. Círculos de Cuidados - actividad de mis círculos
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'circulo') {
            $items = array_merge($items, $this->get_circulos_items($usuario_id, $limite));
        }

        // 10. Eventos - próximos encuentros y mis inscripciones
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'evento') {
            $items = array_merge($items, $this->get_eventos_items($usuario_id, $limite));
        }

        // 11. Grupos de consumo - grupos y ciclos activos
        if ($tipo_filtro === 'todos' || $tipo_filtro === 'grupo_consumo') {
            $items = array_merge($items, $this->get_grupos_consumo_items($usuario_id, $limite));
        }

        // Ordenar por fecha descendente
        usort($items, function($a, $b) {
            return strtotime($b['fecha']) - strtotime($a['fecha']);
        });

        // Aplicar diversidad (no más de 3 consecutivos del mismo tipo/autor)
        $items = $this->aplicar_diversidad_feed($items);

        // Aplicar offset y límite
        $items = array_slice($items, $offset, $limite);

        // Guardar en caché (5 minutos)
        set_transient($cache_key, $items, 5 * MINUTE_IN_SECONDS);

        return $items;
    }

    /**
     * Aplica diversidad al feed para evitar contenido repetitivo
     *
     * @param array $items
     * @param int $max_consecutivos
     * @return array
     */
    private function aplicar_diversidad_feed($items, $max_consecutivos = 3) {
        $resultado = [];
        $contadores = [
            'tipo' => [],
            'autor' => [],
        ];

        foreach ($items as $item) {
            $tipo = $item['tipo'] ?? 'otro';
            $autor_id = $item['autor']['id'] ?? 0;

            // Resetear contadores si cambia el tipo/autor
            if (!isset($contadores['tipo'][$tipo])) {
                $contadores['tipo'][$tipo] = 0;
            }
            if (!isset($contadores['autor'][$autor_id])) {
                $contadores['autor'][$autor_id] = 0;
            }

            // Verificar límites consecutivos
            if ($contadores['tipo'][$tipo] < $max_consecutivos &&
                $contadores['autor'][$autor_id] < $max_consecutivos) {
                $resultado[] = $item;
                $contadores['tipo'][$tipo]++;
                $contadores['autor'][$autor_id]++;

                // Resetear otros contadores
                foreach ($contadores['tipo'] as $k => $v) {
                    if ($k !== $tipo) {
                        $contadores['tipo'][$k] = 0;
                    }
                }
            }
        }

        return $resultado;
    }

    /**
     * Obtiene items de Red Social
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_red_social_items($usuario_id, $limite = 20) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';

        // Verificar que la tabla existe
        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name as autor_nombre
             FROM {$tabla} p
             LEFT JOIN {$wpdb->users} u ON p.autor_id = u.ID
             WHERE p.estado = 'publicado'
             AND p.visibilidad IN ('publica', 'comunidad')
             ORDER BY p.fecha_publicacion DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($publicaciones as $pub) {
            $items[] = $this->normalizar_item([
                'id' => $pub->id,
                'tipo' => 'publicacion',
                'origen' => 'red-social',
                'autor' => [
                    'id' => $pub->autor_id,
                    'nombre' => $pub->autor_nombre,
                    'avatar' => get_avatar_url($pub->autor_id, ['size' => 48]),
                ],
                'contenido' => [
                    'texto' => $pub->contenido,
                    'tipo_media' => $pub->tipo,
                ],
                'multimedia' => $this->parse_adjuntos($pub->adjuntos),
                'interacciones' => [
                    'likes' => (int) $pub->me_gusta,
                    'comentarios' => (int) $pub->comentarios,
                    'compartidos' => (int) $pub->compartidos,
                    'vistas' => (int) $pub->vistas,
                ],
                'fecha' => $pub->fecha_publicacion,
                'url' => home_url('/red-social/ver/' . $pub->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Podcast
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_podcast_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_podcast_episodios';
        $tabla_series = $wpdb->prefix . 'flavor_podcast_series';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $autor_col = $this->obtener_primera_columna_existente($tabla, ['autor_id', 'usuario_id']);
        $fecha_col = $this->obtener_primera_columna_existente($tabla, ['fecha_publicacion', 'fecha_creacion', 'created_at']);
        if ($fecha_col === '') {
            $fecha_col = 'id';
        }

        $join_series = $this->tabla_existe($tabla_series) ? " LEFT JOIN {$tabla_series} s ON e.serie_id = s.id" : '';
        $select_serie = $this->tabla_existe($tabla_series) ? ", s.titulo as serie_titulo" : ", '' as serie_titulo";
        $join_autor = $autor_col !== '' ? " LEFT JOIN {$wpdb->users} u ON e.{$autor_col} = u.ID" : '';
        $select_autor = $autor_col !== '' ? ", e.{$autor_col} as autor_id, u.display_name as autor_nombre" : ", 0 as autor_id, '' as autor_nombre";

        $episodios = $wpdb->get_results($wpdb->prepare(
            "SELECT e.* {$select_serie} {$select_autor}
             FROM {$tabla} e
             {$join_series}
             {$join_autor}
             WHERE e.estado = 'publicado'
             ORDER BY e.{$fecha_col} DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($episodios as $ep) {
            $items[] = $this->normalizar_item([
                'id' => $ep->id,
                'tipo' => 'podcast',
                'origen' => 'podcast',
                'autor' => [
                    'id' => $ep->autor_id,
                    'nombre' => $ep->autor_nombre,
                    'avatar' => get_avatar_url($ep->autor_id, ['size' => 48]),
                ],
                'contenido' => [
                    'titulo' => $ep->titulo,
                    'texto' => $ep->descripcion,
                    'duracion' => $ep->duracion ?? 0,
                    'serie' => $ep->serie_titulo,
                ],
                'multimedia' => [
                    [
                        'tipo' => 'audio',
                        'url' => $ep->archivo_url ?? '',
                        'thumbnail' => $ep->imagen_url ?? '',
                    ],
                ],
                'interacciones' => [
                    'reproducciones' => (int) ($ep->reproducciones ?? 0),
                    'likes' => (int) ($ep->me_gusta ?? 0),
                    'comentarios' => (int) ($ep->comentarios ?? 0),
                ],
                'fecha' => $ep->{$fecha_col} ?? '',
                'url' => home_url('/podcast/ver/' . $ep->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Multimedia
     *
     * @param int $usuario_id
     * @param int $limite
     * @param string $tipo_filtro
     * @return array
     */
    private function get_multimedia_items($usuario_id, $limite = 10, $tipo_filtro = 'todos') {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_multimedia';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $where_tipo = '';
        if ($tipo_filtro !== 'todos' && in_array($tipo_filtro, ['video', 'imagen', 'audio'])) {
            $where_tipo = $wpdb->prepare(" AND m.tipo = %s", $tipo_filtro);
        }

        $autor_col = $this->obtener_primera_columna_existente($tabla, ['usuario_id', 'autor_id', 'user_id']);
        $fecha_col = $this->obtener_primera_columna_existente($tabla, ['fecha_subida', 'fecha_creacion', 'created_at']);
        if ($fecha_col === '') {
            $fecha_col = 'id';
        }

        $join_autor = $autor_col !== '' ? " LEFT JOIN {$wpdb->users} u ON m.{$autor_col} = u.ID" : '';
        $select_autor = $autor_col !== '' ? ", m.{$autor_col} as autor_id, u.display_name as autor_nombre" : ", 0 as autor_id, '' as autor_nombre";

        $elementos = $wpdb->get_results($wpdb->prepare(
            "SELECT m.* {$select_autor}
             FROM {$tabla} m
             {$join_autor}
             WHERE m.estado = 'publicado' {$where_tipo}
             ORDER BY m.{$fecha_col} DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($elementos as $elem) {
            $items[] = $this->normalizar_item([
                'id' => $elem->id,
                'tipo' => $elem->tipo,
                'origen' => 'multimedia',
                'autor' => [
                    'id' => $elem->autor_id ?? 0,
                    'nombre' => $elem->autor_nombre,
                    'avatar' => !empty($elem->autor_id) ? get_avatar_url($elem->autor_id, ['size' => 48]) : '',
                ],
                'contenido' => [
                    'titulo' => $elem->titulo ?? '',
                    'texto' => $elem->descripcion ?? '',
                ],
                'multimedia' => [
                    [
                        'tipo' => $elem->tipo,
                        'url' => $elem->url ?? '',
                        'thumbnail' => $elem->thumbnail_url ?? '',
                    ],
                ],
                'interacciones' => [
                    'vistas' => (int) ($elem->vistas ?? 0),
                    'likes' => (int) ($elem->me_gusta ?? 0),
                    'comentarios' => (int) ($elem->comentarios ?? 0),
                ],
                'fecha' => $elem->{$fecha_col} ?? '',
                'url' => home_url('/multimedia/ver/' . $elem->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Radio
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_radio_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_radio_programas';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $autor_col = $this->obtener_primera_columna_existente($tabla, ['conductor_id', 'autor_id', 'usuario_id']);
        $fecha_col = $this->obtener_primera_columna_existente($tabla, ['fecha_emision', 'fecha_creacion', 'created_at']);
        if ($fecha_col === '') {
            $fecha_col = 'id';
        }

        $join_autor = $autor_col !== '' ? " LEFT JOIN {$wpdb->users} u ON p.{$autor_col} = u.ID" : '';
        $select_autor = $autor_col !== '' ? ", p.{$autor_col} as autor_id, u.display_name as autor_nombre" : ", 0 as autor_id, '' as autor_nombre";

        $programas = $wpdb->get_results($wpdb->prepare(
            "SELECT p.* {$select_autor}
             FROM {$tabla} p
             {$join_autor}
             WHERE p.estado = 'activo'
             ORDER BY p.{$fecha_col} DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($programas as $prog) {
            $items[] = $this->normalizar_item([
                'id' => $prog->id,
                'tipo' => 'radio',
                'origen' => 'radio',
                'autor' => [
                    'id' => $prog->autor_id ?? 0,
                    'nombre' => $prog->autor_nombre ?? __('Radio Comunitaria', 'flavor-chat-ia'),
                    'avatar' => !empty($prog->autor_id) ? get_avatar_url($prog->autor_id, ['size' => 48]) : '',
                ],
                'contenido' => [
                    'titulo' => $prog->titulo ?? '',
                    'texto' => $prog->descripcion ?? '',
                    'duracion' => $prog->duracion ?? 0,
                ],
                'multimedia' => [
                    [
                        'tipo' => 'audio',
                        'url' => $prog->archivo_url ?? '',
                        'thumbnail' => $prog->imagen_url ?? '',
                    ],
                ],
                'interacciones' => [
                    'oyentes' => (int) ($prog->oyentes ?? 0),
                    'likes' => (int) ($prog->me_gusta ?? 0),
                ],
                'fecha' => $prog->{$fecha_col} ?? '',
                'url' => home_url('/radio/programa/' . $prog->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Comunidades
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_comunidades_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        $tabla_publicaciones = $wpdb->prefix . 'flavor_comunidades_publicaciones';

        if (!$this->tabla_existe($tabla_publicaciones)) {
            return [];
        }

        $select_icono = $this->columna_existe($tabla_comunidades, 'icono')
            ? ", c.icono as comunidad_icono"
            : ", '' as comunidad_icono";

        // Obtener publicaciones de comunidades a las que pertenece el usuario
        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, c.nombre as comunidad_nombre {$select_icono},
                    u.display_name as autor_nombre
             FROM {$tabla_publicaciones} p
             LEFT JOIN {$tabla_comunidades} c ON p.comunidad_id = c.id
             LEFT JOIN {$wpdb->users} u ON p.autor_id = u.ID
             WHERE p.estado = 'publicado'
             ORDER BY p.fecha_creacion DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($publicaciones as $pub) {
            $items[] = $this->normalizar_item([
                'id' => $pub->id,
                'tipo' => 'comunidad',
                'origen' => 'comunidades',
                'autor' => [
                    'id' => $pub->autor_id,
                    'nombre' => $pub->autor_nombre,
                    'avatar' => get_avatar_url($pub->autor_id, ['size' => 48]),
                ],
                'contenido' => [
                    'texto' => $pub->contenido ?? '',
                ],
                'contexto' => [
                    'comunidad_id' => $pub->comunidad_id,
                    'comunidad_nombre' => $pub->comunidad_nombre,
                    'comunidad_icono' => $pub->comunidad_icono,
                ],
                'multimedia' => $this->parse_adjuntos($pub->adjuntos ?? ''),
                'interacciones' => [
                    'likes' => (int) ($pub->me_gusta ?? 0),
                    'comentarios' => (int) ($pub->comentarios ?? 0),
                ],
                'fecha' => $pub->fecha_creacion,
                'url' => home_url('/comunidades/' . $pub->comunidad_id . '/publicacion/' . $pub->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Foros
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_foros_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_foros_temas';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $foro_col = $this->obtener_primera_columna_existente($tabla, ['foro_id', 'categoria_id']);
        $join_foro = ($foro_col !== '' && $this->tabla_existe($tabla_foros))
            ? " LEFT JOIN {$tabla_foros} f ON t.{$foro_col} = f.id"
            : '';
        $select_foro = $join_foro !== '' ? ", f.nombre as foro_nombre" : ", '' as foro_nombre";

        $temas = $wpdb->get_results($wpdb->prepare(
            "SELECT t.* {$select_foro}, u.display_name as autor_nombre
             FROM {$tabla} t
             {$join_foro}
             LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
             WHERE t.estado = 'activo'
             ORDER BY t.ultima_actividad DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($temas as $tema) {
            $items[] = $this->normalizar_item([
                'id' => $tema->id,
                'tipo' => 'foro',
                'origen' => 'foros',
                'autor' => [
                    'id' => $tema->autor_id,
                    'nombre' => $tema->autor_nombre,
                    'avatar' => get_avatar_url($tema->autor_id, ['size' => 48]),
                ],
                'contenido' => [
                    'titulo' => $tema->titulo,
                    'texto' => wp_trim_words($tema->contenido ?? '', 50),
                ],
                'contexto' => [
                    'foro_nombre' => $tema->foro_nombre,
                ],
                'interacciones' => [
                    'respuestas' => (int) ($tema->respuestas ?? 0),
                    'vistas' => (int) ($tema->vistas ?? 0),
                ],
                'fecha' => $tema->ultima_actividad ?? $tema->fecha_creacion,
                'url' => home_url('/foros/tema/' . $tema->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Ayuda Vecinal
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_ayuda_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_ayuda_vecinal';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $autor_col = $this->obtener_primera_columna_existente($tabla, ['usuario_id', 'autor_id', 'solicitante_id']);
        $fecha_col = $this->obtener_primera_columna_existente($tabla, ['fecha_creacion', 'created_at', 'fecha']);
        if ($fecha_col === '') {
            $fecha_col = 'id';
        }

        $join_autor = $autor_col !== '' ? " LEFT JOIN {$wpdb->users} u ON a.{$autor_col} = u.ID" : '';
        $select_autor = $autor_col !== '' ? ", a.{$autor_col} as autor_id, u.display_name as autor_nombre" : ", 0 as autor_id, '' as autor_nombre";

        $solicitudes = $wpdb->get_results($wpdb->prepare(
            "SELECT a.* {$select_autor}
             FROM {$tabla} a
             {$join_autor}
             WHERE a.estado IN ('abierta', 'en_proceso')
             ORDER BY a.{$fecha_col} DESC
             LIMIT %d",
            $limite
        ));

        $items = [];
        foreach ($solicitudes as $sol) {
            $items[] = $this->normalizar_item([
                'id' => $sol->id,
                'tipo' => 'ayuda',
                'origen' => 'ayuda-vecinal',
                'autor' => [
                    'id' => $sol->autor_id ?? 0,
                    'nombre' => $sol->autor_nombre,
                    'avatar' => !empty($sol->autor_id) ? get_avatar_url($sol->autor_id, ['size' => 48]) : '',
                ],
                'contenido' => [
                    'titulo' => $sol->titulo ?? '',
                    'texto' => $sol->descripcion ?? '',
                    'categoria' => $sol->categoria ?? '',
                    'urgencia' => $sol->urgencia ?? 'normal',
                ],
                'contexto' => [
                    'tipo_ayuda' => $sol->tipo ?? 'solicitud',
                    'estado' => $sol->estado,
                ],
                'interacciones' => [
                    'ofertas' => (int) ($sol->ofertas ?? 0),
                    'comentarios' => (int) ($sol->comentarios ?? 0),
                ],
                'fecha' => $sol->{$fecha_col} ?? '',
                'url' => home_url('/ayuda-vecinal/' . $sol->id . '/'),
            ]);
        }

        return $items;
    }

    /**
     * Obtiene items de Colectivos (proyectos y asambleas)
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite     Límite de items
     * @return array Items normalizados
     */
    private function get_colectivos_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        $tabla_miembros = $wpdb->prefix . 'flavor_colectivos_miembros';
        $tabla_proyectos = $wpdb->prefix . 'flavor_colectivos_proyectos';
        $tabla_asambleas = $wpdb->prefix . 'flavor_colectivos_asambleas';

        if (!$this->tabla_existe($tabla_colectivos)) {
            return [];
        }

        $items = [];

        // Obtener colectivos del usuario
        $mis_colectivos = $wpdb->get_col($wpdb->prepare(
            "SELECT colectivo_id FROM {$tabla_miembros}
             WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (empty($mis_colectivos)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($mis_colectivos), '%d'));

        // Proyectos recientes de mis colectivos
        if ($this->tabla_existe($tabla_proyectos)) {
            $proyectos = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.nombre as colectivo_nombre, u.display_name as autor_nombre
                 FROM {$tabla_proyectos} p
                 LEFT JOIN {$tabla_colectivos} c ON p.colectivo_id = c.id
                 LEFT JOIN {$wpdb->users} u ON p.creador_id = u.ID
                 WHERE p.colectivo_id IN ({$placeholders})
                 AND p.estado != 'borrador'
                 ORDER BY p.fecha_creacion DESC
                 LIMIT %d",
                array_merge($mis_colectivos, [$limite])
            ));

            foreach ($proyectos as $proy) {
                $items[] = $this->normalizar_item([
                    'id' => 'proyecto_' . $proy->id,
                    'tipo' => 'colectivo',
                    'origen' => 'colectivo-proyecto',
                    'autor' => [
                        'id' => $proy->creador_id,
                        'nombre' => $proy->autor_nombre ?? __('Colectivo', 'flavor-chat-ia'),
                        'avatar' => get_avatar_url($proy->creador_id, ['size' => 48]),
                    ],
                    'contenido' => [
                        'titulo' => $proy->nombre ?? '',
                        'texto' => $proy->descripcion ?? '',
                        'estado' => $proy->estado ?? 'activo',
                    ],
                    'contexto' => [
                        'colectivo' => $proy->colectivo_nombre,
                        'colectivo_id' => $proy->colectivo_id,
                        'subtipo' => 'proyecto',
                    ],
                    'interacciones' => [
                        'participantes' => (int) ($proy->participantes ?? 0),
                    ],
                    'fecha' => $proy->fecha_creacion,
                    'url' => home_url('/colectivos/' . $proy->colectivo_id . '/proyectos/' . $proy->id . '/'),
                ]);
            }
        }

        // Asambleas próximas de mis colectivos
        if ($this->tabla_existe($tabla_asambleas)) {
            $asambleas = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.nombre as colectivo_nombre, u.display_name as convocante_nombre
                 FROM {$tabla_asambleas} a
                 LEFT JOIN {$tabla_colectivos} c ON a.colectivo_id = c.id
                 LEFT JOIN {$wpdb->users} u ON a.convocante_id = u.ID
                 WHERE a.colectivo_id IN ({$placeholders})
                 AND a.fecha_hora >= NOW()
                 ORDER BY a.fecha_hora ASC
                 LIMIT %d",
                array_merge($mis_colectivos, [$limite])
            ));

            foreach ($asambleas as $asam) {
                $items[] = $this->normalizar_item([
                    'id' => 'asamblea_' . $asam->id,
                    'tipo' => 'colectivo',
                    'origen' => 'colectivo-asamblea',
                    'autor' => [
                        'id' => $asam->convocante_id ?? 0,
                        'nombre' => $asam->convocante_nombre ?? __('Colectivo', 'flavor-chat-ia'),
                        'avatar' => get_avatar_url($asam->convocante_id ?? 0, ['size' => 48]),
                    ],
                    'contenido' => [
                        'titulo' => sprintf(__('Asamblea: %s', 'flavor-chat-ia'), $asam->titulo ?? ''),
                        'texto' => $asam->descripcion ?? '',
                        'fecha_evento' => $asam->fecha_hora,
                        'lugar' => $asam->lugar ?? '',
                    ],
                    'contexto' => [
                        'colectivo' => $asam->colectivo_nombre,
                        'colectivo_id' => $asam->colectivo_id,
                        'subtipo' => 'asamblea',
                    ],
                    'interacciones' => [
                        'confirmados' => (int) ($asam->confirmados ?? 0),
                    ],
                    'fecha' => $asam->fecha_creacion ?? $asam->fecha_hora,
                    'url' => home_url('/colectivos/' . $asam->colectivo_id . '/asambleas/' . $asam->id . '/'),
                ]);
            }
        }

        return $items;
    }

    /**
     * Obtiene items de Círculos de Cuidados
     *
     * @param int $usuario_id ID del usuario
     * @param int $limite     Límite de items
     * @return array Items normalizados
     */
    private function get_circulos_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla_circulos = $wpdb->prefix . 'flavor_circulos_cuidados';
        $tabla_miembros = $wpdb->prefix . 'flavor_circulos_miembros';
        $tabla_actividades = $wpdb->prefix . 'flavor_circulos_actividades';

        if (!$this->tabla_existe($tabla_circulos)) {
            return [];
        }

        $items = [];

        // Obtener círculos del usuario
        $mis_circulos = [];
        if ($this->tabla_existe($tabla_miembros)) {
            $mis_circulos = $wpdb->get_col($wpdb->prepare(
                "SELECT circulo_id FROM {$tabla_miembros}
                 WHERE usuario_id = %d AND estado = 'activo'",
                $usuario_id
            ));
        }

        // Si el usuario no pertenece a ningún círculo, mostrar círculos públicos recientes
        if (empty($mis_circulos)) {
            $circulos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as creador_nombre
                 FROM {$tabla_circulos} c
                 LEFT JOIN {$wpdb->users} u ON c.creador_id = u.ID
                 WHERE c.privacidad = 'publico' AND c.estado = 'activo'
                 ORDER BY c.fecha_creacion DESC
                 LIMIT %d",
                $limite
            ));
        } else {
            $placeholders = implode(',', array_fill(0, count($mis_circulos), '%d'));
            $circulos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as creador_nombre
                 FROM {$tabla_circulos} c
                 LEFT JOIN {$wpdb->users} u ON c.creador_id = u.ID
                 WHERE c.id IN ({$placeholders}) AND c.estado = 'activo'
                 ORDER BY c.fecha_creacion DESC
                 LIMIT %d",
                array_merge($mis_circulos, [$limite])
            ));
        }

        foreach ($circulos as $circ) {
            $items[] = $this->normalizar_item([
                'id' => 'circulo_' . $circ->id,
                'tipo' => 'circulo',
                'origen' => 'circulo-cuidados',
                'autor' => [
                    'id' => $circ->creador_id,
                    'nombre' => $circ->creador_nombre ?? __('Organizador', 'flavor-chat-ia'),
                    'avatar' => get_avatar_url($circ->creador_id, ['size' => 48]),
                ],
                'contenido' => [
                    'titulo' => $circ->nombre ?? '',
                    'texto' => $circ->descripcion ?? '',
                    'tipo_cuidado' => $circ->tipo ?? 'general',
                ],
                'contexto' => [
                    'miembros' => (int) ($circ->miembros_count ?? 0),
                    'privacidad' => $circ->privacidad ?? 'publico',
                ],
                'interacciones' => [
                    'miembros' => (int) ($circ->miembros_count ?? 0),
                    'actividades' => (int) ($circ->actividades_count ?? 0),
                ],
                'fecha' => $circ->fecha_creacion,
                'url' => home_url('/circulos-cuidados/' . $circ->id . '/'),
            ]);
        }

        // Actividades próximas de mis círculos
        if (!empty($mis_circulos) && $this->tabla_existe($tabla_actividades)) {
            $placeholders = implode(',', array_fill(0, count($mis_circulos), '%d'));
            $actividades = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, c.nombre as circulo_nombre, u.display_name as organizador_nombre
                 FROM {$tabla_actividades} a
                 LEFT JOIN {$tabla_circulos} c ON a.circulo_id = c.id
                 LEFT JOIN {$wpdb->users} u ON a.organizador_id = u.ID
                 WHERE a.circulo_id IN ({$placeholders})
                 AND a.fecha_inicio >= NOW()
                 ORDER BY a.fecha_inicio ASC
                 LIMIT %d",
                array_merge($mis_circulos, [$limite])
            ));

            foreach ($actividades as $act) {
                $items[] = $this->normalizar_item([
                    'id' => 'actividad_' . $act->id,
                    'tipo' => 'circulo',
                    'origen' => 'circulo-actividad',
                    'autor' => [
                        'id' => $act->organizador_id ?? 0,
                        'nombre' => $act->organizador_nombre ?? __('Círculo', 'flavor-chat-ia'),
                        'avatar' => get_avatar_url($act->organizador_id ?? 0, ['size' => 48]),
                    ],
                    'contenido' => [
                        'titulo' => $act->titulo ?? '',
                        'texto' => $act->descripcion ?? '',
                        'fecha_evento' => $act->fecha_inicio,
                        'lugar' => $act->lugar ?? '',
                    ],
                    'contexto' => [
                        'circulo' => $act->circulo_nombre,
                        'circulo_id' => $act->circulo_id,
                        'subtipo' => 'actividad',
                    ],
                    'interacciones' => [
                        'participantes' => (int) ($act->participantes ?? 0),
                    ],
                    'fecha' => $act->fecha_creacion ?? $act->fecha_inicio,
                    'url' => home_url('/circulos-cuidados/' . $act->circulo_id . '/actividades/' . $act->id . '/'),
                ]);
            }
        }

        return $items;
    }

    /**
     * Obtiene items de Eventos.
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_eventos_items($usuario_id, $limite = 10) {
        global $wpdb;

        $tabla_eventos = $wpdb->prefix . 'flavor_eventos';
        $tabla_inscripciones = $wpdb->prefix . 'flavor_eventos_inscripciones';

        if (!$this->tabla_existe($tabla_eventos)) {
            return [];
        }

        $items = [];

        $eventos = $wpdb->get_results($wpdb->prepare(
            "SELECT e.*,
                    (SELECT COUNT(*) FROM {$tabla_inscripciones} i WHERE i.evento_id = e.id AND i.estado = 'confirmado') AS inscritos_count
             FROM {$tabla_eventos} e
             WHERE e.estado = 'publicado'
             AND e.fecha_inicio >= %s
             ORDER BY e.fecha_inicio ASC
             LIMIT %d",
            current_time('mysql'),
            $limite
        ));

        foreach ($eventos as $evento) {
            $items[] = $this->normalizar_item([
                'id' => 'evento_' . $evento->id,
                'tipo' => 'evento',
                'origen' => 'eventos',
                'autor' => [
                    'id' => $evento->organizador_id ?? 0,
                    'nombre' => $evento->organizador_nombre ?? __('Eventos', 'flavor-chat-ia'),
                    'avatar' => !empty($evento->organizador_id) ? get_avatar_url($evento->organizador_id, ['size' => 48]) : '',
                ],
                'contenido' => [
                    'titulo' => $evento->titulo ?? '',
                    'texto' => $evento->descripcion ?? '',
                    'fecha_evento' => $evento->fecha_inicio,
                    'lugar' => $evento->lugar ?? $evento->ubicacion ?? '',
                ],
                'contexto' => [
                    'evento_id' => (int) $evento->id,
                    'subtipo' => 'proximo',
                ],
                'interacciones' => [
                    'inscritos' => (int) ($evento->inscritos_count ?? 0),
                ],
                'fecha' => $evento->fecha_inicio,
                'url' => home_url('/mi-portal/eventos/ver/' . $evento->id . '/'),
            ]);
        }

        if ($usuario_id > 0 && $this->tabla_existe($tabla_inscripciones)) {
            $mis_inscripciones = $wpdb->get_results($wpdb->prepare(
                "SELECT i.*, e.titulo, e.descripcion, e.fecha_inicio, e.lugar, e.ubicacion
                 FROM {$tabla_inscripciones} i
                 INNER JOIN {$tabla_eventos} e ON i.evento_id = e.id
                 WHERE i.usuario_id = %d
                 AND i.estado = 'confirmado'
                 AND e.estado = 'publicado'
                 AND e.fecha_inicio >= %s
                 ORDER BY e.fecha_inicio ASC
                 LIMIT %d",
                $usuario_id,
                current_time('mysql'),
                max(1, (int) floor($limite / 2))
            ));

            foreach ($mis_inscripciones as $inscripcion) {
                $items[] = $this->normalizar_item([
                    'id' => 'evento_inscripcion_' . $inscripcion->evento_id,
                    'tipo' => 'evento',
                    'origen' => 'eventos-inscripcion',
                    'autor' => [
                        'id' => $usuario_id,
                        'nombre' => wp_get_current_user()->display_name ?: __('Mi agenda', 'flavor-chat-ia'),
                        'avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                    ],
                    'contenido' => [
                        'titulo' => sprintf(__('Inscrito: %s', 'flavor-chat-ia'), $inscripcion->titulo ?? ''),
                        'texto' => $inscripcion->descripcion ?? '',
                        'fecha_evento' => $inscripcion->fecha_inicio,
                        'lugar' => $inscripcion->lugar ?? $inscripcion->ubicacion ?? '',
                    ],
                    'contexto' => [
                        'evento_id' => (int) $inscripcion->evento_id,
                        'subtipo' => 'inscripcion',
                    ],
                    'interacciones' => [
                        'estado' => $inscripcion->estado ?? 'confirmado',
                    ],
                    'fecha' => $inscripcion->fecha_inicio,
                    'url' => home_url('/mi-portal/eventos/ver/' . $inscripcion->evento_id . '/'),
                ]);
            }
        }

        return $items;
    }

    /**
     * Obtiene items de Grupos de Consumo.
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    private function get_grupos_consumo_items($usuario_id, $limite = 10) {
        global $wpdb;

        if ($usuario_id <= 0) {
            return [];
        }

        $tabla_consumidores = $wpdb->prefix . 'flavor_gc_consumidores';
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';

        if (!$this->tabla_existe($tabla_consumidores)) {
            return [];
        }

        $items = [];

        $membresias = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*
             FROM {$tabla_consumidores} c
             WHERE c.usuario_id = %d
             AND c.estado = 'activo'
             ORDER BY c.fecha_alta DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ));

        foreach ($membresias as $membresia) {
            $grupo_id = (int) ($membresia->grupo_id ?? 0);
            $grupo = $grupo_id > 0 ? get_post($grupo_id) : null;

            if (!$grupo || $grupo->post_type !== 'gc_grupo') {
                continue;
            }

            $total_pedidos = 0;
            if ($this->tabla_existe($tabla_pedidos)) {
                $total_pedidos = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla_pedidos} WHERE usuario_id = %d",
                    $usuario_id
                ));
            }

            $items[] = $this->normalizar_item([
                'id' => 'grupo_consumo_' . $grupo_id,
                'tipo' => 'grupo_consumo',
                'origen' => 'grupos-consumo',
                'autor' => [
                    'id' => $usuario_id,
                    'nombre' => wp_get_current_user()->display_name ?: __('Grupo de consumo', 'flavor-chat-ia'),
                    'avatar' => get_avatar_url($usuario_id, ['size' => 48]),
                ],
                'contenido' => [
                    'titulo' => $grupo->post_title ?? '',
                    'texto' => $grupo->post_excerpt ?: wp_trim_words(wp_strip_all_tags($grupo->post_content ?? ''), 20),
                    'ubicacion' => get_post_meta($grupo_id, '_gc_ubicacion', true),
                ],
                'contexto' => [
                    'grupo_consumo_id' => $grupo_id,
                    'subtipo' => 'membresia',
                ],
                'interacciones' => [
                    'pedidos' => $total_pedidos,
                ],
                'fecha' => $membresia->fecha_alta ?? $grupo->post_date,
                'url' => add_query_arg('grupo_id', $grupo_id, home_url('/mi-portal/grupos-consumo/')),
            ]);
        }

        return $items;
    }

    /**
     * Normaliza un item del feed al formato estándar
     *
     * @param array $item
     * @return array
     */
    private function normalizar_item($item) {
        $tipo = $item['tipo'] ?? 'publicacion';

        return [
            'id' => $item['id'] ?? 0,
            'tipo' => $tipo,
            'tipo_info' => $this->content_types[$tipo] ?? [
                'label' => ucfirst($tipo),
                'icon' => '📄',
                'color' => '#6b7280',
            ],
            'origen' => $item['origen'] ?? 'desconocido',
            'autor' => $item['autor'] ?? [
                'id' => 0,
                'nombre' => __('Anónimo', 'flavor-chat-ia'),
                'avatar' => '',
            ],
            'contenido' => $item['contenido'] ?? [],
            'multimedia' => $item['multimedia'] ?? [],
            'contexto' => $item['contexto'] ?? [],
            'interacciones' => $item['interacciones'] ?? [
                'likes' => 0,
                'comentarios' => 0,
            ],
            'fecha' => $item['fecha'] ?? current_time('mysql'),
            'fecha_humana' => $this->format_fecha_humana($item['fecha'] ?? current_time('mysql')),
            'url' => $item['url'] ?? '#',
        ];
    }

    /**
     * Parsea adjuntos JSON
     *
     * @param string|null $adjuntos
     * @return array
     */
    private function parse_adjuntos($adjuntos) {
        if (empty($adjuntos)) {
            return [];
        }

        $parsed = json_decode($adjuntos, true);

        if (!is_array($parsed)) {
            return [];
        }

        return $parsed;
    }

    /**
     * Formatea fecha en formato humano (hace X tiempo)
     *
     * @param string $fecha
     * @return string
     */
    public function format_fecha_humana($fecha) {
        $timestamp = strtotime($fecha);
        $ahora = current_time('timestamp');
        $diferencia = $ahora - $timestamp;

        if ($diferencia < 60) {
            return __('Ahora mismo', 'flavor-chat-ia');
        } elseif ($diferencia < 3600) {
            $minutos = round($diferencia / 60);
            return sprintf(_n('Hace %d minuto', 'Hace %d minutos', $minutos, 'flavor-chat-ia'), $minutos);
        } elseif ($diferencia < 86400) {
            $horas = round($diferencia / 3600);
            return sprintf(_n('Hace %d hora', 'Hace %d horas', $horas, 'flavor-chat-ia'), $horas);
        } elseif ($diferencia < 604800) {
            $dias = round($diferencia / 86400);
            return sprintf(_n('Hace %d día', 'Hace %d días', $dias, 'flavor-chat-ia'), $dias);
        } else {
            return date_i18n(get_option('date_format'), $timestamp);
        }
    }

    // =========================================================================
    // MÉTODOS AUXILIARES
    // =========================================================================

    /**
     * Obtiene trending hashtags y temas
     *
     * @param int $limite
     * @return array
     */
    public function obtener_trending($limite = 10) {
        global $wpdb;

        $tabla_hashtags = $wpdb->prefix . 'flavor_social_hashtags';

        if (!$this->tabla_existe($tabla_hashtags)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT hashtag, total_usos
             FROM {$tabla_hashtags}
             WHERE fecha_ultimo_uso > DATE_SUB(NOW(), INTERVAL 7 DAY)
             ORDER BY total_usos DESC
             LIMIT %d",
            $limite
        ), ARRAY_A);
    }

    /**
     * Obtiene sugerencias de usuarios a seguir
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    public function obtener_sugerencias_usuarios($usuario_id, $limite = 5) {
        global $wpdb;

        // Usuarios con más seguidores que el usuario no sigue
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        if (!$this->tabla_existe($tabla_seguimientos)) {
            return [];
        }

        $sugerencias = $wpdb->get_results($wpdb->prepare(
            "SELECT u.ID, u.display_name, COUNT(s.id) as seguidores
             FROM {$wpdb->users} u
             LEFT JOIN {$tabla_seguimientos} s ON u.ID = s.seguido_id
             WHERE u.ID != %d
             AND u.ID NOT IN (
                 SELECT seguido_id FROM {$tabla_seguimientos} WHERE seguidor_id = %d
             )
             GROUP BY u.ID
             ORDER BY seguidores DESC
             LIMIT %d",
            $usuario_id,
            $usuario_id,
            $limite
        ), ARRAY_A);

        foreach ($sugerencias as &$sug) {
            $sug['avatar'] = get_avatar_url($sug['ID'], ['size' => 48]);
            $sug['url'] = home_url('/mi-portal/mi-red/perfil/?id=' . $sug['ID']);
        }

        return $sugerencias;
    }

    /**
     * Obtiene contenido destacado
     *
     * @param int $limite
     * @return array
     */
    public function obtener_contenido_destacado($limite = 12) {
        // Combinar lo más popular de cada módulo
        $destacados = [];

        // Lo más reciente con más interacciones
        $feed = $this->obtener_feed_unificado(0, $limite * 2, 0);

        // Ordenar por interacciones totales
        usort($feed, function($a, $b) {
            $total_a = ($a['interacciones']['likes'] ?? 0) +
                       ($a['interacciones']['comentarios'] ?? 0) * 2 +
                       ($a['interacciones']['vistas'] ?? 0) * 0.1;
            $total_b = ($b['interacciones']['likes'] ?? 0) +
                       ($b['interacciones']['comentarios'] ?? 0) * 2 +
                       ($b['interacciones']['vistas'] ?? 0) * 0.1;
            return $total_b - $total_a;
        });

        return array_slice($feed, 0, $limite);
    }

    /**
     * Obtiene usuarios populares
     *
     * @param int $limite
     * @return array
     */
    public function obtener_usuarios_populares($limite = 10) {
        global $wpdb;

        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        if (!$this->tabla_existe($tabla_perfiles)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name
             FROM {$tabla_perfiles} p
             LEFT JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             ORDER BY p.seguidores DESC
             LIMIT %d",
            $limite
        ), ARRAY_A);
    }

    /**
     * Obtiene conversaciones del chat interno
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_conversaciones($usuario_id) {
        // Delegar al módulo de chat interno si existe
        if (class_exists('Flavor_Chat_Interno_Module')) {
            $chat = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('chat_interno');
            if ($chat && method_exists($chat, 'get_conversaciones_usuario')) {
                return $chat->get_conversaciones_usuario($usuario_id);
            }
        }
        return [];
    }

    /**
     * Obtiene grupos de chat
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_grupos_chat($usuario_id) {
        // Delegar al módulo de chat grupos si existe
        if (class_exists('Flavor_Chat_Grupos_Module')) {
            $chat = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('chat_grupos');
            if ($chat && method_exists($chat, 'get_grupos_usuario')) {
                return $chat->get_grupos_usuario($usuario_id);
            }
        }
        return [];
    }

    /**
     * Obtiene notificaciones del usuario
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    public function obtener_notificaciones($usuario_id, $limite = 30) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_social_notificaciones';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT n.*, u.display_name as emisor_nombre
             FROM {$tabla} n
             LEFT JOIN {$wpdb->users} u ON n.emisor_id = u.ID
             WHERE n.receptor_id = %d
             ORDER BY n.fecha_creacion DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ), ARRAY_A);
    }

    /**
     * Obtiene conteo de notificaciones no leídas
     *
     * @param int $usuario_id
     * @return int
     */
    public function get_unread_notifications_count($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_social_notificaciones';

        if (!$this->tabla_existe($tabla)) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE usuario_id = %d AND leida = 0",
            $usuario_id
        ));
    }

    /**
     * Obtiene conteo de mensajes no leídos
     *
     * La tabla flavor_chat_interno_mensajes usa arquitectura de conversaciones
     * con participantes, no tiene columna receptor_id directa.
     *
     * @param int $usuario_id
     * @return int
     */
    public function get_unread_messages_count($usuario_id) {
        global $wpdb;

        $tabla_mensajes = $wpdb->prefix . 'flavor_chat_interno_mensajes';
        $tabla_participantes = $wpdb->prefix . 'flavor_chat_interno_participantes';

        // Verificar que existan ambas tablas
        if (!$this->tabla_existe($tabla_mensajes) || !$this->tabla_existe($tabla_participantes)) {
            return 0;
        }

        // Contar mensajes no leídos en todas las conversaciones donde participa el usuario
        // Un mensaje está "no leído" si:
        // 1. El usuario es participante de la conversación
        // 2. El mensaje fue enviado por otro usuario (remitente_id != usuario_id)
        // 3. El id del mensaje es mayor que ultimo_mensaje_leido del participante
        // 4. El mensaje no está eliminado
        $total_no_leidos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(no_leidos), 0) FROM (
                SELECT COUNT(*) as no_leidos
                FROM {$tabla_mensajes} m
                INNER JOIN {$tabla_participantes} p ON m.conversacion_id = p.conversacion_id
                WHERE p.usuario_id = %d
                AND m.remitente_id != %d
                AND m.id > p.ultimo_mensaje_leido
                AND m.eliminado = 0
                GROUP BY m.conversacion_id
            ) as subquery",
            $usuario_id,
            $usuario_id
        ));

        return $total_no_leidos;
    }

    /**
     * Obtiene galería multimedia del usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_galeria_usuario($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_multimedia';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE usuario_id = %d AND estado = 'publicado'
             ORDER BY fecha_subida DESC
             LIMIT 50",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Obtiene álbumes del usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_albumes_usuario($usuario_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_multimedia_albumes';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha_creacion DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Obtiene perfil de usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_perfil_usuario($usuario_id) {
        global $wpdb;

        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return [];
        }

        $tabla_perfiles = $wpdb->prefix . 'flavor_social_perfiles';

        $perfil_extra = [];
        if ($this->tabla_existe($tabla_perfiles)) {
            $perfil_extra = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM {$tabla_perfiles} WHERE usuario_id = %d",
                $usuario_id
            ), ARRAY_A) ?? [];
        }

        return array_merge([
            'id' => $usuario_id,
            'nombre' => $usuario->display_name,
            'email' => $usuario->user_email,
            'avatar' => get_avatar_url($usuario_id, ['size' => 200]),
            'fecha_registro' => $usuario->user_registered,
            'bio' => get_user_meta($usuario_id, 'description', true),
        ], $perfil_extra);
    }

    /**
     * Obtiene publicaciones de un usuario
     *
     * @param int $usuario_id
     * @param int $limite
     * @return array
     */
    public function obtener_publicaciones_usuario($usuario_id, $limite = 20) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_social_publicaciones';

        if (!$this->tabla_existe($tabla)) {
            return [];
        }

        $publicaciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE autor_id = %d AND estado = 'publicado'
             ORDER BY fecha_publicacion DESC
             LIMIT %d",
            $usuario_id,
            $limite
        ), ARRAY_A);

        return array_map([$this, 'normalizar_publicacion'], $publicaciones);
    }

    /**
     * Normaliza una publicación
     *
     * @param array $pub
     * @return array
     */
    private function normalizar_publicacion($pub) {
        return $this->normalizar_item([
            'id' => $pub['id'],
            'tipo' => 'publicacion',
            'origen' => 'red-social',
            'autor' => [
                'id' => $pub['autor_id'],
                'nombre' => get_userdata($pub['autor_id'])->display_name ?? '',
                'avatar' => get_avatar_url($pub['autor_id'], ['size' => 48]),
            ],
            'contenido' => [
                'texto' => $pub['contenido'],
                'tipo_media' => $pub['tipo'],
            ],
            'multimedia' => $this->parse_adjuntos($pub['adjuntos'] ?? ''),
            'interacciones' => [
                'likes' => (int) $pub['me_gusta'],
                'comentarios' => (int) $pub['comentarios'],
                'compartidos' => (int) $pub['compartidos'],
            ],
            'fecha' => $pub['fecha_publicacion'],
            'url' => home_url('/red-social/ver/' . $pub['id'] . '/'),
        ]);
    }

    /**
     * Obtiene estadísticas de usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_estadisticas_usuario($usuario_id) {
        global $wpdb;

        $stats = [
            'publicaciones' => 0,
            'seguidores' => 0,
            'siguiendo' => 0,
            'me_gusta_recibidos' => 0,
        ];

        $tabla_publicaciones = $wpdb->prefix . 'flavor_social_publicaciones';
        $tabla_seguimientos = $wpdb->prefix . 'flavor_social_seguimientos';

        if ($this->tabla_existe($tabla_publicaciones)) {
            $stats['publicaciones'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_publicaciones} WHERE autor_id = %d AND estado = 'publicado'",
                $usuario_id
            ));

            $stats['me_gusta_recibidos'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT SUM(me_gusta) FROM {$tabla_publicaciones} WHERE autor_id = %d",
                $usuario_id
            ));
        }

        if ($this->tabla_existe($tabla_seguimientos)) {
            $stats['seguidores'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimientos} WHERE seguido_id = %d",
                $usuario_id
            ));

            $stats['siguiendo'] = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_seguimientos} WHERE seguidor_id = %d",
                $usuario_id
            ));
        }

        return $stats;
    }

    /**
     * Busca contenido en todos los módulos
     *
     * @param string $termino
     * @return array
     */
    public function buscar_contenido($termino) {
        $resultados = [
            'usuarios' => [],
            'publicaciones' => [],
            'multimedia' => [],
            'comunidades' => [],
            'foros' => [],
            'podcast' => [],
            'colectivos' => [],
            'ayuda' => [],
        ];

        global $wpdb;

        $termino_like = '%' . $wpdb->esc_like($termino) . '%';

        // 1. Buscar usuarios
        $resultados['usuarios'] = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, display_name, user_email
             FROM {$wpdb->users}
             WHERE display_name LIKE %s OR user_login LIKE %s
             LIMIT 10",
            $termino_like,
            $termino_like
        ), ARRAY_A);

        foreach ($resultados['usuarios'] as &$usuario) {
            $usuario['avatar'] = get_avatar_url($usuario['ID'], ['size' => 48]);
            $usuario['url'] = home_url('/mi-portal/mi-red/perfil/' . $usuario['ID'] . '/');
        }

        // 2. Buscar publicaciones
        $tabla_pub = $wpdb->prefix . 'flavor_social_publicaciones';
        if ($this->tabla_existe($tabla_pub)) {
            $pubs = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_pub}
                 WHERE contenido LIKE %s AND estado = 'publicado'
                 ORDER BY fecha_publicacion DESC
                 LIMIT 10",
                $termino_like
            ), ARRAY_A);

            foreach ($pubs as $pub) {
                $resultados['publicaciones'][] = $this->normalizar_publicacion($pub);
            }
        }

        // 3. Buscar multimedia
        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
        if ($this->tabla_existe($tabla_multimedia)) {
            $medias = $wpdb->get_results($wpdb->prepare(
                "SELECT m.*, u.display_name as autor_nombre
                 FROM {$tabla_multimedia} m
                 LEFT JOIN {$wpdb->users} u ON m.usuario_id = u.ID
                 WHERE (m.titulo LIKE %s OR m.descripcion LIKE %s) AND m.estado = 'publicado'
                 ORDER BY m.fecha_creacion DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($medias as $media) {
                $resultados['multimedia'][] = [
                    'id' => $media['id'],
                    'tipo' => $media['tipo'] ?? 'imagen',
                    'titulo' => $media['titulo'] ?? '',
                    'thumbnail' => $media['thumbnail'] ?? $media['url'] ?? '',
                    'autor' => $media['autor_nombre'],
                    'fecha' => $media['fecha_creacion'],
                    'url' => home_url('/multimedia/' . $media['id'] . '/'),
                ];
            }
        }

        // 4. Buscar comunidades
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';
        if ($this->tabla_existe($tabla_comunidades)) {
            $comunidades = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as creador_nombre
                 FROM {$tabla_comunidades} c
                 LEFT JOIN {$wpdb->users} u ON c.creador_id = u.ID
                 WHERE (c.nombre LIKE %s OR c.descripcion LIKE %s) AND c.estado = 'activa'
                 ORDER BY c.miembros_count DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($comunidades as $com) {
                $resultados['comunidades'][] = [
                    'id' => $com['id'],
                    'nombre' => $com['nombre'],
                    'descripcion' => wp_trim_words($com['descripcion'] ?? '', 20),
                    'imagen' => $com['imagen'] ?? '',
                    'miembros' => (int) ($com['miembros_count'] ?? 0),
                    'url' => home_url('/comunidades/' . $com['id'] . '/'),
                ];
            }
        }

        // 5. Buscar foros/temas
        $tabla_foros = $wpdb->prefix . 'flavor_foros_temas';
        if ($this->tabla_existe($tabla_foros)) {
            $temas = $wpdb->get_results($wpdb->prepare(
                "SELECT t.*, u.display_name as autor_nombre
                 FROM {$tabla_foros} t
                 LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
                 WHERE (t.titulo LIKE %s OR t.contenido LIKE %s) AND t.estado = 'publicado'
                 ORDER BY t.fecha_creacion DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($temas as $tema) {
                $resultados['foros'][] = [
                    'id' => $tema['id'],
                    'titulo' => $tema['titulo'] ?? '',
                    'extracto' => wp_trim_words($tema['contenido'] ?? '', 20),
                    'autor' => $tema['autor_nombre'],
                    'respuestas' => (int) ($tema['respuestas_count'] ?? 0),
                    'fecha' => $tema['fecha_creacion'],
                    'url' => home_url('/foros/tema/' . $tema['id'] . '/'),
                ];
            }
        }

        // 6. Buscar podcasts
        $tabla_podcast = $wpdb->prefix . 'flavor_podcast_episodios';
        if ($this->tabla_existe($tabla_podcast)) {
            $episodios = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, u.display_name as autor_nombre
                 FROM {$tabla_podcast} e
                 LEFT JOIN {$wpdb->users} u ON e.autor_id = u.ID
                 WHERE (e.titulo LIKE %s OR e.descripcion LIKE %s) AND e.estado = 'publicado'
                 ORDER BY e.fecha_publicacion DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($episodios as $ep) {
                $resultados['podcast'][] = [
                    'id' => $ep['id'],
                    'titulo' => $ep['titulo'] ?? '',
                    'descripcion' => wp_trim_words($ep['descripcion'] ?? '', 20),
                    'imagen' => $ep['imagen'] ?? '',
                    'duracion' => $ep['duracion'] ?? 0,
                    'autor' => $ep['autor_nombre'],
                    'fecha' => $ep['fecha_publicacion'],
                    'url' => home_url('/podcast/episodio/' . $ep['id'] . '/'),
                ];
            }
        }

        // 7. Buscar colectivos
        $tabla_colectivos = $wpdb->prefix . 'flavor_colectivos';
        if ($this->tabla_existe($tabla_colectivos)) {
            $colectivos = $wpdb->get_results($wpdb->prepare(
                "SELECT c.*, u.display_name as creador_nombre
                 FROM {$tabla_colectivos} c
                 LEFT JOIN {$wpdb->users} u ON c.creador_id = u.ID
                 WHERE (c.nombre LIKE %s OR c.descripcion LIKE %s) AND c.estado = 'activo'
                 ORDER BY c.miembros_count DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($colectivos as $col) {
                $resultados['colectivos'][] = [
                    'id' => $col['id'],
                    'nombre' => $col['nombre'],
                    'descripcion' => wp_trim_words($col['descripcion'] ?? '', 20),
                    'imagen' => $col['logo'] ?? '',
                    'miembros' => (int) ($col['miembros_count'] ?? 0),
                    'url' => home_url('/colectivos/' . $col['id'] . '/'),
                ];
            }
        }

        // 8. Buscar ayuda vecinal
        $tabla_ayuda = $wpdb->prefix . 'flavor_ayuda_vecinal';
        if ($this->tabla_existe($tabla_ayuda)) {
            $solicitudes = $wpdb->get_results($wpdb->prepare(
                "SELECT a.*, u.display_name as autor_nombre
                 FROM {$tabla_ayuda} a
                 LEFT JOIN {$wpdb->users} u ON a.usuario_id = u.ID
                 WHERE (a.titulo LIKE %s OR a.descripcion LIKE %s) AND a.estado IN ('abierta', 'en_proceso')
                 ORDER BY a.fecha_creacion DESC
                 LIMIT 10",
                $termino_like,
                $termino_like
            ), ARRAY_A);

            foreach ($solicitudes as $sol) {
                $resultados['ayuda'][] = [
                    'id' => $sol['id'],
                    'titulo' => $sol['titulo'] ?? '',
                    'descripcion' => wp_trim_words($sol['descripcion'] ?? '', 20),
                    'tipo' => $sol['tipo'] ?? 'solicitud',
                    'urgencia' => $sol['urgencia'] ?? 'normal',
                    'autor' => $sol['autor_nombre'],
                    'fecha' => $sol['fecha_creacion'],
                    'url' => home_url('/ayuda-vecinal/' . $sol['id'] . '/'),
                ];
            }
        }

        // Contar total de resultados
        $resultados['total'] = array_sum(array_map('count', $resultados)) - 1; // -1 para no contar 'total'

        return $resultados;
    }

    /**
     * Obtiene comunidades del usuario
     *
     * @param int $usuario_id
     * @return array
     */
    public function obtener_comunidades_usuario($usuario_id) {
        global $wpdb;

        $tabla_miembros = $wpdb->prefix . 'flavor_comunidades_miembros';
        $tabla_comunidades = $wpdb->prefix . 'flavor_comunidades';

        if (!$this->tabla_existe($tabla_miembros)) {
            return [];
        }

        $user_col = $this->obtener_primera_columna_existente($tabla_miembros, ['usuario_id', 'user_id', 'miembro_id']);
        if ($user_col === '') {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT c.* FROM {$tabla_comunidades} c
             INNER JOIN {$tabla_miembros} m ON c.id = m.comunidad_id
             WHERE m.{$user_col} = %d AND c.estado = 'activa'",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Obtiene tipos de publicación permitidos para el usuario
     *
     * @return array
     */
    private function get_allowed_post_types() {
        return [
            'texto' => ['label' => __('Texto', 'flavor-chat-ia'), 'icon' => '📝'],
            'imagen' => ['label' => __('Imagen', 'flavor-chat-ia'), 'icon' => '📷'],
            'video' => ['label' => __('Video', 'flavor-chat-ia'), 'icon' => '🎬'],
            'enlace' => ['label' => __('Enlace', 'flavor-chat-ia'), 'icon' => '🔗'],
        ];
    }

    /**
     * Renderiza pantalla de login requerido
     */
    private function render_login_required() {
        include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/login-required.php';
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Cargar más items del feed
     */
    public function ajax_cargar_feed() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $offset = isset($_POST['offset']) ? absint($_POST['offset']) : 0;
        $limite = isset($_POST['limite']) ? absint($_POST['limite']) : 20;
        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : 'todos';

        $items = $this->obtener_feed_unificado($usuario_id, $limite, $offset, $tipo);

        // Renderizar HTML de los items
        ob_start();
        foreach ($items as $item) {
            include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/partials/feed-item.php';
        }
        $html = ob_get_clean();

        wp_send_json_success([
            'html' => $html,
            'items' => $items,
            'hay_mas' => count($items) === $limite,
            'offset' => $offset + count($items),
        ]);
    }

    /**
     * AJAX: Crear publicación
     */
    public function ajax_crear_publicacion() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';
        $tipo = isset($_POST['tipo']) ? sanitize_key($_POST['tipo']) : 'texto';

        if (empty($contenido)) {
            wp_send_json_error(['message' => __('El contenido no puede estar vacío', 'flavor-chat-ia')]);
        }

        // Delegar al módulo de Red Social
        if (class_exists('Flavor_Chat_Red_Social_Module')) {
            $red_social = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('red_social');
            if ($red_social && method_exists($red_social, 'crear_publicacion')) {
                $resultado = $red_social->crear_publicacion([
                    'autor_id' => $usuario_id,
                    'contenido' => $contenido,
                    'tipo' => $tipo,
                    'visibilidad' => 'comunidad',
                ]);

                if ($resultado) {
                    // Invalidar caché del feed del usuario
                    $this->invalidar_cache_feed($usuario_id);

                    wp_send_json_success([
                        'message' => __('Publicación creada', 'flavor-chat-ia'),
                        'id' => $resultado,
                    ]);
                }
            }
        }

        wp_send_json_error(['message' => __('Error al crear la publicación', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Toggle like
     */
    public function ajax_toggle_like() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $tipo_item = isset($_POST['tipo_item']) ? sanitize_key($_POST['tipo_item']) : 'publicacion';

        if (!$item_id) {
            wp_send_json_error(['message' => __('ID inválido', 'flavor-chat-ia')]);
        }

        // Delegar según tipo
        $resultado = false;
        if ($tipo_item === 'publicacion' && class_exists('Flavor_Chat_Red_Social_Module')) {
            $red_social = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('red_social');
            if ($red_social && method_exists($red_social, 'toggle_like')) {
                $resultado = $red_social->toggle_like($item_id, $usuario_id);
            }
        }

        if ($resultado !== false) {
            wp_send_json_success([
                'liked' => $resultado['liked'] ?? true,
                'count' => $resultado['count'] ?? 0,
            ]);
        }

        wp_send_json_error(['message' => __('Error al procesar', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Crear comentario
     */
    public function ajax_crear_comentario() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $contenido = isset($_POST['contenido']) ? sanitize_textarea_field($_POST['contenido']) : '';
        $tipo_item = isset($_POST['tipo_item']) ? sanitize_key($_POST['tipo_item']) : 'publicacion';

        if (!$item_id || empty($contenido)) {
            wp_send_json_error(['message' => __('Datos incompletos', 'flavor-chat-ia')]);
        }

        // Delegar según tipo
        if ($tipo_item === 'publicacion' && class_exists('Flavor_Chat_Red_Social_Module')) {
            $red_social = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('red_social');
            if ($red_social && method_exists($red_social, 'crear_comentario')) {
                $comentario_id = $red_social->crear_comentario([
                    'publicacion_id' => $item_id,
                    'autor_id' => $usuario_id,
                    'contenido' => $contenido,
                ]);

                if ($comentario_id) {
                    wp_send_json_success([
                        'message' => __('Comentario añadido', 'flavor-chat-ia'),
                        'id' => $comentario_id,
                    ]);
                }
            }
        }

        wp_send_json_error(['message' => __('Error al comentar', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Obtener comentarios
     */
    public function ajax_obtener_comentarios() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $tipo_item = isset($_POST['tipo_item']) ? sanitize_key($_POST['tipo_item']) : 'publicacion';

        if (!$item_id) {
            wp_send_json_error(['message' => __('ID inválido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_social_comentarios';

        if (!$this->tabla_existe($tabla)) {
            wp_send_json_success(['comentarios' => []]);
        }

        $comentarios = $wpdb->get_results($wpdb->prepare(
            "SELECT c.*, u.display_name as autor_nombre
             FROM {$tabla} c
             LEFT JOIN {$wpdb->users} u ON c.autor_id = u.ID
             WHERE c.publicacion_id = %d AND c.estado = 'publicado'
             ORDER BY c.fecha_creacion ASC
             LIMIT 50",
            $item_id
        ), ARRAY_A);

        foreach ($comentarios as &$com) {
            $com['avatar'] = get_avatar_url($com['autor_id'], ['size' => 32]);
            $com['fecha_humana'] = $this->format_fecha_humana($com['fecha_creacion']);
        }

        wp_send_json_success(['comentarios' => $comentarios]);
    }

    /**
     * AJAX: Buscar contenido
     */
    public function ajax_buscar() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';

        if (strlen($termino) < 2) {
            wp_send_json_error(['message' => __('Término muy corto', 'flavor-chat-ia')]);
        }

        $resultados = $this->buscar_contenido($termino);

        wp_send_json_success($resultados);
    }

    /**
     * AJAX: Obtener notificaciones
     */
    public function ajax_obtener_notificaciones() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        $notificaciones = $this->obtener_notificaciones($usuario_id, 20);

        wp_send_json_success(['notificaciones' => $notificaciones]);
    }

    /**
     * AJAX: Marcar notificación como leída
     */
    public function ajax_marcar_notificacion() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $notificacion_id = isset($_POST['notificacion_id']) ? absint($_POST['notificacion_id']) : 0;

        if (!$notificacion_id) {
            wp_send_json_error(['message' => __('ID inválido', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_social_notificaciones';

        $wpdb->update(
            $tabla,
            ['leida' => 1, 'fecha_lectura' => current_time('mysql')],
            ['id' => $notificacion_id, 'receptor_id' => get_current_user_id()],
            ['%d', '%s'],
            ['%d', '%d']
        );

        wp_send_json_success(['message' => __('Marcada como leída', 'flavor-chat-ia')]);
    }

    /**
     * AJAX: Guardar suscripción push
     */
    public function ajax_save_push_subscription() {
        check_ajax_referer('mi_red_nonce', 'nonce');

        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            wp_send_json_error(['message' => __('Usuario no autenticado', 'flavor-chat-ia')]);
        }

        $subscription_json = isset($_POST['subscription']) ? sanitize_text_field($_POST['subscription']) : '';
        if (empty($subscription_json)) {
            wp_send_json_error(['message' => __('Suscripción inválida', 'flavor-chat-ia')]);
        }

        $subscription = json_decode(stripslashes($subscription_json), true);
        if (!$subscription || !isset($subscription['endpoint'])) {
            wp_send_json_error(['message' => __('Formato de suscripción inválido', 'flavor-chat-ia')]);
        }

        // Guardar en usermeta
        update_user_meta($usuario_id, 'mi_red_push_subscription', $subscription);
        update_user_meta($usuario_id, 'mi_red_push_subscribed_at', current_time('mysql'));

        // También guardar en una tabla para envío masivo
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_push_subscriptions';

        // Crear tabla si no existe
        if (!$this->tabla_existe($tabla)) {
            $charset_collate = $wpdb->get_charset_collate();
            $wpdb->query("CREATE TABLE IF NOT EXISTS {$tabla} (
                id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                usuario_id bigint(20) UNSIGNED NOT NULL,
                endpoint text NOT NULL,
                p256dh varchar(255) DEFAULT '',
                auth varchar(255) DEFAULT '',
                fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
                activa tinyint(1) DEFAULT 1,
                PRIMARY KEY (id),
                KEY usuario_id (usuario_id),
                KEY activa (activa)
            ) {$charset_collate}");
        }

        // Desactivar suscripciones anteriores del usuario
        $wpdb->update(
            $tabla,
            ['activa' => 0],
            ['usuario_id' => $usuario_id],
            ['%d'],
            ['%d']
        );

        // Insertar nueva suscripción
        $wpdb->insert($tabla, [
            'usuario_id' => $usuario_id,
            'endpoint' => $subscription['endpoint'],
            'p256dh' => $subscription['keys']['p256dh'] ?? '',
            'auth' => $subscription['keys']['auth'] ?? '',
            'activa' => 1,
        ], ['%d', '%s', '%s', '%s', '%d']);

        wp_send_json_success([
            'message' => __('Suscripción guardada', 'flavor-chat-ia'),
            'subscription_id' => $wpdb->insert_id,
        ]);
    }

    // =========================================================================
    // REST API CALLBACKS
    // =========================================================================

    /**
     * REST: Obtener feed
     */
    public function rest_obtener_feed($request) {
        $usuario_id = get_current_user_id();
        $limite = $request->get_param('limite');
        $offset = $request->get_param('offset');
        $tipo = $request->get_param('tipo');

        $items = $this->obtener_feed_unificado($usuario_id, $limite, $offset, $tipo);

        return rest_ensure_response([
            'success' => true,
            'data' => $items,
            'meta' => [
                'offset' => $offset,
                'limite' => $limite,
                'hay_mas' => count($items) === $limite,
            ],
        ]);
    }

    /**
     * REST: Crear publicación
     */
    public function rest_crear_publicacion($request) {
        $params = $request->get_json_params();
        $usuario_id = get_current_user_id();

        $contenido = isset($params['contenido']) ? sanitize_textarea_field($params['contenido']) : '';
        $tipo = isset($params['tipo']) ? sanitize_key($params['tipo']) : 'texto';

        if (empty($contenido)) {
            return new WP_Error('invalid_content', __('El contenido no puede estar vacío', 'flavor-chat-ia'), ['status' => 400]);
        }

        // Delegar al módulo de Red Social
        if (class_exists('Flavor_Chat_Red_Social_Module')) {
            $red_social = Flavor_Chat_Module_Loader::get_instance()->get_module_instance('red_social');
            if ($red_social && method_exists($red_social, 'crear_publicacion')) {
                $resultado = $red_social->crear_publicacion([
                    'autor_id' => $usuario_id,
                    'contenido' => $contenido,
                    'tipo' => $tipo,
                    'visibilidad' => 'comunidad',
                ]);

                if ($resultado) {
                    delete_transient("mi_red_feed_{$usuario_id}_todos_0_20");

                    return rest_ensure_response([
                        'success' => true,
                        'data' => ['id' => $resultado],
                    ]);
                }
            }
        }

        return new WP_Error('create_failed', __('Error al crear la publicación', 'flavor-chat-ia'), ['status' => 500]);
    }

    /**
     * REST: Obtener perfil
     */
    public function rest_obtener_perfil($request) {
        $perfil_id = $request->get_param('id');

        $perfil = $this->obtener_perfil_usuario($perfil_id);
        $estadisticas = $this->obtener_estadisticas_usuario($perfil_id);
        $publicaciones = $this->obtener_publicaciones_usuario($perfil_id, 10);

        return rest_ensure_response([
            'success' => true,
            'data' => [
                'perfil' => $perfil,
                'estadisticas' => $estadisticas,
                'publicaciones' => $publicaciones,
            ],
        ]);
    }

    /**
     * REST: Obtener trending
     */
    public function rest_obtener_trending($request) {
        $trending = $this->obtener_trending(10);

        return rest_ensure_response([
            'success' => true,
            'data' => $trending,
        ]);
    }
}

// Inicializar
add_action('init', function() {
    Flavor_Mi_Red_Social::get_instance();
}, 15);
