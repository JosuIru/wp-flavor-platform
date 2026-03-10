<?php
/**
 * Panel de Administración de Contenido Federado
 *
 * Gestiona la visualización y administración del contenido
 * sincronizado desde otros nodos de la red.
 *
 * @package FlavorChatIA\Network
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Network_Federation_Admin {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Tipos de contenido federado
     */
    private $content_types = [];

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
        $this->content_types = [
            'producers'   => ['label' => __('Productores', 'flavor-chat-ia'), 'icon' => '🌾', 'table' => 'flavor_network_producers'],
            'events'      => ['label' => __('Eventos', 'flavor-chat-ia'), 'icon' => '📅', 'table' => 'flavor_network_events'],
            'carpooling'  => ['label' => __('Carpooling', 'flavor-chat-ia'), 'icon' => '🚗', 'table' => 'flavor_network_carpooling'],
            'workshops'   => ['label' => __('Talleres', 'flavor-chat-ia'), 'icon' => '🎓', 'table' => 'flavor_network_workshops'],
            'spaces'      => ['label' => __('Espacios', 'flavor-chat-ia'), 'icon' => '🏠', 'table' => 'flavor_network_spaces'],
            'marketplace' => ['label' => __('Marketplace', 'flavor-chat-ia'), 'icon' => '🛒', 'table' => 'flavor_network_marketplace'],
            'time_bank'   => ['label' => __('Banco Tiempo', 'flavor-chat-ia'), 'icon' => '⏰', 'table' => 'flavor_network_time_bank'],
            'courses'     => ['label' => __('Cursos', 'flavor-chat-ia'), 'icon' => '📚', 'table' => 'flavor_network_courses'],
        ];

        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('wp_ajax_federation_toggle_visibility', [$this, 'ajax_toggle_visibility']);
        add_action('wp_ajax_federation_sync_now', [$this, 'ajax_sync_now']);
    }

    /**
     * Añade el menú de administración
     */
    public function add_admin_menu() {
        add_submenu_page(
            'flavor-network',
            __('Contenido Federado', 'flavor-chat-ia'),
            __('Contenido Federado', 'flavor-chat-ia'),
            'manage_options',
            'flavor-federation',
            [$this, 'render_admin_page']
        );
    }

    /**
     * Renderiza la página de administración
     */
    public function render_admin_page() {
        $current_type = isset($_GET['type']) ? sanitize_key($_GET['type']) : 'events';

        if (!isset($this->content_types[$current_type])) {
            $current_type = 'events';
        }

        $stats = $this->get_federation_stats();
        ?>
        <div class="wrap flavor-federation-admin">
            <h1><?php echo esc_html__('Contenido Federado de la Red', 'flavor-chat-ia'); ?></h1>

            <!-- Estadísticas generales -->
            <div class="federation-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
                <?php foreach ($this->content_types as $type => $config): ?>
                    <?php $count = $stats[$type] ?? 0; ?>
                    <a href="<?php echo esc_url(add_query_arg('type', $type)); ?>"
                       class="federation-stat-card <?php echo $current_type === $type ? 'active' : ''; ?>"
                       style="display: block; padding: 20px; background: <?php echo $current_type === $type ? '#0073aa' : '#fff'; ?>;
                              border-radius: 8px; text-decoration: none; text-align: center;
                              box-shadow: 0 1px 3px rgba(0,0,0,0.1); transition: all 0.2s;
                              color: <?php echo $current_type === $type ? '#fff' : '#333'; ?>;">
                        <div style="font-size: 28px; margin-bottom: 5px;"><?php echo $config['icon']; ?></div>
                        <div style="font-size: 24px; font-weight: bold;"><?php echo number_format($count); ?></div>
                        <div style="font-size: 12px; opacity: 0.8;"><?php echo esc_html($config['label']); ?></div>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Acciones -->
            <div style="margin: 20px 0; display: flex; gap: 10px; align-items: center;">
                <button type="button" class="button button-primary" id="btn-sync-now">
                    🔄 <?php echo esc_html__('Sincronizar Ahora', 'flavor-chat-ia'); ?>
                </button>
                <span id="sync-status" style="color: #666;"></span>

                <span style="margin-left: auto; color: #666;">
                    <?php
                    $last_sync = get_option('flavor_network_last_sync', 0);
                    if ($last_sync) {
                        printf(
                            __('Última sincronización: %s', 'flavor-chat-ia'),
                            human_time_diff($last_sync) . ' ' . __('atrás', 'flavor-chat-ia')
                        );
                    }
                    ?>
                </span>
            </div>

            <!-- Tabla de contenido -->
            <div class="federation-content-table" style="background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                <?php $this->render_content_table($current_type); ?>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Sincronizar ahora
            $('#btn-sync-now').on('click', function() {
                var $btn = $(this);
                var $status = $('#sync-status');

                $btn.prop('disabled', true).text('⏳ Sincronizando...');
                $status.text('');

                $.post(ajaxurl, {
                    action: 'federation_sync_now',
                    _wpnonce: '<?php echo wp_create_nonce('federation_sync'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false).html('🔄 <?php echo esc_js(__('Sincronizar Ahora', 'flavor-chat-ia')); ?>');
                    if (response.success) {
                        $status.text('✅ ' + response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        $status.text('❌ Error: ' + response.data);
                    }
                });
            });

            // Toggle visibilidad
            $('.toggle-visibility').on('click', function() {
                var $btn = $(this);
                var id = $btn.data('id');
                var type = $btn.data('type');

                $.post(ajaxurl, {
                    action: 'federation_toggle_visibility',
                    id: id,
                    type: type,
                    _wpnonce: '<?php echo wp_create_nonce('federation_toggle'); ?>'
                }, function(response) {
                    if (response.success) {
                        $btn.text(response.data.visible ? '👁️' : '🚫');
                        $btn.closest('tr').toggleClass('hidden-item', !response.data.visible);
                    }
                });
            });
        });
        </script>

        <style>
        .federation-stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important; }
        .federation-stat-card.active { transform: scale(1.02); }
        .federation-content-table table { width: 100%; border-collapse: collapse; }
        .federation-content-table th, .federation-content-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .federation-content-table th { background: #f8f9fa; font-weight: 600; }
        .federation-content-table tr:hover { background: #f8f9fa; }
        .federation-content-table tr.hidden-item { opacity: 0.5; }
        .toggle-visibility { cursor: pointer; font-size: 18px; border: none; background: none; }
        .node-badge { display: inline-block; padding: 2px 8px; background: #e3f2fd; color: #1565c0; border-radius: 12px; font-size: 11px; }
        </style>
        <?php
    }

    /**
     * Renderiza la tabla de contenido para un tipo específico
     */
    private function render_content_table($type) {
        global $wpdb;

        $config = $this->content_types[$type];
        $tabla = $wpdb->prefix . $config['table'];
        $nodo_local = get_option('flavor_network_node_id', '');

        // Verificar que la tabla existe
        if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") !== $tabla) {
            echo '<p style="padding: 20px; text-align: center; color: #666;">';
            echo esc_html__('Tabla no disponible. Ejecute la actualización de base de datos.', 'flavor-chat-ia');
            echo '</p>';
            return;
        }

        // Obtener contenido (excluyendo el nodo local)
        $items = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$tabla} WHERE nodo_id != %s ORDER BY actualizado_en DESC LIMIT 100",
            $nodo_local
        ));

        if (empty($items)) {
            echo '<p style="padding: 40px; text-align: center; color: #666;">';
            echo $config['icon'] . ' ';
            echo esc_html__('No hay contenido federado de este tipo todavía.', 'flavor-chat-ia');
            echo '</p>';
            return;
        }

        // Renderizar tabla según el tipo
        echo '<table>';
        echo '<thead><tr>';
        echo '<th style="width: 40px;"></th>';

        switch ($type) {
            case 'events':
                echo '<th>' . esc_html__('Evento', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Fecha', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Ubicación', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Nodo', 'flavor-chat-ia') . '</th>';
                break;
            case 'courses':
                echo '<th>' . esc_html__('Curso', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Categoría', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Modalidad', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Precio', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Nodo', 'flavor-chat-ia') . '</th>';
                break;
            case 'marketplace':
                echo '<th>' . esc_html__('Anuncio', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Tipo', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Precio', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Nodo', 'flavor-chat-ia') . '</th>';
                break;
            case 'time_bank':
                echo '<th>' . esc_html__('Servicio', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Tipo', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Horas', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Nodo', 'flavor-chat-ia') . '</th>';
                break;
            default:
                echo '<th>' . esc_html__('Título', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Descripción', 'flavor-chat-ia') . '</th>';
                echo '<th>' . esc_html__('Nodo', 'flavor-chat-ia') . '</th>';
        }

        echo '<th style="width: 100px;">' . esc_html__('Actualizado', 'flavor-chat-ia') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($items as $item) {
            $visible = isset($item->visible_en_red) ? $item->visible_en_red : 1;
            $row_class = $visible ? '' : 'hidden-item';

            echo '<tr class="' . esc_attr($row_class) . '">';
            echo '<td><button type="button" class="toggle-visibility" data-id="' . esc_attr($item->id) . '" data-type="' . esc_attr($type) . '">';
            echo $visible ? '👁️' : '🚫';
            echo '</button></td>';

            switch ($type) {
                case 'events':
                    echo '<td><strong>' . esc_html($item->titulo) . '</strong></td>';
                    echo '<td>' . esc_html(date_i18n('d M Y H:i', strtotime($item->fecha_inicio))) . '</td>';
                    echo '<td>' . esc_html($item->ubicacion ?: '-') . '</td>';
                    break;
                case 'courses':
                    echo '<td><strong>' . esc_html($item->titulo) . '</strong></td>';
                    echo '<td>' . esc_html($item->categoria ?: '-') . '</td>';
                    echo '<td>' . esc_html(ucfirst($item->modalidad)) . '</td>';
                    echo '<td>' . ($item->es_gratuito ? __('Gratis', 'flavor-chat-ia') : number_format($item->precio, 2) . ' €') . '</td>';
                    break;
                case 'marketplace':
                    echo '<td><strong>' . esc_html($item->titulo) . '</strong></td>';
                    echo '<td>' . esc_html(ucfirst($item->tipo)) . '</td>';
                    echo '<td>' . ($item->es_gratuito ? __('Gratis', 'flavor-chat-ia') : ($item->precio ? number_format($item->precio, 2) . ' €' : '-')) . '</td>';
                    break;
                case 'time_bank':
                    echo '<td><strong>' . esc_html($item->titulo) . '</strong></td>';
                    echo '<td>' . esc_html(ucfirst($item->tipo)) . '</td>';
                    echo '<td>' . number_format($item->horas_estimadas, 1) . ' h</td>';
                    break;
                default:
                    $titulo = $item->titulo ?? $item->nombre ?? '-';
                    $descripcion = $item->descripcion ?? '';
                    echo '<td><strong>' . esc_html($titulo) . '</strong></td>';
                    echo '<td>' . esc_html(wp_trim_words($descripcion, 10)) . '</td>';
            }

            echo '<td><span class="node-badge">' . esc_html(substr($item->nodo_id, 0, 8)) . '...</span></td>';
            echo '<td>' . esc_html(human_time_diff(strtotime($item->actualizado_en))) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    /**
     * Obtiene estadísticas de contenido federado
     */
    private function get_federation_stats() {
        global $wpdb;

        $stats = [];
        $nodo_local = get_option('flavor_network_node_id', '');

        foreach ($this->content_types as $type => $config) {
            $tabla = $wpdb->prefix . $config['table'];

            if ($wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla) {
                $stats[$type] = (int) $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabla} WHERE nodo_id != %s AND visible_en_red = 1",
                    $nodo_local
                ));
            } else {
                $stats[$type] = 0;
            }
        }

        return $stats;
    }

    /**
     * AJAX: Toggle visibilidad de un item federado
     */
    public function ajax_toggle_visibility() {
        check_ajax_referer('federation_toggle', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        global $wpdb;

        $id = absint($_POST['id']);
        $type = sanitize_key($_POST['type']);

        if (!isset($this->content_types[$type])) {
            wp_send_json_error('Tipo inválido');
        }

        $tabla = $wpdb->prefix . $this->content_types[$type]['table'];

        // Obtener estado actual
        $visible = $wpdb->get_var($wpdb->prepare(
            "SELECT visible_en_red FROM {$tabla} WHERE id = %d",
            $id
        ));

        // Toggle
        $nuevo_estado = $visible ? 0 : 1;
        $wpdb->update($tabla, ['visible_en_red' => $nuevo_estado], ['id' => $id]);

        wp_send_json_success(['visible' => (bool) $nuevo_estado]);
    }

    /**
     * AJAX: Sincronizar ahora
     */
    public function ajax_sync_now() {
        check_ajax_referer('federation_sync', '_wpnonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sin permisos');
        }

        // Ejecutar sincronización
        do_action('flavor_network_sync_peers');

        // Actualizar timestamp
        update_option('flavor_network_last_sync', time());

        wp_send_json_success([
            'message' => __('Sincronización completada', 'flavor-chat-ia'),
        ]);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_Network_Federation_Admin::get_instance();
    }
});
