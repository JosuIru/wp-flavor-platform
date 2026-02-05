<?php
/**
 * Setup Wizard - Asistente de configuración inicial
 *
 * Guía paso a paso para configurar Flavor Platform por primera vez
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
        $this->init_hooks();
    }

    /**
     * Inicializa los pasos del wizard
     *
     * @return void
     */
    private function init_pasos() {
        $this->pasos = [
            'welcome' => [
                'nombre' => __('Bienvenida', 'flavor-chat-ia'),
                'icono' => 'dashicons-welcome-learn-more',
            ],
            'profile' => [
                'nombre' => __('Perfil de Aplicación', 'flavor-chat-ia'),
                'icono' => 'dashicons-smartphone',
            ],
            'ai' => [
                'nombre' => __('Configurar IA', 'flavor-chat-ia'),
                'icono' => 'dashicons-format-chat',
            ],
            'modules' => [
                'nombre' => __('Módulos', 'flavor-chat-ia'),
                'icono' => 'dashicons-admin-plugins',
            ],
            'design' => [
                'nombre' => __('Diseño', 'flavor-chat-ia'),
                'icono' => 'dashicons-art',
            ],
            'complete' => [
                'nombre' => __('Finalizar', 'flavor-chat-ia'),
                'icono' => 'dashicons-yes-alt',
            ],
        ];
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        // Mostrar wizard solo en la activación o cuando se solicite
        add_action('admin_init', [$this, 'maybe_redirect_to_wizard']);

        // Registrar página del wizard
        add_action('admin_menu', [$this, 'add_wizard_page']);

        // Procesar formularios del wizard
        add_action('admin_init', [$this, 'process_wizard_steps']);

        // Assets del wizard
        add_action('admin_enqueue_scripts', [$this, 'enqueue_wizard_assets']);
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
        if (!is_admin() || isset($_GET['page']) && $_GET['page'] === 'flavor-setup-wizard') {
            return;
        }

        // Solo redirigir una vez
        if (get_transient('flavor_setup_redirect')) {
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
     * Procesa los pasos del wizard
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

        $paso = sanitize_text_field($_POST['flavor_wizard_step']);

        switch ($paso) {
            case 'profile':
                $this->save_profile_step();
                break;
            case 'ai':
                $this->save_ai_step();
                break;
            case 'modules':
                $this->save_modules_step();
                break;
            case 'design':
                $this->save_design_step();
                break;
            case 'complete':
                $this->complete_wizard();
                break;
        }
    }

    /**
     * Guarda el paso de perfil
     *
     * @return void
     */
    private function save_profile_step() {
        if (isset($_POST['app_profile'])) {
            update_option('flavor_selected_profile', sanitize_text_field($_POST['app_profile']));

            // Activar módulos del perfil automáticamente
            if (class_exists('Flavor_App_Profiles')) {
                $profiles = Flavor_App_Profiles::get_instance();
                $profile_data = $profiles->obtener_perfil(sanitize_text_field($_POST['app_profile']));

                if ($profile_data && isset($profile_data['modulos_requeridos'])) {
                    update_option('flavor_active_modules', $profile_data['modulos_requeridos']);
                }
            }
        }

        wp_safe_redirect(admin_url('admin.php?page=flavor-setup-wizard&step=ai'));
        exit;
    }

    /**
     * Guarda el paso de IA
     *
     * @return void
     */
    private function save_ai_step() {
        if (isset($_POST['ai_provider']) && isset($_POST['api_key'])) {
            $provider = sanitize_text_field($_POST['ai_provider']);
            $api_key = sanitize_text_field($_POST['api_key']);

            // Guardar configuración según el proveedor
            update_option('flavor_ai_provider', $provider);
            update_option('flavor_' . $provider . '_api_key', $api_key);
        }

        wp_safe_redirect(admin_url('admin.php?page=flavor-setup-wizard&step=modules'));
        exit;
    }

    /**
     * Guarda el paso de módulos
     *
     * @return void
     */
    private function save_modules_step() {
        if (isset($_POST['active_modules']) && is_array($_POST['active_modules'])) {
            $modules = array_map('sanitize_text_field', $_POST['active_modules']);
            update_option('flavor_active_modules', $modules);
        }

        wp_safe_redirect(admin_url('admin.php?page=flavor-setup-wizard&step=design'));
        exit;
    }

    /**
     * Guarda el paso de diseño
     *
     * @return void
     */
    private function save_design_step() {
        if (isset($_POST['primary_color'])) {
            update_option('flavor_primary_color', sanitize_hex_color($_POST['primary_color']));
        }

        if (isset($_POST['font_family'])) {
            update_option('flavor_font_family', sanitize_text_field($_POST['font_family']));
        }

        wp_safe_redirect(admin_url('admin.php?page=flavor-setup-wizard&step=complete'));
        exit;
    }

    /**
     * Completa el wizard
     *
     * @return void
     */
    private function complete_wizard() {
        update_option('flavor_setup_completed', true);
        update_option('flavor_setup_completed_date', current_time('mysql'));

        wp_safe_redirect(admin_url('admin.php?page=flavor-dashboard'));
        exit;
    }

    /**
     * Renderiza el wizard
     *
     * @return void
     */
    public function render_wizard() {
        $this->paso_actual = isset($_GET['step']) ? sanitize_text_field($_GET['step']) : 'welcome';

        // Validar que el paso exista
        if (!isset($this->pasos[$this->paso_actual])) {
            $this->paso_actual = 'welcome';
        }

        ?>
        <div class="wrap flavor-wizard-wrap">
            <?php $this->render_wizard_header(); ?>
            <?php $this->render_wizard_steps(); ?>
            <?php $this->render_wizard_content(); ?>
        </div>
        <?php
    }

    /**
     * Renderiza el header del wizard
     *
     * @return void
     */
    private function render_wizard_header() {
        ?>
        <div class="flavor-wizard-header">
            <h1><?php echo esc_html__('Configuración Inicial de Flavor Platform', 'flavor-chat-ia'); ?></h1>
            <p><?php echo esc_html__('Te guiaremos paso a paso para configurar tu plataforma.', 'flavor-chat-ia'); ?></p>
        </div>
        <?php
    }

    /**
     * Renderiza los indicadores de pasos
     *
     * @return void
     */
    private function render_wizard_steps() {
        $paso_keys = array_keys($this->pasos);
        $indice_actual = array_search($this->paso_actual, $paso_keys);

        ?>
        <div class="flavor-wizard-steps">
            <?php foreach ($this->pasos as $key => $paso): ?>
                <?php
                $indice_paso = array_search($key, $paso_keys);
                $clase = '';
                if ($indice_paso < $indice_actual) {
                    $clase = 'completed';
                } elseif ($key === $this->paso_actual) {
                    $clase = 'active';
                }
                ?>
                <div class="flavor-wizard-step <?php echo esc_attr($clase); ?>">
                    <span class="dashicons <?php echo esc_attr($paso['icono']); ?>"></span>
                    <span class="step-name"><?php echo esc_html($paso['nombre']); ?></span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza el contenido del paso actual
     *
     * @return void
     */
    private function render_wizard_content() {
        $metodo = 'render_step_' . $this->paso_actual;

        if (method_exists($this, $metodo)) {
            call_user_func([$this, $metodo]);
        }
    }

    /**
     * Paso: Bienvenida
     *
     * @return void
     */
    private function render_step_welcome() {
        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('¡Bienvenido a Flavor Platform 3.0!', 'flavor-chat-ia'); ?></h2>

            <p><?php echo esc_html__('Flavor Platform es una plataforma modular que te permite crear comunidades, gestionar contenido y conectar con usuarios de forma inteligente.', 'flavor-chat-ia'); ?></p>

            <h3><?php echo esc_html__('¿Qué puedes hacer?', 'flavor-chat-ia'); ?></h3>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><?php echo esc_html__('Crear landing pages con el constructor visual', 'flavor-chat-ia'); ?></li>
                <li><?php echo esc_html__('Conectar sitios en una red de comunidades', 'flavor-chat-ia'); ?></li>
                <li><?php echo esc_html__('Gestionar publicidad ética y monetizar', 'flavor-chat-ia'); ?></li>
                <li><?php echo esc_html__('Usar asistente IA en el panel de administración', 'flavor-chat-ia'); ?></li>
                <li><?php echo esc_html__('Y mucho más con addons y módulos', 'flavor-chat-ia'); ?></li>
            </ul>

            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-setup-wizard&step=profile')); ?>" class="button button-primary button-hero">
                    <?php echo esc_html__('Comenzar Configuración', 'flavor-chat-ia'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-dashboard')); ?>" class="button button-hero">
                    <?php echo esc_html__('Saltar Configuración', 'flavor-chat-ia'); ?>
                </a>
            </p>
        </div>
        <?php
    }

    /**
     * Paso: Perfil de aplicación
     *
     * @return void
     */
    private function render_step_profile() {
        $profiles = [];
        if (class_exists('Flavor_App_Profiles')) {
            $profiles_instance = Flavor_App_Profiles::get_instance();
            $profiles = $profiles_instance->obtener_perfiles();
        }

        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('Selecciona tu Perfil de Aplicación', 'flavor-chat-ia'); ?></h2>
            <p><?php echo esc_html__('Elige el perfil que mejor se ajuste a tu proyecto. Esto activará los módulos recomendados.', 'flavor-chat-ia'); ?></p>

            <form method="post">
                <?php wp_nonce_field('flavor_wizard_step'); ?>
                <input type="hidden" name="flavor_wizard_step" value="profile">

                <div class="flavor-profile-grid">
                    <?php foreach ($profiles as $key => $profile): ?>
                        <label class="flavor-profile-option">
                            <input type="radio" name="app_profile" value="<?php echo esc_attr($key); ?>" <?php checked($key, 'custom'); ?>>
                            <div class="profile-card">
                                <h3><?php echo esc_html($profile['nombre']); ?></h3>
                                <p><?php echo esc_html($profile['descripcion'] ?? ''); ?></p>
                            </div>
                        </label>
                    <?php endforeach; ?>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo esc_html__('Continuar', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Paso: Configurar IA
     *
     * @return void
     */
    private function render_step_ai() {
        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('Configurar Motor de IA', 'flavor-chat-ia'); ?></h2>
            <p><?php echo esc_html__('Configura tu motor de IA para habilitar el chat inteligente y asistentes.', 'flavor-chat-ia'); ?></p>

            <form method="post">
                <?php wp_nonce_field('flavor_wizard_step'); ?>
                <input type="hidden" name="flavor_wizard_step" value="ai">

                <table class="form-table">
                    <tr>
                        <th><label for="ai_provider"><?php echo esc_html__('Proveedor de IA', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select name="ai_provider" id="ai_provider" class="regular-text">
                                <option value="claude">Claude (Anthropic)</option>
                                <option value="openai">OpenAI (GPT)</option>
                                <option value="deepseek">DeepSeek</option>
                                <option value="mistral">Mistral AI</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="api_key"><?php echo esc_html__('API Key', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="password" name="api_key" id="api_key" class="regular-text">
                            <p class="description"><?php echo esc_html__('Puedes configurarlo después si no tienes tu API key ahora.', 'flavor-chat-ia'); ?></p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo esc_html__('Continuar', 'flavor-chat-ia'); ?>
                    </button>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=flavor-setup-wizard&step=modules')); ?>" class="button button-hero">
                        <?php echo esc_html__('Saltar', 'flavor-chat-ia'); ?>
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Paso: Módulos
     *
     * @return void
     */
    private function render_step_modules() {
        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('Selecciona Módulos Adicionales', 'flavor-chat-ia'); ?></h2>
            <p><?php echo esc_html__('Activa los módulos que necesites. Puedes cambiarlos después.', 'flavor-chat-ia'); ?></p>

            <form method="post">
                <?php wp_nonce_field('flavor_wizard_step'); ?>
                <input type="hidden" name="flavor_wizard_step" value="modules">

                <div class="flavor-modules-grid">
                    <label><input type="checkbox" name="active_modules[]" value="eventos"> Eventos</label>
                    <label><input type="checkbox" name="active_modules[]" value="marketplace"> Marketplace</label>
                    <label><input type="checkbox" name="active_modules[]" value="grupos_consumo"> Grupos de Consumo</label>
                    <label><input type="checkbox" name="active_modules[]" value="banco_tiempo"> Banco de Tiempo</label>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo esc_html__('Continuar', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Paso: Diseño
     *
     * @return void
     */
    private function render_step_design() {
        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('Personaliza el Diseño', 'flavor-chat-ia'); ?></h2>
            <p><?php echo esc_html__('Define los colores y tipografía básicos de tu sitio.', 'flavor-chat-ia'); ?></p>

            <form method="post">
                <?php wp_nonce_field('flavor_wizard_step'); ?>
                <input type="hidden" name="flavor_wizard_step" value="design">

                <table class="form-table">
                    <tr>
                        <th><label for="primary_color"><?php echo esc_html__('Color Primario', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <input type="color" name="primary_color" id="primary_color" value="#2271b1">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="font_family"><?php echo esc_html__('Tipografía', 'flavor-chat-ia'); ?></label></th>
                        <td>
                            <select name="font_family" id="font_family">
                                <option value="Inter">Inter</option>
                                <option value="Roboto">Roboto</option>
                                <option value="Open Sans">Open Sans</option>
                            </select>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo esc_html__('Continuar', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Paso: Completado
     *
     * @return void
     */
    private function render_step_complete() {
        ?>
        <div class="flavor-wizard-content">
            <h2><?php echo esc_html__('¡Configuración Completada!', 'flavor-chat-ia'); ?></h2>
            <p><?php echo esc_html__('Flavor Platform está listo para usar. ¿Qué quieres hacer ahora?', 'flavor-chat-ia'); ?></p>

            <div class="flavor-next-steps">
                <a href="<?php echo admin_url('admin.php?page=flavor-addons'); ?>" class="flavor-next-step-card">
                    <span class="dashicons dashicons-admin-plugins"></span>
                    <h3><?php echo esc_html__('Explorar Addons', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Añade funcionalidades con addons', 'flavor-chat-ia'); ?></p>
                </a>
                <?php if (class_exists('Flavor_Page_Builder')): ?>
                <a href="<?php echo admin_url('post-new.php?post_type=flavor_landing'); ?>" class="flavor-next-step-card">
                    <span class="dashicons dashicons-layout"></span>
                    <h3><?php echo esc_html__('Crear Landing Page', 'flavor-chat-ia'); ?></h3>
                    <p><?php echo esc_html__('Diseña tu primera página', 'flavor-chat-ia'); ?></p>
                </a>
                <?php endif; ?>
            </div>

            <form method="post">
                <?php wp_nonce_field('flavor_wizard_step'); ?>
                <input type="hidden" name="flavor_wizard_step" value="complete">

                <p class="submit">
                    <button type="submit" class="button button-primary button-hero">
                        <?php echo esc_html__('Ir al Dashboard', 'flavor-chat-ia'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
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

        $css_wizard = "
            .flavor-wizard-wrap {
                max-width: 900px;
                margin: 40px auto;
                background: #fff;
                padding: 40px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.13);
            }
            .flavor-wizard-header {
                text-align: center;
                margin-bottom: 40px;
            }
            .flavor-wizard-steps {
                display: flex;
                justify-content: space-between;
                margin-bottom: 40px;
                padding-bottom: 20px;
                border-bottom: 2px solid #f0f0f1;
            }
            .flavor-wizard-step {
                flex: 1;
                text-align: center;
                opacity: 0.5;
            }
            .flavor-wizard-step.active {
                opacity: 1;
            }
            .flavor-wizard-step.completed {
                opacity: 0.7;
            }
            .flavor-wizard-step .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
            }
            .flavor-wizard-step .step-name {
                display: block;
                font-size: 12px;
                margin-top: 5px;
            }
            .flavor-profile-grid, .flavor-modules-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .flavor-profile-option {
                cursor: pointer;
            }
            .profile-card {
                padding: 20px;
                border: 2px solid #ddd;
                border-radius: 4px;
                transition: all 0.3s;
            }
            .flavor-profile-option input:checked + .profile-card {
                border-color: #2271b1;
                background: #f0f6fc;
            }
            .flavor-next-steps {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin: 30px 0;
            }
            .flavor-next-step-card {
                padding: 30px;
                border: 1px solid #c3c4c7;
                border-radius: 4px;
                text-align: center;
                text-decoration: none;
                transition: all 0.3s;
            }
            .flavor-next-step-card:hover {
                border-color: #2271b1;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            }
            .flavor-next-step-card .dashicons {
                font-size: 48px;
                width: 48px;
                height: 48px;
                color: #2271b1;
            }
        ";

        wp_add_inline_style('wp-admin', $css_wizard);
    }
}
