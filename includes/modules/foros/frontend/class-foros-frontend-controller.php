<?php
/**
 * Frontend Controller para Foros de Discusion
 *
 * Controlador frontend con shortcodes, AJAX handlers y tabs para el dashboard
 *
 * @package FlavorChatIA
 * @subpackage Modules\Foros
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Foros_Frontend_Controller {

    /**
     * Instancia singleton
     * @var Flavor_Foros_Frontend_Controller|null
     */
    private static $instancia = null;

    /**
     * Nombre de las tablas
     */
    private $tabla_foros;
    private $tabla_temas;
    private $tabla_respuestas;
    private $tabla_votos;

    /**
     * Constructor privado
     */
    private function __construct() {
        global $wpdb;
        $this->tabla_foros = $wpdb->prefix . 'flavor_foros';
        $this->tabla_temas = $wpdb->prefix . 'flavor_foros_temas';
        $this->tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
        $this->tabla_votos = $wpdb->prefix . 'flavor_foros_votos';

        $this->init();
    }

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Foros_Frontend_Controller
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializa el controlador
     */
    public function init() {
        // Shortcodes
        add_shortcode('flavor_foros_listado', [$this, 'shortcode_listado']);
        add_shortcode('flavor_foros_categoria', [$this, 'shortcode_categoria']);
        add_shortcode('flavor_foros_tema', [$this, 'shortcode_tema']);
        add_shortcode('flavor_foros_nuevo_tema', [$this, 'shortcode_nuevo_tema']);
        add_shortcode('flavor_foros_mis_temas', [$this, 'shortcode_mis_temas']);
        add_shortcode('flavor_foros_mis_respuestas', [$this, 'shortcode_mis_respuestas']);
        add_shortcode('flavor_foros_buscar', [$this, 'shortcode_buscar']);
        add_shortcode('flavor_foros_actividad_reciente', [$this, 'shortcode_actividad_reciente']);

        // AJAX handlers
        add_action('wp_ajax_flavor_foros_crear_tema', [$this, 'ajax_crear_tema']);
        add_action('wp_ajax_flavor_foros_responder', [$this, 'ajax_responder']);
        add_action('wp_ajax_flavor_foros_votar', [$this, 'ajax_votar']);
        add_action('wp_ajax_flavor_foros_marcar_solucion', [$this, 'ajax_marcar_solucion']);
        add_action('wp_ajax_flavor_foros_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_nopriv_flavor_foros_buscar', [$this, 'ajax_buscar']);
        add_action('wp_ajax_flavor_foros_obtener_temas', [$this, 'ajax_obtener_temas']);
        add_action('wp_ajax_nopriv_flavor_foros_obtener_temas', [$this, 'ajax_obtener_temas']);
        add_action('wp_ajax_flavor_foros_reportar', [$this, 'ajax_reportar']);

        // Dashboard tabs
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_dashboard_tabs']);

        // Assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
    }

    /**
     * Registra los tabs del dashboard
     */
    public function registrar_dashboard_tabs($tabs) {
        $tabs['foros'] = [
            'id' => 'foros',
            'label' => __('Foros', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-chat',
            'orden' => 35,
            'callback' => [$this, 'render_dashboard_tab'],
        ];

        $tabs['foros-mis-temas'] = [
            'id' => 'foros-mis-temas',
            'label' => __('Mis Temas', 'flavor-chat-ia'),
            'icon' => 'dashicons-admin-comments',
            'orden' => 36,
            'parent' => 'foros',
            'callback' => [$this, 'render_dashboard_mis_temas'],
        ];

        return $tabs;
    }

    /**
     * Registra assets frontend
     */
    public function registrar_assets() {
        wp_register_style(
            'flavor-foros-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/foros/assets/css/foros-frontend.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_register_script(
            'flavor-foros-frontend',
            FLAVOR_CHAT_IA_URL . 'includes/modules/foros/assets/js/foros-frontend.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-foros-frontend', 'flavorForosConfig', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_foros_nonce'),
            'strings' => [
                'confirmarEliminar' => __('¿Estás seguro de eliminar este contenido?', 'flavor-chat-ia'),
                'enviando' => __('Enviando...', 'flavor-chat-ia'),
                'error' => __('Error al procesar la solicitud', 'flavor-chat-ia'),
                'exito' => __('Operación completada', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encola assets cuando se necesitan
     */
    private function enqueue_assets() {
        wp_enqueue_style('flavor-foros-frontend');
        wp_enqueue_script('flavor-foros-frontend');
    }

    // =========================================================
    // SHORTCODES
    // =========================================================

    /**
     * Shortcode: Listado de foros/categorias
     */
    public function shortcode_listado($atts) {
        $atts = shortcode_atts([
            'mostrar_descripcion' => 'si',
            'mostrar_estadisticas' => 'si',
        ], $atts);

        $this->enqueue_assets();

        if (!Flavor_Chat_Helpers::tabla_existe($this->tabla_foros)) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('El sistema de foros no está configurado.', 'flavor-chat-ia') . '</div>';
        }

        global $wpdb;
        $foros = $wpdb->get_results("
            SELECT f.*,
                   (SELECT COUNT(*) FROM {$this->tabla_temas} WHERE foro_id = f.id AND estado = 'publicado') as total_temas,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} r
                    INNER JOIN {$this->tabla_temas} t ON r.tema_id = t.id
                    WHERE t.foro_id = f.id) as total_respuestas,
                   (SELECT MAX(t.fecha_actividad) FROM {$this->tabla_temas} t WHERE t.foro_id = f.id) as ultima_actividad
            FROM {$this->tabla_foros} f
            WHERE f.estado = 'activo'
            ORDER BY f.orden ASC, f.nombre ASC
        ");

        ob_start();
        ?>
        <div class="flavor-foros-listado">
            <div class="flavor-foros-header">
                <h2><?php _e('Foros de Discusión', 'flavor-chat-ia'); ?></h2>
                <?php if (is_user_logged_in()): ?>
                <div class="flavor-foros-acciones">
                    <a href="#buscar" class="flavor-btn flavor-btn-outline flavor-btn-sm">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Buscar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <?php if (empty($foros)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay foros disponibles.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-foros-grid">
                    <?php foreach ($foros as $foro): ?>
                        <div class="flavor-foro-card">
                            <div class="flavor-foro-icono">
                                <span class="dashicons <?php echo esc_attr($foro->icono ?: 'dashicons-format-chat'); ?>"></span>
                            </div>
                            <div class="flavor-foro-contenido">
                                <h3 class="flavor-foro-titulo">
                                    <a href="<?php echo esc_url(add_query_arg('foro_id', $foro->id)); ?>">
                                        <?php echo esc_html($foro->nombre); ?>
                                    </a>
                                </h3>
                                <?php if ($atts['mostrar_descripcion'] === 'si' && !empty($foro->descripcion)): ?>
                                    <p class="flavor-foro-descripcion"><?php echo esc_html($foro->descripcion); ?></p>
                                <?php endif; ?>

                                <?php if ($atts['mostrar_estadisticas'] === 'si'): ?>
                                    <div class="flavor-foro-stats">
                                        <span class="flavor-stat">
                                            <span class="dashicons dashicons-admin-comments"></span>
                                            <?php printf(
                                                _n('%d tema', '%d temas', $foro->total_temas, 'flavor-chat-ia'),
                                                $foro->total_temas
                                            ); ?>
                                        </span>
                                        <span class="flavor-stat">
                                            <span class="dashicons dashicons-format-status"></span>
                                            <?php printf(
                                                _n('%d respuesta', '%d respuestas', $foro->total_respuestas, 'flavor-chat-ia'),
                                                $foro->total_respuestas
                                            ); ?>
                                        </span>
                                        <?php if ($foro->ultima_actividad): ?>
                                            <span class="flavor-stat flavor-stat-tiempo">
                                                <span class="dashicons dashicons-clock"></span>
                                                <?php echo human_time_diff(strtotime($foro->ultima_actividad)); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ver categoria/foro
     */
    public function shortcode_categoria($atts) {
        $atts = shortcode_atts([
            'foro_id' => 0,
            'por_pagina' => 20,
        ], $atts);

        $foro_id = absint($atts['foro_id'] ?: (isset($_GET['foro_id']) ? $_GET['foro_id'] : 0));
        if (!$foro_id) {
            return $this->shortcode_listado([]);
        }

        $this->enqueue_assets();

        global $wpdb;
        $foro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_foros} WHERE id = %d AND estado = 'activo'",
            $foro_id
        ));

        if (!$foro) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Foro no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['por_pagina'];

        $temas = $wpdb->get_results($wpdb->prepare("
            SELECT t.*,
                   u.display_name as autor_nombre,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE tema_id = t.id) as total_respuestas,
                   (SELECT SUM(votos) FROM {$this->tabla_respuestas} WHERE tema_id = t.id) as total_votos_respuestas
            FROM {$this->tabla_temas} t
            LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
            WHERE t.foro_id = %d AND t.estado = 'publicado'
            ORDER BY t.es_fijado DESC, t.fecha_actividad DESC
            LIMIT %d OFFSET %d
        ", $foro_id, $atts['por_pagina'], $offset));

        $total_temas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_temas} WHERE foro_id = %d AND estado = 'publicado'",
            $foro_id
        ));

        ob_start();
        ?>
        <div class="flavor-foros-categoria">
            <div class="flavor-foros-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg('foro_id')); ?>">
                    <?php _e('Foros', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html($foro->nombre); ?></span>
            </div>

            <div class="flavor-foros-header">
                <div class="flavor-foro-info">
                    <h2><?php echo esc_html($foro->nombre); ?></h2>
                    <?php if (!empty($foro->descripcion)): ?>
                        <p><?php echo esc_html($foro->descripcion); ?></p>
                    <?php endif; ?>
                </div>
                <?php if (is_user_logged_in()): ?>
                    <a href="<?php echo esc_url(add_query_arg(['accion' => 'nuevo_tema', 'foro_id' => $foro_id])); ?>"
                       class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-plus"></span>
                        <?php _e('Nuevo Tema', 'flavor-chat-ia'); ?>
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($temas)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No hay temas en este foro. ¡Sé el primero en crear uno!', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-temas-lista">
                    <?php foreach ($temas as $tema): ?>
                        <div class="flavor-tema-item <?php echo $tema->es_fijado ? 'flavor-tema-fijado' : ''; ?> <?php echo $tema->tiene_solucion ? 'flavor-tema-resuelto' : ''; ?>">
                            <div class="flavor-tema-estado">
                                <?php if ($tema->es_fijado): ?>
                                    <span class="dashicons dashicons-admin-post" title="<?php esc_attr_e('Fijado', 'flavor-chat-ia'); ?>"></span>
                                <?php endif; ?>
                                <?php if ($tema->tiene_solucion): ?>
                                    <span class="dashicons dashicons-yes-alt flavor-color-success" title="<?php esc_attr_e('Resuelto', 'flavor-chat-ia'); ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="flavor-tema-contenido">
                                <h3 class="flavor-tema-titulo">
                                    <a href="<?php echo esc_url(add_query_arg('tema_id', $tema->id)); ?>">
                                        <?php echo esc_html($tema->titulo); ?>
                                    </a>
                                </h3>
                                <div class="flavor-tema-meta">
                                    <span class="flavor-tema-autor">
                                        <?php echo get_avatar($tema->autor_id, 24); ?>
                                        <?php echo esc_html($tema->autor_nombre); ?>
                                    </span>
                                    <span class="flavor-tema-fecha">
                                        <?php echo human_time_diff(strtotime($tema->fecha_creacion)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flavor-tema-stats">
                                <div class="flavor-stat-item">
                                    <span class="flavor-stat-valor"><?php echo absint($tema->total_respuestas); ?></span>
                                    <span class="flavor-stat-label"><?php _e('respuestas', 'flavor-chat-ia'); ?></span>
                                </div>
                                <div class="flavor-stat-item">
                                    <span class="flavor-stat-valor"><?php echo absint($tema->vistas); ?></span>
                                    <span class="flavor-stat-label"><?php _e('vistas', 'flavor-chat-ia'); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php $this->render_paginacion($total_temas, $atts['por_pagina'], $pagina); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ver tema con respuestas
     */
    public function shortcode_tema($atts) {
        $atts = shortcode_atts([
            'tema_id' => 0,
            'respuestas_por_pagina' => 25,
        ], $atts);

        $tema_id = absint($atts['tema_id'] ?: (isset($_GET['tema_id']) ? $_GET['tema_id'] : 0));
        if (!$tema_id) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Tema no especificado.', 'flavor-chat-ia') . '</div>';
        }

        $this->enqueue_assets();

        global $wpdb;

        // Incrementar vistas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_temas} SET vistas = vistas + 1 WHERE id = %d",
            $tema_id
        ));

        $tema = $wpdb->get_row($wpdb->prepare("
            SELECT t.*, f.nombre as foro_nombre, f.id as foro_id,
                   u.display_name as autor_nombre
            FROM {$this->tabla_temas} t
            LEFT JOIN {$this->tabla_foros} f ON t.foro_id = f.id
            LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
            WHERE t.id = %d AND t.estado = 'publicado'
        ", $tema_id));

        if (!$tema) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Tema no encontrado.', 'flavor-chat-ia') . '</div>';
        }

        $pagina = max(1, absint($_GET['pag'] ?? 1));
        $offset = ($pagina - 1) * $atts['respuestas_por_pagina'];

        $respuestas = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, u.display_name as autor_nombre,
                   (SELECT COUNT(*) FROM {$this->tabla_votos} WHERE respuesta_id = r.id AND tipo = 'positivo') as votos_positivos,
                   (SELECT COUNT(*) FROM {$this->tabla_votos} WHERE respuesta_id = r.id AND tipo = 'negativo') as votos_negativos
            FROM {$this->tabla_respuestas} r
            LEFT JOIN {$wpdb->users} u ON r.autor_id = u.ID
            WHERE r.tema_id = %d AND r.estado = 'publicado' AND r.padre_id IS NULL
            ORDER BY r.es_solucion DESC, r.votos DESC, r.fecha_creacion ASC
            LIMIT %d OFFSET %d
        ", $tema_id, $atts['respuestas_por_pagina'], $offset));

        $total_respuestas = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE tema_id = %d AND estado = 'publicado' AND padre_id IS NULL",
            $tema_id
        ));

        $usuario_actual = get_current_user_id();

        ob_start();
        ?>
        <div class="flavor-foros-tema">
            <div class="flavor-foros-breadcrumb">
                <a href="<?php echo esc_url(remove_query_arg(['foro_id', 'tema_id'])); ?>">
                    <?php _e('Foros', 'flavor-chat-ia'); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <a href="<?php echo esc_url(add_query_arg('foro_id', $tema->foro_id, remove_query_arg('tema_id'))); ?>">
                    <?php echo esc_html($tema->foro_nombre); ?>
                </a>
                <span class="dashicons dashicons-arrow-right-alt2"></span>
                <span><?php echo esc_html(wp_trim_words($tema->titulo, 5)); ?></span>
            </div>

            <article class="flavor-tema-principal">
                <header class="flavor-tema-header">
                    <h1><?php echo esc_html($tema->titulo); ?></h1>
                    <div class="flavor-tema-meta">
                        <span class="flavor-tema-autor">
                            <?php echo get_avatar($tema->autor_id, 32); ?>
                            <?php echo esc_html($tema->autor_nombre); ?>
                        </span>
                        <span class="flavor-tema-fecha">
                            <?php echo date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($tema->fecha_creacion)); ?>
                        </span>
                        <?php if ($tema->tiene_solucion): ?>
                            <span class="flavor-badge flavor-badge-success">
                                <span class="dashicons dashicons-yes"></span>
                                <?php _e('Resuelto', 'flavor-chat-ia'); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </header>
                <div class="flavor-tema-contenido-texto">
                    <?php echo wp_kses_post(wpautop($tema->contenido)); ?>
                </div>
                <footer class="flavor-tema-footer">
                    <div class="flavor-tema-tags">
                        <?php
                        $etiquetas = maybe_unserialize($tema->etiquetas);
                        if (!empty($etiquetas) && is_array($etiquetas)):
                            foreach ($etiquetas as $etiqueta): ?>
                                <span class="flavor-tag"><?php echo esc_html($etiqueta); ?></span>
                            <?php endforeach;
                        endif; ?>
                    </div>
                    <div class="flavor-tema-stats">
                        <span><span class="dashicons dashicons-visibility"></span> <?php echo absint($tema->vistas); ?> <?php _e('vistas', 'flavor-chat-ia'); ?></span>
                        <span><span class="dashicons dashicons-admin-comments"></span> <?php echo absint($total_respuestas); ?> <?php _e('respuestas', 'flavor-chat-ia'); ?></span>
                    </div>
                </footer>
            </article>

            <section class="flavor-respuestas-seccion">
                <h2><?php printf(__('%d Respuestas', 'flavor-chat-ia'), $total_respuestas); ?></h2>

                <?php if (!empty($respuestas)): ?>
                    <div class="flavor-respuestas-lista">
                        <?php foreach ($respuestas as $respuesta): ?>
                            <?php $this->render_respuesta($respuesta, $tema, $usuario_actual); ?>
                        <?php endforeach; ?>
                    </div>

                    <?php $this->render_paginacion($total_respuestas, $atts['respuestas_por_pagina'], $pagina); ?>
                <?php endif; ?>

                <?php if (is_user_logged_in()): ?>
                    <div class="flavor-responder-formulario">
                        <h3><?php _e('Tu Respuesta', 'flavor-chat-ia'); ?></h3>
                        <form id="flavor-form-respuesta" class="flavor-form">
                            <?php wp_nonce_field('flavor_foros_nonce', 'nonce'); ?>
                            <input type="hidden" name="tema_id" value="<?php echo esc_attr($tema_id); ?>">

                            <div class="flavor-form-group">
                                <textarea name="contenido" id="respuesta-contenido" rows="6"
                                          placeholder="<?php esc_attr_e('Escribe tu respuesta...', 'flavor-chat-ia'); ?>"
                                          required minlength="10"></textarea>
                            </div>

                            <div class="flavor-form-actions">
                                <button type="submit" class="flavor-btn flavor-btn-primary">
                                    <span class="dashicons dashicons-edit"></span>
                                    <?php _e('Publicar Respuesta', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="flavor-alert flavor-alert-info">
                        <?php printf(
                            __('<a href="%s">Inicia sesión</a> para responder a este tema.', 'flavor-chat-ia'),
                            wp_login_url(get_permalink())
                        ); ?>
                    </div>
                <?php endif; ?>
            </section>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renderiza una respuesta
     */
    private function render_respuesta($respuesta, $tema, $usuario_actual) {
        $puede_marcar_solucion = ($tema->autor_id == $usuario_actual) && !$tema->tiene_solucion;
        $voto_usuario = $this->obtener_voto_usuario($respuesta->id, $usuario_actual);
        ?>
        <div class="flavor-respuesta-item <?php echo $respuesta->es_solucion ? 'flavor-respuesta-solucion' : ''; ?>"
             data-respuesta-id="<?php echo esc_attr($respuesta->id); ?>">
            <?php if ($respuesta->es_solucion): ?>
                <div class="flavor-solucion-badge">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Solución aceptada', 'flavor-chat-ia'); ?>
                </div>
            <?php endif; ?>

            <div class="flavor-respuesta-votos">
                <button class="flavor-voto-btn flavor-voto-up <?php echo $voto_usuario === 'positivo' ? 'activo' : ''; ?>"
                        data-tipo="positivo" <?php echo !$usuario_actual ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-arrow-up-alt2"></span>
                </button>
                <span class="flavor-votos-total"><?php echo absint($respuesta->votos); ?></span>
                <button class="flavor-voto-btn flavor-voto-down <?php echo $voto_usuario === 'negativo' ? 'activo' : ''; ?>"
                        data-tipo="negativo" <?php echo !$usuario_actual ? 'disabled' : ''; ?>>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </button>
            </div>

            <div class="flavor-respuesta-contenido">
                <div class="flavor-respuesta-header">
                    <span class="flavor-respuesta-autor">
                        <?php echo get_avatar($respuesta->autor_id, 28); ?>
                        <?php echo esc_html($respuesta->autor_nombre); ?>
                    </span>
                    <span class="flavor-respuesta-fecha">
                        <?php echo human_time_diff(strtotime($respuesta->fecha_creacion)); ?>
                    </span>
                </div>
                <div class="flavor-respuesta-texto">
                    <?php echo wp_kses_post(wpautop($respuesta->contenido)); ?>
                </div>
                <div class="flavor-respuesta-acciones">
                    <?php if ($puede_marcar_solucion): ?>
                        <button class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-marcar-solucion">
                            <span class="dashicons dashicons-yes"></span>
                            <?php _e('Marcar como solución', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                    <?php if ($usuario_actual): ?>
                        <button class="flavor-btn flavor-btn-sm flavor-btn-text flavor-reportar" data-tipo="respuesta">
                            <span class="dashicons dashicons-flag"></span>
                            <?php _e('Reportar', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Shortcode: Crear nuevo tema
     */
    public function shortcode_nuevo_tema($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   sprintf(__('<a href="%s">Inicia sesión</a> para crear un tema.', 'flavor-chat-ia'), wp_login_url(get_permalink())) .
                   '</div>';
        }

        $this->enqueue_assets();

        $foro_id = isset($_GET['foro_id']) ? absint($_GET['foro_id']) : 0;

        global $wpdb;
        $foros = $wpdb->get_results("SELECT id, nombre FROM {$this->tabla_foros} WHERE estado = 'activo' ORDER BY nombre");

        ob_start();
        ?>
        <div class="flavor-nuevo-tema">
            <h2><?php _e('Crear Nuevo Tema', 'flavor-chat-ia'); ?></h2>

            <form id="flavor-form-nuevo-tema" class="flavor-form">
                <?php wp_nonce_field('flavor_foros_nonce', 'nonce'); ?>

                <div class="flavor-form-group">
                    <label for="foro_id"><?php _e('Foro', 'flavor-chat-ia'); ?> *</label>
                    <select name="foro_id" id="foro_id" required>
                        <option value=""><?php _e('Selecciona un foro', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($foros as $foro): ?>
                            <option value="<?php echo esc_attr($foro->id); ?>"
                                    <?php selected($foro_id, $foro->id); ?>>
                                <?php echo esc_html($foro->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flavor-form-group">
                    <label for="titulo"><?php _e('Título', 'flavor-chat-ia'); ?> *</label>
                    <input type="text" name="titulo" id="titulo" required
                           placeholder="<?php esc_attr_e('¿Cuál es tu pregunta o tema?', 'flavor-chat-ia'); ?>"
                           minlength="10" maxlength="200">
                </div>

                <div class="flavor-form-group">
                    <label for="contenido"><?php _e('Contenido', 'flavor-chat-ia'); ?> *</label>
                    <textarea name="contenido" id="contenido" rows="10" required
                              placeholder="<?php esc_attr_e('Describe tu tema con el mayor detalle posible...', 'flavor-chat-ia'); ?>"
                              minlength="50"></textarea>
                </div>

                <div class="flavor-form-group">
                    <label for="etiquetas"><?php _e('Etiquetas', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="etiquetas" id="etiquetas"
                           placeholder="<?php esc_attr_e('Ej: ayuda, duda, sugerencia (separadas por coma)', 'flavor-chat-ia'); ?>">
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary">
                        <span class="dashicons dashicons-welcome-add-page"></span>
                        <?php _e('Publicar Tema', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(remove_query_arg('accion')); ?>" class="flavor-btn flavor-btn-outline">
                        <?php _e('Cancelar', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis temas
     */
    public function shortcode_mis_temas($atts) {
        if (!is_user_logged_in()) {
            return '<div class="flavor-alert flavor-alert-warning">' .
                   __('Debes iniciar sesión para ver tus temas.', 'flavor-chat-ia') . '</div>';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $temas = $wpdb->get_results($wpdb->prepare("
            SELECT t.*, f.nombre as foro_nombre,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE tema_id = t.id) as total_respuestas
            FROM {$this->tabla_temas} t
            LEFT JOIN {$this->tabla_foros} f ON t.foro_id = f.id
            WHERE t.autor_id = %d
            ORDER BY t.fecha_creacion DESC
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-temas">
            <h2><?php _e('Mis Temas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($temas)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No has creado ningún tema todavía.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-tabla-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php _e('Tema', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Foro', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Respuestas', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Estado', 'flavor-chat-ia'); ?></th>
                                <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($temas as $tema): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo esc_url(add_query_arg('tema_id', $tema->id)); ?>">
                                            <?php echo esc_html($tema->titulo); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($tema->foro_nombre); ?></td>
                                    <td><?php echo absint($tema->total_respuestas); ?></td>
                                    <td>
                                        <?php if ($tema->tiene_solucion): ?>
                                            <span class="flavor-badge flavor-badge-success"><?php _e('Resuelto', 'flavor-chat-ia'); ?></span>
                                        <?php else: ?>
                                            <span class="flavor-badge flavor-badge-info"><?php _e('Abierto', 'flavor-chat-ia'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo human_time_diff(strtotime($tema->fecha_creacion)); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis respuestas
     */
    public function shortcode_mis_respuestas($atts) {
        if (!is_user_logged_in()) {
            return '';
        }

        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $respuestas = $wpdb->get_results($wpdb->prepare("
            SELECT r.*, t.titulo as tema_titulo, t.id as tema_id
            FROM {$this->tabla_respuestas} r
            LEFT JOIN {$this->tabla_temas} t ON r.tema_id = t.id
            WHERE r.autor_id = %d
            ORDER BY r.fecha_creacion DESC
            LIMIT 50
        ", $usuario_id));

        ob_start();
        ?>
        <div class="flavor-mis-respuestas">
            <h2><?php _e('Mis Respuestas', 'flavor-chat-ia'); ?></h2>

            <?php if (empty($respuestas)): ?>
                <div class="flavor-alert flavor-alert-info">
                    <?php _e('No has respondido a ningún tema todavía.', 'flavor-chat-ia'); ?>
                </div>
            <?php else: ?>
                <div class="flavor-respuestas-lista-compacta">
                    <?php foreach ($respuestas as $respuesta): ?>
                        <div class="flavor-respuesta-compacta">
                            <div class="flavor-respuesta-info">
                                <a href="<?php echo esc_url(add_query_arg('tema_id', $respuesta->tema_id)); ?>">
                                    <?php echo esc_html($respuesta->tema_titulo); ?>
                                </a>
                                <span class="flavor-respuesta-fecha"><?php echo human_time_diff(strtotime($respuesta->fecha_creacion)); ?></span>
                            </div>
                            <p class="flavor-respuesta-extracto">
                                <?php echo esc_html(wp_trim_words($respuesta->contenido, 20)); ?>
                            </p>
                            <div class="flavor-respuesta-stats-mini">
                                <span class="flavor-votos">
                                    <span class="dashicons dashicons-thumbs-up"></span>
                                    <?php echo absint($respuesta->votos); ?>
                                </span>
                                <?php if ($respuesta->es_solucion): ?>
                                    <span class="flavor-badge flavor-badge-success flavor-badge-sm">
                                        <?php _e('Solución', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Buscar en foros
     */
    public function shortcode_buscar($atts) {
        $this->enqueue_assets();
        $busqueda = sanitize_text_field($_GET['q'] ?? '');

        ob_start();
        ?>
        <div class="flavor-foros-buscar">
            <form method="get" class="flavor-buscar-form">
                <input type="text" name="q" value="<?php echo esc_attr($busqueda); ?>"
                       placeholder="<?php esc_attr_e('Buscar en los foros...', 'flavor-chat-ia'); ?>">
                <button type="submit" class="flavor-btn flavor-btn-primary">
                    <span class="dashicons dashicons-search"></span>
                </button>
            </form>

            <?php if (!empty($busqueda)): ?>
                <div class="flavor-resultados-busqueda" data-busqueda="<?php echo esc_attr($busqueda); ?>">
                    <?php echo $this->realizar_busqueda($busqueda); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Actividad reciente
     */
    public function shortcode_actividad_reciente($atts) {
        $atts = shortcode_atts([
            'limite' => 10,
        ], $atts);

        $this->enqueue_assets();

        global $wpdb;
        $actividad = $wpdb->get_results($wpdb->prepare("
            (SELECT 'tema' as tipo, t.id, t.titulo as titulo, t.fecha_creacion as fecha,
                    u.display_name as autor, t.id as tema_id
             FROM {$this->tabla_temas} t
             LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
             WHERE t.estado = 'publicado')
            UNION ALL
            (SELECT 'respuesta' as tipo, r.id, t.titulo as titulo, r.fecha_creacion as fecha,
                    u.display_name as autor, r.tema_id
             FROM {$this->tabla_respuestas} r
             LEFT JOIN {$this->tabla_temas} t ON r.tema_id = t.id
             LEFT JOIN {$wpdb->users} u ON r.autor_id = u.ID
             WHERE r.estado = 'publicado')
            ORDER BY fecha DESC
            LIMIT %d
        ", $atts['limite']));

        ob_start();
        ?>
        <div class="flavor-actividad-reciente">
            <h3><?php _e('Actividad Reciente', 'flavor-chat-ia'); ?></h3>
            <?php if (empty($actividad)): ?>
                <p class="flavor-no-actividad"><?php _e('No hay actividad reciente.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <ul class="flavor-actividad-lista">
                    <?php foreach ($actividad as $item): ?>
                        <li class="flavor-actividad-item">
                            <span class="dashicons <?php echo $item->tipo === 'tema' ? 'dashicons-admin-comments' : 'dashicons-format-status'; ?>"></span>
                            <span class="flavor-actividad-texto">
                                <strong><?php echo esc_html($item->autor); ?></strong>
                                <?php echo $item->tipo === 'tema' ? __('creó', 'flavor-chat-ia') : __('respondió a', 'flavor-chat-ia'); ?>
                                <a href="<?php echo esc_url(add_query_arg('tema_id', $item->tema_id)); ?>">
                                    <?php echo esc_html(wp_trim_words($item->titulo, 8)); ?>
                                </a>
                            </span>
                            <span class="flavor-actividad-tiempo"><?php echo human_time_diff(strtotime($item->fecha)); ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // =========================================================
    // DASHBOARD TABS
    // =========================================================

    /**
     * Render del tab principal del dashboard
     */
    public function render_dashboard_tab() {
        $this->enqueue_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;

        // Estadísticas del usuario
        $stats = [
            'mis_temas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_temas} WHERE autor_id = %d",
                $usuario_id
            )),
            'mis_respuestas' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE autor_id = %d",
                $usuario_id
            )),
            'votos_recibidos' => $wpdb->get_var($wpdb->prepare("
                SELECT COALESCE(SUM(r.votos), 0)
                FROM {$this->tabla_respuestas} r
                WHERE r.autor_id = %d
            ", $usuario_id)),
            'soluciones' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE autor_id = %d AND es_solucion = 1",
                $usuario_id
            )),
        ];

        // Actividad reciente
        $actividad = $wpdb->get_results($wpdb->prepare("
            (SELECT 'tema' as tipo, t.id, t.titulo, t.fecha_creacion as fecha
             FROM {$this->tabla_temas} t WHERE t.autor_id = %d ORDER BY fecha DESC LIMIT 5)
            UNION ALL
            (SELECT 'respuesta' as tipo, t.id, t.titulo, r.fecha_creacion as fecha
             FROM {$this->tabla_respuestas} r
             LEFT JOIN {$this->tabla_temas} t ON r.tema_id = t.id
             WHERE r.autor_id = %d ORDER BY fecha DESC LIMIT 5)
            ORDER BY fecha DESC LIMIT 10
        ", $usuario_id, $usuario_id));

        ?>
        <div class="flavor-dashboard-foros">
            <div class="flavor-kpi-grid flavor-grid-4">
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-admin-comments"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($stats['mis_temas']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Temas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-format-status"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($stats['mis_respuestas']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Respuestas', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-thumbs-up"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($stats['votos_recibidos']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Votos', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
                <div class="flavor-kpi-card">
                    <div class="flavor-kpi-icono"><span class="dashicons dashicons-yes-alt"></span></div>
                    <div class="flavor-kpi-contenido">
                        <span class="flavor-kpi-valor"><?php echo absint($stats['soluciones']); ?></span>
                        <span class="flavor-kpi-label"><?php _e('Soluciones', 'flavor-chat-ia'); ?></span>
                    </div>
                </div>
            </div>

            <div class="flavor-panel">
                <h3><?php _e('Tu Actividad Reciente', 'flavor-chat-ia'); ?></h3>
                <?php if (empty($actividad)): ?>
                    <p class="flavor-no-datos"><?php _e('No tienes actividad reciente en los foros.', 'flavor-chat-ia'); ?></p>
                <?php else: ?>
                    <ul class="flavor-actividad-lista">
                        <?php foreach ($actividad as $item): ?>
                            <li>
                                <span class="dashicons <?php echo $item->tipo === 'tema' ? 'dashicons-admin-comments' : 'dashicons-format-status'; ?>"></span>
                                <a href="<?php echo esc_url(add_query_arg('tema_id', $item->id)); ?>">
                                    <?php echo esc_html($item->titulo); ?>
                                </a>
                                <span class="flavor-tiempo"><?php echo human_time_diff(strtotime($item->fecha)); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render del tab "Mis Temas"
     */
    public function render_dashboard_mis_temas() {
        echo $this->shortcode_mis_temas([]);
    }

    // =========================================================
    // AJAX HANDLERS
    // =========================================================

    /**
     * AJAX: Crear tema
     */
    public function ajax_crear_tema() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $foro_id = absint($_POST['foro_id'] ?? 0);
        $titulo = sanitize_text_field($_POST['titulo'] ?? '');
        $contenido = wp_kses_post($_POST['contenido'] ?? '');
        $etiquetas = sanitize_text_field($_POST['etiquetas'] ?? '');

        if (!$foro_id || empty($titulo) || empty($contenido)) {
            wp_send_json_error(['message' => __('Todos los campos son requeridos.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Verificar que el foro existe
        $foro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_foros} WHERE id = %d AND estado = 'activo'",
            $foro_id
        ));

        if (!$foro) {
            wp_send_json_error(['message' => __('Foro no válido.', 'flavor-chat-ia')]);
        }

        $etiquetas_array = !empty($etiquetas) ? array_map('trim', explode(',', $etiquetas)) : [];

        $resultado = $wpdb->insert($this->tabla_temas, [
            'foro_id' => $foro_id,
            'autor_id' => get_current_user_id(),
            'titulo' => $titulo,
            'contenido' => $contenido,
            'etiquetas' => maybe_serialize($etiquetas_array),
            'estado' => 'publicado',
            'fecha_creacion' => current_time('mysql'),
            'fecha_actividad' => current_time('mysql'),
        ]);

        if ($resultado) {
            $tema_id = $wpdb->insert_id;
            wp_send_json_success([
                'message' => __('Tema creado correctamente.', 'flavor-chat-ia'),
                'tema_id' => $tema_id,
                'redirect' => add_query_arg('tema_id', $tema_id, remove_query_arg(['accion', 'foro_id'])),
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al crear el tema.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Responder a tema
     */
    public function ajax_responder() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $tema_id = absint($_POST['tema_id'] ?? 0);
        $contenido = wp_kses_post($_POST['contenido'] ?? '');
        $padre_id = absint($_POST['padre_id'] ?? 0);

        if (!$tema_id || empty($contenido)) {
            wp_send_json_error(['message' => __('El contenido es requerido.', 'flavor-chat-ia')]);
        }

        global $wpdb;

        // Verificar tema
        $tema = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_temas} WHERE id = %d AND estado = 'publicado'",
            $tema_id
        ));

        if (!$tema) {
            wp_send_json_error(['message' => __('Tema no encontrado.', 'flavor-chat-ia')]);
        }

        $resultado = $wpdb->insert($this->tabla_respuestas, [
            'tema_id' => $tema_id,
            'padre_id' => $padre_id ?: null,
            'autor_id' => get_current_user_id(),
            'contenido' => $contenido,
            'estado' => 'publicado',
            'fecha_creacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            // Actualizar fecha de actividad del tema
            $wpdb->update(
                $this->tabla_temas,
                ['fecha_actividad' => current_time('mysql')],
                ['id' => $tema_id]
            );

            wp_send_json_success([
                'message' => __('Respuesta publicada.', 'flavor-chat-ia'),
                'respuesta_id' => $wpdb->insert_id,
            ]);
        } else {
            wp_send_json_error(['message' => __('Error al publicar respuesta.', 'flavor-chat-ia')]);
        }
    }

    /**
     * AJAX: Votar respuesta
     */
    public function ajax_votar() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $respuesta_id = absint($_POST['respuesta_id'] ?? 0);
        $tipo = sanitize_text_field($_POST['tipo'] ?? '');

        if (!$respuesta_id || !in_array($tipo, ['positivo', 'negativo'])) {
            wp_send_json_error(['message' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        $usuario_id = get_current_user_id();
        global $wpdb;

        // Verificar voto existente
        $voto_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tabla_votos} WHERE respuesta_id = %d AND usuario_id = %d",
            $respuesta_id, $usuario_id
        ));

        if ($voto_existente) {
            if ($voto_existente->tipo === $tipo) {
                // Quitar voto
                $wpdb->delete($this->tabla_votos, [
                    'respuesta_id' => $respuesta_id,
                    'usuario_id' => $usuario_id,
                ]);
                $cambio = $tipo === 'positivo' ? -1 : 1;
            } else {
                // Cambiar voto
                $wpdb->update(
                    $this->tabla_votos,
                    ['tipo' => $tipo],
                    ['respuesta_id' => $respuesta_id, 'usuario_id' => $usuario_id]
                );
                $cambio = $tipo === 'positivo' ? 2 : -2;
            }
        } else {
            // Nuevo voto
            $wpdb->insert($this->tabla_votos, [
                'respuesta_id' => $respuesta_id,
                'usuario_id' => $usuario_id,
                'tipo' => $tipo,
                'fecha' => current_time('mysql'),
            ]);
            $cambio = $tipo === 'positivo' ? 1 : -1;
        }

        // Actualizar contador de votos
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tabla_respuestas} SET votos = votos + %d WHERE id = %d",
            $cambio, $respuesta_id
        ));

        $nuevo_total = $wpdb->get_var($wpdb->prepare(
            "SELECT votos FROM {$this->tabla_respuestas} WHERE id = %d",
            $respuesta_id
        ));

        wp_send_json_success([
            'votos' => absint($nuevo_total),
        ]);
    }

    /**
     * AJAX: Marcar como solución
     */
    public function ajax_marcar_solucion() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $respuesta_id = absint($_POST['respuesta_id'] ?? 0);
        $usuario_id = get_current_user_id();

        global $wpdb;

        // Obtener respuesta y tema
        $respuesta = $wpdb->get_row($wpdb->prepare(
            "SELECT r.*, t.autor_id as tema_autor_id, t.id as tema_id
             FROM {$this->tabla_respuestas} r
             LEFT JOIN {$this->tabla_temas} t ON r.tema_id = t.id
             WHERE r.id = %d",
            $respuesta_id
        ));

        if (!$respuesta) {
            wp_send_json_error(['message' => __('Respuesta no encontrada.', 'flavor-chat-ia')]);
        }

        // Solo el autor del tema puede marcar solución
        if ($respuesta->tema_autor_id != $usuario_id) {
            wp_send_json_error(['message' => __('No tienes permiso para esta acción.', 'flavor-chat-ia')]);
        }

        // Marcar respuesta como solución
        $wpdb->update(
            $this->tabla_respuestas,
            ['es_solucion' => 1],
            ['id' => $respuesta_id]
        );

        // Marcar tema como resuelto
        $wpdb->update(
            $this->tabla_temas,
            ['tiene_solucion' => 1],
            ['id' => $respuesta->tema_id]
        );

        wp_send_json_success([
            'message' => __('Respuesta marcada como solución.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Buscar en foros
     */
    public function ajax_buscar() {
        $busqueda = sanitize_text_field($_POST['q'] ?? $_GET['q'] ?? '');

        if (strlen($busqueda) < 3) {
            wp_send_json_error(['message' => __('La búsqueda debe tener al menos 3 caracteres.', 'flavor-chat-ia')]);
        }

        $html = $this->realizar_busqueda($busqueda);
        wp_send_json_success(['html' => $html]);
    }

    /**
     * AJAX: Obtener temas
     */
    public function ajax_obtener_temas() {
        $foro_id = absint($_POST['foro_id'] ?? 0);
        $pagina = max(1, absint($_POST['pagina'] ?? 1));
        $por_pagina = 20;

        global $wpdb;

        $where = "t.estado = 'publicado'";
        if ($foro_id) {
            $where .= $wpdb->prepare(" AND t.foro_id = %d", $foro_id);
        }

        $temas = $wpdb->get_results("
            SELECT t.*, f.nombre as foro_nombre, u.display_name as autor_nombre,
                   (SELECT COUNT(*) FROM {$this->tabla_respuestas} WHERE tema_id = t.id) as total_respuestas
            FROM {$this->tabla_temas} t
            LEFT JOIN {$this->tabla_foros} f ON t.foro_id = f.id
            LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
            WHERE {$where}
            ORDER BY t.es_fijado DESC, t.fecha_actividad DESC
            LIMIT {$por_pagina} OFFSET " . (($pagina - 1) * $por_pagina)
        );

        wp_send_json_success(['temas' => $temas]);
    }

    /**
     * AJAX: Reportar contenido
     */
    public function ajax_reportar() {
        check_ajax_referer('flavor_foros_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', 'flavor-chat-ia')]);
        }

        $tipo = sanitize_text_field($_POST['tipo'] ?? '');
        $id = absint($_POST['id'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        if (!in_array($tipo, ['tema', 'respuesta']) || !$id) {
            wp_send_json_error(['message' => __('Datos inválidos.', 'flavor-chat-ia')]);
        }

        // Aquí se podría guardar el reporte en una tabla o enviar notificación
        do_action('flavor_foros_contenido_reportado', $tipo, $id, get_current_user_id(), $motivo);

        wp_send_json_success([
            'message' => __('Gracias por tu reporte. Lo revisaremos pronto.', 'flavor-chat-ia'),
        ]);
    }

    // =========================================================
    // HELPERS
    // =========================================================

    /**
     * Realiza búsqueda en foros
     */
    private function realizar_busqueda($busqueda) {
        global $wpdb;
        $like = '%' . $wpdb->esc_like($busqueda) . '%';

        $resultados = $wpdb->get_results($wpdb->prepare("
            SELECT t.id, t.titulo, t.contenido, t.fecha_creacion, f.nombre as foro_nombre,
                   u.display_name as autor_nombre
            FROM {$this->tabla_temas} t
            LEFT JOIN {$this->tabla_foros} f ON t.foro_id = f.id
            LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
            WHERE t.estado = 'publicado'
              AND (t.titulo LIKE %s OR t.contenido LIKE %s)
            ORDER BY t.fecha_actividad DESC
            LIMIT 50
        ", $like, $like));

        if (empty($resultados)) {
            return '<div class="flavor-alert flavor-alert-info">' .
                   __('No se encontraron resultados.', 'flavor-chat-ia') . '</div>';
        }

        $html = '<div class="flavor-resultados-lista">';
        foreach ($resultados as $tema) {
            $html .= sprintf(
                '<div class="flavor-resultado-item">
                    <h4><a href="%s">%s</a></h4>
                    <p>%s</p>
                    <div class="flavor-resultado-meta">
                        <span>%s</span> &middot; <span>%s</span> &middot; <span>%s</span>
                    </div>
                </div>',
                esc_url(add_query_arg('tema_id', $tema->id)),
                esc_html($tema->titulo),
                esc_html(wp_trim_words($tema->contenido, 30)),
                esc_html($tema->foro_nombre),
                esc_html($tema->autor_nombre),
                human_time_diff(strtotime($tema->fecha_creacion))
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Obtiene el voto del usuario para una respuesta
     */
    private function obtener_voto_usuario($respuesta_id, $usuario_id) {
        if (!$usuario_id) {
            return null;
        }

        global $wpdb;
        return $wpdb->get_var($wpdb->prepare(
            "SELECT tipo FROM {$this->tabla_votos} WHERE respuesta_id = %d AND usuario_id = %d",
            $respuesta_id, $usuario_id
        ));
    }

    /**
     * Renderiza paginación
     */
    private function render_paginacion($total, $por_pagina, $pagina_actual) {
        $total_paginas = ceil($total / $por_pagina);
        if ($total_paginas <= 1) {
            return;
        }

        $url_base = remove_query_arg('pag');
        ?>
        <nav class="flavor-paginacion">
            <?php if ($pagina_actual > 1): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual - 1, $url_base)); ?>"
                   class="flavor-pag-link">
                    <span class="dashicons dashicons-arrow-left-alt2"></span>
                </a>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                <?php if ($i == $pagina_actual): ?>
                    <span class="flavor-pag-actual"><?php echo $i; ?></span>
                <?php else: ?>
                    <a href="<?php echo esc_url(add_query_arg('pag', $i, $url_base)); ?>"
                       class="flavor-pag-link"><?php echo $i; ?></a>
                <?php endif; ?>
            <?php endfor; ?>

            <?php if ($pagina_actual < $total_paginas): ?>
                <a href="<?php echo esc_url(add_query_arg('pag', $pagina_actual + 1, $url_base)); ?>"
                   class="flavor-pag-link">
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            <?php endif; ?>
        </nav>
        <?php
    }
}

// Inicializar
Flavor_Foros_Frontend_Controller::get_instance();
