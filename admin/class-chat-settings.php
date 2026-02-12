<?php
/**
 * Página de configuración del Chat IA
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Chat_Settings {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug del menú
     */
    const MENU_SLUG = 'flavor-chat-ia';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Chat_Settings
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
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // AJAX handlers
        add_action('wp_ajax_flavor_chat_autoconfig', [$this, 'ajax_autoconfig']);
        add_action('wp_ajax_flavor_chat_get_analytics', [$this, 'ajax_get_analytics']);
        add_action('wp_ajax_flavor_test_push_notification', [$this, 'ajax_test_push_notification']);
    }

    /**
     * Añade el menú de administración
     */
    public function add_menu() {
        add_menu_page(
            __('Flavor Platform', 'flavor-chat-ia'),
            __('Flavor Platform', 'flavor-chat-ia'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page'],
            'dashicons-superhero-alt',
            30
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Configuración', 'flavor-chat-ia'),
            __('Configuración', 'flavor-chat-ia'),
            'manage_options',
            self::MENU_SLUG,
            [$this, 'render_settings_page']
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Escalados', 'flavor-chat-ia'),
            __('Escalados', 'flavor-chat-ia'),
            'manage_options',
            'flavor-chat-ia-escalations',
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
    }

    /**
     * Sanitiza los settings
     *
     * @param array $input
     * @return array
     */
    public function sanitize_settings($input) {
        $existing = get_option('flavor_chat_ia_settings', []);
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
                $sanitized_value = sanitize_text_field($value);

                // Si el valor está vacío y ya existía uno encriptado, mantener el existente
                if (empty($sanitized_value) && !empty($existing[$field])) {
                    $sanitized[$field] = $existing[$field];
                } else if (!empty($sanitized_value)) {
                    // Encriptar si hay clase de encriptación disponible
                    $sanitized[$field] = $encryption
                        ? $encryption->encrypt($sanitized_value)
                        : $sanitized_value;
                } else {
                    $sanitized[$field] = '';
                }
            }

            $sanitized['claude_model'] = sanitize_text_field($input['claude_model'] ?? 'claude-sonnet-4-20250514');
            $sanitized['openai_model'] = sanitize_text_field($input['openai_model'] ?? 'gpt-4o-mini');
            $sanitized['deepseek_model'] = sanitize_text_field($input['deepseek_model'] ?? 'deepseek-chat');
            $sanitized['mistral_model'] = sanitize_text_field($input['mistral_model'] ?? 'mistral-small-latest');

            $sanitized['max_tokens_per_message'] = absint($input['max_tokens_per_message'] ?? 1000);

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
            if (class_exists('Flavor_Chat_Knowledge_Base')) {
                Flavor_Chat_Knowledge_Base::get_instance()->invalidate_cache();
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
            $valid_modules = ['woocommerce', 'banco_tiempo', 'grupos_consumo', 'marketplace', 'facturas', 'fichaje_empleados', 'eventos', 'socios', 'incidencias', 'participacion', 'presupuestos_participativos', 'avisos_municipales', 'advertising', 'ayuda_vecinal', 'biblioteca', 'bicicletas_compartidas', 'carpooling', 'chat_grupos', 'chat_interno', 'compostaje', 'cursos', 'empresarial', 'espacios_comunes', 'huertos_urbanos', 'multimedia', 'parkings', 'podcast', 'radio', 'reciclaje', 'red_social', 'talleres', 'tramites', 'transparencia', 'colectivos', 'foros', 'clientes', 'comunidades', 'bares', 'trading_ia', 'dex_solana', 'themacle'];
            $sanitized['active_modules'] = isset($input['active_modules']) && is_array($input['active_modules'])
                ? array_values(array_intersect($input['active_modules'], $valid_modules))
                : [];

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
            if (isset($_POST['flavor_chat_ia_module_banco_tiempo'])) {
                $banco_tiempo_config = [
                    'hora_minima_intercambio' => max(0.5, floatval($_POST['flavor_chat_ia_module_banco_tiempo']['hora_minima_intercambio'] ?? 0.5)),
                    'hora_maxima_intercambio' => max(1, floatval($_POST['flavor_chat_ia_module_banco_tiempo']['hora_maxima_intercambio'] ?? 8)),
                    'requiere_validacion' => !empty($_POST['flavor_chat_ia_module_banco_tiempo']['requiere_validacion']),
                    'saldo_inicial_horas' => max(0, absint($_POST['flavor_chat_ia_module_banco_tiempo']['saldo_inicial_horas'] ?? 5)),
                    'permite_saldo_negativo' => !empty($_POST['flavor_chat_ia_module_banco_tiempo']['permite_saldo_negativo']),
                    'limite_saldo_negativo' => min(0, intval($_POST['flavor_chat_ia_module_banco_tiempo']['limite_saldo_negativo'] ?? -10)),
                    'notificar_saldo_bajo' => !empty($_POST['flavor_chat_ia_module_banco_tiempo']['notificar_saldo_bajo']),
                    'umbral_notificacion_saldo' => max(0, absint($_POST['flavor_chat_ia_module_banco_tiempo']['umbral_notificacion_saldo'] ?? 2)),
                ];
                update_option('flavor_chat_ia_module_banco_tiempo', $banco_tiempo_config);
            }

            if (isset($_POST['flavor_chat_ia_module_grupos_consumo'])) {
                $grupos_config = [
                    'dias_para_pedido' => max(1, absint($_POST['flavor_chat_ia_module_grupos_consumo']['dias_para_pedido'] ?? 7)),
                    'pedido_minimo' => max(0, floatval($_POST['flavor_chat_ia_module_grupos_consumo']['pedido_minimo'] ?? 0)),
                    'participantes_minimos' => max(2, absint($_POST['flavor_chat_ia_module_grupos_consumo']['participantes_minimos'] ?? 5)),
                    'permite_multiples_pedidos' => !empty($_POST['flavor_chat_ia_module_grupos_consumo']['permite_multiples_pedidos']),
                    'coordina_reparto' => !empty($_POST['flavor_chat_ia_module_grupos_consumo']['coordina_reparto']),
                    'requiere_pago_anticipado' => !empty($_POST['flavor_chat_ia_module_grupos_consumo']['requiere_pago_anticipado']),
                    'porcentaje_gastos_gestion' => max(0, min(20, floatval($_POST['flavor_chat_ia_module_grupos_consumo']['porcentaje_gastos_gestion'] ?? 0))),
                ];
                update_option('flavor_chat_ia_module_grupos_consumo', $grupos_config);
            }

            if (isset($_POST['flavor_chat_ia_module_marketplace'])) {
                $marketplace_config = [
                    'permite_venta' => !empty($_POST['flavor_chat_ia_module_marketplace']['permite_venta']),
                    'permite_intercambio' => !empty($_POST['flavor_chat_ia_module_marketplace']['permite_intercambio']),
                    'permite_regalo' => !empty($_POST['flavor_chat_ia_module_marketplace']['permite_regalo']),
                    'requiere_moderacion' => !empty($_POST['flavor_chat_ia_module_marketplace']['requiere_moderacion']),
                    'dias_vigencia_anuncio' => max(7, min(90, absint($_POST['flavor_chat_ia_module_marketplace']['dias_vigencia_anuncio'] ?? 30))),
                    'max_fotos_por_anuncio' => max(1, min(10, absint($_POST['flavor_chat_ia_module_marketplace']['max_fotos_por_anuncio'] ?? 5))),
                    'permite_reservas' => !empty($_POST['flavor_chat_ia_module_marketplace']['permite_reservas']),
                ];
                update_option('flavor_chat_ia_module_marketplace', $marketplace_config);
            }

            if (isset($_POST['flavor_chat_ia_module_woocommerce'])) {
                $woocommerce_config = [
                    'mostrar_stock' => !empty($_POST['flavor_chat_ia_module_woocommerce']['mostrar_stock']),
                    'limite_productos_busqueda' => max(5, min(50, absint($_POST['flavor_chat_ia_module_woocommerce']['limite_productos_busqueda'] ?? 10))),
                ];
                update_option('flavor_chat_ia_module_woocommerce', $woocommerce_config);
            }

            if (isset($_POST['flavor_chat_ia_module_facturas'])) {
                $facturas_config = [
                    'serie_predeterminada' => strtoupper(sanitize_text_field($_POST['flavor_chat_ia_module_facturas']['serie_predeterminada'] ?? 'F')),
                    'iva_predeterminado' => max(0, min(100, absint($_POST['flavor_chat_ia_module_facturas']['iva_predeterminado'] ?? 21))),
                    'enviar_email_automatico' => !empty($_POST['flavor_chat_ia_module_facturas']['enviar_email_automatico']),
                    'formato_numero_factura' => sanitize_text_field($_POST['flavor_chat_ia_module_facturas']['formato_numero_factura'] ?? 'SERIE-YYYY-NNNN'),
                    'prefijo_rectificativa' => strtoupper(sanitize_text_field($_POST['flavor_chat_ia_module_facturas']['prefijo_rectificativa'] ?? 'R')),
                    'dias_vencimiento_predeterminado' => max(0, min(180, absint($_POST['flavor_chat_ia_module_facturas']['dias_vencimiento_predeterminado'] ?? 30))),
                    'texto_pie_factura' => sanitize_textarea_field($_POST['flavor_chat_ia_module_facturas']['texto_pie_factura'] ?? ''),
                ];
                update_option('flavor_chat_ia_module_facturas', $facturas_config);
            }

            if (isset($_POST['flavor_chat_ia_module_fichaje_empleados'])) {
                $fichaje_config = [
                    'horario_entrada' => sanitize_text_field($_POST['flavor_chat_ia_module_fichaje_empleados']['horario_entrada'] ?? '09:00'),
                    'horario_salida' => sanitize_text_field($_POST['flavor_chat_ia_module_fichaje_empleados']['horario_salida'] ?? '18:00'),
                    'tiempo_gracia' => max(0, min(60, absint($_POST['flavor_chat_ia_module_fichaje_empleados']['tiempo_gracia'] ?? 15))),
                    'requiere_geolocalizacion' => !empty($_POST['flavor_chat_ia_module_fichaje_empleados']['requiere_geolocalizacion']),
                    'radio_geolocalizacion_metros' => max(10, min(1000, absint($_POST['flavor_chat_ia_module_fichaje_empleados']['radio_geolocalizacion_metros'] ?? 100))),
                    'permite_multiples_entradas' => !empty($_POST['flavor_chat_ia_module_fichaje_empleados']['permite_multiples_entradas']),
                    'genera_informes_mensuales' => !empty($_POST['flavor_chat_ia_module_fichaje_empleados']['genera_informes_mensuales']),
                ];
                update_option('flavor_chat_ia_module_fichaje_empleados', $fichaje_config);
            }

            if (isset($_POST['flavor_chat_ia_module_eventos'])) {
                $eventos_config = [
                    'requiere_aprobacion' => !empty($_POST['flavor_chat_ia_module_eventos']['requiere_aprobacion']),
                    'permite_invitados' => !empty($_POST['flavor_chat_ia_module_eventos']['permite_invitados']),
                    'dias_recordatorio' => max(0, min(7, absint($_POST['flavor_chat_ia_module_eventos']['dias_recordatorio'] ?? 1))),
                ];
                update_option('flavor_chat_ia_module_eventos', $eventos_config);
            }

            if (isset($_POST['flavor_chat_ia_module_socios'])) {
                $socios_config = [
                    'cuota_mensual' => max(0, floatval($_POST['flavor_chat_ia_module_socios']['cuota_mensual'] ?? 30.00)),
                    'cuota_anual' => max(0, floatval($_POST['flavor_chat_ia_module_socios']['cuota_anual'] ?? 300.00)),
                    'dia_cargo' => max(1, min(28, absint($_POST['flavor_chat_ia_module_socios']['dia_cargo'] ?? 1))),
                    'permite_cuota_reducida' => !empty($_POST['flavor_chat_ia_module_socios']['permite_cuota_reducida']),
                ];
                update_option('flavor_chat_ia_module_socios', $socios_config);
            }

            if (isset($_POST['flavor_chat_ia_module_incidencias'])) {
                $incidencias_config = [
                    'requiere_ubicacion_gps' => !empty($_POST['flavor_chat_ia_module_incidencias']['requiere_ubicacion_gps']),
                    'requiere_foto' => !empty($_POST['flavor_chat_ia_module_incidencias']['requiere_foto']),
                    'visibilidad_publica' => !empty($_POST['flavor_chat_ia_module_incidencias']['visibilidad_publica']),
                    'votos_para_urgencia' => max(1, min(50, absint($_POST['flavor_chat_ia_module_incidencias']['votos_para_urgencia'] ?? 5))),
                ];
                update_option('flavor_chat_ia_module_incidencias', $incidencias_config);
            }

            if (isset($_POST['flavor_chat_ia_module_participacion'])) {
                $participacion_config = [
                    'requiere_verificacion' => !empty($_POST['flavor_chat_ia_module_participacion']['requiere_verificacion']),
                    'moderacion_propuestas' => !empty($_POST['flavor_chat_ia_module_participacion']['moderacion_propuestas']),
                    'votos_necesarios_propuesta' => max(5, min(500, absint($_POST['flavor_chat_ia_module_participacion']['votos_necesarios_propuesta'] ?? 10))),
                    'duracion_votacion_dias' => max(1, min(30, absint($_POST['flavor_chat_ia_module_participacion']['duracion_votacion_dias'] ?? 7))),
                ];
                update_option('flavor_chat_ia_module_participacion', $participacion_config);
            }

            if (isset($_POST['flavor_chat_ia_module_presupuestos_participativos'])) {
                $presupuestos_config = [
                    'presupuesto_anual' => max(0, floatval($_POST['flavor_chat_ia_module_presupuestos_participativos']['presupuesto_anual'] ?? 50000.00)),
                    'votos_maximos_por_persona' => max(1, min(10, absint($_POST['flavor_chat_ia_module_presupuestos_participativos']['votos_maximos_por_persona'] ?? 3))),
                    'proyecto_monto_minimo' => max(0, floatval($_POST['flavor_chat_ia_module_presupuestos_participativos']['proyecto_monto_minimo'] ?? 1000.00)),
                    'proyecto_monto_maximo' => max(0, floatval($_POST['flavor_chat_ia_module_presupuestos_participativos']['proyecto_monto_maximo'] ?? 15000.00)),
                ];
                update_option('flavor_chat_ia_module_presupuestos_participativos', $presupuestos_config);
            }

            if (isset($_POST['flavor_chat_ia_module_avisos_municipales'])) {
                $avisos_config = [
                    'enviar_push_notifications' => !empty($_POST['flavor_chat_ia_module_avisos_municipales']['enviar_push_notifications']),
                    'requiere_confirmacion_lectura' => !empty($_POST['flavor_chat_ia_module_avisos_municipales']['requiere_confirmacion_lectura']),
                ];
                update_option('flavor_chat_ia_module_avisos_municipales', $avisos_config);
            }

            // Guardado genérico para módulos sin handler específico
            $modulos_con_handler = ['banco_tiempo', 'grupos_consumo', 'marketplace', 'woocommerce', 'facturas', 'fichaje_empleados', 'eventos', 'socios', 'incidencias', 'participacion', 'presupuestos_participativos', 'avisos_municipales'];
            $modulos_restantes = array_diff($valid_modules, $modulos_con_handler);

            foreach ($modulos_restantes as $modulo_id_generico) {
                $clave_post = 'flavor_chat_ia_module_' . $modulo_id_generico;
                if (isset($_POST[$clave_post]) && is_array($_POST[$clave_post])) {
                    $configuracion_modulo = [];
                    foreach ($_POST[$clave_post] as $clave_config => $valor_config) {
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
                    $existentes = get_option($clave_post, []);
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
                    update_option($clave_post, $configuracion_modulo);
                }
            }
        }

        unset($sanitized['_tab']);
        return $sanitized;
    }

    /**
     * Encola assets de admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-chat-ia') === false) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_media();

        $sufijo_asset = defined('WP_DEBUG') && WP_DEBUG ? '' : '.min';

        wp_enqueue_style(
            'flavor-chat-ia-admin',
            FLAVOR_CHAT_IA_URL . "admin/css/admin{$sufijo_asset}.css",
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-chat-ia-admin',
            FLAVOR_CHAT_IA_URL . "admin/js/admin{$sufijo_asset}.js",
            ['jquery', 'wp-color-picker'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-chat-ia-admin', 'flavorChatAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_chat_admin_nonce'),
            'strings' => [
                'confirmDelete' => __('¿Eliminar este elemento?', 'flavor-chat-ia'),
                'analyzing' => __('Analizando sitio...', 'flavor-chat-ia'),
                'success' => __('Configuración generada', 'flavor-chat-ia'),
                'error' => __('Error al analizar', 'flavor-chat-ia'),
            ],
        ]);
    }

    /**
     * Renderiza la página de configuración
     */
    public function render_settings_page() {
        $settings = get_option('flavor_chat_ia_settings', []);
        $active_tab = $_GET['tab'] ?? 'general';
        ?>
        <div class="wrap flavor-chat-settings">
            <h1><?php esc_html_e('Flavor Chat IA', 'flavor-chat-ia'); ?></h1>

            <?php settings_errors('flavor_chat_ia_settings'); ?>

            <nav class="nav-tab-wrapper">
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=general"
                   class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('General', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=providers"
                   class="nav-tab <?php echo $active_tab === 'providers' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Proveedores IA', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=appearance"
                   class="nav-tab <?php echo $active_tab === 'appearance' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Apariencia', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=quick_actions"
                   class="nav-tab <?php echo $active_tab === 'quick_actions' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Acciones Rápidas', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=knowledge"
                   class="nav-tab <?php echo $active_tab === 'knowledge' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Base de Conocimiento', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=escalation"
                   class="nav-tab <?php echo $active_tab === 'escalation' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Escalado', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=modules"
                   class="nav-tab <?php echo $active_tab === 'modules' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Módulos', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=firebase_push"
                   class="nav-tab <?php echo $active_tab === 'firebase_push' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Push Notifications', 'flavor-chat-ia'); ?>
                </a>
                <a href="?page=<?php echo self::MENU_SLUG; ?>&tab=analytics"
                   class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
                    <?php esc_html_e('Analíticas', 'flavor-chat-ia'); ?>
                </a>
            </nav>

            <form method="post" action="options.php" id="flavor-chat-settings-form">
                <?php settings_fields('flavor_chat_ia_settings'); ?>

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
                }
                ?>

                <?php if ($active_tab !== 'analytics'): ?>
                    <?php submit_button(__('Guardar cambios', 'flavor-chat-ia')); ?>
                <?php endif; ?>
            </form>
        </div>
        <?php
    }

    /**
     * Pestaña General
     */
    private function render_general_tab($settings) {
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="general">
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Habilitar Chat', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[enabled]" value="1"
                               <?php checked(!empty($settings['enabled'])); ?>>
                        <?php esc_html_e('Activar el chat IA en el sitio', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Modo Test', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[admin_only]" value="1"
                               <?php checked(!empty($settings['admin_only'])); ?>>
                        <?php esc_html_e('Solo visible para administradores', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Widget Flotante', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_settings[show_floating_widget]" value="1"
                               <?php checked($settings['show_floating_widget'] ?? true); ?>>
                        <?php esc_html_e('Mostrar botón flotante en todas las páginas', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Nombre del Asistente', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[assistant_name]"
                           value="<?php echo esc_attr($settings['assistant_name'] ?? 'Asistente Virtual'); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Rol/Descripción', 'flavor-chat-ia'); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[assistant_role]" rows="3" class="large-text"><?php
                        echo esc_textarea($settings['assistant_role'] ?? '');
                    ?></textarea>
                    <p class="description"><?php esc_html_e('Describe el rol del asistente para que sepa cómo comportarse.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Tono', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[tone]">
                        <option value="friendly" <?php selected($settings['tone'] ?? 'friendly', 'friendly'); ?>><?php esc_html_e('Amable y cercano', 'flavor-chat-ia'); ?></option>
                        <option value="formal" <?php selected($settings['tone'] ?? '', 'formal'); ?>><?php esc_html_e('Profesional y formal', 'flavor-chat-ia'); ?></option>
                        <option value="casual" <?php selected($settings['tone'] ?? '', 'casual'); ?>><?php esc_html_e('Informal y relajado', 'flavor-chat-ia'); ?></option>
                        <option value="enthusiastic" <?php selected($settings['tone'] ?? '', 'enthusiastic'); ?>><?php esc_html_e('Entusiasta y positivo', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Idiomas', 'flavor-chat-ia'); ?></th>
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
                    <p class="description"><?php esc_html_e('Compatible con WPML y Polylang.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Límite de mensajes', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[max_messages_per_session]"
                           value="<?php echo esc_attr($settings['max_messages_per_session'] ?? 50); ?>"
                           min="10" max="200" class="small-text">
                    <span><?php esc_html_e('mensajes por sesión', 'flavor-chat-ia'); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Shortcode', 'flavor-chat-ia'); ?></th>
                <td>
                    <code>[flavor_chat]</code>
                    <p class="description"><?php esc_html_e('Usa este shortcode para insertar el chat en cualquier página.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Pestaña Proveedores IA
     */
    private function render_providers_tab($settings) {
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="providers">

        <h2><?php esc_html_e('Proveedor de IA', 'flavor-chat-ia'); ?></h2>
        <p class="description"><?php esc_html_e('Selecciona el proveedor activo. Puedes configurar varios y cambiar entre ellos.', 'flavor-chat-ia'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Proveedor activo', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[active_provider]" id="active_provider">
                        <option value="claude" <?php selected($settings['active_provider'] ?? 'claude', 'claude'); ?>>Claude (Anthropic)</option>
                        <option value="openai" <?php selected($settings['active_provider'] ?? '', 'openai'); ?>>OpenAI (GPT)</option>
                        <option value="deepseek" <?php selected($settings['active_provider'] ?? '', 'deepseek'); ?>>DeepSeek (Gratuito)</option>
                        <option value="mistral" <?php selected($settings['active_provider'] ?? '', 'mistral'); ?>>Mistral AI (Gratuito)</option>
                    </select>
                </td>
            </tr>
        </table>

        <!-- Claude -->
        <div class="provider-settings provider-claude" style="border: 1px solid #ccd0d4; padding: 15px; margin: 20px 0; border-radius: 4px; background: #f9f9f9;">
            <h3 style="margin-top: 0;">🟣 Claude (Anthropic)</h3>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', 'flavor-chat-ia'); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[claude_api_key]"
                               value="<?php echo esc_attr($settings['claude_api_key'] ?? $settings['api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><a href="https://console.anthropic.com/" target="_blank">console.anthropic.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
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
                    <th><?php esc_html_e('API Key', 'flavor-chat-ia'); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[openai_api_key]"
                               value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
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
            <p class="description"><?php esc_html_e('~500K tokens/día gratis', 'flavor-chat-ia'); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', 'flavor-chat-ia'); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[deepseek_api_key]"
                               value="<?php echo esc_attr($settings['deepseek_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><a href="https://platform.deepseek.com/" target="_blank">platform.deepseek.com</a></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
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
            <p class="description"><?php esc_html_e('1M tokens/mes gratis', 'flavor-chat-ia'); ?></p>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('API Key', 'flavor-chat-ia'); ?></th>
                    <td>
                        <input type="password" name="flavor_chat_ia_settings[mistral_api_key]"
                               value="<?php echo esc_attr($settings['mistral_api_key'] ?? ''); ?>" class="regular-text">
                        <p class="description"><a href="https://console.mistral.ai/" target="_blank">console.mistral.ai</a></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[mistral_model]">
                            <option value="mistral-small-latest" <?php selected($settings['mistral_model'] ?? 'mistral-small-latest', 'mistral-small-latest'); ?>>Mistral Small (Gratis)</option>
                            <option value="mistral-large-latest" <?php selected($settings['mistral_model'] ?? '', 'mistral-large-latest'); ?>>Mistral Large</option>
                        </select>
                    </td>
                </tr>
            </table>
        </div>

        <h2><?php esc_html_e('Límites', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Máx. tokens por mensaje', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[max_tokens_per_message]"
                           value="<?php echo esc_attr($settings['max_tokens_per_message'] ?? 1000); ?>"
                           min="100" max="4000" class="small-text">
                </td>
            </tr>
        </table>

        <!-- Configuración por contexto -->
        <hr style="margin: 30px 0;">
        <h2><?php esc_html_e('IA por contexto', 'flavor-chat-ia'); ?></h2>
        <p class="description"><?php esc_html_e('Puedes usar un proveedor y modelo diferente para el chat público (frontend) y para el asistente de administración (backend). Si seleccionas "Usar proveedor por defecto", se usará el proveedor activo configurado arriba.', 'flavor-chat-ia'); ?></p>

        <?php
        $provider_context_options = [
            'default' => __('Usar proveedor por defecto', 'flavor-chat-ia'),
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
                'label' => __('Chat Público (Frontend)', 'flavor-chat-ia'),
                'description' => __('Widget de chat para visitantes del sitio', 'flavor-chat-ia'),
                'icon' => 'dashicons-format-chat',
            ],
            'backend' => [
                'label' => __('Admin Assistant (Backend)', 'flavor-chat-ia'),
                'description' => __('Asistente de IA para administradores en el panel de WordPress', 'flavor-chat-ia'),
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
                    <th><?php esc_html_e('Proveedor', 'flavor-chat-ia'); ?></th>
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
                    <th><?php esc_html_e('Modelo', 'flavor-chat-ia'); ?></th>
                    <td>
                        <select name="flavor_chat_ia_settings[ia_model_<?php echo esc_attr($context_key); ?>]"
                                class="context-model-select"
                                data-context="<?php echo esc_attr($context_key); ?>">
                            <option value=""><?php esc_html_e('Modelo por defecto del proveedor', 'flavor-chat-ia'); ?></option>
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

        <script>
        jQuery(function($) {
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
                <?php esc_html_e('Autoconfiguración desde el tema', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Aplica automáticamente los colores de tu tema de WordPress al chat.', 'flavor-chat-ia'); ?></p>
            <button type="button" id="apply-theme-colors" class="button button-primary">
                <?php esc_html_e('Aplicar colores del tema', 'flavor-chat-ia'); ?>
            </button>
            <span id="theme-colors-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Colores', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Color principal', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][primary_color]" id="primary_color"
                           value="<?php echo esc_attr($appearance['primary_color'] ?? $theme_colors['primary'] ?? '#0073aa'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Fondo de cabecera', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][header_bg]" id="header_bg"
                           value="<?php echo esc_attr($appearance['header_bg'] ?? $theme_colors['secondary'] ?? '#1e3a5f'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Burbuja del usuario', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][user_bubble]" id="user_bubble"
                           value="<?php echo esc_attr($appearance['user_bubble'] ?? $theme_colors['primary'] ?? '#0073aa'); ?>">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Burbuja del asistente', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="color" name="flavor_chat_ia_settings[appearance][assistant_bubble]" id="assistant_bubble"
                           value="<?php echo esc_attr($appearance['assistant_bubble'] ?? '#f0f0f0'); ?>">
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Avatar', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Imagen del avatar', 'flavor-chat-ia'); ?></th>
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
                    <button type="button" class="button" id="upload-avatar"><?php esc_html_e('Seleccionar imagen', 'flavor-chat-ia'); ?></button>
                    <button type="button" class="button" id="remove-avatar" <?php echo empty($appearance['avatar_url']) ? 'style="display:none;"' : ''; ?>><?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?></button>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Posición y tamaño', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Posición', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][position]">
                        <option value="bottom-right" <?php selected($appearance['position'] ?? 'bottom-right', 'bottom-right'); ?>><?php esc_html_e('Abajo derecha', 'flavor-chat-ia'); ?></option>
                        <option value="bottom-left" <?php selected($appearance['position'] ?? '', 'bottom-left'); ?>><?php esc_html_e('Abajo izquierda', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Distancia desde abajo', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][bottom_offset]"
                           value="<?php echo esc_attr($appearance['bottom_offset'] ?? 20); ?>" min="10" max="200" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Distancia desde el lado', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][side_offset]"
                           value="<?php echo esc_attr($appearance['side_offset'] ?? 20); ?>" min="10" max="200" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Tamaño del botón', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][trigger_size]">
                        <option value="small" <?php selected($appearance['trigger_size'] ?? '', 'small'); ?>><?php esc_html_e('Pequeño (50px)', 'flavor-chat-ia'); ?></option>
                        <option value="medium" <?php selected($appearance['trigger_size'] ?? 'medium', 'medium'); ?>><?php esc_html_e('Mediano (60px)', 'flavor-chat-ia'); ?></option>
                        <option value="large" <?php selected($appearance['trigger_size'] ?? '', 'large'); ?>><?php esc_html_e('Grande (70px)', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Animación', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_settings[appearance][trigger_animation]">
                        <option value="none" <?php selected($appearance['trigger_animation'] ?? '', 'none'); ?>><?php esc_html_e('Sin animación', 'flavor-chat-ia'); ?></option>
                        <option value="pulse" <?php selected($appearance['trigger_animation'] ?? 'pulse', 'pulse'); ?>><?php esc_html_e('Pulso', 'flavor-chat-ia'); ?></option>
                        <option value="bounce" <?php selected($appearance['trigger_animation'] ?? '', 'bounce'); ?>><?php esc_html_e('Rebote', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Ancho del widget', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][widget_width]"
                           value="<?php echo esc_attr($appearance['widget_width'] ?? 380); ?>" min="300" max="500" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Alto del widget', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][widget_height]"
                           value="<?php echo esc_attr($appearance['widget_height'] ?? 500); ?>" min="400" max="700" class="small-text"> px
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Bordes redondeados', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_settings[appearance][border_radius]"
                           value="<?php echo esc_attr($appearance['border_radius'] ?? 16); ?>" min="0" max="30" class="small-text"> px
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Mensajes', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Mensaje de bienvenida', 'flavor-chat-ia'); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[appearance][welcome_message]" rows="3" class="large-text"><?php
                        echo esc_textarea($appearance['welcome_message'] ?? '¡Hola! Soy tu asistente virtual. ¿En qué puedo ayudarte?');
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Placeholder del input', 'flavor-chat-ia'); ?></th>
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
                <?php esc_html_e('Autoconfiguración con IA', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Analiza tu sitio y genera acciones rápidas relevantes automáticamente.', 'flavor-chat-ia'); ?></p>
            <button type="button" id="autoconfig-quick-actions" class="button button-primary" data-section="quick_actions">
                <?php esc_html_e('Generar acciones con IA', 'flavor-chat-ia'); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <p class="description"><?php esc_html_e('Botones que aparecen debajo del mensaje de bienvenida.', 'flavor-chat-ia'); ?></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width:50px;"><?php esc_html_e('Activo', 'flavor-chat-ia'); ?></th>
                    <th style="width:60px;"><?php esc_html_e('Icono', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Texto del botón', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Mensaje que envía', 'flavor-chat-ia'); ?></th>
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

        <h3 style="margin-top:30px;"><?php esc_html_e('Acciones personalizadas', 'flavor-chat-ia'); ?></h3>
        <div id="custom-actions-container">
            <?php
            $custom_actions = $settings['custom_quick_actions'] ?? [];
            foreach ($custom_actions as $index => $custom):
            ?>
            <div class="custom-action-item" style="background:#fff;padding:10px;border:1px solid #ccd0d4;margin-bottom:10px;">
                <input type="text" name="flavor_chat_ia_settings[custom_quick_actions][<?php echo $index; ?>][label]"
                       value="<?php echo esc_attr($custom['label'] ?? ''); ?>" placeholder="<?php esc_attr_e('Texto del botón', 'flavor-chat-ia'); ?>" class="regular-text">
                <input type="text" name="flavor_chat_ia_settings[custom_quick_actions][<?php echo $index; ?>][prompt]"
                       value="<?php echo esc_attr($custom['prompt'] ?? ''); ?>" placeholder="<?php esc_attr_e('Mensaje que envía', 'flavor-chat-ia'); ?>" class="large-text">
                <button type="button" class="button remove-custom-action"><?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?></button>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-custom-action" class="button"><?php esc_html_e('+ Añadir acción', 'flavor-chat-ia'); ?></button>

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
                <?php esc_html_e('Autoconfiguración con IA', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Analiza tu sitio web (páginas, productos, etc.) y genera automáticamente la información del negocio, FAQs y políticas.', 'flavor-chat-ia'); ?></p>
            <button type="button" id="autoconfig-knowledge" class="button button-primary" data-section="knowledge">
                <?php esc_html_e('Analizar sitio y autoconfigurar', 'flavor-chat-ia'); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Información del Negocio', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Nombre del negocio', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[business_info][name]"
                           value="<?php echo esc_attr($business_info['name'] ?? get_bloginfo('name')); ?>" class="large-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_settings[business_info][description]" rows="4" class="large-text"><?php
                        echo esc_textarea($business_info['description'] ?? '');
                    ?></textarea>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Dirección', 'flavor-chat-ia'); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][address]" value="<?php echo esc_attr($business_info['address'] ?? ''); ?>" class="large-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][phone]" value="<?php echo esc_attr($business_info['phone'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Email', 'flavor-chat-ia'); ?></th>
                <td><input type="email" name="flavor_chat_ia_settings[business_info][email]" value="<?php echo esc_attr($business_info['email'] ?? ''); ?>" class="regular-text"></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario', 'flavor-chat-ia'); ?></th>
                <td><input type="text" name="flavor_chat_ia_settings[business_info][schedule]" value="<?php echo esc_attr($business_info['schedule'] ?? ''); ?>" class="large-text" placeholder="L-V 9:00-18:00"></td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Temas del negocio (Antispam)', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Temas permitidos', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[business_topics]"
                           value="<?php echo esc_attr(implode(', ', $settings['business_topics'] ?? [])); ?>" class="large-text"
                           placeholder="productos, pedidos, envíos, devoluciones">
                    <p class="description"><?php esc_html_e('Lista de temas separados por comas. El chat solo responderá sobre estos temas.', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>

        <hr>

        <h2><?php esc_html_e('Preguntas Frecuentes (FAQs)', 'flavor-chat-ia'); ?></h2>
        <div id="faqs-container">
            <?php
            if (empty($faqs)) $faqs = [['question' => '', 'answer' => '']];
            foreach ($faqs as $index => $faq):
            ?>
            <div class="faq-item" style="background:#fff;padding:15px;border:1px solid #ccd0d4;margin-bottom:10px;">
                <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
                    <strong><?php printf(__('FAQ #%d', 'flavor-chat-ia'), $index + 1); ?></strong>
                    <button type="button" class="button remove-faq"><?php esc_html_e('Eliminar', 'flavor-chat-ia'); ?></button>
                </div>
                <p>
                    <label><?php esc_html_e('Pregunta:', 'flavor-chat-ia'); ?></label>
                    <input type="text" name="flavor_chat_ia_settings[faqs][<?php echo $index; ?>][question]"
                           value="<?php echo esc_attr($faq['question'] ?? ''); ?>" class="large-text">
                </p>
                <p>
                    <label><?php esc_html_e('Respuesta:', 'flavor-chat-ia'); ?></label>
                    <textarea name="flavor_chat_ia_settings[faqs][<?php echo $index; ?>][answer]" rows="3" class="large-text"><?php
                        echo esc_textarea($faq['answer'] ?? '');
                    ?></textarea>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
        <button type="button" id="add-faq" class="button"><?php esc_html_e('+ Añadir FAQ', 'flavor-chat-ia'); ?></button>

        <hr>

        <h2><?php esc_html_e('Políticas', 'flavor-chat-ia'); ?></h2>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Política de envíos', 'flavor-chat-ia'); ?></th>
                <td><textarea name="flavor_chat_ia_settings[policies][shipping]" rows="3" class="large-text"><?php echo esc_textarea($policies['shipping'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Política de devoluciones', 'flavor-chat-ia'); ?></th>
                <td><textarea name="flavor_chat_ia_settings[policies][returns]" rows="3" class="large-text"><?php echo esc_textarea($policies['returns'] ?? ''); ?></textarea></td>
            </tr>
            <tr>
                <th><?php esc_html_e('Política de privacidad', 'flavor-chat-ia'); ?></th>
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
                <?php esc_html_e('Autoconfiguración con IA', 'flavor-chat-ia'); ?>
            </h3>
            <p style="margin:0 0 12px 0;"><?php esc_html_e('Detecta automáticamente la información de contacto de tu sitio.', 'flavor-chat-ia'); ?></p>
            <button type="button" id="autoconfig-escalation" class="button button-primary" data-section="escalation">
                <?php esc_html_e('Detectar datos de contacto', 'flavor-chat-ia'); ?>
            </button>
            <span class="autoconfig-status" style="margin-left:12px;"></span>
        </div>

        <h2><?php esc_html_e('Opciones de Contacto', 'flavor-chat-ia'); ?></h2>
        <p class="description"><?php esc_html_e('Se mostrarán cuando el chat escale a atención humana.', 'flavor-chat-ia'); ?></p>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e('WhatsApp', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[escalation_whatsapp]"
                           value="<?php echo esc_attr($settings['escalation_whatsapp'] ?? ''); ?>"
                           class="regular-text" placeholder="+34 600 000 000">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Teléfono', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_settings[escalation_phone]"
                           value="<?php echo esc_attr($settings['escalation_phone'] ?? ''); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Email', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="email" name="flavor_chat_ia_settings[escalation_email]"
                           value="<?php echo esc_attr($settings['escalation_email'] ?? ''); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario de atención', 'flavor-chat-ia'); ?></th>
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
        if (class_exists('Flavor_Chat_Module_Loader')) {
            $loader = Flavor_Chat_Module_Loader::get_instance();
            $available_modules = $loader->get_registered_modules();
        }

        // Módulos conocidos (hardcoded para cuando no hay loader)
        $known_modules = [
            'woocommerce' => [
                'name' => __('WooCommerce', 'flavor-chat-ia'),
                'description' => __('Integración con tienda WooCommerce - permite consultar productos, pedidos, etc.', 'flavor-chat-ia'),
                'requires' => 'WooCommerce',
            ],
            'banco_tiempo' => [
                'name' => __('Banco de Tiempo', 'flavor-chat-ia'),
                'description' => __('Sistema de intercambio de servicios y tiempo entre miembros.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'grupos_consumo' => [
                'name' => __('Grupos de Consumo', 'flavor-chat-ia'),
                'description' => __('Gestión de pedidos colectivos y grupos de consumo.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'marketplace' => [
                'name' => __('Marketplace', 'flavor-chat-ia'),
                'description' => __('Anuncios de regalo, venta e intercambio entre usuarios.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'facturas' => [
                'name' => __('Facturas', 'flavor-chat-ia'),
                'description' => __('Gestión de facturas y facturación para administradores desde la app móvil.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'fichaje_empleados' => [
                'name' => __('Fichaje de Empleados', 'flavor-chat-ia'),
                'description' => __('Control de horarios, asistencia y fichaje de empleados desde la app móvil.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'eventos' => [
                'name' => __('Eventos', 'flavor-chat-ia'),
                'description' => __('Gestión de eventos comunitarios, actividades y encuentros desde la app móvil.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'socios' => [
                'name' => __('Gestión de Socios', 'flavor-chat-ia'),
                'description' => __('Gestión de socios, cuotas y membresías desde la app móvil.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'incidencias' => [
                'name' => __('Incidencias Urbanas', 'flavor-chat-ia'),
                'description' => __('Reporte y seguimiento de incidencias en el barrio (baches, farolas, etc.).', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'participacion' => [
                'name' => __('Participación Ciudadana', 'flavor-chat-ia'),
                'description' => __('Propuestas, votaciones y consultas ciudadanas.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'presupuestos_participativos' => [
                'name' => __('Presupuestos Participativos', 'flavor-chat-ia'),
                'description' => __('Sistema de presupuestos participativos - los vecinos deciden cómo gastar el presupuesto municipal.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'avisos_municipales' => [
                'name' => __('Avisos Municipales', 'flavor-chat-ia'),
                'description' => __('Canal oficial de comunicación del ayuntamiento con los vecinos.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'advertising' => [
                'name' => __('Publicidad', 'flavor-chat-ia'),
                'description' => __('Gestión de anuncios y campañas publicitarias en la plataforma.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'ayuda_vecinal' => [
                'name' => __('Ayuda Vecinal', 'flavor-chat-ia'),
                'description' => __('Red de ayuda mutua entre vecinos - ofrece y solicita ayuda en tu comunidad.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'biblioteca' => [
                'name' => __('Biblioteca Comunitaria', 'flavor-chat-ia'),
                'description' => __('Sistema de préstamo e intercambio de libros entre vecinos.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'bicicletas_compartidas' => [
                'name' => __('Bicicletas Compartidas', 'flavor-chat-ia'),
                'description' => __('Sistema de préstamo y uso compartido de bicicletas comunitarias.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'carpooling' => [
                'name' => __('Carpooling', 'flavor-chat-ia'),
                'description' => __('Sistema de viajes compartidos para reducir costes y emisiones.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'chat_grupos' => [
                'name' => __('Chat de Grupos', 'flavor-chat-ia'),
                'description' => __('Canales de chat grupales para comunicación comunitaria.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'chat_interno' => [
                'name' => __('Chat Interno', 'flavor-chat-ia'),
                'description' => __('Mensajería directa entre usuarios de la comunidad.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'compostaje' => [
                'name' => __('Compostaje Comunitario', 'flavor-chat-ia'),
                'description' => __('Gestión de composteras comunitarias y recogida de residuos orgánicos.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'cursos' => [
                'name' => __('Cursos y Formación', 'flavor-chat-ia'),
                'description' => __('Plataforma de cursos y formación continua comunitaria.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'empresarial' => [
                'name' => __('Gestión Empresarial', 'flavor-chat-ia'),
                'description' => __('Herramientas de gestión empresarial: CRM, proyectos, tareas.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'espacios_comunes' => [
                'name' => __('Espacios Comunes', 'flavor-chat-ia'),
                'description' => __('Reserva y gestión de espacios comunitarios compartidos.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'huertos_urbanos' => [
                'name' => __('Huertos Urbanos', 'flavor-chat-ia'),
                'description' => __('Gestión de parcelas, riego y cosechas en huertos urbanos comunitarios.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'multimedia' => [
                'name' => __('Multimedia', 'flavor-chat-ia'),
                'description' => __('Galería multimedia compartida: fotos, vídeos y documentos de la comunidad.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'parkings' => [
                'name' => __('Parkings Compartidos', 'flavor-chat-ia'),
                'description' => __('Sistema de plazas de aparcamiento compartidas entre vecinos.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'podcast' => [
                'name' => __('Podcast', 'flavor-chat-ia'),
                'description' => __('Plataforma de podcast comunitario con episodios y suscripciones.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'radio' => [
                'name' => __('Radio Comunitaria', 'flavor-chat-ia'),
                'description' => __('Emisora de radio comunitaria con programación y emisión en directo.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'reciclaje' => [
                'name' => __('Reciclaje', 'flavor-chat-ia'),
                'description' => __('Puntos de reciclaje, recogida selectiva y estadísticas ambientales.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'red_social' => [
                'name' => __('Red Social', 'flavor-chat-ia'),
                'description' => __('Red social interna con publicaciones, perfiles y seguidores.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'talleres' => [
                'name' => __('Talleres Prácticos', 'flavor-chat-ia'),
                'description' => __('Talleres prácticos y workshops organizados por y para la comunidad.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'tramites' => [
                'name' => __('Trámites y Gestiones', 'flavor-chat-ia'),
                'description' => __('Sistema de gestión de trámites administrativos y solicitudes ciudadanas.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'transparencia' => [
                'name' => __('Portal de Transparencia', 'flavor-chat-ia'),
                'description' => __('Portal de transparencia con datos públicos, presupuestos y rendición de cuentas.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'colectivos' => [
                'name' => __('Colectivos', 'flavor-chat-ia'),
                'description' => __('Gestión de colectivos y asociaciones locales.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'foros' => [
                'name' => __('Foros', 'flavor-chat-ia'),
                'description' => __('Foros de discusión comunitarios por temáticas.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'clientes' => [
                'name' => __('Clientes', 'flavor-chat-ia'),
                'description' => __('Gestión de clientes y CRM integrado.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'comunidades' => [
                'name' => __('Comunidades', 'flavor-chat-ia'),
                'description' => __('Gestión de comunidades y sub-comunidades.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'bares' => [
                'name' => __('Bares y Hostelería', 'flavor-chat-ia'),
                'description' => __('Gestión de bares, restaurantes y hostelería local.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'trading_ia' => [
                'name' => __('Trading IA', 'flavor-chat-ia'),
                'description' => __('Herramientas de trading asistidas por inteligencia artificial.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'dex_solana' => [
                'name' => __('DEX Solana', 'flavor-chat-ia'),
                'description' => __('Exchange descentralizado en la blockchain de Solana.', 'flavor-chat-ia'),
                'requires' => null,
            ],
            'themacle' => [
                'name' => __('Themacle Web Components', 'flavor-chat-ia'),
                'description' => __('Componentes web universales reutilizables para el constructor de páginas: heros, grids, galerías, CTAs y más.', 'flavor-chat-ia'),
                'requires' => null,
            ],
        ];

        // Combinar información
        foreach ($known_modules as $id => &$module) {
            if (isset($available_modules[$id])) {
                // Si el módulo está en available_modules pero no puede activarse por falta de tablas,
                // permitir activarlo de todas formas si no tiene dependencias externas
                if (!$available_modules[$id]['can_activate'] && !$module['requires']) {
                    $module['can_activate'] = true;
                    $module['activation_error'] = __('Las tablas se crearán automáticamente al activar', 'flavor-chat-ia');
                } else {
                    $module['can_activate'] = $available_modules[$id]['can_activate'];
                    $module['activation_error'] = $available_modules[$id]['activation_error'];
                }
                $module['is_loaded'] = $available_modules[$id]['is_loaded'];
            } else {
                // Si no está en available_modules, verificar solo dependencias externas
                $module['can_activate'] = !$module['requires'] || class_exists($module['requires']);
                $module['activation_error'] = $module['requires'] && !class_exists($module['requires'])
                    ? sprintf(__('Requiere %s instalado', 'flavor-chat-ia'), $module['requires'])
                    : '';
                $module['is_loaded'] = false;
            }
        }
        ?>
        <input type="hidden" name="flavor_chat_ia_settings[_tab]" value="modules">

        <h2><?php esc_html_e('Módulos para Apps Móviles', 'flavor-chat-ia'); ?></h2>
        <p><?php esc_html_e('Selecciona qué módulos estarán disponibles para las aplicaciones móviles a través de la API REST.', 'flavor-chat-ia'); ?></p>

        <div class="notice notice-info inline" style="margin: 20px 0;">
            <p>
                <strong><?php esc_html_e('Importante:', 'flavor-chat-ia'); ?></strong>
                <?php esc_html_e('Los módulos activos se mostrarán en las apps móviles y sus funcionalidades estarán disponibles vía API.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 50px;"><?php esc_html_e('Activo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Descripción', 'flavor-chat-ia'); ?></th>
                    <th style="width: 120px;"><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
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
                                ⚙️ <?php esc_html_e('Configurar', 'flavor-chat-ia'); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo esc_html($module_data['description']); ?>
                    </td>
                    <td>
                        <?php if ($can_activate): ?>
                            <?php if ($module_data['is_loaded']): ?>
                                <span style="color: #46b450;">✓ <?php esc_html_e('Cargado', 'flavor-chat-ia'); ?></span>
                            <?php elseif ($is_active): ?>
                                <span style="color: #0073aa;">○ <?php esc_html_e('Activo', 'flavor-chat-ia'); ?></span>
                            <?php else: ?>
                                <span style="color: #999;">○ <?php esc_html_e('Disponible', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span style="color: #dc3232;">✗ <?php esc_html_e('No disponible', 'flavor-chat-ia'); ?></span>
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
                <strong><?php esc_html_e('Nota:', 'flavor-chat-ia'); ?></strong>
                <?php esc_html_e('Los módulos marcados como "No disponible" requieren que instales primero el plugin o extensión correspondiente.', 'flavor-chat-ia'); ?>
            </p>
        </div>

        <h3 style="margin-top: 30px;"><?php esc_html_e('Endpoints de API', 'flavor-chat-ia'); ?></h3>
        <p><?php esc_html_e('Las apps móviles acceden a estos endpoints para consumir los módulos activos:', 'flavor-chat-ia'); ?></p>

        <table class="widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e('Módulo', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Endpoint', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e('Información del sitio', 'flavor-chat-ia'); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/info')); ?></code></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Lista de módulos', 'flavor-chat-ia'); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/modules')); ?></code></td>
                </tr>
                <tr>
                    <td><?php esc_html_e('Tema y colores', 'flavor-chat-ia'); ?></td>
                    <td><code><?php echo esc_url(rest_url('app-discovery/v1/theme')); ?></code></td>
                </tr>
                <?php foreach ($known_modules as $module_id => $module_data):
                    if (in_array($module_id, $active_modules)):
                ?>
                <tr>
                    <td><?php echo esc_html($module_data['name']); ?></td>
                    <td><code><?php echo esc_url(rest_url("flavor-chat-ia/v1/{$module_id}/")); ?></code></td>
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
        $module_settings = get_option("flavor_chat_ia_module_{$module_id}", []);

        ?>
        <div style="background: white; border: 1px solid #ddd; border-radius: 4px; padding: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin: 0;">
                    <?php printf(__('Configuración de %s', 'flavor-chat-ia'), $module_data['name']); ?>
                </h3>
                <button type="button" class="button"
                        onclick="document.getElementById('module-config-<?php echo esc_attr($module_id); ?>').style.display='none';">
                    <?php esc_html_e('Cerrar', 'flavor-chat-ia'); ?>
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
                <p><strong><?php esc_html_e('Banco de Tiempo', 'flavor-chat-ia'); ?></strong></p>
                <p><?php esc_html_e('Sistema de intercambio de servicios donde el tiempo es la moneda. Cada hora de servicio ofrecido = 1 hora de tiempo que puedes recibir.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('⏱️ Configuración de Intercambios', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Horas mínimas por intercambio', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[hora_minima_intercambio]"
                           value="<?php echo esc_attr($hora_min); ?>"
                           step="0.5"
                           min="0.5"
                           max="24"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Duración mínima de un intercambio de servicios', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horas máximas por intercambio', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[hora_maxima_intercambio]"
                           value="<?php echo esc_attr($hora_max); ?>"
                           step="0.5"
                           min="1"
                           max="24"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Duración máxima de un intercambio de servicios', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Validación de intercambios', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[requiere_validacion]"
                               value="1"
                               <?php checked($requiere_validacion); ?>>
                        <?php esc_html_e('Requiere que ambas partes confirmen el intercambio realizado', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('💰 Gestión de Saldos', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Saldo inicial (horas)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[saldo_inicial_horas]"
                           value="<?php echo esc_attr($saldo_inicial); ?>"
                           step="1"
                           min="0"
                           max="50"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Horas que reciben los nuevos miembros al unirse al banco de tiempo', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir saldo negativo', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[permite_saldo_negativo]"
                               value="1"
                               <?php checked($permite_negativos); ?>
                               id="permite_saldo_negativo">
                        <?php esc_html_e('Los usuarios pueden recibir servicios aunque no tengan horas disponibles', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Límite de saldo negativo', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[limite_saldo_negativo]"
                           value="<?php echo esc_attr($limite_negativo); ?>"
                           step="1"
                           min="-50"
                           max="0"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Máximo de horas negativas permitidas (ej: -10 significa que pueden deber hasta 10 horas)', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('🔔 Notificaciones', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Notificar saldo bajo', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_banco_tiempo[notificar_saldo_bajo]"
                               value="1"
                               <?php checked($notificar_saldo_bajo); ?>>
                        <?php esc_html_e('Avisar a los usuarios cuando su saldo sea bajo', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Umbral de notificación', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_banco_tiempo[umbral_notificacion_saldo]"
                           value="<?php echo esc_attr($umbral_notificacion); ?>"
                           step="1"
                           min="0"
                           max="10"
                           class="small-text"> horas
                    <p class="description"><?php esc_html_e('Notificar cuando el saldo esté por debajo de este número de horas', 'flavor-chat-ia'); ?></p>
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
                <p><strong><?php esc_html_e('Grupos de Consumo', 'flavor-chat-ia'); ?></strong></p>
                <p><?php esc_html_e('Organiza pedidos colectivos a productores locales. Los miembros se unen a pedidos, comparten gastos de transporte y reciben productos frescos.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('📦 Configuración de Pedidos', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Días para realizar pedido', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[dias_para_pedido]"
                           value="<?php echo esc_attr($dias_pedido); ?>"
                           min="1"
                           max="30"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Plazo para que los miembros se unan a un pedido colectivo antes de cerrarlo', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Pedido mínimo por persona (€)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[pedido_minimo]"
                           value="<?php echo esc_attr($pedido_minimo); ?>"
                           step="0.01"
                           min="0"
                           class="small-text"> €
                    <p class="description"><?php esc_html_e('Importe mínimo que debe pedir cada participante', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Participantes mínimos', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[participantes_minimos]"
                           value="<?php echo esc_attr($participantes_minimos); ?>"
                           min="2"
                           max="50"
                           class="small-text"> personas
                    <p class="description"><?php esc_html_e('Número mínimo de participantes para confirmar un pedido colectivo', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Múltiples pedidos', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[permite_multiples_pedidos]"
                               value="1"
                               <?php checked($permite_multiples); ?>>
                        <?php esc_html_e('Permitir varios pedidos abiertos al mismo tiempo', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('🚚 Logística y Reparto', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Coordinar reparto', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[coordina_reparto]"
                               value="1"
                               <?php checked($coordina_reparto); ?>>
                        <?php esc_html_e('Gestionar puntos de recogida y horarios desde la app', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Gastos de gestión (%)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_grupos_consumo[porcentaje_gastos_gestion]"
                           value="<?php echo esc_attr($gastos_gestion); ?>"
                           step="0.5"
                           min="0"
                           max="20"
                           class="small-text"> %
                    <p class="description"><?php esc_html_e('Porcentaje añadido para cubrir gastos de gestión y transporte', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('💳 Pagos', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Pago anticipado', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_grupos_consumo[requiere_pago_anticipado]"
                               value="1"
                               <?php checked($requiere_pago); ?>>
                        <?php esc_html_e('Requerir pago antes de confirmar participación', 'flavor-chat-ia'); ?>
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
                <p><strong><?php esc_html_e('Marketplace Comunitario', 'flavor-chat-ia'); ?></strong></p>
                <p><?php esc_html_e('Plataforma para compartir, intercambiar y vender objetos entre vecinos. Fomenta la economía circular y reduce el desperdicio.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('🏷️ Tipos de Anuncios', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Tipos permitidos', 'flavor-chat-ia'); ?></th>
                <td>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_venta]" value="1" <?php checked($permite_venta); ?>>
                        <?php esc_html_e('Venta - Objetos en venta con precio', 'flavor-chat-ia'); ?>
                    </label>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_intercambio]" value="1" <?php checked($permite_intercambio); ?>>
                        <?php esc_html_e('Intercambio - Trueque de objetos', 'flavor-chat-ia'); ?>
                    </label>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_regalo]" value="1" <?php checked($permite_regalo); ?>>
                        <?php esc_html_e('Regalo - Objetos gratuitos', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('📋 Gestión de Anuncios', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Moderación', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[requiere_moderacion]" value="1" <?php checked($requiere_moderacion); ?>>
                        <?php esc_html_e('Los anuncios requieren aprobación antes de publicarse', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Días de vigencia', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_marketplace[dias_vigencia_anuncio]"
                           value="<?php echo esc_attr($dias_vigencia); ?>" min="7" max="90" class="small-text"> días
                    <p class="description"><?php esc_html_e('Los anuncios se archivarán automáticamente después de este tiempo', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Máximo de fotos', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_marketplace[max_fotos_por_anuncio]"
                           value="<?php echo esc_attr($max_fotos); ?>" min="1" max="10" class="small-text"> fotos
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Sistema de reservas', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_marketplace[permite_reservas]" value="1" <?php checked($permite_reservas); ?>>
                        <?php esc_html_e('Permitir reservar objetos temporalmente', 'flavor-chat-ia'); ?>
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
                <th><?php esc_html_e('Mostrar stock disponible', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_woocommerce[mostrar_stock]"
                               value="1"
                               <?php checked($mostrar_stock); ?>>
                        <?php esc_html_e('Mostrar información de stock en búsquedas', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Productos por búsqueda', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_woocommerce[limite_productos_busqueda]"
                           value="<?php echo esc_attr($limite_productos); ?>"
                           min="5"
                           max="50"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número máximo de productos a mostrar en cada búsqueda', 'flavor-chat-ia'); ?></p>
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
                <p><strong><?php esc_html_e('Gestión de Facturas', 'flavor-chat-ia'); ?></strong></p>
                <p><?php esc_html_e('Sistema completo de facturación para administradores. Crea, envía y gestiona facturas directamente desde la app móvil.', 'flavor-chat-ia'); ?></p>
            </div>
        </div>

        <h4><?php esc_html_e('📄 Configuración de Facturas', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Serie predeterminada', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_module_facturas[serie_predeterminada]"
                           value="<?php echo esc_attr($serie); ?>"
                           maxlength="3" class="small-text" style="text-transform: uppercase;">
                    <p class="description"><?php esc_html_e('Letra(s) para identificar la serie (ej: F, A, B)', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Formato de numeración', 'flavor-chat-ia'); ?></th>
                <td>
                    <select name="flavor_chat_ia_module_facturas[formato_numero_factura]">
                        <option value="SERIE-YYYY-NNNN" <?php selected($formato_numero, 'SERIE-YYYY-NNNN'); ?>>F-2025-0001</option>
                        <option value="SERIE-NNNN" <?php selected($formato_numero, 'SERIE-NNNN'); ?>>F-0001</option>
                        <option value="YYYY/NNNN" <?php selected($formato_numero, 'YYYY/NNNN'); ?>>2025/0001</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('IVA predeterminado (%)', 'flavor-chat-ia'); ?></th>
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
                <th><?php esc_html_e('Días de vencimiento', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number" name="flavor_chat_ia_module_facturas[dias_vencimiento_predeterminado]"
                           value="<?php echo esc_attr($dias_vencimiento); ?>" min="0" max="180" class="small-text"> días
                    <p class="description"><?php esc_html_e('Plazo de pago predeterminado desde la emisión', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('📧 Envío de Facturas', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Envío automático por email', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="flavor_chat_ia_module_facturas[enviar_email_automatico]" value="1" <?php checked($enviar_email); ?>>
                        <?php esc_html_e('Enviar factura por email automáticamente al crearla', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h4 style="margin-top: 30px;"><?php esc_html_e('⚙️ Opciones Avanzadas', 'flavor-chat-ia'); ?></h4>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e('Prefijo facturas rectificativas', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="text" name="flavor_chat_ia_module_facturas[prefijo_rectificativa]"
                           value="<?php echo esc_attr($prefijo_rectificativa); ?>"
                           maxlength="3" class="small-text" style="text-transform: uppercase;">
                    <p class="description"><?php esc_html_e('Letra para identificar facturas rectificativas (ej: R)', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Texto pie de factura', 'flavor-chat-ia'); ?></th>
                <td>
                    <textarea name="flavor_chat_ia_module_facturas[texto_pie_factura]"
                              rows="3" class="large-text"><?php echo esc_textarea($texto_pie); ?></textarea>
                    <p class="description"><?php esc_html_e('Texto adicional en el pie de todas las facturas', 'flavor-chat-ia'); ?></p>
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
                <th><?php esc_html_e('Horario de entrada', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="time"
                           name="flavor_chat_ia_module_fichaje_empleados[horario_entrada]"
                           value="<?php echo esc_attr($horario_entrada); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Horario de salida', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="time"
                           name="flavor_chat_ia_module_fichaje_empleados[horario_salida]"
                           value="<?php echo esc_attr($horario_salida); ?>"
                           class="regular-text">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Tiempo de gracia (minutos)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_fichaje_empleados[tiempo_gracia]"
                           value="<?php echo esc_attr($tiempo_gracia); ?>"
                           min="0"
                           max="60"
                           class="small-text"> minutos
                    <p class="description"><?php esc_html_e('Minutos de margen antes de marcar retraso', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Requiere geolocalización', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_fichaje_empleados[requiere_geolocalizacion]"
                               value="1"
                               <?php checked($requiere_gps); ?>>
                        <?php esc_html_e('Obligar a activar GPS para fichar', 'flavor-chat-ia'); ?>
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
                <th><?php esc_html_e('Eventos requieren aprobación', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_eventos[requiere_aprobacion]"
                               value="1"
                               <?php checked($requiere_aprobacion); ?>>
                        <?php esc_html_e('Los eventos creados por usuarios deben ser aprobados por administrador', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir acompañantes', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_eventos[permite_invitados]"
                               value="1"
                               <?php checked($permite_invitados); ?>>
                        <?php esc_html_e('Los asistentes pueden registrar acompañantes', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Días de recordatorio', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_eventos[dias_recordatorio]"
                           value="<?php echo esc_attr($dias_recordatorio); ?>"
                           min="0"
                           max="7"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Días antes del evento para enviar recordatorio (0 = desactivado)', 'flavor-chat-ia'); ?></p>
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
                <th><?php esc_html_e('Cuota mensual (€)', 'flavor-chat-ia'); ?></th>
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
                <th><?php esc_html_e('Cuota anual (€)', 'flavor-chat-ia'); ?></th>
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
                <th><?php esc_html_e('Día de cargo mensual', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_socios[dia_cargo]"
                           value="<?php echo esc_attr($dia_cargo); ?>"
                           min="1"
                           max="28"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Día del mes para cargo automático de cuotas', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Permitir cuota reducida', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_socios[permite_cuota_reducida]"
                               value="1"
                               <?php checked($permite_reducida); ?>>
                        <?php esc_html_e('Permitir cuotas reducidas para casos especiales', 'flavor-chat-ia'); ?>
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
                <th><?php esc_html_e('Requiere ubicación GPS', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[requiere_ubicacion_gps]"
                               value="1"
                               <?php checked($requiere_gps); ?>>
                        <?php esc_html_e('Las incidencias deben incluir ubicación GPS', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Requiere fotografía', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[requiere_foto]"
                               value="1"
                               <?php checked($requiere_foto); ?>>
                        <?php esc_html_e('Las incidencias deben incluir al menos una foto', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Visibilidad pública', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_incidencias[visibilidad_publica]"
                               value="1"
                               <?php checked($visibilidad); ?>>
                        <?php esc_html_e('Las incidencias son visibles públicamente para todos los vecinos', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos para marcar como urgente', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_incidencias[votos_para_urgencia]"
                           value="<?php echo esc_attr($votos_urgencia); ?>"
                           min="1"
                           max="50"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número de votos necesarios para cambiar prioridad a urgente', 'flavor-chat-ia'); ?></p>
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
                <th><?php esc_html_e('Verificación de vecinos', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_participacion[requiere_verificacion]"
                               value="1"
                               <?php checked($requiere_verificacion); ?>>
                        <?php esc_html_e('Solo vecinos verificados pueden votar y crear propuestas', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Moderación de propuestas', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_participacion[moderacion_propuestas]"
                               value="1"
                               <?php checked($moderacion); ?>>
                        <?php esc_html_e('Las propuestas requieren aprobación del ayuntamiento antes de publicarse', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos necesarios para propuesta', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_participacion[votos_necesarios_propuesta]"
                           value="<?php echo esc_attr($votos_necesarios); ?>"
                           min="5"
                           max="500"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Apoyos mínimos para que una propuesta sea evaluada', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Duración de votaciones (días)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_participacion[duracion_votacion_dias]"
                           value="<?php echo esc_attr($duracion_votacion); ?>"
                           min="1"
                           max="30"
                           class="small-text"> días
                    <p class="description"><?php esc_html_e('Tiempo por defecto para las votaciones', 'flavor-chat-ia'); ?></p>
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
                <th><?php esc_html_e('Presupuesto anual (€)', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[presupuesto_anual]"
                           value="<?php echo esc_attr($presupuesto_anual); ?>"
                           step="1000"
                           min="0"
                           class="regular-text"> €
                    <p class="description"><?php esc_html_e('Presupuesto total disponible para proyectos ciudadanos', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Votos máximos por persona', 'flavor-chat-ia'); ?></th>
                <td>
                    <input type="number"
                           name="flavor_chat_ia_module_presupuestos_participativos[votos_maximos_por_persona]"
                           value="<?php echo esc_attr($votos_maximos); ?>"
                           min="1"
                           max="10"
                           class="small-text">
                    <p class="description"><?php esc_html_e('Número máximo de proyectos que puede votar cada persona', 'flavor-chat-ia'); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Monto mínimo de proyecto (€)', 'flavor-chat-ia'); ?></th>
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
                <th><?php esc_html_e('Monto máximo de proyecto (€)', 'flavor-chat-ia'); ?></th>
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
                <th><?php esc_html_e('Notificaciones push', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_avisos_municipales[enviar_push_notifications]"
                               value="1"
                               <?php checked($enviar_push); ?>>
                        <?php esc_html_e('Enviar notificaciones push para avisos urgentes', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e('Confirmación de lectura', 'flavor-chat-ia'); ?></th>
                <td>
                    <label>
                        <input type="checkbox"
                               name="flavor_chat_ia_module_avisos_municipales[requiere_confirmacion_lectura]"
                               value="1"
                               <?php checked($requiere_confirmacion); ?>>
                        <?php esc_html_e('Requerir confirmación de lectura en avisos importantes', 'flavor-chat-ia'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <p class="description">
                        <?php esc_html_e('Categorías disponibles: Urgente, Corte de servicio, Evento, Informativo, Tráfico, Obras, Convocatoria', 'flavor-chat-ia'); ?>
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
        $loader = class_exists('Flavor_Chat_Module_Loader') ? Flavor_Chat_Module_Loader::get_instance() : null;
        $modulos_registrados = $loader ? $loader->get_registered_modules() : [];
        $default_settings = [];

        if (isset($modulos_registrados[$module_id])) {
            $module_file = FLAVOR_CHAT_IA_PATH . 'includes/modules/' . str_replace('_', '-', $module_id) . '/class-' . str_replace('_', '-', $module_id) . '-module.php';
            if (file_exists($module_file)) {
                require_once $module_file;
                $class_name = $modulos_registrados[$module_id]['name'] ?? '';
                // Obtener las claves de settings desde la opción guardada o los defaults del módulo
                $option_key = 'flavor_chat_ia_module_' . $module_id;
                $default_settings = get_option($option_key, []);
            }
        }

        $settings = wp_parse_args($saved_settings, $default_settings);

        if (empty($settings)) {
            ?>
            <p><?php esc_html_e('Este módulo no tiene configuraciones adicionales.', 'flavor-chat-ia'); ?></p>
            <?php
            return;
        }

        // Mapeo de nombres legibles para las claves de configuración
        $etiquetas = [
            'disponible_app' => __('Disponible en', 'flavor-chat-ia'),
            'requiere_verificacion_usuarios' => __('Requiere verificación de usuarios', 'flavor-chat-ia'),
            'requiere_verificacion_usuario' => __('Requiere verificación de usuario', 'flavor-chat-ia'),
            'requiere_verificacion_conductor' => __('Requiere verificación de conductor', 'flavor-chat-ia'),
            'permite_valoraciones' => __('Permite valoraciones', 'flavor-chat-ia'),
            'sistema_puntos_solidaridad' => __('Sistema de puntos solidarios', 'flavor-chat-ia'),
            'sistema_puntos' => __('Sistema de puntos', 'flavor-chat-ia'),
            'puntos_por_ayuda' => __('Puntos por ayuda', 'flavor-chat-ia'),
            'puntos_por_prestamo' => __('Puntos por préstamo', 'flavor-chat-ia'),
            'puntos_por_kg' => __('Puntos por Kg', 'flavor-chat-ia'),
            'puntos_por_kg_depositado' => __('Puntos por Kg depositado', 'flavor-chat-ia'),
            'permite_donaciones' => __('Permite donaciones', 'flavor-chat-ia'),
            'permite_intercambios' => __('Permite intercambios', 'flavor-chat-ia'),
            'permite_prestamos' => __('Permite préstamos', 'flavor-chat-ia'),
            'duracion_prestamo_dias' => __('Duración del préstamo (días)', 'flavor-chat-ia'),
            'renovaciones_maximas' => __('Renovaciones máximas', 'flavor-chat-ia'),
            'permite_reservas' => __('Permite reservas', 'flavor-chat-ia'),
            'permite_reservas_anticipadas' => __('Permite reservas anticipadas', 'flavor-chat-ia'),
            'permite_reservas_recurrentes' => __('Permite reservas recurrentes', 'flavor-chat-ia'),
            'requiere_verificacion_isbn' => __('Requiere verificación ISBN', 'flavor-chat-ia'),
            'requiere_fianza' => __('Requiere fianza', 'flavor-chat-ia'),
            'importe_fianza' => __('Importe de fianza (€)', 'flavor-chat-ia'),
            'importe_fianza_predeterminado' => __('Fianza predeterminada (€)', 'flavor-chat-ia'),
            'precio_hora' => __('Precio por hora (€)', 'flavor-chat-ia'),
            'precio_dia' => __('Precio por día (€)', 'flavor-chat-ia'),
            'precio_mes' => __('Precio por mes (€)', 'flavor-chat-ia'),
            'precio_medio_hora' => __('Precio medio/hora (€)', 'flavor-chat-ia'),
            'precio_medio_dia' => __('Precio medio/día (€)', 'flavor-chat-ia'),
            'precio_medio_mes' => __('Precio medio/mes (€)', 'flavor-chat-ia'),
            'precio_por_km' => __('Precio por Km (€)', 'flavor-chat-ia'),
            'precio_parcela_anual' => __('Precio parcela anual (€)', 'flavor-chat-ia'),
            'duracion_maxima_prestamo_dias' => __('Duración máxima préstamo (días)', 'flavor-chat-ia'),
            'duracion_maxima_horas' => __('Duración máxima (horas)', 'flavor-chat-ia'),
            'duracion_maxima_programa' => __('Duración máxima programa (min)', 'flavor-chat-ia'),
            'duracion_maxima_minutos' => __('Duración máxima (min)', 'flavor-chat-ia'),
            'horas_anticipacion_reserva' => __('Horas anticipación reserva', 'flavor-chat-ia'),
            'horas_anticipacion_minima' => __('Horas anticipación mínima', 'flavor-chat-ia'),
            'horas_anticipacion_cancelacion' => __('Horas anticipación cancelación', 'flavor-chat-ia'),
            'dias_anticipacion_maxima' => __('Días anticipación máxima', 'flavor-chat-ia'),
            'dias_anticipacion_cancelacion' => __('Días anticipación cancelación', 'flavor-chat-ia'),
            'requiere_aprobacion_organizadores' => __('Requiere aprobación de organizadores', 'flavor-chat-ia'),
            'requiere_aprobacion_instructores' => __('Requiere aprobación de instructores', 'flavor-chat-ia'),
            'requiere_aprobacion_programas' => __('Requiere aprobación de programas', 'flavor-chat-ia'),
            'permite_talleres_gratuitos' => __('Permite talleres gratuitos', 'flavor-chat-ia'),
            'permite_talleres_pago' => __('Permite talleres de pago', 'flavor-chat-ia'),
            'permite_cursos_gratuitos' => __('Permite cursos gratuitos', 'flavor-chat-ia'),
            'permite_cursos_pago' => __('Permite cursos de pago', 'flavor-chat-ia'),
            'permite_cursos_online' => __('Permite cursos online', 'flavor-chat-ia'),
            'permite_cursos_presenciales' => __('Permite cursos presenciales', 'flavor-chat-ia'),
            'permite_certificados' => __('Permite certificados', 'flavor-chat-ia'),
            'requiere_evaluacion' => __('Requiere evaluación', 'flavor-chat-ia'),
            'comision_talleres_pago' => __('Comisión talleres de pago (%)', 'flavor-chat-ia'),
            'comision_cursos_pago' => __('Comisión cursos de pago (%)', 'flavor-chat-ia'),
            'comision_plataforma_porcentaje' => __('Comisión plataforma (%)', 'flavor-chat-ia'),
            'max_participantes_por_taller' => __('Máx. participantes por taller', 'flavor-chat-ia'),
            'max_alumnos_por_curso' => __('Máx. alumnos por curso', 'flavor-chat-ia'),
            'max_pasajeros_por_viaje' => __('Máx. pasajeros por viaje', 'flavor-chat-ia'),
            'min_participantes_para_confirmar' => __('Mín. participantes para confirmar', 'flavor-chat-ia'),
            'permite_lista_espera' => __('Permite lista de espera', 'flavor-chat-ia'),
            'permite_mascotas' => __('Permite mascotas', 'flavor-chat-ia'),
            'permite_equipaje_grande' => __('Permite equipaje grande', 'flavor-chat-ia'),
            'radio_busqueda_km' => __('Radio de búsqueda (Km)', 'flavor-chat-ia'),
            'calculo_coste_automatico' => __('Cálculo de coste automático', 'flavor-chat-ia'),
            'permite_subir' => __('Permite subir contenido', 'flavor-chat-ia'),
            'permite_subir_episodios' => __('Permite subir episodios', 'flavor-chat-ia'),
            'requiere_moderacion' => __('Requiere moderación', 'flavor-chat-ia'),
            'permite_comentarios' => __('Permite comentarios', 'flavor-chat-ia'),
            'permite_albumes' => __('Permite álbumes', 'flavor-chat-ia'),
            'permite_geolocalizacion' => __('Permite geolocalización', 'flavor-chat-ia'),
            'genera_thumbnails' => __('Genera miniaturas', 'flavor-chat-ia'),
            'genera_rss' => __('Genera feed RSS', 'flavor-chat-ia'),
            'transcripcion_automatica' => __('Transcripción automática', 'flavor-chat-ia'),
            'max_tamano_imagen_mb' => __('Tamaño máx. imagen (MB)', 'flavor-chat-ia'),
            'max_tamano_video_mb' => __('Tamaño máx. vídeo (MB)', 'flavor-chat-ia'),
            'tamano_maximo_mb' => __('Tamaño máximo (MB)', 'flavor-chat-ia'),
            'requiere_fotos' => __('Requiere fotos', 'flavor-chat-ia'),
            'notificar_liberacion' => __('Notificar liberación', 'flavor-chat-ia'),
            'notificar_mantenimiento' => __('Notificar mantenimiento', 'flavor-chat-ia'),
            'notificar_recogidas' => __('Notificar recogidas', 'flavor-chat-ia'),
            'notificar_compost_listo' => __('Notificar compost listo', 'flavor-chat-ia'),
            'permite_alquiler_temporal' => __('Permite alquiler temporal', 'flavor-chat-ia'),
            'permite_alquiler_permanente' => __('Permite alquiler permanente', 'flavor-chat-ia'),
            'permite_reportar_problemas' => __('Permite reportar problemas', 'flavor-chat-ia'),
            'permite_reportar_contenedores' => __('Permite reportar contenedores', 'flavor-chat-ia'),
            'permite_canje_puntos' => __('Permite canje de puntos', 'flavor-chat-ia'),
            'permite_recoger_compost' => __('Permite recoger compost', 'flavor-chat-ia'),
            'permite_solicitar_parcela' => __('Permite solicitar parcela', 'flavor-chat-ia'),
            'permite_intercambio_cosechas' => __('Permite intercambio de cosechas', 'flavor-chat-ia'),
            'permite_locutores_comunidad' => __('Permite locutores de la comunidad', 'flavor-chat-ia'),
            'permite_dedicatorias' => __('Permite dedicatorias', 'flavor-chat-ia'),
            'chat_en_vivo' => __('Chat en vivo', 'flavor-chat-ia'),
            'grabacion_automatica' => __('Grabación automática', 'flavor-chat-ia'),
            'url_stream' => __('URL del stream', 'flavor-chat-ia'),
            'frecuencia_fm' => __('Frecuencia FM', 'flavor-chat-ia'),
            'kg_minimos_recogida' => __('Kg mínimos para recogida', 'flavor-chat-ia'),
            'sistema_turnos_volteo' => __('Sistema de turnos de volteo', 'flavor-chat-ia'),
            'sistema_turnos_riego' => __('Sistema de turnos de riego', 'flavor-chat-ia'),
            'requiere_compromiso_asistencia' => __('Requiere compromiso de asistencia', 'flavor-chat-ia'),
            'horas_minimas_mes' => __('Horas mínimas al mes', 'flavor-chat-ia'),
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
                            <option value="cliente" <?php selected($valor_ajuste, 'cliente'); ?>><?php esc_html_e('App cliente', 'flavor-chat-ia'); ?></option>
                            <option value="admin" <?php selected($valor_ajuste, 'admin'); ?>><?php esc_html_e('App admin', 'flavor-chat-ia'); ?></option>
                            <option value="ambas" <?php selected($valor_ajuste, 'ambas'); ?>><?php esc_html_e('Ambas apps', 'flavor-chat-ia'); ?></option>
                        </select>
                    <?php elseif (is_bool($valor_ajuste) || ($valor_ajuste === 1 || $valor_ajuste === 0 || $valor_ajuste === '1' || $valor_ajuste === '0') && !is_numeric($clave_ajuste) && strpos($clave_ajuste, 'precio') === false && strpos($clave_ajuste, 'importe') === false && strpos($clave_ajuste, 'puntos') === false && strpos($clave_ajuste, 'max') === false && strpos($clave_ajuste, 'min') === false && strpos($clave_ajuste, 'horas') === false && strpos($clave_ajuste, 'dias') === false && strpos($clave_ajuste, 'kg') === false && strpos($clave_ajuste, 'comision') === false && strpos($clave_ajuste, 'radio') === false && strpos($clave_ajuste, 'tamano') === false && strpos($clave_ajuste, 'duracion') === false): ?>
                        <label>
                            <input type="checkbox"
                                   name="<?php echo esc_attr($nombre_campo); ?>"
                                   value="1"
                                   <?php checked($valor_ajuste); ?>>
                            <?php esc_html_e('Activado', 'flavor-chat-ia'); ?>
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
                        <p class="description"><?php esc_html_e('Separar con comas', 'flavor-chat-ia'); ?></p>
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
            <h2><?php esc_html_e('Firebase Push Notifications', 'flavor-chat-ia'); ?></h2>
            <p class="description"><?php esc_html_e('Configura Firebase Cloud Messaging para enviar notificaciones push a la app Flutter.', 'flavor-chat-ia'); ?></p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="flavor_firebase_project_id"><?php esc_html_e('Project ID de Firebase', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="flavor_firebase_project_id"
                               name="flavor_chat_ia_settings[flavor_firebase_project_id]"
                               value="<?php echo esc_attr($project_id); ?>"
                               class="regular-text"
                               placeholder="mi-proyecto-firebase" />
                        <p class="description"><?php esc_html_e('El ID del proyecto en Firebase Console (Settings > General).', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="flavor_firebase_service_account"><?php esc_html_e('Service Account JSON', 'flavor-chat-ia'); ?></label>
                    </th>
                    <td>
                        <textarea id="flavor_firebase_service_account"
                                  name="flavor_chat_ia_settings[flavor_firebase_service_account]"
                                  rows="10" cols="60"
                                  class="large-text code"
                                  placeholder='{"type": "service_account", "project_id": "...", ...}'><?php echo esc_textarea($service_account_json); ?></textarea>
                        <p class="description"><?php esc_html_e('Pega aqui el contenido completo del archivo JSON de la Service Account de Firebase. Obtenlo en Firebase Console > Settings > Service Accounts > Generate New Private Key.', 'flavor-chat-ia'); ?></p>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Estado de la configuracion', 'flavor-chat-ia'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Project ID', 'flavor-chat-ia'); ?></th>
                    <td>
                        <?php if (!empty($project_id)): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                            <code><?php echo esc_html($project_id); ?></code>
                        <?php else: ?>
                            <span class="dashicons dashicons-warning" style="color: #dba617;"></span>
                            <?php esc_html_e('No configurado', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Service Account', 'flavor-chat-ia'); ?></th>
                    <td>
                        <?php
                        if (!empty($service_account_json)) {
                            $sa_data = json_decode($service_account_json, true);
                            if ($sa_data && isset($sa_data['client_email'])) {
                                echo '<span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span> ';
                                echo esc_html($sa_data['client_email']);
                            } else {
                                echo '<span class="dashicons dashicons-dismiss" style="color: #d63638;"></span> ';
                                esc_html_e('JSON invalido', 'flavor-chat-ia');
                            }
                        } else {
                            echo '<span class="dashicons dashicons-warning" style="color: #dba617;"></span> ';
                            esc_html_e('No configurado', 'flavor-chat-ia');
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Extension OpenSSL', 'flavor-chat-ia'); ?></th>
                    <td>
                        <?php if (extension_loaded('openssl')): ?>
                            <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                            <?php esc_html_e('Disponible', 'flavor-chat-ia'); ?>
                        <?php else: ?>
                            <span class="dashicons dashicons-dismiss" style="color: #d63638;"></span>
                            <?php esc_html_e('No disponible - Requerida para firmar JWT', 'flavor-chat-ia'); ?>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <h3><?php esc_html_e('Prueba de envio', 'flavor-chat-ia'); ?></h3>
            <p class="description"><?php esc_html_e('Envia una notificacion de prueba a tu usuario actual (debes tener un token FCM registrado).', 'flavor-chat-ia'); ?></p>
            <button type="button" id="flavor-test-push-notification" class="button button-secondary">
                <?php esc_html_e('Enviar notificacion de prueba', 'flavor-chat-ia'); ?>
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
            <label for="analytics-period"><?php esc_html_e('Período:', 'flavor-chat-ia'); ?></label>
            <select id="analytics-period">
                <option value="day"><?php esc_html_e('Hoy', 'flavor-chat-ia'); ?></option>
                <option value="week" selected><?php esc_html_e('Últimos 7 días', 'flavor-chat-ia'); ?></option>
                <option value="month"><?php esc_html_e('Últimos 30 días', 'flavor-chat-ia'); ?></option>
            </select>
            <button type="button" id="refresh-analytics" class="button"><?php esc_html_e('Actualizar', 'flavor-chat-ia'); ?></button>
        </div>

        <div class="analytics-grid" id="analytics-container" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;">
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Conversaciones', 'flavor-chat-ia'); ?></h3>
                <div class="analytics-value" id="stat-conversations" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Mensajes Totales', 'flavor-chat-ia'); ?></h3>
                <div class="analytics-value" id="stat-messages" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Promedio msgs/conv', 'flavor-chat-ia'); ?></h3>
                <div class="analytics-value" id="stat-avg-messages" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Escaladas', 'flavor-chat-ia'); ?></h3>
                <div class="analytics-value" id="stat-escalated" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Conversiones', 'flavor-chat-ia'); ?></h3>
                <div class="analytics-value" id="stat-conversions" style="font-size:28px;font-weight:bold;">-</div>
            </div>
            <div class="analytics-card" style="background:#fff;padding:20px;border:1px solid #ccd0d4;border-radius:4px;">
                <h3 style="margin:0 0 10px;font-size:14px;color:#666;"><?php esc_html_e('Tokens Usados', 'flavor-chat-ia'); ?></h3>
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

        $escalation = Flavor_Chat_Escalation::get_instance();
        $escalations = $escalation->get_pending_escalations();
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Solicitudes de atención', 'flavor-chat-ia'); ?></h1>

            <?php if (empty($escalations)): ?>
                <p><?php esc_html_e('No hay solicitudes pendientes.', 'flavor-chat-ia'); ?></p>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('ID', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Motivo', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Estado', 'flavor-chat-ia'); ?></th>
                            <th><?php esc_html_e('Acciones', 'flavor-chat-ia'); ?></th>
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
                                    <?php esc_html_e('Resolver', 'flavor-chat-ia'); ?>
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
        check_ajax_referer('flavor_chat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
        }

        $section = sanitize_text_field($_POST['section'] ?? 'all');

        // Recopilar información del sitio
        $site_info = $this->gather_site_info();

        // Obtener el motor de IA activo
        if (!class_exists('Flavor_Engine_Manager')) {
            wp_send_json_error(['error' => __('Motor de IA no disponible', 'flavor-chat-ia')]);
        }

        $engine_manager = Flavor_Engine_Manager::get_instance();
        $engine = $engine_manager->get_active_engine();

        if (!$engine || !$engine->is_configured()) {
            wp_send_json_error(['error' => __('Configura primero un proveedor de IA en la pestaña Proveedores', 'flavor-chat-ia')]);
        }

        // Construir el prompt según la sección
        $prompt = $this->build_autoconfig_prompt($section, $site_info);

        $messages = [['role' => 'user', 'content' => $prompt]];
        $system_prompt = 'Eres un experto en configuración de chatbots para e-commerce. Genera configuraciones en formato JSON válido. Responde SOLO con el JSON, sin explicaciones adicionales.';

        $response = $engine->send_message($messages, $system_prompt, []);

        if (!$response['success']) {
            wp_send_json_error(['error' => $response['error'] ?? __('Error al generar configuración', 'flavor-chat-ia')]);
        }

        // Parsear la respuesta JSON
        $json_response = $response['response'];
        // Limpiar markdown si existe
        $json_response = preg_replace('/```json\s*/', '', $json_response);
        $json_response = preg_replace('/```\s*/', '', $json_response);

        $config = json_decode(trim($json_response), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(['error' => __('Error al parsear respuesta de IA', 'flavor-chat-ia'), 'raw' => $json_response]);
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
        check_ajax_referer('flavor_chat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Sin permisos.', 'flavor-chat-ia')]);
        }

        if (!class_exists('Flavor_Push_Notification_Channel')) {
            wp_send_json_error(['message' => __('El canal de push no esta disponible.', 'flavor-chat-ia')]);
        }

        $usuario_id_actual = get_current_user_id();
        $canal_push = new Flavor_Push_Notification_Channel();
        $resultado = $canal_push->send(
            $usuario_id_actual,
            __('Prueba Push Flavor', 'flavor-chat-ia'),
            __('Esta es una notificacion de prueba desde Flavor Platform.', 'flavor-chat-ia'),
            ['type' => 'test']
        );

        if ($resultado['enviados'] > 0) {
            wp_send_json_success(['message' => sprintf(
                __('Notificacion enviada a %d dispositivo(s).', 'flavor-chat-ia'),
                $resultado['enviados']
            )]);
        } elseif (!empty($resultado['sin_token'])) {
            wp_send_json_error(['message' => __('No tienes tokens FCM registrados. Abre la app en tu dispositivo primero.', 'flavor-chat-ia')]);
        } else {
            $error_msg = $resultado['error'] ?? __('Error desconocido al enviar push.', 'flavor-chat-ia');
            wp_send_json_error(['message' => $error_msg]);
        }
    }

    public function ajax_get_analytics() {
        check_ajax_referer('flavor_chat_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => __('Sin permisos', 'flavor-chat-ia')]);
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
}
