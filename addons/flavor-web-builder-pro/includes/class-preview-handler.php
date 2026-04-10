<?php
/**
 * Handler de Vista Previa del Page Builder
 *
 * Gestiona la previsualización de páginas construidas con el Page Builder
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Preview_Handler {

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
        add_action('template_redirect', [$this, 'handle_preview']);
        add_action('wp_ajax_flavor_save_preview', [$this, 'ajax_save_preview']);
    }

    /**
     * Maneja las peticiones de preview
     */
    public function handle_preview() {
        // Verificar si es una petición de preview
        if (!isset($_GET['flavor_preview']) || !isset($_GET['preview_id'])) {
            return;
        }

        $preview_id = sanitize_text_field($_GET['preview_id']);
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field($_GET['_wpnonce']) : '';

        // Verificar nonce
        if (!wp_verify_nonce($nonce, 'flavor_preview_' . $preview_id)) {
            wp_die(__('Vista previa no válida', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Obtener datos del preview desde transient
        $preview_data = get_transient('flavor_preview_' . $preview_id);

        if (!$preview_data) {
            wp_die(__('La vista previa ha expirado. Por favor, genera una nueva.', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Renderizar preview
        $this->render_preview($preview_data);
        exit;
    }

    /**
     * AJAX: Guarda los datos temporales para preview
     */
    public function ajax_save_preview() {
        check_ajax_referer('flavor_page_builder', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Permisos insuficientes', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $layout = isset($_POST['layout']) ? json_decode(stripslashes($_POST['layout']), true) : [];
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (empty($layout)) {
            wp_send_json_error(['message' => __('No hay contenido para previsualizar', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Generar ID único para este preview
        $preview_id = uniqid('preview_', true);

        // Guardar datos en transient (expira en 1 hora)
        $preview_data = [
            'layout' => $layout,
            'post_id' => $post_id,
            'timestamp' => time(),
        ];

        set_transient('flavor_preview_' . $preview_id, $preview_data, HOUR_IN_SECONDS);

        // Generar URL de preview
        $preview_url = add_query_arg([
            'flavor_preview' => '1',
            'preview_id' => $preview_id,
            '_wpnonce' => wp_create_nonce('flavor_preview_' . $preview_id),
        ], home_url('/'));

        wp_send_json_success([
            'preview_url' => $preview_url,
            'preview_id' => $preview_id,
        ]);
    }

    /**
     * Renderiza la vista previa
     */
    private function render_preview($preview_data) {
        $layout = $preview_data['layout'];
        $post_id = $preview_data['post_id'];

        // Obtener título del post si existe
        $post_title = $post_id ? get_the_title($post_id) : __('Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN);

        // Cargar Tailwind CSS y estilos del diseño
        $design_settings = Flavor_Design_Settings::get_instance();

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <meta name="robots" content="noindex, nofollow">
            <title><?php echo esc_html($post_title); ?> - Vista Previa</title>

            <!-- Tailwind CSS CDN -->
            <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

            <!-- Estilos del sistema de diseño -->
            <?php $design_settings->output_custom_css(); ?>

            <style>
                body {
                    margin: 0;
                    padding: 0;
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                }

                /* Barra de preview */
                .flavor-preview-bar {
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    background: #1e293b;
                    color: white;
                    padding: 12px 20px;
                    z-index: 99999;
                    display: flex;
                    align-items: center;
                    justify-content: space-between;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.2);
                }

                .flavor-preview-bar-title {
                    font-weight: 600;
                    display: flex;
                    align-items: center;
                    gap: 10px;
                }

                .flavor-preview-bar-icon {
                    width: 20px;
                    height: 20px;
                    fill: #fbbf24;
                }

                .flavor-preview-bar-close {
                    background: #ef4444;
                    color: white;
                    border: none;
                    padding: 8px 16px;
                    border-radius: 6px;
                    cursor: pointer;
                    font-weight: 600;
                    transition: background 0.2s;
                }

                .flavor-preview-bar-close:hover {
                    background: #dc2626;
                }

                .flavor-preview-content {
                    margin-top: 60px;
                }

                /* Responsive toggles */
                .flavor-preview-device-toggle {
                    display: flex;
                    gap: 10px;
                    background: rgba(255,255,255,0.1);
                    padding: 4px;
                    border-radius: 8px;
                }

                .flavor-preview-device-btn {
                    background: transparent;
                    border: none;
                    color: rgba(255,255,255,0.7);
                    padding: 6px 12px;
                    border-radius: 6px;
                    cursor: pointer;
                    transition: all 0.2s;
                }

                .flavor-preview-device-btn:hover {
                    color: white;
                    background: rgba(255,255,255,0.1);
                }

                .flavor-preview-device-btn.active {
                    background: white;
                    color: #1e293b;
                }

                /* Contenedor responsive */
                .flavor-preview-container {
                    transition: all 0.3s ease;
                    margin: 0 auto;
                }

                .flavor-preview-container.mobile {
                    max-width: 375px;
                }

                .flavor-preview-container.tablet {
                    max-width: 768px;
                }

                .flavor-preview-container.desktop {
                    max-width: 100%;
                }
            </style>

            <?php wp_head(); ?>
        </head>
        <body>
            <!-- Barra de preview -->
            <div class="flavor-preview-bar">
                <div class="flavor-preview-bar-title">
                    <svg class="flavor-preview-bar-icon" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <span><?php _e('MODO VISTA PREVIA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> - <?php echo esc_html($post_title); ?></span>
                </div>

                <div class="flavor-preview-device-toggle">
                    <button class="flavor-preview-device-btn active" data-device="desktop">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.804A1 1 0 0113 18H7a1 1 0 01-.707-1.707l.804-.804L7.22 15H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                    <button class="flavor-preview-device-btn" data-device="tablet">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                    </button>
                    <button class="flavor-preview-device-btn" data-device="mobile">
                        <svg width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M7 2a2 2 0 00-2 2v12a2 2 0 002 2h6a2 2 0 002-2V4a2 2 0 00-2-2H7zm3 14a1 1 0 100-2 1 1 0 000 2z"/>
                        </svg>
                    </button>
                </div>

                <button class="flavor-preview-bar-close" onclick="window.close();">
                    <?php _e('Cerrar Vista Previa', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <!-- Contenido del preview -->
            <div class="flavor-preview-content">
                <div class="flavor-preview-container desktop" id="preview-container">
                    <?php
                    // Renderizar componentes
                    $renderer = new Flavor_Component_Renderer();

                    foreach ($layout as $component_data) {
                        $renderer->render_component(
                            $component_data['component_id'],
                            $component_data['data'] ?? [],
                            $component_data['settings'] ?? []
                        );
                    }
                    ?>
                </div>
            </div>

            <script>
                // Device toggle
                document.querySelectorAll('.flavor-preview-device-btn').forEach(btn => {
                    btn.addEventListener('click', function() {
                        const device = this.dataset.device;
                        const container = document.getElementById('preview-container');

                        // Update active state
                        document.querySelectorAll('.flavor-preview-device-btn').forEach(b => b.classList.remove('active'));
                        this.classList.add('active');

                        // Update container class
                        container.className = 'flavor-preview-container ' + device;
                    });
                });
            </script>

            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }
}

// Inicializar
Flavor_Preview_Handler::get_instance();
