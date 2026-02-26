<?php
/**
 * Frontend Controller para Socios
 *
 * @package FlavorChatIA
 * @subpackage Modules\Socios
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Controlador Frontend para el módulo de Socios
 */
class Flavor_Socios_Frontend_Controller {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Obtener instancia
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->init();
    }

    /**
     * Inicializar hooks
     */
    private function init() {
        add_action('wp_enqueue_scripts', [$this, 'registrar_assets']);
        add_action('init', [$this, 'registrar_shortcodes']);

        // AJAX handlers
        add_action('wp_ajax_socios_solicitar_alta', [$this, 'ajax_solicitar_alta']);
        add_action('wp_ajax_nopriv_socios_solicitar_alta', [$this, 'ajax_solicitar_alta']);
        add_action('wp_ajax_socios_actualizar_perfil', [$this, 'ajax_actualizar_perfil']);
        add_action('wp_ajax_socios_renovar_cuota', [$this, 'ajax_renovar_cuota']);
        add_action('wp_ajax_socios_obtener_estado', [$this, 'ajax_obtener_estado']);
        add_action('wp_ajax_socios_descargar_carnet', [$this, 'ajax_descargar_carnet']);
    }

    /**
     * Registrar assets
     */
    public function registrar_assets() {
        $base_url = plugins_url('assets/', dirname(__FILE__));
        $version = FLAVOR_CHAT_IA_VERSION ?? '1.0.0';

        wp_register_style(
            'flavor-socios',
            $base_url . 'css/socios.css',
            [],
            $version
        );

        wp_register_script(
            'flavor-socios',
            $base_url . 'js/socios.js',
            ['jquery'],
            $version,
            true
        );

        wp_localize_script('flavor-socios', 'flavorSocios', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('socios_nonce'),
            'i18n' => [
                'solicitud_enviada' => __('Solicitud enviada correctamente', 'flavor-chat-ia'),
                'perfil_actualizado' => __('Perfil actualizado', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
                'cargando' => __('Procesando...', 'flavor-chat-ia'),
                'confirmar_renovar' => __('¿Confirmas la renovación de tu cuota?', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Encolar assets
     */
    public function encolar_assets() {
        wp_enqueue_style('flavor-socios');
        wp_enqueue_script('flavor-socios');
    }

    /**
     * Registrar shortcodes
     */
    public function registrar_shortcodes() {
        add_shortcode('socios_formulario_alta', [$this, 'shortcode_formulario_alta']);
        add_shortcode('socios_mi_perfil', [$this, 'shortcode_mi_perfil']);
        add_shortcode('socios_mi_carnet', [$this, 'shortcode_mi_carnet']);
        add_shortcode('socios_mis_cuotas', [$this, 'shortcode_mis_cuotas']);
        add_shortcode('socios_directorio', [$this, 'shortcode_directorio']);
        add_shortcode('socios_ventajas', [$this, 'shortcode_ventajas']);
        add_shortcode('socios_estadisticas', [$this, 'shortcode_estadisticas']);
    }

    /**
     * Shortcode: Formulario de alta
     */
    public function shortcode_formulario_alta($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'tipo_membresia' => '',
            'mostrar_tipos' => 'true',
        ], $atts);

        // Si ya es socio, mostrar mensaje
        if (is_user_logged_in()) {
            global $wpdb;
            $tabla_socios = $wpdb->prefix . 'flavor_socios';
            $usuario_id = get_current_user_id();

            if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
                $es_socio = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$tabla_socios} WHERE usuario_id = %d AND estado IN ('activo', 'pendiente')",
                    $usuario_id
                ));

                if ($es_socio) {
                    return '<div class="flavor-notice flavor-notice-info"><p>' .
                        __('Ya eres socio/a. Puedes ver tu perfil en el área de socios.', 'flavor-chat-ia') .
                        '</p><a href="' . esc_url(home_url('/mi-portal/socios/')) . '" class="flavor-btn flavor-btn-primary">' .
                        __('Ir a mi perfil', 'flavor-chat-ia') . '</a></div>';
                }
            }
        }

        // Obtener tipos de membresía
        global $wpdb;
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';
        $tipos_membresia = [];

        if (Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
            $tipos_membresia = $wpdb->get_results(
                "SELECT * FROM {$tabla_tipos} WHERE activo = 1 ORDER BY precio ASC"
            );
        }

        ob_start();
        ?>
        <div class="flavor-socios-alta">
            <h2><?php esc_html_e('Hazte Socio/a', 'flavor-chat-ia'); ?></h2>

            <?php if ($atts['mostrar_tipos'] === 'true' && !empty($tipos_membresia)): ?>
                <div class="tipos-membresia">
                    <h3><?php esc_html_e('Elige tu tipo de membresía', 'flavor-chat-ia'); ?></h3>
                    <div class="tipos-grid">
                        <?php foreach ($tipos_membresia as $tipo): ?>
                            <div class="tipo-card <?php echo $tipo->destacado ? 'destacado' : ''; ?>">
                                <?php if ($tipo->destacado): ?>
                                    <span class="tipo-badge"><?php esc_html_e('Recomendado', 'flavor-chat-ia'); ?></span>
                                <?php endif; ?>
                                <h4><?php echo esc_html($tipo->nombre); ?></h4>
                                <div class="tipo-precio">
                                    <span class="precio"><?php echo number_format_i18n($tipo->precio, 2); ?> €</span>
                                    <span class="periodo">/<?php echo esc_html($tipo->periodo); ?></span>
                                </div>
                                <?php if ($tipo->descripcion): ?>
                                    <p><?php echo esc_html($tipo->descripcion); ?></p>
                                <?php endif; ?>
                                <?php if ($tipo->beneficios): ?>
                                    <ul class="tipo-beneficios">
                                        <?php foreach (explode("\n", $tipo->beneficios) as $beneficio): ?>
                                            <?php if (trim($beneficio)): ?>
                                                <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html(trim($beneficio)); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <button type="button" class="flavor-btn flavor-btn-primary flavor-btn-block btn-seleccionar-tipo" data-tipo="<?php echo esc_attr($tipo->id); ?>">
                                    <?php esc_html_e('Seleccionar', 'flavor-chat-ia'); ?>
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <form id="form-alta-socio" class="flavor-form" <?php echo ($atts['mostrar_tipos'] === 'true' && !empty($tipos_membresia)) ? 'style="display:none;"' : ''; ?>>
                <?php wp_nonce_field('socios_nonce', 'socios_nonce_field'); ?>
                <input type="hidden" name="tipo_membresia_id" id="tipo_membresia_id" value="<?php echo esc_attr($atts['tipo_membresia']); ?>">

                <div class="flavor-form-section">
                    <h3><?php esc_html_e('Datos Personales', 'flavor-chat-ia'); ?></h3>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="nombre"><?php esc_html_e('Nombre', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" name="nombre" id="nombre" required value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->first_name) : ''; ?>">
                        </div>
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="apellidos"><?php esc_html_e('Apellidos', 'flavor-chat-ia'); ?> *</label>
                            <input type="text" name="apellidos" id="apellidos" required value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->last_name) : ''; ?>">
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="email"><?php esc_html_e('Email', 'flavor-chat-ia'); ?> *</label>
                            <input type="email" name="email" id="email" required value="<?php echo is_user_logged_in() ? esc_attr(wp_get_current_user()->user_email) : ''; ?>">
                        </div>
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="telefono"><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label>
                            <input type="tel" name="telefono" id="telefono">
                        </div>
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="dni"><?php esc_html_e('DNI/NIE', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="dni" id="dni">
                        </div>
                        <div class="flavor-form-group flavor-form-col-6">
                            <label for="fecha_nacimiento"><?php esc_html_e('Fecha de Nacimiento', 'flavor-chat-ia'); ?></label>
                            <input type="date" name="fecha_nacimiento" id="fecha_nacimiento">
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label for="direccion"><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="direccion" id="direccion">
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-4">
                            <label for="codigo_postal"><?php esc_html_e('Código Postal', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="codigo_postal" id="codigo_postal">
                        </div>
                        <div class="flavor-form-group flavor-form-col-8">
                            <label for="ciudad"><?php esc_html_e('Ciudad', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="ciudad" id="ciudad">
                        </div>
                    </div>
                </div>

                <?php if (!is_user_logged_in()): ?>
                    <div class="flavor-form-section">
                        <h3><?php esc_html_e('Crear Cuenta', 'flavor-chat-ia'); ?></h3>
                        <div class="flavor-form-row">
                            <div class="flavor-form-group flavor-form-col-6">
                                <label for="password"><?php esc_html_e('Contraseña', 'flavor-chat-ia'); ?> *</label>
                                <input type="password" name="password" id="password" required minlength="8">
                            </div>
                            <div class="flavor-form-group flavor-form-col-6">
                                <label for="password_confirm"><?php esc_html_e('Confirmar Contraseña', 'flavor-chat-ia'); ?> *</label>
                                <input type="password" name="password_confirm" id="password_confirm" required>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flavor-form-section">
                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="acepta_estatutos" value="1" required>
                            <?php esc_html_e('Acepto los estatutos y normas de la asociación', 'flavor-chat-ia'); ?> *
                        </label>
                    </div>
                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="acepta_privacidad" value="1" required>
                            <?php printf(__('Acepto la %spolitica de privacidad%s', 'flavor-chat-ia'), '<a href="' . esc_url(get_privacy_policy_url()) . '" target="_blank">', '</a>'); ?> *
                        </label>
                    </div>
                    <div class="flavor-form-group flavor-checkbox-group">
                        <label>
                            <input type="checkbox" name="acepta_comunicaciones" value="1">
                            <?php esc_html_e('Acepto recibir comunicaciones sobre actividades y novedades', 'flavor-chat-ia'); ?>
                        </label>
                    </div>
                </div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                        <?php esc_html_e('Enviar Solicitud', 'flavor-chat-ia'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi perfil de socio
     */
    public function shortcode_mi_perfil($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $socio = null;
        if (Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            $socio = $wpdb->get_row($wpdb->prepare(
                "SELECT s.*, t.nombre as tipo_nombre, t.beneficios as tipo_beneficios
                 FROM {$tabla_socios} s
                 LEFT JOIN {$tabla_tipos} t ON s.tipo_membresia_id = t.id
                 WHERE s.usuario_id = %d
                 ORDER BY s.created_at DESC LIMIT 1",
                $usuario_id
            ));
        }

        if (!$socio) {
            return '<div class="flavor-empty-state"><p>' .
                __('No eres socio/a todavía.', 'flavor-chat-ia') . '</p>' .
                '<a href="' . esc_url(home_url('/socios/alta/')) . '" class="flavor-btn flavor-btn-primary">' .
                __('Hacerse Socio/a', 'flavor-chat-ia') . '</a></div>';
        }

        $estados_colores = [
            'pendiente' => 'warning',
            'activo' => 'success',
            'suspendido' => 'danger',
            'baja' => 'secondary',
        ];

        ob_start();
        ?>
        <div class="flavor-socio-perfil">
            <div class="perfil-header">
                <div class="perfil-avatar">
                    <?php echo get_avatar($usuario_id, 100); ?>
                </div>
                <div class="perfil-info">
                    <h2><?php echo esc_html($socio->nombre . ' ' . $socio->apellidos); ?></h2>
                    <p class="numero-socio">
                        <?php esc_html_e('Nº Socio:', 'flavor-chat-ia'); ?>
                        <strong><?php echo esc_html($socio->numero_socio ?? '-'); ?></strong>
                    </p>
                    <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_colores[$socio->estado] ?? 'secondary'); ?>">
                        <?php echo esc_html(ucfirst($socio->estado)); ?>
                    </span>
                </div>
            </div>

            <div class="perfil-cards">
                <div class="perfil-card">
                    <h4><?php esc_html_e('Membresía', 'flavor-chat-ia'); ?></h4>
                    <p class="tipo-membresia"><?php echo esc_html($socio->tipo_nombre ?? __('Estándar', 'flavor-chat-ia')); ?></p>
                    <?php if ($socio->fecha_alta): ?>
                        <p class="fecha-alta">
                            <small><?php esc_html_e('Socio/a desde:', 'flavor-chat-ia'); ?> <?php echo date_i18n('d/m/Y', strtotime($socio->fecha_alta)); ?></small>
                        </p>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h4><?php esc_html_e('Cuota', 'flavor-chat-ia'); ?></h4>
                    <?php if ($socio->cuota_pagada): ?>
                        <p class="cuota-estado cuota-pagada">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Cuota al día', 'flavor-chat-ia'); ?>
                        </p>
                        <?php if ($socio->fecha_vencimiento_cuota): ?>
                            <p><small><?php esc_html_e('Vence:', 'flavor-chat-ia'); ?> <?php echo date_i18n('d/m/Y', strtotime($socio->fecha_vencimiento_cuota)); ?></small></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="cuota-estado cuota-pendiente">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Cuota pendiente', 'flavor-chat-ia'); ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/mi-portal/socios/pagar-cuota/')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-primary">
                            <?php esc_html_e('Pagar', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>

                <div class="perfil-card">
                    <h4><?php esc_html_e('Carnet', 'flavor-chat-ia'); ?></h4>
                    <?php if ($socio->estado === 'activo' && $socio->cuota_pagada): ?>
                        <a href="<?php echo esc_url(home_url('/mi-portal/socios/carnet/')); ?>" class="flavor-btn flavor-btn-sm flavor-btn-secondary">
                            <span class="dashicons dashicons-id-alt"></span>
                            <?php esc_html_e('Ver Carnet', 'flavor-chat-ia'); ?>
                        </a>
                    <?php else: ?>
                        <p class="carnet-no-disponible">
                            <small><?php esc_html_e('Disponible cuando la cuota esté al día', 'flavor-chat-ia'); ?></small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="perfil-datos">
                <h3><?php esc_html_e('Mis Datos', 'flavor-chat-ia'); ?></h3>
                <form id="form-actualizar-perfil" class="flavor-form">
                    <?php wp_nonce_field('socios_nonce', 'socios_nonce_field'); ?>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-6">
                            <label><?php esc_html_e('Email', 'flavor-chat-ia'); ?></label>
                            <input type="email" name="email" value="<?php echo esc_attr($socio->email); ?>">
                        </div>
                        <div class="flavor-form-group flavor-form-col-6">
                            <label><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></label>
                            <input type="tel" name="telefono" value="<?php echo esc_attr($socio->telefono); ?>">
                        </div>
                    </div>

                    <div class="flavor-form-group">
                        <label><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></label>
                        <input type="text" name="direccion" value="<?php echo esc_attr($socio->direccion); ?>">
                    </div>

                    <div class="flavor-form-row">
                        <div class="flavor-form-group flavor-form-col-4">
                            <label><?php esc_html_e('Código Postal', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="codigo_postal" value="<?php echo esc_attr($socio->codigo_postal); ?>">
                        </div>
                        <div class="flavor-form-group flavor-form-col-8">
                            <label><?php esc_html_e('Ciudad', 'flavor-chat-ia'); ?></label>
                            <input type="text" name="ciudad" value="<?php echo esc_attr($socio->ciudad); ?>">
                        </div>
                    </div>

                    <div class="flavor-form-actions">
                        <button type="submit" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Guardar Cambios', 'flavor-chat-ia'); ?>
                        </button>
                    </div>
                </form>
            </div>

            <?php if (!empty($socio->tipo_beneficios)): ?>
                <div class="perfil-beneficios">
                    <h3><?php esc_html_e('Tus Beneficios', 'flavor-chat-ia'); ?></h3>
                    <ul class="beneficios-lista">
                        <?php foreach (explode("\n", $socio->tipo_beneficios) as $beneficio): ?>
                            <?php if (trim($beneficio)): ?>
                                <li><span class="dashicons dashicons-yes"></span> <?php echo esc_html(trim($beneficio)); ?></li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mi carnet
     */
    public function shortcode_mi_carnet($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d AND estado = 'activo'",
            $usuario_id
        ));

        if (!$socio || !$socio->cuota_pagada) {
            return '<div class="flavor-empty-state"><p>' . __('Tu carnet no está disponible. Asegúrate de que tu cuota esté al día.', 'flavor-chat-ia') . '</p></div>';
        }

        $sitio_nombre = get_bloginfo('name');
        $qr_data = home_url('/validar-socio/' . ($socio->numero_socio ?? $socio->id));

        ob_start();
        ?>
        <div class="flavor-socio-carnet">
            <div class="carnet-card">
                <div class="carnet-header">
                    <h3><?php echo esc_html($sitio_nombre); ?></h3>
                    <span class="carnet-tipo"><?php esc_html_e('Carnet de Socio/a', 'flavor-chat-ia'); ?></span>
                </div>

                <div class="carnet-body">
                    <div class="carnet-foto">
                        <?php echo get_avatar($usuario_id, 120); ?>
                    </div>
                    <div class="carnet-datos">
                        <h2><?php echo esc_html($socio->nombre . ' ' . $socio->apellidos); ?></h2>
                        <p class="carnet-numero">
                            <?php esc_html_e('Nº', 'flavor-chat-ia'); ?>
                            <strong><?php echo esc_html($socio->numero_socio ?? sprintf('%06d', $socio->id)); ?></strong>
                        </p>
                        <p class="carnet-desde">
                            <?php esc_html_e('Socio/a desde', 'flavor-chat-ia'); ?>
                            <?php echo date_i18n('Y', strtotime($socio->fecha_alta ?? $socio->created_at)); ?>
                        </p>
                    </div>
                </div>

                <div class="carnet-footer">
                    <div class="carnet-validez">
                        <?php esc_html_e('Válido hasta:', 'flavor-chat-ia'); ?>
                        <?php echo date_i18n('d/m/Y', strtotime($socio->fecha_vencimiento_cuota ?? '+1 year')); ?>
                    </div>
                    <div class="carnet-qr" data-qr="<?php echo esc_attr($qr_data); ?>">
                        <!-- QR generado por JS -->
                    </div>
                </div>
            </div>

            <div class="carnet-acciones">
                <button type="button" class="flavor-btn flavor-btn-secondary" onclick="window.print();">
                    <span class="dashicons dashicons-printer"></span>
                    <?php esc_html_e('Imprimir', 'flavor-chat-ia'); ?>
                </button>
                <button type="button" class="flavor-btn flavor-btn-primary btn-descargar-carnet">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Descargar', 'flavor-chat-ia'); ?>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Mis cuotas
     */
    public function shortcode_mis_cuotas($atts) {
        if (!is_user_logged_in()) {
            return '<p class="flavor-login-required">' . __('Debes iniciar sesión.', 'flavor-chat-ia') . '</p>';
        }

        $this->encolar_assets();
        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';
        $tabla_pagos = $wpdb->prefix . 'flavor_socios_pagos';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_socios} WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$socio) {
            return '<div class="flavor-empty-state"><p>' . __('No eres socio/a.', 'flavor-chat-ia') . '</p></div>';
        }

        $pagos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_pagos)) {
            $pagos = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$tabla_pagos} WHERE socio_id = %d ORDER BY fecha_pago DESC",
                $socio->id
            ));
        }

        $estados_pagos = [
            'pendiente' => ['color' => 'warning', 'label' => __('Pendiente', 'flavor-chat-ia')],
            'completado' => ['color' => 'success', 'label' => __('Pagado', 'flavor-chat-ia')],
            'fallido' => ['color' => 'danger', 'label' => __('Fallido', 'flavor-chat-ia')],
            'reembolsado' => ['color' => 'secondary', 'label' => __('Reembolsado', 'flavor-chat-ia')],
        ];

        ob_start();
        ?>
        <div class="flavor-socio-cuotas">
            <div class="cuotas-resumen">
                <div class="resumen-card">
                    <h4><?php esc_html_e('Estado Actual', 'flavor-chat-ia'); ?></h4>
                    <?php if ($socio->cuota_pagada): ?>
                        <p class="estado-ok">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php esc_html_e('Cuota al día', 'flavor-chat-ia'); ?>
                        </p>
                    <?php else: ?>
                        <p class="estado-pendiente">
                            <span class="dashicons dashicons-warning"></span>
                            <?php esc_html_e('Cuota pendiente', 'flavor-chat-ia'); ?>
                        </p>
                        <a href="<?php echo esc_url(home_url('/mi-portal/socios/pagar-cuota/')); ?>" class="flavor-btn flavor-btn-primary">
                            <?php esc_html_e('Pagar Ahora', 'flavor-chat-ia'); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <?php if ($socio->fecha_vencimiento_cuota): ?>
                    <div class="resumen-card">
                        <h4><?php esc_html_e('Próxima Renovación', 'flavor-chat-ia'); ?></h4>
                        <p class="fecha-renovacion"><?php echo date_i18n('d F Y', strtotime($socio->fecha_vencimiento_cuota)); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <h3><?php esc_html_e('Historial de Pagos', 'flavor-chat-ia'); ?></h3>

            <?php if (empty($pagos)): ?>
                <p class="sin-pagos"><?php esc_html_e('No hay pagos registrados.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <div class="flavor-table-responsive">
                    <table class="flavor-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Concepto', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Importe', 'flavor-chat-ia'); ?></th>
                                <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pagos as $pago): ?>
                                <tr>
                                    <td><?php echo date_i18n('d/m/Y', strtotime($pago->fecha_pago ?? $pago->created_at)); ?></td>
                                    <td><?php echo esc_html($pago->concepto ?? __('Cuota anual', 'flavor-chat-ia')); ?></td>
                                    <td><?php echo number_format_i18n($pago->importe, 2); ?> €</td>
                                    <td>
                                        <span class="flavor-badge flavor-badge-<?php echo esc_attr($estados_pagos[$pago->estado]['color'] ?? 'secondary'); ?>">
                                            <?php echo esc_html($estados_pagos[$pago->estado]['label'] ?? ucfirst($pago->estado)); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Directorio de socios
     */
    public function shortcode_directorio($atts) {
        $this->encolar_assets();

        $atts = shortcode_atts([
            'mostrar_contacto' => 'false',
            'limite' => 50,
        ], $atts);

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        if (!Flavor_Chat_Helpers::tabla_existe($tabla_socios)) {
            return '';
        }

        $socios = $wpdb->get_results($wpdb->prepare(
            "SELECT id, usuario_id, nombre, apellidos, ciudad, fecha_alta, mostrar_en_directorio
             FROM {$tabla_socios}
             WHERE estado = 'activo' AND mostrar_en_directorio = 1
             ORDER BY apellidos, nombre
             LIMIT %d",
            intval($atts['limite'])
        ));

        if (empty($socios)) {
            return '<p>' . __('No hay socios en el directorio público.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-socios-directorio">
            <div class="directorio-stats">
                <p><?php printf(esc_html__('%d socios/as activos/as', 'flavor-chat-ia'), count($socios)); ?></p>
            </div>

            <div class="directorio-grid">
                <?php foreach ($socios as $socio): ?>
                    <div class="socio-card-mini">
                        <?php echo get_avatar($socio->usuario_id, 60); ?>
                        <div class="socio-info">
                            <h4><?php echo esc_html($socio->nombre . ' ' . $socio->apellidos); ?></h4>
                            <?php if ($socio->ciudad): ?>
                                <p class="socio-ciudad"><?php echo esc_html($socio->ciudad); ?></p>
                            <?php endif; ?>
                            <p class="socio-desde">
                                <small><?php printf(esc_html__('Desde %s', 'flavor-chat-ia'), date_i18n('Y', strtotime($socio->fecha_alta))); ?></small>
                            </p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Ventajas de ser socio
     */
    public function shortcode_ventajas($atts) {
        $this->encolar_assets();

        global $wpdb;
        $tabla_tipos = $wpdb->prefix . 'flavor_socios_tipos';

        $tipos = [];
        if (Flavor_Chat_Helpers::tabla_existe($tabla_tipos)) {
            $tipos = $wpdb->get_results("SELECT * FROM {$tabla_tipos} WHERE activo = 1 ORDER BY precio");
        }

        // Ventajas generales
        $ventajas_generales = [
            ['icon' => 'tickets', 'titulo' => __('Descuentos en Actividades', 'flavor-chat-ia'), 'desc' => __('Precios especiales en todos los eventos y talleres', 'flavor-chat-ia')],
            ['icon' => 'groups', 'titulo' => __('Comunidad', 'flavor-chat-ia'), 'desc' => __('Forma parte de una red de personas con intereses comunes', 'flavor-chat-ia')],
            ['icon' => 'megaphone', 'titulo' => __('Participación', 'flavor-chat-ia'), 'desc' => __('Voz y voto en las decisiones de la asociación', 'flavor-chat-ia')],
            ['icon' => 'email', 'titulo' => __('Información Exclusiva', 'flavor-chat-ia'), 'desc' => __('Acceso anticipado a eventos y novedades', 'flavor-chat-ia')],
        ];

        ob_start();
        ?>
        <div class="flavor-socios-ventajas">
            <div class="ventajas-generales">
                <h3><?php esc_html_e('Ventajas de ser Socio/a', 'flavor-chat-ia'); ?></h3>
                <div class="ventajas-grid">
                    <?php foreach ($ventajas_generales as $ventaja): ?>
                        <div class="ventaja-item">
                            <span class="dashicons dashicons-<?php echo esc_attr($ventaja['icon']); ?>"></span>
                            <h4><?php echo esc_html($ventaja['titulo']); ?></h4>
                            <p><?php echo esc_html($ventaja['desc']); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($tipos)): ?>
                <div class="tipos-membresia-info">
                    <h3><?php esc_html_e('Tipos de Membresía', 'flavor-chat-ia'); ?></h3>
                    <div class="tipos-comparativa">
                        <?php foreach ($tipos as $tipo): ?>
                            <div class="tipo-info-card">
                                <h4><?php echo esc_html($tipo->nombre); ?></h4>
                                <p class="tipo-precio-info"><?php echo number_format_i18n($tipo->precio, 2); ?> €/<?php echo esc_html($tipo->periodo); ?></p>
                                <?php if ($tipo->beneficios): ?>
                                    <ul>
                                        <?php foreach (explode("\n", $tipo->beneficios) as $b): ?>
                                            <?php if (trim($b)): ?>
                                                <li><?php echo esc_html(trim($b)); ?></li>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="socios-cta">
                <a href="<?php echo esc_url(home_url('/socios/alta/')); ?>" class="flavor-btn flavor-btn-primary flavor-btn-lg">
                    <?php esc_html_e('Hazte Socio/a', 'flavor-chat-ia'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode: Estadísticas
     */
    public function shortcode_estadisticas($atts) {
        $this->encolar_assets();

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $total_socios = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo'");
        $nuevos_mes = $wpdb->get_var("SELECT COUNT(*) FROM {$tabla_socios} WHERE estado = 'activo' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");

        ob_start();
        ?>
        <div class="flavor-socios-stats-public">
            <div class="stat-big">
                <span class="stat-numero"><?php echo number_format_i18n($total_socios); ?></span>
                <span class="stat-texto"><?php esc_html_e('Socios/as activos/as', 'flavor-chat-ia'); ?></span>
            </div>
            <?php if ($nuevos_mes > 0): ?>
                <div class="stat-secundario">
                    <span>+<?php echo number_format_i18n($nuevos_mes); ?></span>
                    <span><?php esc_html_e('nuevos este mes', 'flavor-chat-ia'); ?></span>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // ================================
    // AJAX HANDLERS
    // ================================

    /**
     * AJAX: Solicitar alta
     */
    public function ajax_solicitar_alta() {
        check_ajax_referer('socios_nonce', 'socios_nonce_field');

        $nombre = isset($_POST['nombre']) ? sanitize_text_field($_POST['nombre']) : '';
        $apellidos = isset($_POST['apellidos']) ? sanitize_text_field($_POST['apellidos']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $telefono = isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '';
        $dni = isset($_POST['dni']) ? sanitize_text_field($_POST['dni']) : '';
        $direccion = isset($_POST['direccion']) ? sanitize_text_field($_POST['direccion']) : '';
        $ciudad = isset($_POST['ciudad']) ? sanitize_text_field($_POST['ciudad']) : '';
        $codigo_postal = isset($_POST['codigo_postal']) ? sanitize_text_field($_POST['codigo_postal']) : '';
        $fecha_nacimiento = isset($_POST['fecha_nacimiento']) ? sanitize_text_field($_POST['fecha_nacimiento']) : null;
        $tipo_membresia_id = isset($_POST['tipo_membresia_id']) ? absint($_POST['tipo_membresia_id']) : null;
        $acepta_comunicaciones = isset($_POST['acepta_comunicaciones']) ? 1 : 0;

        if (empty($nombre) || empty($apellidos) || empty($email)) {
            wp_send_json_error(__('Los campos nombre, apellidos y email son obligatorios', 'flavor-chat-ia'));
        }

        if (!is_email($email)) {
            wp_send_json_error(__('El email no es válido', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        // Crear usuario si no está logueado
        $usuario_id = get_current_user_id();
        if (!$usuario_id) {
            $password = isset($_POST['password']) ? $_POST['password'] : '';
            if (empty($password)) {
                wp_send_json_error(__('La contraseña es obligatoria', 'flavor-chat-ia'));
            }

            if (email_exists($email)) {
                wp_send_json_error(__('Este email ya está registrado', 'flavor-chat-ia'));
            }

            $usuario_id = wp_create_user($email, $password, $email);
            if (is_wp_error($usuario_id)) {
                wp_send_json_error($usuario_id->get_error_message());
            }

            wp_update_user([
                'ID' => $usuario_id,
                'first_name' => $nombre,
                'last_name' => $apellidos,
            ]);
        }

        // Verificar si ya es socio
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_socios} WHERE usuario_id = %d OR email = %s",
            $usuario_id,
            $email
        ));

        if ($existe) {
            wp_send_json_error(__('Ya existe una solicitud o membresía con estos datos', 'flavor-chat-ia'));
        }

        // Generar número de socio
        $ultimo_numero = $wpdb->get_var("SELECT MAX(CAST(numero_socio AS UNSIGNED)) FROM {$tabla_socios}");
        $nuevo_numero = sprintf('%06d', ($ultimo_numero ?? 0) + 1);

        $resultado = $wpdb->insert($tabla_socios, [
            'usuario_id' => $usuario_id,
            'numero_socio' => $nuevo_numero,
            'nombre' => $nombre,
            'apellidos' => $apellidos,
            'email' => $email,
            'telefono' => $telefono,
            'dni' => $dni,
            'direccion' => $direccion,
            'ciudad' => $ciudad,
            'codigo_postal' => $codigo_postal,
            'fecha_nacimiento' => $fecha_nacimiento ?: null,
            'tipo_membresia_id' => $tipo_membresia_id ?: null,
            'estado' => 'pendiente',
            'acepta_comunicaciones' => $acepta_comunicaciones,
            'created_at' => current_time('mysql'),
        ]);

        if ($resultado) {
            do_action('socio_solicitud_created', $wpdb->insert_id, $usuario_id);

            wp_send_json_success([
                'mensaje' => __('Solicitud enviada correctamente. Te contactaremos pronto.', 'flavor-chat-ia'),
                'redirect' => home_url('/mi-portal/'),
            ]);
        } else {
            wp_send_json_error(__('Error al procesar la solicitud', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Actualizar perfil
     */
    public function ajax_actualizar_perfil() {
        check_ajax_referer('socios_nonce', 'socios_nonce_field');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        $usuario_id = get_current_user_id();

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $datos = [
            'email' => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
            'telefono' => isset($_POST['telefono']) ? sanitize_text_field($_POST['telefono']) : '',
            'direccion' => isset($_POST['direccion']) ? sanitize_text_field($_POST['direccion']) : '',
            'ciudad' => isset($_POST['ciudad']) ? sanitize_text_field($_POST['ciudad']) : '',
            'codigo_postal' => isset($_POST['codigo_postal']) ? sanitize_text_field($_POST['codigo_postal']) : '',
            'updated_at' => current_time('mysql'),
        ];

        $resultado = $wpdb->update($tabla_socios, $datos, ['usuario_id' => $usuario_id]);

        if ($resultado !== false) {
            wp_send_json_success(['mensaje' => __('Perfil actualizado', 'flavor-chat-ia')]);
        } else {
            wp_send_json_error(__('Error al actualizar', 'flavor-chat-ia'));
        }
    }

    /**
     * AJAX: Renovar cuota
     */
    public function ajax_renovar_cuota() {
        check_ajax_referer('socios_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('Debes iniciar sesión', 'flavor-chat-ia'));
        }

        // Redirigir a página de pago
        wp_send_json_success([
            'redirect' => home_url('/mi-portal/socios/pagar-cuota/'),
        ]);
    }

    /**
     * AJAX: Obtener estado
     */
    public function ajax_obtener_estado() {
        if (!is_user_logged_in()) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        global $wpdb;
        $tabla_socios = $wpdb->prefix . 'flavor_socios';

        $socio = $wpdb->get_row($wpdb->prepare(
            "SELECT estado, cuota_pagada, fecha_vencimiento_cuota FROM {$tabla_socios} WHERE usuario_id = %d",
            get_current_user_id()
        ));

        if ($socio) {
            wp_send_json_success([
                'es_socio' => true,
                'estado' => $socio->estado,
                'cuota_pagada' => (bool) $socio->cuota_pagada,
                'fecha_vencimiento' => $socio->fecha_vencimiento_cuota,
            ]);
        } else {
            wp_send_json_success(['es_socio' => false]);
        }
    }

    /**
     * AJAX: Descargar carnet
     */
    public function ajax_descargar_carnet() {
        check_ajax_referer('socios_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(__('No autorizado', 'flavor-chat-ia'));
        }

        // Aquí se generaría el PDF del carnet
        // Por ahora solo devolvemos la URL del carnet digital
        wp_send_json_success([
            'url' => home_url('/mi-portal/socios/carnet/?print=1'),
        ]);
    }
}
