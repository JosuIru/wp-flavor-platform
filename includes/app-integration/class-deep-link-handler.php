<?php
/**
 * Handler de URLs de Deep Links
 *
 * Procesa URLs cortas tipo /app/{slug} y redirige según el dispositivo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Deep_Link_Handler {

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
     * Constructor
     */
    private function __construct() {
        add_action('init', [$this, 'add_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'handle_deep_link']);
    }

    /**
     * Añade reglas de reescritura para URLs /app/{slug}
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^app/([a-z0-9-]+)/?$',
            'index.php?flavor_app_slug=$matches[1]',
            'top'
        );

        // Flush rewrite rules si es necesario (solo una vez)
        if (get_option('flavor_deep_links_flush_needed', false)) {
            flush_rewrite_rules();
            delete_option('flavor_deep_links_flush_needed');
        }
    }

    /**
     * Añade variables de query personalizadas
     */
    public function add_query_vars($query_vars) {
        $query_vars[] = 'flavor_app_slug';
        return $query_vars;
    }

    /**
     * Maneja las peticiones a URLs de deep links
     */
    public function handle_deep_link() {
        $slug = get_query_var('flavor_app_slug');

        if (empty($slug)) {
            return;
        }

        // Verificar que la empresa existe
        $manager = Flavor_Deep_Link_Manager::get_instance();
        $config = $manager->get_config_by_slug($slug);

        if (!$config) {
            wp_die(
                __('Empresa no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                __('Error', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['response' => 404]
            );
        }

        // Detectar el dispositivo
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $is_android = stripos($user_agent, 'Android') !== false;
        $is_ios = stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false;

        // Si es la app nativa, redirigir al deep link
        if ($is_android || $is_ios) {
            $this->render_smart_app_banner($slug, $config, $is_android, $is_ios);
        } else {
            // Si es navegador de escritorio, mostrar página informativa
            $this->render_info_page($slug, $config);
        }

        exit;
    }

    /**
     * Renderiza una página con smart app banner para móviles
     */
    private function render_smart_app_banner($slug, $config, $is_android, $is_ios) {
        $api_config_url = rest_url(Flavor_Deep_Link_Manager::API_NAMESPACE . '/config/' . $slug);

        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($config->nombre); ?> - App</title>

            <?php if ($is_ios): ?>
                <!-- Smart App Banner para iOS -->
                <meta name="apple-itunes-app" content="app-id=123456789, app-argument=flavorapp://company/<?php echo esc_attr($slug); ?>">
            <?php endif; ?>

            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 20px;
                }

                .container {
                    background: white;
                    border-radius: 20px;
                    padding: 40px;
                    max-width: 500px;
                    width: 100%;
                    text-align: center;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }

                .logo {
                    width: 100px;
                    height: 100px;
                    border-radius: 50%;
                    margin: 0 auto 20px;
                    object-fit: cover;
                    border: 4px solid #f0f0f0;
                }

                .logo-placeholder {
                    width: 100px;
                    height: 100px;
                    border-radius: 50%;
                    margin: 0 auto 20px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 48px;
                    color: white;
                    font-weight: bold;
                }

                h1 {
                    font-size: 28px;
                    color: #333;
                    margin-bottom: 10px;
                }

                p {
                    color: #666;
                    font-size: 16px;
                    line-height: 1.6;
                    margin-bottom: 30px;
                }

                .buttons {
                    display: flex;
                    flex-direction: column;
                    gap: 15px;
                }

                .btn {
                    display: inline-block;
                    padding: 16px 32px;
                    border-radius: 12px;
                    text-decoration: none;
                    font-weight: 600;
                    font-size: 16px;
                    transition: all 0.3s ease;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    color: white;
                }

                .btn-primary:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
                }

                .btn-secondary {
                    background: #f0f0f0;
                    color: #333;
                }

                .btn-secondary:hover {
                    background: #e0e0e0;
                }

                .store-buttons {
                    margin-top: 20px;
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                }

                .store-btn {
                    display: inline-block;
                    height: 50px;
                }

                .store-btn img {
                    height: 100%;
                    width: auto;
                }

                .info {
                    margin-top: 30px;
                    padding: 20px;
                    background: #f9f9f9;
                    border-radius: 12px;
                    font-size: 14px;
                    color: #666;
                }

                @media (max-width: 600px) {
                    .container {
                        padding: 30px 20px;
                    }

                    h1 {
                        font-size: 24px;
                    }

                    .buttons {
                        gap: 10px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <?php if ($config->logo_url): ?>
                    <img src="<?php echo esc_url($config->logo_url); ?>" alt="<?php echo esc_attr($config->nombre); ?>" class="logo">
                <?php else: ?>
                    <div class="logo-placeholder">
                        <?php echo esc_html(substr($config->nombre, 0, 1)); ?>
                    </div>
                <?php endif; ?>

                <h1><?php echo esc_html($config->nombre); ?></h1>
                <p><?php echo esc_html($config->descripcion ?: 'Bienvenido a nuestra aplicación móvil'); ?></p>

                <div class="buttons">
                    <?php if ($is_android): ?>
                        <a href="flavorapp://company/<?php echo esc_attr($slug); ?>" class="btn btn-primary" id="open-app-btn">
                            Abrir en la App
                        </a>
                        <a href="https://play.google.com/store/apps/details?id=com.flavor.community&referrer=<?php echo urlencode('company=' . $slug); ?>" class="btn btn-secondary">
                            Descargar de Google Play
                        </a>
                    <?php elseif ($is_ios): ?>
                        <a href="flavorapp://company/<?php echo esc_attr($slug); ?>" class="btn btn-primary" id="open-app-btn">
                            Abrir en la App
                        </a>
                        <a href="https://apps.apple.com/app/id123456789?pt=company&ct=<?php echo esc_attr($slug); ?>" class="btn btn-secondary">
                            Descargar de App Store
                        </a>
                    <?php endif; ?>
                </div>

                <div class="info">
                    <strong>¿No tienes la app instalada?</strong><br>
                    Descárgala desde tu tienda de aplicaciones para comenzar.
                </div>
            </div>

            <script>
                // Intentar abrir la app automáticamente
                (function() {
                    const deepLink = 'flavorapp://company/<?php echo esc_js($slug); ?>';
                    const fallbackDelay = 2500;

                    // Configurar fallback a store
                    const fallbackUrl = <?php
                        if ($is_android) {
                            echo '"https://play.google.com/store/apps/details?id=com.flavor.community&referrer=' . urlencode('company=' . $slug) . '"';
                        } elseif ($is_ios) {
                            echo '"https://apps.apple.com/app/id123456789?pt=company&ct=' . esc_js($slug) . '"';
                        } else {
                            echo '""';
                        }
                    ?>;

                    const startTime = Date.now();

                    // Intentar abrir la app
                    window.location.href = deepLink;

                    // Si después de 2.5s el usuario sigue aquí, probablemente no tiene la app
                    const fallbackTimer = setTimeout(function() {
                        // Solo hacer fallback si la página aún está visible
                        // (si la app se abrió, la página estará en background)
                        if (!document.hidden && fallbackUrl) {
                            window.location.href = fallbackUrl;
                        }
                    }, fallbackDelay);

                    // Limpiar el timer si la página se oculta (app opened successfully)
                    document.addEventListener('visibilitychange', function() {
                        if (document.hidden) {
                            clearTimeout(fallbackTimer);
                        }
                    });

                    // Limpiar el timer si el usuario abandona la página
                    window.addEventListener('pagehide', function() {
                        clearTimeout(fallbackTimer);
                    });
                })();
            </script>
        </body>
        </html>
        <?php
    }

    /**
     * Renderiza página informativa para navegadores de escritorio
     */
    private function render_info_page($slug, $config) {
        $api_config_url = rest_url(Flavor_Deep_Link_Manager::API_NAMESPACE . '/config/' . $slug);
        $config_data = json_decode($config->configuracion, true);
        $colores = $config_data['colores'] ?? [];

        ?>
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($config->nombre); ?> - Aplicación Móvil</title>
            <style>
                * {
                    margin: 0;
                    padding: 0;
                    box-sizing: border-box;
                }

                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    min-height: 100vh;
                    padding: 40px 20px;
                }

                .container {
                    max-width: 800px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 20px;
                    padding: 60px;
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
                }

                .header {
                    text-align: center;
                    margin-bottom: 50px;
                }

                .logo {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    margin: 0 auto 30px;
                    object-fit: cover;
                    border: 5px solid #f0f0f0;
                }

                .logo-placeholder {
                    width: 120px;
                    height: 120px;
                    border-radius: 50%;
                    margin: 0 auto 30px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 60px;
                    color: white;
                    font-weight: bold;
                }

                h1 {
                    font-size: 36px;
                    color: #333;
                    margin-bottom: 15px;
                }

                .subtitle {
                    font-size: 18px;
                    color: #666;
                    line-height: 1.6;
                }

                .section {
                    margin: 40px 0;
                }

                .section h2 {
                    font-size: 24px;
                    color: #333;
                    margin-bottom: 20px;
                }

                .qr-section {
                    text-align: center;
                    padding: 40px;
                    background: #f9f9f9;
                    border-radius: 15px;
                }

                .qr-code {
                    width: 250px;
                    height: 250px;
                    margin: 20px auto;
                    padding: 20px;
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                }

                .store-buttons {
                    display: flex;
                    gap: 20px;
                    justify-content: center;
                    margin: 30px 0;
                }

                .store-btn {
                    display: inline-block;
                    height: 60px;
                    transition: transform 0.3s ease;
                }

                .store-btn:hover {
                    transform: translateY(-3px);
                }

                .store-btn img {
                    height: 100%;
                    width: auto;
                }

                .api-info {
                    background: #f9f9f9;
                    padding: 30px;
                    border-radius: 15px;
                    border-left: 4px solid #667eea;
                }

                .api-info h3 {
                    font-size: 18px;
                    color: #333;
                    margin-bottom: 15px;
                }

                .api-info code {
                    display: block;
                    background: #fff;
                    padding: 15px;
                    border-radius: 8px;
                    font-family: 'Courier New', monospace;
                    font-size: 14px;
                    color: #d63384;
                    word-break: break-all;
                    margin: 10px 0;
                }

                .features {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                    gap: 20px;
                    margin: 30px 0;
                }

                .feature {
                    text-align: center;
                    padding: 20px;
                }

                .feature-icon {
                    width: 60px;
                    height: 60px;
                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                    border-radius: 50%;
                    margin: 0 auto 15px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 30px;
                    color: white;
                }

                .feature h3 {
                    font-size: 16px;
                    color: #333;
                    margin-bottom: 10px;
                }

                .feature p {
                    font-size: 14px;
                    color: #666;
                    line-height: 1.5;
                }

                @media (max-width: 768px) {
                    .container {
                        padding: 40px 30px;
                    }

                    h1 {
                        font-size: 28px;
                    }

                    .store-buttons {
                        flex-direction: column;
                        align-items: center;
                    }

                    .qr-code {
                        width: 200px;
                        height: 200px;
                    }
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <?php if ($config->logo_url): ?>
                        <img src="<?php echo esc_url($config->logo_url); ?>" alt="<?php echo esc_attr($config->nombre); ?>" class="logo">
                    <?php else: ?>
                        <div class="logo-placeholder">
                            <?php echo esc_html(substr($config->nombre, 0, 1)); ?>
                        </div>
                    <?php endif; ?>

                    <h1><?php echo esc_html($config->nombre); ?></h1>
                    <p class="subtitle"><?php echo esc_html($config->descripcion ?: 'Descarga nuestra aplicación móvil'); ?></p>
                </div>

                <div class="section">
                    <h2>Descarga la App</h2>
                    <div class="store-buttons">
                        <a href="https://play.google.com/store/apps/details?id=com.flavor.community&referrer=<?php echo urlencode('company=' . $slug); ?>" class="store-btn" target="_blank" rel="noopener">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/7/78/Google_Play_Store_badge_EN.svg" alt="Get it on Google Play">
                        </a>
                        <a href="https://apps.apple.com/app/id123456789?pt=company&ct=<?php echo esc_attr($slug); ?>" class="store-btn" target="_blank" rel="noopener">
                            <img src="https://upload.wikimedia.org/wikipedia/commons/3/3c/Download_on_the_App_Store_Badge.svg" alt="Download on the App Store">
                        </a>
                    </div>
                </div>

                <div class="section qr-section">
                    <h2>Escanea el QR con tu móvil</h2>
                    <p>Escanea este código QR con tu teléfono para descargar la app configurada para <?php echo esc_html($config->nombre); ?></p>
                    <div class="qr-code">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?php echo urlencode(get_site_url() . '/app/' . $slug); ?>" alt="QR Code" style="width: 100%; height: 100%;">
                    </div>
                </div>

                <div class="section">
                    <h2>Características</h2>
                    <div class="features">
                        <div class="feature">
                            <div class="feature-icon">💬</div>
                            <h3>Chat Inteligente</h3>
                            <p>Asistente IA para responder tus dudas</p>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">📅</div>
                            <h3>Reservas</h3>
                            <p>Gestiona tus reservas fácilmente</p>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">🛒</div>
                            <h3>Marketplace</h3>
                            <p>Compra y vende en la comunidad</p>
                        </div>
                        <div class="feature">
                            <div class="feature-icon">🚗</div>
                            <h3>Carpooling</h3>
                            <p>Comparte viajes y ahorra</p>
                        </div>
                    </div>
                </div>

                <div class="section">
                    <div class="api-info">
                        <h3>Para Desarrolladores</h3>
                        <p>URL de configuración de API:</p>
                        <code><?php echo esc_html($api_config_url); ?></code>
                        <p style="margin-top: 15px;">La app móvil utilizará esta URL para obtener la configuración personalizada de <?php echo esc_html($config->nombre); ?>.</p>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Activar reglas de reescritura
     */
    public static function activate() {
        update_option('flavor_deep_links_flush_needed', true);
    }
}

// Inicializar
Flavor_Deep_Link_Handler::get_instance();
