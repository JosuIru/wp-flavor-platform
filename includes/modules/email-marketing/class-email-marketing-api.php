<?php
/**
 * API REST para Email Marketing
 *
 * @package FlavorPlatform
 * @subpackage EmailMarketing
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Email_Marketing_API {

    /**
     * Namespace de la API
     */
    const NAMESPACE = 'flavor/v1';

    /**
     * Registrar rutas
     */
    public static function register_routes() {
        // Suscriptores
        register_rest_route(self::NAMESPACE, '/em/suscribir', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'suscribir'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/confirmar', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'confirmar'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/baja', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'darse_baja'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/preferencias', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_preferencias'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/preferencias', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'actualizar_preferencias'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        // Listas (público)
        register_rest_route(self::NAMESPACE, '/em/listas', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'get_listas'],
            'permission_callback' => [__CLASS__, 'public_permission_check'],
        ]);

        // --- Rutas protegidas (admin) ---

        // Suscriptores
        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_suscriptores'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_suscriptor'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'admin_actualizar_suscriptor'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'admin_eliminar_suscriptor'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores/importar', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_importar_suscriptores'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/suscriptores/exportar', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_exportar_suscriptores'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        // Listas (admin)
        register_rest_route(self::NAMESPACE, '/em/admin/listas', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_listas'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/listas', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_crear_lista'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/listas/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'admin_actualizar_lista'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/listas/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'admin_eliminar_lista'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        // Campañas
        register_rest_route(self::NAMESPACE, '/em/admin/campanias', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_campanias'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_crear_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'admin_actualizar_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)', [
            'methods' => 'DELETE',
            'callback' => [__CLASS__, 'admin_eliminar_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)/enviar', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_enviar_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)/test', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_enviar_test'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/campanias/(?P<id>\d+)/estadisticas', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_estadisticas_campania'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        // Automatizaciones
        register_rest_route(self::NAMESPACE, '/em/admin/automatizaciones', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_automatizaciones'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/automatizaciones', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_crear_automatizacion'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/automatizaciones/(?P<id>\d+)', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'admin_actualizar_automatizacion'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/automatizaciones/(?P<id>\d+)/estado', [
            'methods' => 'PUT',
            'callback' => [__CLASS__, 'admin_cambiar_estado_automatizacion'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        // Plantillas
        register_rest_route(self::NAMESPACE, '/em/admin/plantillas', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_plantillas'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        register_rest_route(self::NAMESPACE, '/em/admin/plantillas', [
            'methods' => 'POST',
            'callback' => [__CLASS__, 'admin_crear_plantilla'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);

        // Estadísticas
        register_rest_route(self::NAMESPACE, '/em/admin/estadisticas', [
            'methods' => 'GET',
            'callback' => [__CLASS__, 'admin_get_estadisticas'],
            'permission_callback' => [__CLASS__, 'check_admin_permission'],
        ]);
    }

    /**
     * Verificar permisos de admin
     */
    public static function check_admin_permission() {
        return current_user_can('manage_options');
    }

    // =========================================================================
    // ENDPOINTS PÚBLICOS
    // =========================================================================

    /**
     * Suscribir
     */
    public static function suscribir($request) {
        $email = sanitize_email($request->get_param('email'));
        $lista = sanitize_text_field($request->get_param('lista') ?: 'newsletter-principal');
        $nombre = sanitize_text_field($request->get_param('nombre') ?: '');

        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');

        if (!$modulo) {
            return new WP_REST_Response(['success' => false, 'error' => __('Módulo no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN)], 500);
        }

        $resultado = $modulo->suscribir($email, $lista, ['nombre' => $nombre]);

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * Confirmar suscripción
     */
    public static function confirmar($request) {
        $token = sanitize_text_field($request->get_param('token'));

        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');
        $resultado = $modulo->confirmar_suscripcion($token);

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * Darse de baja
     */
    public static function darse_baja($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $motivo = sanitize_text_field($request->get_param('motivo') ?: '');

        // Verificar token y obtener suscriptor (implementar en módulo)
        global $wpdb;

        // Por simplicidad, asumimos que el token es el hash del ID + salt
        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');

        // Buscar suscriptor por token
        $suscriptor = null;
        $suscriptores = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE estado != 'baja'"
        );

        foreach ($suscriptores as $sus) {
            $token_esperado = hash('sha256', $sus->id . AUTH_SALT . date('Y-m'));
            if (hash_equals($token_esperado, $token)) {
                $suscriptor = $sus;
                break;
            }
        }

        if (!$suscriptor) {
            return new WP_REST_Response(['success' => false, 'error' => __('Token no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $resultado = $modulo->dar_de_baja($suscriptor->id, null, $motivo);

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * Obtener preferencias
     */
    public static function get_preferencias($request) {
        $token = sanitize_text_field($request->get_param('token'));

        global $wpdb;

        // Buscar suscriptor
        $suscriptor = null;
        $suscriptores = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE estado != 'baja'"
        );

        foreach ($suscriptores as $sus) {
            $token_esperado = hash('sha256', $sus->id . AUTH_SALT . date('Y-m'));
            if (hash_equals($token_esperado, $token)) {
                $suscriptor = $sus;
                break;
            }
        }

        if (!$suscriptor) {
            return new WP_REST_Response(['success' => false, 'error' => __('Token no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        // Obtener listas del suscriptor
        $listas = $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, sl.estado as estado_suscripcion
             FROM {$wpdb->prefix}flavor_em_listas l
             INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON l.id = sl.lista_id
             WHERE sl.suscriptor_id = %d",
            $suscriptor->id
        ));

        return new WP_REST_Response([
            'success' => true,
            'suscriptor' => [
                'email' => $suscriptor->email,
                'nombre' => $suscriptor->nombre,
            ],
            'listas' => $listas,
        ]);
    }

    /**
     * Actualizar preferencias
     */
    public static function actualizar_preferencias($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $listas_activas = $request->get_param('listas') ?: [];

        global $wpdb;

        // Buscar suscriptor
        $suscriptor = null;
        $suscriptores = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_suscriptores WHERE estado != 'baja'"
        );

        foreach ($suscriptores as $sus) {
            $token_esperado = hash('sha256', $sus->id . AUTH_SALT . date('Y-m'));
            if (hash_equals($token_esperado, $token)) {
                $suscriptor = $sus;
                break;
            }
        }

        if (!$suscriptor) {
            return new WP_REST_Response(['success' => false, 'error' => __('Token no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        // Actualizar listas
        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');

        // Obtener listas actuales
        $listas_actuales = $wpdb->get_col($wpdb->prepare(
            "SELECT lista_id FROM {$wpdb->prefix}flavor_em_suscriptor_lista
             WHERE suscriptor_id = %d AND estado = 'activo'",
            $suscriptor->id
        ));

        // Dar de baja de las no seleccionadas
        foreach ($listas_actuales as $lista_id) {
            if (!in_array($lista_id, $listas_activas)) {
                $modulo->dar_de_baja($suscriptor->id, $lista_id);
            }
        }

        // Suscribir a las nuevas
        foreach ($listas_activas as $lista_id) {
            if (!in_array($lista_id, $listas_actuales)) {
                $lista = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}flavor_em_listas WHERE id = %d",
                    $lista_id
                ));

                if ($lista) {
                    $modulo->suscribir($suscriptor->email, $lista->slug);
                }
            }
        }

        return new WP_REST_Response(['success' => true, 'mensaje' => __('Preferencias actualizadas', FLAVOR_PLATFORM_TEXT_DOMAIN)]);
    }

    /**
     * Obtener listas públicas
     */
    public static function get_listas($request) {
        global $wpdb;

        $listas = $wpdb->get_results(
            "SELECT id, nombre, slug, descripcion
             FROM {$wpdb->prefix}flavor_em_listas
             WHERE activa = 1
             ORDER BY nombre ASC"
        );

        return new WP_REST_Response([
            'success' => true,
            'listas' => $listas,
        ]);
    }

    // =========================================================================
    // ENDPOINTS ADMIN
    // =========================================================================

    /**
     * Admin: Obtener suscriptores
     */
    public static function admin_get_suscriptores($request) {
        global $wpdb;

        $pagina = absint($request->get_param('pagina') ?: 1);
        $por_pagina = absint($request->get_param('por_pagina') ?: 20);
        $buscar = sanitize_text_field($request->get_param('buscar') ?: '');
        $lista = absint($request->get_param('lista') ?: 0);
        $estado = sanitize_key($request->get_param('estado') ?: '');

        $offset = ($pagina - 1) * $por_pagina;

        $where = ['1=1'];
        $params = [];

        if ($buscar) {
            $where[] = "(s.email LIKE %s OR s.nombre LIKE %s)";
            $params[] = '%' . $wpdb->esc_like($buscar) . '%';
            $params[] = '%' . $wpdb->esc_like($buscar) . '%';
        }

        if ($estado) {
            $where[] = "s.estado = %s";
            $params[] = $estado;
        }

        $join = '';
        if ($lista) {
            $join = "INNER JOIN {$wpdb->prefix}flavor_em_suscriptor_lista sl ON s.id = sl.suscriptor_id";
            $where[] = "sl.lista_id = %d AND sl.estado = 'activo'";
            $params[] = $lista;
        }

        $where_sql = implode(' AND ', $where);

        // Total
        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT s.id) FROM {$wpdb->prefix}flavor_em_suscriptores s $join WHERE $where_sql",
            ...$params
        ));

        // Suscriptores
        $params[] = $por_pagina;
        $params[] = $offset;

        $suscriptores = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT s.*
             FROM {$wpdb->prefix}flavor_em_suscriptores s
             $join
             WHERE $where_sql
             ORDER BY s.creado_en DESC
             LIMIT %d OFFSET %d",
            ...$params
        ));

        return new WP_REST_Response([
            'success' => true,
            'suscriptores' => $suscriptores,
            'total' => intval($total),
            'paginas' => ceil($total / $por_pagina),
            'pagina_actual' => $pagina,
        ]);
    }

    /**
     * Admin: Obtener suscriptor individual
     */
    public static function admin_get_suscriptor($request) {
        $id = absint($request['id']);

        $tracking = Flavor_EM_Tracking::get_instance();
        $estadisticas = $tracking->get_estadisticas_suscriptor($id);

        if (!$estadisticas) {
            return new WP_REST_Response(['success' => false, 'error' => __('Suscriptor no encontrado', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Admin: Obtener listas
     */
    public static function admin_get_listas($request) {
        global $wpdb;

        $listas = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_listas ORDER BY nombre ASC"
        );

        return new WP_REST_Response([
            'success' => true,
            'listas' => $listas,
        ]);
    }

    /**
     * Admin: Crear lista
     */
    public static function admin_crear_lista($request) {
        global $wpdb;

        $nombre = sanitize_text_field($request->get_param('nombre'));
        $slug = sanitize_title($request->get_param('slug') ?: $nombre);
        $descripcion = sanitize_textarea_field($request->get_param('descripcion') ?: '');
        $doble_optin = (bool) $request->get_param('doble_optin');

        // Verificar slug único
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}flavor_em_listas WHERE slug = %s",
            $slug
        ));

        if ($existe) {
            $slug = $slug . '-' . time();
        }

        $wpdb->insert($wpdb->prefix . 'flavor_em_listas', [
            'nombre' => $nombre,
            'slug' => $slug,
            'descripcion' => $descripcion,
            'doble_optin' => $doble_optin ? 1 : 0,
            'activa' => 1,
        ]);

        return new WP_REST_Response([
            'success' => true,
            'lista_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * Admin: Obtener campañas
     */
    public static function admin_get_campanias($request) {
        global $wpdb;

        $estado = sanitize_key($request->get_param('estado') ?: '');

        $where = $estado ? $wpdb->prepare("WHERE estado = %s", $estado) : '';

        $campanias = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias $where ORDER BY creado_en DESC"
        );

        return new WP_REST_Response([
            'success' => true,
            'campanias' => $campanias,
        ]);
    }

    /**
     * Admin: Crear campaña
     */
    public static function admin_crear_campania($request) {
        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');

        $datos = [
            'nombre' => $request->get_param('nombre'),
            'asunto' => $request->get_param('asunto'),
            'contenido_html' => $request->get_param('contenido_html'),
            'listas_ids' => $request->get_param('listas_ids'),
            'plantilla_id' => $request->get_param('plantilla_id'),
        ];

        $campania_id = $modulo->crear_campania($datos);

        return new WP_REST_Response([
            'success' => true,
            'campania_id' => $campania_id,
        ]);
    }

    /**
     * Admin: Obtener campaña
     */
    public static function admin_get_campania($request) {
        global $wpdb;

        $id = absint($request['id']);

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias WHERE id = %d",
            $id
        ));

        if (!$campania) {
            return new WP_REST_Response(['success' => false, 'error' => __('Campaña no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'campania' => $campania,
        ]);
    }

    /**
     * Admin: Actualizar campaña
     */
    public static function admin_actualizar_campania($request) {
        global $wpdb;

        $id = absint($request['id']);

        $update_data = [];

        $campos = ['nombre', 'asunto', 'preview_text', 'contenido_html', 'contenido_texto', 'remitente_nombre', 'remitente_email'];

        foreach ($campos as $campo) {
            $valor = $request->get_param($campo);
            if ($valor !== null) {
                $update_data[$campo] = $campo === 'contenido_html' ? wp_kses_post($valor) : sanitize_text_field($valor);
            }
        }

        if ($request->get_param('listas_ids') !== null) {
            $update_data['listas_ids'] = wp_json_encode($request->get_param('listas_ids'));
        }

        if (empty($update_data)) {
            return new WP_REST_Response(['success' => false, 'error' => __('No hay datos para actualizar', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $wpdb->update(
            $wpdb->prefix . 'flavor_em_campanias',
            $update_data,
            ['id' => $id]
        );

        return new WP_REST_Response(['success' => true]);
    }

    /**
     * Admin: Enviar campaña
     */
    public static function admin_enviar_campania($request) {
        $id = absint($request['id']);
        $fecha_programada = $request->get_param('fecha_programada');

        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');
        $resultado = $modulo->programar_campania($id, $fecha_programada);

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 400);
    }

    /**
     * Admin: Enviar test
     */
    public static function admin_enviar_test($request) {
        global $wpdb;

        $id = absint($request['id']);
        $email_test = sanitize_email($request->get_param('email'));

        if (!is_email($email_test)) {
            return new WP_REST_Response(['success' => false, 'error' => __('Email no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $campania = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}flavor_em_campanias WHERE id = %d",
            $id
        ));

        if (!$campania) {
            return new WP_REST_Response(['success' => false, 'error' => __('Campaña no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        $modulo = Flavor_Platform_Module_Loader::get_module('email_marketing');
        $settings = $modulo->get_settings();

        $sender = new Flavor_EM_Sender($settings);
        $resultado = $sender->enviar_test($email_test, $campania);

        return new WP_REST_Response($resultado, $resultado['success'] ? 200 : 500);
    }

    /**
     * Admin: Estadísticas de campaña
     */
    public static function admin_get_estadisticas_campania($request) {
        $id = absint($request['id']);

        $tracking = Flavor_EM_Tracking::get_instance();
        $estadisticas = $tracking->get_estadisticas_campania($id);

        if (!$estadisticas) {
            return new WP_REST_Response(['success' => false, 'error' => __('Campaña no encontrada', FLAVOR_PLATFORM_TEXT_DOMAIN)], 404);
        }

        return new WP_REST_Response([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    /**
     * Admin: Obtener automatizaciones
     */
    public static function admin_get_automatizaciones($request) {
        global $wpdb;

        $automatizaciones = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_automatizaciones ORDER BY creado_en DESC"
        );

        return new WP_REST_Response([
            'success' => true,
            'automatizaciones' => $automatizaciones,
        ]);
    }

    /**
     * Admin: Crear automatización
     */
    public static function admin_crear_automatizacion($request) {
        global $wpdb;

        $datos = [
            'nombre' => sanitize_text_field($request->get_param('nombre')),
            'descripcion' => sanitize_textarea_field($request->get_param('descripcion') ?: ''),
            'trigger_tipo' => sanitize_key($request->get_param('trigger_tipo')),
            'trigger_config' => wp_json_encode($request->get_param('trigger_config') ?: []),
            'pasos' => wp_json_encode($request->get_param('pasos') ?: []),
            'estado' => 'borrador',
            'creado_por' => get_current_user_id(),
        ];

        $wpdb->insert($wpdb->prefix . 'flavor_em_automatizaciones', $datos);

        return new WP_REST_Response([
            'success' => true,
            'automatizacion_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * Admin: Cambiar estado automatización
     */
    public static function admin_cambiar_estado_automatizacion($request) {
        global $wpdb;

        $id = absint($request['id']);
        $estado = sanitize_key($request->get_param('estado'));

        $estados_validos = ['activa', 'pausada', 'borrador'];

        if (!in_array($estado, $estados_validos)) {
            return new WP_REST_Response(['success' => false, 'error' => __('Estado no válido', FLAVOR_PLATFORM_TEXT_DOMAIN)], 400);
        }

        $wpdb->update(
            $wpdb->prefix . 'flavor_em_automatizaciones',
            ['estado' => $estado],
            ['id' => $id]
        );

        return new WP_REST_Response(['success' => true]);
    }

    /**
     * Admin: Obtener plantillas
     */
    public static function admin_get_plantillas($request) {
        global $wpdb;

        $plantillas = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}flavor_em_plantillas WHERE activa = 1 ORDER BY es_predefinida DESC, nombre ASC"
        );

        return new WP_REST_Response([
            'success' => true,
            'plantillas' => $plantillas,
        ]);
    }

    /**
     * Admin: Crear plantilla
     */
    public static function admin_crear_plantilla($request) {
        global $wpdb;

        $datos = [
            'nombre' => sanitize_text_field($request->get_param('nombre')),
            'categoria' => sanitize_key($request->get_param('categoria') ?: 'personalizada'),
            'contenido_html' => wp_kses_post($request->get_param('contenido_html')),
            'es_predefinida' => 0,
            'activa' => 1,
        ];

        $wpdb->insert($wpdb->prefix . 'flavor_em_plantillas', $datos);

        return new WP_REST_Response([
            'success' => true,
            'plantilla_id' => $wpdb->insert_id,
        ]);
    }

    /**
     * Admin: Estadísticas globales
     */
    public static function admin_get_estadisticas($request) {
        $periodo = sanitize_text_field($request->get_param('periodo') ?: '30 days');

        $tracking = Flavor_EM_Tracking::get_instance();
        $estadisticas = $tracking->get_estadisticas_globales($periodo);

        return new WP_REST_Response([
            'success' => true,
            'data' => $estadisticas,
        ]);
    }

    public static function public_permission_check($request) {
        $method = strtoupper($request->get_method());
        $tipo = in_array($method, ['POST', 'PUT', 'DELETE'], true) ? 'post' : 'get';
        return Flavor_API_Rate_Limiter::check_rate_limit($tipo);
    }
}

// Registrar rutas en rest_api_init
add_action('rest_api_init', ['Flavor_Email_Marketing_API', 'register_routes']);
