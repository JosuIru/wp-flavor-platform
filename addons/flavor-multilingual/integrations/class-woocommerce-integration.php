<?php
/**
 * Integración con WooCommerce
 *
 * Permite traducir productos, categorías, atributos y checkout.
 *
 * @package FlavorMultilingual
 * @since 1.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_ML_WooCommerce_Integration {

    /**
     * Instancia singleton
     *
     * @var Flavor_ML_WooCommerce_Integration|null
     */
    private static $instance = null;

    /**
     * Campos de producto traducibles
     *
     * @var array
     */
    private $product_translatable_fields = array(
        'post_title'      => 'title',
        'post_content'    => 'content',
        'post_excerpt'    => 'short_description',
        '_purchase_note'  => 'purchase_note',
        '_button_text'    => 'button_text',
    );

    /**
     * Meta keys traducibles de productos
     *
     * @var array
     */
    private $product_meta_translatable = array(
        '_purchase_note',
        '_button_text',
        '_product_url',
    );

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_ML_WooCommerce_Integration
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        // Solo inicializar si WooCommerce está activo
        if (!$this->is_woocommerce_active()) {
            return;
        }

        $this->init_hooks();
    }

    /**
     * Verifica si WooCommerce está activo
     *
     * @return bool
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce');
    }

    /**
     * Inicializa los hooks
     */
    private function init_hooks() {
        // === PRODUCTOS ===
        // Filtrar título y descripción de productos
        add_filter('the_title', array($this, 'filter_product_title'), 10, 2);
        add_filter('woocommerce_short_description', array($this, 'filter_product_short_description'));
        add_filter('the_content', array($this, 'filter_product_content'), 5);

        // Filtrar datos del producto en listas
        add_filter('woocommerce_product_get_name', array($this, 'filter_product_name'), 10, 2);
        add_filter('woocommerce_product_get_short_description', array($this, 'filter_product_short_desc'), 10, 2);
        add_filter('woocommerce_product_get_description', array($this, 'filter_product_description'), 10, 2);
        add_filter('woocommerce_product_get_purchase_note', array($this, 'filter_purchase_note'), 10, 2);

        // === CATEGORÍAS Y ATRIBUTOS ===
        add_filter('get_term', array($this, 'filter_product_term'), 10, 2);
        add_filter('get_terms', array($this, 'filter_product_terms'), 10, 3);
        add_filter('woocommerce_attribute_label', array($this, 'filter_attribute_label'), 10, 3);

        // === CARRITO Y CHECKOUT ===
        add_filter('woocommerce_cart_item_name', array($this, 'filter_cart_item_name'), 10, 3);
        add_filter('woocommerce_order_item_name', array($this, 'filter_order_item_name'), 10, 2);

        // === EMAILS ===
        add_filter('woocommerce_email_subject_new_order', array($this, 'filter_email_subject'), 10, 2);
        add_filter('woocommerce_email_heading_new_order', array($this, 'filter_email_heading'), 10, 2);

        // === ADMIN ===
        if (is_admin()) {
            // Metabox de traducciones en productos
            add_action('add_meta_boxes', array($this, 'add_product_translation_metabox'), 30);

            // Campos en categorías de producto
            add_action('product_cat_add_form_fields', array($this, 'add_category_translation_fields'));
            add_action('product_cat_edit_form_fields', array($this, 'edit_category_translation_fields'), 10, 2);
            add_action('created_product_cat', array($this, 'save_category_translations'));
            add_action('edited_product_cat', array($this, 'save_category_translations'));

            // Campos en atributos de producto
            add_action('woocommerce_after_add_attribute_fields', array($this, 'add_attribute_translation_fields'));
            add_action('woocommerce_after_edit_attribute_fields', array($this, 'edit_attribute_translation_fields'));
            add_action('woocommerce_attribute_added', array($this, 'save_attribute_translations'), 10, 2);
            add_action('woocommerce_attribute_updated', array($this, 'save_attribute_translations'), 10, 3);

            // Pestaña en producto
            add_filter('woocommerce_product_data_tabs', array($this, 'add_translation_product_tab'));
            add_action('woocommerce_product_data_panels', array($this, 'render_translation_product_panel'));

            // AJAX
            add_action('wp_ajax_flavor_ml_translate_product', array($this, 'ajax_translate_product'));
            add_action('wp_ajax_flavor_ml_save_product_translation', array($this, 'ajax_save_product_translation'));

            // Guardar traducciones cuando se guarda el producto
            add_action('woocommerce_process_product_meta', array($this, 'save_product_translations'), 20);
        }

        // === API REST ===
        add_action('rest_api_init', array($this, 'register_wc_translation_endpoints'));

        // === STRINGS DE WOOCOMMERCE ===
        add_filter('gettext', array($this, 'filter_woocommerce_strings'), 10, 3);
        add_filter('gettext_with_context', array($this, 'filter_woocommerce_strings_context'), 10, 4);

        // === INTEGRACIÓN CON TRADUCCIÓN DE POSTS ===
        add_filter('flavor_multilingual_supported_post_types', array($this, 'add_wc_post_types'));
    }

    // ================================================================
    // FILTROS DE FRONTEND
    // ================================================================

    /**
     * Filtra el título del producto
     *
     * @param string $title   Título
     * @param int    $post_id ID del post
     * @return string
     */
    public function filter_product_title($title, $post_id) {
        if (!$post_id || get_post_type($post_id) !== 'product') {
            return $title;
        }

        return $this->get_translated_field($post_id, 'title', $title);
    }

    /**
     * Filtra la descripción corta del producto
     *
     * @param string $description Descripción
     * @return string
     */
    public function filter_product_short_description($description) {
        global $post;

        if (!$post || get_post_type($post->ID) !== 'product') {
            return $description;
        }

        return $this->get_translated_field($post->ID, 'short_description', $description);
    }

    /**
     * Filtra el contenido/descripción completa del producto
     *
     * @param string $content Contenido
     * @return string
     */
    public function filter_product_content($content) {
        global $post;

        if (!$post || get_post_type($post->ID) !== 'product') {
            return $content;
        }

        return $this->get_translated_field($post->ID, 'content', $content);
    }

    /**
     * Filtra el nombre del producto (objeto WC_Product)
     *
     * @param string     $name    Nombre
     * @param WC_Product $product Producto
     * @return string
     */
    public function filter_product_name($name, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $name;
        }

        return $this->get_translated_field($product->get_id(), 'title', $name);
    }

    /**
     * Filtra la descripción corta del objeto producto
     *
     * @param string     $desc    Descripción
     * @param WC_Product $product Producto
     * @return string
     */
    public function filter_product_short_desc($desc, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $desc;
        }

        return $this->get_translated_field($product->get_id(), 'short_description', $desc);
    }

    /**
     * Filtra la descripción completa del objeto producto
     *
     * @param string     $desc    Descripción
     * @param WC_Product $product Producto
     * @return string
     */
    public function filter_product_description($desc, $product) {
        if (is_admin() && !wp_doing_ajax()) {
            return $desc;
        }

        return $this->get_translated_field($product->get_id(), 'content', $desc);
    }

    /**
     * Filtra la nota de compra del producto
     *
     * @param string     $note    Nota
     * @param WC_Product $product Producto
     * @return string
     */
    public function filter_purchase_note($note, $product) {
        if (is_admin()) {
            return $note;
        }

        return $this->get_translated_field($product->get_id(), 'purchase_note', $note);
    }

    /**
     * Obtiene un campo traducido del producto
     *
     * @param int    $product_id ID del producto
     * @param string $field      Campo
     * @param string $default    Valor por defecto
     * @return string
     */
    private function get_translated_field($product_id, $field, $default) {
        $core = Flavor_Multilingual_Core::get_instance();

        if ($core->is_default_language()) {
            return $default;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_translation('product', $product_id, $current_lang, $field);

        return ($translation !== null && $translation !== '') ? $translation : $default;
    }

    // ================================================================
    // CATEGORÍAS Y ATRIBUTOS
    // ================================================================

    /**
     * Filtra términos de producto (categorías, etiquetas)
     *
     * @param WP_Term $term     Término
     * @param string  $taxonomy Taxonomía
     * @return WP_Term
     */
    public function filter_product_term($term, $taxonomy) {
        if (is_admin() && !wp_doing_ajax()) {
            return $term;
        }

        // Solo taxonomías de WooCommerce
        $wc_taxonomies = array('product_cat', 'product_tag');
        if (!in_array($taxonomy, $wc_taxonomies)) {
            return $term;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        if ($core->is_default_language()) {
            return $term;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Traducir nombre
        $translated_name = $storage->get_translation('term', $term->term_id, $current_lang, 'name');
        if ($translated_name) {
            $term->name = $translated_name;
        }

        // Traducir descripción
        $translated_desc = $storage->get_translation('term', $term->term_id, $current_lang, 'description');
        if ($translated_desc) {
            $term->description = $translated_desc;
        }

        return $term;
    }

    /**
     * Filtra array de términos
     *
     * @param array  $terms    Términos
     * @param array  $taxonomies Taxonomías
     * @param array  $args     Argumentos
     * @return array
     */
    public function filter_product_terms($terms, $taxonomies, $args) {
        if (is_admin() && !wp_doing_ajax()) {
            return $terms;
        }

        if (!is_array($terms)) {
            return $terms;
        }

        foreach ($terms as $key => $term) {
            if (is_object($term) && isset($term->taxonomy)) {
                $terms[$key] = $this->filter_product_term($term, $term->taxonomy);
            }
        }

        return $terms;
    }

    /**
     * Filtra etiquetas de atributos
     *
     * @param string $label     Etiqueta
     * @param string $name      Nombre del atributo
     * @param array  $product   Producto (opcional)
     * @return string
     */
    public function filter_attribute_label($label, $name, $product = null) {
        if (is_admin() && !wp_doing_ajax()) {
            return $label;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        if ($core->is_default_language()) {
            return $label;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        // Buscar traducción del atributo
        $attribute_id = wc_attribute_taxonomy_id_by_name($name);
        if ($attribute_id) {
            $translation = $storage->get_translation('wc_attribute', $attribute_id, $current_lang, 'label');
            if ($translation) {
                return $translation;
            }
        }

        return $label;
    }

    // ================================================================
    // CARRITO Y CHECKOUT
    // ================================================================

    /**
     * Filtra nombre del producto en el carrito
     *
     * @param string $name      Nombre
     * @param array  $cart_item Item del carrito
     * @param string $cart_key  Clave del carrito
     * @return string
     */
    public function filter_cart_item_name($name, $cart_item, $cart_key) {
        $product_id = $cart_item['product_id'] ?? 0;

        if (!$product_id) {
            return $name;
        }

        return $this->get_translated_field($product_id, 'title', $name);
    }

    /**
     * Filtra nombre del producto en pedidos
     *
     * @param string        $name Nombre
     * @param WC_Order_Item $item Item del pedido
     * @return string
     */
    public function filter_order_item_name($name, $item) {
        $product_id = $item->get_product_id();

        if (!$product_id) {
            return $name;
        }

        return $this->get_translated_field($product_id, 'title', $name);
    }

    // ================================================================
    // EMAILS
    // ================================================================

    /**
     * Filtra asunto de emails
     *
     * @param string   $subject Asunto
     * @param WC_Order $order   Pedido
     * @return string
     */
    public function filter_email_subject($subject, $order) {
        // Detectar idioma del cliente
        $customer_lang = get_user_meta($order->get_customer_id(), 'flavor_preferred_language', true);

        if (!$customer_lang) {
            return $subject;
        }

        // Buscar traducción del asunto
        $storage = Flavor_Translation_Storage::get_instance();
        $key = md5('email_subject_new_order');
        $translation = $storage->get_string_translation($subject, $customer_lang, 'woocommerce');

        return $translation ?: $subject;
    }

    /**
     * Filtra encabezado de emails
     *
     * @param string   $heading Encabezado
     * @param WC_Order $order   Pedido
     * @return string
     */
    public function filter_email_heading($heading, $order) {
        $customer_lang = get_user_meta($order->get_customer_id(), 'flavor_preferred_language', true);

        if (!$customer_lang) {
            return $heading;
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $translation = $storage->get_string_translation($heading, $customer_lang, 'woocommerce');

        return $translation ?: $heading;
    }

    // ================================================================
    // ADMIN - METABOX DE PRODUCTOS
    // ================================================================

    /**
     * Añade pestaña de traducciones en datos de producto
     *
     * @param array $tabs Pestañas
     * @return array
     */
    public function add_translation_product_tab($tabs) {
        $tabs['flavor_translations'] = array(
            'label'    => __('🌐 Traducciones', 'flavor-multilingual'),
            'target'   => 'flavor_translations_panel',
            'class'    => array(),
            'priority' => 90,
        );

        return $tabs;
    }

    /**
     * Renderiza el panel de traducciones del producto
     */
    public function render_translation_product_panel() {
        global $post;

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        wp_nonce_field('flavor_ml_product', 'flavor_ml_product_nonce');
        ?>
        <div id="flavor_translations_panel" class="panel woocommerce_options_panel">
            <div class="options_group">
                <p class="form-field">
                    <label><?php _e('Traducciones del producto', 'flavor-multilingual'); ?></label>
                    <span class="description">
                        <?php _e('Traduce el nombre, descripción corta y descripción completa del producto.', 'flavor-multilingual'); ?>
                    </span>
                </p>

                <?php foreach ($languages as $code => $lang) : ?>
                    <?php if ($code === $default_lang) continue; ?>

                    <?php
                    $title_trans = $storage->get_translation('product', $post->ID, $code, 'title') ?? '';
                    $short_desc_trans = $storage->get_translation('product', $post->ID, $code, 'short_description') ?? '';
                    $content_trans = $storage->get_translation('product', $post->ID, $code, 'content') ?? '';
                    ?>

                    <div class="flavor-ml-product-lang" data-lang="<?php echo esc_attr($code); ?>">
                        <h4 style="padding: 10px 12px; background: #f8f8f8; border-bottom: 1px solid #ddd; margin: 0;">
                            <?php if ($lang['flag']) : ?>
                                <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>"
                                     alt="" width="18" height="12" style="margin-right: 6px;">
                            <?php endif; ?>
                            <?php echo esc_html($lang['native_name']); ?>
                            <button type="button" class="button button-small flavor-ml-translate-product-btn"
                                    data-lang="<?php echo esc_attr($code); ?>"
                                    style="float: right; margin-top: -3px;">
                                <span class="dashicons dashicons-translation" style="font-size: 14px; line-height: 1.5;"></span>
                                <?php _e('Traducir con IA', 'flavor-multilingual'); ?>
                            </button>
                        </h4>

                        <p class="form-field">
                            <label><?php _e('Nombre', 'flavor-multilingual'); ?></label>
                            <input type="text"
                                   name="flavor_ml_product[<?php echo esc_attr($code); ?>][title]"
                                   value="<?php echo esc_attr($title_trans); ?>"
                                   class="short"
                                   placeholder="<?php echo esc_attr($post->post_title); ?>">
                        </p>

                        <p class="form-field">
                            <label><?php _e('Descripción corta', 'flavor-multilingual'); ?></label>
                            <textarea name="flavor_ml_product[<?php echo esc_attr($code); ?>][short_description]"
                                      rows="3"
                                      placeholder="<?php echo esc_attr(wp_strip_all_tags($post->post_excerpt)); ?>"
                                      style="width: 100%;"><?php echo esc_textarea($short_desc_trans); ?></textarea>
                        </p>

                        <p class="form-field">
                            <label><?php _e('Descripción completa', 'flavor-multilingual'); ?></label>
                            <textarea name="flavor_ml_product[<?php echo esc_attr($code); ?>][content]"
                                      rows="5"
                                      placeholder="<?php _e('Descripción traducida...', 'flavor-multilingual'); ?>"
                                      style="width: 100%;"><?php echo esc_textarea($content_trans); ?></textarea>
                        </p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        jQuery(function($) {
            // Guardar traducciones cuando se guarda el producto
            $('form#post').on('submit', function() {
                // Las traducciones se envían con el formulario
            });

            // Traducir con IA
            $('.flavor-ml-translate-product-btn').on('click', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var lang = $btn.data('lang');
                var postId = $('#post_ID').val();
                var $container = $btn.closest('.flavor-ml-product-lang');

                $btn.prop('disabled', true);
                $btn.find('.dashicons').addClass('spin');

                $.post(ajaxurl, {
                    action: 'flavor_ml_translate_product',
                    nonce: $('#flavor_ml_product_nonce').val(),
                    post_id: postId,
                    lang: lang
                }, function(response) {
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('spin');

                    if (response.success && response.data.translations) {
                        var trans = response.data.translations;
                        if (trans.title) {
                            $container.find('input[name*="[title]"]').val(trans.title);
                        }
                        if (trans.short_description) {
                            $container.find('textarea[name*="[short_description]"]').val(trans.short_description);
                        }
                        if (trans.content) {
                            $container.find('textarea[name*="[content]"]').val(trans.content);
                        }
                    } else {
                        alert(response.data || '<?php _e('Error al traducir', 'flavor-multilingual'); ?>');
                    }
                });
            });
        });
        </script>
        <style>
            .flavor-ml-product-lang { border: 1px solid #ddd; margin: 10px 12px; background: #fff; }
            .flavor-ml-product-lang .form-field { padding: 10px 12px !important; }
            .flavor-ml-product-lang .form-field label { display: block; margin-bottom: 5px; }
            .flavor-ml-product-lang input.short { width: 100%; }
            .dashicons.spin { animation: spin 1s linear infinite; }
            @keyframes spin { 100% { transform: rotate(360deg); } }
        </style>
        <?php
    }

    /**
     * Guarda traducciones de producto al guardar
     *
     * @param int $product_id ID del producto
     */
    public function save_product_translations($product_id) {
        // Verificar nonce
        if (!isset($_POST['flavor_ml_product_nonce']) ||
            !wp_verify_nonce($_POST['flavor_ml_product_nonce'], 'flavor_ml_product')) {
            return;
        }

        // Verificar permisos
        if (!current_user_can('edit_product', $product_id)) {
            return;
        }

        // Verificar que hay datos de traducción
        if (!isset($_POST['flavor_ml_product']) || !is_array($_POST['flavor_ml_product'])) {
            return;
        }

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($_POST['flavor_ml_product'] as $lang => $fields) {
            $lang = sanitize_key($lang);

            foreach ($fields as $field => $value) {
                $field = sanitize_key($field);
                $value = wp_kses_post($value);

                if (!empty($value)) {
                    $storage->save_translation('product', $product_id, $lang, $field, $value, array(
                        'auto'   => false,
                        'status' => 'published',
                    ));
                }
            }
        }
    }

    /**
     * Añade metabox alternativo para traducciones de producto
     */
    public function add_product_translation_metabox() {
        // El metabox principal está en las pestañas de datos del producto
        // Este es un metabox lateral con resumen
        add_meta_box(
            'flavor-ml-product-status',
            __('🌐 Estado de Traducciones', 'flavor-multilingual'),
            array($this, 'render_product_status_metabox'),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Renderiza el metabox de estado de traducciones
     *
     * @param WP_Post $post Post actual
     */
    public function render_product_status_metabox($post) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        echo '<ul style="margin: 0;">';

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) continue;

            $has_title = $storage->get_translation('product', $post->ID, $code, 'title');
            $has_desc = $storage->get_translation('product', $post->ID, $code, 'content');

            $status_icon = ($has_title && $has_desc) ? '✅' : ($has_title || $has_desc ? '⚠️' : '❌');
            $status_text = ($has_title && $has_desc)
                ? __('Completo', 'flavor-multilingual')
                : ($has_title || $has_desc ? __('Parcial', 'flavor-multilingual') : __('Sin traducir', 'flavor-multilingual'));

            printf(
                '<li style="padding: 5px 0; border-bottom: 1px solid #eee;">
                    %s <strong>%s</strong>: %s <small style="color: #666;">%s</small>
                </li>',
                $lang['flag'] ? '<img src="' . esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']) . '" width="16" height="11">' : '',
                esc_html($lang['native_name']),
                $status_icon,
                esc_html($status_text)
            );
        }

        echo '</ul>';
        echo '<p style="margin-top: 10px;"><small>' . __('Edita traducciones en la pestaña "Traducciones" de datos del producto.', 'flavor-multilingual') . '</small></p>';
    }

    // ================================================================
    // ADMIN - CATEGORÍAS
    // ================================================================

    /**
     * Añade campos de traducción en formulario de añadir categoría
     */
    public function add_category_translation_fields() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) continue;
            ?>
            <div class="form-field">
                <label><?php printf(__('Nombre (%s)', 'flavor-multilingual'), $lang['native_name']); ?></label>
                <input type="text" name="flavor_ml_term[<?php echo esc_attr($code); ?>][name]">
            </div>
            <div class="form-field">
                <label><?php printf(__('Descripción (%s)', 'flavor-multilingual'), $lang['native_name']); ?></label>
                <textarea name="flavor_ml_term[<?php echo esc_attr($code); ?>][description]" rows="3"></textarea>
            </div>
            <?php
        }
    }

    /**
     * Añade campos de traducción en formulario de editar categoría
     *
     * @param WP_Term $term     Término
     * @param string  $taxonomy Taxonomía
     */
    public function edit_category_translation_fields($term, $taxonomy) {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) continue;

            $name_trans = $storage->get_translation('term', $term->term_id, $code, 'name') ?? '';
            $desc_trans = $storage->get_translation('term', $term->term_id, $code, 'description') ?? '';
            ?>
            <tr class="form-field">
                <th scope="row">
                    <?php if ($lang['flag']) : ?>
                        <img src="<?php echo esc_url(FLAVOR_MULTILINGUAL_URL . 'assets/flags/' . $lang['flag']); ?>" width="16" height="11">
                    <?php endif; ?>
                    <?php printf(__('Nombre (%s)', 'flavor-multilingual'), $lang['native_name']); ?>
                </th>
                <td>
                    <input type="text" name="flavor_ml_term[<?php echo esc_attr($code); ?>][name]"
                           value="<?php echo esc_attr($name_trans); ?>">
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row">
                    <?php printf(__('Descripción (%s)', 'flavor-multilingual'), $lang['native_name']); ?>
                </th>
                <td>
                    <textarea name="flavor_ml_term[<?php echo esc_attr($code); ?>][description]"
                              rows="3"><?php echo esc_textarea($desc_trans); ?></textarea>
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Guarda traducciones de categoría
     *
     * @param int $term_id ID del término
     */
    public function save_category_translations($term_id) {
        if (!isset($_POST['flavor_ml_term'])) {
            return;
        }

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($_POST['flavor_ml_term'] as $lang => $fields) {
            $lang = sanitize_key($lang);

            if (!empty($fields['name'])) {
                $storage->save_translation('term', $term_id, $lang, 'name', sanitize_text_field($fields['name']));
            }

            if (!empty($fields['description'])) {
                $storage->save_translation('term', $term_id, $lang, 'description', wp_kses_post($fields['description']));
            }
        }
    }

    // ================================================================
    // ADMIN - ATRIBUTOS
    // ================================================================

    /**
     * Añade campos de traducción en formulario de añadir atributo
     */
    public function add_attribute_translation_fields() {
        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) continue;
            ?>
            <div class="form-field">
                <label for="flavor_ml_attr_<?php echo esc_attr($code); ?>">
                    <?php printf(__('Nombre (%s)', 'flavor-multilingual'), $lang['native_name']); ?>
                </label>
                <input type="text" name="flavor_ml_attribute[<?php echo esc_attr($code); ?>]"
                       id="flavor_ml_attr_<?php echo esc_attr($code); ?>">
            </div>
            <?php
        }
    }

    /**
     * Añade campos de traducción en formulario de editar atributo
     */
    public function edit_attribute_translation_fields() {
        global $wpdb;

        $attribute_id = isset($_GET['edit']) ? absint($_GET['edit']) : 0;
        if (!$attribute_id) {
            return;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $languages = $core->get_active_languages();
        $default_lang = $core->get_default_language();
        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($languages as $code => $lang) {
            if ($code === $default_lang) continue;

            $label_trans = $storage->get_translation('wc_attribute', $attribute_id, $code, 'label') ?? '';
            ?>
            <tr class="form-field">
                <th scope="row" valign="top">
                    <label for="flavor_ml_attr_<?php echo esc_attr($code); ?>">
                        <?php printf(__('Nombre (%s)', 'flavor-multilingual'), $lang['native_name']); ?>
                    </label>
                </th>
                <td>
                    <input type="text" name="flavor_ml_attribute[<?php echo esc_attr($code); ?>]"
                           id="flavor_ml_attr_<?php echo esc_attr($code); ?>"
                           value="<?php echo esc_attr($label_trans); ?>">
                </td>
            </tr>
            <?php
        }
    }

    /**
     * Guarda traducciones de atributo
     *
     * @param int   $attribute_id ID del atributo
     * @param array $attribute    Datos del atributo
     */
    public function save_attribute_translations($attribute_id, $attribute) {
        if (!isset($_POST['flavor_ml_attribute'])) {
            return;
        }

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($_POST['flavor_ml_attribute'] as $lang => $label) {
            $lang = sanitize_key($lang);
            $label = sanitize_text_field($label);

            if (!empty($label)) {
                $storage->save_translation('wc_attribute', $attribute_id, $lang, 'label', $label);
            }
        }
    }

    // ================================================================
    // AJAX
    // ================================================================

    /**
     * AJAX: Traducir producto con IA
     */
    public function ajax_translate_product() {
        check_ajax_referer('flavor_ml_product', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $to_lang = sanitize_key($_POST['lang'] ?? '');

        if (!$post_id || !$to_lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $product = wc_get_product($post_id);
        if (!$product) {
            wp_send_json_error(__('Producto no encontrado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();

        $translations = array();

        // Traducir nombre
        $name = $product->get_name();
        if ($name) {
            $result = $translator->translate_text($name, $from_lang, $to_lang, 'Nombre de producto de tienda online');
            if (!is_wp_error($result)) {
                $translations['title'] = $result;
            }
        }

        // Traducir descripción corta
        $short_desc = $product->get_short_description();
        if ($short_desc) {
            $result = $translator->translate_html($short_desc, $from_lang, $to_lang, 'Descripción corta de producto');
            if (!is_wp_error($result)) {
                $translations['short_description'] = $result;
            }
        }

        // Traducir descripción completa
        $description = $product->get_description();
        if ($description) {
            $result = $translator->translate_html($description, $from_lang, $to_lang, 'Descripción completa de producto');
            if (!is_wp_error($result)) {
                $translations['content'] = $result;
            }
        }

        if (empty($translations)) {
            wp_send_json_error(__('No hay contenido para traducir', 'flavor-multilingual'));
        }

        wp_send_json_success(array(
            'translations' => $translations,
            'message'      => __('Producto traducido', 'flavor-multilingual'),
        ));
    }

    /**
     * AJAX: Guardar traducción de producto
     */
    public function ajax_save_product_translation() {
        check_ajax_referer('flavor_ml_product', 'nonce');

        if (!current_user_can('edit_products')) {
            wp_send_json_error(__('Sin permisos', 'flavor-multilingual'));
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $lang = sanitize_key($_POST['lang'] ?? '');
        $fields = isset($_POST['fields']) ? $_POST['fields'] : array();

        if (!$post_id || !$lang) {
            wp_send_json_error(__('Parámetros inválidos', 'flavor-multilingual'));
        }

        $storage = Flavor_Translation_Storage::get_instance();
        $saved = 0;

        foreach ($fields as $field => $value) {
            $field = sanitize_key($field);
            $value = wp_kses_post($value);

            if (!empty($value)) {
                $storage->save_translation('product', $post_id, $lang, $field, $value);
                $saved++;
            }
        }

        wp_send_json_success(array(
            'message' => sprintf(__('Guardados %d campos', 'flavor-multilingual'), $saved),
        ));
    }

    // ================================================================
    // API REST
    // ================================================================

    /**
     * Registra endpoints de API para WooCommerce
     */
    public function register_wc_translation_endpoints() {
        register_rest_route('flavor-multilingual/v1', '/products/(?P<id>\d+)/translations', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array($this, 'api_get_product_translations'),
                'permission_callback' => '__return_true',
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array($this, 'api_save_product_translations'),
                'permission_callback' => function() {
                    return current_user_can('edit_products');
                },
            ),
        ));
    }

    /**
     * API: Obtener traducciones de producto
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_get_product_translations($request) {
        $product_id = $request->get_param('id');
        $storage = Flavor_Translation_Storage::get_instance();

        return rest_ensure_response(
            $storage->get_all_translations('product', $product_id)
        );
    }

    /**
     * API: Guardar traducciones de producto
     *
     * @param WP_REST_Request $request Request
     * @return WP_REST_Response
     */
    public function api_save_product_translations($request) {
        $product_id = $request->get_param('id');
        $lang = $request->get_param('lang');
        $fields = $request->get_param('fields');

        if (!$lang || !$fields) {
            return new WP_Error('missing_params', __('Faltan parámetros', 'flavor-multilingual'), array('status' => 400));
        }

        $storage = Flavor_Translation_Storage::get_instance();

        foreach ($fields as $field => $value) {
            $storage->save_translation('product', $product_id, $lang, $field, $value);
        }

        return rest_ensure_response(array('success' => true));
    }

    // ================================================================
    // STRINGS DE WOOCOMMERCE
    // ================================================================

    /**
     * Filtra strings de WooCommerce
     *
     * @param string $translation Traducción
     * @param string $text        Texto original
     * @param string $domain      Dominio
     * @return string
     */
    public function filter_woocommerce_strings($translation, $text, $domain) {
        if ($domain !== 'woocommerce') {
            return $translation;
        }

        $core = Flavor_Multilingual_Core::get_instance();
        if ($core->is_default_language()) {
            return $translation;
        }

        $current_lang = $core->get_current_language();
        $storage = Flavor_Translation_Storage::get_instance();

        $custom_trans = $storage->get_string_translation($text, $current_lang, 'woocommerce');

        return $custom_trans ?: $translation;
    }

    /**
     * Filtra strings con contexto de WooCommerce
     *
     * @param string $translation Traducción
     * @param string $text        Texto original
     * @param string $context     Contexto
     * @param string $domain      Dominio
     * @return string
     */
    public function filter_woocommerce_strings_context($translation, $text, $context, $domain) {
        return $this->filter_woocommerce_strings($translation, $text, $domain);
    }

    // ================================================================
    // UTILIDADES
    // ================================================================

    /**
     * Añade post types de WooCommerce a la lista de traducibles
     *
     * @param array $post_types Post types
     * @return array
     */
    public function add_wc_post_types($post_types) {
        $wc_types = array('product', 'product_variation', 'shop_order', 'shop_coupon');

        foreach ($wc_types as $type) {
            if (!in_array($type, $post_types)) {
                $post_types[] = $type;
            }
        }

        return $post_types;
    }

    /**
     * Traduce un producto completo
     *
     * @param int    $product_id ID del producto
     * @param string $to_lang    Idioma destino
     * @return array|WP_Error
     */
    public function translate_product($product_id, $to_lang) {
        $product = wc_get_product($product_id);

        if (!$product) {
            return new WP_Error('product_not_found', __('Producto no encontrado', 'flavor-multilingual'));
        }

        $core = Flavor_Multilingual_Core::get_instance();
        $from_lang = $core->get_default_language();
        $translator = Flavor_AI_Translator::get_instance();
        $storage = Flavor_Translation_Storage::get_instance();

        $translations = array();
        $fields = array(
            'title'             => $product->get_name(),
            'short_description' => $product->get_short_description(),
            'content'           => $product->get_description(),
            'purchase_note'     => $product->get_purchase_note(),
        );

        foreach ($fields as $field => $value) {
            if (empty($value)) {
                continue;
            }

            $is_html = in_array($field, array('short_description', 'content'));
            $context = sprintf('Producto WooCommerce - %s', $field);

            if ($is_html) {
                $result = $translator->translate_html($value, $from_lang, $to_lang, $context);
            } else {
                $result = $translator->translate_text($value, $from_lang, $to_lang, $context);
            }

            if (!is_wp_error($result)) {
                $translations[$field] = $result;
                $storage->save_translation('product', $product_id, $to_lang, $field, $result, array(
                    'auto'   => true,
                    'status' => 'draft',
                ));
            }
        }

        return $translations;
    }
}
