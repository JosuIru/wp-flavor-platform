<?php
/**
 * Integraciones del sistema de reputación con módulos existentes
 *
 * Conecta acciones de módulos con el sistema de puntos automáticamente
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Reputation_Integrations {

    /**
     * Instancia única
     */
    private static $instance = null;

    /**
     * Constructor privado
     */
    private function __construct() {
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
     * Inicializar hooks con módulos existentes
     */
    private function init_hooks() {
        // === EVENTOS ===
        add_action('flavor_evento_inscripcion', [$this, 'puntos_asistir_evento'], 10, 2);
        add_action('flavor_evento_creado', [$this, 'puntos_crear_evento'], 10, 2);

        // === CURSOS ===
        add_action('flavor_curso_completado', [$this, 'puntos_completar_curso'], 10, 2);
        add_action('flavor_leccion_completada', [$this, 'puntos_completar_leccion'], 10, 3);

        // === TALLERES ===
        add_action('flavor_taller_asistencia', [$this, 'puntos_asistir_taller'], 10, 2);

        // === BANCO DE TIEMPO ===
        add_action('flavor_servicio_completado', [$this, 'puntos_servicio_banco_tiempo'], 10, 3);
        add_action('flavor_servicio_valorado', [$this, 'puntos_valoracion_servicio'], 10, 3);

        // === MARKETPLACE ===
        add_action('flavor_anuncio_publicado', [$this, 'puntos_publicar_anuncio'], 10, 2);
        add_action('flavor_transaccion_completada', [$this, 'puntos_transaccion'], 10, 3);

        // === GRUPOS DE CONSUMO ===
        add_action('flavor_gc_pedido_creado', [$this, 'puntos_crear_pedido_gc'], 10, 2);
        add_action('flavor_gc_grupo_unido', [$this, 'puntos_unirse_grupo_gc'], 10, 2);

        // === PARTICIPACIÓN CIUDADANA ===
        add_action('flavor_propuesta_creada', [$this, 'puntos_crear_propuesta'], 10, 2);
        add_action('flavor_voto_emitido', [$this, 'puntos_votar'], 10, 3);
        add_action('flavor_presupuesto_propuesta', [$this, 'puntos_propuesta_presupuesto'], 10, 2);

        // === INCIDENCIAS ===
        add_action('flavor_incidencia_reportada', [$this, 'puntos_reportar_incidencia'], 10, 2);
        add_action('flavor_incidencia_verificada', [$this, 'puntos_verificar_incidencia'], 10, 2);

        // === HUERTOS URBANOS ===
        add_action('flavor_huerto_actividad', [$this, 'puntos_actividad_huerto'], 10, 3);
        add_action('flavor_cosecha_registrada', [$this, 'puntos_registrar_cosecha'], 10, 2);

        // === RECICLAJE ===
        add_action('flavor_reciclaje_entrega', [$this, 'puntos_entrega_reciclaje'], 10, 3);

        // === CARPOOLING ===
        add_action('flavor_viaje_compartido', [$this, 'puntos_compartir_viaje'], 10, 2);
        add_action('flavor_viaje_completado', [$this, 'puntos_completar_viaje'], 10, 3);

        // === FOROS/COMUNIDADES ===
        add_action('flavor_tema_creado', [$this, 'puntos_crear_tema'], 10, 2);
        add_action('flavor_respuesta_publicada', [$this, 'puntos_responder_tema'], 10, 3);
        add_action('flavor_respuesta_mejor', [$this, 'puntos_mejor_respuesta'], 10, 2);

        // === BIBLIOTECA ===
        add_action('flavor_recurso_compartido', [$this, 'puntos_compartir_recurso'], 10, 2);

        // === AYUDA VECINAL ===
        add_action('flavor_ayuda_ofrecida', [$this, 'puntos_ofrecer_ayuda'], 10, 2);
        add_action('flavor_ayuda_completada', [$this, 'puntos_completar_ayuda'], 10, 2);

        // === COMENTARIOS GENÉRICOS ===
        add_action('wp_insert_comment', [$this, 'puntos_comentario'], 10, 2);

        // === PERFIL ===
        add_action('profile_update', [$this, 'verificar_perfil_completo'], 10, 2);

        // === PRIMERA PUBLICACIÓN ===
        add_action('transition_post_status', [$this, 'verificar_primera_publicacion'], 10, 3);
    }

    // ==========================================
    // HANDLERS DE PUNTOS
    // ==========================================

    /**
     * Puntos por asistir a evento
     */
    public function puntos_asistir_evento($usuario_id, $evento_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'evento_asistido', null, [
            'referencia_id' => $evento_id,
            'referencia_tipo' => 'evento',
            'descripcion' => 'Inscripción en evento'
        ]);
    }

    /**
     * Puntos por crear evento
     */
    public function puntos_crear_evento($usuario_id, $evento_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 15, [
            'referencia_id' => $evento_id,
            'referencia_tipo' => 'evento',
            'descripcion' => 'Crear evento'
        ]);
    }

    /**
     * Puntos por completar curso
     */
    public function puntos_completar_curso($usuario_id, $curso_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'curso_completado', null, [
            'referencia_id' => $curso_id,
            'referencia_tipo' => 'curso',
            'descripcion' => 'Completar curso'
        ]);
    }

    /**
     * Puntos por completar lección
     */
    public function puntos_completar_leccion($usuario_id, $leccion_id, $curso_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'leccion', 2, [
            'referencia_id' => $leccion_id,
            'referencia_tipo' => 'leccion',
            'descripcion' => 'Completar lección'
        ]);
    }

    /**
     * Puntos por asistir a taller
     */
    public function puntos_asistir_taller($usuario_id, $taller_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'taller_asistido', null, [
            'referencia_id' => $taller_id,
            'referencia_tipo' => 'taller',
            'descripcion' => 'Asistir a taller'
        ]);
    }

    /**
     * Puntos por completar servicio en banco de tiempo
     */
    public function puntos_servicio_banco_tiempo($usuario_id, $servicio_id, $horas) {
        $puntos = 12 + ($horas * 2); // Base + bonus por horas
        flavor_reputation()->agregar_puntos($usuario_id, 'servicio_realizado', $puntos, [
            'referencia_id' => $servicio_id,
            'referencia_tipo' => 'servicio_bt',
            'descripcion' => sprintf('Servicio completado (%d horas)', $horas)
        ]);
    }

    /**
     * Puntos por valorar servicio
     */
    public function puntos_valoracion_servicio($usuario_id, $servicio_id, $estrellas) {
        // El que recibe la valoración positiva obtiene puntos
        if ($estrellas >= 4) {
            flavor_reputation()->agregar_puntos($usuario_id, 'respuesta_valorada', $estrellas, [
                'referencia_id' => $servicio_id,
                'referencia_tipo' => 'valoracion_bt',
                'descripcion' => sprintf('Valoración positiva (%d estrellas)', $estrellas)
            ]);
        }
    }

    /**
     * Puntos por publicar anuncio
     */
    public function puntos_publicar_anuncio($usuario_id, $anuncio_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 8, [
            'referencia_id' => $anuncio_id,
            'referencia_tipo' => 'anuncio',
            'descripcion' => 'Publicar anuncio en marketplace'
        ]);
    }

    /**
     * Puntos por transacción completada
     */
    public function puntos_transaccion($usuario_id, $transaccion_id, $tipo) {
        $puntos = $tipo === 'venta' ? 10 : 5;
        flavor_reputation()->agregar_puntos($usuario_id, 'transaccion', $puntos, [
            'referencia_id' => $transaccion_id,
            'referencia_tipo' => 'transaccion',
            'descripcion' => 'Transacción completada en marketplace'
        ]);
    }

    /**
     * Puntos por crear pedido en grupo de consumo
     */
    public function puntos_crear_pedido_gc($usuario_id, $pedido_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'pedido_gc', 5, [
            'referencia_id' => $pedido_id,
            'referencia_tipo' => 'pedido_gc',
            'descripcion' => 'Realizar pedido en grupo de consumo'
        ]);
    }

    /**
     * Puntos por unirse a grupo de consumo
     */
    public function puntos_unirse_grupo_gc($usuario_id, $grupo_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'unirse_comunidad', 10, [
            'referencia_id' => $grupo_id,
            'referencia_tipo' => 'grupo_consumo',
            'descripcion' => 'Unirse a grupo de consumo'
        ]);
    }

    /**
     * Puntos por crear propuesta
     */
    public function puntos_crear_propuesta($usuario_id, $propuesta_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 12, [
            'referencia_id' => $propuesta_id,
            'referencia_tipo' => 'propuesta',
            'descripcion' => 'Crear propuesta ciudadana'
        ]);
    }

    /**
     * Puntos por votar
     */
    public function puntos_votar($usuario_id, $votacion_id, $opcion) {
        flavor_reputation()->agregar_puntos($usuario_id, 'voto', 2, [
            'referencia_id' => $votacion_id,
            'referencia_tipo' => 'votacion',
            'descripcion' => 'Participar en votación'
        ]);
    }

    /**
     * Puntos por propuesta en presupuestos participativos
     */
    public function puntos_propuesta_presupuesto($usuario_id, $propuesta_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 15, [
            'referencia_id' => $propuesta_id,
            'referencia_tipo' => 'propuesta_presupuesto',
            'descripcion' => 'Propuesta en presupuestos participativos'
        ]);
    }

    /**
     * Puntos por reportar incidencia
     */
    public function puntos_reportar_incidencia($usuario_id, $incidencia_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 8, [
            'referencia_id' => $incidencia_id,
            'referencia_tipo' => 'incidencia',
            'descripcion' => 'Reportar incidencia'
        ]);
    }

    /**
     * Puntos por verificar incidencia (confirmar existencia)
     */
    public function puntos_verificar_incidencia($usuario_id, $incidencia_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'verificacion', 3, [
            'referencia_id' => $incidencia_id,
            'referencia_tipo' => 'incidencia',
            'descripcion' => 'Verificar incidencia'
        ]);
    }

    /**
     * Puntos por actividad en huerto
     */
    public function puntos_actividad_huerto($usuario_id, $huerto_id, $tipo_actividad) {
        $puntos_por_tipo = [
            'riego' => 2,
            'siembra' => 5,
            'mantenimiento' => 3,
            'cosecha' => 8
        ];
        $puntos = $puntos_por_tipo[$tipo_actividad] ?? 3;

        flavor_reputation()->agregar_puntos($usuario_id, 'actividad_huerto', $puntos, [
            'referencia_id' => $huerto_id,
            'referencia_tipo' => 'huerto',
            'descripcion' => sprintf('Actividad en huerto: %s', $tipo_actividad)
        ]);
    }

    /**
     * Puntos por registrar cosecha
     */
    public function puntos_registrar_cosecha($usuario_id, $cosecha_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'cosecha', 10, [
            'referencia_id' => $cosecha_id,
            'referencia_tipo' => 'cosecha',
            'descripcion' => 'Registrar cosecha'
        ]);
    }

    /**
     * Puntos por entrega de reciclaje
     */
    public function puntos_entrega_reciclaje($usuario_id, $entrega_id, $kg) {
        $puntos = max(5, round($kg * 2)); // Mínimo 5 puntos, 2 por kg
        flavor_reputation()->agregar_puntos($usuario_id, 'reciclaje', $puntos, [
            'referencia_id' => $entrega_id,
            'referencia_tipo' => 'reciclaje',
            'descripcion' => sprintf('Entrega de reciclaje (%.1f kg)', $kg)
        ]);
    }

    /**
     * Puntos por compartir viaje
     */
    public function puntos_compartir_viaje($usuario_id, $viaje_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 8, [
            'referencia_id' => $viaje_id,
            'referencia_tipo' => 'viaje',
            'descripcion' => 'Publicar viaje compartido'
        ]);
    }

    /**
     * Puntos por completar viaje como conductor
     */
    public function puntos_completar_viaje($usuario_id, $viaje_id, $pasajeros) {
        $puntos = 10 + ($pasajeros * 3); // Base + bonus por pasajeros
        flavor_reputation()->agregar_puntos($usuario_id, 'viaje_completado', $puntos, [
            'referencia_id' => $viaje_id,
            'referencia_tipo' => 'viaje',
            'descripcion' => sprintf('Viaje completado (%d pasajeros)', $pasajeros)
        ]);
    }

    /**
     * Puntos por crear tema en foro
     */
    public function puntos_crear_tema($usuario_id, $tema_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', null, [
            'referencia_id' => $tema_id,
            'referencia_tipo' => 'tema_foro',
            'descripcion' => 'Crear tema en foro'
        ]);
    }

    /**
     * Puntos por responder en tema
     */
    public function puntos_responder_tema($usuario_id, $respuesta_id, $tema_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'comentario', null, [
            'referencia_id' => $respuesta_id,
            'referencia_tipo' => 'respuesta_foro',
            'descripcion' => 'Responder en foro'
        ]);
    }

    /**
     * Puntos por mejor respuesta
     */
    public function puntos_mejor_respuesta($usuario_id, $respuesta_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'respuesta_valorada', 10, [
            'referencia_id' => $respuesta_id,
            'referencia_tipo' => 'respuesta_foro',
            'descripcion' => 'Mejor respuesta del tema'
        ]);
    }

    /**
     * Puntos por compartir recurso en biblioteca
     */
    public function puntos_compartir_recurso($usuario_id, $recurso_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'publicacion', 8, [
            'referencia_id' => $recurso_id,
            'referencia_tipo' => 'recurso_biblioteca',
            'descripcion' => 'Compartir recurso en biblioteca'
        ]);
    }

    /**
     * Puntos por ofrecer ayuda vecinal
     */
    public function puntos_ofrecer_ayuda($usuario_id, $ayuda_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'oferta_ayuda', 5, [
            'referencia_id' => $ayuda_id,
            'referencia_tipo' => 'ayuda_vecinal',
            'descripcion' => 'Ofrecer ayuda vecinal'
        ]);
    }

    /**
     * Puntos por completar ayuda
     */
    public function puntos_completar_ayuda($usuario_id, $ayuda_id) {
        flavor_reputation()->agregar_puntos($usuario_id, 'servicio_realizado', 15, [
            'referencia_id' => $ayuda_id,
            'referencia_tipo' => 'ayuda_vecinal',
            'descripcion' => 'Completar ayuda vecinal'
        ]);
    }

    /**
     * Puntos por comentar (WordPress comments)
     */
    public function puntos_comentario($comment_id, $comment) {
        // Solo comentarios aprobados
        if ($comment->comment_approved != 1) {
            return;
        }

        // Solo usuarios logueados
        if (!$comment->user_id) {
            return;
        }

        // Evitar spam (mínimo 20 caracteres)
        if (strlen($comment->comment_content) < 20) {
            return;
        }

        flavor_reputation()->agregar_puntos($comment->user_id, 'comentario', null, [
            'referencia_id' => $comment_id,
            'referencia_tipo' => 'comentario',
            'descripcion' => 'Comentario publicado'
        ]);
    }

    /**
     * Verificar si el perfil está completo
     */
    public function verificar_perfil_completo($usuario_id, $old_user_data) {
        // Verificar campos requeridos
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return;
        }

        $campos_completos = [
            !empty($usuario->display_name),
            !empty($usuario->user_email),
            !empty(get_user_meta($usuario_id, 'description', true)),
            !empty(get_user_meta($usuario_id, 'wp_user_avatar', true)) ||
            !empty(get_user_meta($usuario_id, 'profile_picture', true))
        ];

        $porcentaje = (count(array_filter($campos_completos)) / count($campos_completos)) * 100;

        // Si el perfil está completo al 100% y no ha recibido el badge
        if ($porcentaje >= 100) {
            $ya_completado = get_user_meta($usuario_id, '_flavor_perfil_completo', true);
            if (!$ya_completado) {
                flavor_reputation()->agregar_puntos($usuario_id, 'completar_perfil', null, [
                    'descripcion' => 'Perfil completado al 100%'
                ]);
                update_user_meta($usuario_id, '_flavor_perfil_completo', 1);
            }
        }
    }

    /**
     * Verificar primera publicación del usuario
     */
    public function verificar_primera_publicacion($new_status, $old_status, $post) {
        // Solo cuando se publica por primera vez
        if ($new_status !== 'publish' || $old_status === 'publish') {
            return;
        }

        // Solo posts y páginas (o CPTs que queramos)
        $tipos_validos = ['post', 'page', 'flavor_evento', 'flavor_curso', 'flavor_taller'];
        if (!in_array($post->post_type, $tipos_validos)) {
            return;
        }

        $autor_id = $post->post_author;
        if (!$autor_id) {
            return;
        }

        // Verificar si es la primera publicación
        $primera_publicacion = get_user_meta($autor_id, '_flavor_primera_publicacion', true);
        if ($primera_publicacion) {
            return;
        }

        flavor_reputation()->agregar_puntos($autor_id, 'primera_publicacion', null, [
            'referencia_id' => $post->ID,
            'referencia_tipo' => $post->post_type,
            'descripcion' => 'Primera publicación'
        ]);
        update_user_meta($autor_id, '_flavor_primera_publicacion', $post->ID);
    }
}

// Inicializar después de que el reputation manager esté disponible
add_action('init', function() {
    if (function_exists('flavor_reputation')) {
        Flavor_Reputation_Integrations::get_instance();
    }
}, 20);
