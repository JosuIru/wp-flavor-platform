<?php
/**
 * Sistema de Búsqueda Avanzada para la Red Social
 *
 * Búsqueda de publicaciones, usuarios, hashtags y comunidades
 * con filtros avanzados por tipo, fecha, ubicación y más.
 *
 * @package Flavor_Chat_IA
 * @since 1.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Social_Search {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Tipos de contenido buscables
     */
    private $tipos_contenido = [
        'publicaciones' => [
            'etiqueta' => 'Publicaciones',
            'icono' => 'dashicons-edit',
        ],
        'usuarios' => [
            'etiqueta' => 'Usuarios',
            'icono' => 'dashicons-admin-users',
        ],
        'hashtags' => [
            'etiqueta' => 'Hashtags',
            'icono' => 'dashicons-tag',
        ],
        'comunidades' => [
            'etiqueta' => 'Comunidades',
            'icono' => 'dashicons-groups',
        ],
    ];

    /**
     * Obtener instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Shortcodes
        add_shortcode('flavor_busqueda_social', [$this, 'shortcode_busqueda']);
        add_shortcode('flavor_social_search', [$this, 'shortcode_busqueda']);

        // AJAX handlers
        add_action('wp_ajax_flavor_social_search', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_flavor_social_search', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_social_search_suggestions', [$this, 'ajax_sugerencias']);
        add_action('wp_ajax_nopriv_flavor_social_search_suggestions', [$this, 'ajax_sugerencias']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Encolar assets
        add_action('wp_enqueue_scripts', [$this, 'maybe_enqueue_assets']);
    }

    /**
     * Shortcode de búsqueda
     */
    public function shortcode_busqueda($atts) {
        $atts = shortcode_atts([
            'placeholder' => __('Buscar publicaciones, usuarios, hashtags...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'mostrar_filtros' => 'yes',
            'tipos' => 'publicaciones,usuarios,hashtags,comunidades',
            'limite' => 10,
            'estilo' => 'completo', // completo, compacto, barra
        ], $atts, 'flavor_busqueda_social');

        $tipos_permitidos = array_map('trim', explode(',', $atts['tipos']));
        $mostrar_filtros = $atts['mostrar_filtros'] === 'yes';
        $estilo_clase = 'flavor-social-search--' . sanitize_html_class($atts['estilo']);

        // Encolar assets
        $this->enqueue_assets();

        ob_start();
        ?>
        <div class="flavor-social-search <?php echo esc_attr($estilo_clase); ?>" data-limite="<?php echo esc_attr($atts['limite']); ?>">
            <!-- Barra de búsqueda -->
            <div class="fss-search-bar">
                <div class="fss-input-wrapper">
                    <span class="fss-icon fss-icon-search">
                        <span class="dashicons dashicons-search"></span>
                    </span>
                    <input type="text"
                           class="fss-input"
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>"
                           autocomplete="off"
                           data-tipos="<?php echo esc_attr(implode(',', $tipos_permitidos)); ?>">
                    <span class="fss-icon fss-icon-clear" style="display: none;">
                        <span class="dashicons dashicons-no-alt"></span>
                    </span>
                    <span class="fss-icon fss-icon-loading" style="display: none;">
                        <span class="dashicons dashicons-update"></span>
                    </span>
                </div>

                <?php if ($mostrar_filtros): ?>
                <button type="button" class="fss-btn-filtros" title="<?php esc_attr_e('Filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    <span class="dashicons dashicons-filter"></span>
                </button>
                <?php endif; ?>
            </div>

            <!-- Sugerencias (autocompletado) -->
            <div class="fss-suggestions" style="display: none;"></div>

            <?php if ($mostrar_filtros): ?>
            <!-- Panel de filtros -->
            <div class="fss-filters" style="display: none;">
                <div class="fss-filters-header">
                    <h4><?php esc_html_e('Filtros de búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <button type="button" class="fss-btn-close-filters">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>

                <div class="fss-filters-body">
                    <!-- Tipo de contenido -->
                    <div class="fss-filter-group">
                        <label><?php esc_html_e('Tipo de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <div class="fss-filter-chips">
                            <?php foreach ($tipos_permitidos as $tipo): ?>
                                <?php if (isset($this->tipos_contenido[$tipo])): ?>
                                <label class="fss-chip">
                                    <input type="checkbox" name="tipos[]" value="<?php echo esc_attr($tipo); ?>" checked>
                                    <span class="dashicons <?php echo esc_attr($this->tipos_contenido[$tipo]['icono']); ?>"></span>
                                    <?php echo esc_html($this->tipos_contenido[$tipo]['etiqueta']); ?>
                                </label>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Fecha -->
                    <div class="fss-filter-group">
                        <label><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="fecha" class="fss-select">
                            <option value=""><?php esc_html_e('Cualquier fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="hoy"><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="semana"><?php esc_html_e('Esta semana', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="mes"><?php esc_html_e('Este mes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="ano"><?php esc_html_e('Este año', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>

                    <!-- Hashtag específico -->
                    <div class="fss-filter-group">
                        <label><?php esc_html_e('Hashtag', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="hashtag" class="fss-input-small" placeholder="#ejemplo">
                    </div>

                    <!-- Ubicación -->
                    <div class="fss-filter-group">
                        <label><?php esc_html_e('Ubicación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <input type="text" name="ubicacion" class="fss-input-small" placeholder="<?php esc_attr_e('Ciudad o lugar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                    </div>

                    <!-- Solo usuarios verificados -->
                    <div class="fss-filter-group">
                        <label class="fss-checkbox-label">
                            <input type="checkbox" name="verificados">
                            <?php esc_html_e('Solo usuarios verificados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </div>

                    <!-- Ordenar por -->
                    <div class="fss-filter-group">
                        <label><?php esc_html_e('Ordenar por', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                        <select name="ordenar" class="fss-select">
                            <option value="relevancia"><?php esc_html_e('Relevancia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="reciente"><?php esc_html_e('Más reciente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="popular"><?php esc_html_e('Más popular', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    </div>
                </div>

                <div class="fss-filters-footer">
                    <button type="button" class="fss-btn fss-btn-secondary fss-btn-reset">
                        <?php esc_html_e('Limpiar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <button type="button" class="fss-btn fss-btn-primary fss-btn-apply">
                        <?php esc_html_e('Aplicar filtros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <!-- Resultados -->
            <div class="fss-results" style="display: none;">
                <div class="fss-results-header">
                    <span class="fss-results-count"></span>
                    <div class="fss-results-tabs"></div>
                </div>
                <div class="fss-results-body"></div>
                <div class="fss-results-footer" style="display: none;">
                    <button type="button" class="fss-btn fss-btn-secondary fss-btn-load-more">
                        <?php esc_html_e('Cargar más resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>

            <!-- Estado vacío -->
            <div class="fss-empty" style="display: none;">
                <span class="dashicons dashicons-search"></span>
                <p><?php esc_html_e('No se encontraron resultados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <script>
            if (typeof flavorSocialSearchConfig === 'undefined') {
                var flavorSocialSearchConfig = {
                    ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                    nonce: '<?php echo wp_create_nonce('flavor_social_search_nonce'); ?>',
                    strings: {
                        resultados: '<?php echo esc_js(__('resultados', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        resultado: '<?php echo esc_js(__('resultado', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        buscando: '<?php echo esc_js(__('Buscando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        error: '<?php echo esc_js(__('Error al buscar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        seguidores: '<?php echo esc_js(__('seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        publicaciones: '<?php echo esc_js(__('publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        miembros: '<?php echo esc_js(__('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>',
                        usos: '<?php echo esc_js(__('usos', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>'
                    }
                };
            }
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Búsqueda principal
     */
    public function buscar($termino, $opciones = []) {
        global $wpdb;

        $termino = sanitize_text_field($termino);
        if (strlen($termino) < 2) {
            return [
                'success' => false,
                'error' => __('El término debe tener al menos 2 caracteres', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $opciones = wp_parse_args($opciones, [
            'tipos' => ['publicaciones', 'usuarios', 'hashtags', 'comunidades'],
            'limite' => 10,
            'offset' => 0,
            'fecha' => '',
            'hashtag' => '',
            'ubicacion' => '',
            'verificados' => false,
            'ordenar' => 'relevancia',
        ]);

        $resultados = [];
        $total = 0;

        // Buscar en cada tipo
        foreach ($opciones['tipos'] as $tipo) {
            $metodo_busqueda = 'buscar_' . $tipo;
            if (method_exists($this, $metodo_busqueda)) {
                $resultado_tipo = $this->$metodo_busqueda($termino, $opciones);
                if (!empty($resultado_tipo['items'])) {
                    $resultados[$tipo] = $resultado_tipo;
                    $total += $resultado_tipo['total'];
                }
            }
        }

        return [
            'success' => true,
            'termino' => $termino,
            'total' => $total,
            'resultados' => $resultados,
            'filtros' => $opciones,
        ];
    }

    /**
     * Buscar publicaciones
     */
    private function buscar_publicaciones($termino, $opciones) {
        global $wpdb;

        $tabla = $this->prefix . 'social_publicaciones';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['items' => [], 'total' => 0];
        }

        $where = ["p.estado = 'publicado'", "p.privacidad = 'publico'"];
        $params = [];

        // Búsqueda en contenido
        $where[] = "p.contenido LIKE %s";
        $params[] = '%' . $wpdb->esc_like($termino) . '%';

        // Filtro de fecha
        if (!empty($opciones['fecha'])) {
            $fecha_filtro = $this->get_fecha_filtro($opciones['fecha']);
            if ($fecha_filtro) {
                $where[] = "p.fecha_creacion >= %s";
                $params[] = $fecha_filtro;
            }
        }

        // Filtro de ubicación
        if (!empty($opciones['ubicacion'])) {
            $where[] = "p.ubicacion LIKE %s";
            $params[] = '%' . $wpdb->esc_like($opciones['ubicacion']) . '%';
        }

        // Filtro de hashtag
        if (!empty($opciones['hashtag'])) {
            $hashtag_limpio = ltrim($opciones['hashtag'], '#');
            $tabla_hashtags = $this->prefix . 'social_hashtags';
            $tabla_rel = $this->prefix . 'social_publicaciones_hashtags';

            if (Flavor_Chat_Helpers::tabla_existe($tabla_hashtags)) {
                $where[] = "p.id IN (
                    SELECT ph.publicacion_id FROM {$tabla_rel} ph
                    INNER JOIN {$tabla_hashtags} h ON ph.hashtag_id = h.id
                    WHERE h.nombre = %s
                )";
                $params[] = $hashtag_limpio;
            }
        }

        // Ordenación
        $orderby = $this->get_orderby_publicaciones($opciones['ordenar']);

        $where_sql = implode(' AND ', $where);

        // Contar total
        $count_sql = "SELECT COUNT(*) FROM {$tabla} p WHERE {$where_sql}";
        $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));

        // Obtener resultados
        $params[] = $opciones['limite'];
        $params[] = $opciones['offset'];

        $sql = $wpdb->prepare(
            "SELECT p.*, u.display_name, u.user_email
             FROM {$tabla} p
             INNER JOIN {$wpdb->users} u ON p.usuario_id = u.ID
             WHERE {$where_sql}
             ORDER BY {$orderby}
             LIMIT %d OFFSET %d",
            $params
        );

        $filas = $wpdb->get_results($sql);
        $items = [];

        foreach ($filas as $fila) {
            $items[] = [
                'id' => $fila->id,
                'tipo' => 'publicacion',
                'contenido' => wp_trim_words($fila->contenido, 30),
                'autor' => [
                    'id' => $fila->usuario_id,
                    'nombre' => $fila->display_name,
                    'avatar' => get_avatar_url($fila->usuario_id, ['size' => 48]),
                ],
                'likes' => (int) $fila->likes_count,
                'comentarios' => (int) $fila->comentarios_count,
                'fecha' => human_time_diff(strtotime($fila->fecha_creacion), current_time('timestamp')),
                'url' => $this->get_url_publicacion($fila->id),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'etiqueta' => __('Publicaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Buscar usuarios
     */
    private function buscar_usuarios($termino, $opciones) {
        global $wpdb;

        $tabla_perfiles = $this->prefix . 'social_perfiles';

        $where = ["1=1"];
        $params = [];

        // Buscar en nombre, email, bio
        $where[] = "(u.display_name LIKE %s OR u.user_login LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($termino) . '%';
        $params[] = '%' . $wpdb->esc_like($termino) . '%';

        // Filtro de verificados
        if ($opciones['verificados'] && Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $where[] = "p.verificado = 1";
        }

        $where_sql = implode(' AND ', $where);

        // Ordenación
        $orderby = 'u.display_name ASC';
        if ($opciones['ordenar'] === 'popular' && Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $orderby = 'p.seguidores_count DESC';
        }

        // Query base
        $join_perfil = '';
        $select_extra = '0 as seguidores_count, 0 as publicaciones_count, 0 as verificado, NULL as bio';

        if (Flavor_Chat_Helpers::tabla_existe($tabla_perfiles)) {
            $join_perfil = "LEFT JOIN {$tabla_perfiles} p ON u.ID = p.usuario_id";
            $select_extra = 'COALESCE(p.seguidores_count, 0) as seguidores_count,
                             COALESCE(p.publicaciones_count, 0) as publicaciones_count,
                             COALESCE(p.verificado, 0) as verificado,
                             p.bio';
        }

        // Contar total
        $count_sql = "SELECT COUNT(DISTINCT u.ID) FROM {$wpdb->users} u {$join_perfil} WHERE {$where_sql}";
        $total = (int) $wpdb->get_var($wpdb->prepare($count_sql, $params));

        // Obtener resultados
        $params[] = $opciones['limite'];
        $params[] = $opciones['offset'];

        $sql = $wpdb->prepare(
            "SELECT DISTINCT u.ID, u.display_name, u.user_login, {$select_extra}
             FROM {$wpdb->users} u
             {$join_perfil}
             WHERE {$where_sql}
             ORDER BY {$orderby}
             LIMIT %d OFFSET %d",
            $params
        );

        $filas = $wpdb->get_results($sql);
        $items = [];

        foreach ($filas as $fila) {
            $items[] = [
                'id' => $fila->ID,
                'tipo' => 'usuario',
                'nombre' => $fila->display_name,
                'username' => '@' . $fila->user_login,
                'avatar' => get_avatar_url($fila->ID, ['size' => 64]),
                'bio' => $fila->bio ? wp_trim_words($fila->bio, 15) : '',
                'seguidores' => (int) $fila->seguidores_count,
                'publicaciones' => (int) $fila->publicaciones_count,
                'verificado' => (bool) $fila->verificado,
                'url' => $this->get_url_perfil($fila->ID),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'etiqueta' => __('Usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Buscar hashtags
     */
    private function buscar_hashtags($termino, $opciones) {
        global $wpdb;

        $tabla = $this->prefix . 'social_hashtags';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['items' => [], 'total' => 0];
        }

        $termino_limpio = ltrim($termino, '#');

        // Contar total
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE nombre LIKE %s",
            '%' . $wpdb->esc_like($termino_limpio) . '%'
        ));

        // Obtener resultados
        $filas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE nombre LIKE %s
             ORDER BY usos_count DESC
             LIMIT %d OFFSET %d",
            '%' . $wpdb->esc_like($termino_limpio) . '%',
            $opciones['limite'],
            $opciones['offset']
        ));

        $items = [];
        foreach ($filas as $fila) {
            $items[] = [
                'id' => $fila->id,
                'tipo' => 'hashtag',
                'nombre' => '#' . $fila->nombre,
                'usos' => (int) $fila->usos_count,
                'trending' => (bool) $fila->trending,
                'url' => $this->get_url_hashtag($fila->nombre),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'etiqueta' => __('Hashtags', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Buscar comunidades
     */
    private function buscar_comunidades($termino, $opciones) {
        global $wpdb;

        $tabla = $this->prefix . 'comunidades';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla)) {
            return ['items' => [], 'total' => 0];
        }

        $where = ["estado = 'activa'"];
        $params = [];

        $where[] = "(nombre LIKE %s OR descripcion LIKE %s)";
        $params[] = '%' . $wpdb->esc_like($termino) . '%';
        $params[] = '%' . $wpdb->esc_like($termino) . '%';

        $where_sql = implode(' AND ', $where);

        // Contar
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla} WHERE {$where_sql}",
            $params
        ));

        // Obtener
        $params[] = $opciones['limite'];
        $params[] = $opciones['offset'];

        $filas = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla}
             WHERE {$where_sql}
             ORDER BY miembros_count DESC
             LIMIT %d OFFSET %d",
            $params
        ));

        $items = [];
        foreach ($filas as $fila) {
            $items[] = [
                'id' => $fila->id,
                'tipo' => 'comunidad',
                'nombre' => $fila->nombre,
                'descripcion' => wp_trim_words($fila->descripcion ?? '', 20),
                'miembros' => (int) ($fila->miembros_count ?? 0),
                'imagen' => $fila->imagen ?? '',
                'url' => $this->get_url_comunidad($fila->id),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'etiqueta' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];
    }

    /**
     * Obtener sugerencias de autocompletado
     */
    public function get_sugerencias($termino) {
        global $wpdb;

        $termino = sanitize_text_field($termino);
        if (strlen($termino) < 2) {
            return [];
        }

        $sugerencias = [];

        // Sugerir hashtags
        $tabla_hashtags = $this->prefix . 'social_hashtags';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_hashtags)) {
            $termino_limpio = ltrim($termino, '#');
            $hashtags = $wpdb->get_results($wpdb->prepare(
                "SELECT nombre, usos_count FROM {$tabla_hashtags}
                 WHERE nombre LIKE %s
                 ORDER BY usos_count DESC
                 LIMIT 5",
                $wpdb->esc_like($termino_limpio) . '%'
            ));

            foreach ($hashtags as $hash) {
                $sugerencias[] = [
                    'tipo' => 'hashtag',
                    'texto' => '#' . $hash->nombre,
                    'subtexto' => sprintf(__('%d usos', FLAVOR_PLATFORM_TEXT_DOMAIN), $hash->usos_count),
                    'icono' => 'dashicons-tag',
                ];
            }
        }

        // Sugerir usuarios
        $usuarios = $wpdb->get_results($wpdb->prepare(
            "SELECT ID, display_name, user_login FROM {$wpdb->users}
             WHERE display_name LIKE %s OR user_login LIKE %s
             LIMIT 5",
            '%' . $wpdb->esc_like($termino) . '%',
            '%' . $wpdb->esc_like($termino) . '%'
        ));

        foreach ($usuarios as $usuario) {
            $sugerencias[] = [
                'tipo' => 'usuario',
                'texto' => $usuario->display_name,
                'subtexto' => '@' . $usuario->user_login,
                'icono' => 'dashicons-admin-users',
                'avatar' => get_avatar_url($usuario->ID, ['size' => 32]),
            ];
        }

        return array_slice($sugerencias, 0, 8);
    }

    /**
     * Obtener filtro de fecha SQL
     */
    private function get_fecha_filtro($periodo) {
        switch ($periodo) {
            case 'hoy':
                return date('Y-m-d 00:00:00');
            case 'semana':
                return date('Y-m-d H:i:s', strtotime('-7 days'));
            case 'mes':
                return date('Y-m-d H:i:s', strtotime('-30 days'));
            case 'ano':
                return date('Y-m-d H:i:s', strtotime('-1 year'));
            default:
                return null;
        }
    }

    /**
     * Obtener ORDER BY para publicaciones
     */
    private function get_orderby_publicaciones($ordenar) {
        switch ($ordenar) {
            case 'reciente':
                return 'p.fecha_creacion DESC';
            case 'popular':
                return '(p.likes_count + p.comentarios_count * 2 + p.compartidos_count * 3) DESC';
            default: // relevancia
                return 'p.fecha_creacion DESC';
        }
    }

    /**
     * URLs de contenido
     */
    private function get_url_publicacion($id) {
        return add_query_arg(['publicacion' => $id], home_url('/red-social/'));
    }

    private function get_url_perfil($user_id) {
        return add_query_arg(['usuario' => $user_id], home_url('/red-social/perfil/'));
    }

    private function get_url_hashtag($hashtag) {
        return add_query_arg(['hashtag' => $hashtag], home_url('/red-social/explorar/'));
    }

    private function get_url_comunidad($id) {
        return add_query_arg(['comunidad' => $id], home_url('/comunidades/'));
    }

    /**
     * AJAX: Buscar
     */
    public function ajax_buscar() {
        check_ajax_referer('flavor_social_search_nonce', 'nonce');

        $termino = sanitize_text_field($_POST['termino'] ?? '');
        $tipos = isset($_POST['tipos']) ? array_map('sanitize_key', (array) $_POST['tipos']) : [];
        $filtros = [
            'tipos' => !empty($tipos) ? $tipos : ['publicaciones', 'usuarios', 'hashtags', 'comunidades'],
            'limite' => intval($_POST['limite'] ?? 10),
            'offset' => intval($_POST['offset'] ?? 0),
            'fecha' => sanitize_key($_POST['fecha'] ?? ''),
            'hashtag' => sanitize_text_field($_POST['hashtag'] ?? ''),
            'ubicacion' => sanitize_text_field($_POST['ubicacion'] ?? ''),
            'verificados' => !empty($_POST['verificados']),
            'ordenar' => sanitize_key($_POST['ordenar'] ?? 'relevancia'),
        ];

        $resultado = $this->buscar($termino, $filtros);

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            wp_send_json_error($resultado['error']);
        }
    }

    /**
     * AJAX: Sugerencias
     */
    public function ajax_sugerencias() {
        check_ajax_referer('flavor_social_search_nonce', 'nonce');

        $termino = sanitize_text_field($_POST['termino'] ?? '');
        $sugerencias = $this->get_sugerencias($termino);

        wp_send_json_success($sugerencias);
    }

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        register_rest_route('flavor-app/v1', '/social/search', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_buscar'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'tipos' => [
                    'type' => 'string',
                    'default' => 'publicaciones,usuarios,hashtags,comunidades',
                ],
                'limite' => [
                    'type' => 'integer',
                    'default' => 10,
                ],
                'offset' => [
                    'type' => 'integer',
                    'default' => 0,
                ],
            ],
        ]);

        register_rest_route('flavor-app/v1', '/social/search/suggestions', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_sugerencias'],
            'permission_callback' => '__return_true',
            'args' => [
                'q' => [
                    'required' => true,
                    'type' => 'string',
                ],
            ],
        ]);
    }

    /**
     * REST: Buscar
     */
    public function rest_buscar(WP_REST_Request $request) {
        $termino = $request->get_param('q');
        $tipos = array_map('trim', explode(',', $request->get_param('tipos')));

        $resultado = $this->buscar($termino, [
            'tipos' => $tipos,
            'limite' => $request->get_param('limite'),
            'offset' => $request->get_param('offset'),
        ]);

        return new WP_REST_Response($resultado);
    }

    /**
     * REST: Sugerencias
     */
    public function rest_sugerencias(WP_REST_Request $request) {
        $termino = sanitize_text_field($request->get_param('q'));
        return new WP_REST_Response($this->get_sugerencias($termino));
    }

    /**
     * Encolar assets condicionalmente
     */
    public function maybe_enqueue_assets() {
        global $post;

        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'flavor_busqueda_social')) {
            $this->enqueue_assets();
        }
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'flavor-social-search',
            FLAVOR_CHAT_IA_URL . 'assets/css/modules/social-search.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-social-search',
            FLAVOR_CHAT_IA_URL . 'assets/js/social-search.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );
    }
}

// Inicializar
Flavor_Social_Search::get_instance();
