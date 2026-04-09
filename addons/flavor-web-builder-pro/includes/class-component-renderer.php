<?php
/**
 * Renderizador de Componentes
 *
 * Renderiza componentes del Page Builder en el frontend
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Component_Renderer {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Registry de componentes
     */
    private $registry;

    /**
     * Obtener instancia singleton
     *
     * @return Flavor_Component_Renderer
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
    public function __construct() {
        $this->registry = Flavor_Component_Registry::get_instance();
    }

    /**
     * Renderiza un componente
     *
     * @param string $component_id ID del componente
     * @param array $data Datos del componente
     * @param array $settings Configuración adicional
     */
    public function render_component($component_id, $data = [], $settings = []) {
        $component = $this->registry->get_component($component_id);

        if (!$component) {
            if (current_user_can('edit_posts')) {
                echo '<div class="flavor-component-error">';
                echo '<p>' . esc_html__('Componente no encontrado:', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' ' . esc_html($component_id) . '</p>';
                echo '</div>';
            }
            return;
        }

        // Si viene de un alias, transformar datos legacy al formato unificado
        if (!empty($component['_alias_from'])) {
            $data = $this->transformar_datos_legacy($component, $data);
        }

        // Pre-procesar datos (repeater, data_source, queries)
        $data = $this->pre_process_data($component_id, $data, $component);

        // Extraer variables de los datos
        extract($data);

        // Aplicar settings CSS
        $wrapper_style = $this->get_wrapper_style($settings);
        $wrapper_class = $this->get_wrapper_class($settings);

        // Buscar template del componente (con fallback por categoría)
        $category = $component['category'] ?? '';
        $template_path = $this->find_template($component['template'], $category);

        if (!$template_path) {
            if (current_user_can('edit_posts')) {
                echo '<div class="flavor-component-error">';
                echo '<p>' . esc_html__('Template no encontrado:', FLAVOR_PLATFORM_TEXT_DOMAIN) . ' ' . esc_html($component['template']) . '</p>';
                echo '</div>';
            }
            return;
        }

        // Renderizar wrapper con settings
        if (!empty($wrapper_style) || !empty($wrapper_class)) {
            echo '<div class="flavor-component-wrapper ' . esc_attr($wrapper_class) . '" style="' . esc_attr($wrapper_style) . '">';
        }

        // Incluir template
        include $template_path;

        // Cerrar wrapper
        if (!empty($wrapper_style) || !empty($wrapper_class)) {
            echo '</div>';
        }
    }

    /**
     * Transforma datos de un componente legacy al formato del componente unificado
     *
     * Cuando un layout guardado usa un ID viejo (ej: 'carpooling_hero'),
     * el alias lo resuelve al componente unificado, pero los datos almacenados
     * pueden tener campos del componente viejo. Este método asegura que los
     * datos legacy se mapeen correctamente al componente unificado.
     *
     * @param array $component Definición del componente unificado (con _alias_from y _alias_preset)
     * @param array $data Datos almacenados del componente legacy
     * @return array Datos transformados para el componente unificado
     */
    private function transformar_datos_legacy($component, $data) {
        $nombre_preset = $component['_alias_preset'] ?? '';

        // Si el componente unificado tiene presets y hay datos del preset, aplicarlos como base
        if (!empty($nombre_preset) && !empty($component['presets'][$nombre_preset]['values'])) {
            $valores_preset = $component['presets'][$nombre_preset]['values'];
            // Los datos legacy tienen prioridad sobre los del preset
            $data = array_merge($valores_preset, $data);
        }

        // Asegurar que la variante esté definida si no viene en los datos
        if (!isset($data['variante']) && !empty($component['variants'])) {
            // Usar la variante del preset si existe, o la primera disponible
            if (!empty($component['presets'][$nombre_preset]['values']['variante'])) {
                $data['variante'] = $component['presets'][$nombre_preset]['values']['variante'];
            } else {
                $variantes_disponibles = array_keys($component['variants']);
                $data['variante'] = $variantes_disponibles[0] ?? 'centrado';
            }
        }

        return $data;
    }

    /**
     * Pre-procesa datos del componente: resuelve data_source y prepara items
     *
     * @param string $component_id ID del componente
     * @param array $data Datos del componente
     * @param array $component Definicion del componente
     * @return array Datos procesados
     */
    private function pre_process_data($component_id, $data, $component) {
        if (empty($component['fields'])) {
            return $data;
        }

        foreach ($component['fields'] as $field_name => $field_def) {
            if ($field_def['type'] === 'data_source') {
                $source_config = $data[$field_name] ?? ['source' => 'manual'];
                if (is_string($source_config)) {
                    $source_config = ['source' => $source_config];
                }

                $source = $source_config['source'] ?? 'manual';
                $items_field = $field_def['items_field'] ?? 'items';

                if ($source !== 'manual') {
                    $limite = intval($source_config['limite'] ?? 6);
                    $orden = $source_config['orden'] ?? 'date_desc';
                    $data[$items_field] = $this->query_items($source, $limite, $orden);
                }
                // Si es manual, los items ya estan en $data[$items_field]
            }
        }

        return $data;
    }

    /**
     * Ejecuta query de WordPress para obtener items dinamicos
     *
     * Soporta post types nativos de WP y fuentes custom via filtro.
     * Los modulos pueden usar el filtro 'flavor_query_custom_items' para
     * proveer datos de tablas custom (ej: carpooling, eventos).
     *
     * @param string $post_type Tipo de post o identificador de fuente custom
     * @param int $limite Numero maximo de resultados
     * @param string $orden Criterio de orden
     * @return array Items formateados
     */
    private function query_items($post_type, $limite = 6, $orden = 'date_desc') {
        // Permitir que modulos provean items custom para fuentes no-WP
        $items_custom = apply_filters('flavor_query_custom_items', null, $post_type, $limite, $orden);
        if (is_array($items_custom)) {
            return $items_custom;
        }

        $orderby = 'date';
        $order = 'DESC';

        switch ($orden) {
            case 'date_asc':
                $orderby = 'date';
                $order = 'ASC';
                break;
            case 'title_asc':
                $orderby = 'title';
                $order = 'ASC';
                break;
            case 'title_desc':
                $orderby = 'title';
                $order = 'DESC';
                break;
            case 'date_desc':
            default:
                $orderby = 'date';
                $order = 'DESC';
                break;
        }

        $args_consulta = [
            'post_type'      => sanitize_text_field($post_type),
            'posts_per_page' => min(absint($limite), 20),
            'orderby'        => $orderby,
            'order'          => $order,
            'post_status'    => 'publish',
        ];

        $posts = get_posts($args_consulta);
        $items_resultado = [];

        foreach ($posts as $post) {
            $imagen_url = '';
            $imagen_id = get_post_thumbnail_id($post->ID);
            if ($imagen_id) {
                $imagen_url = wp_get_attachment_image_url($imagen_id, 'medium_large');
            }

            $items_resultado[] = [
                'titulo'      => $post->post_title,
                'descripcion' => wp_trim_words($post->post_excerpt ?: $post->post_content, 25, '...'),
                'imagen'      => $imagen_url ?: '',
                'url'         => get_permalink($post->ID),
                'fecha'       => get_the_date('', $post),
                'autor'       => get_the_author_meta('display_name', $post->post_author),
                'post_id'     => $post->ID,
            ];
        }

        return $items_resultado;
    }

    /**
     * Busca el archivo de template
     *
     * @param string $template Nombre del template (ej: 'carpooling/hero')
     * @param string $category Categoría del componente para fallback
     * @return string|false Path del template o false
     */
    private function find_template($template, $category = '') {
        // Permitir tema override
        $theme_template = get_stylesheet_directory() . '/flavor-templates/components/' . $template . '.php';
        if (file_exists($theme_template)) {
            return $theme_template;
        }

        // Template del addon (flavor-web-builder-pro)
        $addon_template = FLAVOR_WEB_BUILDER_PATH . 'templates/components/' . $template . '.php';
        if (file_exists($addon_template)) {
            return $addon_template;
        }

        // Template del plugin principal (flavor-chat-ia)
        if (defined('FLAVOR_CHAT_IA_PATH')) {
            $main_plugin_template = FLAVOR_CHAT_IA_PATH . 'templates/components/' . $template . '.php';
            if (file_exists($main_plugin_template)) {
                return $main_plugin_template;
            }
        }

        // Buscar fallback basado en categoría
        $fallback_template = $this->get_fallback_template($template, $category);
        if ($fallback_template) {
            return $fallback_template;
        }

        return false;
    }

    /**
     * Obtener template de fallback según tipo de componente
     *
     * @param string $template Nombre del template original
     * @param string $category Categoría del componente
     * @return string|false
     */
    private function get_fallback_template($template, $category) {
        // Mapear categorías a templates genéricos
        $fallback_map = [
            'hero' => '_generic-hero',
            'listings' => '_generic-grid',
            'cta' => '_generic-cta',
            'content' => '_generic-content',
            'forms' => '_generic-content',
        ];

        // Detectar tipo por nombre del template si no hay categoría
        if (empty($category)) {
            $template_lower = strtolower($template);
            if (strpos($template_lower, 'hero') !== false) {
                $category = 'hero';
            } elseif (strpos($template_lower, 'grid') !== false || strpos($template_lower, 'listado') !== false || strpos($template_lower, 'categorias') !== false) {
                $category = 'listings';
            } elseif (strpos($template_lower, 'cta') !== false) {
                $category = 'cta';
            } elseif (strpos($template_lower, 'mapa') !== false || strpos($template_lower, 'map') !== false) {
                $category = 'mapa';
            } else {
                $category = 'content';
            }
        }

        // Fallback especial para mapas
        if ($category === 'mapa' || strpos($template, 'mapa') !== false) {
            $mapa_fallback = FLAVOR_WEB_BUILDER_PATH . 'templates/components/landings/_generic-mapa.php';
            if (file_exists($mapa_fallback)) {
                return $mapa_fallback;
            }
        }

        // Buscar fallback genérico
        $fallback_name = $fallback_map[$category] ?? '_generic-content';
        $fallback_path = FLAVOR_WEB_BUILDER_PATH . 'templates/components/landings/' . $fallback_name . '.php';

        if (file_exists($fallback_path)) {
            return $fallback_path;
        }

        return false;
    }

    /**
     * Genera estilos CSS del wrapper según settings
     *
     * @param array $settings
     * @return string
     */
    private function get_wrapper_style($settings) {
        $styles = [];

        // Alineación
        if (!empty($settings['align'])) {
            $align_map = [
                'left' => 'text-align: left;',
                'center' => 'text-align: center;',
                'right' => 'text-align: right;',
            ];
            if (isset($align_map[$settings['align']])) {
                $styles[] = $align_map[$settings['align']];
            }
        }

        // Spacing (margin y padding) - solo si valor > 0
        if (!empty($settings['spacing'])) {
            if (isset($settings['spacing']['margin'])) {
                $margin = $settings['spacing']['margin'];
                if (!empty($margin['top'])) $styles[] = 'margin-top: ' . intval($margin['top']) . 'px;';
                if (!empty($margin['bottom'])) $styles[] = 'margin-bottom: ' . intval($margin['bottom']) . 'px;';
            }

            if (isset($settings['spacing']['padding'])) {
                $padding = $settings['spacing']['padding'];
                if (!empty($padding['top'])) $styles[] = 'padding-top: ' . intval($padding['top']) . 'px;';
                if (!empty($padding['bottom'])) $styles[] = 'padding-bottom: ' . intval($padding['bottom']) . 'px;';
            }
        }

        // Background
        if (!empty($settings['background'])) {
            if (!empty($settings['background']['color'])) {
                $styles[] = 'background-color: ' . sanitize_hex_color($settings['background']['color']) . ';';
            }

            if (!empty($settings['background']['image'])) {
                $image_url = wp_get_attachment_image_url($settings['background']['image'], 'full');
                if ($image_url) {
                    $styles[] = 'background-image: url(' . esc_url($image_url) . ');';
                    $styles[] = 'background-size: cover;';
                    $styles[] = 'background-position: center;';
                }
            }
        }

        return implode(' ', $styles);
    }

    /**
     * Genera clases CSS del wrapper
     *
     * @param array $settings
     * @return string
     */
    private function get_wrapper_class($settings) {
        $classes = [];

        // Añadir clases personalizadas
        if (!empty($settings['custom_class'])) {
            $classes[] = sanitize_html_class($settings['custom_class']);
        }

        // Clases de visibilidad por dispositivo
        if (isset($settings['visibility_desktop']) && !$settings['visibility_desktop']) {
            $classes[] = 'flavor-hidden-desktop';
        }
        if (isset($settings['visibility_tablet']) && !$settings['visibility_tablet']) {
            $classes[] = 'flavor-hidden-tablet';
        }
        if (isset($settings['visibility_mobile']) && !$settings['visibility_mobile']) {
            $classes[] = 'flavor-hidden-mobile';
        }

        return implode(' ', $classes);
    }

    /**
     * Renderiza un layout completo
     *
     * @param array $layout Array de componentes
     */
    public function render_layout($layout) {
        if (empty($layout) || !is_array($layout)) {
            return;
        }

        foreach ($layout as $component_data) {
            if (empty($component_data['component_id'])) {
                continue;
            }

            $this->render_component(
                $component_data['component_id'],
                $component_data['data'] ?? [],
                $component_data['settings'] ?? []
            );
        }
    }
}
