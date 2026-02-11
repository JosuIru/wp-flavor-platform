<?php
/**
 * Setup Wizard - Asistente de configuración inicial mejorado
 *
 * Guía paso a paso para configurar Flavor Platform por primera vez
 * con importación de datos demo, preview en vivo y guardado de progreso
 *
 * @package FlavorPlatform
 * @subpackage Admin
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para el asistente de configuración
 *
 * @since 3.0.0
 */
class Flavor_Setup_Wizard {

    /**
     * Instancia singleton
     *
     * @var Flavor_Setup_Wizard
     */
    private static $instancia = null;

    /**
     * Pasos del wizard
     *
     * @var array
     */
    private $pasos = [];

    /**
     * Paso actual
     *
     * @var string
     */
    private $paso_actual = '';

    /**
     * Índice del paso actual
     *
     * @var int
     */
    private $indice_paso_actual = 0;

    /**
     * Datos guardados del wizard
     *
     * @var array
     */
    private $datos_wizard = [];

    /**
     * Temas visuales disponibles
     *
     * @var array
     */
    private $temas_visuales = [];

    /**
     * Módulos disponibles con descripciones
     *
     * @var array
     */
    private $modulos_disponibles = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Setup_Wizard
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_pasos();
        $this->init_temas_visuales();
        $this->init_modulos();
        $this->cargar_datos_guardados();
        $this->init_hooks();
    }

    /**
     * Inicializa los pasos del wizard
     *
     * @return void
     */
    private function init_pasos() {
        $this->pasos = [
            'bienvenida' => [
                'nombre' => __('Bienvenida', 'flavor-chat-ia'),
                'descripcion' => __('Tipo de organización', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'numero' => 1,
            ],
            'info_basica' => [
                'nombre' => __('Información', 'flavor-chat-ia'),
                'descripcion' => __('Nombre, logo y colores', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-settings',
                'numero' => 2,
            ],
            'modulos' => [
                'nombre' => __('Módulos', 'flavor-chat-ia'),
                'descripcion' => __('Funcionalidades adicionales', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-plugins',
                'numero' => 3,
            ],
            'diseno' => [
                'nombre' => __('Diseño', 'flavor-chat-ia'),
                'descripcion' => __('Tema visual', 'flavor-chat-ia'),
                'icono' => 'dashicons-art',
                'numero' => 4,
            ],
            'demo_data' => [
                'nombre' => __('Datos Demo', 'flavor-chat-ia'),
                'descripcion' => __('Importar contenido de ejemplo', 'flavor-chat-ia'),
                'icono' => 'dashicons-database-import',
                'numero' => 5,
            ],
            'notificaciones' => [
                'nombre' => __('Notificaciones', 'flavor-chat-ia'),
                'descripcion' => __('Email y push', 'flavor-chat-ia'),
                'icono' => 'dashicons-bell',
                'numero' => 6,
            ],
            'resumen' => [
                'nombre' => __('Resumen', 'flavor-chat-ia'),
                'descripcion' => __('Revisar y finalizar', 'flavor-chat-ia'),
                'icono' => 'dashicons-yes-alt',
                'numero' => 7,
            ],
        ];
    }

    /**
     * Inicializa los temas visuales disponibles
     *
     * @return void
     */
    private function init_temas_visuales() {
        $this->temas_visuales = [
            'default' => [
                'nombre' => __('Clásico', 'flavor-chat-ia'),
                'descripcion' => __('Tema limpio y profesional con colores neutros', 'flavor-chat-ia'),
                'color_primario' => '#3b82f6',
                'color_secundario' => '#6b7280',
                'color_fondo' => '#ffffff',
                'preview_class' => 'theme-default',
            ],
            'modern-purple' => [
                'nombre' => __('Púrpura Moderno', 'flavor-chat-ia'),
                'descripcion' => __('Estilo moderno con acentos en púrpura', 'flavor-chat-ia'),
                'color_primario' => '#8b5cf6',
                'color_secundario' => '#64748b',
                'color_fondo' => '#faf5ff',
                'preview_class' => 'theme-purple',
            ],
            'ocean-blue' => [
                'nombre' => __('Océano', 'flavor-chat-ia'),
                'descripcion' => __('Tonos frescos y azules oceánicos', 'flavor-chat-ia'),
                'color_primario' => '#0891b2',
                'color_secundario' => '#64748b',
                'color_fondo' => '#f0fdfa',
                'preview_class' => 'theme-ocean',
            ],
            'forest-green' => [
                'nombre' => __('Bosque', 'flavor-chat-ia'),
                'descripcion' => __('Colores naturales verdes', 'flavor-chat-ia'),
                'color_primario' => '#16a34a',
                'color_secundario' => '#64748b',
                'color_fondo' => '#f0fdf4',
                'preview_class' => 'theme-forest',
            ],
            'sunset-orange' => [
                'nombre' => __('Atardecer', 'flavor-chat-ia'),
                'descripcion' => __('Cálidos tonos naranjas', 'flavor-chat-ia'),
                'color_primario' => '#ea580c',
                'color_secundario' => '#78716c',
                'color_fondo' => '#fff7ed',
                'preview_class' => 'theme-sunset',
            ],
            'rose-pink' => [
                'nombre' => __('Rosa', 'flavor-chat-ia'),
                'descripcion' => __('Elegante con tonos rosados', 'flavor-chat-ia'),
                'color_primario' => '#e11d48',
                'color_secundario' => '#64748b',
                'color_fondo' => '#fff1f2',
                'preview_class' => 'theme-rose',
            ],
            'dark-mode' => [
                'nombre' => __('Modo Oscuro', 'flavor-chat-ia'),
                'descripcion' => __('Tema oscuro para reducir fatiga visual', 'flavor-chat-ia'),
                'color_primario' => '#60a5fa',
                'color_secundario' => '#94a3b8',
                'color_fondo' => '#1e293b',
                'preview_class' => 'theme-dark',
            ],
            'minimal' => [
                'nombre' => __('Minimalista', 'flavor-chat-ia'),
                'descripcion' => __('Diseño ultra limpio en blanco y negro', 'flavor-chat-ia'),
                'color_primario' => '#171717',
                'color_secundario' => '#525252',
                'color_fondo' => '#fafafa',
                'preview_class' => 'theme-minimal',
            ],
        ];
    }

    /**
     * Inicializa los módulos disponibles con descripciones
     *
     * @return void
     */
    private function init_modulos() {
        $this->modulos_disponibles = [
            'eventos' => [
                'nombre' => __('Eventos', 'flavor-chat-ia'),
                'descripcion' => __('Crear y gestionar eventos con inscripciones y recordatorios', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar-alt',
                'categoria' => 'contenido',
            ],
            'talleres' => [
                'nombre' => __('Talleres', 'flavor-chat-ia'),
                'descripcion' => __('Organizar talleres formativos con plazas limitadas', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
                'categoria' => 'contenido',
            ],
            'cursos' => [
                'nombre' => __('Cursos Online', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma de formación con lecciones y certificados', 'flavor-chat-ia'),
                'icono' => 'dashicons-book',
                'categoria' => 'contenido',
            ],
            'socios' => [
                'nombre' => __('Gestión de Socios', 'flavor-chat-ia'),
                'descripcion' => __('Base de datos de socios con cuotas y carnets', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'categoria' => 'comunidad',
            ],
            'marketplace' => [
                'nombre' => __('Marketplace', 'flavor-chat-ia'),
                'descripcion' => __('Compraventa entre usuarios de la comunidad', 'flavor-chat-ia'),
                'icono' => 'dashicons-store',
                'categoria' => 'comercio',
            ],
            'grupos_consumo' => [
                'nombre' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'descripcion' => __('Pedidos colectivos a productores locales', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'categoria' => 'comercio',
            ],
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'descripcion' => __('Intercambio de servicios por horas', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'categoria' => 'comunidad',
            ],
            'chat_grupos' => [
                'nombre' => __('Chat Grupal', 'flavor-chat-ia'),
                'descripcion' => __('Mensajería en grupo para comunidades', 'flavor-chat-ia'),
                'icono' => 'dashicons-format-chat',
                'categoria' => 'comunicacion',
            ],
            'chat_interno' => [
                'nombre' => __('Chat Interno', 'flavor-chat-ia'),
                'descripcion' => __('Mensajería privada entre usuarios', 'flavor-chat-ia'),
                'icono' => 'dashicons-email-alt',
                'categoria' => 'comunicacion',
            ],
            'reservas' => [
                'nombre' => __('Reservas', 'flavor-chat-ia'),
                'descripcion' => __('Sistema de reservas de espacios o servicios', 'flavor-chat-ia'),
                'icono' => 'dashicons-calendar',
                'categoria' => 'operaciones',
            ],
            'espacios_comunes' => [
                'nombre' => __('Espacios Comunes', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de salas y recursos compartidos', 'flavor-chat-ia'),
                'icono' => 'dashicons-building',
                'categoria' => 'operaciones',
            ],
            'huertos_urbanos' => [
                'nombre' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de parcelas y huertos comunitarios', 'flavor-chat-ia'),
                'icono' => 'dashicons-palmtree',
                'categoria' => 'sostenibilidad',
            ],
            'bicicletas_compartidas' => [
                'nombre' => __('Bicis Compartidas', 'flavor-chat-ia'),
                'descripcion' => __('Sistema de préstamo de bicicletas', 'flavor-chat-ia'),
                'icono' => 'dashicons-performance',
                'categoria' => 'sostenibilidad',
            ],
            'reciclaje' => [
                'nombre' => __('Reciclaje', 'flavor-chat-ia'),
                'descripcion' => __('Puntos limpios y gamificación de reciclaje', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-site',
                'categoria' => 'sostenibilidad',
            ],
            'participacion' => [
                'nombre' => __('Participación', 'flavor-chat-ia'),
                'descripcion' => __('Votaciones y encuestas ciudadanas', 'flavor-chat-ia'),
                'icono' => 'dashicons-megaphone',
                'categoria' => 'gobernanza',
            ],
            'transparencia' => [
                'nombre' => __('Transparencia', 'flavor-chat-ia'),
                'descripcion' => __('Portal de datos abiertos y rendición de cuentas', 'flavor-chat-ia'),
                'icono' => 'dashicons-visibility',
                'categoria' => 'gobernanza',
            ],
            'incidencias' => [
                'nombre' => __('Incidencias', 'flavor-chat-ia'),
                'descripcion' => __('Reporte y seguimiento de incidencias', 'flavor-chat-ia'),
                'icono' => 'dashicons-warning',
                'categoria' => 'operaciones',
            ],
            'ayuda_vecinal' => [
                'nombre' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'descripcion' => __('Red de apoyo mutuo entre vecinos', 'flavor-chat-ia'),
                'icono' => 'dashicons-heart',
                'categoria' => 'comunidad',
            ],
            'facturas' => [
                'nombre' => __('Facturación', 'flavor-chat-ia'),
                'descripcion' => __('Generación y gestión de facturas', 'flavor-chat-ia'),
                'icono' => 'dashicons-media-text',
                'categoria' => 'comercio',
            ],
            'multimedia' => [
                'nombre' => __('Multimedia', 'flavor-chat-ia'),
                'descripcion' => __('Galería de fotos, vídeos y documentos', 'flavor-chat-ia'),
                'icono' => 'dashicons-format-gallery',
                'categoria' => 'contenido',
            ],
        ];
    }

    /**
     * Carga los datos guardados del wizard desde user_meta
     *
     * @return void
     */
    private function cargar_datos_guardados() {
        $usuario_actual = get_current_user_id();
        $datos_guardados = get_user_meta($usuario_actual, 'flavor_wizard_progress', true);

        if (!empty($datos_guardados) && is_array($datos_guardados)) {
            $this->datos_wizard = $datos_guardados;
        } else {
            $this->datos_wizard = [
                'perfil' => '',
                'nombre_sitio' => get_bloginfo('name'),
                'logo_url' => '',
                'color_primario' => '#3b82f6',
                'color_secundario' => '#6b7280',
                'modulos_activos' => [],
                'tema_visual' => 'default',
                'importar_demo' => false,
                'notificaciones_email' => true,
                'notificaciones_push' => false,
                'paso_completado' => '',
            ];
        }
    }

    /**
     * Guarda el progreso del wizard en user_meta
     *
     * @param array $datos Datos a guardar
     * @return bool
     */
    private function guardar_progreso($datos) {
        $usuario_actual = get_current_user_id();
        $datos_actuales = $this->datos_wizard;

        // Merge de datos
        $datos_actualizados = array_merge($datos_actuales, $datos);
        $this->datos_wizard = $datos_actualizados;

        return update_user_meta($usuario_actual, 'flavor_wizard_progress', $datos_actualizados);
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Mostrar wizard solo en la activación o cuando se solicite
        add_action('admin_init', [$this, 'maybe_redirect_to_wizard']);

        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_wizard_page']);

        // Procesar formularios del wizard (legacy - mantener para compatibilidad)
        add_action('admin_init', [$this, 'process_wizard_steps']);

        // Assets del wizard
        add_action('admin_enqueue_scripts', [$this, 'enqueue_wizard_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_wizard_save_step', [$this, 'ajax_save_step']);
        add_action('wp_ajax_flavor_wizard_import_demo', [$this, 'ajax_import_demo']);
        add_action('wp_ajax_flavor_wizard_complete', [$this, 'ajax_complete_wizard']);
        add_action('wp_ajax_flavor_wizard_skip', [$this, 'ajax_skip_wizard']);
        add_action('wp_ajax_flavor_wizard_get_progress', [$this, 'ajax_get_progress']);
    }

    /**
     * Redirige al wizard en la primera activación
     *
     * @return void
     */
    public function maybe_redirect_to_wizard() {
        // Solo redirigir si es la primera vez
        if (get_option('flavor_setup_completed')) {
            return;
        }

        // Solo si estamos en admin y no estamos ya en el wizard
        if (!is_admin()) {
            return;
        }

        // Si ya estamos en el wizard, no redirigir
        if (isset($_GET['page']) && $_GET['page'] === 'flavor-setup-wizard') {
            return;
        }

        // Solo redirigir una vez por sesión
        if (get_transient('flavor_setup_redirect')) {
            return;
        }

        // No redirigir en AJAX
        if (wp_doing_ajax()) {
            return;
        }

        set_transient('flavor_setup_redirect', true, 60);

        wp_safe_redirect(admin_url('admin.php?page=flavor-setup-wizard'));
        exit;
    }

    /**
     * Agrega la página del wizard (oculta del menú)
     *
     * @return void
     */
    public function add_wizard_page() {
        add_submenu_page(
            null, // Parent null = oculta del menú
            __('Configuración Inicial', 'flavor-chat-ia'),
            __('Setup Wizard', 'flavor-chat-ia'),
            'manage_options',
            'flavor-setup-wizard',
            [$this, 'render_wizard']
        );
    }

    /**
     * Procesa los pasos del wizard (legacy POST handler)
     *
     * @return void
     */
    public function process_wizard_steps() {
        if (!isset($_POST['flavor_wizard_step']) || !isset($_POST['_wpnonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'], 'flavor_wizard_step')) {
            return;
        }

        // El procesamiento ahora se hace por AJAX, pero mantener para compatibilidad
    }

    /**
     * AJAX: Guardar paso actual
     *
     * @return void
     */
    public function ajax_save_step() {
        check_ajax_referer('flavor_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes', 'flavor-chat-ia')]);
        }

        $paso = isset($_POST['step']) ? sanitize_text_field($_POST['step']) : '';
        $datos = isset($_POST['data']) ? $_POST['data'] : [];

        if (empty($paso) || !isset($this->pasos[$paso])) {
            wp_send_json_error(['message' => __('Paso no válido', 'flavor-chat-ia')]);
        }

        // Sanitizar datos según el paso
        $datos_sanitizados = $this->sanitizar_datos_paso($paso, $datos);

        // Guardar progreso
        $datos_sanitizados['paso_completado'] = $paso;
        $this->guardar_progreso($datos_sanitizados);

        // Aplicar configuración según el paso
        $this->aplicar_configuracion_paso($paso, $datos_sanitizados);

        wp_send_json_success([
            'message' => __('Paso guardado correctamente', 'flavor-chat-ia'),
            'next_step' => $this->obtener_siguiente_paso($paso),
        ]);
    }

    /**
     * Sanitiza los datos según el paso
     *
     * @param string $paso Paso actual
     * @param array $datos Datos recibidos
     * @return array Datos sanitizados
     */
    private function sanitizar_datos_paso($paso, $datos) {
        $datos_sanitizados = [];

        switch ($paso) {
            case 'bienvenida':
                if (isset($datos['perfil'])) {
                    $datos_sanitizados['perfil'] = sanitize_text_field($datos['perfil']);
                }
                break;

            case 'info_basica':
                if (isset($datos['nombre_sitio'])) {
                    $datos_sanitizados['nombre_sitio'] = sanitize_text_field($datos['nombre_sitio']);
                }
                if (isset($datos['logo_url'])) {
                    $datos_sanitizados['logo_url'] = esc_url_raw($datos['logo_url']);
                }
                if (isset($datos['color_primario'])) {
                    $datos_sanitizados['color_primario'] = sanitize_hex_color($datos['color_primario']);
                }
                if (isset($datos['color_secundario'])) {
                    $datos_sanitizados['color_secundario'] = sanitize_hex_color($datos['color_secundario']);
                }
                break;

            case 'modulos':
                if (isset($datos['modulos_activos']) && is_array($datos['modulos_activos'])) {
                    $datos_sanitizados['modulos_activos'] = array_map('sanitize_text_field', $datos['modulos_activos']);
                }
                break;

            case 'diseno':
                if (isset($datos['tema_visual'])) {
                    $datos_sanitizados['tema_visual'] = sanitize_text_field($datos['tema_visual']);
                }
                break;

            case 'demo_data':
                $datos_sanitizados['importar_demo'] = isset($datos['importar_demo']) && $datos['importar_demo'] === 'true';
                break;

            case 'notificaciones':
                $datos_sanitizados['notificaciones_email'] = isset($datos['notificaciones_email']) && $datos['notificaciones_email'] === 'true';
                $datos_sanitizados['notificaciones_push'] = isset($datos['notificaciones_push']) && $datos['notificaciones_push'] === 'true';
                if (isset($datos['email_remitente'])) {
                    $datos_sanitizados['email_remitente'] = sanitize_email($datos['email_remitente']);
                }
                break;
        }

        return $datos_sanitizados;
    }

    /**
     * Aplica la configuración según el paso
     *
     * @param string $paso Paso actual
     * @param array $datos Datos a aplicar
     * @return void
     */
    private function aplicar_configuracion_paso($paso, $datos) {
        switch ($paso) {
            case 'bienvenida':
                if (!empty($datos['perfil'])) {
                    update_option('flavor_selected_profile', $datos['perfil']);

                    // Activar módulos requeridos del perfil
                    if (class_exists('Flavor_App_Profiles')) {
                        $perfiles = Flavor_App_Profiles::get_instance();
                        $datos_perfil = $perfiles->obtener_perfil($datos['perfil']);

                        if ($datos_perfil && isset($datos_perfil['modulos_requeridos'])) {
                            update_option('flavor_active_modules', $datos_perfil['modulos_requeridos']);
                        }
                    }
                }
                break;

            case 'info_basica':
                if (!empty($datos['nombre_sitio'])) {
                    update_option('blogname', $datos['nombre_sitio']);
                }
                if (!empty($datos['logo_url'])) {
                    update_option('flavor_logo_url', $datos['logo_url']);
                }
                if (!empty($datos['color_primario'])) {
                    update_option('flavor_primary_color', $datos['color_primario']);
                }
                if (!empty($datos['color_secundario'])) {
                    update_option('flavor_secondary_color', $datos['color_secundario']);
                }
                break;

            case 'modulos':
                if (!empty($datos['modulos_activos'])) {
                    // Merge con los módulos requeridos del perfil
                    $modulos_actuales = get_option('flavor_active_modules', []);
                    $modulos_finales = array_unique(array_merge($modulos_actuales, $datos['modulos_activos']));
                    update_option('flavor_active_modules', $modulos_finales);
                }
                break;

            case 'diseno':
                if (!empty($datos['tema_visual'])) {
                    update_option('flavor_active_theme', $datos['tema_visual']);
                }
                break;

            case 'notificaciones':
                update_option('flavor_notifications_email', $datos['notificaciones_email'] ?? true);
                update_option('flavor_notifications_push', $datos['notificaciones_push'] ?? false);
                if (!empty($datos['email_remitente'])) {
                    update_option('flavor_email_from', $datos['email_remitente']);
                }
                break;
        }
    }

    /**
     * Obtiene el siguiente paso
     *
     * @param string $paso_actual Paso actual
     * @return string|null Siguiente paso o null si es el último
     */
    private function obtener_siguiente_paso($paso_actual) {
        $claves_pasos = array_keys($this->pasos);
        $indice_actual = array_search($paso_actual, $claves_pasos);

        if ($indice_actual !== false && isset($claves_pasos[$indice_actual + 1])) {
            return $claves_pasos[$indice_actual + 1];
        }

        return null;
    }

    /**
     * AJAX: Importar datos demo
     *
     * @return void
     */
    public function ajax_import_demo() {
        check_ajax_referer('flavor_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes', 'flavor-chat-ia')]);
        }

        $perfil = isset($_POST['perfil']) ? sanitize_text_field($_POST['perfil']) : '';

        if (empty($perfil)) {
            $perfil = get_option('flavor_selected_profile', 'personalizado');
        }

        // Importar datos según el perfil
        $resultado = $this->importar_datos_demo($perfil);

        if ($resultado['success']) {
            wp_send_json_success([
                'message' => $resultado['message'],
                'imported' => $resultado['imported'],
            ]);
        } else {
            wp_send_json_error(['message' => $resultado['message']]);
        }
    }

    /**
     * Importa datos demo según el perfil
     *
     * @param string $perfil Perfil seleccionado
     * @return array Resultado de la importación
     */
    private function importar_datos_demo($perfil) {
        $importados = [];
        $errores = [];

        switch ($perfil) {
            case 'grupo_consumo':
                $importados = $this->importar_demo_grupo_consumo();
                break;

            case 'comunidad':
                $importados = $this->importar_demo_comunidad();
                break;

            case 'banco_tiempo':
                $importados = $this->importar_demo_banco_tiempo();
                break;

            case 'coworking':
                $importados = $this->importar_demo_coworking();
                break;

            case 'barrio':
                $importados = $this->importar_demo_barrio();
                break;

            default:
                // Para perfiles sin demo específica, importar datos genéricos
                $importados = $this->importar_demo_generico();
                break;
        }

        if (!empty($importados)) {
            update_option('flavor_demo_imported', true);
            update_option('flavor_demo_imported_date', current_time('mysql'));
            update_option('flavor_demo_imported_profile', $perfil);

            return [
                'success' => true,
                'message' => sprintf(__('Se han importado %d elementos de ejemplo', 'flavor-chat-ia'), count($importados)),
                'imported' => $importados,
            ];
        }

        return [
            'success' => false,
            'message' => __('No se pudieron importar los datos de ejemplo', 'flavor-chat-ia'),
            'imported' => [],
        ];
    }

    /**
     * Importa datos demo para Grupo de Consumo
     *
     * @return array Elementos importados
     */
    private function importar_demo_grupo_consumo() {
        $importados = [];

        // Crear 3 productores ficticios
        $productores = [
            [
                'nombre' => 'Huerta Ecológica La Verdura',
                'descripcion' => 'Productores locales de verduras y hortalizas ecológicas certificadas.',
                'email' => 'huerta@ejemplo.com',
                'telefono' => '600111222',
                'ubicacion' => 'Navarra',
            ],
            [
                'nombre' => 'Granja Avícola El Corral',
                'descripcion' => 'Huevos camperos y pollos de corral criados en libertad.',
                'email' => 'granja@ejemplo.com',
                'telefono' => '600333444',
                'ubicacion' => 'Aragón',
            ],
            [
                'nombre' => 'Conservas Artesanas Tradición',
                'descripcion' => 'Conservas tradicionales elaboradas con productos de temporada.',
                'email' => 'conservas@ejemplo.com',
                'telefono' => '600555666',
                'ubicacion' => 'La Rioja',
            ],
        ];

        foreach ($productores as $productor) {
            $post_id = wp_insert_post([
                'post_title' => $productor['nombre'],
                'post_content' => $productor['descripcion'],
                'post_type' => 'flavor_productor',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_productor_email', $productor['email']);
                update_post_meta($post_id, '_productor_telefono', $productor['telefono']);
                update_post_meta($post_id, '_productor_ubicacion', $productor['ubicacion']);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'productor', 'id' => $post_id, 'title' => $productor['nombre']];
            }
        }

        // Crear 10 productos
        $productos = [
            ['nombre' => 'Tomates Raf Eco (1kg)', 'precio' => 4.50, 'productor' => 0],
            ['nombre' => 'Lechugas Variadas (pack 3)', 'precio' => 3.00, 'productor' => 0],
            ['nombre' => 'Zanahorias Eco (1kg)', 'precio' => 2.50, 'productor' => 0],
            ['nombre' => 'Calabacines (1kg)', 'precio' => 2.80, 'productor' => 0],
            ['nombre' => 'Huevos Camperos (docena)', 'precio' => 4.00, 'productor' => 1],
            ['nombre' => 'Pollo Campero (unidad ~2kg)', 'precio' => 18.00, 'productor' => 1],
            ['nombre' => 'Caldo de Pollo (500ml)', 'precio' => 5.50, 'productor' => 1],
            ['nombre' => 'Mermelada de Fresa (250g)', 'precio' => 4.50, 'productor' => 2],
            ['nombre' => 'Pimientos del Piquillo (lata)', 'precio' => 3.80, 'productor' => 2],
            ['nombre' => 'Tomate Triturado Eco (500g)', 'precio' => 2.90, 'productor' => 2],
        ];

        foreach ($productos as $producto) {
            $post_id = wp_insert_post([
                'post_title' => $producto['nombre'],
                'post_content' => 'Producto de calidad de nuestros productores locales.',
                'post_type' => 'flavor_producto',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_producto_precio', $producto['precio']);
                update_post_meta($post_id, '_producto_disponible', true);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'producto', 'id' => $post_id, 'title' => $producto['nombre']];
            }
        }

        // Crear 1 ciclo de pedidos
        $fecha_inicio = date('Y-m-d', strtotime('+3 days'));
        $fecha_cierre = date('Y-m-d', strtotime('+10 days'));
        $fecha_reparto = date('Y-m-d', strtotime('+14 days'));

        $ciclo_id = wp_insert_post([
            'post_title' => 'Ciclo de Primavera - ' . date('F Y'),
            'post_content' => 'Ciclo de pedidos de productos de temporada.',
            'post_type' => 'flavor_ciclo',
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($ciclo_id)) {
            update_post_meta($ciclo_id, '_ciclo_fecha_inicio', $fecha_inicio);
            update_post_meta($ciclo_id, '_ciclo_fecha_cierre', $fecha_cierre);
            update_post_meta($ciclo_id, '_ciclo_fecha_reparto', $fecha_reparto);
            update_post_meta($ciclo_id, '_ciclo_estado', 'abierto');
            update_post_meta($ciclo_id, '_is_demo_content', true);
            $importados[] = ['type' => 'ciclo', 'id' => $ciclo_id, 'title' => 'Ciclo de Primavera'];
        }

        // Crear 1 grupo
        $grupo_id = wp_insert_post([
            'post_title' => 'Grupo Centro - Recogida en Local Social',
            'post_content' => 'Grupo de consumo del centro de la ciudad. Recogida los jueves de 17h a 20h.',
            'post_type' => 'flavor_grupo',
            'post_status' => 'publish',
        ]);

        if (!is_wp_error($grupo_id)) {
            update_post_meta($grupo_id, '_grupo_direccion', 'Calle Mayor, 15');
            update_post_meta($grupo_id, '_grupo_horario', 'Jueves 17:00-20:00');
            update_post_meta($grupo_id, '_grupo_activo', true);
            update_post_meta($grupo_id, '_is_demo_content', true);
            $importados[] = ['type' => 'grupo', 'id' => $grupo_id, 'title' => 'Grupo Centro'];
        }

        return $importados;
    }

    /**
     * Importa datos demo para Comunidad
     *
     * @return array Elementos importados
     */
    private function importar_demo_comunidad() {
        $importados = [];

        // Crear 5 eventos
        $eventos = [
            [
                'nombre' => 'Asamblea General Anual',
                'descripcion' => 'Asamblea anual para renovación de junta directiva y aprobación de presupuestos.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+15 days 18:00')),
                'lugar' => 'Salón de Actos',
            ],
            [
                'nombre' => 'Fiesta de Primavera',
                'descripcion' => 'Celebración comunitaria con música en vivo, comida y actividades para toda la familia.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+30 days 12:00')),
                'lugar' => 'Plaza del Pueblo',
            ],
            [
                'nombre' => 'Mercadillo Solidario',
                'descripcion' => 'Venta de artículos de segunda mano con fines benéficos.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+7 days 10:00')),
                'lugar' => 'Patio Central',
            ],
            [
                'nombre' => 'Cine de Verano',
                'descripcion' => 'Proyección de película familiar al aire libre.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+45 days 21:30')),
                'lugar' => 'Jardín Comunitario',
            ],
            [
                'nombre' => 'Jornada de Limpieza Colectiva',
                'descripcion' => 'Día de limpieza y mantenimiento de espacios comunes.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+21 days 09:00')),
                'lugar' => 'Zonas Comunes',
            ],
        ];

        foreach ($eventos as $evento) {
            $post_id = wp_insert_post([
                'post_title' => $evento['nombre'],
                'post_content' => $evento['descripcion'],
                'post_type' => 'flavor_evento',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_evento_fecha', $evento['fecha']);
                update_post_meta($post_id, '_evento_lugar', $evento['lugar']);
                update_post_meta($post_id, '_evento_plazas', rand(20, 100));
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'evento', 'id' => $post_id, 'title' => $evento['nombre']];
            }
        }

        // Crear 3 talleres
        $talleres = [
            [
                'nombre' => 'Taller de Huerto Urbano',
                'descripcion' => 'Aprende a cultivar tus propias verduras en casa o en espacios reducidos.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+10 days 17:00')),
                'plazas' => 15,
            ],
            [
                'nombre' => 'Iniciación al Yoga',
                'descripcion' => 'Taller de introducción al yoga para todos los niveles.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+5 days 10:00')),
                'plazas' => 20,
            ],
            [
                'nombre' => 'Reparación de Electrodomésticos',
                'descripcion' => 'Aprende a reparar pequeños electrodomésticos y alarga su vida útil.',
                'fecha' => date('Y-m-d H:i:s', strtotime('+18 days 16:00')),
                'plazas' => 12,
            ],
        ];

        foreach ($talleres as $taller) {
            $post_id = wp_insert_post([
                'post_title' => $taller['nombre'],
                'post_content' => $taller['descripcion'],
                'post_type' => 'flavor_taller',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_taller_fecha', $taller['fecha']);
                update_post_meta($post_id, '_taller_plazas', $taller['plazas']);
                update_post_meta($post_id, '_taller_precio', 0);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'taller', 'id' => $post_id, 'title' => $taller['nombre']];
            }
        }

        // Crear 10 socios ficticios
        $nombres = ['María García', 'Juan López', 'Ana Martínez', 'Carlos Rodríguez', 'Laura Fernández',
                    'Pedro Sánchez', 'Elena Ruiz', 'Miguel Torres', 'Carmen Díaz', 'David Moreno'];

        foreach ($nombres as $indice => $nombre) {
            $post_id = wp_insert_post([
                'post_title' => $nombre,
                'post_content' => '',
                'post_type' => 'flavor_socio',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_socio_numero', 'SOC-' . str_pad($indice + 1, 4, '0', STR_PAD_LEFT));
                update_post_meta($post_id, '_socio_email', strtolower(str_replace(' ', '.', $nombre)) . '@ejemplo.com');
                update_post_meta($post_id, '_socio_fecha_alta', date('Y-m-d', strtotime('-' . rand(30, 365) . ' days')));
                update_post_meta($post_id, '_socio_activo', true);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'socio', 'id' => $post_id, 'title' => $nombre];
            }
        }

        return $importados;
    }

    /**
     * Importa datos demo para Banco de Tiempo
     *
     * @return array Elementos importados
     */
    private function importar_demo_banco_tiempo() {
        $importados = [];

        // Crear 5 servicios
        $servicios = [
            [
                'nombre' => 'Clases de Inglés',
                'descripcion' => 'Clases particulares de inglés para todos los niveles.',
                'categoria' => 'educacion',
                'horas' => 1,
            ],
            [
                'nombre' => 'Reparaciones Domésticas',
                'descripcion' => 'Pequeñas reparaciones en el hogar: fontanería, electricidad básica.',
                'categoria' => 'hogar',
                'horas' => 2,
            ],
            [
                'nombre' => 'Cuidado de Mascotas',
                'descripcion' => 'Paseo de perros y cuidado de mascotas durante ausencias.',
                'categoria' => 'mascotas',
                'horas' => 1,
            ],
            [
                'nombre' => 'Clases de Cocina',
                'descripcion' => 'Aprende a cocinar platos tradicionales y saludables.',
                'categoria' => 'cocina',
                'horas' => 2,
            ],
            [
                'nombre' => 'Ayuda Informática',
                'descripcion' => 'Soporte con ordenadores, móviles y aplicaciones.',
                'categoria' => 'tecnologia',
                'horas' => 1,
            ],
        ];

        foreach ($servicios as $servicio) {
            $post_id = wp_insert_post([
                'post_title' => $servicio['nombre'],
                'post_content' => $servicio['descripcion'],
                'post_type' => 'flavor_servicio_bt',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_servicio_categoria', $servicio['categoria']);
                update_post_meta($post_id, '_servicio_horas', $servicio['horas']);
                update_post_meta($post_id, '_servicio_disponible', true);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'servicio', 'id' => $post_id, 'title' => $servicio['nombre']];
            }
        }

        // Crear 5 usuarios con habilidades
        $usuarios_habilidades = [
            ['nombre' => 'Rosa Jiménez', 'habilidades' => ['Inglés', 'Francés', 'Traducciones']],
            ['nombre' => 'Antonio Pérez', 'habilidades' => ['Fontanería', 'Electricidad', 'Pintura']],
            ['nombre' => 'Lucía Gómez', 'habilidades' => ['Cuidado de niños', 'Apoyo escolar']],
            ['nombre' => 'Fernando Ruiz', 'habilidades' => ['Jardinería', 'Bricolaje', 'Mecánica básica']],
            ['nombre' => 'Isabel Martín', 'habilidades' => ['Cocina', 'Repostería', 'Nutrición']],
        ];

        foreach ($usuarios_habilidades as $usuario) {
            $post_id = wp_insert_post([
                'post_title' => $usuario['nombre'],
                'post_content' => '',
                'post_type' => 'flavor_miembro_bt',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_miembro_habilidades', $usuario['habilidades']);
                update_post_meta($post_id, '_miembro_horas_disponibles', rand(5, 20));
                update_post_meta($post_id, '_miembro_horas_banco', rand(0, 10));
                update_post_meta($post_id, '_miembro_activo', true);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'miembro_bt', 'id' => $post_id, 'title' => $usuario['nombre']];
            }
        }

        return $importados;
    }

    /**
     * Importa datos demo para Coworking
     *
     * @return array Elementos importados
     */
    private function importar_demo_coworking() {
        $importados = [];

        // Crear espacios
        $espacios = [
            ['nombre' => 'Sala de Reuniones A', 'capacidad' => 8, 'precio_hora' => 15],
            ['nombre' => 'Sala de Reuniones B', 'capacidad' => 4, 'precio_hora' => 10],
            ['nombre' => 'Espacio de Eventos', 'capacidad' => 50, 'precio_hora' => 40],
            ['nombre' => 'Zona Coworking Abierta', 'capacidad' => 20, 'precio_hora' => 5],
        ];

        foreach ($espacios as $espacio) {
            $post_id = wp_insert_post([
                'post_title' => $espacio['nombre'],
                'post_content' => 'Espacio disponible para reservas.',
                'post_type' => 'flavor_espacio',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_espacio_capacidad', $espacio['capacidad']);
                update_post_meta($post_id, '_espacio_precio_hora', $espacio['precio_hora']);
                update_post_meta($post_id, '_espacio_disponible', true);
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'espacio', 'id' => $post_id, 'title' => $espacio['nombre']];
            }
        }

        return $importados;
    }

    /**
     * Importa datos demo para Barrio
     *
     * @return array Elementos importados
     */
    private function importar_demo_barrio() {
        $importados = [];

        // Combinar demos relevantes
        $importados = array_merge(
            $importados,
            $this->importar_demo_comunidad()
        );

        // Añadir algunas incidencias de ejemplo
        $incidencias = [
            ['titulo' => 'Farola fundida en Calle Mayor', 'tipo' => 'alumbrado'],
            ['titulo' => 'Bache en la acera', 'tipo' => 'vias'],
            ['titulo' => 'Papelera llena', 'tipo' => 'limpieza'],
        ];

        foreach ($incidencias as $incidencia) {
            $post_id = wp_insert_post([
                'post_title' => $incidencia['titulo'],
                'post_content' => 'Incidencia reportada por un vecino.',
                'post_type' => 'flavor_incidencia',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_incidencia_tipo', $incidencia['tipo']);
                update_post_meta($post_id, '_incidencia_estado', 'pendiente');
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'incidencia', 'id' => $post_id, 'title' => $incidencia['titulo']];
            }
        }

        return $importados;
    }

    /**
     * Importa datos demo genéricos
     *
     * @return array Elementos importados
     */
    private function importar_demo_generico() {
        $importados = [];

        // Crear algunas páginas básicas
        $paginas = [
            [
                'titulo' => 'Sobre Nosotros',
                'contenido' => 'Bienvenido a nuestra comunidad. Aquí encontrarás información sobre quiénes somos y qué hacemos.',
            ],
            [
                'titulo' => 'Contacto',
                'contenido' => 'Ponte en contacto con nosotros. Estaremos encantados de atenderte.',
            ],
        ];

        foreach ($paginas as $pagina) {
            $post_id = wp_insert_post([
                'post_title' => $pagina['titulo'],
                'post_content' => $pagina['contenido'],
                'post_type' => 'page',
                'post_status' => 'publish',
            ]);

            if (!is_wp_error($post_id)) {
                update_post_meta($post_id, '_is_demo_content', true);
                $importados[] = ['type' => 'page', 'id' => $post_id, 'title' => $pagina['titulo']];
            }
        }

        return $importados;
    }

    /**
     * AJAX: Completar wizard
     *
     * @return void
     */
    public function ajax_complete_wizard() {
        check_ajax_referer('flavor_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes', 'flavor-chat-ia')]);
        }

        // Marcar como completado
        update_option('flavor_setup_completed', true);
        update_option('flavor_setup_completed_date', current_time('mysql'));

        // Limpiar datos temporales del wizard
        $usuario_actual = get_current_user_id();
        delete_user_meta($usuario_actual, 'flavor_wizard_progress');

        // Limpiar transient de redirección
        delete_transient('flavor_setup_redirect');

        wp_send_json_success([
            'message' => __('Configuración completada correctamente', 'flavor-chat-ia'),
            'redirect_url' => admin_url('admin.php?page=flavor-dashboard'),
        ]);
    }

    /**
     * AJAX: Saltar wizard
     *
     * @return void
     */
    public function ajax_skip_wizard() {
        check_ajax_referer('flavor_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes', 'flavor-chat-ia')]);
        }

        // Marcar como completado (saltado)
        update_option('flavor_setup_completed', true);
        update_option('flavor_setup_completed_date', current_time('mysql'));
        update_option('flavor_setup_skipped', true);

        // Limpiar datos temporales
        $usuario_actual = get_current_user_id();
        delete_user_meta($usuario_actual, 'flavor_wizard_progress');
        delete_transient('flavor_setup_redirect');

        wp_send_json_success([
            'message' => __('Configuración saltada', 'flavor-chat-ia'),
            'redirect_url' => admin_url('admin.php?page=flavor-dashboard'),
        ]);
    }

    /**
     * AJAX: Obtener progreso guardado
     *
     * @return void
     */
    public function ajax_get_progress() {
        check_ajax_referer('flavor_wizard_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('No tienes permisos suficientes', 'flavor-chat-ia')]);
        }

        wp_send_json_success([
            'progress' => $this->datos_wizard,
        ]);
    }

    /**
     * Renderiza el wizard
     *
     * @return void
     */
    public function render_wizard() {
        $this->paso_actual = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'bienvenida';

        // Validar que el paso exista
        if (!isset($this->pasos[$this->paso_actual])) {
            $this->paso_actual = 'bienvenida';
        }

        // Calcular índice del paso actual
        $claves_pasos = array_keys($this->pasos);
        $this->indice_paso_actual = array_search($this->paso_actual, $claves_pasos);

        // Verificar si el usuario tiene un paso guardado y debe continuar desde ahí
        if (!empty($this->datos_wizard['paso_completado']) && $this->paso_actual === 'bienvenida') {
            $indice_guardado = array_search($this->datos_wizard['paso_completado'], $claves_pasos);
            if ($indice_guardado !== false && $indice_guardado < count($claves_pasos) - 1) {
                // Hay progreso guardado, preguntar si continuar
                $this->mostrar_dialogo_continuar = true;
            }
        }

        // Cargar la vista
        include FLAVOR_CHAT_IA_PATH . 'admin/views/setup-wizard.php';
    }

    /**
     * Obtiene los pasos del wizard
     *
     * @return array
     */
    public function get_pasos() {
        return $this->pasos;
    }

    /**
     * Obtiene el paso actual
     *
     * @return string
     */
    public function get_paso_actual() {
        return $this->paso_actual;
    }

    /**
     * Obtiene el índice del paso actual
     *
     * @return int
     */
    public function get_indice_paso_actual() {
        return $this->indice_paso_actual;
    }

    /**
     * Obtiene los datos guardados del wizard
     *
     * @return array
     */
    public function get_datos_wizard() {
        return $this->datos_wizard;
    }

    /**
     * Obtiene los temas visuales disponibles
     *
     * @return array
     */
    public function get_temas_visuales() {
        return $this->temas_visuales;
    }

    /**
     * Obtiene los módulos disponibles
     *
     * @return array
     */
    public function get_modulos_disponibles() {
        return $this->modulos_disponibles;
    }

    /**
     * Obtiene los perfiles de aplicación
     *
     * @return array
     */
    public function get_perfiles() {
        if (class_exists('Flavor_App_Profiles')) {
            return Flavor_App_Profiles::get_instance()->obtener_perfiles();
        }

        // Fallback si la clase no está disponible
        return [
            'grupo_consumo' => [
                'nombre' => __('Grupo de Consumo', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de pedidos colectivos y productores locales', 'flavor-chat-ia'),
                'icono' => 'dashicons-carrot',
                'color' => '#46b450',
            ],
            'comunidad' => [
                'nombre' => __('Comunidad/Asociación', 'flavor-chat-ia'),
                'descripcion' => __('Gestión de socios, eventos y recursos', 'flavor-chat-ia'),
                'icono' => 'dashicons-groups',
                'color' => '#e91e63',
            ],
            'banco_tiempo' => [
                'nombre' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'descripcion' => __('Intercambio de servicios por horas', 'flavor-chat-ia'),
                'icono' => 'dashicons-clock',
                'color' => '#9b59b6',
            ],
            'barrio' => [
                'nombre' => __('Barrio/Vecindario', 'flavor-chat-ia'),
                'descripcion' => __('Plataforma vecinal con servicios comunitarios', 'flavor-chat-ia'),
                'icono' => 'dashicons-location',
                'color' => '#22c55e',
            ],
            'personalizado' => [
                'nombre' => __('Personalizado', 'flavor-chat-ia'),
                'descripcion' => __('Configura manualmente los módulos que necesitas', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-generic',
                'color' => '#95a5a6',
            ],
        ];
    }

    /**
     * Registra assets del wizard
     *
     * @param string $hook_suffix Sufijo del hook
     * @return void
     */
    public function enqueue_wizard_assets($hook_suffix) {
        if ($hook_suffix !== 'admin_page_flavor-setup-wizard') {
            return;
        }

        // WordPress media uploader para logo
        wp_enqueue_media();

        // Color picker
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // CSS del wizard
        wp_enqueue_style(
            'flavor-setup-wizard',
            FLAVOR_CHAT_IA_URL . 'admin/css/setup-wizard.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // JS del wizard
        wp_enqueue_script(
            'flavor-setup-wizard',
            FLAVOR_CHAT_IA_URL . 'admin/js/setup-wizard.js',
            ['jquery', 'wp-color-picker'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Pasar datos al JS
        wp_localize_script('flavor-setup-wizard', 'flavorWizard', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_wizard_nonce'),
            'pasos' => array_keys($this->pasos),
            'paso_actual' => $this->paso_actual,
            'datos_guardados' => $this->datos_wizard,
            'temas' => $this->temas_visuales,
            'strings' => [
                'guardando' => __('Guardando...', 'flavor-chat-ia'),
                'guardado' => __('Guardado', 'flavor-chat-ia'),
                'error' => __('Error al guardar', 'flavor-chat-ia'),
                'importando' => __('Importando datos...', 'flavor-chat-ia'),
                'importado' => __('Datos importados correctamente', 'flavor-chat-ia'),
                'error_importar' => __('Error al importar datos', 'flavor-chat-ia'),
                'confirmar_saltar' => __('¿Estás seguro de que quieres saltar la configuración? Podrás configurar todo manualmente después.', 'flavor-chat-ia'),
                'seleccionar_logo' => __('Seleccionar Logo', 'flavor-chat-ia'),
                'usar_imagen' => __('Usar esta imagen', 'flavor-chat-ia'),
                'campo_requerido' => __('Este campo es requerido', 'flavor-chat-ia'),
                'email_invalido' => __('Email no válido', 'flavor-chat-ia'),
            ],
        ]);
    }
}
