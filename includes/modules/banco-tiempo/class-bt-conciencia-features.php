<?php
/**
 * Funcionalidades de Sello de Conciencia para Banco de Tiempo
 *
 * Incluye:
 * - Sistema de Reputación: badges, valoraciones detalladas, verificación
 * - Integración Solidaria: donación de horas, fondo comunitario
 * - Dashboard de Sostenibilidad: métricas de equidad, ciclos, alertas
 *
 * @package FlavorChatIA
 * @subpackage BancoTiempo
 * @since 4.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para las funcionalidades de Sello de Conciencia en Banco de Tiempo
 */
class Flavor_BT_Conciencia_Features {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Badges disponibles
     */
    private $badges_disponibles = [
        'primeros_pasos'     => ['nombre' => 'Primeros Pasos', 'icono' => 'star-filled', 'descripcion' => 'Completó su primer intercambio', 'requisito' => 1],
        'colaborador'        => ['nombre' => 'Colaborador', 'icono' => 'groups', 'descripcion' => '5 intercambios completados', 'requisito' => 5],
        'pilar_comunidad'    => ['nombre' => 'Pilar de la Comunidad', 'icono' => 'building', 'descripcion' => '20 intercambios completados', 'requisito' => 20],
        'maestro_tiempo'     => ['nombre' => 'Maestro del Tiempo', 'icono' => 'clock', 'descripcion' => '50 intercambios completados', 'requisito' => 50],
        'alma_solidaria'     => ['nombre' => 'Alma Solidaria', 'icono' => 'heart', 'descripcion' => 'Donó horas al fondo comunitario', 'requisito' => 'donacion'],
        'mentor'             => ['nombre' => 'Mentor', 'icono' => 'welcome-learn-more', 'descripcion' => 'Ayudó a nuevos miembros', 'requisito' => 'mentor'],
        'cinco_estrellas'    => ['nombre' => 'Cinco Estrellas', 'icono' => 'star-filled', 'descripcion' => 'Rating promedio de 5.0', 'requisito' => 'rating_5'],
        'puntualidad'        => ['nombre' => 'Siempre Puntual', 'icono' => 'calendar-alt', 'descripcion' => 'Rating de puntualidad perfecto', 'requisito' => 'puntualidad_5'],
        'equilibrado'        => ['nombre' => 'Equilibrado', 'icono' => 'image-flip-horizontal', 'descripcion' => 'Balance equitativo dar/recibir', 'requisito' => 'equilibrio'],
    ];

    /**
     * Obtener instancia singleton
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_banco_tiempo_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks(): void {
        // AJAX handlers
        add_action('wp_ajax_bt_valorar_intercambio', [$this, 'ajax_valorar_intercambio']);
        add_action('wp_ajax_bt_donar_horas', [$this, 'ajax_donar_horas']);
        add_action('wp_ajax_bt_solicitar_fondo', [$this, 'ajax_solicitar_fondo']);
        add_action('wp_ajax_bt_obtener_reputacion', [$this, 'ajax_obtener_reputacion']);
        add_action('wp_ajax_bt_obtener_metricas', [$this, 'ajax_obtener_metricas']);
        add_action('wp_ajax_nopriv_bt_obtener_metricas', [$this, 'ajax_obtener_metricas']);

        // Hooks para actualizar reputación automáticamente
        add_action('bt_intercambio_completado', [$this, 'actualizar_reputacion_post_intercambio'], 10, 2);

        // Cron para calcular métricas
        add_action('bt_calcular_metricas_diarias', [$this, 'calcular_metricas_periodo']);

        // Shortcodes
        add_shortcode('bt_mi_reputacion', [$this, 'shortcode_mi_reputacion']);
        add_shortcode('bt_ranking_comunidad', [$this, 'shortcode_ranking_comunidad']);
        add_shortcode('bt_fondo_solidario', [$this, 'shortcode_fondo_solidario']);
        add_shortcode('bt_dashboard_sostenibilidad', [$this, 'shortcode_dashboard_sostenibilidad']);
        add_shortcode('bt_donar_horas', [$this, 'shortcode_donar_horas']);
    }

    // =========================================================================
    // SISTEMA DE REPUTACIÓN
    // =========================================================================

    /**
     * Obtener o crear perfil de reputación
     */
    public function obtener_reputacion(int $usuario_id): array {
        global $wpdb;
        $tabla = $this->prefix . 'reputacion';

        $reputacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        if (!$reputacion) {
            // Crear perfil inicial
            $wpdb->insert($tabla, [
                'usuario_id' => $usuario_id,
                'badges'     => wp_json_encode([]),
            ]);

            $reputacion = [
                'usuario_id'                   => $usuario_id,
                'total_intercambios_completados' => 0,
                'total_horas_dadas'            => 0,
                'total_horas_recibidas'        => 0,
                'rating_promedio'              => 0,
                'rating_puntualidad'           => 0,
                'rating_calidad'               => 0,
                'rating_comunicacion'          => 0,
                'estado_verificacion'          => 'pendiente',
                'badges'                       => [],
                'nivel'                        => 1,
                'puntos_confianza'             => 0,
            ];
        } else {
            $reputacion['badges'] = json_decode($reputacion['badges'] ?: '[]', true);
        }

        // Añadir info de usuario
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $reputacion['nombre'] = $usuario->display_name;
            $reputacion['avatar'] = get_avatar_url($usuario_id, ['size' => 96]);
        }

        return $reputacion;
    }

    /**
     * Registrar valoración detallada
     */
    public function registrar_valoracion(int $transaccion_id, int $valorador_id, array $datos): int|WP_Error {
        global $wpdb;
        $tabla_valoraciones = $this->prefix . 'valoraciones';
        $tabla_transacciones = $this->prefix . 'transacciones';

        // Verificar transacción existe
        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_transacciones WHERE id = %d",
            $transaccion_id
        ), ARRAY_A);

        if (!$transaccion) {
            return new WP_Error('transaccion_no_encontrada', __('Transacción no encontrada', 'flavor-chat-ia'));
        }

        // Determinar rol y valorado
        $es_solicitante = ($transaccion['usuario_solicitante_id'] == $valorador_id);
        $valorado_id = $es_solicitante ? $transaccion['usuario_receptor_id'] : $transaccion['usuario_solicitante_id'];
        $rol = $es_solicitante ? 'solicitante' : 'receptor';

        // Verificar que no haya valorado ya
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_valoraciones WHERE transaccion_id = %d AND valorador_id = %d",
            $transaccion_id,
            $valorador_id
        ));

        if ($existe) {
            return new WP_Error('ya_valorado', __('Ya has valorado este intercambio', 'flavor-chat-ia'));
        }

        $resultado = $wpdb->insert(
            $tabla_valoraciones,
            [
                'transaccion_id'     => $transaccion_id,
                'valorador_id'       => $valorador_id,
                'valorado_id'        => $valorado_id,
                'rol_valorador'      => $rol,
                'rating_general'     => intval($datos['rating_general']),
                'rating_puntualidad' => isset($datos['rating_puntualidad']) ? intval($datos['rating_puntualidad']) : null,
                'rating_calidad'     => isset($datos['rating_calidad']) ? intval($datos['rating_calidad']) : null,
                'rating_comunicacion' => isset($datos['rating_comunicacion']) ? intval($datos['rating_comunicacion']) : null,
                'comentario'         => sanitize_textarea_field($datos['comentario'] ?? ''),
                'es_publica'         => isset($datos['es_publica']) ? 1 : 0,
                'fecha_valoracion'   => current_time('mysql'),
            ],
            ['%d', '%d', '%d', '%s', '%d', '%d', '%d', '%d', '%s', '%d', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al guardar la valoración', 'flavor-chat-ia'));
        }

        // Actualizar reputación del valorado
        $this->recalcular_reputacion($valorado_id);

        return $wpdb->insert_id;
    }

    /**
     * Recalcular reputación de un usuario
     */
    public function recalcular_reputacion(int $usuario_id): void {
        global $wpdb;
        $tabla_reputacion = $this->prefix . 'reputacion';
        $tabla_valoraciones = $this->prefix . 'valoraciones';
        $tabla_transacciones = $this->prefix . 'transacciones';

        // Obtener estadísticas de valoraciones
        $stats_valoraciones = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_valoraciones,
                AVG(rating_general) as rating_promedio,
                AVG(rating_puntualidad) as rating_puntualidad,
                AVG(rating_calidad) as rating_calidad,
                AVG(rating_comunicacion) as rating_comunicacion
             FROM $tabla_valoraciones
             WHERE valorado_id = %d",
            $usuario_id
        ), ARRAY_A);

        // Obtener estadísticas de intercambios
        $stats_intercambios = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total_completados,
                SUM(CASE WHEN usuario_solicitante_id = %d THEN horas ELSE 0 END) as horas_recibidas,
                SUM(CASE WHEN usuario_receptor_id = %d THEN horas ELSE 0 END) as horas_dadas,
                MIN(fecha_completado) as primer_intercambio,
                MAX(fecha_completado) as ultimo_intercambio
             FROM $tabla_transacciones
             WHERE estado = 'completado'
             AND (usuario_solicitante_id = %d OR usuario_receptor_id = %d)",
            $usuario_id,
            $usuario_id,
            $usuario_id,
            $usuario_id
        ), ARRAY_A);

        // Calcular nivel
        $total_intercambios = intval($stats_intercambios['total_completados'] ?? 0);
        $nivel = $this->calcular_nivel($total_intercambios);

        // Calcular puntos de confianza
        $puntos = $this->calcular_puntos_confianza($stats_valoraciones, $stats_intercambios);

        // Determinar badges
        $badges_actuales = $this->calcular_badges($usuario_id, $stats_intercambios, $stats_valoraciones);

        // Actualizar o insertar reputación
        $datos_reputacion = [
            'total_intercambios_completados' => $total_intercambios,
            'total_horas_dadas'              => floatval($stats_intercambios['horas_dadas'] ?? 0),
            'total_horas_recibidas'          => floatval($stats_intercambios['horas_recibidas'] ?? 0),
            'rating_promedio'                => round(floatval($stats_valoraciones['rating_promedio'] ?? 0), 2),
            'rating_puntualidad'             => round(floatval($stats_valoraciones['rating_puntualidad'] ?? 0), 2),
            'rating_calidad'                 => round(floatval($stats_valoraciones['rating_calidad'] ?? 0), 2),
            'rating_comunicacion'            => round(floatval($stats_valoraciones['rating_comunicacion'] ?? 0), 2),
            'fecha_primer_intercambio'       => $stats_intercambios['primer_intercambio'],
            'fecha_ultimo_intercambio'       => $stats_intercambios['ultimo_intercambio'],
            'badges'                         => wp_json_encode($badges_actuales),
            'nivel'                          => $nivel,
            'puntos_confianza'               => $puntos,
        ];

        // Auto-verificar si cumple requisitos
        if ($total_intercambios >= 10 && floatval($stats_valoraciones['rating_promedio'] ?? 0) >= 4.0) {
            $datos_reputacion['estado_verificacion'] = 'verificado';
            $datos_reputacion['fecha_verificacion'] = current_time('mysql');
        }

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if ($existe) {
            $wpdb->update($tabla_reputacion, $datos_reputacion, ['usuario_id' => $usuario_id]);
        } else {
            $datos_reputacion['usuario_id'] = $usuario_id;
            $wpdb->insert($tabla_reputacion, $datos_reputacion);
        }

        // Actualizar límites/saldo
        $this->actualizar_saldo_usuario($usuario_id);
    }

    /**
     * Calcular nivel según intercambios
     */
    private function calcular_nivel(int $total_intercambios): int {
        if ($total_intercambios >= 100) return 10;
        if ($total_intercambios >= 75) return 9;
        if ($total_intercambios >= 50) return 8;
        if ($total_intercambios >= 35) return 7;
        if ($total_intercambios >= 25) return 6;
        if ($total_intercambios >= 15) return 5;
        if ($total_intercambios >= 10) return 4;
        if ($total_intercambios >= 5) return 3;
        if ($total_intercambios >= 2) return 2;
        return 1;
    }

    /**
     * Calcular puntos de confianza
     */
    private function calcular_puntos_confianza(array $valoraciones, array $intercambios): int {
        $puntos = 0;

        // Por intercambios completados
        $puntos += intval($intercambios['total_completados'] ?? 0) * 10;

        // Por rating promedio
        $rating = floatval($valoraciones['rating_promedio'] ?? 0);
        $puntos += intval($rating * 20);

        // Por equilibrio dar/recibir
        $dadas = floatval($intercambios['horas_dadas'] ?? 0);
        $recibidas = floatval($intercambios['horas_recibidas'] ?? 0);
        if ($dadas > 0 && $recibidas > 0) {
            $ratio = min($dadas, $recibidas) / max($dadas, $recibidas);
            $puntos += intval($ratio * 50); // Máximo 50 puntos por equilibrio
        }

        return $puntos;
    }

    /**
     * Calcular badges obtenidos
     */
    private function calcular_badges(int $usuario_id, array $intercambios, array $valoraciones): array {
        global $wpdb;
        $badges = [];

        $total = intval($intercambios['total_completados'] ?? 0);
        $rating = floatval($valoraciones['rating_promedio'] ?? 0);
        $puntualidad = floatval($valoraciones['rating_puntualidad'] ?? 0);

        // Badges por intercambios
        if ($total >= 1) $badges[] = 'primeros_pasos';
        if ($total >= 5) $badges[] = 'colaborador';
        if ($total >= 20) $badges[] = 'pilar_comunidad';
        if ($total >= 50) $badges[] = 'maestro_tiempo';

        // Badges por rating
        if ($total >= 5 && $rating >= 4.9) $badges[] = 'cinco_estrellas';
        if ($total >= 5 && $puntualidad >= 4.9) $badges[] = 'puntualidad';

        // Badge por equilibrio
        $dadas = floatval($intercambios['horas_dadas'] ?? 0);
        $recibidas = floatval($intercambios['horas_recibidas'] ?? 0);
        if ($dadas >= 10 && $recibidas >= 10) {
            $ratio = min($dadas, $recibidas) / max($dadas, $recibidas);
            if ($ratio >= 0.7) {
                $badges[] = 'equilibrado';
            }
        }

        // Badge por donaciones
        $tabla_donaciones = $this->prefix . 'donaciones';
        $ha_donado = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_donaciones WHERE donante_id = %d AND estado IN ('aceptada', 'utilizada')",
            $usuario_id
        ));
        if ($ha_donado > 0) {
            $badges[] = 'alma_solidaria';
        }

        return array_unique($badges);
    }

    /**
     * AJAX: Valorar intercambio
     */
    public function ajax_valorar_intercambio(): void {
        check_ajax_referer('bt_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $transaccion_id = intval($_POST['transaccion_id'] ?? 0);
        $rating_general = intval($_POST['rating_general'] ?? 0);

        if (!$transaccion_id || $rating_general < 1 || $rating_general > 5) {
            wp_send_json_error(['message' => __('Datos inválidos', 'flavor-chat-ia')]);
        }

        $resultado = $this->registrar_valoracion($transaccion_id, get_current_user_id(), $_POST);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Valoración registrada. ¡Gracias por tu feedback!', 'flavor-chat-ia'),
            'id'      => $resultado,
        ]);
    }

    /**
     * AJAX: Obtener reputación
     */
    public function ajax_obtener_reputacion(): void {
        check_ajax_referer('bt_conciencia_nonce', 'nonce');

        $usuario_id = intval($_POST['usuario_id'] ?? get_current_user_id());
        $reputacion = $this->obtener_reputacion($usuario_id);

        // Añadir info de badges
        $badges_info = [];
        foreach ($reputacion['badges'] as $badge_id) {
            if (isset($this->badges_disponibles[$badge_id])) {
                $badges_info[] = array_merge(
                    ['id' => $badge_id],
                    $this->badges_disponibles[$badge_id]
                );
            }
        }
        $reputacion['badges_info'] = $badges_info;

        wp_send_json_success(['reputacion' => $reputacion]);
    }

    // =========================================================================
    // INTEGRACIÓN SOLIDARIA
    // =========================================================================

    /**
     * Donar horas al fondo comunitario o a un usuario
     */
    public function donar_horas(int $donante_id, float $horas, array $datos = []): int|WP_Error {
        global $wpdb;
        $tabla_donaciones = $this->prefix . 'donaciones';
        $tabla_limites = $this->prefix . 'limites';

        // Verificar que tiene saldo suficiente
        $saldo = $this->obtener_saldo($donante_id);
        if ($saldo < $horas) {
            return new WP_Error('saldo_insuficiente', sprintf(
                __('No tienes suficientes horas. Tu saldo actual es %.1f horas.', 'flavor-chat-ia'),
                $saldo
            ));
        }

        $tipo = sanitize_text_field($datos['tipo'] ?? 'fondo_comunitario');
        $beneficiario_id = isset($datos['beneficiario_id']) ? intval($datos['beneficiario_id']) : null;

        // Si es regalo directo, verificar beneficiario
        if ($tipo === 'regalo_directo' && !$beneficiario_id) {
            return new WP_Error('beneficiario_requerido', __('Debes especificar un beneficiario', 'flavor-chat-ia'));
        }

        $resultado = $wpdb->insert(
            $tabla_donaciones,
            [
                'donante_id'      => $donante_id,
                'beneficiario_id' => $beneficiario_id,
                'tipo'            => $tipo,
                'horas'           => $horas,
                'motivo'          => sanitize_text_field($datos['motivo'] ?? ''),
                'mensaje'         => sanitize_textarea_field($datos['mensaje'] ?? ''),
                'estado'          => $tipo === 'fondo_comunitario' ? 'aceptada' : 'pendiente',
                'fecha_donacion'  => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar la donación', 'flavor-chat-ia'));
        }

        // Actualizar saldo del donante
        $this->actualizar_saldo_usuario($donante_id);

        // Si es regalo directo aceptado automáticamente, actualizar beneficiario
        if ($tipo === 'regalo_directo' && $beneficiario_id) {
            // Marcar como aceptada si el beneficiario no tiene deuda
            $saldo_beneficiario = $this->obtener_saldo($beneficiario_id);
            if ($saldo_beneficiario < 0) {
                $wpdb->update($tabla_donaciones, ['estado' => 'aceptada'], ['id' => $wpdb->insert_id]);
                $this->actualizar_saldo_usuario($beneficiario_id);
            }
        }

        // Otorgar badge de alma solidaria si es primera donación
        $this->recalcular_reputacion($donante_id);

        return $wpdb->insert_id;
    }

    /**
     * Solicitar horas del fondo comunitario
     */
    public function solicitar_fondo(int $solicitante_id, float $horas, string $motivo): int|WP_Error {
        global $wpdb;
        $tabla_donaciones = $this->prefix . 'donaciones';

        // Verificar fondo disponible
        $fondo_disponible = $this->obtener_fondo_comunitario();
        if ($fondo_disponible < $horas) {
            return new WP_Error('fondo_insuficiente', sprintf(
                __('El fondo comunitario tiene %.1f horas disponibles.', 'flavor-chat-ia'),
                $fondo_disponible
            ));
        }

        // Crear solicitud de emergencia
        $resultado = $wpdb->insert(
            $tabla_donaciones,
            [
                'donante_id'      => 0, // Fondo comunitario
                'beneficiario_id' => $solicitante_id,
                'tipo'            => 'emergencia',
                'horas'           => $horas,
                'motivo'          => sanitize_textarea_field($motivo),
                'estado'          => 'pendiente', // Requiere aprobación
                'fecha_donacion'  => current_time('mysql'),
            ],
            ['%d', '%d', '%s', '%f', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', __('Error al registrar la solicitud', 'flavor-chat-ia'));
        }

        return $wpdb->insert_id;
    }

    /**
     * Obtener fondo comunitario disponible
     */
    public function obtener_fondo_comunitario(): float {
        global $wpdb;
        $tabla = $this->prefix . 'donaciones';

        // Total donado al fondo
        $total_donado = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla
             WHERE tipo = 'fondo_comunitario' AND estado = 'aceptada'"
        );

        // Total utilizado del fondo
        $total_utilizado = (float) $wpdb->get_var(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla
             WHERE tipo = 'emergencia' AND estado = 'utilizada'"
        );

        return max(0, $total_donado - $total_utilizado);
    }

    /**
     * Obtener saldo de un usuario
     */
    public function obtener_saldo(int $usuario_id): float {
        global $wpdb;
        $tabla_transacciones = $this->prefix . 'transacciones';
        $tabla_donaciones = $this->prefix . 'donaciones';

        // Horas ganadas (como receptor)
        $horas_ganadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
             WHERE usuario_receptor_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        // Horas gastadas (como solicitante)
        $horas_gastadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_transacciones
             WHERE usuario_solicitante_id = %d AND estado = 'completado'",
            $usuario_id
        ));

        // Horas donadas
        $horas_donadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_donaciones
             WHERE donante_id = %d AND estado IN ('aceptada', 'utilizada')",
            $usuario_id
        ));

        // Horas recibidas por donación
        $horas_recibidas_donacion = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_donaciones
             WHERE beneficiario_id = %d AND estado IN ('aceptada', 'utilizada')",
            $usuario_id
        ));

        return $horas_ganadas - $horas_gastadas - $horas_donadas + $horas_recibidas_donacion;
    }

    /**
     * Actualizar saldo en tabla de límites
     */
    private function actualizar_saldo_usuario(int $usuario_id): void {
        global $wpdb;
        $tabla = $this->prefix . 'limites';

        $saldo = $this->obtener_saldo($usuario_id);

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla WHERE usuario_id = %d",
            $usuario_id
        ));

        $datos = [
            'saldo_actual'        => $saldo,
            'fecha_actualizacion' => current_time('mysql'),
        ];

        // Determinar si hay alerta
        if ($saldo < -20) {
            $datos['alerta_activa'] = 1;
            $datos['tipo_alerta'] = 'deuda_alta';
            $datos['fecha_ultima_alerta'] = current_time('mysql');
        } elseif ($saldo > 50) {
            $datos['alerta_activa'] = 1;
            $datos['tipo_alerta'] = 'acumulacion_alta';
            $datos['fecha_ultima_alerta'] = current_time('mysql');
        } else {
            $datos['alerta_activa'] = 0;
            $datos['tipo_alerta'] = null;
        }

        if ($existe) {
            $wpdb->update($tabla, $datos, ['usuario_id' => $usuario_id]);
        } else {
            $datos['usuario_id'] = $usuario_id;
            $wpdb->insert($tabla, $datos);
        }
    }

    /**
     * AJAX: Donar horas
     */
    public function ajax_donar_horas(): void {
        check_ajax_referer('bt_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $horas = floatval($_POST['horas'] ?? 0);
        if ($horas < 0.5) {
            wp_send_json_error(['message' => __('Mínimo 30 minutos para donar', 'flavor-chat-ia')]);
        }

        $resultado = $this->donar_horas(get_current_user_id(), $horas, $_POST);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('¡Gracias por tu generosidad! Tu donación ayudará a la comunidad.', 'flavor-chat-ia'),
            'id'      => $resultado,
        ]);
    }

    /**
     * AJAX: Solicitar fondo
     */
    public function ajax_solicitar_fondo(): void {
        check_ajax_referer('bt_conciencia_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('Debes iniciar sesión', 'flavor-chat-ia')]);
        }

        $horas = floatval($_POST['horas'] ?? 0);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        if ($horas < 0.5 || empty($motivo)) {
            wp_send_json_error(['message' => __('Especifica las horas y el motivo', 'flavor-chat-ia')]);
        }

        $resultado = $this->solicitar_fondo(get_current_user_id(), $horas, $motivo);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Solicitud enviada. Un coordinador la revisará pronto.', 'flavor-chat-ia'),
            'id'      => $resultado,
        ]);
    }

    // =========================================================================
    // DASHBOARD DE SOSTENIBILIDAD
    // =========================================================================

    /**
     * Calcular métricas de un período
     */
    public function calcular_metricas_periodo(string $tipo = 'mensual', ?string $fecha_inicio = null): array {
        global $wpdb;

        $tabla_transacciones = $this->prefix . 'transacciones';
        $tabla_servicios = $this->prefix . 'servicios';
        $tabla_limites = $this->prefix . 'limites';
        $tabla_metricas = $this->prefix . 'metricas';

        // Determinar período
        if (!$fecha_inicio) {
            switch ($tipo) {
                case 'diario':
                    $fecha_inicio = date('Y-m-d');
                    $fecha_fin = date('Y-m-d');
                    break;
                case 'semanal':
                    $fecha_inicio = date('Y-m-d', strtotime('monday this week'));
                    $fecha_fin = date('Y-m-d', strtotime('sunday this week'));
                    break;
                case 'trimestral':
                    $trimestre = ceil(date('n') / 3);
                    $fecha_inicio = date('Y-' . str_pad(($trimestre - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . '-01');
                    $fecha_fin = date('Y-m-t', strtotime($fecha_inicio . ' +2 months'));
                    break;
                case 'anual':
                    $fecha_inicio = date('Y-01-01');
                    $fecha_fin = date('Y-12-31');
                    break;
                default: // mensual
                    $fecha_inicio = date('Y-m-01');
                    $fecha_fin = date('Y-m-t');
            }
        } else {
            $fecha_fin = date('Y-m-t', strtotime($fecha_inicio));
        }

        // Usuarios activos en el período
        $usuarios_activos = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT CASE WHEN usuario_solicitante_id > 0 THEN usuario_solicitante_id END) +
                    COUNT(DISTINCT CASE WHEN usuario_receptor_id > 0 THEN usuario_receptor_id END)
             FROM $tabla_transacciones
             WHERE fecha_solicitud BETWEEN %s AND %s",
            $fecha_inicio,
            $fecha_fin . ' 23:59:59'
        ));

        // Nuevos usuarios (primer intercambio en el período)
        $nuevos_usuarios = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT usuario_id)
             FROM (
                 SELECT usuario_solicitante_id as usuario_id, MIN(fecha_solicitud) as primera
                 FROM $tabla_transacciones GROUP BY usuario_solicitante_id
                 UNION
                 SELECT usuario_receptor_id as usuario_id, MIN(fecha_solicitud) as primera
                 FROM $tabla_transacciones GROUP BY usuario_receptor_id
             ) as primeras
             WHERE primera BETWEEN %s AND %s",
            $fecha_inicio,
            $fecha_fin . ' 23:59:59'
        ));

        // Total intercambios y horas
        $stats_intercambios = $wpdb->get_row($wpdb->prepare(
            "SELECT COUNT(*) as total, COALESCE(SUM(horas), 0) as horas
             FROM $tabla_transacciones
             WHERE estado = 'completado'
             AND fecha_completado BETWEEN %s AND %s",
            $fecha_inicio,
            $fecha_fin . ' 23:59:59'
        ), ARRAY_A);

        // Ratio oferta/demanda por categoría
        $ratio_categorias = $wpdb->get_results($wpdb->prepare(
            "SELECT
                s.categoria,
                COUNT(DISTINCT s.id) as servicios_ofrecidos,
                COUNT(DISTINCT t.id) as intercambios_solicitados
             FROM $tabla_servicios s
             LEFT JOIN $tabla_transacciones t ON t.servicio_id = s.id
                AND t.fecha_solicitud BETWEEN %s AND %s
             WHERE s.estado = 'activo'
             GROUP BY s.categoria",
            $fecha_inicio,
            $fecha_fin . ' 23:59:59'
        ), ARRAY_A);

        $categoria_mas_demandada = null;
        $categoria_menos_demandada = null;
        $max_ratio = 0;
        $min_ratio = PHP_INT_MAX;

        foreach ($ratio_categorias as $cat) {
            $ratio = $cat['servicios_ofrecidos'] > 0
                ? $cat['intercambios_solicitados'] / $cat['servicios_ofrecidos']
                : 0;
            if ($ratio > $max_ratio) {
                $max_ratio = $ratio;
                $categoria_mas_demandada = $cat['categoria'];
            }
            if ($ratio < $min_ratio && $cat['servicios_ofrecidos'] > 0) {
                $min_ratio = $ratio;
                $categoria_menos_demandada = $cat['categoria'];
            }
        }

        // Usuarios con alertas
        $usuarios_deuda_alta = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_limites WHERE saldo_actual < -20"
        );
        $usuarios_excedente_alto = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $tabla_limites WHERE saldo_actual > 50"
        );

        // Calcular índice de equidad (coeficiente de Gini simplificado)
        $saldos = $wpdb->get_col("SELECT saldo_actual FROM $tabla_limites WHERE saldo_actual != 0");
        $indice_equidad = $this->calcular_indice_equidad($saldos);

        // Fondo comunitario
        $fondo = $this->obtener_fondo_comunitario();

        // Horas donadas en período
        $tabla_donaciones = $this->prefix . 'donaciones';
        $horas_donadas = (float) $wpdb->get_var($wpdb->prepare(
            "SELECT COALESCE(SUM(horas), 0) FROM $tabla_donaciones
             WHERE fecha_donacion BETWEEN %s AND %s
             AND estado IN ('aceptada', 'utilizada')",
            $fecha_inicio,
            $fecha_fin . ' 23:59:59'
        ));

        // Calcular puntuación de sostenibilidad
        $puntuacion = $this->calcular_puntuacion_sostenibilidad([
            'usuarios_activos'      => $usuarios_activos,
            'indice_equidad'        => $indice_equidad,
            'usuarios_deuda_alta'   => $usuarios_deuda_alta,
            'usuarios_excedente_alto' => $usuarios_excedente_alto,
            'horas_donadas'         => $horas_donadas,
            'total_intercambios'    => intval($stats_intercambios['total'] ?? 0),
        ]);

        // Generar alertas
        $alertas = [];
        if ($usuarios_deuda_alta > 0) {
            $alertas[] = sprintf(__('%d usuarios con deuda alta (>20h)', 'flavor-chat-ia'), $usuarios_deuda_alta);
        }
        if ($max_ratio > 3) {
            $alertas[] = sprintf(__('"%s" muy demandada (ratio %.1f:1)', 'flavor-chat-ia'), $categoria_mas_demandada, $max_ratio);
        }
        if ($min_ratio < 0.2 && $categoria_menos_demandada) {
            $alertas[] = sprintf(__('Poca demanda de "%s"', 'flavor-chat-ia'), $categoria_menos_demandada);
        }

        $metricas = [
            'periodo_inicio'             => $fecha_inicio,
            'periodo_fin'                => $fecha_fin,
            'tipo_periodo'               => $tipo,
            'total_usuarios_activos'     => $usuarios_activos,
            'nuevos_usuarios'            => $nuevos_usuarios,
            'total_intercambios'         => intval($stats_intercambios['total'] ?? 0),
            'total_horas_intercambiadas' => floatval($stats_intercambios['horas'] ?? 0),
            'horas_donadas_periodo'      => $horas_donadas,
            'fondo_comunitario_actual'   => $fondo,
            'indice_equidad'             => round($indice_equidad, 4),
            'categoria_mas_demandada'    => $categoria_mas_demandada,
            'categoria_menos_demandada'  => $categoria_menos_demandada,
            'ratio_oferta_demanda'       => wp_json_encode($ratio_categorias),
            'alertas_generadas'          => wp_json_encode($alertas),
            'usuarios_con_deuda_alta'    => $usuarios_deuda_alta,
            'usuarios_con_excedente_alto' => $usuarios_excedente_alto,
            'puntuacion_sostenibilidad'  => $puntuacion,
            'fecha_calculo'              => current_time('mysql'),
        ];

        // Guardar métricas
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_metricas WHERE periodo_inicio = %s AND tipo_periodo = %s",
            $fecha_inicio,
            $tipo
        ));

        if ($existe) {
            $wpdb->update($tabla_metricas, $metricas, ['id' => $existe]);
        } else {
            $wpdb->insert($tabla_metricas, $metricas);
        }

        return $metricas;
    }

    /**
     * Calcular índice de equidad (0-1, donde 1 es perfecta equidad)
     */
    private function calcular_indice_equidad(array $valores): float {
        if (empty($valores)) {
            return 1.0;
        }

        $n = count($valores);
        if ($n <= 1) {
            return 1.0;
        }

        sort($valores);
        $suma_acumulada = 0;
        $suma_total = array_sum($valores);

        if ($suma_total == 0) {
            return 1.0;
        }

        $area_bajo_curva = 0;
        foreach ($valores as $i => $valor) {
            $suma_acumulada += $valor;
            $area_bajo_curva += $suma_acumulada;
        }

        $area_bajo_curva /= ($n * $suma_total);
        $gini = 1 - 2 * ($area_bajo_curva - 0.5);

        return max(0, min(1, 1 - $gini));
    }

    /**
     * Calcular puntuación de sostenibilidad
     */
    private function calcular_puntuacion_sostenibilidad(array $datos): int {
        $puntos = 0;

        // Por usuarios activos (máx 25)
        $puntos += min(25, $datos['usuarios_activos'] * 2);

        // Por equidad (máx 25)
        $puntos += intval($datos['indice_equidad'] * 25);

        // Por donaciones (máx 20)
        $puntos += min(20, $datos['horas_donadas'] * 2);

        // Por intercambios (máx 20)
        $puntos += min(20, $datos['total_intercambios']);

        // Penalizaciones
        $puntos -= $datos['usuarios_deuda_alta'] * 2;
        $puntos -= $datos['usuarios_excedente_alto'];

        return max(0, min(100, $puntos));
    }

    /**
     * AJAX: Obtener métricas
     */
    public function ajax_obtener_metricas(): void {
        check_ajax_referer('bt_conciencia_nonce', 'nonce');

        $tipo = sanitize_text_field($_POST['tipo'] ?? 'mensual');
        $metricas = $this->calcular_metricas_periodo($tipo);

        // Decodificar JSON para el frontend
        $metricas['ratio_oferta_demanda'] = json_decode($metricas['ratio_oferta_demanda'], true);
        $metricas['alertas_generadas'] = json_decode($metricas['alertas_generadas'], true);

        wp_send_json_success(['metricas' => $metricas]);
    }

    // =========================================================================
    // SHORTCODES
    // =========================================================================

    /**
     * Shortcode: Mi reputación
     */
    public function shortcode_mi_reputacion(array $atts): string {
        if (!is_user_logged_in()) {
            return '<p class="bt-aviso">' . __('Inicia sesión para ver tu reputación', 'flavor-chat-ia') . '</p>';
        }

        $reputacion = $this->obtener_reputacion(get_current_user_id());
        $badges_info = [];
        foreach ($reputacion['badges'] as $badge_id) {
            if (isset($this->badges_disponibles[$badge_id])) {
                $badges_info[] = array_merge(['id' => $badge_id], $this->badges_disponibles[$badge_id]);
            }
        }

        ob_start();
        include dirname(__FILE__) . '/templates/mi-reputacion.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Ranking comunidad
     */
    public function shortcode_ranking_comunidad(array $atts): string {
        $atts = shortcode_atts([
            'limite' => 10,
            'tipo'   => 'puntos', // puntos, intercambios, rating
        ], $atts);

        global $wpdb;
        $tabla = $this->prefix . 'reputacion';

        $orden = match ($atts['tipo']) {
            'intercambios' => 'total_intercambios_completados DESC',
            'rating'       => 'rating_promedio DESC, total_intercambios_completados DESC',
            default        => 'puntos_confianza DESC',
        };

        $ranking = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, u.display_name, u.user_email
             FROM $tabla r
             JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.total_intercambios_completados > 0
             ORDER BY $orden
             LIMIT %d",
            intval($atts['limite'])
        ), ARRAY_A);

        foreach ($ranking as &$usuario) {
            $usuario['avatar'] = get_avatar_url($usuario['usuario_id'], ['size' => 48]);
            $usuario['badges'] = json_decode($usuario['badges'] ?: '[]', true);
        }

        ob_start();
        include dirname(__FILE__) . '/templates/ranking-comunidad.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Fondo solidario
     */
    public function shortcode_fondo_solidario(array $atts): string {
        $fondo = $this->obtener_fondo_comunitario();

        global $wpdb;
        $tabla = $this->prefix . 'donaciones';

        $ultimas_donaciones = $wpdb->get_results(
            "SELECT d.*, u.display_name
             FROM $tabla d
             JOIN {$wpdb->users} u ON d.donante_id = u.ID
             WHERE d.tipo = 'fondo_comunitario' AND d.estado = 'aceptada'
             ORDER BY d.fecha_donacion DESC
             LIMIT 5",
            ARRAY_A
        );

        $total_donantes = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT donante_id) FROM $tabla WHERE tipo = 'fondo_comunitario' AND estado = 'aceptada'"
        );

        ob_start();
        include dirname(__FILE__) . '/templates/fondo-solidario.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Dashboard sostenibilidad
     */
    public function shortcode_dashboard_sostenibilidad(array $atts): string {
        $atts = shortcode_atts([
            'periodo' => 'mensual',
        ], $atts);

        $metricas = $this->calcular_metricas_periodo($atts['periodo']);
        $metricas['ratio_oferta_demanda'] = json_decode($metricas['ratio_oferta_demanda'], true);
        $metricas['alertas_generadas'] = json_decode($metricas['alertas_generadas'], true);

        ob_start();
        include dirname(__FILE__) . '/templates/dashboard-sostenibilidad.php';
        return ob_get_clean();
    }

    /**
     * Shortcode: Donar horas
     */
    public function shortcode_donar_horas(array $atts): string {
        if (!is_user_logged_in()) {
            return '<p class="bt-aviso">' . __('Inicia sesión para donar horas', 'flavor-chat-ia') . '</p>';
        }

        $saldo = $this->obtener_saldo(get_current_user_id());
        $nonce = wp_create_nonce('bt_conciencia_nonce');

        ob_start();
        include dirname(__FILE__) . '/templates/donar-horas.php';
        return ob_get_clean();
    }

    // =========================================================================
    // HOOK POST-INTERCAMBIO
    // =========================================================================

    /**
     * Actualizar reputación después de completar intercambio
     */
    public function actualizar_reputacion_post_intercambio(int $transaccion_id, array $datos): void {
        global $wpdb;
        $tabla = $this->prefix . 'transacciones';

        $transaccion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla WHERE id = %d",
            $transaccion_id
        ), ARRAY_A);

        if ($transaccion) {
            $this->recalcular_reputacion($transaccion['usuario_solicitante_id']);
            $this->recalcular_reputacion($transaccion['usuario_receptor_id']);
        }
    }

    // =========================================================================
    // ESTADÍSTICAS PARA DASHBOARD
    // =========================================================================

    /**
     * Obtener estadísticas para el dashboard del usuario
     */
    public function get_estadisticas_dashboard(int $usuario_id): array {
        $reputacion = $this->obtener_reputacion($usuario_id);
        $saldo = $this->obtener_saldo($usuario_id);

        return [
            [
                'value' => $reputacion['nivel'],
                'label' => __('Nivel', 'flavor-chat-ia'),
                'icon'  => 'star-filled',
            ],
            [
                'value' => number_format($saldo, 1) . 'h',
                'label' => __('Saldo', 'flavor-chat-ia'),
                'icon'  => 'clock',
            ],
            [
                'value' => count($reputacion['badges']),
                'label' => __('Badges', 'flavor-chat-ia'),
                'icon'  => 'awards',
            ],
        ];
    }
}
