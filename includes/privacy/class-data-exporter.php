<?php
/**
 * Exportador de datos de usuario para RGPD
 *
 * @package Flavor_Platform
 * @since 1.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Data_Exporter {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Prefijo de tablas
     */
    private $prefix;

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
    }

    /**
     * Obtener resumen de datos del usuario
     */
    public function get_user_data_summary($usuario_id) {
        global $wpdb;

        $resumen = [
            'perfil' => $this->get_profile_summary($usuario_id),
            'publicaciones' => $this->count_table_records('social_publicaciones', 'autor_id', $usuario_id),
            'comentarios' => $this->count_table_records('social_comentarios', 'autor_id', $usuario_id),
            'reacciones' => $this->count_table_records('social_reacciones', 'usuario_id', $usuario_id),
            'seguidores' => $this->count_table_records('social_seguimientos', 'seguido_id', $usuario_id),
            'siguiendo' => $this->count_table_records('social_seguimientos', 'seguidor_id', $usuario_id),
            'mensajes_enviados' => $this->count_table_records('mensajes', 'remitente_id', $usuario_id),
            'mensajes_recibidos' => $this->count_table_records('mensajes', 'destinatario_id', $usuario_id),
            'eventos_inscritos' => $this->count_table_records('eventos_inscripciones', 'usuario_id', $usuario_id),
            'cursos_inscritos' => $this->count_table_records('cursos_inscripciones', 'usuario_id', $usuario_id),
            'reservas' => $this->count_table_records('reservas', 'usuario_id', $usuario_id),
            'comunidades' => $this->count_table_records('comunidades_miembros', 'usuario_id', $usuario_id),
            'marketplace_articulos' => $this->count_table_records('marketplace', 'usuario_id', $usuario_id),
            'incidencias' => $this->count_table_records('incidencias', 'usuario_id', $usuario_id),
            'tramites' => $this->count_table_records('tramites', 'usuario_id', $usuario_id),
            'consentimientos' => $this->count_table_records('privacy_consents', 'usuario_id', $usuario_id),
            'fecha_registro' => $this->get_user_registration_date($usuario_id),
            'ultimo_acceso' => $this->get_user_last_login($usuario_id)
        ];

        return $resumen;
    }

    /**
     * Obtener resumen del perfil
     */
    private function get_profile_summary($usuario_id) {
        $user = get_userdata($usuario_id);
        if (!$user) {
            return null;
        }

        return [
            'nombre' => $user->display_name,
            'email' => $user->user_email,
            'usuario' => $user->user_login,
            'fecha_registro' => $user->user_registered
        ];
    }

    /**
     * Contar registros en una tabla
     */
    private function count_table_records($tabla, $campo, $usuario_id) {
        global $wpdb;
        $tabla_completa = $this->prefix . $tabla;

        // Verificar si la tabla existe
        $existe = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla_completa
        ));

        if (!$existe) {
            return 0;
        }

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM `{$tabla_completa}` WHERE `{$campo}` = %d",
            $usuario_id
        ));
    }

    /**
     * Obtener fecha de registro del usuario
     */
    private function get_user_registration_date($usuario_id) {
        $user = get_userdata($usuario_id);
        return $user ? $user->user_registered : null;
    }

    /**
     * Obtener último acceso del usuario
     */
    private function get_user_last_login($usuario_id) {
        return get_user_meta($usuario_id, 'last_login', true) ?: null;
    }

    /**
     * Obtener todos los datos exportables del usuario
     */
    public function get_exportable_data($usuario_id) {
        $datos = [
            'perfil' => $this->export_profile($usuario_id),
            'consentimientos' => $this->export_consents($usuario_id),
            'red_social' => [
                'publicaciones' => $this->export_publications($usuario_id),
                'comentarios' => $this->export_comments($usuario_id),
                'reacciones' => $this->export_reactions($usuario_id),
                'seguidores' => $this->export_followers($usuario_id),
                'siguiendo' => $this->export_following($usuario_id),
                'historias' => $this->export_stories($usuario_id),
                'guardados' => $this->export_saved_posts($usuario_id)
            ],
            'mensajes' => $this->export_messages($usuario_id),
            'notificaciones' => $this->export_notifications($usuario_id),
            'eventos' => $this->export_event_registrations($usuario_id),
            'cursos' => $this->export_course_enrollments($usuario_id),
            'reservas' => $this->export_reservations($usuario_id),
            'comunidades' => $this->export_community_memberships($usuario_id),
            'marketplace' => $this->export_marketplace_items($usuario_id),
            'banco_tiempo' => $this->export_time_bank($usuario_id),
            'reciclaje' => $this->export_recycling_points($usuario_id),
            'incidencias' => $this->export_incidents($usuario_id),
            'tramites' => $this->export_procedures($usuario_id),
            'foros' => $this->export_forum_posts($usuario_id),
            'carpooling' => $this->export_carpooling($usuario_id),
            'grupos_consumo' => $this->export_consumption_groups($usuario_id),
            'fichajes' => $this->export_time_tracking($usuario_id),
            'biblioteca' => $this->export_library_loans($usuario_id),
            'radio' => $this->export_radio_data($usuario_id),
            'huertos' => $this->export_garden_assignments($usuario_id),
            'colectivos' => $this->export_collective_memberships($usuario_id),
            'socio' => $this->export_membership_data($usuario_id),
            'facturas' => $this->export_invoices($usuario_id),
            'presupuestos' => $this->export_budget_proposals($usuario_id),
            'compostaje' => $this->export_composting($usuario_id),
            'ayuda_vecinal' => $this->export_neighbor_help($usuario_id),
            'recursos_compartidos' => $this->export_shared_resources($usuario_id),
            'bicicletas' => $this->export_bike_rentals($usuario_id),
            'encuestas' => $this->export_survey_responses($usuario_id),
            'chat_estados' => $this->export_chat_status($usuario_id),
            'reportes_moderacion' => $this->export_moderation_reports($usuario_id),
            'reputacion' => $this->export_reputation($usuario_id)
        ];

        // Filtrar datos vacíos
        return array_filter($datos, function($v) {
            return !empty($v);
        });
    }

    /**
     * Exportar datos completos del usuario a archivo ZIP
     */
    public function export_user_data($usuario_id) {
        $datos = $this->get_exportable_data($usuario_id);
        $user = get_userdata($usuario_id);

        if (!$user) {
            return new WP_Error('usuario_no_encontrado', 'Usuario no encontrado');
        }

        // Crear directorio temporal
        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/flavor-exports/';

        if (!file_exists($export_dir)) {
            wp_mkdir_p($export_dir);
            // Añadir .htaccess para proteger
            file_put_contents($export_dir . '.htaccess', 'deny from all');
        }

        $timestamp = date('Y-m-d_His');
        $temp_dir = $export_dir . 'temp_' . $usuario_id . '_' . $timestamp . '/';
        wp_mkdir_p($temp_dir);

        try {
            // Crear archivo de información general
            $info = [
                'exportado_para' => $user->display_name,
                'email' => $user->user_email,
                'fecha_exportacion' => current_time('c'),
                'sitio' => get_bloginfo('name'),
                'url_sitio' => home_url()
            ];
            file_put_contents(
                $temp_dir . 'info.json',
                wp_json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );

            // Crear archivo por cada categoría de datos
            foreach ($datos as $categoria => $contenido) {
                if (!empty($contenido)) {
                    $nombre_archivo = sanitize_file_name($categoria) . '.json';
                    file_put_contents(
                        $temp_dir . $nombre_archivo,
                        wp_json_encode($contenido, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
                    );
                }
            }

            // Exportar imágenes/archivos adjuntos
            $this->export_user_media($usuario_id, $temp_dir);

            // Crear archivo ZIP
            $zip_filename = 'export_' . $usuario_id . '_' . $timestamp . '.zip';
            $zip_path = $export_dir . $zip_filename;

            $zip = new ZipArchive();
            if ($zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new Exception('No se pudo crear el archivo ZIP');
            }

            // Añadir todos los archivos del directorio temporal
            $archivos = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($temp_dir),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($archivos as $archivo) {
                if (!$archivo->isDir()) {
                    $ruta_relativa = substr($archivo->getRealPath(), strlen($temp_dir));
                    $zip->addFile($archivo->getRealPath(), $ruta_relativa);
                }
            }

            $zip->close();

            // Limpiar directorio temporal
            $this->delete_directory($temp_dir);

            return [
                'archivo' => $zip_path,
                'nombre' => $zip_filename,
                'tamano' => filesize($zip_path)
            ];

        } catch (Exception $e) {
            // Limpiar en caso de error
            $this->delete_directory($temp_dir);
            return new WP_Error('export_error', $e->getMessage());
        }
    }

    /**
     * Exportar archivos multimedia del usuario
     */
    private function export_user_media($usuario_id, $temp_dir) {
        $media_dir = $temp_dir . 'media/';
        wp_mkdir_p($media_dir);

        // Obtener attachments del usuario
        $attachments = get_posts([
            'post_type' => 'attachment',
            'author' => $usuario_id,
            'posts_per_page' => -1,
            'post_status' => 'any'
        ]);

        $media_index = [];
        foreach ($attachments as $attachment) {
            $file_path = get_attached_file($attachment->ID);
            if ($file_path && file_exists($file_path)) {
                $filename = basename($file_path);
                $new_path = $media_dir . $attachment->ID . '_' . $filename;

                // Solo copiar si el archivo no es muy grande (< 50MB)
                if (filesize($file_path) < 50 * 1024 * 1024) {
                    copy($file_path, $new_path);
                    $media_index[] = [
                        'id' => $attachment->ID,
                        'nombre_original' => $filename,
                        'titulo' => $attachment->post_title,
                        'fecha_subida' => $attachment->post_date
                    ];
                }
            }
        }

        if (!empty($media_index)) {
            file_put_contents(
                $media_dir . 'index.json',
                wp_json_encode($media_index, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
            );
        }
    }

    /**
     * Eliminar directorio recursivamente
     */
    private function delete_directory($dir) {
        if (!is_dir($dir)) {
            return;
        }

        $archivos = array_diff(scandir($dir), ['.', '..']);
        foreach ($archivos as $archivo) {
            $ruta = $dir . '/' . $archivo;
            is_dir($ruta) ? $this->delete_directory($ruta) : unlink($ruta);
        }
        rmdir($dir);
    }

    // =========================================================================
    // MÉTODOS DE EXPORTACIÓN POR CATEGORÍA
    // =========================================================================

    /**
     * Exportar perfil
     */
    private function export_profile($usuario_id) {
        global $wpdb;

        $user = get_userdata($usuario_id);
        if (!$user) {
            return null;
        }

        $perfil = [
            'id' => $user->ID,
            'nombre_usuario' => $user->user_login,
            'email' => $user->user_email,
            'nombre_mostrado' => $user->display_name,
            'nombre' => $user->first_name,
            'apellidos' => $user->last_name,
            'descripcion' => $user->description,
            'url' => $user->user_url,
            'fecha_registro' => $user->user_registered,
            'roles' => $user->roles
        ];

        // Perfil social
        $tabla_perfil = $this->prefix . 'social_perfiles';
        $perfil_social = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$tabla_perfil} WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);

        if ($perfil_social) {
            $perfil['perfil_social'] = $perfil_social;
        }

        // User meta de Flavor
        $meta = $wpdb->get_results($wpdb->prepare(
            "SELECT meta_key, meta_value FROM {$wpdb->usermeta}
             WHERE user_id = %d AND meta_key LIKE %s",
            $usuario_id,
            'flavor_%'
        ), ARRAY_A);

        if ($meta) {
            $perfil['metadata'] = [];
            foreach ($meta as $m) {
                $perfil['metadata'][$m['meta_key']] = maybe_unserialize($m['meta_value']);
            }
        }

        return $perfil;
    }

    /**
     * Exportar consentimientos
     */
    private function export_consents($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'privacy_consents';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_consentimiento, consentido, fecha, ip_address
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar publicaciones
     */
    private function export_publications($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_publicaciones';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, contenido, tipo, adjuntos, visibilidad, ubicacion,
                    me_gusta, comentarios, compartidos, vistas,
                    created_at, updated_at
             FROM {$tabla}
             WHERE autor_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar comentarios
     */
    private function export_comments($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_comentarios';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, publicacion_id, comentario, likes, created_at
             FROM {$tabla}
             WHERE autor_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar reacciones
     */
    private function export_reactions($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_reacciones';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT tipo_reaccion, publicacion_id, comentario_id, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar seguidores
     */
    private function export_followers($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_seguimientos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        $seguidores = $wpdb->get_col($wpdb->prepare(
            "SELECT seguidor_id FROM {$tabla} WHERE seguido_id = %d",
            $usuario_id
        ));

        // Obtener nombres de usuarios
        $resultado = [];
        foreach ($seguidores as $seguidor_id) {
            $user = get_userdata($seguidor_id);
            $resultado[] = [
                'usuario_id' => $seguidor_id,
                'nombre' => $user ? $user->display_name : 'Usuario eliminado'
            ];
        }

        return $resultado;
    }

    /**
     * Exportar siguiendo
     */
    private function export_following($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_seguimientos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        $siguiendo = $wpdb->get_col($wpdb->prepare(
            "SELECT seguido_id FROM {$tabla} WHERE seguidor_id = %d",
            $usuario_id
        ));

        $resultado = [];
        foreach ($siguiendo as $seguido_id) {
            $user = get_userdata($seguido_id);
            $resultado[] = [
                'usuario_id' => $seguido_id,
                'nombre' => $user ? $user->display_name : 'Usuario eliminado'
            ];
        }

        return $resultado;
    }

    /**
     * Exportar historias
     */
    private function export_stories($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_historias';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, tipo, contenido_url, texto, color_fondo, vistas, created_at, expira_at
             FROM {$tabla}
             WHERE autor_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar publicaciones guardadas
     */
    private function export_saved_posts($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'social_guardados';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT publicacion_id, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar mensajes
     */
    private function export_messages($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'mensajes';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id,
                    CASE WHEN remitente_id = %d THEN 'enviado' ELSE 'recibido' END as tipo,
                    remitente_id, destinatario_id, asunto, contenido, leido, created_at
             FROM {$tabla}
             WHERE remitente_id = %d OR destinatario_id = %d
             ORDER BY created_at DESC",
            $usuario_id,
            $usuario_id,
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar notificaciones
     */
    private function export_notifications($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'notificaciones';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, mensaje, tipo, leida, url, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC
             LIMIT 500",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar inscripciones a eventos
     */
    private function export_event_registrations($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'eventos_inscripciones';
        $tabla_eventos = $this->prefix . 'eventos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.id, i.estado, i.fecha_inscripcion, e.titulo as evento
             FROM {$tabla} i
             LEFT JOIN {$tabla_eventos} e ON i.evento_id = e.id
             WHERE i.usuario_id = %d
             ORDER BY i.fecha_inscripcion DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar inscripciones a cursos
     */
    private function export_course_enrollments($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'cursos_inscripciones';
        $tabla_cursos = $this->prefix . 'cursos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT i.id, i.progreso, i.estado, i.created_at, c.titulo as curso
             FROM {$tabla} i
             LEFT JOIN {$tabla_cursos} c ON i.curso_id = c.id
             WHERE i.usuario_id = %d
             ORDER BY i.created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar reservas
     */
    private function export_reservations($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'reservas';
        $tabla_espacios = $this->prefix . 'espacios';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT r.id, r.fecha_inicio, r.fecha_fin, r.estado, r.notas,
                    r.created_at, e.nombre as espacio
             FROM {$tabla} r
             LEFT JOIN {$tabla_espacios} e ON r.espacio_id = e.id
             WHERE r.usuario_id = %d
             ORDER BY r.fecha_inicio DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar membresías de comunidades
     */
    private function export_community_memberships($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'comunidades_miembros';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT comunidad_id, rol, fecha_union
             FROM {$tabla}
             WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar artículos de marketplace
     */
    private function export_marketplace_items($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'marketplace';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, descripcion, precio, categoria, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar banco del tiempo
     */
    private function export_time_bank($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'banco_tiempo_saldo';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT horas, concepto, tipo, fecha
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar puntos de reciclaje
     */
    private function export_recycling_points($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'reciclaje_puntos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT puntos, concepto, fecha
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar incidencias
     */
    private function export_incidents($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'incidencias';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT numero_incidencia, titulo, descripcion, ubicacion,
                    categoria, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar trámites
     */
    private function export_procedures($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'tramites';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, titulo, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar posts de foros
     */
    private function export_forum_posts($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'foros_temas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT id, titulo, contenido, respuestas, vistas, estado, created_at
             FROM {$tabla}
             WHERE autor_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar viajes carpooling
     */
    private function export_carpooling($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'carpooling_viajes';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT origen, destino, fecha_salida, plazas_disponibles,
                    precio, estado, created_at
             FROM {$tabla}
             WHERE conductor_id = %d
             ORDER BY fecha_salida DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar membresías de grupos de consumo
     */
    private function export_consumption_groups($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'grupos_consumo_miembros';
        $tabla_grupos = $this->prefix . 'grupos_consumo';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.fecha_union, g.nombre as grupo
             FROM {$tabla} m
             LEFT JOIN {$tabla_grupos} g ON m.grupo_id = g.id
             WHERE m.usuario_id = %d",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar fichajes
     */
    private function export_time_tracking($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'fichajes';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT fecha, hora_entrada, hora_salida, notas
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar préstamos de biblioteca
     */
    private function export_library_loans($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'biblioteca_prestamos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT libro_titulo, fecha_prestamo, fecha_devolucion, estado
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha_prestamo DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar datos de radio
     */
    private function export_radio_data($usuario_id) {
        global $wpdb;

        $datos = [];

        // Dedicatorias
        $tabla = $this->prefix . 'radio_dedicatorias';
        if ($this->table_exists($tabla)) {
            $datos['dedicatorias'] = $wpdb->get_results($wpdb->prepare(
                "SELECT de_nombre, para_nombre, mensaje, cancion_titulo,
                        cancion_artista, estado, fecha_solicitud
                 FROM {$tabla}
                 WHERE usuario_id = %d
                 ORDER BY fecha_solicitud DESC",
                $usuario_id
            ), ARRAY_A);
        }

        // Chat
        $tabla = $this->prefix . 'radio_chat';
        if ($this->table_exists($tabla)) {
            $datos['chat'] = $wpdb->get_results($wpdb->prepare(
                "SELECT emision_id, mensaje, tipo, fecha
                 FROM {$tabla}
                 WHERE usuario_id = %d
                 ORDER BY fecha DESC
                 LIMIT 500",
                $usuario_id
            ), ARRAY_A);
        }

        // Propuestas
        $tabla = $this->prefix . 'radio_propuestas';
        if ($this->table_exists($tabla)) {
            $datos['propuestas'] = $wpdb->get_results($wpdb->prepare(
                "SELECT nombre_programa, descripcion, categoria, estado, fecha_solicitud
                 FROM {$tabla}
                 WHERE usuario_id = %d",
                $usuario_id
            ), ARRAY_A);
        }

        return $datos;
    }

    /**
     * Exportar asignaciones de huertos
     */
    private function export_garden_assignments($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'huertos_asignaciones';
        $tabla_parcelas = $this->prefix . 'huertos_parcelas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.fecha_inicio, a.fecha_fin, a.estado, p.nombre as parcela
             FROM {$tabla} a
             LEFT JOIN {$tabla_parcelas} p ON a.parcela_id = p.id
             WHERE a.usuario_id = %d",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar membresías de colectivos
     */
    private function export_collective_memberships($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'colectivos_miembros';
        $tabla_colectivos = $this->prefix . 'colectivos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT m.rol, m.fecha_union, c.nombre as colectivo
             FROM {$tabla} m
             LEFT JOIN {$tabla_colectivos} c ON m.colectivo_id = c.id
             WHERE m.usuario_id = %d",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar datos de socio
     */
    private function export_membership_data($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'socios';

        if (!$this->table_exists($tabla)) {
            return null;
        }

        return $wpdb->get_row($wpdb->prepare(
            "SELECT numero_socio, tipo, estado, fecha_alta, fecha_renovacion
             FROM {$tabla}
             WHERE usuario_id = %d",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar facturas
     */
    private function export_invoices($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'facturas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT numero_factura, concepto, total, estado, fecha
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar propuestas de presupuestos
     */
    private function export_budget_proposals($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'presupuestos_propuestas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, descripcion, presupuesto, votos, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar aportes de compostaje
     */
    private function export_composting($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'compostaje_aportes';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT cantidad_kg, tipo, fecha
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar ayuda vecinal
     */
    private function export_neighbor_help($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'ayuda_vecinal';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, descripcion, tipo, urgente, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar recursos compartidos
     */
    private function export_shared_resources($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'recursos_compartidos';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT titulo, descripcion, tipo, estado, created_at
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY created_at DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar alquileres de bicicletas
     */
    private function export_bike_rentals($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'bicicletas_alquileres';
        $tabla_bicis = $this->prefix . 'bicicletas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.fecha_inicio, a.fecha_fin, a.estado, b.codigo as bicicleta
             FROM {$tabla} a
             LEFT JOIN {$tabla_bicis} b ON a.bicicleta_id = b.id
             WHERE a.usuario_id = %d
             ORDER BY a.fecha_inicio DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar respuestas a encuestas
     */
    private function export_survey_responses($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'encuestas_respuestas';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT encuesta_id, respuesta, fecha_respuesta
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha_respuesta DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar estados de chat (tipo WhatsApp Status)
     */
    private function export_chat_status($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'chat_estados';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT tipo, contenido, duracion, vistas, fecha_creacion, fecha_expiracion
             FROM {$tabla}
             WHERE usuario_id = %d
             ORDER BY fecha_creacion DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar reportes de moderación realizados por el usuario
     */
    private function export_moderation_reports($usuario_id) {
        global $wpdb;
        $tabla = $this->prefix . 'moderation_reports';

        if (!$this->table_exists($tabla)) {
            return [];
        }

        return $wpdb->get_results($wpdb->prepare(
            "SELECT contenido_tipo, contenido_id, motivo, estado, fecha_reporte
             FROM {$tabla}
             WHERE reportador_id = %d
             ORDER BY fecha_reporte DESC",
            $usuario_id
        ), ARRAY_A);
    }

    /**
     * Exportar datos de reputación del usuario
     */
    private function export_reputation($usuario_id) {
        global $wpdb;
        $datos = [];

        // Reputación general
        $tabla_reputacion = $this->prefix . 'social_reputacion';
        if ($this->table_exists($tabla_reputacion)) {
            $reputacion = $wpdb->get_row($wpdb->prepare(
                "SELECT puntos_totales, nivel, puntos_semana, puntos_mes, racha_dias,
                        ultima_actividad, fecha_actualizacion
                 FROM {$tabla_reputacion}
                 WHERE usuario_id = %d",
                $usuario_id
            ), ARRAY_A);

            if ($reputacion) {
                $datos['resumen'] = $reputacion;
            }
        }

        // Badges obtenidos
        $tabla_usuario_badges = $this->prefix . 'social_usuario_badges';
        $tabla_badges = $this->prefix . 'social_badges';
        if ($this->table_exists($tabla_usuario_badges) && $this->table_exists($tabla_badges)) {
            $badges = $wpdb->get_results($wpdb->prepare(
                "SELECT b.nombre, b.descripcion, b.categoria, ub.fecha_obtenido
                 FROM {$tabla_usuario_badges} ub
                 INNER JOIN {$tabla_badges} b ON ub.badge_id = b.id
                 WHERE ub.usuario_id = %d
                 ORDER BY ub.fecha_obtenido DESC",
                $usuario_id
            ), ARRAY_A);

            if ($badges) {
                $datos['badges'] = $badges;
            }
        }

        // Historial de puntos (últimos 100)
        $tabla_historial = $this->prefix . 'social_historial_puntos';
        if ($this->table_exists($tabla_historial)) {
            $historial = $wpdb->get_results($wpdb->prepare(
                "SELECT puntos, tipo_accion, descripcion, fecha_creacion
                 FROM {$tabla_historial}
                 WHERE usuario_id = %d
                 ORDER BY fecha_creacion DESC
                 LIMIT 100",
                $usuario_id
            ), ARRAY_A);

            if ($historial) {
                $datos['historial_puntos'] = $historial;
            }
        }

        return $datos;
    }

    /**
     * Verificar si una tabla existe
     */
    private function table_exists($tabla) {
        global $wpdb;
        $nombre_tabla = strpos($tabla, $this->prefix) === 0 ? $tabla : $this->prefix . $tabla;
        return $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $nombre_tabla
        )) !== null;
    }
}
