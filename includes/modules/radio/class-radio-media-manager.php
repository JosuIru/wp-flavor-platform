<?php
/**
 * Radio Media Manager - Gestión de archivos de audio
 *
 * Sistema completo para subir, organizar y reproducir archivos de audio
 * sin necesidad de servidor de streaming externo.
 *
 * @package FlavorPlatform
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Radio_Media_Manager {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Directorio de uploads de radio
     */
    private $upload_dir;

    /**
     * URL base de uploads
     */
    private $upload_url;

    /**
     * Formatos de audio permitidos
     */
    private $allowed_formats = ['mp3', 'ogg', 'wav', 'm4a', 'aac'];

    /**
     * Tamaño máximo de archivo (100MB)
     */
    private $max_file_size = 104857600;

    /**
     * Constructor
     */
    private function __construct() {
        $this->setup_upload_directory();
        $this->init_hooks();
    }

    /**
     * Obtiene instancia singleton
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configura directorio de uploads
     */
    private function setup_upload_directory() {
        $upload = wp_upload_dir();
        $this->upload_dir = $upload['basedir'] . '/flavor-radio/';
        $this->upload_url = $upload['baseurl'] . '/flavor-radio/';

        // Crear directorios si no existen
        $subdirs = ['audios', 'podcasts', 'jingles', 'programas', 'temp'];
        foreach ($subdirs as $subdir) {
            $path = $this->upload_dir . $subdir;
            if (!file_exists($path)) {
                wp_mkdir_p($path);
                // Añadir .htaccess para proteger
                file_put_contents($path . '/.htaccess', "Options -Indexes\n");
            }
        }
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // AJAX handlers
        add_action('wp_ajax_flavor_radio_upload_audio', [$this, 'ajax_upload_audio']);
        add_action('wp_ajax_flavor_radio_delete_audio', [$this, 'ajax_delete_audio']);
        add_action('wp_ajax_flavor_radio_get_audio_library', [$this, 'ajax_get_library']);
        add_action('wp_ajax_flavor_radio_create_playlist', [$this, 'ajax_create_playlist']);
        add_action('wp_ajax_flavor_radio_get_playlist', [$this, 'ajax_get_playlist']);
        add_action('wp_ajax_nopriv_flavor_radio_get_playlist', [$this, 'ajax_get_playlist']);
        add_action('wp_ajax_flavor_radio_get_current_track', [$this, 'ajax_get_current_track']);
        add_action('wp_ajax_nopriv_flavor_radio_get_current_track', [$this, 'ajax_get_current_track']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // Crear tablas
        add_action('init', [$this, 'maybe_create_tables']);
    }

    /**
     * Crea tablas necesarias
     */
    public function maybe_create_tables() {
        global $wpdb;
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';

        if (Flavor_Platform_Helpers::tabla_existe($tabla_audios)) {
            return;
        }

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla de archivos de audio
        $sql_audios = "CREATE TABLE IF NOT EXISTS $tabla_audios (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            titulo varchar(255) NOT NULL,
            artista varchar(255) DEFAULT NULL,
            album varchar(255) DEFAULT NULL,
            archivo_url varchar(500) NOT NULL,
            archivo_path varchar(500) NOT NULL,
            duracion_segundos int(11) DEFAULT 0,
            tamano_bytes bigint(20) DEFAULT 0,
            formato varchar(10) DEFAULT 'mp3',
            tipo enum('cancion','podcast','jingle','cortina','programa','otro') DEFAULT 'cancion',
            programa_id bigint(20) unsigned DEFAULT NULL,
            categoria varchar(100) DEFAULT NULL,
            tags varchar(500) DEFAULT NULL,
            bpm int(11) DEFAULT NULL,
            waveform_data longtext DEFAULT NULL,
            reproducciones int(11) DEFAULT 0,
            subido_por bigint(20) unsigned NOT NULL,
            fecha_subida datetime DEFAULT CURRENT_TIMESTAMP,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY tipo (tipo),
            KEY programa_id (programa_id),
            KEY categoria (categoria),
            KEY activo (activo)
        ) $charset_collate;";

        // Tabla de playlists
        $tabla_playlists = $wpdb->prefix . 'flavor_radio_playlists';
        $sql_playlists = "CREATE TABLE IF NOT EXISTS $tabla_playlists (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            nombre varchar(255) NOT NULL,
            descripcion text DEFAULT NULL,
            tipo enum('manual','automatica','programa','horario') DEFAULT 'manual',
            programa_id bigint(20) unsigned DEFAULT NULL,
            audios_ids longtext DEFAULT NULL COMMENT 'JSON array de IDs',
            orden_reproduccion enum('secuencial','aleatorio','ponderado') DEFAULT 'secuencial',
            repetir tinyint(1) DEFAULT 1,
            activa tinyint(1) DEFAULT 1,
            creado_por bigint(20) unsigned NOT NULL,
            fecha_creacion datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY programa_id (programa_id),
            KEY activa (activa)
        ) $charset_collate;";

        // Tabla de programación automática
        $tabla_auto = $wpdb->prefix . 'flavor_radio_programacion_auto';
        $sql_auto = "CREATE TABLE IF NOT EXISTS $tabla_auto (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            dia_semana tinyint(1) NOT NULL COMMENT '1=Lunes, 7=Domingo',
            hora_inicio time NOT NULL,
            hora_fin time NOT NULL,
            tipo enum('playlist','programa','jingles','silencio') DEFAULT 'playlist',
            playlist_id bigint(20) unsigned DEFAULT NULL,
            programa_id bigint(20) unsigned DEFAULT NULL,
            prioridad int(11) DEFAULT 0,
            activo tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY dia_semana (dia_semana),
            KEY hora_inicio (hora_inicio),
            KEY activo (activo)
        ) $charset_collate;";

        // Tabla de historial de reproducción
        $tabla_historial = $wpdb->prefix . 'flavor_radio_historial';
        $sql_historial = "CREATE TABLE IF NOT EXISTS $tabla_historial (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            audio_id bigint(20) unsigned NOT NULL,
            playlist_id bigint(20) unsigned DEFAULT NULL,
            fecha_reproduccion datetime DEFAULT CURRENT_TIMESTAMP,
            oyentes_count int(11) DEFAULT 0,
            PRIMARY KEY (id),
            KEY audio_id (audio_id),
            KEY fecha_reproduccion (fecha_reproduccion)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_audios);
        dbDelta($sql_playlists);
        dbDelta($sql_auto);
        dbDelta($sql_historial);
    }

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        $namespace = 'flavor/v1';

        register_rest_route($namespace, '/radio/media/library', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_library'],
            'permission_callback' => function() {
                return current_user_can('upload_files');
            },
        ]);

        register_rest_route($namespace, '/radio/media/upload', [
            'methods' => 'POST',
            'callback' => [$this, 'rest_upload_audio'],
            'permission_callback' => function() {
                return current_user_can('upload_files');
            },
        ]);

        register_rest_route($namespace, '/radio/stream/current', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_current_track'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route($namespace, '/radio/playlists', [
            'methods' => 'GET',
            'callback' => [$this, 'rest_get_playlists'],
            'permission_callback' => '__return_true',
        ]);
    }

    // =========================================================================
    // SUBIDA DE ARCHIVOS
    // =========================================================================

    /**
     * AJAX: Subir archivo de audio
     */
    public function ajax_upload_audio() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('No tienes permisos para subir archivos', 'flavor-platform')]);
        }

        if (empty($_FILES['audio_file'])) {
            wp_send_json_error(['message' => __('No se recibió ningún archivo', 'flavor-platform')]);
        }

        $file = $_FILES['audio_file'];
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'cancion');
        $programa_id = absint($_POST['programa_id'] ?? 0);

        $result = $this->upload_audio_file($file, $tipo, $programa_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('Archivo subido correctamente', 'flavor-platform'),
            'audio' => $result,
        ]);
    }

    /**
     * Procesa la subida de un archivo de audio
     */
    public function upload_audio_file($file, $tipo = 'cancion', $programa_id = 0) {
        // Validar formato
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, $this->allowed_formats)) {
            return new WP_Error('invalid_format', __('Formato de audio no permitido', 'flavor-platform'));
        }

        // Validar tamaño
        if ($file['size'] > $this->max_file_size) {
            return new WP_Error('file_too_large', __('El archivo excede el tamaño máximo (100MB)', 'flavor-platform'));
        }

        // Determinar subdirectorio
        $subdir = $this->get_subdir_for_type($tipo);
        $target_dir = $this->upload_dir . $subdir . '/';

        // Generar nombre único
        $filename = sanitize_file_name($file['name']);
        $filename = wp_unique_filename($target_dir, $filename);
        $target_path = $target_dir . $filename;

        // Mover archivo
        if (!move_uploaded_file($file['tmp_name'], $target_path)) {
            return new WP_Error('upload_failed', __('Error al guardar el archivo', 'flavor-platform'));
        }

        // Obtener metadatos del audio
        $metadata = $this->extract_audio_metadata($target_path);

        // Guardar en base de datos
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_audios';

        $data = [
            'titulo' => $metadata['title'] ?: pathinfo($filename, PATHINFO_FILENAME),
            'artista' => $metadata['artist'] ?: '',
            'album' => $metadata['album'] ?: '',
            'archivo_url' => $this->upload_url . $subdir . '/' . $filename,
            'archivo_path' => $target_path,
            'duracion_segundos' => $metadata['duration'] ?: 0,
            'tamano_bytes' => filesize($target_path),
            'formato' => $extension,
            'tipo' => $tipo,
            'programa_id' => $programa_id ?: null,
            'subido_por' => get_current_user_id(),
            'fecha_subida' => current_time('mysql'),
        ];

        $wpdb->insert($tabla, $data);
        $audio_id = $wpdb->insert_id;

        return [
            'id' => $audio_id,
            'titulo' => $data['titulo'],
            'artista' => $data['artista'],
            'url' => $data['archivo_url'],
            'duracion' => $data['duracion_segundos'],
            'duracion_formatted' => $this->format_duration($data['duracion_segundos']),
            'tipo' => $tipo,
        ];
    }

    /**
     * Extrae metadatos del archivo de audio
     */
    private function extract_audio_metadata($file_path) {
        $metadata = [
            'title' => '',
            'artist' => '',
            'album' => '',
            'duration' => 0,
        ];

        // Intentar usar getID3 si está disponible
        if (file_exists(ABSPATH . 'wp-includes/ID3/getid3.php')) {
            require_once ABSPATH . 'wp-includes/ID3/getid3.php';

            $getID3 = new getID3();
            $info = $getID3->analyze($file_path);

            if (!empty($info['tags']['id3v2'])) {
                $tags = $info['tags']['id3v2'];
                $metadata['title'] = $tags['title'][0] ?? '';
                $metadata['artist'] = $tags['artist'][0] ?? '';
                $metadata['album'] = $tags['album'][0] ?? '';
            } elseif (!empty($info['tags']['id3v1'])) {
                $tags = $info['tags']['id3v1'];
                $metadata['title'] = $tags['title'][0] ?? '';
                $metadata['artist'] = $tags['artist'][0] ?? '';
                $metadata['album'] = $tags['album'][0] ?? '';
            }

            $metadata['duration'] = isset($info['playtime_seconds'])
                ? round($info['playtime_seconds'])
                : 0;
        }

        return $metadata;
    }

    /**
     * Obtiene subdirectorio según tipo
     */
    private function get_subdir_for_type($tipo) {
        $map = [
            'cancion' => 'audios',
            'podcast' => 'podcasts',
            'jingle' => 'jingles',
            'cortina' => 'jingles',
            'programa' => 'programas',
            'otro' => 'audios',
        ];
        return $map[$tipo] ?? 'audios';
    }

    /**
     * Formatea duración en segundos a mm:ss
     */
    private function format_duration($seconds) {
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    // =========================================================================
    // LIBRERÍA DE AUDIO
    // =========================================================================

    /**
     * AJAX: Obtener librería de audio
     */
    public function ajax_get_library() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $tipo = sanitize_text_field($_GET['tipo'] ?? '');
        $busqueda = sanitize_text_field($_GET['busqueda'] ?? '');
        $pagina = absint($_GET['pagina'] ?? 1);
        $por_pagina = absint($_GET['por_pagina'] ?? 50);

        $result = $this->get_audio_library($tipo, $busqueda, $pagina, $por_pagina);

        wp_send_json_success($result);
    }

    /**
     * Obtiene la librería de audios
     */
    public function get_audio_library($tipo = '', $busqueda = '', $pagina = 1, $por_pagina = 50) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_audios';

        $where = ['activo = 1'];
        $params = [];

        if (!empty($tipo)) {
            $where[] = 'tipo = %s';
            $params[] = $tipo;
        }

        if (!empty($busqueda)) {
            $where[] = '(titulo LIKE %s OR artista LIKE %s)';
            $like = '%' . $wpdb->esc_like($busqueda) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $where_sql = implode(' AND ', $where);
        $offset = ($pagina - 1) * $por_pagina;

        // Total
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM $tabla WHERE $where_sql",
                ...$params
            )
        );

        // Resultados
        $params[] = $por_pagina;
        $params[] = $offset;

        $audios = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT id, titulo, artista, album, archivo_url, duracion_segundos,
                        formato, tipo, reproducciones, fecha_subida
                 FROM $tabla
                 WHERE $where_sql
                 ORDER BY fecha_subida DESC
                 LIMIT %d OFFSET %d",
                ...$params
            )
        );

        // Formatear
        foreach ($audios as &$audio) {
            $audio->duracion_formatted = $this->format_duration($audio->duracion_segundos);
        }

        return [
            'audios' => $audios,
            'total' => (int)$total,
            'paginas' => ceil($total / $por_pagina),
            'pagina_actual' => $pagina,
        ];
    }

    /**
     * AJAX: Eliminar audio
     */
    public function ajax_delete_audio() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!current_user_can('delete_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        $audio_id = absint($_POST['audio_id'] ?? 0);
        if (!$audio_id) {
            wp_send_json_error(['message' => __('ID no válido', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_audios';

        // Obtener path del archivo
        $audio = $wpdb->get_row($wpdb->prepare(
            "SELECT archivo_path FROM $tabla WHERE id = %d",
            $audio_id
        ));

        if ($audio && file_exists($audio->archivo_path)) {
            unlink($audio->archivo_path);
        }

        // Eliminar de DB (soft delete)
        $wpdb->update($tabla, ['activo' => 0], ['id' => $audio_id]);

        wp_send_json_success(['message' => __('Audio eliminado', 'flavor-platform')]);
    }

    // =========================================================================
    // PLAYLISTS
    // =========================================================================

    /**
     * AJAX: Crear/actualizar playlist
     */
    public function ajax_create_playlist() {
        check_ajax_referer('flavor_radio_nonce', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(['message' => __('Sin permisos', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_playlists';

        $playlist_id = absint($_POST['playlist_id'] ?? 0);
        $nombre = sanitize_text_field($_POST['nombre'] ?? '');
        $descripcion = sanitize_textarea_field($_POST['descripcion'] ?? '');
        $tipo = sanitize_text_field($_POST['tipo'] ?? 'manual');
        $audios_ids = isset($_POST['audios_ids']) ? array_map('absint', $_POST['audios_ids']) : [];
        $orden = sanitize_text_field($_POST['orden'] ?? 'secuencial');

        if (empty($nombre)) {
            wp_send_json_error(['message' => __('El nombre es obligatorio', 'flavor-platform')]);
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'tipo' => $tipo,
            'audios_ids' => json_encode($audios_ids),
            'orden_reproduccion' => $orden,
        ];

        if ($playlist_id > 0) {
            // Actualizar
            $wpdb->update($tabla, $data, ['id' => $playlist_id]);
        } else {
            // Crear
            $data['creado_por'] = get_current_user_id();
            $data['fecha_creacion'] = current_time('mysql');
            $wpdb->insert($tabla, $data);
            $playlist_id = $wpdb->insert_id;
        }

        wp_send_json_success([
            'message' => __('Playlist guardada', 'flavor-platform'),
            'playlist_id' => $playlist_id,
        ]);
    }

    /**
     * AJAX: Obtener playlist con sus audios
     */
    public function ajax_get_playlist() {
        $playlist_id = absint($_GET['playlist_id'] ?? 0);

        if (!$playlist_id) {
            wp_send_json_error(['message' => __('ID no válido', 'flavor-platform')]);
        }

        global $wpdb;
        $tabla_playlists = $wpdb->prefix . 'flavor_radio_playlists';
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';

        $playlist = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_playlists WHERE id = %d AND activa = 1",
            $playlist_id
        ));

        if (!$playlist) {
            wp_send_json_error(['message' => __('Playlist no encontrada', 'flavor-platform')]);
        }

        $audios_ids = json_decode($playlist->audios_ids, true) ?: [];
        $audios = [];

        if (!empty($audios_ids)) {
            $ids_string = implode(',', array_map('absint', $audios_ids));
            $audios = $wpdb->get_results(
                "SELECT id, titulo, artista, archivo_url, duracion_segundos, tipo
                 FROM $tabla_audios
                 WHERE id IN ($ids_string) AND activo = 1
                 ORDER BY FIELD(id, $ids_string)"
            );

            foreach ($audios as &$audio) {
                $audio->duracion_formatted = $this->format_duration($audio->duracion_segundos);
            }
        }

        wp_send_json_success([
            'playlist' => [
                'id' => $playlist->id,
                'nombre' => $playlist->nombre,
                'descripcion' => $playlist->descripcion,
                'tipo' => $playlist->tipo,
                'orden' => $playlist->orden_reproduccion,
            ],
            'audios' => $audios,
            'total_duracion' => array_sum(array_column($audios, 'duracion_segundos')),
        ]);
    }

    // =========================================================================
    // REPRODUCCIÓN AUTOMÁTICA
    // =========================================================================

    /**
     * AJAX: Obtener track actual según programación
     */
    public function ajax_get_current_track() {
        $track = $this->get_current_track();
        wp_send_json_success($track);
    }

    /**
     * Obtiene el track que debería sonar ahora
     */
    public function get_current_track() {
        global $wpdb;
        $tabla_auto = $wpdb->prefix . 'flavor_radio_programacion_auto';
        $tabla_playlists = $wpdb->prefix . 'flavor_radio_playlists';
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';
        $tabla_historial = $wpdb->prefix . 'flavor_radio_historial';

        $dia_semana = date('N'); // 1=Lunes, 7=Domingo
        $hora_actual = current_time('H:i:s');

        // Buscar programación activa
        $programacion = null;
        if (Flavor_Platform_Helpers::tabla_existe($tabla_auto)) {
            $programacion = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $tabla_auto
                 WHERE dia_semana = %d
                   AND hora_inicio <= %s
                   AND hora_fin > %s
                   AND activo = 1
                 ORDER BY prioridad DESC
                 LIMIT 1",
                $dia_semana, $hora_actual, $hora_actual
            ));
        }

        // Si no hay programación, usar playlist por defecto
        if (!$programacion) {
            // Buscar playlist marcada como default
            $playlist_default = $wpdb->get_row(
                "SELECT * FROM $tabla_playlists WHERE tipo = 'automatica' AND activa = 1 LIMIT 1"
            );

            if (!$playlist_default) {
                return [
                    'playing' => false,
                    'message' => __('No hay programación activa', 'flavor-platform'),
                ];
            }

            $programacion = (object)[
                'tipo' => 'playlist',
                'playlist_id' => $playlist_default->id,
            ];
        }

        // Obtener playlist
        $playlist = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_playlists WHERE id = %d AND activa = 1",
            $programacion->playlist_id
        ));

        if (!$playlist) {
            return [
                'playing' => false,
                'message' => __('Playlist no disponible', 'flavor-platform'),
            ];
        }

        // Obtener audios de la playlist
        $audios_ids = json_decode($playlist->audios_ids, true) ?: [];
        if (empty($audios_ids)) {
            return [
                'playing' => false,
                'message' => __('Playlist vacía', 'flavor-platform'),
            ];
        }

        // Determinar qué track toca
        $track_index = $this->calculate_current_track_index($playlist, $audios_ids);
        $audio_id = $audios_ids[$track_index];

        // Obtener datos del audio
        $audio = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $tabla_audios WHERE id = %d AND activo = 1",
            $audio_id
        ));

        if (!$audio) {
            return [
                'playing' => false,
                'message' => __('Audio no disponible', 'flavor-platform'),
            ];
        }

        // Calcular posición en el track actual
        $position = $this->calculate_track_position($playlist, $audios_ids, $track_index);

        return [
            'playing' => true,
            'track' => [
                'id' => $audio->id,
                'titulo' => $audio->titulo,
                'artista' => $audio->artista,
                'url' => $audio->archivo_url,
                'duracion' => $audio->duracion_segundos,
                'duracion_formatted' => $this->format_duration($audio->duracion_segundos),
                'posicion' => $position,
                'tipo' => $audio->tipo,
            ],
            'playlist' => [
                'id' => $playlist->id,
                'nombre' => $playlist->nombre,
                'total_tracks' => count($audios_ids),
                'track_actual' => $track_index + 1,
            ],
            'siguiente' => $this->get_next_track_info($audios_ids, $track_index, $playlist->orden_reproduccion),
        ];
    }

    /**
     * Calcula qué track de la playlist debería sonar ahora
     */
    private function calculate_current_track_index($playlist, $audios_ids) {
        global $wpdb;
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';

        if ($playlist->orden_reproduccion === 'aleatorio') {
            // Para aleatorio, usar seed basado en la hora actual (cambia cada hora)
            $seed = date('YmdH');
            mt_srand(crc32($seed . $playlist->id));
            return mt_rand(0, count($audios_ids) - 1);
        }

        // Secuencial: calcular basándose en duración total y tiempo transcurrido
        $ids_string = implode(',', array_map('absint', $audios_ids));
        $duraciones = $wpdb->get_results(
            "SELECT id, duracion_segundos FROM $tabla_audios WHERE id IN ($ids_string)",
            OBJECT_K
        );

        $duracion_total = 0;
        foreach ($audios_ids as $id) {
            $duracion_total += $duraciones[$id]->duracion_segundos ?? 180; // Default 3 min
        }

        // Segundos desde medianoche
        $segundos_hoy = (int)date('H') * 3600 + (int)date('i') * 60 + (int)date('s');

        // Posición en la playlist (con repetición)
        $posicion = $segundos_hoy % $duracion_total;

        // Encontrar track actual
        $acumulado = 0;
        foreach ($audios_ids as $index => $id) {
            $duracion = $duraciones[$id]->duracion_segundos ?? 180;
            if ($acumulado + $duracion > $posicion) {
                return $index;
            }
            $acumulado += $duracion;
        }

        return 0;
    }

    /**
     * Calcula posición dentro del track actual
     */
    private function calculate_track_position($playlist, $audios_ids, $track_index) {
        global $wpdb;
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';

        $ids_string = implode(',', array_map('absint', $audios_ids));
        $duraciones = $wpdb->get_results(
            "SELECT id, duracion_segundos FROM $tabla_audios WHERE id IN ($ids_string)",
            OBJECT_K
        );

        // Calcular tiempo acumulado hasta el track actual
        $acumulado = 0;
        for ($i = 0; $i < $track_index; $i++) {
            $acumulado += $duraciones[$audios_ids[$i]]->duracion_segundos ?? 180;
        }

        // Segundos desde medianoche
        $segundos_hoy = (int)date('H') * 3600 + (int)date('i') * 60 + (int)date('s');

        // Posición total con repetición
        $duracion_total = array_sum(array_column($duraciones, 'duracion_segundos'));
        $posicion_total = $segundos_hoy % $duracion_total;

        // Posición en el track actual
        return max(0, $posicion_total - $acumulado);
    }

    /**
     * Obtiene info del siguiente track
     */
    private function get_next_track_info($audios_ids, $current_index, $orden) {
        global $wpdb;
        $tabla_audios = $wpdb->prefix . 'flavor_radio_audios';

        $next_index = ($current_index + 1) % count($audios_ids);
        $next_id = $audios_ids[$next_index];

        $audio = $wpdb->get_row($wpdb->prepare(
            "SELECT titulo, artista FROM $tabla_audios WHERE id = %d",
            $next_id
        ));

        return $audio ? [
            'titulo' => $audio->titulo,
            'artista' => $audio->artista,
        ] : null;
    }

    // =========================================================================
    // REST API
    // =========================================================================

    /**
     * REST: Obtener librería
     */
    public function rest_get_library($request) {
        $tipo = $request->get_param('tipo') ?: '';
        $busqueda = $request->get_param('search') ?: '';
        $pagina = $request->get_param('page') ?: 1;

        $result = $this->get_audio_library($tipo, $busqueda, $pagina);

        return new WP_REST_Response([
            'success' => true,
            'data' => $result,
        ], 200);
    }

    /**
     * REST: Track actual
     */
    public function rest_get_current_track($request) {
        $track = $this->get_current_track();

        return new WP_REST_Response([
            'success' => true,
            'data' => $track,
        ], 200);
    }

    /**
     * REST: Obtener playlists
     */
    public function rest_get_playlists($request) {
        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_radio_playlists';

        $playlists = $wpdb->get_results(
            "SELECT id, nombre, descripcion, tipo, orden_reproduccion
             FROM $tabla
             WHERE activa = 1
             ORDER BY nombre"
        );

        return new WP_REST_Response([
            'success' => true,
            'playlists' => $playlists,
        ], 200);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Radio_Media_Manager::get_instance();
});
