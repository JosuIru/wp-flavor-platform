<?php
/**
 * Página de configuración del Asistente IA
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Platform_Settings {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = FLAVOR_PLATFORM_TEXT_DOMAIN;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Platform_Settings
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Menú registrado centralmente por Flavor_Admin_Menu_Manager
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_init', [$this, 'handle_form_submission']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_chat_autoconfig', [$this, 'ajax_autoconfig']);
        add_action('wp_ajax_flavor_chat_get_analytics', [$this, 'ajax_get_analytics']);
        add_action('wp_ajax_flavor_test_push_notification', [$this, 'ajax_test_push_notification']);
        add_action('wp_ajax_flavor_test_ia_connection', [$this, 'ajax_test_ia_connection']);
    }

    /**
     * Maneja el envío del formulario de settings
     */
    public function handle_form_submission() {
        $action = $_POST['flavor_chat_ia_action'] ?? $_POST['flavor_platform_action'] ?? '';
        if ($action !== 'save_settings') {
            return;
        }

        $legacy_nonce   = $_POST['flavor_platform_nonce'] ?? '';
        $platform_nonce = $_POST['flavor_platform_nonce'] ?? '';
        $valid_nonce    = wp_verify_nonce($legacy_nonce, 'flavor_platform_settings_save')
            || wp_verify_nonce($platform_nonce, 'flavor_platform_settings_save');

        if (!$valid_nonce) {
            wp_die(__('Nonce inválido', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        if (!current_user_can('manage_options')) {
            wp_die(__('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN));
        }

        // Sanitizar y guardar
        $input = $_POST['flavor_chat_ia_settings'] ?? $_POST['flavor_platform_settings'] ?? [];
        if (empty($input['_tab']) && !empty($_POST['current_tab'])) {
            $input['_tab'] = sanitize_text_field($_POST['current_tab']);
        }
        $sanitized = $this->sanitize_settings($input);
        flavor_update_main_settings($sanitized);

        // Redirigir de vuelta
        $tab = sanitize_text_field($_POST['current_tab'] ?? 'general');
        $redirect_url = admin_url('admin.php?page=flavor-platform-settings&tab=' . $tab . '&settings-updated=true');

        if (!headers_sent()) {
            wp_safe_redirect($redirect_url);
            exit;
        }

        // Fallback defensivo si algún warning externo ya envió salida.
        echo '<meta http-equiv="refresh" content="0;url=' . esc_url($redirect_url) . '">';
        echo '<script>window.location.href=' . wp_json_encode($redirect_url) . ';</script>';
        exit;
    }

    /**
     * Añade el menú de administración
     */
    public function add_menu() {
        add_menu_page(
            __('Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page'],
            'dashicons-superhero-alt',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Configuración', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Escalados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            'flavor-platform-escalations',
            [$this, 'render_escalations_page']
        );
    }

    /**
     * Registra los settings
     */
    public function register_settings() {
        register_setting('flavor_chat_ia_settings', 'flavor_chat_ia_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);

        register_setting('flavor_platform_settings', 'flavor_platform_settings', [
            'sanitize_callback' => [$this, 'sanitize_settings'],
        ]);
    }

    /**
     * Guarda la configuración y verifica persistencia real.
     * Si falla por caché/filtros externos, fuerza actualización en wp_options.
     *
     * @param array $sanitized
     * @return void
     */
    private function save_settings_reliably($sanitized) {
        flavor_update_main_settings($sanitized);

        // Limpiar caché de opciones antes de verificar
        wp_cache_delete(flavor_get_primary_settings_option(), 'options');
        wp_cache_delete(FLAVOR_PLATFORM_SETTINGS_OPTION, 'options');
        wp_cache_delete('alloptions', 'options');

        $stored = flavor_get_main_settings();
        if ($stored === $sanitized) {
            return;
        }

        // Fallback robusto: escritura directa en BD
        global $wpdb;
        $serialized = maybe_serialize($sanitized);
        $wpdb->update(
            $wpdb->options,
            ['option_value' => $serialized],
            ['option_name' => flavor_get_primary_settings_option()],
            ['%s'],
            ['%s']
        );

        $wpdb->replace(
            $wpdb->options,
            [
                'option_name'  => FLAVOR_PLATFORM_SETTINGS_OPTION,
                'option_value' => $serialized,
                'autoload'     => 'yes',
            ],
            ['%s', '%s', '%s']
        );

        wp_cache_delete(flavor_get_primary_settings_option(), 'options');
        wp_cache_delete(FLAVOR_PLATFORM_SETTINGS_OPTION, 'options');
        wp_cache_delete('alloptions', 'options');
    }

    /**
     * Sanitiza los settings
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $existing = flavor_get_main_settings();
        $sanitized = $existing;

        $current_tab = $input['_tab'] ?? '';

        // Pestaña General
        if ($current_tab === 'general') {
            $sanitized['enabled'] = !empty($input['enabled']);
            $sanitized['admin_only'] = !empty($input['admin_only']);
            $sanitized['show_floating_widget'] = !empty($input['show_floating_widget']);
            $sanitized['assistant_name'] = sanitize_text_field($input['assistant_name'] ?? 'Asistente Virtual');
            $sanitized['assistant_role'] = sanitize_textarea_field($input['assistant_role'] ?? '');
            $sanitized['tone'] = sanitize_text_field($input['tone'] ?? 'friendly');
            $sanitized['max_messages_per_session'] = absint($input['max_messages_per_session'] ?? 50);

            $allowed_languages = ['es', 'eu', 'en', 'fr', 'ca'];
            $sanitized['languages'] = isset($input['languages'])
                ? array_values(array_intersect($input['languages'], $allowed_languages))
                : ['es'];
        }

        // Pestaña Proveedores IA
        if ($current_tab === 'providers') {
            $valid_providers = ['claude', 'openai', 'deepseek', 'mistral'];
            $sanitized['active_provider'] = in_array($input['active_provider'] ?? 'claude', $valid_providers)
                ? $input['active_provider']
                : 'claude';

            // Encriptar API keys antes de guardar (seguridad)
            $encryption = class_exists('Flavor_API_Key_Encryption')
                ? Flavor_API_Key_Encryption::get_instance()
                : null;

            $api_key_fields = [
                'api_key' => $input['api_key'] ?? $existing['api_key'] ?? '',
                'claude_api_key' => $input['claude_api_key'] ?? '',
                'openai_api_key' => $input['openai_api_key'] ?? '',
                'deepseek_api_key' => $input['deepseek_api_key'] ?? '',
                'mistral_api_key' => $input['mistral_api_key'] ?? '',
            ];

            foreach ($api_key_fields as $field => $value) {
                $raw_value = is_string($value) ? wp_unslash($value) : '';
                $sanitized_value = trim(str_replace(["\r", "\n", "\t"], '', $raw_value));

                // Si el valor está vacío y ya existía uno encriptado, mantener el existente
                if (empty($sanitized_value) && !empty($existing[$field])) {
                    $sanitized[$field] = $existing[$field];
                } else if (!empty($sanitized_value) && strpos($sanitized_value, 'enc:v1:') === 0) {
                    // Nunca re-encriptar un valor ya cifrado (evita doble cifrado accidental).
                    $sanitized[$field] = $sanitized_value;
                } else if (!empty($sanitized_value)) {
                    if ($encryption) {
                        $encrypted_value = $encryption->encrypt($sanitized_value);
                        $decrypted_check = $encryption->decrypt($encrypted_value);
                        // Fallback: si no se puede recuperar exactamente, guardar en claro para no romper uso.
                        $sanitized[$field] = ($decrypted_check === $sanitized_value)
                            ? $encrypted_value
                            : $sanitized_value;
                    } else {
                        $sanitized[$field] = $sanitized_value;
                    }
                } else {
                    $sanitized[$field] = '';
                }
            }

            $sanitized['claude_model'] = sanitize_text_field($input['claude_model'] ?? 'claude-sonnet-4-20250514');
            $sanitized['openai_model'] = sanitize_text_field($input['openai_model'] ?? 'gpt-4o-mini');
            $sanitized['deepseek_model'] = sanitize_text_field($input['deepseek_model'] ?? 'deepseek-chat');
            $sanitized['mistral_model'] = sanitize_text_field($input['mistral_model'] ?? 'mistral-small-latest');

            $sanitized['max_tokens_per_message'] = absint($input['max_tokens_per_message'] ?? 1000);

            // Figma Personal Token (para Visual Builder Pro)
            $figma_token_value = $input['figma_personal_token'] ?? '';
            $sanitized_figma = sanitize_text_field($figma_token_value);
            if (empty($sanitized_figma) && !empty($existing['figma_personal_token'])) {
                $sanitized['figma_personal_token'] = $existing['figma_personal_token'];
            } else if (!empty($sanitized_figma)) {
                if ($encryption) {
                    $encrypted_figma = $encryption->encrypt($sanitized_figma);
                    $decrypted_figma = $encryption->decrypt($encrypted_figma);
                    $sanitized['figma_personal_token'] = ($decrypted_figma === $sanitized_figma)
                        ? $encrypted_figma
                        : $sanitized_figma;
                } else {
                    $sanitized['figma_personal_token'] = $sanitized_figma;
                }
            } else {
                $sanitized['figma_personal_token'] = '';
            }

            // Configuración por contexto (frontend/backend)
            $valid_providers_with_default = ['default', 'claude', 'openai', 'deepseek', 'mistral'];

            // Frontend (chat público)
            $sanitized['ia_provider_frontend'] = in_array($input['ia_provider_frontend'] ?? 'default', $valid_providers_with_default)
                ? $input['ia_provider_frontend']
                : 'default';
            $sanitized['ia_model_frontend'] = sanitize_text_field($input['ia_model_frontend'] ?? '');

            // Backend (admin assistant)
            $sanitized['ia_provider_backend'] = in_array($input['ia_provider_backend'] ?? 'default', $valid_providers_with_default)
                ? $input['ia_provider_backend']
                : 'default';
            $sanitized['ia_model_backend'] = sanitize_text_field($input['ia_model_backend'] ?? '');

            // Orden de fallback
            $fallback_order_raw = $input['fallback_order'] ?? '';
            if (!empty($fallback_order_raw)) {
                $fallback_order = array_map('sanitize_key', explode(',', $fallback_order_raw));
                $fallback_order = array_filter($fallback_order, function($p) use ($valid_providers) {
                    return in_array($p, $valid_providers);
                });
                $sanitized['fallback_order'] = array_values($fallback_order);
            }
        }

        // Pestaña Apariencia
        if ($current_tab === 'appearance') {
            $sanitized['appearance'] = [
                'primary_color' => sanitize_hex_color($input['appearance']['primary_color'] ?? '#0073aa'),
                'header_bg' => sanitize_hex_color($input['appearance']['header_bg'] ?? '#1e3a5f'),
                'user_bubble' => sanitize_hex_color($input['appearance']['user_bubble'] ?? '#0073aa'),
                'assistant_bubble' => sanitize_hex_color($input['appearance']['assistant_bubble'] ?? '#f0f0f0'),
                'position' => in_array($input['appearance']['position'] ?? '', ['bottom-right', 'bottom-left'])
                    ? $input['appearance']['position'] : 'bottom-right',
                'widget_width' => min(500, max(300, absint($input['appearance']['widget_width'] ?? 380))),
                'widget_height' => min(700, max(400, absint($input['appearance']['widget_height'] ?? 500))),
                'border_radius' => min(30, max(0, absint($input['appearance']['border_radius'] ?? 16))),
                'welcome_message' => sanitize_textarea_field($input['appearance']['welcome_message'] ?? ''),
                'placeholder' => sanitize_text_field($input['appearance']['placeholder'] ?? ''),
                'avatar_url' => esc_url_raw($input['appearance']['avatar_url'] ?? ''),
                'bottom_offset' => min(200, max(10, absint($input['appearance']['bottom_offset'] ?? 20))),
                'side_offset' => min(200, max(10, absint($input['appearance']['side_offset'] ?? 20))),
                'trigger_size' => in_array($input['appearance']['trigger_size'] ?? '', ['small', 'medium', 'large'])
                    ? $input['appearance']['trigger_size'] : 'medium',
                'trigger_animation' => in_array($input['appearance']['trigger_animation'] ?? '', ['none', 'pulse', 'bounce'])
                    ? $input['appearance']['trigger_animation'] : 'pulse',
            ];
        }

        // Pestaña Acciones Rápidas
        if ($current_tab === 'quick_actions') {
            $sanitized['quick_actions'] = [];
            $allowed_ids = ['products', 'orders', 'shipping', 'returns', 'contact'];
            $allowed_icons = ['cart', 'package', 'truck', 'refresh', 'phone', 'question', 'star', 'info'];

            if (!empty($input['quick_actions']) && is_array($input['quick_actions'])) {
                foreach ($input['quick_actions'] as $id => $action) {
                    if (in_array($id, $allowed_ids)) {
                        $sanitized['quick_actions'][$id] = [
                            'enabled' => !empty($action['enabled']),
                            'icon' => in_array($action['icon'] ?? '', $allowed_icons) ? $action['icon'] : 'info',
                            'label' => sanitize_text_field($action['label'] ?? ''),
                            'prompt' => sanitize_text_field($action['prompt'] ?? ''),
                        ];
                    }
                }
            }

            $sanitized['custom_quick_actions'] = [];
            if (!empty($input['custom_quick_actions']) && is_array($input['custom_quick_actions'])) {
                foreach ($input['custom_quick_actions'] as $action) {
                    if (!empty($action['label']) && !empty($action['prompt'])) {
                        $sanitized['custom_quick_actions'][] = [
                            'label' => sanitize_text_field($action['label']),
                            'prompt' => sanitize_text_field($action['prompt']),
                        ];
                    }
                }
            }
        }

        // Pestaña Base de Conocimiento
        if ($current_tab === 'knowledge') {
            $sanitized['business_info'] = [
                'name' => sanitize_text_field($input['business_info']['name'] ?? ''),
                'address' => sanitize_text_field($input['business_info']['address'] ?? ''),
                'phone' => sanitize_text_field($input['business_info']['phone'] ?? ''),
                'email' => sanitize_email($input['business_info']['email'] ?? ''),
                'schedule' => sanitize_text_field($input['business_info']['schedule'] ?? ''),
                'description' => sanitize_textarea_field($input['business_info']['description'] ?? ''),
            ];

            $sanitized['faqs'] = [];
            if (!empty($input['faqs']) && is_array($input['faqs'])) {
                foreach ($input['faqs'] as $faq) {
                    if (!empty($faq['question']) || !empty($faq['answer'])) {
                        $sanitized['faqs'][] = [
                            'question' => sanitize_text_field($faq['question'] ?? ''),
                            'answer' => sanitize_textarea_field($faq['answer'] ?? ''),
                        ];
                    }
                }
            }

            $sanitized['policies'] = [
                'shipping' => sanitize_textarea_field($input['policies']['shipping'] ?? ''),
                'returns' => sanitize_textarea_field($input['policies']['returns'] ?? ''),
                'privacy' => sanitize_textarea_field($input['policies']['privacy'] ?? ''),
            ];

            // Temas del negocio para antispam on-topic
            $sanitized['business_topics'] = array_filter(array_map(
                'sanitize_text_field',
                explode(',', $input['business_topics'] ?? '')
            ));

            // Invalidar cache
            if (class_exists('Flavor_Platform_Knowledge_Base')) {
                Flavor_Platform_Knowledge_Base::get_instance()->invalidate_cache();
            }
        }

        // Pestaña Escalado
        if ($current_tab === 'escalation') {
            $sanitized['escalation_whatsapp'] = sanitize_text_field($input['escalation_whatsapp'] ?? '');
            $sanitized['escalation_phone'] = sanitize_text_field($input['escalation_phone'] ?? '');
            $sanitized['escalation_email'] = sanitize_email($input['escalation_email'] ?? '');
            $sanitized['escalation_hours'] = sanitize_text_field($input['escalation_hours'] ?? '');
        }

        // Pestaña Módulos

        // Firebase Push Notifications
        if ($current_tab === 'firebase_push') {
            $firebase_config = get_option('flavor_firebase_config', []);
            $firebase_config['project_id'] = sanitize_text_field($input['flavor_firebase_project_id'] ?? '');
            $firebase_config['service_account_json'] = wp_unslash($input['flavor_firebase_service_account'] ?? '');
            update_option('flavor_firebase_config', $firebase_config);
        }

        if ($current_tab === 'modules') {
            $get_module_post = static function ($module_id) {
                $platform_key = 'flavor_platform_module_' . $module_id;
                $legacy_key = 'flavor_chat_ia_module_' . $module_id;

                if (isset($_POST[$platform_key]) && is_array($_POST[$platform_key])) {
                    return $_POST[$platform_key];
                }

                if (isset($_POST[$legacy_key]) && is_array($_POST[$legacy_key])) {
                    return $_POST[$legacy_key];
                }

                return null;
            };

            $valid_modules = ['woocommerce', 'banco_tiempo', 'grupos_consumo', 'marketplace', 'facturas', 'fichaje_empleados', 'eventos', 'socios', 'incidencias', 'participacion', 'presupuestos_participativos', 'avisos_municipales', 'advertising', 'ayuda_vecinal', 'biblioteca', 'bicicletas_compartidas', 'carpooling', 'chat_grupos', 'chat_interno', 'compostaje', 'energia_comunitaria', 'cursos', 'empresarial', 'espacios_comunes', 'huertos_urbanos', 'multimedia', 'parkings', 'podcast', 'radio', 'reciclaje', 'red_social', 'talleres', 'tramites', 'transparencia', 'colectivos', 'foros', 'clientes', 'comunidades', 'bares', 'trading_ia', 'dex_solana', 'themacle'];
            $sanitized['active_modules'] = isset($input['active_modules']) && is_array($input['active_modules'])
                ? array_values(array_intersect($input['active_modules'], $valid_modules))
                : [];
            // No escribir a 'flavor_active_modules' - usar solo 'flavor_chat_ia_settings'

            // Auto-asignar rol admin del modulo al usuario que activa
            $prev_modules = isset($existing['active_modules']) && is_array($existing['active_modules'])
                ? $existing['active_modules']
                : [];
            $activated = array_diff($sanitized['active_modules'], $prev_modules);
            if (!empty($activated) && class_exists('Flavor_Permission_Helper')) {
                $user_id = get_current_user_id();
                if ($user_id) {
                    foreach ($activated as $module_slug) {
                        Flavor_Permission_Helper::assign_module_admin_to_user($user_id, $module_slug);
                    }
                }
            }

            // Guardar configuraciones específicas de cada módulo
            $banco_tiempo_post = $get_module_post('banco_tiempo');
            if ($banco_tiempo_post !== null) {
                $banco_tiempo_config = [
                    'hora_minima_intercambio' => max(0.5, floatval($banco_tiempo_post['hora_minima_intercambio'] ?? 0.5)),
                    'hora_maxima_intercambio' => max(1, floatval($banco_tiempo_post['hora_maxima_intercambio'] ?? 8)),
                    'requiere_validacion' => !empty($banco_tiempo_post['requiere_validacion']),
                    'saldo_inicial_horas' => max(0, absint($banco_tiempo_post['saldo_inicial_horas'] ?? 5)),
                    'permite_saldo_negativo' => !empty($banco_tiempo_post['permite_saldo_negativo']),
                    'limite_saldo_negativo' => min(0, intval($banco_tiempo_post['limite_saldo_negativo'] ?? -10)),
                    'notificar_saldo_bajo' => !empty($banco_tiempo_post['notificar_saldo_bajo']),
                    'umbral_notificacion_saldo' => max(0, absint($banco_tiempo_post['umbral_notificacion_saldo'] ?? 2)),
                ];
                flavor_update_module_settings('banco_tiempo', $banco_tiempo_config);
            }

            $grupos_consumo_post = $get_module_post('grupos_consumo');
            if ($grupos_consumo_post !== null) {
                $grupos_config = [
                    'dias_para_pedido' => max(1, absint($grupos_consumo_post['dias_para_pedido'] ?? 7)),
                    'pedido_minimo' => max(0, floatval($grupos_consumo_post['pedido_minimo'] ?? 0)),
                    'participantes_minimos' => max(2, absint($grupos_consumo_post['participantes_minimos'] ?? 5)),
                    'permite_multiples_pedidos' => !empty($grupos_consumo_post['permite_multiples_pedidos']),
                    'coordina_reparto' => !empty($grupos_consumo_post['coordina_reparto']),
                    'requiere_pago_anticipado' => !empty($grupos_consumo_post['requiere_pago_anticipado']),
                    'porcentaje_gastos_gestion' => max(0, min(20, floatval($grupos_consumo_post['porcentaje_gastos_gestion'] ?? 0))),
                ];
                flavor_update_module_settings('grupos_consumo', $grupos_config);
            }

            $marketplace_post = $get_module_post('marketplace');
            if ($marketplace_post !== null) {
                $marketplace_config = [
                    'permite_venta' => !empty($marketplace_post['permite_venta']),
                    'permite_intercambio' => !empty($marketplace_post['permite_intercambio']),
                    'permite_regalo' => !empty($marketplace_post['permite_regalo']),
                    'requiere_moderacion' => !empty($marketplace_post['requiere_moderacion']),
                    'dias_vigencia_anuncio' => max(7, min(90, absint($marketplace_post['dias_vigencia_anuncio'] ?? 30))),
                    'max_fotos_por_anuncio' => max(1, min(10, absint($marketplace_post['max_fotos_por_anuncio'] ?? 5))),
                    'permite_reservas' => !empty($marketplace_post['permite_reservas']),
                ];
                flavor_update_module_settings('marketplace', $marketplace_config);
            }

            $woocommerce_post = $get_module_post('woocommerce');
            if ($woocommerce_post !== null) {
                $woocommerce_config = [
                    'mostrar_stock' => !empty($woocommerce_post['mostrar_stock']),
                    'limite_productos_busqueda' => max(5, min(50, absint($woocommerce_post['limite_productos_busqueda'] ?? 10))),
                ];
                flavor_update_module_settings('woocommerce', $woocommerce_config);
            }

            $facturas_post = $get_module_post('facturas');
            if ($facturas_post !== null) {
                $facturas_config = [
                    'serie_predeterminada' => strtoupper(sanitize_text_field($facturas_post['serie_predeterminada'] ?? 'F')),
                    'iva_predeterminado' => max(0, min(100, absint($facturas_post['iva_predeterminado'] ?? 21))),
                    'enviar_email_automatico' => !empty($facturas_post['enviar_email_automatico']),
                    'formato_numero_factura' => sanitize_text_field($facturas_post['formato_numero_factura'] ?? 'SERIE-YYYY-NNNN'),
                    'prefijo_rectificativa' => strtoupper(sanitize_text_field($facturas_post['prefijo_rectificativa'] ?? 'R')),
                    'dias_vencimiento_predeterminado' => max(0, min(180, absint($facturas_post['dias_vencimiento_predeterminado'] ?? 30))),
                    'texto_pie_factura' => sanitize_textarea_field($facturas_post['texto_pie_factura'] ?? ''),
                ];
                flavor_update_module_settings('facturas', $facturas_config);
            }

            $fichaje_post = $get_module_post('fichaje_empleados');
            if ($fichaje_post !== null) {
                $fichaje_config = [
                    'horario_entrada' => sanitize_text_field($fichaje_post['horario_entrada'] ?? '09:00'),
                    'horario_salida' => sanitize_text_field($fichaje_post['horario_salida'] ?? '18:00'),
                    'tiempo_gracia' => max(0, min(60, absint($fichaje_post['tiempo_gracia'] ?? 15))),
                    'requiere_geolocalizacion' => !empty($fichaje_post['requiere_geolocalizacion']),
                    'radio_geolocalizacion_metros' => max(10, min(1000, absint($fichaje_post['radio_geolocalizacion_metros'] ?? 100))),
                    'permite_multiples_entradas' => !empty($fichaje_post['permite_multiples_entradas']),
                    'genera_informes_mensuales' => !empty($fichaje_post['genera_informes_mensuales']),
                ];
                flavor_update_module_settings('fichaje_empleados', $fichaje_config);
            }

            $eventos_post = $get_module_post('eventos');
            if ($eventos_post !== null) {
                $eventos_config = [
                    'requiere_aprobacion' => !empty($eventos_post['requiere_aprobacion']),
                    'permite_invitados' => !empty($eventos_post['permite_invitados']),
                    'dias_recordatorio' => max(0, min(7, absint($eventos_post['dias_recordatorio'] ?? 1))),
                ];
                flavor_update_module_settings('eventos', $eventos_config);
            }

            $socios_post = $get_module_post('socios');
            if ($socios_post !== null) {
                $socios_config = [
                    'cuota_mensual' => max(0, floatval($socios_post['cuota_mensual'] ?? 30.00)),
                    'cuota_anual' => max(0, floatval($socios_post['cuota_anual'] ?? 300.00)),
                    'dia_cargo' => max(1, min(28, absint($socios_post['dia_cargo'] ?? 1))),
                    'permite_cuota_reducida' => !empty($socios_post['permite_cuota_reducida']),
                ];
                flavor_update_module_settings('socios', $socios_config);
            }

            $incidencias_post = $get_module_post('incidencias');
            if ($incidencias_post !== null) {
                $incidencias_config = [
                    'requiere_ubicacion_gps' => !empty($incidencias_post['requiere_ubicacion_gps']),
                    'requiere_foto' => !empty($incidencias_post['requiere_foto']),
                    'visibilidad_publica' => !empty($incidencias_post['visibilidad_publica']),
                    'votos_para_urgencia' => max(1, min(50, absint($incidencias_post['votos_para_urgencia'] ?? 5))),
                ];
                flavor_update_module_settings('incidencias', $incidencias_config);
            }

            $participacion_post = $get_module_post('participacion');
            if ($participacion_post !== null) {
                $participacion_config = [
                    'requiere_verificacion' => !empty($participacion_post['requiere_verificacion']),
                    'moderacion_propuestas' => !empty($participacion_post['moderacion_propuestas']),
                    'votos_necesarios_propuesta' => max(5, min(500, absint($participacion_post['votos_necesarios_propuesta'] ?? 10))),
                    'duracion_votacion_dias' => max(1, min(30, absint($participacion_post['duracion_votacion_dias'] ?? 7))),
                ];
                flavor_update_module_settings('participacion', $participacion_config);
            }

            $presupuestos_post = $get_module_post('presupuestos_participativos');
            if ($presupuestos_post !== null) {
                $presupuestos_config = [
                    'presupuesto_anual' => max(0, floatval($presupuestos_post['presupuesto_anual'] ?? 50000.00)),
                    'votos_maximos_por_persona' => max(1, min(10, absint($presupuestos_post['votos_maximos_por_persona'] ?? 3))),
                    'proyecto_monto_minimo' => max(0, floatval($presupuestos_post['proyecto_monto_minimo'] ?? 1000.00)),
                    'proyecto_monto_maximo' => max(0, floatval($presupuestos_post['proyecto_monto_maximo'] ?? 15000.00)),
                ];
                flavor_update_module_settings('presupuestos_participativos', $presupuestos_config);
            }

            $avisos_post = $get_module_post('avisos_municipales');
            if ($avisos_post !== null) {
                $avisos_config = [
                    'enviar_push_notifications' => !empty($avisos_post['enviar_push_notifications']),
                    'requiere_confirmacion_lectura' => !empty($avisos_post['requiere_confirmacion_lectura']),
                ];
                flavor_update_module_settings('avisos_municipales', $avisos_config);
            }

            // Guardado genérico para módulos sin handler específico
            $modulos_con_handler = ['banco_tiempo', 'grupos_consumo', 'marketplace', 'woocommerce', 'facturas', 'fichaje_empleados', 'eventos', 'socios', 'incidencias', 'participacion', 'presupuestos_participativos', 'avisos_municipales'];
            $modulos_restantes = array_diff($valid_modules, $modulos_con_handler);

            foreach ($modulos_restantes as $modulo_id_generico) {
                $configuracion_post = $get_module_post($modulo_id_generico);
                if ($configuracion_post !== null) {
                    $configuracion_modulo = [];
                    foreach ($configuracion_post as $clave_config => $valor_config) {
                        $clave_limpia = sanitize_key($clave_config);
                        if (is_array($valor_config)) {
                            $configuracion_modulo[$clave_limpia] = array_map('sanitize_text_field', $valor_config);
                        } elseif (is_numeric($valor_config)) {
                            $configuracion_modulo[$clave_limpia] = floatval($valor_config);
                        } else {
                            $configuracion_modulo[$clave_limpia] = sanitize_text_field($valor_config);
                        }
                    }
                    // Merge con settings existentes para no perder checkboxes desmarcados
                    $existentes = flavor_get_module_settings($modulo_id_generico);
                    // Checkboxes no marcados no envían POST, así que los dejamos en false
                    foreach ($existentes as $clave_existente => $valor_existente) {
                        if (is_bool($valor_existente) || ($valor_existente === 1 || $valor_existente === 0)) {
                            if (!isset($configuracion_modulo[$clave_existente])) {
                                $configuracion_modulo[$clave_existente] = false;
                            } else {
                                $configuracion_modulo[$clave_existente] = !empty($configuracion_modulo[$clave_existente]);
                            }
                        }
                    }
                    flavor_update_module_settings($modulo_id_generico, $configuracion_modulo);
                }
            }
        }

        // Pestaña Avanzado
        if ($current_tab === 'advanced') {
            $sanitized['limpiar_al_desinstalar'] = !empty($input['limpiar_al_desinstalar']);
            $sanitized['debug_mode'] = !empty($input['debug_mode']);
        }

        unset($sanitized['_tab']);
        return $sanitized;
    }

    /**
     * Encola assets de admin
     */
    public function enqueue_admin_assets($hook) {
        // Cargar en páginas de configuración del chat
        if (
            strpos($hook, FLAVOR_PLATFORM_TEXT_DOMAIN) === false &&
            strpos($hook, 'flavor-chat-config') === false &&
            strpos($hook, 'flavor-platform-settings') === false &&
            strpos($hook, 'flavor-platform-escalations') === false
        ) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_media();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-chat-ia-admin',
            FLAVOR_PLATFORM_URL . "admin/css/admin{$sufijo_asset}.css",
            [],
            FLAVOR_PLATFORM_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-ia-admin',
            FLAVOR_PLATFORM_URL . "admin/js/admin{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker'],
            FLAVOR_PLATFORM_VERSION,
            true
        );

        wp_localize_script('flavor-chat-ia-admin', 'flavorChatAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_platform_admin_nonce'),
            'strings' => [
                'confirmDelete' => __('¿Eliminar este elemento?', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'analyzing' => __('Analizando sitio...', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'success' => __('Configuración generada', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'error' => __('Error al analizar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ],
        ]);
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        $settings = flavor_get_main_settings();
        $active_tab = $_GET['tab'] ?? 'general';
        // Usar el slug de página actual para que las pestañas funcionen correctamente
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : 'flavor-platform-settings';
        ob_start();
        ?>
        <div class="wrap flavor-chat-settings">
            <h1><?php esc_html_e('Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <?php settings_errors('flavor_platform_settings'); ?>

            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=general"
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=providers"
                   class="nav-tab <?php echo $active_tab === 'providers' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Proveedores IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=appearance"
                   class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Apariencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=quick_actions"
                   class="nav-tab <?php echo $active_tab === 'quick_actions' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Acciones Rápidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=knowledge"
                   class="nav-tab <?php echo $active_tab === 'knowledge' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Base de Conocimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=escalation"
                   class="nav-tab <?php echo $active_tab === 'escalation' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Escalado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=modules"
                   class="nav-tab <?php echo $active_tab === 'modules' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=firebase_push"
                   class="nav-tab <?php echo $active_tab === 'firebase_push' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Push Notifications', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=analytics"
                   class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Analíticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
                <a href="?page=<?php echo esc_attr($current_page); ?>&tab=advanced"
                   class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Avanzado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </nav>

            <form method="post" action="" id="flavor-chat-settings-form">
                <?php wp_nonce_field('flavor_platform_settings_save', 'flavor_platform_nonce'); ?>
                <?php wp_nonce_field('flavor_platform_settings_save', 'flavor_platform_nonce'); ?>
                <input type="hidden" name="flavor_chat_ia_action" value="save_settings">
                <input type="hidden" name="flavor_platform_action" value="save_settings">
                <input type="hidden" name="current_tab" value="<?php echo esc_attr($active_tab); ?>">

                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_tab($settings);
                        break;
                    case 'providers':
                        $this->render_providers_tab($settings);
                        break;
                    case 'appearance':
                        $this->render_appearance_tab($settings);
                        break;
                    case 'quick_actions':
                        $this->render_quick_actions_tab($settings);
                        break;
                    case 'knowledge':
                        $this->render_knowledge_tab($settings);
                        break;
                    case 'escalation':
                        $this->render_escalation_tab($settings);
                        break;
                    case 'modules':
                        $this->render_modules_tab($settings);
                        break;
                    case 'firebase_push':
                        $this->render_firebase_push_tab();
                        break;
                    case 'analytics':
                        $this->render_analytics_tab();
                        break;
                    case 'advanced':
                        $this->render_advanced_tab($settings);
                        break;
                }
                ?>

                <?php if ($active_tab !== 'analytics'): ?>
                    <?php submit_button(__('Guardar cambios', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
        $output = ob_get_clean();
        $output = str_replace(
            ['flavor_chat_ia_settings[', 'flavor_chat_ia_module_'],
            ['flavor_platform_settings[', 'flavor_platform_module_'],
            $output
        );
        echo $output;
    }

    /**
     * Pestaña General
     */
    private function render_general_tab($settings) {
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="general">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Habilitar Chat', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[enabled]" value="1"
                               <?php checked(!empty($settings['enabled'])); ?>>
                        <?php esc_html_e('Activar el chat IA en el sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Modo Test', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[admin_only]" value="1"
                               <?php checked(!empty($settings['admin_only'])); ?>>
                        <?php esc_html_e('Solo visible para administradores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Widget Flotante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[show_floating_widget]" value="1"
                               <?php checked($settings['show_floating_widget'] ?? true); ?>>
                        <?php esc_html_e('Mostrar botón flotante en todas las páginas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Nombre del Asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[assistant_name]"
                           value="<?php echo esc_attr($settings['assistant_name'] ?? 'Asistente Virtual'); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Rol/Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[assistant_role]" rows="3" class="large-text"><?php
                        echo esc_textarea($settings['assistant_role'] ?? '');
                    ?></textarea>
                    <p class="description"><?php esc_html_e('Describe el rol del asistente para que sepa cómo comportarse.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Tono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[tone]">
                        <option value="friendly" <?php selected($settings['tone'] ?? 'friendly', 'friendly'); ?>><?php esc_html_e('Amable y cercano', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="formal" <?php selected($settings['tone'] ?? '', 'formal'); ?>><?php esc_html_e('Profesional y formal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="casual" <?php selected($settings['tone'] ?? '', 'casual'); ?>><?php esc_html_e('Informal y relajado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="enthusiastic" <?php selected($settings['tone'] ?? '', 'enthusiastic'); ?>><?php esc_html_e('Entusiasta y positivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Idiomas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <?php
                    $languages = $settings['languages'] ?? ['es'];
                    $available = ['es' => 'Español', 'eu' => 'Euskera', 'en' => 'English', 'ca' => 'Català', 'fr' => 'Français'];
                    foreach ($available as $code => $name):
                    ?>
                    <label style="margin-right: 15px;">
                        <input type="checkbox" name="flavor_chat_ia_settings[languages][]"
                               value="<?php echo esc_attr($code); ?>"
                               <?php checked(in_array($code, $languages)); ?>>
                        <?php echo esc_html($name); ?>
                    </label>
                    <?php endforeach; ?>
                    <p class="description"><?php esc_html_e('Compatible con WPML y Polylang.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Límite de mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[max_messages_per_session]"
                           value="<?php echo esc_attr($settings['max_messages_per_session'] ?? 50); ?>"
                           min="10" max="200" class="small-text">
                    <span><?php esc_html_e('mensajes por sesión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Shortcode', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <code>[flavor_chat]</code>
                    <p class="description"><?php esc_html_e('Usa este shortcode para insertar el chat en cualquier página.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Pestaña Proveedores IA
     */
    private function render_providers_tab($settings) {
        $encryption = class_exists('Flavor_API_Key_Encryption')
            ? Flavor_API_Key_Encryption::get_instance()
            : null;

        $provider_key_status = [
            'claude' => $settings['claude_api_key'] ?? $settings['api_key'] ?? '',
            'openai' => $settings['openai_api_key'] ?? '',
            'deepseek' => $settings['deepseek_api_key'] ?? '',
            'mistral' => $settings['mistral_api_key'] ?? '',
        ];

        $mask_or_empty = function ($value) use ($encryption) {
            if (empty($value)) {
                return '';
            }
            if ($encryption) {
                return $encryption->mask_for_display($value);
            }
            $len = strlen((string) $value);
            if ($len <= 8) {
                return str_repeat('*', $len);
            }
            return substr($value, 0, 4) . str_repeat('*', max(0, $len - 8)) . substr($value, -4);
        };

        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="providers">

        <h2><?php esc_html_e('Proveedor de IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description"><?php esc_html_e('Selecciona el proveedor activo. Puedes configurar varios y cambiar entre ellos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Proveedor activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[active_provider]" id="active_provider">
                        <option value="claude" <?php selected($settings['active_provider'] ?? 'claude', 'claude'); ?>>Claude (Anthropic)</option>
                        <option value="openai" <?php selected($settings['active_provider'] ?? '', 'openai'); ?>>OpenAI (GPT)</option>
                        <option value="deepseek" <?php selected($settings['active_provider'] ?? '', 'deepseek'); ?>>DeepSeek (Gratuito)</option>
                        <option value="mistral" <?php selected($settings['active_provider'] ?? '', 'mistral'); ?>>Mistral AI (Gratuito)</option>
                    </select>
                    <button type="button" id="flavor-test-connection" class="button" style="margin-left: 10px;">
                        <span class="dashicons dashicons-yes-alt" style="vertical-align: middle;"></span>
                        <?php esc_html_e('Verificar Conexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </button>
                    <span id="flavor-connection-status" style="margin-left: 10px;"></span>
                </td>
            </tr>
        </table>

        <!-- Claude -->
        <div class="provider-settings provider-claude" style="border: 1px solid #ccd0d4; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f9f9f9;">
            <h3 style="margin-top: 0;">🟣 Claude (Anthropic)</h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[claude_api_key]"
                               value="" autocomplete="new-password" class="regular-text">
                        <p class="description">
                            <a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a>
                            · <?php esc_html_e('Deja vacío para mantener la clave actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php if (!empty($provider_key_status['claude'])) : ?>
                                <br><strong><?php esc_html_e('Clave guardada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <code><?php echo esc_html($mask_or_empty($provider_key_status['claude'])); ?></code>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[claude_model]">
                            <option value="claude-sonnet-4-20250514" <?php selected($settings['claude_model'] ?? '', 'claude-sonnet-4-20250514'); ?>>Claude Sonnet 4</option>
                            <option value="claude-3-5-sonnet-20241022" <?php selected($settings['claude_model'] ?? '', 'claude-3-5-sonnet-20241022'); ?>>Claude 3.5 Sonnet</option>
                            <option value="claude-3-5-haiku-20241022" <?php selected($settings['claude_model'] ?? '', 'claude-3-5-haiku-20241022'); ?>>Claude 3.5 Haiku</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- OpenAI -->
        <div class="provider-settings provider-openai" style="border: 1px solid #ccd0d4; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f9f9f9;">
            <h3 style="margin-top: 0;">🟢 OpenAI (GPT)</h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[openai_api_key]"
                               value="" autocomplete="new-password" class="regular-text">
                        <p class="description">
                            <a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>
                            · <?php esc_html_e('Deja vacío para mantener la clave actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php if (!empty($provider_key_status['openai'])) : ?>
                                <br><strong><?php esc_html_e('Clave guardada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <code><?php echo esc_html($mask_or_empty($provider_key_status['openai'])); ?></code>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[openai_model]">
                            <option value="gpt-4o" <?php selected($settings['openai_model'] ?? '', 'gpt-4o'); ?>>GPT-4o</option>
                            <option value="gpt-4o-mini" <?php selected($settings['openai_model'] ?? 'gpt-4o-mini', 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                            <option value="gpt-3.5-turbo" <?php selected($settings['openai_model'] ?? '', 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- DeepSeek -->
        <div class="provider-settings provider-deepseek" style="border: 1px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px; background: #ecfdf5;">
            <h3 style="margin-top: 0;">🔵 DeepSeek <span style="background: #10b981; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">GRATUITO</span></h3>
            <p class="description"><?php esc_html_e('~500K tokens/día gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[deepseek_api_key]"
                               value="" autocomplete="new-password" class="regular-text">
                        <p class="description">
                            <a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a>
                            · <?php esc_html_e('Deja vacío para mantener la clave actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php if (!empty($provider_key_status['deepseek'])) : ?>
                                <br><strong><?php esc_html_e('Clave guardada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <code><?php echo esc_html($mask_or_empty($provider_key_status['deepseek'])); ?></code>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[deepseek_model]">
                            <option value="deepseek-chat" <?php selected($settings['deepseek_model'] ?? 'deepseek-chat', 'deepseek-chat'); ?>>DeepSeek Chat</option>
                            <option value="deepseek-reasoner" <?php selected($settings['deepseek_model'] ?? '', 'deepseek-reasoner'); ?>>DeepSeek Reasoner</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Mistral -->
        <div class="provider-settings provider-mistral" style="border: 1px solid #f97316; padding: 15px; margin: 20px 0; border-radius: 4px; background: #fff7ed;">
            <h3 style="margin-top: 0;">🟠 Mistral AI <span style="background: #f97316; color: white; padding: 2px 8px; border-radius: 3px; font-size: 12px;">GRATUITO</span></h3>
            <p class="description"><?php esc_html_e('1M tokens/mes gratis', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[mistral_api_key]"
                               value="" autocomplete="new-password" class="regular-text">
                        <p class="description">
                            <a href="https://console.mistral.ai/" target="_blank">console.mistral.ai</a>
                            · <?php esc_html_e('Deja vacío para mantener la clave actual.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            <?php if (!empty($provider_key_status['mistral'])) : ?>
                                <br><strong><?php esc_html_e('Clave guardada:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                                <code><?php echo esc_html($mask_or_empty($provider_key_status['mistral'])); ?></code>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[mistral_model]">
                            <option value="mistral-small-latest" <?php selected($settings['mistral_model'] ?? 'mistral-small-latest', 'mistral-small-latest'); ?>>Mistral Small (Gratis)</option>
                            <option value="mistral-large-latest" <?php selected($settings['mistral_model'] ?? '', 'mistral-large-latest'); ?>>Mistral Large</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <h2><?php esc_html_e('Límites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Máx. tokens por mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[max_tokens_per_message]"
                           value="<?php echo esc_attr($settings['max_tokens_per_message'] ?? 1000); ?>"
                           min="100" max="4000" class="small-text">
                </td>
            </tr>
        </table>

        <!-- Configuración por contexto -->
        <hr style="margin: 30px 0;">
        <h2><?php esc_html_e('IA por contexto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description"><?php esc_html_e('Puedes usar un proveedor y modelo diferente para el chat público (frontend) y para el asistente de administración (backend). Si seleccionas "Usar proveedor por defecto", se usará el proveedor activo configurado arriba.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <?php
        $provider_context_options = [
            'default' => __('Usar proveedor por defecto', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'claude'  => 'Claude (Anthropic)',
            'openai'  => 'OpenAI (GPT)',
            'deepseek' => 'DeepSeek',
            'mistral' => 'Mistral AI',
        ];

        $models_by_provider = [
            'claude' => [
                'claude-sonnet-4-20250514' => 'Claude Sonnet 4',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
            ],
            'openai' => [
                'gpt-4o' => 'GPT-4o',
                'gpt-4o-mini' => 'GPT-4o Mini',
                'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            ],
            'deepseek' => [
                'deepseek-chat' => 'DeepSeek Chat',
                'deepseek-reasoner' => 'DeepSeek Reasoner',
            ],
            'mistral' => [
                'mistral-small-latest' => 'Mistral Small',
                'mistral-large-latest' => 'Mistral Large',
            ],
        ];

        $context_configs = [
            'frontend' => [
                'label' => __('Chat Público (Frontend)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Widget de chat para visitantes del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-format-chat',
            ],
            'backend' => [
                'label' => __('Admin Assistant (Backend)', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Asistente de IA para administradores en el panel de WordPress', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'icon' => 'dashicons-admin-tools',
            ],
        ];

        foreach ($context_configs as $context_key => $context_info):
            $selected_provider = $settings['ia_provider_' . $context_key] ?? 'default';
            $selected_model = $settings['ia_model_' . $context_key] ?? '';
        ?>
        <div style="border: 1px solid #c3c4c7; padding: 15px 20px; margin: 15px 0; border-radius: 6px; background: #fff;">
            <h3 style="margin-top: 0;">
                <span class="dashicons <?php echo esc_attr($context_info['icon']); ?>" style="margin-right: 5px;"></span>
                <?php echo esc_html($context_info['label']); ?>
            </h3>
            <p class="description" style="margin-top: -5px; margin-bottom: 15px;"><?php echo esc_html($context_info['description']); ?></p>

            <table class="form-table" style="margin-top: 0;">
                <tr>
                    <th><?php esc_html_e('Proveedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[ia_provider_<?php echo esc_attr($context_key); ?>]"
                                class="context-provider-select"
                                data-context="<?php echo esc_attr($context_key); ?>">
                            <?php foreach ($provider_context_options as $provider_value => $provider_label): ?>
                                <option value="<?php echo esc_attr($provider_value); ?>"
                                    <?php selected($selected_provider, $provider_value); ?>>
                                    <?php echo esc_html($provider_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr class="context-model-row context-model-<?php echo esc_attr($context_key); ?>"
                    style="<?php echo ($selected_provider === 'default') ? 'display:none;' : ''; ?>">
                    <th><?php esc_html_e('Modelo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[ia_model_<?php echo esc_attr($context_key); ?>]"
                                class="context-model-select"
                                data-context="<?php echo esc_attr($context_key); ?>">
                            <option value=""><?php esc_html_e('Modelo por defecto del proveedor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <?php foreach ($models_by_provider as $provider_id => $provider_models): ?>
                                <?php foreach ($provider_models as $model_value => $model_label): ?>
                                    <option value="<?php echo esc_attr($model_value); ?>"
                                            data-provider="<?php echo esc_attr($provider_id); ?>"
                                            <?php selected($selected_model, $model_value); ?>
                                            <?php echo ($selected_provider !== $provider_id) ? 'style="display:none;"' : ''; ?>>
                                        <?php echo esc_html($model_label); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Orden de Fallback -->
        <hr style="margin: 30px 0;">
        <h2><?php esc_html_e('Orden de Fallback', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description"><?php esc_html_e('Si el proveedor principal falla (error de red, límite de API, etc.), se usará automáticamente el siguiente proveedor configurado. Arrastra para cambiar el orden de prioridad.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <?php
        // Obtener engines configurados
        $configured_providers = [];
        $all_providers = ['claude', 'openai', 'deepseek', 'mistral'];
        $provider_names = [
            'claude' => '🟣 Claude',
            'openai' => '🟢 OpenAI',
            'deepseek' => '🔵 DeepSeek',
            'mistral' => '🟠 Mistral',
        ];

        foreach ($all_providers as $provider_id) {
            $key_field = ($provider_id === 'claude') ? 'claude_api_key' : $provider_id . '_api_key';
            $has_key = !empty($settings[$key_field]) || ($provider_id === 'claude' && !empty($settings['api_key']));
            $configured_providers[$provider_id] = $has_key;
        }

        // Orden guardado
        $saved_order = $settings['fallback_order'] ?? [];
        if (empty($saved_order)) {
            $saved_order = array_keys(array_filter($configured_providers));
        }

        // Asegurar que todos los configurados estén en el orden
        foreach ($configured_providers as $provider_id => $is_configured) {
            if ($is_configured && !in_array($provider_id, $saved_order)) {
                $saved_order[] = $provider_id;
            }
        }
        ?>

        <div style="border: 1px solid #c3c4c7; padding: 20px; margin: 15px 0; border-radius: 6px; background: #fff;">
            <div id="fallback-order-list" style="display: flex; flex-direction: column; gap: 8px;">
                <?php
                $position = 1;
                foreach ($saved_order as $provider_id):
                    if (!isset($configured_providers[$provider_id])) continue;
                    $is_configured = $configured_providers[$provider_id];
                ?>
                <div class="fallback-item <?php echo $is_configured ? 'configured' : 'not-configured'; ?>"
                     data-provider="<?php echo esc_attr($provider_id); ?>"
                     style="display: flex; align-items: center; padding: 12px 15px; background: <?php echo $is_configured ? '#f0fdf4' : '#fef2f2'; ?>; border: 1px solid <?php echo $is_configured ? '#86efac' : '#fecaca'; ?>; border-radius: 6px; cursor: <?php echo $is_configured ? 'grab' : 'not-allowed'; ?>;">
                    <span class="dashicons dashicons-menu" style="margin-right: 10px; color: #9ca3af;"></span>
                    <span class="position-number" style="background: <?php echo $is_configured ? '#22c55e' : '#ef4444'; ?>; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; margin-right: 12px;">
                        <?php echo $is_configured ? $position++ : '—'; ?>
                    </span>
                    <span style="font-weight: 500; flex: 1;"><?php echo esc_html($provider_names[$provider_id]); ?></span>
                    <?php if ($is_configured): ?>
                        <span style="color: #16a34a; font-size: 13px;">✓ Configurado</span>
                    <?php else: ?>
                        <span style="color: #dc2626; font-size: 13px;">✗ Sin API key</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>

                <?php
                // Añadir los no configurados que no están en el orden
                foreach ($configured_providers as $provider_id => $is_configured):
                    if (in_array($provider_id, $saved_order)) continue;
                ?>
                <div class="fallback-item not-configured"
                     data-provider="<?php echo esc_attr($provider_id); ?>"
                     style="display: flex; align-items: center; padding: 12px 15px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px; cursor: not-allowed;">
                    <span class="dashicons dashicons-menu" style="margin-right: 10px; color: #9ca3af;"></span>
                    <span class="position-number" style="background: #ef4444; color: white; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 12px; margin-right: 12px;">—</span>
                    <span style="font-weight: 500; flex: 1;"><?php echo esc_html($provider_names[$provider_id]); ?></span>
                    <span style="color: #dc2626; font-size: 13px;">✗ Sin API key</span>
                </div>
                <?php endforeach; ?>
            </div>

            <input type="hidden" name="flavor_chat_ia_settings[fallback_order]" id="fallback-order-input"
                   value="<?php echo esc_attr(implode(',', $saved_order)); ?>">

            <p style="margin-top: 15px; margin-bottom: 0; color: #6b7280; font-size: 13px;">
                <span class="dashicons dashicons-info" style="font-size: 16px; vertical-align: middle;"></span>
                <?php esc_html_e('Solo los proveedores con API key configurada pueden usarse como fallback.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <script>
        jQuery(function($) {
            // Sortable para el orden de fallback
            if (typeof $.fn.sortable !== 'undefined') {
                $('#fallback-order-list').sortable({
                    items: '.fallback-item.configured',
                    handle: '.dashicons-menu',
                    axis: 'y',
                    cursor: 'grabbing',
                    update: function(event, ui) {
                        var order = [];
                        var position = 1;
                        $('#fallback-order-list .fallback-item').each(function() {
                            var provider = $(this).data('provider');
                            if ($(this).hasClass('configured')) {
                                $(this).find('.position-number').text(position++);
                                order.push(provider);
                            }
                        });
                        $('#fallback-order-input').val(order.join(','));
                    }
                });
            }

            // Highlight proveedor activo
            function highlightProvider() {
                var active = $('#active_provider').val();
                $('.provider-settings').css({'border-width': '1px', 'opacity': '0.7'});
                $('.provider-' + active).css({'border-width': '3px', 'opacity': '1'});
            }
            highlightProvider();
            $('#active_provider').on('change', highlightProvider);

            // Configuración por contexto: mostrar/ocultar modelo y filtrar opciones
            $('.context-provider-select').on('change', function() {
                var contextKey = $(this).data('context');
                var selectedProvider = $(this).val();
                var $modelRow = $('.context-model-' + contextKey);
                var $modelSelect = $modelRow.find('.context-model-select');

                if (selectedProvider === 'default') {
                    $modelRow.hide();
                    $modelSelect.val('');
                } else {
                    $modelRow.show();
                    // Filtrar modelos por proveedor
                    $modelSelect.find('option[data-provider]').hide();
                    $modelSelect.find('option[data-provider="' + selectedProvider + '"]').show();

                    // Si el modelo seleccionado no pertenece al proveedor, resetear
                    var $currentOption = $modelSelect.find('option:selected');
                    if ($currentOption.data('provider') && $currentOption.data('provider') !== selectedProvider) {
                        $modelSelect.val('');
                    }
                }
            });
        });
        </script>

        <!-- Figma Integration -->
        <hr style="margin: 30px 0;">
        <h2><?php esc_html_e('Integración Figma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description"><?php esc_html_e('Conecta con Figma para importar diseños directamente al Visual Builder Pro.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div style="border: 1px solid #a855f7; padding: 15px; margin: 20px 0; border-radius: 4px; background: #faf5ff;">
            <h3 style="margin-top: 0;">
                <span style="display:inline-block;width:20px;height:20px;background:#a855f7;border-radius:4px;text-align:center;line-height:20px;color:white;font-size:12px;margin-right:6px;">F</span>
                Figma
            </h3>
            <table class="form-table" style="margin-top:0;">
                <tr>
                    <th><?php esc_html_e('Personal Access Token', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[figma_personal_token]"
                               id="figma_personal_token"
                               value="<?php echo esc_attr($settings['figma_personal_token'] ?? ''); ?>"
                               class="regular-text">
                        <button type="button" class="button" id="verify-figma-token" style="margin-left:8px;">
                            <?php esc_html_e('Verificar conexión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                        <span id="figma-token-status" style="margin-left:10px;"></span>
                        <p class="description">
                            <?php
                            printf(
                                esc_html__('Obtén tu token en %s → Settings → Personal Access Tokens', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                '<a href="https://www.figma.com/settings" target="_blank">figma.com/settings</a>'
                            );
                            ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <script>
        jQuery(function($) {
            $('#verify-figma-token').on('click', function() {
                var $btn = $(this);
                var $status = $('#figma-token-status');
                var token = $('#figma_personal_token').val();

                if (!token) {
                    $status.html('<span style="color:#dc2626;">❌ <?php echo esc_js(__('Ingresa un token primero', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>');
                    return;
                }

                $btn.prop('disabled', true);
                $status.html('<span style="color:#6b7280;">⏳ <?php echo esc_js(__('Verificando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>');

                $.post(ajaxurl, {
                    action: 'flavor_vbp_verify_figma_token',
                    nonce: '<?php echo wp_create_nonce('flavor_vbp_figma'); ?>',
                    token: token
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $status.html('<span style="color:#16a34a;">✓ <?php echo esc_js(__('Conectado como', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?> ' + response.data.user.handle + '</span>');
                    } else {
                        $status.html('<span style="color:#dc2626;">❌ ' + (response.data.message || '<?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>') + '</span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $status.html('<span style="color:#dc2626;">❌ <?php echo esc_js(__('Error de red', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>');
                });
            });

            // Verificar conexión IA
            $('#flavor-test-connection').on('click', function() {
                var $btn = $(this);
                var $status = $('#flavor-connection-status');
                var provider = $('#active_provider').val() || '';
                var providerApiInputs = {
                    claude: $('input[name="flavor_chat_ia_settings[claude_api_key]"]').val() || '',
                    openai: $('input[name="flavor_chat_ia_settings[openai_api_key]"]').val() || '',
                    deepseek: $('input[name="flavor_chat_ia_settings[deepseek_api_key]"]').val() || '',
                    mistral: $('input[name="flavor_chat_ia_settings[mistral_api_key]"]').val() || ''
                };
                var providerModelInputs = {
                    claude: $('select[name="flavor_chat_ia_settings[claude_model]"]').val() || '',
                    openai: $('select[name="flavor_chat_ia_settings[openai_model]"]').val() || '',
                    deepseek: $('select[name="flavor_chat_ia_settings[deepseek_model]"]').val() || '',
                    mistral: $('select[name="flavor_chat_ia_settings[mistral_model]"]').val() || ''
                };
                var inputApiKey = providerApiInputs[provider] || '';
                var inputModel = providerModelInputs[provider] || '';

                $btn.prop('disabled', true);
                $status.html('<span style="color:#666;"><span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span><?php echo esc_js(__('Verificando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>');

                $.post(ajaxurl, {
                    action: 'flavor_test_ia_connection',
                    nonce: flavorChatAdmin.nonce,
                    provider: provider,
                    api_key: inputApiKey,
                    model: inputModel
                }, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $status.html('<span style="color:#16a34a;font-weight:500;">' + response.data.message + '</span>');
                    } else {
                        $status.html('<span style="color:#dc2626;">' + response.data.message + '</span>');
                    }
                }).fail(function() {
                    $btn.prop('disabled', false);
                    $status.html('<span style="color:#dc2626;">❌ <?php echo esc_js(__('Error de conexión', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></span>');
                });
            });
        });
        </script>
        <?php
    }

    /**
     * Pestaña Apariencia
     */
    private function render_appearance_tab($settings) {
        $appearance = $settings['appearance'] ?? [];
        $theme_colors = $this->get_theme_colors();
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="appearance">

        <!-- Autoconfiguración -->
        <div style="background:#f0f6fc;border:1px solid #c3daf5;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <h3 style="margin:0 0 8px 0;">
                <span class="dashicons dashicons-admin-appearance"></span>
                <?php esc_html_e('Autoconfiguración desde el tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Aplica automáticamente los colores de tu tema de WordPress al chat.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" id="apply-theme-colors" class="button button-primary">
                <?php esc_html_e('Aplicar colores del tema', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span id="theme-colors-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Colores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Color principal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][primary_color]" id="primary_color"
                           value="<?php echo esc_attr($appearance['primary_color'] ?? $theme_colors['primary'] ?? '#0073aa'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Fondo de cabecera', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][header_bg]" id="header_bg"
                           value="<?php echo esc_attr($appearance['header_bg'] ?? $theme_colors['secondary'] ?? '#1e3a5f'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Burbuja del usuario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][user_bubble]" id="user_bubble"
                           value="<?php echo esc_attr($appearance['user_bubble'] ?? $theme_colors['primary'] ?? '#0073aa'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Burbuja del asistente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][assistant_bubble]" id="assistant_bubble"
                           value="<?php echo esc_attr($appearance['assistant_bubble'] ?? '#f0f0f0'); ?>">
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Avatar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Imagen del avatar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <div id="avatar-preview" style="margin-bottom:10px;">
                        <?php if (!empty($appearance['avatar_url'])): ?>
                            <img src="<?php echo esc_url($appearance['avatar_url']); ?>" style="max-width:80px;max-height:80px;border-radius:50%;">
                        <?php else: ?>
                            <div style="width:80px;height:80px;border-radius:50%;background:#1e3a5f;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🤖</div>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="flavor_chat_ia_settings[appearance][avatar_url]" id="avatar_url"
                           value="<?php echo esc_attr($appearance['avatar_url'] ?? ''); ?>">
                    <button type="button" class="button" id="upload-avatar"><?php esc_html_e('Seleccionar imagen', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="button" class="button" id="remove-avatar" <?php echo empty($appearance['avatar_url']) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Posición y tamaño', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Posición', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][position]">
                        <option value="bottom-right" <?php selected($appearance['position'] ?? 'bottom-right', 'bottom-right'); ?>><?php esc_html_e('Abajo derecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="bottom-left" <?php selected($appearance['position'] ?? '', 'bottom-left'); ?>><?php esc_html_e('Abajo izquierda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Distancia desde abajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][bottom_offset]"
                           value="<?php echo esc_attr($appearance['bottom_offset'] ?? 20); ?>" min="10" max="200" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Distancia desde el lado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][side_offset]"
                           value="<?php echo esc_attr($appearance['side_offset'] ?? 20); ?>" min="10" max="200" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Tamaño del botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][trigger_size]">
                        <option value="small" <?php selected($appearance['trigger_size'] ?? '', 'small'); ?>><?php esc_html_e('Pequeño (50px)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="medium" <?php selected($appearance['trigger_size'] ?? 'medium', 'medium'); ?>><?php esc_html_e('Mediano (60px)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="large" <?php selected($appearance['trigger_size'] ?? '', 'large'); ?>><?php esc_html_e('Grande (70px)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Animación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][trigger_animation]">
                        <option value="none" <?php selected($appearance['trigger_animation'] ?? '', 'none'); ?>><?php esc_html_e('Sin animación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="pulse" <?php selected($appearance['trigger_animation'] ?? 'pulse', 'pulse'); ?>><?php esc_html_e('Pulso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        <option value="bounce" <?php selected($appearance['trigger_animation'] ?? '', 'bounce'); ?>><?php esc_html_e('Rebote', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Ancho del widget', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][widget_width]"
                           value="<?php echo esc_attr($appearance['widget_width'] ?? 380); ?>" min="300" max="500" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Alto del widget', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][widget_height]"
                           value="<?php echo esc_attr($appearance['widget_height'] ?? 500); ?>" min="400" max="700" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Bordes redondeados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][border_radius]"
                           value="<?php echo esc_attr($appearance['border_radius'] ?? 16); ?>" min="0" max="30" class="small-text"> px
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Mensaje de bienvenida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[appearance][welcome_message]" rows="3" class="large-text"><?php
                        echo esc_textarea($appearance['welcome_message'] ?? '¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?');
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Placeholder del input', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[appearance][placeholder]"
                           value="<?php echo esc_attr($appearance['placeholder'] ?? 'Escribe tu mensaje...'); ?>" class="regular-text">
                </td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            // Colores del tema detectados
            var themeColors = <?php echo json_encode($theme_colors); ?>;

            $('#apply-theme-colors').on('click', function() {
                if (themeColors.primary) $('#primary_color').val(themeColors.primary);
                if (themeColors.secondary) $('#header_bg').val(themeColors.secondary);
                if (themeColors.primary) $('#user_bubble').val(themeColors.primary);
                $('#theme-colors-status').html('<span style="color:green;">✓ Colores aplicados</span>');
            });

            // Upload avatar
            $('#upload-avatar').on('click', function(e) {
                e.preventDefault();
                var frame = wp.media({title: 'Seleccionar avatar', multiple: false});
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $('#avatar_url').val(attachment.url);
                    $('#avatar-preview').html('<img src="' + attachment.url + '" style="max-width:80px;max-height:80px;border-radius:50%;">');
                    $('#remove-avatar').show();
                });
                frame.open();
            });

            $('#remove-avatar').on('click', function() {
                $('#avatar_url').val('');
                $('#avatar-preview').html('<div style="width:80px;height:80px;border-radius:50%;background:#1e3a5f;display:flex;align-items:center;justify-content:center;color:white;font-size:24px;">🤖</div>');
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Obtiene colores del tema activo
     */
    private function get_theme_colors() {
        $colors = ['primary' => '#0073aa', 'secondary' => '#1e3a5f'];

        // Intentar leer theme.json
        $theme_json_path = get_stylesheet_directory() . '/theme.json';
        if (file_exists($theme_json_path)) {
            $theme_json = json_decode(file_get_contents($theme_json_path), true);
            $palette = $theme_json['settings']['color']['palette'] ?? [];

            foreach ($palette as $color) {
                $slug = $color['slug'] ?? '';
                if (in_array($slug, ['primary', 'accent', 'brand'])) {
                    $colors['primary'] = $color['color'];
                }
                if (in_array($slug, ['secondary', 'contrast', 'dark'])) {
                    $colors['secondary'] = $color['color'];
                }
            }
        }

        return $colors;
    }

    /**
     * Pestaña Acciones Rápidas
     */
    private function render_quick_actions_tab($settings) {
        $quick_actions = $settings['quick_actions'] ?? [];
        $default_actions = [
            ['id' => 'products', 'label' => 'Ver productos', 'prompt' => '¿Qué productos tenéis disponibles?', 'icon' => 'cart', 'enabled' => true],
            ['id' => 'orders', 'label' => 'Estado de pedido', 'prompt' => 'Quiero saber el estado de mi pedido', 'icon' => 'package', 'enabled' => true],
            ['id' => 'shipping', 'label' => 'Envíos', 'prompt' => '¿Cuáles son las opciones de envío?', 'icon' => 'truck', 'enabled' => true],
            ['id' => 'returns', 'label' => 'Devoluciones', 'prompt' => '¿Cuál es la política de devoluciones?', 'icon' => 'refresh', 'enabled' => false],
            ['id' => 'contact', 'label' => 'Contactar', 'prompt' => 'Necesito hablar con atención al cliente', 'icon' => 'phone', 'enabled' => false],
        ];

        foreach ($default_actions as &$action) {
            if (isset($quick_actions[$action['id']])) {
                $action = array_merge($action, $quick_actions[$action['id']]);
            }
        }
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="quick_actions">

        <!-- Autoconfiguración -->
        <div style="background:#f0f6fc;border:1px solid #c3daf5;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <h3 style="margin:0 0 8px 0;">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php esc_html_e('Autoconfiguración con IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Analiza tu sitio y genera acciones rápidas relevantes automáticamente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" id="autoconfig-quick-actions" class="button button-primary" data-section="quick_actions">
                <?php esc_html_e('Generar acciones con IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <p class="description"><?php esc_html_e('Botones que aparecen debajo del mensaje de bienvenida.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;"><?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width:60px;"><?php esc_html_e('Icono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Mensaje que envía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($default_actions as $action): ?>
                <tr>
                    <td>
                        <input type="checkbox" name="flavor_chat_ia_settings[quick_actions][<?php echo esc_attr($action['id']); ?>][enabled]"
                               value="1" <?php checked(!empty($action['enabled'])); ?>>
                    </td>
                    <td>
                        <select name="flavor_chat_ia_settings[quick_actions][<?php echo esc_attr($action['id']); ?>][icon]" style="width:100%;">
                            <option value="cart" <?php selected($action['icon'], 'cart'); ?>>🛒</option>
                            <option value="package" <?php selected($action['icon'], 'package'); ?>>📦</option>
                            <option value="truck" <?php selected($action['icon'], 'truck'); ?>>🚚</option>
                            <option value="refresh" <?php selected($action['icon'], 'refresh'); ?>>🔄</option>
                            <option value="phone" <?php selected($action['icon'], 'phone'); ?>>📞</option>
                            <option value="question" <?php selected($action['icon'], 'question'); ?>>❓</option>
                            <option value="star" <?php selected($action['icon'], 'star'); ?>>⭐</option>
                            <option value="info" <?php selected($action['icon'], 'info'); ?>>ℹ️</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="flavor_chat_ia_settings[quick_actions][<?php echo esc_attr($action['id']); ?>][label]"
                               value="<?php echo esc_attr($action['label']); ?>" class="regular-text" style="width:100%;">
                    </td>
                    <td>
                        <input type="text" name="flavor_chat_ia_settings[quick_actions][<?php echo esc_attr($action['id']); ?>][prompt]"
                               value="<?php echo esc_attr($action['prompt']); ?>" class="large-text" style="width:100%;">
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="margin-top:30px;"><?php esc_html_e('Acciones personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <div id="custom-actions-container">
            <?php
            $custom_actions = $settings['custom_quick_actions'] ?? [];
            foreach ($custom_actions as $index => $custom):
            ?>
            <div class="custom-action-item" style="background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">
                <input type="text" name="flavor_chat_ia_settings[custom_quick_actions][<?php echo $index; ?>][label]"
                       value="<?php echo esc_attr($custom['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('Texto del botón', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="regular-text">
                <input type="text" name="flavor_chat_ia_settings[custom_quick_actions][<?php echo $index; ?>][prompt]"
                       value="<?php echo esc_attr($custom['prompt'] ?? ''); ?>" placeholder="<?php esc_attr_e('Mensaje que envía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>" class="large-text">
                <button type="button" class="button remove-custom-action"><?php esc_html_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-custom-action" class="button"><?php esc_html_e('+ Añadir acción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

        <script>
        jQuery(function($) {
            var customIndex = <?php echo count($custom_actions); ?>;

            $('#add-custom-action').on('click', function() {
                var html = '<div class="custom-action-item" style="background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">' +
                    '<input type="text" name="flavor_chat_ia_settings[custom_quick_actions][' + customIndex + '][label]" placeholder="Texto del botón" class="regular-text">' +
                    '<input type="text" name="flavor_chat_ia_settings[custom_quick_actions][' + customIndex + '][prompt]" placeholder="Mensaje que envía" class="large-text">' +
                    '<button type="button" class="button remove-custom-action">Eliminar</button></div>';
                $('#custom-actions-container').append(html);
                customIndex++;
            });

            $(document).on('click', '.remove-custom-action', function() {
                $(this).closest('.custom-action-item').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Pestaña Base de Conocimiento
     */
    private function render_knowledge_tab($settings) {
        $business_info = $settings['business_info'] ?? [];
        $faqs = $settings['faqs'] ?? [];
        $policies = $settings['policies'] ?? [];
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="knowledge">

        <!-- Autoconfiguración -->
        <div style="background:#f0f6fc;border:1px solid #c3daf5;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <h3 style="margin:0 0 8px 0;">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php esc_html_e('Autoconfiguración con IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Analiza tu sitio web (páginas, productos, etc.) y genera automáticamente la información del negocio, FAQs y políticas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" id="autoconfig-knowledge" class="button button-primary" data-section="knowledge">
                <?php esc_html_e('Analizar sitio y autoconfigurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Información del Negocio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Nombre del negocio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[business_info][name]"
                           value="<?php echo esc_attr($business_info['name'] ?? get_bloginfo('name')); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[business_info][description]" rows="4" class="large-text"><?php
                        echo esc_textarea($business_info['description'] ?? '');
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Dirección', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][address]" value="<?php echo esc_attr($business_info['address'] ?? ''); ?>" class="large-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][phone]" value="<?php echo esc_attr($business_info['phone'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><input type="email" name="flavor_chat_ia_settings[business_info][email]" value="<?php echo esc_attr($business_info['email'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][schedule]" value="<?php echo esc_attr($business_info['schedule'] ?? ''); ?>" class="large-text" placeholder="L-V 9:00-18:00"></td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Temas del negocio (Antispam)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Temas permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[business_topics]"
                           value="<?php echo esc_attr(implode(', ', $settings['business_topics'] ?? [])); ?>" class="large-text"
                           placeholder="productos, pedidos, envíos, devoluciones">
                    <p class="description"><?php esc_html_e('Lista de temas separados por comas. El chat solo responderá sobre estos temas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Preguntas Frecuentes (FAQs)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div id="faqs-container">
            <?php
            if (empty($faqs)) $faqs = [['question' => '', 'answer' => '']];
            foreach ($faqs as $index => $faq):
            ?>
            <div class="faq-item" style="background:#fff;padding:15px;border:1px solid #ccd0d4;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                    <strong><?php printf(__('FAQ #%d', FLAVOR_PLATFORM_TEXT_DOMAIN), $index + 1); ?></strong>
                    <button type="button" class="button remove-faq"><?php esc_html_e('Eliminar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
                <p>
                    <label><?php esc_html_e('Pregunta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="text" name="flavor_chat_ia_settings[faqs][<?php echo $index; ?>][question]"
                           value="<?php echo esc_attr($faq['question'] ?? ''); ?>" class="large-text">
                </p>
                <p>
                    <label><?php esc_html_e('Respuesta:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea name="flavor_chat_ia_settings[faqs][<?php echo $index; ?>][answer]" rows="3" class="large-text"><?php
                        echo esc_textarea($faq['answer'] ?? '');
                    ?></textarea>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-faq" class="button"><?php esc_html_e('+ Añadir FAQ', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>

        <hr>

        <h2><?php esc_html_e('Políticas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Política de envíos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><textarea name="flavor_chat_ia_settings[policies][shipping]" rows="3" class="large-text"><?php echo esc_textarea($policies['shipping'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Política de devoluciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><textarea name="flavor_chat_ia_settings[policies][returns]" rows="3" class="large-text"><?php echo esc_textarea($policies['returns'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Política de privacidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td><textarea name="flavor_chat_ia_settings[policies][privacy]" rows="3" class="large-text"><?php echo esc_textarea($policies['privacy'] ?? ''); ?></textarea></td>
            </tr>
        </table>

        <script>
        jQuery(function($) {
            var faqIndex = <?php echo count($faqs); ?>;

            $('#add-faq').on('click', function() {
                var html = '<div class="faq-item" style="background:#fff;padding:15px;border:1px solid #ccd0d4;margin-bottom:10px;">' +
                    '<div style="display:flex;justify-content:space-between;margin-bottom:10px;"><strong>FAQ #' + (faqIndex + 1) + '</strong><button type="button" class="button remove-faq">Eliminar</button></div>' +
                    '<p><label>Pregunta:</label><input type="text" name="flavor_chat_ia_settings[faqs][' + faqIndex + '][question]" class="large-text"></p>' +
                    '<p><label>Respuesta:</label><textarea name="flavor_chat_ia_settings[faqs][' + faqIndex + '][answer]" rows="3" class="large-text"></textarea></p></div>';
                $('#faqs-container').append(html);
                faqIndex++;
            });

            $(document).on('click', '.remove-faq', function() {
                $(this).closest('.faq-item').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Pestaña Escalado
     */
    private function render_escalation_tab($settings) {
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="escalation">

        <!-- Autoconfiguración -->
        <div style="background:#f0f6fc;border:1px solid #c3daf5;border-radius:8px;padding:16px 20px;margin-bottom:20px;">
            <h3 style="margin:0 0 8px 0;">
                <span class="dashicons dashicons-admin-site-alt3"></span>
                <?php esc_html_e('Autoconfiguración con IA', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Detecta automáticamente la información de contacto de tu sitio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" id="autoconfig-escalation" class="button button-primary" data-section="escalation">
                <?php esc_html_e('Detectar datos de contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Opciones de Contacto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p class="description"><?php esc_html_e('Se mostrarán cuando el chat escale a atención humana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e('WhatsApp', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[escalation_whatsapp]"
                           value="<?php echo esc_attr($settings['escalation_whatsapp'] ?? ''); ?>"
                           class="regular-text" placeholder="+34 600 000 000">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Teléfono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[escalation_phone]"
                           value="<?php echo esc_attr($settings['escalation_phone'] ?? ''); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="email" name="flavor_chat_ia_settings[escalation_email]"
                           value="<?php echo esc_attr($settings['escalation_email'] ?? ''); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario de atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[escalation_hours]"
                           value="<?php echo esc_attr($settings['escalation_hours'] ?? ''); ?>"
                           class="regular-text" placeholder="L-V 9:00-18:00">
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Pestaña Módulos
     */
    private function render_modules_tab($settings) {
        $active_modules = $settings['active_modules'] ?? ['woocommerce'];

        // Obtener información de módulos disponibles
        $available_modules = [];
        if (class_exists('Flavor_Platform_Module_Loader')) {
            $loader = Flavor_Platform_Module_Loader::get_instance();
            $available_modules = $loader->get_registered_modules();
        }

        // Módulos conocidos (hardcoded para cuando no hay loader)
        $known_modules = [
            'woocommerce' => [
                'name' => __('WooCommerce', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Integración con tienda WooCommerce - permite consultar productos, pedidos, etc.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => 'WooCommerce',
            ],
            'banco_tiempo' => [
                'name' => __('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de intercambio de servicios y tiempo entre miembros.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'grupos_consumo' => [
                'name' => __('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de pedidos colectivos y grupos de consumo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'marketplace' => [
                'name' => __('Marketplace', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Anuncios de regalo, venta e intercambio entre usuarios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'facturas' => [
                'name' => __('Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de facturas y facturación para administradores desde la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'fichaje_empleados' => [
                'name' => __('Fichaje de Empleados', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Control de horarios, asistencia y fichaje de empleados desde la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'eventos' => [
                'name' => __('Eventos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de eventos comunitarios, actividades y encuentros desde la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'socios' => [
                'name' => __('Gestión de Miembros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de miembros, cuotas y membresías desde la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'incidencias' => [
                'name' => __('Incidencias Urbanas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Reporte y seguimiento de incidencias en el barrio (baches, farolas, etc.).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'participacion' => [
                'name' => __('Participación Ciudadana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Propuestas, votaciones y consultas ciudadanas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'presupuestos_participativos' => [
                'name' => __('Presupuestos Participativos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de presupuestos participativos - los vecinos deciden cómo gastar el presupuesto municipal.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'avisos_municipales' => [
                'name' => __('Avisos Municipales', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Canal oficial de comunicación del ayuntamiento con los vecinos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'advertising' => [
                'name' => __('Publicidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de anuncios y campañas publicitarias en la plataforma.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'ayuda_vecinal' => [
                'name' => __('Ayuda Vecinal', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Red de ayuda mutua entre vecinos - ofrece y solicita ayuda en tu comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'biblioteca' => [
                'name' => __('Biblioteca Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de préstamo e intercambio de libros entre vecinos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'bicicletas_compartidas' => [
                'name' => __('Bicicletas Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de préstamo y uso compartido de bicicletas comunitarias.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'carpooling' => [
                'name' => __('Carpooling', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de viajes compartidos para reducir costes y emisiones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'chat_grupos' => [
                'name' => __('Chat de Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Canales de chat grupales para comunicación comunitaria.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'chat_interno' => [
                'name' => __('Chat Interno', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Mensajería directa entre usuarios de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'compostaje' => [
                'name' => __('Compostaje Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de composteras comunitarias y recogida de residuos orgánicos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'energia_comunitaria' => [
                'name' => __('Energia Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de comunidades energéticas, instalaciones, reparto, cierres y liquidaciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'cursos' => [
                'name' => __('Cursos y Formación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Plataforma de cursos y formación continua comunitaria.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'empresarial' => [
                'name' => __('Gestión Empresarial', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Herramientas de gestión empresarial: CRM, proyectos, tareas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'espacios_comunes' => [
                'name' => __('Espacios Comunes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Reserva y gestión de espacios comunitarios compartidos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'huertos_urbanos' => [
                'name' => __('Huertos Urbanos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de parcelas, riego y cosechas en huertos urbanos comunitarios.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'multimedia' => [
                'name' => __('Multimedia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Galería multimedia compartida: fotos, vídeos y documentos de la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'parkings' => [
                'name' => __('Parkings Compartidos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de plazas de aparcamiento compartidas entre vecinos.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'podcast' => [
                'name' => __('Podcast', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Plataforma de podcast comunitario con episodios y suscripciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'radio' => [
                'name' => __('Radio Comunitaria', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Emisora de radio comunitaria con programación y emisión en directo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'reciclaje' => [
                'name' => __('Reciclaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Puntos de reciclaje, recogida selectiva y estadísticas ambientales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'red_social' => [
                'name' => __('Red Social', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Red social interna con publicaciones, perfiles y seguidores.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'talleres' => [
                'name' => __('Talleres Prácticos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Talleres prácticos y workshops organizados por y para la comunidad.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'tramites' => [
                'name' => __('Trámites y Gestiones', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Sistema de gestión de trámites administrativos y solicitudes ciudadanas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'transparencia' => [
                'name' => __('Portal de Transparencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Portal de transparencia con datos públicos, presupuestos y rendición de cuentas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'colectivos' => [
                'name' => __('Colectivos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de colectivos y asociaciones locales.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'foros' => [
                'name' => __('Foros', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Foros de discusión comunitarios por temáticas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'clientes' => [
                'name' => __('Clientes', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de clientes y CRM integrado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'comunidades' => [
                'name' => __('Comunidades', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de comunidades y sub-comunidades.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'bares' => [
                'name' => __('Bares y Hostelería', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Gestión de bares, restaurantes y hostelería local.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'trading_ia' => [
                'name' => __('Trading IA', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Herramientas de trading asistidas por inteligencia artificial.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'dex_solana' => [
                'name' => __('DEX Solana', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Exchange descentralizado en la blockchain de Solana.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
            'themacle' => [
                'name' => __('Themacle Web Components', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'description' => __('Componentes web universales reutilizables para el constructor de páginas: heros, grids, galerías, CTAs y más.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'requires' => null,
            ],
        ];

        foreach ($available_modules as $module_id => $module_data) {
            if (!isset($known_modules[$module_id])) {
                $known_modules[$module_id] = [
                    'name' => $module_data['name'] ?? ucwords(str_replace(['_', '-'], ' ', $module_id)),
                    'description' => $module_data['description'] ?? '',
                    'requires' => null,
                ];
            }
        }

        // Combinar información
        foreach ($known_modules as $id => &$module) {
            if (empty($module['name'])) {
                $module['name'] = ucwords(str_replace(['_', '-'], ' ', $id));
            }
            if (!isset($module['description']) || $module['description'] === '') {
                $module['description'] = $available_modules[$id]['description'] ?? __('Módulo disponible para activar.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            }

            if (isset($available_modules[$id])) {
                // Si el módulo está en available_modules pero no puede activarse por falta de tablas,
                // permitir activarlo de todas formas si no tiene dependencias externas
                if (!$available_modules[$id]['can_activate'] && !$module['requires']) {
                    $module['can_activate'] = true;
                    $module['activation_error'] = __('Las tablas se crearán automáticamente al activar', FLAVOR_PLATFORM_TEXT_DOMAIN);
                } else {
                    $module['can_activate'] = $available_modules[$id]['can_activate'];
                    $module['activation_error'] = $available_modules[$id]['activation_error'];
                }
                $module['is_loaded'] = $available_modules[$id]['is_loaded'];
            } else {
                // Si no está en available_modules, verificar solo dependencias externas
                $module['can_activate'] = !$module['requires'] || class_exists($module['requires']);
                $module['activation_error'] = $module['requires'] && !class_exists($module['requires'])
                    ? sprintf(__('Requiere %s instalado', FLAVOR_PLATFORM_TEXT_DOMAIN), $module['requires'])
                    : '';
                $module['is_loaded'] = false;
            }
        }
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="modules">

        <h2><?php esc_html_e('Módulos Web del Portal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Selecciona qué módulos se cargan en WordPress, en el portal web y en las integraciones base del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <div class="notice notice-info inline" style="margin: 20px 0;">
            <p>
                <strong><?php esc_html_e('Importante:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <?php esc_html_e('Los módulos activos se mostrarán en las apps móviles y sus funcionalidades estarán disponibles vía API.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($known_modules as $module_id => $module_data):
                    $is_active = in_array($module_id, $active_modules);
                    $can_activate = $module_data['can_activate'];
                ?>
                <tr>
                    <td>
                        <input type="checkbox"
                               name="flavor_chat_ia_settings[active_modules][]"
                               value="<?php echo esc_attr($module_id); ?>"
                               <?php checked($is_active); ?>
                               <?php disabled(!$can_activate); ?>
                               id="module_<?php echo esc_attr($module_id); ?>">
                    </td>
                    <td>
                        <label for="module_<?php echo esc_attr($module_id); ?>">
                            <strong><?php echo esc_html($module_data['name']); ?></strong>
                        </label>
                        <?php if ($is_active): ?>
                            <br>
                            <button type="button"
                                    class="button button-small"
                                    onclick="document.getElementById('module-config-<?php echo esc_attr($module_id); ?>').style.display='block';">
                                ⚙️ <?php esc_html_e('Configurar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html($module_data['description']); ?>
                    </td>
                    <td>
                        <?php if ($can_activate): ?>
                            <?php if ($module_data['is_loaded']): ?>
                                <span style="color: #46b450;">✓ <?php esc_html_e('Cargado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php elseif ($is_active): ?>
                                <span style="color: #0073aa;">○ <?php esc_html_e('Activo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php else: ?>
                                <span style="color: #999;">○ <?php esc_html_e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #dc3232;">✗ <?php esc_html_e('No disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                            <?php if ($module_data['activation_error']): ?>
                                <br><small><?php echo esc_html($module_data['activation_error']); ?></small>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($is_active): ?>
                <tr id="module-config-<?php echo esc_attr($module_id); ?>" style="display:none;">
                    <td colspan="4" style="background: #f9f9f9; padding: 20px;">
                        <?php $this->render_module_config($module_id, $module_data); ?>
                    </td>
                </tr>
                <?php endif; ?>

                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="notice notice-warning inline" style="margin-top: 20px;">
            <p>
                <strong><?php esc_html_e('Nota:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong>
                <?php esc_html_e('Los módulos marcados como "No disponible" requieren que instales primero el plugin o extensión correspondiente.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
        </div>

        <h3 style="margin-top: 30px;"><?php esc_html_e('Endpoints de API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p><?php esc_html_e('Las apps móviles acceden a estos endpoints para consumir los módulos activos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Módulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Información del sitio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/info')); ?></code></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Lista de módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/modules')); ?></code></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Tema y colores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/theme')); ?></code></td>
                </tr>
                <?php foreach ($known_modules as $module_id => $module_data):
                    if (in_array($module_id, $active_modules)):
                ?>
                <tr>
                    <td><?php echo esc_html($module_data['name']); ?></td>
                    <td><code><?php echo esc_url(rest_url(FLAVOR_PLATFORM_REST_NAMESPACE . "/{$module_id}/")); ?></code></td>
                </tr>
                <?php
                    endif;
                endforeach;
                ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * Renderiza la configuración específica de un módulo
     */
    private function render_module_config($module_id, $module_data) {
        $module_settings = flavor_get_module_settings($module_id);

        ?>
        <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">
                    <?php printf(__('Configuración de %s', FLAVOR_PLATFORM_TEXT_DOMAIN), $module_data['name']); ?>
                </h3>
                <button type="button" class="button"
                        onclick="document.getElementById('module-config-<?php echo esc_attr($module_id); ?>').style.display='none';">
                    <?php esc_html_e('Cerrar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </button>
            </div>

            <?php
            switch ($module_id) {
                case 'banco_tiempo':
                    $this->render_banco_tiempo_config($module_settings);
                    break;
                case 'grupos_consumo':
                    $this->render_grupos_consumo_config($module_settings);
                    break;
                case 'marketplace':
                    $this->render_marketplace_config($module_settings);
                    break;
                case 'woocommerce':
                    $this->render_woocommerce_config($module_settings);
                    break;
                case 'facturas':
                    $this->render_facturas_config($module_settings);
                    break;
                case 'fichaje_empleados':
                    $this->render_fichaje_empleados_config($module_settings);
                    break;
                case 'eventos':
                    $this->render_eventos_config($module_settings);
                    break;
                case 'socios':
                    $this->render_socios_config($module_settings);
                    break;
                case 'incidencias':
                    $this->render_incidencias_config($module_settings);
                    break;
                case 'participacion':
                    $this->render_participacion_config($module_settings);
                    break;
                case 'presupuestos_participativos':
                    $this->render_presupuestos_participativos_config($module_settings);
                    break;
                case 'avisos_municipales':
                    $this->render_avisos_municipales_config($module_settings);
                    break;
                default:
                    $this->render_generic_module_config($module_id, $module_settings);
            }
            ?>
        </div>
        <?php
    }

    /**
     * Configuración del módulo Banco de Tiempo
     */
    private function render_banco_tiempo_config($settings) {
        $hora_min = $settings['hora_minima_intercambio'] ?? 0.5;
        $hora_max = $settings['hora_maxima_intercambio'] ?? 8;
        $requiere_validacion = $settings['requiere_validacion'] ?? true;
        $saldo_inicial = $settings['saldo_inicial_horas'] ?? 5;
        $permite_negativos = $settings['permite_saldo_negativo'] ?? false;
        $limite_negativo = $settings['limite_saldo_negativo'] ?? -10;
        $notificar_saldo_bajo = $settings['notificar_saldo_bajo'] ?? true;
        $umbral_notificacion = $settings['umbral_notificacion_saldo'] ?? 2;
        ?>
        <div class="info-box info">
            <span class="dashicons dashicons-info info-box-icon"></span>
            <div class="info-box-content">
                <p><strong><?php esc_html_e('Banco de Tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                <p><?php esc_html_e('Sistema de intercambio de servicios donde el tiempo es la moneda. Cada hora de servicio ofrecido = 1 hora de tiempo que puedes recibir.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('⏱️ Configuración de Intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Horas mínimas por intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[hora_minima_intercambio]"
                           value="<?php echo esc_attr($hora_min); ?>"
                           step="0.5"
                           min="0.5"
                           max="24"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Duración mínima de un intercambio de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horas máximas por intercambio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[hora_maxima_intercambio]"
                           value="<?php echo esc_attr($hora_max); ?>"
                           step="0.5"
                           min="1"
                           max="24"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Duración máxima de un intercambio de servicios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Validación de intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[requiere_validacion]"
                               value="1"
                               <?php checked($requiere_validacion); ?>>
                        <?php esc_html_e('Requiere que ambas partes confirmen el intercambio realizado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('💰 Gestión de Saldos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Saldo inicial (horas)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[saldo_inicial_horas]"
                           value="<?php echo esc_attr($saldo_inicial); ?>"
                           step="1"
                           min="0"
                           max="50"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Horas que reciben los nuevos miembros al unirse al banco de tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir saldo negativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[permite_saldo_negativo]"
                               value="1"
                               <?php checked($permite_negativos); ?>
                               id="permite_saldo_negativo">
                        <?php esc_html_e('Los usuarios pueden recibir servicios aunque no tengan horas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Límite de saldo negativo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[limite_saldo_negativo]"
                           value="<?php echo esc_attr($limite_negativo); ?>"
                           step="1"
                           min="-50"
                           max="0"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Máximo de horas negativas permitidas (ej: -10 significa que pueden deber hasta 10 horas)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('🔔 Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Notificar saldo bajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[notificar_saldo_bajo]"
                               value="1"
                               <?php checked($notificar_saldo_bajo); ?>>
                        <?php esc_html_e('Avisar a los usuarios cuando su saldo sea bajo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Umbral de notificación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[umbral_notificacion_saldo]"
                           value="<?php echo esc_attr($umbral_notificacion); ?>"
                           step="1"
                           min="0"
                           max="10"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Notificar cuando el saldo esté por debajo de este número de horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Grupos de Consumo
     */
    private function render_grupos_consumo_config($settings) {
        $dias_pedido = $settings['dias_para_pedido'] ?? 7;
        $pedido_minimo = $settings['pedido_minimo'] ?? 0;
        $participantes_minimos = $settings['participantes_minimos'] ?? 5;
        $permite_multiples = $settings['permite_multiples_pedidos'] ?? true;
        $coordina_reparto = $settings['coordina_reparto'] ?? true;
        $requiere_pago = $settings['requiere_pago_anticipado'] ?? false;
        $gastos_gestion = $settings['porcentaje_gastos_gestion'] ?? 0;
        ?>
        <div class="info-box success">
            <span class="dashicons dashicons-groups info-box-icon"></span>
            <div class="info-box-content">
                <p><strong><?php esc_html_e('Grupos de Consumo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                <p><?php esc_html_e('Organiza pedidos colectivos a productores locales. Los miembros se unen a pedidos, comparten gastos de transporte y reciben productos frescos.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('📦 Configuración de Pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Días para realizar pedido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[dias_para_pedido]"
                           value="<?php echo esc_attr($dias_pedido); ?>"
                           min="1"
                           max="30"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Plazo para que los miembros se unan a un pedido colectivo antes de cerrarlo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Pedido mínimo por persona (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[pedido_minimo]"
                           value="<?php echo esc_attr($pedido_minimo); ?>"
                           step="0.01"
                           min="0"
                           class="small-text"> €
                    <p class="description"><?php esc_html_e('Importe mínimo que debe pedir cada participante', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Participantes mínimos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[participantes_minimos]"
                           value="<?php echo esc_attr($participantes_minimos); ?>"
                           min="2"
                           max="50"
                           class="small-text"> personas
                    <p class="description"><?php esc_html_e('Número mínimo de participantes para confirmar un pedido colectivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Múltiples pedidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[permite_multiples_pedidos]"
                               value="1"
                               <?php checked($permite_multiples); ?>>
                        <?php esc_html_e('Permitir varios pedidos abiertos al mismo tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('🚚 Logística y Reparto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Coordinar reparto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[coordina_reparto]"
                               value="1"
                               <?php checked($coordina_reparto); ?>>
                        <?php esc_html_e('Gestionar puntos de recogida y horarios desde la app', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Gastos de gestión (%)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[porcentaje_gastos_gestion]"
                           value="<?php echo esc_attr($gastos_gestion); ?>"
                           step="0.5"
                           min="0"
                           max="20"
                           class="small-text"> %
                    <p class="description"><?php esc_html_e('Porcentaje añadido para cubrir gastos de gestión y transporte', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('💳 Pagos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Pago anticipado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[requiere_pago_anticipado]"
                               value="1"
                               <?php checked($requiere_pago); ?>>
                        <?php esc_html_e('Requerir pago antes de confirmar participación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Marketplace
     */
    private function render_marketplace_config($settings) {
        $permite_venta = $settings['permite_venta'] ?? true;
        $permite_intercambio = $settings['permite_intercambio'] ?? true;
        $permite_regalo = $settings['permite_regalo'] ?? true;
        $requiere_moderacion = $settings['requiere_moderacion'] ?? false;
        $dias_vigencia = $settings['dias_vigencia_anuncio'] ?? 30;
        $max_fotos = $settings['max_fotos_por_anuncio'] ?? 5;
        $permite_reservas = $settings['permite_reservas'] ?? true;
        ?>
        <div class="info-box warning">
            <span class="dashicons dashicons-store info-box-icon"></span>
            <div class="info-box-content">
                <p><strong><?php esc_html_e('Marketplace Comunitario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                <p><?php esc_html_e('Plataforma para compartir, intercambiar y vender objetos entre vecinos. Fomenta la economía circular y reduce el desperdicio.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('🏷️ Tipos de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Tipos permitidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_venta]" value="1" <?php checked($permite_venta); ?>>
                        <?php esc_html_e('Venta - Objetos en venta con precio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_intercambio]" value="1" <?php checked($permite_intercambio); ?>>
                        <?php esc_html_e('Intercambio - Trueque de objetos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_regalo]" value="1" <?php checked($permite_regalo); ?>>
                        <?php esc_html_e('Regalo - Objetos gratuitos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('📋 Gestión de Anuncios', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Moderación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[requiere_moderacion]" value="1" <?php checked($requiere_moderacion); ?>>
                        <?php esc_html_e('Los anuncios requieren aprobación antes de publicarse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Días de vigencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_marketplace[dias_vigencia_anuncio]"
                           value="<?php echo esc_attr($dias_vigencia); ?>" min="7" max="90" class="small-text"> días
                    <p class="description"><?php esc_html_e('Los anuncios se archivarán automáticamente después de este tiempo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Máximo de fotos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_marketplace[max_fotos_por_anuncio]"
                           value="<?php echo esc_attr($max_fotos); ?>" min="1" max="10" class="small-text"> fotos
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Sistema de reservas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_reservas]" value="1" <?php checked($permite_reservas); ?>>
                        <?php esc_html_e('Permitir reservar objetos temporalmente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo WooCommerce
     */
    private function render_woocommerce_config($settings) {
        $mostrar_stock = $settings['mostrar_stock'] ?? true;
        $limite_productos = $settings['limite_productos_busqueda'] ?? 10;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Mostrar stock disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_woocommerce[mostrar_stock]"
                               value="1"
                               <?php checked($mostrar_stock); ?>>
                        <?php esc_html_e('Mostrar información de stock en búsquedas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Productos por búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_woocommerce[limite_productos_busqueda]"
                           value="<?php echo esc_attr($limite_productos); ?>"
                           min="5"
                           max="50"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número máximo de productos a mostrar en cada búsqueda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Facturas
     */
    private function render_facturas_config($settings) {
        $serie = $settings['serie_predeterminada'] ?? 'F';
        $iva = $settings['iva_predeterminado'] ?? 21;
        $enviar_email = $settings['enviar_email_automatico'] ?? false;
        $formato_numero = $settings['formato_numero_factura'] ?? 'SERIE-YYYY-NNNN';
        $prefijo_rectificativa = $settings['prefijo_rectificativa'] ?? 'R';
        $dias_vencimiento = $settings['dias_vencimiento_predeterminado'] ?? 30;
        $texto_pie = $settings['texto_pie_factura'] ?? '';
        ?>
        <div class="info-box info">
            <span class="dashicons dashicons-media-spreadsheet info-box-icon"></span>
            <div class="info-box-content">
                <p><strong><?php esc_html_e('Gestión de Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></p>
                <p><?php esc_html_e('Sistema completo de facturación para administradores. Crea, envía y gestiona facturas directamente desde la app móvil.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('📄 Configuración de Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Serie predeterminada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_module_facturas[serie_predeterminada]"
                           value="<?php echo esc_attr($serie); ?>"
                           maxlength="3" class="small-text" style="text-transform: uppercase;">
                    <p class="description"><?php esc_html_e('Letra(s) para identificar la serie (ej: F, A, B)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Formato de numeración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_module_facturas[formato_numero_factura]">
                        <option value="SERIE-YYYY-NNNN" <?php selected($formato_numero, 'SERIE-YYYY-NNNN'); ?>>F-2025-0001</option>
                        <option value="SERIE-NNNN" <?php selected($formato_numero, 'SERIE-NNNN'); ?>>F-0001</option>
                        <option value="YYYY/NNNN" <?php selected($formato_numero, 'YYYY/NNNN'); ?>>2025/0001</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('IVA predeterminado (%)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <select name="flavor_chat_ia_module_facturas[iva_predeterminado]">
                        <option value="0" <?php selected($iva, 0); ?>>0% (Exento)</option>
                        <option value="4" <?php selected($iva, 4); ?>>4% (Superreducido)</option>
                        <option value="10" <?php selected($iva, 10); ?>>10% (Reducido)</option>
                        <option value="21" <?php selected($iva, 21); ?>>21% (General)</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Días de vencimiento', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_facturas[dias_vencimiento_predeterminado]"
                           value="<?php echo esc_attr($dias_vencimiento); ?>" min="0" max="180" class="small-text"> días
                    <p class="description"><?php esc_html_e('Plazo de pago predeterminado desde la emisión', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('📧 Envío de Facturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Envío automático por email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_facturas[enviar_email_automatico]" value="1" <?php checked($enviar_email); ?>>
                        <?php esc_html_e('Enviar factura por email automáticamente al crearla', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('⚙️ Opciones Avanzadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Prefijo facturas rectificativas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_module_facturas[prefijo_rectificativa]"
                           value="<?php echo esc_attr($prefijo_rectificativa); ?>"
                           maxlength="3" class="small-text" style="text-transform: uppercase;">
                    <p class="description"><?php esc_html_e('Letra para identificar facturas rectificativas (ej: R)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Texto pie de factura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_module_facturas[texto_pie_factura]"
                              rows="3" class="large-text"><?php echo esc_textarea($texto_pie); ?></textarea>
                    <p class="description"><?php esc_html_e('Texto adicional en el pie de todas las facturas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Fichaje de Empleados
     */
    private function render_fichaje_empleados_config($settings) {
        $horario_entrada = $settings['horario_entrada'] ?? '09:00';
        $horario_salida = $settings['horario_salida'] ?? '18:00';
        $tiempo_gracia = $settings['tiempo_gracia'] ?? 15;
        $requiere_gps = $settings['requiere_geolocalizacion'] ?? false;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Horario de entrada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="time"
                           name="flavor_chat_ia_module_fichaje_empleados[horario_entrada]"
                           value="<?php echo esc_attr($horario_entrada); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario de salida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="time"
                           name="flavor_chat_ia_module_fichaje_empleados[horario_salida]"
                           value="<?php echo esc_attr($horario_salida); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Tiempo de gracia (minutos)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_fichaje_empleados[tiempo_gracia]"
                           value="<?php echo esc_attr($tiempo_gracia); ?>"
                           min="0"
                           max="60"
                           class="small-text"> minutos
                    <p class="description"><?php esc_html_e('Minutos de margen antes de marcar retraso', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Requiere geolocalización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_fichaje_empleados[requiere_geolocalizacion]"
                               value="1"
                               <?php checked($requiere_gps); ?>>
                        <?php esc_html_e('Obligar a activar GPS para fichar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Eventos
     */
    private function render_eventos_config($settings) {
        $requiere_aprobacion = $settings['requiere_aprobacion'] ?? false;
        $permite_invitados = $settings['permite_invitados'] ?? true;
        $dias_recordatorio = $settings['dias_recordatorio'] ?? 1;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Eventos requieren aprobación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_eventos[requiere_aprobacion]"
                               value="1"
                               <?php checked($requiere_aprobacion); ?>>
                        <?php esc_html_e('Los eventos creados por usuarios deben ser aprobados por administrador', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir acompañantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_eventos[permite_invitados]"
                               value="1"
                               <?php checked($permite_invitados); ?>>
                        <?php esc_html_e('Los asistentes pueden registrar acompañantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Días de recordatorio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_eventos[dias_recordatorio]"
                           value="<?php echo esc_attr($dias_recordatorio); ?>"
                           min="0"
                           max="7"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Días antes del evento para enviar recordatorio (0 = desactivado)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Socios
     */
    private function render_socios_config($settings) {
        $cuota_mensual = $settings['cuota_mensual'] ?? 30.00;
        $cuota_anual = $settings['cuota_anual'] ?? 300.00;
        $dia_cargo = $settings['dia_cargo'] ?? 1;
        $permite_reducida = $settings['permite_cuota_reducida'] ?? true;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Cuota mensual (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_socios[cuota_mensual]"
                           value="<?php echo esc_attr($cuota_mensual); ?>"
                           step="0.01"
                           min="0"
                           class="small-text"> €
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Cuota anual (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_socios[cuota_anual]"
                           value="<?php echo esc_attr($cuota_anual); ?>"
                           step="0.01"
                           min="0"
                           class="small-text"> €
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Día de cargo mensual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_socios[dia_cargo]"
                           value="<?php echo esc_attr($dia_cargo); ?>"
                           min="1"
                           max="28"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Día del mes para cargo automático de cuotas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir cuota reducida', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_socios[permite_cuota_reducida]"
                               value="1"
                               <?php checked($permite_reducida); ?>>
                        <?php esc_html_e('Permitir cuotas reducidas para casos especiales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Incidencias
     */
    private function render_incidencias_config($settings) {
        $requiere_gps = $settings['requiere_ubicacion_gps'] ?? true;
        $requiere_foto = $settings['requiere_foto'] ?? false;
        $visibilidad = $settings['visibilidad_publica'] ?? true;
        $votos_urgencia = $settings['votos_para_urgencia'] ?? 5;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Requiere ubicación GPS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[requiere_ubicacion_gps]"
                               value="1"
                               <?php checked($requiere_gps); ?>>
                        <?php esc_html_e('Las incidencias deben incluir ubicación GPS', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Requiere fotografía', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[requiere_foto]"
                               value="1"
                               <?php checked($requiere_foto); ?>>
                        <?php esc_html_e('Las incidencias deben incluir al menos una foto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Visibilidad pública', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[visibilidad_publica]"
                               value="1"
                               <?php checked($visibilidad); ?>>
                        <?php esc_html_e('Las incidencias son visibles públicamente para todos los vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos para marcar como urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_incidencias[votos_para_urgencia]"
                           value="<?php echo esc_attr($votos_urgencia); ?>"
                           min="1"
                           max="50"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número de votos necesarios para cambiar prioridad a urgente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Participación
     */
    private function render_participacion_config($settings) {
        $requiere_verificacion = $settings['requiere_verificacion'] ?? true;
        $votos_necesarios = $settings['votos_necesarios_propuesta'] ?? 10;
        $duracion_votacion = $settings['duracion_votacion_dias'] ?? 7;
        $moderacion = $settings['moderacion_propuestas'] ?? true;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Verificación de vecinos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_participacion[requiere_verificacion]"
                               value="1"
                               <?php checked($requiere_verificacion); ?>>
                        <?php esc_html_e('Solo vecinos verificados pueden votar y crear propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Moderación de propuestas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_participacion[moderacion_propuestas]"
                               value="1"
                               <?php checked($moderacion); ?>>
                        <?php esc_html_e('Las propuestas requieren aprobación del ayuntamiento antes de publicarse', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos necesarios para propuesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_participacion[votos_necesarios_propuesta]"
                           value="<?php echo esc_attr($votos_necesarios); ?>"
                           min="5"
                           max="500"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Apoyos mínimos para que una propuesta sea evaluada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Duración de votaciones (días)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_participacion[duracion_votacion_dias]"
                           value="<?php echo esc_attr($duracion_votacion); ?>"
                           min="1"
                           max="30"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Tiempo por defecto para las votaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Presupuestos Participativos
     */
    private function render_presupuestos_participativos_config($settings) {
        $presupuesto_anual = $settings['presupuesto_anual'] ?? 50000.00;
        $votos_maximos = $settings['votos_maximos_por_persona'] ?? 3;
        $proyecto_minimo = $settings['proyecto_monto_minimo'] ?? 1000.00;
        $proyecto_maximo = $settings['proyecto_monto_maximo'] ?? 15000.00;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Presupuesto anual (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[presupuesto_anual]"
                           value="<?php echo esc_attr($presupuesto_anual); ?>"
                           step="1000"
                           min="0"
                           class="regular-text"> €
                    <p class="description"><?php esc_html_e('Presupuesto total disponible para proyectos ciudadanos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos máximos por persona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[votos_maximos_por_persona]"
                           value="<?php echo esc_attr($votos_maximos); ?>"
                           min="1"
                           max="10"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número máximo de proyectos que puede votar cada persona', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Monto mínimo de proyecto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[proyecto_monto_minimo]"
                           value="<?php echo esc_attr($proyecto_minimo); ?>"
                           step="100"
                           min="0"
                           class="regular-text"> €
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Monto máximo de proyecto (€)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[proyecto_monto_maximo]"
                           value="<?php echo esc_attr($proyecto_maximo); ?>"
                           step="1000"
                           min="0"
                           class="regular-text"> €
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración del módulo Avisos Municipales
     */
    private function render_avisos_municipales_config($settings) {
        $enviar_push = $settings['enviar_push_notifications'] ?? true;
        $requiere_confirmacion = $settings['requiere_confirmacion_lectura'] ?? false;
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Notificaciones push', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_avisos_municipales[enviar_push_notifications]"
                               value="1"
                               <?php checked($enviar_push); ?>>
                        <?php esc_html_e('Enviar notificaciones push para avisos urgentes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Confirmación de lectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_avisos_municipales[requiere_confirmacion_lectura]"
                               value="1"
                               <?php checked($requiere_confirmacion); ?>>
                        <?php esc_html_e('Requerir confirmación de lectura en avisos importantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <p class="description">
                        <?php esc_html_e('Categorías disponibles: Urgente, Corte de servicio, Evento, Informativo, Tráfico, Obras, Convocatoria', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Configuración genérica de un módulo basada en get_default_settings()
     */
    private function render_generic_module_config($module_id, $saved_settings) {
        // Intentar cargar el módulo para obtener sus settings por defecto
        $loader = class_exists('Flavor_Platform_Module_Loader') ? Flavor_Platform_Module_Loader::get_instance() : null;
        $modulos_registrados = $loader ? $loader->get_registered_modules() : [];
        $default_settings = [];

        if (isset($modulos_registrados[$module_id])) {
            $module_file = FLAVOR_PLATFORM_PATH . 'includes/modules/' . str_replace('_', '-', $module_id) . '/class-' . str_replace('_', '-', $module_id) . '-module.php';
            if (file_exists($module_file)) {
                require_once $module_file;
                $class_name = $modulos_registrados[$module_id]['name'] ?? '';
                // Obtener las claves de settings desde la opción guardada o los defaults del módulo
                $default_settings = flavor_get_module_settings($module_id);
            }
        }

        $settings = wp_parse_args($saved_settings, $default_settings);

        if (empty($settings)) {
            ?>
            <p><?php esc_html_e('Este módulo no tiene configuraciones adicionales.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php
            return;
        }

        // Mapeo de nombres legibles para las claves de configuración
        $etiquetas = [
            'disponible_app' => __('Disponible en', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_verificacion_usuarios' => __('Requiere verificación de usuarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_verificacion_usuario' => __('Requiere verificación de usuario', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_verificacion_conductor' => __('Requiere verificación de conductor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_valoraciones' => __('Permite valoraciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sistema_puntos_solidaridad' => __('Sistema de puntos solidarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sistema_puntos' => __('Sistema de puntos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'puntos_por_ayuda' => __('Puntos por ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'puntos_por_prestamo' => __('Puntos por préstamo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'puntos_por_kg' => __('Puntos por Kg', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'puntos_por_kg_depositado' => __('Puntos por Kg depositado', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_donaciones' => __('Permite donaciones', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_intercambios' => __('Permite intercambios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_prestamos' => __('Permite préstamos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion_prestamo_dias' => __('Duración del préstamo (días)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'renovaciones_maximas' => __('Renovaciones máximas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_reservas' => __('Permite reservas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_reservas_anticipadas' => __('Permite reservas anticipadas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_reservas_recurrentes' => __('Permite reservas recurrentes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_verificacion_isbn' => __('Requiere verificación ISBN', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_fianza' => __('Requiere fianza', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'importe_fianza' => __('Importe de fianza (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'importe_fianza_predeterminado' => __('Fianza predeterminada (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_hora' => __('Precio por hora (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_dia' => __('Precio por día (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_mes' => __('Precio por mes (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_medio_hora' => __('Precio medio/hora (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_medio_dia' => __('Precio medio/día (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_medio_mes' => __('Precio medio/mes (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_por_km' => __('Precio por Km (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'precio_parcela_anual' => __('Precio parcela anual (€)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion_maxima_prestamo_dias' => __('Duración máxima préstamo (días)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion_maxima_horas' => __('Duración máxima (horas)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion_maxima_programa' => __('Duración máxima programa (min)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'duracion_maxima_minutos' => __('Duración máxima (min)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'horas_anticipacion_reserva' => __('Horas anticipación reserva', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'horas_anticipacion_minima' => __('Horas anticipación mínima', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'horas_anticipacion_cancelacion' => __('Horas anticipación cancelación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'dias_anticipacion_maxima' => __('Días anticipación máxima', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'dias_anticipacion_cancelacion' => __('Días anticipación cancelación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_aprobacion_organizadores' => __('Requiere aprobación de organizadores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_aprobacion_instructores' => __('Requiere aprobación de instructores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_aprobacion_programas' => __('Requiere aprobación de programas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_talleres_gratuitos' => __('Permite talleres gratuitos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_talleres_pago' => __('Permite talleres de pago', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_cursos_gratuitos' => __('Permite cursos gratuitos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_cursos_pago' => __('Permite cursos de pago', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_cursos_online' => __('Permite cursos online', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_cursos_presenciales' => __('Permite cursos presenciales', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_certificados' => __('Permite certificados', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_evaluacion' => __('Requiere evaluación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comision_talleres_pago' => __('Comisión talleres de pago (%)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comision_cursos_pago' => __('Comisión cursos de pago (%)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'comision_plataforma_porcentaje' => __('Comisión plataforma (%)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'max_participantes_por_taller' => __('Máx. participantes por taller', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'max_alumnos_por_curso' => __('Máx. alumnos por curso', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'max_pasajeros_por_viaje' => __('Máx. pasajeros por viaje', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'min_participantes_para_confirmar' => __('Mín. participantes para confirmar', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_lista_espera' => __('Permite lista de espera', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_mascotas' => __('Permite mascotas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_equipaje_grande' => __('Permite equipaje grande', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'radio_busqueda_km' => __('Radio de búsqueda (Km)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'calculo_coste_automatico' => __('Cálculo de coste automático', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_subir' => __('Permite subir contenido', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_subir_episodios' => __('Permite subir episodios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_moderacion' => __('Requiere moderación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_comentarios' => __('Permite comentarios', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_albumes' => __('Permite álbumes', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_geolocalizacion' => __('Permite geolocalización', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'genera_thumbnails' => __('Genera miniaturas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'genera_rss' => __('Genera feed RSS', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'transcripcion_automatica' => __('Transcripción automática', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'max_tamano_imagen_mb' => __('Tamaño máx. imagen (MB)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'max_tamano_video_mb' => __('Tamaño máx. vídeo (MB)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'tamano_maximo_mb' => __('Tamaño máximo (MB)', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_fotos' => __('Requiere fotos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'notificar_liberacion' => __('Notificar liberación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'notificar_mantenimiento' => __('Notificar mantenimiento', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'notificar_recogidas' => __('Notificar recogidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'notificar_compost_listo' => __('Notificar compost listo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_alquiler_temporal' => __('Permite alquiler temporal', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_alquiler_permanente' => __('Permite alquiler permanente', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_reportar_problemas' => __('Permite reportar problemas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_reportar_contenedores' => __('Permite reportar contenedores', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_canje_puntos' => __('Permite canje de puntos', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_recoger_compost' => __('Permite recoger compost', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_solicitar_parcela' => __('Permite solicitar parcela', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_intercambio_cosechas' => __('Permite intercambio de cosechas', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_locutores_comunidad' => __('Permite locutores de la comunidad', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'permite_dedicatorias' => __('Permite dedicatorias', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'chat_en_vivo' => __('Chat en vivo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'grabacion_automatica' => __('Grabación automática', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'url_stream' => __('URL del stream', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'frecuencia_fm' => __('Frecuencia FM', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'kg_minimos_recogida' => __('Kg mínimos para recogida', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sistema_turnos_volteo' => __('Sistema de turnos de volteo', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'sistema_turnos_riego' => __('Sistema de turnos de riego', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'requiere_compromiso_asistencia' => __('Requiere compromiso de asistencia', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'horas_minimas_mes' => __('Horas mínimas al mes', FLAVOR_PLATFORM_TEXT_DOMAIN),
        ];

        ?>
        <table class="form-table">
            <?php foreach ($settings as $clave_ajuste => $valor_ajuste):
                // Saltar arrays complejos y la clave disponible_app (se renderiza como select)
                if (is_array($valor_ajuste) && !empty($valor_ajuste) && !is_numeric(array_key_first($valor_ajuste))) {
                    continue;
                }
                $etiqueta = $etiquetas[$clave_ajuste] ?? ucfirst(str_replace('_', ' ', $clave_ajuste));
                $nombre_campo = "flavor_chat_ia_module_{$module_id}[{$clave_ajuste}]";
            ?>
            <tr>
                <th><?php echo esc_html($etiqueta); ?></th>
                <td>
                    <?php if ($clave_ajuste === 'disponible_app'): ?>
                        <select name="<?php echo esc_attr($nombre_campo); ?>">
                            <option value="cliente" <?php selected($valor_ajuste, 'cliente'); ?>><?php esc_html_e('App cliente', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="admin" <?php selected($valor_ajuste, 'admin'); ?>><?php esc_html_e('App admin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                            <option value="ambas" <?php selected($valor_ajuste, 'ambas'); ?>><?php esc_html_e('Ambas apps', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                        </select>
                    <?php elseif (is_bool($valor_ajuste) || ($valor_ajuste === 1 || $valor_ajuste === 0 || $valor_ajuste === '1' || $valor_ajuste === '0') && !is_numeric($clave_ajuste) && strpos($clave_ajuste, 'precio') === false && strpos($clave_ajuste, 'importe') === false && strpos($clave_ajuste, 'puntos') === false && strpos($clave_ajuste, 'max') === false && strpos($clave_ajuste, 'min') === false && strpos($clave_ajuste, 'horas') === false && strpos($clave_ajuste, 'dias') === false && strpos($clave_ajuste, 'kg') === false && strpos($clave_ajuste, 'comision') === false && strpos($clave_ajuste, 'radio') === false && strpos($clave_ajuste, 'tamano') === false && strpos($clave_ajuste, 'duracion') === false): ?>
                        <label>
                            <input type="checkbox"
                                   name="<?php echo esc_attr($nombre_campo); ?>"
                                   value="1"
                                   <?php checked($valor_ajuste); ?>>
                            <?php esc_html_e('Activado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    <?php elseif (is_numeric($valor_ajuste) && !is_string($valor_ajuste)): ?>
                        <input type="number"
                               name="<?php echo esc_attr($nombre_campo); ?>"
                               value="<?php echo esc_attr($valor_ajuste); ?>"
                               step="<?php echo (floor($valor_ajuste) != $valor_ajuste) ? '0.01' : '1'; ?>"
                               class="small-text">
                    <?php elseif (is_array($valor_ajuste)): ?>
                        <input type="text"
                               name="<?php echo esc_attr($nombre_campo); ?>"
                               value="<?php echo esc_attr(implode(', ', $valor_ajuste)); ?>"
                               class="regular-text">
                        <p class="description"><?php esc_html_e('Separar con comas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    <?php else: ?>
                        <input type="text"
                               name="<?php echo esc_attr($nombre_campo); ?>"
                               value="<?php echo esc_attr($valor_ajuste); ?>"
                               class="regular-text">
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    /**
     * Pestaña Analíticas
     */

    /**
     * Renderiza la pestana de Firebase Push Notifications
     */
    private function render_firebase_push_tab() {
        $firebase_config = get_option('flavor_firebase_config', []);
        $project_id = $firebase_config['project_id'] ?? '';
        $service_account_json = $firebase_config['service_account_json'] ?? '';
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="firebase_push" />

        <div class="flavor-settings-section">
            <h2><?php esc_html_e('Firebase Push Notifications', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description"><?php esc_html_e('Configura Firebase Cloud Messaging para enviar notificaciones push a la app Flutter.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="flavor_firebase_project_id"><?php esc_html_e('Project ID de Firebase', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <input type="text" id="flavor_firebase_project_id"
                               name="flavor_chat_ia_settings[flavor_firebase_project_id]"
                               value="<?php echo esc_attr($project_id); ?>"
                               class="regular-text"
                               placeholder="mi-proyecto-firebase" />
                        <p class="description"><?php esc_html_e('El ID del proyecto en Firebase Console (Settings > General).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="flavor_firebase_service_account"><?php esc_html_e('Service Account JSON', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    </th>
                    <td>
                        <textarea id="flavor_firebase_service_account"
                                  name="flavor_chat_ia_settings[flavor_firebase_service_account]"
                                  rows="10" cols="60"
                                  class="large-text code"
                                  placeholder='{"type": "service_account", "project_id": "...", ...}'><?php echo esc_textarea($service_account_json); ?></textarea>
                        <p class="description"><?php esc_html_e('Pega aqui el contenido completo del archivo JSON de la Service Account de Firebase. Obtenlo en Firebase Console > Settings > Service Accounts > Generate New Private Key.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Estado de la configuracion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Project ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php if (!empty($project_id)): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                            <code><?php echo esc_html($project_id); ?></code>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                            <?php esc_html_e('No configurado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Service Account', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php
                        if (!empty($service_account_json)) {
                            $sa_data = json_decode($service_account_json, true);
                            if ($sa_data && isset($sa_data['client_email'])) {
                                echo '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> ';
                                echo esc_html($sa_data['client_email']);
                            } else {
                                echo '<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span> ';
                                esc_html_e('JSON invalido', FLAVOR_PLATFORM_TEXT_DOMAIN);
                            }
                        } else {
                            echo '<span class="dashicons dashicons-warning" style="color: #dba617;"></span> ';
                            esc_html_e('No configurado', FLAVOR_PLATFORM_TEXT_DOMAIN);
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Extension OpenSSL', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td>
                        <?php if (extension_loaded('openssl')): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                            <?php esc_html_e('Disponible', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>
                            <?php esc_html_e('No disponible - Requerida para firmar JWT', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Prueba de envio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="description"><?php esc_html_e('Envia una notificacion de prueba a tu usuario actual (debes tener un token FCM registrado).', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <button type="button" id="flavor-test-push-notification" class="button button-secondary">
                <?php esc_html_e('Enviar notificacion de prueba', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <span id="flavor-push-test-result" style="margin-left: 10px;"></span>

            <script type="text/javascript">
            jQuery(document).ready(function($jq) {
                $jq('#flavor-test-push-notification').on('click', function() {
                    var $btn = $jq(this);
                    var $result = $jq('#flavor-push-test-result');
                    $btn.prop('disabled', true);
                    $result.text('Enviando...');
                    $jq.ajax({
                        url: flavorChatAdmin.ajaxUrl,
                        method: 'POST',
                        data: {
                            action: 'flavor_test_push_notification',
                            nonce: flavorChatAdmin.nonce
                        },
                        success: function(response) {
                            if (response.success) {
                                $result.html('<span style="color:#00a32a;">' + response.data.message + '</span>');
                            } else {
                                $result.html('<span style="color:#d63638;">' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            $result.html('<span style="color:#d63638;">Error de conexion</span>');
                        },
                        complete: function() {
                            $btn.prop('disabled', false);
                        }
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    private function render_analytics_tab() {
        ?>
        <div class="analytics-period" style="margin-bottom:20px;">
            <label for="analytics-period"><?php esc_html_e('Período:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
            <select id="analytics-period">
                <option value="day"><?php esc_html_e('Hoy', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="week" selected><?php esc_html_e('Últimos 7 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                <option value="month"><?php esc_html_e('Últimos 30 días', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
            </select>
            <button type="button" id="refresh-analytics" class="button"><?php esc_html_e('Actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        </div>

        <div class="analytics-grid" id="analytics-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;">
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-conversations" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Mensajes Totales', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-messages" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Promedio msgs/conv', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-avg-messages" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Escaladas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-escalated" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Conversiones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-conversions" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Tokens Usados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
                <div class="analytics-value" id="stat-tokens" style="font-size:28px;font-weight:bold;">-</div>
            </div>
        </div>

        <script>
        jQuery(function($) {
            function loadAnalytics() {
                var period = $('#analytics-period').val();
                $.post(flavorChatAdmin.ajaxUrl, {
                    action: 'flavor_chat_get_analytics',
                    nonce: flavorChatAdmin.nonce,
                    period: period
                }, function(response) {
                    if (response.success) {
                        var data = response.data;
                        $('#stat-conversations').text(data.conversations || 0);
                        $('#stat-messages').text(data.messages || 0);
                        $('#stat-avg-messages').text(data.avg_messages || '0');
                        $('#stat-escalated').text(data.escalated || 0);
                        $('#stat-conversions').text(data.conversions || 0);
                        $('#stat-tokens').text(data.tokens || 0);
                    }
                });
            }

            loadAnalytics();
            $('#refresh-analytics, #analytics-period').on('click change', loadAnalytics);
        });
        </script>
        <?php
    }

    /**
     * Renderiza la página de escalados
     */
    public function render_escalations_page() {
        if (!current_user_can('manage_options')) return;

        $escalation = Flavor_Platform_Escalation::get_instance();
        $escalations = $escalation->get_pending_escalations();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Solicitudes de atención', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>

            <?php if (empty($escalations)): ?>
                <p><?php esc_html_e('No hay solicitudes pendientes.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Motivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php esc_html_e('Acciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($escalations as $esc): ?>
                        <tr>
                            <td>#<?php echo esc_html($esc['id']); ?></td>
                            <td><?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($esc['created_at']))); ?></td>
                            <td><?php echo esc_html(wp_trim_words($esc['reason'], 10)); ?></td>
                            <td><?php echo esc_html(ucfirst($esc['status'])); ?></td>
                            <td>
                                <button type="button" class="button resolve-escalation" data-id="<?php echo esc_attr($esc['id']); ?>">
                                    <?php esc_html_e('Resolver', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * AJAX: Autoconfiguración con IA
     */
    public function ajax_autoconfig() {
        check_ajax_referer('flavor_platform_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $section = sanitize_text_field($_POST['section'] ?? 'all');

        // Recopilar información del sitio
        $site_info = $this->gather_site_info();

        // Obtener el motor de IA activo
        if (!class_exists('Flavor_Engine_Manager')) {
            wp_send_json_error(['error' => __('Motor de IA no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_active_engine();

        if (!$engine || !$engine->is_configured()) {
            wp_send_json_error(['error' => __('Configura primero un proveedor de IA en la pestaña Proveedores', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Construir el prompt según la sección
        $prompt = $this->build_autoconfig_prompt($section, $site_info);

        $messages = [['role' => 'user', 'content' => $prompt]];
        $system_prompt = 'Eres un experto en configuración de chatbots para e-commerce. Genera configuraciones en formato JSON válido. Responde SOLO con el JSON, sin explicaciones adicionales.';

        $response = $engine->send_message($messages, $system_prompt, []);

        if (!$response['success']) {
            wp_send_json_error(['error' => $response['error'] ?? __('Error al generar configuración', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        // Parsear la respuesta JSON
        $json_response = $response['response'];
        // Limpiar markdown si existe
        $json_response = preg_replace('/```json\s*/', '', $json_response);
        $json_response = preg_replace('/```\s*/', '', $json_response);

        $config = json_decode(trim($json_response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['error' => __('Error al parsear respuesta de IA', FLAVOR_PLATFORM_TEXT_DOMAIN), 'raw' => $json_response]);
        }

        wp_send_json_success($config);
    }

    /**
     * Recopila información del sitio para autoconfiguración
     */
    private function gather_site_info() {
        $info = [
            'site_name' => get_bloginfo('name'),
            'site_description' => get_bloginfo('description'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
        ];

        // Obtener páginas principales
        $pages = get_pages(['number' => 10, 'sort_column' => 'menu_order']);
        $info['pages'] = array_map(function($page) {
            return ['title' => $page->post_title, 'url' => get_permalink($page)];
        }, $pages);

        // Si hay WooCommerce
        if (class_exists('WooCommerce')) {
            $info['is_woocommerce'] = true;
            $info['product_count'] = wp_count_posts('product')->publish;

            // Categorías de productos
            $categories = get_terms(['taxonomy' => 'product_cat', 'number' => 10, 'hide_empty' => true]);
            $info['product_categories'] = array_map(function($cat) {
                return $cat->name;
            }, $categories);

            // Métodos de envío
            if (class_exists('WC_Shipping_Zones')) {
                $zones = WC_Shipping_Zones::get_zones();
                $methods = [];
                foreach ($zones as $zone) {
                    foreach ($zone['shipping_methods'] as $method) {
                        $methods[] = $method->get_title();
                    }
                }
                $info['shipping_methods'] = array_unique($methods);
            }
        }

        // Buscar información de contacto en páginas
        $contact_page = get_page_by_path('contacto') ?? get_page_by_path('contact');
        if ($contact_page) {
            $info['contact_page_content'] = wp_strip_all_tags($contact_page->post_content);
        }

        return $info;
    }

    /**
     * Construye el prompt para autoconfiguración
     */
    private function build_autoconfig_prompt($section, $site_info) {
        $site_json = json_encode($site_info, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $prompts = [
            'knowledge' => "Analiza esta información del sitio web y genera una configuración de base de conocimiento para un chatbot de atención al cliente:

{$site_json}

Genera un JSON con esta estructura:
{
  \"business_info\": {
    \"name\": \"nombre del negocio\",
    \"description\": \"descripción breve del negocio\",
    \"address\": \"dirección si la encuentras\",
    \"phone\": \"teléfono si lo encuentras\",
    \"email\": \"email de contacto\",
    \"schedule\": \"horario si lo encuentras\"
  },
  \"faqs\": [
    {\"question\": \"pregunta frecuente 1\", \"answer\": \"respuesta\"},
    {\"question\": \"pregunta frecuente 2\", \"answer\": \"respuesta\"}
  ],
  \"policies\": {
    \"shipping\": \"política de envíos\",
    \"returns\": \"política de devoluciones\",
    \"privacy\": \"resumen de privacidad\"
  },
  \"business_topics\": [\"tema1\", \"tema2\", \"tema3\"]
}

Genera al menos 5 FAQs relevantes para el tipo de negocio.",

            'quick_actions' => "Analiza esta información del sitio web y genera acciones rápidas relevantes para un chatbot:

{$site_json}

Genera un JSON con esta estructura:
{
  \"quick_actions\": {
    \"action1\": {\"enabled\": true, \"icon\": \"cart\", \"label\": \"texto del botón\", \"prompt\": \"mensaje que envía\"},
    \"action2\": {\"enabled\": true, \"icon\": \"info\", \"label\": \"texto\", \"prompt\": \"mensaje\"}
  },
  \"custom_quick_actions\": [
    {\"label\": \"acción personalizada\", \"prompt\": \"mensaje\"}
  ]
}

Iconos disponibles: cart, package, truck, refresh, phone, question, star, info
Genera acciones relevantes para el tipo de negocio.",

            'escalation' => "Busca información de contacto en estos datos del sitio:

{$site_json}

Genera un JSON con:
{
  \"escalation_whatsapp\": \"número de WhatsApp si lo encuentras\",
  \"escalation_phone\": \"teléfono si lo encuentras\",
  \"escalation_email\": \"email de contacto\",
  \"escalation_hours\": \"horario de atención si lo encuentras\"
}

if (!class_exists('Flavor_Chat_Settings', false)) {
    class_alias('Flavor_Platform_Settings', 'Flavor_Chat_Settings');
}

Si no encuentras algún dato, deja el campo vacío.",

            'appearance' => "Basándote en el tipo de negocio, sugiere colores apropiados:

{$site_json}

Genera un JSON con:
{
  \"appearance\": {
    \"primary_color\": \"#hexcolor\",
    \"header_bg\": \"#hexcolor\",
    \"user_bubble\": \"#hexcolor\",
    \"assistant_bubble\": \"#f0f0f0\",
    \"welcome_message\": \"mensaje de bienvenida personalizado\"
  }
}

Usa colores profesionales que combinen con el tipo de negocio.",
        ];

        return $prompts[$section] ?? $prompts['knowledge'];
    }

    /**
     * AJAX: Obtener analíticas
     */

    /**
     * AJAX: Enviar notificacion push de prueba al usuario actual
     */
    public function ajax_test_push_notification() {
        check_ajax_referer('flavor_platform_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        if (!class_exists('Flavor_Push_Notification_Channel')) {
            wp_send_json_error(['message' => __('El canal de push no esta disponible.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $usuario_id_actual = get_current_user_id();
        $canal_push = new Flavor_Push_Notification_Channel();
        $resultado = $canal_push->send(
            $usuario_id_actual,
            __('Prueba Push Flavor', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('Esta es una notificacion de prueba desde Flavor Platform.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ['type' => 'test']
        );

        if ($resultado['enviados'] > 0) {
            wp_send_json_success(['message' => sprintf(
                __('Notificacion enviada a %d dispositivo(s).', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $resultado['enviados']
            )]);
        } elseif (!empty($resultado['sin_token'])) {
            wp_send_json_error(['message' => __('No tienes tokens FCM registrados. Abre la app en tu dispositivo primero.', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        } else {
            $error_msg = $resultado['error'] ?? __('Error desconocido al enviar push.', FLAVOR_PLATFORM_TEXT_DOMAIN);
            wp_send_json_error(['message' => $error_msg]);
        }
    }

    /**
     * Verifica la conexión con el proveedor de IA activo
     */
    public function ajax_test_ia_connection() {
        try {
            check_ajax_referer('flavor_platform_admin_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
            }

            // Verificar que el Engine Manager esté disponible
            if (!class_exists('Flavor_Engine_Manager')) {
                wp_send_json_error([
                    'message' => __('❌ Flavor_Engine_Manager no existe. Verifica que el plugin esté correctamente instalado.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ]);
            }

            $engine_manager = Flavor_Engine_Manager::get_instance();
            $settings = flavor_get_main_settings();
            $active_provider = $settings['active_provider'] ?? 'claude';
            $requested_provider = sanitize_key($_POST['provider'] ?? $active_provider);
            $valid_providers = ['claude', 'openai', 'deepseek', 'mistral'];
            if (!in_array($requested_provider, $valid_providers, true)) {
                $requested_provider = $active_provider;
            }

            $engine = $engine_manager->get_engine($requested_provider);

            if (!$engine) {
                $engines_disponibles = array_keys($engine_manager->get_engines());
                wp_send_json_error([
                    'message' => sprintf(
                        __('❌ No hay motor disponible para el proveedor %1$s. Proveedor activo guardado: %2$s. Motores disponibles: %3$s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $requested_provider,
                        $active_provider,
                        implode(', ', $engines_disponibles) ?: 'ninguno'
                    ),
                ]);
            }

            $input_api_key = trim((string) wp_unslash($_POST['api_key'] ?? ''));
            if ($input_api_key !== '') {
                $verification = $engine->verify_api_key($input_api_key);
                if (!empty($verification['valid'])) {
                    wp_send_json_success([
                        'message' => sprintf(
                            __('✅ Conexión exitosa con %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                            $engine->get_name()
                        ),
                        'provider' => $engine->get_id(),
                        'mode' => 'direct',
                    ]);
                }

                wp_send_json_error([
                    'message' => sprintf(
                        __('❌ Error de conexión con %1$s: %2$s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $engine->get_name(),
                        $verification['error'] ?? __('API key inválida o sin saldo.', FLAVOR_PLATFORM_TEXT_DOMAIN)
                    ),
                ]);
            }

            // Verificar si está configurado
            if (!$engine->is_configured()) {
                wp_send_json_error([
                    'message' => sprintf(
                        __('❌ %1$s no tiene API key guardada. Guarda la configuración o pega una clave temporal para verificar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                        $engine->get_name()
                    ),
                ]);
            }

            // Hacer un mensaje de prueba
            $test_messages = [
                ['role' => 'user', 'content' => 'Responde solo con: OK']
            ];

            $response = $engine->send_message($test_messages, 'Eres un asistente de prueba. Responde brevemente.', []);

        if ($response['success']) {
            wp_send_json_success([
                'message' => sprintf(
                    __('✅ Conexión exitosa con %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $engine->get_name()
                ),
                'provider' => $engine->get_id(),
                'response_preview' => mb_substr($response['response'], 0, 100),
            ]);
        } else {
            wp_send_json_error([
                'message' => sprintf(
                    __('❌ Error de conexión con %s: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                    $engine->get_name(),
                    $response['error'] ?? 'Error desconocido'
                ),
            ]);
        }
        } catch (Exception $e) {
            wp_send_json_error([
                'message' => '❌ Error: ' . $e->getMessage(),
            ]);
        } catch (Error $e) {
            wp_send_json_error([
                'message' => '❌ Error fatal: ' . $e->getMessage(),
            ]);
        }
    }

    public function ajax_get_analytics() {
        check_ajax_referer('flavor_platform_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
        }

        $period = sanitize_text_field($_POST['period'] ?? 'week');

        global $wpdb;
        $table_conversations = $wpdb->prefix . 'flavor_chat_conversations';
        $table_messages = $wpdb->prefix . 'flavor_chat_messages';

        // Calcular fecha de inicio
        switch ($period) {
            case 'day':
                $date_from = date('Y-m-d 00:00:00');
                break;
            case 'month':
                $date_from = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            default:
                $date_from = date('Y-m-d 00:00:00', strtotime('-7 days'));
        }

        // Conversaciones
        $conversations = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conversations WHERE started_at >= %s",
            $date_from
        ));

        // Mensajes
        $messages = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_messages m
             JOIN $table_conversations c ON m.conversation_id = c.id
             WHERE c.started_at >= %s",
            $date_from
        ));

        // Promedio
        $avg_messages = $conversations > 0 ? round($messages / $conversations, 1) : 0;

        // Escaladas
        $escalated = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conversations WHERE started_at >= %s AND escalated = 1",
            $date_from
        ));

        // Conversiones
        $conversions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_conversations WHERE started_at >= %s AND conversion_type IS NOT NULL",
            $date_from
        ));

        // Tokens
        $tokens = $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(tokens_used), 0) FROM $table_messages m
             JOIN $table_conversations c ON m.conversation_id = c.id
             WHERE c.started_at >= %s",
            $date_from
        ));

        wp_send_json_success([
            'conversations' => (int) $conversations,
            'messages' => (int) $messages,
            'avg_messages' => $avg_messages,
            'escalated' => (int) $escalated,
            'conversions' => (int) $conversions,
            'tokens' => number_format((int) $tokens),
        ]);
    }

    /**
     * Pestaña Avanzado - Configuraciones avanzadas y peligrosas
     */
    private function render_advanced_tab($settings) {
        global $wpdb;

        // Contar tablas del plugin
        $tablas_flavor = $wpdb->get_results(
            "SHOW TABLES LIKE '{$wpdb->prefix}flavor_%'",
            ARRAY_N
        );
        $num_tablas = count($tablas_flavor);

        // Contar opciones
        $num_opciones = $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE 'flavor_%'"
        );
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="advanced">

        <div class="flavor-settings-section">
            <h2><?php esc_html_e('Datos del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <p class="description">
                <?php esc_html_e('Información sobre los datos almacenados por Flavor Platform.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <table class="form-table" style="max-width: 600px;">
                <tr>
                    <th><?php esc_html_e('Tablas en base de datos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><code><?php echo esc_html($num_tablas); ?></code> <?php esc_html_e('tablas con prefijo flavor_', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Opciones guardadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <td><code><?php echo esc_html($num_opciones); ?></code> <?php esc_html_e('registros en wp_options', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                </tr>
            </table>
        </div>

        <div class="flavor-settings-section" style="margin-top: 30px; padding: 20px; background: #fff3cd; border-left: 4px solid #ffc107; border-radius: 4px;">
            <h2 style="color: #856404; margin-top: 0;">
                <span class="dashicons dashicons-warning" style="margin-right: 8px;"></span>
                <?php esc_html_e('Limpieza al Desinstalar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h2>

            <p class="description" style="color: #856404; font-size: 14px;">
                <?php esc_html_e('Si activas esta opcion, al desinstalar el plugin desde WordPress se eliminaran TODOS los datos:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <ul style="color: #856404; margin-left: 20px; list-style: disc;">
                <li><?php esc_html_e('Todas las tablas de la base de datos (eventos, reservas, foros, mensajes, etc.)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Todas las opciones y configuraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Metadatos de usuarios y posts', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Custom Post Types y sus contenidos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
                <li><?php esc_html_e('Roles y capacidades personalizadas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></li>
            </ul>

            <p style="color: #721c24; font-weight: bold; background: #f8d7da; padding: 10px; border-radius: 4px; margin-top: 15px;">
                <span class="dashicons dashicons-no" style="margin-right: 5px;"></span>
                <?php esc_html_e('ESTA ACCION ES IRREVERSIBLE. Solo activa esta opcion si estas seguro de que quieres eliminar todos los datos del plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="limpiar_al_desinstalar">
                            <?php esc_html_e('Eliminar datos al desinstalar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <label class="flavor-switch">
                            <input type="checkbox"
                                   name="flavor_chat_ia_settings[limpiar_al_desinstalar]"
                                   id="limpiar_al_desinstalar"
                                   value="1"
                                   <?php checked(!empty($settings['limpiar_al_desinstalar'])); ?>>
                            <span class="slider round"></span>
                        </label>
                        <p class="description" style="color: #856404;">
                            <?php esc_html_e('Si esta desactivado (por defecto), los datos se conservaran aunque desinstales el plugin.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="flavor-settings-section" style="margin-top: 30px;">
            <h2><?php esc_html_e('Modo Debug', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="debug_mode">
                            <?php esc_html_e('Activar modo debug', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </label>
                    </th>
                    <td>
                        <label class="flavor-switch">
                            <input type="checkbox"
                                   name="flavor_chat_ia_settings[debug_mode]"
                                   id="debug_mode"
                                   value="1"
                                   <?php checked(!empty($settings['debug_mode'])); ?>>
                            <span class="slider round"></span>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Registra informacion adicional en el log de errores de WordPress. Util para depuracion.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                    </td>
                </tr>
            </table>
        </div>

        <?php if (!empty($tablas_flavor)): ?>
        <div class="flavor-settings-section" style="margin-top: 30px;">
            <h3><?php esc_html_e('Tablas del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <details>
                <summary style="cursor: pointer; color: #0073aa;"><?php esc_html_e('Ver listado de tablas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></summary>
                <ul style="margin-top: 10px; font-family: monospace; font-size: 12px;">
                    <?php foreach ($tablas_flavor as $tabla): ?>
                        <li><?php echo esc_html($tabla[0]); ?></li>
                    <?php endforeach; ?>
                </ul>
            </details>
        </div>
        <?php endif; ?>
        <?php
    }
}
