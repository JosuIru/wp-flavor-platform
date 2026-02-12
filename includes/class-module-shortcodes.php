<?php
/**
 * Shortcodes Automáticos de Módulos
 *
 * Registra shortcodes automáticamente para cada módulo activo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para shortcodes automáticos de módulos
 */
class Flavor_Module_Shortcodes {

    /**
     * Instancia singleton
     */
    private static $instance = null;

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
     * Constructor privado
     */
    private function __construct() {
        add_action('init', [$this, 'register_module_shortcodes'], 20);
    }

    /**
     * Registra shortcodes para todos los módulos activos
     */
    public function register_module_shortcodes() {
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulos = $loader->get_loaded_modules();

        foreach ($modulos as $id => $instance) {
            // Registrar shortcode: [flavor_{modulo_id}]
            $shortcode_name = 'flavor_' . $id;
            add_shortcode($shortcode_name, function($atts) use ($id, $instance) {
                return $this->render_module_shortcode($id, $instance, $atts);
            });
        }
    }

    /**
     * Renderiza el shortcode de un módulo
     */
    private function render_module_shortcode($modulo_id, $instance, $atts) {
        $atts = shortcode_atts([
            'vista' => 'grid', // grid, lista, calendario
            'limite' => '12',
            'columnas' => '3',
            'mostrar_filtros' => 'yes',
            'tipo' => '', // Para filtrar por tipo específico
        ], $atts);

        // Buscar template del módulo
        $template_paths = [
            // Componente específico
            FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/{$modulo_id}-{$atts['vista']}.php",
            FLAVOR_CHAT_IA_PATH . "templates/components/{$modulo_id}/grid.php",

            // Frontend archive
            FLAVOR_CHAT_IA_PATH . "templates/frontend/{$modulo_id}/archive.php",

            // Fallback genérico
            FLAVOR_CHAT_IA_PATH . "templates/components/unified/_generic-grid.php",
        ];

        $template = null;
        foreach ($template_paths as $path) {
            if (file_exists($path)) {
                $template = $path;
                break;
            }
        }

        if (!$template) {
            return $this->render_fallback($modulo_id, $instance, $atts);
        }

        // Preparar datos para el template
        $data = $this->prepare_module_data($modulo_id, $instance, $atts);

        // Renderizar template
        ob_start();

        // Hacer disponibles las variables en el scope del template
        extract($data);

        // Wrapper para estilos consistentes
        echo '<div class="flavor-module-shortcode flavor-module-' . esc_attr($modulo_id) . '">';
        include $template;
        echo '</div>';

        return ob_get_clean();
    }

    /**
     * Prepara los datos del módulo para el template
     */
    private function prepare_module_data($modulo_id, $instance, $atts) {
        global $wpdb;

        $data = [
            'modulo_id' => $modulo_id,
            'modulo_nombre' => $instance->name ?? ucfirst(str_replace('_', ' ', $modulo_id)),
            'atts' => $atts,
            'items' => [],
        ];

        // Intentar obtener datos del módulo
        $tabla = $wpdb->prefix . 'flavor_' . $modulo_id;

        if ($this->tabla_existe($tabla)) {
            $limite = intval($atts['limite']);
            $tipo = sanitize_text_field($atts['tipo']);

            $where = "WHERE 1=1";
            $params = [];

            // Filtro por estado publicado si existe la columna
            $columns = $wpdb->get_col("DESCRIBE {$tabla}");
            if (in_array('estado', $columns)) {
                $where .= " AND estado = %s";
                $params[] = 'publicado';
            }

            // Filtro por tipo si se especifica
            if (!empty($tipo) && in_array('tipo', $columns)) {
                $where .= " AND tipo = %s";
                $params[] = $tipo;
            }

            // Filtro por fecha futura para eventos
            if ($modulo_id === 'eventos' && in_array('fecha_inicio', $columns)) {
                $where .= " AND fecha_inicio >= %s";
                $params[] = current_time('mysql');
            }

            // Orden
            $order = "ORDER BY id DESC";
            if (in_array('fecha_inicio', $columns)) {
                $order = "ORDER BY fecha_inicio ASC";
            } elseif (in_array('fecha_creacion', $columns)) {
                $order = "ORDER BY fecha_creacion DESC";
            } elseif (in_array('created_at', $columns)) {
                $order = "ORDER BY created_at DESC";
            }

            $query = "SELECT * FROM {$tabla} {$where} {$order} LIMIT {$limite}";

            if (!empty($params)) {
                $query = $wpdb->prepare($query, ...$params);
            }

            $data['items'] = $wpdb->get_results($query, ARRAY_A);
        }

        // Si no hay datos reales, proporcionar datos de ejemplo según el módulo
        if (empty($data['items'])) {
            $data['items'] = $this->get_sample_data($modulo_id);
        }

        return $data;
    }

    /**
     * Verifica si una tabla existe
     */
    private function tabla_existe($tabla) {
        global $wpdb;
        $query = $wpdb->prepare('SHOW TABLES LIKE %s', $tabla);
        return $wpdb->get_var($query) === $tabla;
    }

    /**
     * Obtiene datos de ejemplo para un módulo
     */
    private function get_sample_data($modulo_id) {
        switch ($modulo_id) {
            case 'eventos':
                return [
                    ['id'=>1, 'titulo'=>'Conferencia de Tecnología', 'tipo'=>'conferencia', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+3 days')), 'ubicacion'=>'Centro de Convenciones', 'precio'=>15.00, 'aforo_maximo'=>100, 'inscritos_count'=>45, 'estado'=>'publicado', 'descripcion'=>'Últimas tendencias en tecnología'],
                    ['id'=>2, 'titulo'=>'Taller de Cerámica', 'tipo'=>'taller', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'ubicacion'=>'Sala de Artes', 'precio'=>25.00, 'aforo_maximo'=>20, 'inscritos_count'=>18, 'estado'=>'publicado', 'descripcion'=>'Técnicas de cerámica artesanal'],
                    ['id'=>3, 'titulo'=>'Charla: Alimentación Saludable', 'tipo'=>'charla', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+7 days')), 'ubicacion'=>'Biblioteca', 'precio'=>0, 'aforo_maximo'=>50, 'inscritos_count'=>12, 'estado'=>'publicado', 'descripcion'=>'Consejos para una dieta equilibrada'],
                ];

            case 'talleres':
                return [
                    ['id'=>1, 'titulo'=>'Taller de Fotografía', 'categoria'=>'Arte', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+5 days')), 'plazas_disponibles'=>15, 'precio'=>30.00],
                    ['id'=>2, 'titulo'=>'Cocina Mediterránea', 'categoria'=>'Gastronomía', 'fecha_inicio'=>date('Y-m-d H:i:s', strtotime('+10 days')), 'plazas_disponibles'=>12, 'precio'=>40.00],
                ];

            case 'grupos_consumo':
                return [
                    ['id'=>1, 'nombre'=>'Grupo Ecológico Norte', 'descripcion'=>'Productos ecológicos locales', 'miembros'=>25],
                    ['id'=>2, 'nombre'=>'Cooperativa Sur', 'descripcion'=>'Compra conjunta de productos frescos', 'miembros'=>40],
                ];

            default:
                return [];
        }
    }

    /**
     * Renderiza fallback cuando no hay template
     */
    private function render_fallback($modulo_id, $instance, $atts) {
        $nombre = $instance->name ?? ucfirst(str_replace('_', ' ', $modulo_id));

        ob_start();
        ?>
        <div class="flavor-module-fallback" style="padding: 40px 20px; text-align: center; background: #f9fafb; border-radius: 12px; border: 1px dashed #e5e7eb;">
            <h3 style="font-size: 24px; font-weight: 600; margin: 0 0 12px; color: #111827;">
                <?php echo esc_html($nombre); ?>
            </h3>
            <p style="color: #6b7280; margin: 0 0 20px;">
                <?php echo esc_html($instance->description ?? __('Módulo disponible', 'flavor-chat-ia')); ?>
            </p>
            <a href="<?php echo esc_url(home_url('/' . str_replace('_', '-', $modulo_id) . '/')); ?>"
               class="flavor-button"
               style="display: inline-block; padding: 12px 24px; background: #3b82f6; color: white; border-radius: 8px; text-decoration: none; font-weight: 500;">
                <?php _e('Ver', 'flavor-chat-ia'); ?> →
            </a>
        </div>
        <?php
        return ob_get_clean();
    }
}
