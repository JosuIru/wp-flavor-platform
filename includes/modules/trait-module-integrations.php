<?php
/**
 * Trait para Integraciones Dinamicas entre Modulos
 *
 * Este trait permite que los modulos polivalentes se integren automaticamente
 * con cualquier otro modulo activo que declare compatibilidad.
 *
 * ARQUITECTURA:
 * 1. Cada modulo polivalente declara que "ofrece" (ej: recetas, videos, facturas)
 * 2. Cada modulo base declara que "acepta" (ej: productos acepta recetas, videos)
 * 3. El sistema crea automaticamente las relaciones cuando ambos estan activos
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Trait para modulos que ofrecen contenido relacional
 */
trait Flavor_Module_Integration_Provider {

    /**
     * Tipo de contenido que ofrece este modulo
     * Debe ser sobreescrito por cada modulo
     *
     * @return array {
     *     @type string $id          ID unico del tipo de contenido
     *     @type string $label       Etiqueta para mostrar
     *     @type string $icon        Dashicon
     *     @type string $post_type   CPT asociado (si aplica)
     *     @type string $capability  Capacidad requerida para crear
     * }
     */
    protected function get_integration_content_type() {
        return [];
    }

    /**
     * Registrar este modulo como proveedor de contenido
     */
    protected function register_as_integration_provider() {
        $content_type = $this->get_integration_content_type();

        if (empty($content_type)) {
            return;
        }

        add_filter('flavor_integration_providers', function($providers) use ($content_type) {
            $providers[$content_type['id']] = array_merge($content_type, [
                'module_id' => $this->id,
                'module_instance' => $this,
            ]);
            return $providers;
        });

        // Registrar hooks para renderizar en otros modulos
        add_action('flavor_render_integration_' . $content_type['id'], [$this, 'render_integration_box'], 10, 3);
        add_action('flavor_save_integration_' . $content_type['id'], [$this, 'save_integration_data'], 10, 3);
    }

    /**
     * Renderizar caja de integracion en otro modulo
     * Puede ser sobreescrito por cada modulo para personalizar
     *
     * @param int    $object_id   ID del objeto (post, user, etc)
     * @param string $object_type Tipo de objeto
     * @param array  $context     Contexto adicional
     */
    public function render_integration_box($object_id, $object_type, $context = []) {
        $content_type = $this->get_integration_content_type();
        $meta_key = '_flavor_rel_' . $content_type['id'];

        // Obtener IDs relacionados
        $related_ids = get_metadata($object_type === 'user' ? 'user' : 'post', $object_id, $meta_key, true);
        if (!is_array($related_ids)) {
            $related_ids = [];
        }

        // Obtener items disponibles
        $available_items = $this->get_available_items_for_integration();

        ?>
        <div class="flavor-integration-box" data-type="<?php echo esc_attr($content_type['id']); ?>">
            <h4>
                <span class="dashicons <?php echo esc_attr($content_type['icon']); ?>"></span>
                <?php echo esc_html($content_type['label']); ?>
            </h4>

            <div class="flavor-integration-items" style="max-height: 200px; overflow-y: auto; border: 1px solid #ddd; padding: 10px; margin: 10px 0; border-radius: 4px;">
                <?php if (!empty($related_ids)): ?>
                    <?php foreach ($related_ids as $item_id):
                        $item = get_post($item_id);
                        if (!$item) continue;
                    ?>
                    <div class="flavor-integration-item" style="display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid #eee;">
                        <a href="<?php echo get_edit_post_link($item_id); ?>" target="_blank">
                            <?php echo esc_html($item->post_title); ?>
                        </a>
                        <button type="button" class="button-link flavor-remove-integration" data-id="<?php echo esc_attr($item_id); ?>">
                            <span class="dashicons dashicons-no-alt"></span>
                        </button>
                        <input type="hidden" name="<?php echo esc_attr($meta_key); ?>[]" value="<?php echo esc_attr($item_id); ?>" />
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="flavor-no-items" style="color: #666; font-style: italic; margin: 0;">
                        <?php printf(__('Sin %s vinculados', 'flavor-platform'), strtolower($content_type['label'])); ?>
                    </p>
                <?php endif; ?>
            </div>

            <div style="display: flex; gap: 5px;">
                <select class="flavor-integration-selector widefat" style="flex: 1;">
                    <option value=""><?php printf(__('Seleccionar %s...', 'flavor-platform'), strtolower($content_type['label'])); ?></option>
                    <?php foreach ($available_items as $item):
                        if (in_array($item->ID, $related_ids)) continue;
                    ?>
                    <option value="<?php echo esc_attr($item->ID); ?>"><?php echo esc_html($item->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="button" class="button flavor-add-integration">
                    <span class="dashicons dashicons-plus-alt" style="vertical-align: middle;"></span>
                </button>
            </div>

            <?php if (!empty($content_type['post_type']) && current_user_can($content_type['capability'] ?? 'edit_posts')): ?>
            <p style="margin-top: 10px;">
                <a href="<?php echo admin_url('post-new.php?post_type=' . $content_type['post_type']); ?>" class="button button-small" target="_blank">
                    <span class="dashicons dashicons-plus" style="vertical-align: middle;"></span>
                    <?php printf(__('Crear %s', 'flavor-platform'), $content_type['label']); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Obtener items disponibles para vincular
     */
    protected function get_available_items_for_integration() {
        $content_type = $this->get_integration_content_type();

        if (empty($content_type['post_type'])) {
            return [];
        }

        return get_posts([
            'post_type' => $content_type['post_type'],
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
    }

    /**
     * Guardar datos de integracion
     */
    public function save_integration_data($object_id, $object_type, $data) {
        $content_type = $this->get_integration_content_type();
        $meta_key = '_flavor_rel_' . $content_type['id'];

        $ids = [];
        if (isset($data[$meta_key]) && is_array($data[$meta_key])) {
            $ids = array_map('absint', $data[$meta_key]);
            $ids = array_filter($ids);
        }

        if ($object_type === 'user') {
            update_user_meta($object_id, $meta_key, $ids);
        } else {
            update_post_meta($object_id, $meta_key, $ids);
        }

        // Guardar relacion inversa para busquedas bidireccionales
        $this->sync_reverse_relations($object_id, $object_type, $ids, $meta_key);
    }

    /**
     * Sincronizar relaciones inversas
     */
    protected function sync_reverse_relations($object_id, $object_type, $new_ids, $meta_key) {
        $reverse_meta_key = $meta_key . '_reverse';
        $content_type = $this->get_integration_content_type();

        // Obtener IDs anteriores
        $old_ids = get_metadata($object_type === 'user' ? 'user' : 'post', $object_id, $meta_key . '_prev', true);
        if (!is_array($old_ids)) {
            $old_ids = [];
        }

        // Quitar de los que ya no estan
        $removed = array_diff($old_ids, $new_ids);
        foreach ($removed as $item_id) {
            $reverse = get_post_meta($item_id, $reverse_meta_key, true);
            if (is_array($reverse)) {
                $reverse = array_diff($reverse, [$object_id]);
                update_post_meta($item_id, $reverse_meta_key, array_values($reverse));
            }
        }

        // Agregar a los nuevos
        $added = array_diff($new_ids, $old_ids);
        foreach ($added as $item_id) {
            $reverse = get_post_meta($item_id, $reverse_meta_key, true);
            if (!is_array($reverse)) {
                $reverse = [];
            }
            if (!in_array($object_id, $reverse)) {
                $reverse[] = $object_id;
                update_post_meta($item_id, $reverse_meta_key, $reverse);
            }
        }

        // Guardar IDs actuales para proxima comparacion
        if ($object_type === 'user') {
            update_user_meta($object_id, $meta_key . '_prev', $new_ids);
        } else {
            update_post_meta($object_id, $meta_key . '_prev', $new_ids);
        }
    }
}

/**
 * Trait para modulos que aceptan contenido de otros modulos
 */
trait Flavor_Module_Integration_Consumer {

    /**
     * Tipos de contenido que este modulo acepta
     * Debe ser sobreescrito por cada modulo
     *
     * @return array Lista de IDs de tipos de contenido aceptados
     */
    protected function get_accepted_integrations() {
        return [];
    }

    /**
     * Tipos de objeto que pueden recibir integraciones
     *
     * @return array {
     *     @type string $type      'post' o 'user'
     *     @type string $post_type CPT (si type es 'post')
     *     @type string $context   'metabox', 'column', 'profile'
     * }
     */
    protected function get_integration_targets() {
        return [];
    }

    /**
     * Registrar este modulo como consumidor de integraciones
     */
    protected function register_as_integration_consumer() {
        $accepted = $this->get_accepted_integrations();
        $targets = $this->get_integration_targets();

        if (empty($accepted) || empty($targets)) {
            return;
        }

        // Registrar metaboxes para cada target
        add_action('add_meta_boxes', function() use ($accepted, $targets) {
            $this->register_integration_metaboxes($accepted, $targets);
        });

        // Hook para guardar
        foreach ($targets as $target) {
            if ($target['type'] === 'post' && !empty($target['post_type'])) {
                add_action('save_post_' . $target['post_type'], function($post_id) use ($accepted) {
                    $this->save_all_integrations($post_id, 'post', $accepted);
                });
            }
        }

        // Registrar en el sistema central
        add_filter('flavor_integration_consumers', function($consumers) use ($accepted, $targets) {
            $consumers[$this->id] = [
                'module_id' => $this->id,
                'accepted' => $accepted,
                'targets' => $targets,
            ];
            return $consumers;
        });
    }

    /**
     * Registrar metaboxes de integracion
     */
    protected function register_integration_metaboxes($accepted, $targets) {
        $providers = apply_filters('flavor_integration_providers', []);

        foreach ($targets as $target) {
            if ($target['type'] !== 'post' || empty($target['post_type'])) {
                continue;
            }

            // Filtrar solo los providers que estan activos Y aceptados
            $active_providers = array_filter($providers, function($provider) use ($accepted) {
                return in_array($provider['id'], $accepted);
            });

            if (empty($active_providers)) {
                continue;
            }

            add_meta_box(
                'flavor_integrations_' . $target['post_type'],
                __('Contenido Relacionado', 'flavor-platform'),
                function($post) use ($active_providers) {
                    $this->render_integrations_metabox($post, $active_providers);
                },
                $target['post_type'],
                $target['context'] ?? 'side',
                'default'
            );
        }
    }

    /**
     * Renderizar metabox con todas las integraciones
     */
    protected function render_integrations_metabox($post, $providers) {
        wp_nonce_field('flavor_integrations_save', 'flavor_integrations_nonce');

        if (empty($providers)) {
            echo '<p>' . __('No hay modulos de contenido activos para vincular.', 'flavor-platform') . '</p>';
            return;
        }

        echo '<div class="flavor-integrations-container">';

        foreach ($providers as $provider) {
            do_action('flavor_render_integration_' . $provider['id'], $post->ID, 'post', [
                'post_type' => $post->post_type,
            ]);
        }

        echo '</div>';

        // JavaScript para manejar la UI
        $this->render_integrations_js();
    }

    /**
     * JavaScript para la UI de integraciones
     */
    protected function render_integrations_js() {
        static $js_rendered = false;
        if ($js_rendered) return;
        $js_rendered = true;
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Agregar item
            $(document).on('click', '.flavor-add-integration', function() {
                var $box = $(this).closest('.flavor-integration-box');
                var $selector = $box.find('.flavor-integration-selector');
                var itemId = $selector.val();
                var itemText = $selector.find('option:selected').text();
                var type = $box.data('type');
                var metaKey = '_flavor_rel_' + type;

                if (!itemId) return;

                // Quitar mensaje de "sin items"
                $box.find('.flavor-no-items').remove();

                // Agregar item a la lista
                var html = '<div class="flavor-integration-item" style="display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid #eee;">' +
                    '<span>' + itemText + '</span>' +
                    '<button type="button" class="button-link flavor-remove-integration" data-id="' + itemId + '">' +
                    '<span class="dashicons dashicons-no-alt"></span>' +
                    '</button>' +
                    '<input type="hidden" name="' + metaKey + '[]" value="' + itemId + '" />' +
                    '</div>';

                $box.find('.flavor-integration-items').append(html);

                // Quitar del selector
                $selector.find('option:selected').remove();
                $selector.val('');
            });

            // Eliminar item
            $(document).on('click', '.flavor-remove-integration', function() {
                var $item = $(this).closest('.flavor-integration-item');
                var $box = $(this).closest('.flavor-integration-box');
                var itemId = $(this).data('id');
                var itemText = $item.find('a, span').first().text();
                var $selector = $box.find('.flavor-integration-selector');

                // Devolver al selector
                $selector.append('<option value="' + itemId + '">' + itemText + '</option>');

                // Quitar de la lista
                $item.remove();

                // Si no quedan items, mostrar mensaje
                if ($box.find('.flavor-integration-item').length === 0) {
                    var type = $box.data('type');
                    $box.find('.flavor-integration-items').html(
                        '<p class="flavor-no-items" style="color: #666; font-style: italic; margin: 0;">Sin elementos vinculados</p>'
                    );
                }
            });
        });
        </script>
        <style>
        .flavor-integration-box {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .flavor-integration-box:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        .flavor-integration-box h4 {
            margin: 0 0 10px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .flavor-integration-box h4 .dashicons {
            color: #0073aa;
        }
        </style>
        <?php
    }

    /**
     * Guardar todas las integraciones
     */
    protected function save_all_integrations($object_id, $object_type, $accepted) {
        if (!isset($_POST['flavor_integrations_nonce']) ||
            !wp_verify_nonce($_POST['flavor_integrations_nonce'], 'flavor_integrations_save')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $providers = apply_filters('flavor_integration_providers', []);

        foreach ($accepted as $type_id) {
            if (isset($providers[$type_id])) {
                do_action('flavor_save_integration_' . $type_id, $object_id, $object_type, $_POST);
            }
        }
    }
}

/**
 * Clase para Integraciones Funcionales entre Módulos
 *
 * Permite crear conexiones de acciones entre módulos:
 * - Eventos → Grupos Consumo (pedidos para catering)
 * - Cursos → Banco de Tiempo (pago con horas)
 * - Carpooling → Eventos (transporte compartido)
 *
 * @since 1.8.0
 */
class Flavor_Functional_Integrations {

    /**
     * Instancia singleton
     *
     * @var Flavor_Functional_Integrations|null
     */
    private static $instance = null;

    /**
     * Integraciones registradas
     *
     * @var array
     */
    private $integraciones = [];

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Functional_Integrations
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
        add_action('init', [$this, 'register_integrations'], 20);
        add_action('wp_ajax_flavor_functional_integration', [$this, 'ajax_ejecutar_integracion']);
        add_action('wp_ajax_flavor_get_integration_options', [$this, 'ajax_get_options']);
    }

    /**
     * Registrar integraciones predefinidas
     */
    public function register_integrations() {
        // Eventos → Grupos de Consumo: Pedido de catering
        $this->integraciones['eventos_gc_catering'] = [
            'id'          => 'eventos_gc_catering',
            'origen'      => 'eventos',
            'destino'     => 'grupos_consumo',
            'label'       => __('Pedido de Catering', 'flavor-platform'),
            'descripcion' => __('Crear pedido grupal de productos para el catering del evento', 'flavor-platform'),
            'icon'        => 'dashicons-store',
            'callback'    => [$this, 'callback_catering'],
        ];

        // Cursos → Banco de Tiempo: Pago con horas
        $this->integraciones['cursos_banco_tiempo'] = [
            'id'          => 'cursos_banco_tiempo',
            'origen'      => 'cursos',
            'destino'     => 'banco_tiempo',
            'label'       => __('Pago con Horas', 'flavor-platform'),
            'descripcion' => __('Permitir pagar inscripción al curso con horas del banco de tiempo', 'flavor-platform'),
            'icon'        => 'dashicons-clock',
            'callback'    => [$this, 'callback_pago_horas'],
        ];

        // Carpooling → Eventos: Transporte compartido
        $this->integraciones['carpooling_eventos'] = [
            'id'          => 'carpooling_eventos',
            'origen'      => 'carpooling',
            'destino'     => 'eventos',
            'label'       => __('Transporte Compartido', 'flavor-platform'),
            'descripcion' => __('Ofrecer/buscar transporte compartido para asistir al evento', 'flavor-platform'),
            'icon'        => 'dashicons-car',
            'callback'    => [$this, 'callback_carpooling_evento'],
        ];

        // Incidencias → Huertos: Reporte en parcela
        $this->integraciones['incidencias_huertos'] = [
            'id'          => 'incidencias_huertos',
            'origen'      => 'incidencias',
            'destino'     => 'huertos_urbanos',
            'label'       => __('Incidencia en Parcela', 'flavor-platform'),
            'descripcion' => __('Reportar un problema específico en una parcela del huerto', 'flavor-platform'),
            'icon'        => 'dashicons-warning',
            'callback'    => [$this, 'callback_incidencia_parcela'],
        ];

        // Talleres → Comunidades: Exclusividad
        $this->integraciones['talleres_comunidades'] = [
            'id'          => 'talleres_comunidades',
            'origen'      => 'talleres',
            'destino'     => 'comunidades',
            'label'       => __('Taller para Comunidad', 'flavor-platform'),
            'descripcion' => __('Restringir taller solo a miembros de una comunidad específica', 'flavor-platform'),
            'icon'        => 'dashicons-groups',
            'callback'    => [$this, 'callback_taller_comunidad'],
        ];

        // Recetas → Grupos Consumo: Ingredientes
        $this->integraciones['recetas_gc_ingredientes'] = [
            'id'          => 'recetas_gc_ingredientes',
            'origen'      => 'recetas',
            'destino'     => 'grupos_consumo',
            'label'       => __('Ingredientes Disponibles', 'flavor-platform'),
            'descripcion' => __('Vincular receta con productos disponibles en grupos de consumo', 'flavor-platform'),
            'icon'        => 'dashicons-carrot',
            'callback'    => [$this, 'callback_receta_ingredientes'],
        ];

        // Permitir extensión
        $this->integraciones = apply_filters('flavor_functional_integrations', $this->integraciones);
    }

    /**
     * Obtener integraciones para un módulo
     *
     * @param string $modulo_id
     * @return array
     */
    public function get_for_module($modulo_id) {
        $resultado = ['como_origen' => [], 'como_destino' => []];

        foreach ($this->integraciones as $integracion) {
            if ($integracion['origen'] === $modulo_id && flavor_is_module_active($integracion['destino'])) {
                $resultado['como_origen'][] = $integracion;
            }
            if ($integracion['destino'] === $modulo_id && flavor_is_module_active($integracion['origen'])) {
                $resultado['como_destino'][] = $integracion;
            }
        }

        return $resultado;
    }

    /**
     * Callback: Crear pedido de catering
     */
    public function callback_catering($datos, $user_id) {
        global $wpdb;

        $evento_id = absint($datos['evento_id'] ?? 0);
        $grupo_id = absint($datos['grupo_id'] ?? 0);
        $productos = isset($datos['productos']) ? array_map('absint', (array) $datos['productos']) : [];
        $fecha_entrega = sanitize_text_field($datos['fecha_entrega'] ?? '');
        $notas = sanitize_textarea_field($datos['notas'] ?? '');

        if (!$evento_id || !$grupo_id || empty($productos)) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        // Crear pedido en gc_pedidos
        $tabla_pedidos = $wpdb->prefix . 'flavor_gc_pedidos';
        $wpdb->insert($tabla_pedidos, [
            'grupo_id'       => $grupo_id,
            'user_id'        => $user_id,
            'tipo'           => 'catering',
            'evento_id'      => $evento_id,
            'fecha_entrega'  => $fecha_entrega,
            'notas'          => $notas,
            'estado'         => 'pendiente',
            'fecha_creacion' => current_time('mysql'),
        ]);

        $pedido_id = $wpdb->insert_id;

        // Agregar líneas de productos
        $tabla_lineas = $wpdb->prefix . 'flavor_gc_pedidos_lineas';
        foreach ($productos as $producto_id) {
            $wpdb->insert($tabla_lineas, [
                'pedido_id'   => $pedido_id,
                'producto_id' => $producto_id,
                'cantidad'    => 1,
            ]);
        }

        do_action('flavor_gc_pedido_catering_creado', $pedido_id, $evento_id, $user_id);

        return ['success' => true, 'pedido_id' => $pedido_id];
    }

    /**
     * Callback: Configurar pago con horas de banco de tiempo
     */
    public function callback_pago_horas($datos, $user_id) {
        $curso_id = absint($datos['curso_id'] ?? 0);
        $horas = absint($datos['horas_requeridas'] ?? 0);

        if (!$curso_id || !$horas) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        update_post_meta($curso_id, '_flavor_pago_banco_tiempo', [
            'habilitado'       => true,
            'horas_requeridas' => $horas,
            'configurado_por'  => $user_id,
            'fecha'            => current_time('mysql'),
        ]);

        return ['success' => true];
    }

    /**
     * Callback: Crear viaje de carpooling para evento
     */
    public function callback_carpooling_evento($datos, $user_id) {
        global $wpdb;

        $evento_id = absint($datos['evento_id'] ?? 0);
        $tipo = in_array($datos['tipo'] ?? '', ['ofrezco', 'busco']) ? $datos['tipo'] : 'ofrezco';
        $plazas = absint($datos['plazas'] ?? 1);
        $punto_encuentro = sanitize_text_field($datos['punto_encuentro'] ?? '');
        $hora_salida = sanitize_text_field($datos['hora_salida'] ?? '');

        if (!$evento_id || !$punto_encuentro) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        $evento = get_post($evento_id);
        if (!$evento) {
            return new \WP_Error('evento_invalido', __('Evento no válido', 'flavor-platform'));
        }

        $fecha_evento = get_post_meta($evento_id, '_flavor_evento_fecha', true);
        $ubicacion_evento = get_post_meta($evento_id, '_flavor_evento_ubicacion', true);

        $tabla = $wpdb->prefix . 'flavor_carpooling_viajes';
        $wpdb->insert($tabla, [
            'user_id'            => $user_id,
            'tipo'               => $tipo,
            'origen'             => $punto_encuentro,
            'destino'            => $ubicacion_evento ?: $evento->post_title,
            'fecha'              => $fecha_evento,
            'hora_salida'        => $hora_salida,
            'plazas_totales'     => $plazas,
            'plazas_disponibles' => $plazas,
            'evento_id'          => $evento_id,
            'estado'             => 'activo',
            'fecha_creacion'     => current_time('mysql'),
        ]);

        $viaje_id = $wpdb->insert_id;

        do_action('flavor_carpooling_viaje_evento_creado', $viaje_id, $evento_id, $user_id, $tipo);

        return ['success' => true, 'viaje_id' => $viaje_id];
    }

    /**
     * Callback: Vincular incidencia con parcela de huerto
     */
    public function callback_incidencia_parcela($datos, $user_id) {
        global $wpdb;

        $incidencia_id = absint($datos['incidencia_id'] ?? 0);
        $huerto_id = absint($datos['huerto_id'] ?? 0);
        $parcela_id = absint($datos['parcela_id'] ?? 0);

        if (!$incidencia_id || !$huerto_id || !$parcela_id) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        $tabla = $wpdb->prefix . 'flavor_incidencias';
        $wpdb->update($tabla, [
            'huerto_id'  => $huerto_id,
            'parcela_id' => $parcela_id,
        ], ['id' => $incidencia_id]);

        // Notificar al responsable de la parcela
        $tabla_parcelas = $wpdb->prefix . 'flavor_huertos_parcelas';
        $responsable = $wpdb->get_var($wpdb->prepare(
            "SELECT usuario_id FROM {$tabla_parcelas} WHERE id = %d",
            $parcela_id
        ));

        if ($responsable) {
            do_action('flavor_incidencia_parcela_reportada', $incidencia_id, $parcela_id, $responsable);
        }

        return ['success' => true];
    }

    /**
     * Callback: Vincular taller a comunidad
     */
    public function callback_taller_comunidad($datos, $user_id) {
        $taller_id = absint($datos['taller_id'] ?? 0);
        $comunidad_id = absint($datos['comunidad_id'] ?? 0);
        $descuento = min(100, absint($datos['descuento'] ?? 0));

        if (!$taller_id || !$comunidad_id) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        update_post_meta($taller_id, '_flavor_comunidad_exclusiva', $comunidad_id);
        update_post_meta($taller_id, '_flavor_comunidad_descuento', $descuento);

        return ['success' => true];
    }

    /**
     * Callback: Vincular receta con productos de GC
     */
    public function callback_receta_ingredientes($datos, $user_id) {
        $receta_id = absint($datos['receta_id'] ?? 0);
        $productos = isset($datos['productos']) ? array_map('absint', (array) $datos['productos']) : [];

        if (!$receta_id) {
            return new \WP_Error('datos_incompletos', __('Datos incompletos', 'flavor-platform'));
        }

        update_post_meta($receta_id, '_flavor_gc_productos', $productos);

        return ['success' => true];
    }

    /**
     * AJAX: Ejecutar integración
     */
    public function ajax_ejecutar_integracion() {
        check_ajax_referer('flavor_integrations', 'nonce');

        if (!is_user_logged_in()) {
            wp_send_json_error(['mensaje' => __('Debes iniciar sesión', 'flavor-platform')]);
        }

        $integracion_id = sanitize_key($_POST['integracion_id'] ?? '');
        $datos = isset($_POST['datos']) ? (array) $_POST['datos'] : [];

        if (!isset($this->integraciones[$integracion_id])) {
            wp_send_json_error(['mensaje' => __('Integración no válida', 'flavor-platform')]);
        }

        $integracion = $this->integraciones[$integracion_id];

        // Verificar módulos activos
        if (!flavor_is_module_active($integracion['origen']) ||
            !flavor_is_module_active($integracion['destino'])) {
            wp_send_json_error(['mensaje' => __('Módulos requeridos no activos', 'flavor-platform')]);
        }

        $resultado = call_user_func($integracion['callback'], $datos, get_current_user_id());

        if (is_wp_error($resultado)) {
            wp_send_json_error(['mensaje' => $resultado->get_error_message()]);
        }

        wp_send_json_success($resultado);
    }

    /**
     * AJAX: Obtener opciones dinámicas
     */
    public function ajax_get_options() {
        check_ajax_referer('flavor_integrations', 'nonce');

        $tipo = sanitize_key($_POST['tipo'] ?? '');
        $opciones = [];

        switch ($tipo) {
            case 'grupos_consumo':
                global $wpdb;
                $tabla = $wpdb->prefix . 'flavor_gc_grupos';
                $grupos = $wpdb->get_results("SELECT id, nombre FROM {$tabla} WHERE estado = 'activo'");
                foreach ($grupos as $g) {
                    $opciones[$g->id] = $g->nombre;
                }
                break;

            case 'eventos_proximos':
                $eventos = get_posts([
                    'post_type'      => 'flavor_evento',
                    'posts_per_page' => 30,
                    'post_status'    => 'publish',
                    'meta_key'       => '_flavor_evento_fecha',
                    'meta_value'     => date('Y-m-d'),
                    'meta_compare'   => '>=',
                    'orderby'        => 'meta_value',
                    'order'          => 'ASC',
                ]);
                foreach ($eventos as $e) {
                    $fecha = get_post_meta($e->ID, '_flavor_evento_fecha', true);
                    $opciones[$e->ID] = $e->post_title . ' (' . $fecha . ')';
                }
                break;

            case 'comunidades':
                global $wpdb;
                $tabla = $wpdb->prefix . 'flavor_comunidades';
                $items = $wpdb->get_results("SELECT id, nombre FROM {$tabla} WHERE estado = 'activa'");
                foreach ($items as $item) {
                    $opciones[$item->id] = $item->nombre;
                }
                break;

            case 'huertos':
                global $wpdb;
                $tabla = $wpdb->prefix . 'flavor_huertos';
                $items = $wpdb->get_results("SELECT id, nombre FROM {$tabla} WHERE estado = 'activo'");
                foreach ($items as $item) {
                    $opciones[$item->id] = $item->nombre;
                }
                break;

            case 'parcelas':
                $huerto_id = absint($_POST['huerto_id'] ?? 0);
                if ($huerto_id) {
                    global $wpdb;
                    $tabla = $wpdb->prefix . 'flavor_huertos_parcelas';
                    $items = $wpdb->get_results($wpdb->prepare(
                        "SELECT id, codigo FROM {$tabla} WHERE huerto_id = %d",
                        $huerto_id
                    ));
                    foreach ($items as $item) {
                        $opciones[$item->id] = $item->codigo;
                    }
                }
                break;

            case 'gc_productos':
                global $wpdb;
                $tabla = $wpdb->prefix . 'flavor_gc_productos';
                $items = $wpdb->get_results("SELECT id, nombre FROM {$tabla} WHERE activo = 1");
                foreach ($items as $item) {
                    $opciones[$item->id] = $item->nombre;
                }
                break;
        }

        wp_send_json_success($opciones);
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    Flavor_Functional_Integrations::get_instance();
}, 15);
