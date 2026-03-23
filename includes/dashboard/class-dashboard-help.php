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
            'title'       => __('Ayuda', 'flavor-chat-ia'),
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
                    <button type="button" class="dm-help__toggle" aria-label="<?php esc_attr_e('Expandir/colapsar ayuda', 'flavor-chat-ia'); ?>">
                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                    </button>
                    <button type="button" class="dm-help__dismiss" aria-label="<?php esc_attr_e('Ocultar ayuda', 'flavor-chat-ia'); ?>" title="<?php esc_attr_e('No volver a mostrar', 'flavor-chat-ia'); ?>">
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
                            <?php esc_html_e('Consejos', 'flavor-chat-ia'); ?>
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
                            <?php esc_html_e('Primeros pasos', 'flavor-chat-ia'); ?>
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
                            <?php esc_html_e('Enlaces útiles', 'flavor-chat-ia'); ?>
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
                        <?php esc_html_e('Ver tutorial en vídeo', 'flavor-chat-ia'); ?>
                    </button>
                </div>
                <?php endif; ?>

                <div class="dm-help__footer">
                    <button type="button" class="dm-help__restore-btn" style="display: none;">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Mostrar ayuda de nuevo', 'flavor-chat-ia'); ?>
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
                'collapse'     => __('Colapsar', 'flavor-chat-ia'),
                'expand'       => __('Expandir', 'flavor-chat-ia'),
                'dismissed'    => __('Ayuda ocultada. Puedes restaurarla desde el menú.', 'flavor-chat-ia'),
                'videoTitle'   => __('Tutorial', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Registrar ayudas por defecto para módulos comunes
     */
    private function register_default_help() {
        // Grupos de Consumo
        $this->register_help('grupos_consumo', [
            'title'       => __('Cómo usar Grupos de Consumo', 'flavor-chat-ia'),
            'description' => __('Gestiona pedidos colectivos, productores locales y ciclos de compra para tu comunidad.', 'flavor-chat-ia'),
            'tips'        => [
                __('Crea un <strong>ciclo de pedidos</strong> para organizar cada ronda de compras.', 'flavor-chat-ia'),
                __('Los <strong>productores</strong> pueden gestionar sus propios productos desde su panel.', 'flavor-chat-ia'),
                __('Usa las <strong>estadísticas</strong> para ver qué productos son más populares.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra tus <strong>productores</strong> locales con sus datos de contacto.', 'flavor-chat-ia'),
                __('Añade los <strong>productos</strong> disponibles de cada productor.', 'flavor-chat-ia'),
                __('Crea un <strong>ciclo</strong> con fechas de apertura y cierre de pedidos.', 'flavor-chat-ia'),
                __('Comparte el enlace de pedidos con los miembros del grupo.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Documentación completa', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=flavor-documentation#grupos-consumo'), 'icon' => 'dashicons-book'],
                ['text' => __('Configurar notificaciones', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=gc-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Eventos
        $this->register_help('eventos', [
            'title'       => __('Cómo usar Eventos', 'flavor-chat-ia'),
            'description' => __('Organiza eventos comunitarios, gestiona inscripciones y envía recordatorios automáticos.', 'flavor-chat-ia'),
            'tips'        => [
                __('Activa los <strong>recordatorios automáticos</strong> para reducir ausencias.', 'flavor-chat-ia'),
                __('Usa <strong>categorías</strong> para organizar diferentes tipos de eventos.', 'flavor-chat-ia'),
                __('El <strong>calendario</strong> público permite a los usuarios ver todos los eventos.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea un nuevo evento con fecha, hora y ubicación.', 'flavor-chat-ia'),
                __('Configura el aforo máximo si hay límite de plazas.', 'flavor-chat-ia'),
                __('Publica el evento para que aparezca en el calendario.', 'flavor-chat-ia'),
                __('Los usuarios podrán inscribirse desde el frontend.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver calendario', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=eventos-calendario'), 'icon' => 'dashicons-calendar-alt'],
            ],
        ]);

        // Reservas
        $this->register_help('reservas', [
            'title'       => __('Cómo usar Reservas', 'flavor-chat-ia'),
            'description' => __('Sistema de reserva de recursos compartidos: salas, equipos, vehículos y más.', 'flavor-chat-ia'),
            'tips'        => [
                __('Define <strong>horarios de disponibilidad</strong> para cada recurso.', 'flavor-chat-ia'),
                __('Configura <strong>reglas de uso</strong> como duración máxima o anticipación.', 'flavor-chat-ia'),
                __('Los <strong>conflictos</strong> se detectan automáticamente.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra los recursos disponibles (salas, equipos, etc.).', 'flavor-chat-ia'),
                __('Configura los horarios en que cada recurso está disponible.', 'flavor-chat-ia'),
                __('Define quién puede reservar cada recurso.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Gestionar recursos', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reservas-recursos'), 'icon' => 'dashicons-admin-tools'],
            ],
        ]);

        // Marketplace
        $this->register_help('marketplace', [
            'title'       => __('Cómo usar el Marketplace', 'flavor-chat-ia'),
            'description' => __('Mercado local para compra-venta entre miembros de la comunidad.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los anuncios pueden ser de <strong>venta, intercambio o regalo</strong>.', 'flavor-chat-ia'),
                __('Activa la <strong>moderación</strong> para revisar anuncios antes de publicar.', 'flavor-chat-ia'),
                __('Las <strong>categorías</strong> ayudan a los usuarios a encontrar lo que buscan.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Configura las categorías de productos disponibles.', 'flavor-chat-ia'),
                __('Define las políticas de publicación (moderación, duración).', 'flavor-chat-ia'),
                __('Los usuarios publican sus anuncios desde el frontend.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar marketplace', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=marketplace-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Banco de Tiempo
        $this->register_help('banco_tiempo', [
            'title'       => __('Cómo usar el Banco de Tiempo', 'flavor-chat-ia'),
            'description' => __('Intercambia horas de servicio entre miembros. Una hora dada = una hora recibida.', 'flavor-chat-ia'),
            'tips'        => [
                __('Cada persona empieza con un <strong>saldo inicial</strong> configurable.', 'flavor-chat-ia'),
                __('Los <strong>servicios</strong> se intercambian por tiempo, no por dinero.', 'flavor-chat-ia'),
                __('El <strong>fondo solidario</strong> permite ayudar a quien más lo necesita.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Los usuarios registran los servicios que pueden ofrecer.', 'flavor-chat-ia'),
                __('Cuando alguien necesita un servicio, contacta al oferente.', 'flavor-chat-ia'),
                __('Al completar el intercambio, se registran las horas.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver intercambios', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=bt-intercambios'), 'icon' => 'dashicons-update'],
            ],
        ]);

        // Participación
        $this->register_help('participacion', [
            'title'       => __('Cómo usar Participación', 'flavor-chat-ia'),
            'description' => __('Herramientas de democracia participativa: propuestas, votaciones y debates.', 'flavor-chat-ia'),
            'tips'        => [
                __('Las <strong>propuestas</strong> pasan por fases: borrador, debate, votación.', 'flavor-chat-ia'),
                __('Configura <strong>quórums</strong> para que las votaciones sean válidas.', 'flavor-chat-ia'),
                __('Los <strong>comentarios</strong> permiten debatir antes de votar.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea categorías para organizar las propuestas por tema.', 'flavor-chat-ia'),
                __('Define las reglas de votación (mayoría simple, absoluta, etc.).', 'flavor-chat-ia'),
                __('Los usuarios crean propuestas desde el frontend.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar votaciones', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=participacion-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Foros
        $this->register_help('foros', [
            'title'       => __('Cómo usar Foros', 'flavor-chat-ia'),
            'description' => __('Espacio de discusión organizado por categorías y temas.', 'flavor-chat-ia'),
            'tips'        => [
                __('Crea <strong>categorías</strong> para organizar las discusiones.', 'flavor-chat-ia'),
                __('Los <strong>moderadores</strong> pueden fijar y cerrar temas.', 'flavor-chat-ia'),
                __('Activa <strong>notificaciones</strong> para seguir temas de interés.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea las categorías principales del foro.', 'flavor-chat-ia'),
                __('Asigna moderadores para cada categoría.', 'flavor-chat-ia'),
                __('Los usuarios crean temas y participan en discusiones.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Gestionar categorías', 'flavor-chat-ia'), 'url' => admin_url('edit-tags.php?taxonomy=foro_categoria'), 'icon' => 'dashicons-category'],
            ],
        ]);

        // Socios
        $this->register_help('socios', [
            'title'       => __('Cómo usar Gestión de Miembros', 'flavor-chat-ia'),
            'description' => __('Administra la membresía: altas, bajas, cuotas y carnets.', 'flavor-chat-ia'),
            'tips'        => [
                __('Configura <strong>tipos de miembro</strong> con diferentes cuotas.', 'flavor-chat-ia'),
                __('Las <strong>cuotas</strong> se pueden generar automáticamente.', 'flavor-chat-ia'),
                __('Exporta el <strong>listado de miembros</strong> en Excel o CSV.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Define los tipos de membresía disponibles.', 'flavor-chat-ia'),
                __('Configura las cuotas y periodicidad de pago.', 'flavor-chat-ia'),
                __('Los nuevos miembros se dan de alta desde el formulario.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar tipos de miembro', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=socios-tipos'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Red Social
        $this->register_help('red_social', [
            'title'       => __('Cómo usar la Red Social', 'flavor-chat-ia'),
            'description' => __('Red social interna para tu comunidad: perfiles, publicaciones, seguimientos y actividad.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los usuarios pueden <strong>seguirse</strong> entre sí para ver su actividad.', 'flavor-chat-ia'),
                __('Las <strong>publicaciones</strong> pueden incluir texto, imágenes y enlaces.', 'flavor-chat-ia'),
                __('Configura la <strong>moderación</strong> para mantener un ambiente saludable.', 'flavor-chat-ia'),
                __('Los <strong>me gusta</strong> y comentarios fomentan la interacción.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Configura qué tipos de contenido se pueden publicar.', 'flavor-chat-ia'),
                __('Define las reglas de moderación y reportes.', 'flavor-chat-ia'),
                __('Los usuarios completan su perfil y empiezan a publicar.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar red social', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=red-social-settings'), 'icon' => 'dashicons-admin-settings'],
                ['text' => __('Moderación', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=red-social-moderacion'), 'icon' => 'dashicons-shield'],
            ],
        ]);

        // Chat Interno
        $this->register_help('chat_interno', [
            'title'       => __('Cómo usar el Chat Interno', 'flavor-chat-ia'),
            'description' => __('Mensajería privada entre miembros de la comunidad en tiempo real.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los mensajes son <strong>privados</strong> entre los participantes.', 'flavor-chat-ia'),
                __('Se pueden enviar <strong>archivos adjuntos</strong> en las conversaciones.', 'flavor-chat-ia'),
                __('Las <strong>notificaciones</strong> avisan de nuevos mensajes.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Los usuarios inician conversaciones desde el perfil de otro miembro.', 'flavor-chat-ia'),
                __('Los mensajes se envían en tiempo real.', 'flavor-chat-ia'),
                __('El historial queda guardado para futuras consultas.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar chat', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=chat-interno-settings'), 'icon' => 'dashicons-admin-settings'],
            ],
        ]);

        // Chat Grupos
        $this->register_help('chat_grupos', [
            'title'       => __('Cómo usar el Chat de Grupos', 'flavor-chat-ia'),
            'description' => __('Salas de chat grupales para conversaciones temáticas o por equipos.', 'flavor-chat-ia'),
            'tips'        => [
                __('Crea <strong>salas temáticas</strong> para diferentes grupos o temas.', 'flavor-chat-ia'),
                __('Los <strong>administradores</strong> de sala pueden moderar mensajes.', 'flavor-chat-ia'),
                __('Las salas pueden ser <strong>públicas o privadas</strong>.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea las salas de chat necesarias.', 'flavor-chat-ia'),
                __('Asigna administradores a cada sala.', 'flavor-chat-ia'),
                __('Los usuarios se unen a las salas que les interesan.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Gestionar salas', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=chat-grupos-salas'), 'icon' => 'dashicons-groups'],
            ],
        ]);

        // Comunidades
        $this->register_help('comunidades', [
            'title'       => __('Cómo usar Comunidades', 'flavor-chat-ia'),
            'description' => __('Espacios independientes dentro de la plataforma con sus propios miembros y contenido.', 'flavor-chat-ia'),
            'tips'        => [
                __('Cada comunidad tiene su propio <strong>espacio y configuración</strong>.', 'flavor-chat-ia'),
                __('Los <strong>administradores</strong> de comunidad gestionan miembros y contenido.', 'flavor-chat-ia'),
                __('Las comunidades pueden ser <strong>abiertas o por invitación</strong>.', 'flavor-chat-ia'),
                __('Los miembros pueden pertenecer a <strong>múltiples comunidades</strong>.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea una nueva comunidad con nombre y descripción.', 'flavor-chat-ia'),
                __('Configura la privacidad y reglas de acceso.', 'flavor-chat-ia'),
                __('Invita a los primeros miembros o hazla pública.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Crear comunidad', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=comunidades-nueva'), 'icon' => 'dashicons-plus-alt'],
                ['text' => __('Ver todas', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=comunidades-listado'), 'icon' => 'dashicons-networking'],
            ],
        ]);

        // Colectivos
        $this->register_help('colectivos', [
            'title'       => __('Cómo usar Colectivos', 'flavor-chat-ia'),
            'description' => __('Grupos de trabajo o interés dentro de la comunidad con objetivos comunes.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los colectivos pueden tener <strong>proyectos propios</strong>.', 'flavor-chat-ia'),
                __('Cada colectivo tiene su <strong>espacio de comunicación</strong>.', 'flavor-chat-ia'),
                __('Los miembros pueden tener <strong>roles diferentes</strong> (coordinador, miembro, etc.).', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea un colectivo con su propósito y objetivos.', 'flavor-chat-ia'),
                __('Invita a personas interesadas a unirse.', 'flavor-chat-ia'),
                __('Organiza tareas y proyectos del colectivo.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Crear colectivo', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=colectivos-nuevo'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Multimedia
        $this->register_help('multimedia', [
            'title'       => __('Cómo usar Multimedia', 'flavor-chat-ia'),
            'description' => __('Gestión de contenido multimedia: galerías, vídeos, podcasts y documentos.', 'flavor-chat-ia'),
            'tips'        => [
                __('Organiza el contenido en <strong>álbumes y colecciones</strong>.', 'flavor-chat-ia'),
                __('Los usuarios pueden <strong>subir su propio contenido</strong> si está habilitado.', 'flavor-chat-ia'),
                __('Configura <strong>límites de tamaño</strong> y tipos de archivo permitidos.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea las categorías y álbumes para organizar el contenido.', 'flavor-chat-ia'),
                __('Sube contenido multimedia o permite que los usuarios lo hagan.', 'flavor-chat-ia'),
                __('Modera el contenido subido por los usuarios.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Subir contenido', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=multimedia-subir'), 'icon' => 'dashicons-upload'],
                ['text' => __('Galería', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=multimedia-galeria'), 'icon' => 'dashicons-format-gallery'],
            ],
        ]);

        // Radio
        $this->register_help('radio', [
            'title'       => __('Cómo usar Radio Comunitaria', 'flavor-chat-ia'),
            'description' => __('Emisora de radio online con programación, podcasts y emisión en directo.', 'flavor-chat-ia'),
            'tips'        => [
                __('Configura la <strong>parrilla de programación</strong> semanal.', 'flavor-chat-ia'),
                __('Los <strong>podcasts</strong> quedan disponibles para escuchar bajo demanda.', 'flavor-chat-ia'),
                __('La <strong>emisión en directo</strong> permite retransmitir eventos en vivo.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Configura la URL del stream de audio.', 'flavor-chat-ia'),
                __('Crea los programas y asigna horarios.', 'flavor-chat-ia'),
                __('Sube podcasts de los programas emitidos.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Configurar radio', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=radio-settings'), 'icon' => 'dashicons-admin-settings'],
                ['text' => __('Programación', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=radio-programacion'), 'icon' => 'dashicons-calendar-alt'],
            ],
        ]);

        // Huertos Urbanos
        $this->register_help('huertos_urbanos', [
            'title'       => __('Cómo usar Huertos Urbanos', 'flavor-chat-ia'),
            'description' => __('Gestión de parcelas, cosechas y recursos para huertos comunitarios.', 'flavor-chat-ia'),
            'tips'        => [
                __('Asigna <strong>parcelas</strong> a los hortelanos registrados.', 'flavor-chat-ia'),
                __('Registra las <strong>cosechas</strong> para llevar estadísticas.', 'flavor-chat-ia'),
                __('Comparte <strong>recursos</strong> como herramientas y semillas.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra las parcelas disponibles en el huerto.', 'flavor-chat-ia'),
                __('Asigna parcelas a los usuarios interesados.', 'flavor-chat-ia'),
                __('Los hortelanos registran sus cosechas periódicamente.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver parcelas', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=huertos-parcelas'), 'icon' => 'dashicons-grid-view'],
            ],
        ]);

        // Incidencias
        $this->register_help('incidencias', [
            'title'       => __('Cómo usar Incidencias', 'flavor-chat-ia'),
            'description' => __('Sistema de reporte y seguimiento de incidencias y problemas.', 'flavor-chat-ia'),
            'tips'        => [
                __('Las incidencias tienen <strong>estados</strong>: abierta, en proceso, resuelta.', 'flavor-chat-ia'),
                __('Asigna <strong>responsables</strong> para cada tipo de incidencia.', 'flavor-chat-ia'),
                __('Los usuarios reciben <strong>notificaciones</strong> del progreso.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Configura las categorías de incidencias.', 'flavor-chat-ia'),
                __('Los usuarios reportan incidencias desde el frontend.', 'flavor-chat-ia'),
                __('Gestiona y resuelve las incidencias reportadas.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver incidencias', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=incidencias-listado'), 'icon' => 'dashicons-warning'],
            ],
        ]);

        // Talleres
        $this->register_help('talleres', [
            'title'       => __('Cómo usar Talleres', 'flavor-chat-ia'),
            'description' => __('Organiza talleres formativos con inscripciones, materiales y seguimiento.', 'flavor-chat-ia'),
            'tips'        => [
                __('Define los <strong>materiales necesarios</strong> para cada taller.', 'flavor-chat-ia'),
                __('Configura el <strong>aforo máximo</strong> y lista de espera.', 'flavor-chat-ia'),
                __('Los <strong>facilitadores</strong> pueden gestionar sus propios talleres.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea un taller con descripción, fecha y lugar.', 'flavor-chat-ia'),
                __('Añade los materiales o requisitos previos.', 'flavor-chat-ia'),
                __('Abre las inscripciones para los participantes.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Crear taller', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=taller'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Cursos
        $this->register_help('cursos', [
            'title'       => __('Cómo usar Cursos', 'flavor-chat-ia'),
            'description' => __('Plataforma de aprendizaje con cursos, lecciones y seguimiento de progreso.', 'flavor-chat-ia'),
            'tips'        => [
                __('Organiza el contenido en <strong>módulos y lecciones</strong>.', 'flavor-chat-ia'),
                __('Añade <strong>cuestionarios</strong> para evaluar el aprendizaje.', 'flavor-chat-ia'),
                __('Los usuarios ven su <strong>progreso</strong> en cada curso.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea un curso con su estructura de módulos.', 'flavor-chat-ia'),
                __('Añade las lecciones con contenido multimedia.', 'flavor-chat-ia'),
                __('Publica el curso para que los usuarios se inscriban.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Crear curso', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=curso'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Biblioteca
        $this->register_help('biblioteca', [
            'title'       => __('Cómo usar la Biblioteca', 'flavor-chat-ia'),
            'description' => __('Gestión de préstamos de libros, revistas y otros materiales.', 'flavor-chat-ia'),
            'tips'        => [
                __('Configura el <strong>período de préstamo</strong> por tipo de material.', 'flavor-chat-ia'),
                __('Los usuarios pueden hacer <strong>reservas</strong> de materiales prestados.', 'flavor-chat-ia'),
                __('Envía <strong>recordatorios</strong> automáticos de devolución.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra el catálogo de materiales disponibles.', 'flavor-chat-ia'),
                __('Configura las políticas de préstamo.', 'flavor-chat-ia'),
                __('Los usuarios solicitan préstamos desde el catálogo.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver catálogo', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=biblioteca-catalogo'), 'icon' => 'dashicons-book'],
            ],
        ]);

        // Bicicletas Compartidas
        $this->register_help('bicicletas_compartidas', [
            'title'       => __('Cómo usar Bicicletas Compartidas', 'flavor-chat-ia'),
            'description' => __('Sistema de préstamo de bicicletas con estaciones y seguimiento.', 'flavor-chat-ia'),
            'tips'        => [
                __('Registra las <strong>bicicletas</strong> y su estado actual.', 'flavor-chat-ia'),
                __('Configura las <strong>estaciones</strong> de recogida y devolución.', 'flavor-chat-ia'),
                __('Los usuarios reservan bicicletas desde su móvil.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra las estaciones con su ubicación.', 'flavor-chat-ia'),
                __('Añade las bicicletas disponibles.', 'flavor-chat-ia'),
                __('Los usuarios pueden reservar y devolver bicicletas.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver estaciones', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=bicicletas-estaciones'), 'icon' => 'dashicons-location'],
            ],
        ]);

        // Carpooling
        $this->register_help('carpooling', [
            'title'       => __('Cómo usar Carpooling', 'flavor-chat-ia'),
            'description' => __('Comparte viajes en coche para reducir costes y emisiones.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los <strong>conductores</strong> publican sus rutas disponibles.', 'flavor-chat-ia'),
                __('Los <strong>pasajeros</strong> buscan viajes por origen y destino.', 'flavor-chat-ia'),
                __('El sistema calcula una <strong>aportación sugerida</strong> por los gastos.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Los conductores registran sus viajes habituales.', 'flavor-chat-ia'),
                __('Los pasajeros buscan y solicitan plaza.', 'flavor-chat-ia'),
                __('El conductor acepta o rechaza las solicitudes.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Publicar viaje', 'flavor-chat-ia'), 'url' => home_url('/mi-portal/carpooling/publicar/'), 'icon' => 'dashicons-plus-alt'],
            ],
        ]);

        // Avisos Municipales
        $this->register_help('avisos_municipales', [
            'title'       => __('Cómo usar Avisos Municipales', 'flavor-chat-ia'),
            'description' => __('Publica avisos y comunicados para la comunidad.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los avisos pueden ser <strong>urgentes</strong> para mayor visibilidad.', 'flavor-chat-ia'),
                __('Configura <strong>notificaciones</strong> push para avisos importantes.', 'flavor-chat-ia'),
                __('Los avisos antiguos se archivan automáticamente.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea un nuevo aviso con título y contenido.', 'flavor-chat-ia'),
                __('Selecciona el tipo y urgencia del aviso.', 'flavor-chat-ia'),
                __('Publica para que aparezca en el tablón.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Crear aviso', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=avisos-nuevo'), 'icon' => 'dashicons-megaphone'],
            ],
        ]);

        // Tramites
        $this->register_help('tramites', [
            'title'       => __('Cómo usar Trámites', 'flavor-chat-ia'),
            'description' => __('Gestión de trámites administrativos y expedientes online.', 'flavor-chat-ia'),
            'tips'        => [
                __('Define los <strong>tipos de trámite</strong> y sus requisitos.', 'flavor-chat-ia'),
                __('Los usuarios siguen el <strong>estado de sus expedientes</strong>.', 'flavor-chat-ia'),
                __('Configura <strong>plazos</strong> y alertas automáticas.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Crea los tipos de trámite disponibles.', 'flavor-chat-ia'),
                __('Define los documentos requeridos para cada uno.', 'flavor-chat-ia'),
                __('Los usuarios inician trámites desde el portal.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver expedientes', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=tramites-expedientes'), 'icon' => 'dashicons-portfolio'],
            ],
        ]);

        // Transparencia
        $this->register_help('transparencia', [
            'title'       => __('Cómo usar Transparencia', 'flavor-chat-ia'),
            'description' => __('Portal de transparencia con documentos, presupuestos y rendición de cuentas.', 'flavor-chat-ia'),
            'tips'        => [
                __('Publica <strong>documentos</strong> organizados por categorías.', 'flavor-chat-ia'),
                __('Los <strong>presupuestos</strong> se visualizan con gráficos.', 'flavor-chat-ia'),
                __('Mantén un <strong>histórico</strong> de documentos anteriores.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Configura las categorías de documentos.', 'flavor-chat-ia'),
                __('Sube los documentos de transparencia.', 'flavor-chat-ia'),
                __('Los usuarios pueden consultar y descargar.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Subir documento', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=transparencia-subir'), 'icon' => 'dashicons-upload'],
            ],
        ]);

        // Compostaje
        $this->register_help('compostaje', [
            'title'       => __('Cómo usar Compostaje', 'flavor-chat-ia'),
            'description' => __('Gestión de composteras comunitarias y seguimiento de aportaciones.', 'flavor-chat-ia'),
            'tips'        => [
                __('Registra las <strong>aportaciones</strong> de cada participante.', 'flavor-chat-ia'),
                __('Lleva el <strong>control de producción</strong> de compost.', 'flavor-chat-ia'),
                __('Organiza <strong>turnos de mantenimiento</strong> de las composteras.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra las composteras disponibles.', 'flavor-chat-ia'),
                __('Los participantes registran sus aportaciones.', 'flavor-chat-ia'),
                __('Distribuye el compost producido.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver composteras', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=compostaje-composteras'), 'icon' => 'dashicons-carrot'],
            ],
        ]);

        // Economía del Don
        $this->register_help('economia_don', [
            'title'       => __('Cómo usar Economía del Don', 'flavor-chat-ia'),
            'description' => __('Espacio para ofrecer y recibir objetos, servicios y habilidades de forma gratuita.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los <strong>dones</strong> se ofrecen sin esperar nada a cambio.', 'flavor-chat-ia'),
                __('Fomenta la <strong>abundancia</strong> y el compartir en comunidad.', 'flavor-chat-ia'),
                __('Cualquier persona puede ofrecer o solicitar dones.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Publica lo que quieres ofrecer o necesitas.', 'flavor-chat-ia'),
                __('Conecta con quien puede ayudarte o necesita ayuda.', 'flavor-chat-ia'),
                __('Realiza el intercambio y agradece.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ofrecer don', 'flavor-chat-ia'), 'url' => admin_url('post-new.php?post_type=ed_don'), 'icon' => 'dashicons-heart'],
            ],
        ]);

        // Ayuda Vecinal
        $this->register_help('ayuda_vecinal', [
            'title'       => __('Cómo usar Ayuda Vecinal', 'flavor-chat-ia'),
            'description' => __('Red de apoyo mutuo entre vecinos para pequeñas ayudas cotidianas.', 'flavor-chat-ia'),
            'tips'        => [
                __('Las solicitudes pueden ser <strong>urgentes</strong> para priorizar.', 'flavor-chat-ia'),
                __('Los vecinos que ayudan reciben <strong>reconocimiento</strong>.', 'flavor-chat-ia'),
                __('Fomenta la <strong>cohesión social</strong> del barrio.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Publica una solicitud de ayuda.', 'flavor-chat-ia'),
                __('Los vecinos cercanos ven la solicitud.', 'flavor-chat-ia'),
                __('Alguien se ofrece y coordinan la ayuda.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Nueva solicitud', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=ayuda-nueva-solicitud'), 'icon' => 'dashicons-sos'],
            ],
        ]);

        // Espacios Comunes
        $this->register_help('espacios_comunes', [
            'title'       => __('Cómo usar Espacios Comunes', 'flavor-chat-ia'),
            'description' => __('Gestión y reserva de espacios compartidos: salas, terrazas, cocinas comunitarias.', 'flavor-chat-ia'),
            'tips'        => [
                __('Define los <strong>horarios de disponibilidad</strong> de cada espacio.', 'flavor-chat-ia'),
                __('Configura <strong>reglas de uso</strong> como duración máxima o anticipación.', 'flavor-chat-ia'),
                __('Los <strong>conflictos de reserva</strong> se detectan automáticamente.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra los espacios disponibles con su capacidad.', 'flavor-chat-ia'),
                __('Configura los horarios y reglas de cada espacio.', 'flavor-chat-ia'),
                __('Los usuarios reservan desde el portal.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver espacios', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=espacios-listado'), 'icon' => 'dashicons-building'],
            ],
        ]);

        // Reciclaje
        $this->register_help('reciclaje', [
            'title'       => __('Cómo usar Reciclaje', 'flavor-chat-ia'),
            'description' => __('Sistema de puntos de reciclaje con gamificación y seguimiento de impacto ambiental.', 'flavor-chat-ia'),
            'tips'        => [
                __('Los usuarios ganan <strong>puntos</strong> por cada depósito de reciclaje.', 'flavor-chat-ia'),
                __('El <strong>impacto ambiental</strong> se calcula automáticamente.', 'flavor-chat-ia'),
                __('Monitoriza el <strong>estado de los contenedores</strong> en cada punto.', 'flavor-chat-ia'),
            ],
            'steps'       => [
                __('Registra los puntos de reciclaje con su ubicación.', 'flavor-chat-ia'),
                __('Los usuarios depositan residuos y registran la aportación.', 'flavor-chat-ia'),
                __('Consulta las estadísticas de impacto ambiental.', 'flavor-chat-ia'),
            ],
            'links'       => [
                ['text' => __('Ver puntos', 'flavor-chat-ia'), 'url' => admin_url('admin.php?page=reciclaje-puntos'), 'icon' => 'dashicons-admin-site'],
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
