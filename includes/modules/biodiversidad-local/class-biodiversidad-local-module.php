<?php
/**
 * Módulo: Biodiversidad Local
 *
 * Catalogación comunitaria de especies, proyectos de conservación,
 * avistamientos y protección de ecosistemas locales.
 *
 * @package FlavorChatIA
 * @subpackage Modules\BiodiversidadLocal
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Biodiversidad_Local_Module extends Flavor_Chat_Module_Base {

    /**
     * Categorías de especies
     */
    const CATEGORIAS_ESPECIES = [
        'flora' => [
            'nombre' => 'Flora',
            'icono' => 'dashicons-palmtree',
            'color' => '#22c55e',
            'subcategorias' => ['arboles', 'arbustos', 'plantas_herbaceas', 'hongos', 'liquenes', 'algas'],
        ],
        'fauna_vertebrados' => [
            'nombre' => 'Fauna Vertebrada',
            'icono' => 'dashicons-pets',
            'color' => '#f97316',
            'subcategorias' => ['aves', 'mamiferos', 'reptiles', 'anfibios', 'peces'],
        ],
        'fauna_invertebrados' => [
            'nombre' => 'Invertebrados',
            'icono' => 'dashicons-admin-site-alt',
            'color' => '#a855f7',
            'subcategorias' => ['insectos', 'aracnidos', 'moluscos', 'crustaceos', 'otros'],
        ],
    ];

    /**
     * Estados de conservación (basado en IUCN)
     */
    const ESTADOS_CONSERVACION = [
        'no_evaluada' => ['nombre' => 'No Evaluada', 'color' => '#6b7280', 'icono' => 'NE'],
        'preocupacion_menor' => ['nombre' => 'Preocupación Menor', 'color' => '#22c55e', 'icono' => 'LC'],
        'casi_amenazada' => ['nombre' => 'Casi Amenazada', 'color' => '#84cc16', 'icono' => 'NT'],
        'vulnerable' => ['nombre' => 'Vulnerable', 'color' => '#eab308', 'icono' => 'VU'],
        'en_peligro' => ['nombre' => 'En Peligro', 'color' => '#f97316', 'icono' => 'EN'],
        'en_peligro_critico' => ['nombre' => 'En Peligro Crítico', 'color' => '#ef4444', 'icono' => 'CR'],
        'extinta_silvestre' => ['nombre' => 'Extinta en Estado Silvestre', 'color' => '#1f2937', 'icono' => 'EW'],
        'extinta' => ['nombre' => 'Extinta', 'color' => '#000000', 'icono' => 'EX'],
    ];

    /**
     * Tipos de hábitat
     */
    const TIPOS_HABITAT = [
        'bosque' => ['nombre' => 'Bosque', 'icono' => 'dashicons-palmtree'],
        'pradera' => ['nombre' => 'Pradera/Pastizal', 'icono' => 'dashicons-welcome-view-site'],
        'humedal' => ['nombre' => 'Humedal', 'icono' => 'dashicons-format-audio'],
        'rio' => ['nombre' => 'Río/Arroyo', 'icono' => 'dashicons-chart-line'],
        'montaña' => ['nombre' => 'Montaña', 'icono' => 'dashicons-image-filter'],
        'costa' => ['nombre' => 'Costa/Litoral', 'icono' => 'dashicons-waves'],
        'urbano' => ['nombre' => 'Urbano/Periurbano', 'icono' => 'dashicons-building'],
        'agricola' => ['nombre' => 'Agrícola', 'icono' => 'dashicons-carrot'],
    ];

    /**
     * Tipos de proyectos de conservación
     */
    const TIPOS_PROYECTO = [
        'reforestacion' => ['nombre' => 'Reforestación', 'icono' => 'dashicons-palmtree', 'color' => '#22c55e'],
        'limpieza' => ['nombre' => 'Limpieza de Espacios', 'icono' => 'dashicons-trash', 'color' => '#3b82f6'],
        'censo' => ['nombre' => 'Censo de Especies', 'icono' => 'dashicons-clipboard', 'color' => '#8b5cf6'],
        'proteccion' => ['nombre' => 'Protección de Hábitat', 'icono' => 'dashicons-shield', 'color' => '#f59e0b'],
        'educacion' => ['nombre' => 'Educación Ambiental', 'icono' => 'dashicons-book-alt', 'color' => '#06b6d4'],
        'polinizadores' => ['nombre' => 'Apoyo a Polinizadores', 'icono' => 'dashicons-admin-site-alt', 'color' => '#f97316'],
        'fauna_silvestre' => ['nombre' => 'Refugio Fauna Silvestre', 'icono' => 'dashicons-pets', 'color' => '#a855f7'],
        'semillas' => ['nombre' => 'Banco de Semillas', 'icono' => 'dashicons-marker', 'color' => '#84cc16'],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'biodiversidad_local';
        $this->name = __('Biodiversidad Local', 'flavor-chat-ia');
        $this->description = __('Catálogo comunitario de especies locales, proyectos de conservación y ciencia ciudadana.', 'flavor-chat-ia');
        $this->icon = 'dashicons-admin-site-alt3';
        $this->category = 'medioambiente';
        $this->visibility = 'registered';
        $this->version = '1.0.0';

        parent::__construct();
    }

    /**
     * Obtiene la valoración de conciencia del módulo
     *
     * @return array
     */
    public function get_consciousness_valuation(): array {
        return [
            'puntuacion_total' => 87,
            'premisas' => [
                'conciencia_fundamental' => [
                    'puntuacion' => 19,
                    'descripcion' => __('Reconoce el valor intrínseco de cada especie y su derecho a existir, más allá de su utilidad para los humanos.', 'flavor-chat-ia'),
                ],
                'abundancia_organizable' => [
                    'puntuacion' => 18,
                    'descripcion' => __('Cataloga y organiza el conocimiento colectivo sobre la riqueza natural del territorio como patrimonio común.', 'flavor-chat-ia'),
                ],
                'interdependencia_radical' => [
                    'puntuacion' => 20,
                    'descripcion' => __('Visualiza las conexiones ecosistémicas y cómo cada especie depende de otras en la trama de la vida.', 'flavor-chat-ia'),
                ],
                'madurez_ciclica' => [
                    'puntuacion' => 15,
                    'descripcion' => __('Respeta los ciclos naturales y las temporadas de reproducción, migración y descanso de las especies.', 'flavor-chat-ia'),
                ],
                'valor_intrinseco' => [
                    'puntuacion' => 15,
                    'descripcion' => __('Documenta especies sin criterios de utilidad, valorando por igual a las consideradas "humildes" o "insignificantes".', 'flavor-chat-ia'),
                ],
            ],
            'fortalezas' => [
                __('Excelente reconocimiento de la interdependencia ecosistémica', 'flavor-chat-ia'),
                __('Fuerte valoración de la conciencia en todas las formas de vida', 'flavor-chat-ia'),
                __('Promueve la ciencia ciudadana como forma de conexión con la naturaleza', 'flavor-chat-ia'),
            ],
            'areas_mejora' => [
                __('Podría incorporar más elementos de sabiduría indígena sobre biodiversidad', 'flavor-chat-ia'),
                __('Integrar perspectivas de derechos de la naturaleza', 'flavor-chat-ia'),
            ],
        ];
    }

    /**
     * Configura el módulo
     */
    protected function setup_module() {
        $this->register_cpt_especie();
        $this->register_cpt_avistamiento();
        $this->register_cpt_proyecto_conservacion();
        $this->register_taxonomies();
        $this->register_ajax_handlers();
    }

    /**
     * Registra CPT: Especie Local
     */
    private function register_cpt_especie() {
        register_post_type('bl_especie', [
            'labels' => [
                'name' => __('Especies', 'flavor-chat-ia'),
                'singular_name' => __('Especie', 'flavor-chat-ia'),
                'add_new' => __('Añadir Especie', 'flavor-chat-ia'),
                'add_new_item' => __('Añadir Nueva Especie', 'flavor-chat-ia'),
                'edit_item' => __('Editar Especie', 'flavor-chat-ia'),
                'new_item' => __('Nueva Especie', 'flavor-chat-ia'),
                'view_item' => __('Ver Especie', 'flavor-chat-ia'),
                'search_items' => __('Buscar Especies', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/especies'],
            'menu_icon' => 'dashicons-admin-site-alt3',
        ]);
    }

    /**
     * Registra CPT: Avistamiento
     */
    private function register_cpt_avistamiento() {
        register_post_type('bl_avistamiento', [
            'labels' => [
                'name' => __('Avistamientos', 'flavor-chat-ia'),
                'singular_name' => __('Avistamiento', 'flavor-chat-ia'),
                'add_new' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'add_new_item' => __('Registrar Nuevo Avistamiento', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/avistamientos'],
        ]);
    }

    /**
     * Registra CPT: Proyecto de Conservación
     */
    private function register_cpt_proyecto_conservacion() {
        register_post_type('bl_proyecto', [
            'labels' => [
                'name' => __('Proyectos Conservación', 'flavor-chat-ia'),
                'singular_name' => __('Proyecto', 'flavor-chat-ia'),
                'add_new' => __('Crear Proyecto', 'flavor-chat-ia'),
                'add_new_item' => __('Crear Nuevo Proyecto', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail', 'author'],
            'has_archive' => true,
            'rewrite' => ['slug' => 'biodiversidad/proyectos'],
        ]);
    }

    /**
     * Registra taxonomías
     */
    private function register_taxonomies() {
        // Categoría de especie
        register_taxonomy('bl_categoria', 'bl_especie', [
            'labels' => [
                'name' => __('Categorías', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'hierarchical' => true,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'biodiversidad/categoria'],
        ]);

        // Hábitat
        register_taxonomy('bl_habitat', ['bl_especie', 'bl_avistamiento'], [
            'labels' => [
                'name' => __('Hábitats', 'flavor-chat-ia'),
                'singular_name' => __('Hábitat', 'flavor-chat-ia'),
            ],
            'hierarchical' => false,
            'show_admin_column' => true,
            'rewrite' => ['slug' => 'biodiversidad/habitat'],
        ]);
    }

    /**
     * Registra manejadores AJAX
     */
    private function register_ajax_handlers() {
        add_action('wp_ajax_bl_registrar_avistamiento', [$this, 'ajax_registrar_avistamiento']);
        add_action('wp_ajax_bl_registrar_especie', [$this, 'ajax_registrar_especie']);
        add_action('wp_ajax_bl_crear_proyecto', [$this, 'ajax_crear_proyecto']);
        add_action('wp_ajax_bl_participar_proyecto', [$this, 'ajax_participar_proyecto']);
        add_action('wp_ajax_bl_validar_avistamiento', [$this, 'ajax_validar_avistamiento']);
    }

    /**
     * Registra shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('biodiversidad_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('biodiversidad_mapa', [$this, 'shortcode_mapa']);
        add_shortcode('biodiversidad_registrar', [$this, 'shortcode_registrar']);
        add_shortcode('biodiversidad_proyectos', [$this, 'shortcode_proyectos']);
        add_shortcode('biodiversidad_mis_avistamientos', [$this, 'shortcode_mis_avistamientos']);
    }

    /**
     * Encola scripts y estilos
     */
    public function enqueue_assets() {
        $base_url = FLAVOR_CHAT_IA_URL . 'includes/modules/biodiversidad-local/assets/';

        wp_enqueue_style(
            'flavor-biodiversidad',
            $base_url . 'css/biodiversidad-local.css',
            [],
            $this->version
        );

        wp_enqueue_script(
            'flavor-biodiversidad',
            $base_url . 'js/biodiversidad-local.js',
            ['jquery'],
            $this->version,
            true
        );

        wp_localize_script('flavor-biodiversidad', 'flavorBiodiversidad', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('biodiversidad_nonce'),
            'categorias' => self::CATEGORIAS_ESPECIES,
            'estados' => self::ESTADOS_CONSERVACION,
            'habitats' => self::TIPOS_HABITAT,
            'i18n' => [
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'success' => __('Operación completada', 'flavor-chat-ia'),
                'confirm_avistamiento' => __('¿Registrar este avistamiento?', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Shortcode: Catálogo de especies
     */
    public function shortcode_catalogo($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mapa de avistamientos
     */
    public function shortcode_mapa($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/mapa.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Registrar avistamiento
     */
    public function shortcode_registrar($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/registrar.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Proyectos de conservación
     */
    public function shortcode_proyectos($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/proyectos.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis avistamientos
     */
    public function shortcode_mis_avistamientos($atts) {
        $this->enqueue_assets();
        ob_start();
        include __DIR__ . '/templates/mis-avistamientos.php';
        return ob_get_clean();
    }

    /**
     * AJAX: Registrar avistamiento
     */
    public function ajax_registrar_avistamiento() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $especie_id = intval($_POST['especie_id'] ?? 0);
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $latitud = floatval($_POST['latitud'] ?? 0);
        $longitud = floatval($_POST['longitud'] ?? 0);
        $cantidad = intval($_POST['cantidad'] ?? 1);
        $habitat = sanitize_text_field($_POST['habitat'] ?? '');
        $fecha = sanitize_text_field($_POST['fecha'] ?? current_time('Y-m-d'));

        $especie = get_post($especie_id);
        $titulo = sprintf(
            __('Avistamiento: %s - %s', 'flavor-chat-ia'),
            $especie ? $especie->post_title : __('Especie desconocida', 'flavor-chat-ia'),
            date_i18n('j M Y', strtotime($fecha))
        );

        $avistamiento_id = wp_insert_post([
            'post_type' => 'bl_avistamiento',
            'post_status' => 'pending',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($avistamiento_id)) {
            wp_send_json_error(['message' => $avistamiento_id->get_error_message()]);
        }

        update_post_meta($avistamiento_id, '_bl_especie_id', $especie_id);
        update_post_meta($avistamiento_id, '_bl_latitud', $latitud);
        update_post_meta($avistamiento_id, '_bl_longitud', $longitud);
        update_post_meta($avistamiento_id, '_bl_cantidad', $cantidad);
        update_post_meta($avistamiento_id, '_bl_fecha', $fecha);
        update_post_meta($avistamiento_id, '_bl_validaciones', []);

        if ($habitat) {
            wp_set_object_terms($avistamiento_id, $habitat, 'bl_habitat');
        }

        wp_send_json_success([
            'message' => __('Avistamiento registrado. Será revisado por la comunidad.', 'flavor-chat-ia'),
            'avistamiento_id' => $avistamiento_id,
        ]);
    }

    /**
     * AJAX: Registrar nueva especie
     */
    public function ajax_registrar_especie() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $nombre_comun = sanitize_text_field($_POST['nombre_comun'] ?? '');
        $nombre_cientifico = sanitize_text_field($_POST['nombre_cientifico'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_text_field($_POST['categoria'] ?? '');
        $estado = sanitize_text_field($_POST['estado_conservacion'] ?? 'no_evaluada');
        $habitats = array_map('sanitize_text_field', $_POST['habitats'] ?? []);

        if (empty($nombre_comun)) {
            wp_send_json_error(['message' => __('El nombre común es requerido', 'flavor-chat-ia')]);
        }

        $especie_id = wp_insert_post([
            'post_type' => 'bl_especie',
            'post_status' => 'pending',
            'post_title' => $nombre_comun,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($especie_id)) {
            wp_send_json_error(['message' => $especie_id->get_error_message()]);
        }

        update_post_meta($especie_id, '_bl_nombre_cientifico', $nombre_cientifico);
        update_post_meta($especie_id, '_bl_estado_conservacion', $estado);

        if ($categoria) {
            wp_set_object_terms($especie_id, $categoria, 'bl_categoria');
        }

        if (!empty($habitats)) {
            wp_set_object_terms($especie_id, $habitats, 'bl_habitat');
        }

        wp_send_json_success([
            'message' => __('Especie propuesta. Será revisada por la comunidad.', 'flavor-chat-ia'),
            'especie_id' => $especie_id,
        ]);
    }

    /**
     * AJAX: Crear proyecto de conservación
     */
    public function ajax_crear_proyecto() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $fecha_inicio = sanitize_text_field($_POST['fecha_inicio'] ?? '');
        $ubicacion = sanitize_text_field($_POST['ubicacion'] ?? '');
        $participantes_max = intval($_POST['participantes_max'] ?? 0);

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-chat-ia')]);
        }

        $proyecto_id = wp_insert_post([
            'post_type' => 'bl_proyecto',
            'post_status' => 'pending',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($proyecto_id)) {
            wp_send_json_error(['message' => $proyecto_id->get_error_message()]);
        }

        update_post_meta($proyecto_id, '_bl_tipo', $tipo);
        update_post_meta($proyecto_id, '_bl_fecha_inicio', $fecha_inicio);
        update_post_meta($proyecto_id, '_bl_ubicacion', $ubicacion);
        update_post_meta($proyecto_id, '_bl_participantes_max', $participantes_max);
        update_post_meta($proyecto_id, '_bl_participantes', [get_current_user_id()]);

        wp_send_json_success([
            'message' => __('Proyecto creado. Será revisado para su publicación.', 'flavor-chat-ia'),
            'proyecto_id' => $proyecto_id,
        ]);
    }

    /**
     * AJAX: Participar en proyecto
     */
    public function ajax_participar_proyecto() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $proyecto_id = intval($_POST['proyecto_id'] ?? 0);
        $user_id = get_current_user_id();

        $participantes = get_post_meta($proyecto_id, '_bl_participantes', true) ?: [];
        $max_participantes = intval(get_post_meta($proyecto_id, '_bl_participantes_max', true));

        if (in_array($user_id, $participantes)) {
            wp_send_json_error(['message' => __('Ya estás participando en este proyecto', 'flavor-chat-ia')]);
        }

        if ($max_participantes > 0 && count($participantes) >= $max_participantes) {
            wp_send_json_error(['message' => __('El proyecto ha alcanzado el máximo de participantes', 'flavor-chat-ia')]);
        }

        $participantes[] = $user_id;
        update_post_meta($proyecto_id, '_bl_participantes', $participantes);

        wp_send_json_success([
            'message' => __('Te has unido al proyecto de conservación', 'flavor-chat-ia'),
            'participantes' => count($participantes),
        ]);
    }

    /**
     * AJAX: Validar avistamiento
     */
    public function ajax_validar_avistamiento() {
        check_ajax_referer('biodiversidad_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $avistamiento_id = intval($_POST['avistamiento_id'] ?? 0);
        $es_valido = filter_var($_POST['es_valido'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $user_id = get_current_user_id();

        $validaciones = get_post_meta($avistamiento_id, '_bl_validaciones', true) ?: [];

        // Evitar doble validación
        foreach ($validaciones as $val) {
            if ($val['user_id'] === $user_id) {
                wp_send_json_error(['message' => __('Ya has validado este avistamiento', 'flavor-chat-ia')]);
            }
        }

        $validaciones[] = [
            'user_id' => $user_id,
            'es_valido' => $es_valido,
            'fecha' => current_time('mysql'),
        ];

        update_post_meta($avistamiento_id, '_bl_validaciones', $validaciones);

        // Auto-publicar si tiene 3+ validaciones positivas
        $positivas = array_filter($validaciones, fn($v) => $v['es_valido']);
        if (count($positivas) >= 3) {
            wp_update_post([
                'ID' => $avistamiento_id,
                'post_status' => 'publish',
            ]);
        }

        wp_send_json_success([
            'message' => __('Gracias por tu validación', 'flavor-chat-ia'),
            'validaciones_positivas' => count($positivas),
            'validaciones_total' => count($validaciones),
        ]);
    }

    /**
     * Obtiene estadísticas del módulo
     *
     * @return array
     */
    public function get_estadisticas(): array {
        $especies = wp_count_posts('bl_especie');
        $avistamientos = wp_count_posts('bl_avistamiento');
        $proyectos = wp_count_posts('bl_proyecto');

        $user_id = get_current_user_id();
        $mis_avistamientos = new WP_Query([
            'post_type' => 'bl_avistamiento',
            'author' => $user_id,
            'posts_per_page' => -1,
            'fields' => 'ids',
        ]);

        return [
            'especies_catalogadas' => $especies->publish ?? 0,
            'avistamientos_total' => $avistamientos->publish ?? 0,
            'avistamientos_pendientes' => $avistamientos->pending ?? 0,
            'proyectos_activos' => $proyectos->publish ?? 0,
            'mis_avistamientos' => $mis_avistamientos->found_posts,
        ];
    }

    /**
     * Obtiene páginas del frontend
     *
     * @return array
     */
    public function get_frontend_pages(): array {
        return [
            'catalogo' => [
                'titulo' => __('Catálogo de Especies', 'flavor-chat-ia'),
                'slug' => 'biodiversidad',
                'shortcode' => '[biodiversidad_catalogo]',
                'icono' => 'dashicons-admin-site-alt3',
            ],
            'mapa' => [
                'titulo' => __('Mapa de Avistamientos', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/mapa',
                'shortcode' => '[biodiversidad_mapa]',
                'icono' => 'dashicons-location-alt',
            ],
            'registrar' => [
                'titulo' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/registrar',
                'shortcode' => '[biodiversidad_registrar]',
                'icono' => 'dashicons-camera',
            ],
            'proyectos' => [
                'titulo' => __('Proyectos de Conservación', 'flavor-chat-ia'),
                'slug' => 'biodiversidad/proyectos',
                'shortcode' => '[biodiversidad_proyectos]',
                'icono' => 'dashicons-groups',
            ],
            'mis_avistamientos' => [
                'titulo' => __('Mis Avistamientos', 'flavor-chat-ia'),
                'slug' => 'mi-portal/biodiversidad',
                'shortcode' => '[biodiversidad_mis_avistamientos]',
                'icono' => 'dashicons-portfolio',
            ],
        ];
    }

    /**
     * Obtiene acciones del módulo
     *
     * @return array
     */
    public function get_actions(): array {
        return [
            'registrar_avistamiento' => [
                'name' => __('Registrar Avistamiento', 'flavor-chat-ia'),
                'description' => __('Registra un avistamiento de fauna o flora', 'flavor-chat-ia'),
                'callback' => [$this, 'action_registrar_avistamiento'],
            ],
            'buscar_especie' => [
                'name' => __('Buscar Especie', 'flavor-chat-ia'),
                'description' => __('Busca información sobre una especie local', 'flavor-chat-ia'),
                'callback' => [$this, 'action_buscar_especie'],
            ],
            'listar_proyectos' => [
                'name' => __('Ver Proyectos', 'flavor-chat-ia'),
                'description' => __('Lista los proyectos de conservación activos', 'flavor-chat-ia'),
                'callback' => [$this, 'action_listar_proyectos'],
            ],
        ];
    }

    /**
     * Acción: Registrar avistamiento
     */
    public function action_registrar_avistamiento($params) {
        return [
            'success' => true,
            'message' => __('Para registrar un avistamiento, visita la sección de Biodiversidad.', 'flavor-chat-ia'),
            'url' => home_url('/biodiversidad/registrar/'),
        ];
    }

    /**
     * Acción: Buscar especie
     */
    public function action_buscar_especie($params) {
        $termino = $params['especie'] ?? '';

        if (empty($termino)) {
            return [
                'success' => false,
                'message' => __('Indica el nombre de la especie que buscas', 'flavor-chat-ia'),
            ];
        }

        $especies = new WP_Query([
            'post_type' => 'bl_especie',
            'post_status' => 'publish',
            's' => $termino,
            'posts_per_page' => 5,
        ]);

        if (!$especies->have_posts()) {
            return [
                'success' => true,
                'message' => sprintf(__('No encontré especies con el nombre "%s"', 'flavor-chat-ia'), $termino),
                'especies' => [],
            ];
        }

        $resultados = [];
        foreach ($especies->posts as $especie) {
            $resultados[] = [
                'nombre' => $especie->post_title,
                'cientifico' => get_post_meta($especie->ID, '_bl_nombre_cientifico', true),
                'estado' => get_post_meta($especie->ID, '_bl_estado_conservacion', true),
                'url' => get_permalink($especie->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Encontré %d especie(s)', 'flavor-chat-ia'), count($resultados)),
            'especies' => $resultados,
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    public function action_listar_proyectos($params) {
        $proyectos = new WP_Query([
            'post_type' => 'bl_proyecto',
            'post_status' => 'publish',
            'posts_per_page' => 5,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        $lista = [];
        foreach ($proyectos->posts as $proyecto) {
            $tipo = get_post_meta($proyecto->ID, '_bl_tipo', true);
            $tipo_data = self::TIPOS_PROYECTO[$tipo] ?? ['nombre' => $tipo];
            $participantes = get_post_meta($proyecto->ID, '_bl_participantes', true) ?: [];

            $lista[] = [
                'titulo' => $proyecto->post_title,
                'tipo' => $tipo_data['nombre'],
                'participantes' => count($participantes),
                'url' => get_permalink($proyecto->ID),
            ];
        }

        return [
            'success' => true,
            'message' => sprintf(__('Hay %d proyecto(s) de conservación activos', 'flavor-chat-ia'), count($lista)),
            'proyectos' => $lista,
        ];
    }

    /**
     * Obtiene base de conocimiento
     *
     * @return string
     */
    public function get_knowledge_base(): string {
        $stats = $this->get_estadisticas();

        return sprintf(
            __("Módulo de Biodiversidad Local:\n" .
            "- %d especies catalogadas\n" .
            "- %d avistamientos registrados por la comunidad\n" .
            "- %d proyectos de conservación activos\n\n" .
            "Funcionalidades:\n" .
            "- Catálogo colaborativo de especies locales\n" .
            "- Mapa interactivo de avistamientos\n" .
            "- Sistema de ciencia ciudadana con validación comunitaria\n" .
            "- Proyectos de conservación y voluntariado\n" .
            "- Estados de conservación basados en IUCN", 'flavor-chat-ia'),
            $stats['especies_catalogadas'],
            $stats['avistamientos_total'],
            $stats['proyectos_activos']
        );
    }

    /**
     * Obtiene FAQs
     *
     * @return array
     */
    public function get_faqs(): array {
        return [
            [
                'pregunta' => __('¿Cómo registro un avistamiento de fauna o flora?', 'flavor-chat-ia'),
                'respuesta' => __('Ve a Biodiversidad > Registrar Avistamiento. Necesitas indicar la especie, ubicación y fecha. Puedes añadir fotos y descripción.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Cómo se validan los avistamientos?', 'flavor-chat-ia'),
                'respuesta' => __('Los avistamientos son validados por la comunidad. Con 3 validaciones positivas, se publican automáticamente.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Puedo proponer una especie que no está en el catálogo?', 'flavor-chat-ia'),
                'respuesta' => __('Sí, puedes proponer nuevas especies desde la sección de registro. Será revisada antes de añadirse al catálogo.', 'flavor-chat-ia'),
            ],
            [
                'pregunta' => __('¿Cómo puedo participar en proyectos de conservación?', 'flavor-chat-ia'),
                'respuesta' => __('Explora los proyectos activos en Biodiversidad > Proyectos y únete a los que te interesen.', 'flavor-chat-ia'),
            ],
        ];
    }
}
