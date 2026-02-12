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
        // Registrar shortcode de formularios
        add_shortcode('flavor_module_form', [$this, 'render_module_form']);
        // Registrar handler AJAX para formularios
        add_action('wp_ajax_flavor_module_action', [$this, 'handle_form_submission']);
        add_action('wp_ajax_nopriv_flavor_module_action', [$this, 'handle_form_submission']);
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
     * Maneja el envío de formularios vía AJAX
     */
    public function handle_form_submission() {
        // Verificar nonce
        $module_id = sanitize_text_field($_POST['flavor_module'] ?? '');

        if (!check_ajax_referer('flavor_module_action_' . $module_id, 'flavor_nonce', false)) {
            wp_send_json_error(__('Token de seguridad inválido', 'flavor-chat-ia'));
            return;
        }

        $action = sanitize_text_field($_POST['flavor_action'] ?? '');

        if (empty($module_id) || empty($action)) {
            wp_send_json_error(__('Datos incompletos', 'flavor-chat-ia'));
            return;
        }

        // Obtener módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            wp_send_json_error(__('Sistema no disponible', 'flavor-chat-ia'));
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($module_id);

        if (!$instance) {
            wp_send_json_error(__('Módulo no encontrado', 'flavor-chat-ia'));
            return;
        }

        // Verificar que el usuario tenga permisos
        if (class_exists('Flavor_Module_Access_Control')) {
            $control = Flavor_Module_Access_Control::get_instance();
            if (!$control->user_can_access($module_id)) {
                wp_send_json_error(__('No tienes permisos para esta acción', 'flavor-chat-ia'));
                return;
            }
        }

        // Preparar parámetros
        $params = [];
        foreach ($_POST as $key => $value) {
            if (!in_array($key, ['action', 'flavor_module', 'flavor_action', 'flavor_nonce'])) {
                $params[$key] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
        }

        // Ejecutar acción del módulo
        try {
            if (method_exists($instance, 'execute_action')) {
                $result = $instance->execute_action($action, $params);
            } else {
                $result = ['success' => false, 'message' => __('Módulo no soporta acciones', 'flavor-chat-ia')];
            }

            if (is_array($result)) {
                if ($result['success'] ?? false) {
                    wp_send_json_success([
                        'message' => $result['message'] ?? __('Operación completada', 'flavor-chat-ia'),
                        'data' => $result['data'] ?? [],
                        'redirect' => $result['redirect'] ?? '',
                    ]);
                } else {
                    wp_send_json_error($result['message'] ?? __('Error al procesar', 'flavor-chat-ia'));
                }
            } else {
                wp_send_json_error(__('Respuesta inválida del módulo', 'flavor-chat-ia'));
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

    /**
     * Renderiza un formulario de módulo
     * Uso: [flavor_module_form module="eventos" action="inscribirse_evento"]
     */
    public function render_module_form($atts) {
        $atts = shortcode_atts([
            'module' => '',
            'action' => '',
            'titulo' => '',
            'descripcion' => '',
            'mostrar_titulo' => 'yes',
        ], $atts);

        if (empty($atts['module']) || empty($atts['action'])) {
            return '<div class="flavor-error">' . __('Error: Debes especificar module y action', 'flavor-chat-ia') . '</div>';
        }

        // Obtener instancia del módulo
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return '<div class="flavor-error">' . __('Error: Module Loader no disponible', 'flavor-chat-ia') . '</div>';
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $instance = $loader->get_module($atts['module']);

        if (!$instance) {
            return '<div class="flavor-error">' . sprintf(__('Error: Módulo "%s" no encontrado', 'flavor-chat-ia'), $atts['module']) . '</div>';
        }

        // Verificar que el módulo tenga el método get_form_config
        if (!method_exists($instance, 'get_form_config')) {
            return '<div class="flavor-error">' . __('Error: Este módulo no soporta formularios', 'flavor-chat-ia') . '</div>';
        }

        // Obtener configuración del formulario
        $form_config = $instance->get_form_config($atts['action']);

        if (!$form_config) {
            return '<div class="flavor-error">' . sprintf(__('Error: Acción "%s" no tiene configuración de formulario', 'flavor-chat-ia'), $atts['action']) . '</div>';
        }

        // Preparar datos del formulario
        $form_data = [
            'module_id' => $atts['module'],
            'action' => $atts['action'],
            'title' => $atts['titulo'] ?: ($form_config['title'] ?? ucfirst($atts['action'])),
            'description' => $atts['descripcion'] ?: ($form_config['description'] ?? ''),
            'fields' => $form_config['fields'] ?? [],
            'submit_text' => $form_config['submit_text'] ?? __('Enviar', 'flavor-chat-ia'),
            'ajax' => $form_config['ajax'] ?? true,
        ];

        // Renderizar el formulario
        ob_start();
        $this->render_form_html($form_data, $atts);
        return ob_get_clean();
    }

    /**
     * Renderiza el HTML del formulario
     */
    private function render_form_html($form_data, $atts) {
        ?>
        <div class="flavor-module-form-wrapper">
            <?php if ($atts['mostrar_titulo'] === 'yes' && !empty($form_data['title'])) : ?>
                <div class="flavor-form-header">
                    <h3 class="flavor-form-title"><?php echo esc_html($form_data['title']); ?></h3>
                    <?php if (!empty($form_data['description'])) : ?>
                        <p class="flavor-form-description"><?php echo esc_html($form_data['description']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="flavor-module-form"
                  data-module="<?php echo esc_attr($form_data['module_id']); ?>"
                  data-action="<?php echo esc_attr($form_data['action']); ?>"
                  data-ajax="<?php echo $form_data['ajax'] ? '1' : '0'; ?>"
                  method="post">

                <?php wp_nonce_field('flavor_module_action_' . $form_data['module_id'], 'flavor_nonce'); ?>
                <input type="hidden" name="flavor_module" value="<?php echo esc_attr($form_data['module_id']); ?>">
                <input type="hidden" name="flavor_action" value="<?php echo esc_attr($form_data['action']); ?>">

                <div class="flavor-form-fields">
                    <?php foreach ($form_data['fields'] as $field_name => $field_config) : ?>
                        <?php $this->render_form_field($field_name, $field_config); ?>
                    <?php endforeach; ?>
                </div>

                <div class="flavor-form-messages"></div>

                <div class="flavor-form-actions">
                    <button type="submit" class="flavor-button flavor-button--primary">
                        <?php echo esc_html($form_data['submit_text']); ?>
                    </button>
                </div>
            </form>
        </div>

        <style>
        .flavor-module-form-wrapper {
            max-width: 600px;
            margin: 0 auto;
            padding: 30px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .flavor-form-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .flavor-form-title {
            font-size: 28px;
            font-weight: 700;
            color: #111827;
            margin: 0 0 12px;
        }
        .flavor-form-description {
            font-size: 16px;
            color: #6b7280;
            margin: 0;
        }
        .flavor-form-fields {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .flavor-form-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .flavor-form-label {
            font-weight: 600;
            color: #374151;
            font-size: 14px;
        }
        .flavor-form-label--required::after {
            content: " *";
            color: #ef4444;
        }
        .flavor-form-input,
        .flavor-form-textarea,
        .flavor-form-select {
            padding: 12px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.2s;
        }
        .flavor-form-input:focus,
        .flavor-form-textarea:focus,
        .flavor-form-select:focus {
            outline: none;
            border-color: var(--flavor-primary, #3b82f6);
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .flavor-form-textarea {
            min-height: 120px;
            resize: vertical;
        }
        .flavor-form-help {
            font-size: 13px;
            color: #6b7280;
            margin-top: 4px;
        }
        .flavor-form-messages {
            margin: 20px 0;
        }
        .flavor-message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        .flavor-message--success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        .flavor-message--error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        .flavor-form-actions {
            margin-top: 24px;
        }
        .flavor-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 14px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }
        .flavor-button--primary {
            background: var(--flavor-primary, #3b82f6);
            color: white;
        }
        .flavor-button--primary:hover {
            background: var(--flavor-primary-dark, #2563eb);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
        }
        .flavor-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        @media (max-width: 640px) {
            .flavor-module-form-wrapper {
                padding: 20px;
            }
            .flavor-form-title {
                font-size: 24px;
            }
        }
        </style>

        <script>
        (function() {
            document.addEventListener('DOMContentLoaded', function() {
                const forms = document.querySelectorAll('.flavor-module-form');

                forms.forEach(function(form) {
                    if (form.dataset.ajax !== '1') return;

                    form.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const button = form.querySelector('button[type="submit"]');
                        const messages = form.querySelector('.flavor-form-messages');
                        const formData = new FormData(form);

                        formData.append('action', 'flavor_module_action');

                        button.disabled = true;
                        button.textContent = '<?php _e('Enviando...', 'flavor-chat-ia'); ?>';
                        messages.innerHTML = '';

                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                messages.innerHTML = '<div class="flavor-message flavor-message--success">' +
                                    (data.data.message || '<?php _e('Operación completada con éxito', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                                form.reset();

                                // Redirigir si se especifica
                                if (data.data.redirect) {
                                    setTimeout(function() {
                                        window.location.href = data.data.redirect;
                                    }, 1500);
                                }
                            } else {
                                messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                    (data.data || '<?php _e('Error al procesar el formulario', 'flavor-chat-ia'); ?>') +
                                    '</div>';
                            }
                        })
                        .catch(error => {
                            messages.innerHTML = '<div class="flavor-message flavor-message--error">' +
                                '<?php _e('Error de conexión', 'flavor-chat-ia'); ?>' +
                                '</div>';
                        })
                        .finally(function() {
                            button.disabled = false;
                            button.textContent = '<?php echo esc_js($form_data['submit_text'] ?? __('Enviar', 'flavor-chat-ia')); ?>';
                        });
                    });
                });
            });
        })();
        </script>
        <?php
    }

    /**
     * Renderiza un campo del formulario
     */
    private function render_form_field($field_name, $field_config) {
        $type = $field_config['type'] ?? 'text';
        $label = $field_config['label'] ?? ucfirst(str_replace('_', ' ', $field_name));
        $required = $field_config['required'] ?? false;
        $placeholder = $field_config['placeholder'] ?? '';
        $help = $field_config['help'] ?? '';
        $options = $field_config['options'] ?? [];
        $value = $field_config['value'] ?? '';

        ?>
        <div class="flavor-form-field">
            <label for="flavor_field_<?php echo esc_attr($field_name); ?>"
                   class="flavor-form-label <?php echo $required ? 'flavor-form-label--required' : ''; ?>">
                <?php echo esc_html($label); ?>
            </label>

            <?php if ($type === 'textarea') : ?>
                <textarea
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-textarea"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['rows'])) : ?>rows="<?php echo intval($field_config['rows']); ?>"<?php endif; ?>
                ><?php echo esc_textarea($value); ?></textarea>

            <?php elseif ($type === 'select') : ?>
                <select
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-select"
                    <?php echo $required ? 'required' : ''; ?>>
                    <?php if (!$required || $placeholder) : ?>
                        <option value=""><?php echo esc_html($placeholder ?: __('Selecciona una opción', 'flavor-chat-ia')); ?></option>
                    <?php endif; ?>
                    <?php foreach ($options as $opt_value => $opt_label) : ?>
                        <option value="<?php echo esc_attr($opt_value); ?>" <?php selected($value, $opt_value); ?>>
                            <?php echo esc_html($opt_label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

            <?php else : ?>
                <input
                    type="<?php echo esc_attr($type); ?>"
                    id="flavor_field_<?php echo esc_attr($field_name); ?>"
                    name="<?php echo esc_attr($field_name); ?>"
                    class="flavor-form-input"
                    placeholder="<?php echo esc_attr($placeholder); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    <?php echo $required ? 'required' : ''; ?>
                    <?php if (isset($field_config['min'])) : ?>min="<?php echo esc_attr($field_config['min']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['max'])) : ?>max="<?php echo esc_attr($field_config['max']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['step'])) : ?>step="<?php echo esc_attr($field_config['step']); ?>"<?php endif; ?>
                    <?php if (isset($field_config['pattern'])) : ?>pattern="<?php echo esc_attr($field_config['pattern']); ?>"<?php endif; ?>
                >
            <?php endif; ?>

            <?php if ($help) : ?>
                <span class="flavor-form-help"><?php echo esc_html($help); ?></span>
            <?php endif; ?>
        </div>
        <?php
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
