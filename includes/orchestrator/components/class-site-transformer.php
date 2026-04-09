<?php
/**
 * Componente Transformador de Sitio
 *
 * Configura el sitio WordPress para que funcione como la plantilla:
 * - Establece la página de inicio
 * - Crea el menú de navegación
 * - Configura opciones del frontend
 *
 * @package FlavorChatIA
 * @subpackage Orchestrator/Components
 */

if (!defined('ABSPATH')) {
    exit;
}

// Cargar interface si no esta cargada
if (!interface_exists('Flavor_Template_Component_Interface')) {
    require_once dirname(__DIR__) . '/interface-template-component.php';
}

/**
 * Clase Flavor_Site_Transformer
 *
 * Transforma el sitio WordPress para que funcione como la plantilla activa
 */
class Flavor_Site_Transformer extends Flavor_Template_Component_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'site_transformer';
        $this->componente_nombre = __('Transformador de Sitio', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Instala/configura el sitio según la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion completa de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        $resultados = [
            'home_configurado' => false,
            'menu_creado' => false,
            'opciones_configuradas' => false,
        ];

        // 1. Configurar página de inicio
        $resultado_home = $this->configurar_pagina_inicio($plantilla_id, $definicion);
        $resultados['home_configurado'] = $resultado_home['success'];
        if (!$resultado_home['success'] && !empty($resultado_home['error'])) {
            $this->registrar_advertencia('home_config', $resultado_home['error']);
        }

        // 2. Crear menú de navegación
        $resultado_menu = $this->crear_menu_navegacion($plantilla_id, $definicion);
        $resultados['menu_creado'] = $resultado_menu['success'];
        if (!$resultado_menu['success'] && !empty($resultado_menu['error'])) {
            $this->registrar_advertencia('menu_config', $resultado_menu['error']);
        }

        // 3. Configurar opciones del frontend
        $resultado_opciones = $this->configurar_opciones_frontend($plantilla_id, $definicion);
        $resultados['opciones_configuradas'] = $resultado_opciones['success'];

        // Guardar estado previo para rollback
        $this->guardar_meta_instalacion($plantilla_id, 'site_transformer', [
            'fecha' => current_time('mysql'),
            'home_previo' => get_option('page_on_front'),
            'menu_previo' => get_nav_menu_locations(),
        ]);

        $mensaje = __('Sitio transformado correctamente.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        if ($resultados['home_configurado']) {
            $mensaje .= ' ' . __('Página de inicio configurada.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }
        if ($resultados['menu_creado']) {
            $mensaje .= ' ' . __('Menú creado.', FLAVOR_PLATFORM_TEXT_DOMAIN);
        }

        return $this->respuesta_exito($mensaje, $resultados);
    }

    /**
     * Desinstala/revierte la configuración del sitio
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $estado_previo = $this->obtener_meta_instalacion($plantilla_id, 'site_transformer', []);

        // Restaurar página de inicio
        if (!empty($estado_previo['home_previo'])) {
            update_option('page_on_front', $estado_previo['home_previo']);
        } else {
            // Volver a mostrar posts en portada
            update_option('show_on_front', 'posts');
            update_option('page_on_front', 0);
        }

        // Eliminar menú creado por la plantilla
        $this->eliminar_menu_plantilla($plantilla_id);

        // Limpiar meta
        $this->eliminar_meta_instalacion($plantilla_id);

        return $this->respuesta_exito(
            __('Configuración del sitio revertida.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            []
        );
    }

    /**
     * Verifica el estado de la configuración
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @return array Estado de la configuración
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $paginas = $definicion['paginas'] ?? [];
        $menu_definido = $definicion['menu'] ?? [];

        // Verificar página de inicio
        $home_correcto = false;
        $pagina_home = null;

        foreach ($paginas as $pagina) {
            if (!empty($pagina['es_home'])) {
                $pagina_home = $pagina;
                break;
            }
        }

        if ($pagina_home) {
            $pagina_existente = get_page_by_path($pagina_home['slug']);
            if ($pagina_existente) {
                $home_actual = (int) get_option('page_on_front');
                $home_correcto = ($home_actual === $pagina_existente->ID);
            }
        }

        // Verificar menú
        $menu_existe = false;
        if (!empty($menu_definido['nombre'])) {
            $menu = wp_get_nav_menu_object($menu_definido['nombre']);
            $menu_existe = ($menu !== false);
        }

        $estado = 'no_instalado';
        if ($home_correcto && $menu_existe) {
            $estado = 'completo';
        } elseif ($home_correcto || $menu_existe) {
            $estado = 'parcial';
        }

        return [
            'estado' => $estado,
            'detalles' => [
                'home_configurado' => $home_correcto,
                'menu_creado' => $menu_existe,
            ],
            'mensaje' => sprintf(
                __('Home: %s, Menú: %s', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $home_correcto ? __('OK', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('No', FLAVOR_PLATFORM_TEXT_DOMAIN),
                $menu_existe ? __('OK', FLAVOR_PLATFORM_TEXT_DOMAIN) : __('No', FLAVOR_PLATFORM_TEXT_DOMAIN)
            ),
        ];
    }

    /**
     * Configura la página de inicio según la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definición de la plantilla
     * @return array Resultado
     */
    private function configurar_pagina_inicio($plantilla_id, $definicion) {
        $paginas = $definicion['paginas'] ?? [];

        // Buscar la página marcada como home
        $pagina_home = null;
        foreach ($paginas as $pagina) {
            if (!empty($pagina['es_home'])) {
                $pagina_home = $pagina;
                break;
            }
        }

        if (!$pagina_home) {
            return [
                'success' => false,
                'error' => __('No hay página de inicio definida en la plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Buscar la página en WordPress
        $slug = sanitize_title($pagina_home['slug']);
        $pagina_wp = get_page_by_path($slug);

        if (!$pagina_wp) {
            // Intentar crear la página si no existe
            $id_pagina = wp_insert_post([
                'post_title' => $pagina_home['titulo'] ?? $pagina_home['title'] ?? 'Inicio',
                'post_name' => $slug,
                'post_content' => $pagina_home['contenido'] ?? $pagina_home['content'] ?? '',
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_author' => get_current_user_id() ?: 1,
            ]);

            if (is_wp_error($id_pagina)) {
                return [
                    'success' => false,
                    'error' => $id_pagina->get_error_message(),
                ];
            }

            // Marcar como página de plantilla
            update_post_meta($id_pagina, '_flavor_template_page', true);
            update_post_meta($id_pagina, '_flavor_template_id', $plantilla_id);
            update_post_meta($id_pagina, '_flavor_is_home', true);

            // Aplicar template si existe
            if (!empty($pagina_home['template'])) {
                update_post_meta($id_pagina, '_wp_page_template', $pagina_home['template']);
            }

            $pagina_wp = get_post($id_pagina);
        }

        // Configurar WordPress para usar página estática como inicio
        update_option('show_on_front', 'page');
        update_option('page_on_front', $pagina_wp->ID);

        // Opcional: configurar página de blog si existe
        $pagina_blog = get_page_by_path('blog');
        if ($pagina_blog) {
            update_option('page_for_posts', $pagina_blog->ID);
        }

        return [
            'success' => true,
            'pagina_id' => $pagina_wp->ID,
            'pagina_url' => get_permalink($pagina_wp->ID),
        ];
    }

    /**
     * Crea el menú de navegación según la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definición de la plantilla
     * @return array Resultado
     */
    private function crear_menu_navegacion($plantilla_id, $definicion) {
        $menu_definido = $definicion['menu'] ?? [];

        if (empty($menu_definido) || empty($menu_definido['items'])) {
            return [
                'success' => false,
                'error' => __('No hay menú definido en la plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        $nombre_menu = $menu_definido['nombre'] ?? 'Menu Principal ' . ucfirst($plantilla_id);
        $ubicacion = $menu_definido['ubicacion'] ?? 'primary';

        // Verificar si el menú ya existe
        $menu_existente = wp_get_nav_menu_object($nombre_menu);

        if ($menu_existente) {
            // Eliminar items existentes para recrear
            $items_existentes = wp_get_nav_menu_items($menu_existente->term_id);
            foreach ($items_existentes as $item) {
                wp_delete_post($item->ID, true);
            }
            $menu_id = $menu_existente->term_id;
        } else {
            // Crear nuevo menú
            $menu_id = wp_create_nav_menu($nombre_menu);

            if (is_wp_error($menu_id)) {
                return [
                    'success' => false,
                    'error' => $menu_id->get_error_message(),
                ];
            }
        }

        // Agregar items al menú
        $items = $menu_definido['items'] ?? [];
        $orden = 0;

        foreach ($items as $item) {
            $orden++;

            $item_data = [
                'menu-item-title' => $item['titulo'] ?? $item['title'] ?? 'Sin título',
                'menu-item-url' => home_url($item['url'] ?? '/'),
                'menu-item-status' => 'publish',
                'menu-item-type' => 'custom',
                'menu-item-position' => $orden,
            ];

            // Si es una página interna, intentar vincular directamente
            if (!empty($item['url']) && strpos($item['url'], 'http') !== 0) {
                $slug = trim($item['url'], '/');
                $pagina = get_page_by_path($slug);

                if ($pagina) {
                    $item_data['menu-item-object'] = 'page';
                    $item_data['menu-item-object-id'] = $pagina->ID;
                    $item_data['menu-item-type'] = 'post_type';
                    unset($item_data['menu-item-url']);
                }
            }

            // Agregar clases CSS si hay icono
            if (!empty($item['icono'])) {
                $item_data['menu-item-classes'] = 'menu-icon-' . sanitize_html_class($item['icono']);
            }

            wp_update_nav_menu_item($menu_id, 0, $item_data);
        }

        // Asignar menú a la ubicación
        $ubicaciones = get_nav_menu_locations();
        $ubicaciones[$ubicacion] = $menu_id;
        set_theme_mod('nav_menu_locations', $ubicaciones);

        // Guardar referencia para poder eliminar después
        update_term_meta($menu_id, '_flavor_template_id', $plantilla_id);

        return [
            'success' => true,
            'menu_id' => $menu_id,
            'items_creados' => count($items),
        ];
    }

    /**
     * Configura opciones del frontend según la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definición de la plantilla
     * @return array Resultado
     */
    private function configurar_opciones_frontend($plantilla_id, $definicion) {
        // Configurar color principal si está definido
        $color_principal = $definicion['color'] ?? '#3b82f6';

        $opciones_frontend = [
            'flavor_color_principal' => $color_principal,
            'flavor_plantilla_activa' => $plantilla_id,
            'flavor_plantilla_nombre' => $definicion['nombre'] ?? $plantilla_id,
            'flavor_plantilla_icono' => $definicion['icono'] ?? 'dashicons-admin-generic',
        ];

        foreach ($opciones_frontend as $clave => $valor) {
            update_option($clave, $valor);
        }

        // Registrar landings activas
        if (!empty($definicion['landing']['activa'])) {
            $landings_activas = get_option('flavor_landings_activas', []);
            $landings_activas[$plantilla_id] = [
                'nombre' => $definicion['nombre'] ?? $plantilla_id,
                'secciones' => count($definicion['landing']['secciones'] ?? []),
                'activa' => true,
            ];
            update_option('flavor_landings_activas', $landings_activas);
        }

        return [
            'success' => true,
            'opciones_configuradas' => array_keys($opciones_frontend),
        ];
    }

    /**
     * Elimina el menú creado por una plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     */
    private function eliminar_menu_plantilla($plantilla_id) {
        // Buscar menús con el meta de la plantilla
        $menus = wp_get_nav_menus();

        foreach ($menus as $menu) {
            $template_id = get_term_meta($menu->term_id, '_flavor_template_id', true);
            if ($template_id === $plantilla_id) {
                wp_delete_nav_menu($menu->term_id);
            }
        }
    }
}
