<?php
/**
 * Funcionalidades Compartidas entre Módulos
 *
 * Proporciona características comunes que pueden aplicarse a cualquier
 * entidad de cualquier módulo: valoraciones, favoritos, comentarios,
 * seguimiento, reportes, etiquetas, etc.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Registro central de funcionalidades compartidas
 */
class Flavor_Shared_Features {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Features registradas
     */
    private $features = [];

    /**
     * Entidades que usan cada feature
     */
    private $entity_features = [];

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->register_core_features();
        $this->init_hooks();
    }

    /**
     * Registra las funcionalidades básicas
     */
    private function register_core_features() {
        // Valoraciones / Ratings
        $this->register_feature('ratings', [
            'label'       => __('Valoraciones', 'flavor-chat-ia'),
            'description' => __('Permite a los usuarios valorar con estrellas', 'flavor-chat-ia'),
            'icon'        => 'dashicons-star-filled',
            'handler'     => 'Flavor_Feature_Ratings',
            'default'     => true,
        ]);

        // Favoritos
        $this->register_feature('favorites', [
            'label'       => __('Favoritos', 'flavor-chat-ia'),
            'description' => __('Permite guardar elementos como favoritos', 'flavor-chat-ia'),
            'icon'        => 'dashicons-heart',
            'handler'     => 'Flavor_Feature_Favorites',
            'default'     => true,
        ]);

        // Comentarios
        $this->register_feature('comments', [
            'label'       => __('Comentarios', 'flavor-chat-ia'),
            'description' => __('Sistema de comentarios integrado', 'flavor-chat-ia'),
            'icon'        => 'dashicons-admin-comments',
            'handler'     => 'Flavor_Feature_Comments',
            'default'     => true,
        ]);

        // Seguimiento / Follow
        $this->register_feature('follow', [
            'label'       => __('Seguir', 'flavor-chat-ia'),
            'description' => __('Permite seguir entidades para recibir actualizaciones', 'flavor-chat-ia'),
            'icon'        => 'dashicons-visibility',
            'handler'     => 'Flavor_Feature_Follow',
            'default'     => false,
        ]);

        // Compartir
        $this->register_feature('share', [
            'label'       => __('Compartir', 'flavor-chat-ia'),
            'description' => __('Botones para compartir en redes sociales', 'flavor-chat-ia'),
            'icon'        => 'dashicons-share',
            'handler'     => 'Flavor_Feature_Share',
            'default'     => true,
        ]);

        // Reportar
        $this->register_feature('report', [
            'label'       => __('Reportar', 'flavor-chat-ia'),
            'description' => __('Permite reportar contenido inapropiado', 'flavor-chat-ia'),
            'icon'        => 'dashicons-flag',
            'handler'     => 'Flavor_Feature_Report',
            'default'     => false,
        ]);

        // Etiquetas / Tags
        $this->register_feature('tags', [
            'label'       => __('Etiquetas', 'flavor-chat-ia'),
            'description' => __('Sistema de etiquetas para organización', 'flavor-chat-ia'),
            'icon'        => 'dashicons-tag',
            'handler'     => 'Flavor_Feature_Tags',
            'default'     => false,
        ]);

        // Historial de vistas
        $this->register_feature('views', [
            'label'       => __('Contador de vistas', 'flavor-chat-ia'),
            'description' => __('Registra y muestra el número de vistas', 'flavor-chat-ia'),
            'icon'        => 'dashicons-visibility',
            'handler'     => 'Flavor_Feature_Views',
            'default'     => true,
        ]);

        // Reacciones (like, love, etc)
        $this->register_feature('reactions', [
            'label'       => __('Reacciones', 'flavor-chat-ia'),
            'description' => __('Reacciones tipo emoji (me gusta, me encanta, etc)', 'flavor-chat-ia'),
            'icon'        => 'dashicons-smiley',
            'handler'     => 'Flavor_Feature_Reactions',
            'default'     => false,
        ]);

        // Bookmarks / Guardar para después
        $this->register_feature('bookmarks', [
            'label'       => __('Guardar', 'flavor-chat-ia'),
            'description' => __('Guardar elementos para ver después', 'flavor-chat-ia'),
            'icon'        => 'dashicons-bookmark',
            'handler'     => 'Flavor_Feature_Bookmarks',
            'default'     => false,
        ]);

        // Versiones / Historial
        $this->register_feature('versions', [
            'label'       => __('Historial de versiones', 'flavor-chat-ia'),
            'description' => __('Guarda versiones anteriores del contenido', 'flavor-chat-ia'),
            'icon'        => 'dashicons-backup',
            'handler'     => 'Flavor_Feature_Versions',
            'default'     => false,
        ]);

        // QR Code
        $this->register_feature('qrcode', [
            'label'       => __('Código QR', 'flavor-chat-ia'),
            'description' => __('Genera código QR para acceso rápido', 'flavor-chat-ia'),
            'icon'        => 'dashicons-smartphone',
            'handler'     => 'Flavor_Feature_QRCode',
            'default'     => false,
        ]);

        // Exportar
        $this->register_feature('export', [
            'label'       => __('Exportar', 'flavor-chat-ia'),
            'description' => __('Permite exportar contenido (PDF, JSON, etc)', 'flavor-chat-ia'),
            'icon'        => 'dashicons-download',
            'handler'     => 'Flavor_Feature_Export',
            'default'     => false,
        ]);
    }

    /**
     * Registra una nueva feature
     */
    public function register_feature($id, $args) {
        $defaults = [
            'label'       => $id,
            'description' => '',
            'icon'        => 'dashicons-admin-generic',
            'handler'     => null,
            'default'     => false,
            'requires'    => [],
        ];

        $this->features[$id] = wp_parse_args($args, $defaults);

        // Cargar handler si existe
        if ($this->features[$id]['handler'] && class_exists($this->features[$id]['handler'])) {
            $handler_class = $this->features[$id]['handler'];
            $this->features[$id]['handler_instance'] = new $handler_class();
        }

        return $this;
    }

    /**
     * Habilita una feature para una entidad
     */
    public function enable_feature_for($entity_type, $feature_id, $options = []) {
        if (!isset($this->features[$feature_id])) {
            return false;
        }

        if (!isset($this->entity_features[$entity_type])) {
            $this->entity_features[$entity_type] = [];
        }

        $this->entity_features[$entity_type][$feature_id] = $options;

        // Disparar acción para que el handler se configure
        do_action("flavor_feature_enabled_{$feature_id}", $entity_type, $options);

        return true;
    }

    /**
     * Deshabilita una feature para una entidad
     */
    public function disable_feature_for($entity_type, $feature_id) {
        if (isset($this->entity_features[$entity_type][$feature_id])) {
            unset($this->entity_features[$entity_type][$feature_id]);
        }

        return true;
    }

    /**
     * Verifica si una entidad tiene una feature habilitada
     */
    public function entity_has_feature($entity_type, $feature_id) {
        return isset($this->entity_features[$entity_type][$feature_id]);
    }

    /**
     * Obtiene todas las features habilitadas para una entidad
     */
    public function get_entity_features($entity_type) {
        return isset($this->entity_features[$entity_type])
            ? $this->entity_features[$entity_type]
            : [];
    }

    /**
     * Obtiene todas las features registradas
     */
    public function get_all_features() {
        return $this->features;
    }

    /**
     * Inicializa hooks
     */
    private function init_hooks() {
        // Crear tablas
        add_action('init', [$this, 'maybe_create_tables']);

        // REST API
        add_action('rest_api_init', [$this, 'register_rest_routes']);

        // AJAX handlers
        add_action('wp_ajax_flavor_feature_action', [$this, 'handle_ajax_action']);
        add_action('wp_ajax_nopriv_flavor_feature_action', [$this, 'handle_ajax_action_nopriv']);

        // Admin: Configuración de features por módulo
        add_action('flavor_module_settings_after', [$this, 'render_features_settings'], 10, 2);

        // Frontend: Renderizar features
        add_action('flavor_after_content', [$this, 'render_entity_features'], 10, 2);

        // Cargar assets
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Crea las tablas necesarias
     */
    public function maybe_create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Tabla unificada de interacciones
        $tabla_interacciones = $wpdb->prefix . 'flavor_interactions';

        $sql = "CREATE TABLE IF NOT EXISTS $tabla_interacciones (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id bigint(20) UNSIGNED NOT NULL,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            interaction_type varchar(50) NOT NULL,
            value text,
            metadata longtext,
            ip_address varchar(45),
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_entity (user_id, entity_type, entity_id),
            KEY entity_interaction (entity_type, entity_id, interaction_type),
            KEY interaction_type (interaction_type),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Tabla de contadores agregados
        $tabla_contadores = $wpdb->prefix . 'flavor_interaction_counts';

        $sql2 = "CREATE TABLE IF NOT EXISTS $tabla_contadores (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            entity_type varchar(50) NOT NULL,
            entity_id bigint(20) UNSIGNED NOT NULL,
            interaction_type varchar(50) NOT NULL,
            count_value bigint(20) UNSIGNED DEFAULT 0,
            sum_value decimal(10,2) DEFAULT 0,
            avg_value decimal(5,2) DEFAULT 0,
            last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY entity_interaction (entity_type, entity_id, interaction_type)
        ) $charset_collate;";

        dbDelta($sql2);
    }

    /**
     * Registra rutas REST
     */
    public function register_rest_routes() {
        // Acción genérica
        register_rest_route('flavor-features/v1', '/interact', [
            'methods'  => 'POST',
            'callback' => [$this, 'api_interact'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
            'args' => [
                'entity_type' => ['required' => true, 'type' => 'string'],
                'entity_id'   => ['required' => true, 'type' => 'integer'],
                'action'      => ['required' => true, 'type' => 'string'],
                'value'       => ['type' => 'string'],
            ],
        ]);

        // Obtener interacciones de una entidad
        register_rest_route('flavor-features/v1', '/entity/(?P<type>[a-z_]+)/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'api_get_entity_interactions'],
            'permission_callback' => '__return_true',
        ]);

        // Obtener interacciones del usuario actual
        register_rest_route('flavor-features/v1', '/user/interactions', [
            'methods'  => 'GET',
            'callback' => [$this, 'api_get_user_interactions'],
            'permission_callback' => function() {
                return is_user_logged_in();
            },
        ]);
    }

    /**
     * API: Realizar interacción
     */
    public function api_interact($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $entity_type = sanitize_text_field($request->get_param('entity_type'));
        $entity_id = absint($request->get_param('entity_id'));
        $action = sanitize_text_field($request->get_param('action'));
        $value = $request->get_param('value');

        // Verificar que la feature está habilitada para esta entidad
        if (!$this->entity_has_feature($entity_type, $action)) {
            // Verificar si es una acción relacionada (rating -> ratings)
            $feature_id = rtrim($action, 's') . 's'; // rating -> ratings
            if (!$this->entity_has_feature($entity_type, $feature_id)) {
                return new WP_Error('feature_disabled', 'Esta funcionalidad no está habilitada', ['status' => 400]);
            }
        }

        $tabla = $wpdb->prefix . 'flavor_interactions';

        // Verificar si ya existe
        $existe = $wpdb->get_row($wpdb->prepare(
            "SELECT id, value FROM $tabla
             WHERE user_id = %d AND entity_type = %s AND entity_id = %d AND interaction_type = %s",
            $user_id, $entity_type, $entity_id, $action
        ));

        $resultado = [];

        if ($existe) {
            // Toggle o actualizar
            if ($action === 'favorite' || $action === 'follow' || $action === 'bookmark') {
                // Toggle: si existe, eliminar
                $wpdb->delete($tabla, ['id' => $existe->id]);
                $resultado['status'] = 'removed';
            } else {
                // Actualizar valor
                $wpdb->update($tabla, [
                    'value' => $value,
                    'updated_at' => current_time('mysql'),
                ], ['id' => $existe->id]);
                $resultado['status'] = 'updated';
            }
        } else {
            // Crear nuevo
            $wpdb->insert($tabla, [
                'user_id' => $user_id,
                'entity_type' => $entity_type,
                'entity_id' => $entity_id,
                'interaction_type' => $action,
                'value' => $value,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
                'created_at' => current_time('mysql'),
            ]);
            $resultado['status'] = 'added';
        }

        // Actualizar contadores
        $this->update_counts($entity_type, $entity_id, $action);

        // Disparar acción
        do_action("flavor_interaction_{$action}", $user_id, $entity_type, $entity_id, $value, $resultado['status']);

        // Obtener nuevos contadores
        $resultado['counts'] = $this->get_entity_counts($entity_type, $entity_id);

        return rest_ensure_response($resultado);
    }

    /**
     * Actualiza contadores agregados
     */
    private function update_counts($entity_type, $entity_id, $interaction_type) {
        global $wpdb;

        $tabla_interacciones = $wpdb->prefix . 'flavor_interactions';
        $tabla_contadores = $wpdb->prefix . 'flavor_interaction_counts';

        // Calcular nuevos valores
        $stats = $wpdb->get_row($wpdb->prepare("
            SELECT
                COUNT(*) as count_value,
                COALESCE(SUM(CAST(value AS DECIMAL(10,2))), 0) as sum_value,
                COALESCE(AVG(CAST(value AS DECIMAL(10,2))), 0) as avg_value
            FROM $tabla_interacciones
            WHERE entity_type = %s AND entity_id = %d AND interaction_type = %s
        ", $entity_type, $entity_id, $interaction_type));

        // Upsert en tabla de contadores
        $wpdb->query($wpdb->prepare("
            INSERT INTO $tabla_contadores (entity_type, entity_id, interaction_type, count_value, sum_value, avg_value)
            VALUES (%s, %d, %s, %d, %f, %f)
            ON DUPLICATE KEY UPDATE
                count_value = VALUES(count_value),
                sum_value = VALUES(sum_value),
                avg_value = VALUES(avg_value)
        ", $entity_type, $entity_id, $interaction_type, $stats->count_value, $stats->sum_value, $stats->avg_value));
    }

    /**
     * Obtiene contadores de una entidad
     */
    public function get_entity_counts($entity_type, $entity_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_interaction_counts';

        $resultados = $wpdb->get_results($wpdb->prepare("
            SELECT interaction_type, count_value, sum_value, avg_value
            FROM $tabla
            WHERE entity_type = %s AND entity_id = %d
        ", $entity_type, $entity_id));

        $counts = [];
        foreach ($resultados as $row) {
            $counts[$row->interaction_type] = [
                'count' => (int) $row->count_value,
                'sum'   => (float) $row->sum_value,
                'avg'   => round((float) $row->avg_value, 1),
            ];
        }

        return $counts;
    }

    /**
     * API: Obtener interacciones de entidad
     */
    public function api_get_entity_interactions($request) {
        $entity_type = sanitize_text_field($request->get_param('type'));
        $entity_id = absint($request->get_param('id'));

        $counts = $this->get_entity_counts($entity_type, $entity_id);

        // Si el usuario está logueado, obtener sus interacciones
        $user_interactions = [];
        if (is_user_logged_in()) {
            $user_interactions = $this->get_user_entity_interactions(
                get_current_user_id(),
                $entity_type,
                $entity_id
            );
        }

        return rest_ensure_response([
            'counts' => $counts,
            'user_interactions' => $user_interactions,
        ]);
    }

    /**
     * Obtiene interacciones de un usuario con una entidad
     */
    public function get_user_entity_interactions($user_id, $entity_type, $entity_id) {
        global $wpdb;

        $tabla = $wpdb->prefix . 'flavor_interactions';

        $resultados = $wpdb->get_results($wpdb->prepare("
            SELECT interaction_type, value, created_at
            FROM $tabla
            WHERE user_id = %d AND entity_type = %s AND entity_id = %d
        ", $user_id, $entity_type, $entity_id));

        $interactions = [];
        foreach ($resultados as $row) {
            $interactions[$row->interaction_type] = [
                'value' => $row->value,
                'date'  => $row->created_at,
            ];
        }

        return $interactions;
    }

    /**
     * API: Obtener interacciones del usuario
     */
    public function api_get_user_interactions($request) {
        global $wpdb;

        $user_id = get_current_user_id();
        $tipo = $request->get_param('tipo');
        $limite = min(100, max(1, $request->get_param('limite') ?: 20));

        $tabla = $wpdb->prefix . 'flavor_interactions';

        $where = 'user_id = %d';
        $params = [$user_id];

        if ($tipo) {
            $where .= ' AND interaction_type = %s';
            $params[] = $tipo;
        }

        $params[] = $limite;

        $resultados = $wpdb->get_results($wpdb->prepare("
            SELECT entity_type, entity_id, interaction_type, value, created_at
            FROM $tabla
            WHERE $where
            ORDER BY created_at DESC
            LIMIT %d
        ", $params));

        return rest_ensure_response($resultados);
    }

    /**
     * Handler AJAX para usuarios logueados
     */
    public function handle_ajax_action() {
        check_ajax_referer('flavor_features_nonce', 'nonce');

        $entity_type = sanitize_text_field($_POST['entity_type'] ?? '');
        $entity_id = absint($_POST['entity_id'] ?? 0);
        $action = sanitize_text_field($_POST['feature_action'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        if (!$entity_type || !$entity_id || !$action) {
            wp_send_json_error(['message' => 'Parámetros inválidos']);
        }

        // Simular request REST
        $request = new WP_REST_Request('POST');
        $request->set_param('entity_type', $entity_type);
        $request->set_param('entity_id', $entity_id);
        $request->set_param('action', $action);
        $request->set_param('value', $value);

        $response = $this->api_interact($request);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => $response->get_error_message()]);
        }

        wp_send_json_success($response->get_data());
    }

    /**
     * Handler AJAX para usuarios no logueados
     */
    public function handle_ajax_action_nopriv() {
        $action = sanitize_text_field($_POST['feature_action'] ?? '');

        // Solo permitir algunas acciones sin login
        $allowed_noauth = ['view'];

        if (in_array($action, $allowed_noauth)) {
            // Registrar vista anónima
            $this->record_anonymous_view(
                sanitize_text_field($_POST['entity_type'] ?? ''),
                absint($_POST['entity_id'] ?? 0)
            );
            wp_send_json_success(['status' => 'recorded']);
        }

        wp_send_json_error(['message' => 'Debes iniciar sesión']);
    }

    /**
     * Registra vista anónima
     */
    private function record_anonymous_view($entity_type, $entity_id) {
        if (!$entity_type || !$entity_id) {
            return;
        }

        global $wpdb;
        $tabla = $wpdb->prefix . 'flavor_interaction_counts';

        $wpdb->query($wpdb->prepare("
            INSERT INTO $tabla (entity_type, entity_id, interaction_type, count_value)
            VALUES (%s, %d, 'view', 1)
            ON DUPLICATE KEY UPDATE count_value = count_value + 1
        ", $entity_type, $entity_id));
    }

    /**
     * Renderiza configuración de features en settings del módulo
     */
    public function render_features_settings($module_id, $settings) {
        ?>
        <div class="flavor-features-settings" style="margin-top: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd;">
            <h3><?php _e('Funcionalidades Compartidas', 'flavor-chat-ia'); ?></h3>
            <p class="description"><?php _e('Selecciona las funcionalidades que deseas habilitar para este módulo.', 'flavor-chat-ia'); ?></p>

            <table class="form-table">
                <?php foreach ($this->features as $feature_id => $feature): ?>
                <tr>
                    <th scope="row">
                        <span class="dashicons <?php echo esc_attr($feature['icon']); ?>"></span>
                        <?php echo esc_html($feature['label']); ?>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="flavor_features[<?php echo esc_attr($feature_id); ?>]"
                                   value="1"
                                   <?php checked($this->entity_has_feature($module_id, $feature_id)); ?> />
                            <?php _e('Habilitar', 'flavor-chat-ia'); ?>
                        </label>
                        <p class="description"><?php echo esc_html($feature['description']); ?></p>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    /**
     * Renderiza features en el frontend
     */
    public function render_entity_features($entity_type, $entity_id) {
        $features = $this->get_entity_features($entity_type);

        if (empty($features)) {
            return;
        }

        $counts = $this->get_entity_counts($entity_type, $entity_id);
        $user_interactions = is_user_logged_in()
            ? $this->get_user_entity_interactions(get_current_user_id(), $entity_type, $entity_id)
            : [];

        ?>
        <div class="flavor-entity-features" data-entity-type="<?php echo esc_attr($entity_type); ?>" data-entity-id="<?php echo esc_attr($entity_id); ?>">
            <?php foreach ($features as $feature_id => $options): ?>
                <?php $this->render_feature_ui($feature_id, $entity_type, $entity_id, $counts, $user_interactions); ?>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Renderiza UI de una feature individual
     */
    private function render_feature_ui($feature_id, $entity_type, $entity_id, $counts, $user_interactions) {
        $feature = $this->features[$feature_id] ?? null;
        if (!$feature) {
            return;
        }

        $count = isset($counts[$feature_id]) ? $counts[$feature_id]['count'] : 0;
        $user_has = isset($user_interactions[$feature_id]);
        $active_class = $user_has ? 'active' : '';

        switch ($feature_id) {
            case 'favorites':
                ?>
                <button class="flavor-feature-btn flavor-favorite <?php echo $active_class; ?>"
                        data-action="favorite"
                        title="<?php echo $user_has ? __('Quitar de favoritos', 'flavor-chat-ia') : __('Añadir a favoritos', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-heart"></span>
                    <span class="count"><?php echo esc_html($count); ?></span>
                </button>
                <?php
                break;

            case 'ratings':
                $avg = isset($counts['rating']) ? $counts['rating']['avg'] : 0;
                $user_rating = isset($user_interactions['rating']) ? $user_interactions['rating']['value'] : 0;
                ?>
                <div class="flavor-rating-container">
                    <div class="flavor-stars" data-action="rating" data-current="<?php echo esc_attr($user_rating); ?>">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="star <?php echo $i <= $avg ? 'filled' : ''; ?>" data-value="<?php echo $i; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-info"><?php printf(__('%s (%d votos)', 'flavor-chat-ia'), number_format($avg, 1), $count); ?></span>
                </div>
                <?php
                break;

            case 'share':
                $url = urlencode(get_permalink($entity_id));
                $title = urlencode(get_the_title($entity_id));
                ?>
                <div class="flavor-share-buttons">
                    <a href="https://twitter.com/intent/tweet?url=<?php echo $url; ?>&text=<?php echo $title; ?>"
                       target="_blank" class="share-twitter" title="Twitter">
                        <span class="dashicons dashicons-twitter"></span>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url; ?>"
                       target="_blank" class="share-facebook" title="Facebook">
                        <span class="dashicons dashicons-facebook"></span>
                    </a>
                    <a href="https://wa.me/?text=<?php echo $title; ?>%20<?php echo $url; ?>"
                       target="_blank" class="share-whatsapp" title="WhatsApp">
                        <span class="dashicons dashicons-whatsapp"></span>
                    </a>
                </div>
                <?php
                break;

            case 'views':
                $view_count = isset($counts['view']) ? $counts['view']['count'] : 0;
                ?>
                <span class="flavor-views">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php printf(_n('%d vista', '%d vistas', $view_count, 'flavor-chat-ia'), $view_count); ?>
                </span>
                <?php
                break;

            case 'follow':
                ?>
                <button class="flavor-feature-btn flavor-follow <?php echo $active_class; ?>"
                        data-action="follow">
                    <span class="dashicons dashicons-<?php echo $user_has ? 'yes' : 'plus'; ?>"></span>
                    <?php echo $user_has ? __('Siguiendo', 'flavor-chat-ia') : __('Seguir', 'flavor-chat-ia'); ?>
                    <span class="count">(<?php echo esc_html($count); ?>)</span>
                </button>
                <?php
                break;

            case 'bookmarks':
                ?>
                <button class="flavor-feature-btn flavor-bookmark <?php echo $active_class; ?>"
                        data-action="bookmark"
                        title="<?php echo $user_has ? __('Guardado', 'flavor-chat-ia') : __('Guardar para después', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-bookmark"></span>
                </button>
                <?php
                break;
        }
    }

    /**
     * Encola assets
     */
    public function enqueue_assets() {
        if (!is_singular()) {
            return;
        }

        wp_enqueue_style(
            'flavor-shared-features',
            FLAVOR_CHAT_IA_URL . 'assets/css/shared-features.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        wp_enqueue_script(
            'flavor-shared-features',
            FLAVOR_CHAT_IA_URL . 'assets/js/shared-features.js',
            ['jquery'],
            FLAVOR_CHAT_IA_VERSION,
            true
        );

        wp_localize_script('flavor-shared-features', 'FlavorFeatures', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url('flavor-features/v1/'),
            'nonce'   => wp_create_nonce('flavor_features_nonce'),
            'restNonce' => wp_create_nonce('wp_rest'),
            'isLoggedIn' => is_user_logged_in(),
            'strings' => [
                'loginRequired' => __('Debes iniciar sesión para realizar esta acción', 'flavor-chat-ia'),
                'error' => __('Ha ocurrido un error', 'flavor-chat-ia'),
            ],
        ]);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Shared_Features::get_instance();
}, 15);

/**
 * Helper: Habilitar feature para un módulo
 */
function flavor_enable_feature($entity_type, $feature_id, $options = []) {
    return Flavor_Shared_Features::get_instance()->enable_feature_for($entity_type, $feature_id, $options);
}

/**
 * Helper: Obtener contadores de una entidad
 */
function flavor_get_entity_counts($entity_type, $entity_id) {
    return Flavor_Shared_Features::get_instance()->get_entity_counts($entity_type, $entity_id);
}

/**
 * Helper: Renderizar features de una entidad
 */
function flavor_render_features($entity_type, $entity_id) {
    Flavor_Shared_Features::get_instance()->render_entity_features($entity_type, $entity_id);
}

/**
 * Helper: Renderizar features para el post actual con registro automático
 *
 * Este helper es ideal para usar en templates single. Registra automáticamente
 * las features básicas si no están configuradas, y luego las renderiza.
 *
 * @param array $features Features a habilitar (default: ['ratings', 'favorites', 'share', 'views'])
 * @return void
 */
function flavor_render_post_features($features = null) {
    if (!is_singular()) {
        return;
    }

    $post_type = get_post_type();
    $post_id = get_the_ID();

    if (!$post_type || !$post_id) {
        return;
    }

    $instance = Flavor_Shared_Features::get_instance();

    // Features por defecto si no se especifican
    if ($features === null) {
        $features = ['ratings', 'favorites', 'share', 'views'];
    }

    // Registrar features si no están configuradas para este post_type
    $current_features = $instance->get_entity_features($post_type);
    if (empty($current_features)) {
        foreach ($features as $feature_id) {
            $instance->enable_feature_for($post_type, $feature_id);
        }
    }

    // Renderizar contenedor con estilos Tailwind
    ?>
    <div class="flavor-post-features mt-8 pt-6 border-t border-gray-200">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <?php $instance->render_entity_features($post_type, $post_id); ?>
        </div>
    </div>
    <?php
}
