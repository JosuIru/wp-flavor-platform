<?php
/**
 * Módulo: Saberes Ancestrales
 *
 * Preservación y transmisión del conocimiento tradicional comunitario.
 * Conecta generaciones y honra la sabiduría de los mayores.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del módulo Saberes Ancestrales
 */
class Flavor_Chat_Saberes_Ancestrales_Module extends Flavor_Chat_Module_Base {

    /**
     * Categorías de saberes
     */
    const CATEGORIAS_SABER = [
        'agricultura' => [
            'nombre' => 'Agricultura tradicional',
            'descripcion' => 'Cultivos, ciclos lunares, semillas antiguas',
            'icono' => 'dashicons-carrot',
            'color' => '#8B4513',
        ],
        'artesania' => [
            'nombre' => 'Artesanía',
            'descripcion' => 'Oficios manuales, tejidos, cerámica',
            'icono' => 'dashicons-art',
            'color' => '#D2691E',
        ],
        'medicina' => [
            'nombre' => 'Medicina natural',
            'descripcion' => 'Plantas medicinales, remedios caseros',
            'icono' => 'dashicons-heart',
            'color' => '#228B22',
        ],
        'gastronomia' => [
            'nombre' => 'Gastronomía tradicional',
            'descripcion' => 'Recetas, conservas, fermentos',
            'icono' => 'dashicons-food',
            'color' => '#FF6347',
        ],
        'tradiciones' => [
            'nombre' => 'Tradiciones y rituales',
            'descripcion' => 'Fiestas, ceremonias, costumbres',
            'icono' => 'dashicons-groups',
            'color' => '#9932CC',
        ],
        'construccion' => [
            'nombre' => 'Construcción tradicional',
            'descripcion' => 'Técnicas constructivas ancestrales',
            'icono' => 'dashicons-admin-home',
            'color' => '#CD853F',
        ],
        'musica' => [
            'nombre' => 'Música y danza',
            'descripcion' => 'Canciones, instrumentos, bailes',
            'icono' => 'dashicons-format-audio',
            'color' => '#4169E1',
        ],
        'narracion' => [
            'nombre' => 'Narración oral',
            'descripcion' => 'Cuentos, leyendas, refranes',
            'icono' => 'dashicons-format-quote',
            'color' => '#708090',
        ],
        'oficios' => [
            'nombre' => 'Oficios perdidos',
            'descripcion' => 'Herrería, carpintería, cestería...',
            'icono' => 'dashicons-hammer',
            'color' => '#696969',
        ],
    ];

    /**
     * Tipos de transmisión
     */
    const TIPOS_TRANSMISION = [
        'documentacion' => [
            'nombre' => 'Documentación',
            'descripcion' => 'Registro escrito, fotos, vídeos',
        ],
        'taller' => [
            'nombre' => 'Taller práctico',
            'descripcion' => 'Aprendizaje presencial guiado',
        ],
        'mentoria' => [
            'nombre' => 'Mentoría',
            'descripcion' => 'Acompañamiento uno a uno',
        ],
        'circulo' => [
            'nombre' => 'Círculo de saberes',
            'descripcion' => 'Encuentro grupal de intercambio',
        ],
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->module_id = 'saberes_ancestrales';
        $this->module_name = __('Saberes Ancestrales', 'flavor-chat-ia');
        $this->module_description = __('Preserva y transmite el conocimiento tradicional de la comunidad', 'flavor-chat-ia');
        $this->module_icon = 'dashicons-book';
        $this->module_color = '#8B4513';

        parent::__construct();
    }

    /**
     * Inicializa el módulo
     */
    public function init(): void {
        $this->register_post_types();
        $this->register_taxonomies();
        $this->register_ajax_handlers();
        $this->register_shortcodes();

        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('flavor_register_dashboard_widgets', [$this, 'register_dashboard_widget']);
    }

    /**
     * Registra los tipos de post personalizados
     */
    private function register_post_types(): void {
        // Saberes documentados
        register_post_type('sa_saber', [
            'labels' => [
                'name' => __('Saberes', 'flavor-chat-ia'),
                'singular_name' => __('Saber', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail', 'excerpt'],
            'capability_type' => 'post',
            'has_archive' => true,
            'rewrite' => ['slug' => 'saberes'],
        ]);

        // Portadores de saberes (personas mayores/sabias)
        register_post_type('sa_portador', [
            'labels' => [
                'name' => __('Portadores de Saberes', 'flavor-chat-ia'),
                'singular_name' => __('Portador de Saber', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'thumbnail'],
            'capability_type' => 'post',
        ]);

        // Talleres de transmisión
        register_post_type('sa_taller', [
            'labels' => [
                'name' => __('Talleres de Saberes', 'flavor-chat-ia'),
                'singular_name' => __('Taller', 'flavor-chat-ia'),
            ],
            'public' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'editor', 'author', 'thumbnail'],
            'capability_type' => 'post',
        ]);

        // Solicitudes de aprendizaje
        register_post_type('sa_solicitud', [
            'labels' => [
                'name' => __('Solicitudes de Aprendizaje', 'flavor-chat-ia'),
                'singular_name' => __('Solicitud', 'flavor-chat-ia'),
            ],
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title', 'author'],
            'capability_type' => 'post',
        ]);
    }

    /**
     * Registra taxonomías
     */
    private function register_taxonomies(): void {
        register_taxonomy('sa_categoria', ['sa_saber', 'sa_taller'], [
            'labels' => [
                'name' => __('Categorías de Saber', 'flavor-chat-ia'),
                'singular_name' => __('Categoría', 'flavor-chat-ia'),
            ],
            'public' => true,
            'hierarchical' => true,
        ]);

        register_taxonomy('sa_origen', 'sa_saber', [
            'labels' => [
                'name' => __('Origen', 'flavor-chat-ia'),
                'singular_name' => __('Origen', 'flavor-chat-ia'),
            ],
            'public' => true,
            'hierarchical' => false,
        ]);
    }

    /**
     * Registra los manejadores AJAX
     */
    private function register_ajax_handlers(): void {
        add_action('wp_ajax_sa_registrar_saber', [$this, 'ajax_registrar_saber']);
        add_action('wp_ajax_sa_solicitar_aprendizaje', [$this, 'ajax_solicitar_aprendizaje']);
        add_action('wp_ajax_sa_inscribirse_taller', [$this, 'ajax_inscribirse_taller']);
        add_action('wp_ajax_sa_proponer_taller', [$this, 'ajax_proponer_taller']);
        add_action('wp_ajax_sa_agradecer_saber', [$this, 'ajax_agradecer_saber']);
    }

    /**
     * Registra los shortcodes
     */
    public function register_shortcodes(): void {
        add_shortcode('saberes_catalogo', [$this, 'shortcode_catalogo']);
        add_shortcode('saberes_portadores', [$this, 'shortcode_portadores']);
        add_shortcode('saberes_talleres', [$this, 'shortcode_talleres']);
        add_shortcode('saberes_compartir', [$this, 'shortcode_compartir']);
        add_shortcode('saberes_mis_aprendizajes', [$this, 'shortcode_mis_aprendizajes']);
    }

    /**
     * Encola los assets del módulo
     */
    public function enqueue_assets(): void {
        if (!$this->is_module_page()) {
            return;
        }

        wp_enqueue_style(
            'flavor-saberes-ancestrales',
            $this->get_module_url() . 'assets/css/saberes-ancestrales.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-saberes-ancestrales',
            $this->get_module_url() . 'assets/js/saberes-ancestrales.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-saberes-ancestrales', 'flavorSaberes', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('saberes_nonce'),
            'categorias' => self::CATEGORIAS_SABER,
            'i18n' => [
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'guardado' => __('Guardado correctamente', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Verifica si estamos en una página del módulo
     */
    private function is_module_page(): bool {
        global $post;
        if (!$post) {
            return false;
        }
        return has_shortcode($post->post_content, 'saberes_catalogo')
            || has_shortcode($post->post_content, 'saberes_portadores')
            || has_shortcode($post->post_content, 'saberes_talleres')
            || has_shortcode($post->post_content, 'saberes_compartir')
            || strpos($_SERVER['REQUEST_URI'], '/saberes-ancestrales') !== false;
    }

    /**
     * AJAX: Registrar un saber
     */
    public function ajax_registrar_saber(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $categoria = sanitize_key($_POST['categoria'] ?? '');
        $origen = sanitize_text_field($_POST['origen'] ?? '');
        $portador = sanitize_text_field($_POST['portador'] ?? '');

        if (empty($titulo) || empty($descripcion)) {
            wp_send_json_error(['message' => __('Título y descripción son requeridos', 'flavor-chat-ia')]);
        }

        $saber_id = wp_insert_post([
            'post_type' => 'sa_saber',
            'post_status' => 'pending', // Revisión antes de publicar
            'post_author' => $user_id,
            'post_title' => $titulo,
            'post_content' => $descripcion,
        ]);

        if (is_wp_error($saber_id)) {
            wp_send_json_error(['message' => $saber_id->get_error_message()]);
        }

        // Asignar categoría
        if ($categoria && isset(self::CATEGORIAS_SABER[$categoria])) {
            wp_set_object_terms($saber_id, $categoria, 'sa_categoria');
        }

        // Guardar metadatos
        update_post_meta($saber_id, '_sa_origen', $origen);
        update_post_meta($saber_id, '_sa_portador', $portador);
        update_post_meta($saber_id, '_sa_documentado_por', $user_id);
        update_post_meta($saber_id, '_sa_fecha_documentacion', current_time('mysql'));
        update_post_meta($saber_id, '_sa_agradecimientos', 0);

        wp_send_json_success([
            'message' => __('Saber documentado. Será revisado antes de publicarse.', 'flavor-chat-ia'),
            'saber_id' => $saber_id,
        ]);
    }

    /**
     * AJAX: Solicitar aprendizaje de un saber
     */
    public function ajax_solicitar_aprendizaje(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $saber_id = intval($_POST['saber_id'] ?? 0);
        $mensaje = sanitize_textarea_field($_POST['mensaje'] ?? '');

        $saber = get_post($saber_id);
        if (!$saber || $saber->post_type !== 'sa_saber') {
            wp_send_json_error(['message' => __('Saber no encontrado', 'flavor-chat-ia')]);
        }

        // Verificar si ya solicitó
        global $wpdb;
        $ya_solicito = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_solicitud'
               AND p.post_author = %d
               AND pm.meta_key = '_sa_saber_id'
               AND pm.meta_value = %d",
            $user_id, $saber_id
        ));

        if ($ya_solicito > 0) {
            wp_send_json_error(['message' => __('Ya has solicitado aprender este saber', 'flavor-chat-ia')]);
        }

        $solicitud_id = wp_insert_post([
            'post_type' => 'sa_solicitud',
            'post_status' => 'publish',
            'post_author' => $user_id,
            'post_title' => sprintf(__('Solicitud: %s', 'flavor-chat-ia'), $saber->post_title),
        ]);

        update_post_meta($solicitud_id, '_sa_saber_id', $saber_id);
        update_post_meta($solicitud_id, '_sa_mensaje', $mensaje);
        update_post_meta($solicitud_id, '_sa_estado', 'pendiente');
        update_post_meta($solicitud_id, '_sa_fecha', current_time('mysql'));

        wp_send_json_success([
            'message' => __('Solicitud enviada. Te contactaremos cuando haya oportunidad de aprender.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Inscribirse en taller
     */
    public function ajax_inscribirse_taller(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $user_id = get_current_user_id();
        $taller_id = intval($_POST['taller_id'] ?? 0);

        $taller = get_post($taller_id);
        if (!$taller || $taller->post_type !== 'sa_taller') {
            wp_send_json_error(['message' => __('Taller no encontrado', 'flavor-chat-ia')]);
        }

        $inscritos = get_post_meta($taller_id, '_sa_inscritos', true) ?: [];
        $plazas = intval(get_post_meta($taller_id, '_sa_plazas', true)) ?: 20;

        if (in_array($user_id, $inscritos)) {
            wp_send_json_error(['message' => __('Ya estás inscrito', 'flavor-chat-ia')]);
        }

        if (count($inscritos) >= $plazas) {
            wp_send_json_error(['message' => __('No quedan plazas disponibles', 'flavor-chat-ia')]);
        }

        $inscritos[] = $user_id;
        update_post_meta($taller_id, '_sa_inscritos', $inscritos);

        wp_send_json_success([
            'message' => __('Inscripción completada', 'flavor-chat-ia'),
            'plazas_restantes' => $plazas - count($inscritos),
        ]);
    }

    /**
     * AJAX: Agradecer un saber
     */
    public function ajax_agradecer_saber(): void {
        check_ajax_referer('saberes_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $saber_id = intval($_POST['saber_id'] ?? 0);

        $agradecimientos = intval(get_post_meta($saber_id, '_sa_agradecimientos', true));
        update_post_meta($saber_id, '_sa_agradecimientos', $agradecimientos + 1);

        wp_send_json_success([
            'agradecimientos' => $agradecimientos + 1,
        ]);
    }

    /**
     * Obtiene estadísticas del módulo
     */
    public function get_estadisticas(): array {
        global $wpdb;

        $saberes_total = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_saber' AND post_status = 'publish'"
        );

        $portadores = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'sa_portador' AND post_status = 'publish'"
        );

        $talleres_proximos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_taller'
               AND p.post_status = 'publish'
               AND pm.meta_key = '_sa_fecha'
               AND pm.meta_value >= %s",
            current_time('mysql')
        ));

        $saberes_por_categoria = $wpdb->get_results(
            "SELECT t.slug as categoria, COUNT(*) as total
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             INNER JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
             WHERE p.post_type = 'sa_saber'
               AND p.post_status = 'publish'
               AND tt.taxonomy = 'sa_categoria'
             GROUP BY t.slug"
        , ARRAY_A);

        return [
            'saberes_total' => intval($saberes_total),
            'portadores' => intval($portadores),
            'talleres_proximos' => intval($talleres_proximos),
            'saberes_por_categoria' => $saberes_por_categoria,
        ];
    }

    /**
     * Obtiene estadísticas del usuario
     */
    public function get_estadisticas_usuario(int $user_id): array {
        global $wpdb;

        $saberes_documentados = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'sa_saber' AND post_author = %d",
            $user_id
        ));

        $talleres_inscritos = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_taller'
               AND pm.meta_key = '_sa_inscritos'
               AND pm.meta_value LIKE %s",
            '%"' . $user_id . '"%'
        ));

        $solicitudes_pendientes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'sa_solicitud'
               AND p.post_author = %d
               AND pm.meta_key = '_sa_estado'
               AND pm.meta_value = 'pendiente'",
            $user_id
        ));

        return [
            'saberes_documentados' => intval($saberes_documentados),
            'talleres_inscritos' => intval($talleres_inscritos),
            'solicitudes_pendientes' => intval($solicitudes_pendientes),
        ];
    }

    /**
     * Shortcode: Catálogo de saberes
     */
    public function shortcode_catalogo($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/catalogo.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Portadores de saberes
     */
    public function shortcode_portadores($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/portadores.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Talleres
     */
    public function shortcode_talleres($atts): string {
        ob_start();
        include $this->get_module_path() . 'templates/talleres.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Compartir saber
     */
    public function shortcode_compartir($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="sa-login-required">' . __('Inicia sesión para compartir saberes', 'flavor-chat-ia') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/compartir.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis aprendizajes
     */
    public function shortcode_mis_aprendizajes($atts): string {
        if (!is_user_logged_in()) {
            return '<p class="sa-login-required">' . __('Inicia sesión para ver tus aprendizajes', 'flavor-chat-ia') . '</p>';
        }
        ob_start();
        include $this->get_module_path() . 'templates/mis-aprendizajes.php';
        return ob_get_clean();
    }

    /**
     * Registra el widget de dashboard
     */
    public function register_dashboard_widget($registry): void {
        $widget_file = $this->get_module_path() . 'class-saberes-ancestrales-widget.php';
        if (file_exists($widget_file)) {
            require_once $widget_file;
            if (class_exists('Flavor_Saberes_Ancestrales_Widget')) {
                $registry->register(new Flavor_Saberes_Ancestrales_Widget($this));
            }
        }
    }

    /**
     * Obtiene la ruta del módulo
     */
    public function get_module_path(): string {
        return plugin_dir_path(__FILE__);
    }

    /**
     * Obtiene la URL del módulo
     */
    public function get_module_url(): string {
        return plugin_dir_url(__FILE__);
    }

    /**
     * Valoración de conciencia del módulo
     */
    public function get_consciousness_valuation(): array {
        return [
            'nombre' => 'Saberes Ancestrales',
            'puntuacion' => 88,
            'premisas' => [
                'madurez_ciclica' => 0.30, // Ciclos generacionales de conocimiento
                'conciencia_fundamental' => 0.25, // Sabiduría acumulada
                'interdependencia_radical' => 0.20, // Conexión intergeneracional
                'valor_intrinseco' => 0.15, // Valor del conocimiento tradicional
                'abundancia_organizable' => 0.10, // Organizar y transmitir saberes
            ],
            'descripcion_contribucion' => 'Este módulo honra la sabiduría de los mayores y el conocimiento ' .
                'acumulado por generaciones. Reconoce que la madurez colectiva viene de integrar ' .
                'el pasado con el presente, y que cada saber ancestral contiene conciencia cristalizada.',
            'categoria' => 'cultura',
        ];
    }
}
