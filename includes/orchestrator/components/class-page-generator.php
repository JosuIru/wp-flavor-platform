<?php
/**
 * Componente Generador de Paginas
 *
 * Gestiona la creacion de paginas WordPress para las plantillas
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
 * Clase Flavor_Page_Generator
 *
 * Crea las paginas necesarias para una plantilla
 */
class Flavor_Page_Generator extends Flavor_Template_Component_Base {

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'paginas';
        $this->componente_nombre = __('Generador de Paginas', 'flavor-chat-ia');
    }

    /**
     * Instala las paginas de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'paginas' array
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        // Verificar si la creación de páginas está deshabilitada
        if (get_option('flavor_pages_creation_disabled')) {
            return $this->respuesta_exito(
                __('Creación de páginas deshabilitada.', 'flavor-chat-ia'),
                ['paginas_creadas' => []]
            );
        }

        $this->limpiar_mensajes();

        $paginas_definidas = $definicion['paginas'] ?? [];

        if (empty($paginas_definidas)) {
            return $this->respuesta_exito(
                __('No hay paginas definidas para esta plantilla.', 'flavor-chat-ia'),
                ['paginas_creadas' => []]
            );
        }

        // Separar paginas padre e hijas para crear en orden correcto
        $paginas_padre = [];
        $paginas_hijas = [];

        foreach ($paginas_definidas as $pagina) {
            if (empty($pagina['parent']) || $pagina['parent'] === 0) {
                $paginas_padre[] = $pagina;
            } else {
                $paginas_hijas[] = $pagina;
            }
        }

        $paginas_creadas = [];
        $paginas_existentes = [];
        $paginas_fallidas = [];
        $mapa_ids_padre = []; // Cache de slug => ID para padres

        // Paso 1: Crear paginas padre
        foreach ($paginas_padre as $pagina_data) {
            $resultado = $this->crear_pagina($pagina_data, 0, $plantilla_id);

            if ($resultado['success']) {
                if ($resultado['ya_existia']) {
                    $paginas_existentes[] = $resultado['pagina'];
                } else {
                    $paginas_creadas[] = $resultado['pagina'];
                }
                $mapa_ids_padre[$pagina_data['slug']] = $resultado['pagina']['id'];
            } else {
                $paginas_fallidas[] = [
                    'slug'  => $pagina_data['slug'],
                    'error' => $resultado['error'],
                ];
            }
        }

        // Paso 2: Crear paginas hijas
        foreach ($paginas_hijas as $pagina_data) {
            $id_padre = 0;

            // Buscar ID del padre
            if (!empty($pagina_data['parent'])) {
                if (isset($mapa_ids_padre[$pagina_data['parent']])) {
                    $id_padre = $mapa_ids_padre[$pagina_data['parent']];
                } else {
                    // Buscar en BD por slug
                    $pagina_padre = get_page_by_path($pagina_data['parent']);
                    if ($pagina_padre) {
                        $id_padre = $pagina_padre->ID;
                        $mapa_ids_padre[$pagina_data['parent']] = $id_padre;
                    }
                }
            }

            $resultado = $this->crear_pagina($pagina_data, $id_padre, $plantilla_id);

            if ($resultado['success']) {
                if ($resultado['ya_existia']) {
                    $paginas_existentes[] = $resultado['pagina'];
                } else {
                    $paginas_creadas[] = $resultado['pagina'];
                }
            } else {
                $paginas_fallidas[] = [
                    'slug'  => $pagina_data['slug'],
                    'error' => $resultado['error'],
                ];
            }
        }

        // Guardar registro de paginas creadas
        $ids_paginas_creadas = array_column($paginas_creadas, 'id');
        $this->guardar_meta_instalacion($plantilla_id, 'paginas_creadas', $ids_paginas_creadas);

        // Flush rewrite rules para que las URLs funcionen
        flush_rewrite_rules();

        $mensaje = sprintf(
            __('Se crearon %d paginas correctamente.', 'flavor-chat-ia'),
            count($paginas_creadas)
        );

        if (!empty($paginas_existentes)) {
            $mensaje .= ' ' . sprintf(
                __('%d paginas ya existian.', 'flavor-chat-ia'),
                count($paginas_existentes)
            );
        }

        return $this->respuesta_exito($mensaje, [
            'paginas_creadas'    => $paginas_creadas,
            'paginas_existentes' => $paginas_existentes,
            'paginas_fallidas'   => $paginas_fallidas,
        ]);
    }

    /**
     * Desinstala las paginas de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $ids_paginas = $this->obtener_meta_instalacion($plantilla_id, 'paginas_creadas', []);

        // Tambien buscar paginas por meta
        $paginas_por_meta = get_posts([
            'post_type'      => 'page',
            'posts_per_page' => -1,
            'meta_query'     => [
                [
                    'key'   => '_flavor_template_id',
                    'value' => $plantilla_id,
                ],
            ],
            'fields'         => 'ids',
        ]);

        $ids_paginas = array_unique(array_merge($ids_paginas, $paginas_por_meta));

        if (empty($ids_paginas)) {
            return $this->respuesta_exito(
                __('No hay paginas registradas para esta plantilla.', 'flavor-chat-ia'),
                ['paginas_eliminadas' => []]
            );
        }

        $paginas_eliminadas = [];
        $paginas_fallidas = [];

        // Ordenar para eliminar hijas primero (por parent desc)
        $paginas_ordenadas = [];
        foreach ($ids_paginas as $id_pagina) {
            $post = get_post($id_pagina);
            if ($post) {
                $paginas_ordenadas[] = [
                    'id'     => $id_pagina,
                    'parent' => $post->post_parent,
                ];
            }
        }

        usort($paginas_ordenadas, function($a, $b) {
            return $b['parent'] - $a['parent']; // Hijas primero
        });

        foreach ($paginas_ordenadas as $pagina_info) {
            $id_pagina = $pagina_info['id'];

            // Verificar que la pagina tiene el meta de plantilla
            $template_id_meta = get_post_meta($id_pagina, '_flavor_template_id', true);

            if ($template_id_meta !== $plantilla_id) {
                $this->registrar_advertencia(
                    'pagina_sin_meta',
                    sprintf(__('La pagina %d no pertenece a esta plantilla.', 'flavor-chat-ia'), $id_pagina)
                );
                continue;
            }

            $titulo_pagina = get_the_title($id_pagina);

            // Mover a papelera (no eliminar permanentemente)
            $resultado = wp_trash_post($id_pagina);

            if ($resultado) {
                $paginas_eliminadas[] = [
                    'id'     => $id_pagina,
                    'titulo' => $titulo_pagina,
                ];
            } else {
                $paginas_fallidas[] = [
                    'id'     => $id_pagina,
                    'titulo' => $titulo_pagina,
                ];
            }
        }

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);

        // Flush rewrite rules
        flush_rewrite_rules();

        return $this->respuesta_exito(
            sprintf(
                __('Se movieron %d paginas a la papelera.', 'flavor-chat-ia'),
                count($paginas_eliminadas)
            ),
            [
                'paginas_eliminadas' => $paginas_eliminadas,
                'paginas_fallidas'   => $paginas_fallidas,
            ]
        );
    }

    /**
     * Verifica el estado de las paginas de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con paginas esperadas
     * @return array Estado de las paginas
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $paginas_definidas = $definicion['paginas'] ?? [];

        if (empty($paginas_definidas)) {
            return [
                'estado'   => 'no_aplica',
                'detalles' => [],
                'mensaje'  => __('No hay paginas definidas para esta plantilla.', 'flavor-chat-ia'),
            ];
        }

        $paginas_existentes = [];
        $paginas_faltantes = [];

        foreach ($paginas_definidas as $pagina_data) {
            $pagina_encontrada = $this->buscar_pagina_existente($pagina_data);

            if ($pagina_encontrada) {
                $paginas_existentes[] = [
                    'id'     => $pagina_encontrada->ID,
                    'titulo' => $pagina_encontrada->post_title,
                    'slug'   => $pagina_encontrada->post_name,
                    'url'    => get_permalink($pagina_encontrada->ID),
                ];
            } else {
                $paginas_faltantes[] = [
                    'titulo' => $pagina_data['title'],
                    'slug'   => $pagina_data['slug'],
                ];
            }
        }

        // Determinar estado
        $total_esperadas = count($paginas_definidas);
        $total_existentes = count($paginas_existentes);

        if ($total_existentes === $total_esperadas) {
            $estado = 'completo';
        } elseif ($total_existentes > 0) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => [
                'esperadas'  => $total_esperadas,
                'existentes' => $paginas_existentes,
                'faltantes'  => $paginas_faltantes,
            ],
            'mensaje'  => sprintf(
                __('%d de %d paginas existen.', 'flavor-chat-ia'),
                $total_existentes,
                $total_esperadas
            ),
        ];
    }

    /**
     * Crea una pagina individual
     *
     * @param array $pagina_data Datos de la pagina
     * @param int $id_padre ID del padre (0 para raiz)
     * @param string $plantilla_id ID de la plantilla
     * @return array Resultado
     */
    private function crear_pagina($pagina_data, $id_padre, $plantilla_id) {
        // Verificar si ya existe
        $pagina_existente = $this->buscar_pagina_existente($pagina_data, $id_padre);

        if ($pagina_existente) {
            return [
                'success'    => true,
                'ya_existia' => true,
                'pagina'     => [
                    'id'     => $pagina_existente->ID,
                    'titulo' => $pagina_existente->post_title,
                    'slug'   => $pagina_existente->post_name,
                    'url'    => get_permalink($pagina_existente->ID),
                ],
            ];
        }

        // Preparar datos de la pagina (soporta 'titulo'/'title' y 'contenido'/'content')
        $titulo_pagina = $pagina_data['titulo'] ?? $pagina_data['title'] ?? 'Sin título';
        $contenido_pagina = $pagina_data['contenido'] ?? $pagina_data['content'] ?? '';

        $datos_post = [
            'post_title'    => sanitize_text_field($titulo_pagina),
            'post_name'     => sanitize_title($pagina_data['slug']),
            'post_content'  => wp_kses_post($contenido_pagina),
            'post_status'   => 'publish',
            'post_type'     => 'page',
            'post_parent'   => $id_padre,
            'post_author'   => get_current_user_id() ?: 1,
            'comment_status' => 'closed',
            'ping_status'   => 'closed',
        ];

        // Crear la pagina
        $id_pagina = wp_insert_post($datos_post);

        if (is_wp_error($id_pagina)) {
            $this->registrar_error(
                'crear_pagina_error',
                sprintf(__('Error al crear pagina "%s": %s', 'flavor-chat-ia'), $pagina_data['title'], $id_pagina->get_error_message())
            );

            return [
                'success' => false,
                'error'   => $id_pagina->get_error_message(),
            ];
        }

        // Marcar con meta para identificar como pagina de plantilla
        update_post_meta($id_pagina, '_flavor_template_page', true);
        update_post_meta($id_pagina, '_flavor_template_id', $plantilla_id);

        // Meta adicional si existe
        if (!empty($pagina_data['meta'])) {
            foreach ($pagina_data['meta'] as $meta_key => $meta_value) {
                update_post_meta($id_pagina, $meta_key, $meta_value);
            }
        }

        // Template de pagina si se especifica
        if (!empty($pagina_data['template'])) {
            update_post_meta($id_pagina, '_wp_page_template', $pagina_data['template']);
        }

        return [
            'success'    => true,
            'ya_existia' => false,
            'pagina'     => [
                'id'     => $id_pagina,
                'titulo' => $pagina_data['title'],
                'slug'   => $pagina_data['slug'],
                'url'    => get_permalink($id_pagina),
            ],
        ];
    }

    /**
     * Busca una pagina existente por slug y padre
     *
     * @param array $pagina_data Datos de la pagina a buscar
     * @param int $id_padre ID del padre (opcional)
     * @return WP_Post|null
     */
    private function buscar_pagina_existente($pagina_data, $id_padre = null) {
        $slug = sanitize_title($pagina_data['slug']);

        // Si no hay padre definido, buscar pagina padre
        if ($id_padre === null && empty($pagina_data['parent'])) {
            return get_page_by_path($slug);
        }

        // Buscar con padre
        if ($id_padre === null && !empty($pagina_data['parent'])) {
            $padre = get_page_by_path($pagina_data['parent']);
            $id_padre = $padre ? $padre->ID : 0;
        }

        $args = [
            'name'           => $slug,
            'post_type'      => 'page',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'post_parent'    => $id_padre ?: 0,
        ];

        $paginas = get_posts($args);

        return !empty($paginas) ? $paginas[0] : null;
    }
}
