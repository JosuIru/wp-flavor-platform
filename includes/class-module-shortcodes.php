<?php
/**
 * Shortcodes Automáticos de Módulos
 *
 * Registra shortcodes automáticamente para cada módulo activo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes automáticos de módulos
 */
class Flavor_Module_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        add_action('init', [$this, 'register_module_shortcodes'], 20);
        add_action('init', [$this, 'register_specific_shortcodes'], 21);
        // Registrar shortcode de formularios
        add_shortcode('flavor_module_form', [$this, 'render_module_form']);
        // Registrar shortcode de listados de módulos
        add_shortcode('flavor_module_listing', [$this, 'render_module_listing']);
        // Registrar handler AJAX para formularios
        add_action('wp_ajax_flavor_module_action', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_flavor_module_action', [$this, 'handle_form_submission']);
    }

    /**
     * Registra un shortcode solo si no existe ya
     * Esto permite que los módulos registren sus propios shortcodes primero
     *
     * @param string $tag Nombre del shortcode
     * @param string $module Módulo al que pertenece
     * @param string $template Template a renderizar
     */
    private function register_fallback_shortcode($tag, $module, $template) {
        if (!shortcode_exists($tag)) {
            add_shortcode($tag, function($atts) use ($module, $template) {
                return $this->render_template_shortcode($module, $template, $atts);
            });
        }
    }

    /**
     * Registra shortcodes específicos para módulos con templates propios
     * Solo se registran como fallback si el módulo no ha registrado sus propios shortcodes
     */
    public function register_specific_shortcodes() {
        // === ESPACIOS COMUNES ===
        $this->register_fallback_shortcode('espacios_listado', 'espacios-comunes', 'listado');
        $this->register_fallback_shortcode('espacios_mis_reservas', 'espacios-comunes', 'mis-reservas');
        $this->register_fallback_shortcode('espacios_calendario', 'espacios-comunes', 'calendario');
        $this->register_fallback_shortcode('espacios_detalle', 'espacios-comunes', 'detalle');

        // === EVENTOS ===
        $this->register_fallback_shortcode('eventos_listado', 'eventos', 'listado');
        $this->register_fallback_shortcode('eventos_mis_inscripciones', 'eventos', 'mis-inscripciones');
        $this->register_fallback_shortcode('eventos_calendario', 'eventos', 'calendario');

        // === BIBLIOTECA ===
        $this->register_fallback_shortcode('biblioteca_catalogo', 'biblioteca', 'catalogo');
        $this->register_fallback_shortcode('biblioteca_mis_prestamos', 'biblioteca', 'mis-prestamos');
        $this->register_fallback_shortcode('biblioteca_mis_libros', 'biblioteca', 'mis-libros');

        // === MARKETPLACE ===
        $this->register_fallback_shortcode('marketplace_listado', 'marketplace', 'listado');
        $this->register_fallback_shortcode('marketplace_formulario', 'marketplace', 'formulario');

        // === INCIDENCIAS ===
        $this->register_fallback_shortcode('incidencias_listado', 'incidencias', 'listado');
        $this->register_fallback_shortcode('incidencias_mis_incidencias', 'incidencias', 'mis-incidencias');
        $this->register_fallback_shortcode('incidencias_mapa', 'incidencias', 'mapa');
        $this->register_fallback_shortcode('incidencias_reportar', 'incidencias', 'reportar');

        // === CURSOS ===
        $this->register_fallback_shortcode('cursos_catalogo', 'cursos', 'catalogo');
        $this->register_fallback_shortcode('cursos_mis_cursos', 'cursos', 'mis-cursos');
        $this->register_fallback_shortcode('cursos_aula', 'cursos', 'aula');

        // === TALLERES ===
        $this->register_fallback_shortcode('proximos_talleres', 'talleres', 'listado');
        $this->register_fallback_shortcode('mis_inscripciones_talleres', 'talleres', 'mis-inscripciones');
        $this->register_fallback_shortcode('calendario_talleres', 'talleres', 'calendario');
        $this->register_fallback_shortcode('proponer_taller', 'talleres', 'proponer');

        // === HUERTOS URBANOS ===
        $this->register_fallback_shortcode('mapa_huertos', 'huertos-urbanos', 'mapa');
        $this->register_fallback_shortcode('mi_parcela', 'huertos-urbanos', 'mi-parcela');
        $this->register_fallback_shortcode('lista_huertos', 'huertos-urbanos', 'listado');
        $this->register_fallback_shortcode('calendario_cultivos', 'huertos-urbanos', 'calendario');
        $this->register_fallback_shortcode('intercambios_huertos', 'huertos-urbanos', 'intercambios');

        // === PARTICIPACIÓN ===
        $this->register_fallback_shortcode('propuestas_activas', 'participacion', 'propuestas');
        $this->register_fallback_shortcode('votacion_activa', 'participacion', 'votacion');
        $this->register_fallback_shortcode('crear_propuesta', 'participacion', 'crear');
        $this->register_fallback_shortcode('resultados_participacion', 'participacion', 'resultados');

        // === PRESUPUESTOS PARTICIPATIVOS ===
        $this->register_fallback_shortcode('presupuesto_participativo', 'presupuestos-participativos', 'dashboard');
        $this->register_fallback_shortcode('fases_participacion', 'presupuestos-participativos', 'fases');

        // === RECICLAJE ===
        $this->register_fallback_shortcode('reciclaje_mis_puntos', 'reciclaje', 'mis-puntos');
        $this->register_fallback_shortcode('reciclaje_puntos_cercanos', 'reciclaje', 'puntos-cercanos');
        $this->register_fallback_shortcode('reciclaje_ranking', 'reciclaje', 'ranking');
        $this->register_fallback_shortcode('reciclaje_guia', 'reciclaje', 'guia');
        $this->register_fallback_shortcode('reciclaje_recompensas', 'reciclaje', 'recompensas');

        // === COMPOSTAJE ===
        $this->register_fallback_shortcode('mis_aportaciones', 'compostaje', 'mis-aportaciones');
        $this->register_fallback_shortcode('estadisticas_compostaje', 'compostaje', 'estadisticas');
        $this->register_fallback_shortcode('mapa_composteras', 'compostaje', 'mapa');
        $this->register_fallback_shortcode('registrar_aportacion', 'compostaje', 'registrar');
        $this->register_fallback_shortcode('ranking_compostaje', 'compostaje', 'ranking');

        // === RED SOCIAL ===
        $this->register_fallback_shortcode('rs_perfil', 'red-social', 'perfil');
        $this->register_fallback_shortcode('rs_feed', 'red-social', 'feed');
        $this->register_fallback_shortcode('rs_explorar', 'red-social', 'explorar');
        $this->register_fallback_shortcode('rs_historias', 'red-social', 'historias');

        // === GRUPOS DE CONSUMO ===
        $this->register_fallback_shortcode('gc_ciclo_actual', 'grupos-consumo', 'ciclo-actual');
        $this->register_fallback_shortcode('gc_mi_pedido', 'grupos-consumo', 'mi-pedido');
        $this->register_fallback_shortcode('gc_productos', 'grupos-consumo', 'productos');
        $this->register_fallback_shortcode('gc_productores_cercanos', 'grupos-consumo', 'productores');
        $this->register_fallback_shortcode('gc_mi_cesta', 'grupos-consumo', 'mi-cesta');

        // === PARKINGS ===
        $this->register_fallback_shortcode('flavor_disponibilidad_parking', 'parkings', 'disponibilidad');
        $this->register_fallback_shortcode('flavor_mis_reservas_parking', 'parkings', 'mis-reservas');
        $this->register_fallback_shortcode('flavor_mapa_parkings', 'parkings', 'mapa');
        $this->register_fallback_shortcode('flavor_ocupacion_tiempo_real', 'parkings', 'ocupacion');
        $this->register_fallback_shortcode('flavor_solicitar_plaza', 'parkings', 'solicitar');

        // === CHAT ===
        $this->register_fallback_shortcode('flavor_chat_inbox', 'chat-interno', 'inbox');
        $this->register_fallback_shortcode('flavor_iniciar_chat', 'chat-interno', 'iniciar');
        $this->register_fallback_shortcode('flavor_chat_grupos', 'chat-interno', 'grupos');

        // === BICICLETAS COMPARTIDAS ===
        $this->register_fallback_shortcode('flavor_bicicletas_mapa', 'bicicletas-compartidas', 'mapa');
        $this->register_fallback_shortcode('flavor_bicicletas_mis_prestamos', 'bicicletas-compartidas', 'mis-prestamos');
        $this->register_fallback_shortcode('flavor_bicicletas_estaciones', 'bicicletas-compartidas', 'estaciones');
        $this->register_fallback_shortcode('flavor_bicicletas_alquilar', 'bicicletas-compartidas', 'alquilar');

        // === CARPOOLING ===
        $this->register_fallback_shortcode('flavor_carpooling_viajes', 'carpooling', 'viajes');
        $this->register_fallback_shortcode('flavor_carpooling_mis_viajes', 'carpooling', 'mis-viajes');
        $this->register_fallback_shortcode('flavor_carpooling_publicar', 'carpooling', 'publicar');
        $this->register_fallback_shortcode('flavor_carpooling_buscar', 'carpooling', 'buscar');

        // === BANCO TIEMPO ===
        $this->register_fallback_shortcode('flavor_banco_tiempo_servicios', 'banco-tiempo', 'servicios');
        $this->register_fallback_shortcode('flavor_banco_tiempo_mis_intercambios', 'banco-tiempo', 'mis-intercambios');
        $this->register_fallback_shortcode('flavor_banco_tiempo_ofrecer', 'banco-tiempo', 'ofrecer');
        $this->register_fallback_shortcode('flavor_banco_tiempo_mi_saldo', 'banco-tiempo', 'mi-saldo');

        // === COMUNIDADES ===
        $this->register_fallback_shortcode('flavor_comunidades_listado', 'comunidades', 'listado');
        $this->register_fallback_shortcode('flavor_comunidades_mis_comunidades', 'comunidades', 'mis-comunidades');
        $this->register_fallback_shortcode('flavor_comunidades_crear', 'comunidades', 'crear');
        $this->register_fallback_shortcode('flavor_comunidades_detalle', 'comunidades', 'detalle');

        // === PODCAST ===
        $this->register_fallback_shortcode('flavor_podcast_listado', 'podcast', 'listado');
        $this->register_fallback_shortcode('flavor_podcast_reproductor', 'podcast', 'reproductor');
        $this->register_fallback_shortcode('flavor_podcast_episodio', 'podcast', 'episodio');

        // === RADIO ===
        $this->register_fallback_shortcode('flavor_radio_player', 'radio', 'player');
        $this->register_fallback_shortcode('flavor_radio_programacion', 'radio', 'programacion');

        // === RED SOCIAL ===
        $this->register_fallback_shortcode('flavor_red_social_perfil', 'red-social', 'perfil');
        $this->register_fallback_shortcode('flavor_red_social_feed', 'red-social', 'feed');
        $this->register_fallback_shortcode('flavor_red_social_amigos', 'red-social', 'amigos');

        // === TRAMITES ===
        $this->register_fallback_shortcode('flavor_tramites_listado', 'tramites', 'listado');
        $this->register_fallback_shortcode('flavor_tramites_mis_tramites', 'tramites', 'mis-tramites');
        $this->register_fallback_shortcode('flavor_tramites_nuevo', 'tramites', 'nuevo');

        // === TRANSPARENCIA ===
        $this->register_fallback_shortcode('flavor_transparencia_portal', 'transparencia', 'portal');
        $this->register_fallback_shortcode('flavor_transparencia_contratos', 'transparencia', 'contratos');
        $this->register_fallback_shortcode('flavor_transparencia_presupuestos', 'transparencia', 'presupuestos');

        // === AVISOS MUNICIPALES ===
        $this->register_fallback_shortcode('flavor_avisos_listado', 'avisos-municipales', 'listado');
        $this->register_fallback_shortcode('flavor_avisos_suscripcion', 'avisos-municipales', 'suscripcion');

        // === DIRECTORIO ===
        $this->register_fallback_shortcode('flavor_directorio_buscar', 'directorio', 'buscar');
        $this->register_fallback_shortcode('flavor_directorio_categorias', 'directorio', 'categorias');

        // === VOLUNTARIADO ===
        $this->register_fallback_shortcode('flavor_voluntariado_oportunidades', 'voluntariado', 'oportunidades');
        $this->register_fallback_shortcode('flavor_voluntariado_mis_actividades', 'voluntariado', 'mis-actividades');
        $this->register_fallback_shortcode('flavor_voluntariado_inscribirse', 'voluntariado', 'inscribirse');

        // === ENCUESTAS ===
        $this->register_fallback_shortcode('flavor_encuestas_activas', 'encuestas', 'activas');
        $this->register_fallback_shortcode('flavor_encuestas_mis_respuestas', 'encuestas', 'mis-respuestas');
        $this->register_fallback_shortcode('flavor_encuestas_crear', 'encuestas', 'crear');

        // === FOROS ===
        $this->register_fallback_shortcode('flavor_foros_listado', 'foros', 'listado');
        $this->register_fallback_shortcode('flavor_foros_tema', 'foros', 'tema');
        $this->register_fallback_shortcode('flavor_foros_crear_tema', 'foros', 'crear-tema');

        // === EMPLEO ===
        $this->register_fallback_shortcode('flavor_empleo_ofertas', 'empleo', 'ofertas');
        $this->register_fallback_shortcode('flavor_empleo_mis_candidaturas', 'empleo', 'mis-candidaturas');
        $this->register_fallback_shortcode('flavor_empleo_publicar', 'empleo', 'publicar');

        // === SERVICIOS SOCIALES ===
        $this->register_fallback_shortcode('flavor_servicios_sociales_recursos', 'servicios-sociales', 'recursos');
        $this->register_fallback_shortcode('flavor_servicios_sociales_solicitar', 'servicios-sociales', 'solicitar');

        // === MONEDA LOCAL ===
        $this->register_fallback_shortcode('flavor_moneda_local_saldo', 'moneda-local', 'saldo');
        $this->register_fallback_shortcode('flavor_moneda_local_transferir', 'moneda-local', 'transferir');
        $this->register_fallback_shortcode('flavor_moneda_local_comercios', 'moneda-local', 'comercios');

        // === FICHAJE ===
        $this->register_fallback_shortcode('flavor_fichaje_fichar', 'fichaje', 'fichar');
        $this->register_fallback_shortcode('flavor_fichaje_historial', 'fichaje', 'historial');
        $this->register_fallback_shortcode('flavor_fichaje_resumen', 'fichaje', 'resumen');

        // === ADVERTISING ===
        $this->register_fallback_shortcode('flavor_advertising_banner', 'advertising', 'banner');
        $this->register_fallback_shortcode('flavor_advertising_mis_campanas', 'advertising', 'mis-campanas');
    }

    /**
     * Renderiza un template de shortcode de módulo
     */
    private function render_template_shortcode($module_slug, $template_name, $atts) {
        $atts = shortcode_atts([
            'limit' => '12',
            'columnas' => '3',
            'id' => '',
        ], $atts);

        // Verificar login para vistas personales
        if (strpos($template_name, 'mis-') === 0 && !is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Buscar template en varias ubicaciones
        $template_paths = [
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/templates/{$template_name}.php",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/{$template_name}.php",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/{$template_name}.php",
        ];

        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                ob_start();
                // Variables disponibles en el template
                $usuario_id = get_current_user_id();
                $espacio_id = !empty($atts['id']) ? intval($atts['id']) : (isset($_GET['espacio_id']) ? intval($_GET['espacio_id']) : 0);
                $evento_id = !empty($atts['id']) ? intval($atts['id']) : (isset($_GET['evento_id']) ? intval($_GET['evento_id']) : 0);
                $item_id = !empty($atts['id']) ? intval($atts['id']) : 0;
                $limit = intval($atts['limit']);
                $columnas = intval($atts['columnas']);
                include $path;
                return '<div class="flavor-shortcode-wrapper flavor-' . esc_attr($module_slug) . '-' . esc_attr($template_name) . '">' . ob_get_clean() . '</div>';
            }
        }

        // Si no existe template, mostrar mensaje de próximamente
        return $this->render_coming_soon_message($module_slug, $template_name);
    }

    /**
     * Renderiza mensaje de próximamente
     */
    private function render_coming_soon_message($module_slug, $template_name) {
        $module_name = ucwords(str_replace('-', ' ', $module_slug));
        $view_name = ucwords(str_replace('-', ' ', $template_name));

        return sprintf(
            '<div class="flavor-coming-soon">
                <span class="dashicons dashicons-clock"></span>
                <h4>%s - %s</h4>
                <p>%s</p>
            </div>
            <style>
            .flavor-coming-soon { text-align: center; padding: 2rem; background: linear-gradient(135deg, #f0f9ff 0%%, #e0f2fe 100%%); border-radius: 12px; border: 1px dashed #0ea5e9; }
            .flavor-coming-soon .dashicons { font-size: 36px; width: 36px; height: 36px; color: #0ea5e9; margin-bottom: 0.5rem; }
            .flavor-coming-soon h4 { margin: 0.5rem 0; color: #0369a1; }
            .flavor-coming-soon p { margin: 0; color: #64748b; font-size: 0.875rem; }
            </style>',
            esc_html($module_name),
            esc_html($view_name),
            __('Esta funcionalidad estará disponible próximamente', 'flavor-chat-ia')
        );
    }

    /**
     * Renderiza un listado de módulo
     * Uso: [flavor_module_listing module="participacion" action="listar_propuestas" columnas="2" limite="12"]
     */
    public function render_module_listing($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => 'listar',
            'vista' => 'grid',
            'limite' => '12',
            'columnas' => '3',
            'mostrar_filtros' => 'no',
            'mostrar_fecha' => 'yes',
            'mostrar_precio' => 'no',
            'tipo' => '',
            'color' => '#4f46e5',
        ], $atts);

        if (empty($atts['module'])) {
            return '<div class="flavor-error">' . __('Error: Debes especificar el módulo', 'flavor-chat-ia') . '</div>';
        }

        // Normalizar nombre del módulo
        $modulo_id = str_replace('-', '_', sanitize_key($atts['module']));

        // Obtener instancia del módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return '<div class="flavor-error">' . __('Error: Module Loader no disponible', 'flavor-chat-ia') . '</div>';
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($modulo_id);

        if (!$instance) {
            // Intentar con guiones
            $modulo_id_alt = str_replace('_', '-', $modulo_id);
            $instance = $loader->get_module($modulo_id_alt);

            if (!$instance) {
                return '<div class="flavor-error">' . sprintf(__('Módulo "%s" no encontrado o no activo', 'flavor-chat-ia'), esc_html($atts['module'])) . '</div>';
            }
            $modulo_id = $modulo_id_alt;
        }

        // Usar el mismo sistema de renderizado que los shortcodes automáticos
        return $this->render_module_shortcode($modulo_id, $instance, $atts);
    }

    /**
     * Registra shortcodes para todos los módulos activos
     */
    public function register_module_shortcodes() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos = $loader->get_loaded_modules();

        foreach ($modulos as $id => $instance) {
            $id_normalizado = str_replace('_', '-', $id);

            // Registrar shortcode: [flavor_{modulo_id}]
            $shortcode_name = 'flavor_' . $id;
            add_shortcode($shortcode_name, function($atts) use ($id, $instance) {
                return $this->render_module_shortcode($id, $instance, $atts);
            });

            // Registrar shortcode de acciones: [flavor_{modulo}_acciones]
            add_shortcode('flavor_' . $id . '_acciones', function($atts) use ($id, $instance) {
                return $this->render_module_acciones($id, $instance, $atts);
            });
            add_shortcode('flavor_' . $id_normalizado . '_acciones', function($atts) use ($id, $instance) {
                return $this->render_module_acciones($id, $instance, $atts);
            });

            // Registrar shortcodes de vistas comunes
            $this->register_common_view_shortcodes($id, $id_normalizado, $instance);
        }
    }

    /**
     * Registra shortcodes de vistas comunes para un módulo
     */
    private function register_common_view_shortcodes($id, $id_normalizado, $instance) {
        // Vistas comunes que deberían tener shortcodes
        $vistas_comunes = [
            'listado', 'calendario', 'mapa', 'catalogo',
            'mis_inscripciones', 'mis_reservas', 'mis_prestamos',
            'mis_anuncios', 'mis_cursos', 'mis_viajes', 'mis_incidencias',
            'buscar', 'formulario', 'crear', 'reportar'
        ];

        foreach ($vistas_comunes as $vista) {
            $vista_normalizada = str_replace('_', '-', $vista);

            // [eventos_listado] -> [flavor_module_listing module="eventos" vista="listado"]
            add_shortcode($id_normalizado . '_' . $vista_normalizada, function($atts) use ($id, $instance, $vista) {
                $atts = shortcode_atts(['limit' => '12', 'columnas' => '3'], $atts);
                return $this->render_module_view($id, $instance, $vista, $atts);
            });

            // También con guiones bajos
            add_shortcode($id . '_' . $vista, function($atts) use ($id, $instance, $vista) {
                $atts = shortcode_atts(['limit' => '12', 'columnas' => '3'], $atts);
                return $this->render_module_view($id, $instance, $vista, $atts);
            });
        }
    }

    /**
     * Renderiza una vista específica de un módulo
     */
    private function render_module_view($module_id, $instance, $vista, $atts) {
        // Verificar si el usuario está logueado para vistas personales
        if (strpos($vista, 'mis_') === 0 && !is_user_logged_in()) {
            return $this->render_login_required_message();
        }

        // Intentar cargar template específico del módulo
        $module_slug = str_replace('_', '-', $module_id);
        $vista_slug = str_replace('_', '-', $vista);

        $template_paths = [
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/templates/{$vista_slug}.php",
            FLAVOR_CHAT_IA_PATH . "includes/modules/{$module_slug}/frontend/{$vista_slug}.php",
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$module_slug}/{$vista_slug}.php",
        ];

        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                ob_start();
                $module = $instance;
                $usuario_id = get_current_user_id();
                include $path;
                return ob_get_clean();
            }
        }

        // Si no hay template, usar render genérico
        return $this->render_module_shortcode($module_id, $instance, array_merge($atts, ['vista' => $vista]));
    }

    /**
     * Renderiza las acciones de un módulo
     */
    private function render_module_acciones($module_id, $instance, $atts) {
        $atts = shortcode_atts([
            'layout' => 'buttons',
            'mostrar' => '', // Filtrar acciones específicas
        ], $atts);

        // Si el módulo tiene el trait de acciones, usarlo
        if (method_exists($instance, 'get_frontend_actions')) {
            $acciones = $instance->get_frontend_actions();
        } else {
            // Usar acciones por defecto según el módulo
            $acciones = $this->get_default_module_actions($module_id);
        }

        if (empty($acciones)) {
            return '';
        }

        // Filtrar acciones si se especifica
        if (!empty($atts['mostrar'])) {
            $filtrar = array_map('trim', explode(',', $atts['mostrar']));
            $acciones = array_intersect_key($acciones, array_flip($filtrar));
        }

        ob_start();
        $this->render_acciones_html($module_id, $acciones, $atts['layout']);
        return ob_get_clean();
    }

    /**
     * Obtiene acciones por defecto para un módulo
     */
    private function get_default_module_actions($module_id) {
        $module_slug = str_replace('_', '-', $module_id);

        $acciones_por_modulo = [
            'eventos' => [
                'inscribirse' => ['label' => __('Inscribirse', 'flavor-chat-ia'), 'icon' => 'dashicons-yes-alt', 'url' => '#inscribirse'],
                'ver_calendario' => ['label' => __('Calendario', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar-alt', 'url' => '/mi-portal/eventos/calendario'],
                'mis_inscripciones' => ['label' => __('Mis Inscripciones', 'flavor-chat-ia'), 'icon' => 'dashicons-tickets-alt', 'url' => '/mi-portal/eventos/mis-inscripciones'],
            ],
            'espacios-comunes' => [
                'reservar' => ['label' => __('Reservar', 'flavor-chat-ia'), 'icon' => 'dashicons-calendar', 'url' => '#reservar'],
                'mis_reservas' => ['label' => __('Mis Reservas', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/espacios-comunes/mis-reservas'],
                'ver_disponibilidad' => ['label' => __('Disponibilidad', 'flavor-chat-ia'), 'icon' => 'dashicons-visibility', 'url' => '/mi-portal/espacios-comunes/calendario'],
            ],
            'biblioteca' => [
                'buscar' => ['label' => __('Buscar Libro', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'url' => '/mi-portal/biblioteca'],
                'mis_prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-book', 'url' => '/mi-portal/biblioteca/mis-prestamos'],
            ],
            'bicicletas-compartidas' => [
                'alquilar' => ['label' => __('Alquilar Bici', 'flavor-chat-ia'), 'icon' => 'dashicons-unlock', 'url' => '/mi-portal/bicicletas-compartidas'],
                'devolver' => ['label' => __('Devolver', 'flavor-chat-ia'), 'icon' => 'dashicons-lock', 'url' => '#devolver'],
                'mis_prestamos' => ['label' => __('Mis Préstamos', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/bicicletas-compartidas/mis-prestamos'],
            ],
            'incidencias' => [
                'reportar' => ['label' => __('Reportar Incidencia', 'flavor-chat-ia'), 'icon' => 'dashicons-flag', 'url' => '/mi-portal/incidencias/reportar'],
                'mis_reportes' => ['label' => __('Mis Reportes', 'flavor-chat-ia'), 'icon' => 'dashicons-list-view', 'url' => '/mi-portal/incidencias/mis-incidencias'],
            ],
            'marketplace' => [
                'publicar' => ['label' => __('Publicar Anuncio', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'url' => '/mi-portal/marketplace/publicar'],
                'mis_anuncios' => ['label' => __('Mis Anuncios', 'flavor-chat-ia'), 'icon' => 'dashicons-megaphone', 'url' => '/mi-portal/marketplace/mis-anuncios'],
            ],
            'banco-tiempo' => [
                'ofrecer' => ['label' => __('Ofrecer Servicio', 'flavor-chat-ia'), 'icon' => 'dashicons-plus-alt', 'url' => '/mi-portal/banco-tiempo/ofrecer'],
                'buscar' => ['label' => __('Buscar Servicio', 'flavor-chat-ia'), 'icon' => 'dashicons-search', 'url' => '/mi-portal/banco-tiempo'],
                'mis_intercambios' => ['label' => __('Mis Intercambios', 'flavor-chat-ia'), 'icon' => 'dashicons-randomize', 'url' => '/mi-portal/banco-tiempo/mis-intercambios'],
            ],
        ];

        return $acciones_por_modulo[$module_slug] ?? [];
    }

    /**
     * Renderiza HTML de acciones
     */
    private function render_acciones_html($module_id, $acciones, $layout) {
        $module_slug = str_replace('_', '-', $module_id);
        ?>
        <div class="flavor-module-actions flavor-module-actions--<?php echo esc_attr($layout); ?>" data-module="<?php echo esc_attr($module_id); ?>">
            <?php foreach ($acciones as $action_id => $action) : ?>
                <a href="<?php echo esc_url($action['url'] ?? '#'); ?>"
                   class="flavor-action-btn"
                   data-action="<?php echo esc_attr($action_id); ?>">
                    <span class="dashicons <?php echo esc_attr($action['icon'] ?? 'dashicons-yes'); ?>"></span>
                    <span class="flavor-action-btn__label"><?php echo esc_html($action['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <style>
        .flavor-module-actions { display: flex; flex-wrap: wrap; gap: 0.75rem; margin: 1rem 0; }
        .flavor-action-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.25rem; background: #f3f4f6; border-radius: 8px; text-decoration: none; color: #374151; font-weight: 500; transition: all 0.2s; }
        .flavor-action-btn:hover { background: #4f46e5; color: white; }
        .flavor-action-btn .dashicons { font-size: 18px; width: 18px; height: 18px; }
        </style>
        <?php
    }

    /**
     * Renderiza mensaje de login requerido
     */
    private function render_login_required_message() {
        $login_url = wp_login_url(get_permalink());
        return sprintf(
            '<div class="flavor-login-required">
                <span class="dashicons dashicons-lock"></span>
                <p>%s</p>
                <a href="%s" class="flavor-button">%s</a>
            </div>
            <style>
            .flavor-login-required { text-align: center; padding: 2rem; background: #f9fafb; border-radius: 12px; }
            .flavor-login-required .dashicons { font-size: 48px; width: 48px; height: 48px; color: #9ca3af; margin-bottom: 1rem; }
            .flavor-login-required p { color: #6b7280; margin-bottom: 1rem; }
            .flavor-login-required .flavor-button { display: inline-block; padding: 0.75rem 1.5rem; background: #4f46e5; color: white; border-radius: 8px; text-decoration: none; }
            </style>',
            __('Inicia sesión para ver tu contenido personal', 'flavor-chat-ia'),
            esc_url($login_url),
            __('Iniciar sesión', 'flavor-chat-ia')
        );
    }

    /**
     * Maneja el envío de formularios vía AJAX
     */
    public function handle_form_submission() {
        // Verificar nonce
        $module_id = sanitize_text_field($_POST['flavor_module'] ?? '');

        if (!check_ajax_referer('flavor_module_action_' . $module_id, 'flavor_nonce', false)) {
            wp_send_json_error(__('Token de seguridad inválido', 'flavor-chat-ia'));
            return;
        }

        $action = sanitize_text_field($_POST['flavor_action'] ?? '');

        if (empty($module_id) || empty($action)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-chat-ia'));
            return;
        }

        // Obtener módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            wp_send_json_error(__('Sistema no disponible', 'flavor-chat-ia'));
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($module_id);

        if (!$instance) {
            wp_send_json_error(__('Módulo no encontrado', 'flavor-chat-ia'));
            return;
        }

        // Verificar que el usuario tenga permisos
        if (class_exists('Flavor_Module_Access_Control')) {
            $control = Flavor_Module_Access_Control::get_instance();
            if (!$control->user_can_access($module_id)) {
                wp_send_json_error(__('No tienes permisos para esta acción', 'flavor-chat-ia'));
                return;
            }
        }

        // Preparar parámetros
        $params = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'flavor_module', 'flavor_action', 'flavor_nonce'])) {
                $params[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
        }

        // Ejecutar acción del módulo
        try {
            if (method_exists($instance, 'execute_action')) {
                $result = $instance->execute_action($action, $params);
            } else {
                $result = ['success' => false, 'message' => __('Módulo no soporta acciones', 'flavor-chat-ia')];
            }

            if (is_array($result)) {
                if ($result['success'] ?? false) {
                    wp_send_json_success([
                        'message' => $result['message'] ?? __('Operación completada', 'flavor-chat-ia'),
                        'data' => $result['data'] ?? [],
                        'redirect' => $result['redirect'] ?? '',
                    ]);
                } else {
                    wp_send_json_error($result['message'] ?? __('Error al procesar', 'flavor-chat-ia'));
                }
            } else {
                wp_send_json_error(__('Respuesta inválida del módulo', 'flavor-chat-ia'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Renderiza un formulario de módulo
     * Uso: [flavor_module_form module="eventos" action="inscribirse_evento"]
     */
    public function render_module_form($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => '',
            'titulo' => '',
            'descripcion' => '',
            'mostrar_titulo' => 'yes',
        ], $atts);

        if (empty($atts['module']) || empty($atts['action'])) {
            return '<div class="flavor-error">' . __('Error: Debes especificar module y action', 'flavor-chat-ia') . '</div>';
        }

        // Obtener instancia del módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return '<div class="flavor-error">' . __('Error: Module Loader no disponible', 'flavor-chat-ia') . '</div>';
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($atts['module']);

        if (!$instance) {
            return '<div class="flavor-error">' . sprintf(__('Error: Módulo "%s" no encontrado', 'flavor-chat-ia'), $atts['module']) . '</div>';
        }

        // Verificar que el módulo tenga el método get_form_config
        if (!method_exists($instance, 'get_form_config')) {
            return '<div class="flavor-error">' . __('Error: Este módulo no soporta formularios', 'flavor-chat-ia') . '</div>';
        }

        // Obtener configuración del formulario
        $form_config = $instance->get_form_config($atts['action']);

        if (!$form_config) {
            return '<div class="flavor-error">' . sprintf(__('Error: Acción "%s" no tiene configuración de formulario', 'flavor-chat-ia'), $atts['action']) . '</div>';
        }

        // Preparar datos del formulario
        $form_data = [
            'module_id' => $atts['module'],
            'action' => $atts['action'],
            'title' => $atts['titulo'] ?: ($form_config['title'] ?? ucfirst($atts['action'])),
            'description' => $atts['descripcion'] ?: ($form_config['description'] ?? ''),
            'fields' => $form_config['fields'] ?? [],
            'submit_text' => $form_config['submit_text'] ?? __('Enviar', 'flavor-chat-ia'),
            'ajax' => $form_config['ajax'] ?? true,
        ];

        // Renderizar el formulario
        ob_start();
        $this->render_form_html($form_data, $atts);
        return ob_get_clean();
    }

    /**
     * Renderiza el HTML del formulario
     */
    private function render_form_html($form_data, $atts) {
        ?>
        <div class="flavor-module-form-wrapper">
            <?php if ($atts['mostrar_titulo'] === 'yes' && !empty($form_data['title'])) : ?>
                <div class="flavor-form-header">
                    <h3 class="flavor-form-title"><?php echo esc_html($form_data['title']); ?></h3>
                    <?php if (!empty($form_data['description'])) : ?>
                        <p class="flavor-form-description"><?php echo esc_html($form_data['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="flavor-module-form"
                  data-module="<?php echo esc_attr($form_data['module_id']); ?>"
                  data-action="<?php echo esc_attr($form_data['action']); ?>"
                  data-ajax="<?php echo $form_data['ajax'] ? '1' : '0'; ?>"
                  method="post">

                <?php wp_nonce_field('flavor_module_action_' . $form_data['module_id'], 'flavor_nonce'); ?>
                <input type="hidden" name="flavor_module" value="<?php echo esc_attr($form_data['module_id']); ?>">
                <input type="hidden" name="flavor_action" value="<?php echo esc_attr($form_data['action']); ?>">

                <div class="flavor-form-fields">
                    <?php foreach ($form_data['fields'] as $field_name => $field_config) : ?>
                        <?php $this->render_form_field($field_name, $field_config); ?>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-form-messages"></div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-button flavor-button--primary">
                        <?php echo esc_html($form_data['submit_text']); ?>
                    </button>
                </div>
            </form>
        </div>

        <style>
        .flavor-module-form-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .flavor-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .flavor-form-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
        }
        .flavor-form-description {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        .flavor-form-fields {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .flavor-form-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .flavor-form-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .flavor-form-label--required::after {
            content: " *";
            color: #ef4444;
        }
        .flavor-form-input,
        .flavor-form-textarea,
        .flavor-form-select {
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .flavor-form-input:focus,
        .flavor-form-textarea:focus,
        .flavor-form-select:focus {
            outline: none;
            border-color: var(--flavor-primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .flavor-form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .flavor-form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        .flavor-form-messages {
            margin: 20px 0;
        }
        .flavor-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .flavor-message--success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .flavor-message--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .flavor-form-actions {
            margin-top: 24px;
        }
        .flavor-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }
        .flavor-button--primary {
            background: var(--flavor-primary, #3b82f6);
            color: white;
        }
        .flavor-button--primary:hover {
            background: var(--flavor-primary-dark, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .flavor-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 640px) {
            .flavor-module-form-wrapper {
                padding: 20px;
            }
            .flavor-form-title {
                font-size: 24px;
            }
        }
        </style>

        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('.flavor-module-form');

                forms.forEach(function(form) {
                    if (form.dataset.ajax !== '1') return;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const button = form.querySelector('button[type="submit"]');
                        const messages = form.querySelector('.flavor-form-messages');
                        const formData = new FormData(form);

                        formData.append('action', 'flavor_module_action');

                        button.disabled = true;
                        button.textContent = '<?php _e('Enviando...', 'flavor-chat-ia'); ?>';
                        messages.innerHTML = '';

                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messages.innerHTML = '<div class="flavor-message flavor-message--success">' +
                                    (data.data.message || '<?php _e('Operación completada con éxito', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                                form.reset();

                                // Redirigir si se especifica
                                if (data.data.redirect) {
                                    setTimeout(function() {
                                        window.location.href = data.data.redirect;
                                    }, 1500);
                                }
                            } else {
                                messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                    (data.data || '<?php _e('Error al procesar el formulario', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                            }
                        })
                        .catch(error => {
                            messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                '<?php _e('Error de conexión', 'flavor-chat-ia'); ?>' +
                                '</div>';
                        })
                        .finally(function() {
                            button.disabled = false;
                            button.textContent = '<?php echo esc_js($form_data['submit_text'] ?? __('Enviar', 'flavor-chat-ia')); ?>';
                        });
                    });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderiza un campo del formulario
     */
    private function render_form_field($field_name, $field_config) {
        $type = $field_config['type'] ?? 'text';
        $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
        $required = $field_config['required'] ?? false;
        $placeholder = $field_config['placeholder'] ?? '';
        $help = $field_config['help'] ?? '';
        $options = $field_config['options'] ?? [];
        $value = $field_config['value'] ?? '';

        ?>
        <div class="flavor-form-field">
            <label for="flavor_field_<?php echo esc_attr($field_name); ?>"
                   class="flavor-form-label <?php echo $required ? 'flavor-form-label--required' : ''; ?>">
                <?php echo esc_html($label); ?>
            </label>

            <?php if ($type === 'textarea') : ?>
                <textarea
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-textarea"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['rows'])) : ?>rows="<?php echo intval($field_config['rows']); ?>"<?php endif; ?>
                ><?php echo esc_textarea($value); ?></textarea>

            <?php elseif ($type === 'select') : ?>
                <select
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-select"
                    <?php echo $required ? 'required' : ''; ?>>
                    <?php if (!$required || $placeholder) : ?>
                        <option value=""><?php echo esc_html($placeholder ?: __('Selecciona una opción', 'flavor-chat-ia')); ?></option>
                    <?php endif; ?>
                    <?php foreach ($options as $opt_value => $opt_label) : ?>
                        <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                            <?php echo esc_html($opt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php else : ?>
                <input
                    type="<?php echo esc_attr($type); ?>"
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-input"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['min'])) : ?>min="<?php echo esc_attr($field_config['min']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['max'])) : ?>max="<?php echo esc_attr($field_config['max']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['step'])) : ?>step="<?php echo esc_attr($field_config['step']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['pattern'])) : ?>pattern="<?php echo esc_attr($field_config['pattern']); ?>"<?php endif; ?>
                >
            <?php endif; ?>

            <?php if ($help) : ?>
                <span class="flavor-form-help"><?php echo esc_html($help); ?></span>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el shortcode de un módulo
     */
    private function render_module_shortcode($modulo_id, $instance, $atts) {
        $atts = shortcode_atts([
            'vista' => 'grid', // grid, lista, calendario
            'limite' => '12',
            'columnas' => '3',
            'mostrar_filtros' => 'yes',
            'mostrar_fecha' => 'yes',
            'mostrar_precio' => 'no',
            'tipo' => '', // Para filtrar por tipo específico
            'color' => '#4f46e5',
        ], $atts);

        // Preparar datos para el template
        $data = $this->prepare_module_data($modulo_id, $instance, $atts);

        // Buscar template del módulo en varias ubicaciones
        $template_paths = $this->get_template_paths($modulo_id, $atts['vista']);

        $template = null;
        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                $template = $path;
                break;
            }
        }

        // Renderizar template
        ob_start();

        // Hacer disponibles las variables en el scope del template
        extract($data);

        // Variables adicionales para templates genéricos
        $titulo = $data['modulo_nombre'];
        $items = $data['items'];
        $columnas = intval($atts['columnas']);
        $color_primario = $atts['color'];
        $mostrar_fecha = $atts['mostrar_fecha'] === 'yes';
        $mostrar_precio = $atts['mostrar_precio'] === 'yes';
        $mostrar_imagen = true;
        $mostrar_descripcion = true;
        $estilo = 'cards';

        // Wrapper para estilos consistentes
        echo '<div class="flavor-module-shortcode flavor-module-' . esc_attr($modulo_id) . '">';

        if ($template) {
            include $template;
        } else {
            // Usar el Component Renderer del Web Builder Pro si está disponible
            echo $this->render_with_component_renderer($modulo_id, $data, $atts);
        }

        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Obtiene las rutas de template posibles para un módulo
     *
     * @param string $modulo_id ID del módulo
     * @param string $vista Tipo de vista (grid, lista, calendario)
     * @return array Lista de rutas de template
     */
    private function get_template_paths($modulo_id, $vista) {
        $paths = [];

        // Normalizar IDs para soportar guiones y guiones bajos
        $modulo_guiones = str_replace('_', '-', $modulo_id);
        $modulo_guiones_bajos = str_replace('-', '_', $modulo_id);

        // 1. Templates en el directorio views del módulo (includes/modules/{modulo}/views/)
        $paths[] = FLAVOR_CHAT_IA_PATH . "includes/modules/{$modulo_guiones}/views/{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "includes/modules/{$modulo_guiones_bajos}/views/{$vista}.php";

        // 2. Template específico del módulo en el plugin principal
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/{$modulo_id}-{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_guiones}/{$vista}.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/grid.php";
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/frontend/{$modulo_id}/archive.php";

        // 3. Template genérico en el plugin principal
        $paths[] = FLAVOR_CHAT_IA_PATH . "templates/components/unified/_generic-grid.php";

        // 4. Templates del Web Builder Pro addon
        if (defined('FLAVOR_WEB_BUILDER_PATH')) {
            $paths[] = FLAVOR_WEB_BUILDER_PATH . "templates/components/{$modulo_id}/{$vista}.php";
            $paths[] = FLAVOR_WEB_BUILDER_PATH . "templates/components/landings/_generic-grid.php";
        }

        return $paths;
    }

    /**
     * Renderiza usando el Component Renderer del Web Builder Pro
     *
     * @param string $modulo_id ID del módulo
     * @param array $data Datos del módulo
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    private function render_with_component_renderer($modulo_id, $data, $atts) {
        // Verificar si Web Builder Pro está disponible
        if (!class_exists('Flavor_Component_Renderer')) {
            return $this->render_fallback($modulo_id, null, $atts);
        }

        $renderer = Flavor_Component_Renderer::get_instance();

        // Preparar datos para el componente unificado
        $component_data = [
            'titulo' => $data['modulo_nombre'],
            'items' => $data['items'],
            'columnas' => intval($atts['columnas']),
            'color_primario' => $atts['color'],
            'mostrar_fecha' => $atts['mostrar_fecha'] === 'yes',
            'mostrar_precio' => $atts['mostrar_precio'] === 'yes',
            'mostrar_imagen' => true,
            'mostrar_descripcion' => true,
            'estilo' => 'cards',
            'btn_texto' => '',
            'btn_url' => '',
        ];

        // Intentar renderizar el componente unified_grid
        ob_start();
        $renderer->render_component('unified_grid', $component_data, [
            'custom_class' => 'flavor-module-' . sanitize_html_class($modulo_id),
        ]);
        $output = ob_get_clean();

        // Si el componente no existe, usar template genérico directo
        if (empty(trim($output)) || strpos($output, 'error') !== false) {
            return $this->render_generic_grid($data, $atts);
        }

        return $output;
    }

    /**
     * Renderiza usando el template genérico de grid directamente
     *
     * @param array $data Datos del módulo
     * @param array $atts Atributos del shortcode
     * @return string HTML renderizado
     */
    private function render_generic_grid($data, $atts) {
        // Variables para el template
        $titulo = $data['modulo_nombre'];
        $subtitulo = '';
        $items = $data['items'];
        $columnas = intval($atts['columnas']);
        $mostrar_imagen = true;
        $mostrar_descripcion = true;
        $mostrar_fecha = $atts['mostrar_fecha'] === 'yes';
        $mostrar_precio = $atts['mostrar_precio'] === 'yes';
        $color_primario = $atts['color'];
        $btn_texto = '';
        $btn_url = '';
        $estilo = 'cards';

        // Buscar template genérico
        $template_path = null;
        if (defined('FLAVOR_WEB_BUILDER_PATH')) {
            $template_path = FLAVOR_WEB_BUILDER_PATH . 'templates/components/landings/_generic-grid.php';
        }

        if ($template_path && file_exists($template_path)) {
            ob_start();
            include $template_path;
            return ob_get_clean();
        }

        // Fallback último recurso
        return $this->render_fallback($data['modulo_id'], null, $atts);
    }

    /**
     * Prepara los datos del módulo para el template
     */
    private function prepare_module_data($modulo_id, $instance, $atts) {
        global $wpdb;

        $data = [
            'modulo_id' => $modulo_id,
            'modulo_nombre' => $instance->name ?? ucfirst(str_replace('_', ' ', $modulo_id)),
            'atts' => $atts,
            'items' => [],
        ];

        // Intentar obtener datos del módulo
        $tabla = $wpdb->prefix . 'flavor_' . $modulo_id;

        if ($this->tabla_existe($tabla)) {
            $limite = intval($atts['limite']);
            $tipo = sanitize_text_field($atts['tipo']);

            $where = "WHERE 1=1";
            $params = [];

            // Filtro por estado publicado si existe la columna
            $columns = $wpdb->get_col("DESCRIBE {$tabla}");
            if (in_array('estado', $columns)) {
                $where .= " AND estado = %s";
                $params[] = 'publicado';
            }

            // Filtro por tipo si se especifica
            if (!empty($tipo) && in_array('tipo', $columns)) {
                $where .= " AND tipo = %s";
                $params[] = $tipo;
            }

            // Filtro por fecha futura para eventos
            if ($modulo_id === 'eventos' && in_array('fecha_inicio', $columns)) {
                $where .= " AND fecha_inicio >= %s";
                $params[] = current_time('mysql');
            }

            // Orden
            $order = "ORDER BY id DESC";
            if (in_array('fecha_inicio', $columns)) {
                $order = "ORDER BY fecha_inicio ASC";
            } elseif (in_array('fecha_creacion', $columns)) {
                $order = "ORDER BY fecha_creacion DESC";
            } elseif (in_array('created_at', $columns)) {
                $order = "ORDER BY created_at DESC";
            }

            $query = "SELECT * FROM {$tabla} {$where} {$order} LIMIT {$limite}";

            if (!empty($params)) {
                $query = $wpdb->prepare($query, ...$params);
            }

            $data['items'] = $wpdb->get_results($query, ARRAY_A);
        }

        // Si no hay datos reales, proporcionar datos de ejemplo según el módulo
        if (empty($data['items'])) {
            $data['items'] = $this->get_sample_data($modulo_id);
        }

        return $data;
    }

    /**
     * Verifica si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $tabla);
        return $wpdb->get_var($query) === $tabla;
    }

    /**
     * Obtiene datos de ejemplo para un módulo
     */
    private function get_sample_data($modulo_id) {
        // Normalizar ID de módulo
        $modulo_normalizado = str_replace('-', '_', $modulo_id);

        switch ($modulo_normalizado) {
            case 'eventos':
                return [
                    ['id'=>1, 'titulo'=>'Conferencia de Tecnología', 'tipo'=>'conferencia', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+3 days')), 'ubicacion'=>'Centro de Convenciones', 'precio'=>15.00, 'aforo_maximo'=>100, 'inscritos_count'=>45, 'estado'=>'publicado', 'descripcion'=>'Últimas tendencias en tecnología', 'icon'=>'dashicons-calendar-alt'],
                    ['id'=>2, 'titulo'=>'Taller de Cerámica', 'tipo'=>'taller', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'ubicacion'=>'Sala de Artes', 'precio'=>25.00, 'aforo_maximo'=>20, 'inscritos_count'=>18, 'estado'=>'publicado', 'descripcion'=>'Técnicas de cerámica artesanal', 'icon'=>'dashicons-art'],
                    ['id'=>3, 'titulo'=>'Charla: Alimentación Saludable', 'tipo'=>'charla', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'ubicacion'=>'Biblioteca', 'precio'=>0, 'aforo_maximo'=>50, 'inscritos_count'=>12, 'estado'=>'publicado', 'descripcion'=>'Consejos para una dieta equilibrada', 'icon'=>'dashicons-heart'],
                ];

            case 'talleres':
                return [
                    ['id'=>1, 'titulo'=>'Taller de Fotografía', 'tipo'=>'Arte', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'plazas_disponibles'=>15, 'precio'=>30.00, 'descripcion'=>'Aprende técnicas de fotografía digital', 'icon'=>'dashicons-camera'],
                    ['id'=>2, 'titulo'=>'Cocina Mediterránea', 'tipo'=>'Gastronomía', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+10 days')), 'plazas_disponibles'=>12, 'precio'=>40.00, 'descripcion'=>'Recetas tradicionales del Mediterráneo', 'icon'=>'dashicons-carrot'],
                    ['id'=>3, 'titulo'=>'Yoga para Principiantes', 'tipo'=>'Bienestar', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'plazas_disponibles'=>20, 'precio'=>15.00, 'descripcion'=>'Inicia tu práctica de yoga', 'icon'=>'dashicons-universal-access'],
                ];

            case 'grupos_consumo':
                return [
                    ['id'=>1, 'titulo'=>'Grupo Ecológico Norte', 'descripcion'=>'Productos ecológicos y de proximidad de agricultores locales', 'miembros'=>25, 'icon'=>'dashicons-carrot'],
                    ['id'=>2, 'titulo'=>'Cooperativa Sur', 'descripcion'=>'Compra conjunta de productos frescos y de temporada', 'miembros'=>40, 'icon'=>'dashicons-store'],
                    ['id'=>3, 'titulo'=>'Huerta Compartida', 'descripcion'=>'Verduras de nuestro propio huerto comunitario', 'miembros'=>18, 'icon'=>'dashicons-palmtree'],
                ];

            case 'bicicletas_compartidas':
                return [
                    ['id'=>1, 'titulo'=>'Estación Centro', 'descripcion'=>'Plaza Mayor, 12 bicicletas disponibles', 'disponibles'=>12, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                    ['id'=>2, 'titulo'=>'Estación Parque', 'descripcion'=>'Entrada principal del parque, 8 bicicletas', 'disponibles'=>8, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                    ['id'=>3, 'titulo'=>'Estación Universidad', 'descripcion'=>'Campus principal, 15 bicicletas', 'disponibles'=>15, 'tipo'=>'estacion', 'icon'=>'dashicons-location'],
                ];

            case 'carpooling':
                return [
                    ['id'=>1, 'titulo'=>'Madrid → Barcelona', 'descripcion'=>'Salida a las 8:00, 3 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+2 days')), 'precio'=>25.00, 'plazas'=>3, 'icon'=>'dashicons-car'],
                    ['id'=>2, 'titulo'=>'Valencia → Alicante', 'descripcion'=>'Salida a las 10:00, 2 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+1 day')), 'precio'=>12.00, 'plazas'=>2, 'icon'=>'dashicons-car'],
                    ['id'=>3, 'titulo'=>'Sevilla → Córdoba', 'descripcion'=>'Salida a las 9:30, 4 plazas libres', 'fecha_inicio'=>date('Y-m-d', strtotime('+3 days')), 'precio'=>15.00, 'plazas'=>4, 'icon'=>'dashicons-car'],
                ];

            case 'biblioteca':
                return [
                    ['id'=>1, 'titulo'=>'El Quijote', 'descripcion'=>'Miguel de Cervantes - Clásico de la literatura española', 'tipo'=>'Novela', 'disponible'=>true, 'icon'=>'dashicons-book'],
                    ['id'=>2, 'titulo'=>'1984', 'descripcion'=>'George Orwell - Distopía y control social', 'tipo'=>'Novela', 'disponible'=>true, 'icon'=>'dashicons-book'],
                    ['id'=>3, 'titulo'=>'Cien años de soledad', 'descripcion'=>'Gabriel García Márquez - Realismo mágico', 'tipo'=>'Novela', 'disponible'=>false, 'icon'=>'dashicons-book'],
                ];

            case 'marketplace':
                return [
                    ['id'=>1, 'titulo'=>'Bicicleta de montaña', 'descripcion'=>'Bicicleta en buen estado, poco uso', 'precio'=>150.00, 'tipo'=>'venta', 'icon'=>'dashicons-products'],
                    ['id'=>2, 'titulo'=>'Colección de libros', 'descripcion'=>'50 libros variados de segunda mano', 'precio'=>45.00, 'tipo'=>'venta', 'icon'=>'dashicons-book-alt'],
                    ['id'=>3, 'titulo'=>'Mueble de jardín', 'descripcion'=>'Mesa con 4 sillas de exterior', 'precio'=>80.00, 'tipo'=>'venta', 'icon'=>'dashicons-admin-home'],
                ];

            case 'incidencias':
                return [
                    ['id'=>1, 'titulo'=>'Farola sin funcionar', 'descripcion'=>'Calle Mayor esquina Plaza', 'tipo'=>'alumbrado', 'estado'=>'pendiente', 'icon'=>'dashicons-warning'],
                    ['id'=>2, 'titulo'=>'Bache en calzada', 'descripcion'=>'Avenida Principal km 2', 'tipo'=>'via_publica', 'estado'=>'en_proceso', 'icon'=>'dashicons-warning'],
                    ['id'=>3, 'titulo'=>'Contenedor roto', 'descripcion'=>'Junto al parque infantil', 'tipo'=>'limpieza', 'estado'=>'resuelto', 'icon'=>'dashicons-yes-alt'],
                ];

            case 'comunidades':
                return [
                    ['id'=>1, 'titulo'=>'Comunidad Vecinal Centro', 'descripcion'=>'Vecinos del barrio centro unidos por un mejor barrio', 'miembros'=>156, 'tipo'=>'vecinal', 'icon'=>'dashicons-groups'],
                    ['id'=>2, 'titulo'=>'Club de Lectura', 'descripcion'=>'Amantes de la lectura compartiendo recomendaciones', 'miembros'=>45, 'tipo'=>'cultural', 'icon'=>'dashicons-book'],
                    ['id'=>3, 'titulo'=>'Runners del Parque', 'descripcion'=>'Grupo de running para todos los niveles', 'miembros'=>89, 'tipo'=>'deportivo', 'icon'=>'dashicons-heart'],
                ];

            case 'espacios_comunes':
                return [
                    ['id'=>1, 'titulo'=>'Sala de Reuniones A', 'descripcion'=>'Capacidad 20 personas, proyector incluido', 'tipo'=>'sala', 'disponible'=>true, 'icon'=>'dashicons-building'],
                    ['id'=>2, 'titulo'=>'Salón de Actos', 'descripcion'=>'Capacidad 100 personas, escenario y sonido', 'tipo'=>'auditorio', 'disponible'=>true, 'icon'=>'dashicons-megaphone'],
                    ['id'=>3, 'titulo'=>'Terraza Comunitaria', 'descripcion'=>'Espacio exterior para eventos', 'tipo'=>'exterior', 'disponible'=>false, 'icon'=>'dashicons-palmtree'],
                ];

            case 'huertos_urbanos':
                return [
                    ['id'=>1, 'titulo'=>'Parcela A-12', 'descripcion'=>'25m² disponible para cultivo', 'tipo'=>'parcela', 'disponible'=>true, 'precio'=>15.00, 'icon'=>'dashicons-carrot'],
                    ['id'=>2, 'titulo'=>'Parcela B-05', 'descripcion'=>'30m² con sistema de riego', 'tipo'=>'parcela', 'disponible'=>false, 'precio'=>20.00, 'icon'=>'dashicons-carrot'],
                    ['id'=>3, 'titulo'=>'Zona Comunitaria', 'descripcion'=>'Espacio compartido para principiantes', 'tipo'=>'comunitaria', 'disponible'=>true, 'precio'=>5.00, 'icon'=>'dashicons-groups'],
                ];

            case 'podcast':
                return [
                    ['id'=>1, 'titulo'=>'Episodio 45: Sostenibilidad urbana', 'descripcion'=>'Hablamos sobre cómo hacer ciudades más verdes', 'fecha'=>date('Y-m-d', strtotime('-3 days')), 'duracion'=>'45:00', 'icon'=>'dashicons-microphone'],
                    ['id'=>2, 'titulo'=>'Episodio 44: Economía circular', 'descripcion'=>'Cómo reducir, reutilizar y reciclar', 'fecha'=>date('Y-m-d', strtotime('-10 days')), 'duracion'=>'38:00', 'icon'=>'dashicons-microphone'],
                    ['id'=>3, 'titulo'=>'Episodio 43: Movilidad compartida', 'descripcion'=>'El futuro del transporte en las ciudades', 'fecha'=>date('Y-m-d', strtotime('-17 days')), 'duracion'=>'52:00', 'icon'=>'dashicons-microphone'],
                ];

            case 'banco_tiempo':
                return [
                    ['id'=>1, 'titulo'=>'Clases de guitarra', 'descripcion'=>'Ofrezco clases de guitarra para principiantes', 'tipo'=>'oferta', 'tiempo'=>'2h', 'icon'=>'dashicons-format-audio'],
                    ['id'=>2, 'titulo'=>'Ayuda con mudanza', 'descripcion'=>'Necesito ayuda para mover muebles', 'tipo'=>'demanda', 'tiempo'=>'3h', 'icon'=>'dashicons-hammer'],
                    ['id'=>3, 'titulo'=>'Reparación de ordenadores', 'descripcion'=>'Ofrezco mantenimiento básico de PCs', 'tipo'=>'oferta', 'tiempo'=>'1h', 'icon'=>'dashicons-laptop'],
                ];

            case 'cursos':
                return [
                    ['id'=>1, 'titulo'=>'Introducción a Python', 'descripcion'=>'Aprende programación desde cero', 'tipo'=>'online', 'fecha_inicio'=>date('Y-m-d', strtotime('+7 days')), 'precio'=>50.00, 'icon'=>'dashicons-laptop'],
                    ['id'=>2, 'titulo'=>'Marketing Digital', 'descripcion'=>'Estrategias de marketing en redes sociales', 'tipo'=>'presencial', 'fecha_inicio'=>date('Y-m-d', strtotime('+14 days')), 'precio'=>75.00, 'icon'=>'dashicons-share'],
                    ['id'=>3, 'titulo'=>'Inglés Conversacional', 'descripcion'=>'Mejora tu fluidez hablando', 'tipo'=>'hibrido', 'fecha_inicio'=>date('Y-m-d', strtotime('+5 days')), 'precio'=>40.00, 'icon'=>'dashicons-translation'],
                ];

            case 'parkings':
                return [
                    ['id'=>1, 'titulo'=>'Parking Centro', 'descripcion'=>'Plaza Mayor - 50 plazas disponibles', 'plazas_libres'=>50, 'precio'=>2.50, 'icon'=>'dashicons-location-alt'],
                    ['id'=>2, 'titulo'=>'Parking Estación', 'descripcion'=>'Junto a la estación de tren', 'plazas_libres'=>25, 'precio'=>1.80, 'icon'=>'dashicons-location-alt'],
                    ['id'=>3, 'titulo'=>'Parking Hospital', 'descripcion'=>'Acceso 24 horas', 'plazas_libres'=>120, 'precio'=>2.00, 'icon'=>'dashicons-location-alt'],
                ];

            default:
                // Datos genéricos para módulos sin datos específicos
                return [
                    ['id'=>1, 'titulo'=>__('Elemento de ejemplo 1', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del primer elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                    ['id'=>2, 'titulo'=>__('Elemento de ejemplo 2', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del segundo elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                    ['id'=>3, 'titulo'=>__('Elemento de ejemplo 3', 'flavor-chat-ia'), 'descripcion'=>__('Descripción del tercer elemento de ejemplo', 'flavor-chat-ia'), 'icon'=>'dashicons-admin-generic'],
                ];
        }
    }

    /**
     * Renderiza fallback cuando no hay template
     */
    private function render_fallback($modulo_id, $instance, $atts) {
        $nombre = is_object($instance) ? ($instance->name ?? ucfirst(str_replace(['_', '-'], ' ', $modulo_id))) : ucfirst(str_replace(['_', '-'], ' ', $modulo_id));
        $descripcion = is_object($instance) ? ($instance->description ?? '') : '';
        $color = $atts['color'] ?? '#4f46e5';

        ob_start();
        ?>
        <div class="flavor-module-fallback" style="--fmf-color: <?php echo esc_attr($color); ?>;">
            <div class="fmf-icon">
                <span class="dashicons dashicons-grid-view"></span>
            </div>
            <h3 class="fmf-title"><?php echo esc_html($nombre); ?></h3>
            <?php if (!empty($descripcion)) : ?>
                <p class="fmf-desc"><?php echo esc_html($descripcion); ?></p>
            <?php else : ?>
                <p class="fmf-desc"><?php esc_html_e('Este módulo está disponible pero aún no tiene contenido.', 'flavor-chat-ia'); ?></p>
            <?php endif; ?>
            <div class="fmf-actions">
                <a href="<?php echo esc_url(home_url('/' . str_replace('_', '-', $modulo_id) . '/')); ?>" class="fmf-btn">
                    <?php esc_html_e('Ver módulo', 'flavor-chat-ia'); ?>
                    <span class="dashicons dashicons-arrow-right-alt2"></span>
                </a>
            </div>
        </div>
        <style>
        .flavor-module-fallback {
            padding: 60px 20px;
            text-align: center;
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }
        .fmf-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            background: var(--fmf-color, #4f46e5);
            border-radius: 16px;
            margin-bottom: 20px;
        }
        .fmf-icon .dashicons {
            font-size: 32px;
            width: 32px;
            height: 32px;
            color: white;
        }
        .fmf-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
        }
        .fmf-desc {
            font-size: 1rem;
            color: #6b7280;
            margin: 0 0 24px;
            max-width: 400px;
            margin-left: auto;
            margin-right: auto;
        }
        .fmf-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
        }
        .fmf-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            background: var(--fmf-color, #4f46e5);
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s;
        }
        .fmf-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .fmf-btn .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
        }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Obtiene el icono dashicons para un módulo
     *
     * @param string $modulo_id ID del módulo
     * @return string Clase de icono dashicons
     */
    private function get_module_icon($modulo_id) {
        $iconos = [
            'eventos' => 'dashicons-calendar-alt',
            'talleres' => 'dashicons-welcome-learn-more',
            'cursos' => 'dashicons-book-alt',
            'reservas' => 'dashicons-calendar',
            'incidencias' => 'dashicons-warning',
            'marketplace' => 'dashicons-cart',
            'biblioteca' => 'dashicons-book',
            'podcast' => 'dashicons-microphone',
            'radio' => 'dashicons-format-audio',
            'comunidades' => 'dashicons-groups',
            'huertos-urbanos' => 'dashicons-carrot',
            'bicicletas-compartidas' => 'dashicons-bike',
            'carpooling' => 'dashicons-car',
            'parkings' => 'dashicons-location-alt',
            'banco-tiempo' => 'dashicons-clock',
            'grupos-consumo' => 'dashicons-store',
            'espacios-comunes' => 'dashicons-building',
            'participacion' => 'dashicons-megaphone',
            'presupuestos' => 'dashicons-chart-pie',
            'tramites' => 'dashicons-clipboard',
            'avisos-municipales' => 'dashicons-bell',
            'transparencia' => 'dashicons-visibility',
            'reciclaje' => 'dashicons-update',
            'compostaje' => 'dashicons-carrot',
        ];

        $modulo_id_normalizado = str_replace('_', '-', $modulo_id);
        return $iconos[$modulo_id_normalizado] ?? 'dashicons-admin-generic';
    }
}
