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
            'flavor-platform',
            __( 'Generador de Apps', 'flavor-platform' ),
            __( '🚀 Generador Apps', 'flavor-platform' ),
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
                'analyzing' => __( 'Analizando con IA...', 'flavor-platform' ),
                'generating' => __( 'Generando estructura...', 'flavor-platform' ),
                'success' => __( 'Sitio generado correctamente', 'flavor-platform' ),
                'error' => __( 'Error en la generación', 'flavor-platform' ),
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
                <?php esc_html_e( 'Generador de Apps/Webs', 'flavor-platform' ); ?>
            </h1>

            <p class="description">
                <?php esc_html_e( 'Describe tu proyecto y la IA generará una estructura completa con páginas, módulos y configuración.', 'flavor-platform' ); ?>
            </p>

            <!-- Wizard Steps -->
            <div class="generator-wizard">
                <div class="wizard-steps">
                    <div class="wizard-step active" data-step="1">
                        <span class="step-number">1</span>
                        <span class="step-label"><?php esc_html_e( 'Descripción', 'flavor-platform' ); ?></span>
                    </div>
                    <div class="wizard-step" data-step="2">
                        <span class="step-number">2</span>
                        <span class="step-label"><?php esc_html_e( 'Propuesta', 'flavor-platform' ); ?></span>
                    </div>
                    <div class="wizard-step" data-step="3">
                        <span class="step-number">3</span>
                        <span class="step-label"><?php esc_html_e( 'Generación', 'flavor-platform' ); ?></span>
                    </div>
                </div>

                <!-- Step 1: Descripción -->
                <div class="wizard-content" id="step-1">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Describe tu proyecto', 'flavor-platform' ); ?></h2>

                        <div class="form-field">
                            <label for="proyecto-descripcion">
                                <?php esc_html_e( 'Cuéntanos sobre tu comunidad o proyecto', 'flavor-platform' ); ?>
                            </label>
                            <textarea
                                id="proyecto-descripcion"
                                rows="8"
                                placeholder="<?php esc_attr_e( 'Ejemplo: Somos una asociación vecinal del barrio de San Juan. Necesitamos gestionar nuestros 250 miembros, organizar eventos culturales mensuales, tener un tablón de anuncios para compraventa entre vecinos, y permitir reservas de las salas del local social. También queremos que los vecinos puedan reportar incidencias del barrio y participar en votaciones sobre mejoras.', 'flavor-platform' ); ?>"
                            ></textarea>
                        </div>

                        <div class="quick-templates">
                            <h4><?php esc_html_e( 'O elige un tipo de comunidad:', 'flavor-platform' ); ?></h4>

                            <!-- Comunitario -->
                            <h5 style="margin: 15px 0 8px; color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Comunitario', 'flavor-platform' ); ?></h5>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="vecinal">
                                    <span class="dashicons dashicons-admin-home"></span>
                                    <?php esc_html_e( 'Comunidad Vecinal', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="deportiva">
                                    <span class="dashicons dashicons-awards"></span>
                                    <?php esc_html_e( 'Club Deportivo', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="cultural">
                                    <span class="dashicons dashicons-art"></span>
                                    <?php esc_html_e( 'Asociación Cultural', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="colectivo-social">
                                    <span class="dashicons dashicons-groups"></span>
                                    <?php esc_html_e( 'Colectivo Social', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="ecologica">
                                    <span class="dashicons dashicons-palmtree"></span>
                                    <?php esc_html_e( 'Iniciativa Ecológica', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="cuidados">
                                    <span class="dashicons dashicons-heart"></span>
                                    <?php esc_html_e( 'Red de Cuidados', 'flavor-platform' ); ?>
                                </button>
                            </div>

                            <!-- Empresarial -->
                            <h5 style="margin: 20px 0 8px; color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Empresarial', 'flavor-platform' ); ?></h5>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="empresarial">
                                    <span class="dashicons dashicons-building"></span>
                                    <?php esc_html_e( 'Empresa / PYME', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="startup">
                                    <span class="dashicons dashicons-lightbulb"></span>
                                    <?php esc_html_e( 'Startup', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="coworking">
                                    <span class="dashicons dashicons-networking"></span>
                                    <?php esc_html_e( 'Espacio Coworking', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="consultoria">
                                    <span class="dashicons dashicons-chart-line"></span>
                                    <?php esc_html_e( 'Consultoría', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="finanzas">
                                    <span class="dashicons dashicons-money-alt"></span>
                                    <?php esc_html_e( 'Finanzas / Banca', 'flavor-platform' ); ?>
                                </button>
                            </div>

                            <!-- Educación y Cultura -->
                            <h5 style="margin: 20px 0 8px; color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Educación y Cultura', 'flavor-platform' ); ?></h5>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="educativa">
                                    <span class="dashicons dashicons-welcome-learn-more"></span>
                                    <?php esc_html_e( 'Centro Educativo', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="medios">
                                    <span class="dashicons dashicons-video-alt3"></span>
                                    <?php esc_html_e( 'Medios / Podcast', 'flavor-platform' ); ?>
                                </button>
                            </div>

                            <!-- Consumo y Economía -->
                            <h5 style="margin: 20px 0 8px; color: #666; font-size: 12px; text-transform: uppercase;"><?php esc_html_e( 'Consumo y Economía', 'flavor-platform' ); ?></h5>
                            <div class="template-buttons">
                                <button type="button" class="template-btn" data-template="consumo">
                                    <span class="dashicons dashicons-carrot"></span>
                                    <?php esc_html_e( 'Grupo de Consumo', 'flavor-platform' ); ?>
                                </button>
                                <button type="button" class="template-btn" data-template="gastronomia">
                                    <span class="dashicons dashicons-food"></span>
                                    <?php esc_html_e( 'Gastronomía', 'flavor-platform' ); ?>
                                </button>
                            </div>
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-analyze" class="button button-primary button-hero">
                                <span class="dashicons dashicons-search"></span>
                                <?php esc_html_e( 'Analizar y Proponer', 'flavor-platform' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Propuesta -->
                <div class="wizard-content hidden" id="step-2">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Propuesta de Estructura', 'flavor-platform' ); ?></h2>

                        <div class="propuesta-container">
                            <!-- Se llenará dinámicamente -->
                            <div id="propuesta-content"></div>
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-back-1" class="button">
                                <span class="dashicons dashicons-arrow-left-alt"></span>
                                <?php esc_html_e( 'Volver', 'flavor-platform' ); ?>
                            </button>
                            <button type="button" id="btn-generate" class="button button-primary button-hero">
                                <span class="dashicons dashicons-admin-site"></span>
                                <?php esc_html_e( 'Generar Sitio', 'flavor-platform' ); ?>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Resultado -->
                <div class="wizard-content hidden" id="step-3">
                    <div class="step-card">
                        <h2><?php esc_html_e( 'Sitio Generado', 'flavor-platform' ); ?></h2>

                        <div id="resultado-content">
                            <!-- Se llenará dinámicamente -->
                        </div>

                        <div class="step-actions">
                            <button type="button" id="btn-new" class="button">
                                <span class="dashicons dashicons-plus-alt"></span>
                                <?php esc_html_e( 'Crear otro sitio', 'flavor-platform' ); ?>
                            </button>
                            <a href="<?php echo esc_url( home_url() ); ?>" target="_blank" class="button button-primary">
                                <span class="dashicons dashicons-external"></span>
                                <?php esc_html_e( 'Ver sitio', 'flavor-platform' ); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Loader -->
            <div class="generator-loader hidden">
                <div class="loader-content">
                    <div class="loader-spinner"></div>
                    <p class="loader-text"><?php esc_html_e( 'Procesando...', 'flavor-platform' ); ?></p>
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

        <script type="text/template" id="template-empresarial">
Somos una empresa / PYME con 30 empleados. Necesitamos:
- Control de fichaje y asistencia de empleados
- Gestión de clientes y CRM básico
- Sistema de facturación y presupuestos
- Reserva de salas de reuniones
- Portal del empleado con nóminas y documentos
- Directorio de empleados
- Comunicación interna y anuncios
- Gestión de proyectos y tareas
        </script>

        <script type="text/template" id="template-startup">
Somos una startup tecnológica. Necesitamos:
- Portal para inversores con métricas
- Gestión de equipo y OKRs
- Blog y comunicación externa
- Sistema de tickets y soporte
- Documentación y wiki interna
- Reserva de salas y recursos
- Eventos y demos para clientes
- Tablón de ofertas de trabajo
        </script>

        <script type="text/template" id="template-consultoria">
Somos una consultora de servicios profesionales. Necesitamos:
- Gestión de clientes y proyectos
- Sistema de facturación y horas
- Portal de cliente con acceso a documentos
- Calendario de reuniones y citas
- Base de conocimiento interna
- Comunicación con clientes
- Propuestas y presupuestos
- Fichaje y control horario
        </script>

        <script type="text/template" id="template-finanzas">
Somos una entidad financiera / cooperativa de crédito. Necesitamos:
- Gestión de socios y cuentas
- Portal de banca online para socios
- Sistema de préstamos y créditos
- Transparencia financiera y memorias
- Participación en asambleas
- Comunicación con socios
- Eventos y formación financiera
- Documentación legal y contratos
        </script>

        <script type="text/template" id="template-cuidados">
Somos una red de cuidados y apoyo mutuo. Necesitamos:
- Banco de tiempo para intercambiar servicios
- Círculos de cuidados por zonas
- Coordinación de voluntariado
- Calendario de turnos de cuidado
- Comunicación entre cuidadores
- Recursos y guías de cuidados
- Solicitudes de ayuda
- Red de contactos de emergencia
        </script>

        <script type="text/template" id="template-consumo">
Somos un grupo de consumo responsable. Necesitamos:
- Gestión de socios del grupo
- Catálogo de productos de productores locales
- Sistema de pedidos colectivos
- Calendario de entregas y recogidas
- Comunicación con productores
- Foro de discusión del grupo
- Transparencia de cuentas
- Turnos de voluntariado en el local
        </script>

        <script type="text/template" id="template-gastronomia">
Somos un restaurante / bar cooperativo. Necesitamos:
- Sistema de reservas de mesas
- Carta digital y pedidos
- Gestión de eventos y catering
- Comunicación con clientes
- Programa de fidelización
- Venta de productos locales
- Integración con TPV
- Fichaje de empleados
        </script>

        <script type="text/template" id="template-medios">
Somos un medio de comunicación / podcast. Necesitamos:
- Plataforma de radio/podcast
- Sistema de programación
- Gestión de colaboradores
- Archivo de programas
- Interacción con la audiencia
- Eventos en directo
- Crowdfunding y suscripciones
- Blog y artículos
        </script>
        <?php
    }
}

// Inicializar
Flavor_App_Generator_Admin::get_instance();
