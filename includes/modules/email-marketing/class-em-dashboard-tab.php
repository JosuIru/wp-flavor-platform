<?php
/**
 * Email Marketing Dashboard Tab para el usuario
 *
 * Proporciona tabs en el dashboard del usuario para gestionar:
 * - Suscripciones a listas de email
 * - Preferencias de frecuencia y tipo de emails
 * - Historial de emails recibidos
 *
 * @package FlavorChatIA
 * @subpackage EmailMarketing
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_EM_Dashboard_Tab {

    /**
     * Prefijo para tablas de base de datos
     * @var string
     */
    const TABLE_PREFIX = 'flavor_em_';

    /**
     * Instancia singleton
     * @var Flavor_EM_Dashboard_Tab|null
     */
    private static $instance = null;

    /**
     * Usuario actual
     * @var WP_User|null
     */
    private $usuario_actual = null;

    /**
     * Suscriptor del usuario actual
     * @var object|null
     */
    private $suscriptor_actual = null;

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_EM_Dashboard_Tab
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Registrar tabs en el dashboard del usuario
        add_filter('flavor_user_dashboard_tabs', [$this, 'registrar_tabs'], 10, 1);

        // AJAX handlers para acciones del dashboard
        add_action('wp_ajax_em_dashboard_toggle_suscripcion', [$this, 'ajax_toggle_suscripcion']);
        add_action('wp_ajax_em_dashboard_guardar_preferencias', [$this, 'ajax_guardar_preferencias']);
        add_action('wp_ajax_em_dashboard_marcar_email_leido', [$this, 'ajax_marcar_email_leido']);

        // Encolar assets del dashboard
        add_action('wp_enqueue_scripts', [$this, 'enqueue_dashboard_assets']);
    }

    /**
     * Registrar tabs en el dashboard del usuario
     *
     * @param array $tabs Tabs existentes
     * @return array Tabs modificados
     */
    public function registrar_tabs($tabs) {
        // Solo mostrar si el usuario está logueado
        if (!is_user_logged_in()) {
            return $tabs;
        }

        // Tab de suscripciones
        $tabs['email-suscripciones'] = [
            'titulo' => __('Mis Suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-email-alt',
            'callback' => [$this, 'render_tab_suscripciones'],
            'orden' => 50,
            'grupo' => 'comunicaciones',
        ];

        // Tab de preferencias
        $tabs['email-preferencias'] = [
            'titulo' => __('Preferencias de Email', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-admin-generic',
            'callback' => [$this, 'render_tab_preferencias'],
            'orden' => 51,
            'grupo' => 'comunicaciones',
        ];

        // Tab de historial
        $tabs['email-historial'] = [
            'titulo' => __('Historial de Emails', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'icono' => 'dashicons-list-view',
            'callback' => [$this, 'render_tab_historial'],
            'orden' => 52,
            'grupo' => 'comunicaciones',
        ];

        return $tabs;
    }

    /**
     * Encolar assets del dashboard
     */
    public function enqueue_dashboard_assets() {
        if (!is_user_logged_in()) {
            return;
        }

        // Solo cargar en páginas de dashboard
        if (!$this->is_dashboard_page()) {
            return;
        }

        wp_enqueue_style(
            'flavor-em-dashboard',
            plugins_url('assets/css/em-dashboard.css', __FILE__),
            [],
            defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0'
        );

        wp_enqueue_script(
            'flavor-em-dashboard',
            plugins_url('assets/js/em-dashboard.js', __FILE__),
            ['jquery'],
            defined('FLAVOR_CHAT_IA_VERSION') ? FLAVOR_CHAT_IA_VERSION : '1.0.0',
            true
        );

        wp_localize_script('flavor-em-dashboard', 'flavorEMDashboard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_em_dashboard'),
            'strings' => [
                'guardando' => __('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'guardado' => __('Guardado correctamente', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al guardar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'confirmar_baja' => __('¿Estás seguro de darte de baja de esta lista?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'suscrito' => __('Suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'no_suscrito' => __('No suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Verificar si estamos en una página de dashboard
     *
     * @return bool
     */
    private function is_dashboard_page() {
        global $post;

        if (!$post) {
            return false;
        }

        // Verificar por shortcode de dashboard
        if (has_shortcode($post->post_content, 'flavor_user_dashboard')) {
            return true;
        }

        // Verificar por slug de página
        $dashboard_slugs = ['mi-cuenta', 'dashboard', 'mi-perfil', 'my-account'];
        if (in_array($post->post_name, $dashboard_slugs, true)) {
            return true;
        }

        return false;
    }

    /**
     * Obtener usuario actual
     *
     * @return WP_User|null
     */
    private function get_usuario_actual() {
        if ($this->usuario_actual === null) {
            $this->usuario_actual = wp_get_current_user();
        }
        return $this->usuario_actual;
    }

    /**
     * Obtener suscriptor del usuario actual
     *
     * @return object|null
     */
    private function get_suscriptor_actual() {
        if ($this->suscriptor_actual !== null) {
            return $this->suscriptor_actual;
        }

        $usuario = $this->get_usuario_actual();
        if (!$usuario || !$usuario->ID) {
            return null;
        }

        global $wpdb;
        $tabla_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        // Buscar por usuario_id o por email
        $this->suscriptor_actual = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_suscriptores WHERE usuario_id = %d OR email = %s ORDER BY usuario_id DESC LIMIT 1",
            $usuario->ID,
            $usuario->user_email
        ));

        // Si encontramos por email pero no tiene usuario_id, vincularlo
        if ($this->suscriptor_actual && !$this->suscriptor_actual->usuario_id) {
            $wpdb->update(
                $tabla_suscriptores,
                ['usuario_id' => $usuario->ID],
                ['id' => $this->suscriptor_actual->id]
            );
            $this->suscriptor_actual->usuario_id = $usuario->ID;
        }

        return $this->suscriptor_actual;
    }

    /**
     * Crear suscriptor para el usuario actual si no existe
     *
     * @return object|null
     */
    private function crear_suscriptor_si_no_existe() {
        $suscriptor = $this->get_suscriptor_actual();

        if ($suscriptor) {
            return $suscriptor;
        }

        $usuario = $this->get_usuario_actual();
        if (!$usuario || !$usuario->ID) {
            return null;
        }

        global $wpdb;
        $tabla_suscriptores = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptores';

        $resultado = $wpdb->insert($tabla_suscriptores, [
            'email' => $usuario->user_email,
            'nombre' => $usuario->first_name ?: $usuario->display_name,
            'apellidos' => $usuario->last_name ?: '',
            'usuario_id' => $usuario->ID,
            'estado' => 'activo',
            'origen' => 'registro_usuario',
            'fecha_registro' => current_time('mysql'),
            'fecha_confirmacion' => current_time('mysql'),
        ]);

        if ($resultado) {
            $this->suscriptor_actual = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_suscriptores WHERE id = %d",
                $wpdb->insert_id
            ));
        }

        return $this->suscriptor_actual;
    }

    // =========================================================================
    // TAB: SUSCRIPCIONES
    // =========================================================================

    /**
     * Renderizar tab de suscripciones
     *
     * @return string
     */
    public function render_tab_suscripciones() {
        $suscriptor = $this->get_suscriptor_actual();
        $listas_suscritas = $this->obtener_listas_suscritas();
        $listas_disponibles = $this->obtener_listas_disponibles();

        ob_start();
        ?>
        <div class="em-dashboard-tab em-suscripciones">
            <div class="em-tab-header">
                <h3><?php esc_html_e('Mis Suscripciones de Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="em-tab-descripcion">
                    <?php esc_html_e('Gestiona las listas de email a las que estás suscrito.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (!$suscriptor): ?>
                <div class="em-no-suscriptor">
                    <p><?php esc_html_e('No tienes ninguna suscripción activa.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <button type="button" class="em-btn em-btn-primary" id="em-crear-suscriptor">
                        <?php esc_html_e('Activar suscripciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            <?php else: ?>

                <!-- Estadísticas rápidas -->
                <div class="em-stats-grid">
                    <div class="em-stat-card">
                        <span class="em-stat-numero"><?php echo count($listas_suscritas); ?></span>
                        <span class="em-stat-label"><?php esc_html_e('Listas activas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="em-stat-card">
                        <span class="em-stat-numero"><?php echo intval($suscriptor->total_emails_enviados); ?></span>
                        <span class="em-stat-label"><?php esc_html_e('Emails recibidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                    <div class="em-stat-card">
                        <span class="em-stat-numero"><?php echo intval($suscriptor->total_abiertos); ?></span>
                        <span class="em-stat-label"><?php esc_html_e('Emails leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                    </div>
                </div>

                <!-- Listas suscritas -->
                <div class="em-seccion">
                    <h4><?php esc_html_e('Listas a las que estás suscrito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>

                    <?php if (empty($listas_suscritas)): ?>
                        <p class="em-empty-message">
                            <?php esc_html_e('No estás suscrito a ninguna lista actualmente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    <?php else: ?>
                        <div class="em-listas-grid">
                            <?php foreach ($listas_suscritas as $lista): ?>
                                <div class="em-lista-card em-lista-activa" data-lista-id="<?php echo esc_attr($lista->id); ?>">
                                    <div class="em-lista-info">
                                        <h5 class="em-lista-nombre"><?php echo esc_html($lista->nombre); ?></h5>
                                        <?php if (!empty($lista->descripcion)): ?>
                                            <p class="em-lista-descripcion"><?php echo esc_html($lista->descripcion); ?></p>
                                        <?php endif; ?>
                                        <span class="em-lista-fecha">
                                            <?php
                                            printf(
                                                esc_html__('Suscrito desde: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                                date_i18n(get_option('date_format'), strtotime($lista->fecha_suscripcion))
                                            );
                                            ?>
                                        </span>
                                    </div>
                                    <div class="em-lista-acciones">
                                        <label class="em-toggle">
                                            <input type="checkbox"
                                                   class="em-toggle-suscripcion"
                                                   data-lista-id="<?php echo esc_attr($lista->id); ?>"
                                                   checked>
                                            <span class="em-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Listas disponibles -->
                <?php if (!empty($listas_disponibles)): ?>
                    <div class="em-seccion">
                        <h4><?php esc_html_e('Otras listas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                        <div class="em-listas-grid">
                            <?php foreach ($listas_disponibles as $lista): ?>
                                <div class="em-lista-card em-lista-disponible" data-lista-id="<?php echo esc_attr($lista->id); ?>">
                                    <div class="em-lista-info">
                                        <h5 class="em-lista-nombre"><?php echo esc_html($lista->nombre); ?></h5>
                                        <?php if (!empty($lista->descripcion)): ?>
                                            <p class="em-lista-descripcion"><?php echo esc_html($lista->descripcion); ?></p>
                                        <?php endif; ?>
                                        <span class="em-lista-suscriptores">
                                            <?php
                                            printf(
                                                esc_html(_n('%d suscriptor', '%d suscriptores', $lista->total_suscriptores, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                                                $lista->total_suscriptores
                                            );
                                            ?>
                                        </span>
                                    </div>
                                    <div class="em-lista-acciones">
                                        <label class="em-toggle">
                                            <input type="checkbox"
                                                   class="em-toggle-suscripcion"
                                                   data-lista-id="<?php echo esc_attr($lista->id); ?>">
                                            <span class="em-toggle-slider"></span>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener listas suscritas del usuario actual
     *
     * @return array
     */
    private function obtener_listas_suscritas() {
        $suscriptor = $this->get_suscriptor_actual();

        if (!$suscriptor) {
            return [];
        }

        global $wpdb;
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.fecha_suscripcion, sl.estado as estado_suscripcion
             FROM $tabla_listas l
             INNER JOIN $tabla_relacion sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d AND sl.estado = 'activo' AND l.activa = 1
             ORDER BY l.nombre ASC",
            $suscriptor->id
        ));
    }

    /**
     * Obtener listas disponibles (no suscritas)
     *
     * @return array
     */
    private function obtener_listas_disponibles() {
        $suscriptor = $this->get_suscriptor_actual();

        global $wpdb;
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        if (!$suscriptor) {
            // Si no hay suscriptor, mostrar todas las listas públicas
            return $wpdb->get_results(
                "SELECT * FROM $tabla_listas WHERE activa = 1 AND publica = 1 ORDER BY nombre ASC"
            );
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.* FROM $tabla_listas l
             WHERE l.activa = 1 AND l.publica = 1
             AND l.id NOT IN (
                 SELECT lista_id FROM $tabla_relacion
                 WHERE suscriptor_id = %d AND estado = 'activo'
             )
             ORDER BY l.nombre ASC",
            $suscriptor->id
        ));
    }

    // =========================================================================
    // TAB: PREFERENCIAS
    // =========================================================================

    /**
     * Renderizar tab de preferencias
     *
     * @return string
     */
    public function render_tab_preferencias() {
        $suscriptor = $this->get_suscriptor_actual();
        $preferencias = $this->obtener_preferencias_usuario();

        ob_start();
        ?>
        <div class="em-dashboard-tab em-preferencias">
            <div class="em-tab-header">
                <h3><?php esc_html_e('Preferencias de Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="em-tab-descripcion">
                    <?php esc_html_e('Configura cómo y cuándo quieres recibir nuestros emails.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <form id="em-preferencias-form" class="em-form">
                <?php wp_nonce_field('flavor_em_dashboard', 'em_dashboard_nonce'); ?>

                <!-- Frecuencia de emails -->
                <div class="em-form-seccion">
                    <h4><?php esc_html_e('Frecuencia de envío', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p class="em-form-ayuda">
                        <?php esc_html_e('Elige con qué frecuencia deseas recibir nuestros emails.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>

                    <div class="em-radio-group">
                        <label class="em-radio-option">
                            <input type="radio" name="frecuencia" value="tiempo_real"
                                   <?php checked($preferencias['frecuencia'], 'tiempo_real'); ?>>
                            <span class="em-radio-label">
                                <strong><?php esc_html_e('Tiempo real', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Recibir emails tan pronto como se envíen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-radio-option">
                            <input type="radio" name="frecuencia" value="diario"
                                   <?php checked($preferencias['frecuencia'], 'diario'); ?>>
                            <span class="em-radio-label">
                                <strong><?php esc_html_e('Resumen diario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Un email al día con todas las novedades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-radio-option">
                            <input type="radio" name="frecuencia" value="semanal"
                                   <?php checked($preferencias['frecuencia'], 'semanal'); ?>>
                            <span class="em-radio-label">
                                <strong><?php esc_html_e('Resumen semanal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Un email a la semana con lo más importante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-radio-option">
                            <input type="radio" name="frecuencia" value="mensual"
                                   <?php checked($preferencias['frecuencia'], 'mensual'); ?>>
                            <span class="em-radio-label">
                                <strong><?php esc_html_e('Resumen mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Un email al mes con lo destacado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Tipos de contenido -->
                <div class="em-form-seccion">
                    <h4><?php esc_html_e('Tipos de contenido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                    <p class="em-form-ayuda">
                        <?php esc_html_e('Selecciona qué tipos de emails deseas recibir.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>

                    <div class="em-checkbox-group">
                        <label class="em-checkbox-option">
                            <input type="checkbox" name="tipos[]" value="novedades"
                                   <?php checked(in_array('novedades', $preferencias['tipos'])); ?>>
                            <span class="em-checkbox-label">
                                <strong><?php esc_html_e('Novedades y actualizaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Información sobre nuevas funciones y mejoras', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-checkbox-option">
                            <input type="checkbox" name="tipos[]" value="promociones"
                                   <?php checked(in_array('promociones', $preferencias['tipos'])); ?>>
                            <span class="em-checkbox-label">
                                <strong><?php esc_html_e('Ofertas y promociones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Descuentos exclusivos y ofertas especiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-checkbox-option">
                            <input type="checkbox" name="tipos[]" value="eventos"
                                   <?php checked(in_array('eventos', $preferencias['tipos'])); ?>>
                            <span class="em-checkbox-label">
                                <strong><?php esc_html_e('Eventos y actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Invitaciones a eventos, talleres y actividades', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-checkbox-option">
                            <input type="checkbox" name="tipos[]" value="comunidad"
                                   <?php checked(in_array('comunidad', $preferencias['tipos'])); ?>>
                            <span class="em-checkbox-label">
                                <strong><?php esc_html_e('Noticias de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Actividad de la comunidad y menciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>

                        <label class="em-checkbox-option">
                            <input type="checkbox" name="tipos[]" value="transaccional"
                                   <?php checked(in_array('transaccional', $preferencias['tipos'])); ?>>
                            <span class="em-checkbox-label">
                                <strong><?php esc_html_e('Emails transaccionales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <span><?php esc_html_e('Confirmaciones, recibos y notificaciones importantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            </span>
                        </label>
                    </div>
                </div>

                <!-- Formato preferido -->
                <div class="em-form-seccion">
                    <h4><?php esc_html_e('Formato de email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>

                    <div class="em-radio-group em-radio-inline">
                        <label class="em-radio-option">
                            <input type="radio" name="formato" value="html"
                                   <?php checked($preferencias['formato'], 'html'); ?>>
                            <span class="em-radio-label"><?php esc_html_e('HTML (con imágenes)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>

                        <label class="em-radio-option">
                            <input type="radio" name="formato" value="texto"
                                   <?php checked($preferencias['formato'], 'texto'); ?>>
                            <span class="em-radio-label"><?php esc_html_e('Solo texto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Botones de acción -->
                <div class="em-form-acciones">
                    <button type="submit" class="em-btn em-btn-primary">
                        <?php esc_html_e('Guardar preferencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <span class="em-form-mensaje"></span>
                </div>
            </form>

            <!-- Opción de darse de baja de todo -->
            <div class="em-seccion em-seccion-peligro">
                <h4><?php esc_html_e('Darse de baja', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><?php esc_html_e('Si no deseas recibir más emails, puedes darte de baja de todas las listas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button type="button" class="em-btn em-btn-danger" id="em-baja-total">
                    <?php esc_html_e('Darme de baja de todo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener preferencias del usuario
     *
     * @return array
     */
    private function obtener_preferencias_usuario() {
        $suscriptor = $this->get_suscriptor_actual();
        $usuario = $this->get_usuario_actual();

        $preferencias_default = [
            'frecuencia' => 'tiempo_real',
            'tipos' => ['novedades', 'transaccional'],
            'formato' => 'html',
        ];

        if (!$usuario || !$usuario->ID) {
            return $preferencias_default;
        }

        $preferencias_guardadas = get_user_meta($usuario->ID, 'flavor_em_preferencias', true);

        if (empty($preferencias_guardadas) || !is_array($preferencias_guardadas)) {
            return $preferencias_default;
        }

        return wp_parse_args($preferencias_guardadas, $preferencias_default);
    }

    // =========================================================================
    // TAB: HISTORIAL
    // =========================================================================

    /**
     * Renderizar tab de historial
     *
     * @return string
     */
    public function render_tab_historial() {
        $suscriptor = $this->get_suscriptor_actual();
        $pagina_actual = isset($_GET['em_page']) ? max(1, intval($_GET['em_page'])) : 1;
        $por_pagina = 10;
        $historial = $this->obtener_historial_emails($pagina_actual, $por_pagina);

        ob_start();
        ?>
        <div class="em-dashboard-tab em-historial">
            <div class="em-tab-header">
                <h3><?php esc_html_e('Historial de Emails', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <p class="em-tab-descripcion">
                    <?php esc_html_e('Revisa los emails que has recibido.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <?php if (!$suscriptor): ?>
                <div class="em-empty-state">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p><?php esc_html_e('No tienes historial de emails.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php elseif (empty($historial['items'])): ?>
                <div class="em-empty-state">
                    <span class="dashicons dashicons-email-alt"></span>
                    <p><?php esc_html_e('Aún no has recibido ningún email.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </div>
            <?php else: ?>

                <!-- Filtros -->
                <div class="em-historial-filtros">
                    <select id="em-filtro-campania" class="em-select">
                        <option value=""><?php esc_html_e('Todas las campañas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <?php foreach ($this->obtener_campanias_usuario() as $campania): ?>
                            <option value="<?php echo esc_attr($campania->id); ?>">
                                <?php echo esc_html($campania->nombre); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <select id="em-filtro-estado" class="em-select">
                        <option value=""><?php esc_html_e('Todos los estados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="abierto"><?php esc_html_e('Leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="no_abierto"><?php esc_html_e('No leídos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </div>

                <!-- Lista de emails -->
                <div class="em-historial-lista">
                    <?php foreach ($historial['items'] as $email): ?>
                        <div class="em-email-item <?php echo $email->abierto ? 'em-leido' : 'em-no-leido'; ?>"
                             data-email-id="<?php echo esc_attr($email->id); ?>">
                            <div class="em-email-indicador">
                                <?php if ($email->abierto): ?>
                                    <span class="dashicons dashicons-yes-alt" title="<?php esc_attr_e('Leído', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-marker" title="<?php esc_attr_e('No leído', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></span>
                                <?php endif; ?>
                            </div>
                            <div class="em-email-contenido">
                                <h5 class="em-email-asunto"><?php echo esc_html($email->asunto); ?></h5>
                                <div class="em-email-meta">
                                    <?php if (!empty($email->campania_nombre)): ?>
                                        <span class="em-email-campania"><?php echo esc_html($email->campania_nombre); ?></span>
                                    <?php endif; ?>
                                    <span class="em-email-fecha">
                                        <?php echo esc_html(human_time_diff(strtotime($email->enviado_en), current_time('timestamp'))); ?>
                                        <?php esc_html_e('atrás', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="em-email-acciones">
                                <?php if ($email->clicks > 0): ?>
                                    <span class="em-email-clicks" title="<?php esc_attr_e('Clicks en enlaces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                                        <span class="dashicons dashicons-admin-links"></span>
                                        <?php echo intval($email->clicks); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Paginación -->
                <?php if ($historial['total_paginas'] > 1): ?>
                    <div class="em-paginacion">
                        <?php
                        $pagina_base = remove_query_arg('em_page');

                        if ($pagina_actual > 1): ?>
                            <a href="<?php echo esc_url(add_query_arg('em_page', $pagina_actual - 1, $pagina_base)); ?>"
                               class="em-btn em-btn-secundario">
                                <?php esc_html_e('Anterior', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>

                        <span class="em-paginacion-info">
                            <?php
                            printf(
                                esc_html__('Página %1$d de %2$d', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                $pagina_actual,
                                $historial['total_paginas']
                            );
                            ?>
                        </span>

                        <?php if ($pagina_actual < $historial['total_paginas']): ?>
                            <a href="<?php echo esc_url(add_query_arg('em_page', $pagina_actual + 1, $pagina_base)); ?>"
                               class="em-btn em-btn-secundario">
                                <?php esc_html_e('Siguiente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener historial de emails del usuario
     *
     * @param int $pagina Número de página
     * @param int $por_pagina Items por página
     * @return array
     */
    private function obtener_historial_emails($pagina = 1, $por_pagina = 10) {
        $suscriptor = $this->get_suscriptor_actual();

        if (!$suscriptor) {
            return [
                'items' => [],
                'total' => 0,
                'total_paginas' => 0,
            ];
        }

        global $wpdb;
        $tabla_cola = $wpdb->prefix . self::TABLE_PREFIX . 'cola';
        $tabla_campanias = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';
        $tabla_tracking = $wpdb->prefix . self::TABLE_PREFIX . 'tracking';

        $offset = ($pagina - 1) * $por_pagina;

        // Contar total
        $total = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_cola WHERE suscriptor_id = %d AND estado = 'enviado'",
            $suscriptor->id
        ));

        // Obtener emails con información de campaña y tracking
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT
                c.id,
                c.asunto,
                c.enviado_en,
                c.campania_id,
                camp.nombre as campania_nombre,
                (SELECT COUNT(*) FROM $tabla_tracking t WHERE t.suscriptor_id = c.suscriptor_id AND t.campania_id = c.campania_id AND t.tipo = 'abierto') as abierto,
                (SELECT COUNT(*) FROM $tabla_tracking t WHERE t.suscriptor_id = c.suscriptor_id AND t.campania_id = c.campania_id AND t.tipo = 'click') as clicks
             FROM $tabla_cola c
             LEFT JOIN $tabla_campanias camp ON c.campania_id = camp.id
             WHERE c.suscriptor_id = %d AND c.estado = 'enviado'
             ORDER BY c.enviado_en DESC
             LIMIT %d OFFSET %d",
            $suscriptor->id,
            $por_pagina,
            $offset
        ));

        return [
            'items' => $items,
            'total' => $total,
            'total_paginas' => ceil($total / $por_pagina),
        ];
    }

    /**
     * Obtener campañas en las que el usuario ha recibido emails
     *
     * @return array
     */
    private function obtener_campanias_usuario() {
        $suscriptor = $this->get_suscriptor_actual();

        if (!$suscriptor) {
            return [];
        }

        global $wpdb;
        $tabla_cola = $wpdb->prefix . self::TABLE_PREFIX . 'cola';
        $tabla_campanias = $wpdb->prefix . self::TABLE_PREFIX . 'campanias';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT camp.id, camp.nombre
             FROM $tabla_cola c
             INNER JOIN $tabla_campanias camp ON c.campania_id = camp.id
             WHERE c.suscriptor_id = %d AND c.estado = 'enviado'
             ORDER BY camp.nombre ASC",
            $suscriptor->id
        ));
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Toggle suscripción a una lista
     */
    public function ajax_toggle_suscripcion() {
        check_ajax_referer('flavor_em_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $lista_id = isset($_POST['lista_id']) ? intval($_POST['lista_id']) : 0;
        $accion = isset($_POST['accion']) ? sanitize_text_field($_POST['accion']) : 'toggle';

        if (!$lista_id) {
            wp_send_json_error(['message' => __('Lista no válida.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $suscriptor = $this->crear_suscriptor_si_no_existe();

        if (!$suscriptor) {
            wp_send_json_error(['message' => __('Error al crear suscriptor.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';

        // Verificar que la lista existe y es pública
        $lista = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_listas WHERE id = %d AND activa = 1 AND publica = 1",
            $lista_id
        ));

        if (!$lista) {
            wp_send_json_error(['message' => __('Lista no disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Verificar estado actual
        $relacion_actual = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_relacion WHERE suscriptor_id = %d AND lista_id = %d",
            $suscriptor->id,
            $lista_id
        ));

        if ($relacion_actual) {
            // Toggle estado
            $nuevo_estado = ($relacion_actual->estado === 'activo') ? 'baja' : 'activo';

            $wpdb->update(
                $tabla_relacion,
                [
                    'estado' => $nuevo_estado,
                    'fecha_baja' => ($nuevo_estado === 'baja') ? current_time('mysql') : null,
                ],
                [
                    'suscriptor_id' => $suscriptor->id,
                    'lista_id' => $lista_id,
                ]
            );

            // Actualizar contador de lista
            $this->actualizar_contador_lista($lista_id);

            wp_send_json_success([
                'estado' => $nuevo_estado,
                'message' => ($nuevo_estado === 'activo')
                    ? __('Suscrito correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN)
                    : __('Dado de baja correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        } else {
            // Crear nueva suscripción
            $wpdb->insert($tabla_relacion, [
                'suscriptor_id' => $suscriptor->id,
                'lista_id' => $lista_id,
                'estado' => 'activo',
                'fecha_suscripcion' => current_time('mysql'),
            ]);

            // Actualizar contador de lista
            $this->actualizar_contador_lista($lista_id);

            wp_send_json_success([
                'estado' => 'activo',
                'message' => __('Suscrito correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ]);
        }
    }

    /**
     * Actualizar contador de suscriptores de una lista
     *
     * @param int $lista_id
     */
    private function actualizar_contador_lista($lista_id) {
        global $wpdb;
        $tabla_listas = $wpdb->prefix . self::TABLE_PREFIX . 'listas';
        $tabla_relacion = $wpdb->prefix . self::TABLE_PREFIX . 'suscriptor_lista';

        $total_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_relacion WHERE lista_id = %d AND estado = 'activo'",
            $lista_id
        ));

        $wpdb->update(
            $tabla_listas,
            ['total_suscriptores' => $total_activos],
            ['id' => $lista_id]
        );
    }

    /**
     * AJAX: Guardar preferencias de email
     */
    public function ajax_guardar_preferencias() {
        check_ajax_referer('flavor_em_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $usuario = $this->get_usuario_actual();

        $frecuencia = isset($_POST['frecuencia']) ? sanitize_text_field($_POST['frecuencia']) : 'tiempo_real';
        $tipos = isset($_POST['tipos']) && is_array($_POST['tipos'])
            ? array_map('sanitize_text_field', $_POST['tipos'])
            : ['novedades'];
        $formato = isset($_POST['formato']) ? sanitize_text_field($_POST['formato']) : 'html';

        // Validar frecuencia
        $frecuencias_validas = ['tiempo_real', 'diario', 'semanal', 'mensual'];
        if (!in_array($frecuencia, $frecuencias_validas, true)) {
            $frecuencia = 'tiempo_real';
        }

        // Validar tipos
        $tipos_validos = ['novedades', 'promociones', 'eventos', 'comunidad', 'transaccional'];
        $tipos = array_intersect($tipos, $tipos_validos);

        // Validar formato
        $formatos_validos = ['html', 'texto'];
        if (!in_array($formato, $formatos_validos, true)) {
            $formato = 'html';
        }

        $preferencias = [
            'frecuencia' => $frecuencia,
            'tipos' => $tipos,
            'formato' => $formato,
        ];

        update_user_meta($usuario->ID, 'flavor_em_preferencias', $preferencias);

        wp_send_json_success([
            'message' => __('Preferencias guardadas correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'preferencias' => $preferencias,
        ]);
    }

    /**
     * AJAX: Marcar email como leído
     */
    public function ajax_marcar_email_leido() {
        check_ajax_referer('flavor_em_dashboard', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $email_id = isset($_POST['email_id']) ? intval($_POST['email_id']) : 0;

        if (!$email_id) {
            wp_send_json_error(['message' => __('Email no válido.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $suscriptor = $this->get_suscriptor_actual();

        if (!$suscriptor) {
            wp_send_json_error(['message' => __('Suscriptor no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        global $wpdb;
        $tabla_cola = $wpdb->prefix . self::TABLE_PREFIX . 'cola';
        $tabla_tracking = $wpdb->prefix . self::TABLE_PREFIX . 'tracking';

        // Verificar que el email pertenece al usuario
        $email = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_cola WHERE id = %d AND suscriptor_id = %d",
            $email_id,
            $suscriptor->id
        ));

        if (!$email) {
            wp_send_json_error(['message' => __('Email no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Registrar apertura manual si no existe
        $ya_abierto = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_tracking
             WHERE suscriptor_id = %d AND campania_id = %d AND tipo = 'abierto'",
            $suscriptor->id,
            $email->campania_id
        ));

        if (!$ya_abierto) {
            $wpdb->insert($tabla_tracking, [
                'campania_id' => $email->campania_id,
                'suscriptor_id' => $suscriptor->id,
                'tipo' => 'abierto',
                'ip' => $this->get_client_ip(),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'fecha' => current_time('mysql'),
            ]);

            // Actualizar estadísticas del suscriptor
            $wpdb->query($wpdb->prepare(
                "UPDATE {$wpdb->prefix}" . self::TABLE_PREFIX . "suscriptores
                 SET total_abiertos = total_abiertos + 1, ultima_apertura = %s
                 WHERE id = %d",
                current_time('mysql'),
                $suscriptor->id
            ));
        }

        wp_send_json_success([
            'message' => __('Email marcado como leído.', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ]);
    }

    /**
     * Obtener IP del cliente
     *
     * @return string
     */
    private function get_client_ip() {
        $headers_ip = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];

        foreach ($headers_ip as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return sanitize_text_field($ip);
                }
            }
        }

        return '127.0.0.1';
    }
}

// Inicializar la clase
Flavor_EM_Dashboard_Tab::get_instance();
