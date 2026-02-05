<?php
/**
 * Módulo de Sector Empresarial
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Empresarial_Module extends Flavor_Chat_Module_Base {

    protected $module_id = 'empresarial';
    protected $module_name = 'Sector Empresarial';
    protected $module_description = 'Componentes profesionales para empresas: héroes corporativos, servicios, equipo, testimonios, estadísticas y más';
    protected $module_icon = 'dashicons-building';

    /**
     * Constructor
     */
    public function __construct() {
        // Mapear propiedades del módulo al formato base
        $this->id = $this->module_id;
        $this->name = $this->module_name;
        $this->description = $this->module_description;

        parent::__construct();
    }

    /**
     * Verificar si el módulo puede activarse
     */
    public function can_activate() {
        return true; // Sin requisitos especiales
    }

    /**
     * Activar módulo
     */
    public function activate() {
        $this->create_tables();
        return true;
    }

    /**
     * Desactivar módulo
     */
    public function deactivate() {
        return true;
    }

    /**
     * Obtener componentes web del módulo
     */
    public function get_web_components() {
        return [
            'empresarial_hero' => [
                'label' => __('Hero Corporativo', 'flavor-chat-ia'),
                'description' => __('Hero profesional para empresas con diseño elegante', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-building',
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Título', 'flavor-chat-ia'),
                        'default' => 'Soluciones Empresariales de Calidad'
                    ],
                    'subtitulo' => [
                        'type' => 'textarea',
                        'label' => __('Subtítulo', 'flavor-chat-ia'),
                        'default' => 'Potencia tu negocio con nuestros servicios profesionales y tecnología de vanguardia'
                    ],
                    'texto_boton_principal' => [
                        'type' => 'text',
                        'label' => __('Texto Botón Principal', 'flavor-chat-ia'),
                        'default' => 'Solicitar Demo'
                    ],
                    'url_boton_principal' => [
                        'type' => 'url',
                        'label' => __('URL Botón Principal', 'flavor-chat-ia'),
                        'default' => '#contacto'
                    ],
                    'texto_boton_secundario' => [
                        'type' => 'text',
                        'label' => __('Texto Botón Secundario', 'flavor-chat-ia'),
                        'default' => 'Ver Servicios'
                    ],
                    'url_boton_secundario' => [
                        'type' => 'url',
                        'label' => __('URL Botón Secundario', 'flavor-chat-ia'),
                        'default' => '#servicios'
                    ],
                    'imagen_fondo' => [
                        'type' => 'image',
                        'label' => __('Imagen de Fondo', 'flavor-chat-ia'),
                        'default' => ''
                    ],
                    'mostrar_video' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Video', 'flavor-chat-ia'),
                        'default' => false
                    ],
                    'url_video' => [
                        'type' => 'url',
                        'label' => __('URL Video (YouTube/Vimeo)', 'flavor-chat-ia'),
                        'default' => ''
                    ]
                ],
                'template' => 'empresarial/hero'
            ],
            'empresarial_servicios' => [
                'label' => __('Grid de Servicios', 'flavor-chat-ia'),
                'description' => __('Muestra servicios o soluciones en un grid profesional', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-grid-view',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestros Servicios'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Soluciones integrales diseñadas para hacer crecer tu negocio'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3'
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['cards', 'minimal', 'bordered'],
                        'default' => 'cards'
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => ['post'],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Servicios', 'flavor-chat-ia'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'icono' => ['type' => 'text', 'label' => __('Icono (clase dashicons)', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/servicios-grid'
            ],
            'empresarial_equipo' => [
                'label' => __('Equipo / Staff', 'flavor-chat-ia'),
                'description' => __('Muestra los miembros del equipo con fotos y roles', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-groups',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestro Equipo'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Profesionales comprometidos con tu éxito'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['grid', 'slider', 'list'],
                        'default' => 'grid'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas (Grid)', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '4'
                    ],
                    'mostrar_redes_sociales' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Redes Sociales', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Miembros del equipo', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                            'puesto' => ['type' => 'text', 'label' => __('Puesto', 'flavor-chat-ia'), 'default' => ''],
                            'bio' => ['type' => 'textarea', 'label' => __('Biografía', 'flavor-chat-ia'), 'default' => ''],
                            'foto' => ['type' => 'image', 'label' => __('Foto', 'flavor-chat-ia'), 'default' => ''],
                            'linkedin' => ['type' => 'url', 'label' => __('LinkedIn URL', 'flavor-chat-ia'), 'default' => ''],
                            'twitter' => ['type' => 'url', 'label' => __('Twitter URL', 'flavor-chat-ia'), 'default' => ''],
                            'email' => ['type' => 'text', 'label' => __('Email', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/equipo'
            ],
            'empresarial_testimonios' => [
                'label' => __('Testimonios', 'flavor-chat-ia'),
                'description' => __('Muestra testimonios de clientes satisfechos', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-format-quote',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Lo Que Dicen Nuestros Clientes'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['carousel', 'grid', 'masonry'],
                        'default' => 'carousel'
                    ],
                    'mostrar_foto' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Foto del Cliente', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'mostrar_empresa' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Empresa', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Testimonios', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre', 'flavor-chat-ia'), 'default' => ''],
                            'puesto' => ['type' => 'text', 'label' => __('Puesto', 'flavor-chat-ia'), 'default' => ''],
                            'empresa' => ['type' => 'text', 'label' => __('Empresa', 'flavor-chat-ia'), 'default' => ''],
                            'testimonio' => ['type' => 'textarea', 'label' => __('Testimonio', 'flavor-chat-ia'), 'default' => ''],
                            'rating' => ['type' => 'number', 'label' => __('Rating (1-5)', 'flavor-chat-ia'), 'default' => 5],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/testimonios'
            ],
            'empresarial_stats' => [
                'label' => __('Estadísticas / Métricas', 'flavor-chat-ia'),
                'description' => __('Muestra números y logros importantes de la empresa', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-chart-bar',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Resultados que Hablan por Sí Solos'
                    ],
                    'estilo' => [
                        'type' => 'select',
                        'label' => __('Estilo', 'flavor-chat-ia'),
                        'options' => ['minimal', 'cards', 'highlighted'],
                        'default' => 'highlighted'
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Estadísticas', 'flavor-chat-ia'),
                        'fields' => [
                            'numero' => ['type' => 'text', 'label' => __('Número / Cifra', 'flavor-chat-ia'), 'default' => ''],
                            'texto' => ['type' => 'text', 'label' => __('Texto descriptivo', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 8,
                    ],
                ],
                'template' => 'empresarial/stats'
            ],
            'empresarial_contacto' => [
                'label' => __('Formulario de Contacto', 'flavor-chat-ia'),
                'description' => __('Formulario profesional de contacto para empresas', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-email',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Contacta con Nosotros'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Estamos aquí para ayudarte. Envíanos tu consulta y te responderemos pronto.'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['simple', 'con_mapa', 'dos_columnas'],
                        'default' => 'dos_columnas'
                    ],
                    'email_destino' => [
                        'type' => 'text',
                        'label' => __('Email de Destino', 'flavor-chat-ia'),
                        'default' => get_option('admin_email')
                    ],
                    'mostrar_telefono' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Teléfono', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'telefono' => [
                        'type' => 'text',
                        'label' => __('Teléfono', 'flavor-chat-ia'),
                        'default' => '+34 900 000 000'
                    ],
                    'mostrar_direccion' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Dirección', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'direccion' => [
                        'type' => 'textarea',
                        'label' => __('Dirección', 'flavor-chat-ia'),
                        'default' => 'Calle Principal 123, 28001 Madrid, España'
                    ]
                ],
                'template' => 'empresarial/contacto'
            ],
            'empresarial_pricing' => [
                'label' => __('Tabla de Precios', 'flavor-chat-ia'),
                'description' => __('Muestra planes y precios de forma clara y atractiva', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-money-alt',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Planes y Precios'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Elige el plan perfecto para tu negocio'
                    ],
                    'periodo' => [
                        'type' => 'select',
                        'label' => __('Periodo de Facturación', 'flavor-chat-ia'),
                        'options' => ['mensual', 'anual', 'ambos'],
                        'default' => 'mensual'
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Planes', 'flavor-chat-ia'),
                        'fields' => [
                            'nombre' => ['type' => 'text', 'label' => __('Nombre del plan', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'precio_mensual' => ['type' => 'text', 'label' => __('Precio mensual', 'flavor-chat-ia'), 'default' => '0'],
                            'precio_anual' => ['type' => 'text', 'label' => __('Precio anual', 'flavor-chat-ia'), 'default' => '0'],
                            'caracteristicas' => ['type' => 'textarea', 'label' => __('Características (una por línea)', 'flavor-chat-ia'), 'default' => ''],
                            'destacar' => ['type' => 'toggle', 'label' => __('Destacar este plan', 'flavor-chat-ia'), 'default' => false],
                            'badge' => ['type' => 'text', 'label' => __('Badge (ej: Más Popular)', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 6,
                    ],
                ],
                'template' => 'empresarial/pricing'
            ],
            'empresarial_portfolio' => [
                'label' => __('Portfolio / Casos de Éxito', 'flavor-chat-ia'),
                'description' => __('Muestra proyectos o casos de éxito de la empresa', 'flavor-chat-ia'),
                'category' => 'empresarial',
                'icon' => 'dashicons-portfolio',
                'fields' => [
                    'titulo_seccion' => [
                        'type' => 'text',
                        'label' => __('Título de Sección', 'flavor-chat-ia'),
                        'default' => 'Nuestros Casos de Éxito'
                    ],
                    'descripcion_seccion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción de Sección', 'flavor-chat-ia'),
                        'default' => 'Proyectos que transformaron negocios'
                    ],
                    'layout' => [
                        'type' => 'select',
                        'label' => __('Layout', 'flavor-chat-ia'),
                        'options' => ['grid', 'masonry', 'carousel'],
                        'default' => 'masonry'
                    ],
                    'columnas' => [
                        'type' => 'select',
                        'label' => __('Columnas', 'flavor-chat-ia'),
                        'options' => ['2', '3', '4'],
                        'default' => '3'
                    ],
                    'mostrar_filtros' => [
                        'type' => 'toggle',
                        'label' => __('Mostrar Filtros por Categoría', 'flavor-chat-ia'),
                        'default' => true
                    ],
                    'fuente_datos' => [
                        'type' => 'data_source',
                        'label' => __('Fuente de datos', 'flavor-chat-ia'),
                        'post_types' => ['post'],
                        'items_field' => 'items',
                        'default' => 'manual',
                    ],
                    'items' => [
                        'type' => 'repeater',
                        'label' => __('Proyectos', 'flavor-chat-ia'),
                        'fields' => [
                            'titulo' => ['type' => 'text', 'label' => __('Título', 'flavor-chat-ia'), 'default' => ''],
                            'cliente' => ['type' => 'text', 'label' => __('Cliente', 'flavor-chat-ia'), 'default' => ''],
                            'categoria' => ['type' => 'text', 'label' => __('Categoría', 'flavor-chat-ia'), 'default' => ''],
                            'descripcion' => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia'), 'default' => ''],
                            'resultados' => ['type' => 'text', 'label' => __('Resultados destacados', 'flavor-chat-ia'), 'default' => ''],
                            'imagen' => ['type' => 'image', 'label' => __('Imagen', 'flavor-chat-ia'), 'default' => ''],
                        ],
                        'default' => [],
                        'max_items' => 12,
                    ],
                ],
                'template' => 'empresarial/portfolio'
            ]
        ];
    }

    /**
     * Inicializar el módulo
     */
    public function init() {
        add_action('init', [$this, 'maybe_create_tables']);
        add_action('wp_ajax_empresarial_contacto', [$this, 'ajax_contacto_form']);
        add_action('wp_ajax_nopriv_empresarial_contacto', [$this, 'ajax_contacto_form']);
    }

    // =========================================================================
    // TABLAS DE BASE DE DATOS
    // =========================================================================

    /**
     * Crea las tablas si no existen
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_contactos)) {
            $this->create_tables();
        }
    }

    /**
     * Crea las tablas necesarias para el módulo empresarial
     */
    private function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $sql_contactos = "CREATE TABLE IF NOT EXISTS $tabla_contactos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(200) NOT NULL,
            email varchar(200) NOT NULL,
            telefono varchar(50) DEFAULT NULL,
            empresa varchar(200) DEFAULT NULL,
            asunto varchar(255) DEFAULT NULL,
            mensaje text NOT NULL,
            origen varchar(100) DEFAULT 'web',
            estado enum('nuevo','leido','respondido','archivado') DEFAULT 'nuevo',
            asignado_a bigint(20) unsigned DEFAULT NULL,
            notas_internas text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY email (email),
            KEY created_at (created_at),
            KEY asignado_a (asignado_a)
        ) $charset_collate;";

        $sql_proyectos = "CREATE TABLE IF NOT EXISTS $tabla_proyectos (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            cliente_nombre varchar(200) NOT NULL,
            cliente_email varchar(200) DEFAULT NULL,
            titulo varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            estado enum('propuesta','aprobado','en_curso','completado','cancelado') DEFAULT 'propuesta',
            presupuesto decimal(12,2) DEFAULT 0.00,
            fecha_inicio date DEFAULT NULL,
            fecha_entrega date DEFAULT NULL,
            responsable_id bigint(20) unsigned DEFAULT NULL,
            progreso int(11) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY estado (estado),
            KEY cliente_email (cliente_email),
            KEY responsable_id (responsable_id),
            KEY fecha_entrega (fecha_entrega)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_contactos);
        dbDelta($sql_proyectos);
    }

    // =========================================================================
    // AJAX - FORMULARIO DE CONTACTO FRONTEND
    // =========================================================================

    /**
     * Procesa el envío del formulario de contacto desde el frontend
     */
    public function ajax_contacto_form() {
        // Verificar nonce de seguridad
        if (!check_ajax_referer('empresarial_contacto_nonce', 'nonce', false)) {
            wp_send_json_error([
                'message' => __('Error de seguridad. Recarga la página e intenta de nuevo.', 'flavor-chat-ia'),
            ]);
        }

        // Sanitizar datos del formulario
        $nombre_contacto  = sanitize_text_field(wp_unslash($_POST['nombre'] ?? ''));
        $email_contacto   = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $telefono_contacto = sanitize_text_field(wp_unslash($_POST['telefono'] ?? ''));
        $empresa_contacto = sanitize_text_field(wp_unslash($_POST['empresa'] ?? ''));
        $asunto_contacto  = sanitize_text_field(wp_unslash($_POST['asunto'] ?? ''));
        $mensaje_contacto = sanitize_textarea_field(wp_unslash($_POST['mensaje'] ?? ''));
        $origen_contacto  = sanitize_text_field(wp_unslash($_POST['origen'] ?? 'web'));

        // Validar campos obligatorios
        if (empty($nombre_contacto) || empty($email_contacto) || empty($mensaje_contacto)) {
            wp_send_json_error([
                'message' => __('Por favor, completa los campos obligatorios: nombre, email y mensaje.', 'flavor-chat-ia'),
            ]);
        }

        if (!is_email($email_contacto)) {
            wp_send_json_error([
                'message' => __('El email proporcionado no es válido.', 'flavor-chat-ia'),
            ]);
        }

        // Validar origen permitido
        $origenes_permitidos = ['web', 'landing', 'popup'];
        if (!in_array($origen_contacto, $origenes_permitidos, true)) {
            $origen_contacto = 'web';
        }

        // Insertar en base de datos
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $resultado_insercion = $wpdb->insert(
            $tabla_contactos,
            [
                'nombre'     => $nombre_contacto,
                'email'      => $email_contacto,
                'telefono'   => $telefono_contacto,
                'empresa'    => $empresa_contacto,
                'asunto'     => $asunto_contacto,
                'mensaje'    => $mensaje_contacto,
                'origen'     => $origen_contacto,
                'estado'     => 'nuevo',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            wp_send_json_error([
                'message' => __('Error al guardar el mensaje. Inténtalo de nuevo más tarde.', 'flavor-chat-ia'),
            ]);
        }

        // Notificar por email al administrador
        $email_admin = get_option('admin_email');
        $asunto_email = sprintf(
            __('[%s] Nuevo contacto empresarial: %s', 'flavor-chat-ia'),
            get_bloginfo('name'),
            $asunto_contacto ?: __('Sin asunto', 'flavor-chat-ia')
        );
        $cuerpo_email = sprintf(
            __("Nuevo mensaje de contacto recibido:\n\nNombre: %s\nEmail: %s\nTeléfono: %s\nEmpresa: %s\nAsunto: %s\n\nMensaje:\n%s\n\nOrigen: %s", 'flavor-chat-ia'),
            $nombre_contacto,
            $email_contacto,
            $telefono_contacto ?: '-',
            $empresa_contacto ?: '-',
            $asunto_contacto ?: '-',
            $mensaje_contacto,
            $origen_contacto
        );
        wp_mail($email_admin, $asunto_email, $cuerpo_email);

        wp_send_json_success([
            'message'    => __('Mensaje enviado correctamente. Te responderemos lo antes posible.', 'flavor-chat-ia'),
            'contacto_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * Obtener acciones del módulo
     */
    public function get_actions() {
        return [
            'listar_contactos' => [
                'description' => 'Listar los mensajes de contacto recibidos',
                'params' => ['estado', 'limite', 'pagina'],
            ],
            'ver_contacto' => [
                'description' => 'Ver detalle de un mensaje de contacto',
                'params' => ['contacto_id'],
            ],
            'responder_contacto' => [
                'description' => 'Marcar un contacto como respondido y añadir notas',
                'params' => ['contacto_id', 'notas'],
            ],
            'crear_proyecto' => [
                'description' => 'Crear un nuevo proyecto empresarial',
                'params' => ['titulo', 'cliente_nombre', 'cliente_email', 'descripcion', 'presupuesto', 'fecha_inicio', 'fecha_entrega'],
            ],
            'listar_proyectos' => [
                'description' => 'Listar proyectos empresariales',
                'params' => ['estado', 'limite', 'pagina'],
            ],
            'ver_proyecto' => [
                'description' => 'Ver detalle de un proyecto',
                'params' => ['proyecto_id'],
            ],
            'actualizar_proyecto' => [
                'description' => 'Actualizar estado o progreso de un proyecto',
                'params' => ['proyecto_id', 'estado', 'progreso', 'descripcion'],
            ],
            'estadisticas' => [
                'description' => 'Obtener estadísticas del panel empresarial',
                'params' => [],
            ],
            'buscar' => [
                'description' => 'Buscar en contactos y proyectos',
                'params' => ['termino', 'tipo', 'limite'],
            ],
        ];
    }

    /**
     * Ejecutar acción del módulo
     */
    public function execute_action($action, $data = []) {
        $nombre_metodo_accion = 'action_' . $action;

        if (method_exists($this, $nombre_metodo_accion)) {
            return $this->$nombre_metodo_accion($data);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-chat-ia'), $action),
        ];
    }

    // =========================================================================
    // IMPLEMENTACIONES DE ACCIONES
    // =========================================================================

    /**
     * Acción: Listar contactos recibidos
     */
    private function action_listar_contactos($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $estado_filtro     = sanitize_text_field($parametros['estado'] ?? '');
        $limite_resultados = absint($parametros['limite'] ?? 20);
        $pagina_actual     = max(1, absint($parametros['pagina'] ?? 1));
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $condiciones_where = '1=1';
        $valores_parametros = [];

        if (!empty($estado_filtro)) {
            $estados_validos = ['nuevo', 'leido', 'respondido', 'archivado'];
            if (in_array($estado_filtro, $estados_validos, true)) {
                $condiciones_where .= ' AND estado = %s';
                $valores_parametros[] = $estado_filtro;
            }
        }

        // Contar total
        $consulta_total = "SELECT COUNT(*) FROM $tabla_contactos WHERE $condiciones_where";
        if (!empty($valores_parametros)) {
            $total_contactos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
        } else {
            $total_contactos = (int) $wpdb->get_var($consulta_total);
        }

        // Obtener resultados
        $consulta_contactos = "SELECT id, nombre, email, empresa, asunto, origen, estado, created_at
                               FROM $tabla_contactos
                               WHERE $condiciones_where
                               ORDER BY created_at DESC
                               LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $lista_contactos = $wpdb->get_results($wpdb->prepare($consulta_contactos, $parametros_completos), ARRAY_A);

        return [
            'success'    => true,
            'total'      => $total_contactos,
            'pagina'     => $pagina_actual,
            'por_pagina' => $limite_resultados,
            'contactos'  => $lista_contactos ?: [],
            'mensaje'    => sprintf(__('Se encontraron %d contactos.', 'flavor-chat-ia'), $total_contactos),
        ];
    }

    /**
     * Acción: Ver detalle de un contacto
     */
    private function action_ver_contacto($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $contacto_id = absint($parametros['contacto_id'] ?? 0);
        if (!$contacto_id) {
            return [
                'success' => false,
                'error'   => __('ID de contacto no válido.', 'flavor-chat-ia'),
            ];
        }

        $detalle_contacto = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_contactos WHERE id = %d", $contacto_id),
            ARRAY_A
        );

        if (!$detalle_contacto) {
            return [
                'success' => false,
                'error'   => __('Contacto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Marcar como leído si estaba como nuevo
        if ($detalle_contacto['estado'] === 'nuevo') {
            $wpdb->update(
                $tabla_contactos,
                ['estado' => 'leido', 'updated_at' => current_time('mysql')],
                ['id' => $contacto_id],
                ['%s', '%s'],
                ['%d']
            );
            $detalle_contacto['estado'] = 'leido';
        }

        // Información del usuario asignado
        $nombre_asignado = '';
        if (!empty($detalle_contacto['asignado_a'])) {
            $usuario_asignado = get_userdata($detalle_contacto['asignado_a']);
            if ($usuario_asignado) {
                $nombre_asignado = $usuario_asignado->display_name;
            }
        }
        $detalle_contacto['nombre_asignado'] = $nombre_asignado;

        return [
            'success'  => true,
            'contacto' => $detalle_contacto,
        ];
    }

    /**
     * Acción: Marcar contacto como respondido
     */
    private function action_responder_contacto($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';

        $contacto_id    = absint($parametros['contacto_id'] ?? 0);
        $notas_respuesta = sanitize_textarea_field($parametros['notas'] ?? '');

        if (!$contacto_id) {
            return [
                'success' => false,
                'error'   => __('ID de contacto no válido.', 'flavor-chat-ia'),
            ];
        }

        $contacto_existente = $wpdb->get_row(
            $wpdb->prepare("SELECT id, estado FROM $tabla_contactos WHERE id = %d", $contacto_id),
            ARRAY_A
        );

        if (!$contacto_existente) {
            return [
                'success' => false,
                'error'   => __('Contacto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizacion = [
            'estado'     => 'respondido',
            'updated_at' => current_time('mysql'),
        ];
        $formatos_actualizacion = ['%s', '%s'];

        if (!empty($notas_respuesta)) {
            $datos_actualizacion['notas_internas'] = $notas_respuesta;
            $formatos_actualizacion[] = '%s';
        }

        // Asignar al usuario actual si no tiene asignado
        if (is_user_logged_in()) {
            $datos_actualizacion['asignado_a'] = get_current_user_id();
            $formatos_actualizacion[] = '%d';
        }

        $wpdb->update(
            $tabla_contactos,
            $datos_actualizacion,
            ['id' => $contacto_id],
            $formatos_actualizacion,
            ['%d']
        );

        return [
            'success' => true,
            'mensaje' => __('Contacto marcado como respondido.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Crear un nuevo proyecto
     */
    private function action_crear_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $titulo_proyecto       = sanitize_text_field($parametros['titulo'] ?? '');
        $nombre_cliente        = sanitize_text_field($parametros['cliente_nombre'] ?? '');
        $email_cliente         = sanitize_email($parametros['cliente_email'] ?? '');
        $descripcion_proyecto  = sanitize_textarea_field($parametros['descripcion'] ?? '');
        $presupuesto_proyecto  = floatval($parametros['presupuesto'] ?? 0);
        $fecha_inicio_proyecto = sanitize_text_field($parametros['fecha_inicio'] ?? '');
        $fecha_entrega_proyecto = sanitize_text_field($parametros['fecha_entrega'] ?? '');

        // Validaciones
        if (empty($titulo_proyecto)) {
            return [
                'success' => false,
                'error'   => __('El título del proyecto es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        if (empty($nombre_cliente)) {
            return [
                'success' => false,
                'error'   => __('El nombre del cliente es obligatorio.', 'flavor-chat-ia'),
            ];
        }

        // Validar formato de fechas
        $fecha_inicio_valida  = null;
        $fecha_entrega_valida = null;

        if (!empty($fecha_inicio_proyecto)) {
            $fecha_inicio_parseada = date_create($fecha_inicio_proyecto);
            if ($fecha_inicio_parseada) {
                $fecha_inicio_valida = date_format($fecha_inicio_parseada, 'Y-m-d');
            }
        }

        if (!empty($fecha_entrega_proyecto)) {
            $fecha_entrega_parseada = date_create($fecha_entrega_proyecto);
            if ($fecha_entrega_parseada) {
                $fecha_entrega_valida = date_format($fecha_entrega_parseada, 'Y-m-d');
            }
        }

        $identificador_responsable = is_user_logged_in() ? get_current_user_id() : null;

        $resultado_insercion = $wpdb->insert(
            $tabla_proyectos,
            [
                'titulo'         => $titulo_proyecto,
                'cliente_nombre' => $nombre_cliente,
                'cliente_email'  => $email_cliente,
                'descripcion'    => $descripcion_proyecto,
                'estado'         => 'propuesta',
                'presupuesto'    => $presupuesto_proyecto,
                'fecha_inicio'   => $fecha_inicio_valida,
                'fecha_entrega'  => $fecha_entrega_valida,
                'responsable_id' => $identificador_responsable,
                'progreso'       => 0,
                'created_at'     => current_time('mysql'),
                'updated_at'     => current_time('mysql'),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($resultado_insercion === false) {
            return [
                'success' => false,
                'error'   => __('Error al crear el proyecto. Inténtalo de nuevo.', 'flavor-chat-ia'),
            ];
        }

        return [
            'success'     => true,
            'proyecto_id' => $wpdb->insert_id,
            'mensaje'     => sprintf(__('Proyecto "%s" creado correctamente.', 'flavor-chat-ia'), $titulo_proyecto),
        ];
    }

    /**
     * Acción: Listar proyectos
     */
    private function action_listar_proyectos($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $estado_filtro     = sanitize_text_field($parametros['estado'] ?? '');
        $limite_resultados = absint($parametros['limite'] ?? 20);
        $pagina_actual     = max(1, absint($parametros['pagina'] ?? 1));
        $offset_consulta   = ($pagina_actual - 1) * $limite_resultados;

        $condiciones_where = '1=1';
        $valores_parametros = [];

        if (!empty($estado_filtro)) {
            $estados_validos_proyecto = ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'];
            if (in_array($estado_filtro, $estados_validos_proyecto, true)) {
                $condiciones_where .= ' AND estado = %s';
                $valores_parametros[] = $estado_filtro;
            }
        }

        // Contar total
        $consulta_total = "SELECT COUNT(*) FROM $tabla_proyectos WHERE $condiciones_where";
        if (!empty($valores_parametros)) {
            $total_proyectos = (int) $wpdb->get_var($wpdb->prepare($consulta_total, $valores_parametros));
        } else {
            $total_proyectos = (int) $wpdb->get_var($consulta_total);
        }

        // Obtener resultados
        $consulta_proyectos = "SELECT id, titulo, cliente_nombre, estado, presupuesto, progreso, fecha_inicio, fecha_entrega, created_at
                               FROM $tabla_proyectos
                               WHERE $condiciones_where
                               ORDER BY created_at DESC
                               LIMIT %d OFFSET %d";

        $parametros_completos = array_merge($valores_parametros, [$limite_resultados, $offset_consulta]);
        $lista_proyectos = $wpdb->get_results($wpdb->prepare($consulta_proyectos, $parametros_completos), ARRAY_A);

        return [
            'success'    => true,
            'total'      => $total_proyectos,
            'pagina'     => $pagina_actual,
            'por_pagina' => $limite_resultados,
            'proyectos'  => $lista_proyectos ?: [],
            'mensaje'    => sprintf(__('Se encontraron %d proyectos.', 'flavor-chat-ia'), $total_proyectos),
        ];
    }

    /**
     * Acción: Ver detalle de un proyecto
     */
    private function action_ver_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $proyecto_id = absint($parametros['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            return [
                'success' => false,
                'error'   => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ];
        }

        $detalle_proyecto = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM $tabla_proyectos WHERE id = %d", $proyecto_id),
            ARRAY_A
        );

        if (!$detalle_proyecto) {
            return [
                'success' => false,
                'error'   => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        // Información del responsable
        $nombre_responsable = '';
        if (!empty($detalle_proyecto['responsable_id'])) {
            $usuario_responsable = get_userdata($detalle_proyecto['responsable_id']);
            if ($usuario_responsable) {
                $nombre_responsable = $usuario_responsable->display_name;
            }
        }
        $detalle_proyecto['nombre_responsable'] = $nombre_responsable;

        // Calcular días restantes hasta entrega
        $dias_restantes_entrega = null;
        if (!empty($detalle_proyecto['fecha_entrega'])) {
            $fecha_entrega_obj = date_create($detalle_proyecto['fecha_entrega']);
            $fecha_hoy_obj     = date_create(current_time('Y-m-d'));
            if ($fecha_entrega_obj && $fecha_hoy_obj) {
                $diferencia_fechas = date_diff($fecha_hoy_obj, $fecha_entrega_obj);
                $dias_restantes_entrega = (int) $diferencia_fechas->format('%R%a');
            }
        }
        $detalle_proyecto['dias_restantes'] = $dias_restantes_entrega;

        return [
            'success'  => true,
            'proyecto' => $detalle_proyecto,
        ];
    }

    /**
     * Acción: Actualizar estado o progreso de un proyecto
     */
    private function action_actualizar_proyecto($parametros) {
        global $wpdb;
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        $proyecto_id = absint($parametros['proyecto_id'] ?? 0);
        if (!$proyecto_id) {
            return [
                'success' => false,
                'error'   => __('ID de proyecto no válido.', 'flavor-chat-ia'),
            ];
        }

        $proyecto_existente = $wpdb->get_row(
            $wpdb->prepare("SELECT id, estado FROM $tabla_proyectos WHERE id = %d", $proyecto_id),
            ARRAY_A
        );

        if (!$proyecto_existente) {
            return [
                'success' => false,
                'error'   => __('Proyecto no encontrado.', 'flavor-chat-ia'),
            ];
        }

        $datos_actualizacion = [
            'updated_at' => current_time('mysql'),
        ];
        $formatos_actualizacion = ['%s'];

        // Actualizar estado si se proporciona
        $nuevo_estado = sanitize_text_field($parametros['estado'] ?? '');
        if (!empty($nuevo_estado)) {
            $estados_validos_proyecto = ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'];
            if (in_array($nuevo_estado, $estados_validos_proyecto, true)) {
                $datos_actualizacion['estado'] = $nuevo_estado;
                $formatos_actualizacion[] = '%s';

                // Si se completa, poner progreso al 100%
                if ($nuevo_estado === 'completado') {
                    $datos_actualizacion['progreso'] = 100;
                    $formatos_actualizacion[] = '%d';
                }
            }
        }

        // Actualizar progreso si se proporciona
        if (isset($parametros['progreso'])) {
            $nuevo_progreso = min(100, max(0, absint($parametros['progreso'])));
            $datos_actualizacion['progreso'] = $nuevo_progreso;
            $formatos_actualizacion[] = '%d';
        }

        // Actualizar descripción si se proporciona
        $nueva_descripcion = sanitize_textarea_field($parametros['descripcion'] ?? '');
        if (!empty($nueva_descripcion)) {
            $datos_actualizacion['descripcion'] = $nueva_descripcion;
            $formatos_actualizacion[] = '%s';
        }

        $wpdb->update(
            $tabla_proyectos,
            $datos_actualizacion,
            ['id' => $proyecto_id],
            $formatos_actualizacion,
            ['%d']
        );

        return [
            'success' => true,
            'mensaje' => __('Proyecto actualizado correctamente.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Obtener estadísticas del panel empresarial
     */
    private function action_estadisticas($parametros) {
        global $wpdb;
        $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
        $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';

        // -- Estadísticas de contactos --
        $total_contactos           = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos");
        $contactos_nuevos          = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado = 'nuevo'");
        $contactos_pendientes      = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado IN ('nuevo', 'leido')");
        $contactos_respondidos     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_contactos WHERE estado = 'respondido'");

        // Contactos de los últimos 30 días
        $fecha_hace_30_dias = date('Y-m-d H:i:s', strtotime('-30 days'));
        $contactos_ultimo_mes = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_contactos WHERE created_at >= %s", $fecha_hace_30_dias)
        );

        // Contactos de los últimos 7 días
        $fecha_hace_7_dias = date('Y-m-d H:i:s', strtotime('-7 days'));
        $contactos_ultima_semana = (int) $wpdb->get_var(
            $wpdb->prepare("SELECT COUNT(*) FROM $tabla_contactos WHERE created_at >= %s", $fecha_hace_7_dias)
        );

        // -- Estadísticas de proyectos --
        $total_proyectos       = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos");
        $proyectos_activos     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");
        $proyectos_completados = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'completado'");
        $proyectos_propuesta   = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'propuesta'");
        $proyectos_cancelados  = (int) $wpdb->get_var("SELECT COUNT(*) FROM $tabla_proyectos WHERE estado = 'cancelado'");

        // Presupuesto total y por estado
        $presupuesto_total = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos");
        $presupuesto_activo = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");
        $presupuesto_completado = (float) $wpdb->get_var("SELECT COALESCE(SUM(presupuesto), 0) FROM $tabla_proyectos WHERE estado = 'completado'");

        // Progreso promedio de proyectos activos
        $progreso_promedio = (float) $wpdb->get_var("SELECT COALESCE(AVG(progreso), 0) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso')");

        // Proyectos con fecha de entrega vencida
        $proyectos_vencidos = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla_proyectos WHERE estado IN ('aprobado', 'en_curso') AND fecha_entrega IS NOT NULL AND fecha_entrega < %s",
                current_time('Y-m-d')
            )
        );

        // Orígenes de contactos (distribución)
        $distribucion_origenes = $wpdb->get_results(
            "SELECT origen, COUNT(*) as cantidad FROM $tabla_contactos GROUP BY origen ORDER BY cantidad DESC",
            ARRAY_A
        );

        return [
            'success' => true,
            'estadisticas' => [
                'contactos' => [
                    'total'          => $total_contactos,
                    'nuevos'         => $contactos_nuevos,
                    'pendientes'     => $contactos_pendientes,
                    'respondidos'    => $contactos_respondidos,
                    'ultimo_mes'     => $contactos_ultimo_mes,
                    'ultima_semana'  => $contactos_ultima_semana,
                    'origenes'       => $distribucion_origenes ?: [],
                ],
                'proyectos' => [
                    'total'              => $total_proyectos,
                    'activos'            => $proyectos_activos,
                    'completados'        => $proyectos_completados,
                    'propuestas'         => $proyectos_propuesta,
                    'cancelados'         => $proyectos_cancelados,
                    'vencidos'           => $proyectos_vencidos,
                    'progreso_promedio'  => round($progreso_promedio, 1),
                ],
                'financiero' => [
                    'presupuesto_total'      => $presupuesto_total,
                    'presupuesto_activo'     => $presupuesto_activo,
                    'presupuesto_completado' => $presupuesto_completado,
                    'presupuesto_total_fmt'      => $this->format_price($presupuesto_total),
                    'presupuesto_activo_fmt'     => $this->format_price($presupuesto_activo),
                    'presupuesto_completado_fmt' => $this->format_price($presupuesto_completado),
                ],
            ],
            'mensaje' => __('Estadísticas del módulo empresarial.', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: Buscar en contactos y proyectos
     */
    private function action_buscar($parametros) {
        global $wpdb;

        $termino_busqueda  = sanitize_text_field($parametros['termino'] ?? '');
        $tipo_busqueda     = sanitize_text_field($parametros['tipo'] ?? 'todos');
        $limite_resultados = absint($parametros['limite'] ?? 10);

        if (empty($termino_busqueda)) {
            return [
                'success' => false,
                'error'   => __('Debes proporcionar un término de búsqueda.', 'flavor-chat-ia'),
            ];
        }

        $patron_busqueda    = '%' . $wpdb->esc_like($termino_busqueda) . '%';
        $contactos_encontrados = [];
        $proyectos_encontrados = [];

        // Buscar en contactos
        if (in_array($tipo_busqueda, ['todos', 'contactos'], true)) {
            $tabla_contactos = $wpdb->prefix . 'flavor_empresarial_contactos';
            $contactos_encontrados = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, nombre, email, empresa, asunto, estado, created_at
                     FROM $tabla_contactos
                     WHERE nombre LIKE %s OR email LIKE %s OR empresa LIKE %s OR asunto LIKE %s OR mensaje LIKE %s
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda,
                    $limite_resultados
                ),
                ARRAY_A
            ) ?: [];
        }

        // Buscar en proyectos
        if (in_array($tipo_busqueda, ['todos', 'proyectos'], true)) {
            $tabla_proyectos = $wpdb->prefix . 'flavor_empresarial_proyectos';
            $proyectos_encontrados = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, titulo, cliente_nombre, cliente_email, estado, presupuesto, progreso, created_at
                     FROM $tabla_proyectos
                     WHERE titulo LIKE %s OR cliente_nombre LIKE %s OR cliente_email LIKE %s OR descripcion LIKE %s
                     ORDER BY created_at DESC
                     LIMIT %d",
                    $patron_busqueda, $patron_busqueda, $patron_busqueda, $patron_busqueda,
                    $limite_resultados
                ),
                ARRAY_A
            ) ?: [];
        }

        $total_resultados = count($contactos_encontrados) + count($proyectos_encontrados);

        return [
            'success'    => true,
            'termino'    => $termino_busqueda,
            'total'      => $total_resultados,
            'contactos'  => $contactos_encontrados,
            'proyectos'  => $proyectos_encontrados,
            'mensaje'    => sprintf(
                __('Se encontraron %d resultados para "%s".', 'flavor-chat-ia'),
                $total_resultados,
                $termino_busqueda
            ),
        ];
    }

    /**
     * Obtener configuración REST API
     */
    public function get_rest_config() {
        return [
            'enabled' => true,
        ];
    }

    /**
     * Obtener settings del módulo
     */
    public function get_module_settings() {
        return [];
    }

    /**
     * Obtener definiciones de herramientas para IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'empresarial_contactos',
                'description' => 'Lista y busca los mensajes de contacto empresariales recibidos. Permite filtrar por estado (nuevo, leido, respondido, archivado) y buscar por nombre, email, empresa o asunto.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'accion' => [
                            'type' => 'string',
                            'description' => 'Acción a realizar: listar, ver, responder, buscar',
                            'enum' => ['listar', 'ver', 'responder', 'buscar'],
                        ],
                        'contacto_id' => [
                            'type' => 'integer',
                            'description' => 'ID del contacto (para ver o responder)',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Filtrar por estado: nuevo, leido, respondido, archivado',
                            'enum' => ['nuevo', 'leido', 'respondido', 'archivado'],
                        ],
                        'termino' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda para buscar contactos',
                        ],
                        'notas' => [
                            'type' => 'string',
                            'description' => 'Notas internas al responder un contacto',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 20,
                        ],
                        'pagina' => [
                            'type' => 'integer',
                            'description' => 'Página de resultados',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['accion'],
                ],
            ],
            [
                'name' => 'empresarial_proyectos',
                'description' => 'Gestiona proyectos empresariales. Permite crear, listar, ver detalles y actualizar estado/progreso de proyectos con sus clientes, presupuestos y fechas.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'accion' => [
                            'type' => 'string',
                            'description' => 'Acción a realizar: listar, ver, crear, actualizar, buscar',
                            'enum' => ['listar', 'ver', 'crear', 'actualizar', 'buscar'],
                        ],
                        'proyecto_id' => [
                            'type' => 'integer',
                            'description' => 'ID del proyecto (para ver o actualizar)',
                        ],
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título del proyecto (para crear)',
                        ],
                        'cliente_nombre' => [
                            'type' => 'string',
                            'description' => 'Nombre del cliente (para crear)',
                        ],
                        'cliente_email' => [
                            'type' => 'string',
                            'description' => 'Email del cliente (para crear)',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del proyecto',
                        ],
                        'presupuesto' => [
                            'type' => 'number',
                            'description' => 'Presupuesto del proyecto en euros',
                        ],
                        'fecha_inicio' => [
                            'type' => 'string',
                            'description' => 'Fecha de inicio (formato YYYY-MM-DD)',
                        ],
                        'fecha_entrega' => [
                            'type' => 'string',
                            'description' => 'Fecha de entrega (formato YYYY-MM-DD)',
                        ],
                        'estado' => [
                            'type' => 'string',
                            'description' => 'Estado del proyecto: propuesta, aprobado, en_curso, completado, cancelado',
                            'enum' => ['propuesta', 'aprobado', 'en_curso', 'completado', 'cancelado'],
                        ],
                        'progreso' => [
                            'type' => 'integer',
                            'description' => 'Porcentaje de progreso (0-100)',
                        ],
                        'termino' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 20,
                        ],
                        'pagina' => [
                            'type' => 'integer',
                            'description' => 'Página de resultados',
                            'default' => 1,
                        ],
                    ],
                    'required' => ['accion'],
                ],
            ],
            [
                'name' => 'empresarial_estadisticas',
                'description' => 'Obtiene estadísticas del módulo empresarial: total de contactos, contactos pendientes, proyectos activos, presupuestos, progreso medio, etc. Útil para dashboards y resúmenes ejecutivos.',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => new \stdClass(), // Sin parámetros requeridos
                    'required' => [],
                ],
            ],
        ];
    }

    /**
     * Obtener base de conocimiento para IA
     */
    public function get_knowledge_base() {
        return <<<'KNOWLEDGE'
**Modulo Empresarial - Guia de Uso**

Este modulo gestiona la actividad empresarial: contactos comerciales, proyectos y estadisticas de negocio.

**Contactos Comerciales:**
- Los visitantes pueden enviar formularios de contacto desde la web, landings o popups.
- Cada contacto tiene: nombre, email, telefono, empresa, asunto, mensaje, origen y estado.
- Estados de contacto: nuevo (sin leer), leido (visto), respondido (contestado), archivado.
- Se puede asignar un responsable a cada contacto y anadir notas internas.

**Proyectos:**
- Se pueden crear proyectos vinculados a clientes con presupuesto y fechas.
- Estados de proyecto: propuesta, aprobado, en_curso, completado, cancelado.
- Cada proyecto tiene progreso (0-100%), responsable, fecha de inicio y fecha de entrega.
- Al completar un proyecto, el progreso se establece automaticamente al 100%.

**Comandos disponibles:**
- "listar contactos": muestra los mensajes de contacto recibidos
- "ver contacto [ID]": muestra el detalle de un contacto
- "responder contacto [ID]": marca un contacto como respondido
- "crear proyecto": crea un nuevo proyecto empresarial
- "listar proyectos": muestra los proyectos
- "ver proyecto [ID]": muestra el detalle de un proyecto
- "actualizar proyecto [ID]": actualiza estado o progreso
- "estadisticas": muestra el dashboard con metricas de negocio
- "buscar [termino]": busca en contactos y proyectos

**Estadisticas disponibles:**
- Total y desglose de contactos por estado
- Contactos recibidos en la ultima semana y mes
- Distribucion de origenes de contacto
- Proyectos activos, completados y pendientes
- Presupuestos totales, activos y completados
- Progreso promedio de proyectos activos
- Proyectos con fecha de entrega vencida
KNOWLEDGE;
    }
}
