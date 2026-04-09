<?php
/**
 * Componente Constructor de Landing Pages
 *
 * Gestiona la construccion de landing pages con secciones dinamicas
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
 * Clase Flavor_Landing_Builder
 *
 * Construye landing pages a partir de definiciones de secciones
 */
class Flavor_Landing_Builder extends Flavor_Template_Component_Base {

    /**
     * Tipos de secciones soportadas
     *
     * @var array
     */
    private $tipos_secciones = [
        'hero',
        'features',
        'grid',
        'cta',
        'testimonials',
        'pricing',
        'faq',
        'contact',
        'text',
        'gallery',
        'stats',
        'team',
        'timeline',
        'cards',
    ];

    /**
     * Constructor
     */
    public function __construct() {
        $this->componente_id = 'landing';
        $this->componente_nombre = __('Constructor de Landing', FLAVOR_PLATFORM_TEXT_DOMAIN);
    }

    /**
     * Instala/construye la landing page
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con 'landing' y 'secciones'
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function instalar($plantilla_id, $definicion, $opciones = []) {
        $this->limpiar_mensajes();

        $config_landing = $definicion['landing'] ?? [];
        $secciones = $definicion['secciones'] ?? $config_landing['secciones'] ?? [];

        if (empty($secciones)) {
            return $this->respuesta_exito(
                __('No hay secciones definidas para la landing.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['contenido_generado' => false]
            );
        }

        // Buscar la pagina landing (principal de la plantilla)
        $id_pagina_landing = $this->obtener_pagina_landing($plantilla_id, $config_landing);

        if (!$id_pagina_landing) {
            return $this->respuesta_error(
                __('No se encontro la pagina landing para actualizar.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['pagina_buscada' => $config_landing['slug'] ?? 'landing']
            );
        }

        // Guardar contenido original como snapshot
        $contenido_original = get_post_field('post_content', $id_pagina_landing);
        $this->crear_snapshot($plantilla_id, 'contenido_landing', $contenido_original);

        // Generar contenido de las secciones
        $contenido_generado = $this->generar_contenido_landing($secciones, $plantilla_id);

        // Actualizar la pagina
        $resultado_actualizacion = wp_update_post([
            'ID'           => $id_pagina_landing,
            'post_content' => $contenido_generado,
        ]);

        if (is_wp_error($resultado_actualizacion)) {
            $this->registrar_error(
                'actualizar_landing_error',
                $resultado_actualizacion->get_error_message()
            );

            return $this->respuesta_error(
                __('Error al actualizar la landing page.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['error' => $resultado_actualizacion->get_error_message()]
            );
        }

        // Guardar metadata de secciones
        update_post_meta($id_pagina_landing, '_flavor_landing_secciones', $secciones);
        update_post_meta($id_pagina_landing, '_flavor_landing_plantilla', $plantilla_id);

        $this->guardar_meta_instalacion($plantilla_id, 'pagina_landing_id', $id_pagina_landing);
        $this->guardar_meta_instalacion($plantilla_id, 'secciones_instaladas', array_column($secciones, 'tipo'));

        return $this->respuesta_exito(
            sprintf(
                __('Landing page actualizada con %d secciones.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($secciones)
            ),
            [
                'pagina_id'   => $id_pagina_landing,
                'pagina_url'  => get_permalink($id_pagina_landing),
                'secciones'   => count($secciones),
            ]
        );
    }

    /**
     * Desinstala/restaura la landing page
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion de la plantilla
     * @param array $opciones Opciones adicionales
     * @return array Resultado de la operacion
     */
    public function desinstalar($plantilla_id, $definicion = [], $opciones = []) {
        $this->limpiar_mensajes();

        $id_pagina_landing = $this->obtener_meta_instalacion($plantilla_id, 'pagina_landing_id');

        if (!$id_pagina_landing) {
            return $this->respuesta_exito(
                __('No hay landing page registrada para esta plantilla.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                ['restaurada' => false]
            );
        }

        // Intentar restaurar contenido original
        $contenido_original = $this->obtener_snapshot($plantilla_id, 'contenido_landing');

        if ($contenido_original !== null) {
            wp_update_post([
                'ID'           => $id_pagina_landing,
                'post_content' => $contenido_original,
            ]);

            // Limpiar meta de secciones
            delete_post_meta($id_pagina_landing, '_flavor_landing_secciones');
            delete_post_meta($id_pagina_landing, '_flavor_landing_plantilla');
        } else {
            $this->registrar_advertencia(
                'sin_snapshot',
                __('No se encontro el contenido original para restaurar.', FLAVOR_PLATFORM_TEXT_DOMAIN)
            );
        }

        // Limpiar metadatos
        $this->eliminar_meta_instalacion($plantilla_id);

        return $this->respuesta_exito(
            __('Landing page restaurada a su estado original.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            [
                'pagina_id'  => $id_pagina_landing,
                'restaurada' => $contenido_original !== null,
            ]
        );
    }

    /**
     * Verifica el estado de la landing page
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $definicion Definicion con secciones esperadas
     * @return array Estado de la landing
     */
    public function verificar_estado($plantilla_id, $definicion = []) {
        $config_landing = $definicion['landing'] ?? [];
        $secciones_esperadas = $definicion['secciones'] ?? $config_landing['secciones'] ?? [];

        $id_pagina_landing = $this->obtener_meta_instalacion($plantilla_id, 'pagina_landing_id');

        if (!$id_pagina_landing) {
            // Intentar buscar por config
            $id_pagina_landing = $this->obtener_pagina_landing($plantilla_id, $config_landing);
        }

        if (!$id_pagina_landing) {
            return [
                'estado'   => 'no_instalado',
                'detalles' => [],
                'mensaje'  => __('No se encontro la pagina landing.', FLAVOR_PLATFORM_TEXT_DOMAIN),
            ];
        }

        // Verificar secciones
        $secciones_actuales = get_post_meta($id_pagina_landing, '_flavor_landing_secciones', true) ?: [];
        $contenido_actual = get_post_field('post_content', $id_pagina_landing);

        $secciones_encontradas = [];
        $secciones_faltantes = [];

        foreach ($secciones_esperadas as $seccion) {
            $tipo = $seccion['tipo'] ?? '';
            $identificador = $seccion['id'] ?? $tipo;

            // Verificar si la seccion esta en el contenido
            if ($this->seccion_existe_en_contenido($identificador, $contenido_actual)) {
                $secciones_encontradas[] = $identificador;
            } else {
                $secciones_faltantes[] = $identificador;
            }
        }

        // Determinar estado
        if (empty($secciones_esperadas)) {
            $estado = 'no_aplica';
        } elseif (empty($secciones_faltantes)) {
            $estado = 'completo';
        } elseif (!empty($secciones_encontradas)) {
            $estado = 'parcial';
        } else {
            $estado = 'no_instalado';
        }

        return [
            'estado'   => $estado,
            'detalles' => [
                'pagina_id'    => $id_pagina_landing,
                'pagina_url'   => get_permalink($id_pagina_landing),
                'secciones'    => [
                    'encontradas' => $secciones_encontradas,
                    'faltantes'   => $secciones_faltantes,
                ],
            ],
            'mensaje'  => sprintf(
                __('%d de %d secciones configuradas.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                count($secciones_encontradas),
                count($secciones_esperadas)
            ),
        ];
    }

    /**
     * Obtiene la pagina landing de la plantilla
     *
     * @param string $plantilla_id ID de la plantilla
     * @param array $config_landing Configuracion de la landing
     * @return int|null ID de la pagina o null
     */
    private function obtener_pagina_landing($plantilla_id, $config_landing = []) {
        // Buscar por slug especificado
        if (!empty($config_landing['slug'])) {
            $pagina = get_page_by_path($config_landing['slug']);
            if ($pagina) {
                return $pagina->ID;
            }
        }

        // Buscar por ID especificado
        if (!empty($config_landing['page_id'])) {
            $pagina = get_post($config_landing['page_id']);
            if ($pagina && $pagina->post_type === 'page') {
                return $pagina->ID;
            }
        }

        // Buscar pagina marcada como landing de esta plantilla
        $paginas = get_posts([
            'post_type'      => 'page',
            'posts_per_page' => 1,
            'meta_query'     => [
                [
                    'key'   => '_flavor_template_id',
                    'value' => $plantilla_id,
                ],
                [
                    'key'   => '_flavor_template_page',
                    'value' => true,
                ],
            ],
            'orderby'        => 'post_parent',
            'order'          => 'ASC', // Primero las padres (landing principal)
        ]);

        if (!empty($paginas)) {
            return $paginas[0]->ID;
        }

        return null;
    }

    /**
     * Genera el contenido HTML de la landing a partir de secciones
     *
     * @param array $secciones Array de secciones
     * @param string $plantilla_id ID de la plantilla
     * @return string Contenido HTML/shortcodes
     */
    private function generar_contenido_landing($secciones, $plantilla_id) {
        $contenido_partes = [];

        foreach ($secciones as $indice => $seccion) {
            $contenido_seccion = $this->renderizar_seccion($seccion, $plantilla_id, $indice);

            if (!empty($contenido_seccion)) {
                $contenido_partes[] = $contenido_seccion;
            }
        }

        return implode("\n\n", $contenido_partes);
    }

    /**
     * Renderiza una seccion individual
     *
     * @param array $seccion Datos de la seccion
     * @param string $plantilla_id ID de la plantilla
     * @param int $indice Indice de la seccion
     * @return string HTML/shortcode de la seccion
     */
    private function renderizar_seccion($seccion, $plantilla_id, $indice = 0) {
        $tipo = $seccion['tipo'] ?? 'text';
        $id_seccion = $seccion['id'] ?? $tipo . '_' . $indice;

        // Verificar si hay un shortcode especifico
        if (!empty($seccion['shortcode'])) {
            return $this->renderizar_shortcode($seccion['shortcode'], $seccion);
        }

        // Renderizar segun tipo
        $metodo_render = 'renderizar_seccion_' . $tipo;

        if (method_exists($this, $metodo_render)) {
            return $this->$metodo_render($seccion, $id_seccion);
        }

        // Fallback: seccion generica
        return $this->renderizar_seccion_generica($seccion, $id_seccion);
    }

    /**
     * Renderiza un shortcode con atributos
     *
     * @param string $shortcode Nombre del shortcode
     * @param array $seccion Datos de la seccion (atributos)
     * @return string
     */
    private function renderizar_shortcode($shortcode, $seccion) {
        $atributos = $seccion['atributos'] ?? [];
        $atributos_str = '';

        foreach ($atributos as $clave => $valor) {
            if (is_array($valor)) {
                $valor = implode(',', $valor);
            }
            $atributos_str .= sprintf(' %s="%s"', $clave, esc_attr($valor));
        }

        return sprintf('[%s%s]', $shortcode, $atributos_str);
    }

    /**
     * Renderiza seccion hero
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_hero($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? '';
        $subtitulo = $seccion['subtitulo'] ?? '';
        $cta_texto = $seccion['cta_texto'] ?? '';
        $cta_url = $seccion['cta_url'] ?? '#';
        $imagen = $seccion['imagen'] ?? '';

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-hero %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-hero ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading {"level":1,"className":"flavor-hero__title"} -->' . "\n" .
                '<h1 class="wp-block-heading flavor-hero__title">%s</h1>' . "\n" .
                '<!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        if ($subtitulo) {
            $html .= sprintf(
                '<!-- wp:paragraph {"className":"flavor-hero__subtitle"} -->' . "\n" .
                '<p class="flavor-hero__subtitle">%s</p>' . "\n" .
                '<!-- /wp:paragraph -->' . "\n",
                esc_html($subtitulo)
            );
        }

        if ($cta_texto) {
            $html .= sprintf(
                '<!-- wp:buttons -->' . "\n" .
                '<div class="wp-block-buttons">' .
                '<!-- wp:button {"className":"flavor-hero__cta"} -->' . "\n" .
                '<div class="wp-block-button flavor-hero__cta"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>' . "\n" .
                '<!-- /wp:button -->' .
                '</div>' . "\n" .
                '<!-- /wp:buttons -->' . "\n",
                esc_url($cta_url),
                esc_html($cta_texto)
            );
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Renderiza seccion de features/caracteristicas
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_features($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? '';
        $features = $seccion['items'] ?? $seccion['features'] ?? [];
        $columnas = $seccion['columnas'] ?? 3;

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-features %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-features ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading {"textAlign":"center","className":"flavor-features__title"} -->' . "\n" .
                '<h2 class="wp-block-heading has-text-align-center flavor-features__title">%s</h2>' . "\n" .
                '<!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        if (!empty($features)) {
            $html .= sprintf(
                '<!-- wp:columns {"className":"flavor-features__grid columns-%d"} -->' . "\n" .
                '<div class="wp-block-columns flavor-features__grid columns-%d">',
                $columnas,
                $columnas
            );

            foreach ($features as $feature) {
                $html .= '<!-- wp:column -->' . "\n" . '<div class="wp-block-column">';

                if (!empty($feature['icono'])) {
                    $html .= sprintf(
                        '<div class="flavor-feature__icon">%s</div>',
                        $feature['icono']
                    );
                }

                if (!empty($feature['titulo'])) {
                    $html .= sprintf(
                        '<!-- wp:heading {"level":3} --><h3 class="wp-block-heading">%s</h3><!-- /wp:heading -->',
                        esc_html($feature['titulo'])
                    );
                }

                if (!empty($feature['descripcion'])) {
                    $html .= sprintf(
                        '<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->',
                        esc_html($feature['descripcion'])
                    );
                }

                $html .= '</div>' . "\n" . '<!-- /wp:column -->';
            }

            $html .= '</div>' . "\n" . '<!-- /wp:columns -->';
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Renderiza seccion de grid/listado
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_grid($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? '';
        $modulo = $seccion['modulo'] ?? '';
        $accion = $seccion['accion'] ?? '';
        $columnas = $seccion['columnas'] ?? 3;
        $limite = $seccion['limite'] ?? 6;

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-grid %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-grid ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading {"textAlign":"center"} -->' . "\n" .
                '<h2 class="wp-block-heading has-text-align-center">%s</h2>' . "\n" .
                '<!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        // Shortcode del modulo
        if ($modulo && $accion) {
            $html .= sprintf(
                '[flavor_module_listing module="%s" action="%s" columnas="%d" limite="%d"]',
                esc_attr($modulo),
                esc_attr($accion),
                $columnas,
                $limite
            );
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Renderiza seccion CTA (call to action)
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_cta($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? '';
        $descripcion = $seccion['descripcion'] ?? '';
        $boton_texto = $seccion['boton_texto'] ?? $seccion['cta_texto'] ?? '';
        $boton_url = $seccion['boton_url'] ?? $seccion['cta_url'] ?? '#';

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-cta %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-cta ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading {"textAlign":"center"} -->' . "\n" .
                '<h2 class="wp-block-heading has-text-align-center">%s</h2>' . "\n" .
                '<!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        if ($descripcion) {
            $html .= sprintf(
                '<!-- wp:paragraph {"align":"center"} -->' . "\n" .
                '<p class="has-text-align-center">%s</p>' . "\n" .
                '<!-- /wp:paragraph -->' . "\n",
                esc_html($descripcion)
            );
        }

        if ($boton_texto) {
            $html .= sprintf(
                '<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->' . "\n" .
                '<div class="wp-block-buttons">' .
                '<!-- wp:button -->' . "\n" .
                '<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></div>' . "\n" .
                '<!-- /wp:button -->' .
                '</div>' . "\n" .
                '<!-- /wp:buttons -->' . "\n",
                esc_url($boton_url),
                esc_html($boton_texto)
            );
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Renderiza seccion de FAQ
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_faq($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? __('Preguntas Frecuentes', FLAVOR_PLATFORM_TEXT_DOMAIN);
        $items = $seccion['items'] ?? [];

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-faq %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-faq ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading {"textAlign":"center"} -->' . "\n" .
                '<h2 class="wp-block-heading has-text-align-center">%s</h2>' . "\n" .
                '<!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        foreach ($items as $item) {
            $html .= '<!-- wp:details -->' . "\n";
            $html .= '<details class="wp-block-details">';
            $html .= sprintf('<summary>%s</summary>', esc_html($item['pregunta'] ?? ''));
            $html .= sprintf('<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->', esc_html($item['respuesta'] ?? ''));
            $html .= '</details>' . "\n";
            $html .= '<!-- /wp:details -->' . "\n";
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Renderiza seccion generica/texto
     *
     * @param array $seccion Datos
     * @param string $id_seccion ID de la seccion
     * @return string
     */
    private function renderizar_seccion_generica($seccion, $id_seccion) {
        $titulo = $seccion['titulo'] ?? '';
        $contenido = $seccion['contenido'] ?? $seccion['texto'] ?? '';

        $html = sprintf(
            '<!-- wp:group {"className":"flavor-section %s","layout":{"type":"constrained"}} -->' . "\n",
            esc_attr($id_seccion)
        );
        $html .= '<div class="wp-block-group flavor-section ' . esc_attr($id_seccion) . '">';

        if ($titulo) {
            $html .= sprintf(
                '<!-- wp:heading --><h2 class="wp-block-heading">%s</h2><!-- /wp:heading -->' . "\n",
                esc_html($titulo)
            );
        }

        if ($contenido) {
            $html .= sprintf(
                '<!-- wp:paragraph --><p>%s</p><!-- /wp:paragraph -->' . "\n",
                wp_kses_post($contenido)
            );
        }

        $html .= '</div>' . "\n" . '<!-- /wp:group -->';

        return $html;
    }

    /**
     * Verifica si una seccion existe en el contenido
     *
     * @param string $identificador ID o tipo de seccion
     * @param string $contenido Contenido de la pagina
     * @return bool
     */
    private function seccion_existe_en_contenido($identificador, $contenido) {
        // Buscar por clase CSS
        if (strpos($contenido, 'class="' . $identificador) !== false ||
            strpos($contenido, "class='" . $identificador) !== false ||
            strpos($contenido, ' ' . $identificador . '"') !== false) {
            return true;
        }

        // Buscar por ID
        if (strpos($contenido, 'id="' . $identificador) !== false) {
            return true;
        }

        return false;
    }
}
