<?php
/**
 * Gestor principal de moderación de contenido
 *
 * Sistema centralizado para moderar contenido generado por usuarios
 * en todos los módulos de la plataforma.
 *
 * @package Flavor_Chat_IA
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Moderation_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

    /**
     * Tipos de contenido moderables
     */
    const CONTENT_TYPES = [
        'publicacion' => [
            'label' => 'Publicación',
            'icon' => 'admin-post',
            'table' => 'social_publicaciones',
            'id_field' => 'id',
            'content_field' => 'contenido',
            'author_field' => 'autor_id',
            'status_field' => 'estado'
        ],
        'comentario' => [
            'label' => 'Comentario',
            'icon' => 'admin-comments',
            'table' => 'social_comentarios',
            'id_field' => 'id',
            'content_field' => 'comentario',
            'author_field' => 'autor_id',
            'status_field' => 'estado'
        ],
        'tema_foro' => [
            'label' => 'Tema de foro',
            'icon' => 'format-chat',
            'table' => 'foros_temas',
            'id_field' => 'id',
            'content_field' => 'contenido',
            'author_field' => 'autor_id',
            'status_field' => 'estado'
        ],
        'mensaje' => [
            'label' => 'Mensaje privado',
            'icon' => 'email',
            'table' => 'mensajes',
            'id_field' => 'id',
            'content_field' => 'contenido',
            'author_field' => 'remitente_id',
            'status_field' => null
        ],
        'anuncio_marketplace' => [
            'label' => 'Anuncio marketplace',
            'icon' => 'cart',
            'table' => 'marketplace',
            'id_field' => 'id',
            'content_field' => 'descripcion',
            'author_field' => 'usuario_id',
            'status_field' => 'estado'
        ],
        'incidencia' => [
            'label' => 'Incidencia',
            'icon' => 'warning',
            'table' => 'incidencias',
            'id_field' => 'id',
            'content_field' => 'descripcion',
            'author_field' => 'usuario_id',
            'status_field' => 'estado'
        ],
        'ayuda_vecinal' => [
            'label' => 'Ayuda vecinal',
            'icon' => 'heart',
            'table' => 'ayuda_vecinal',
            'id_field' => 'id',
            'content_field' => 'descripcion',
            'author_field' => 'usuario_id',
            'status_field' => 'estado'
        ],
        'propuesta_presupuesto' => [
            'label' => 'Propuesta presupuesto',
            'icon' => 'money',
            'table' => 'presupuestos_propuestas',
            'id_field' => 'id',
            'content_field' => 'descripcion',
            'author_field' => 'usuario_id',
            'status_field' => 'estado'
        ],
        'perfil_usuario' => [
            'label' => 'Perfil de usuario',
            'icon' => 'admin-users',
            'table' => null,
            'id_field' => 'ID',
            'content_field' => null,
            'author_field' => null,
            'status_field' => null
        ]
    ];

    /**
     * Motivos de reporte predefinidos
     */
    const REPORT_REASONS = [
        'spam' => [
            'label' => 'Spam',
            'descripcion' => 'Contenido publicitario no deseado o repetitivo',
            'severidad' => 'media'
        ],
        'contenido_inapropiado' => [
            'label' => 'Contenido inapropiado',
            'descripcion' => 'Contenido ofensivo, sexual o violento',
            'severidad' => 'alta'
        ],
        'acoso' => [
            'label' => 'Acoso o bullying',
            'descripcion' => 'Comportamiento abusivo hacia otros usuarios',
            'severidad' => 'alta'
        ],
        'informacion_falsa' => [
            'label' => 'Información falsa',
            'descripcion' => 'Desinformación o noticias falsas',
            'severidad' => 'media'
        ],
        'suplantacion' => [
            'label' => 'Suplantación de identidad',
            'descripcion' => 'Hacerse pasar por otra persona',
            'severidad' => 'alta'
        ],
        'odio' => [
            'label' => 'Discurso de odio',
            'descripcion' => 'Contenido que promueve odio hacia grupos',
            'severidad' => 'critica'
        ],
        'privacidad' => [
            'label' => 'Violación de privacidad',
            'descripcion' => 'Compartir información personal sin consentimiento',
            'severidad' => 'alta'
        ],
        'ilegal' => [
            'label' => 'Contenido ilegal',
            'descripcion' => 'Contenido que viola la ley',
            'severidad' => 'critica'
        ],
        'otro' => [
            'label' => 'Otro motivo',
            'descripcion' => 'Otro tipo de violación de normas',
            'severidad' => 'baja'
        ]
    ];

    /**
     * Estados de reporte
     */
    const REPORT_STATUS = [
        'pendiente' => 'Pendiente de revisión',
        'en_revision' => 'En revisión',
        'accion_tomada' => 'Acción tomada',
        'rechazado' => 'Reporte rechazado',
        'duplicado' => 'Reporte duplicado'
    ];

    /**
     * Tipos de acción de moderación
     */
    const ACTION_TYPES = [
        'aprobar' => ['label' => 'Aprobar contenido', 'icon' => 'yes-alt', 'color' => 'success'],
        'rechazar' => ['label' => 'Rechazar contenido', 'icon' => 'no-alt', 'color' => 'danger'],
        'ocultar' => ['label' => 'Ocultar contenido', 'icon' => 'hidden', 'color' => 'warning'],
        'restaurar' => ['label' => 'Restaurar contenido', 'icon' => 'backup', 'color' => 'info'],
        'editar' => ['label' => 'Editar contenido', 'icon' => 'edit', 'color' => 'info'],
        'eliminar' => ['label' => 'Eliminar permanentemente', 'icon' => 'trash', 'color' => 'danger'],
        'warning' => ['label' => 'Enviar advertencia', 'icon' => 'warning', 'color' => 'warning'],
        'ban_temporal' => ['label' => 'Suspensión temporal', 'icon' => 'clock', 'color' => 'danger'],
        'ban_permanente' => ['label' => 'Suspensión permanente', 'icon' => 'dismiss', 'color' => 'danger'],
        'desbloquear' => ['label' => 'Desbloquear usuario', 'icon' => 'unlock', 'color' => 'success'],
        'silenciar' => ['label' => 'Silenciar usuario', 'icon' => 'controls-volumeoff', 'color' => 'warning'],
        'quitar_silencio' => ['label' => 'Quitar silencio', 'icon' => 'controls-volumeon', 'color' => 'success']
    ];

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
        global $wpdb;
        $this->prefix = $wpdb->prefix . 'flavor_';

        $this->init_hooks();
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', [$this, 'add_admin_menu']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_mod_report_content', [$this, 'ajax_report_content']);
        add_action('wp_ajax_flavor_mod_process_report', [$this, 'ajax_process_report']);
        add_action('wp_ajax_flavor_mod_bulk_action', [$this, 'ajax_bulk_action']);
        add_action('wp_ajax_flavor_mod_get_reports', [$this, 'ajax_get_reports']);
        add_action('wp_ajax_flavor_mod_get_user_history', [$this, 'ajax_get_user_history']);
        add_action('wp_ajax_flavor_mod_apply_sanction', [$this, 'ajax_apply_sanction']);
        add_action('wp_ajax_flavor_mod_get_stats', [$this, 'ajax_get_stats']);

        // AJAX públicos (para reportar)
        add_action('wp_ajax_flavor_report_content', [$this, 'ajax_user_report_content']);

        // Verificar sanciones de usuario
        add_action('init', [$this, 'check_user_sanctions']);

        // Enqueue assets en admin
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);

        // Cron para limpiar sanciones expiradas
        add_action('flavor_cleanup_expired_sanctions', [$this, 'cleanup_expired_sanctions']);
        if (!wp_next_scheduled('flavor_cleanup_expired_sanctions')) {
            wp_schedule_event(time(), 'hourly', 'flavor_cleanup_expired_sanctions');
        }

        // Añadir columna de estado de moderación en admin de usuarios
        add_filter('manage_users_columns', [$this, 'add_user_moderation_column']);
        add_filter('manage_users_custom_column', [$this, 'render_user_moderation_column'], 10, 3);
    }

    // =========================================================================
    // GESTIÓN DE REPORTES
    // =========================================================================

    /**
     * Crear un reporte de contenido
     */
    public function create_report($datos) {
        global $wpdb;
        $tabla_reportes = $this->prefix . 'moderation_reports';

        $campos_requeridos = ['tipo_contenido', 'contenido_id', 'motivo', 'reportado_por'];
        foreach ($campos_requeridos as $campo) {
            if (empty($datos[$campo])) {
                return new WP_Error('campo_requerido', "El campo {$campo} es requerido");
            }
        }

        // Validar tipo de contenido
        if (!isset(self::CONTENT_TYPES[$datos['tipo_contenido']])) {
            return new WP_Error('tipo_invalido', 'Tipo de contenido no válido');
        }

        // Validar motivo
        if (!isset(self::REPORT_REASONS[$datos['motivo']])) {
            return new WP_Error('motivo_invalido', 'Motivo de reporte no válido');
        }

        // Verificar si ya existe un reporte similar pendiente
        $reporte_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_reportes}
             WHERE tipo_contenido = %s
             AND contenido_id = %d
             AND reportado_por = %d
             AND estado IN ('pendiente', 'en_revision')",
            $datos['tipo_contenido'],
            $datos['contenido_id'],
            $datos['reportado_por']
        ));

        if ($reporte_existente) {
            return new WP_Error('reporte_existente', 'Ya has reportado este contenido');
        }

        // Obtener autor del contenido
        $autor_contenido = $this->get_content_author($datos['tipo_contenido'], $datos['contenido_id']);

        // Crear reporte
        $resultado = $wpdb->insert(
            $tabla_reportes,
            [
                'tipo_contenido' => $datos['tipo_contenido'],
                'contenido_id' => $datos['contenido_id'],
                'autor_contenido_id' => $autor_contenido,
                'motivo' => $datos['motivo'],
                'descripcion' => sanitize_textarea_field($datos['descripcion'] ?? ''),
                'reportado_por' => $datos['reportado_por'],
                'severidad' => self::REPORT_REASONS[$datos['motivo']]['severidad'],
                'estado' => 'pendiente',
                'metadata' => wp_json_encode([
                    'url_referencia' => sanitize_url($datos['url_referencia'] ?? ''),
                    'ip' => $this->get_user_ip(),
                    'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? '')
                ]),
                'fecha_creacion' => current_time('mysql')
            ],
            ['%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s']
        );

        if ($resultado === false) {
            return new WP_Error('db_error', 'Error al crear el reporte');
        }

        $reporte_id = $wpdb->insert_id;

        // Incrementar contador de reportes del contenido
        $this->increment_report_count($datos['tipo_contenido'], $datos['contenido_id']);

        // Notificar a moderadores si es de alta severidad
        if (in_array(self::REPORT_REASONS[$datos['motivo']]['severidad'], ['alta', 'critica'])) {
            $this->notify_moderators_new_report($reporte_id);
        }

        // Auto-ocultar si tiene muchos reportes
        $this->check_auto_hide($datos['tipo_contenido'], $datos['contenido_id']);

        do_action('flavor_moderation_report_created', $reporte_id, $datos);

        return $reporte_id;
    }

    /**
     * Obtener reportes con filtros
     */
    public function get_reports($filtros = []) {
        global $wpdb;
        $tabla_reportes = $this->prefix . 'moderation_reports';

        $where_clauses = ['1=1'];
        $valores = [];

        // Filtros
        if (!empty($filtros['estado'])) {
            $where_clauses[] = 'r.estado = %s';
            $valores[] = $filtros['estado'];
        }

        if (!empty($filtros['tipo_contenido'])) {
            $where_clauses[] = 'r.tipo_contenido = %s';
            $valores[] = $filtros['tipo_contenido'];
        }

        if (!empty($filtros['motivo'])) {
            $where_clauses[] = 'r.motivo = %s';
            $valores[] = $filtros['motivo'];
        }

        if (!empty($filtros['severidad'])) {
            $where_clauses[] = 'r.severidad = %s';
            $valores[] = $filtros['severidad'];
        }

        if (!empty($filtros['autor_contenido_id'])) {
            $where_clauses[] = 'r.autor_contenido_id = %d';
            $valores[] = $filtros['autor_contenido_id'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where_clauses[] = 'r.fecha_creacion >= %s';
            $valores[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where_clauses[] = 'r.fecha_creacion <= %s';
            $valores[] = $filtros['fecha_hasta'];
        }

        // Paginación
        $pagina = max(1, intval($filtros['pagina'] ?? 1));
        $por_pagina = min(100, max(1, intval($filtros['por_pagina'] ?? 20)));
        $offset = ($pagina - 1) * $por_pagina;

        // Ordenamiento
        $orden_permitidos = ['fecha_creacion', 'severidad', 'estado'];
        $orden_campo = in_array($filtros['orden_campo'] ?? '', $orden_permitidos)
            ? $filtros['orden_campo']
            : 'fecha_creacion';
        $orden_dir = strtoupper($filtros['orden_dir'] ?? 'DESC') === 'ASC' ? 'ASC' : 'DESC';

        $where_sql = implode(' AND ', $where_clauses);

        // Contar total
        $sql_count = "SELECT COUNT(*) FROM {$tabla_reportes} r WHERE {$where_sql}";
        if (!empty($valores)) {
            $sql_count = $wpdb->prepare($sql_count, $valores);
        }
        $total = (int) $wpdb->get_var($sql_count);

        // Obtener reportes
        $sql = "SELECT r.*,
                       u_reporter.display_name as reportado_por_nombre,
                       u_autor.display_name as autor_nombre
                FROM {$tabla_reportes} r
                LEFT JOIN {$wpdb->users} u_reporter ON r.reportado_por = u_reporter.ID
                LEFT JOIN {$wpdb->users} u_autor ON r.autor_contenido_id = u_autor.ID
                WHERE {$where_sql}
                ORDER BY r.{$orden_campo} {$orden_dir}
                LIMIT {$offset}, {$por_pagina}";

        if (!empty($valores)) {
            $sql = $wpdb->prepare($sql, $valores);
        }

        $reportes = $wpdb->get_results($sql, ARRAY_A);

        // Enriquecer con datos del contenido
        foreach ($reportes as &$reporte) {
            $reporte['contenido_preview'] = $this->get_content_preview(
                $reporte['tipo_contenido'],
                $reporte['contenido_id']
            );
            $reporte['tipo_info'] = self::CONTENT_TYPES[$reporte['tipo_contenido']] ?? null;
            $reporte['motivo_info'] = self::REPORT_REASONS[$reporte['motivo']] ?? null;
            $reporte['metadata'] = json_decode($reporte['metadata'], true);
        }

        return [
            'reportes' => $reportes,
            'total' => $total,
            'paginas' => ceil($total / $por_pagina),
            'pagina_actual' => $pagina
        ];
    }

    /**
     * Procesar un reporte
     */
    public function process_report($reporte_id, $accion, $datos = []) {
        global $wpdb;
        $tabla_reportes = $this->prefix . 'moderation_reports';

        $reporte = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_reportes} WHERE id = %d",
            $reporte_id
        ));

        if (!$reporte) {
            return new WP_Error('no_encontrado', 'Reporte no encontrado');
        }

        if (!isset(self::ACTION_TYPES[$accion])) {
            return new WP_Error('accion_invalida', 'Acción no válida');
        }

        $moderador_id = get_current_user_id();
        $notas = sanitize_textarea_field($datos['notas'] ?? '');

        // Ejecutar la acción correspondiente
        $resultado_accion = $this->execute_moderation_action(
            $accion,
            $reporte->tipo_contenido,
            $reporte->contenido_id,
            $reporte->autor_contenido_id,
            $datos
        );

        if (is_wp_error($resultado_accion)) {
            return $resultado_accion;
        }

        // Actualizar estado del reporte
        $nuevo_estado = in_array($accion, ['aprobar', 'restaurar']) ? 'rechazado' : 'accion_tomada';

        $wpdb->update(
            $tabla_reportes,
            [
                'estado' => $nuevo_estado,
                'procesado_por' => $moderador_id,
                'fecha_procesado' => current_time('mysql'),
                'accion_tomada' => $accion,
                'notas_moderador' => $notas
            ],
            ['id' => $reporte_id],
            ['%s', '%d', '%s', '%s', '%s'],
            ['%d']
        );

        // Registrar acción de moderación
        $this->log_moderation_action([
            'tipo' => $accion,
            'tipo_contenido' => $reporte->tipo_contenido,
            'contenido_id' => $reporte->contenido_id,
            'usuario_afectado' => $reporte->autor_contenido_id,
            'moderador_id' => $moderador_id,
            'reporte_id' => $reporte_id,
            'notas' => $notas
        ]);

        // Marcar reportes similares como duplicados
        $this->mark_similar_reports_as_duplicates($reporte);

        do_action('flavor_moderation_report_processed', $reporte_id, $accion, $moderador_id);

        return true;
    }

    /**
     * Ejecutar acción de moderación
     */
    private function execute_moderation_action($accion, $tipo_contenido, $contenido_id, $autor_id, $datos = []) {
        global $wpdb;

        $tipo_info = self::CONTENT_TYPES[$tipo_contenido] ?? null;

        switch ($accion) {
            case 'aprobar':
                if ($tipo_info && $tipo_info['table'] && $tipo_info['status_field']) {
                    $tabla = $this->prefix . $tipo_info['table'];
                    $wpdb->update(
                        $tabla,
                        [$tipo_info['status_field'] => 'publicado'],
                        [$tipo_info['id_field'] => $contenido_id],
                        ['%s'],
                        ['%d']
                    );
                }
                break;

            case 'ocultar':
                if ($tipo_info && $tipo_info['table'] && $tipo_info['status_field']) {
                    $tabla = $this->prefix . $tipo_info['table'];
                    $wpdb->update(
                        $tabla,
                        [$tipo_info['status_field'] => 'oculto'],
                        [$tipo_info['id_field'] => $contenido_id],
                        ['%s'],
                        ['%d']
                    );
                }
                break;

            case 'rechazar':
            case 'eliminar':
                if ($tipo_info && $tipo_info['table']) {
                    $tabla = $this->prefix . $tipo_info['table'];
                    if ($tipo_info['status_field']) {
                        $wpdb->update(
                            $tabla,
                            [$tipo_info['status_field'] => 'eliminado'],
                            [$tipo_info['id_field'] => $contenido_id],
                            ['%s'],
                            ['%d']
                        );
                    } else {
                        // Eliminación física si no hay campo de estado
                        $wpdb->delete($tabla, [$tipo_info['id_field'] => $contenido_id], ['%d']);
                    }
                }
                // Notificar al autor
                $this->notify_user_content_removed($autor_id, $tipo_contenido, $datos['motivo_notificacion'] ?? '');
                break;

            case 'restaurar':
                if ($tipo_info && $tipo_info['table'] && $tipo_info['status_field']) {
                    $tabla = $this->prefix . $tipo_info['table'];
                    $wpdb->update(
                        $tabla,
                        [$tipo_info['status_field'] => 'publicado'],
                        [$tipo_info['id_field'] => $contenido_id],
                        ['%s'],
                        ['%d']
                    );
                }
                break;

            case 'warning':
                return $this->send_warning($autor_id, $datos['mensaje'] ?? 'Has recibido una advertencia por incumplir las normas de la comunidad.');

            case 'ban_temporal':
                $duracion_dias = intval($datos['duracion_dias'] ?? 7);
                return $this->apply_sanction($autor_id, 'ban_temporal', $duracion_dias, $datos['motivo'] ?? '');

            case 'ban_permanente':
                return $this->apply_sanction($autor_id, 'ban_permanente', null, $datos['motivo'] ?? '');

            case 'desbloquear':
                return $this->remove_sanction($autor_id);

            case 'silenciar':
                $duracion_dias = intval($datos['duracion_dias'] ?? 7);
                return $this->apply_sanction($autor_id, 'silenciado', $duracion_dias, $datos['motivo'] ?? '');

            case 'quitar_silencio':
                return $this->remove_sanction($autor_id, 'silenciado');
        }

        return true;
    }

    // =========================================================================
    // GESTIÓN DE SANCIONES
    // =========================================================================

    /**
     * Aplicar sanción a usuario
     */
    public function apply_sanction($usuario_id, $tipo, $duracion_dias = null, $motivo = '') {
        global $wpdb;
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';

        // Verificar si ya tiene sanción activa del mismo tipo
        $sancion_existente = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_sanciones}
             WHERE usuario_id = %d AND tipo = %s AND estado = 'activa'",
            $usuario_id,
            $tipo
        ));

        if ($sancion_existente) {
            // Actualizar sanción existente
            $fecha_expiracion = $duracion_dias
                ? date('Y-m-d H:i:s', strtotime("+{$duracion_dias} days"))
                : null;

            $wpdb->update(
                $tabla_sanciones,
                [
                    'fecha_expiracion' => $fecha_expiracion,
                    'motivo' => sanitize_textarea_field($motivo),
                    'aplicada_por' => get_current_user_id(),
                    'fecha_actualizacion' => current_time('mysql')
                ],
                ['id' => $sancion_existente],
                ['%s', '%s', '%d', '%s'],
                ['%d']
            );

            $sancion_id = $sancion_existente;
        } else {
            // Crear nueva sanción
            $fecha_expiracion = $duracion_dias
                ? date('Y-m-d H:i:s', strtotime("+{$duracion_dias} days"))
                : null;

            $wpdb->insert(
                $tabla_sanciones,
                [
                    'usuario_id' => $usuario_id,
                    'tipo' => $tipo,
                    'motivo' => sanitize_textarea_field($motivo),
                    'fecha_expiracion' => $fecha_expiracion,
                    'aplicada_por' => get_current_user_id(),
                    'estado' => 'activa',
                    'fecha_creacion' => current_time('mysql')
                ],
                ['%d', '%s', '%s', '%s', '%d', '%s', '%s']
            );

            $sancion_id = $wpdb->insert_id;
        }

        // Aplicar meta de usuario para verificación rápida
        update_user_meta($usuario_id, 'flavor_sancion_activa', $tipo);
        update_user_meta($usuario_id, 'flavor_sancion_expira', $fecha_expiracion);

        // Notificar al usuario
        $this->notify_user_sanction($usuario_id, $tipo, $duracion_dias, $motivo);

        // Registrar acción
        $this->log_moderation_action([
            'tipo' => $tipo,
            'tipo_contenido' => 'perfil_usuario',
            'contenido_id' => $usuario_id,
            'usuario_afectado' => $usuario_id,
            'moderador_id' => get_current_user_id(),
            'notas' => $motivo
        ]);

        do_action('flavor_moderation_sanction_applied', $usuario_id, $tipo, $sancion_id);

        return $sancion_id;
    }

    /**
     * Remover sanción
     */
    public function remove_sanction($usuario_id, $tipo = null) {
        global $wpdb;
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';

        $where = ['usuario_id' => $usuario_id, 'estado' => 'activa'];
        $where_format = ['%d', '%s'];

        if ($tipo) {
            $where['tipo'] = $tipo;
            $where_format[] = '%s';
        }

        $wpdb->update(
            $tabla_sanciones,
            [
                'estado' => 'levantada',
                'levantada_por' => get_current_user_id(),
                'fecha_actualizacion' => current_time('mysql')
            ],
            $where,
            ['%s', '%d', '%s'],
            $where_format
        );

        // Limpiar meta de usuario
        delete_user_meta($usuario_id, 'flavor_sancion_activa');
        delete_user_meta($usuario_id, 'flavor_sancion_expira');

        // Registrar acción
        $this->log_moderation_action([
            'tipo' => 'desbloquear',
            'tipo_contenido' => 'perfil_usuario',
            'contenido_id' => $usuario_id,
            'usuario_afectado' => $usuario_id,
            'moderador_id' => get_current_user_id(),
            'notas' => 'Sanción levantada manualmente'
        ]);

        do_action('flavor_moderation_sanction_removed', $usuario_id);

        return true;
    }

    /**
     * Verificar sanciones de usuario actual
     */
    public function check_user_sanctions() {
        if (!is_user_logged_in()) {
            return;
        }

        $usuario_id = get_current_user_id();
        $sancion_activa = get_user_meta($usuario_id, 'flavor_sancion_activa', true);

        if (!$sancion_activa) {
            return;
        }

        $fecha_expiracion = get_user_meta($usuario_id, 'flavor_sancion_expira', true);

        // Si expiró, limpiar
        if ($fecha_expiracion && strtotime($fecha_expiracion) < time()) {
            $this->cleanup_user_expired_sanctions($usuario_id);
            return;
        }

        // Aplicar restricciones según tipo de sanción
        switch ($sancion_activa) {
            case 'ban_temporal':
            case 'ban_permanente':
                // Bloquear acceso completo a funcionalidades
                add_filter('flavor_user_can_post', '__return_false');
                add_filter('flavor_user_can_comment', '__return_false');
                add_filter('flavor_user_can_message', '__return_false');
                add_action('wp_footer', [$this, 'show_ban_notice']);
                break;

            case 'silenciado':
                // Solo bloquear publicación de contenido
                add_filter('flavor_user_can_post', '__return_false');
                add_filter('flavor_user_can_comment', '__return_false');
                break;
        }
    }

    /**
     * Mostrar aviso de baneo
     */
    public function show_ban_notice() {
        $usuario_id = get_current_user_id();
        $sancion_activa = get_user_meta($usuario_id, 'flavor_sancion_activa', true);
        $fecha_expiracion = get_user_meta($usuario_id, 'flavor_sancion_expira', true);

        $mensaje = '';
        if ($sancion_activa === 'ban_permanente') {
            $mensaje = 'Tu cuenta ha sido suspendida permanentemente por incumplir las normas de la comunidad.';
        } elseif ($fecha_expiracion) {
            $fecha_legible = date_i18n('d/m/Y H:i', strtotime($fecha_expiracion));
            $mensaje = "Tu cuenta está temporalmente suspendida hasta el {$fecha_legible}.";
        }

        if ($mensaje) {
            echo '<div id="flavor-ban-notice" style="position:fixed;bottom:0;left:0;right:0;background:#dc3545;color:#fff;padding:15px;text-align:center;z-index:99999;">';
            echo '<strong>Cuenta suspendida:</strong> ' . esc_html($mensaje);
            echo '</div>';
        }
    }

    /**
     * Enviar advertencia a usuario
     */
    public function send_warning($usuario_id, $mensaje) {
        global $wpdb;
        $tabla_warnings = $this->prefix . 'moderation_warnings';

        $wpdb->insert(
            $tabla_warnings,
            [
                'usuario_id' => $usuario_id,
                'mensaje' => sanitize_textarea_field($mensaje),
                'enviado_por' => get_current_user_id(),
                'leido' => 0,
                'fecha_creacion' => current_time('mysql')
            ],
            ['%d', '%s', '%d', '%d', '%s']
        );

        $warning_id = $wpdb->insert_id;

        // Incrementar contador de warnings
        $total_warnings = (int) get_user_meta($usuario_id, 'flavor_warnings_count', true);
        update_user_meta($usuario_id, 'flavor_warnings_count', $total_warnings + 1);

        // Notificar por email
        $usuario = get_userdata($usuario_id);
        if ($usuario) {
            $subject = '[' . get_bloginfo('name') . '] Has recibido una advertencia';
            $body = "Hola {$usuario->display_name},\n\n";
            $body .= "Has recibido una advertencia de los moderadores:\n\n";
            $body .= $mensaje . "\n\n";
            $body .= "Por favor, revisa las normas de la comunidad para evitar futuras sanciones.";

            wp_mail($usuario->user_email, $subject, $body);
        }

        // Auto-sanción si tiene muchos warnings
        if ($total_warnings + 1 >= 3) {
            $this->apply_sanction($usuario_id, 'ban_temporal', 3, 'Auto-suspensión por acumulación de advertencias');
        }

        do_action('flavor_moderation_warning_sent', $usuario_id, $warning_id);

        return $warning_id;
    }

    /**
     * Obtener historial de usuario
     */
    public function get_user_moderation_history($usuario_id) {
        global $wpdb;
        $tabla_acciones = $this->prefix . 'moderation_actions';
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';
        $tabla_warnings = $this->prefix . 'moderation_warnings';
        $tabla_reportes = $this->prefix . 'moderation_reports';

        // Acciones de moderación sobre su contenido
        $acciones = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla_acciones}
             WHERE usuario_afectado = %d
             ORDER BY fecha_creacion DESC
             LIMIT 50",
            $usuario_id
        ), ARRAY_A);

        // Sanciones
        $sanciones = $wpdb->get_results($wpdb->prepare(
            "SELECT s.*, u.display_name as aplicada_por_nombre
             FROM {$tabla_sanciones} s
             LEFT JOIN {$wpdb->users} u ON s.aplicada_por = u.ID
             WHERE s.usuario_id = %d
             ORDER BY s.fecha_creacion DESC",
            $usuario_id
        ), ARRAY_A);

        // Warnings
        $warnings = $wpdb->get_results($wpdb->prepare(
            "SELECT w.*, u.display_name as enviado_por_nombre
             FROM {$tabla_warnings} w
             LEFT JOIN {$wpdb->users} u ON w.enviado_por = u.ID
             WHERE w.usuario_id = %d
             ORDER BY w.fecha_creacion DESC",
            $usuario_id
        ), ARRAY_A);

        // Reportes recibidos
        $reportes = $wpdb->get_results($wpdb->prepare(
            "SELECT COUNT(*) as total,
                    SUM(CASE WHEN estado = 'accion_tomada' THEN 1 ELSE 0 END) as confirmados,
                    SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes
             FROM {$tabla_reportes}
             WHERE autor_contenido_id = %d",
            $usuario_id
        ), ARRAY_A);

        // Estadísticas
        $stats = [
            'total_warnings' => count($warnings),
            'total_sanciones' => count($sanciones),
            'sancion_activa' => get_user_meta($usuario_id, 'flavor_sancion_activa', true),
            'reportes_recibidos' => $reportes[0] ?? ['total' => 0, 'confirmados' => 0, 'pendientes' => 0]
        ];

        return [
            'acciones' => $acciones,
            'sanciones' => $sanciones,
            'warnings' => $warnings,
            'stats' => $stats
        ];
    }

    // =========================================================================
    // REGISTRO DE ACCIONES
    // =========================================================================

    /**
     * Registrar acción de moderación
     */
    public function log_moderation_action($datos) {
        global $wpdb;
        $tabla_acciones = $this->prefix . 'moderation_actions';

        return $wpdb->insert(
            $tabla_acciones,
            [
                'tipo_accion' => $datos['tipo'],
                'tipo_contenido' => $datos['tipo_contenido'],
                'contenido_id' => $datos['contenido_id'],
                'usuario_afectado' => $datos['usuario_afectado'],
                'moderador_id' => $datos['moderador_id'],
                'reporte_id' => $datos['reporte_id'] ?? null,
                'notas' => $datos['notas'] ?? '',
                'metadata' => wp_json_encode($datos['metadata'] ?? []),
                'fecha_creacion' => current_time('mysql')
            ],
            ['%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s', '%s']
        );
    }

    // =========================================================================
    // ESTADÍSTICAS
    // =========================================================================

    /**
     * Obtener estadísticas de moderación
     */
    public function get_stats($periodo = 'semana') {
        global $wpdb;
        $tabla_reportes = $this->prefix . 'moderation_reports';
        $tabla_acciones = $this->prefix . 'moderation_actions';
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';

        // Determinar fecha de inicio según periodo
        switch ($periodo) {
            case 'dia':
                $fecha_inicio = date('Y-m-d 00:00:00');
                break;
            case 'semana':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-7 days'));
                break;
            case 'mes':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-30 days'));
                break;
            case 'ano':
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-365 days'));
                break;
            default:
                $fecha_inicio = date('Y-m-d 00:00:00', strtotime('-7 days'));
        }

        // Reportes
        $reportes_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes,
                SUM(CASE WHEN estado = 'en_revision' THEN 1 ELSE 0 END) as en_revision,
                SUM(CASE WHEN estado = 'accion_tomada' THEN 1 ELSE 0 END) as accion_tomada,
                SUM(CASE WHEN estado = 'rechazado' THEN 1 ELSE 0 END) as rechazados,
                SUM(CASE WHEN severidad = 'critica' THEN 1 ELSE 0 END) as criticos,
                SUM(CASE WHEN severidad = 'alta' THEN 1 ELSE 0 END) as alta_prioridad
             FROM {$tabla_reportes}
             WHERE fecha_creacion >= %s",
            $fecha_inicio
        ), ARRAY_A);

        // Reportes por tipo
        $reportes_por_tipo = $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_contenido, COUNT(*) as total
             FROM {$tabla_reportes}
             WHERE fecha_creacion >= %s
             GROUP BY tipo_contenido
             ORDER BY total DESC",
            $fecha_inicio
        ), ARRAY_A);

        // Reportes por motivo
        $reportes_por_motivo = $wpdb->get_results($wpdb->prepare(
            "SELECT motivo, COUNT(*) as total
             FROM {$tabla_reportes}
             WHERE fecha_creacion >= %s
             GROUP BY motivo
             ORDER BY total DESC",
            $fecha_inicio
        ), ARRAY_A);

        // Acciones de moderadores
        $acciones_moderadores = $wpdb->get_results($wpdb->prepare(
            "SELECT a.moderador_id, u.display_name, COUNT(*) as total_acciones
             FROM {$tabla_acciones} a
             LEFT JOIN {$wpdb->users} u ON a.moderador_id = u.ID
             WHERE a.fecha_creacion >= %s
             GROUP BY a.moderador_id
             ORDER BY total_acciones DESC
             LIMIT 10",
            $fecha_inicio
        ), ARRAY_A);

        // Sanciones aplicadas
        $sanciones_stats = $wpdb->get_row($wpdb->prepare(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN tipo = 'ban_temporal' THEN 1 ELSE 0 END) as bans_temporales,
                SUM(CASE WHEN tipo = 'ban_permanente' THEN 1 ELSE 0 END) as bans_permanentes,
                SUM(CASE WHEN tipo = 'silenciado' THEN 1 ELSE 0 END) as silenciados,
                SUM(CASE WHEN estado = 'activa' THEN 1 ELSE 0 END) as activas
             FROM {$tabla_sanciones}
             WHERE fecha_creacion >= %s",
            $fecha_inicio
        ), ARRAY_A);

        // Tiempo promedio de resolución
        $tiempo_resolucion = $wpdb->get_var($wpdb->prepare(
            "SELECT AVG(TIMESTAMPDIFF(HOUR, fecha_creacion, fecha_procesado))
             FROM {$tabla_reportes}
             WHERE fecha_procesado IS NOT NULL
             AND fecha_creacion >= %s",
            $fecha_inicio
        ));

        // Tendencia diaria
        $tendencia = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(fecha_creacion) as fecha, COUNT(*) as total
             FROM {$tabla_reportes}
             WHERE fecha_creacion >= %s
             GROUP BY DATE(fecha_creacion)
             ORDER BY fecha ASC",
            $fecha_inicio
        ), ARRAY_A);

        // Usuarios más reportados
        $usuarios_mas_reportados = $wpdb->get_results($wpdb->prepare(
            "SELECT r.autor_contenido_id, u.display_name, COUNT(*) as total_reportes,
                    SUM(CASE WHEN r.estado = 'accion_tomada' THEN 1 ELSE 0 END) as confirmados
             FROM {$tabla_reportes} r
             LEFT JOIN {$wpdb->users} u ON r.autor_contenido_id = u.ID
             WHERE r.fecha_creacion >= %s
             AND r.autor_contenido_id IS NOT NULL
             GROUP BY r.autor_contenido_id
             ORDER BY total_reportes DESC
             LIMIT 10",
            $fecha_inicio
        ), ARRAY_A);

        return [
            'reportes' => $reportes_stats,
            'reportes_por_tipo' => $reportes_por_tipo,
            'reportes_por_motivo' => $reportes_por_motivo,
            'acciones_moderadores' => $acciones_moderadores,
            'sanciones' => $sanciones_stats,
            'tiempo_resolucion_horas' => round($tiempo_resolucion, 1),
            'tendencia' => $tendencia,
            'usuarios_mas_reportados' => $usuarios_mas_reportados,
            'periodo' => $periodo
        ];
    }

    // =========================================================================
    // UTILIDADES
    // =========================================================================

    /**
     * Obtener autor del contenido
     */
    private function get_content_author($tipo_contenido, $contenido_id) {
        global $wpdb;

        $tipo_info = self::CONTENT_TYPES[$tipo_contenido] ?? null;
        if (!$tipo_info || !$tipo_info['table'] || !$tipo_info['author_field']) {
            return null;
        }

        $tabla = $this->prefix . $tipo_info['table'];
        return $wpdb->get_var($wpdb->prepare(
            "SELECT {$tipo_info['author_field']} FROM {$tabla} WHERE {$tipo_info['id_field']} = %d",
            $contenido_id
        ));
    }

    /**
     * Obtener preview del contenido
     */
    private function get_content_preview($tipo_contenido, $contenido_id) {
        global $wpdb;

        $tipo_info = self::CONTENT_TYPES[$tipo_contenido] ?? null;
        if (!$tipo_info || !$tipo_info['table']) {
            return ['preview' => 'Contenido no disponible', 'existe' => false];
        }

        if ($tipo_contenido === 'perfil_usuario') {
            $user = get_userdata($contenido_id);
            return [
                'preview' => $user ? $user->display_name : 'Usuario eliminado',
                'existe' => (bool) $user,
                'avatar' => $user ? get_avatar_url($contenido_id, ['size' => 50]) : ''
            ];
        }

        $tabla = $this->prefix . $tipo_info['table'];
        $contenido_field = $tipo_info['content_field'] ?? 'contenido';

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE {$tipo_info['id_field']} = %d",
            $contenido_id
        ), ARRAY_A);

        if (!$row) {
            return ['preview' => 'Contenido eliminado', 'existe' => false];
        }

        $preview = '';
        if (isset($row[$contenido_field])) {
            $preview = wp_trim_words(strip_tags($row[$contenido_field]), 30);
        } elseif (isset($row['titulo'])) {
            $preview = $row['titulo'];
        } elseif (isset($row['nombre'])) {
            $preview = $row['nombre'];
        }

        return [
            'preview' => $preview,
            'existe' => true,
            'datos' => $row,
            'estado' => $row[$tipo_info['status_field']] ?? null
        ];
    }

    /**
     * Incrementar contador de reportes
     */
    private function increment_report_count($tipo_contenido, $contenido_id) {
        global $wpdb;
        $tabla_contadores = $this->prefix . 'moderation_report_counts';

        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_contadores}
             WHERE tipo_contenido = %s AND contenido_id = %d",
            $tipo_contenido,
            $contenido_id
        ));

        if ($existe) {
            $wpdb->query($wpdb->prepare(
                "UPDATE {$tabla_contadores}
                 SET total_reportes = total_reportes + 1, fecha_ultimo = NOW()
                 WHERE tipo_contenido = %s AND contenido_id = %d",
                $tipo_contenido,
                $contenido_id
            ));
        } else {
            $wpdb->insert(
                $tabla_contadores,
                [
                    'tipo_contenido' => $tipo_contenido,
                    'contenido_id' => $contenido_id,
                    'total_reportes' => 1,
                    'fecha_ultimo' => current_time('mysql')
                ],
                ['%s', '%d', '%d', '%s']
            );
        }
    }

    /**
     * Verificar auto-ocultación por exceso de reportes
     */
    private function check_auto_hide($tipo_contenido, $contenido_id) {
        global $wpdb;
        $tabla_contadores = $this->prefix . 'moderation_report_counts';

        $total = $wpdb->get_var($wpdb->prepare(
            "SELECT total_reportes FROM {$tabla_contadores}
             WHERE tipo_contenido = %s AND contenido_id = %d",
            $tipo_contenido,
            $contenido_id
        ));

        // Auto-ocultar si tiene 5 o más reportes
        $umbral_auto_hide = apply_filters('flavor_moderation_auto_hide_threshold', 5);

        if ($total >= $umbral_auto_hide) {
            $tipo_info = self::CONTENT_TYPES[$tipo_contenido] ?? null;
            if ($tipo_info && $tipo_info['table'] && $tipo_info['status_field']) {
                $tabla = $this->prefix . $tipo_info['table'];
                $wpdb->update(
                    $tabla,
                    [$tipo_info['status_field'] => 'moderacion'],
                    [$tipo_info['id_field'] => $contenido_id],
                    ['%s'],
                    ['%d']
                );

                // Registrar acción automática
                $this->log_moderation_action([
                    'tipo' => 'auto_hide',
                    'tipo_contenido' => $tipo_contenido,
                    'contenido_id' => $contenido_id,
                    'usuario_afectado' => $this->get_content_author($tipo_contenido, $contenido_id),
                    'moderador_id' => 0, // Sistema
                    'notas' => "Auto-ocultado por alcanzar {$total} reportes"
                ]);
            }
        }
    }

    /**
     * Marcar reportes similares como duplicados
     */
    private function mark_similar_reports_as_duplicates($reporte_procesado) {
        global $wpdb;
        $tabla_reportes = $this->prefix . 'moderation_reports';

        $wpdb->update(
            $tabla_reportes,
            ['estado' => 'duplicado'],
            [
                'tipo_contenido' => $reporte_procesado->tipo_contenido,
                'contenido_id' => $reporte_procesado->contenido_id,
                'estado' => 'pendiente'
            ],
            ['%s'],
            ['%s', '%d', '%s']
        );
    }

    /**
     * Limpiar sanciones expiradas
     */
    public function cleanup_expired_sanctions() {
        global $wpdb;
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';

        // Obtener sanciones expiradas
        $expiradas = $wpdb->get_col(
            "SELECT usuario_id FROM {$tabla_sanciones}
             WHERE estado = 'activa'
             AND fecha_expiracion IS NOT NULL
             AND fecha_expiracion < NOW()"
        );

        if (empty($expiradas)) {
            return;
        }

        // Marcar como expiradas
        $wpdb->query(
            "UPDATE {$tabla_sanciones}
             SET estado = 'expirada', fecha_actualizacion = NOW()
             WHERE estado = 'activa'
             AND fecha_expiracion IS NOT NULL
             AND fecha_expiracion < NOW()"
        );

        // Limpiar meta de usuarios
        foreach ($expiradas as $usuario_id) {
            $this->cleanup_user_expired_sanctions($usuario_id);
        }
    }

    /**
     * Limpiar meta de usuario con sanción expirada
     */
    private function cleanup_user_expired_sanctions($usuario_id) {
        global $wpdb;
        $tabla_sanciones = $this->prefix . 'moderation_sanctions';

        // Verificar si tiene alguna sanción activa
        $tiene_activa = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$tabla_sanciones}
             WHERE usuario_id = %d
             AND estado = 'activa'
             AND (fecha_expiracion IS NULL OR fecha_expiracion > NOW())",
            $usuario_id
        ));

        if (!$tiene_activa) {
            delete_user_meta($usuario_id, 'flavor_sancion_activa');
            delete_user_meta($usuario_id, 'flavor_sancion_expira');
        }
    }

    /**
     * Obtener IP del usuario
     */
    private function get_user_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = sanitize_text_field($_SERVER[$key]);
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    // =========================================================================
    // NOTIFICACIONES
    // =========================================================================

    /**
     * Notificar a moderadores sobre nuevo reporte
     */
    private function notify_moderators_new_report($reporte_id) {
        $moderadores = get_users([
            'role__in' => ['administrator', 'editor'],
            'fields' => ['ID', 'user_email', 'display_name']
        ]);

        if (empty($moderadores)) {
            return;
        }

        $admin_url = admin_url('admin.php?page=flavor-moderation&reporte_id=' . $reporte_id);

        foreach ($moderadores as $mod) {
            $subject = '[' . get_bloginfo('name') . '] Nuevo reporte de alta prioridad';
            $body = "Hola {$mod->display_name},\n\n";
            $body .= "Se ha recibido un nuevo reporte de contenido que requiere atención.\n\n";
            $body .= "Revisar reporte: {$admin_url}\n\n";
            $body .= "Este es un mensaje automático del sistema de moderación.";

            wp_mail($mod->user_email, $subject, $body);
        }
    }

    /**
     * Notificar a usuario sobre sanción
     */
    private function notify_user_sanction($usuario_id, $tipo, $duracion_dias, $motivo) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return;
        }

        $tipo_legible = [
            'ban_temporal' => 'suspensión temporal',
            'ban_permanente' => 'suspensión permanente',
            'silenciado' => 'silenciamiento'
        ][$tipo] ?? $tipo;

        $subject = '[' . get_bloginfo('name') . '] Tu cuenta ha sido sancionada';
        $body = "Hola {$usuario->display_name},\n\n";
        $body .= "Tu cuenta ha recibido una {$tipo_legible}";

        if ($duracion_dias) {
            $body .= " por {$duracion_dias} días";
        }

        $body .= ".\n\n";

        if ($motivo) {
            $body .= "Motivo: {$motivo}\n\n";
        }

        $body .= "Si crees que esto es un error, puedes contactar con nosotros respondiendo a este email.";

        wp_mail($usuario->user_email, $subject, $body);
    }

    /**
     * Notificar a usuario sobre contenido eliminado
     */
    private function notify_user_content_removed($usuario_id, $tipo_contenido, $motivo) {
        $usuario = get_userdata($usuario_id);
        if (!$usuario) {
            return;
        }

        $tipo_info = self::CONTENT_TYPES[$tipo_contenido] ?? null;
        $tipo_legible = $tipo_info ? strtolower($tipo_info['label']) : 'contenido';

        $subject = '[' . get_bloginfo('name') . '] Tu contenido ha sido eliminado';
        $body = "Hola {$usuario->display_name},\n\n";
        $body .= "Tu {$tipo_legible} ha sido eliminado por incumplir las normas de la comunidad.\n\n";

        if ($motivo) {
            $body .= "Motivo: {$motivo}\n\n";
        }

        $body .= "Por favor, revisa las normas de la comunidad para evitar futuras incidencias.";

        wp_mail($usuario->user_email, $subject, $body);
    }

    // =========================================================================
    // ADMIN
    // =========================================================================

    /**
     * Añadir menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-platform',
            'Moderación',
            'Moderación',
            'edit_posts',
            'flavor-moderation',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Enqueue assets de admin
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'flavor-moderation') === false) {
            return;
        }

        wp_enqueue_style(
            'flavor-moderation-admin',
            FLAVOR_CHAT_IA_URL . 'includes/moderation/assets/moderation-admin.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-moderation-admin',
            FLAVOR_CHAT_IA_URL . 'includes/moderation/assets/moderation-admin.js',
            ['jquery', 'wp-util'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        // Chart.js para estadísticas
        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js',
            [],
            '4.4.0',
            true
        );

        wp_localize_script('flavor-moderation-admin', 'flavorModeration', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('flavor_moderation_nonce'),
            'reportReasons' => self::REPORT_REASONS,
            'contentTypes' => self::CONTENT_TYPES,
            'actionTypes' => self::ACTION_TYPES,
            'strings' => [
                'confirmAction' => '¿Estás seguro de realizar esta acción?',
                'confirmBulk' => '¿Aplicar esta acción a los elementos seleccionados?',
                'processing' => 'Procesando...',
                'success' => 'Acción completada',
                'error' => 'Error al procesar la solicitud'
            ]
        ]);
    }

    /**
     * Renderizar página de admin
     */
    public function render_admin_page() {
        $tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'cola';
        include FLAVOR_CHAT_IA_PATH . 'includes/moderation/views/admin-moderation.php';
    }

    /**
     * Añadir columna en lista de usuarios
     */
    public function add_user_moderation_column($columns) {
        $columns['flavor_moderation'] = 'Estado';
        return $columns;
    }

    /**
     * Renderizar columna de moderación
     */
    public function render_user_moderation_column($value, $column_name, $user_id) {
        if ($column_name !== 'flavor_moderation') {
            return $value;
        }

        $sancion = get_user_meta($user_id, 'flavor_sancion_activa', true);
        $warnings = (int) get_user_meta($user_id, 'flavor_warnings_count', true);

        $output = '';

        if ($sancion) {
            $expira = get_user_meta($user_id, 'flavor_sancion_expira', true);
            $label = [
                'ban_temporal' => 'Suspendido',
                'ban_permanente' => 'Baneado',
                'silenciado' => 'Silenciado'
            ][$sancion] ?? $sancion;

            $output .= '<span class="flavor-user-badge danger">' . esc_html($label) . '</span>';

            if ($expira) {
                $output .= '<br><small>Hasta: ' . date_i18n('d/m/Y', strtotime($expira)) . '</small>';
            }
        } elseif ($warnings > 0) {
            $output .= '<span class="flavor-user-badge warning">' . $warnings . ' warnings</span>';
        } else {
            $output .= '<span class="flavor-user-badge success">OK</span>';
        }

        return $output;
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * Registrar rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor-app/v1';

        // Reportar contenido (usuarios)
        register_rest_route($namespace, '/moderation/report', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_create_report'],
            'permission_callback' => function() {
                return is_user_logged_in();
            }
        ]);

        // Obtener reportes (moderadores)
        register_rest_route($namespace, '/moderation/reports', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_reports'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        // Procesar reporte (moderadores)
        register_rest_route($namespace, '/moderation/reports/(?P<id>\d+)/process', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_process_report'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);

        // Estadísticas
        register_rest_route($namespace, '/moderation/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_stats'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }

    /**
     * REST: Crear reporte
     */
    public function rest_create_report(WP_REST_Request $request) {
        $resultado = $this->create_report([
            'tipo_contenido' => $request->get_param('tipo_contenido'),
            'contenido_id' => $request->get_param('contenido_id'),
            'motivo' => $request->get_param('motivo'),
            'descripcion' => $request->get_param('descripcion'),
            'reportado_por' => get_current_user_id(),
            'url_referencia' => $request->get_param('url_referencia')
        ]);

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reporte enviado. Gracias por ayudar a mantener la comunidad segura.',
            'reporte_id' => $resultado
        ], 200);
    }

    /**
     * REST: Obtener reportes
     */
    public function rest_get_reports(WP_REST_Request $request) {
        $reportes = $this->get_reports([
            'estado' => $request->get_param('estado'),
            'tipo_contenido' => $request->get_param('tipo_contenido'),
            'motivo' => $request->get_param('motivo'),
            'severidad' => $request->get_param('severidad'),
            'pagina' => $request->get_param('pagina'),
            'por_pagina' => $request->get_param('por_pagina')
        ]);

        return new WP_REST_Response($reportes, 200);
    }

    /**
     * REST: Procesar reporte
     */
    public function rest_process_report(WP_REST_Request $request) {
        $resultado = $this->process_report(
            $request->get_param('id'),
            $request->get_param('accion'),
            [
                'notas' => $request->get_param('notas'),
                'duracion_dias' => $request->get_param('duracion_dias'),
                'mensaje' => $request->get_param('mensaje'),
                'motivo' => $request->get_param('motivo')
            ]
        );

        if (is_wp_error($resultado)) {
            return new WP_REST_Response([
                'success' => false,
                'message' => $resultado->get_error_message()
            ], 400);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => 'Reporte procesado correctamente'
        ], 200);
    }

    /**
     * REST: Obtener estadísticas
     */
    public function rest_get_stats(WP_REST_Request $request) {
        $stats = $this->get_stats($request->get_param('periodo') ?? 'semana');
        return new WP_REST_Response($stats, 200);
    }

    // =========================================================================
    // AJAX HANDLERS
    // =========================================================================

    /**
     * AJAX: Usuario reporta contenido
     */
    public function ajax_user_report_content() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'Debes iniciar sesión para reportar contenido']);
        }

        $resultado = $this->create_report([
            'tipo_contenido' => sanitize_key($_POST['tipo_contenido'] ?? ''),
            'contenido_id' => intval($_POST['contenido_id'] ?? 0),
            'motivo' => sanitize_key($_POST['motivo'] ?? ''),
            'descripcion' => sanitize_textarea_field($_POST['descripcion'] ?? ''),
            'reportado_por' => get_current_user_id(),
            'url_referencia' => sanitize_url($_POST['url_referencia'] ?? '')
        ]);

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success([
            'message' => 'Reporte enviado. Gracias por ayudar a mantener la comunidad segura.',
            'reporte_id' => $resultado
        ]);
    }

    /**
     * AJAX: Obtener reportes
     */
    public function ajax_get_reports() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $reportes = $this->get_reports($_POST);
        wp_send_json_success($reportes);
    }

    /**
     * AJAX: Procesar reporte
     */
    public function ajax_process_report() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $resultado = $this->process_report(
            intval($_POST['reporte_id'] ?? 0),
            sanitize_key($_POST['accion'] ?? ''),
            [
                'notas' => sanitize_textarea_field($_POST['notas'] ?? ''),
                'duracion_dias' => intval($_POST['duracion_dias'] ?? 7),
                'mensaje' => sanitize_textarea_field($_POST['mensaje'] ?? ''),
                'motivo' => sanitize_textarea_field($_POST['motivo'] ?? ''),
                'motivo_notificacion' => sanitize_textarea_field($_POST['motivo_notificacion'] ?? '')
            ]
        );

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Reporte procesado correctamente']);
    }

    /**
     * AJAX: Acción masiva
     */
    public function ajax_bulk_action() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $ids = array_map('intval', $_POST['ids'] ?? []);
        $accion = sanitize_key($_POST['accion'] ?? '');

        if (empty($ids) || empty($accion)) {
            wp_send_json_error(['message' => 'Datos incompletos']);
        }

        $exitos = 0;
        $errores = 0;

        foreach ($ids as $id) {
            $resultado = $this->process_report($id, $accion, [
                'notas' => 'Acción masiva'
            ]);

            if (is_wp_error($resultado)) {
                $errores++;
            } else {
                $exitos++;
            }
        }

        wp_send_json_success([
            'message' => "Procesados: {$exitos} exitosos, {$errores} errores"
        ]);
    }

    /**
     * AJAX: Obtener historial de usuario
     */
    public function ajax_get_user_history() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $usuario_id = intval($_POST['usuario_id'] ?? 0);
        if (!$usuario_id) {
            wp_send_json_error(['message' => 'Usuario no especificado']);
        }

        $historial = $this->get_user_moderation_history($usuario_id);
        $usuario = get_userdata($usuario_id);

        wp_send_json_success([
            'usuario' => [
                'id' => $usuario_id,
                'nombre' => $usuario ? $usuario->display_name : 'Usuario desconocido',
                'email' => $usuario ? $usuario->user_email : '',
                'avatar' => get_avatar_url($usuario_id, ['size' => 80]),
                'registrado' => $usuario ? $usuario->user_registered : ''
            ],
            'historial' => $historial
        ]);
    }

    /**
     * AJAX: Aplicar sanción
     */
    public function ajax_apply_sanction() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $usuario_id = intval($_POST['usuario_id'] ?? 0);
        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $duracion = intval($_POST['duracion_dias'] ?? 7);
        $motivo = sanitize_textarea_field($_POST['motivo'] ?? '');

        if (!$usuario_id || !$tipo) {
            wp_send_json_error(['message' => 'Datos incompletos']);
        }

        if ($tipo === 'desbloquear') {
            $resultado = $this->remove_sanction($usuario_id);
        } else {
            $resultado = $this->apply_sanction($usuario_id, $tipo, $duracion, $motivo);
        }

        if (is_wp_error($resultado)) {
            wp_send_json_error(['message' => $resultado->get_error_message()]);
        }

        wp_send_json_success(['message' => 'Sanción aplicada correctamente']);
    }

    /**
     * AJAX: Obtener estadísticas
     */
    public function ajax_get_stats() {
        check_ajax_referer('flavor_moderation_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $periodo = sanitize_key($_POST['periodo'] ?? 'semana');
        $stats = $this->get_stats($periodo);

        wp_send_json_success($stats);
    }
}
