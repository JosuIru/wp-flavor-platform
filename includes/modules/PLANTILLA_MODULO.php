<?php
/**
 * PLANTILLA PARA CREAR NUEVOS MÓDULOS
 *
 * INSTRUCCIONES:
 * 1. Copia este archivo a: includes/modules/mi-modulo/class-mi-modulo-module.php
 * 2. Busca y reemplaza "MiModulo" por el nombre de tu módulo (PascalCase)
 * 3. Busca y reemplaza "mi_modulo" por el ID de tu módulo (snake_case)
 * 4. Busca y reemplaza "Mi Módulo" por el nombre visible de tu módulo
 * 5. Implementa la lógica de tu módulo
 * 6. Registra el módulo en class-module-loader.php
 * 7. Añádelo a un perfil en class-app-profiles.php
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Módulo: Mi Módulo
 * Descripción: [Describe aquí qué hace tu módulo]
 */
class Flavor_Platform_MiModulo_Module extends Flavor_Platform_Module_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'mi_modulo'; // ID único (snake_case, solo letras y guiones bajos)
        $this->name = __('Mi Módulo', 'flavor-platform'); // Nombre visible
        $this->description = __('Descripción breve de qué hace este módulo.', 'flavor-platform');

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * Verifica si el módulo puede activarse
     * Retorna true si todas las dependencias están disponibles
     */
    public function can_activate() {
        // Ejemplo: verificar si existe un plugin necesario
        // return class_exists('WooCommerce');

        // Ejemplo: verificar si existe una tabla personalizada
        // global $wpdb;
        // $tabla = $wpdb->prefix . 'mi_modulo_datos';
        // return $wpdb->get_var("SHOW TABLES LIKE '$tabla'") === $tabla;

        return true; // Sin dependencias
    }

    /**
     * {@inheritdoc}
     *
     * Mensaje que se muestra si el módulo no puede activarse
     */
    public function get_activation_error() {
        if (!$this->can_activate()) {
            return __('Este módulo requiere [dependencia] para funcionar.', 'flavor-platform');
        }
        return '';
    }

    /**
     * {@inheritdoc}
     *
     * Configuración por defecto del módulo
     * Se guarda en: wp_options -> flavor_chat_ia_module_mi_modulo
     */
    protected function get_default_settings() {
        return [
            'opcion_1' => true,
            'opcion_2' => 'valor_por_defecto',
            'limite_resultados' => 10,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Inicialización del módulo
     * Se ejecuta cuando el módulo está activo
     */
    public function init() {
        // Registrar Custom Post Types
        add_action('init', [$this, 'registrar_custom_post_types']);

        // Registrar Taxonomías
        add_action('init', [$this, 'registrar_taxonomias']);

        // Registrar Meta Boxes (Custom Fields)
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes']);
        add_action('save_post_mi_cpt', [$this, 'guardar_meta_boxes']);

        // Shortcodes
        add_shortcode('mi_modulo_listado', [$this, 'shortcode_listado']);

        // AJAX (si necesitas)
        add_action('wp_ajax_mi_modulo_accion', [$this, 'ajax_handler']);
        add_action('wp_ajax_nopriv_mi_modulo_accion', [$this, 'ajax_handler']);

        // Hooks personalizados
        // add_action('mi_evento_personalizado', [$this, 'on_evento']);
    }

    /**
     * Registra Custom Post Types si es necesario
     */
    public function registrar_custom_post_types() {
        // Ejemplo de CPT
        /*
        register_post_type('mi_cpt', [
            'labels' => [
                'name' => __('Mis Items', 'flavor-platform'),
                'singular_name' => __('Item', 'flavor-platform'),
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
            'menu_icon' => 'dashicons-admin-generic',
            'show_in_rest' => true,
        ]);
        */
    }

    /**
     * Registra Taxonomías si es necesario
     */
    public function registrar_taxonomias() {
        // Ejemplo de taxonomía
        /*
        register_taxonomy('mi_categoria', 'mi_cpt', [
            'labels' => [
                'name' => __('Categorías', 'flavor-platform'),
                'singular_name' => __('Categoría', 'flavor-platform'),
            ],
            'hierarchical' => true,
            'show_in_rest' => true,
        ]);
        */
    }

    /**
     * Registra Meta Boxes (Custom Fields)
     */
    public function registrar_meta_boxes() {
        // Ejemplo de meta box
        /*
        add_meta_box(
            'mi_meta_box',
            __('Información Adicional', 'flavor-platform'),
            [$this, 'renderizar_meta_box'],
            'mi_cpt',
            'normal',
            'high'
        );
        */
    }

    /**
     * Renderiza el contenido del Meta Box
     */
    public function renderizar_meta_box($post) {
        // Ejemplo de campos
        /*
        wp_nonce_field('mi_meta_nonce', 'mi_meta_nonce_field');
        $valor = get_post_meta($post->ID, '_mi_campo', true);
        ?>
        <label for="mi_campo"><?php _e('Mi Campo', 'flavor-platform'); ?></label>
        <input type="text" id="mi_campo" name="mi_campo"
               value="<?php echo esc_attr($valor); ?>" class="widefat" />
        <?php
        */
    }

    /**
     * Guarda los datos del Meta Box
     */
    public function guardar_meta_boxes($post_id) {
        // Verificaciones de seguridad
        if (!isset($_POST['mi_meta_nonce_field']) ||
            !wp_verify_nonce($_POST['mi_meta_nonce_field'], 'mi_meta_nonce')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Guardar campos
        if (isset($_POST['mi_campo'])) {
            update_post_meta($post_id, '_mi_campo', sanitize_text_field($_POST['mi_campo']));
        }
    }

    /**
     * {@inheritdoc}
     *
     * Define las acciones disponibles del módulo
     * Estas acciones pueden ser llamadas por el chat IA
     */
    public function get_actions() {
        return [
            'buscar' => [
                'description' => 'Buscar items en el módulo',
                'params' => ['busqueda', 'limite'],
            ],
            'crear' => [
                'description' => 'Crear un nuevo item',
                'params' => ['titulo', 'descripcion'],
            ],
            'obtener' => [
                'description' => 'Obtener detalles de un item',
                'params' => ['item_id'],
            ],
            'actualizar' => [
                'description' => 'Actualizar un item existente',
                'params' => ['item_id', 'datos'],
            ],
            'eliminar' => [
                'description' => 'Eliminar un item',
                'params' => ['item_id'],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Ejecuta una acción del módulo
     */
    public function execute_action($nombre_accion, $parametros) {
        $metodo_accion = 'action_' . $nombre_accion;

        if (method_exists($this, $metodo_accion)) {
            return $this->$metodo_accion($parametros);
        }

        return [
            'success' => false,
            'error' => sprintf(__('Acción no implementada: %s', 'flavor-platform'), $nombre_accion),
        ];
    }

    /**
     * Acción: Buscar items
     */
    private function action_buscar($parametros) {
        $busqueda = sanitize_text_field($parametros['busqueda'] ?? '');
        $limite = absint($parametros['limite'] ?? 10);

        // Implementa tu lógica de búsqueda aquí
        // Ejemplo con WP_Query:
        /*
        $args = [
            'post_type' => 'mi_cpt',
            'posts_per_page' => $limite,
            's' => $busqueda,
        ];
        $query = new WP_Query($args);
        $resultados = [];
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $resultados[] = [
                    'id' => get_the_ID(),
                    'titulo' => get_the_title(),
                    'url' => get_permalink(),
                ];
            }
            wp_reset_postdata();
        }
        */

        return [
            'success' => true,
            'total' => 0, // count($resultados)
            'resultados' => [], // $resultados
            'mensaje' => sprintf(__('Se encontraron %d items.', 'flavor-platform'), 0),
        ];
    }

    /**
     * Acción: Crear item
     */
    private function action_crear($parametros) {
        // Verificar autenticación si es necesario
        if (!is_user_logged_in()) {
            return [
                'success' => false,
                'error' => __('Debes iniciar sesión para crear items.', 'flavor-platform'),
            ];
        }

        $titulo = sanitize_text_field($parametros['titulo'] ?? '');
        $descripcion = sanitize_textarea_field($parametros['descripcion'] ?? '');

        if (empty($titulo)) {
            return [
                'success' => false,
                'error' => __('El título es obligatorio.', 'flavor-platform'),
            ];
        }

        // Crear post/registro
        /*
        $post_id = wp_insert_post([
            'post_type' => 'mi_cpt',
            'post_title' => $titulo,
            'post_content' => $descripcion,
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ]);

        if (is_wp_error($post_id)) {
            return [
                'success' => false,
                'error' => $post_id->get_error_message(),
            ];
        }
        */

        return [
            'success' => true,
            'item_id' => 0, // $post_id
            'mensaje' => __('Item creado correctamente.', 'flavor-platform'),
        ];
    }

    /**
     * Acción: Obtener detalles
     */
    private function action_obtener($parametros) {
        $item_id = absint($parametros['item_id'] ?? 0);

        if (!$item_id) {
            return [
                'success' => false,
                'error' => __('ID de item no válido.', 'flavor-platform'),
            ];
        }

        // Obtener datos del item
        // $post = get_post($item_id);
        // ...

        return [
            'success' => true,
            'item' => [
                'id' => $item_id,
                'titulo' => '',
                'descripcion' => '',
                // ...más campos
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Define las herramientas (tools) para Claude API
     * Estas definiciones se envían automáticamente al chat IA
     */
    public function get_tool_definitions() {
        return [
            [
                'name' => 'mi_modulo_buscar',
                'description' => 'Busca items en el módulo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'busqueda' => [
                            'type' => 'string',
                            'description' => 'Término de búsqueda',
                        ],
                        'limite' => [
                            'type' => 'integer',
                            'description' => 'Número máximo de resultados',
                            'default' => 10,
                        ],
                    ],
                    'required' => ['busqueda'],
                ],
            ],
            [
                'name' => 'mi_modulo_crear',
                'description' => 'Crea un nuevo item en el módulo',
                'input_schema' => [
                    'type' => 'object',
                    'properties' => [
                        'titulo' => [
                            'type' => 'string',
                            'description' => 'Título del item',
                        ],
                        'descripcion' => [
                            'type' => 'string',
                            'description' => 'Descripción del item',
                        ],
                    ],
                    'required' => ['titulo'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     *
     * Proporciona conocimiento base al sistema de IA
     * Este texto se incluye en el system prompt
     */
    public function get_knowledge_base() {
        return <<<KNOWLEDGE
**Mi Módulo - Guía de Uso**

[Describe aquí cómo funciona tu módulo]

Este módulo permite:
- Funcionalidad 1
- Funcionalidad 2
- Funcionalidad 3

Comandos disponibles:
- "buscar [término]": busca items
- "crear item": crea un nuevo item
- "mostrar [ID]": muestra detalles de un item

Importante:
- Los usuarios deben estar autenticados para crear items
- Hay un límite de X items por usuario
KNOWLEDGE;
    }

    /**
     * {@inheritdoc}
     *
     * Proporciona FAQs (preguntas frecuentes)
     * El chat puede responder estas sin llamar a la API
     */
    public function get_faqs() {
        return [
            [
                'pregunta' => '¿Cómo funciona este módulo?',
                'respuesta' => 'Este módulo te permite [explicación breve].',
            ],
            [
                'pregunta' => '¿Puedo [hacer algo]?',
                'respuesta' => 'Sí/No, [explicación].',
            ],
            [
                'pregunta' => '¿Es gratuito?',
                'respuesta' => 'Sí, todas las funcionalidades son gratuitas.',
            ],
        ];
    }

    /**
     * Shortcode para mostrar listado en frontend
     * Uso: [mi_modulo_listado limite="10"]
     */
    public function shortcode_listado($atributos) {
        $atributos = shortcode_atts([
            'limite' => 10,
            'categoria' => '',
        ], $atributos);

        ob_start();
        ?>
        <div class="mi-modulo-listado">
            <!-- Tu HTML aquí -->
            <p><?php _e('Listado de items...', 'flavor-platform'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handler para peticiones AJAX
     */
    public function ajax_handler() {
        check_ajax_referer('mi_modulo_nonce', 'nonce');

        // Tu lógica AJAX aquí

        wp_send_json_success([
            'mensaje' => __('Operación realizada.', 'flavor-platform'),
        ]);
    }
}

// Legacy alias for backward compatibility
// NOTA: Cambia "MiModulo" por el nombre real de tu módulo
if (!class_exists('Flavor_Chat_MiModulo_Module', false)) {
    class_alias('Flavor_Platform_MiModulo_Module', 'Flavor_Chat_MiModulo_Module');
}

/**
 * CHECKLIST DESPUÉS DE CREAR EL MÓDULO:
 *
 * ✅ 1. Registrar en class-module-loader.php:
 *    En el método discover_modules(), añadir:
 *    'mi_modulo' => [
 *        'file' => $modules_path . 'mi-modulo/class-mi-modulo-module.php',
 *        'class' => 'Flavor_Chat_MiModulo_Module',
 *    ],
 *
 * ✅ 2. Añadir a un perfil en class-app-profiles.php:
 *    En el método definir_perfiles(), añadir 'mi_modulo' a algún perfil
 *
 * ✅ 3. Si necesitas tablas personalizadas:
 *    - Crear archivo: includes/modules/mi-modulo/install.php
 *    - Crear función: mi_modulo_crear_tablas()
 *    - Llamarla desde flavor-chat-ia.php en create_module_tables()
 *
 * ✅ 4. Activar el módulo desde el admin:
 *    WordPress Admin → Flavor Chat IA → Perfil App
 *
 * ✅ 5. Probar con el chat IA
 */
