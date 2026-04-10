<?php
/**
 * Documentacion interactiva de la API REST
 *
 * Proporciona una interfaz tipo Swagger UI para explorar y probar
 * los endpoints de la API de Flavor Platform.
 *
 * @package FlavorPlatform\Admin
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la documentacion de la API
 */
class Flavor_API_Docs {

    /**
     * Instancia singleton
     *
     * @var Flavor_API_Docs|null
     */
    private static $instancia = null;

    /**
     * Slug de la pagina
     *
     * @var string
     */
    private $slug_pagina = 'flavor-api-docs';

    /**
     * Endpoints escaneados
     *
     * @var array
     */
    private $endpoints_escaneados = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_API_Docs
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
        // NOTA: El menú se registra centralizadamente en class-admin-menu-manager.php
        // add_action('admin_menu', [$this, 'agregar_menu']);
        add_action('admin_enqueue_scripts', [$this, 'encolar_assets']);
        add_action('wp_ajax_flavor_api_test_endpoint', [$this, 'ajax_probar_endpoint']);
        add_action('rest_api_init', [$this, 'registrar_endpoint_documentacion']);
    }

    /**
     * Agrega el menu en el admin
     */
    public function agregar_menu() {
        add_submenu_page(
            'flavor-platform',
            __('API Documentation', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('API Docs', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            $this->slug_pagina,
            [$this, 'renderizar_pagina']
        );
    }

    /**
     * Registra endpoint para obtener documentacion JSON
     */
    public function registrar_endpoint_documentacion() {
        register_rest_route('flavor/v1', '/docs/endpoints', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_endpoints_json'],
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ]);

        register_rest_route('flavor/v1', '/docs/openapi', [
            'methods' => 'GET',
            'callback' => [$this, 'obtener_openapi_spec'],
            'permission_callback' => [$this, 'public_permission_check'],
        ]);
    }

    /**
     * Obtiene la especificacion OpenAPI
     *
     * @return WP_REST_Response
     */
    public function obtener_openapi_spec() {
        $ruta_archivo = FLAVOR_PLATFORM_PATH . 'docs/api/openapi.yaml';

        if (!file_exists($ruta_archivo)) {
            return new WP_Error('not_found', 'OpenAPI spec not found', ['status' => 404]);
        }

        $contenido_yaml = file_get_contents($ruta_archivo);

        // Reemplazar variables del servidor
        $url_sitio = home_url();
        $contenido_yaml = str_replace('{protocol}://{domain}', $url_sitio, $contenido_yaml);

        return new WP_REST_Response([
            'success' => true,
            'spec' => $contenido_yaml,
        ]);
    }

    /**
     * Encola assets para la pagina de documentacion
     *
     * @param string $hook Hook actual
     */
    public function encolar_assets($hook) {
        if (strpos($hook, $this->slug_pagina) === false) {
            return;
        }

        // Swagger UI desde CDN
        wp_enqueue_style(
            'swagger-ui',
            'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui.css',
            [],
            '5.0.0'
        );

        wp_enqueue_script(
            'swagger-ui-bundle',
            'https://cdn.jsdelivr.net/npm/swagger-ui-dist@5/swagger-ui-bundle.js',
            [],
            '5.0.0',
            true
        );

        // Estilos personalizados
        wp_add_inline_style('swagger-ui', $this->obtener_estilos_personalizados());

        // Script de inicializacion
        wp_add_inline_script('swagger-ui-bundle', $this->obtener_script_inicializacion(), 'after');

        // Localizacion
        wp_localize_script('swagger-ui-bundle', 'flavorApiDocs', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_api_test'),
            'restUrl' => rest_url(),
            'restNonce' => wp_create_nonce('wp_rest'),
            'openApiUrl' => rest_url('flavor/v1/docs/openapi'),
            'i18n' => [
                'probar' => __('Probar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'copiar' => __('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'copiado' => __('Copiado!', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'cargando' => __('Cargando...', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Obtiene estilos personalizados
     *
     * @return string
     */
    private function obtener_estilos_personalizados() {
        return '
            .flavor-api-docs-wrapper {
                max-width: 1400px;
                margin: 20px auto;
                padding: 0 20px;
            }

            .flavor-api-header {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 30px;
                border-radius: 12px;
                margin-bottom: 30px;
            }

            .flavor-api-header h1 {
                margin: 0 0 10px 0;
                font-size: 28px;
                font-weight: 600;
            }

            .flavor-api-header p {
                margin: 0;
                opacity: 0.9;
                font-size: 16px;
            }

            .flavor-api-stats {
                display: flex;
                gap: 30px;
                margin-top: 20px;
            }

            .flavor-api-stat {
                background: rgba(255,255,255,0.15);
                padding: 15px 25px;
                border-radius: 8px;
            }

            .flavor-api-stat-number {
                font-size: 24px;
                font-weight: 700;
            }

            .flavor-api-stat-label {
                font-size: 13px;
                opacity: 0.8;
            }

            .flavor-api-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                border-bottom: 2px solid #e5e7eb;
                padding-bottom: 10px;
            }

            .flavor-api-tab {
                padding: 10px 20px;
                background: #f3f4f6;
                border: none;
                border-radius: 8px 8px 0 0;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #4b5563;
                transition: all 0.2s;
            }

            .flavor-api-tab:hover {
                background: #e5e7eb;
            }

            .flavor-api-tab.active {
                background: #667eea;
                color: white;
            }

            .flavor-api-content {
                background: white;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                overflow: hidden;
            }

            .flavor-endpoints-list {
                padding: 20px;
            }

            .flavor-endpoint-group {
                margin-bottom: 30px;
            }

            .flavor-endpoint-group-title {
                font-size: 18px;
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #e5e7eb;
            }

            .flavor-endpoint-item {
                display: flex;
                align-items: center;
                gap: 15px;
                padding: 15px;
                background: #f9fafb;
                border-radius: 8px;
                margin-bottom: 10px;
                transition: all 0.2s;
            }

            .flavor-endpoint-item:hover {
                background: #f3f4f6;
                transform: translateX(5px);
            }

            .flavor-endpoint-method {
                min-width: 70px;
                padding: 5px 12px;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 700;
                text-align: center;
                text-transform: uppercase;
            }

            .flavor-endpoint-method.get { background: #10b981; color: white; }
            .flavor-endpoint-method.post { background: #3b82f6; color: white; }
            .flavor-endpoint-method.put { background: #f59e0b; color: white; }
            .flavor-endpoint-method.delete { background: #ef4444; color: white; }
            .flavor-endpoint-method.patch { background: #8b5cf6; color: white; }

            .flavor-endpoint-route {
                flex: 1;
                font-family: Monaco, Consolas, monospace;
                font-size: 14px;
                color: #374151;
            }

            .flavor-endpoint-auth {
                font-size: 12px;
                color: #6b7280;
            }

            .flavor-endpoint-auth.required {
                color: #dc2626;
            }

            .flavor-endpoint-auth.public {
                color: #059669;
            }

            .flavor-endpoint-actions {
                display: flex;
                gap: 8px;
            }

            .flavor-endpoint-btn {
                padding: 6px 12px;
                border: none;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s;
            }

            .flavor-endpoint-btn.try {
                background: #667eea;
                color: white;
            }

            .flavor-endpoint-btn.try:hover {
                background: #5a67d8;
            }

            .flavor-endpoint-btn.copy {
                background: #e5e7eb;
                color: #374151;
            }

            .flavor-endpoint-btn.copy:hover {
                background: #d1d5db;
            }

            .swagger-ui .topbar { display: none; }

            .swagger-ui .info { margin: 30px 0; }

            .swagger-ui .opblock-tag {
                font-size: 16px !important;
            }

            .swagger-ui .opblock {
                border-radius: 8px !important;
                margin-bottom: 10px !important;
            }

            .flavor-code-example {
                background: #1f2937;
                color: #e5e7eb;
                padding: 20px;
                border-radius: 8px;
                overflow-x: auto;
                font-family: Monaco, Consolas, monospace;
                font-size: 13px;
                line-height: 1.6;
            }

            .flavor-code-example .comment { color: #6b7280; }
            .flavor-code-example .string { color: #10b981; }
            .flavor-code-example .keyword { color: #f59e0b; }

            .flavor-modal {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 100000;
                align-items: center;
                justify-content: center;
            }

            .flavor-modal.active {
                display: flex;
            }

            .flavor-modal-content {
                background: white;
                border-radius: 12px;
                max-width: 800px;
                width: 90%;
                max-height: 80vh;
                overflow: auto;
                padding: 30px;
            }

            .flavor-modal-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }

            .flavor-modal-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #6b7280;
            }

            .flavor-response-panel {
                background: #1f2937;
                color: #e5e7eb;
                padding: 20px;
                border-radius: 8px;
                margin-top: 20px;
                max-height: 400px;
                overflow: auto;
            }

            .flavor-response-status {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 4px;
                font-size: 12px;
                font-weight: 600;
                margin-bottom: 10px;
            }

            .flavor-response-status.success { background: #10b981; }
            .flavor-response-status.error { background: #ef4444; }

            @media (max-width: 768px) {
                .flavor-api-stats { flex-direction: column; gap: 10px; }
                .flavor-endpoint-item { flex-direction: column; align-items: flex-start; }
                .flavor-endpoint-actions { width: 100%; justify-content: flex-end; }
            }
        ';
    }

    /**
     * Obtiene script de inicializacion
     *
     * @return string
     */
    private function obtener_script_inicializacion() {
        return "
            document.addEventListener('DOMContentLoaded', function() {
                const swaggerContainer = document.getElementById('swagger-ui');
                if (!swaggerContainer) return;

                // Fetch OpenAPI spec
                fetch(flavorApiDocs.openApiUrl, {
                    headers: {
                        'X-WP-Nonce': flavorApiDocs.restNonce
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.spec) {
                        // Parse YAML to JSON
                        const spec = jsyaml.load(data.spec);

                        // Initialize Swagger UI
                        SwaggerUIBundle({
                            spec: spec,
                            dom_id: '#swagger-ui',
                            deepLinking: true,
                            presets: [
                                SwaggerUIBundle.presets.apis,
                                SwaggerUIBundle.SwaggerUIStandalonePreset
                            ],
                            plugins: [
                                SwaggerUIBundle.plugins.DownloadUrl
                            ],
                            layout: 'BaseLayout',
                            docExpansion: 'list',
                            filter: true,
                            requestInterceptor: function(req) {
                                req.headers['X-WP-Nonce'] = flavorApiDocs.restNonce;
                                return req;
                            }
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading OpenAPI spec:', error);
                    swaggerContainer.innerHTML = '<p style=\"padding:20px;color:#ef4444;\">Error cargando la especificacion de la API</p>';
                });

                // Tab switching
                document.querySelectorAll('.flavor-api-tab').forEach(tab => {
                    tab.addEventListener('click', function() {
                        const target = this.dataset.tab;

                        document.querySelectorAll('.flavor-api-tab').forEach(t => t.classList.remove('active'));
                        document.querySelectorAll('.flavor-tab-content').forEach(c => c.style.display = 'none');

                        this.classList.add('active');
                        document.getElementById('tab-' + target).style.display = 'block';
                    });
                });

                // Copy to clipboard
                document.querySelectorAll('.flavor-endpoint-btn.copy').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const route = this.closest('.flavor-endpoint-item').querySelector('.flavor-endpoint-route').textContent;
                        const fullUrl = flavorApiDocs.restUrl.replace('/wp-json/', '') + route;

                        navigator.clipboard.writeText(fullUrl).then(() => {
                            const originalText = this.textContent;
                            this.textContent = flavorApiDocs.i18n.copiado;
                            setTimeout(() => { this.textContent = originalText; }, 2000);
                        });
                    });
                });

                // Try endpoint
                document.querySelectorAll('.flavor-endpoint-btn.try').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const item = this.closest('.flavor-endpoint-item');
                        const method = item.querySelector('.flavor-endpoint-method').textContent.trim();
                        const route = item.querySelector('.flavor-endpoint-route').textContent;

                        if (method.toUpperCase() !== 'GET') {
                            alert('Solo se pueden probar endpoints GET desde aqui. Usa la vista Swagger para otros metodos.');
                            return;
                        }

                        this.textContent = flavorApiDocs.i18n.cargando;
                        this.disabled = true;

                        fetch(flavorApiDocs.restUrl.replace('/wp-json/', '') + route, {
                            headers: {
                                'X-WP-Nonce': flavorApiDocs.restNonce
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            showResponseModal(route, 200, data);
                        })
                        .catch(error => {
                            showResponseModal(route, 500, { error: error.message });
                        })
                        .finally(() => {
                            this.textContent = flavorApiDocs.i18n.probar;
                            this.disabled = false;
                        });
                    });
                });

                function showResponseModal(endpoint, status, data) {
                    const modal = document.getElementById('response-modal');
                    const title = modal.querySelector('.flavor-modal-title');
                    const statusEl = modal.querySelector('.flavor-response-status');
                    const body = modal.querySelector('.flavor-response-body');

                    title.textContent = 'Respuesta: ' + endpoint;
                    statusEl.textContent = 'Status: ' + status;
                    statusEl.className = 'flavor-response-status ' + (status < 400 ? 'success' : 'error');
                    body.textContent = JSON.stringify(data, null, 2);

                    modal.classList.add('active');
                }

                document.querySelectorAll('.flavor-modal-close').forEach(btn => {
                    btn.addEventListener('click', function() {
                        this.closest('.flavor-modal').classList.remove('active');
                    });
                });

                document.querySelectorAll('.flavor-modal').forEach(modal => {
                    modal.addEventListener('click', function(e) {
                        if (e.target === this) {
                            this.classList.remove('active');
                        }
                    });
                });
            });
        ";
    }

    /**
     * Renderiza la pagina de documentacion
     */
    public function renderizar_pagina() {
        if (!current_user_can('manage_options')) {
            wp_die(__('No tienes permisos para acceder a esta pagina.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        $endpoints = $this->escanear_endpoints();
        $estadisticas = $this->calcular_estadisticas($endpoints);
        ?>
        <div class="wrap flavor-api-docs-wrapper">
            <div class="flavor-api-header">
                <h1><?php _e('Flavor Platform API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
                <p><?php _e('Documentacion interactiva de la API REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

                <div class="flavor-api-stats">
                    <div class="flavor-api-stat">
                        <div class="flavor-api-stat-number"><?php echo esc_html($estadisticas['total_endpoints']); ?></div>
                        <div class="flavor-api-stat-label"><?php _e('Endpoints', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flavor-api-stat">
                        <div class="flavor-api-stat-number"><?php echo esc_html($estadisticas['total_namespaces']); ?></div>
                        <div class="flavor-api-stat-label"><?php _e('Namespaces', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flavor-api-stat">
                        <div class="flavor-api-stat-number"><?php echo esc_html($estadisticas['endpoints_publicos']); ?></div>
                        <div class="flavor-api-stat-label"><?php _e('Publicos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                    <div class="flavor-api-stat">
                        <div class="flavor-api-stat-number"><?php echo esc_html($estadisticas['endpoints_autenticados']); ?></div>
                        <div class="flavor-api-stat-label"><?php _e('Autenticados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></div>
                    </div>
                </div>
            </div>

            <div class="flavor-api-tabs">
                <button class="flavor-api-tab active" data-tab="swagger">
                    <?php _e('Swagger UI', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-api-tab" data-tab="endpoints">
                    <?php _e('Lista de Endpoints', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
                <button class="flavor-api-tab" data-tab="examples">
                    <?php _e('Ejemplos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <div class="flavor-api-content">
                <!-- Tab: Swagger UI -->
                <div id="tab-swagger" class="flavor-tab-content">
                    <div id="swagger-ui"></div>
                </div>

                <!-- Tab: Lista de Endpoints -->
                <div id="tab-endpoints" class="flavor-tab-content" style="display:none;">
                    <div class="flavor-endpoints-list">
                        <?php foreach ($endpoints as $namespace => $rutas): ?>
                            <div class="flavor-endpoint-group">
                                <h3 class="flavor-endpoint-group-title">
                                    <?php echo esc_html($namespace); ?>
                                </h3>
                                <?php foreach ($rutas as $ruta): ?>
                                    <div class="flavor-endpoint-item">
                                        <span class="flavor-endpoint-method <?php echo esc_attr(strtolower($ruta['method'])); ?>">
                                            <?php echo esc_html($ruta['method']); ?>
                                        </span>
                                        <span class="flavor-endpoint-route">
                                            <?php echo esc_html($ruta['route']); ?>
                                        </span>
                                        <span class="flavor-endpoint-auth <?php echo $ruta['requires_auth'] ? 'required' : 'public'; ?>">
                                            <?php echo $ruta['requires_auth'] ? __('Requiere auth', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('Publico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                        </span>
                                        <div class="flavor-endpoint-actions">
                                            <?php if (strtoupper($ruta['method']) === 'GET'): ?>
                                                <button class="flavor-endpoint-btn try">
                                                    <?php _e('Probar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                                </button>
                                            <?php endif; ?>
                                            <button class="flavor-endpoint-btn copy">
                                                <?php _e('Copiar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                            </button>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Ejemplos -->
                <div id="tab-examples" class="flavor-tab-content" style="display:none;">
                    <div class="flavor-endpoints-list">
                        <h3><?php _e('Autenticacion con JWT', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example"><span class="comment"># Obtener token</span>
curl -X POST "<?php echo esc_url(home_url('/wp-json/jwt-auth/v1/token')); ?>" \
  -H "Content-Type: application/json" \
  -d '{
    <span class="string">"username"</span>: <span class="string">"tu_usuario"</span>,
    <span class="string">"password"</span>: <span class="string">"tu_contrasena"</span>
  }'

<span class="comment"># Usar token en peticiones</span>
curl "<?php echo esc_url(home_url('/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE . '/mis-pedidos')); ?>" \
  -H "Authorization: Bearer <span class="keyword">TU_TOKEN_JWT</span>"</pre>

                        <h3><?php _e('Listar Pedidos Abiertos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example">curl "<?php echo esc_url(home_url('/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE . '/pedidos?estado=abierto&per_page=20')); ?>"</pre>

                        <h3><?php _e('Unirse a un Pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example">curl -X POST "<?php echo esc_url(home_url('/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE . '/pedidos/123/unirse')); ?>" \
  -H "Authorization: Bearer <span class="keyword">TU_TOKEN</span>" \
  -H "Content-Type: application/json" \
  -d '{
    <span class="string">"cantidad"</span>: 2.5
  }'</pre>

                        <h3><?php _e('Buscar Productores Cercanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example">curl "<?php echo esc_url(home_url('/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE . '/gc/productores-cercanos')); ?>?lat=40.4168&lng=-3.7038&limite=10"</pre>

                        <h3><?php _e('Directorio de la Red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example">curl "<?php echo esc_url(home_url('/wp-json/flavor-network/v1/directory')); ?>?tipo=cooperativa&verificado=true"</pre>

                        <h3><?php _e('JavaScript/Fetch', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example"><span class="keyword">const</span> response = <span class="keyword">await</span> fetch(<span class="string">'<?php echo esc_url(home_url('/wp-json/' . FLAVOR_PLATFORM_REST_NAMESPACE . '/gc/productos')); ?>'</span>, {
    headers: {
        <span class="string">'X-WP-Nonce'</span>: wpApiSettings.nonce
    }
});

<span class="keyword">const</span> data = <span class="keyword">await</span> response.json();
console.log(data);</pre>

                        <h3><?php _e('PHP/WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <pre class="flavor-code-example"><span class="keyword">$response</span> = wp_remote_get(
    rest_url(<span class="string">FLAVOR_PLATFORM_REST_NAMESPACE . '/pedidos'</span>),
    [
        <span class="string">'headers'</span> => [
            <span class="string">'Authorization'</span> => <span class="string">'Bearer '</span> . <span class="keyword">$token</span>
        ]
    ]
);

<span class="keyword">$body</span> = json_decode(wp_remote_retrieve_body(<span class="keyword">$response</span>), true);</pre>
                    </div>
                </div>
            </div>

            <!-- Modal de respuesta -->
            <div id="response-modal" class="flavor-modal">
                <div class="flavor-modal-content">
                    <div class="flavor-modal-header">
                        <h3 class="flavor-modal-title"><?php _e('Respuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                        <button class="flavor-modal-close">&times;</button>
                    </div>
                    <span class="flavor-response-status"></span>
                    <div class="flavor-response-panel">
                        <pre class="flavor-response-body"></pre>
                    </div>
                </div>
            </div>
        </div>

        <!-- js-yaml para parsear YAML -->
        <script src="https://cdn.jsdelivr.net/npm/js-yaml@4.1.0/dist/js-yaml.min.js"></script>
        <?php
    }

    /**
     * Escanea todos los endpoints REST registrados
     *
     * @return array
     */
    public function escanear_endpoints() {
        $servidor_rest = rest_get_server();
        $rutas = $servidor_rest->get_routes();
        $endpoints = [];

        $namespaces_flavor = [
            'flavor',
            FLAVOR_PLATFORM_TEXT_DOMAIN,
            'flavor-network',
        ];

        foreach ($rutas as $ruta => $manejadores) {
            // Filtrar solo namespaces de Flavor
            $es_flavor = false;
            foreach ($namespaces_flavor as $namespace) {
                if (strpos($ruta, '/' . $namespace . '/') !== false) {
                    $es_flavor = true;
                    break;
                }
            }

            if (!$es_flavor) {
                continue;
            }

            // Extraer namespace
            preg_match('/^\/([^\/]+\/v\d+)/', $ruta, $coincidencias);
            $namespace = $coincidencias[1] ?? 'unknown';

            if (!isset($endpoints[$namespace])) {
                $endpoints[$namespace] = [];
            }

            foreach ($manejadores as $manejador) {
                if (!isset($manejador['methods'])) {
                    continue;
                }

                $metodos = is_array($manejador['methods']) ? $manejador['methods'] : [$manejador['methods']];

                foreach ($metodos as $metodo => $habilitado) {
                    if (!$habilitado || $metodo === 'OPTIONS') {
                        continue;
                    }

                    $requiere_auth = true;
                    if (isset($manejador['permission_callback'])) {
                        $callback = $manejador['permission_callback'];
                        if ($callback === '__return_true' || (is_string($callback) && $callback === '__return_true')) {
                            $requiere_auth = false;
                        }
                    }

                    $endpoints[$namespace][] = [
                        'route' => $ruta,
                        'method' => $metodo,
                        'requires_auth' => $requiere_auth,
                        'args' => $manejador['args'] ?? [],
                    ];
                }
            }
        }

        // Ordenar namespaces
        ksort($endpoints);

        return $endpoints;
    }

    /**
     * Calcula estadisticas de los endpoints
     *
     * @param array $endpoints Endpoints escaneados
     * @return array
     */
    private function calcular_estadisticas($endpoints) {
        $total_endpoints = 0;
        $endpoints_publicos = 0;
        $endpoints_autenticados = 0;

        foreach ($endpoints as $rutas) {
            foreach ($rutas as $ruta) {
                $total_endpoints++;
                if ($ruta['requires_auth']) {
                    $endpoints_autenticados++;
                } else {
                    $endpoints_publicos++;
                }
            }
        }

        return [
            'total_endpoints' => $total_endpoints,
            'total_namespaces' => count($endpoints),
            'endpoints_publicos' => $endpoints_publicos,
            'endpoints_autenticados' => $endpoints_autenticados,
        ];
    }

    /**
     * Obtiene endpoints en formato JSON
     *
     * @return WP_REST_Response
     */
    public function obtener_endpoints_json() {
        $endpoints = $this->escanear_endpoints();
        $estadisticas = $this->calcular_estadisticas($endpoints);

        return new WP_REST_Response([
            'success' => true,
            'endpoints' => $endpoints,
            'stats' => $estadisticas,
        ]);
    }

    /**
     * AJAX: Probar endpoint
     */
    public function ajax_probar_endpoint() {
        check_ajax_referer('flavor_api_test', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $endpoint = sanitize_text_field($_POST['endpoint'] ?? '');
        $metodo = strtoupper(sanitize_text_field($_POST['method'] ?? 'GET'));

        if (empty($endpoint)) {
            wp_send_json_error(['message' => __('Endpoint requerido', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Solo permitir GET para pruebas simples
        if ($metodo !== 'GET') {
            wp_send_json_error(['message' => __('Solo se permiten pruebas GET', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $url_completa = rest_url(ltrim($endpoint, '/'));
        $respuesta = wp_remote_get($url_completa, [
            'timeout' => 30,
            'cookies' => $_COOKIE,
        ]);

        if (is_wp_error($respuesta)) {
            wp_send_json_error([
                'message' => $respuesta->get_error_message(),
            ]);
        }

        $codigo = wp_remote_retrieve_response_code($respuesta);
        $cuerpo = wp_remote_retrieve_body($respuesta);
        $datos = json_decode($cuerpo, true);

        wp_send_json_success([
            'status' => $codigo,
            'data' => $datos,
            'raw' => $cuerpo,
        ]);
    }

    public function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

/**
 * Inicializa la documentacion de la API
 *
 * @return Flavor_API_Docs
 */
function flavor_api_docs() {
    return Flavor_API_Docs::get_instance();
}

// Inicializar
add_action('plugins_loaded', 'flavor_api_docs');
