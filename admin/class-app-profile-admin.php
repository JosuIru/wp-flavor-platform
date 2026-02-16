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
        add_action('admin_post_flavor_chat_ia_crear_landing_frontend', [$this, 'procesar_crear_landing_frontend']);
        add_action('admin_post_flavor_chat_ia_crear_todas_landings_frontend', [$this, 'procesar_crear_todas_landings_frontend']);
        add_action('admin_enqueue_scripts', [$this, 'cargar_estilos_admin']);

        // Hooks del Template Orchestrator
        add_action('admin_post_flavor_activar_plantilla', [$this, 'procesar_activacion_plantilla']);
        add_action('wp_ajax_flavor_template_preview', [$this, 'ajax_obtener_preview']);
        add_action('wp_ajax_flavor_template_install_step', [$this, 'ajax_ejecutar_paso_instalacion']);

        // AJAX para toggle de módulos sin recargar página
        add_action('wp_ajax_flavor_toggle_modulo', [$this, 'ajax_toggle_modulo']);
    }


    /**
     * Carga estilos y scripts para el admin del compositor
     */
    public function cargar_estilos_admin($sufijo_hook) {
        // Solo cargar en la pagina del compositor de aplicacion
        $page_param = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $screen_id = '';
        if (function_exists('get_current_screen')) {
            $screen = get_current_screen();
            $screen_id = $screen ? $screen->id : '';
        }
        if (
            strpos($sufijo_hook, 'flavor-app-composer') === false &&
            $page_param !== 'flavor-app-composer' &&
            strpos($screen_id, 'flavor-app-composer') === false
        ) {
            return;
        }

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        // CSS de tokens de diseño (base para todos los estilos)
        wp_enqueue_style(
            'flavor-design-tokens',
            FLAVOR_CHAT_IA_URL . "admin/css/design-tokens{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

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

        // Registrar el plugin focus antes del arranque de Alpine
        wp_add_inline_script(
            'alpinejs-focus',
            "document.addEventListener('alpine:init', function() {
                if (window.Alpine && window.AlpineFocus) {
                    window.Alpine.plugin(window.AlpineFocus);
                }
            });"
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

        // Forzar carga de scripts/estilos del compositor y pintarlos en la página
        $this->cargar_estilos_admin('flavor-app-composer');
        wp_print_styles(['flavor-design-tokens', 'flavor-app-composer', 'flavor-template-orchestrator']);

        $id_perfil_actual = $this->gestor_perfiles->obtener_perfil_activo();
        $perfiles_activos = $this->gestor_perfiles->obtener_perfiles_activos(); // Múltiples perfiles
        $perfiles = $this->gestor_perfiles->obtener_perfiles();
        $categorias_modulos = $this->gestor_perfiles->obtener_categorias_modulos();
        $tipos_organizacion = $this->gestor_perfiles->obtener_tipos_organizacion();
        $impactos_sociales = $this->gestor_perfiles->obtener_impactos_sociales();
        $landing_tags = $this->obtener_tags_landings();
        $configuracion = get_option('flavor_chat_ia_settings', []);
        $modulos_activos = $configuracion['active_modules'] ?? [];

        // Debug: verificar qué perfiles están activos
        flavor_log_debug( 'Perfiles activos: ' . implode(', ', $perfiles_activos), 'AppProfile' );

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos_registrados = $loader->get_registered_modules();

        // Datos para Alpine.js
        $datos_compositor = [
            'perfilActivo'      => $id_perfil_actual,
            'perfilesActivos'   => array_values($perfiles_activos), // Array de perfiles activos
            'modulosActivos'    => array_values($modulos_activos),
            'perfiles'          => $perfiles,
            'categorias'        => $categorias_modulos,
            'tiposOrganizacion' => $tipos_organizacion,
            'impactosSociales'  => $impactos_sociales,
            'landingTags'       => $landing_tags,
            'modulosRegistrados' => $modulos_registrados,
            'adminPostUrl'      => admin_url('admin-post.php'),
            'nonces'            => [
                'cambiarPerfil' => wp_create_nonce('cambiar_perfil'),
                'toggleModulo'  => wp_create_nonce('toggle_modulo'),
            ],
            'i18n' => [
                'confirmarCambioPerfil' => __('Deseas cambiar de plantilla? Esto activara los modulos correspondientes.', 'flavor-chat-ia'),
                'confirmarPerfilesMultiples' => __('Se activaran los modulos de todos los perfiles seleccionados. ¿Continuar?', 'flavor-chat-ia'),
            ],
        ];

        wp_localize_script('flavor-app-composer', 'flavorComposerData', $datos_compositor);
        wp_print_scripts(['flavor-app-composer', 'flavor-template-orchestrator', 'alpinejs', 'alpinejs-focus']);

        // Mostrar mensajes de resultado
        $mensaje = isset($_GET['mensaje']) ? sanitize_text_field($_GET['mensaje']) : '';
        ?>
        <div class="wrap flavor-composer-wrapper"
             x-data="window.flavorComposerState ? flavorComposerState() : { pasoActual: 'plantillas', modoMultiSeleccion: false, perfilesSeleccionados: [] }"
             x-init="pasoActual = pasoActual || 'plantillas'">
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
                <div class="flavor-composer-filters" style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center; margin: 10px 0 20px;">
                    <input type="search"
                           class="regular-text"
                           placeholder="<?php esc_attr_e('Buscar perfiles o módulos...', 'flavor-chat-ia'); ?>"
                           style="min-width: 260px;"
                           x-model="filtroPerfilTexto">
                    <select class="regular-text" style="min-width: 220px;" x-model="filtroPerfilTipo">
                        <option value="todos"><?php esc_html_e('Tipo de organización', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($tipos_organizacion as $tipo_id => $tipo_label): ?>
                            <option value="<?php echo esc_attr($tipo_id); ?>"><?php echo esc_html($tipo_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select class="regular-text" style="min-width: 220px;" x-model="filtroPerfilImpacto">
                        <option value="todos"><?php esc_html_e('Impacto social', 'flavor-chat-ia'); ?></option>
                        <?php foreach ($impactos_sociales as $impacto_id => $impacto_label): ?>
                            <option value="<?php echo esc_attr($impacto_id); ?>"><?php echo esc_html($impacto_label); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="flavor-category-tabs" style="margin: 0;">
                        <span class="flavor-category-tab"
                              :class="{ 'activo': filtroPerfilCategoria === 'todos' }"
                              @click="filtroPerfilCategoria = 'todos'">
                            <?php _e('Todos los sectores', 'flavor-chat-ia'); ?>
                        </span>
                        <?php foreach ($categorias_modulos as $id_categoria => $datos_categoria): ?>
                            <span class="flavor-category-tab"
                                  :class="{ 'activo': filtroPerfilCategoria === '<?php echo esc_js($id_categoria); ?>' }"
                                  @click="filtroPerfilCategoria = '<?php echo esc_js($id_categoria); ?>'">
                                <?php echo esc_html($datos_categoria['nombre']); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="button" @click="filtroPerfilTexto = ''; filtroPerfilCategoria = 'todos'; filtroPerfilTipo = 'todos'; filtroPerfilImpacto = 'todos'">
                        <?php _e('Limpiar filtros', 'flavor-chat-ia'); ?>
                    </button>
                </div>

                <!-- Botón para activar selección múltiple -->
                <div class="flavor-multi-select-toolbar" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; padding: 15px; background: #fff; border: 1px solid #ddd; border-radius: 8px;">
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" x-model="modoMultiSeleccion" @change="actualizarModoMulti()">
                        <span style="font-weight: 500;"><?php _e('Activar varios perfiles a la vez', 'flavor-chat-ia'); ?></span>
                    </label>

                    <div x-show="modoMultiSeleccion && perfilesSeleccionados.length > 0" x-transition style="display: flex; gap: 10px; align-items: center;">
                        <span style="color: #666; font-size: 13px;">
                            <span x-text="perfilesSeleccionados.length"></span> <?php _e('perfiles seleccionados', 'flavor-chat-ia'); ?>
                        </span>
                        <button @click="aplicarPerfilesMultiples()" class="button button-primary" type="button">
                            <span class="dashicons dashicons-yes-alt" style="margin-top: 3px;"></span>
                            <?php _e('Aplicar selección', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </div>

                <div class="flavor-templates-grid">
                    <?php foreach ($perfiles as $id_perfil => $datos_perfil): ?>
                        <div class="flavor-template-card"
                             x-show="perfilCoincideFiltro('<?php echo esc_js($id_perfil); ?>')"
                             :class="{ 'seleccionado': modoMultiSeleccion ? perfilesSeleccionados.includes('<?php echo esc_js($id_perfil); ?>') : esPerfilActivo('<?php echo esc_js($id_perfil); ?>') }"
                             @click="modoMultiSeleccion ? togglePerfilSeleccion('<?php echo esc_js($id_perfil); ?>') : (<?php echo ($id_perfil !== 'personalizado') ? "abrirPreviewPlantilla('" . esc_js($id_perfil) . "')" : "cambiarPerfil('" . esc_js($id_perfil) . "')"; ?>)">

                            <!-- Checkbox para multi-selección -->
                            <div x-show="modoMultiSeleccion" class="flavor-template-checkbox" style="position: absolute; top: 10px; left: 10px; z-index: 10;">
                                <input type="checkbox"
                                       :checked="perfilesSeleccionados.includes('<?php echo esc_js($id_perfil); ?>')"
                                       @click.stop="togglePerfilSeleccion('<?php echo esc_js($id_perfil); ?>')"
                                       style="width: 20px; height: 20px; cursor: pointer;">
                            </div>

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
                                <button
                                    type="button"
                                    class="button button-small"
                                    :class="{ 'button-primary': !esPerfilActivo('<?php echo esc_js($id_perfil); ?>') }"
                                    x-text="esPerfilActivo('<?php echo esc_js($id_perfil); ?>') ? '<?php echo esc_js(__('Activo', 'flavor-chat-ia')); ?>' : '<?php echo esc_js(__('Ver preview', 'flavor-chat-ia')); ?>'">
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Paso 2: Vista Unificada de Módulos -->
            <div x-show="pasoActual === 'modulos'" x-transition>
                <?php
                // Renderizar la vista unificada de módulos que combina:
                // - Activación/Desactivación
                // - Control de visibilidad y permisos
                // - Gestión de landings
                if (class_exists('Flavor_Unified_Modules_View')) {
                    Flavor_Unified_Modules_View::get_instance()->render();
                }
                ?>
            </div>

            <?php
            // NOTA: Las secciones de modulos frontend, visibilidad y demo pages
            // ahora están integradas en la vista unificada de módulos arriba

            // La seccion de paginas de demostracion se muestra junto a landings

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
     * Renderiza la seccion de control de acceso/visibilidad de modulos
     */
    private function renderizar_seccion_visibilidad_modulos() {
        // Verificar que existe la clase de administracion de visibilidad
        if (!class_exists('Flavor_Module_Visibility_Admin')) {
            return;
        }

        $admin_visibilidad = Flavor_Module_Visibility_Admin::get_instance();
        echo $admin_visibilidad->renderizar_seccion_visibilidad();
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
     * Normaliza IDs de modulos y elimina duplicados.
     * Convierte a formato slug con underscore para evitar variantes repetidas.
     *
     * @param array $modulos
     * @return array
     */
    private function normalizar_ids_modulos($modulos) {
        if (!is_array($modulos)) {
            return [];
        }

        $normalizados = [];
        foreach ($modulos as $modulo) {
            $id = sanitize_key((string) $modulo);
            $id = str_replace('-', '_', $id);
            $id = trim($id, '_');

            if ($id === '') {
                continue;
            }

            $normalizados[] = $id;
        }

        return array_values(array_unique($normalizados));
    }

    /**
     * Define etiquetas para filtrar landings, demo pages y modulos.
     *
     * @return array
     */
    private function obtener_tags_landings() {
        return [
            'grupos_consumo' => [
                'tipos' => ['comunidad', 'cooperativa', 'empresa'],
                'impactos' => ['consumo_local', 'economia_circular'],
            ],
            'banco_tiempo' => [
                'tipos' => ['comunidad', 'asociacion'],
                'impactos' => ['solidaridad', 'intercambio'],
            ],
            'ayuntamiento' => [
                'tipos' => ['publico', 'administracion'],
                'impactos' => ['transparencia', 'participacion'],
            ],
            'comunidades' => [
                'tipos' => ['comunidad'],
                'impactos' => ['cohesion_social'],
            ],
            'espacios_comunes' => [
                'tipos' => ['empresa', 'comunidad', 'espacios'],
                'impactos' => ['economia_colaborativa'],
            ],
            'ayuda_vecinal' => [
                'tipos' => ['comunidad'],
                'impactos' => ['solidaridad', 'cohesion_social'],
            ],
            'huertos_urbanos' => [
                'tipos' => ['comunidad'],
                'impactos' => ['sostenibilidad'],
            ],
            'biblioteca' => [
                'tipos' => ['comunidad', 'educacion'],
                'impactos' => ['cultura', 'educacion'],
            ],
            'cursos' => [
                'tipos' => ['educacion', 'empresa', 'comunidad'],
                'impactos' => ['educacion'],
            ],
            'podcast' => [
                'tipos' => ['medios', 'comunidad'],
                'impactos' => ['cultura'],
            ],
            'radio' => [
                'tipos' => ['medios', 'comunidad'],
                'impactos' => ['cultura'],
            ],
            'bicicletas' => [
                'tipos' => ['comunidad', 'publico'],
                'impactos' => ['movilidad', 'sostenibilidad'],
            ],
            'reciclaje' => [
                'tipos' => ['comunidad', 'cooperativa', 'empresa'],
                'impactos' => ['sostenibilidad', 'economia_circular'],
            ],
            'tienda_local' => [
                'tipos' => ['empresa', 'comunidad'],
                'impactos' => ['consumo_local', 'economia_circular'],
            ],
            'marketplace' => [
                'tipos' => ['empresa', 'comunidad'],
                'impactos' => ['economia_circular'],
            ],
            'eventos' => [
                'tipos' => ['comunidad', 'empresa', 'publico'],
                'impactos' => ['cohesion_social', 'cultura'],
            ],
            'incidencias' => [
                'tipos' => ['publico', 'comunidad'],
                'impactos' => ['participacion'],
            ],
            'empresarial' => [
                'tipos' => ['empresa'],
                'impactos' => [],
            ],
            'clientes' => [
                'tipos' => ['empresa'],
                'impactos' => [],
            ],
            'socios' => [
                'tipos' => ['comunidad', 'cooperativa', 'asociacion', 'ong'],
                'impactos' => ['cohesion_social'],
            ],
            'talleres' => [
                'tipos' => ['comunidad', 'educacion', 'empresa'],
                'impactos' => ['educacion', 'cultura'],
            ],
            'reservas' => [
                'tipos' => ['empresa', 'espacios'],
                'impactos' => [],
            ],
            'facturas' => [
                'tipos' => ['empresa'],
                'impactos' => ['transparencia'],
            ],
            'tramites' => [
                'tipos' => ['publico', 'administracion'],
                'impactos' => ['participacion', 'transparencia'],
            ],
            'avisos_municipales' => [
                'tipos' => ['publico', 'administracion'],
                'impactos' => ['participacion'],
            ],
            'red_social' => [
                'tipos' => ['comunidad', 'empresa'],
                'impactos' => ['cohesion_social'],
            ],
            'network' => [
                'tipos' => ['red', 'comunidad', 'empresa'],
                'impactos' => ['intercooperacion'],
            ],
            'restaurante' => [
                'tipos' => ['empresa', 'hosteleria'],
                'impactos' => [],
            ],
            'peluqueria' => [
                'tipos' => ['empresa'],
                'impactos' => [],
            ],
            'gimnasio' => [
                'tipos' => ['empresa', 'deporte'],
                'impactos' => ['salud'],
            ],
            'clinica' => [
                'tipos' => ['empresa'],
                'impactos' => ['salud'],
            ],
            'hotel' => [
                'tipos' => ['empresa', 'hosteleria'],
                'impactos' => [],
            ],
            'inmobiliaria' => [
                'tipos' => ['empresa'],
                'impactos' => [],
            ],
        ];
    }

    /**
     * Procesa el cambio de perfil (soporta único o múltiple)
     */
    public function procesar_cambio_perfil() {
        check_admin_referer('cambiar_perfil');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $regenerar_paginas = isset($_POST['regenerar_paginas']) && $_POST['regenerar_paginas'] === '1';
        $menu_sync = sanitize_text_field($_POST['menu_sync'] ?? 'merge');

        // Soportar múltiples perfiles
        if (isset($_POST['perfiles']) && is_array($_POST['perfiles'])) {
            $perfiles_ids = array_map('sanitize_text_field', $_POST['perfiles']);

            if ($this->gestor_perfiles->establecer_perfiles($perfiles_ids)) {
                $configuracion = get_option('flavor_chat_ia_settings', []);
                $modulos_activos = $this->normalizar_ids_modulos($configuracion['active_modules'] ?? []);
                if ($regenerar_paginas && !empty($modulos_activos)) {
                    Flavor_Page_Creator::create_pages_for_modules($modulos_activos);
                }
                $this->sincronizar_menu_principal(null, [
                    'modulos_activos' => $modulos_activos,
                    'menu_sync' => $menu_sync,
                ]);
                wp_safe_redirect(add_query_arg(
                    ['page' => 'flavor-app-composer', 'mensaje' => 'perfiles_cambiados'],
                    admin_url('admin.php')
                ));
            } else {
                wp_safe_redirect(add_query_arg(
                    ['page' => 'flavor-app-composer', 'error' => 'perfiles_invalidos'],
                    admin_url('admin.php')
                ));
            }
        }
        // Compatibilidad con perfil único
        else {
            $perfil_id = sanitize_text_field($_POST['perfil_id'] ?? '');

            if ($this->gestor_perfiles->establecer_perfil($perfil_id)) {
                $configuracion = get_option('flavor_chat_ia_settings', []);
                $modulos_activos = $this->normalizar_ids_modulos($configuracion['active_modules'] ?? []);
                if ($regenerar_paginas && !empty($modulos_activos)) {
                    Flavor_Page_Creator::create_pages_for_modules($modulos_activos);
                }
                $this->sincronizar_menu_principal(null, [
                    'modulos_activos' => $modulos_activos,
                    'menu_sync' => $menu_sync,
                ]);
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
     * AJAX handler para toggle de módulos (sin recargar página)
     */
    public function ajax_toggle_modulo() {
        // Verificar nonce
        if (!check_ajax_referer('toggle_modulo', '_ajax_nonce', false)) {
            wp_send_json_error([
                'message' => __('Nonce de seguridad inválido.', 'flavor-chat-ia')
            ]);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error([
                'message' => __('No tienes permisos para realizar esta acción.', 'flavor-chat-ia')
            ]);
            return;
        }

        $modulo_id = sanitize_text_field($_POST['modulo_id'] ?? '');
        $activar = isset($_POST['activar']) && $_POST['activar'] === '1';

        if (empty($modulo_id)) {
            wp_send_json_error([
                'message' => __('ID de módulo no válido.', 'flavor-chat-ia')
            ]);
            return;
        }

        // Log para debug
        flavor_log_debug( sprintf( 'Toggle módulo: %s, activar: %s', $modulo_id, $activar ? 'SÍ' : 'NO' ), 'AppProfile' );

        try {
            // Verificar si es un módulo requerido ANTES de intentar desactivarlo
            if (!$activar) {
                $modulos_requeridos = $this->gestor_perfiles->obtener_modulos_requeridos();
                flavor_log_debug( 'Módulos requeridos del perfil: ' . implode(', ', $modulos_requeridos), 'AppProfile' );
                flavor_log_debug( 'Intentando desactivar: ' . $modulo_id, 'AppProfile' );
                flavor_log_debug( '¿Es requerido?: ' . (in_array($modulo_id, $modulos_requeridos) ? 'SÍ' : 'NO'), 'AppProfile' );

                if (in_array($modulo_id, $modulos_requeridos)) {
                    wp_send_json_error([
                        'message' => sprintf(
                            __('No se puede desactivar "%s" porque es un módulo requerido por el perfil actual.', 'flavor-chat-ia'),
                            $modulo_id
                        ),
                        'modulo_requerido' => true
                    ]);
                    return;
                }
            }

            $resultado = false;

            if ($activar) {
                $resultado = $this->gestor_perfiles->activar_modulo_opcional($modulo_id);
            } else {
                $resultado = $this->gestor_perfiles->desactivar_modulo_opcional($modulo_id);
            }

            // Log del resultado
            flavor_log_debug( sprintf( 'Resultado operación: %s', $resultado ? 'OK' : 'FAIL' ), 'AppProfile' );

            // Si la operación falló, enviar error
            if ($resultado === false) {
                wp_send_json_error([
                    'message' => $activar
                        ? __('No se pudo activar el módulo. Verifica que exista y cumpla los requisitos.', 'flavor-chat-ia')
                        : __('No se pudo desactivar el módulo. Puede ser un módulo requerido.', 'flavor-chat-ia')
                ]);
                return;
            }

            // Forzar limpieza TOTAL de caché
            wp_cache_flush();

            // Leer DIRECTAMENTE de la base de datos para evitar cualquier caché
            global $wpdb;
            $configuracion_raw = $wpdb->get_var("SELECT option_value FROM {$wpdb->options} WHERE option_name = 'flavor_chat_ia_settings'");
            $configuracion = maybe_unserialize($configuracion_raw);
            $modulos_activos = $configuracion['active_modules'] ?? [];

            // Log de módulos activos
            flavor_log_debug( 'Módulos activos después del cambio (DIRECTO DE BD): ' . implode(', ', $modulos_activos), 'AppProfile' );
            flavor_log_debug( '¿Módulo está en el array?: ' . (in_array($modulo_id, $modulos_activos) ? 'SÍ' : 'NO'), 'AppProfile' );

            // Verificar que el cambio se aplicó correctamente
            $modulo_esta_activo = in_array($modulo_id, $modulos_activos);
            if ($activar && !$modulo_esta_activo) {
                wp_send_json_error([
                    'message' => __('El módulo no se activó correctamente. Verifica los logs del servidor.', 'flavor-chat-ia')
                ]);
                return;
            }
            if (!$activar && $modulo_esta_activo) {
                wp_send_json_error([
                    'message' => __('El módulo no se desactivó correctamente. Puede ser un módulo requerido.', 'flavor-chat-ia')
                ]);
                return;
            }

            wp_send_json_success([
                'message' => $activar
                    ? __('Módulo activado correctamente.', 'flavor-chat-ia')
                    : __('Módulo desactivado correctamente.', 'flavor-chat-ia'),
                'modulo_id' => $modulo_id,
                'activado' => $activar,
                'modulos_activos' => array_values($modulos_activos)
            ]);
        } catch (Exception $e) {
            flavor_log_error( 'Error en toggle: ' . $e->getMessage(), 'AppProfile' );
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
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
     * Crea una landing frontend si no existe.
     */
    public function procesar_crear_landing_frontend() {
        check_admin_referer('crear_landing_frontend');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulo_slug = sanitize_title($_POST['modulo_slug'] ?? '');
        if (empty($modulo_slug)) {
            wp_safe_redirect(add_query_arg(
                ['page' => 'flavor-app-composer', 'mensaje' => 'landing_error'],
                admin_url('admin.php')
            ));
            exit;
        }

        $pagina_existente = get_page_by_path($modulo_slug);
        $contenido = sprintf('[flavor_landing module="%s"]', $modulo_slug);
        $usuario_id = get_current_user_id();

        if ($pagina_existente) {
            $post_id = $pagina_existente->ID;
            if (get_post_meta($post_id, '_flavor_demo_page', true)) {
                wp_update_post([
                    'ID' => $post_id,
                    'post_title' => $pagina_existente->post_title,
                    'post_content' => $contenido,
                    'post_status' => 'publish',
                ]);
            }
        } else {
            $post_id = wp_insert_post([
                'post_title' => ucwords(str_replace('-', ' ', $modulo_slug)),
                'post_name' => $modulo_slug,
                'post_content' => $contenido,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => $usuario_id,
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
        }

        if ($post_id && !is_wp_error($post_id)) {
            update_post_meta($post_id, '_wp_page_template', 'flavor-landing');
            update_post_meta($post_id, '_flavor_landing_module', $modulo_slug);
            update_post_meta($post_id, '_flavor_auto_page_modules', str_replace('-', '_', $modulo_slug));
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => 'landing_creada'],
            admin_url('admin.php')
        ));
        exit;
    }

    /**
     * Crea todas las landings frontend faltantes.
     */
    public function procesar_crear_todas_landings_frontend() {
        check_admin_referer('crear_landing_frontend');

        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para realizar esta acción.', 'flavor-chat-ia'));
        }

        $modulos_frontend = $this->obtener_modulos_frontend();
        foreach ($modulos_frontend as $modulo_slug => $modulo) {
            if (get_page_by_path($modulo_slug)) {
                continue;
            }
            $contenido = sprintf('[flavor_landing module="%s"]', $modulo_slug);
            $post_id = wp_insert_post([
                'post_title' => $modulo['name'] ?? ucwords(str_replace('-', ' ', $modulo_slug)),
                'post_name' => $modulo_slug,
                'post_content' => $contenido,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id(),
                'comment_status' => 'closed',
                'ping_status' => 'closed',
            ]);
            if ($post_id && !is_wp_error($post_id)) {
                update_post_meta($post_id, '_wp_page_template', 'flavor-landing');
                update_post_meta($post_id, '_flavor_landing_module', $modulo_slug);
                update_post_meta($post_id, '_flavor_auto_page_modules', str_replace('-', '_', $modulo_slug));
            }
        }

        wp_safe_redirect(add_query_arg(
            ['page' => 'flavor-app-composer', 'mensaje' => 'landings_creadas'],
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
            'empresarial' => [
                'name' => __('Empresarial', 'flavor-chat-ia'),
                'description' => __('Landing corporativa y herramientas de gestion empresarial', 'flavor-chat-ia'),
                'icon' => 'dashicons-briefcase',
                'color' => '#1f2937',
                'url' => home_url('/empresarial/'),
            ],
            'clientes' => [
                'name' => __('Clientes (CRM)', 'flavor-chat-ia'),
                'description' => __('Gestion de clientes, pipeline y contactos comerciales', 'flavor-chat-ia'),
                'icon' => 'dashicons-id',
                'color' => '#0ea5e9',
                'url' => home_url('/clientes/'),
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
            <h2><?php _e('Landings y Páginas de Demostración', 'flavor-chat-ia'); ?></h2>
            <p class="description">
                <?php _e('Gestiona qué landings están públicas y crea páginas demo para pruebas o presentaciones.', 'flavor-chat-ia'); ?>
            </p>

            <h3 style="margin-top: 25px;"><?php _e('Landings / Módulos Frontend', 'flavor-chat-ia'); ?></h3>
            <p class="description">
                <?php _e('Activa o desactiva las páginas públicas (landings) disponibles para tu sitio.', 'flavor-chat-ia'); ?>
            </p>
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin:10px 0 15px;">
                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display:inline;">
                    <?php wp_nonce_field('crear_landing_frontend'); ?>
                    <input type="hidden" name="action" value="flavor_chat_ia_crear_todas_landings_frontend">
                    <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('¿Crear todas las landings faltantes? Las existentes no se sobrescribirán.', 'flavor-chat-ia'); ?>');">
                        <?php _e('Crear todas las landings faltantes', 'flavor-chat-ia'); ?>
                    </button>
                </form>
            </div>

            <div class="flavor-app-profiles" style="margin-top: 20px;">
                <?php foreach ($modulos_frontend as $modulo_id => $modulo): ?>
                    <?php
                    $modulo_categoria_id = str_replace('-', '_', $modulo_id);
                    $pagina_landing = get_page_by_path($modulo_id);
                    $landing_url = $pagina_landing ? get_permalink($pagina_landing->ID) : $modulo['url'];
                    ?>
                    <div class="flavor-profile-card <?php echo $modulo['activo'] ? 'activo' : ''; ?>"
                         x-show="landingCoincideFiltro('<?php echo esc_js($modulo['name']); ?>', '<?php echo esc_js($modulo['description']); ?>', moduleCategoryMap['<?php echo esc_js($modulo_categoria_id); ?>'] || [], (landingTags['<?php echo esc_js($modulo_categoria_id); ?>']?.tipos || []), (landingTags['<?php echo esc_js($modulo_categoria_id); ?>']?.impactos || []))"
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

                        <div style="display: flex; gap: 10px; margin-top: 15px; flex-wrap: wrap;">
                            <?php if ($pagina_landing): ?>
                                <a href="<?php echo esc_url($landing_url); ?>" target="_blank"
                                   class="button button-secondary" style="flex: 1; text-align: center;">
                                    <?php _e('Ver Landing', 'flavor-chat-ia'); ?> ↗
                                </a>
                            <?php else: ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="flex: 1;">
                                    <?php wp_nonce_field('crear_landing_frontend'); ?>
                                    <input type="hidden" name="action" value="flavor_chat_ia_crear_landing_frontend">
                                    <input type="hidden" name="modulo_slug" value="<?php echo esc_attr($modulo_id); ?>">
                                    <button type="submit" class="button button-secondary" style="width: 100%;">
                                        <?php _e('Crear Landing', 'flavor-chat-ia'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                            <button class="button <?php echo $modulo['activo'] ? '' : 'button-primary'; ?>"
                                    onclick="toggleModuloFrontend('<?php echo esc_js($modulo_id); ?>', <?php echo $modulo['activo'] ? 'false' : 'true'; ?>)"
                                    style="flex: 1;">
                                <?php echo $modulo['activo'] ? __('Desactivar', 'flavor-chat-ia') : __('Activar', 'flavor-chat-ia'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php $this->renderizar_seccion_demo_pages(true); ?>
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
    private function renderizar_seccion_demo_pages($embedded = false) {
        // Verificar si el gestor de datos demo está disponible
        if (!class_exists('Flavor_Demo_Data_Manager')) {
            return;
        }

        $demo_manager = Flavor_Demo_Data_Manager::get_instance();
        $demo_scope = sanitize_text_field($_GET['demo_scope'] ?? 'active');
        if (!in_array($demo_scope, ['active', 'all'], true)) {
            $demo_scope = 'active';
        }
        $paginas = $demo_manager->get_demo_pages_list($demo_scope);
        $count_paginas_demo = count(array_filter($paginas, function($pagina) {
            return !empty($pagina['is_demo']);
        }));
        $tiene_paginas_demo = $count_paginas_demo > 0;

        // Mostrar mensajes de resultado
        $mensaje = isset($_GET['mensaje']) ? sanitize_text_field($_GET['mensaje']) : '';
        ?>
        <div class="flavor-demo-pages-section" style="<?php echo $embedded ? 'margin-top: 35px;' : 'margin-top: 40px;'; ?>">
            <?php if ($embedded): ?>
                <h3><?php _e('Páginas de Demostración', 'flavor-chat-ia'); ?></h3>
            <?php else: ?>
                <h2><?php _e('Páginas de Demostración', 'flavor-chat-ia'); ?></h2>
            <?php endif; ?>
            <p class="description">
                <?php _e('Crea páginas de WordPress con las landings de cada módulo. Útil para probar y mostrar a clientes.', 'flavor-chat-ia'); ?>
            </p>
            <div style="display: flex; gap: 8px; margin: 10px 0 15px; flex-wrap: wrap;">
                <a class="button <?php echo $demo_scope === 'active' ? 'button-primary' : ''; ?>"
                   href="<?php echo esc_url(add_query_arg(['page' => 'flavor-app-composer', 'demo_scope' => 'active'], admin_url('admin.php'))); ?>">
                    <?php _e('Solo módulos activos', 'flavor-chat-ia'); ?>
                </a>
                <a class="button <?php echo $demo_scope === 'all' ? 'button-primary' : ''; ?>"
                   href="<?php echo esc_url(add_query_arg(['page' => 'flavor-app-composer', 'demo_scope' => 'all'], admin_url('admin.php'))); ?>">
                    <?php _e('Todos los módulos', 'flavor-chat-ia'); ?>
                </a>
            </div>

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
                            <input type="hidden" name="demo_scope" value="<?php echo esc_attr($demo_scope); ?>">
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
                    <?php $pagina_categoria_id = str_replace('-', '_', $pagina_id); ?>
                    <div class="flavor-demo-page-card"
                         x-show="landingCoincideFiltro('<?php echo esc_js($pagina['title']); ?>', '<?php echo esc_js($pagina['slug']); ?>', moduleCategoryMap['<?php echo esc_js($pagina_categoria_id); ?>'] || [], (landingTags['<?php echo esc_js($pagina_categoria_id); ?>']?.tipos || []), (landingTags['<?php echo esc_js($pagina_categoria_id); ?>']?.impactos || []))"
                         style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 15px; transition: all 0.2s ease;">
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

        // TODOS los módulos soportados para datos demo con sus configuraciones visuales
        $todos_modulos_demo = [
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
            'reservas' => [
                'nombre' => __('Reservas', 'flavor-chat-ia'),
                'icono' => 'dashicons-book',
                'color' => '#0891b2',
            ],
            'socios' => [
                'nombre' => __('Socios', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'color' => '#7c3aed',
            ],
            'facturas' => [
                'nombre' => __('Facturas', 'flavor-chat-ia'),
                'icono' => 'dashicons-media-spreadsheet',
                'color' => '#dc2626',
            ],
            'incidencias' => [
                'nombre' => __('Incidencias', 'flavor-chat-ia'),
                'icono' => 'dashicons-warning',
                'color' => '#f97316',
            ],
            'talleres' => [
                'nombre' => __('Talleres', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'color' => '#8b5cf6',
            ],
            'tramites' => [
                'nombre' => __('Trámites', 'flavor-chat-ia'),
                'icono' => 'dashicons-forms',
                'color' => '#0891b2',
            ],
            'avisos_municipales' => [
                'nombre' => __('Avisos Municipales', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
                'color' => '#dc2626',
            ],
            'red_social' => [
                'nombre' => __('Red Social', 'flavor-chat-ia'),
                'icono' => 'dashicons-share',
                'color' => '#3b82f6',
            ],
            'network' => [
                'nombre' => __('Red de Comunidades', 'flavor-chat-ia'),
                'icono' => 'dashicons-networking',
                'color' => '#10b981',
            ],
        ];

        // Obtener módulos activos del perfil actual
        $perfil_actual = $this->gestor_perfiles->obtener_perfil_activo();
        $perfiles = $this->gestor_perfiles->obtener_perfiles();

        // Obtener módulos REALMENTE activos (no solo los del perfil)
        // Leer directamente de la configuración guardada en WordPress
        $modulos_realmente_activos = [];

        // 1. Obtener módulos activos de la configuración del plugin
        $settings = get_option('flavor_chat_ia_settings', []);
        if (!empty($settings['active_modules']) && is_array($settings['active_modules'])) {
            $modulos_realmente_activos = $settings['active_modules'];
        }

        // 2. Si no hay configuración guardada, intentar con Module Loader
        if (empty($modulos_realmente_activos) && class_exists('Flavor_Module_Loader')) {
            $module_loader = Flavor_Module_Loader::get_instance();
            if (method_exists($module_loader, 'get_loaded_modules')) {
                $loaded_modules = $module_loader->get_loaded_modules();
                $modulos_realmente_activos = array_keys($loaded_modules);
            }
        }

        // 3. Como último recurso, usar los del perfil
        if (empty($modulos_realmente_activos) && isset($perfiles[$perfil_actual])) {
            $modulos_requeridos = $perfiles[$perfil_actual]['modulos_requeridos'] ?? [];
            $modulos_opcionales = $perfiles[$perfil_actual]['modulos_opcionales'] ?? [];
            $modulos_realmente_activos = array_merge($modulos_requeridos, $modulos_opcionales);
        }

        // Añadir componentes especiales del core que no son módulos pero tienen datos demo
        global $wpdb;

        // Network (Red de Comunidades) - verificar si sus tablas existen
        $tabla_network = $wpdb->prefix . 'flavor_network_nodes';
        if (Flavor_Chat_Helpers::tabla_existe($tabla_network)) {
            $modulos_realmente_activos[] = 'network';
        }

        // Red Social - verificar tablas (si no está ya detectada)
        if (!in_array('red_social', $modulos_realmente_activos, true)) {
            $tabla_red_social = $wpdb->prefix . 'flavor_red_social_publicaciones';
            if (Flavor_Chat_Helpers::tabla_existe($tabla_red_social)) {
                $modulos_realmente_activos[] = 'red_social';
            }
        }

        // Eliminar duplicados
        $modulos_realmente_activos = array_unique($modulos_realmente_activos);

        // Filtrar: mostrar solo módulos que están REALMENTE ACTIVOS y tienen datos demo
        $modulos_demo = [];

        // Si tenemos la lista de módulos realmente activos, filtrar
        if (!empty($modulos_realmente_activos)) {
            foreach ($todos_modulos_demo as $id_modulo => $config_modulo) {
                if (in_array($id_modulo, $modulos_realmente_activos, true)) {
                    $modulos_demo[$id_modulo] = $config_modulo;
                }
            }
        } else {
            // Si no podemos detectar, mostrar todos los disponibles
            $modulos_demo = $todos_modulos_demo;
        }

        // Contar datos demo actuales para los módulos filtrados
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
                <?php if (!empty($modulos_demo)): ?>
                    <br>
                    <strong><?php printf(
                        esc_html__('Mostrando %d módulo(s) activo(s) con datos demo disponibles en el perfil "%s".', 'flavor-chat-ia'),
                        count($modulos_demo),
                        esc_html($perfiles[$perfil_actual]['nombre'] ?? $perfil_actual)
                    ); ?></strong>
                <?php endif; ?>
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
                        <?php if (!empty($modulos_demo)): ?>
                            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                <?php wp_nonce_field('flavor_demo_data_action'); ?>
                                <input type="hidden" name="action" value="flavor_populate_demo_data">
                                <input type="hidden" name="modulo_id" value="all">
                                <?php foreach (array_keys($modulos_demo) as $modulo_id): ?>
                                    <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_id); ?>">
                                <?php endforeach; ?>
                                <button type="submit" class="button button-primary" onclick="return confirm('<?php esc_attr_e('¿Cargar datos de ejemplo en todos los módulos activos?', 'flavor-chat-ia'); ?>');">
                                    <span class="dashicons dashicons-database-add" style="margin-right: 5px;"></span>
                                    <?php printf(
                                        esc_html__('Cargar todos (%d módulos)', 'flavor-chat-ia'),
                                        count($modulos_demo)
                                    ); ?>
                                </button>
                            </form>

                            <?php if ($tiene_datos_demo): ?>
                                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" style="display: inline;">
                                    <?php wp_nonce_field('flavor_demo_data_action'); ?>
                                    <input type="hidden" name="action" value="flavor_clear_demo_data">
                                    <input type="hidden" name="modulo_id" value="all">
                                    <?php foreach (array_keys($modulos_demo) as $modulo_id): ?>
                                        <input type="hidden" name="modulos_activos[]" value="<?php echo esc_attr($modulo_id); ?>">
                                    <?php endforeach; ?>
                                    <button type="submit" class="button" style="color: #d63638;" onclick="return confirm('<?php esc_attr_e('¿Eliminar TODOS los datos de demostración? Esta acción no se puede deshacer.', 'flavor-chat-ia'); ?>');">
                                        <span class="dashicons dashicons-trash" style="margin-right: 5px;"></span>
                                        <?php _e('Eliminar todos los datos demo', 'flavor-chat-ia'); ?>
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="description" style="margin: 0;">
                                <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
                                <?php _e('No hay módulos activos que soporten datos de demostración en el perfil actual.', 'flavor-chat-ia'); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Grid de módulos -->
            <?php if (!empty($modulos_demo)): ?>
                <div class="flavor-demo-modules-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 15px; margin-top: 20px;">
                    <?php foreach ($modulos_demo as $modulo_id => $modulo): ?>
                        <div class="flavor-demo-module-card"
                             x-show="landingCoincideFiltro('<?php echo esc_js($modulo['nombre']); ?>', '', moduleCategoryMap['<?php echo esc_js($modulo_id); ?>'] || [], (landingTags['<?php echo esc_js($modulo_id); ?>']?.tipos || []), (landingTags['<?php echo esc_js($modulo_id); ?>']?.impactos || []))"
                             style="background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px;">
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
            <?php else: ?>
                <div style="padding: 40px; text-align: center; background: #f9fafb; border: 2px dashed #e5e7eb; border-radius: 8px; margin-top: 20px;">
                    <span class="dashicons dashicons-info" style="font-size: 48px; color: #9ca3af; margin-bottom: 10px;"></span>
                    <p style="margin: 0; font-size: 16px; color: #6b7280;">
                        <?php _e('No hay módulos con datos demo disponibles en el perfil actual.', 'flavor-chat-ia'); ?>
                    </p>
                    <p style="margin: 10px 0 0 0; font-size: 14px; color: #9ca3af;">
                        <?php _e('Cambia a un perfil diferente o activa más módulos para ver opciones de datos demo.', 'flavor-chat-ia'); ?>
                    </p>
                </div>
            <?php endif; ?>

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
        $modulos_opcionales = isset($_POST['modulos_opcionales']) ? (array) $_POST['modulos_opcionales'] : [];
        $modulos_opcionales = $this->normalizar_ids_modulos($modulos_opcionales);
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

        $modulos_requeridos = $this->normalizar_ids_modulos((array) ($perfil['modulos_requeridos'] ?? []));
        $modulos_opcionales = $this->normalizar_ids_modulos((array) ($perfil['modulos_opcionales'] ?? []));

        // Construir respuesta con datos de la plantilla
        $datos_preview = [
            'id' => $plantilla_id,
            'nombre' => $perfil['nombre'] ?? '',
            'descripcion' => $perfil['descripcion'] ?? '',
            'icono' => $perfil['icono'] ?? 'dashicons-admin-generic',
            'color' => $perfil['color'] ?? '#2271b1',
            'modulos_requeridos' => $modulos_requeridos,
            'modulos_opcionales' => $modulos_opcionales,
            'paginas' => $this->obtener_paginas_plantilla($plantilla_id),
            'landing' => $this->obtener_landing_plantilla($plantilla_id),
        ];

        wp_send_json_success($datos_preview);
    }

    /**
     * AJAX: Ejecuta un paso de instalacion
     */
    public function ajax_ejecutar_paso_instalacion() {
        flavor_log_debug( 'Inicio ajax_ejecutar_paso_instalacion', 'AppProfile' );
        check_ajax_referer('flavor_activar_plantilla', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['mensaje' => __('Sin permisos', 'flavor-chat-ia')]);
            return;
        }

        $paso = sanitize_text_field($_POST['paso'] ?? '');
        $plantilla_id = sanitize_text_field($_POST['plantilla_id'] ?? '');
        $modulos_opcionales = json_decode(stripslashes($_POST['modulos_opcionales'] ?? '[]'), true);
        $cargar_demo = isset($_POST['cargar_demo']) && $_POST['cargar_demo'] === '1';

        flavor_log_debug( 'Paso: ' . $paso . ', Plantilla: ' . $plantilla_id, 'AppProfile' );

        if (empty($paso) || empty($plantilla_id)) {
            wp_send_json_error(['mensaje' => __('Parametros invalidos', 'flavor-chat-ia')]);
            return;
        }

        $opciones = [
            'modulos_opcionales' => $this->normalizar_ids_modulos(is_array($modulos_opcionales) ? $modulos_opcionales : []),
            'cargar_demo' => $cargar_demo,
        ];

        // Ejecutar el paso correspondiente
        $resultado = $this->ejecutar_paso_instalacion($paso, $plantilla_id, $opciones);

        flavor_log_debug( 'Resultado del paso ' . $paso . ': ' . ($resultado['success'] ? 'SUCCESS' : 'ERROR'), 'AppProfile' );

        if ($resultado['success']) {
            wp_send_json_success($resultado);
        } else {
            flavor_log_debug( 'Error: ' . json_encode($resultado), 'AppProfile' );
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
        flavor_log_debug( 'INICIO - Plantilla: ' . $plantilla_id, 'InstalarModulos' );

        try {
            // Activar modulos requeridos
            $modulos_a_activar = $perfil['modulos_requeridos'] ?? [];
            flavor_log_debug( 'Módulos requeridos: ' . json_encode($modulos_a_activar), 'InstalarModulos' );

            // Agregar modulos opcionales seleccionados
            if (!empty($opciones['modulos_opcionales'])) {
                $modulos_a_activar = array_merge($modulos_a_activar, $opciones['modulos_opcionales']);
            }
            $modulos_a_activar = $this->normalizar_ids_modulos($modulos_a_activar);
            flavor_log_debug( 'Módulos totales a activar: ' . json_encode($modulos_a_activar), 'InstalarModulos' );

            // Actualizar modulos activos
            flavor_log_debug( 'Actualizando active_modules...', 'InstalarModulos' );
            $configuracion = get_option('flavor_chat_ia_settings', []);
            $modulos_actuales = $configuracion['active_modules'] ?? [];
            flavor_log_debug( 'Módulos actuales antes: ' . json_encode($modulos_actuales), 'InstalarModulos' );

            $modulos_combinados = array_unique(array_merge($modulos_actuales, $modulos_a_activar));
            flavor_log_debug( 'Módulos combinados: ' . json_encode($modulos_combinados), 'InstalarModulos' );

            $configuracion['active_modules'] = $modulos_combinados;

            // Intentar guardar con update_option primero
            $update_result = update_option('flavor_chat_ia_settings', $configuracion);
            flavor_log_debug( 'update_option result: ' . ($update_result ? 'true' : 'false'), 'InstalarModulos' );

            // Si update_option devuelve false, forzar guardado directo en BD
            if (!$update_result) {
                flavor_log_debug( 'update_option falló, usando WPDB directo...', 'InstalarModulos' );
                global $wpdb;
                $serialized = maybe_serialize($configuracion);
                $wpdb_result = $wpdb->update(
                    $wpdb->options,
                    ['option_value' => $serialized],
                    ['option_name' => 'flavor_chat_ia_settings'],
                    ['%s'],
                    ['%s']
                );
                flavor_log_debug( 'WPDB update result: ' . $wpdb_result, 'InstalarModulos' );

                // Limpiar caché de opciones
                wp_cache_delete('flavor_chat_ia_settings', 'options');
                wp_cache_delete('alloptions', 'options');
            }

            // Verificar que se guardó correctamente
            $configuracion_verificacion = get_option('flavor_chat_ia_settings', []);
            $modulos_guardados = $configuracion_verificacion['active_modules'] ?? [];
            flavor_log_debug( 'Módulos guardados después: ' . json_encode($modulos_guardados), 'InstalarModulos' );

            // Cargar el helper para guardar el perfil directamente en BD
            flavor_log_debug( 'Cargando Profile Saver...', 'InstalarModulos' );
            require_once FLAVOR_CHAT_IA_PATH . 'includes/class-profile-saver.php';

            // Guardar app_profile directamente en BD (bypass WordPress cache/hooks)
            flavor_log_debug( 'Guardando perfil...', 'InstalarModulos' );
            $resultado_perfil = Flavor_Profile_Saver::guardar_perfil($plantilla_id);
            flavor_log_debug( 'Perfil guardado - Exito: ' . ($resultado_perfil['exito'] ? 'true' : 'false'), 'InstalarModulos' );

            // IMPORTANTE: Limpiar caché para asegurar que los módulos se vean como activos
            flavor_log_debug( 'Limpiando caché...', 'InstalarModulos' );
            wp_cache_delete('flavor_chat_ia_settings', 'options');
            wp_cache_delete('alloptions', 'options');

            // IMPORTANTE: Inicializar módulos recién activados
            flavor_log_debug( 'Inicializando módulos...', 'InstalarModulos' );

            // Asegurar que Module Loader esté disponible
            if (!class_exists('Flavor_Chat_Module_Loader')) {
                flavor_log_debug( 'Cargando Flavor_Chat_Module_Loader...', 'InstalarModulos' );
                require_once FLAVOR_CHAT_IA_PATH . 'includes/modules/class-module-loader.php';
                flavor_log_debug( 'Clase cargada correctamente', 'InstalarModulos' );
            }

            $modulos_inicializados = 0;
            $errores_inicializacion = [];
            $module_loader = Flavor_Chat_Module_Loader::get_instance();

            foreach ($modulos_a_activar as $module_id) {
                flavor_log_debug( 'Procesando módulo: ' . $module_id, 'InstalarModulos' );
                try {
                    // Cargar el módulo si no está ya cargado
                    $module = $module_loader->get_module($module_id);
                    flavor_log_debug( 'Módulo cargado: ' . ($module ? get_class($module) : 'NULL'), 'InstalarModulos' );

                    if ($module) {
                        // Crear tablas si el módulo lo necesita
                        if (method_exists($module, 'maybe_create_tables')) {
                            $module->maybe_create_tables();
                        }

                        // Crear páginas si el módulo lo necesita
                        if (method_exists($module, 'maybe_create_pages')) {
                            $module->maybe_create_pages();
                        }

                        $modulos_inicializados++;
                    } else {
                        $errores_inicializacion[] = "No se pudo cargar el módulo: $module_id";
                    }
                } catch (Exception $e) {
                    $errores_inicializacion[] = "Error en módulo $module_id: " . $e->getMessage();
                }
            }

            $total_modulos = count($modulos_a_activar);

            $descripcion = sprintf(
                _n('%d modulo activado', '%d modulos activados', $total_modulos, 'flavor-chat-ia'),
                $total_modulos
            );

            if ($modulos_inicializados > 0) {
                $descripcion .= sprintf(
                    ' (' . _n('%d inicializado', '%d inicializados', $modulos_inicializados, 'flavor-chat-ia') . ')',
                    $modulos_inicializados
                );
            }

            flavor_log_debug( 'ÉXITO - Módulos activados: ' . $total_modulos . ', Inicializados: ' . $modulos_inicializados, 'InstalarModulos' );
            if (!empty($errores_inicializacion)) {
                flavor_log_error( 'Errores de inicialización: ' . json_encode($errores_inicializacion), 'InstalarModulos' );
            }

            return [
                'success' => true,
                'descripcion' => $descripcion,
                'modulos_activados' => $total_modulos,
                'modulos_inicializados' => $modulos_inicializados,
                'errores_inicializacion' => $errores_inicializacion,
                'debug_metodo' => 'WPDB_DIRECTO_V2',
                'debug_valor_previo' => $resultado_perfil['valor_previo'],
                'debug_wpdb_result' => $resultado_perfil['wpdb_result'],
                'debug_valor_guardado' => $resultado_perfil['valor_guardado'],
                'debug_exito' => $resultado_perfil['exito'],
            ];
        } catch (Exception $excepcion) {
            flavor_log_error( 'ERROR FATAL: ' . $excepcion->getMessage(), 'InstalarModulos' );
            flavor_log_error( 'Stack trace: ' . $excepcion->getTraceAsString(), 'InstalarModulos' );
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
                'trace' => $excepcion->getTraceAsString(),
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

            // Crear páginas adicionales para módulos seleccionados
            if (class_exists('Flavor_Page_Creator')) {
                $modulos_activos = array_merge(
                    $perfil['modulos_requeridos'] ?? [],
                    $opciones['modulos_opcionales'] ?? []
                );
                $modulos_activos = $this->normalizar_ids_modulos($modulos_activos);
                Flavor_Page_Creator::create_pages_for_modules($modulos_activos);
            }

            // Sincronizar menú principal con páginas de módulos activos
            $this->sincronizar_menu_principal($perfil, $opciones);

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

            // Usar Site Transformer para configurar home y menu
            $resultado_transformacion = $this->aplicar_transformacion_sitio($plantilla_id);
            $this->configurar_apps_moviles($perfil, $opciones);

            // Verificar app_profile ANTES de terminar este paso
            $config_check = get_option('flavor_chat_ia_settings', []);
            $app_profile_actual = $config_check['app_profile'] ?? 'NO_EXISTE';

            return [
                'success' => true,
                'descripcion' => __('Configuracion aplicada', 'flavor-chat-ia'),
                'debug_app_profile_final' => $app_profile_actual,
                'transformacion' => $resultado_transformacion,
            ];
        } catch (Exception $excepcion) {
            return [
                'success' => false,
                'mensaje' => $excepcion->getMessage(),
            ];
        }
    }

    /**
     * Configura navegación móvil con tabs principales y drawer con el resto.
     * IMPORTANTE: Preserva la configuración existente de tabs si ya está configurada
     * para evitar sobreescribir cambios manuales del usuario.
     */
    private function configurar_apps_moviles($perfil, $opciones) {
        $config = get_option('flavor_apps_config', []);

        // Solo establecer navigation_style si no está configurado
        if (!isset($config['navigation_style'])) {
            $config['navigation_style'] = 'hybrid';
        }
        if (!isset($config['hybrid_show_appbar'])) {
            $config['hybrid_show_appbar'] = true;
        }
        if (!isset($config['web_sections_menu'])) {
            $config['web_sections_menu'] = 'location:primary';
        }

        $modulos_activos = array_merge(
            $perfil['modulos_requeridos'] ?? [],
            $opciones['modulos_opcionales'] ?? []
        );
        $modulos_activos = $this->normalizar_ids_modulos($modulos_activos);

        // PRESERVAR TABS EXISTENTES: Solo generar tabs automáticos si no hay configuración previa
        // Esto evita sobreescribir la configuración manual del panel de navegación
        $tabs_existentes = $config['tabs'] ?? [];
        $tiene_tabs_configurados = !empty($tabs_existentes) && is_array($tabs_existentes);

        if (!$tiene_tabs_configurados) {
            $tabs = [];
            $tabs[] = [
                'id' => 'home',
                'label' => __('Inicio', 'flavor-chat-ia'),
                'icon' => 'home',
                'type' => 'web',
                'url' => home_url('/'),
                'enabled' => true,
                'order' => 0,
            ];

            $tab_map = [
                'grupos_consumo' => ['slug' => 'grupos-consumo', 'label' => __('Grupos de Consumo', 'flavor-chat-ia'), 'icon' => 'groups', 'order' => 1],
                'marketplace' => ['slug' => 'marketplace', 'label' => __('Marketplace', 'flavor-chat-ia'), 'icon' => 'store', 'order' => 2],
                'eventos' => ['slug' => 'eventos', 'label' => __('Eventos', 'flavor-chat-ia'), 'icon' => 'event', 'order' => 3],
            ];

            foreach ($tab_map as $module_id => $tab) {
                if (!in_array($module_id, $modulos_activos, true)) {
                    continue;
                }
                $page = get_page_by_path($tab['slug']);
                $url = $page ? get_permalink($page->ID) : home_url('/' . $tab['slug'] . '/');
                $tabs[] = [
                    'id' => sanitize_key($module_id),
                    'label' => $tab['label'],
                    'icon' => $tab['icon'],
                    'type' => 'web',
                    'url' => $url,
                    'enabled' => true,
                    'order' => $tab['order'],
                ];
            }

            $perfil_page = get_page_by_path('mi-cuenta');
            $tabs[] = [
                'id' => 'perfil',
                'label' => __('Perfil', 'flavor-chat-ia'),
                'icon' => 'person',
                'type' => 'web',
                'url' => $perfil_page ? get_permalink($perfil_page->ID) : home_url('/mi-cuenta/'),
                'enabled' => true,
                'order' => 4,
            ];

            $config['tabs'] = $tabs;
            $config['default_tab'] = 'home';
        }

        // PRESERVAR DRAWER_ITEMS EXISTENTES: Solo generar si no hay configuración previa
        $drawer_existentes = $config['drawer_items'] ?? [];
        $tiene_drawer_configurado = !empty($drawer_existentes) && is_array($drawer_existentes);

        if (!$tiene_drawer_configurado) {
            $drawer_items = [];
            $locations = get_nav_menu_locations();
            $menu_id = $locations['primary'] ?? 0;
            if ($menu_id) {
                $menu_items = wp_get_nav_menu_items($menu_id);
                if (!empty($menu_items)) {
                    foreach ($menu_items as $menu_item) {
                        $drawer_items[] = [
                            'title' => $menu_item->title ?? '',
                            'url' => $menu_item->url ?? '',
                            'icon' => get_post_meta($menu_item->ID, '_menu_item_icon', true) ?: 'public',
                            'type' => 'web',
                            'target' => '',
                            'order' => isset($menu_item->menu_order) ? intval($menu_item->menu_order) : 0,
                            'enabled' => true,
                        ];
                    }
                }
            }
            $config['drawer_items'] = $drawer_items;
        }

        // Actualizar configuración de módulos (esto sí debe actualizarse siempre)
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $registered_modules = $loader->get_registered_modules();
            $modules_config = $config['modules'] ?? [];
            foreach ($registered_modules as $module_id => $module_data) {
                $modules_config[$module_id] = [
                    'enabled' => in_array($module_id, $modulos_activos, true),
                ];
            }
            $config['modules'] = $modules_config;
        }

        update_option('flavor_apps_config', $config);

        // Invalidar caché de API para que los cambios se reflejen inmediatamente
        delete_transient('flavor_api_system_info');
        delete_transient('flavor_api_available_modules');
        delete_transient('flavor_api_layouts');
        delete_transient('flavor_api_theme');
    }

    /**
     * Aplica la transformacion del sitio (home, menu, opciones frontend)
     *
     * @param string $plantilla_id ID de la plantilla
     * @return array Resultado de la transformacion
     */
    private function aplicar_transformacion_sitio($plantilla_id) {
        // Cargar el componente Site Transformer
        $archivo_transformer = FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/components/class-site-transformer.php';

        if (!file_exists($archivo_transformer)) {
            return ['success' => false, 'error' => __('Site Transformer no encontrado', 'flavor-chat-ia')];
        }

        // Cargar interface si no esta cargada
        $archivo_interface = FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/interface-template-component.php';
        if (file_exists($archivo_interface) && !interface_exists('Flavor_Template_Component_Interface')) {
            require_once $archivo_interface;
        }

        require_once $archivo_transformer;

        // Obtener definicion completa de la plantilla
        $archivo_definiciones = FLAVOR_CHAT_IA_PATH . 'includes/orchestrator/class-template-definitions.php';
        if (!file_exists($archivo_definiciones)) {
            return ['success' => false, 'error' => __('Definiciones no encontradas', 'flavor-chat-ia')];
        }

        require_once $archivo_definiciones;

        $definiciones = Flavor_Template_Definitions::get_instance();
        $definicion = $definiciones->obtener_definicion($plantilla_id);

        if (!$definicion) {
            return ['success' => false, 'error' => sprintf(__('Definicion de plantilla no encontrada: %s', 'flavor-chat-ia'), $plantilla_id)];
        }

        // Ejecutar transformacion
        $transformer = new Flavor_Site_Transformer();
        return $transformer->instalar($plantilla_id, $definicion, []);
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
            $modulos_activos = $this->normalizar_ids_modulos($modulos_activos);

            $registros_creados = 0;

            foreach ($modulos_activos as $modulo_id) {
                $resultado = $demo_manager->populate_module($modulo_id);
                if (is_array($resultado) && !empty($resultado['success'])) {
                    $registros_creados += intval($resultado['count'] ?? 1);
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
     * Sincroniza el menú principal con páginas creadas de módulos activos.
     */
    private function sincronizar_menu_principal($perfil, $opciones) {
        $locations = get_nav_menu_locations();
        $menu_id = $locations['primary'] ?? 0;

        if (!$menu_id) {
            flavor_log_debug( 'No hay menú principal configurado, saltando sincronización', 'SyncMenu' );
            return;
        }

        if (!empty($opciones['modulos_activos'])) {
            $modulos_activos = $opciones['modulos_activos'];
        } else {
            $modulos_activos = array_merge(
                $perfil['modulos_requeridos'] ?? [],
                $opciones['modulos_opcionales'] ?? []
            );
        }
        $modulos_activos = $this->normalizar_ids_modulos($modulos_activos);
        $menu_sync = $opciones['menu_sync'] ?? 'merge';

        flavor_log_debug( 'Sincronizando menú ID: ' . $menu_id . ' con modo: ' . $menu_sync, 'SyncMenu' );

        $items = wp_get_nav_menu_items($menu_id);
        $existing_page_ids = [];
        if (!empty($items)) {
            foreach ($items as $item) {
                if ($menu_sync === 'replace') {
                    wp_delete_post($item->ID, true);
                    continue;
                }
                if ($item->object === 'page') {
                    $existing_page_ids[] = (int) $item->object_id;
                }
            }
        }

        if ($menu_sync === 'replace') {
            $existing_page_ids = [];
        }

        // Recopilar todas las páginas de módulos (padres e hijos)
        $all_pages = [];
        foreach ($modulos_activos as $modulo_id) {
            $pages = get_posts([
                'post_type' => 'page',
                'post_status' => 'publish',
                'posts_per_page' => -1, // Todas las páginas
                'meta_query' => [
                    [
                        'key' => '_flavor_auto_page_modules',
                        'value' => $modulo_id,
                        'compare' => 'LIKE',
                    ],
                ],
            ]);

            foreach ($pages as $page) {
                $all_pages[$page->ID] = $page;
            }
        }

        if (empty($all_pages)) {
            flavor_log_debug( 'No se encontraron páginas para añadir al menú', 'SyncMenu' );
            return;
        }

        // Separar páginas padre e hijos
        $parent_pages = [];
        $child_pages = [];

        foreach ($all_pages as $page) {
            if ($page->post_parent == 0) {
                $parent_pages[$page->ID] = $page;
            } else {
                $child_pages[$page->ID] = $page;
            }
        }

        // Ordenar páginas padre alfabéticamente
        if (!empty($parent_pages)) {
            uasort($parent_pages, function($a, $b) {
                return strcasecmp($a->post_title, $b->post_title);
            });
        }

        flavor_log_debug( 'Páginas padre: ' . count($parent_pages) . ', Páginas hijo: ' . count($child_pages), 'SyncMenu' );

        // Log de todas las páginas padre
        foreach ($parent_pages as $page) {
            flavor_log_debug( 'Página padre detectada: ID=' . $page->ID . ' Título="' . $page->post_title . '"', 'SyncMenu' );
        }

        // Mapa de page_id => menu_item_id
        $page_to_menu_item = [];

        // Primero añadir todas las páginas padre
        foreach ($parent_pages as $page) {
            if (in_array((int) $page->ID, $existing_page_ids, true)) {
                flavor_log_debug( 'Página padre ya existe en menú: ' . $page->post_title, 'SyncMenu' );

                // IMPORTANTE: Buscar el menu_item_id existente y añadirlo al mapeo
                $existing_items = wp_get_nav_menu_items($menu_id);
                if ($existing_items) {
                    foreach ($existing_items as $item) {
                        if ($item->object === 'page' && $item->object_id == $page->ID) {
                            $page_to_menu_item[$page->ID] = $item->ID;
                            flavor_log_debug( 'Añadido al mapeo página padre existente: ' . $page->post_title . ' (menu_item: ' . $item->ID . ')', 'SyncMenu' );
                            break;
                        }
                    }
                }
                continue;
            }

            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $page->post_title,
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page->ID,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish',
            ]);

            if (!is_wp_error($menu_item_id)) {
                $page_to_menu_item[$page->ID] = $menu_item_id;
                $existing_page_ids[] = (int) $page->ID;
                flavor_log_debug( 'Añadida página padre al menú: ' . $page->post_title . ' (menu_item: ' . $menu_item_id . ')', 'SyncMenu' );
            }
        }

        // Log del mapa de páginas a menu items
        flavor_log_debug( 'Mapa page_to_menu_item: ' . json_encode($page_to_menu_item), 'SyncMenu' );

        // Luego añadir páginas hijo bajo sus padres
        foreach ($child_pages as $page) {
            flavor_log_debug( 'Procesando página hijo: ID=' . $page->ID . ' Título="' . $page->post_title . '" post_parent=' . $page->post_parent, 'SyncMenu' );

            if (in_array((int) $page->ID, $existing_page_ids, true)) {
                flavor_log_debug( 'Página hijo ya existe en menú: ' . $page->post_title, 'SyncMenu' );

                // Verificar que el padre también esté en el mapeo para futuros hijos
                if (!isset($page_to_menu_item[$page->post_parent])) {
                    $existing_items = wp_get_nav_menu_items($menu_id);
                    if ($existing_items) {
                        foreach ($existing_items as $item) {
                            if ($item->object === 'page' && $item->object_id == $page->post_parent) {
                                $page_to_menu_item[$page->post_parent] = $item->ID;
                                flavor_log_debug( 'Añadido al mapeo padre de hijo existente: ID=' . $page->post_parent . ' (menu_item: ' . $item->ID . ')', 'SyncMenu' );
                                break;
                            }
                        }
                    }
                }
                continue;
            }

            // Verificar si el padre está en el menú
            $parent_menu_item_id = $page_to_menu_item[$page->post_parent] ?? 0;
            flavor_log_debug( 'Buscando padre ' . $page->post_parent . ' en mapa, encontrado menu_item_id: ' . $parent_menu_item_id, 'SyncMenu' );

            if (!$parent_menu_item_id) {
                // Si el padre no está en el menú, buscar si ya existe
                $parent_items = wp_get_nav_menu_items($menu_id);
                if ($parent_items) {
                    foreach ($parent_items as $item) {
                        if ($item->object === 'page' && $item->object_id == $page->post_parent) {
                            $parent_menu_item_id = $item->ID;
                            $page_to_menu_item[$page->post_parent] = $item->ID;
                            break;
                        }
                    }
                }
            }

            $menu_item_id = wp_update_nav_menu_item($menu_id, 0, [
                'menu-item-title' => $page->post_title,
                'menu-item-object' => 'page',
                'menu-item-object-id' => $page->ID,
                'menu-item-type' => 'post_type',
                'menu-item-status' => 'publish',
                'menu-item-parent-id' => $parent_menu_item_id, // Establecer el padre
            ]);

            if (!is_wp_error($menu_item_id)) {
                $existing_page_ids[] = (int) $page->ID;
                flavor_log_debug( 'Añadida página hijo al menú: ' . $page->post_title . ' bajo parent_id: ' . $parent_menu_item_id, 'SyncMenu' );
            }
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
