<?php
/**
 * Sistema de Ayuda para Dashboards de Módulos
 *
 * Proporciona ayuda contextual, tooltips y secciones informativas
 * para los dashboards de módulos de forma sistemática.
 *
 * @package FlavorChatIA
 * @since 3.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la ayuda en dashboards
 */
class Flavor_Dashboard_Help {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Configuraciones de ayuda por módulo
     */
    private $module_help = [];

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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_dismiss_help', [$this, 'ajax_dismiss_help']);
        add_action('wp_ajax_flavor_restore_help', [$this, 'ajax_restore_help']);

        $this->register_default_help();
    }

    /**
     * AJAX: Ocultar ayuda de un módulo
     */
    public function ajax_dismiss_help() {
        check_ajax_referer('flavor_dashboard_help', 'nonce');

        $module_id = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';

        if (empty($module_id)) {
            wp_send_json_error(['message' => 'Module ID required']);
        }

        update_user_meta(get_current_user_id(), 'flavor_help_dismissed_' . $module_id, 1);

        wp_send_json_success(['module' => $module_id]);
    }

    /**
     * AJAX: Restaurar ayuda de un módulo
     */
    public function ajax_restore_help() {
        check_ajax_referer('flavor_dashboard_help', 'nonce');

        $module_id = isset($_POST['module']) ? sanitize_key($_POST['module']) : '';

        if (empty($module_id)) {
            wp_send_json_error(['message' => 'Module ID required']);
        }

        delete_user_meta(get_current_user_id(), 'flavor_help_dismissed_' . $module_id);

        wp_send_json_success(['module' => $module_id]);
    }

    /**
     * Registrar ayuda para un módulo
     *
     * @param string $module_id ID del módulo
     * @param array $config Configuración de ayuda
     */
    public function register_help($module_id, $config) {
        $this->module_help[$module_id] = wp_parse_args($config, [
            'title'       => __('Ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => '',
            'tips'        => [],
            'steps'       => [],
            'links'       => [],
            'video_url'   => '',
            'tooltips'    => [],
        ]);
    }

    /**
     * Obtener configuración de ayuda de un módulo
     *
     * @param string $module_id ID del módulo
     * @return array|null
     */
    public function get_help($module_id) {
        return $this->module_help[$module_id] ?? null;
    }

    /**
     * Renderizar sección de ayuda colapsable
     *
     * @param string $module_id ID del módulo
     * @param array $options Opciones de renderizado
     */
    public function render_help_section($module_id, $options = []) {
        $help = $this->get_help($module_id);

        if (!$help) {
            return;
        }

        $options = wp_parse_args($options, [
            'collapsed'    => true,
            'show_tips'    => true,
            'show_steps'   => true,
            'show_links'   => true,
            'show_video'   => true,
            'context'      => 'admin', // admin o frontend
        ]);

        $collapsed_class = $options['collapsed'] ? 'dm-help--collapsed' : '';
        $user_dismissed = get_user_meta(get_current_user_id(), 'flavor_help_dismissed_' . $module_id, true);

        if ($user_dismissed && $options['collapsed']) {
            $collapsed_class = 'dm-help--dismissed';
        }
        ?>
        <div class="dm-help <?php echo esc_attr($collapsed_class); ?>" data-module="<?php echo esc_attr($module_id); ?>">
            <div class="dm-help__header" role="button" tabindex="0" aria-expanded="<?php echo $options['collapsed'] ? 'false' : 'true'; ?>">
                <div class="dm-help__header-content">
                    <span class="dashicons dashicons-editor-help dm-help__icon"></span>
                    <span class="dm-help__title"><?php echo esc_html($help['title']); ?></span>
                </div>
                <div class="dm-help__header-actions">
                    <button type="button" class="dm-help__toggle" aria-label="<?php esc_attr_e('Expandir/colapsar ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <button type="button" class="dm-help__dismiss" aria-label="<?php esc_attr_e('Ocultar ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" title="<?php esc_attr_e('No volver a mostrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
                        <span class="dashicons dashicons-no-alt"></span>
                    </button>
                </div>
            </div>

            <div class="dm-help__content">
                <?php if (!empty($help['description'])): ?>
                <div class="dm-help__description">
                    <?php echo wp_kses_post($help['description']); ?>
                </div>
                <?php endif; ?>

                <div class="dm-help__grid">
                    <?php if ($options['show_tips'] && !empty($help['tips'])): ?>
                    <div class="dm-help__section dm-help__tips">
                        <h4 class="dm-help__section-title">
                            <span class="dashicons dashicons-lightbulb"></span>
                            <?php esc_html_e('Consejos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h4>
                        <ul class="dm-help__list">
                            <?php foreach ($help['tips'] as $tip): ?>
                            <li><?php echo wp_kses_post($tip); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($options['show_steps'] && !empty($help['steps'])): ?>
                    <div class="dm-help__section dm-help__steps">
                        <h4 class="dm-help__section-title">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php esc_html_e('Primeros pasos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h4>
                        <ol class="dm-help__list dm-help__list--numbered">
                            <?php foreach ($help['steps'] as $step): ?>
                            <li><?php echo wp_kses_post($step); ?></li>
                            <?php endforeach; ?>
                        </ol>
                    </div>
                    <?php endif; ?>

                    <?php if ($options['show_links'] && !empty($help['links'])): ?>
                    <div class="dm-help__section dm-help__links">
                        <h4 class="dm-help__section-title">
                            <span class="dashicons dashicons-admin-links"></span>
                            <?php esc_html_e('Enlaces útiles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </h4>
                        <ul class="dm-help__list dm-help__list--links">
                            <?php foreach ($help['links'] as $link): ?>
                            <li>
                                <a href="<?php echo esc_url($link['url']); ?>"
                                   target="<?php echo isset($link['external']) && $link['external'] ? '_blank' : '_self'; ?>"
                                   <?php echo isset($link['external']) && $link['external'] ? 'rel="noopener noreferrer"' : ''; ?>>
                                    <?php if (!empty($link['icon'])): ?>
                                    <span class="dashicons <?php echo esc_attr($link['icon']); ?>"></span>
                                    <?php endif; ?>
                                    <?php echo esc_html($link['text']); ?>
                                    <?php if (isset($link['external']) && $link['external']): ?>
                                    <span class="dashicons dashicons-external" style="font-size: 14px;"></span>
                                    <?php endif; ?>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($options['show_video'] && !empty($help['video_url'])): ?>
                <div class="dm-help__video">
                    <button type="button" class="dm-btn dm-btn--secondary dm-help__video-btn" data-video="<?php echo esc_url($help['video_url']); ?>">
                        <span class="dashicons dashicons-video-alt3"></span>
                        <?php esc_html_e('Ver tutorial en vídeo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
                <?php endif; ?>

                <div class="dm-help__footer">
                    <button type="button" class="dm-help__restore-btn" style="display: none;">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Mostrar ayuda de nuevo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Renderizar tooltip helper
     *
     * @param string $content Contenido del tooltip
     * @param array $options Opciones
     * @return string HTML del icono con tooltip
     */
    public function tooltip($content, $options = []) {
        $options = wp_parse_args($options, [
            'position' => 'top',
            'icon'     => 'dashicons-editor-help',
            'class'    => '',
        ]);

        return sprintf(
            '<span class="dm-tooltip %s" data-tooltip="%s" data-position="%s">
                <span class="dashicons %s"></span>
            </span>',
            esc_attr($options['class']),
            esc_attr($content),
            esc_attr($options['position']),
            esc_attr($options['icon'])
        );
    }

    /**
     * Renderizar stat card con tooltip de ayuda
     *
     * @param array $config Configuración de la stat card
     */
    public function render_stat_with_help($config) {
        $config = wp_parse_args($config, [
            'value'    => 0,
            'label'    => '',
            'icon'     => 'dashicons-chart-bar',
            'variant'  => '',
            'help'     => '',
            'help_pos' => 'top',
        ]);

        $variant_class = $config['variant'] ? 'dm-stat-card--' . $config['variant'] : '';
        ?>
        <div class="dm-stat-card <?php echo esc_attr($variant_class); ?>">
            <div class="dm-stat-card__icon">
                <span class="dashicons <?php echo esc_attr($config['icon']); ?>"></span>
            </div>
            <div class="dm-stat-card__content">
                <div class="dm-stat-card__value"><?php echo esc_html($config['value']); ?></div>
                <div class="dm-stat-card__label">
                    <?php echo esc_html($config['label']); ?>
                    <?php if (!empty($config['help'])): ?>
                    <?php echo $this->tooltip($config['help'], ['position' => $config['help_pos']]); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets($hook = '') {
        // Solo cargar en páginas de admin relevantes o frontend con dashboards
        if (is_admin()) {
            // Lista de prefijos de páginas donde cargar los estilos de ayuda
            $allowed_prefixes = [
                'flavor',
                'admin_page_',
                'toplevel_page_',
                // Módulos específicos
                'gc-', 'grupos-consumo',
                'eventos', 'cursos', 'talleres',
                'marketplace', 'banco-tiempo',
                'socios', 'reservas', 'biblioteca',
                'incidencias', 'participacion',
                'comunidades', 'colectivos',
                'red-social', 'chat-',
                'huertos', 'bicicletas',
                'carpooling', 'avisos',
                'tramites', 'transparencia',
                'compostaje', 'reciclaje',
                'economia-don', 'ayuda-vecinal',
                'espacios', 'multimedia', 'radio',
                'foros', 'biodiversidad',
            ];

            $is_allowed = false;
            foreach ($allowed_prefixes as $prefix) {
                if (strpos($hook, $prefix) !== false) {
                    $is_allowed = true;
                    break;
                }
            }

            // También verificar el parámetro page de la URL
            if (!$is_allowed && isset($_GET['page'])) {
                $current_page = sanitize_text_field($_GET['page']);
                foreach ($allowed_prefixes as $prefix) {
                    if (strpos($current_page, $prefix) !== false) {
                        $is_allowed = true;
                        break;
                    }
                }
            }

            if (!$is_allowed) {
                return;
            }
        }

        wp_enqueue_style(
            'flavor-dashboard-help',
            FLAVOR_CHAT_IA_URL . 'assets/css/layouts/dashboard-help.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-dashboard-help',
            FLAVOR_CHAT_IA_URL . 'assets/js/dashboard-help.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-dashboard-help', 'flavorDashboardHelp', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('flavor_dashboard_help'),
            'i18n'    => [
                'collapse'     => __('Colapsar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'expand'       => __('Expandir', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'dismissed'    => __('Ayuda ocultada. Puedes restaurarla desde el menú.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'videoTitle'   => __('Tutorial', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Registrar ayudas por defecto para módulos comunes
     */
    private function register_default_help() {
        // Grupos de Consumo
        $this->register_help('grupos_consumo', [
            'title'       => __('Cómo usar Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestiona pedidos colectivos, productores locales y ciclos de compra para tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Crea un <strong>ciclo de pedidos</strong> para organizar cada ronda de compras.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>productores</strong> pueden gestionar sus propios productos desde su panel.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Usa las <strong>estadísticas</strong> para ver qué productos son más populares.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra tus <strong>productores</strong> locales con sus datos de contacto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Añade los <strong>productos</strong> disponibles de cada productor.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Crea un <strong>ciclo</strong> con fechas de apertura y cierre de pedidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Comparte el enlace de pedidos con los miembros del grupo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Documentación completa', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=flavor-platform-docs#grupos-consumo'), 'icon' => 'dashicons-book'],
                ['text' => __('Configurar notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=gc-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Eventos
        $this->register_help('eventos', [
            'title'       => __('Cómo usar Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Organiza eventos comunitarios, gestiona inscripciones y envía recordatorios automáticos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Activa los <strong>recordatorios automáticos</strong> para reducir ausencias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Usa <strong>categorías</strong> para organizar diferentes tipos de eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El <strong>calendario</strong> público permite a los usuarios ver todos los eventos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea un nuevo evento con fecha, hora y ubicación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura el aforo máximo si hay límite de plazas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Publica el evento para que aparezca en el calendario.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios podrán inscribirse desde el frontend.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver calendario', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=eventos-calendario'), 'icon' => 'dashicons-calendar-alt'],
            ],
        ]);

        // Reservas
        $this->register_help('reservas', [
            'title'       => __('Cómo usar Reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Sistema de reserva de recursos compartidos: salas, equipos, vehículos y más.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Define <strong>horarios de disponibilidad</strong> para cada recurso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>reglas de uso</strong> como duración máxima o anticipación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>conflictos</strong> se detectan automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra los recursos disponibles (salas, equipos, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura los horarios en que cada recurso está disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Define quién puede reservar cada recurso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Gestionar recursos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=reservas-recursos'), 'icon' => 'dashicons-admin-tools'],
            ],
        ]);

        // Marketplace
        $this->register_help('marketplace', [
            'title'       => __('Cómo usar el Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Mercado local para compra-venta entre miembros de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los anuncios pueden ser de <strong>venta, intercambio o regalo</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Activa la <strong>moderación</strong> para revisar anuncios antes de publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las <strong>categorías</strong> ayudan a los usuarios a encontrar lo que buscan.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Configura las categorías de productos disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Define las políticas de publicación (moderación, duración).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios publican sus anuncios desde el frontend.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=marketplace-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Banco de Tiempo
        $this->register_help('banco_tiempo', [
            'title'       => __('Cómo usar el Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Intercambia horas de servicio entre miembros. Una hora dada = una hora recibida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Cada persona empieza con un <strong>saldo inicial</strong> configurable.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>servicios</strong> se intercambian por tiempo, no por dinero.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El <strong>fondo solidario</strong> permite ayudar a quien más lo necesita.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Los usuarios registran los servicios que pueden ofrecer.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Cuando alguien necesita un servicio, contacta al oferente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Al completar el intercambio, se registran las horas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=bt-intercambios'), 'icon' => 'dashicons-update'],
            ],
        ]);

        // Participación
        $this->register_help('participacion', [
            'title'       => __('Cómo usar Participación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Herramientas de democracia participativa: propuestas, votaciones y debates.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Las <strong>propuestas</strong> pasan por fases: borrador, debate, votación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>quórums</strong> para que las votaciones sean válidas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>comentarios</strong> permiten debatir antes de votar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea categorías para organizar las propuestas por tema.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Define las reglas de votación (mayoría simple, absoluta, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios crean propuestas desde el frontend.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=participacion-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Foros
        $this->register_help('foros', [
            'title'       => __('Cómo usar Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Espacio de discusión organizado por categorías y temas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Crea <strong>categorías</strong> para organizar las discusiones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>moderadores</strong> pueden fijar y cerrar temas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Activa <strong>notificaciones</strong> para seguir temas de interés.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea las categorías principales del foro.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Asigna moderadores para cada categoría.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios crean temas y participan en discusiones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Gestionar categorías', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('edit-tags.php?taxonomy=foro_categoria'), 'icon' => 'dashicons-category'],
            ],
        ]);

        // Socios
        $this->register_help('socios', [
            'title'       => __('Cómo usar Gestión de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Administra la membresía: altas, bajas, cuotas y carnets.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Configura <strong>tipos de miembro</strong> con diferentes cuotas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las <strong>cuotas</strong> se pueden generar automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Exporta el <strong>listado de miembros</strong> en Excel o CSV.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Define los tipos de membresía disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura las cuotas y periodicidad de pago.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los nuevos miembros se dan de alta desde el formulario.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar tipos de miembro', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=socios-tipos'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Red Social
        $this->register_help('red_social', [
            'title'       => __('Cómo usar la Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Red social interna para tu comunidad: perfiles, publicaciones, seguimientos y actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los usuarios pueden <strong>seguirse</strong> entre sí para ver su actividad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las <strong>publicaciones</strong> pueden incluir texto, imágenes y enlaces.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura la <strong>moderación</strong> para mantener un ambiente saludable.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>me gusta</strong> y comentarios fomentan la interacción.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Configura qué tipos de contenido se pueden publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Define las reglas de moderación y reportes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios completan su perfil y empiezan a publicar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar red social', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=red-social-settings'), 'icon' => 'dashicons-admin-settings'],
                ['text' => __('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=red-social-moderacion'), 'icon' => 'dashicons-shield'],
            ],
        ]);

        // Chat Interno
        $this->register_help('chat_interno', [
            'title'       => __('Cómo usar el Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Mensajería privada entre miembros de la comunidad en tiempo real.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los mensajes son <strong>privados</strong> entre los participantes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Se pueden enviar <strong>archivos adjuntos</strong> en las conversaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las <strong>notificaciones</strong> avisan de nuevos mensajes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Los usuarios inician conversaciones desde el perfil de otro miembro.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los mensajes se envían en tiempo real.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El historial queda guardado para futuras consultas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar chat', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=chat-interno-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Chat Grupos
        $this->register_help('chat_grupos', [
            'title'       => __('Cómo usar el Chat de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Salas de chat grupales para conversaciones temáticas o por equipos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Crea <strong>salas temáticas</strong> para diferentes grupos o temas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>administradores</strong> de sala pueden moderar mensajes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las salas pueden ser <strong>públicas o privadas</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea las salas de chat necesarias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Asigna administradores a cada sala.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios se unen a las salas que les interesan.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Gestionar salas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=chat-grupos-salas'), 'icon' => 'dashicons-groups'],
            ],
        ]);

        // Comunidades
        $this->register_help('comunidades', [
            'title'       => __('Cómo usar Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Espacios independientes dentro de la plataforma con sus propios miembros y contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Cada comunidad tiene su propio <strong>espacio y configuración</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>administradores</strong> de comunidad gestionan miembros y contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Las comunidades pueden ser <strong>abiertas o por invitación</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los miembros pueden pertenecer a <strong>múltiples comunidades</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea una nueva comunidad con nombre y descripción.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura la privacidad y reglas de acceso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Invita a los primeros miembros o hazla pública.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Crear comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-nueva'), 'icon' => 'dashicons-plus-alt'],
                ['text' => __('Ver todas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=comunidades-listado'), 'icon' => 'dashicons-networking'],
            ],
        ]);

        // Colectivos
        $this->register_help('colectivos', [
            'title'       => __('Cómo usar Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Grupos de trabajo o interés dentro de la comunidad con objetivos comunes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los colectivos pueden tener <strong>proyectos propios</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Cada colectivo tiene su <strong>espacio de comunicación</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los miembros pueden tener <strong>roles diferentes</strong> (coordinador, miembro, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea un colectivo con su propósito y objetivos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Invita a personas interesadas a unirse.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Organiza tareas y proyectos del colectivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Crear colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=colectivos-nuevo'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Multimedia
        $this->register_help('multimedia', [
            'title'       => __('Cómo usar Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión de contenido multimedia: galerías, vídeos, podcasts y documentos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Organiza el contenido en <strong>álbumes y colecciones</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios pueden <strong>subir su propio contenido</strong> si está habilitado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>límites de tamaño</strong> y tipos de archivo permitidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea las categorías y álbumes para organizar el contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Sube contenido multimedia o permite que los usuarios lo hagan.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Modera el contenido subido por los usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Subir contenido', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=multimedia-subir'), 'icon' => 'dashicons-upload'],
                ['text' => __('Galería', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=multimedia-galeria'), 'icon' => 'dashicons-format-gallery'],
            ],
        ]);

        // Radio
        $this->register_help('radio', [
            'title'       => __('Cómo usar Radio Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Emisora de radio online con programación, podcasts y emisión en directo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Configura la <strong>parrilla de programación</strong> semanal.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>podcasts</strong> quedan disponibles para escuchar bajo demanda.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('La <strong>emisión en directo</strong> permite retransmitir eventos en vivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Configura la URL del stream de audio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Crea los programas y asigna horarios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Sube podcasts de los programas emitidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Configurar radio', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=radio-settings'), 'icon' => 'dashicons-admin-settings'],
                ['text' => __('Programación', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=radio-programacion'), 'icon' => 'dashicons-calendar-alt'],
            ],
        ]);

        // Huertos Urbanos
        $this->register_help('huertos_urbanos', [
            'title'       => __('Cómo usar Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión de parcelas, cosechas y recursos para huertos comunitarios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Asigna <strong>parcelas</strong> a los hortelanos registrados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Registra las <strong>cosechas</strong> para llevar estadísticas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Comparte <strong>recursos</strong> como herramientas y semillas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra las parcelas disponibles en el huerto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Asigna parcelas a los usuarios interesados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los hortelanos registran sus cosechas periódicamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver parcelas', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=huertos-parcelas'), 'icon' => 'dashicons-grid-view'],
            ],
        ]);

        // Incidencias
        $this->register_help('incidencias', [
            'title'       => __('Cómo usar Incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Sistema de reporte y seguimiento de incidencias y problemas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Las incidencias tienen <strong>estados</strong>: abierta, en proceso, resuelta.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Asigna <strong>responsables</strong> para cada tipo de incidencia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios reciben <strong>notificaciones</strong> del progreso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Configura las categorías de incidencias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios reportan incidencias desde el frontend.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Gestiona y resuelve las incidencias reportadas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver incidencias', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=incidencias-listado'), 'icon' => 'dashicons-warning'],
            ],
        ]);

        // Talleres
        $this->register_help('talleres', [
            'title'       => __('Cómo usar Talleres', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Organiza talleres formativos con inscripciones, materiales y seguimiento.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Define los <strong>materiales necesarios</strong> para cada taller.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura el <strong>aforo máximo</strong> y lista de espera.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>facilitadores</strong> pueden gestionar sus propios talleres.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea un taller con descripción, fecha y lugar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Añade los materiales o requisitos previos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Abre las inscripciones para los participantes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Crear taller', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('post-new.php?post_type=taller'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Cursos
        $this->register_help('cursos', [
            'title'       => __('Cómo usar Cursos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Plataforma de aprendizaje con cursos, lecciones y seguimiento de progreso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Organiza el contenido en <strong>módulos y lecciones</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Añade <strong>cuestionarios</strong> para evaluar el aprendizaje.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios ven su <strong>progreso</strong> en cada curso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea un curso con su estructura de módulos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Añade las lecciones con contenido multimedia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Publica el curso para que los usuarios se inscriban.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Crear curso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('post-new.php?post_type=curso'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Biblioteca
        $this->register_help('biblioteca', [
            'title'       => __('Cómo usar la Biblioteca', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión de préstamos de libros, revistas y otros materiales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Configura el <strong>período de préstamo</strong> por tipo de material.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios pueden hacer <strong>reservas</strong> de materiales prestados.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Envía <strong>recordatorios</strong> automáticos de devolución.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra el catálogo de materiales disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura las políticas de préstamo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios solicitan préstamos desde el catálogo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver catálogo', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=biblioteca-catalogo'), 'icon' => 'dashicons-book'],
            ],
        ]);

        // Bicicletas Compartidas
        $this->register_help('bicicletas_compartidas', [
            'title'       => __('Cómo usar Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Sistema de préstamo de bicicletas con estaciones y seguimiento.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Registra las <strong>bicicletas</strong> y su estado actual.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura las <strong>estaciones</strong> de recogida y devolución.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios reservan bicicletas desde su móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra las estaciones con su ubicación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Añade las bicicletas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios pueden reservar y devolver bicicletas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver estaciones', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=bicicletas-estaciones'), 'icon' => 'dashicons-location'],
            ],
        ]);

        // Carpooling
        $this->register_help('carpooling', [
            'title'       => __('Cómo usar Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Comparte viajes en coche para reducir costes y emisiones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los <strong>conductores</strong> publican sus rutas disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>pasajeros</strong> buscan viajes por origen y destino.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El sistema calcula una <strong>aportación sugerida</strong> por los gastos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Los conductores registran sus viajes habituales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los pasajeros buscan y solicitan plaza.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El conductor acepta o rechaza las solicitudes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Publicar viaje', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => home_url('/mi-portal/carpooling/publicar/'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Avisos Municipales
        $this->register_help('avisos_municipales', [
            'title'       => __('Cómo usar Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Publica avisos y comunicados para la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los avisos pueden ser <strong>urgentes</strong> para mayor visibilidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>notificaciones</strong> push para avisos importantes.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los avisos antiguos se archivan automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea un nuevo aviso con título y contenido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Selecciona el tipo y urgencia del aviso.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Publica para que aparezca en el tablón.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Crear aviso', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=avisos-nuevo'), 'icon' => 'dashicons-megaphone'],
            ],
        ]);

        // Tramites
        $this->register_help('tramites', [
            'title'       => __('Cómo usar Trámites', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión de trámites administrativos y expedientes online.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Define los <strong>tipos de trámite</strong> y sus requisitos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios siguen el <strong>estado de sus expedientes</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>plazos</strong> y alertas automáticas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Crea los tipos de trámite disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Define los documentos requeridos para cada uno.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios inician trámites desde el portal.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver expedientes', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=tramites-expedientes'), 'icon' => 'dashicons-portfolio'],
            ],
        ]);

        // Transparencia
        $this->register_help('transparencia', [
            'title'       => __('Cómo usar Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Portal de transparencia con documentos, presupuestos y rendición de cuentas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Publica <strong>documentos</strong> organizados por categorías.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>presupuestos</strong> se visualizan con gráficos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Mantén un <strong>histórico</strong> de documentos anteriores.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Configura las categorías de documentos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Sube los documentos de transparencia.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios pueden consultar y descargar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Subir documento', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=transparencia-subir'), 'icon' => 'dashicons-upload'],
            ],
        ]);

        // Compostaje
        $this->register_help('compostaje', [
            'title'       => __('Cómo usar Compostaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión de composteras comunitarias y seguimiento de aportaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Registra las <strong>aportaciones</strong> de cada participante.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Lleva el <strong>control de producción</strong> de compost.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Organiza <strong>turnos de mantenimiento</strong> de las composteras.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra las composteras disponibles.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los participantes registran sus aportaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Distribuye el compost producido.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver composteras', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=compostaje-composteras'), 'icon' => 'dashicons-carrot'],
            ],
        ]);

        // Economía del Don
        $this->register_help('economia_don', [
            'title'       => __('Cómo usar Economía del Don', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Espacio para ofrecer y recibir objetos, servicios y habilidades de forma gratuita.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los <strong>dones</strong> se ofrecen sin esperar nada a cambio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Fomenta la <strong>abundancia</strong> y el compartir en comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Cualquier persona puede ofrecer o solicitar dones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Publica lo que quieres ofrecer o necesitas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Conecta con quien puede ayudarte o necesita ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Realiza el intercambio y agradece.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ofrecer don', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('post-new.php?post_type=ed_don'), 'icon' => 'dashicons-heart'],
            ],
        ]);

        // Ayuda Vecinal
        $this->register_help('ayuda_vecinal', [
            'title'       => __('Cómo usar Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Red de apoyo mutuo entre vecinos para pequeñas ayudas cotidianas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Las solicitudes pueden ser <strong>urgentes</strong> para priorizar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los vecinos que ayudan reciben <strong>reconocimiento</strong>.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Fomenta la <strong>cohesión social</strong> del barrio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Publica una solicitud de ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los vecinos cercanos ven la solicitud.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Alguien se ofrece y coordinan la ayuda.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Nueva solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=ayuda-nueva-solicitud'), 'icon' => 'dashicons-sos'],
            ],
        ]);

        // Espacios Comunes
        $this->register_help('espacios_comunes', [
            'title'       => __('Cómo usar Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Gestión y reserva de espacios compartidos: salas, terrazas, cocinas comunitarias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Define los <strong>horarios de disponibilidad</strong> de cada espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura <strong>reglas de uso</strong> como duración máxima o anticipación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los <strong>conflictos de reserva</strong> se detectan automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra los espacios disponibles con su capacidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Configura los horarios y reglas de cada espacio.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios reservan desde el portal.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver espacios', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=espacios-listado'), 'icon' => 'dashicons-building'],
            ],
        ]);

        // Reciclaje
        $this->register_help('reciclaje', [
            'title'       => __('Cómo usar Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'description' => __('Sistema de puntos de reciclaje con gamificación y seguimiento de impacto ambiental.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tips'        => [
                __('Los usuarios ganan <strong>puntos</strong> por cada depósito de reciclaje.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('El <strong>impacto ambiental</strong> se calcula automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Monitoriza el <strong>estado de los contenedores</strong> en cada punto.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'steps'       => [
                __('Registra los puntos de reciclaje con su ubicación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Los usuarios depositan residuos y registran la aportación.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Consulta las estadísticas de impacto ambiental.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
            'links'       => [
                ['text' => __('Ver puntos', FLAVOR_PLATFORM_TEXT_DOMAIN), 'url' => admin_url('admin.php?page=reciclaje-puntos'), 'icon' => 'dashicons-admin-site'],
            ],
        ]);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Dashboard_Help::get_instance();
});

/**
 * Helper function para renderizar sección de ayuda
 *
 * @param string $module_id ID del módulo
 * @param array $options Opciones
 */
function flavor_dashboard_help($module_id, $options = []) {
    Flavor_Dashboard_Help::get_instance()->render_help_section($module_id, $options);
}

/**
 * Helper function para crear tooltip
 *
 * @param string $content Contenido
 * @param array $options Opciones
 * @return string HTML
 */
function flavor_tooltip($content, $options = []) {
    return Flavor_Dashboard_Help::get_instance()->tooltip($content, $options);
}

/**
 * Helper function para registrar ayuda de módulo
 *
 * @param string $module_id ID del módulo
 * @param array $config Configuración
 */
function flavor_register_module_help($module_id, $config) {
    Flavor_Dashboard_Help::get_instance()->register_help($module_id, $config);
}
