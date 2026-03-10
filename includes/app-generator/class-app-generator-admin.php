<?php
/**
 * Admin UI para el Generador de Apps
 *
 * @package Flavor_Chat_IA
 * @since 3.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Flavor_App_Generator_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 20 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-chat-ia',
            __( 'Generador de Apps', 'flavor-chat-ia' ),
            __( '🚀 Generador Apps', 'flavor-chat-ia' ),
            'manage_options',
            'flavor-app-generator',
            [ $this, 'render_page' ]
        );
    }

    /**
     * Encolar assets
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, 'flavor-app-generator' ) === false ) {
            return;
        }

        wp_enqueue_style(
            'flavor-app-generator',
            FLAVOR_CHAT_IA_URL . 'includes/app-generator/css/app-generator.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-app-generator',
            FLAVOR_CHAT_IA_URL . 'includes/app-generator/js/app-generator.js',
            [ 'jquery' ],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script( 'flavor-app-generator', 'FlavorAppGenerator', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'flavor_app_generator' ),
            'strings' => [
                'analyzing' => __( 'Analizando con IA...', 'flavor-chat-ia' ),
                'generating' => __( 'Generando estructura...', 'flavor-chat-ia' ),
                'success' => __( 'Sitio generado correctamente', 'flavor-chat-ia' ),
                'error' => __( 'Error en la generación', 'flavor-chat-ia' ),
            ],
        ] );
    }

    /**
     * Renderizar página
     */
    public function render_page() {
        $generator = Flavor_App_Generator::get_instance();
        $casos_uso = $generator->get_casos_uso();
        ?>
        <div class="wrap flavor-app-generator">
            <h1>
                <span class="dashicons dashicons-admin-multisite"></span>
                <?php esc_html_e( 'Generador de Apps/Webs', 'flavor-chat-ia' ); ?>
            </h1>

            <p class="description">
                <?php esc_html_e( 'Describe tu proyecto y la IA generará una estructura completa con páginas, módulos y configuración.', 'flavor-chat-ia' ); ?>
            </p>

            <!-- Wizard Steps -->
            <div class="generator-wizard">
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label"><?php esc_html_e( 'Descripción', 'flavor-chat-ia' ); ?></span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label"><?php esc_html_e( 'Propuesta', 'flavor-chat-ia' ); ?></span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label"><?php esc_html_e( 'Generación', 'flavor-chat-ia' ); ?></span>
                    </div>
                </div>

                <!-- Step 1: Descripción -->
                <div class="wizard-content" id="step-1">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Describe tu proyecto', 'flavor-chat-ia' ); ?></h2>

                        <div class="form-field">
                            <label for="proyecto-descripcion">
                                <?php esc_html_e( 'Cuéntanos sobre tu comunidad o proyecto', 'flavor-chat-ia' ); ?>
                            </label>
                            <textarea
                                id="proyecto-descripcion"
                                rows="8"
                                placeholder="<?php esc_attr_e( 'Ejemplo: Somos una asociación vecinal del barrio de San Juan. Necesitamos gestionar nuestros 250 socios, organizar eventos culturales mensuales, tener un tablón de anuncios para compraventa entre vecinos, y permitir reservas de las salas del local social. También queremos que los vecinos puedan reportar incidencias del barrio y participar en votaciones sobre mejoras.', 'flavor-chat-ia' ); ?>"
                            ></textarea>
                        </div>

                        <div class="quick-templates">
                            <h4><?php esc_html_e( 'O elige un tipo de comunidad:', 'flavor-chat-ia' ); ?></h4>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="vecinal">
                                    <span class="dashicons dashicons-admin-home"></span>
                                    <?php esc_html_e( 'Comunidad Vecinal', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="deportiva">
                                    <span class="dashicons dashicons-awards"></span>
                                    <?php esc_html_e( 'Club Deportivo', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="cultural">
                                    <span class="dashicons dashicons-art"></span>
                                    <?php esc_html_e( 'Asociación Cultural', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="coworking">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php esc_html_e( 'Espacio Coworking', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="educativa">
                                    <span class="dashicons dashicons-welcome-learn-more"></span>
                                    <?php esc_html_e( 'Centro Educativo', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="ecologica">
                                    <span class="dashicons dashicons-palmtree"></span>
                                    <?php esc_html_e( 'Iniciativa Ecológica', 'flavor-chat-ia' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="colectivo-social">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php esc_html_e( 'Colectivo Social', 'flavor-chat-ia' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-analyze" class="button button-primary button-hero">
                                <span class="dashicons dashicons-search"></span>
                                <?php esc_html_e( 'Analizar y Proponer', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Propuesta -->
                <div class="wizard-content hidden" id="step-2">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Propuesta de Estructura', 'flavor-chat-ia' ); ?></h2>

                        <div class="propuesta-container">
                            <!-- Se llenará dinámicamente -->
                            <div id="propuesta-content"></div>
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-back-1" class="button">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <?php esc_html_e( 'Volver', 'flavor-chat-ia' ); ?>
                            </button>
                            <button type="button" id="btn-generate" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php esc_html_e( 'Generar Sitio', 'flavor-chat-ia' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Resultado -->
                <div class="wizard-content hidden" id="step-3">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Sitio Generado', 'flavor-chat-ia' ); ?></h2>

                        <div id="resultado-content">
                            <!-- Se llenará dinámicamente -->
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-new" class="button">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e( 'Crear otro sitio', 'flavor-chat-ia' ); ?>
                            </button>
                            <a href="<?php echo esc_url( home_url() ); ?>" target="_blank" class="button button-primary">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e( 'Ver sitio', 'flavor-chat-ia' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loader -->
            <div class="generator-loader hidden">
                <div class="loader-content">
                    <div class="loader-spinner"></div>
                    <p class="loader-text"><?php esc_html_e( 'Procesando...', 'flavor-chat-ia' ); ?></p>
                </div>
            </div>
        </div>

        <!-- Templates ocultos para propuestas rápidas -->
        <script type="text/template" id="template-vecinal">
Somos una comunidad de vecinos con aproximadamente 200 familias. Necesitamos:
- Gestión de socios y cuotas de la comunidad
- Reserva de espacios comunes (salón, piscina, pistas)
- Sistema para reportar incidencias y averías
- Tablón de anuncios y compraventa entre vecinos
- Foro de discusión para temas del barrio
- Participación en votaciones de mejoras
        </script>

        <script type="text/template" id="template-deportiva">
Somos un club deportivo con 150 socios. Necesitamos:
- Gestión de socios con diferentes categorías (juvenil, adulto, senior)
- Calendario de eventos y competiciones
- Reserva de instalaciones (pistas, gimnasio)
- Inscripciones a cursos y entrenamientos
- Comunicación con los socios
- Gestión de equipos y colectivos
        </script>

        <script type="text/template" id="template-cultural">
Somos una asociación cultural. Necesitamos:
- Gestión de socios y voluntarios
- Calendario de eventos y actividades culturales
- Inscripciones a talleres y cursos
- Biblioteca con préstamo de libros
- Foros de discusión temáticos
- Radio o podcast comunitario
        </script>

        <script type="text/template" id="template-coworking">
Gestionamos un espacio de coworking con 50 puestos. Necesitamos:
- Gestión de miembros y membresías
- Reserva de salas de reuniones y puestos
- Calendario de eventos y networking
- Sistema de fichaje y control de accesos
- Directorio de profesionales
- Tablón de ofertas de trabajo
        </script>

        <script type="text/template" id="template-educativa">
Somos un centro de formación. Necesitamos:
- Gestión de alumnos y profesores
- Catálogo de cursos con inscripciones
- Calendario de clases y eventos
- Biblioteca de recursos
- Sistema de reservas de aulas
- Comunicación con familias
        </script>

        <script type="text/template" id="template-ecologica">
Somos una iniciativa de sostenibilidad local. Necesitamos:
- Gestión de huertos urbanos y parcelas
- Sistema de compostaje comunitario
- Préstamo de bicicletas compartidas
- Compartir coche (carpooling)
- Banco de tiempo para intercambiar servicios
- Talleres de sostenibilidad
        </script>

        <script type="text/template" id="template-colectivo-social">
Somos un colectivo social / ONG / movimiento ciudadano. Necesitamos:
- Red social interna para conectar miembros y seguidores
- Foros de debate y discusión por temáticas
- Grupos de trabajo y comisiones
- Encuestas y votaciones para decisiones colectivas
- Calendario de eventos y asambleas
- Repositorio de recursos y documentos compartidos
- Perfiles de miembros con sus habilidades
- Sistema de propuestas y participación
        </script>
        <?php
    }
}

// Inicializar
Flavor_App_Generator_Admin::get_instance();
