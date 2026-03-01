<?php
/**
 * Dashboard Tab para Campanias
 *
 * Muestra tabs en el dashboard del usuario para:
 * - Campanias que ha firmado
 * - Campanias que ha creado
 * - Campanias que sigue
 *
 * @package FlavorChatIA
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar las tabs del dashboard de usuario para Campanias
 */
class Flavor_Campanias_Dashboard_Tab {

    /**
     * Instancia singleton
     *
     * @var Flavor_Campanias_Dashboard_Tab|null
     */
    private static $instancia = null;

    /**
     * ID del usuario actual
     *
     * @var int
     */
    private $usuario_id;

    /**
     * Prefijo de la base de datos
     *
     * @var string
     */
    private $prefijo_db;

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->prefijo_db = $wpdb->prefix;
        $this->usuario_id = get_current_user_id();
        $this->init();
    }

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Campanias_Dashboard_Tab
     */
    public static function get_instance() {
        if (null === self::$instancia) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Inicializacion de hooks
     */
    private function init() {
        // Registrar tabs en el dashboard del usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // Registrar assets
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);

        // AJAX handlers para acciones dentro de las tabs
        add_action('wp_ajax_campanias_dashboard_retirar_firma', [$this, 'ajax_retirar_firma']);
        add_action('wp_ajax_campanias_dashboard_dejar_seguir', [$this, 'ajax_dejar_seguir']);
        add_action('wp_ajax_campanias_dashboard_cambiar_estado', [$this, 'ajax_cambiar_estado']);
    }

    /**
     * Registrar las tabs en el filtro del dashboard
     *
     * @param array $tabs Array de tabs existentes
     * @return array Array de tabs modificado
     */
    public function registrar_tabs($tabs) {
        // Tab: Mis Firmas
        $tabs['campanias-mis-firmas'] = [
            'label'       => __('Mis Firmas', 'flavor-chat-ia'),
            'icon'        => '✍️',
            'callback'    => [$this, 'render_tab_mis_firmas'],
            'priority'    => 30,
            'capability'  => 'read',
            'badge_count' => $this->contar_firmas_usuario(),
        ];

        // Tab: Mis Campanias
        $tabs['campanias-mis-campanias'] = [
            'label'       => __('Mis Campanias', 'flavor-chat-ia'),
            'icon'        => '📣',
            'callback'    => [$this, 'render_tab_mis_campanias'],
            'priority'    => 31,
            'capability'  => 'read',
            'badge_count' => $this->contar_campanias_usuario(),
        ];

        // Tab: Siguiendo
        $tabs['campanias-siguiendo'] = [
            'label'       => __('Campanias Siguiendo', 'flavor-chat-ia'),
            'icon'        => '👁️',
            'callback'    => [$this, 'render_tab_siguiendo'],
            'priority'    => 32,
            'capability'  => 'read',
            'badge_count' => $this->contar_campanias_siguiendo(),
        ];

        return $tabs;
    }

    /**
     * Registrar assets CSS y JS
     */
    public function registrar_assets() {
        if (!$this->es_pagina_dashboard()) {
            return;
        }

        wp_enqueue_style(
            'flavor-campanias-dashboard',
            FLAVOR_CHAT_IA_URL . 'includes/modules/campanias/assets/css/campanias-dashboard.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-campanias-dashboard',
            FLAVOR_CHAT_IA_URL . 'includes/modules/campanias/assets/js/campanias-dashboard.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-campanias-dashboard', 'flavorCampaniasDashboard', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('campanias_dashboard_nonce'),
            'strings' => [
                'confirmarRetirarFirma'  => __('¿Seguro que deseas retirar tu firma de esta campaña?', 'flavor-chat-ia'),
                'confirmarDejarSeguir'   => __('¿Seguro que deseas dejar de seguir esta campaña?', 'flavor-chat-ia'),
                'firmaRetirada'          => __('Firma retirada correctamente.', 'flavor-chat-ia'),
                'dejadoDeSeguir'         => __('Has dejado de seguir la campaña.', 'flavor-chat-ia'),
                'errorOperacion'         => __('Error al procesar la operacion.', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Determinar si estamos en una pagina de dashboard
     *
     * @return bool
     */
    private function es_pagina_dashboard() {
        if (!is_user_logged_in()) {
            return false;
        }

        global $post;
        if (!$post) {
            return false;
        }

        // Verificar si contiene shortcode de dashboard o si la URL contiene 'dashboard'
        $shortcodes_dashboard = ['flavor_dashboard', 'user_dashboard', 'mi_cuenta', 'mi_panel'];
        foreach ($shortcodes_dashboard as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return strpos($post->post_name, 'dashboard') !== false ||
               strpos($post->post_name, 'mi-cuenta') !== false ||
               strpos($post->post_name, 'mi-panel') !== false;
    }

    /**
     * Render Tab: Mis Firmas
     * Muestra las campanias que el usuario ha firmado
     */
    public function render_tab_mis_firmas() {
        $campanias_firmadas = $this->obtener_campanias_firmadas();
        ?>
        <div class="flavor-dashboard-tab flavor-campanias-mis-firmas">
            <div class="flavor-tab-header">
                <h2 class="flavor-tab-title">
                    <span class="flavor-tab-icon">✍️</span>
                    <?php esc_html_e('Campanias que has firmado', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-tab-description">
                    <?php esc_html_e('Listado de todas las campanias ciudadanas donde has dejado tu firma de apoyo.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if (empty($campanias_firmadas)): ?>
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">📝</div>
                    <h3><?php esc_html_e('Aun no has firmado ninguna campania', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Explora las campanias activas y firma aquellas que apoyes.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url($this->get_url_campanias()); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Ver campanias activas', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-campanias-grid">
                    <?php foreach ($campanias_firmadas as $campania): ?>
                        <?php $this->render_card_campania($campania, 'firma'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Tab: Mis Campanias
     * Muestra las campanias creadas por el usuario
     */
    public function render_tab_mis_campanias() {
        $mis_campanias = $this->obtener_campanias_creadas();
        ?>
        <div class="flavor-dashboard-tab flavor-campanias-mis-campanias">
            <div class="flavor-tab-header">
                <div class="flavor-tab-header-row">
                    <div>
                        <h2 class="flavor-tab-title">
                            <span class="flavor-tab-icon">📣</span>
                            <?php esc_html_e('Mis Campanias', 'flavor-chat-ia'); ?>
                        </h2>
                        <p class="flavor-tab-description">
                            <?php esc_html_e('Gestiona las campanias que has creado y sigue su progreso.', 'flavor-chat-ia'); ?>
                        </p>
                    </div>
                    <div class="flavor-tab-actions">
                        <a href="<?php echo esc_url($this->get_url_crear_campania()); ?>" class="flavor-btn flavor-btn-primary">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <?php esc_html_e('Nueva Campania', 'flavor-chat-ia'); ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if (empty($mis_campanias)): ?>
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">📣</div>
                    <h3><?php esc_html_e('No has creado ninguna campania todavia', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Crea tu primera campania para movilizar a la comunidad.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url($this->get_url_crear_campania()); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Crear mi primera campania', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-campanias-grid flavor-campanias-grid--gestion">
                    <?php foreach ($mis_campanias as $campania): ?>
                        <?php $this->render_card_campania($campania, 'creador'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Tab: Siguiendo
     * Muestra las campanias que el usuario sigue (como participante)
     */
    public function render_tab_siguiendo() {
        $campanias_siguiendo = $this->obtener_campanias_siguiendo();
        ?>
        <div class="flavor-dashboard-tab flavor-campanias-siguiendo">
            <div class="flavor-tab-header">
                <h2 class="flavor-tab-title">
                    <span class="flavor-tab-icon">👁️</span>
                    <?php esc_html_e('Campanias que sigues', 'flavor-chat-ia'); ?>
                </h2>
                <p class="flavor-tab-description">
                    <?php esc_html_e('Campanias donde participas como colaborador/a y recibes actualizaciones.', 'flavor-chat-ia'); ?>
                </p>
            </div>

            <?php if (empty($campanias_siguiendo)): ?>
                <div class="flavor-empty-state">
                    <div class="flavor-empty-icon">👁️</div>
                    <h3><?php esc_html_e('No estas siguiendo ninguna campania', 'flavor-chat-ia'); ?></h3>
                    <p><?php esc_html_e('Unete a campanias para recibir actualizaciones y colaborar.', 'flavor-chat-ia'); ?></p>
                    <a href="<?php echo esc_url($this->get_url_campanias()); ?>" class="flavor-btn flavor-btn-primary">
                        <?php esc_html_e('Explorar campanias', 'flavor-chat-ia'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="flavor-campanias-grid">
                    <?php foreach ($campanias_siguiendo as $campania): ?>
                        <?php $this->render_card_campania($campania, 'seguidor'); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render card de campania con barra de progreso
     *
     * @param object $campania Datos de la campania
     * @param string $contexto Contexto de visualizacion (firma, creador, seguidor)
     */
    private function render_card_campania($campania, $contexto = 'firma') {
        $porcentaje_progreso = $this->calcular_porcentaje_progreso($campania);
        $estado_clase = $this->get_clase_estado($campania->estado);
        $estado_label = $this->get_label_estado($campania->estado);
        $tipo_label = $this->get_label_tipo($campania->tipo);
        $url_detalle = $this->get_url_detalle_campania($campania->id);
        ?>
        <div class="flavor-campania-card" data-campania-id="<?php echo esc_attr($campania->id); ?>">
            <?php if (!empty($campania->imagen)): ?>
                <div class="flavor-campania-card__imagen">
                    <img src="<?php echo esc_url($campania->imagen); ?>" alt="<?php echo esc_attr($campania->titulo); ?>">
                </div>
            <?php endif; ?>

            <div class="flavor-campania-card__contenido">
                <div class="flavor-campania-card__header">
                    <span class="flavor-campania-card__tipo"><?php echo esc_html($tipo_label); ?></span>
                    <span class="flavor-campania-card__estado <?php echo esc_attr($estado_clase); ?>">
                        <?php echo esc_html($estado_label); ?>
                    </span>
                </div>

                <h3 class="flavor-campania-card__titulo">
                    <a href="<?php echo esc_url($url_detalle); ?>">
                        <?php echo esc_html($campania->titulo); ?>
                    </a>
                </h3>

                <?php if (!empty($campania->descripcion)): ?>
                    <p class="flavor-campania-card__descripcion">
                        <?php echo esc_html(wp_trim_words($campania->descripcion, 20)); ?>
                    </p>
                <?php endif; ?>

                <!-- Barra de progreso -->
                <?php if ($campania->objetivo_firmas > 0): ?>
                    <div class="flavor-campania-card__progreso">
                        <div class="flavor-progreso-barra">
                            <div class="flavor-progreso-barra__relleno" style="width: <?php echo esc_attr(min(100, $porcentaje_progreso)); ?>%"></div>
                        </div>
                        <div class="flavor-progreso-stats">
                            <span class="flavor-progreso-stats__firmas">
                                <strong><?php echo esc_html(number_format_i18n($campania->firmas_actuales)); ?></strong>
                                <?php esc_html_e('firmas', 'flavor-chat-ia'); ?>
                            </span>
                            <span class="flavor-progreso-stats__objetivo">
                                <?php
                                printf(
                                    /* translators: %d: numero de firmas objetivo */
                                    esc_html__('de %s objetivo', 'flavor-chat-ia'),
                                    number_format_i18n($campania->objetivo_firmas)
                                );
                                ?>
                            </span>
                            <span class="flavor-progreso-stats__porcentaje">
                                <?php echo esc_html(round($porcentaje_progreso, 1)); ?>%
                            </span>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="flavor-campania-card__info-simple">
                        <span class="flavor-info-item">
                            <span class="dashicons dashicons-groups"></span>
                            <?php
                            printf(
                                /* translators: %d: numero de participantes */
                                esc_html__('%d participantes', 'flavor-chat-ia'),
                                $this->contar_participantes_campania($campania->id)
                            );
                            ?>
                        </span>
                    </div>
                <?php endif; ?>

                <!-- Meta informacion -->
                <div class="flavor-campania-card__meta">
                    <?php if (!empty($campania->fecha_inicio)): ?>
                        <span class="flavor-meta-item">
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($campania->fecha_inicio))); ?>
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($campania->ubicacion)): ?>
                        <span class="flavor-meta-item">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html(wp_trim_words($campania->ubicacion, 3)); ?>
                        </span>
                    <?php endif; ?>

                    <?php if ($contexto === 'firma' && !empty($campania->fecha_firma)): ?>
                        <span class="flavor-meta-item">
                            <span class="dashicons dashicons-edit"></span>
                            <?php
                            printf(
                                /* translators: %s: fecha de firma */
                                esc_html__('Firmada el %s', 'flavor-chat-ia'),
                                date_i18n(get_option('date_format'), strtotime($campania->fecha_firma))
                            );
                            ?>
                        </span>
                    <?php endif; ?>
                </div>

                <!-- Acciones segun contexto -->
                <div class="flavor-campania-card__acciones">
                    <a href="<?php echo esc_url($url_detalle); ?>" class="flavor-btn flavor-btn-sm flavor-btn-outline">
                        <?php esc_html_e('Ver detalle', 'flavor-chat-ia'); ?>
                    </a>

                    <?php if ($contexto === 'creador'): ?>
                        <a href="<?php echo esc_url($this->get_url_editar_campania($campania->id)); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                            <span class="dashicons dashicons-edit"></span>
                            <?php esc_html_e('Editar', 'flavor-chat-ia'); ?>
                        </a>
                        <button type="button"
                                class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-campania-cambiar-estado"
                                data-campania-id="<?php echo esc_attr($campania->id); ?>"
                                data-estado-actual="<?php echo esc_attr($campania->estado); ?>">
                            <span class="dashicons dashicons-admin-generic"></span>
                            <?php esc_html_e('Estado', 'flavor-chat-ia'); ?>
                        </button>
                    <?php elseif ($contexto === 'firma'): ?>
                        <button type="button"
                                class="flavor-btn flavor-btn-sm flavor-btn-danger-outline flavor-campania-retirar-firma"
                                data-campania-id="<?php echo esc_attr($campania->id); ?>">
                            <?php esc_html_e('Retirar firma', 'flavor-chat-ia'); ?>
                        </button>
                    <?php elseif ($contexto === 'seguidor'): ?>
                        <button type="button"
                                class="flavor-btn flavor-btn-sm flavor-btn-outline flavor-campania-dejar-seguir"
                                data-campania-id="<?php echo esc_attr($campania->id); ?>">
                            <?php esc_html_e('Dejar de seguir', 'flavor-chat-ia'); ?>
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtener campanias firmadas por el usuario
     *
     * @return array
     */
    private function obtener_campanias_firmadas() {
        global $wpdb;

        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';
        $tabla_firmas = $this->prefijo_db . 'flavor_campanias_firmas';

        $sql = $wpdb->prepare(
            "SELECT c.*, f.created_at as fecha_firma
             FROM {$tabla_campanias} c
             INNER JOIN {$tabla_firmas} f ON c.id = f.campania_id
             WHERE f.user_id = %d
             ORDER BY f.created_at DESC",
            $this->usuario_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Obtener campanias creadas por el usuario
     *
     * @return array
     */
    private function obtener_campanias_creadas() {
        global $wpdb;

        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';

        $sql = $wpdb->prepare(
            "SELECT *
             FROM {$tabla_campanias}
             WHERE creador_id = %d
             ORDER BY created_at DESC",
            $this->usuario_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Obtener campanias que el usuario sigue (como participante)
     *
     * @return array
     */
    private function obtener_campanias_siguiendo() {
        global $wpdb;

        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';
        $tabla_participantes = $this->prefijo_db . 'flavor_campanias_participantes';

        $sql = $wpdb->prepare(
            "SELECT c.*, p.rol as rol_participante, p.fecha_union
             FROM {$tabla_campanias} c
             INNER JOIN {$tabla_participantes} p ON c.id = p.campania_id
             WHERE p.user_id = %d
               AND p.estado = 'confirmado'
               AND c.creador_id != %d
             ORDER BY p.fecha_union DESC",
            $this->usuario_id,
            $this->usuario_id
        );

        return $wpdb->get_results($sql);
    }

    /**
     * Contar firmas del usuario
     *
     * @return int
     */
    private function contar_firmas_usuario() {
        global $wpdb;
        $tabla_firmas = $this->prefijo_db . 'flavor_campanias_firmas';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_firmas} WHERE user_id = %d",
            $this->usuario_id
        ));
    }

    /**
     * Contar campanias creadas por el usuario
     *
     * @return int
     */
    private function contar_campanias_usuario() {
        global $wpdb;
        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_campanias} WHERE creador_id = %d",
            $this->usuario_id
        ));
    }

    /**
     * Contar campanias que el usuario sigue
     *
     * @return int
     */
    private function contar_campanias_siguiendo() {
        global $wpdb;
        $tabla_participantes = $this->prefijo_db . 'flavor_campanias_participantes';
        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*)
             FROM {$tabla_participantes} p
             INNER JOIN {$tabla_campanias} c ON p.campania_id = c.id
             WHERE p.user_id = %d
               AND p.estado = 'confirmado'
               AND c.creador_id != %d",
            $this->usuario_id,
            $this->usuario_id
        ));
    }

    /**
     * Contar participantes de una campania
     *
     * @param int $campania_id ID de la campania
     * @return int
     */
    private function contar_participantes_campania($campania_id) {
        global $wpdb;
        $tabla_participantes = $this->prefijo_db . 'flavor_campanias_participantes';

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$tabla_participantes} WHERE campania_id = %d AND estado = 'confirmado'",
            $campania_id
        ));
    }

    /**
     * Calcular porcentaje de progreso de firmas
     *
     * @param object $campania Datos de la campania
     * @return float
     */
    private function calcular_porcentaje_progreso($campania) {
        if (empty($campania->objetivo_firmas) || $campania->objetivo_firmas <= 0) {
            return 0;
        }
        return ($campania->firmas_actuales / $campania->objetivo_firmas) * 100;
    }

    /**
     * Obtener clase CSS segun estado
     *
     * @param string $estado Estado de la campania
     * @return string
     */
    private function get_clase_estado($estado) {
        $clases = [
            'planificada' => 'flavor-estado--planificada',
            'activa'      => 'flavor-estado--activa',
            'pausada'     => 'flavor-estado--pausada',
            'completada'  => 'flavor-estado--completada',
            'cancelada'   => 'flavor-estado--cancelada',
        ];
        return $clases[$estado] ?? 'flavor-estado--default';
    }

    /**
     * Obtener label de estado
     *
     * @param string $estado Estado de la campania
     * @return string
     */
    private function get_label_estado($estado) {
        $labels = [
            'planificada' => __('Planificada', 'flavor-chat-ia'),
            'activa'      => __('Activa', 'flavor-chat-ia'),
            'pausada'     => __('Pausada', 'flavor-chat-ia'),
            'completada'  => __('Completada', 'flavor-chat-ia'),
            'cancelada'   => __('Cancelada', 'flavor-chat-ia'),
        ];
        return $labels[$estado] ?? ucfirst($estado);
    }

    /**
     * Obtener label de tipo de campania
     *
     * @param string $tipo Tipo de campania
     * @return string
     */
    private function get_label_tipo($tipo) {
        $labels = [
            'protesta'          => __('Protesta', 'flavor-chat-ia'),
            'recogida_firmas'   => __('Recogida de firmas', 'flavor-chat-ia'),
            'concentracion'     => __('Concentracion', 'flavor-chat-ia'),
            'boicot'            => __('Boicot', 'flavor-chat-ia'),
            'denuncia_publica'  => __('Denuncia publica', 'flavor-chat-ia'),
            'sensibilizacion'   => __('Sensibilizacion', 'flavor-chat-ia'),
            'accion_legal'      => __('Accion legal', 'flavor-chat-ia'),
            'otra'              => __('Otra', 'flavor-chat-ia'),
        ];
        return $labels[$tipo] ?? ucfirst(str_replace('_', ' ', $tipo));
    }

    /**
     * Obtener URL de listado de campanias
     *
     * @return string
     */
    private function get_url_campanias() {
        return Flavor_Chat_Helpers::get_action_url('campanias', 'listar') ?: home_url('/campanias/');
    }

    /**
     * Obtener URL de detalle de campania
     *
     * @param int $campania_id ID de la campania
     * @return string
     */
    private function get_url_detalle_campania($campania_id) {
        $base_url = Flavor_Chat_Helpers::get_action_url('campanias', 'detalle') ?: home_url('/campania/');
        return add_query_arg('campania_id', $campania_id, $base_url);
    }

    /**
     * Obtener URL de crear campania
     *
     * @return string
     */
    private function get_url_crear_campania() {
        return Flavor_Chat_Helpers::get_action_url('campanias', 'crear') ?: home_url('/crear-campania/');
    }

    /**
     * Obtener URL de editar campania
     *
     * @param int $campania_id ID de la campania
     * @return string
     */
    private function get_url_editar_campania($campania_id) {
        $base_url = Flavor_Chat_Helpers::get_action_url('campanias', 'editar') ?: home_url('/editar-campania/');
        return add_query_arg('campania_id', $campania_id, $base_url);
    }

    /**
     * AJAX: Retirar firma de una campania
     */
    public function ajax_retirar_firma() {
        check_ajax_referer('campanias_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $campania_id = intval($_POST['campania_id'] ?? 0);
        $tabla_firmas = $this->prefijo_db . 'flavor_campanias_firmas';
        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';

        // Verificar que la firma existe
        $firma_existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_firmas} WHERE campania_id = %d AND user_id = %d",
            $campania_id,
            get_current_user_id()
        ));

        if (!$firma_existe) {
            wp_send_json_error(['error' => __('No tienes una firma registrada en esta campania.', 'flavor-chat-ia')]);
        }

        // Eliminar la firma
        $resultado = $wpdb->delete(
            $tabla_firmas,
            [
                'campania_id' => $campania_id,
                'user_id'     => get_current_user_id(),
            ],
            ['%d', '%d']
        );

        if ($resultado === false) {
            wp_send_json_error(['error' => __('Error al retirar la firma.', 'flavor-chat-ia')]);
        }

        // Actualizar contador de firmas
        $wpdb->query($wpdb->prepare(
            "UPDATE {$tabla_campanias} SET firmas_actuales = GREATEST(0, firmas_actuales - 1) WHERE id = %d",
            $campania_id
        ));

        wp_send_json_success([
            'mensaje' => __('Firma retirada correctamente.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Dejar de seguir una campania
     */
    public function ajax_dejar_seguir() {
        check_ajax_referer('campanias_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $campania_id = intval($_POST['campania_id'] ?? 0);
        $tabla_participantes = $this->prefijo_db . 'flavor_campanias_participantes';

        // Eliminar participacion
        $resultado = $wpdb->delete(
            $tabla_participantes,
            [
                'campania_id' => $campania_id,
                'user_id'     => get_current_user_id(),
            ],
            ['%d', '%d']
        );

        if ($resultado === false) {
            wp_send_json_error(['error' => __('Error al abandonar la campania.', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'mensaje' => __('Has dejado de seguir la campania.', 'flavor-chat-ia'),
        ]);
    }

    /**
     * AJAX: Cambiar estado de una campania (solo creador)
     */
    public function ajax_cambiar_estado() {
        check_ajax_referer('campanias_dashboard_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['error' => __('Debes iniciar sesion.', 'flavor-chat-ia')]);
        }

        global $wpdb;
        $campania_id = intval($_POST['campania_id'] ?? 0);
        $nuevo_estado = sanitize_text_field($_POST['nuevo_estado'] ?? '');
        $tabla_campanias = $this->prefijo_db . 'flavor_campanias';

        // Verificar que el usuario es el creador
        $creador_id = $wpdb->get_var($wpdb->prepare(
            "SELECT creador_id FROM {$tabla_campanias} WHERE id = %d",
            $campania_id
        ));

        if ((int) $creador_id !== get_current_user_id()) {
            wp_send_json_error(['error' => __('No tienes permisos para modificar esta campania.', 'flavor-chat-ia')]);
        }

        // Validar estado
        $estados_validos = ['planificada', 'activa', 'pausada', 'completada', 'cancelada'];
        if (!in_array($nuevo_estado, $estados_validos, true)) {
            wp_send_json_error(['error' => __('Estado no valido.', 'flavor-chat-ia')]);
        }

        // Actualizar estado
        $resultado = $wpdb->update(
            $tabla_campanias,
            ['estado' => $nuevo_estado],
            ['id' => $campania_id],
            ['%s'],
            ['%d']
        );

        if ($resultado === false) {
            wp_send_json_error(['error' => __('Error al actualizar el estado.', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'mensaje'      => __('Estado actualizado correctamente.', 'flavor-chat-ia'),
            'nuevo_estado' => $nuevo_estado,
            'estado_label' => $this->get_label_estado($nuevo_estado),
        ]);
    }
}
