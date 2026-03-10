<?php
/**
 * Modulo de Recetas para Flavor Chat IA
 *
 * Gestiona recetas que pueden vincularse a productos de WooCommerce
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Modulo de Recetas
 * Permite crear y gestionar recetas vinculables a productos
 *
 * INTEGRACIONES:
 * - Este modulo es un PROVIDER de contenido
 * - Otros modulos pueden vincular recetas a sus entidades
 * - Ejemplo: productos, eventos, talleres, huertos pueden tener recetas vinculadas
 */
class Flavor_Chat_Recetas_Module extends Flavor_Chat_Module_Base {

    use Flavor_Module_Admin_Pages_Trait;
    use Flavor_Module_Integration_Provider;

    /**
     * Constructor
     */
    public function __construct() {
        $this->id = 'recetas';
        $this->name = __('Recetas', 'flavor-chat-ia');
        $this->description = __('Gestiona recetas vinculables a productos. Incluye ingredientes, pasos y tiempos de preparacion.', 'flavor-chat-ia');

        parent::__construct();
    }

    /**
     * Define el tipo de contenido que ofrece este modulo
     * Usado por el sistema de integraciones dinamicas
     */
    protected function get_integration_content_type() {
        return [
            'id'         => 'recetas',
            'label'      => __('Recetas', 'flavor-chat-ia'),
            'icon'       => 'dashicons-carrot',
            'post_type'  => 'flavor_receta',
            'capability' => 'edit_posts',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function can_activate() {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function get_activation_error() {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function get_default_settings() {
        return [
            'mostrar_en_productos' => true,
            'permitir_valoraciones' => true,
            'mostrar_tiempo_preparacion' => true,
            'mostrar_dificultad' => true,
        ];
    }

    /**
     * Configuracion para el Panel Unificado de Gestion
     */
    protected function get_admin_config() {
        return [
            'id' => 'recetas',
            'label' => __('Recetas', 'flavor-chat-ia'),
            'titulo' => __('Recetas', 'flavor-chat-ia'),
            'categoria' => 'recursos',
            'descripcion' => __('Gestionar recetas vinculables a productos', 'flavor-chat-ia'),
            'icon' => 'dashicons-carrot',
            'icono' => 'dashicons-carrot',
            'capability' => 'edit_posts',
            'orden' => 35,
            'render_callback' => [$this, 'render_admin_page'],
            'permisos' => 'edit_posts',
            'paginas' => [
                [
                    'slug' => 'flavor-recetas-dashboard',
                    'titulo' => __('Dashboard', 'flavor-chat-ia'),
                    'callback' => [$this, 'render_admin_page'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function init() {
        // Registrar como proveedor de integraciones
        // Permite que otros modulos vinculen recetas a sus entidades
        $this->register_as_integration_provider();

        // Registrar CPT y taxonomias
        add_action('init', [$this, 'registrar_post_type_receta']);
        add_action('init', [$this, 'registrar_taxonomias']);

        // Meta boxes para recetas
        add_action('add_meta_boxes', [$this, 'registrar_meta_boxes_receta']);
        add_action('save_post_flavor_receta', [$this, 'guardar_meta_receta']);

        // Integracion con WooCommerce
        if (class_exists('WooCommerce')) {
            // Pestana personalizada en productos
            add_filter('woocommerce_product_data_tabs', [$this, 'agregar_tab_producto']);
            add_action('woocommerce_product_data_panels', [$this, 'contenido_tab_producto']);
            add_action('woocommerce_process_product_meta', [$this, 'guardar_meta_producto']);

            // Mostrar recetas en frontend del producto
            add_action('woocommerce_after_single_product_summary', [$this, 'mostrar_recetas_producto'], 15);
        }

        // Registrar en Panel Unificado
        $this->registrar_en_panel_unificado();

        // Shortcodes
        add_shortcode('flavor_recetas', [$this, 'shortcode_listado_recetas']);
        add_shortcode('flavor_receta', [$this, 'shortcode_receta_individual']);

        // AJAX
        add_action('wp_ajax_flavor_buscar_recetas', [$this, 'ajax_buscar_recetas']);

        // Assets
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Registrar Custom Post Type de Recetas
     */
    public function registrar_post_type_receta() {
        $labels = [
            'name'                  => __('Recetas', 'flavor-chat-ia'),
            'singular_name'         => __('Receta', 'flavor-chat-ia'),
            'menu_name'             => __('Recetas', 'flavor-chat-ia'),
            'add_new'               => __('Agregar Nueva', 'flavor-chat-ia'),
            'add_new_item'          => __('Agregar Nueva Receta', 'flavor-chat-ia'),
            'edit_item'             => __('Editar Receta', 'flavor-chat-ia'),
            'new_item'              => __('Nueva Receta', 'flavor-chat-ia'),
            'view_item'             => __('Ver Receta', 'flavor-chat-ia'),
            'search_items'          => __('Buscar Recetas', 'flavor-chat-ia'),
            'not_found'             => __('No se encontraron recetas', 'flavor-chat-ia'),
            'not_found_in_trash'    => __('No hay recetas en la papelera', 'flavor-chat-ia'),
            'all_items'             => __('Todas las Recetas', 'flavor-chat-ia'),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'publicly_queryable'  => true,
            'show_ui'             => true,
            'show_in_menu'        => false, // Lo mostramos en el panel unificado
            'query_var'           => true,
            'rewrite'             => ['slug' => 'receta'],
            'capability_type'     => 'post',
            'has_archive'         => true,
            'hierarchical'        => false,
            'menu_position'       => null,
            'menu_icon'           => 'dashicons-carrot',
            'supports'            => ['title', 'editor', 'thumbnail', 'excerpt'],
            'show_in_rest'        => true,
        ];

        register_post_type('flavor_receta', $args);
    }

    /**
     * Registrar taxonomias para recetas
     */
    public function registrar_taxonomias() {
        // Categoria de receta
        register_taxonomy('receta_categoria', 'flavor_receta', [
            'labels' => [
                'name'              => __('Categorias de Receta', 'flavor-chat-ia'),
                'singular_name'     => __('Categoria', 'flavor-chat-ia'),
                'search_items'      => __('Buscar Categorias', 'flavor-chat-ia'),
                'all_items'         => __('Todas las Categorias', 'flavor-chat-ia'),
                'edit_item'         => __('Editar Categoria', 'flavor-chat-ia'),
                'add_new_item'      => __('Agregar Nueva Categoria', 'flavor-chat-ia'),
            ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'categoria-receta'],
        ]);

        // Insertar categorias por defecto
        $categorias_defecto = [
            'entrantes'    => __('Entrantes', 'flavor-chat-ia'),
            'principales'  => __('Platos Principales', 'flavor-chat-ia'),
            'postres'      => __('Postres', 'flavor-chat-ia'),
            'bebidas'      => __('Bebidas', 'flavor-chat-ia'),
            'ensaladas'    => __('Ensaladas', 'flavor-chat-ia'),
            'sopas'        => __('Sopas y Cremas', 'flavor-chat-ia'),
            'aperitivos'   => __('Aperitivos', 'flavor-chat-ia'),
            'conservas'    => __('Conservas', 'flavor-chat-ia'),
        ];

        foreach ($categorias_defecto as $slug => $nombre) {
            if (!term_exists($slug, 'receta_categoria')) {
                wp_insert_term($nombre, 'receta_categoria', ['slug' => $slug]);
            }
        }

        // Tipo de dieta
        register_taxonomy('receta_dieta', 'flavor_receta', [
            'labels' => [
                'name'              => __('Tipos de Dieta', 'flavor-chat-ia'),
                'singular_name'     => __('Tipo de Dieta', 'flavor-chat-ia'),
            ],
            'hierarchical'      => false,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => ['slug' => 'dieta'],
        ]);

        // Insertar tipos de dieta
        $dietas_defecto = [
            'vegetariana'  => __('Vegetariana', 'flavor-chat-ia'),
            'vegana'       => __('Vegana', 'flavor-chat-ia'),
            'sin-gluten'   => __('Sin Gluten', 'flavor-chat-ia'),
            'sin-lactosa'  => __('Sin Lactosa', 'flavor-chat-ia'),
            'keto'         => __('Keto', 'flavor-chat-ia'),
            'mediterranea' => __('Mediterranea', 'flavor-chat-ia'),
        ];

        foreach ($dietas_defecto as $slug => $nombre) {
            if (!term_exists($slug, 'receta_dieta')) {
                wp_insert_term($nombre, 'receta_dieta', ['slug' => $slug]);
            }
        }
    }

    /**
     * Registrar meta boxes para recetas
     */
    public function registrar_meta_boxes_receta() {
        add_meta_box(
            'flavor_receta_detalles',
            __('Detalles de la Receta', 'flavor-chat-ia'),
            [$this, 'render_meta_box_detalles'],
            'flavor_receta',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_receta_ingredientes',
            __('Ingredientes', 'flavor-chat-ia'),
            [$this, 'render_meta_box_ingredientes'],
            'flavor_receta',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_receta_pasos',
            __('Pasos de Preparacion', 'flavor-chat-ia'),
            [$this, 'render_meta_box_pasos'],
            'flavor_receta',
            'normal',
            'high'
        );

        add_meta_box(
            'flavor_receta_productos',
            __('Productos Vinculados', 'flavor-chat-ia'),
            [$this, 'render_meta_box_productos'],
            'flavor_receta',
            'side',
            'default'
        );

        // Meta box de Grupos de Consumo - siempre registrar, verificar disponibilidad en render
        add_meta_box(
            'flavor_receta_gc_productos',
            __('Productos del Grupo de Consumo', 'flavor-chat-ia'),
            [$this, 'render_meta_box_gc_productos'],
            'flavor_receta',
            'normal',
            'default'
        );

        // Meta box de Videos - siempre registrar, verificar disponibilidad en render
        add_meta_box(
            'flavor_receta_videos',
            __('Videos de la Receta', 'flavor-chat-ia'),
            [$this, 'render_meta_box_videos'],
            'flavor_receta',
            'normal',
            'default'
        );
    }

    /**
     * Verifica si un módulo está activo
     */
    private function is_module_active($module_id) {
        // Para grupos_consumo, verificar si el CPT existe
        if ($module_id === 'grupos_consumo') {
            return post_type_exists('gc_producto');
        }

        // Para multimedia/videos, verificar si la tabla existe
        if ($module_id === 'multimedia' || $module_id === 'videos') {
            global $wpdb;
            return Flavor_Chat_Helpers::tabla_existe($wpdb->prefix . 'flavor_multimedia');
        }

        // Fallback al Module Loader
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return false;
        }
        $loader = Flavor_Chat_Module_Loader::get_instance();
        $active_modules = $loader->get_loaded_modules();
        return isset($active_modules[$module_id]);
    }

    /**
     * Render meta box de detalles
     */
    public function render_meta_box_detalles($post) {
        wp_nonce_field('flavor_receta_meta', 'flavor_receta_nonce');

        $tiempo_preparacion = get_post_meta($post->ID, '_receta_tiempo_preparacion', true);
        $tiempo_coccion = get_post_meta($post->ID, '_receta_tiempo_coccion', true);
        $porciones = get_post_meta($post->ID, '_receta_porciones', true);
        $dificultad = get_post_meta($post->ID, '_receta_dificultad', true);
        $calorias = get_post_meta($post->ID, '_receta_calorias', true);
        ?>
        <table class="form-table">
            <tr>
                <th><label for="receta_tiempo_preparacion"><?php _e('Tiempo de Preparacion (min)', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" id="receta_tiempo_preparacion" name="receta_tiempo_preparacion"
                           value="<?php echo esc_attr($tiempo_preparacion); ?>" min="0" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="receta_tiempo_coccion"><?php _e('Tiempo de Coccion (min)', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" id="receta_tiempo_coccion" name="receta_tiempo_coccion"
                           value="<?php echo esc_attr($tiempo_coccion); ?>" min="0" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="receta_porciones"><?php _e('Porciones', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" id="receta_porciones" name="receta_porciones"
                           value="<?php echo esc_attr($porciones); ?>" min="1" class="small-text" />
                </td>
            </tr>
            <tr>
                <th><label for="receta_dificultad"><?php _e('Dificultad', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <select id="receta_dificultad" name="receta_dificultad">
                        <option value=""><?php _e('Seleccionar...', 'flavor-chat-ia'); ?></option>
                        <option value="facil" <?php selected($dificultad, 'facil'); ?>><?php _e('Facil', 'flavor-chat-ia'); ?></option>
                        <option value="media" <?php selected($dificultad, 'media'); ?>><?php _e('Media', 'flavor-chat-ia'); ?></option>
                        <option value="dificil" <?php selected($dificultad, 'dificil'); ?>><?php _e('Dificil', 'flavor-chat-ia'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="receta_calorias"><?php _e('Calorias por porcion', 'flavor-chat-ia'); ?></label></th>
                <td>
                    <input type="number" id="receta_calorias" name="receta_calorias"
                           value="<?php echo esc_attr($calorias); ?>" min="0" class="small-text" />
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render meta box de ingredientes
     */
    public function render_meta_box_ingredientes($post) {
        $ingredientes = get_post_meta($post->ID, '_receta_ingredientes', true);
        if (!is_array($ingredientes)) {
            $ingredientes = [];
        }
        ?>
        <div id="receta-ingredientes-container">
            <table class="widefat" id="tabla-ingredientes">
                <thead>
                    <tr>
                        <th style="width: 100px;"><?php _e('Cantidad', 'flavor-chat-ia'); ?></th>
                        <th style="width: 80px;"><?php _e('Unidad', 'flavor-chat-ia'); ?></th>
                        <th><?php _e('Ingrediente', 'flavor-chat-ia'); ?></th>
                        <th style="width: 50px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($ingredientes)) {
                        foreach ($ingredientes as $indice => $ingrediente) {
                            $this->render_fila_ingrediente($indice, $ingrediente);
                        }
                    } else {
                        $this->render_fila_ingrediente(0, []);
                    }
                    ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" id="agregar-ingrediente">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php _e('Agregar Ingrediente', 'flavor-chat-ia'); ?>
                </button>
            </p>
        </div>

        <script type="text/template" id="plantilla-ingrediente">
            <?php $this->render_fila_ingrediente('{{INDEX}}', []); ?>
        </script>

        <script>
        jQuery(document).ready(function($) {
            var indiceIngrediente = <?php echo max(0, count($ingredientes) - 1); ?>;

            $('#agregar-ingrediente').on('click', function() {
                indiceIngrediente++;
                var plantilla = $('#plantilla-ingrediente').html();
                plantilla = plantilla.replace(/\{\{INDEX\}\}/g, indiceIngrediente);
                $('#tabla-ingredientes tbody').append(plantilla);
            });

            $(document).on('click', '.eliminar-ingrediente', function() {
                $(this).closest('tr').remove();
            });
        });
        </script>
        <?php
    }

    /**
     * Render fila de ingrediente
     */
    private function render_fila_ingrediente($indice, $ingrediente) {
        $cantidad = isset($ingrediente['cantidad']) ? $ingrediente['cantidad'] : '';
        $unidad = isset($ingrediente['unidad']) ? $ingrediente['unidad'] : '';
        $nombre = isset($ingrediente['nombre']) ? $ingrediente['nombre'] : '';
        ?>
        <tr>
            <td>
                <input type="text" name="receta_ingredientes[<?php echo esc_attr($indice); ?>][cantidad]"
                       value="<?php echo esc_attr($cantidad); ?>" class="small-text" placeholder="2" />
            </td>
            <td>
                <input type="text" name="receta_ingredientes[<?php echo esc_attr($indice); ?>][unidad]"
                       value="<?php echo esc_attr($unidad); ?>" class="small-text" placeholder="kg" />
            </td>
            <td>
                <input type="text" name="receta_ingredientes[<?php echo esc_attr($indice); ?>][nombre]"
                       value="<?php echo esc_attr($nombre); ?>" class="widefat" placeholder="<?php esc_attr_e('Nombre del ingrediente', 'flavor-chat-ia'); ?>" />
            </td>
            <td>
                <button type="button" class="button eliminar-ingrediente" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                    <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
                </button>
            </td>
        </tr>
        <?php
    }

    /**
     * Render meta box de pasos
     */
    public function render_meta_box_pasos($post) {
        $pasos = get_post_meta($post->ID, '_receta_pasos', true);
        if (!is_array($pasos)) {
            $pasos = [];
        }
        ?>
        <div id="receta-pasos-container">
            <div id="lista-pasos">
                <?php
                if (!empty($pasos)) {
                    foreach ($pasos as $indice => $paso) {
                        $this->render_paso($indice, $paso);
                    }
                } else {
                    $this->render_paso(0, '');
                }
                ?>
            </div>
            <p>
                <button type="button" class="button" id="agregar-paso">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php _e('Agregar Paso', 'flavor-chat-ia'); ?>
                </button>
            </p>
        </div>

        <script type="text/template" id="plantilla-paso">
            <?php $this->render_paso('{{INDEX}}', ''); ?>
        </script>

        <script>
        jQuery(document).ready(function($) {
            var indicePaso = <?php echo max(0, count($pasos) - 1); ?>;

            $('#agregar-paso').on('click', function() {
                indicePaso++;
                var plantilla = $('#plantilla-paso').html();
                plantilla = plantilla.replace(/\{\{INDEX\}\}/g, indicePaso);
                $('#lista-pasos').append(plantilla);
                actualizarNumerosPasos();
            });

            $(document).on('click', '.eliminar-paso', function() {
                $(this).closest('.paso-item').remove();
                actualizarNumerosPasos();
            });

            function actualizarNumerosPasos() {
                $('#lista-pasos .paso-item').each(function(i) {
                    $(this).find('.numero-paso').text(i + 1);
                });
            }
        });
        </script>
        <?php
    }

    /**
     * Render paso individual
     */
    private function render_paso($indice, $texto) {
        ?>
        <div class="paso-item" style="display: flex; gap: 10px; margin-bottom: 10px; align-items: flex-start;">
            <span class="numero-paso" style="background: #0073aa; color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-weight: bold;">
                <?php echo is_numeric($indice) ? ($indice + 1) : ''; ?>
            </span>
            <textarea name="receta_pasos[<?php echo esc_attr($indice); ?>]"
                      rows="2" class="widefat"
                      placeholder="<?php esc_attr_e('Describe este paso...', 'flavor-chat-ia'); ?>"><?php echo esc_textarea($texto); ?></textarea>
            <button type="button" class="button eliminar-paso" title="<?php esc_attr_e('Eliminar', 'flavor-chat-ia'); ?>">
                <span class="dashicons dashicons-trash" style="vertical-align: middle;"></span>
            </button>
        </div>
        <?php
    }

    /**
     * Render meta box de productos vinculados
     */
    public function render_meta_box_productos($post) {
        $productos_ids = get_post_meta($post->ID, '_receta_productos_vinculados', true);
        if (!is_array($productos_ids)) {
            $productos_ids = [];
        }

        if (!class_exists('WooCommerce')) {
            echo '<p class="description">' . __('WooCommerce no esta activo. Activa WooCommerce para vincular productos.', 'flavor-chat-ia') . '</p>';
            return;
        }
        ?>
        <p class="description"><?php _e('Selecciona los productos que se usan en esta receta.', 'flavor-chat-ia'); ?></p>

        <div id="productos-vinculados-lista" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 5px; margin: 10px 0;">
            <?php
            if (!empty($productos_ids)) {
                foreach ($productos_ids as $producto_id) {
                    $producto = wc_get_product($producto_id);
                    if ($producto) {
                        ?>
                        <div class="producto-vinculado" style="display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid #eee;">
                            <span><?php echo esc_html($producto->get_name()); ?></span>
                            <button type="button" class="button-link desvincular-producto" data-id="<?php echo esc_attr($producto_id); ?>">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                            <input type="hidden" name="receta_productos_vinculados[]" value="<?php echo esc_attr($producto_id); ?>" />
                        </div>
                        <?php
                    }
                }
            } else {
                echo '<p class="no-productos" style="color: #666; font-style: italic;">' . __('Sin productos vinculados', 'flavor-chat-ia') . '</p>';
            }
            ?>
        </div>

        <p>
            <select id="agregar-producto-selector" class="widefat">
                <option value=""><?php _e('Seleccionar producto...', 'flavor-chat-ia'); ?></option>
                <?php
                $productos = wc_get_products(['limit' => -1, 'status' => 'publish']);
                foreach ($productos as $producto) {
                    if (!in_array($producto->get_id(), $productos_ids)) {
                        echo '<option value="' . esc_attr($producto->get_id()) . '">' . esc_html($producto->get_name()) . '</option>';
                    }
                }
                ?>
            </select>
        </p>
        <p>
            <button type="button" class="button" id="agregar-producto-btn">
                <?php _e('Vincular Producto', 'flavor-chat-ia'); ?>
            </button>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#agregar-producto-btn').on('click', function() {
                var $selector = $('#agregar-producto-selector');
                var productoId = $selector.val();
                var productoNombre = $selector.find('option:selected').text();

                if (!productoId) return;

                $('.no-productos').remove();

                var html = '<div class="producto-vinculado" style="display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid #eee;">' +
                    '<span>' + productoNombre + '</span>' +
                    '<button type="button" class="button-link desvincular-producto" data-id="' + productoId + '">' +
                    '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                    '<input type="hidden" name="receta_productos_vinculados[]" value="' + productoId + '" />' +
                    '</div>';

                $('#productos-vinculados-lista').append(html);
                $selector.find('option:selected').remove();
                $selector.val('');
            });

            $(document).on('click', '.desvincular-producto', function() {
                var productoId = $(this).data('id');
                var productoNombre = $(this).siblings('span').text();
                $(this).closest('.producto-vinculado').remove();

                $('#agregar-producto-selector').append('<option value="' + productoId + '">' + productoNombre + '</option>');

                if ($('#productos-vinculados-lista .producto-vinculado').length === 0) {
                    $('#productos-vinculados-lista').html('<p class="no-productos" style="color: #666; font-style: italic;"><?php echo esc_js(__('Sin productos vinculados', 'flavor-chat-ia')); ?></p>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render meta box de productos de Grupos de Consumo
     */
    public function render_meta_box_gc_productos($post) {
        $productos_gc_ids = get_post_meta($post->ID, '_receta_gc_productos', true);
        if (!is_array($productos_gc_ids)) {
            $productos_gc_ids = [];
        }

        // Obtener productos disponibles
        $productos_gc = get_posts([
            'post_type' => 'gc_producto',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        // Si no hay productos GC y el CPT no existe, mostrar mensaje
        if (empty($productos_gc) && !post_type_exists('gc_producto')) {
            echo '<div class="notice notice-info inline" style="margin: 0;"><p>';
            echo __('El módulo de Grupos de Consumo no está activo o no hay productos disponibles.', 'flavor-chat-ia');
            echo '</p></div>';
            return;
        }

        // Si el CPT existe pero no hay productos
        if (empty($productos_gc)) {
            echo '<div class="notice notice-warning inline" style="margin: 0;"><p>';
            echo __('No hay productos de Grupos de Consumo creados aún.', 'flavor-chat-ia');
            echo ' <a href="' . admin_url('post-new.php?post_type=gc_producto') . '">' . __('Crear producto', 'flavor-chat-ia') . '</a>';
            echo '</p></div>';
            return;
        }
        ?>
        <p class="description"><?php _e('Vincula productos del Grupo de Consumo que se usan en esta receta.', 'flavor-chat-ia'); ?></p>

        <div id="gc-productos-lista" style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <?php
            if (!empty($productos_gc_ids)) {
                foreach ($productos_gc_ids as $producto_id) {
                    $producto = get_post($producto_id);
                    if ($producto && $producto->post_type === 'gc_producto') {
                        $precio = get_post_meta($producto_id, '_gc_precio', true);
                        $unidad = get_post_meta($producto_id, '_gc_unidad', true);
                        $productor_id = get_post_meta($producto_id, '_gc_productor_id', true);
                        $productor_nombre = $productor_id ? get_the_title($productor_id) : '';
                        ?>
                        <div class="gc-producto-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee; background: #f9f9f9; margin-bottom: 5px; border-radius: 4px;">
                            <div>
                                <strong><?php echo esc_html($producto->post_title); ?></strong>
                                <?php if ($precio): ?>
                                    <span style="color: #0073aa; margin-left: 10px;"><?php echo esc_html($precio); ?>€/<?php echo esc_html($unidad ?: 'ud'); ?></span>
                                <?php endif; ?>
                                <?php if ($productor_nombre): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($productor_nombre); ?></small>
                                <?php endif; ?>
                            </div>
                            <button type="button" class="button-link desvincular-gc-producto" data-id="<?php echo esc_attr($producto_id); ?>" title="<?php esc_attr_e('Quitar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                            </button>
                            <input type="hidden" name="receta_gc_productos[]" value="<?php echo esc_attr($producto_id); ?>" />
                        </div>
                        <?php
                    }
                }
            } else {
                echo '<p class="no-gc-productos" style="color: #666; font-style: italic; margin: 0;">' . __('Sin productos de Grupos de Consumo vinculados', 'flavor-chat-ia') . '</p>';
            }
            ?>
        </div>

        <div style="display: flex; gap: 5px;">
            <select id="agregar-gc-producto-selector" class="widefat" style="flex: 1;">
                <option value=""><?php _e('Seleccionar producto del grupo...', 'flavor-chat-ia'); ?></option>
                <?php
                // Usar $productos_gc ya obtenido arriba
                foreach ($productos_gc as $producto) {
                    if (!in_array($producto->ID, $productos_gc_ids)) {
                        $precio = get_post_meta($producto->ID, '_gc_precio', true);
                        $unidad = get_post_meta($producto->ID, '_gc_unidad', true);
                        $precio_str = $precio ? " ({$precio}€/{$unidad})" : '';
                        echo '<option value="' . esc_attr($producto->ID) . '">' . esc_html($producto->post_title . $precio_str) . '</option>';
                    }
                }
                ?>
            </select>
            <button type="button" class="button" id="agregar-gc-producto-btn">
                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
            </button>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#agregar-gc-producto-btn').on('click', function() {
                var $selector = $('#agregar-gc-producto-selector');
                var productoId = $selector.val();
                var productoNombre = $selector.find('option:selected').text();

                if (!productoId) return;

                $('.no-gc-productos').remove();

                var html = '<div class="gc-producto-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee; background: #f9f9f9; margin-bottom: 5px; border-radius: 4px;">' +
                    '<div><strong>' + productoNombre + '</strong></div>' +
                    '<button type="button" class="button-link desvincular-gc-producto" data-id="' + productoId + '">' +
                    '<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>' +
                    '</button>' +
                    '<input type="hidden" name="receta_gc_productos[]" value="' + productoId + '" />' +
                    '</div>';

                $('#gc-productos-lista').append(html);
                $selector.find('option:selected').remove();
                $selector.val('');
            });

            $(document).on('click', '.desvincular-gc-producto', function() {
                var productoId = $(this).data('id');
                var productoNombre = $(this).siblings('div').find('strong').text();
                $(this).closest('.gc-producto-item').remove();

                $('#agregar-gc-producto-selector').append('<option value="' + productoId + '">' + productoNombre + '</option>');

                if ($('#gc-productos-lista .gc-producto-item').length === 0) {
                    $('#gc-productos-lista').html('<p class="no-gc-productos" style="color: #666; font-style: italic; margin: 0;"><?php echo esc_js(__('Sin productos de Grupos de Consumo vinculados', 'flavor-chat-ia')); ?></p>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Render meta box de videos del módulo multimedia
     */
    public function render_meta_box_videos($post) {
        global $wpdb;

        $videos_ids = get_post_meta($post->ID, '_receta_videos', true);
        if (!is_array($videos_ids)) {
            $videos_ids = [];
        }

        $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';

        // Verificar que la tabla existe
        if (!Flavor_Chat_Helpers::tabla_existe($tabla_multimedia)) {
            echo '<div class="notice notice-info inline" style="margin: 0;"><p>';
            echo __('El módulo de Multimedia no está activo.', 'flavor-chat-ia');
            echo '</p></div>';
            return;
        }

        // Obtener videos disponibles
        $videos_disponibles = $wpdb->get_results(
            "SELECT id, titulo FROM {$tabla_multimedia}
             WHERE tipo = 'video' AND estado IN ('publico', 'comunidad')
             ORDER BY fecha_creacion DESC
             LIMIT 100"
        );

        // Si no hay videos
        if (empty($videos_disponibles) && empty($videos_ids)) {
            echo '<div class="notice notice-warning inline" style="margin: 0;"><p>';
            echo __('No hay videos disponibles en el módulo Multimedia.', 'flavor-chat-ia');
            echo ' <a href="' . admin_url('admin.php?page=multimedia') . '">' . __('Subir videos', 'flavor-chat-ia') . '</a>';
            echo '</p></div>';
            return;
        }
        ?>
        <p class="description"><?php _e('Vincula videos del módulo multimedia a esta receta (tutoriales, paso a paso, etc.).', 'flavor-chat-ia'); ?></p>

        <div id="receta-videos-lista" style="max-height: 250px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;">
            <?php
            if (!empty($videos_ids)) {
                foreach ($videos_ids as $video_id) {
                    $video = $wpdb->get_row($wpdb->prepare(
                        "SELECT id, titulo, archivo_url, tipo FROM {$tabla_multimedia} WHERE id = %d",
                        $video_id
                    ));
                    if ($video) {
                        ?>
                        <div class="video-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee; background: #f0f7ff; margin-bottom: 5px; border-radius: 4px;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="dashicons dashicons-video-alt3" style="font-size: 24px; color: #0073aa;"></span>
                                <strong><?php echo esc_html($video->titulo); ?></strong>
                            </div>
                            <button type="button" class="button-link desvincular-video" data-id="<?php echo esc_attr($video_id); ?>" title="<?php esc_attr_e('Quitar', 'flavor-chat-ia'); ?>">
                                <span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>
                            </button>
                            <input type="hidden" name="receta_videos[]" value="<?php echo esc_attr($video_id); ?>" />
                        </div>
                        <?php
                    }
                }
            } else {
                echo '<p class="no-videos" style="color: #666; font-style: italic; margin: 0;">' . __('Sin videos vinculados', 'flavor-chat-ia') . '</p>';
            }
            ?>
        </div>

        <div style="display: flex; gap: 5px;">
            <select id="agregar-video-selector" class="widefat" style="flex: 1;">
                <option value=""><?php _e('Seleccionar video...', 'flavor-chat-ia'); ?></option>
                <?php
                // Usar $videos_disponibles ya obtenido arriba
                foreach ($videos_disponibles as $video) {
                    if (!in_array($video->id, $videos_ids)) {
                        echo '<option value="' . esc_attr($video->id) . '">🎬 ' . esc_html($video->titulo) . '</option>';
                    }
                }
                ?>
            </select>
            <button type="button" class="button" id="agregar-video-btn">
                <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
            </button>
        </div>

        <p style="margin-top: 10px;">
            <a href="<?php echo admin_url('admin.php?page=multimedia'); ?>" class="button button-small" target="_blank">
                <span class="dashicons dashicons-upload" style="vertical-align: middle;"></span>
                <?php _e('Subir Video', 'flavor-chat-ia'); ?>
            </a>
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#agregar-video-btn').on('click', function() {
                var $selector = $('#agregar-video-selector');
                var videoId = $selector.val();
                var videoNombre = $selector.find('option:selected').text().replace('🎬 ', '');

                if (!videoId) return;

                $('.no-videos').remove();

                var html = '<div class="video-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px; border-bottom: 1px solid #eee; background: #f0f7ff; margin-bottom: 5px; border-radius: 4px;">' +
                    '<div style="display: flex; align-items: center; gap: 10px;">' +
                    '<span class="dashicons dashicons-video-alt3" style="font-size: 24px; color: #0073aa;"></span>' +
                    '<strong>' + videoNombre + '</strong>' +
                    '</div>' +
                    '<button type="button" class="button-link desvincular-video" data-id="' + videoId + '">' +
                    '<span class="dashicons dashicons-no-alt" style="color: #dc3232;"></span>' +
                    '</button>' +
                    '<input type="hidden" name="receta_videos[]" value="' + videoId + '" />' +
                    '</div>';

                $('#receta-videos-lista').append(html);
                $selector.find('option:selected').remove();
                $selector.val('');
            });

            $(document).on('click', '.desvincular-video', function() {
                var videoId = $(this).data('id');
                var videoNombre = $(this).siblings('div').find('strong').text();
                $(this).closest('.video-item').remove();

                $('#agregar-video-selector').append('<option value="' + videoId + '">🎬 ' + videoNombre + '</option>');

                if ($('#receta-videos-lista .video-item').length === 0) {
                    $('#receta-videos-lista').html('<p class="no-videos" style="color: #666; font-style: italic; margin: 0;"><?php echo esc_js(__('Sin videos vinculados', 'flavor-chat-ia')); ?></p>');
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Guardar meta de receta
     */
    public function guardar_meta_receta($post_id) {
        if (!isset($_POST['flavor_receta_nonce']) ||
            !wp_verify_nonce($_POST['flavor_receta_nonce'], 'flavor_receta_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Detalles
        $campos_numericos = ['receta_tiempo_preparacion', 'receta_tiempo_coccion', 'receta_porciones', 'receta_calorias'];
        foreach ($campos_numericos as $campo) {
            if (isset($_POST[$campo])) {
                update_post_meta($post_id, '_' . $campo, absint($_POST[$campo]));
            }
        }

        if (isset($_POST['receta_dificultad'])) {
            update_post_meta($post_id, '_receta_dificultad', sanitize_text_field($_POST['receta_dificultad']));
        }

        // Ingredientes
        if (isset($_POST['receta_ingredientes']) && is_array($_POST['receta_ingredientes'])) {
            $ingredientes_limpios = [];
            foreach ($_POST['receta_ingredientes'] as $ingrediente) {
                if (!empty($ingrediente['nombre'])) {
                    $ingredientes_limpios[] = [
                        'cantidad' => sanitize_text_field($ingrediente['cantidad']),
                        'unidad' => sanitize_text_field($ingrediente['unidad']),
                        'nombre' => sanitize_text_field($ingrediente['nombre']),
                    ];
                }
            }
            update_post_meta($post_id, '_receta_ingredientes', $ingredientes_limpios);
        }

        // Pasos
        if (isset($_POST['receta_pasos']) && is_array($_POST['receta_pasos'])) {
            $pasos_limpios = [];
            foreach ($_POST['receta_pasos'] as $paso) {
                $paso_limpio = sanitize_textarea_field($paso);
                if (!empty($paso_limpio)) {
                    $pasos_limpios[] = $paso_limpio;
                }
            }
            update_post_meta($post_id, '_receta_pasos', $pasos_limpios);
        }

        // Productos vinculados
        $productos_anteriores = get_post_meta($post_id, '_receta_productos_vinculados', true);
        if (!is_array($productos_anteriores)) {
            $productos_anteriores = [];
        }

        $productos_nuevos = [];
        if (isset($_POST['receta_productos_vinculados']) && is_array($_POST['receta_productos_vinculados'])) {
            $productos_nuevos = array_map('absint', $_POST['receta_productos_vinculados']);
        }

        update_post_meta($post_id, '_receta_productos_vinculados', $productos_nuevos);

        // Sincronizar vinculacion bidireccional con productos
        // Quitar de productos que ya no estan
        foreach ($productos_anteriores as $producto_id) {
            if (!in_array($producto_id, $productos_nuevos)) {
                $recetas_producto = get_post_meta($producto_id, '_producto_recetas_vinculadas', true);
                if (is_array($recetas_producto)) {
                    $recetas_producto = array_diff($recetas_producto, [$post_id]);
                    update_post_meta($producto_id, '_producto_recetas_vinculadas', array_values($recetas_producto));
                }
            }
        }

        // Agregar a nuevos productos
        foreach ($productos_nuevos as $producto_id) {
            if (!in_array($producto_id, $productos_anteriores)) {
                $recetas_producto = get_post_meta($producto_id, '_producto_recetas_vinculadas', true);
                if (!is_array($recetas_producto)) {
                    $recetas_producto = [];
                }
                if (!in_array($post_id, $recetas_producto)) {
                    $recetas_producto[] = $post_id;
                    update_post_meta($producto_id, '_producto_recetas_vinculadas', $recetas_producto);
                }
            }
        }

        // Productos de Grupos de Consumo
        if ($this->is_module_active('grupos_consumo')) {
            $gc_productos = [];
            if (isset($_POST['receta_gc_productos']) && is_array($_POST['receta_gc_productos'])) {
                $gc_productos = array_map('absint', $_POST['receta_gc_productos']);
                // Filtrar solo los que realmente son gc_producto
                $gc_productos = array_filter($gc_productos, function($id) {
                    $post = get_post($id);
                    return $post && $post->post_type === 'gc_producto';
                });
            }
            update_post_meta($post_id, '_receta_gc_productos', array_values($gc_productos));
        }

        // Videos del módulo multimedia
        if ($this->is_module_active('multimedia') || $this->is_module_active('videos')) {
            $videos = [];
            if (isset($_POST['receta_videos']) && is_array($_POST['receta_videos'])) {
                $videos = array_map('absint', $_POST['receta_videos']);
                // Verificar que los videos existen en la tabla multimedia
                global $wpdb;
                $tabla_multimedia = $wpdb->prefix . 'flavor_multimedia';
                if (Flavor_Chat_Helpers::tabla_existe($tabla_multimedia)) {
                    $videos = array_filter($videos, function($id) use ($wpdb, $tabla_multimedia) {
                        return (bool) $wpdb->get_var($wpdb->prepare(
                            "SELECT id FROM {$tabla_multimedia} WHERE id = %d AND tipo = 'video'",
                            $id
                        ));
                    });
                }
            }
            update_post_meta($post_id, '_receta_videos', array_values($videos));
        }
    }

    /**
     * Agregar tab en productos WooCommerce
     */
    public function agregar_tab_producto($tabs) {
        $tabs['flavor_recetas'] = [
            'label'    => __('Recetas y Notas', 'flavor-chat-ia'),
            'target'   => 'flavor_recetas_data',
            'class'    => [],
            'priority' => 80,
        ];
        return $tabs;
    }

    /**
     * Contenido del tab en productos WooCommerce
     */
    public function contenido_tab_producto() {
        global $post;

        $recetas_vinculadas = get_post_meta($post->ID, '_producto_recetas_vinculadas', true);
        if (!is_array($recetas_vinculadas)) {
            $recetas_vinculadas = [];
        }

        $notas_producto = get_post_meta($post->ID, '_producto_notas', true);
        ?>
        <div id="flavor_recetas_data" class="panel woocommerce_options_panel">
            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php _e('Recetas Vinculadas', 'flavor-chat-ia'); ?></h4>
                <p class="form-field" style="padding-left: 12px;">
                    <label><?php _e('Seleccionar Recetas', 'flavor-chat-ia'); ?></label>
                    <select id="producto_recetas_vinculadas" name="producto_recetas_vinculadas[]" multiple="multiple" class="wc-enhanced-select" style="width: 50%;">
                        <?php
                        $recetas = get_posts([
                            'post_type' => 'flavor_receta',
                            'posts_per_page' => -1,
                            'post_status' => 'publish',
                        ]);

                        foreach ($recetas as $receta) {
                            $seleccionada = in_array($receta->ID, $recetas_vinculadas) ? 'selected' : '';
                            echo '<option value="' . esc_attr($receta->ID) . '" ' . $seleccionada . '>' . esc_html($receta->post_title) . '</option>';
                        }
                        ?>
                    </select>
                    <?php echo wc_help_tip(__('Selecciona las recetas que usan este producto como ingrediente.', 'flavor-chat-ia')); ?>
                </p>

                <?php if (!empty($recetas_vinculadas)): ?>
                <div style="padding: 0 12px;">
                    <strong><?php _e('Recetas actualmente vinculadas:', 'flavor-chat-ia'); ?></strong>
                    <ul style="margin-top: 5px;">
                        <?php foreach ($recetas_vinculadas as $receta_id):
                            $receta = get_post($receta_id);
                            if ($receta): ?>
                            <li>
                                <a href="<?php echo get_edit_post_link($receta_id); ?>" target="_blank">
                                    <?php echo esc_html($receta->post_title); ?>
                                </a>
                            </li>
                            <?php endif;
                        endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </div>

            <div class="options_group">
                <h4 style="padding-left: 12px;"><?php _e('Notas del Producto', 'flavor-chat-ia'); ?></h4>
                <?php
                woocommerce_wp_textarea_input([
                    'id'          => 'producto_notas',
                    'label'       => __('Notas internas', 'flavor-chat-ia'),
                    'description' => __('Notas internas sobre el producto. No se muestran al cliente.', 'flavor-chat-ia'),
                    'desc_tip'    => true,
                    'value'       => $notas_producto,
                ]);
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * Guardar meta de producto WooCommerce
     */
    public function guardar_meta_producto($post_id) {
        // Guardar recetas vinculadas
        $recetas_anteriores = get_post_meta($post_id, '_producto_recetas_vinculadas', true);
        if (!is_array($recetas_anteriores)) {
            $recetas_anteriores = [];
        }

        $recetas_nuevas = [];
        if (isset($_POST['producto_recetas_vinculadas']) && is_array($_POST['producto_recetas_vinculadas'])) {
            $recetas_nuevas = array_map('absint', $_POST['producto_recetas_vinculadas']);
        }

        update_post_meta($post_id, '_producto_recetas_vinculadas', $recetas_nuevas);

        // Sincronizar vinculacion bidireccional con recetas
        foreach ($recetas_anteriores as $receta_id) {
            if (!in_array($receta_id, $recetas_nuevas)) {
                $productos_receta = get_post_meta($receta_id, '_receta_productos_vinculados', true);
                if (is_array($productos_receta)) {
                    $productos_receta = array_diff($productos_receta, [$post_id]);
                    update_post_meta($receta_id, '_receta_productos_vinculados', array_values($productos_receta));
                }
            }
        }

        foreach ($recetas_nuevas as $receta_id) {
            if (!in_array($receta_id, $recetas_anteriores)) {
                $productos_receta = get_post_meta($receta_id, '_receta_productos_vinculados', true);
                if (!is_array($productos_receta)) {
                    $productos_receta = [];
                }
                if (!in_array($post_id, $productos_receta)) {
                    $productos_receta[] = $post_id;
                    update_post_meta($receta_id, '_receta_productos_vinculados', $productos_receta);
                }
            }
        }

        // Guardar notas
        if (isset($_POST['producto_notas'])) {
            update_post_meta($post_id, '_producto_notas', sanitize_textarea_field($_POST['producto_notas']));
        }
    }

    /**
     * Mostrar recetas en el frontend del producto
     */
    public function mostrar_recetas_producto() {
        global $product;

        if (!$product) return;

        $recetas_ids = get_post_meta($product->get_id(), '_producto_recetas_vinculadas', true);

        if (empty($recetas_ids) || !is_array($recetas_ids)) {
            return;
        }

        $recetas = get_posts([
            'post_type' => 'flavor_receta',
            'post__in' => $recetas_ids,
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        if (empty($recetas)) return;
        ?>
        <div class="flavor-recetas-producto" style="margin-top: 30px;">
            <h2><?php _e('Recetas con este producto', 'flavor-chat-ia'); ?></h2>
            <div class="recetas-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; margin-top: 15px;">
                <?php foreach ($recetas as $receta):
                    $imagen = get_the_post_thumbnail_url($receta->ID, 'medium');
                    $tiempo_total = intval(get_post_meta($receta->ID, '_receta_tiempo_preparacion', true)) +
                                   intval(get_post_meta($receta->ID, '_receta_tiempo_coccion', true));
                    $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    ?>
                    <div class="receta-card" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                        <?php if ($imagen): ?>
                        <a href="<?php echo get_permalink($receta->ID); ?>">
                            <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($receta->post_title); ?>" style="width: 100%; height: 150px; object-fit: cover;" />
                        </a>
                        <?php endif; ?>
                        <div style="padding: 15px;">
                            <h3 style="margin: 0 0 10px; font-size: 16px;">
                                <a href="<?php echo get_permalink($receta->ID); ?>" style="text-decoration: none; color: inherit;">
                                    <?php echo esc_html($receta->post_title); ?>
                                </a>
                            </h3>
                            <div style="display: flex; gap: 15px; font-size: 12px; color: #666;">
                                <?php if ($tiempo_total > 0): ?>
                                <span><span class="dashicons dashicons-clock" style="font-size: 14px;"></span> <?php echo $tiempo_total; ?> min</span>
                                <?php endif; ?>
                                <?php if ($dificultad): ?>
                                <span><?php echo ucfirst($dificultad); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render pagina de administracion
     */
    public function render_admin_page() {
        $accion = isset($_GET['accion']) ? sanitize_text_field($_GET['accion']) : 'listado';
        ?>
        <div class="wrap flavor-recetas-admin">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h1><?php _e('Gestion de Recetas', 'flavor-chat-ia'); ?></h1>
                <a href="<?php echo admin_url('post-new.php?post_type=flavor_receta'); ?>" class="button button-primary">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                    <?php _e('Nueva Receta', 'flavor-chat-ia'); ?>
                </a>
            </div>

            <?php
            // Estadisticas rapidas
            $total_recetas = wp_count_posts('flavor_receta')->publish;
            $recetas_con_productos = $this->contar_recetas_con_productos();
            ?>
            <div class="flavor-stats-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #0073aa;"><?php echo $total_recetas; ?></div>
                    <div style="color: #666;"><?php _e('Total Recetas', 'flavor-chat-ia'); ?></div>
                </div>
                <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
                    <div style="font-size: 28px; font-weight: bold; color: #00a32a;"><?php echo $recetas_con_productos; ?></div>
                    <div style="color: #666;"><?php _e('Con Productos Vinculados', 'flavor-chat-ia'); ?></div>
                </div>
            </div>

            <?php $this->render_tabla_recetas(); ?>
        </div>
        <?php
    }

    /**
     * Contar recetas con productos
     */
    private function contar_recetas_con_productos() {
        global $wpdb;
        return (int) $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key = '_receta_productos_vinculados'
            AND meta_value != ''
            AND meta_value != 'a:0:{}'
        ");
    }

    /**
     * Render tabla de recetas
     */
    private function render_tabla_recetas() {
        $recetas = get_posts([
            'post_type' => 'flavor_receta',
            'posts_per_page' => 20,
            'post_status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        if (empty($recetas)) {
            echo '<div class="notice notice-info"><p>' . __('No hay recetas creadas aun.', 'flavor-chat-ia') . '</p></div>';
            return;
        }
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Receta', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Categoria', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Tiempo Total', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Dificultad', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Productos', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php _e('Acciones', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recetas as $receta):
                    $categorias = wp_get_post_terms($receta->ID, 'receta_categoria', ['fields' => 'names']);
                    $tiempo_prep = intval(get_post_meta($receta->ID, '_receta_tiempo_preparacion', true));
                    $tiempo_coccion = intval(get_post_meta($receta->ID, '_receta_tiempo_coccion', true));
                    $tiempo_total = $tiempo_prep + $tiempo_coccion;
                    $dificultad = get_post_meta($receta->ID, '_receta_dificultad', true);
                    $productos = get_post_meta($receta->ID, '_receta_productos_vinculados', true);
                    $num_productos = is_array($productos) ? count($productos) : 0;
                    ?>
                    <tr>
                        <td>
                            <strong>
                                <a href="<?php echo get_edit_post_link($receta->ID); ?>">
                                    <?php echo esc_html($receta->post_title); ?>
                                </a>
                            </strong>
                            <?php if ($receta->post_status !== 'publish'): ?>
                            <span class="post-state"> - <?php echo get_post_status_object($receta->post_status)->label; ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo !empty($categorias) ? esc_html(implode(', ', $categorias)) : '-'; ?></td>
                        <td><?php echo $tiempo_total > 0 ? $tiempo_total . ' min' : '-'; ?></td>
                        <td><?php echo $dificultad ? ucfirst($dificultad) : '-'; ?></td>
                        <td><?php echo $num_productos; ?></td>
                        <td><?php echo get_the_date('d/m/Y', $receta->ID); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($receta->ID); ?>" class="button button-small"><?php _e('Editar', 'flavor-chat-ia'); ?></a>
                            <a href="<?php echo get_permalink($receta->ID); ?>" class="button button-small" target="_blank"><?php _e('Ver', 'flavor-chat-ia'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    /**
     * AJAX buscar recetas
     */
    public function ajax_buscar_recetas() {
        check_ajax_referer('flavor_recetas_nonce', 'nonce');

        $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';

        $recetas = get_posts([
            'post_type' => 'flavor_receta',
            's' => $termino,
            'posts_per_page' => 10,
            'post_status' => 'publish',
        ]);

        $resultados = [];
        foreach ($recetas as $receta) {
            $resultados[] = [
                'id' => $receta->ID,
                'titulo' => $receta->post_title,
                'url' => get_permalink($receta->ID),
            ];
        }

        wp_send_json_success($resultados);
    }

    /**
     * Shortcode listado de recetas
     */
    public function shortcode_listado_recetas($atts) {
        $atts = shortcode_atts([
            'cantidad' => 6,
            'categoria' => '',
            'columnas' => 3,
        ], $atts);

        $args = [
            'post_type' => 'flavor_receta',
            'posts_per_page' => intval($atts['cantidad']),
            'post_status' => 'publish',
        ];

        if (!empty($atts['categoria'])) {
            $args['tax_query'] = [[
                'taxonomy' => 'receta_categoria',
                'field' => 'slug',
                'terms' => $atts['categoria'],
            ]];
        }

        $recetas = get_posts($args);

        if (empty($recetas)) {
            return '<p>' . __('No hay recetas disponibles.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-recetas-grid" style="display: grid; grid-template-columns: repeat(<?php echo intval($atts['columnas']); ?>, 1fr); gap: 20px;">
            <?php foreach ($recetas as $receta):
                $imagen = get_the_post_thumbnail_url($receta->ID, 'medium');
                $tiempo_total = intval(get_post_meta($receta->ID, '_receta_tiempo_preparacion', true)) +
                               intval(get_post_meta($receta->ID, '_receta_tiempo_coccion', true));
                ?>
                <div class="receta-card" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                    <?php if ($imagen): ?>
                    <a href="<?php echo get_permalink($receta->ID); ?>">
                        <img src="<?php echo esc_url($imagen); ?>" alt="<?php echo esc_attr($receta->post_title); ?>" style="width: 100%; height: 180px; object-fit: cover;" />
                    </a>
                    <?php endif; ?>
                    <div style="padding: 15px;">
                        <h3 style="margin: 0 0 10px; font-size: 18px;">
                            <a href="<?php echo get_permalink($receta->ID); ?>" style="text-decoration: none; color: inherit;">
                                <?php echo esc_html($receta->post_title); ?>
                            </a>
                        </h3>
                        <?php if ($receta->post_excerpt): ?>
                        <p style="color: #666; font-size: 14px; margin: 0 0 10px;"><?php echo esc_html(wp_trim_words($receta->post_excerpt, 15)); ?></p>
                        <?php endif; ?>
                        <?php if ($tiempo_total > 0): ?>
                        <span style="font-size: 12px; color: #888;">
                            <span class="dashicons dashicons-clock" style="font-size: 14px; vertical-align: middle;"></span>
                            <?php echo $tiempo_total; ?> min
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Shortcode receta individual
     */
    public function shortcode_receta_individual($atts = []) {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts, 'flavor_receta');

        $receta_id = absint($atts['id']);

        if (!$receta_id && is_singular('flavor_receta')) {
            $receta_id = get_the_ID();
        }

        if (!$receta_id && isset($_GET['id'])) {
            $receta_id = absint($_GET['id']);
        }

        if (!$receta_id) {
            return '<p class="flavor-notice">' . esc_html__('Receta no encontrada.', 'flavor-chat-ia') . '</p>';
        }

        $receta = get_post($receta_id);

        if (!$receta || $receta->post_type !== 'flavor_receta' || $receta->post_status !== 'publish') {
            return '<p class="flavor-notice">' . esc_html__('Receta no disponible.', 'flavor-chat-ia') . '</p>';
        }

        $template = dirname(__FILE__) . '/templates/receta-single.php';

        if (!file_exists($template)) {
            return '<p class="flavor-notice">' . esc_html__('Vista de receta no disponible.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        include $template;
        return ob_get_clean();
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;

        if ($post_type === 'flavor_receta' || (isset($_GET['page']) && strpos($_GET['page'], 'recetas') !== false)) {
            wp_enqueue_style('dashicons');
        }
    }

    /**
     * Verifica si se deben cargar los assets del modulo
     *
     * @return bool
     */
    private function should_load_assets() {
        global $post;

        if (!$post) {
            return false;
        }

        $shortcodes_modulo = [
            'flavor_recetas',
            'flavor_receta',
        ];

        foreach ($shortcodes_modulo as $shortcode) {
            if (has_shortcode($post->post_content, $shortcode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        if (!$this->should_load_assets() && !is_singular('flavor_receta') && !is_post_type_archive('flavor_receta')) {
            return;
        }

        wp_enqueue_style('dashicons');
    }

    /**
     * {@inheritdoc}
     */
    public function get_actions() {
        return [
            'buscar_recetas' => [
                'description' => 'Buscar recetas por ingredientes o categoría',
                'params' => ['termino', 'categoria'],
            ],
            'mis_recetas' => [
                'description' => 'Ver mis recetas guardadas',
                'params' => [],
            ],
            'recetas_populares' => [
                'description' => 'Ver recetas más populares',
                'params' => [],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function execute_action($action_name, $params) {
        $aliases = [
            'listar' => 'buscar_recetas',
            'listado' => 'buscar_recetas',
            'buscar' => 'buscar_recetas',
            'mis_items' => 'mis_recetas',
            'mis-recetas' => 'mis_recetas',
            'populares' => 'recetas_populares',
            'destacadas' => 'recetas_populares',
            'crear' => 'crear_receta',
            'nuevo' => 'crear_receta',
            'foro' => 'foro_receta',
            'multimedia' => 'multimedia_receta',
            'red-social' => 'red_social_receta',
            'red_social' => 'red_social_receta',
        ];

        $action_name = $aliases[$action_name] ?? $action_name;
        $method = 'action_' . $action_name;

        if (method_exists($this, $method)) {
            return $this->$method($params);
        }

        return [
            'success' => false,
            'error' => __('Acción no implementada', 'flavor-chat-ia'),
        ];
    }

    /**
     * Acción: buscar/listar recetas.
     */
    private function action_buscar_recetas($params) {
        $shortcode = '[flavor_recetas';

        if (!empty($params['termino'])) {
            $shortcode .= ' busqueda="' . esc_attr($params['termino']) . '"';
        }

        if (!empty($params['categoria'])) {
            $shortcode .= ' categoria="' . esc_attr($params['categoria']) . '"';
        }

        $shortcode .= ']';

        return [
            'success' => true,
            'html' => do_shortcode($shortcode),
        ];
    }

    /**
     * Acción: mis recetas.
     */
    private function action_mis_recetas($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_recetas_mis_recetas]'),
        ];
    }

    /**
     * Acción: recetas populares.
     */
    private function action_recetas_populares($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_recetas_destacadas]'),
        ];
    }

    /**
     * Acción: crear receta.
     */
    private function action_crear_receta($params) {
        return [
            'success' => true,
            'html' => do_shortcode('[flavor_recetas_crear]'),
        ];
    }

    /**
     * Resuelve la receta contextual para tabs satélite.
     *
     * @param array $params Parámetros opcionales.
     * @return WP_Post|null
     */
    private function resolve_contextual_receta(array $params = []) {
        $receta_id = absint(
            $params['receta_id']
            ?? $params['id']
            ?? $_GET['receta_id']
            ?? $_GET['id']
            ?? 0
        );

        if ($receta_id <= 0) {
            return null;
        }

        $receta = get_post($receta_id);
        if (!$receta || $receta->post_type !== 'flavor_receta') {
            return null;
        }

        return $receta;
    }

    /**
     * Acción: foro contextual de receta.
     *
     * @param array $params Parámetros.
     * @return string
     */
    private function action_foro_receta($params) {
        $receta = $this->resolve_contextual_receta((array) $params);
        if (!$receta) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona una receta para ver su foro.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-contextual-tab flavor-contextual-foro">
            <div class="flavor-contextual-header" style="margin-bottom:1.5rem;">
                <h2><?php esc_html_e('Foro de la receta', 'flavor-chat-ia'); ?></h2>
                <p><?php echo esc_html(get_the_title($receta)); ?></p>
            </div>
            <?php echo do_shortcode(sprintf(
                '[flavor_foros_integrado entidad="receta" entidad_id="%d"]',
                absint($receta->ID)
            )); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Acción: multimedia contextual de receta.
     *
     * @param array $params Parámetros.
     * @return string
     */
    private function action_multimedia_receta($params) {
        $receta = $this->resolve_contextual_receta((array) $params);
        if (!$receta) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona una receta para ver su galería.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-contextual-tab flavor-contextual-multimedia">
            <div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div>
                    <h2><?php esc_html_e('Multimedia de la receta', 'flavor-chat-ia'); ?></h2>
                    <p><?php echo esc_html(get_the_title($receta)); ?></p>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/multimedia/subir/?receta_id=' . absint($receta->ID))); ?>" class="button button-primary">
                    <?php esc_html_e('Subir archivo', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php echo do_shortcode(sprintf(
                '[flavor_multimedia_galeria entidad="receta" entidad_id="%d"]',
                absint($receta->ID)
            )); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Acción: red social contextual de receta.
     *
     * @param array $params Parámetros.
     * @return string
     */
    private function action_red_social_receta($params) {
        $receta = $this->resolve_contextual_receta((array) $params);
        if (!$receta) {
            return '<p class="flavor-notice">' . esc_html__('Selecciona una receta para ver su actividad social.', 'flavor-chat-ia') . '</p>';
        }

        if (!is_user_logged_in()) {
            return '<p class="flavor-notice">' . esc_html__('Inicia sesión para participar en la actividad social de esta receta.', 'flavor-chat-ia') . '</p>';
        }

        ob_start();
        ?>
        <div class="flavor-contextual-tab flavor-contextual-red-social">
            <div class="flavor-contextual-header" style="margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
                <div>
                    <h2><?php esc_html_e('Actividad social de la receta', 'flavor-chat-ia'); ?></h2>
                    <p><?php echo esc_html(get_the_title($receta)); ?></p>
                </div>
                <a href="<?php echo esc_url(home_url('/mi-portal/red-social/crear/?receta_id=' . absint($receta->ID))); ?>" class="button button-primary">
                    <?php esc_html_e('Publicar', 'flavor-chat-ia'); ?>
                </a>
            </div>
            <?php echo do_shortcode(sprintf(
                '[flavor_social_feed entidad="receta" entidad_id="%d"]',
                absint($receta->ID)
            )); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * {@inheritdoc}
     */
    public function get_tool_definitions() {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function get_knowledge_base() {
        return __('El módulo de Recetas permite compartir y descubrir recetas de la comunidad, con enfoque en cocina local y sostenible.', 'flavor-chat-ia');
    }

    /**
     * Configuración de formularios del módulo
     *
     * @param string $action_name Nombre de la acción del formulario
     * @return array|null Configuración del formulario o null si no existe
     */
    public function get_form_config($action_name) {
        $configs = [
            'crear_receta' => [
                'title' => __('Compartir Receta', 'flavor-chat-ia'),
                'description' => __('Comparte una receta con la comunidad', 'flavor-chat-ia'),
                'fields' => [
                    'titulo' => [
                        'type' => 'text',
                        'label' => __('Nombre de la receta', 'flavor-chat-ia'),
                        'required' => true,
                        'placeholder' => __('Ej: Paella valenciana tradicional', 'flavor-chat-ia'),
                    ],
                    'descripcion' => [
                        'type' => 'textarea',
                        'label' => __('Descripción', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 3,
                        'placeholder' => __('Breve descripción de la receta...', 'flavor-chat-ia'),
                    ],
                    'categoria' => [
                        'type' => 'select',
                        'label' => __('Categoría', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'entrante' => __('Entrante', 'flavor-chat-ia'),
                            'principal' => __('Plato principal', 'flavor-chat-ia'),
                            'postre' => __('Postre', 'flavor-chat-ia'),
                            'bebida' => __('Bebida', 'flavor-chat-ia'),
                            'snack' => __('Snack/Aperitivo', 'flavor-chat-ia'),
                            'conserva' => __('Conserva', 'flavor-chat-ia'),
                        ],
                    ],
                    'dificultad' => [
                        'type' => 'select',
                        'label' => __('Dificultad', 'flavor-chat-ia'),
                        'required' => true,
                        'options' => [
                            'facil' => __('Fácil', 'flavor-chat-ia'),
                            'media' => __('Media', 'flavor-chat-ia'),
                            'dificil' => __('Difícil', 'flavor-chat-ia'),
                        ],
                    ],
                    'tiempo_preparacion' => [
                        'type' => 'number',
                        'label' => __('Tiempo de preparación (minutos)', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                    ],
                    'porciones' => [
                        'type' => 'number',
                        'label' => __('Número de porciones', 'flavor-chat-ia'),
                        'required' => true,
                        'min' => 1,
                        'default' => 4,
                    ],
                    'ingredientes' => [
                        'type' => 'textarea',
                        'label' => __('Ingredientes', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 6,
                        'placeholder' => __('Un ingrediente por línea...', 'flavor-chat-ia'),
                    ],
                    'pasos' => [
                        'type' => 'textarea',
                        'label' => __('Pasos de preparación', 'flavor-chat-ia'),
                        'required' => true,
                        'rows' => 8,
                        'placeholder' => __('Describe los pasos de preparación...', 'flavor-chat-ia'),
                    ],
                    'imagen' => [
                        'type' => 'file',
                        'label' => __('Imagen de la receta', 'flavor-chat-ia'),
                        'required' => false,
                        'accept' => 'image/*',
                    ],
                ],
                'submit_text' => __('Enviar Receta', 'flavor-chat-ia'),
                'ajax' => true,
            ],
        ];

        return $configs[$action_name] ?? null;
    }

    /**
     * Configuración para el Module Renderer
     *
     * @return array
     */
    public static function get_renderer_config(): array {
        return [
            'module'   => 'recetas',
            'title'    => __('Recetario Comunitario', 'flavor-chat-ia'),
            'subtitle' => __('Comparte y descubre recetas de la comunidad', 'flavor-chat-ia'),
            'icon'     => '🍳',
            'color'    => 'warning', // Usa variable CSS --flavor-warning del tema

            'database' => [
                'table'       => 'flavor_recetas',
                'primary_key' => 'id',
            ],

            'fields' => [
                'titulo'       => ['type' => 'text', 'label' => __('Nombre de la receta', 'flavor-chat-ia'), 'required' => true],
                'descripcion'  => ['type' => 'textarea', 'label' => __('Descripción', 'flavor-chat-ia')],
                'categoria'    => ['type' => 'select', 'label' => __('Categoría', 'flavor-chat-ia')],
                'dificultad'   => ['type' => 'select', 'label' => __('Dificultad', 'flavor-chat-ia')],
                'tiempo'       => ['type' => 'number', 'label' => __('Tiempo (minutos)', 'flavor-chat-ia')],
                'porciones'    => ['type' => 'number', 'label' => __('Porciones', 'flavor-chat-ia')],
                'ingredientes' => ['type' => 'textarea', 'label' => __('Ingredientes', 'flavor-chat-ia'), 'required' => true],
                'pasos'        => ['type' => 'textarea', 'label' => __('Preparación', 'flavor-chat-ia'), 'required' => true],
                'imagen'       => ['type' => 'file', 'label' => __('Imagen', 'flavor-chat-ia')],
            ],

            'estados' => [
                'borrador'   => ['label' => __('Borrador', 'flavor-chat-ia'), 'color' => 'gray', 'icon' => '📝'],
                'publicada'  => ['label' => __('Publicada', 'flavor-chat-ia'), 'color' => 'green', 'icon' => '✅'],
                'destacada'  => ['label' => __('Destacada', 'flavor-chat-ia'), 'color' => 'yellow', 'icon' => '⭐'],
            ],

            'stats' => [
                [
                    'key'   => 'total_recetas',
                    'label' => __('Recetas', 'flavor-chat-ia'),
                    'icon'  => '🍳',
                    'color' => 'orange',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_recetas WHERE estado = 'publicada'",
                ],
                [
                    'key'   => 'cocineros',
                    'label' => __('Cocineros', 'flavor-chat-ia'),
                    'icon'  => '👨‍🍳',
                    'color' => 'blue',
                    'query' => "SELECT COUNT(DISTINCT user_id) FROM {prefix}flavor_recetas",
                ],
                [
                    'key'   => 'valoraciones',
                    'label' => __('Valoraciones', 'flavor-chat-ia'),
                    'icon'  => '⭐',
                    'color' => 'yellow',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_recetas_valoraciones",
                ],
                [
                    'key'   => 'mis_recetas',
                    'label' => __('Mis recetas', 'flavor-chat-ia'),
                    'icon'  => '📖',
                    'color' => 'green',
                    'query' => "SELECT COUNT(*) FROM {prefix}flavor_recetas WHERE user_id = {user_id}",
                ],
            ],

            'card' => [
                'layout'      => 'recipe',
                'image_field' => 'imagen',
                'title_field' => 'titulo',
                'meta_fields' => ['tiempo', 'dificultad', 'porciones'],
                'badge_field' => 'categoria',
                'show_rating' => true,
                'show_author' => true,
            ],

            'tabs' => [
                'listado' => [
                    'label'   => __('Recetas', 'flavor-chat-ia'),
                    'icon'    => '🍳',
                    'content' => '[flavor_recetas cantidad="12" columnas="3"]',
                ],
                'mis-recetas' => [
                    'label'   => __('Mis recetas', 'flavor-chat-ia'),
                    'icon'    => '📖',
                    'content' => 'shortcode:flavor_recetas_mis_recetas',
                    'requires_login' => true,
                ],
                'favoritas' => [
                    'label'   => __('Favoritas', 'flavor-chat-ia'),
                    'icon'    => '❤️',
                    'content' => 'shortcode:flavor_recetas_favoritas',
                    'requires_login' => true,
                ],
                'nueva' => [
                    'label'   => __('Añadir', 'flavor-chat-ia'),
                    'icon'    => '➕',
                    'content' => 'shortcode:flavor_recetas_crear',
                    'requires_login' => true,
                ],
                'buscar' => [
                    'label'   => __('Buscar', 'flavor-chat-ia'),
                    'icon'    => '🔍',
                    'content' => 'shortcode:flavor_recetas_buscador',
                ],
                'foro' => [
                    'label'   => __('Foro', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-admin-comments',
                    'content' => 'callback:render_tab_foro',
                ],
                'multimedia' => [
                    'label'   => __('Multimedia', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-format-gallery',
                    'content' => 'callback:render_tab_multimedia',
                ],
                'red-social' => [
                    'label'   => __('Red social', 'flavor-chat-ia'),
                    'icon'    => 'dashicons-share',
                    'content' => 'callback:render_tab_red_social',
                    'requires_login' => true,
                ],
            ],

            'archive' => [
                'columns'       => 3,
                'per_page'      => 12,
                'order_by'      => 'created_at',
                'order'         => 'DESC',
                'filterable_by' => ['categoria', 'dificultad', 'tiempo'],
            ],

            'dashboard' => [
                'widgets' => [
                    'recetas_destacadas' => ['type' => 'carousel', 'title' => __('Recetas destacadas', 'flavor-chat-ia')],
                    'mis_recetas'        => ['type' => 'list', 'title' => __('Mis recetas', 'flavor-chat-ia')],
                ],
                'actions' => [
                    'nueva_receta' => [
                        'label' => __('Nueva receta', 'flavor-chat-ia'),
                        'icon'  => '➕',
                        'modal' => 'recetas-nueva',
                    ],
                ],
            ],

            'features' => [
                'has_archive'    => true,
                'has_single'     => true,
                'has_dashboard'  => true,
                'has_search'     => true,
                'has_categories' => true,
                'has_ratings'    => true,
                'has_favorites'  => true,
                'has_comments'   => true,
                'has_print'      => true,
            ],
        ];
    }

    /**
     * Renderiza el tab de foro contextual.
     *
     * @return string
     */
    public function render_tab_foro(): string {
        return $this->action_foro_receta([]);
    }

    /**
     * Renderiza el tab de multimedia contextual.
     *
     * @return string
     */
    public function render_tab_multimedia(): string {
        return $this->action_multimedia_receta([]);
    }

    /**
     * Renderiza el tab social contextual.
     *
     * @return string
     */
    public function render_tab_red_social(): string {
        return $this->action_red_social_receta([]);
    }
}
