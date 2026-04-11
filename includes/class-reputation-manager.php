<?php
/**
 * Gestor centralizado del sistema de reputación
 *
 * Maneja puntos, niveles, badges y rachas de actividad
 *
 * @package Flavor_Platform
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reputation_Manager {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Configuración de puntos por acción
     */
    private $puntos_acciones = [
        'publicacion'        => 10,
        'comentario'         => 3,
        'like_recibido'      => 2,
        'like_dado'          => 1,
        'login_diario'       => 2,
        'mencion_recibida'   => 2,
        'historia'           => 5,
        'compartido'         => 8,
        'evento_asistido'    => 5,
        'curso_completado'   => 15,
        'taller_asistido'    => 10,
        'servicio_realizado' => 12,
        'invitar_usuario'    => 15,
        'verificacion_perfil'=> 50,
        'respuesta_valorada' => 5,
        'primera_publicacion'=> 20,
        'completar_perfil'   => 25,
    ];

    /**
     * Configuración de niveles
     */
    private $niveles = [
        'nuevo'       => ['min' => 0, 'max' => 24, 'nombre' => 'Nuevo'],
        'activo'      => ['min' => 25, 'max' => 99, 'nombre' => 'Activo'],
        'contribuidor'=> ['min' => 100, 'max' => 299, 'nombre' => 'Contribuidor'],
        'experto'     => ['min' => 300, 'max' => 599, 'nombre' => 'Experto'],
        'lider'       => ['min' => 600, 'max' => 999, 'nombre' => 'Líder'],
        'embajador'   => ['min' => 1000, 'max' => 2499, 'nombre' => 'Embajador'],
        'leyenda'     => ['min' => 2500, 'max' => PHP_INT_MAX, 'nombre' => 'Leyenda'],
    ];

    /**
     * Constructor privado (singleton)
     */
    private function __construct() {
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';
        $this->init_hooks();
    }

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
     * Inicializar hooks
     */
    private function init_hooks() {
        // Login diario
        add_action('wp_login', [$this, 'registrar_login_diario'], 10, 2);

        // Hook global para otros módulos
        add_action('flavor_agregar_puntos', [$this, 'agregar_puntos'], 10, 4);

        // Verificar badges después de añadir puntos
        add_action('flavor_puntos_agregados', [$this, 'verificar_badges'], 10, 2);

        // Reset semanal/mensual
        add_action('flavor_cron_reset_puntos_semana', [$this, 'reset_puntos_semana']);
        add_action('flavor_cron_reset_puntos_mes', [$this, 'reset_puntos_mes']);

        // Registrar cron si no existe
        if (!wp_next_scheduled('flavor_cron_reset_puntos_semana')) {
            wp_schedule_event(strtotime('next monday midnight'), 'weekly', 'flavor_cron_reset_puntos_semana');
        }
        if (!wp_next_scheduled('flavor_cron_reset_puntos_mes')) {
            wp_schedule_event(strtotime('first day of next month midnight'), 'monthly', 'flavor_cron_reset_puntos_mes');
        }
    }

    /**
     * Agregar puntos a un usuario
     *
     * @param int    $usuario_id      ID del usuario
     * @param string $tipo_accion     Tipo de acción
     * @param int    $puntos_custom   Puntos personalizados (opcional)
     * @param array  $datos_extra     Datos adicionales
     * @return bool|int Puntos agregados o false
     */
    public function agregar_puntos($usuario_id, $tipo_accion, $puntos_custom = null, $datos_extra = []) {
        global $wpdb;

        if (!$usuario_id || !$tipo_accion) {
            return false;
        }

        $puntos = $puntos_custom !== null ? $puntos_custom : ($this->puntos_acciones[$tipo_accion] ?? 0);

        if ($puntos <= 0) {
            return false;
        }

        $datos_historial = [
            'usuario_id'     => $usuario_id,
            'puntos'         => $puntos,
            'tipo_accion'    => $tipo_accion,
            'descripcion'    => $datos_extra['descripcion'] ?? $this->get_descripcion_accion($tipo_accion),
            'referencia_id'  => $datos_extra['referencia_id'] ?? null,
            'referencia_tipo'=> $datos_extra['referencia_tipo'] ?? null,
            'fecha_creacion' => current_time('mysql'),
        ];

        $wpdb->insert($this->prefix . 'social_historial_puntos', $datos_historial);
        $this->actualizar_totales($usuario_id, $puntos);
        do_action('flavor_puntos_agregados', $usuario_id, $puntos);

        return $puntos;
    }

    /**
     * Actualizar totales de puntos del usuario
     */
    private function actualizar_totales($usuario_id, $puntos_nuevos) {
        global $wpdb;

        $tabla_reputacion = $this->prefix . 'social_reputacion';

        // OPTIMIZACIÓN: Seleccionar solo campos necesarios
        $registro_existente = $wpdb->get_row($wpdb->prepare(
            "SELECT id, puntos_totales, puntos_semana, puntos_mes, nivel FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if ($registro_existente) {
            $puntos_totales_nuevos = $registro_existente->puntos_totales + $puntos_nuevos;
            $nivel_nuevo = $this->calcular_nivel($puntos_totales_nuevos);
            $nivel_anterior = $registro_existente->nivel;

            $wpdb->update(
                $tabla_reputacion,
                [
                    'puntos_totales' => $puntos_totales_nuevos,
                    'puntos_semana'  => $registro_existente->puntos_semana + $puntos_nuevos,
                    'puntos_mes'     => $registro_existente->puntos_mes + $puntos_nuevos,
                    'nivel'          => $nivel_nuevo,
                    'ultima_actividad' => current_time('mysql'),
                ],
                ['usuario_id' => $usuario_id]
            );

            if ($nivel_nuevo !== $nivel_anterior) {
                $this->notificar_subida_nivel($usuario_id, $nivel_nuevo, $nivel_anterior);
            }
        } else {
            $nivel = $this->calcular_nivel($puntos_nuevos);

            $wpdb->insert($tabla_reputacion, [
                'usuario_id'       => $usuario_id,
                'puntos_totales'   => $puntos_nuevos,
                'puntos_semana'    => $puntos_nuevos,
                'puntos_mes'       => $puntos_nuevos,
                'nivel'            => $nivel,
                'racha_dias'       => 1,
                'ultima_actividad' => current_time('mysql'),
            ]);
        }
    }

    /**
     * Calcular nivel basado en puntos
     */
    public function calcular_nivel($puntos) {
        foreach ($this->niveles as $slug => $config) {
            if ($puntos >= $config['min'] && $puntos <= $config['max']) {
                return $slug;
            }
        }
        return 'nuevo';
    }

    /**
     * Obtener info de nivel
     */
    public function get_info_nivel($nivel_slug) {
        return $this->niveles[$nivel_slug] ?? $this->niveles['nuevo'];
    }

    /**
     * Registrar login diario (con deduplicación)
     */
    public function registrar_login_diario($user_login, $user) {
        global $wpdb;

        $usuario_id = $user->ID;
        $hoy = current_time('Y-m-d');

        $ya_registrado = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->prefix}social_historial_puntos
             WHERE usuario_id = %d AND tipo_accion = 'login_diario'
             AND DATE(fecha_creacion) = %s",
            $usuario_id,
            $hoy
        ));

        if ($ya_registrado) {
            return;
        }

        $this->agregar_puntos($usuario_id, 'login_diario', null, [
            'descripcion' => 'Inicio de sesión diario'
        ]);

        $this->actualizar_racha($usuario_id);
    }

    /**
     * Actualizar racha de días consecutivos
     */
    private function actualizar_racha($usuario_id) {
        global $wpdb;

        $tabla_reputacion = $this->prefix . 'social_reputacion';
        $ayer = date('Y-m-d', strtotime('-1 day'));

        $registro = $wpdb->get_row($wpdb->prepare(
            "SELECT racha_dias, DATE(ultima_actividad) as fecha_ultima
             FROM $tabla_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));

        if (!$registro) {
            return;
        }

        $nueva_racha = 1;
        if ($registro->fecha_ultima === $ayer) {
            $nueva_racha = $registro->racha_dias + 1;
        }

        $wpdb->update(
            $tabla_reputacion,
            ['racha_dias' => $nueva_racha],
            ['usuario_id' => $usuario_id]
        );

        $this->verificar_badges_racha($usuario_id, $nueva_racha);
    }

    /**
     * Verificar badges basados en racha
     */
    private function verificar_badges_racha($usuario_id, $racha) {
        global $wpdb;

        $tabla_badges = $this->prefix . 'social_badges';

        $badges_racha = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_badges
             WHERE condicion_especial LIKE %s AND activo = 1",
            '%racha_dias%'
        ));

        foreach ($badges_racha as $badge) {
            $condicion = json_decode($badge->condicion_especial, true);
            if ($condicion && isset($condicion['valor']) && $racha >= $condicion['valor']) {
                $this->otorgar_badge($usuario_id, $badge->id);
            }
        }
    }

    /**
     * Verificar y otorgar badges según puntos/condiciones
     */
    public function verificar_badges($usuario_id, $puntos_actuales = null) {
        global $wpdb;

        if ($puntos_actuales === null) {
            $puntos_actuales = $this->get_puntos_usuario($usuario_id);
        }

        $tabla_badges = $this->prefix . 'social_badges';

        $badges_puntos = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $tabla_badges
             WHERE puntos_requeridos > 0 AND puntos_requeridos <= %d AND activo = 1",
            $puntos_actuales
        ));

        foreach ($badges_puntos as $badge) {
            $this->otorgar_badge($usuario_id, $badge->id);
        }
    }

    /**
     * Otorgar badge a usuario (si no lo tiene)
     */
    public function otorgar_badge($usuario_id, $badge_id) {
        global $wpdb;

        $tabla_usuario_badges = $this->prefix . 'social_usuario_badges';

        $ya_tiene = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $tabla_usuario_badges WHERE usuario_id = %d AND badge_id = %d",
            $usuario_id,
            $badge_id
        ));

        if ($ya_tiene) {
            return false;
        }

        $wpdb->insert($tabla_usuario_badges, [
            'usuario_id'     => $usuario_id,
            'badge_id'       => $badge_id,
            'fecha_obtenido' => current_time('mysql'),
            'destacado'      => 0,
        ]);

        $this->notificar_badge_obtenido($usuario_id, $badge_id);

        return true;
    }

    /**
     * Obtener puntos totales de un usuario
     */
    public function get_puntos_usuario($usuario_id) {
        global $wpdb;

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT puntos_totales FROM {$this->prefix}social_reputacion WHERE usuario_id = %d",
            $usuario_id
        ));
    }

    /**
     * Obtener reputación completa de un usuario
     */
    public function get_reputacion_usuario($usuario_id) {
        global $wpdb;

        $reputacion = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->prefix}social_reputacion WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        if (!$reputacion) {
            return [
                'usuario_id'      => $usuario_id,
                'puntos_totales'  => 0,
                'nivel'           => 'nuevo',
                'nivel_nombre'    => 'Nuevo',
                'puntos_semana'   => 0,
                'puntos_mes'      => 0,
                'racha_dias'      => 0,
                'badges'          => [],
                'siguiente_nivel' => $this->niveles['activo'],
                'progreso_nivel'  => 0,
            ];
        }

        $nivel_info = $this->get_info_nivel($reputacion['nivel']);
        $reputacion['nivel_nombre'] = $nivel_info['nombre'];

        $nivel_actual_idx = array_search($reputacion['nivel'], array_keys($this->niveles));
        $niveles_keys = array_keys($this->niveles);

        if ($nivel_actual_idx < count($niveles_keys) - 1) {
            $siguiente_nivel_key = $niveles_keys[$nivel_actual_idx + 1];
            $siguiente_nivel = $this->niveles[$siguiente_nivel_key];
            $reputacion['siguiente_nivel'] = $siguiente_nivel;

            $puntos_en_nivel = $reputacion['puntos_totales'] - $nivel_info['min'];
            $puntos_para_siguiente = $siguiente_nivel['min'] - $nivel_info['min'];
            $reputacion['progreso_nivel'] = round(($puntos_en_nivel / $puntos_para_siguiente) * 100);
        } else {
            $reputacion['siguiente_nivel'] = null;
            $reputacion['progreso_nivel'] = 100;
        }

        $reputacion['badges'] = $this->get_badges_usuario($usuario_id);

        return $reputacion;
    }

    /**
     * Obtener badges de un usuario
     */
    public function get_badges_usuario($usuario_id) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, ub.fecha_obtenido, ub.destacado
             FROM {$this->prefix}social_badges b
             INNER JOIN {$this->prefix}social_usuario_badges ub ON b.id = ub.badge_id
             WHERE ub.usuario_id = %d
             ORDER BY ub.fecha_obtenido DESC",
            $usuario_id
        ));
    }

    /**
     * Obtener leaderboard
     */
    public function get_leaderboard($periodo = 'total', $limite = 10) {
        global $wpdb;

        $columna_puntos = 'puntos_totales';
        if ($periodo === 'semana') {
            $columna_puntos = 'puntos_semana';
        } elseif ($periodo === 'mes') {
            $columna_puntos = 'puntos_mes';
        }

        $resultados = $wpdb->get_results($wpdb->prepare(
            "SELECT r.usuario_id, r.$columna_puntos as puntos, r.nivel, r.racha_dias,
                    u.display_name, u.user_email
             FROM {$this->prefix}social_reputacion r
             INNER JOIN {$wpdb->users} u ON r.usuario_id = u.ID
             WHERE r.$columna_puntos > 0
             ORDER BY r.$columna_puntos DESC
             LIMIT %d",
            $limite
        ));

        $posicion = 1;
        foreach ($resultados as &$resultado) {
            $resultado->posicion = $posicion++;
            $resultado->avatar_url = get_avatar_url($resultado->usuario_id, ['size' => 64]);
            $resultado->nivel_nombre = $this->niveles[$resultado->nivel]['nombre'] ?? 'Nuevo';
        }

        return $resultados;
    }

    /**
     * Reset puntos semanales
     */
    public function reset_puntos_semana() {
        global $wpdb;
        $wpdb->query("UPDATE {$this->prefix}social_reputacion SET puntos_semana = 0");
    }

    /**
     * Reset puntos mensuales
     */
    public function reset_puntos_mes() {
        global $wpdb;
        $wpdb->query("UPDATE {$this->prefix}social_reputacion SET puntos_mes = 0");
    }

    /**
     * Obtener descripción de acción
     */
    private function get_descripcion_accion($tipo_accion) {
        $descripciones = [
            'publicacion'        => 'Crear publicación',
            'comentario'         => 'Comentar en publicación',
            'like_recibido'      => 'Recibir un me gusta',
            'like_dado'          => 'Dar me gusta',
            'login_diario'       => 'Inicio de sesión diario',
            'mencion_recibida'   => 'Ser mencionado',
            'historia'           => 'Crear historia',
            'compartido'         => 'Compartir contenido',
            'evento_asistido'    => 'Asistir a evento',
            'curso_completado'   => 'Completar curso',
            'taller_asistido'    => 'Asistir a taller',
            'servicio_realizado' => 'Realizar servicio (banco de tiempo)',
            'invitar_usuario'    => 'Invitar nuevo usuario',
            'verificacion_perfil'=> 'Verificar perfil',
            'respuesta_valorada' => 'Respuesta valorada positivamente',
            'primera_publicacion'=> 'Primera publicación',
            'completar_perfil'   => 'Completar perfil',
        ];

        return $descripciones[$tipo_accion] ?? $tipo_accion;
    }

    /**
     * Notificar subida de nivel
     */
    private function notificar_subida_nivel($usuario_id, $nivel_nuevo, $nivel_anterior) {
        $nivel_info = $this->get_info_nivel($nivel_nuevo);

        if (class_exists('Flavor_Notifications_System')) {
            Flavor_Notifications_System::get_instance()->crear_notificacion(
                $usuario_id,
                'reputacion',
                'Subiste de nivel!',
                sprintf('Has alcanzado el nivel %s. Felicidades!', $nivel_info['nombre']),
                ['nivel' => $nivel_nuevo, 'nivel_anterior' => $nivel_anterior]
            );
        }

        do_action('flavor_usuario_subio_nivel', $usuario_id, $nivel_nuevo, $nivel_anterior);
    }

    /**
     * Notificar badge obtenido
     */
    private function notificar_badge_obtenido($usuario_id, $badge_id) {
        global $wpdb;

        $badge = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->prefix}social_badges WHERE id = %d",
            $badge_id
        ));

        if (!$badge) {
            return;
        }

        if (class_exists('Flavor_Notifications_System')) {
            Flavor_Notifications_System::get_instance()->crear_notificacion(
                $usuario_id,
                'reputacion',
                'Nuevo badge desbloqueado!',
                sprintf('%s %s: %s', $badge->icono, $badge->nombre, $badge->descripcion),
                ['badge_id' => $badge_id]
            );
        }

        do_action('flavor_usuario_obtuvo_badge', $usuario_id, $badge_id, $badge);
    }

    /**
     * Obtener historial de puntos de un usuario
     */
    public function get_historial_puntos($usuario_id, $limite = 20, $offset = 0) {
        global $wpdb;

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->prefix}social_historial_puntos
             WHERE usuario_id = %d
             ORDER BY fecha_creacion DESC
             LIMIT %d OFFSET %d",
            $usuario_id,
            $limite,
            $offset
        ));
    }

    /**
     * Obtener todos los badges disponibles
     */
    public function get_badges_disponibles() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT * FROM {$this->prefix}social_badges WHERE activo = 1 ORDER BY orden ASC"
        );
    }

    /**
     * Verificar si usuario tiene un badge específico
     */
    public function usuario_tiene_badge($usuario_id, $badge_slug) {
        global $wpdb;

        return (bool) $wpdb->get_var($wpdb->prepare(
            "SELECT ub.id
             FROM {$this->prefix}social_usuario_badges ub
             INNER JOIN {$this->prefix}social_badges b ON ub.badge_id = b.id
             WHERE ub.usuario_id = %d AND b.slug = %s",
            $usuario_id,
            $badge_slug
        ));
    }

    /**
     * Obtener configuración de puntos
     */
    public function get_puntos_config() {
        return $this->puntos_acciones;
    }

    /**
     * Obtener configuración de niveles
     */
    public function get_niveles_config() {
        return $this->niveles;
    }
}

/**
 * Función helper para obtener instancia
 */
function flavor_reputation() {
    return Flavor_Reputation_Manager::get_instance();
}
