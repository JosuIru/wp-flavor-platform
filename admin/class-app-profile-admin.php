<?php
/**
 * Gestión de Perfiles de Aplicación en el Admin
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la interfaz de admin de perfiles
 */
class Flavor_App_Profile_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Gestor de perfiles
     */
    private $gestor_perfiles;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_App_Profile_Admin
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
        $this->gestor_perfiles = Flavor_App_Profiles::get_instance();
        $this->init_hooks();
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // Menu registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_post_flavor_chat_ia_cambiar_perfil', [$this, 'procesar_cambio_perfil']);
        add_action('admin_post_flavor_chat_ia_toggle_modulo', [$this, 'procesar_toggle_modulo']);
        add_action('admin_post_flavor_chat_ia_toggle_modulo_frontend', [$this, 'procesar_toggle_modulo_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'cargar_estilos_admin']);

        // Hooks del Template Orchestrator
        add_action('admin_post_flavor_activar_plantilla', [$this, 'procesar_activacion_plantilla']);
        add_action('wp_ajax_flavor_template_preview', [$this, 'ajax_obtener_preview']);
        add_action('wp_ajax_flavor_template_install_step', [$this, 'ajax_ejecutar_paso_instalacion']);
    }


    /**
     * Carga estilos y scripts para el admin del compositor
     */
    public function cargar_estilos_admin($sufijo_hook) {
        // Solo cargar en la pagina del compositor de aplicacion
        if (strpos($sufijo_hook, 'flavor-app-composer') === false) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS del compositor
        wp_enqueue_style(
            'flavor-app-composer',
            FLAVOR_CHAT_IA_URL . "admin/css/app-composer{$sufijo_asset}.css",
            ['flavor-design-tokens'],
            FLAVOR_CHAT_IA_VERSION
        );

        // CSS del Template Orchestrator
        wp_enqueue_style(
            'flavor-template-orchestrator',
            FLAVOR_CHAT_IA_URL . "admin/css/template-orchestrator{$sufijo_asset}.css",
            ['flavor-app-composer'],
            FLAVOR_CHAT_IA_VERSION
        );

        // App Composer JS - registra el componente Alpine
        wp_enqueue_script(
            'flavor-app-composer',
            FLAVOR_CHAT_IA_URL . "admin/js/app-composer{$sufijo_asset}.js",
            [],
            FLAVOR_CHAT_IA_VERSION,
            true // Cargar en footer
        );

        // Template Orchestrator JS
        wp_enqueue_script(
            'flavor-template-orchestrator',
            FLAVOR_CHAT_IA_URL . "admin/js/template-orchestrator{$sufijo_asset}.js",
            ['flavor-app-composer'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Datos para el Template Orchestrator
        $datos_orquestador = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonces' => [
                'preview' => wp_create_nonce('flavor_template_preview'),
                'activar' => wp_create_nonce('flavor_activar_plantilla'),
            ],
            'i18n' => [
                'pasoModulos' => __('Instalando modulos', 'flavor-chat-ia'),
                'pasoTablas' => __('Creando tablas de base de datos', 'flavor-chat-ia'),
                'pasoPaginas' => __('Creando paginas', 'flavor-chat-ia'),
                'pasoLanding' => __('Configurando landing page', 'flavor-chat-ia'),
                'pasoConfiguracion' => __('Aplicando configuracion', 'flavor-chat-ia'),
                'pasoDemo' => __('Cargando datos de demostracion', 'flavor-chat-ia'),
                'tituloInstalando' => __('Instalando plantilla...', 'flavor-chat-ia'),
                'tituloCompletado' => __('Instalacion completada', 'flavor-chat-ia'),
                'tituloError' => __('Error en la instalacion', 'flavor-chat-ia'),
                'errorCarga' => __('Error de conexion al cargar la plantilla', 'flavor-chat-ia'),
                'errorGeneral' => __('Error de conexion durante la instalacion', 'flavor-chat-ia'),
            ],
        ];
        wp_localize_script('flavor-template-orchestrator', 'flavorOrchestratorData', $datos_orquestador);

        // Alpine.js - depende de template-orchestrator para asegurar el orden correcto
        wp_enqueue_script(
            'alpinejs',
            'https://cdn.jsdelivr.net/npm/alpinejs@3.14.3/dist/cdn.min.js',
            ['flavor-template-orchestrator'],
            '3.14.3',
            true
        );

        // Agregar plugin focus de Alpine para accesibilidad del modal
        wp_enqueue_script(
            'alpinejs-focus',
            'https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.14.3/dist/cdn.min.js',
            [],
            '3.14.3',
            true
        );

        // Agregar atributo defer a Alpine para que espere al DOM
        add_filter('script_loader_tag', function($tag, $handle) {
            if ($handle === 'alpinejs' || $handle === 'alpinejs-focus') {
                return str_replace(' src', ' defer src', $tag);
            }
            return $tag;
        }, 10, 2);
    }

    /**
     * Renderiza la pagina del compositor (2 pasos: plantillas + modulos)
     */
    public function renderizar_pagina_perfil() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta pagina.', 'flavor-chat-ia'));
        }

        $id_perfil_actual = $this->gestor_perfiles->obtener_perfil_activo();
        $perfiles = $this->gestor_perfiles->obtener_perfiles();
        $categorias_modulos = $this->gestor_perfiles->obtener_categorias_modulos();
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        // Debug: verificar qué perfil está activo
        error_log('Perfil activo: ' . $id_perfil_actual);

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();

        // Datos para Alpine.js
        $datos_compositor = [
            'perfilActivo'      => $id_perfil_actual,
            'modulosActivos'    => array_values($modulos_activos),
            'perfiles'          => $perfiles,
            'categorias'        => $categorias_modulos,
            'modulosRegistrados' => $modulos_registrados,
            'adminPostUrl'      => admin_url('admin-post.php'),
            'nonces'            => [
                'cambiarPerfil' => wp_create_nonce('cambiar_perfil'),
                'toggleModulo'  => wp_create_nonce('toggle_modulo'),
            ],
            'i18n' => [
                'confirmarCambioPerfil' => __('Deseas cambiar de plantilla? Esto activara los modulos correspondientes.', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-app-composer', 'flavorComposerData', $datos_compositor);

        // Mostrar mensajes de resultado
        $mensaje = isset($_GET['mensaje']) ? sanitize_text_field($_GET['mensaje']) : '';
        ?>
        <div class="wrap flavor-composer-wrapper" x-data="() => ({ ...flavorComposer(), ...flavorTemplateOrchestrator() })">
            <h1><?php _e('Compositor & Modulos', 'flavor-chat-ia'); ?></h1>
            <p class="description"><?php _e('Elige una plantilla predefinida o personaliza los modulos de tu aplicacion.', 'flavor-chat-ia'); ?></p>

            <?php if ($mensaje === 'perfil_cambiado'): ?>
                <div class="notice notice-success is-dismissible"><p><?php _e('Plantilla cambiada correctamente.', 'flavor-chat-ia'); ?></p></div>
            <?php elseif ($mensaje === 'modulo_actualizado'): ?>
                <div class="notice notice-success is-dismissible"><p><?php _e('Modulo actualizado correctamente.', 'flavor-chat-ia'); ?></p></div>
            <?php elseif ($mensaje === 'plantilla_activada'): ?>
                <div class="notice notice-success is-dismissible"><p><?php _e('Plantilla activada correctamente.', 'flavor-chat-ia'); ?></p></div>
            <?php elseif ($mensaje === 'plantilla_error'): ?>
                <div class="notice notice-error is-dismissible"><p><?php _e('Error al activar la plantilla.', 'flavor-chat-ia'); ?></p></div>
            <?php endif; ?>

            <!-- Navegación de pasos -->
            <div class="flavor-composer-steps">
                <div class="flavor-composer-step" :class="{ 'activo': pasoActual === 'plantillas' }" @click="irAPaso('plantillas')">
                    <span class="step-numero">1</span> <?php _e('Plantillas', 'flavor-chat-ia'); ?>
                </div>
                <div class="flavor-composer-step" :class="{ 'activo': pasoActual === 'modulos' }" @click="irAPaso('modulos')">
                    <span class="step-numero">2</span> <?php _e('Módulos', 'flavor-chat-ia'); ?>
                </div>
            </div>

            <!-- Paso 1: Galeria de Plantillas -->
            <div x-show="pasoActual === 'plantillas'" x-transition>
                <div class="flavor-templates-grid">
                    <?php foreach ($perfiles as $id_perfil => $datos_perfil): ?>
                        <div class="flavor-template-card"
                             :class="{ 'seleccionado': esPerfilActivo('<?php echo esc_js($id_perfil); ?>') }"
                             @click="<?php echo ($id_perfil !== 'personalizado') ? "window.abrirPreviewPlantilla('" . esc_js($id_perfil) . "')" : "cambiarPerfil('" . esc_js($id_perfil) . "')"; ?>">

                            <div class="flavor-template-header">
                                <div class="flavor-template-icon" style="background: <?php echo esc_attr($datos_perfil['color']); ?>15;">
                                    <span class="dashicons <?php echo esc_attr($datos_perfil['icono']); ?>"
                                          style="color: <?php echo esc_attr($datos_perfil['color']); ?>;"></span>
                                </div>
                                <div>
                                    <h3 class="flavor-template-title"><?php echo esc_html($datos_perfil['nombre']); ?></h3>
                                </div>
                            </div>

                            <p class="flavor-template-descripcion"><?php echo esc_html($datos_perfil['descripcion']); ?></p>

                            <?php if ($id_perfil !== 'personalizado'): ?>
                                <span class="flavor-template-modulos-badge">
                                    <?php printf(
                                        esc_html__('%d modulos', 'flavor-chat-ia'),
                                        count($datos_perfil['modulos_requeridos']) + count($datos_perfil['modulos_opcionales'])
                                    ); ?>
                                </span>

                                <div class="flavor-template-tags">
                                    <?php foreach ($datos_perfil['modulos_requeridos'] as $id_modulo): ?>
                                        <span class="flavor-modulo-tag requerido">
                                            <?php echo esc_html($modulos_registrados[$id_modulo]['name'] ?? ucfirst(str_replace('_', ' ', $id_modulo))); ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <div class="flavor-template-btn">
                                <span class="button button-small" :class="{ 'button-primary': !esPerfilActivo('<?php echo esc_js($id_perfil); ?>') }">
                                    <template x-if="esPerfilActivo('<?php echo esc_js($id_perfil); ?>')">
                                        <span><?php _e('Activo', 'flavor-chat-ia'); ?></span>
                                    </template>
                                    <template x-if="!esPerfilActivo('<?php echo esc_js($id_perfil); ?>')">
                                        <span><?php _e('Ver preview', 'flavor-chat-ia'); ?></span>
                                    </template>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Paso 2: Compositor de Módulos -->
            <div x-show="pasoActual === 'modulos'" x-transition>
                <!-- Tabs de categorías -->
                <div class="flavor-category-tabs">
                    <span class="flavor-category-tab"
                          :class="{ 'activo': categoriaFiltrada === 'todos' }"
                          @click="filtrarCategoria('todos')">
                        <?php _e('Todos', 'flavor-chat-ia'); ?>
                    </span>
                    <?php foreach ($categorias_modulos as $id_categoria => $datos_categoria): ?>
                        <span class="flavor-category-tab"
                              :class="{ 'activo': categoriaFiltrada === '<?php echo esc_js($id_categoria); ?>' }"
                              @click="filtrarCategoria('<?php echo esc_js($id_categoria); ?>')">
                            <?php echo esc_html($datos_categoria['nombre']); ?>
                            <span class="conteo" x-text="contarActivosEnCategoria('<?php echo esc_js($id_categoria); ?>') + '/' + (categorias['<?php echo esc_js($id_categoria); ?>']?.modulos?.length || 0)"></span>
                        </span>
                    <?php endforeach; ?>
                </div>

                <!-- Grid de módulos -->
                <div class="flavor-modules-grid">
                    <?php foreach ($categorias_modulos as $id_categoria => $datos_categoria): ?>
                        <?php foreach ($datos_categoria['modulos'] as $id_modulo): ?>
                            <?php
                            $info_modulo = $modulos_registrados[$id_modulo] ?? null;
                            $nombre_modulo = $info_modulo['name'] ?? ucfirst(str_replace('_', ' ', $id_modulo));
                            $descripcion_modulo = $info_modulo['description'] ?? '';
                            ?>
                            <div class="flavor-module-card"
                                 x-show="categoriaFiltrada === 'todos' || categoriaFiltrada === '<?php echo esc_js($id_categoria); ?>'"
                                 :class="{
                                     'activo': esModuloActivo('<?php echo esc_js($id_modulo); ?>'),
                                     'requerido': esModuloRequerido('<?php echo esc_js($id_modulo); ?>')
                                 }">

                                <div class="flavor-module-icon" style="background: <?php echo esc_attr($datos_categoria['nombre'] === 'Comercio' ? '#dbeafe' : ($datos_categoria['nombre'] === 'Comunidad' ? '#fce7f3' : ($datos_categoria['nombre'] === 'Gobernanza' ? '#e0e7ff' : ($datos_categoria['nombre'] === 'Sostenibilidad' ? '#d1fae5' : ($datos_categoria['nombre'] === 'Contenido' ? '#fef3c7' : '#f3f4f6'))))); ?>">
                                    <span class="dashicons <?php echo esc_attr($info_modulo['icon'] ?? 'dashicons-admin-plugins'); ?>"
                                          style="color: var(--flavor-text-secondary);"></span>
                                </div>

                                <div class="flavor-module-info">
                                    <h4>
                                        <?php echo esc_html($nombre_modulo); ?>
                                        <template x-if="esModuloRequerido('<?php echo esc_js($id_modulo); ?>')">
                                            <span class="flavor-module-badge requerido"><?php _e('Requerido', 'flavor-chat-ia'); ?></span>
                                        </template>
                                    </h4>
                                    <p><?php echo esc_html($descripcion_modulo); ?></p>
                                </div>

                                <label class="flavor-toggle" @click.stop>
                                    <input type="checkbox"
                                           :checked="esModuloActivo('<?php echo esc_js($id_modulo); ?>') || esModuloRequerido('<?php echo esc_js($id_modulo); ?>')"
                                           :disabled="esModuloRequerido('<?php echo esc_js($id_modulo); ?>')"
                                           @change="toggleModulo('<?php echo esc_js($id_modulo); ?>')">
                                    <span class="flavor-toggle-slider"></span>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php
            // Renderizar modulos frontend (landings)
            $modulos_frontend = $this->obtener_modulos_frontend();
            $this->renderizar_modulos_frontend($modulos_frontend);

            // Renderizar seccion de paginas de demostracion
            $this->renderizar_seccion_demo_pages();

            // Renderizar seccion de datos de demostracion
            $this->renderizar_seccion_demo_data();

            // Renderizar modal de preview del Template Orchestrator
            $this->renderizar_modal_preview_plantilla();

            // Renderizar vista de progreso del Template Orchestrator
            $this->renderizar_vista_progreso();
            ?>
        </div>
        <?php
    }

    /**
     * Renderiza el modal de preview de plantilla dinamicamente
     */
    private function renderizar_modal_preview_plantilla() {
        include FLAVOR_CHAT_IA_PATH . 'admin/views/template-preview-modal.php';
    }

    /**
     * Renderiza la vista de progreso de instalacion
     */
    private function renderizar_vista_progreso() {
        include FLAVOR_CHAT_IA_PATH . 'admin/views/template-progress.php';
    }

    /**
     * Procesa el cambio de perfil
     */
    public function procesar_cambio_perfil() {
        check_admin_referer('cambiar_perfil');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $perfil_id = sanitize_text_field($_POST['perfil_id'] ?? '');

        if ($this->gestor_perfiles->establecer_perfil($perfil_id)) {
            wp_safe_redirect(add_query_arg(
                ['page' => 'flavor-app-composer', 'mensaje' => 'perfil_cambiado'],
                admin_url('admin.php')
            ));
        } else {
            wp_safe_redirect(add_query_arg(
                ['page' => 'flavor-app-composer', 'error' => 'perfil_invalido'],
                admin_url('admin.php')
            ));
        }
        exit;
    }

    /**
     * Procesa activar/desactivar módulo
     */
    public function procesar_toggle_modulo() {
        check_admin_referer('toggle_modulo');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulo_id = sanitize_text_field($_POST['modulo_id'] ?? '');
        $activar = isset($_POST['activar']) && $_POST['activar'] === '1';

        if ($activar) {
            $this->gestor_perfiles->activar_modulo_opcional($modulo_id);
        } else {
            $this->gestor_perfiles->desactivar_modulo_opcional($modulo_id);
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => 'modulo_actualizado'],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Procesa activar/desactivar módulo frontend
     */
    public function procesar_toggle_modulo_frontend() {
        check_admin_referer('toggle_modulo_frontend');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulo_id = sanitize_text_field($_POST['modulo_id'] ?? '');
        $activar = isset($_POST['activar']) && $_POST['activar'] === '1';

        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_frontend_modules'] ?? [];

        // Si no hay módulos configurados, inicializar con todos
        if (empty($modulos_activos)) {
            $modulos_activos = array_keys($this->obtener_modulos_frontend());
        }

        if ($activar) {
            if (!in_array($modulo_id, $modulos_activos)) {
                $modulos_activos[] = $modulo_id;
            }
        } else {
            $clave = array_search($modulo_id, $modulos_activos);
            if ($clave !== false) {
                unset($modulos_activos[$clave]);
                $modulos_activos = array_values($modulos_activos);
            }
        }

        $configuracion['active_frontend_modules'] = $modulos_activos;
        update_option('flavor_chat_ia_settings', $configuracion);

        // Programar flush de rewrite rules
        if (class_exists('Flavor_Frontend_Loader')) {
            Flavor_Frontend_Loader::schedule_flush_rewrite();
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => 'modulo_frontend_actualizado'],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Obtiene la lista de módulos frontend (landings) disponibles
     *
     * @return array
     */
    private function obtener_modulos_frontend() {
        $modulos_frontend = [
            'grupos-consumo' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Pedidos colectivos de productos locales y ecológicos', 'flavor-chat-ia'),
                'icon' => 'dashicons-carrot',
                'color' => '#84cc16',
                'url' => home_url('/grupos-consumo/'),
            ],
            'banco-tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Intercambio de servicios y habilidades por horas', 'flavor-chat-ia'),
                'icon' => 'dashicons-clock',
                'color' => '#8b5cf6',
                'url' => home_url('/banco-tiempo/'),
            ],
            'ayuntamiento' => [
                'name' => __('Ayuntamiento', 'flavor-chat-ia'),
                'description' => __('Portal ciudadano con trámites, noticias y servicios municipales', 'flavor-chat-ia'),
                'icon' => 'dashicons-building',
                'color' => '#1d4ed8',
                'url' => home_url('/ayuntamiento/'),
            ],
            'comunidades' => [
                'name' => __('Comunidades', 'flavor-chat-ia'),
                'description' => __('Redes vecinales y grupos de interés común', 'flavor-chat-ia'),
                'icon' => 'dashicons-groups',
                'color' => '#f43f5e',
                'url' => home_url('/comunidades/'),
            ],
            'espacios-comunes' => [
                'name' => __('Espacios Comunes', 'flavor-chat-ia'),
                'description' => __('Reserva de salas, locales y espacios compartidos', 'flavor-chat-ia'),
                'icon' => 'dashicons-admin-multisite',
                'color' => '#06b6d4',
                'url' => home_url('/espacios-comunes/'),
            ],
            'ayuda-vecinal' => [
                'name' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Red de apoyo mutuo entre vecinos', 'flavor-chat-ia'),
                'icon' => 'dashicons-heart',
                'color' => '#f97316',
                'url' => home_url('/ayuda-vecinal/'),
            ],
            'huertos-urbanos' => [
                'name' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'description' => __('Gestión de parcelas y huertos comunitarios', 'flavor-chat-ia'),
                'icon' => 'dashicons-palmtree',
                'color' => '#22c55e',
                'url' => home_url('/huertos-urbanos/'),
            ],
            'biblioteca' => [
                'name' => __('Biblioteca', 'flavor-chat-ia'),
                'description' => __('Catálogo y préstamo de libros comunitarios', 'flavor-chat-ia'),
                'icon' => 'dashicons-book',
                'color' => '#6366f1',
                'url' => home_url('/biblioteca/'),
            ],
            'cursos' => [
                'name' => __('Cursos y Talleres', 'flavor-chat-ia'),
                'description' => __('Formación y actividades educativas', 'flavor-chat-ia'),
                'icon' => 'dashicons-welcome-learn-more',
                'color' => '#a855f7',
                'url' => home_url('/cursos/'),
            ],
            'podcast' => [
                'name' => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Contenido de audio y episodios', 'flavor-chat-ia'),
                'icon' => 'dashicons-microphone',
                'color' => '#14b8a6',
                'url' => home_url('/podcast/'),
            ],
            'radio' => [
                'name' => __('Radio', 'flavor-chat-ia'),
                'description' => __('Emisora de radio comunitaria', 'flavor-chat-ia'),
                'icon' => 'dashicons-controls-volumeon',
                'color' => '#ef4444',
                'url' => home_url('/radio/'),
            ],
            'bicicletas' => [
                'name' => __('Bicicletas', 'flavor-chat-ia'),
                'description' => __('Préstamo y reparación de bicicletas', 'flavor-chat-ia'),
                'icon' => 'dashicons-location-alt',
                'color' => '#a3e635',
                'url' => home_url('/bicicletas/'),
            ],
            'reciclaje' => [
                'name' => __('Reciclaje', 'flavor-chat-ia'),
                'description' => __('Puntos de reciclaje y economía circular', 'flavor-chat-ia'),
                'icon' => 'dashicons-update',
                'color' => '#10b981',
                'url' => home_url('/reciclaje/'),
            ],
            'tienda-local' => [
                'name' => __('Tienda Local', 'flavor-chat-ia'),
                'description' => __('Directorio de comercios y productos locales', 'flavor-chat-ia'),
                'icon' => 'dashicons-store',
                'color' => '#f59e0b',
                'url' => home_url('/tienda-local/'),
            ],
            'incidencias' => [
                'name' => __('Incidencias', 'flavor-chat-ia'),
                'description' => __('Reporte y seguimiento de problemas urbanos', 'flavor-chat-ia'),
                'icon' => 'dashicons-warning',
                'color' => '#e11d48',
                'url' => home_url('/incidencias/'),
            ],
        ];

        // Marcar módulos activos (verificar si el controlador existe)
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_frontend_modules'] ?? array_keys($modulos_frontend);

        foreach ($modulos_frontend as $modulo_id => &$modulo) {
            $modulo['activo'] = in_array($modulo_id, $modulos_activos);
            $modulo['can_activate'] = true;
        }

        return $modulos_frontend;
    }

    /**
     * Renderiza la sección de módulos frontend
     *
     * @param array $modulos_frontend
     */
    public function renderizar_modulos_frontend($modulos_frontend) {
        ?>
        <div class="flavor-modulos-lista" style="margin-top: 40px;">
            <h2><?php _e('Landings / Módulos Frontend', 'flavor-chat-ia'); ?></h2>
            <p class="description">
                <?php _e('Activa o desactiva las páginas públicas (landings) disponibles para tu sitio.', 'flavor-chat-ia'); ?>
            </p>

            <div class="flavor-app-profiles" style="margin-top: 20px;">
                <?php foreach ($modulos_frontend as $modulo_id => $modulo): ?>
                    <div class="flavor-profile-card <?php echo $modulo['activo'] ? 'activo' : ''; ?>"
                         style="cursor: default;">
                        <div class="flavor-profile-header">
                            <div class="flavor-profile-icon" style="background: <?php echo esc_attr($modulo['color']); ?>20;">
                                <span class="dashicons <?php echo esc_attr($modulo['icon']); ?>"
                                      style="color: <?php echo esc_attr($modulo['color']); ?>; font-size: 28px;"></span>
                            </div>
                            <div>
                                <h3 class="flavor-profile-title" style="font-size: 15px;">
                                    <?php echo esc_html($modulo['name']); ?>
                                </h3>
                            </div>
                        </div>

                        <p class="flavor-profile-descripcion" style="font-size: 13px;">
                            <?php echo esc_html($modulo['description']); ?>
                        </p>

                        <div style="display: flex; gap: 10px; margin-top: 15px;">
                            <a href="<?php echo esc_url($modulo['url']); ?>" target="_blank"
                               class="button button-secondary" style="flex: 1; text-align: center;">
                                <?php _e('Ver Landing', 'flavor-chat-ia'); ?> ↗
                            </a>
                            <button class="button <?php echo $modulo['activo'] ? '' : 'button-primary'; ?>"
                                    onclick="toggleModuloFrontend('<?php echo esc_js($modulo_id); ?>', <?php echo $modulo['activo'] ? 'false' : 'true'; ?>)"
                                    style="flex: 1;">
                                <?php echo $modulo['activo'] ? __('Desactivar', 'flavor-chat-ia') : __('Activar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        function toggleModuloFrontend(moduloId, activar) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '<?php echo admin_url('admin-post.php'); ?>';

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'flavor_chat_ia_toggle_modulo_frontend';
            form.appendChild(actionInput);

            const moduloInput = document.createElement('input');
            moduloInput.type = 'hidden';
            moduloInput.name = 'modulo_id';
            moduloInput.value = moduloId;
            form.appendChild(moduloInput);

            const activarInput = document.createElement('input');
            activarInput.type = 'hidden';
            activarInput.name = 'activar';
            activarInput.value = activar ? '1' : '0';
            form.appendChild(activarInput);

            const nonceInput = document.createElement('input');
            nonceInput.type = 'hidden';
            nonceInput.name = '_wpnonce';
            nonceInput.value = '<?php echo wp_create_nonce('toggle_modulo_frontend'); ?>';
            form.appendChild(nonceInput);

            document.body.appendChild(form);
            form.submit();
        }
        </script>
        <?php
    }

    /**
     * Renderiza la sección de páginas de demostración
     */
    private function renderizar_seccion_demo_pages() {
        // Verificar si el gestor de datos demo está disponible
        if (!class_exists('Flavor_Demo_Data_Manager')) {
            return;
        }

        $demo_manager = Flavor_Demo_Data_Manager::get_instance();
        $paginas = $demo_manager->get_demo_pages_list();
        $tiene_paginas_demo = $demo_manager->has_demo_pages();
        $count_paginas_demo = $demo_manager->get_demo_pages_count();

        // Mostrar mensajes de resultado
        $mensaje = isset($_GET['mensaje']) ? sanitize_text_field($_GET['mensaje']) : '';
        ?>
        <div class="flavor-demo-pages-section" style="margin-top: 40px;">
            <h2><?php _e('Páginas de Demostración', 'flavor-chat-ia'); ?></h2>
            <p class="description">
                <?php _e('Crea páginas de WordPress con las landings de cada módulo. Útil para probar y mostrar a clientes.', 'flavor-chat-ia'); ?>
            </p>

            <?php if ($mensaje === 'demo_pages_created'): ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong><?php _e('¡Páginas de demostración creadas correctamente!', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php elseif ($mensaje === 'demo_pages_deleted'): ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong><?php _e('Páginas de demostración eliminadas correctamente.', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php elseif ($mensaje === 'demo_pages_error'): ?>
                <div class="notice notice-error" style="margin: 15px 0;">
                    <p><strong><?php _e('Error al crear las páginas de demostración.', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php endif; ?>

            <!-- Acciones globales -->
            <div class="flavor-demo-global-actions" style="display: flex; gap: 15px; margin: 20px 0; padding: 20px; background: #f0f0f1; border-radius: 8px;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 10px 0;">
                        <?php _e('Gestión de páginas', 'flavor-chat-ia'); ?>
                        <?php if ($tiene_paginas_demo): ?>
                            <span style="background: #22c55e; color: white; padding: 2px 8px; border-radius: 4px; font-size: 12px; margin-left: 10px;">
                                <?php printf(
                                    esc_html(_n('%d página creada', '%d páginas creadas', $count_paginas_demo, 'flavor-chat-ia')),
                                    $count_paginas_demo
                                ); ?>
                            </span>
                        <?php endif; ?>
                    </h4>
                    <p class="description" style="margin: 0 0 15px 0;">
                        <?php _e('Crea todas las páginas de una vez o elimínalas cuando ya no las necesites.', 'flavor-chat-ia'); ?>
                    </p>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                            <?php wp_nonce_field('flavor_demo_pages_action'); ?>
                            <input type="hidden" name="action" value="flavor_create_demo_pages">
                            <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('¿Crear todas las páginas de demostración? Las páginas existentes con el mismo slug no se sobrescribirán.', 'flavor-chat-ia'); ?>');">
                                <span class="dashicons dashicons-welcome-add-page" style="margin-right: 5px;"></span>
                                <?php _e('Crear todas las páginas', 'flavor-chat-ia'); ?>
                            </button>
                        </form>

                        <?php if ($tiene_paginas_demo): ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('flavor_demo_pages_action'); ?>
                                <input type="hidden" name="action" value="flavor_delete_demo_pages">
                                <button type="submit" class="button" style="color: #d63638;" onclick="return confirm('<?php esc_attr_e('¿Eliminar TODAS las páginas de demostración? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>');">
                                    <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span>
                                    <?php _e('Eliminar todas las páginas demo', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Grid de páginas -->
            <div class="flavor-demo-pages-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 12px; margin-top: 20px;">
                <?php foreach ($paginas as $pagina_id => $pagina): ?>
                    <div class="flavor-demo-page-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; transition: all 0.2s ease;">
                        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                            <div style="width: 40px; height: 40px; background: <?php echo esc_attr($pagina['color']); ?>15; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                <span class="dashicons <?php echo esc_attr($pagina['icon']); ?>" style="color: <?php echo esc_attr($pagina['color']); ?>; font-size: 20px;"></span>
                            </div>
                            <div style="flex: 1; min-width: 0;">
                                <h4 style="margin: 0; font-size: 14px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?php echo esc_html($pagina['title']); ?>
                                </h4>
                                <span style="font-size: 11px; color: #6b7280;">
                                    /<?php echo esc_html($pagina['slug']); ?>/
                                </span>
                            </div>
                            <?php if ($pagina['is_demo']): ?>
                                <span style="background: #dbeafe; color: #1d4ed8; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                    DEMO
                                </span>
                            <?php elseif ($pagina['exists']): ?>
                                <span style="background: #fef3c7; color: #92400e; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: 600;">
                                    EXISTE
                                </span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; gap: 6px;">
                            <?php if ($pagina['exists']): ?>
                                <a href="<?php echo esc_url($pagina['url']); ?>" target="_blank" class="button button-small" style="flex: 1; text-align: center; font-size: 12px;">
                                    <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>
                                    <?php _e('Ver', 'flavor-chat-ia'); ?>
                                </a>
                                <?php if ($pagina['edit_url']): ?>
                                    <a href="<?php echo esc_url($pagina['edit_url']); ?>" class="button button-small" style="flex: 1; text-align: center; font-size: 12px;">
                                        <span class="dashicons dashicons-edit" style="font-size: 14px; vertical-align: middle;"></span>
                                        <?php _e('Editar', 'flavor-chat-ia'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="flex: 1; text-align: center; color: #9ca3af; font-size: 12px; padding: 6px;">
                                    <?php _e('No creada', 'flavor-chat-ia'); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="description" style="margin-top: 15px; color: #6b7280;">
                <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
                <?php _e('Las páginas marcadas como "EXISTE" ya existían antes y no se eliminarán. Solo se eliminan las páginas creadas con el botón "Crear todas".', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Renderiza la sección de datos de demostración
     */
    private function renderizar_seccion_demo_data() {
        // Verificar si el gestor de datos demo está disponible
        if (!class_exists('Flavor_Demo_Data_Manager')) {
            return;
        }

        $demo_manager = Flavor_Demo_Data_Manager::get_instance();

        // Módulos soportados para datos demo
        $modulos_demo = [
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'color' => '#8b5cf6',
            ],
            'eventos' => [
                'nombre' => __('Eventos', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar',
                'color' => '#3b82f6',
            ],
            'marketplace' => [
                'nombre' => __('Marketplace', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
                'color' => '#f59e0b',
            ],
            'grupos_consumo' => [
                'nombre' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'color' => '#22c55e',
            ],
            'ayuda_vecinal' => [
                'nombre' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'icono' => 'dashicons-heart',
                'color' => '#ef4444',
            ],
        ];

        // Contar datos demo actuales
        $tiene_datos_demo = false;
        foreach ($modulos_demo as $id => &$modulo) {
            $modulo['tiene_datos'] = $demo_manager->has_demo_data($id);
            $modulo['count'] = $demo_manager->get_demo_data_count($id);
            if ($modulo['tiene_datos']) {
                $tiene_datos_demo = true;
            }
        }
        unset($modulo);

        // Mostrar mensajes de resultado
        $mensaje = isset($_GET['mensaje']) ? sanitize_text_field($_GET['mensaje']) : '';
        ?>
        <div class="flavor-demo-data-section" style="margin-top: 40px;">
            <h2><?php _e('Datos de Demostración', 'flavor-chat-ia'); ?></h2>
            <p class="description">
                <?php _e('Carga datos de ejemplo para probar los módulos o elimínalos cuando ya no los necesites.', 'flavor-chat-ia'); ?>
            </p>

            <?php if ($mensaje === 'demo_data_populated'): ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong><?php _e('¡Datos de demostración cargados correctamente!', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php elseif ($mensaje === 'demo_data_cleared'): ?>
                <div class="notice notice-success" style="margin: 15px 0;">
                    <p><strong><?php _e('Datos de demostración eliminados correctamente.', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php elseif ($mensaje === 'demo_data_error'): ?>
                <div class="notice notice-error" style="margin: 15px 0;">
                    <p><strong><?php _e('Error al cargar los datos de demostración.', 'flavor-chat-ia'); ?></strong></p>
                </div>
            <?php endif; ?>

            <!-- Acciones globales -->
            <div class="flavor-demo-global-actions" style="display: flex; gap: 15px; margin: 20px 0; padding: 20px; background: #f0f0f1; border-radius: 8px;">
                <div style="flex: 1;">
                    <h4 style="margin: 0 0 10px 0;"><?php _e('Acciones rápidas', 'flavor-chat-ia'); ?></h4>
                    <p class="description" style="margin: 0 0 15px 0;">
                        <?php _e('Carga o elimina datos demo de todos los módulos a la vez.', 'flavor-chat-ia'); ?>
                    </p>
                    <div style="display: flex; gap: 10px;">
                        <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                            <?php wp_nonce_field('flavor_demo_data_action'); ?>
                            <input type="hidden" name="action" value="flavor_populate_demo_data">
                            <input type="hidden" name="modulo_id" value="all">
                            <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('¿Cargar datos de ejemplo en todos los módulos?', 'flavor-chat-ia'); ?>');">
                                <span class="dashicons dashicons-database-add" style="margin-right: 5px;"></span>
                                <?php _e('Cargar todos los datos demo', 'flavor-chat-ia'); ?>
                            </button>
                        </form>

                        <?php if ($tiene_datos_demo): ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('flavor_demo_data_action'); ?>
                                <input type="hidden" name="action" value="flavor_clear_demo_data">
                                <input type="hidden" name="modulo_id" value="all">
                                <button type="submit" class="button" style="color: #d63638;" onclick="return confirm('<?php esc_attr_e('¿Eliminar TODOS los datos de demostración? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>');">
                                    <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span>
                                    <?php _e('Eliminar todos los datos demo', 'flavor-chat-ia'); ?>
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Grid de módulos -->
            <div class="flavor-demo-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;">
                <?php foreach ($modulos_demo as $modulo_id => $modulo): ?>
                    <div class="flavor-demo-module-card" style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 15px;">
                            <div style="width: 48px; height: 48px; background: <?php echo esc_attr($modulo['color']); ?>15; border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                                <span class="dashicons <?php echo esc_attr($modulo['icono']); ?>" style="color: <?php echo esc_attr($modulo['color']); ?>; font-size: 24px;"></span>
                            </div>
                            <div>
                                <h4 style="margin: 0; font-size: 15px;"><?php echo esc_html($modulo['nombre']); ?></h4>
                                <?php if ($modulo['tiene_datos']): ?>
                                    <span style="font-size: 12px; color: #22c55e; display: flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-yes-alt" style="font-size: 14px;"></span>
                                        <?php printf(
                                            esc_html(_n('%d registro demo', '%d registros demo', $modulo['count'], 'flavor-chat-ia')),
                                            $modulo['count']
                                        ); ?>
                                    </span>
                                <?php else: ?>
                                    <span style="font-size: 12px; color: #6b7280;">
                                        <?php _e('Sin datos demo', 'flavor-chat-ia'); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div style="display: flex; gap: 8px;">
                            <?php if (!$modulo['tiene_datos']): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="flex: 1;">
                                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                                    <input type="hidden" name="action" value="flavor_populate_demo_data">
                                    <input type="hidden" name="modulo_id" value="<?php echo esc_attr($modulo_id); ?>">
                                    <button type="submit" class="button button-primary" style="width: 100%;">
                                        <span class="dashicons dashicons-plus-alt" style="margin-right: 5px;"></span>
                                        <?php _e('Cargar datos', 'flavor-chat-ia'); ?>
                                    </button>
                                </form>
                            <?php else: ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="flex: 1;">
                                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                                    <input type="hidden" name="action" value="flavor_clear_demo_data">
                                    <input type="hidden" name="modulo_id" value="<?php echo esc_attr($modulo_id); ?>">
                                    <button type="submit" class="button" style="width: 100%;" onclick="return confirm('<?php esc_attr_e('¿Eliminar los datos de demostración de este módulo?', 'flavor-chat-ia'); ?>');">
                                        <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span>
                                        <?php _e('Eliminar datos', 'flavor-chat-ia'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p class="description" style="margin-top: 20px; color: #6b7280;">
                <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
                <?php _e('Los datos de demostracion se marcan internamente para poder eliminarlos sin afectar a los datos reales.', 'flavor-chat-ia'); ?>
            </p>
        </div>
        <?php
    }

    // ══════════════════════════════════════════════════════════════════════════
    // TEMPLATE ORCHESTRATOR - METODOS AJAX Y PROCESAMIENTO
    // ══════════════════════════════════════════════════════════════════════════

    /**
     * Procesa la activacion de una plantilla via POST
     */
    public function procesar_activacion_plantilla() {
        check_admin_referer('flavor_activar_plantilla');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta accion.', 'flavor-chat-ia'));
        }

        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $modulos_opcionales = isset($_POST['modulos_opcionales']) ? array_map('sanitize_text_field', (array) $_POST['modulos_opcionales']) : [];
        $cargar_demo = isset($_POST['cargar_demo']) && $_POST['cargar_demo'] === '1';

        $opciones = [
            'modulos_opcionales' => $modulos_opcionales,
            'cargar_demo' => $cargar_demo,
        ];

        // Intentar activar la plantilla
        $resultado = $this->activar_plantilla_completa($plantilla_id, $opciones);

        if ($resultado['success']) {
            wp_safe_redirect(add_query_arg(
                ['page' => 'flavor-app-composer', 'mensaje' => 'plantilla_activada'],
                admin_url('admin.php')
            ));
        } else {
            wp_safe_redirect(add_query_arg(
                ['page' => 'flavor-app-composer', 'mensaje' => 'plantilla_error', 'error' => urlencode($resultado['mensaje'] ?? '')],
                admin_url('admin.php')
            ));
        }
        exit;
    }

    /**
     * AJAX: Obtiene el preview de una plantilla
     */
    public function ajax_obtener_preview() {
        check_ajax_referer('flavor_template_preview', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['mensaje' => __('Sin permisos', 'flavor-chat-ia')]);
            return;
        }

        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');

        if (empty($plantilla_id)) {
            wp_send_json_error(['mensaje' => __('ID de plantilla no especificado', 'flavor-chat-ia')]);
            return;
        }

        // Obtener datos del perfil
        $perfil = $this->gestor_perfiles->obtener_perfil($plantilla_id);

        if (!$perfil) {
            wp_send_json_error(['mensaje' => __('Plantilla no encontrada', 'flavor-chat-ia')]);
            return;
        }

        // Construir respuesta con datos de la plantilla
        $datos_preview = [
            'id' => $plantilla_id,
            'nombre' => $perfil['nombre'] ?? '',
            'descripcion' => $perfil['descripcion'] ?? '',
            'icono' => $perfil['icono'] ?? 'dashicons-admin-generic',
            'color' => $perfil['color'] ?? '#2271b1',
            'modulos_requeridos' => $perfil['modulos_requeridos'] ?? [],
            'modulos_opcionales' => $perfil['modulos_opcionales'] ?? [],
            'paginas' => $this->obtener_paginas_plantilla($plantilla_id),
            'landing' => $this->obtener_landing_plantilla($plantilla_id),
        ];

        wp_send_json_success($datos_preview);
    }

    /**
     * AJAX: Ejecuta un paso de instalacion
     */
    public function ajax_ejecutar_paso_instalacion() {
        error_log('[AJAX] Inicio ajax_ejecutar_paso_instalacion');
        check_ajax_referer('flavor_activar_plantilla', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['mensaje' => __('Sin permisos', 'flavor-chat-ia')]);
            return;
        }

        $paso = sanitize_text_field($_POST['paso'] ?? '');
        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $modulos_opcionales = json_decode(stripslashes($_POST['modulos_opcionales'] ?? '[]'), true);
        $cargar_demo = isset($_POST['cargar_demo']) && $_POST['cargar_demo'] === '1';

        error_log('[AJAX] Paso: ' . $paso . ', Plantilla: ' . $plantilla_id);

        if (empty($paso) || empty($plantilla_id)) {
            wp_send_json_error(['mensaje' => __('Parametros invalidos', 'flavor-chat-ia')]);
            return;
        }

        $opciones = [
            'modulos_opcionales' => is_array($modulos_opcionales) ? array_map('sanitize_text_field', $modulos_opcionales) : [],
            'cargar_demo' => $cargar_demo,
        ];

        // Ejecutar el paso correspondiente
        $resultado = $this->ejecutar_paso_instalacion($paso, $plantilla_id, $opciones);

        error_log('[AJAX] Resultado del paso ' . $paso . ': ' . ($resultado['success'] ? 'SUCCESS' : 'ERROR'));

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            error_log('[AJAX] Error: ' . json_encode($resultado));
            wp_send_json_error($resultado);
        }
    }

    /**
     * Ejecuta un paso individual de la instalacion
     *
     * @param string $paso ID del paso
     * @param string $plantilla_id ID de la plantilla
     * @param array $opciones Opciones de instalacion
     * @return array Resultado del paso
     */
    private function ejecutar_paso_instalacion($paso, $plantilla_id, $opciones) {
        $perfil = $this->gestor_perfiles->obtener_perfil($plantilla_id);

        if (!$perfil) {
            return [
                'success' => false,
                'mensaje' => __('Plantilla no encontrada', 'flavor-chat-ia'),
            ];
        }

        switch ($paso) {
            case 'modulos':
                return $this->paso_instalar_modulos($plantilla_id, $perfil, $opciones);

            case 'tablas':
                return $this->paso_crear_tablas($plantilla_id, $perfil, $opciones);

            case 'paginas':
                return $this->paso_crear_paginas($plantilla_id, $perfil, $opciones);

            case 'landing':
                return $this->paso_configurar_landing($plantilla_id, $perfil, $opciones);

            case 'configuracion':
                return $this->paso_aplicar_configuracion($plantilla_id, $perfil, $opciones);

            case 'demo':
                return $this->paso_cargar_datos_demo($plantilla_id, $perfil, $opciones);

            default:
                return [
                    'success' => false,
                    'mensaje' => __('Paso desconocido', 'flavor-chat-ia'),
                ];
        }
    }

    /**
     * Paso: Instalar modulos
     */
    private function paso_instalar_modulos($plantilla_id, $perfil, $opciones) {
        try {
            // Activar modulos requeridos
            $modulos_a_activar = $perfil['modulos_requeridos'] ?? [];

            // Agregar modulos opcionales seleccionados
            if (!empty($opciones['modulos_opcionales'])) {
                $modulos_a_activar = array_merge($modulos_a_activar, $opciones['modulos_opcionales']);
            }

            // Actualizar configuracion
            $configuracion = get_option('flavor_chat_ia_settings', []);
            $valor_previo = $configuracion['app_profile'] ?? 'NO_EXISTE';
            $configuracion['active_modules'] = array_unique($modulos_a_activar);
            $configuracion['app_profile'] = $plantilla_id;

            error_log('[Instalación] Guardando app_profile: ' . $plantilla_id);
            $resultado_update = update_option('flavor_chat_ia_settings', $configuracion);
            error_log('[Instalación] update_option resultado: ' . ($resultado_update ? 'true' : 'false'));

            // IMPORTANTE: Limpiar cache y volver a leer para detectar si algo lo sobrescribió
            wp_cache_delete('alloptions', 'options');

            // Esperar un momento para que otros hooks terminen
            usleep(100000); // 100ms

            // Re-leer y verificar
            $verificacion = get_option('flavor_chat_ia_settings', []);
            error_log('[Instalación] Primera verificación app_profile: ' . ($verificacion['app_profile'] ?? 'NO EXISTE'));

            // Si se perdió, volver a guardarlo
            if (($verificacion['app_profile'] ?? '') !== $plantilla_id) {
                error_log('[Instalación] ¡app_profile fue sobrescrito! Guardando de nuevo...');
                $verificacion['app_profile'] = $plantilla_id;
                update_option('flavor_chat_ia_settings', $verificacion);
                wp_cache_delete('alloptions', 'options');

                // Verificación final
                $verificacion_final = get_option('flavor_chat_ia_settings', []);
                error_log('[Instalación] Verificación FINAL app_profile: ' . ($verificacion_final['app_profile'] ?? 'NO EXISTE'));
            }

            $total_modulos = count($modulos_a_activar);

            return [
                'success' => true,
                'descripcion' => sprintf(
                    _n('%d modulo activado', '%d modulos activados', $total_modulos, 'flavor-chat-ia'),
                    $total_modulos
                ),
                'debug_app_profile_set' => $plantilla_id,
                'debug_update_result' => $resultado_update,
                'debug_valor_previo' => $valor_previo,
                'debug_valor_nuevo' => $plantilla_id,
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Paso: Crear tablas de base de datos
     */
    private function paso_crear_tablas($plantilla_id, $perfil, $opciones) {
        try {
            // Los modulos crean sus propias tablas al activarse
            // Este paso verifica que las tablas existan

            global $wpdb;
            $tablas_creadas = 0;

            // Lista de tablas por modulo (simplificado)
            $modulos_activos = array_merge(
                $perfil['modulos_requeridos'] ?? [],
                $opciones['modulos_opcionales'] ?? []
            );

            foreach ($modulos_activos as $modulo_id) {
                // Trigger de activacion del modulo si existe
                $nombre_clase = 'Flavor_Module_' . str_replace('_', '', ucwords($modulo_id, '_'));
                if (class_exists($nombre_clase) && method_exists($nombre_clase, 'activar')) {
                    call_user_func([$nombre_clase, 'activar']);
                    $tablas_creadas++;
                }
            }

            return [
                'success' => true,
                'descripcion' => __('Tablas verificadas', 'flavor-chat-ia'),
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Paso: Crear paginas
     */
    private function paso_crear_paginas($plantilla_id, $perfil, $opciones) {
        try {
            $paginas = $this->obtener_paginas_plantilla($plantilla_id);
            $paginas_creadas = 0;

            foreach ($paginas as $pagina_id => $pagina) {
                // Verificar si la pagina ya existe
                $pagina_existente = get_page_by_path($pagina['slug'] ?? $pagina_id);

                if (!$pagina_existente) {
                    $nueva_pagina_id = wp_insert_post([
                        'post_title' => $pagina['titulo'] ?? ucfirst(str_replace('-', ' ', $pagina_id)),
                        'post_name' => $pagina['slug'] ?? $pagina_id,
                        'post_content' => $pagina['contenido'] ?? '',
                        'post_status' => 'publish',
                        'post_type' => 'page',
                        'post_author' => get_current_user_id(),
                    ]);

                    if ($nueva_pagina_id && !is_wp_error($nueva_pagina_id)) {
                        $paginas_creadas++;

                        // Guardar meta de plantilla
                        update_post_meta($nueva_pagina_id, '_flavor_template', $plantilla_id);
                        update_post_meta($nueva_pagina_id, '_flavor_page_type', $pagina_id);
                    }
                }
            }

            return [
                'success' => true,
                'descripcion' => sprintf(
                    _n('%d pagina creada', '%d paginas creadas', $paginas_creadas, 'flavor-chat-ia'),
                    $paginas_creadas
                ),
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Paso: Configurar landing page
     */
    private function paso_configurar_landing($plantilla_id, $perfil, $opciones) {
        try {
            $landing = $this->obtener_landing_plantilla($plantilla_id);

            if (empty($landing)) {
                return [
                    'success' => true,
                    'descripcion' => __('Sin landing configurada', 'flavor-chat-ia'),
                ];
            }

            // Guardar configuracion de landing
            update_option('flavor_landing_' . $plantilla_id, $landing);

            $total_secciones = count($landing['secciones'] ?? []);

            return [
                'success' => true,
                'descripcion' => sprintf(
                    _n('%d seccion configurada', '%d secciones configuradas', $total_secciones, 'flavor-chat-ia'),
                    $total_secciones
                ),
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Paso: Aplicar configuracion
     */
    private function paso_aplicar_configuracion($plantilla_id, $perfil, $opciones) {
        try {
            // Flush de rewrite rules para las nuevas paginas
            flush_rewrite_rules();

            // Guardar que la plantilla esta activa
            update_option('flavor_plantilla_activa', $plantilla_id);
            update_option('flavor_plantilla_activada_en', current_time('mysql'));

            // Verificar app_profile ANTES de terminar este paso
            $config_check = get_option('flavor_chat_ia_settings', []);
            $app_profile_actual = $config_check['app_profile'] ?? 'NO_EXISTE';

            return [
                'success' => true,
                'descripcion' => __('Configuracion aplicada', 'flavor-chat-ia'),
                'debug_app_profile_final' => $app_profile_actual,
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Paso: Cargar datos de demostracion
     */
    private function paso_cargar_datos_demo($plantilla_id, $perfil, $opciones) {
        if (!$opciones['cargar_demo']) {
            return [
                'success' => true,
                'descripcion' => __('Omitido', 'flavor-chat-ia'),
            ];
        }

        try {
            // Verificar si el gestor de datos demo esta disponible
            if (!class_exists('Flavor_Demo_Data_Manager')) {
                return [
                    'success' => true,
                    'descripcion' => __('Gestor de demo no disponible', 'flavor-chat-ia'),
                ];
            }

            $demo_manager = Flavor_Demo_Data_Manager::get_instance();
            $modulos_activos = array_merge(
                $perfil['modulos_requeridos'] ?? [],
                $opciones['modulos_opcionales'] ?? []
            );

            $registros_creados = 0;

            foreach ($modulos_activos as $modulo_id) {
                if (method_exists($demo_manager, 'populate_demo_data')) {
                    $resultado = $demo_manager->populate_demo_data($modulo_id);
                    if ($resultado) {
                        $registros_creados += is_numeric($resultado) ? $resultado : 1;
                    }
                }
            }

            return [
                'success' => true,
                'descripcion' => sprintf(
                    _n('%d registro de demo creado', '%d registros de demo creados', $registros_creados, 'flavor-chat-ia'),
                    $registros_creados
                ),
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Activa una plantilla completa (modo sincrono)
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $opciones Opciones de instalacion
     * @return array Resultado
     */
    private function activar_plantilla_completa($plantilla_id, $opciones) {
        $perfil = $this->gestor_perfiles->obtener_perfil($plantilla_id);

        if (!$perfil) {
            return [
                'success' => false,
                'mensaje' => __('Plantilla no encontrada', 'flavor-chat-ia'),
            ];
        }

        $pasos = ['modulos', 'tablas', 'paginas', 'landing', 'configuracion'];

        if ($opciones['cargar_demo']) {
            $pasos[] = 'demo';
        }

        foreach ($pasos as $paso) {
            $resultado = $this->ejecutar_paso_instalacion($paso, $plantilla_id, $opciones);

            if (!$resultado['success']) {
                return $resultado;
            }
        }

        return [
            'success' => true,
            'mensaje' => __('Plantilla activada correctamente', 'flavor-chat-ia'),
        ];
    }

    /**
     * Obtiene las paginas definidas para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Paginas
     */
    private function obtener_paginas_plantilla($plantilla_id) {
        // Definicion de paginas por plantilla
        $paginas_por_plantilla = [
            'tienda' => [
                'tienda' => [
                    'titulo' => __('Tienda', 'flavor-chat-ia'),
                    'slug' => 'tienda',
                    'icono' => 'dashicons-store',
                    'contenido' => '[flavor_tienda]',
                ],
                'carrito' => [
                    'titulo' => __('Carrito', 'flavor-chat-ia'),
                    'slug' => 'carrito',
                    'icono' => 'dashicons-cart',
                    'contenido' => '[flavor_carrito]',
                ],
            ],
            'grupo_consumo' => [
                'grupos-consumo' => [
                    'titulo' => __('Grupos de Consumo', 'flavor-chat-ia'),
                    'slug' => 'grupos-consumo',
                    'icono' => 'dashicons-carrot',
                    'contenido' => '[flavor_grupos_consumo]',
                ],
                'productores' => [
                    'titulo' => __('Productores', 'flavor-chat-ia'),
                    'slug' => 'productores',
                    'icono' => 'dashicons-businessman',
                    'contenido' => '[flavor_productores]',
                ],
            ],
            'banco_tiempo' => [
                'banco-tiempo' => [
                    'titulo' => __('Banco de Tiempo', 'flavor-chat-ia'),
                    'slug' => 'banco-tiempo',
                    'icono' => 'dashicons-clock',
                    'contenido' => '[flavor_banco_tiempo]',
                ],
                'servicios' => [
                    'titulo' => __('Servicios', 'flavor-chat-ia'),
                    'slug' => 'servicios',
                    'icono' => 'dashicons-hammer',
                    'contenido' => '[flavor_servicios]',
                ],
            ],
            'comunidad' => [
                'comunidad' => [
                    'titulo' => __('Comunidad', 'flavor-chat-ia'),
                    'slug' => 'comunidad',
                    'icono' => 'dashicons-groups',
                    'contenido' => '[flavor_comunidad]',
                ],
                'eventos' => [
                    'titulo' => __('Eventos', 'flavor-chat-ia'),
                    'slug' => 'eventos',
                    'icono' => 'dashicons-calendar',
                    'contenido' => '[flavor_eventos]',
                ],
                'socios' => [
                    'titulo' => __('Socios', 'flavor-chat-ia'),
                    'slug' => 'socios',
                    'icono' => 'dashicons-id-alt',
                    'contenido' => '[flavor_socios]',
                ],
            ],
            'ayuntamiento' => [
                'ayuntamiento' => [
                    'titulo' => __('Ayuntamiento', 'flavor-chat-ia'),
                    'slug' => 'ayuntamiento',
                    'icono' => 'dashicons-building',
                    'contenido' => '[flavor_ayuntamiento]',
                ],
                'tramites' => [
                    'titulo' => __('Tramites', 'flavor-chat-ia'),
                    'slug' => 'tramites',
                    'icono' => 'dashicons-clipboard',
                    'contenido' => '[flavor_tramites]',
                ],
                'incidencias' => [
                    'titulo' => __('Incidencias', 'flavor-chat-ia'),
                    'slug' => 'incidencias',
                    'icono' => 'dashicons-warning',
                    'contenido' => '[flavor_incidencias]',
                ],
            ],
            'barrio' => [
                'barrio' => [
                    'titulo' => __('Mi Barrio', 'flavor-chat-ia'),
                    'slug' => 'barrio',
                    'icono' => 'dashicons-location',
                    'contenido' => '[flavor_barrio]',
                ],
                'ayuda-vecinal' => [
                    'titulo' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                    'slug' => 'ayuda-vecinal',
                    'icono' => 'dashicons-heart',
                    'contenido' => '[flavor_ayuda_vecinal]',
                ],
                'huertos' => [
                    'titulo' => __('Huertos Urbanos', 'flavor-chat-ia'),
                    'slug' => 'huertos',
                    'icono' => 'dashicons-palmtree',
                    'contenido' => '[flavor_huertos]',
                ],
            ],
        ];

        return $paginas_por_plantilla[$plantilla_id] ?? [];
    }

    /**
     * Obtiene la configuracion de landing para una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Configuracion de landing
     */
    private function obtener_landing_plantilla($plantilla_id) {
        // Definicion de landings por plantilla
        $landings_por_plantilla = [
            'tienda' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'productos' => ['titulo' => __('Productos Destacados', 'flavor-chat-ia')],
                    'categorias' => ['titulo' => __('Categorias', 'flavor-chat-ia')],
                    'testimonios' => ['titulo' => __('Testimonios', 'flavor-chat-ia')],
                    'newsletter' => ['titulo' => __('Newsletter', 'flavor-chat-ia')],
                ],
            ],
            'grupo_consumo' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'como_funciona' => ['titulo' => __('Como Funciona', 'flavor-chat-ia')],
                    'productores' => ['titulo' => __('Productores', 'flavor-chat-ia')],
                    'beneficios' => ['titulo' => __('Beneficios', 'flavor-chat-ia')],
                    'cta' => ['titulo' => __('Llamada a la Accion', 'flavor-chat-ia')],
                ],
            ],
            'banco_tiempo' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'servicios' => ['titulo' => __('Servicios Disponibles', 'flavor-chat-ia')],
                    'como_funciona' => ['titulo' => __('Como Funciona', 'flavor-chat-ia')],
                    'estadisticas' => ['titulo' => __('Estadisticas', 'flavor-chat-ia')],
                    'cta' => ['titulo' => __('Unete', 'flavor-chat-ia')],
                ],
            ],
            'comunidad' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'eventos' => ['titulo' => __('Proximos Eventos', 'flavor-chat-ia')],
                    'grupos' => ['titulo' => __('Grupos', 'flavor-chat-ia')],
                    'miembros' => ['titulo' => __('Miembros Destacados', 'flavor-chat-ia')],
                    'contacto' => ['titulo' => __('Contacto', 'flavor-chat-ia')],
                ],
            ],
            'ayuntamiento' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'servicios' => ['titulo' => __('Servicios', 'flavor-chat-ia')],
                    'noticias' => ['titulo' => __('Noticias', 'flavor-chat-ia')],
                    'tramites' => ['titulo' => __('Tramites Online', 'flavor-chat-ia')],
                    'contacto' => ['titulo' => __('Contacto', 'flavor-chat-ia')],
                ],
            ],
            'barrio' => [
                'secciones' => [
                    'hero' => ['titulo' => __('Hero', 'flavor-chat-ia')],
                    'servicios' => ['titulo' => __('Servicios del Barrio', 'flavor-chat-ia')],
                    'ayuda' => ['titulo' => __('Ayuda Vecinal', 'flavor-chat-ia')],
                    'actividades' => ['titulo' => __('Actividades', 'flavor-chat-ia')],
                    'mapa' => ['titulo' => __('Mapa', 'flavor-chat-ia')],
                ],
            ],
        ];

        return $landings_por_plantilla[$plantilla_id] ?? [];
    }
}
