<?php
/**
 * Flavor Page Creator V2
 *
 * Versión mejorada que usa los nuevos componentes estandarizados
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) exit;

class Flavor_Page_Creator_V2 {

    /**
     * Helper: Genera contenido de página con el nuevo formato
     */
    private static function page_content($config) {
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
     * Definición de páginas V2 con nuevos componentes
     */
    public static function get_pages_to_create() {
        return [
            // MI PORTAL - Dashboard principal
            [
                'title' => 'Mi Portal',
                'slug' => 'mi-portal',
                'content' => '[flavor_mi_portal mostrar_breadcrumbs="no"]',
                'parent' => 0,
            ],

            // ========== TALLERES ==========
            [
                'title' => 'Talleres',
                'slug' => 'talleres',
                'content' => self::page_content([
                    'title' => 'Talleres de la Comunidad',
                    'subtitle' => 'Aprende nuevas habilidades y comparte tus conocimientos',
                    'background' => 'gradient',
                    'module' => 'talleres',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="talleres" action="talleres_disponibles" columnas="3" limite="12"]',
                ]),
                'parent' => 0,
            ],
            [
                'title' => 'Proponer Taller',
                'slug' => 'crear',
                'content' => self::page_content([
                    'title' => 'Proponer un Taller',
                    'subtitle' => 'Comparte tu conocimiento con la comunidad',
                    'module' => 'talleres',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="talleres" action="crear_taller"]',
                ]),
                'parent' => 'talleres',
            ],
            [
                'title' => 'Inscribirse en Taller',
                'slug' => 'inscribirse',
                'content' => self::page_content([
                    'title' => 'Inscribirse en el Taller',
                    'subtitle' => 'Reserva tu plaza en el taller',
                    'module' => 'talleres',
                    'current' => 'inscribirse',
                    'content_after' => '[flavor_module_form module="talleres" action="inscribirse"]',
                ]),
                'parent' => 'talleres',
            ],
            [
                'title' => 'Mis Talleres',
                'slug' => 'mis-talleres',
                'content' => self::page_content([
                    'title' => 'Mis Talleres',
                    'subtitle' => 'Gestiona los talleres en los que participas',
                    'module' => 'talleres',
                    'current' => 'mis-talleres',
                    'content_after' => '[flavor_module_dashboard module="talleres"]',
                ]),
                'parent' => 'talleres',
            ],

            // ========== EVENTOS ==========
            [
                'title' => 'Eventos',
                'slug' => 'eventos',
                'content' => self::page_content([
                    'title' => 'Eventos de la Comunidad',
                    'subtitle' => 'Descubre y participa en los próximos eventos',
                    'background' => 'gradient',
                    'module' => 'eventos',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="eventos" action="eventos_proximos" columnas="3" limite="12"]',
                ]),
                'parent' => 0,
            ],
            [
                'title' => 'Crear Evento',
                'slug' => 'crear',
                'content' => self::page_content([
                    'title' => 'Crear un Evento',
                    'subtitle' => 'Organiza un evento para la comunidad',
                    'module' => 'eventos',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="eventos" action="crear_evento"]',
                ]),
                'parent' => 'eventos',
            ],
            [
                'title' => 'Inscribirse en Evento',
                'slug' => 'inscribirse',
                'content' => self::page_content([
                    'title' => 'Inscribirse en el Evento',
                    'subtitle' => 'Confirma tu asistencia',
                    'module' => 'eventos',
                    'current' => 'inscribirse',
                    'content_after' => '[flavor_module_form module="eventos" action="inscribirse_evento"]',
                ]),
                'parent' => 'eventos',
            ],
            [
                'title' => 'Mis Eventos',
                'slug' => 'mis-eventos',
                'content' => self::page_content([
                    'title' => 'Mis Eventos',
                    'subtitle' => 'Gestiona tus eventos y confirmaciones',
                    'module' => 'eventos',
                    'current' => 'mis-eventos',
                    'content_after' => '[flavor_module_dashboard module="eventos"]',
                ]),
                'parent' => 'eventos',
            ],

            // ========== INCIDENCIAS ==========
            [
                'title' => 'Incidencias',
                'slug' => 'incidencias',
                'content' => self::page_content([
                    'title' => 'Incidencias de la Comunidad',
                    'subtitle' => 'Reporta y consulta incidencias',
                    'background' => 'gradient',
                    'module' => 'incidencias',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="incidencias" action="listar_incidencias" columnas="2"]',
                ]),
                'parent' => 0,
            ],
            [
                'title' => 'Reportar Incidencia',
                'slug' => 'crear',
                'content' => self::page_content([
                    'title' => 'Reportar una Incidencia',
                    'subtitle' => 'Informa sobre un problema en la comunidad',
                    'module' => 'incidencias',
                    'current' => 'crear',
                    'content_after' => '[flavor_module_form module="incidencias" action="crear_incidencia"]',
                ]),
                'parent' => 'incidencias',
            ],
            [
                'title' => 'Mis Incidencias',
                'slug' => 'mis-incidencias',
                'content' => self::page_content([
                    'title' => 'Mis Incidencias',
                    'subtitle' => 'Consulta el estado de tus reportes',
                    'module' => 'incidencias',
                    'current' => 'mis-incidencias',
                    'content_after' => '[flavor_module_dashboard module="incidencias"]',
                ]),
                'parent' => 'incidencias',
            ],

            // ========== ESPACIOS COMUNES ==========
            [
                'title' => 'Espacios Comunes',
                'slug' => 'espacios-comunes',
                'content' => self::page_content([
                    'title' => 'Espacios Comunes',
                    'subtitle' => 'Reserva instalaciones de la comunidad',
                    'background' => 'gradient',
                    'module' => 'espacios-comunes',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="espacios_comunes" action="listar_espacios" columnas="3"]',
                ]),
                'parent' => 0,
            ],
            [
                'title' => 'Reservar Espacio',
                'slug' => 'reservar',
                'content' => self::page_content([
                    'title' => 'Reservar un Espacio',
                    'subtitle' => 'Solicita el uso de una instalación',
                    'module' => 'espacios-comunes',
                    'current' => 'reservar',
                    'content_after' => '[flavor_module_form module="espacios_comunes" action="reservar_espacio"]',
                ]),
                'parent' => 'espacios-comunes',
            ],
            [
                'title' => 'Mis Reservas',
                'slug' => 'mis-reservas',
                'content' => self::page_content([
                    'title' => 'Mis Reservas',
                    'subtitle' => 'Gestiona tus reservas de espacios',
                    'module' => 'espacios-comunes',
                    'current' => 'mis-reservas',
                    'content_after' => '[flavor_module_dashboard module="espacios_comunes"]',
                ]),
                'parent' => 'espacios-comunes',
            ],

            // ========== GRUPOS DE CONSUMO ==========
            [
                'title' => 'Grupos de Consumo',
                'slug' => 'grupos-consumo',
                'content' => self::page_content([
                    'title' => 'Grupos de Consumo',
                    'subtitle' => 'Compra productos locales de forma colaborativa',
                    'background' => 'gradient',
                    'module' => 'grupos-consumo',
                    'current' => 'listado',
                    'content_after' => '[flavor_module_listing module="grupos_consumo" action="listar_grupos" columnas="3"]',
                ]),
                'parent' => 0,
            ],
            [
                'title' => 'Mi Grupo',
                'slug' => 'mi-grupo',
                'content' => self::page_content([
                    'title' => 'Mi Grupo de Consumo',
                    'subtitle' => 'Gestiona tu participación en el grupo',
                    'module' => 'grupos-consumo',
                    'current' => 'mi-grupo',
                    'content_after' => '[flavor_module_dashboard module="grupos_consumo"]',
                ]),
                'parent' => 'grupos-consumo',
            ],
            [
                'title' => 'Hacer Pedido',
                'slug' => 'pedidos',
                'content' => self::page_content([
                    'title' => 'Hacer Pedido',
                    'subtitle' => 'Realiza tu pedido semanal',
                    'module' => 'grupos-consumo',
                    'current' => 'pedidos',
                    'content_after' => '[flavor_module_form module="grupos_consumo" action="crear_pedido"]',
                ]),
                'parent' => 'grupos-consumo',
            ],

            // Añadir más módulos según necesidad...
        ];
    }

    /**
     * Crea o actualiza todas las páginas
     */
    public static function create_or_update_pages() {
        $pages = self::get_pages_to_create();
        $created = [];
        $updated = [];

        foreach ($pages as $page_data) {
            $parent_id = 0;

            // Resolver padre
            if (!empty($page_data['parent']) && $page_data['parent'] !== 0) {
                $parent_page = get_page_by_path($page_data['parent']);
                if ($parent_page) {
                    $parent_id = $parent_page->ID;
                }
            }

            // Buscar si existe
            $existing_page = get_page_by_path($page_data['slug']);

            if ($existing_page) {
                // Actualizar contenido
                wp_update_post([
                    'ID' => $existing_page->ID,
                    'post_content' => $page_data['content'],
                ]);

                // Actualizar metas
                update_post_meta($existing_page->ID, '_flavor_auto_page', 1);
                update_post_meta($existing_page->ID, '_flavor_full_width', 1);
                update_post_meta($existing_page->ID, '_flavor_auto_page_modules', self::extract_modules_from_page($page_data));

                // Asignar template full-width
                self::assign_full_width_template($existing_page->ID);

                $updated[] = $page_data['title'];
            } else {
                // Crear nueva
                $page_id = wp_insert_post([
                    'post_title' => $page_data['title'],
                    'post_name' => $page_data['slug'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_parent' => $parent_id,
                    'post_author' => get_current_user_id(),
                ]);

                if (!is_wp_error($page_id)) {
                    update_post_meta($page_id, '_flavor_auto_page', 1);
                    update_post_meta($page_id, '_flavor_full_width', 1);
                    update_post_meta($page_id, '_flavor_auto_page_modules', self::extract_modules_from_page($page_data));

                    self::assign_full_width_template($page_id);

                    $created[] = $page_data['title'];
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
        ];
    }

    /**
     * Extrae módulos del contenido de la página
     */
    private static function extract_modules_from_page($page_data) {
        $modules = [];
        $content = $page_data['content'];

        // Buscar module="xxx" en el contenido
        if (preg_match_all('/module=["\']([^"\']+)["\']/', $content, $matches)) {
            $modules = array_unique($matches[1]);
        }

        return !empty($modules) ? implode(',', $modules) : '';
    }

    /**
     * Asigna template full-width
     */
    private static function assign_full_width_template($page_id) {
        $templates = ['template-full-width.php', 'full-width.php', 'page-templates/full-width.php'];
        foreach ($templates as $template) {
            $template_file = get_template_directory() . '/' . $template;
            if (file_exists($template_file)) {
                update_post_meta($page_id, '_wp_page_template', $template);
                return true;
            }
        }
        return false;
    }
}
