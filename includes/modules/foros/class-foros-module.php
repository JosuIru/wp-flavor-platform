<?php
/**
 * Modulo de Foros de Discusion para Chat IA
 *
 * Sistema completo de foros comunitarios con categorias, hilos y respuestas.
 *
 * @package FlavorChatIA
 * @version 1.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Foros - Sistema de foros comunitarios
 */
class Flavor_Chat_Foros_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Notifications_Trait;
    use Flavor_Module_Integration_Consumer;
    use Flavor_Encuestas_Features;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'foros';
        $this->name = 'Foros de Discusion'; // Translation loaded on init
        $this->description = 'Sistema de foros comunitarios con categorias, hilos y respuestas'; // Translation loaded on init

        // Principios Gailu que implementa este modulo
        $this->gailu_principios = ['gobernanza', 'aprendizaje'];
        $this->gailu_contribuye_a = ['cohesion'];

        parent::__construct();

        // Admin pages
        add_action('admin_menu', [$this, 'registrar_paginas_admin']);
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
     * Verifica si el módulo está activo
     */
    public function is_active() {
        return $this->can_activate();
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
     * Define que tipos de contenido acepta este modulo
     *
     * @return array IDs de providers aceptados
     */
    protected function get_accepted_integrations() {
        return ['multimedia', 'videos'];
    }

    /**
     * Define donde se muestran los metaboxes de integracion
     *
     * @return array Configuracion de targets
     */
    protected function get_integration_targets() {
        global $wpdb;
        return [
            [
                'type'    => 'table',
                'table'   => $wpdb->prefix . 'flavor_foros_temas',
                'context' => 'side',
            ],
        ];
    }

    /**
     * Define los tabs que este módulo inyecta en otros módulos
     *
     * Cuando foros está activo, puede mostrar un tab de "Discusión" o "Foro"
     * en los dashboards de grupos de consumo, eventos, comunidades, etc.
     *
     * @return array Configuración de tabs por módulo destino
     */
    public function get_tab_integrations() {
        return [
            // Tab de foro para Grupos de Consumo
            'grupos_consumo' => [
                'id'       => 'foro-grupo',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="grupo_consumo" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('grupo_consumo', $contexto['entity_id']);
                },
            ],

            // Tab de discusión para Eventos
            'eventos' => [
                'id'       => 'discusion-evento',
                'label'    => __('Discusión', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="evento" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('evento', $contexto['entity_id']);
                },
            ],

            // Tab de foro para Comunidades
            'comunidades' => [
                'id'       => 'foro-comunidad',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="comunidad" entidad_id="{entity_id}"]',
                'priority' => 90,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('comunidad', $contexto['entity_id']);
                },
            ],

            'incidencias' => [
                'id'       => 'foro-incidencia',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="incidencia" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('incidencia', $contexto['entity_id']);
                },
            ],

            'documentacion_legal' => [
                'id'       => 'foro-documento-legal',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="documento_legal" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('documento_legal', $contexto['entity_id']);
                },
            ],

            'presupuestos_participativos' => [
                'id'       => 'foro-pp-proyecto',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="pp_proyecto" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('pp_proyecto', $contexto['entity_id']);
                },
            ],

            'saberes_ancestrales' => [
                'id'       => 'foro-saber-ancestral',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="saber_ancestral" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('saber_ancestral', $contexto['entity_id']);
                },
            ],

            'transparencia' => [
                'id'       => 'foro-documento-transparencia',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="documento_transparencia" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('documento_transparencia', $contexto['entity_id']);
                },
            ],

            'avisos_municipales' => [
                'id'       => 'foro-aviso-municipal',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="aviso_municipal" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('aviso_municipal', $contexto['entity_id']);
                },
            ],

            'economia_don' => [
                'id'       => 'foro-economia-don',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="economia_don" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('economia_don', $contexto['entity_id']);
                },
            ],

            'advertising' => [
                'id'       => 'foro-advertising-ad',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="advertising_ad" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('advertising_ad', $contexto['entity_id']);
                },
            ],

            'radio' => [
                'id'       => 'foro-radio-programa',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="radio_programa" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('radio_programa', $contexto['entity_id']);
                },
            ],

            'energia_comunitaria' => [
                'id'       => 'foro-energia-comunidad',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="energia_comunidad" entidad_id="{entity_id}"]',
                'priority' => 95,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('energia_comunidad', $contexto['entity_id']);
                },
            ],

            // Tab de discusión para Cursos
            'cursos' => [
                'id'       => 'foro-curso',
                'label'    => __('Foro del Curso', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="curso" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('curso', $contexto['entity_id']);
                },
            ],

            // Tab de discusión para Talleres
            'talleres' => [
                'id'       => 'foro-taller',
                'label'    => __('Foro del Taller', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="taller" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('taller', $contexto['entity_id']);
                },
            ],

            // Tab para Colectivos
            'colectivos' => [
                'id'       => 'foro-colectivo',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="colectivo" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('colectivo', $contexto['entity_id']);
                },
            ],

            // Tab para Círculos de Cuidados
            'circulos_cuidados' => [
                'id'       => 'foro-circulo',
                'label'    => __('Discusión', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="circulo" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('circulo', $contexto['entity_id']);
                },
            ],

            // Tab para Banco de Tiempo
            'banco_tiempo' => [
                'id'       => 'foro-servicio',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="servicio_bt" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('servicio_bt', $contexto['entity_id']);
                },
            ],

            // Tab de foro para Trabajo Digno
            'trabajo_digno' => [
                'id'       => 'foro-oferta-trabajo',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="trabajo_digno_oferta" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('trabajo_digno_oferta', $contexto['entity_id']);
                },
            ],

            // Tab de foro para Huertos Urbanos
            'huertos_urbanos' => [
                'id'       => 'foro-huerto',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="huerto" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('huerto', $contexto['entity_id']);
                },
            ],
            'participacion' => [
                'id'       => 'foro-propuesta',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="participacion_propuesta" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('participacion_propuesta', $contexto['entity_id']);
                },
            ],
            'economia_suficiencia' => [
                'id'       => 'foro-recurso-suficiencia',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="es_recurso" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('es_recurso', $contexto['entity_id']);
                },
            ],
            'justicia_restaurativa' => [
                'id'       => 'foro-proceso-restaurativo',
                'label'    => __('Foro', 'flavor-chat-ia'),
                'icon'     => 'dashicons-format-chat',
                'content'  => '[flavor_foros_integrado entidad="jr_proceso" entidad_id="{entity_id}"]',
                'priority' => 100,
                'badge'    => function($contexto) {
                    return $this->contar_temas_entidad('jr_proceso', $contexto['entity_id']);
                },
            ],
        ];
    }

    /**
     * Cuenta los temas de foro asociados a una entidad
     *
     * @param string $tipo_entidad Tipo de entidad (grupo_consumo, evento, etc.)
     * @param int    $entidad_id   ID de la entidad
     * @return int Número de temas
     */
    public function contar_temas_entidad($tipo_entidad, $entidad_id) {
        if (!$entidad_id) {
            return 0;
        }

        if (!$this->has_integrated_forum_mapping($tipo_entidad, $entidad_id)) {
            return 0;
        }

        global $wpdb;
        $foro_id = $this->resolve_integrated_forum_id($tipo_entidad, $entidad_id);
        if (!$foro_id) {
            return 0;
        }

        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_hilos)) {
            return 0;
        }

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_hilos}
             WHERE foro_id = %d AND estado != 'eliminado'",
            $foro_id
        ));

        return intval($total);
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        $this->register_as_integration_consumer();
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('init', [$this, 'maybe_create_pages']);
        add_action('init', [$this, 'register_shortcodes']);
        $this->registrar_en_panel_unificado();
        // Cargar Dashboard Tab
        $this->inicializar_dashboard_tab();

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Admin: Gestión de categorías de foros
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('wp_ajax_flavor_foros_guardar_categoria', [$this, 'ajax_guardar_categoria']);
        add_action('wp_ajax_flavor_foros_eliminar_categoria', [$this, 'ajax_eliminar_categoria']);

        // Admin: Moderación de hilos y respuestas
        add_action('wp_ajax_flavor_foros_moderar_hilo', [$this, 'ajax_moderar_hilo']);
        add_action('wp_ajax_flavor_foros_moderar_respuesta', [$this, 'ajax_moderar_respuesta']);

        // Integrar funcionalidades de encuestas
        $this->init_encuestas_features('foro');

        // Cargar controladores frontend
        $this->load_frontend_controllers();
    }

    /**
     * Carga los controladores frontend del módulo
     */
    private function load_frontend_controllers() {
        $ruta_modulo = dirname(__FILE__);

        // Frontend Controller principal
        $archivo_frontend_controller = $ruta_modulo . '/frontend/class-foros-frontend-controller.php';
        if (file_exists($archivo_frontend_controller)) {
            require_once $archivo_frontend_controller;
        }

        // Dashboard Tab para el cliente
        $archivo_dashboard_tab = $ruta_modulo . '/class-foros-dashboard-tab.php';
        if (file_exists($archivo_dashboard_tab)) {
            require_once $archivo_dashboard_tab;
        }
    }

    /**
     * Registra shortcodes del módulo
     */
    public function register_shortcodes() {
        add_shortcode('flavor_foros_integrado', [$this, 'shortcode_foro_integrado']);
        add_shortcode('flavor_foros_listado', [$this, 'shortcode_listado_foros']);
    }

    /**
     * Shortcode: Foro integrado para tabs de otros módulos
     *
     * Muestra un mini-foro asociado a una entidad específica
     *
     * @param array $atts Atributos del shortcode
     * @return string HTML
     */
    public function shortcode_foro_integrado($atts) {
        $atts = shortcode_atts([
            'entidad'    => '',
            'entidad_id' => 0,
            'foro_id'    => 0,
            'limite'     => 10,
        ], $atts);

        $entidad_tipo = sanitize_key($atts['entidad']);
        $entidad_id = absint($atts['entidad_id']);
        $foro_id = absint($atts['foro_id']);

        if (!$foro_id && !$entidad_tipo && !$entidad_id) {
            return '<p class="foros-aviso">' . __('Configuración del foro incompleta.', 'flavor-chat-ia') . '</p>';
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $resolved_foro_id = $foro_id ?: $this->resolve_integrated_forum_id($entidad_tipo, $entidad_id);
        if (!$resolved_foro_id) {
            return '<p class="foros-aviso">' . esc_html__('No hay un foro disponible para este contexto todavía.', 'flavor-chat-ia') . '</p>';
        }

        $foro = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_foros} WHERE id = %d AND estado = 'activo'",
            $resolved_foro_id
        ));

        if (!$foro) {
            return '<p class="foros-aviso">' . esc_html__('El foro asociado no está disponible.', 'flavor-chat-ia') . '</p>';
        }

        $hilos = $wpdb->get_results($wpdb->prepare(
            "SELECT t.*, u.display_name as autor_nombre,
                    (SELECT COUNT(*) FROM {$tabla_respuestas} r WHERE r.hilo_id = t.id AND r.estado != 'eliminado') as num_respuestas,
                    (SELECT MAX(r.created_at) FROM {$tabla_respuestas} r WHERE r.hilo_id = t.id AND r.estado != 'eliminado') as ultima_respuesta
             FROM {$tabla_hilos} t
             LEFT JOIN {$wpdb->users} u ON t.autor_id = u.ID
             WHERE t.foro_id = %d AND t.estado != 'eliminado'
             ORDER BY t.es_fijado DESC, COALESCE(ultima_respuesta, t.ultima_actividad, t.created_at) DESC
             LIMIT %d",
            $resolved_foro_id,
            intval($atts['limite'])
        ));

        $puede_crear = is_user_logged_in();
        $is_fallback = empty($foro_id) && !$this->has_integrated_forum_mapping($entidad_tipo, $entidad_id);

        ob_start();
        ?>
        <div class="flavor-foros-integrado" data-foro-id="<?php echo esc_attr($resolved_foro_id); ?>">

            <?php if ($is_fallback): ?>
            <div class="foros-aviso-contexto" style="margin-bottom:16px;padding:12px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;color:#1d4ed8;">
                <?php
                printf(
                    esc_html__('Mostrando el foro general "%s" mientras se define un mapeo específico para este módulo.', 'flavor-chat-ia'),
                    esc_html($foro->nombre)
                );
                ?>
            </div>
            <?php endif; ?>

            <?php if ($puede_crear): ?>
            <div class="foros-acciones-header">
                <button type="button" class="foros-btn-nuevo-tema">
                    <span class="dashicons dashicons-plus-alt2"></span>
                    <?php _e('Nuevo tema', 'flavor-chat-ia'); ?>
                </button>
            </div>
            <?php endif; ?>

            <?php if (empty($hilos)): ?>
                <div class="foros-vacio">
                    <span class="dashicons dashicons-format-chat"></span>
                    <p><?php _e('Aún no hay temas de discusión.', 'flavor-chat-ia'); ?></p>
                    <?php if ($puede_crear): ?>
                    <p class="foros-vacio-cta"><?php _e('¡Sé el primero en iniciar una conversación!', 'flavor-chat-ia'); ?></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="foros-lista-temas">
                    <?php foreach ($hilos as $tema): ?>
                    <article class="foros-tema <?php echo !empty($tema->es_fijado) ? 'foros-tema--fijado' : ''; ?>">
                        <div class="foros-tema-avatar">
                            <?php echo get_avatar($tema->autor_id, 40); ?>
                        </div>
                        <div class="foros-tema-contenido">
                            <h4 class="foros-tema-titulo">
                                <?php if (!empty($tema->es_fijado)): ?>
                                    <span class="dashicons dashicons-admin-post" title="<?php esc_attr_e('Fijado', 'flavor-chat-ia'); ?>"></span>
                                <?php endif; ?>
                                <a href="<?php echo esc_url(add_query_arg('tema_id', $tema->id, home_url('/mi-portal/foros/'))); ?>">
                                    <?php echo esc_html($tema->titulo); ?>
                                </a>
                            </h4>
                            <div class="foros-tema-meta">
                                <span class="foros-tema-autor"><?php echo esc_html($tema->autor_nombre); ?></span>
                                <span class="foros-tema-fecha"><?php echo esc_html(human_time_diff(strtotime($tema->created_at), current_time('timestamp'))); ?></span>
                                <span class="foros-tema-respuestas">
                                    <span class="dashicons dashicons-admin-comments"></span>
                                    <?php echo intval($tema->num_respuestas); ?>
                                </span>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>

                <?php if (count($hilos) >= intval($atts['limite'])): ?>
                <div class="foros-ver-mas">
                    <a href="<?php echo esc_url(home_url('/mi-portal/foros/')); ?>" class="foros-btn-ver-todos">
                        <?php _e('Ver todos los temas', 'flavor-chat-ia'); ?>
                    </a>
                </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- Modal nuevo tema -->
            <?php if ($puede_crear): ?>
            <div class="foros-modal-tema" style="display: none;">
                <div class="foros-modal-overlay"></div>
                <div class="foros-modal-content">
                    <button class="foros-modal-cerrar">&times;</button>
                    <h3><?php _e('Nuevo tema de discusión', 'flavor-chat-ia'); ?></h3>
                    <form class="foros-form-tema">
                        <?php wp_nonce_field('flavor_foros_nonce', 'foros_nonce'); ?>
                        <input type="hidden" name="foro_id" value="<?php echo esc_attr($resolved_foro_id); ?>">

                        <div class="foros-form-campo">
                            <label><?php _e('Título', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="titulo" required placeholder="<?php esc_attr_e('Título del tema', 'flavor-chat-ia'); ?>">
                        </div>

                        <div class="foros-form-campo">
                            <label><?php _e('Contenido', 'flavor-chat-ia'); ?></label>
                            <textarea name="contenido" rows="5" required placeholder="<?php esc_attr_e('Escribe tu mensaje...', 'flavor-chat-ia'); ?>"></textarea>
                        </div>

                        <button type="submit" class="foros-btn-enviar"><?php _e('Publicar tema', 'flavor-chat-ia'); ?></button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <style>
        .flavor-foros-integrado { font-family: inherit; }
        .foros-acciones-header { margin-bottom: 16px; }
        .foros-btn-nuevo-tema { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: var(--module-color, #3b82f6); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 0.875rem; }
        .foros-btn-nuevo-tema:hover { opacity: 0.9; }
        .foros-vacio { text-align: center; padding: 40px 20px; color: #6b7280; }
        .foros-vacio .dashicons { font-size: 48px; width: 48px; height: 48px; margin-bottom: 12px; opacity: 0.5; }
        .foros-lista-temas { display: flex; flex-direction: column; gap: 12px; }
        .foros-tema { display: flex; gap: 12px; padding: 12px; background: #f9fafb; border-radius: 8px; }
        .foros-tema--fijado { background: #fef3c7; border: 1px solid #fcd34d; }
        .foros-tema-avatar img { border-radius: 50%; }
        .foros-tema-contenido { flex: 1; min-width: 0; }
        .foros-tema-titulo { margin: 0 0 4px; font-size: 0.9375rem; }
        .foros-tema-titulo a { color: inherit; text-decoration: none; }
        .foros-tema-titulo a:hover { color: var(--module-color, #3b82f6); }
        .foros-tema-meta { display: flex; gap: 12px; font-size: 0.8125rem; color: #6b7280; }
        .foros-tema-respuestas { display: inline-flex; align-items: center; gap: 4px; }
        .foros-tema-respuestas .dashicons { font-size: 14px; width: 14px; height: 14px; }
        .foros-ver-mas { text-align: center; margin-top: 16px; }
        .foros-btn-ver-todos { color: var(--module-color, #3b82f6); text-decoration: none; font-size: 0.875rem; }
        .foros-modal-tema { position: fixed; inset: 0; z-index: 9999; display: flex; align-items: center; justify-content: center; }
        .foros-modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.5); }
        .foros-modal-content { position: relative; background: #fff; padding: 24px; border-radius: 12px; max-width: 500px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .foros-modal-cerrar { position: absolute; top: 12px; right: 12px; background: none; border: none; font-size: 24px; cursor: pointer; color: #6b7280; }
        .foros-form-campo { margin-bottom: 16px; }
        .foros-form-campo label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 0.875rem; }
        .foros-form-campo input, .foros-form-campo textarea { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.9375rem; }
        .foros-btn-enviar { width: 100%; padding: 12px; background: var(--module-color, #3b82f6); color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 0.9375rem; font-weight: 500; }
        </style>

        <script>
        (function() {
            const container = document.querySelector('.flavor-foros-integrado');
            if (!container) return;

            const btnNuevo = container.querySelector('.foros-btn-nuevo-tema');
            const modal = container.querySelector('.foros-modal-tema');

            if (btnNuevo && modal) {
                btnNuevo.addEventListener('click', () => modal.style.display = 'flex');
                modal.querySelector('.foros-modal-overlay').addEventListener('click', () => modal.style.display = 'none');
                modal.querySelector('.foros-modal-cerrar').addEventListener('click', () => modal.style.display = 'none');

                const form = modal.querySelector('.foros-form-tema');
                form.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const formData = new FormData(form);
                    formData.append('action', 'flavor_foros_crear_tema');

                    try {
                        const ajaxEndpoint = (window.flavorForosConfig && window.flavorForosConfig.ajaxUrl) || '/wp-admin/admin-ajax.php';
                        const response = await fetch(ajaxEndpoint, {
                            method: 'POST',
                            body: formData
                        });
                        const data = await response.json();
                        if (data.success) {
                            location.reload();
                        } else {
                            alert((data.data && data.data.message) || 'Error al crear el tema');
                        }
                    } catch (err) {
                        console.error(err);
                        alert('Error de conexión');
                    }
                });
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    /**
     * Resuelve el foro usado por una integración embebida.
     *
     * Mientras no exista mapeo real por entidad, usa un foro activo general.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @return int
     */
    private function resolve_integrated_forum_id($entidad_tipo, $entidad_id) {
        global $wpdb;

        $mapped_forum_id = $this->get_integrated_forum_id($entidad_tipo, $entidad_id, true);
        if ($mapped_forum_id > 0) {
            return $mapped_forum_id;
        }

        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            return 0;
        }

        return (int) $wpdb->get_var(
            "SELECT id FROM {$tabla_foros} WHERE estado = 'activo' ORDER BY orden ASC, id ASC LIMIT 1"
        );
    }

    /**
     * Indica si existe mapeo explícito para una integración embebida.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @return bool
     */
    private function has_integrated_forum_mapping($entidad_tipo, $entidad_id) {
        return $this->get_integrated_forum_id($entidad_tipo, $entidad_id, false) > 0;
    }

    /**
     * Obtiene el foro contextual para una entidad integrada.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @param bool   $auto_create
     * @return int
     */
    private function get_integrated_forum_id($entidad_tipo, $entidad_id, $auto_create = false) {
        $entidad_tipo = sanitize_key($entidad_tipo);
        $entidad_id = absint($entidad_id);

        if (!$entidad_tipo || !$entidad_id) {
            return 0;
        }

        $stored_forum_id = $this->get_stored_integrated_forum_id($entidad_tipo, $entidad_id);
        $mapped_forum_id = (int) apply_filters(
            'flavor_foros_resolve_integrated_forum_id',
            $stored_forum_id,
            $entidad_tipo,
            $entidad_id
        );

        if ($mapped_forum_id > 0) {
            return $mapped_forum_id;
        }

        if ($stored_forum_id > 0) {
            return $stored_forum_id;
        }

        if ($auto_create) {
            return $this->maybe_provision_integrated_forum($entidad_tipo, $entidad_id);
        }

        return 0;
    }

    /**
     * Obtiene el mapeo persistido entidad -> foro.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @return int
     */
    private function get_stored_integrated_forum_id($entidad_tipo, $entidad_id) {
        $map = get_option('flavor_foros_entity_forum_map', []);
        if (!is_array($map)) {
            return 0;
        }

        return absint($map[$entidad_tipo][$entidad_id] ?? 0);
    }

    /**
     * Guarda el mapeo persistido entidad -> foro.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @param int    $foro_id
     * @return void
     */
    private function save_integrated_forum_mapping($entidad_tipo, $entidad_id, $foro_id) {
        $entidad_tipo = sanitize_key($entidad_tipo);
        $entidad_id = absint($entidad_id);
        $foro_id = absint($foro_id);

        if (!$entidad_tipo || !$entidad_id || !$foro_id) {
            return;
        }

        $map = get_option('flavor_foros_entity_forum_map', []);
        if (!is_array($map)) {
            $map = [];
        }

        if (!isset($map[$entidad_tipo]) || !is_array($map[$entidad_tipo])) {
            $map[$entidad_tipo] = [];
        }

        $map[$entidad_tipo][$entidad_id] = $foro_id;
        update_option('flavor_foros_entity_forum_map', $map, false);
    }

    /**
     * Crea un foro dedicado para una entidad si aún no existe mapeo.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @return int
     */
    private function maybe_provision_integrated_forum($entidad_tipo, $entidad_id) {
        global $wpdb;

        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            return 0;
        }

        $entity = $this->resolve_integrated_entity_context($entidad_tipo, $entidad_id);
        if (empty($entity['label'])) {
            return 0;
        }

        $foro_id = absint($wpdb->get_var($wpdb->prepare(
            "SELECT id
             FROM {$tabla_foros}
             WHERE nombre = %s AND estado = 'activo'
             ORDER BY id ASC
             LIMIT 1",
            $entity['forum_name']
        )));

        if (!$foro_id) {
            $max_orden = (int) $wpdb->get_var("SELECT COALESCE(MAX(orden), 0) FROM {$tabla_foros}");
            $inserted = $wpdb->insert(
                $tabla_foros,
                [
                    'nombre' => $entity['forum_name'],
                    'descripcion' => $entity['description'],
                    'icono' => 'forum',
                    'orden' => $max_orden + 1,
                    'estado' => 'activo',
                    'moderadores' => null,
                    'created_at' => current_time('mysql'),
                ],
                ['%s', '%s', '%s', '%d', '%s', '%s', '%s']
            );

            if (!$inserted) {
                return 0;
            }

            $foro_id = (int) $wpdb->insert_id;
        }

        if ($foro_id > 0) {
            $this->save_integrated_forum_mapping($entidad_tipo, $entidad_id, $foro_id);
        }

        return $foro_id;
    }

    /**
     * Resuelve la etiqueta de una entidad integrada para crear un foro contextual.
     *
     * @param string $entidad_tipo
     * @param int    $entidad_id
     * @return array{label:string,forum_name:string,description:string}
     */
    private function resolve_integrated_entity_context($entidad_tipo, $entidad_id) {
        global $wpdb;

        $label = '';
        $description = '';

        switch ($entidad_tipo) {
            case 'comunidad':
                $tabla = $wpdb->prefix . 'flavor_comunidades';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT nombre, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->nombre;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'evento':
                $tabla = $wpdb->prefix . 'flavor_eventos';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'incidencia':
                $tabla = $wpdb->prefix . 'flavor_incidencias';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'documento_legal':
                $tabla = $wpdb->prefix . 'flavor_documentacion_legal';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'pp_proyecto':
                $tabla = $wpdb->prefix . 'flavor_pp_proyectos';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'saber_ancestral':
                $post = get_post($entidad_id);
                if ($post && $post->post_type === 'sa_saber') {
                    $label = $post->post_title;
                    $description = wp_trim_words((string) $post->post_content, 30);
                }
                break;

            case 'documento_transparencia':
                $tabla = $wpdb->prefix . 'flavor_transparencia_documentos_publicos';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'aviso_municipal':
                $tabla = $wpdb->prefix . 'flavor_avisos_municipales';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, contenido AS descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'economia_don':
                $tabla = $wpdb->prefix . 'flavor_economia_dones';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'energia_comunidad':
                $tabla = $wpdb->prefix . 'flavor_energia_comunidades';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT nombre, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->nombre;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'grupo_consumo':
                $post = get_post($entidad_id);
                if ($post && $post->post_type === 'gc_grupo') {
                    $label = get_the_title($post);
                    $description = wp_trim_words((string) $post->post_content, 30);
                }
                break;

            case 'podcast_serie':
                $tabla = $wpdb->prefix . 'flavor_podcast_series';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'libro_biblioteca':
                $tabla = $wpdb->prefix . 'flavor_biblioteca_libros';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'taller':
                $tabla = $wpdb->prefix . 'flavor_talleres';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'marketplace_anuncio':
                $tabla = $wpdb->prefix . 'flavor_marketplace_anuncios';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'advertising_ad':
                $post = get_post($entidad_id);
                if ($post && $post->post_type === 'flavor_ad') {
                    $label = $post->post_title;
                    $description = wp_trim_words((string) ($post->post_excerpt ?: $post->post_content), 30);
                }
                break;

            case 'radio_programa':
                $tabla = $wpdb->prefix . 'flavor_radio_programas';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT nombre AS titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'trabajo_digno_oferta':
                $post = get_post($entidad_id);
                if ($post && $post->post_type === 'td_oferta') {
                    $label = $post->post_title;
                    $description = wp_trim_words((string) $post->post_content, 30);
                }
                break;

            case 'huerto':
                $tabla = $wpdb->prefix . 'flavor_huertos';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT nombre, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->nombre;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'participacion_propuesta':
                $tabla = $wpdb->prefix . 'flavor_propuestas';
                if (Flavor_Chat_Helpers::tabla_existe($tabla)) {
                    $entity = $wpdb->get_row($wpdb->prepare(
                        "SELECT titulo, descripcion FROM {$tabla} WHERE id = %d",
                        $entidad_id
                    ));
                    if ($entity) {
                        $label = $entity->titulo;
                        $description = wp_trim_words((string) $entity->descripcion, 30);
                    }
                }
                break;

            case 'jr_proceso':
                $post = get_post($entidad_id);
                if ($post && $post->post_type === 'jr_proceso') {
                    $label = $post->post_title;
                    $description = wp_trim_words((string) $post->post_content, 30);
                }
                break;

            default:
                $post = get_post($entidad_id);
                if ($post instanceof WP_Post) {
                    $label = get_the_title($post);
                    $description = wp_trim_words((string) $post->post_content, 30);
                }
                break;
        }

        if (!$label) {
            return [
                'label' => '',
                'forum_name' => '',
                'description' => '',
            ];
        }

        return [
            'label' => $label,
            'forum_name' => sprintf(__('Foro: %s', 'flavor-chat-ia'), $label),
            'description' => $description ?: sprintf(__('Espacio de debate para %s.', 'flavor-chat-ia'), $label),
        ];
    }

    /**
     * Shortcode: Listado de foros
     */
    public function shortcode_listado_foros($atts) {
        $atts = shortcode_atts([
            'limite' => 20,
            'categoria' => '',
        ], $atts);

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $limite = absint($atts['limite']);
        $categoria = sanitize_text_field($atts['categoria']);

        $where = "WHERE estado = 'activo'";
        if ($categoria) {
            $where .= $wpdb->prepare(" AND categoria = %s", $categoria);
        }

        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $foros = $wpdb->get_results(
            "SELECT f.*,
                    (SELECT COUNT(*) FROM {$tabla_hilos} h WHERE h.foro_id = f.id AND h.estado != 'eliminado') as total_temas,
                    (SELECT MAX(h.ultima_actividad) FROM {$tabla_hilos} h WHERE h.foro_id = f.id) as ultima_actividad
             FROM {$tabla_foros} f
             {$where}
             ORDER BY f.orden ASC, f.created_at DESC
             LIMIT {$limite}"
        );

        ob_start();
        ?>
        <div class="flavor-foros-listado">
            <?php if (empty($foros)): ?>
                <p class="foros-vacio"><?php esc_html_e('No hay foros disponibles.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <div class="foros-grid">
                    <?php foreach ($foros as $foro): ?>
                        <div class="foro-card">
                            <h3 class="foro-titulo">
                                <a href="<?php echo esc_url(add_query_arg('foro_id', $foro->id, home_url('/mi-portal/foros/'))); ?>">
                                    <?php echo esc_html($foro->nombre); ?>
                                </a>
                            </h3>
                            <?php if ($foro->descripcion): ?>
                                <p class="foro-descripcion"><?php echo esc_html(wp_trim_words($foro->descripcion, 20)); ?></p>
                            <?php endif; ?>
                            <div class="foro-meta">
                                <span class="foro-temas"><?php echo esc_html($foro->total_temas ?? 0); ?> <?php esc_html_e('temas', 'flavor-chat-ia'); ?></span>
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
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            $this->create_tables();
            $this->crear_categorias_ejemplo();
        }
    }

    /**
     * Crea categorías de foros de ejemplo
     */
    private function crear_categorias_ejemplo() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        // Verificar si ya hay categorías
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_foros");
        if ($count > 0) {
            return;
        }

        $categorias_ejemplo = [
            [
                'nombre'      => __('General', 'flavor-chat-ia'),
                'descripcion' => __('Discusiones generales de la comunidad. Cualquier tema es bienvenido aquí.', 'flavor-chat-ia'),
                'icono'       => '💬',
                'orden'       => 1,
                'estado'      => 'activo',
            ],
            [
                'nombre'      => __('Presentaciones', 'flavor-chat-ia'),
                'descripcion' => __('¡Preséntate a la comunidad! Cuéntanos quién eres y qué te trae por aquí.', 'flavor-chat-ia'),
                'icono'       => '👋',
                'orden'       => 2,
                'estado'      => 'activo',
            ],
            [
                'nombre'      => __('Ayuda y Soporte', 'flavor-chat-ia'),
                'descripcion' => __('¿Tienes dudas o problemas? Pregunta aquí y la comunidad te ayudará.', 'flavor-chat-ia'),
                'icono'       => '🆘',
                'orden'       => 3,
                'estado'      => 'activo',
            ],
            [
                'nombre'      => __('Ideas y Sugerencias', 'flavor-chat-ia'),
                'descripcion' => __('Comparte tus ideas para mejorar la comunidad. Todas las propuestas son bienvenidas.', 'flavor-chat-ia'),
                'icono'       => '💡',
                'orden'       => 4,
                'estado'      => 'activo',
            ],
            [
                'nombre'      => __('Eventos y Actividades', 'flavor-chat-ia'),
                'descripcion' => __('Organiza o entérate de los próximos eventos y actividades de la comunidad.', 'flavor-chat-ia'),
                'icono'       => '📅',
                'orden'       => 5,
                'estado'      => 'activo',
            ],
        ];

        $fecha_actual = current_time('mysql');

        foreach ($categorias_ejemplo as $categoria) {
            $categoria['created_at'] = $fecha_actual;
            $wpdb->insert($tabla_foros, $categoria);
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
    // Configuracion del Panel de Administracion Unificado
    // =========================================================

    /**
     * Configuracion de admin para el Panel Unificado
     *
     * @return array
     */
    protected function get_admin_config() {
        return [
            'id' => 'foros',
            'label' => __('Foros de Discusion', 'flavor-chat-ia'),
            'icon' => 'dashicons-format-chat',
            'capability' => 'manage_options',
            'categoria' => 'comunicacion',
            'paginas' => [
                [
                    'slug' => 'flavor-foros-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_pagina_dashboard'],
                ],
                [
                    'slug' => 'flavor-foros-listado',
                    'titulo' => __('Foros', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_foros'],
                    'badge' => [$this, 'contar_foros_activos'],
                ],
                [
                    'slug' => 'flavor-foros-moderacion',
                    'titulo' => __('Moderacion', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_moderacion'],
                    'badge' => [$this, 'contar_pendientes_moderacion'],
                ],
            ],
            'dashboard_widget' => [$this, 'render_dashboard_widget'],
            'estadisticas' => [$this, 'get_estadisticas_admin'],
        ];
    }

    /**
     * Helper: Renderiza el encabezado de pagina admin
     *
     * @param string $titulo Titulo de la pagina
     * @param array  $acciones Botones de accion opcionales
     */
    protected function render_page_header($titulo, $acciones = []) {
        ?>
        <h1 class="wp-heading-inline">
            <span class="dashicons dashicons-format-chat"></span>
            <?php echo esc_html($titulo); ?>
        </h1>
        <?php if (!empty($acciones)) : ?>
            <?php foreach ($acciones as $accion) : ?>
                <a href="<?php echo esc_url($accion['url']); ?>" class="page-title-action <?php echo esc_attr($accion['class'] ?? ''); ?>">
                    <?php echo esc_html($accion['label']); ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
        <hr class="wp-header-end">
        <?php
    }

    /**
     * Helper: Renderiza tabs de navegacion
     *
     * @param array  $tabs Lista de tabs
     * @param string $tab_actual Tab activo actualmente
     */
    protected function render_page_tabs($tabs, $tab_actual) {
        $pagina_base = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        ?>
        <nav class="nav-tab-wrapper">
            <?php foreach ($tabs as $tab) : ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=' . $pagina_base . '&tab=' . $tab['slug'])); ?>"
                   class="nav-tab <?php echo $tab_actual === $tab['slug'] ? 'nav-tab-active' : ''; ?>">
                    <?php echo esc_html($tab['label']); ?>
                    <?php if (!empty($tab['badge'])) : ?>
                        <span class="badge" style="background: #d63638; color: #fff; padding: 2px 6px; border-radius: 10px; font-size: 11px; margin-left: 5px;">
                            <?php echo intval($tab['badge']); ?>
                        </span>
                    <?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <?php
    }

    /**
     * Renderiza el dashboard de administracion de foros
     */
    public function render_admin_dashboard() {
        $is_dashboard_viewer = current_user_can('flavor_ver_dashboard') && !current_user_can('manage_options');
        $estadisticas = $this->get_estadisticas_admin();
        ?>
        <div class="wrap flavor-admin-page">
            <?php
            $acciones = $is_dashboard_viewer
                ? [
                    [
                        'label' => __('Ver en portal', 'flavor-chat-ia'),
                        'url' => home_url('/mi-portal/foros/'),
                        'class' => '',
                    ],
                ]
                : [
                    [
                        'label' => __('Nuevo Foro', 'flavor-chat-ia'),
                        'url' => admin_url('admin.php?page=flavor-foros-listado&action=nuevo'),
                        'class' => 'button-primary',
                    ],
                ];
            $this->render_page_header(__('Dashboard de Foros', 'flavor-chat-ia'), $acciones);

            if ($is_dashboard_viewer) :
            ?>
                <div class="notice notice-info"><p><?php esc_html_e('Vista resumida para gestor de grupos. La creación y moderación avanzada siguen reservadas a administración.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>

            <div class="flavor-stats-grid">
                <div class="stat-card">
                    <span class="dashicons dashicons-category"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_foros']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Foros', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-admin-comments"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_hilos']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Hilos', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-format-chat"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_respuestas']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Respuestas', 'flavor-chat-ia'); ?></div>
                </div>
                <div class="stat-card">
                    <span class="dashicons dashicons-visibility"></span>
                    <div class="stat-value"><?php echo intval($estadisticas['total_vistas']); ?></div>
                    <div class="stat-label"><?php esc_html_e('Vistas Totales', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <div class="flavor-admin-section">
                <h2><?php esc_html_e('Ultimos Hilos', 'flavor-chat-ia'); ?></h2>
                <?php $this->render_ultimos_hilos_tabla(); ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de listado de foros
     */
    public function render_admin_foros() {
        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Gestionar Foros', 'flavor-chat-ia'), [
                [
                    'label' => __('Crear Foro', 'flavor-chat-ia'),
                    'url' => admin_url('admin.php?page=flavor-foros-listado&action=nuevo'),
                    'class' => 'button-primary',
                ],
            ]); ?>

            <?php $this->render_foros_tabla(); ?>
        </div>
        <?php
    }

    /**
     * Renderiza la pagina de moderacion
     */
    public function render_admin_moderacion() {
        $tab_actual = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'hilos';
        $pendientes_hilos = $this->contar_hilos_pendientes();
        $pendientes_respuestas = $this->contar_respuestas_reportadas();

        ?>
        <div class="wrap flavor-admin-page">
            <?php $this->render_page_header(__('Moderacion de Foros', 'flavor-chat-ia')); ?>

            <?php $this->render_page_tabs([
                [
                    'slug' => 'hilos',
                    'label' => __('Hilos', 'flavor-chat-ia'),
                    'badge' => $pendientes_hilos,
                ],
                [
                    'slug' => 'respuestas',
                    'label' => __('Respuestas', 'flavor-chat-ia'),
                    'badge' => $pendientes_respuestas,
                ],
                [
                    'slug' => 'reportes',
                    'label' => __('Reportes', 'flavor-chat-ia'),
                ],
            ], $tab_actual); ?>

            <div class="flavor-admin-section">
                <?php
                switch ($tab_actual) {
                    case 'respuestas':
                        $this->render_respuestas_moderacion();
                        break;
                    case 'reportes':
                        $this->render_reportes();
                        break;
                    default:
                        $this->render_hilos_moderacion();
                        break;
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Renderiza el widget del dashboard
     */
    public function render_dashboard_widget() {
        $estadisticas = $this->get_estadisticas_admin();
        ?>
        <div class="flavor-widget-content">
            <ul class="flavor-widget-stats">
                <li>
                    <span class="label"><?php esc_html_e('Foros activos:', 'flavor-chat-ia'); ?></span>
                    <span class="value"><?php echo intval($estadisticas['total_foros']); ?></span>
                </li>
                <li>
                    <span class="label"><?php esc_html_e('Hilos totales:', 'flavor-chat-ia'); ?></span>
                    <span class="value"><?php echo intval($estadisticas['total_hilos']); ?></span>
                </li>
                <li>
                    <span class="label"><?php esc_html_e('Respuestas:', 'flavor-chat-ia'); ?></span>
                    <span class="value"><?php echo intval($estadisticas['total_respuestas']); ?></span>
                </li>
                <li>
                    <span class="label"><?php esc_html_e('Pendientes moderacion:', 'flavor-chat-ia'); ?></span>
                    <span class="value"><?php echo intval($this->contar_pendientes_moderacion()); ?></span>
                </li>
            </ul>
            <p class="flavor-widget-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-foros-dashboard')); ?>" class="button">
                    <?php esc_html_e('Ver Dashboard', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Obtiene estadisticas para el panel de admin
     *
     * @return array
     */
    public function get_estadisticas_admin() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $total_foros = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_foros WHERE estado = 'activo'");
        $total_hilos = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos WHERE estado != 'eliminado'");
        $total_respuestas = $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'visible'");
        $total_vistas = $wpdb->get_var("SELECT SUM(vistas) FROM $tabla_hilos");

        return [
            'total_foros' => intval($total_foros),
            'total_hilos' => intval($total_hilos),
            'total_respuestas' => intval($total_respuestas),
            'total_vistas' => intval($total_vistas),
        ];
    }

    /**
     * Cuenta los foros activos
     *
     * @return int
     */
    public function contar_foros_activos() {
        // Verificar que el módulo esté activo
        if (!$this->can_activate()) {
            return 0;
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_foros WHERE estado = 'activo'");
    }

    /**
     * Cuenta elementos pendientes de moderacion
     *
     * @return int
     */
    public function contar_pendientes_moderacion() {
        return $this->contar_hilos_pendientes() + $this->contar_respuestas_reportadas();
    }

    /**
     * Cuenta hilos pendientes de moderacion
     *
     * @return int
     */
    private function contar_hilos_pendientes() {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $requiere_moderacion = $this->get_setting('requiere_moderacion', false);

        if (!$requiere_moderacion) {
            return 0;
        }

        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_hilos WHERE estado = 'pendiente'");
    }

    /**
     * Cuenta respuestas reportadas
     *
     * @return int
     */
    private function contar_respuestas_reportadas() {
        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';
        return (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_respuestas WHERE estado = 'reportado'");
    }

    /**
     * Renderiza la tabla de ultimos hilos
     */
    private function render_ultimos_hilos_tabla() {
        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $hilos_recientes = $wpdb->get_results(
            "SELECT h.*, f.nombre AS nombre_foro
             FROM $tabla_hilos h
             LEFT JOIN $tabla_foros f ON f.id = h.foro_id
             WHERE h.estado != 'eliminado'
             ORDER BY h.created_at DESC
             LIMIT 10"
        );

        if (empty($hilos_recientes)) {
            echo '<p>' . esc_html__('No hay hilos todavia.', 'flavor-chat-ia') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Titulo', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Foro', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Autor', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Respuestas', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($hilos_recientes as $hilo) {
            $autor = get_user_by('ID', $hilo->autor_id);
            echo '<tr>';
            echo '<td><strong>' . esc_html($hilo->titulo) . '</strong></td>';
            echo '<td>' . esc_html($hilo->nombre_foro) . '</td>';
            echo '<td>' . ($autor ? esc_html($autor->display_name) : '-') . '</td>';
            echo '<td>' . intval($hilo->respuestas_count) . '</td>';
            echo '<td>' . esc_html(date_i18n(get_option('date_format'), strtotime($hilo->created_at))) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Renderiza la tabla de foros
     */
    private function render_foros_tabla() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $foros = $wpdb->get_results("SELECT * FROM $tabla_foros ORDER BY orden ASC, nombre ASC");

        if (empty($foros)) {
            echo '<p>' . esc_html__('No hay foros creados. Crea el primero.', 'flavor-chat-ia') . '</p>';
            return;
        }

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Nombre', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Descripcion', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Estado', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Orden', 'flavor-chat-ia') . '</th>';
        echo '<th>' . esc_html__('Acciones', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($foros as $foro) {
            $estados_clase = [
                'activo' => 'status-active',
                'cerrado' => 'status-warning',
                'archivado' => 'status-inactive',
            ];
            $estado_clase = $estados_clase[$foro->estado] ?? '';

            echo '<tr>';
            echo '<td><strong>' . esc_html($foro->nombre) . '</strong></td>';
            echo '<td>' . esc_html(wp_trim_words($foro->descripcion, 10)) . '</td>';
            echo '<td><span class="status-badge ' . esc_attr($estado_clase) . '">' . esc_html(ucfirst($foro->estado)) . '</span></td>';
            echo '<td>' . intval($foro->orden) . '</td>';
            echo '<td>';
            echo '<a href="#" class="button button-small foro-editar" data-id="' . esc_attr($foro->id) . '" data-nombre="' . esc_attr($foro->nombre) . '" data-descripcion="' . esc_attr($foro->descripcion) . '" data-estado="' . esc_attr($foro->estado) . '" data-orden="' . esc_attr($foro->orden) . '">' . esc_html__('Editar', 'flavor-chat-ia') . '</a> ';
            echo '<a href="' . esc_url(home_url('/foro/' . $foro->slug)) . '" class="button button-small" target="_blank">' . esc_html__('Ver', 'flavor-chat-ia') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';

        // Modal editar foro
        echo '<div id="modal-editar-foro" style="display:none;">
            <div class="modal-overlay" style="position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.7);z-index:100000;">
                <div class="modal-content" style="position:relative;max-width:500px;margin:50px auto;background:#fff;padding:20px;border-radius:4px;">
                    <h2>' . esc_html__('Editar Foro', 'flavor-chat-ia') . '</h2>
                    <form method="post">
                        ' . wp_nonce_field('editar_foro', '_wpnonce', true, false) . '
                        <input type="hidden" name="accion" value="editar_foro">
                        <input type="hidden" name="foro_id" id="edit-foro-id">
                        <table class="form-table">
                            <tr><th><label for="edit-foro-nombre">' . esc_html__('Nombre', 'flavor-chat-ia') . '</label></th>
                            <td><input type="text" id="edit-foro-nombre" name="nombre" class="regular-text" required></td></tr>
                            <tr><th><label for="edit-foro-descripcion">' . esc_html__('Descripción', 'flavor-chat-ia') . '</label></th>
                            <td><textarea id="edit-foro-descripcion" name="descripcion" rows="3" class="large-text"></textarea></td></tr>
                            <tr><th><label for="edit-foro-estado">' . esc_html__('Estado', 'flavor-chat-ia') . '</label></th>
                            <td><select id="edit-foro-estado" name="estado">
                                <option value="activo">' . esc_html__('Activo', 'flavor-chat-ia') . '</option>
                                <option value="cerrado">' . esc_html__('Cerrado', 'flavor-chat-ia') . '</option>
                                <option value="archivado">' . esc_html__('Archivado', 'flavor-chat-ia') . '</option>
                            </select></td></tr>
                            <tr><th><label for="edit-foro-orden">' . esc_html__('Orden', 'flavor-chat-ia') . '</label></th>
                            <td><input type="number" id="edit-foro-orden" name="orden" class="small-text" min="0"></td></tr>
                        </table>
                        <p class="submit">
                            <button type="submit" class="button button-primary">' . esc_html__('Guardar', 'flavor-chat-ia') . '</button>
                            <button type="button" class="button" id="cerrar-modal-foro">' . esc_html__('Cancelar', 'flavor-chat-ia') . '</button>
                        </p>
                    </form>
                </div>
            </div>
        </div>';

        echo '<script>
        jQuery(document).ready(function($) {
            $(".foro-editar").on("click", function(e) {
                e.preventDefault();
                $("#edit-foro-id").val($(this).data("id"));
                $("#edit-foro-nombre").val($(this).data("nombre"));
                $("#edit-foro-descripcion").val($(this).data("descripcion"));
                $("#edit-foro-estado").val($(this).data("estado"));
                $("#edit-foro-orden").val($(this).data("orden"));
                $("#modal-editar-foro").fadeIn();
            });
            $("#cerrar-modal-foro, .modal-overlay").on("click", function(e) {
                if (e.target === this) $("#modal-editar-foro").fadeOut();
            });
        });
        </script>';
    }

    /**
     * Renderiza la seccion de moderacion de hilos
     */
    private function render_hilos_moderacion() {
        echo '<p>' . esc_html__('Aqui apareceran los hilos que requieran moderacion.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderiza la seccion de moderacion de respuestas
     */
    private function render_respuestas_moderacion() {
        echo '<p>' . esc_html__('Aqui apareceran las respuestas reportadas o pendientes de revision.', 'flavor-chat-ia') . '</p>';
    }

    /**
     * Renderiza la seccion de reportes
     */
    private function render_reportes() {
        echo '<p>' . esc_html__('Aqui apareceran los reportes enviados por los usuarios.', 'flavor-chat-ia') . '</p>';
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
                'error' => __('Debes iniciar sesion para editar.', 'flavor-chat-ia'),
            ];
        }

        $mensaje_id = absint($parametros['mensaje_id'] ?? 0);
        $contenido = sanitize_textarea_field($parametros['contenido'] ?? '');
        $motivo_edicion = sanitize_text_field($parametros['motivo_edicion'] ?? '');

        if (!$mensaje_id || empty($contenido)) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesion para editar.', 'flavor-chat-ia'),
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
            return ['success' => false, 'error' => __('Mensaje no encontrado.', 'flavor-chat-ia')];
        }

        if ((int) $mensaje->autor_id !== $usuario_id && !current_user_can('manage_options')) {
            return ['success' => false, 'error' => __('Mensaje no encontrado.', 'flavor-chat-ia')];
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
            return ['success' => false, 'error' => __('Mensaje no encontrado.', 'flavor-chat-ia')];
        }

        return [
            'success' => true,
            'mensaje' => __('Mensaje actualizado correctamente.', 'flavor-chat-ia'),
        ];
    }

    // =========================================================
    // REST API
    // =========================================================

    /**
     * Registra las rutas REST del modulo de foros
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        // GET /flavor/v1/foros - Listar foros/categorias
        register_rest_route($namespace, '/foros', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_listar_foros'],
            'permission_callback' => [$this, 'api_permiso_publico'],
        ]);

        // GET /flavor/v1/foros/{id}/temas - Listar temas de un foro
        register_rest_route($namespace, '/foros/(?P<id>\d+)/temas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_listar_temas_foro'],
            'permission_callback' => [$this, 'api_permiso_publico'],
            'args'                => [
                'id' => [
                    'description' => __('ID del foro', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'pagina' => [
                    'description' => __('Numero de pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 1,
                ],
                'orden' => [
                    'description' => __('Criterio de ordenacion', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'enum'        => ['recientes', 'actividad', 'mas_vistos', 'mas_respuestas'],
                    'default'     => 'actividad',
                ],
            ],
        ]);

        // GET /flavor/v1/foros/temas/{id} - Obtener un tema con respuestas
        register_rest_route($namespace, '/foros/temas/(?P<id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_obtener_tema'],
            'permission_callback' => [$this, 'api_permiso_publico'],
            'args'                => [
                'id' => [
                    'description' => __('ID del tema/hilo', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'pagina' => [
                    'description' => __('Numero de pagina de respuestas', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 1,
                ],
            ],
        ]);

        // POST /flavor/v1/foros/temas - Crear nuevo tema
        register_rest_route($namespace, '/foros/temas', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_crear_tema'],
            'permission_callback' => [$this, 'api_permiso_usuario_autenticado'],
            'args'                => [
                'foro_id' => [
                    'description' => __('ID del foro donde crear el tema', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'titulo' => [
                    'description' => __('Titulo del tema', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'required'    => true,
                ],
                'contenido' => [
                    'description' => __('Contenido del tema', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'required'    => true,
                ],
            ],
        ]);

        // POST /flavor/v1/foros/temas/{id}/responder - Responder a tema
        register_rest_route($namespace, '/foros/temas/(?P<id>\d+)/responder', [
            'methods'             => 'POST',
            'callback'            => [$this, 'api_responder_tema'],
            'permission_callback' => [$this, 'api_permiso_usuario_autenticado'],
            'args'                => [
                'id' => [
                    'description' => __('ID del tema/hilo', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'required'    => true,
                ],
                'contenido' => [
                    'description' => __('Contenido de la respuesta', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'required'    => true,
                ],
                'parent_id' => [
                    'description' => __('ID de la respuesta padre (para respuestas anidadas)', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 0,
                ],
            ],
        ]);

        // GET /flavor/v1/foros/buscar - Buscar en foros
        register_rest_route($namespace, '/foros/buscar', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_buscar'],
            'permission_callback' => [$this, 'api_permiso_publico'],
            'args'                => [
                'busqueda' => [
                    'description' => __('Termino de busqueda', 'flavor-chat-ia'),
                    'type'        => 'string',
                    'required'    => true,
                ],
                'foro_id' => [
                    'description' => __('ID del foro para filtrar (opcional)', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 0,
                ],
                'limite' => [
                    'description' => __('Numero maximo de resultados', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 20,
                ],
            ],
        ]);

        // GET /flavor/v1/foros/mis-temas - Temas del usuario autenticado
        register_rest_route($namespace, '/foros/mis-temas', [
            'methods'             => 'GET',
            'callback'            => [$this, 'api_mis_temas'],
            'permission_callback' => [$this, 'api_permiso_usuario_autenticado'],
            'args'                => [
                'pagina' => [
                    'description' => __('Numero de pagina', 'flavor-chat-ia'),
                    'type'        => 'integer',
                    'default'     => 1,
                ],
            ],
        ]);
    }

    // =========================================================
    // Callbacks de permisos REST
    // =========================================================

    /**
     * Permiso: Acceso publico (cualquier usuario)
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return bool
     */
    public function api_permiso_publico($request) {
        // Verificar rate limiting si esta disponible
        if (class_exists('Flavor_API_Rate_Limiter')) {
            $metodo = strtoupper($request->get_method());
            $tipo_limite = in_array($metodo, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
            return Flavor_API_Rate_Limiter::check_rate_limit($tipo_limite);
        }
        return true;
    }

    /**
     * Permiso: Usuario autenticado requerido
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return bool|WP_Error
     */
    public function api_permiso_usuario_autenticado($request) {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_forbidden',
                __('Debes iniciar sesion para realizar esta accion.', 'flavor-chat-ia'),
                ['status' => 401]
            );
        }

        // Verificar rate limiting si esta disponible
        if (class_exists('Flavor_API_Rate_Limiter')) {
            $metodo = strtoupper($request->get_method());
            $tipo_limite = in_array($metodo, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
            if (!Flavor_API_Rate_Limiter::check_rate_limit($tipo_limite)) {
                return new WP_Error(
                    'rest_rate_limit_exceeded',
                    __('Has excedido el limite de peticiones. Intenta de nuevo mas tarde.', 'flavor-chat-ia'),
                    ['status' => 429]
                );
            }
        }

        return true;
    }

    // =========================================================
    // Callbacks de endpoints REST
    // =========================================================

    /**
     * API: Listar todos los foros/categorias
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_listar_foros($request) {
        $resultado = $this->action_listar_foros([]);

        if (!$resultado['success']) {
            return new WP_REST_Response($resultado, 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Listar temas de un foro especifico
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_listar_temas_foro($request) {
        $parametros = [
            'foro_id' => absint($request['id']),
            'pagina'  => absint($request->get_param('pagina') ?? 1),
            'orden'   => sanitize_text_field($request->get_param('orden') ?? 'actividad'),
        ];

        $resultado = $this->action_ver_foro($parametros);

        if (!$resultado['success']) {
            $codigo_estado = ($resultado['error'] ?? '') === __('Foro no encontrado o no disponible.', 'flavor-chat-ia') ? 404 : 400;
            return new WP_REST_Response($resultado, $codigo_estado);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener un tema/hilo con sus respuestas
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_obtener_tema($request) {
        $parametros = [
            'hilo_id' => absint($request['id']),
            'pagina'  => absint($request->get_param('pagina') ?? 1),
        ];

        $resultado = $this->action_ver_hilo($parametros);

        if (!$resultado['success']) {
            $codigo_estado = ($resultado['error'] ?? '') === __('Hilo no encontrado.', 'flavor-chat-ia') ? 404 : 400;
            return new WP_REST_Response($resultado, $codigo_estado);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Crear un nuevo tema/hilo
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_crear_tema($request) {
        $parametros_json = $request->get_json_params();

        $parametros = [
            'foro_id'   => absint($parametros_json['foro_id'] ?? 0),
            'titulo'    => sanitize_text_field($parametros_json['titulo'] ?? ''),
            'contenido' => sanitize_textarea_field($parametros_json['contenido'] ?? ''),
        ];

        $resultado = $this->action_crear_hilo($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response($resultado, 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Responder a un tema/hilo
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_responder_tema($request) {
        $parametros_json = $request->get_json_params();

        $parametros = [
            'hilo_id'   => absint($request['id']),
            'contenido' => sanitize_textarea_field($parametros_json['contenido'] ?? ''),
            'parent_id' => absint($parametros_json['parent_id'] ?? 0),
        ];

        $resultado = $this->action_responder($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response($resultado, 400);
        }

        return new WP_REST_Response($resultado, 201);
    }

    /**
     * API: Buscar en los foros
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_buscar($request) {
        $parametros = [
            'busqueda' => sanitize_text_field($request->get_param('busqueda') ?? ''),
            'foro_id'  => absint($request->get_param('foro_id') ?? 0),
            'limite'   => min(100, absint($request->get_param('limite') ?? 20)),
        ];

        $resultado = $this->action_buscar($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response($resultado, 400);
        }

        return new WP_REST_Response($resultado, 200);
    }

    /**
     * API: Obtener temas del usuario autenticado
     *
     * @param WP_REST_Request $request Objeto de solicitud REST.
     * @return WP_REST_Response
     */
    public function api_mis_temas($request) {
        $parametros = [
            'pagina' => absint($request->get_param('pagina') ?? 1),
        ];

        $resultado = $this->action_mis_hilos($parametros);

        if (!$resultado['success']) {
            return new WP_REST_Response($resultado, 400);
        }

        return new WP_REST_Response($resultado, 200);
    }
    /**
     * Crea/actualiza páginas del módulo si es necesario
     */
    public function maybe_create_pages() {
        if (!class_exists('Flavor_Page_Creator')) {
            return;
        }

        // En admin: refrescar páginas del módulo
        if (is_admin()) {
            Flavor_Page_Creator::refresh_module_pages('foros');
            return;
        }

        // En frontend: crear páginas si no existen (solo una vez)
        $pagina = get_page_by_path('foros');
        if (!$pagina && !get_option('flavor_foros_pages_created')) {
            Flavor_Page_Creator::create_pages_for_modules(['foros']);
            update_option('flavor_foros_pages_created', 1, false);
        }
    }

    /**
     * Obtiene estadísticas para el dashboard del cliente
     *
     * @return array Estadísticas del módulo
     */
    public function get_estadisticas_dashboard() {
        global $wpdb;
        $estadisticas = [];

        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_foros)) {
            return $estadisticas;
        }

        // Total de foros activos
        $total_foros = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$tabla_foros} WHERE estado = 'activo'"
        );

        $estadisticas['foros'] = [
            'icon' => 'dashicons-format-chat',
            'valor' => $total_foros,
            'label' => __('Foros', 'flavor-chat-ia'),
            'color' => 'purple',
        ];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_hilos)) {
            // Hilos activos
            $hilos_activos = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$tabla_hilos}
                 WHERE estado = 'abierto'
                 AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
            );

            if ($hilos_activos > 0) {
                $estadisticas['hilos_recientes'] = [
                    'icon' => 'dashicons-admin-comments',
                    'valor' => $hilos_activos,
                    'label' => __('Hilos esta semana', 'flavor-chat-ia'),
                    'color' => 'green',
                ];
            }
        }

        $usuario_id = get_current_user_id();
        if ($usuario_id && Flavor_Chat_Helpers::tabla_existe($tabla_hilos)) {
            // Mis hilos
            $mis_hilos = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabla_hilos} WHERE autor_id = %d",
                $usuario_id
            ));

            if ($mis_hilos > 0) {
                $estadisticas['mis_hilos'] = [
                    'icon' => 'dashicons-edit',
                    'valor' => $mis_hilos,
                    'label' => __('Mis hilos', 'flavor-chat-ia'),
                    'color' => 'blue',
                ];
            }
        }

        return $estadisticas;
    }

    /**
     * Define las páginas del módulo (Page Creator V3)
     *
     * @return array Definiciones de páginas
     */
    public function get_pages_definition() {
        return [
            [
                'title' => __('Foros', 'flavor-chat-ia'),
                'slug' => 'foros',
                'content' => '<h1>' . __('Foros de la Comunidad', 'flavor-chat-ia') . '</h1>
<p>' . __('Participa en las discusiones de la comunidad', 'flavor-chat-ia') . '</p>

[flavor_module_listing module="foros" action="listar_temas" columnas="1"]',
                'parent' => 0,
            ],
            [
                'title' => __('Nuevo Tema', 'flavor-chat-ia'),
                'slug' => 'nuevo-tema',
                'content' => '<h1>' . __('Crear Nuevo Tema', 'flavor-chat-ia') . '</h1>
<p>' . __('Inicia una nueva discusión', 'flavor-chat-ia') . '</p>

[flavor_module_form module="foros" action="crear_tema"]',
                'parent' => 'foros',
            ],
            [
                'title' => __('Ver Tema', 'flavor-chat-ia'),
                'slug' => 'tema',
                'content' => '[flavor_module_form module="foros" action="responder_tema"]',
                'parent' => 'foros',
            ],
            [
                'title' => __('Mis Temas', 'flavor-chat-ia'),
                'slug' => 'mis-temas',
                'content' => '<h1>' . __('Mis Temas', 'flavor-chat-ia') . '</h1>

[flavor_module_dashboard module="foros"]',
                'parent' => 'foros',
            ],
        ];
    }

    // =========================================================
    // Administración de Categorías de Foros
    // =========================================================

    /**
     * Registra el menú de administración
     */
    public function register_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __('Gestionar Foros', 'flavor-chat-ia'),
            __('Foros', 'flavor-chat-ia'),
            'manage_options',
            'flavor-foros-admin',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderiza la página de administración de foros
     */
    public function render_admin_page() {
        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        // Obtener todas las categorías de foros
        $categorias = $wpdb->get_results(
            "SELECT f.*,
                    COALESCE(COUNT(DISTINCT h.id), 0) AS total_hilos,
                    COALESCE(SUM(h.respuestas_count), 0) AS total_respuestas
             FROM $tabla_foros f
             LEFT JOIN $tabla_hilos h ON h.foro_id = f.id AND h.estado != 'eliminado'
             GROUP BY f.id
             ORDER BY f.orden ASC, f.nombre ASC"
        );

        // Verificar si estamos editando
        $editando = null;
        if (isset($_GET['action']) && $_GET['action'] === 'editar' && isset($_GET['id'])) {
            $editando = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_foros WHERE id = %d",
                absint($_GET['id'])
            ));
        }

        $nonce = wp_create_nonce('flavor_foros_admin');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">
                <?php esc_html_e('Gestionar Categorías de Foros', 'flavor-chat-ia'); ?>
            </h1>
            <hr class="wp-header-end">

            <div id="flavor-foros-admin" style="display: grid; grid-template-columns: 1fr 350px; gap: 20px; margin-top: 20px;">
                <!-- Lista de foros -->
                <div class="flavor-foros-lista">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 40px;"><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Hilos', 'flavor-chat-ia'); ?></th>
                                <th style="width: 100px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="foros-lista">
                            <?php if (empty($categorias)) : ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <span class="dashicons dashicons-format-chat" style="font-size: 48px; color: #ccc;"></span>
                                        <p><?php esc_html_e('No hay categorías de foros creadas.', 'flavor-chat-ia'); ?></p>
                                        <p><?php esc_html_e('Usa el formulario de la derecha para crear tu primera categoría.', 'flavor-chat-ia'); ?></p>
                                    </td>
                                </tr>
                            <?php else : ?>
                                <?php foreach ($categorias as $cat) : ?>
                                    <tr data-id="<?php echo esc_attr($cat->id); ?>">
                                        <td>
                                            <input type="number"
                                                   value="<?php echo esc_attr($cat->orden); ?>"
                                                   min="0"
                                                   style="width: 50px;"
                                                   class="foro-orden"
                                                   data-id="<?php echo esc_attr($cat->id); ?>">
                                        </td>
                                        <td>
                                            <strong>
                                                <span style="margin-right: 5px;"><?php echo esc_html($cat->icono); ?></span>
                                                <?php echo esc_html($cat->nombre); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo esc_html(wp_trim_words($cat->descripcion, 10)); ?></td>
                                        <td>
                                            <span class="dashicons dashicons-admin-comments"></span>
                                            <?php echo esc_html($cat->total_hilos); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $estado_clase = [
                                                'activo' => 'background: #d4edda; color: #155724;',
                                                'cerrado' => 'background: #fff3cd; color: #856404;',
                                                'archivado' => 'background: #f8d7da; color: #721c24;',
                                            ];
                                            ?>
                                            <span style="padding: 3px 8px; border-radius: 3px; font-size: 11px; <?php echo $estado_clase[$cat->estado] ?? ''; ?>">
                                                <?php echo esc_html(ucfirst($cat->estado)); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-foros-admin&action=editar&id=' . $cat->id)); ?>"
                                               class="button button-small">
                                                <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                                            </a>
                                            <button type="button"
                                                    class="button button-small button-link-delete foro-eliminar"
                                                    data-id="<?php echo esc_attr($cat->id); ?>"
                                                    data-nombre="<?php echo esc_attr($cat->nombre); ?>">
                                                <?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Formulario -->
                <div class="flavor-foros-form" style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h2 style="margin-top: 0;">
                        <?php echo $editando
                            ? esc_html__('Editar Categoría', 'flavor-chat-ia')
                            : esc_html__('Nueva Categoría', 'flavor-chat-ia'); ?>
                    </h2>

                    <form id="form-categoria-foro">
                        <input type="hidden" name="id" value="<?php echo $editando ? esc_attr($editando->id) : ''; ?>">
                        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

                        <p>
                            <label for="nombre"><strong><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?></strong></label>
                            <input type="text"
                                   id="nombre"
                                   name="nombre"
                                   class="widefat"
                                   required
                                   placeholder="<?php esc_attr_e('Ej: General, Soporte, Ideas...', 'flavor-chat-ia'); ?>"
                                   value="<?php echo $editando ? esc_attr($editando->nombre) : ''; ?>">
                        </p>

                        <p>
                            <label for="descripcion"><strong><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></strong></label>
                            <textarea id="descripcion"
                                      name="descripcion"
                                      class="widefat"
                                      rows="3"
                                      placeholder="<?php esc_attr_e('Describe el propósito de este foro...', 'flavor-chat-ia'); ?>"><?php echo $editando ? esc_textarea($editando->descripcion) : ''; ?></textarea>
                        </p>

                        <p>
                            <label for="icono"><strong><?php esc_html_e('Icono', 'flavor-chat-ia'); ?></strong></label>
                            <input type="text"
                                   id="icono"
                                   name="icono"
                                   class="widefat"
                                   placeholder="💬"
                                   value="<?php echo $editando ? esc_attr($editando->icono) : '💬'; ?>">
                            <span class="description"><?php esc_html_e('Usa un emoji o nombre de dashicon (ej: forum, admin-comments)', 'flavor-chat-ia'); ?></span>
                        </p>

                        <p>
                            <label for="orden"><strong><?php esc_html_e('Orden', 'flavor-chat-ia'); ?></strong></label>
                            <input type="number"
                                   id="orden"
                                   name="orden"
                                   class="small-text"
                                   min="0"
                                   value="<?php echo $editando ? esc_attr($editando->orden) : '0'; ?>">
                        </p>

                        <p>
                            <label for="estado"><strong><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></strong></label>
                            <select id="estado" name="estado" class="widefat">
                                <option value="activo" <?php selected($editando->estado ?? 'activo', 'activo'); ?>>
                                    <?php esc_html_e('Activo - Los usuarios pueden crear hilos', 'flavor-chat-ia'); ?>
                                </option>
                                <option value="cerrado" <?php selected($editando->estado ?? '', 'cerrado'); ?>>
                                    <?php esc_html_e('Cerrado - Solo lectura, no se pueden crear hilos', 'flavor-chat-ia'); ?>
                                </option>
                                <option value="archivado" <?php selected($editando->estado ?? '', 'archivado'); ?>>
                                    <?php esc_html_e('Archivado - Oculto del listado público', 'flavor-chat-ia'); ?>
                                </option>
                            </select>
                        </p>

                        <p style="margin-top: 20px;">
                            <button type="submit" class="button button-primary button-large">
                                <?php echo $editando
                                    ? esc_html__('Guardar Cambios', 'flavor-chat-ia')
                                    : esc_html__('Crear Categoría', 'flavor-chat-ia'); ?>
                            </button>
                            <?php if ($editando) : ?>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-foros-admin')); ?>" class="button">
                                    <?php esc_html_e('Cancelar', 'flavor-chat-ia'); ?>
                                </a>
                            <?php endif; ?>
                        </p>
                    </form>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                var nonce = '<?php echo esc_js($nonce); ?>';

                // Guardar categoría
                $('#form-categoria-foro').on('submit', function(e) {
                    e.preventDefault();
                    var $form = $(this);
                    var $btn = $form.find('button[type="submit"]');
                    var originalText = $btn.text();

                    $btn.prop('disabled', true).text('<?php echo esc_js(__('Guardando...', 'flavor-chat-ia')); ?>');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flavor_foros_guardar_categoria',
                            nonce: nonce,
                            id: $form.find('[name="id"]').val(),
                            nombre: $form.find('[name="nombre"]').val(),
                            descripcion: $form.find('[name="descripcion"]').val(),
                            icono: $form.find('[name="icono"]').val(),
                            orden: $form.find('[name="orden"]').val(),
                            estado: $form.find('[name="estado"]').val()
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.href = '<?php echo esc_js(admin_url('admin.php?page=flavor-foros-admin&saved=1')); ?>';
                            } else {
                                alert(response.data || '<?php echo esc_js(__('Error al guardar', 'flavor-chat-ia')); ?>');
                                $btn.prop('disabled', false).text(originalText);
                            }
                        },
                        error: function() {
                            alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
                            $btn.prop('disabled', false).text(originalText);
                        }
                    });
                });

                // Eliminar categoría
                $('.foro-eliminar').on('click', function() {
                    var id = $(this).data('id');
                    var nombre = $(this).data('nombre');

                    if (!confirm('<?php echo esc_js(__('¿Eliminar la categoría', 'flavor-chat-ia')); ?> "' + nombre + '"?\n\n<?php echo esc_js(__('Los hilos dentro de esta categoría también se eliminarán.', 'flavor-chat-ia')); ?>')) {
                        return;
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flavor_foros_eliminar_categoria',
                            nonce: nonce,
                            id: id
                        },
                        success: function(response) {
                            if (response.success) {
                                window.location.reload();
                            } else {
                                alert(response.data || '<?php echo esc_js(__('Error al eliminar', 'flavor-chat-ia')); ?>');
                            }
                        }
                    });
                });

                // Cambiar orden
                $('.foro-orden').on('change', function() {
                    var id = $(this).data('id');
                    var orden = $(this).val();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'flavor_foros_guardar_categoria',
                            nonce: nonce,
                            id: id,
                            orden: orden,
                            solo_orden: 1
                        }
                    });
                });

                // Mostrar mensaje de guardado
                <?php if (isset($_GET['saved'])) : ?>
                    var $notice = $('<div class="notice notice-success is-dismissible"><p><?php echo esc_js(__('Categoría guardada correctamente.', 'flavor-chat-ia')); ?></p></div>');
                    $('.wrap h1').after($notice);
                <?php endif; ?>
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX: Guardar categoría de foro
     */
    public function ajax_guardar_categoria() {
        check_ajax_referer('flavor_foros_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para esta acción.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';

        $id = absint($_POST['id'] ?? 0);
        $solo_orden = isset($_POST['solo_orden']);

        // Si solo actualizamos orden
        if ($solo_orden && $id) {
            $resultado = $wpdb->update(
                $tabla_foros,
                ['orden' => absint($_POST['orden'] ?? 0)],
                ['id' => $id],
                ['%d'],
                ['%d']
            );
            wp_send_json_success();
        }

        // Validar nombre
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            wp_send_json_error(__('El nombre es obligatorio.', 'flavor-chat-ia'));
        }

        $datos = [
            'nombre'      => $nombre,
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'icono'       => sanitize_text_field($_POST['icono'] ?? '💬'),
            'orden'       => absint($_POST['orden'] ?? 0),
            'estado'      => sanitize_key($_POST['estado'] ?? 'activo'),
        ];

        // Validar estado
        if (!in_array($datos['estado'], ['activo', 'cerrado', 'archivado'], true)) {
            $datos['estado'] = 'activo';
        }

        if ($id) {
            // Actualizar
            $resultado = $wpdb->update(
                $tabla_foros,
                $datos,
                ['id' => $id],
                ['%s', '%s', '%s', '%d', '%s'],
                ['%d']
            );
        } else {
            // Insertar
            $datos['created_at'] = current_time('mysql');
            $resultado = $wpdb->insert(
                $tabla_foros,
                $datos,
                ['%s', '%s', '%s', '%d', '%s', '%s']
            );
            $id = $wpdb->insert_id;
        }

        if ($resultado === false) {
            wp_send_json_error(__('Error al guardar en la base de datos.', 'flavor-chat-ia'));
        }

        wp_send_json_success(['id' => $id]);
    }

    /**
     * AJAX: Eliminar categoría de foro
     */
    public function ajax_eliminar_categoria() {
        check_ajax_referer('flavor_foros_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para esta acción.', 'flavor-chat-ia'));
        }

        $id = absint($_POST['id'] ?? 0);
        if (!$id) {
            wp_send_json_error(__('ID no válido.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_foros = $wpdb->prefix . 'flavor_foros';
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        // Obtener hilos del foro
        $hilos = $wpdb->get_col($wpdb->prepare(
            "SELECT id FROM $tabla_hilos WHERE foro_id = %d",
            $id
        ));

        // Eliminar respuestas de los hilos
        if (!empty($hilos)) {
            $placeholders = implode(',', array_fill(0, count($hilos), '%d'));
            $wpdb->query($wpdb->prepare(
                "DELETE FROM $tabla_respuestas WHERE hilo_id IN ($placeholders)",
                $hilos
            ));
        }

        // Eliminar hilos
        $wpdb->delete($tabla_hilos, ['foro_id' => $id], ['%d']);

        // Eliminar foro
        $resultado = $wpdb->delete($tabla_foros, ['id' => $id], ['%d']);

        if ($resultado === false) {
            wp_send_json_error(__('Error al eliminar.', 'flavor-chat-ia'));
        }

        wp_send_json_success();
    }

    /**
     * AJAX: Moderar un hilo (aprobar, cerrar, fijar, eliminar)
     */
    public function ajax_moderar_hilo() {
        check_ajax_referer('flavor_foros_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para esta accion.', 'flavor-chat-ia'));
        }

        $hilo_id = absint($_POST['hilo_id'] ?? 0);
        $accion_moderacion = sanitize_key($_POST['accion_moderacion'] ?? '');

        if (!$hilo_id) {
            wp_send_json_error(__('ID de hilo no valido.', 'flavor-chat-ia'));
        }

        $acciones_permitidas = ['abrir', 'cerrar', 'fijar', 'desfijar', 'eliminar'];
        if (!in_array($accion_moderacion, $acciones_permitidas, true)) {
            wp_send_json_error(__('Accion no permitida.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';

        $datos_actualizar = [];
        $mensaje_exito = '';

        switch ($accion_moderacion) {
            case 'abrir':
                $datos_actualizar = ['estado' => 'abierto', 'es_fijado' => 0];
                $mensaje_exito = __('Hilo abierto correctamente.', 'flavor-chat-ia');
                break;
            case 'cerrar':
                $datos_actualizar = ['estado' => 'cerrado'];
                $mensaje_exito = __('Hilo cerrado correctamente.', 'flavor-chat-ia');
                break;
            case 'fijar':
                $datos_actualizar = ['es_fijado' => 1, 'estado' => 'fijado'];
                $mensaje_exito = __('Hilo fijado correctamente.', 'flavor-chat-ia');
                break;
            case 'desfijar':
                $datos_actualizar = ['es_fijado' => 0, 'estado' => 'abierto'];
                $mensaje_exito = __('Hilo desfijado correctamente.', 'flavor-chat-ia');
                break;
            case 'eliminar':
                $datos_actualizar = ['estado' => 'eliminado'];
                $mensaje_exito = __('Hilo eliminado correctamente.', 'flavor-chat-ia');
                break;
        }

        $datos_actualizar['updated_at'] = current_time('mysql');

        $resultado = $wpdb->update(
            $tabla_hilos,
            $datos_actualizar,
            ['id' => $hilo_id]
        );

        if ($resultado === false) {
            wp_send_json_error(__('Error al actualizar el hilo.', 'flavor-chat-ia'));
        }

        wp_send_json_success(['mensaje' => $mensaje_exito]);
    }

    /**
     * AJAX: Moderar una respuesta (mostrar, ocultar, eliminar)
     */
    public function ajax_moderar_respuesta() {
        check_ajax_referer('flavor_foros_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('No tienes permisos para esta accion.', 'flavor-chat-ia'));
        }

        $respuesta_id = absint($_POST['respuesta_id'] ?? 0);
        $accion_moderacion = sanitize_key($_POST['accion_moderacion'] ?? '');

        if (!$respuesta_id) {
            wp_send_json_error(__('ID de respuesta no valido.', 'flavor-chat-ia'));
        }

        $acciones_permitidas = ['mostrar', 'ocultar', 'eliminar'];
        if (!in_array($accion_moderacion, $acciones_permitidas, true)) {
            wp_send_json_error(__('Accion no permitida.', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_respuestas = $wpdb->prefix . 'flavor_foros_respuestas';

        $datos_actualizar = [];
        $mensaje_exito = '';

        switch ($accion_moderacion) {
            case 'mostrar':
                $datos_actualizar = ['estado' => 'visible'];
                $mensaje_exito = __('Respuesta visible nuevamente.', 'flavor-chat-ia');
                break;
            case 'ocultar':
                $datos_actualizar = ['estado' => 'oculto'];
                $mensaje_exito = __('Respuesta ocultada correctamente.', 'flavor-chat-ia');
                break;
            case 'eliminar':
                $datos_actualizar = ['estado' => 'eliminado'];
                $mensaje_exito = __('Respuesta eliminada correctamente.', 'flavor-chat-ia');
                break;
        }

        $datos_actualizar['updated_at'] = current_time('mysql');

        $resultado = $wpdb->update(
            $tabla_respuestas,
            $datos_actualizar,
            ['id' => $respuesta_id]
        );

        if ($resultado === false) {
            wp_send_json_error(__('Error al actualizar la respuesta.', 'flavor-chat-ia'));
        }

        // Si se elimina respuesta, actualizar contador del hilo
        if ($accion_moderacion === 'eliminar') {
            $tabla_hilos = $wpdb->prefix . 'flavor_foros_hilos';
            $hilo_id = $wpdb->get_var($wpdb->prepare(
                "SELECT hilo_id FROM $tabla_respuestas WHERE id = %d",
                $respuesta_id
            ));

            if ($hilo_id) {
                $wpdb->query($wpdb->prepare(
                    "UPDATE $tabla_hilos SET respuestas_count = GREATEST(0, respuestas_count - 1) WHERE id = %d",
                    $hilo_id
                ));
            }
        }

        wp_send_json_success(['mensaje' => $mensaje_exito]);
    }

    /**
     * Registrar páginas de administración (ocultas del sidebar)
     */
    public function registrar_paginas_admin() {
        $capability = 'manage_options';

        // Dashboard - página oculta (slug principal)
        add_submenu_page(
            null,
            __('Dashboard Foros', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'foros',
            [$this, 'render_pagina_dashboard']
        );

        // Dashboard - página oculta (slug para panel unificado)
        add_submenu_page(
            null,
            __('Dashboard Foros', 'flavor-chat-ia'),
            __('Dashboard', 'flavor-chat-ia'),
            $capability,
            'foros-dashboard',
            [$this, 'render_pagina_dashboard']
        );

        // Listado de foros - página oculta (flavor-foros-listado)
        add_submenu_page(
            null,
            __('Listado de Foros', 'flavor-chat-ia'),
            __('Foros', 'flavor-chat-ia'),
            $capability,
            'flavor-foros-listado',
            [$this, 'render_pagina_foros']
        );

        // Listado de foros - página oculta (foros-listado)
        add_submenu_page(
            null,
            __('Listado de Foros', 'flavor-chat-ia'),
            __('Foros', 'flavor-chat-ia'),
            $capability,
            'foros-listado',
            [$this, 'render_pagina_foros']
        );

        // Hilos - página oculta
        add_submenu_page(
            null,
            __('Hilos de Foros', 'flavor-chat-ia'),
            __('Hilos', 'flavor-chat-ia'),
            $capability,
            'foros-hilos',
            [$this, 'render_pagina_hilos']
        );

        // Moderación - página oculta (flavor-foros-moderacion)
        add_submenu_page(
            null,
            __('Moderación de Foros', 'flavor-chat-ia'),
            __('Moderación', 'flavor-chat-ia'),
            $capability,
            'flavor-foros-moderacion',
            [$this, 'render_pagina_moderacion']
        );

        // Moderación - página oculta (foros-moderacion)
        add_submenu_page(
            null,
            __('Moderación de Foros', 'flavor-chat-ia'),
            __('Moderación', 'flavor-chat-ia'),
            $capability,
            'foros-moderacion',
            [$this, 'render_pagina_moderacion']
        );
    }

    /**
     * Renderizar página dashboard
     */
    public function render_pagina_dashboard() {
        $views_path = dirname(__FILE__) . '/views/dashboard.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Dashboard Foros', 'flavor-chat-ia') . '</h1>';
            echo '<p>' . esc_html__('Panel de administración del módulo de foros.', 'flavor-chat-ia') . '</p></div>';
        }
    }

    /**
     * Renderizar página de foros
     */
    public function render_pagina_foros() {
        $views_path = dirname(__FILE__) . '/views/foros.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Foros', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de hilos
     */
    public function render_pagina_hilos() {
        $views_path = dirname(__FILE__) . '/views/hilos.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Gestión de Hilos', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Renderizar página de moderación
     */
    public function render_pagina_moderacion() {
        $views_path = dirname(__FILE__) . '/views/moderacion.php';
        if (file_exists($views_path)) {
            include $views_path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__('Moderación de Foros', 'flavor-chat-ia') . '</h1></div>';
        }
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'foros',
            'title'    => __('Foros de la Comunidad', 'flavor-chat-ia'),
            'subtitle' => __('Participa en debates y comparte ideas', 'flavor-chat-ia'),
            'icon'     => '💬',
            'color'    => 'primary', // Usa variable CSS --flavor-primary del tema

            'database' => [
                'table'       => 'flavor_foros',
                'primary_key' => 'id',
            ],

            'fields' => [
                'nombre'      => ['label' => __('Nombre', 'flavor-chat-ia'), 'type' => 'text', 'required' => true],
                'descripcion' => ['label' => __('Descripción', 'flavor-chat-ia'), 'type' => 'textarea'],
                'icono'       => ['label' => __('Icono', 'flavor-chat-ia'), 'type' => 'emoji', 'default' => '💬'],
                'estado'      => ['label' => __('Estado', 'flavor-chat-ia'), 'type' => 'select'],
            ],

            'estados' => [
                'activo'    => ['label' => __('Activo', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'cerrado'   => ['label' => __('Cerrado', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '🔒'],
                'archivado' => ['label' => __('Archivado', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '📦'],
            ],

            'stats' => [
                'total_foros'     => ['label' => __('Foros', 'flavor-chat-ia'), 'icon' => '💬', 'color' => 'blue'],
                'total_hilos'     => ['label' => __('Hilos', 'flavor-chat-ia'), 'icon' => '📝', 'color' => 'indigo'],
                'total_respuestas'=> ['label' => __('Respuestas', 'flavor-chat-ia'), 'icon' => '💭', 'color' => 'purple'],
                'usuarios_activos'=> ['label' => __('Usuarios activos', 'flavor-chat-ia'), 'icon' => '👥', 'color' => 'green'],
            ],

            'card' => [
                'title_field'    => 'nombre',
                'subtitle_field' => 'descripcion',
                'badge_field'    => 'estado',
                'icon_field'     => 'icono',
                'meta_fields'    => ['total_hilos', 'ultima_actividad'],
            ],

            'tabs' => [
                'categorias' => [
                    'label'   => __('Categorías', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-category',
                    'content' => 'template:_archive.php',
                ],
                'hilos' => [
                    'label'   => __('Hilos recientes', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-format-chat',
                    'content' => '[flavor_foros_actividad_reciente limite="12"]',
                ],
                'mis-hilos' => [
                    'label'   => __('Mis hilos', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-admin-comments',
                    'content' => 'template:mis-hilos.php',
                ],
                'nuevo-hilo' => [
                    'label'   => __('Nuevo hilo', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-plus-alt',
                    'content' => 'template:nuevo-hilo.php',
                ],
            ],

            'archive' => [
                'columns'      => 2,
                'per_page'     => 10,
                'show_filters' => true,
                'show_search'  => true,
            ],

            'dashboard' => [
                'show_stats'   => true,
                'show_actions' => true,
                'actions'      => [
                    'nuevo_hilo' => ['label' => __('Nuevo hilo', 'flavor-chat-ia'), 'icon' => '➕', 'color' => 'blue'],
                    'ver_foros'  => ['label' => __('Ver foros', 'flavor-chat-ia'), 'icon' => '💬', 'color' => 'indigo'],
                ],
            ],
        ];
    }


    /**
     * Inicializa el dashboard tab del módulo
     */
    private function inicializar_dashboard_tab() {
        $archivo = dirname(__FILE__) . '/class-foros-dashboard-tab.php';
        if (file_exists($archivo)) {
            require_once $archivo;
            if (class_exists('Flavor_Foros_Dashboard_Tab')) {
                Flavor_Foros_Dashboard_Tab::get_instance();
            }
        }
    }
}
