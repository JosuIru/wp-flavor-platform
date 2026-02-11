<?php
/**
 * API Documentation - Documentación Swagger/OpenAPI
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_API_Documentation {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Endpoints documentados
     */
    private $endpoints = [];

    /**
     * Schemas
     */
    private $schemas = [];

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init_documentation();
        $this->init_hooks();
    }

    /**
     * Inicializar documentación
     */
    private function init_documentation() {
        // Schemas comunes
        $this->schemas = [
            'Error' => [
                'type' => 'object',
                'properties' => [
                    'code' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                ],
            ],
            'Pagination' => [
                'type' => 'object',
                'properties' => [
                    'total' => ['type' => 'integer'],
                    'pages' => ['type' => 'integer'],
                    'current_page' => ['type' => 'integer'],
                    'per_page' => ['type' => 'integer'],
                ],
            ],
            'Notification' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'user_id' => ['type' => 'integer'],
                    'event_type' => ['type' => 'string'],
                    'title' => ['type' => 'string'],
                    'message' => ['type' => 'string'],
                    'data' => ['type' => 'object'],
                    'priority' => ['type' => 'string', 'enum' => ['low', 'normal', 'high', 'urgent']],
                    'read_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Webhook' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'url' => ['type' => 'string', 'format' => 'uri'],
                    'events' => ['type' => 'array', 'items' => ['type' => 'string']],
                    'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Theme' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'description' => ['type' => 'string'],
                    'variables' => ['type' => 'object', 'additionalProperties' => ['type' => 'string']],
                ],
            ],
            'ModuleConfig' => [
                'type' => 'object',
                'properties' => [
                    'module_id' => ['type' => 'string'],
                    'config' => ['type' => 'object'],
                ],
            ],
            'Conversation' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'session_id' => ['type' => 'string'],
                    'language' => ['type' => 'string'],
                    'message_count' => ['type' => 'integer'],
                    'escalated' => ['type' => 'boolean'],
                    'started_at' => ['type' => 'string', 'format' => 'date-time'],
                    'ended_at' => ['type' => 'string', 'format' => 'date-time', 'nullable' => true],
                ],
            ],
            'Message' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'conversation_id' => ['type' => 'integer'],
                    'role' => ['type' => 'string', 'enum' => ['user', 'assistant', 'system']],
                    'content' => ['type' => 'string'],
                    'tokens_used' => ['type' => 'integer'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
            'Component' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'string'],
                    'name' => ['type' => 'string'],
                    'category' => ['type' => 'string'],
                    'template' => ['type' => 'string'],
                    'fields' => ['type' => 'array', 'items' => ['$ref' => '#/components/schemas/ComponentField']],
                ],
            ],
            'ComponentField' => [
                'type' => 'object',
                'properties' => [
                    'name' => ['type' => 'string'],
                    'type' => ['type' => 'string'],
                    'label' => ['type' => 'string'],
                    'default' => ['type' => 'string'],
                ],
            ],
        ];

        // Endpoints
        $this->endpoints = [
            // Chat
            '/flavor/v1/chat/send' => [
                'post' => [
                    'tags' => ['Chat'],
                    'summary' => 'Enviar mensaje al chat',
                    'description' => 'Envía un mensaje al asistente virtual y recibe una respuesta.',
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['message'],
                                    'properties' => [
                                        'message' => ['type' => 'string', 'description' => 'Mensaje del usuario'],
                                        'session_id' => ['type' => 'string', 'description' => 'ID de sesión (opcional)'],
                                        'language' => ['type' => 'string', 'default' => 'es'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Respuesta del asistente',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'response' => ['type' => 'string'],
                                            'session_id' => ['type' => 'string'],
                                            'tokens_used' => ['type' => 'integer'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Notifications
            '/flavor/v1/notifications' => [
                'get' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Obtener notificaciones del usuario',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 1]],
                        ['name' => 'per_page', 'in' => 'query', 'schema' => ['type' => 'integer', 'default' => 20]],
                        ['name' => 'unread_only', 'in' => 'query', 'schema' => ['type' => 'boolean']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de notificaciones',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'notifications' => [
                                                'type' => 'array',
                                                'items' => ['$ref' => '#/components/schemas/Notification'],
                                            ],
                                            'pagination' => ['$ref' => '#/components/schemas/Pagination'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/flavor/v1/notifications/{id}/read' => [
                'post' => [
                    'tags' => ['Notifications'],
                    'summary' => 'Marcar notificación como leída',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Notificación marcada como leída'],
                    ],
                ],
            ],

            // Webhooks
            '/flavor/v1/webhooks' => [
                'get' => [
                    'tags' => ['Webhooks'],
                    'summary' => 'Listar webhooks',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de webhooks',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'array',
                                        'items' => ['$ref' => '#/components/schemas/Webhook'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Webhooks'],
                    'summary' => 'Crear webhook',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'required' => true,
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'required' => ['name', 'url', 'events'],
                                    'properties' => [
                                        'name' => ['type' => 'string'],
                                        'url' => ['type' => 'string', 'format' => 'uri'],
                                        'events' => ['type' => 'array', 'items' => ['type' => 'string']],
                                        'secret' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '201' => ['description' => 'Webhook creado'],
                    ],
                ],
            ],
            '/flavor/v1/webhook-events' => [
                'get' => [
                    'tags' => ['Webhooks'],
                    'summary' => 'Listar eventos disponibles para webhooks',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de eventos',
                        ],
                    ],
                ],
            ],

            // Themes
            '/flavor/v1/themes' => [
                'get' => [
                    'tags' => ['Themes'],
                    'summary' => 'Listar temas disponibles',
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de temas',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'array',
                                        'items' => ['$ref' => '#/components/schemas/Theme'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            '/flavor/v1/themes/active' => [
                'get' => [
                    'tags' => ['Themes'],
                    'summary' => 'Obtener tema activo',
                    'responses' => [
                        '200' => [
                            'description' => 'Tema activo',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/Theme'],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Themes'],
                    'summary' => 'Establecer tema activo',
                    'security' => [['bearerAuth' => []]],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'theme_id' => ['type' => 'string'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Tema aplicado'],
                    ],
                ],
            ],

            // Module Config
            '/flavor/v1/modules/{module_id}/config' => [
                'get' => [
                    'tags' => ['Modules'],
                    'summary' => 'Obtener configuración del módulo',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'module_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'responses' => [
                        '200' => [
                            'description' => 'Configuración del módulo',
                            'content' => [
                                'application/json' => [
                                    'schema' => ['$ref' => '#/components/schemas/ModuleConfig'],
                                ],
                            ],
                        ],
                    ],
                ],
                'post' => [
                    'tags' => ['Modules'],
                    'summary' => 'Guardar configuración del módulo',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'module_id', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                    ],
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => ['type' => 'object'],
                            ],
                        ],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Configuración guardada'],
                    ],
                ],
            ],
            '/flavor/v1/modules/config-schema' => [
                'get' => [
                    'tags' => ['Modules'],
                    'summary' => 'Obtener schema de configuración de todos los módulos',
                    'security' => [['bearerAuth' => []]],
                    'responses' => [
                        '200' => ['description' => 'Schema de configuración'],
                    ],
                ],
            ],

            // Components
            '/flavor/v1/components' => [
                'get' => [
                    'tags' => ['Page Builder'],
                    'summary' => 'Listar componentes disponibles',
                    'responses' => [
                        '200' => [
                            'description' => 'Lista de componentes',
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'array',
                                        'items' => ['$ref' => '#/components/schemas/Component'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            // Layouts
            '/flavor/v1/layouts' => [
                'get' => [
                    'tags' => ['Page Builder'],
                    'summary' => 'Listar layouts predefinidos',
                    'parameters' => [
                        ['name' => 'type', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['menu', 'footer', 'header']]],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Lista de layouts'],
                    ],
                ],
            ],

            // App Layouts
            '/flavor/v1/app-layouts' => [
                'get' => [
                    'tags' => ['App Integration'],
                    'summary' => 'Obtener layouts para apps móviles',
                    'parameters' => [
                        ['name' => 'profile', 'in' => 'query', 'schema' => ['type' => 'string']],
                        ['name' => 'platform', 'in' => 'query', 'schema' => ['type' => 'string', 'enum' => ['ios', 'android', 'flutter']]],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Layout para la app'],
                    ],
                ],
            ],

            // Analytics
            '/flavor/v1/analytics/conversations' => [
                'get' => [
                    'tags' => ['Analytics'],
                    'summary' => 'Obtener estadísticas de conversaciones',
                    'security' => [['bearerAuth' => []]],
                    'parameters' => [
                        ['name' => 'start_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                        ['name' => 'end_date', 'in' => 'query', 'schema' => ['type' => 'string', 'format' => 'date']],
                    ],
                    'responses' => [
                        '200' => ['description' => 'Estadísticas de conversaciones'],
                    ],
                ],
            ],
        ];

        $this->endpoints = apply_filters('flavor_api_endpoints', $this->endpoints);
        $this->schemas = apply_filters('flavor_api_schemas', $this->schemas);
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('rest_api_init', [$this, 'register_documentation_route']);
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'add_documentation_page']);
    }

    /**
     * Registrar ruta de documentación
     */
    public function register_documentation_route() {
        register_rest_route('flavor/v1', '/docs', [
            'methods' => 'GET',
            'callback' => [$this, 'get_openapi_spec'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);

        register_rest_route('flavor/v1', '/docs/endpoints', [
            'methods' => 'GET',
            'callback' => [$this, 'get_endpoints_list'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Generar especificación OpenAPI
     */
    public function get_openapi_spec($request) {
        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'Flavor Chat IA API',
                'description' => 'API REST para el plugin Flavor Chat IA. Incluye endpoints para chat con IA, notificaciones, webhooks, temas y más.',
                'version' => FLAVOR_CHAT_IA_VERSION,
                'contact' => [
                    'name' => 'Flavor',
                    'url' => 'https://flavor.dev',
                    'email' => 'support@flavor.dev',
                ],
                'license' => [
                    'name' => 'GPL v2 or later',
                    'url' => 'https://www.gnu.org/licenses/gpl-2.0.html',
                ],
            ],
            'servers' => [
                [
                    'url' => rest_url(),
                    'description' => 'Servidor actual',
                ],
            ],
            'tags' => [
                ['name' => 'Chat', 'description' => 'Operaciones del chat con IA'],
                ['name' => 'Notifications', 'description' => 'Sistema de notificaciones'],
                ['name' => 'Webhooks', 'description' => 'Gestión de webhooks'],
                ['name' => 'Themes', 'description' => 'Gestión de temas visuales'],
                ['name' => 'Modules', 'description' => 'Configuración de módulos'],
                ['name' => 'Page Builder', 'description' => 'Componentes y layouts'],
                ['name' => 'App Integration', 'description' => 'Integración con apps móviles'],
                ['name' => 'Analytics', 'description' => 'Estadísticas y analíticas'],
            ],
            'paths' => $this->endpoints,
            'components' => [
                'schemas' => $this->schemas,
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type' => 'http',
                        'scheme' => 'bearer',
                        'bearerFormat' => 'JWT',
                        'description' => 'Autenticación usando Application Passwords de WordPress o JWT',
                    ],
                    'cookieAuth' => [
                        'type' => 'apiKey',
                        'in' => 'cookie',
                        'name' => 'wordpress_logged_in',
                        'description' => 'Cookie de sesión de WordPress',
                    ],
                ],
            ],
        ];

        return rest_ensure_response($spec);
    }

    /**
     * Obtener lista simple de endpoints
     */
    public function get_endpoints_list($request) {
        $endpoints_list = [];

        foreach ($this->endpoints as $path => $methods) {
            foreach ($methods as $method => $details) {
                $endpoints_list[] = [
                    'path' => $path,
                    'method' => strtoupper($method),
                    'summary' => $details['summary'] ?? '',
                    'tags' => $details['tags'] ?? [],
                    'requires_auth' => isset($details['security']),
                ];
            }
        }

        return rest_ensure_response($endpoints_list);
    }

    /**
     * Añadir página de documentación al admin
     */
    public function add_documentation_page() {
        add_submenu_page(
            'flavor-dashboard',
            'API Documentation',
            'API Docs',
            'manage_options',
            'flavor-api-docs',
            [$this, 'render_documentation_page']
        );
    }

    /**
     * Renderizar página de documentación
     */
    public function render_documentation_page() {
        $api_url = rest_url('flavor/v1/docs');
        ?>
        <div class="wrap">
            <h1>Flavor Chat IA - API Documentation</h1>

            <div class="flavor-api-docs-container">
                <p>Documentación interactiva de la API REST del plugin.</p>

                <div class="flavor-api-docs-links">
                    <a href="<?php echo esc_url($api_url); ?>" target="_blank" class="button">
                        Ver especificación OpenAPI (JSON)
                    </a>
                    <a href="<?php echo esc_url(rest_url('flavor/v1/docs/endpoints')); ?>" target="_blank" class="button">
                        Lista de endpoints
                    </a>
                </div>

                <h2>Swagger UI</h2>
                <div id="swagger-ui"></div>
            </div>
        </div>

        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css">
        <script src="https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            SwaggerUIBundle({
                url: "<?php echo esc_url($api_url); ?>",
                dom_id: '#swagger-ui',
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.SwaggerUIStandalonePreset
                ],
                layout: "BaseLayout"
            });
        });
        </script>

        <style>
        .flavor-api-docs-container {
            max-width: 1200px;
            margin-top: 20px;
        }
        .flavor-api-docs-links {
            margin: 20px 0;
            display: flex;
            gap: 10px;
        }
        #swagger-ui {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        </style>
        <?php
    }

    /**
     * Registrar endpoint en la documentación
     *
     * @param string $path
     * @param string $method
     * @param array $details
     */
    public function register_endpoint($path, $method, $details) {
        if (!isset($this->endpoints[$path])) {
            $this->endpoints[$path] = [];
        }
        $this->endpoints[$path][strtolower($method)] = $details;
    }

    /**
     * Registrar schema
     *
     * @param string $name
     * @param array $schema
     */
    public function register_schema($name, $schema) {
        $this->schemas[$name] = $schema;
    }

    /**
     * Obtener endpoints
     */
    public function get_endpoints() {
        return $this->endpoints;
    }

    /**
     * Obtener schemas
     */
    public function get_schemas() {
        return $this->schemas;
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}
