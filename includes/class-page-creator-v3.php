<?php
/**
 * Flavor Page Creator V3 - Modular
 *
 * Sistema orquestador que recopila definiciones de páginas desde cada módulo
 * y las crea automáticamente. Los módulos declaran sus propias páginas vía
 * el método get_pages_definition().
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) exit;

class Flavor_Page_Creator_V3 {

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
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
        // Hook para crear páginas cuando se activa un módulo
        add_action('flavor_module_activated', [$this, 'on_module_activated']);

        // Hook para eliminar páginas cuando se desactiva un módulo
        add_action('flavor_module_deactivated', [$this, 'on_module_deactivated']);
    }

    /**
     * Cuando se activa un módulo, crear sus páginas
     *
     * @param string $module_id ID del módulo activado
     */
    public function on_module_activated($module_id) {
        error_log("[Page Creator V3] Módulo activado: {$module_id}, creando páginas...");

        $module_loader = Flavor_Module_Loader::get_instance();
        $module_instance = $module_loader->get_module_instance($module_id);

        if (!$module_instance) {
            error_log("[Page Creator V3] No se pudo obtener instancia del módulo {$module_id}");
            return;
        }

        if (!method_exists($module_instance, 'get_pages_definition')) {
            error_log("[Page Creator V3] El módulo {$module_id} no implementa get_pages_definition()");
            return;
        }

        $pages = $module_instance->get_pages_definition();

        if (empty($pages)) {
            error_log("[Page Creator V3] El módulo {$module_id} no tiene páginas definidas");
            return;
        }

        error_log("[Page Creator V3] Creando " . count($pages) . " páginas para {$module_id}");
        $this->create_pages_for_module($module_id, $pages);
    }

    /**
     * Cuando se desactiva un módulo, eliminar sus páginas (opcional)
     *
     * @param string $module_id ID del módulo desactivado
     */
    public function on_module_deactivated($module_id) {
        // Por ahora, no eliminar páginas automáticamente
        // En el futuro podríamos agregar una opción de configuración
        error_log("[Page Creator V3] Módulo desactivado: {$module_id} (páginas no eliminadas)");
    }

    /**
     * Recopila todas las definiciones de páginas de todos los módulos activos
     *
     * @return array Array de páginas agrupadas por módulo
     */
    public function get_all_pages_definitions() {
        $all_pages = [];
        $module_loader = Flavor_Module_Loader::get_instance();
        $loaded_modules = $module_loader->get_loaded_modules();

        foreach ($loaded_modules as $module_id => $module_instance) {
            if (method_exists($module_instance, 'get_pages_definition')) {
                $pages = $module_instance->get_pages_definition();

                if (!empty($pages)) {
                    $all_pages[$module_id] = $pages;
                }
            }
        }

        return $all_pages;
    }

    /**
     * Crea o actualiza todas las páginas de todos los módulos activos
     */
    public function create_all_pages() {
        $all_pages = $this->get_all_pages_definitions();

        if (empty($all_pages)) {
            error_log("[Page Creator V3] No hay páginas definidas en ningún módulo");
            return;
        }

        error_log("[Page Creator V3] Creando páginas para " . count($all_pages) . " módulos");

        foreach ($all_pages as $module_id => $pages) {
            $this->create_pages_for_module($module_id, $pages);
        }

        error_log("[Page Creator V3] Páginas creadas correctamente");
    }

    /**
     * Crea páginas para un módulo específico
     *
     * @param string $module_id ID del módulo
     * @param array $pages Definiciones de páginas
     */
    private function create_pages_for_module($module_id, $pages) {
        foreach ($pages as $page_data) {
            $this->create_or_update_page($module_id, $page_data);
        }
    }

    /**
     * Crea o actualiza una página individual
     *
     * @param string $module_id ID del módulo propietario
     * @param array $page_data Datos de la página
     * @return int|WP_Error ID de la página creada o error
     */
    private function create_or_update_page($module_id, $page_data) {
        // Validar datos requeridos
        if (empty($page_data['title']) || empty($page_data['slug'])) {
            error_log("[Page Creator V3] Error: página sin título o slug en módulo {$module_id}");
            return new WP_Error('invalid_page_data', 'Título y slug son requeridos');
        }

        // Defaults
        $defaults = [
            'title' => '',
            'slug' => '',
            'content' => '',
            'parent' => 0,
            'template' => 'page-full-width.php',
            'status' => 'publish',
            'menu_order' => 0,
            'meta' => [],
        ];

        $page_data = wp_parse_args($page_data, $defaults);

        // Resolver ID del padre si es un slug
        $parent_id = 0;
        if (!empty($page_data['parent']) && !is_numeric($page_data['parent'])) {
            $parent_page = get_page_by_path($page_data['parent']);
            if ($parent_page) {
                $parent_id = $parent_page->ID;
            }
        } elseif (is_numeric($page_data['parent'])) {
            $parent_id = $page_data['parent'];
        }

        // Generar slug completo (con padre si aplica)
        $full_slug = $page_data['slug'];
        if ($parent_id > 0) {
            $parent_page = get_post($parent_id);
            if ($parent_page) {
                $full_slug = $parent_page->post_name . '/' . $page_data['slug'];
            }
        }

        // Verificar si la página ya existe
        $existing_page = get_page_by_path($full_slug);

        $page_args = [
            'post_title'   => $page_data['title'],
            'post_name'    => $page_data['slug'],
            'post_content' => $page_data['content'],
            'post_status'  => $page_data['status'],
            'post_type'    => 'page',
            'post_parent'  => $parent_id,
            'menu_order'   => $page_data['menu_order'],
        ];

        if ($existing_page) {
            // Actualizar página existente
            $page_args['ID'] = $existing_page->ID;
            $page_id = wp_update_post($page_args);

            if (is_wp_error($page_id)) {
                error_log("[Page Creator V3] Error actualizando página '{$page_data['title']}': " . $page_id->get_error_message());
                return $page_id;
            }

            error_log("[Page Creator V3] Página actualizada: {$page_data['title']} (ID: {$page_id})");
        } else {
            // Crear nueva página
            $page_id = wp_insert_post($page_args);

            if (is_wp_error($page_id)) {
                error_log("[Page Creator V3] Error creando página '{$page_data['title']}': " . $page_id->get_error_message());
                return $page_id;
            }

            error_log("[Page Creator V3] Página creada: {$page_data['title']} (ID: {$page_id})");
        }

        // Asignar template si es necesario
        if (!empty($page_data['template'])) {
            update_post_meta($page_id, '_wp_page_template', $page_data['template']);
        }

        // Guardar metadata adicional
        if (!empty($page_data['meta'])) {
            foreach ($page_data['meta'] as $meta_key => $meta_value) {
                update_post_meta($page_id, $meta_key, $meta_value);
            }
        }

        // Guardar módulo propietario en metadata
        update_post_meta($page_id, '_flavor_owner_module', $module_id);

        // Agregar a metadata del módulo
        update_post_meta($page_id, '_flavor_modules', [$module_id]);

        return $page_id;
    }

    /**
     * Obtiene las páginas creadas por un módulo específico
     *
     * @param string $module_id ID del módulo
     * @return array Array de IDs de páginas
     */
    public function get_module_pages($module_id) {
        $args = [
            'post_type' => 'page',
            'post_status' => 'any',
            'meta_query' => [
                [
                    'key' => '_flavor_owner_module',
                    'value' => $module_id,
                    'compare' => '='
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ];

        return get_posts($args);
    }

    /**
     * Elimina las páginas de un módulo
     *
     * @param string $module_id ID del módulo
     * @param bool $force_delete Si true, elimina permanentemente. Si false, mueve a papelera
     * @return int Número de páginas eliminadas
     */
    public function delete_module_pages($module_id, $force_delete = false) {
        $page_ids = $this->get_module_pages($module_id);
        $deleted_count = 0;

        foreach ($page_ids as $page_id) {
            $result = wp_delete_post($page_id, $force_delete);
            if ($result) {
                $deleted_count++;
            }
        }

        error_log("[Page Creator V3] Eliminadas {$deleted_count} páginas del módulo {$module_id}");
        return $deleted_count;
    }

    /**
     * Helper: Genera contenido de página con formato estandarizado
     *
     * @param array $config Configuración de la página
     * @return string Shortcode generado
     */
    public static function page_content($config) {
        $defaults = [
            'title' => '',
            'subtitle' => '',
            'module' => '',
            'current' => '',
            'background' => 'white',
            'breadcrumbs' => 'yes',
            'content_after' => '',
        ];

        $config = wp_parse_args($config, $defaults);

        $content = sprintf(
            '[flavor_page_header
    title="%s"
    subtitle="%s"
    breadcrumbs="%s"
    background="%s"%s%s]',
            esc_attr($config['title']),
            esc_attr($config['subtitle']),
            $config['breadcrumbs'],
            $config['background'],
            !empty($config['module']) ? "\n    module=\"{$config['module']}\"" : '',
            !empty($config['current']) ? "\n    current=\"{$config['current']}\"" : ''
        );

        if (!empty($config['content_after'])) {
            $content .= "\n\n" . $config['content_after'];
        }

        return $content;
    }

    /**
     * Obtiene estadísticas de páginas
     *
     * @return array Estadísticas
     */
    public function get_stats() {
        $all_pages = $this->get_all_pages_definitions();
        $total_modules = count($all_pages);
        $total_pages = 0;

        foreach ($all_pages as $pages) {
            $total_pages += count($pages);
        }

        $created_pages = get_posts([
            'post_type' => 'page',
            'meta_query' => [
                [
                    'key' => '_flavor_owner_module',
                    'compare' => 'EXISTS'
                ]
            ],
            'fields' => 'ids',
            'posts_per_page' => -1,
        ]);

        return [
            'modules_with_pages' => $total_modules,
            'total_pages_defined' => $total_pages,
            'total_pages_created' => count($created_pages),
        ];
    }
}
