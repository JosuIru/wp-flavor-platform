<?php
/**
 * Trait: Acciones Frontend para Módulos
 *
 * Proporciona acciones de usuario comunes para módulos:
 * - Reservar, Inscribirse, Alquilar, Solicitar, etc.
 * - Formularios de acción
 * - Páginas frontend automáticas
 *
 * @package FlavorChatIA
 * @since 4.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para acciones frontend de módulos
 */
trait Flavor_Module_Frontend_Actions {

    /**
     * Acciones disponibles del módulo
     *
     * @var array
     */
    protected $frontend_actions = [];

    /**
     * Inicializar sistema de acciones frontend
     */
    protected function init_frontend_actions() {
        $this->definir_acciones_frontend();
        $this->registrar_shortcodes_acciones();
        $this->registrar_ajax_handlers();
    }

    /**
     * Definir acciones frontend del módulo
     * Los módulos deben sobrescribir este método
     */
    protected function definir_acciones_frontend() {
        // Acciones por defecto según tipo de módulo
        $this->frontend_actions = $this->get_default_actions_by_type();
    }

    /**
     * Obtener acciones por defecto según el tipo de módulo
     *
     * @return array
     */
    protected function get_default_actions_by_type() {
        $module_id = $this->get_id();

        // Mapeo de módulos a tipos de acción
        $acciones_por_modulo = [
            // Reservas y alquileres
            'bicicletas-compartidas' => [
                'alquilar' => [
                    'label' => __('Alquilar Bicicleta', 'flavor-chat-ia'),
                    'icon' => 'dashicons-unlock',
                    'type' => 'form',
                    'fields' => ['estacion', 'duracion'],
                    'capability' => 'read',
                ],
                'devolver' => [
                    'label' => __('Devolver Bicicleta', 'flavor-chat-ia'),
                    'icon' => 'dashicons-lock',
                    'type' => 'action',
                    'capability' => 'read',
                ],
                'ver_mapa' => [
                    'label' => __('Ver Mapa', 'flavor-chat-ia'),
                    'icon' => 'dashicons-location',
                    'type' => 'page',
                    'page' => 'mapa-bicicletas',
                ],
            ],
            'parking' => [
                'reservar' => [
                    'label' => __('Reservar Plaza', 'flavor-chat-ia'),
                    'icon' => 'dashicons-car',
                    'type' => 'form',
                    'fields' => ['fecha', 'hora_inicio', 'hora_fin', 'matricula'],
                ],
                'mis_reservas' => [
                    'label' => __('Mis Reservas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-list-view',
                    'type' => 'page',
                    'page' => 'mis-reservas-parking',
                ],
            ],
            'carpooling' => [
                'buscar_viaje' => [
                    'label' => __('Buscar Viaje', 'flavor-chat-ia'),
                    'icon' => 'dashicons-search',
                    'type' => 'form',
                    'fields' => ['origen', 'destino', 'fecha'],
                ],
                'ofrecer_viaje' => [
                    'label' => __('Ofrecer Viaje', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus-alt',
                    'type' => 'form',
                    'fields' => ['origen', 'destino', 'fecha', 'hora', 'plazas', 'precio'],
                ],
                'mis_viajes' => [
                    'label' => __('Mis Viajes', 'flavor-chat-ia'),
                    'icon' => 'dashicons-car',
                    'type' => 'page',
                ],
            ],
            // Eventos y cursos
            'eventos' => [
                'inscribirse' => [
                    'label' => __('Inscribirse', 'flavor-chat-ia'),
                    'icon' => 'dashicons-yes-alt',
                    'type' => 'action',
                    'requires' => ['evento_id'],
                ],
                'ver_calendario' => [
                    'label' => __('Calendario', 'flavor-chat-ia'),
                    'icon' => 'dashicons-calendar-alt',
                    'type' => 'page',
                    'page' => 'calendario-eventos',
                ],
                'mis_inscripciones' => [
                    'label' => __('Mis Inscripciones', 'flavor-chat-ia'),
                    'icon' => 'dashicons-tickets-alt',
                    'type' => 'page',
                ],
            ],
            'cursos' => [
                'inscribirse' => [
                    'label' => __('Inscribirse al Curso', 'flavor-chat-ia'),
                    'icon' => 'dashicons-welcome-learn-more',
                    'type' => 'form',
                    'fields' => ['curso_id', 'modalidad'],
                ],
                'mis_cursos' => [
                    'label' => __('Mis Cursos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-book',
                    'type' => 'page',
                ],
                'catalogo' => [
                    'label' => __('Ver Catálogo', 'flavor-chat-ia'),
                    'icon' => 'dashicons-grid-view',
                    'type' => 'page',
                    'page' => 'catalogo-cursos',
                ],
            ],
            'talleres' => [
                'inscribirse' => [
                    'label' => __('Inscribirse', 'flavor-chat-ia'),
                    'icon' => 'dashicons-hammer',
                    'type' => 'action',
                ],
                'ver_talleres' => [
                    'label' => __('Ver Talleres', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-tools',
                    'type' => 'page',
                ],
            ],
            // Espacios y recursos
            'espacios-comunes' => [
                'reservar' => [
                    'label' => __('Reservar Espacio', 'flavor-chat-ia'),
                    'icon' => 'dashicons-building',
                    'type' => 'form',
                    'fields' => ['espacio_id', 'fecha', 'hora_inicio', 'hora_fin', 'motivo'],
                ],
                'ver_disponibilidad' => [
                    'label' => __('Ver Disponibilidad', 'flavor-chat-ia'),
                    'icon' => 'dashicons-calendar',
                    'type' => 'page',
                    'page' => 'disponibilidad-espacios',
                ],
                'mis_reservas' => [
                    'label' => __('Mis Reservas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-list-view',
                    'type' => 'page',
                ],
            ],
            'biblioteca' => [
                'buscar' => [
                    'label' => __('Buscar Libro', 'flavor-chat-ia'),
                    'icon' => 'dashicons-search',
                    'type' => 'form',
                    'fields' => ['titulo', 'autor', 'categoria'],
                ],
                'reservar' => [
                    'label' => __('Reservar Libro', 'flavor-chat-ia'),
                    'icon' => 'dashicons-book-alt',
                    'type' => 'action',
                    'requires' => ['libro_id'],
                ],
                'mis_prestamos' => [
                    'label' => __('Mis Préstamos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-book',
                    'type' => 'page',
                ],
            ],
            // Comercio
            'marketplace' => [
                'publicar' => [
                    'label' => __('Publicar Anuncio', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus',
                    'type' => 'form',
                    'fields' => ['titulo', 'descripcion', 'precio', 'categoria', 'imagenes'],
                ],
                'buscar' => [
                    'label' => __('Buscar', 'flavor-chat-ia'),
                    'icon' => 'dashicons-search',
                    'type' => 'page',
                    'page' => 'marketplace',
                ],
                'mis_anuncios' => [
                    'label' => __('Mis Anuncios', 'flavor-chat-ia'),
                    'icon' => 'dashicons-megaphone',
                    'type' => 'page',
                ],
            ],
            'tienda-local' => [
                'comprar' => [
                    'label' => __('Comprar', 'flavor-chat-ia'),
                    'icon' => 'dashicons-cart',
                    'type' => 'page',
                    'page' => 'tienda',
                ],
                'mis_pedidos' => [
                    'label' => __('Mis Pedidos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-clipboard',
                    'type' => 'page',
                ],
            ],
            'grupos-consumo' => [
                'unirse' => [
                    'label' => __('Unirse a Grupo', 'flavor-chat-ia'),
                    'icon' => 'dashicons-groups',
                    'type' => 'form',
                    'fields' => ['grupo_id'],
                ],
                'ver_grupos' => [
                    'label' => __('Ver Grupos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-networking',
                    'type' => 'page',
                ],
                'hacer_pedido' => [
                    'label' => __('Hacer Pedido', 'flavor-chat-ia'),
                    'icon' => 'dashicons-cart',
                    'type' => 'form',
                ],
            ],
            // Servicios municipales
            'incidencias' => [
                'reportar' => [
                    'label' => __('Reportar Incidencia', 'flavor-chat-ia'),
                    'icon' => 'dashicons-warning',
                    'type' => 'form',
                    'fields' => ['tipo', 'ubicacion', 'descripcion', 'foto'],
                ],
                'mis_incidencias' => [
                    'label' => __('Mis Incidencias', 'flavor-chat-ia'),
                    'icon' => 'dashicons-list-view',
                    'type' => 'page',
                ],
            ],
            'tramites' => [
                'iniciar' => [
                    'label' => __('Iniciar Trámite', 'flavor-chat-ia'),
                    'icon' => 'dashicons-media-document',
                    'type' => 'page',
                    'page' => 'tramites',
                ],
                'mis_tramites' => [
                    'label' => __('Mis Trámites', 'flavor-chat-ia'),
                    'icon' => 'dashicons-portfolio',
                    'type' => 'page',
                ],
            ],
            'participacion-ciudadana' => [
                'votar' => [
                    'label' => __('Votar', 'flavor-chat-ia'),
                    'icon' => 'dashicons-forms',
                    'type' => 'page',
                    'page' => 'votaciones',
                ],
                'proponer' => [
                    'label' => __('Hacer Propuesta', 'flavor-chat-ia'),
                    'icon' => 'dashicons-lightbulb',
                    'type' => 'form',
                    'fields' => ['titulo', 'descripcion', 'categoria'],
                ],
            ],
            'presupuestos-participativos' => [
                'ver_proyectos' => [
                    'label' => __('Ver Proyectos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-chart-pie',
                    'type' => 'page',
                ],
                'votar' => [
                    'label' => __('Votar Proyecto', 'flavor-chat-ia'),
                    'icon' => 'dashicons-yes',
                    'type' => 'action',
                ],
                'proponer' => [
                    'label' => __('Proponer Proyecto', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus-alt2',
                    'type' => 'form',
                ],
            ],
            // Sostenibilidad
            'huertos-urbanos' => [
                'solicitar_parcela' => [
                    'label' => __('Solicitar Parcela', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-site',
                    'type' => 'form',
                    'fields' => ['zona', 'tamano', 'experiencia'],
                ],
                'mi_huerto' => [
                    'label' => __('Mi Huerto', 'flavor-chat-ia'),
                    'icon' => 'dashicons-carrot',
                    'type' => 'page',
                ],
                'registrar_actividad' => [
                    'label' => __('Registrar Actividad', 'flavor-chat-ia'),
                    'icon' => 'dashicons-edit',
                    'type' => 'form',
                ],
            ],
            'reciclaje' => [
                'registrar' => [
                    'label' => __('Registrar Reciclaje', 'flavor-chat-ia'),
                    'icon' => 'dashicons-image-rotate',
                    'type' => 'form',
                    'fields' => ['tipo', 'cantidad', 'punto_limpio'],
                ],
                'puntos_limpios' => [
                    'label' => __('Puntos Limpios', 'flavor-chat-ia'),
                    'icon' => 'dashicons-location',
                    'type' => 'page',
                ],
                'mis_stats' => [
                    'label' => __('Mis Estadísticas', 'flavor-chat-ia'),
                    'icon' => 'dashicons-chart-bar',
                    'type' => 'page',
                ],
            ],
            'compostaje' => [
                'solicitar_compostador' => [
                    'label' => __('Solicitar Compostador', 'flavor-chat-ia'),
                    'icon' => 'dashicons-carrot',
                    'type' => 'form',
                ],
                'registrar_aporte' => [
                    'label' => __('Registrar Aporte', 'flavor-chat-ia'),
                    'icon' => 'dashicons-plus',
                    'type' => 'form',
                ],
            ],
            // Comunicación
            'chat-grupos' => [
                'crear_grupo' => [
                    'label' => __('Crear Grupo', 'flavor-chat-ia'),
                    'icon' => 'dashicons-groups',
                    'type' => 'form',
                    'fields' => ['nombre', 'descripcion', 'tipo'],
                ],
                'mis_grupos' => [
                    'label' => __('Mis Grupos', 'flavor-chat-ia'),
                    'icon' => 'dashicons-admin-comments',
                    'type' => 'page',
                ],
            ],
            'foros' => [
                'nuevo_tema' => [
                    'label' => __('Nuevo Tema', 'flavor-chat-ia'),
                    'icon' => 'dashicons-edit',
                    'type' => 'form',
                    'fields' => ['titulo', 'contenido', 'categoria'],
                ],
                'ver_foros' => [
                    'label' => __('Ver Foros', 'flavor-chat-ia'),
                    'icon' => 'dashicons-format-chat',
                    'type' => 'page',
                ],
            ],
            // Ayuda mutua
            'banco-tiempo' => [
                'ofrecer_servicio' => [
                    'label' => __('Ofrecer Servicio', 'flavor-chat-ia'),
                    'icon' => 'dashicons-heart',
                    'type' => 'form',
                    'fields' => ['servicio', 'descripcion', 'horas'],
                ],
                'buscar_servicio' => [
                    'label' => __('Buscar Servicio', 'flavor-chat-ia'),
                    'icon' => 'dashicons-search',
                    'type' => 'page',
                ],
                'mi_saldo' => [
                    'label' => __('Mi Saldo', 'flavor-chat-ia'),
                    'icon' => 'dashicons-clock',
                    'type' => 'page',
                ],
            ],
            'ayuda-vecinal' => [
                'pedir_ayuda' => [
                    'label' => __('Pedir Ayuda', 'flavor-chat-ia'),
                    'icon' => 'dashicons-sos',
                    'type' => 'form',
                    'fields' => ['tipo', 'descripcion', 'urgencia'],
                ],
                'ofrecer_ayuda' => [
                    'label' => __('Ofrecer Ayuda', 'flavor-chat-ia'),
                    'icon' => 'dashicons-buddicons-buddypress-logo',
                    'type' => 'form',
                ],
                'ver_solicitudes' => [
                    'label' => __('Ver Solicitudes', 'flavor-chat-ia'),
                    'icon' => 'dashicons-list-view',
                    'type' => 'page',
                ],
            ],
            // Usuario
            'fichaje-empleados' => [
                'fichar_entrada' => [
                    'label' => __('Fichar Entrada', 'flavor-chat-ia'),
                    'icon' => 'dashicons-clock',
                    'type' => 'action',
                ],
                'fichar_salida' => [
                    'label' => __('Fichar Salida', 'flavor-chat-ia'),
                    'icon' => 'dashicons-migrate',
                    'type' => 'action',
                ],
                'mis_fichajes' => [
                    'label' => __('Mis Fichajes', 'flavor-chat-ia'),
                    'icon' => 'dashicons-calendar-alt',
                    'type' => 'page',
                ],
            ],
        ];

        return $acciones_por_modulo[$module_id] ?? [];
    }

    /**
     * Registrar shortcodes de acciones
     */
    protected function registrar_shortcodes_acciones() {
        $module_id = $this->get_id();

        // Shortcode para mostrar acciones del módulo
        add_shortcode('flavor_' . $module_id . '_acciones', [$this, 'shortcode_acciones']);

        // Shortcode para formulario de acción específica
        add_shortcode('flavor_' . $module_id . '_form', [$this, 'shortcode_formulario_accion']);
    }

    /**
     * Registrar handlers AJAX
     */
    protected function registrar_ajax_handlers() {
        $module_id = $this->get_id();

        add_action('wp_ajax_flavor_' . $module_id . '_action', [$this, 'ajax_ejecutar_accion']);
        add_action('wp_ajax_nopriv_flavor_' . $module_id . '_action', [$this, 'ajax_ejecutar_accion_no_auth']);
    }

    /**
     * Shortcode: Mostrar acciones disponibles
     *
     * Uso: [flavor_{modulo}_acciones layout="buttons|list|grid"]
     */
    public function shortcode_acciones($atts) {
        $atts = shortcode_atts([
            'layout' => 'buttons',
            'acciones' => '', // Filtrar acciones específicas
            'class' => '',
        ], $atts);

        if (!is_user_logged_in() && $this->requiere_login()) {
            return $this->renderizar_login_requerido();
        }

        $acciones = $this->get_frontend_actions();

        if (!empty($atts['acciones'])) {
            $filtrar = array_map('trim', explode(',', $atts['acciones']));
            $acciones = array_intersect_key($acciones, array_flip($filtrar));
        }

        if (empty($acciones)) {
            return '';
        }

        ob_start();
        $this->renderizar_acciones($acciones, $atts['layout'], $atts['class']);
        return ob_get_clean();
    }

    /**
     * Renderizar acciones
     */
    protected function renderizar_acciones($acciones, $layout = 'buttons', $extra_class = '') {
        $module_id = $this->get_id();
        ?>
        <div class="flavor-module-actions flavor-module-actions--<?php echo esc_attr($layout); ?> <?php echo esc_attr($extra_class); ?>" data-module="<?php echo esc_attr($module_id); ?>">
            <?php foreach ($acciones as $action_id => $action) : ?>
                <?php if ($this->user_can_action($action)) : ?>
                    <?php $this->renderizar_boton_accion($action_id, $action); ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderizar botón de acción
     */
    protected function renderizar_boton_accion($action_id, $action) {
        $module_id = $this->get_id();
        $type = $action['type'] ?? 'action';
        $icon = $action['icon'] ?? 'dashicons-yes';
        $label = $action['label'] ?? ucfirst($action_id);

        switch ($type) {
            case 'page':
                $page_slug = $action['page'] ?? $module_id . '-' . $action_id;
                $page_url = $this->get_page_url($page_slug);
                ?>
                <a href="<?php echo esc_url($page_url); ?>" class="flavor-action-btn flavor-action-btn--page">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                    <span class="flavor-action-btn__label"><?php echo esc_html($label); ?></span>
                </a>
                <?php
                break;

            case 'form':
                ?>
                <button type="button"
                        class="flavor-action-btn flavor-action-btn--form"
                        data-action="<?php echo esc_attr($action_id); ?>"
                        data-module="<?php echo esc_attr($module_id); ?>">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                    <span class="flavor-action-btn__label"><?php echo esc_html($label); ?></span>
                </button>
                <?php
                break;

            case 'action':
            default:
                ?>
                <button type="button"
                        class="flavor-action-btn flavor-action-btn--action"
                        data-action="<?php echo esc_attr($action_id); ?>"
                        data-module="<?php echo esc_attr($module_id); ?>">
                    <span class="dashicons <?php echo esc_attr($icon); ?>"></span>
                    <span class="flavor-action-btn__label"><?php echo esc_html($label); ?></span>
                </button>
                <?php
                break;
        }
    }

    /**
     * Obtener acciones frontend
     *
     * @return array
     */
    public function get_frontend_actions() {
        if (empty($this->frontend_actions)) {
            $this->definir_acciones_frontend();
        }
        return $this->frontend_actions;
    }

    /**
     * Verificar si usuario puede ejecutar acción
     */
    protected function user_can_action($action) {
        $capability = $action['capability'] ?? 'read';

        if (!is_user_logged_in()) {
            return false;
        }

        return current_user_can($capability);
    }

    /**
     * Verificar si el módulo requiere login
     */
    protected function requiere_login() {
        return true; // Por defecto, todas las acciones requieren login
    }

    /**
     * Renderizar mensaje de login requerido
     */
    protected function renderizar_login_requerido() {
        ob_start();
        ?>
        <div class="flavor-login-required">
            <p><?php _e('Debes iniciar sesión para realizar esta acción.', 'flavor-chat-ia'); ?></p>
            <a href="<?php echo esc_url(wp_login_url(get_permalink())); ?>" class="flavor-button flavor-button--primary">
                <?php _e('Iniciar Sesión', 'flavor-chat-ia'); ?>
            </a>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtener URL de página del módulo
     */
    protected function get_page_url($slug) {
        // Buscar página por slug
        $page = get_page_by_path($slug);

        if ($page) {
            return get_permalink($page);
        }

        // Fallback: construir URL
        return home_url('/' . $slug . '/');
    }

    /**
     * Handler AJAX para ejecutar acción
     */
    public function ajax_ejecutar_accion() {
        // Verificar nonce
        if (!check_ajax_referer('flavor_module_action', 'nonce', false)) {
            wp_send_json_error(__('Token inválido', 'flavor-chat-ia'));
        }

        $action_id = sanitize_key($_POST['action_id'] ?? '');

        if (empty($action_id)) {
            wp_send_json_error(__('Acción no especificada', 'flavor-chat-ia'));
        }

        // Verificar que la acción existe
        $acciones = $this->get_frontend_actions();
        if (!isset($acciones[$action_id])) {
            wp_send_json_error(__('Acción no válida', 'flavor-chat-ia'));
        }

        // Verificar permisos
        if (!$this->user_can_action($acciones[$action_id])) {
            wp_send_json_error(__('No tienes permisos para esta acción', 'flavor-chat-ia'));
        }

        // Obtener parámetros
        $params = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'action_id', 'nonce', 'module'])) {
                $params[$key] = is_array($value)
                    ? array_map('sanitize_text_field', $value)
                    : sanitize_text_field($value);
            }
        }

        // Ejecutar acción
        $result = $this->ejecutar_accion_frontend($action_id, $params);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result['message'] ?? __('Error al procesar', 'flavor-chat-ia'));
        }
    }

    /**
     * Handler AJAX para usuarios no autenticados
     */
    public function ajax_ejecutar_accion_no_auth() {
        wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
    }

    /**
     * Ejecutar acción frontend
     * Los módulos deben sobrescribir este método
     *
     * @param string $action_id ID de la acción
     * @param array $params Parámetros
     * @return array ['success' => bool, 'message' => string, 'data' => array]
     */
    protected function ejecutar_accion_frontend($action_id, $params) {
        // Implementación por defecto - los módulos deben sobrescribir
        return [
            'success' => false,
            'message' => __('Acción no implementada', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtener configuración de formulario para acción
     *
     * @param string $action_id
     * @return array|null
     */
    public function get_form_config($action_id) {
        $acciones = $this->get_frontend_actions();

        if (!isset($acciones[$action_id]) || $acciones[$action_id]['type'] !== 'form') {
            return null;
        }

        $action = $acciones[$action_id];
        $fields = $action['fields'] ?? [];

        // Generar campos según los nombres
        $field_configs = [];
        foreach ($fields as $field_name) {
            $field_configs[$field_name] = $this->get_field_config($field_name);
        }

        return [
            'title' => $action['label'] ?? '',
            'description' => $action['description'] ?? '',
            'fields' => $field_configs,
            'submit_text' => $action['submit_text'] ?? __('Enviar', 'flavor-chat-ia'),
            'ajax' => true,
        ];
    }

    /**
     * Obtener configuración de campo por nombre
     */
    protected function get_field_config($field_name) {
        $campos_comunes = [
            'fecha' => [
                'type' => 'date',
                'label' => __('Fecha', 'flavor-chat-ia'),
                'required' => true,
            ],
            'hora' => [
                'type' => 'time',
                'label' => __('Hora', 'flavor-chat-ia'),
                'required' => true,
            ],
            'hora_inicio' => [
                'type' => 'time',
                'label' => __('Hora inicio', 'flavor-chat-ia'),
                'required' => true,
            ],
            'hora_fin' => [
                'type' => 'time',
                'label' => __('Hora fin', 'flavor-chat-ia'),
                'required' => true,
            ],
            'titulo' => [
                'type' => 'text',
                'label' => __('Título', 'flavor-chat-ia'),
                'required' => true,
            ],
            'descripcion' => [
                'type' => 'textarea',
                'label' => __('Descripción', 'flavor-chat-ia'),
                'required' => false,
            ],
            'precio' => [
                'type' => 'number',
                'label' => __('Precio', 'flavor-chat-ia'),
                'required' => false,
                'min' => 0,
                'step' => '0.01',
            ],
            'cantidad' => [
                'type' => 'number',
                'label' => __('Cantidad', 'flavor-chat-ia'),
                'required' => true,
                'min' => 1,
            ],
            'ubicacion' => [
                'type' => 'text',
                'label' => __('Ubicación', 'flavor-chat-ia'),
                'required' => true,
            ],
            'origen' => [
                'type' => 'text',
                'label' => __('Origen', 'flavor-chat-ia'),
                'required' => true,
            ],
            'destino' => [
                'type' => 'text',
                'label' => __('Destino', 'flavor-chat-ia'),
                'required' => true,
            ],
            'plazas' => [
                'type' => 'number',
                'label' => __('Plazas disponibles', 'flavor-chat-ia'),
                'required' => true,
                'min' => 1,
                'max' => 8,
            ],
            'matricula' => [
                'type' => 'text',
                'label' => __('Matrícula', 'flavor-chat-ia'),
                'required' => true,
            ],
            'motivo' => [
                'type' => 'textarea',
                'label' => __('Motivo', 'flavor-chat-ia'),
                'required' => false,
            ],
            'tipo' => [
                'type' => 'select',
                'label' => __('Tipo', 'flavor-chat-ia'),
                'required' => true,
                'options' => [], // Se llena dinámicamente
            ],
            'categoria' => [
                'type' => 'select',
                'label' => __('Categoría', 'flavor-chat-ia'),
                'required' => false,
                'options' => [],
            ],
            'foto' => [
                'type' => 'file',
                'label' => __('Foto', 'flavor-chat-ia'),
                'accept' => 'image/*',
                'required' => false,
            ],
            'imagenes' => [
                'type' => 'file',
                'label' => __('Imágenes', 'flavor-chat-ia'),
                'accept' => 'image/*',
                'multiple' => true,
                'required' => false,
            ],
            'urgencia' => [
                'type' => 'select',
                'label' => __('Urgencia', 'flavor-chat-ia'),
                'required' => true,
                'options' => [
                    'baja' => __('Baja', 'flavor-chat-ia'),
                    'media' => __('Media', 'flavor-chat-ia'),
                    'alta' => __('Alta', 'flavor-chat-ia'),
                    'urgente' => __('Urgente', 'flavor-chat-ia'),
                ],
            ],
        ];

        return $campos_comunes[$field_name] ?? [
            'type' => 'text',
            'label' => ucfirst(str_replace('_', ' ', $field_name)),
            'required' => false,
        ];
    }
}
