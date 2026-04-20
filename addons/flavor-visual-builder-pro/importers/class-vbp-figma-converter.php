<?php
/**
 * Visual Builder Pro - Figma Converter
 *
 * Convierte nodos de Figma a elementos VBP.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Conversor de nodos Figma a elementos VBP
 */
class Flavor_VBP_Figma_Converter {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Figma_Converter|null
     */
    private static $instancia = null;

    /**
     * Mapeo de tipos de nodo Figma a tipos VBP
     *
     * @var array
     */
    private $type_map = array(
        'FRAME'      => 'container',
        'GROUP'      => 'columns',
        'TEXT'       => 'text',
        'RECTANGLE'  => 'spacer',
        'ELLIPSE'    => 'spacer',
        'LINE'       => 'divider',
        'VECTOR'     => 'image',
        'COMPONENT'  => 'container',
        'INSTANCE'   => 'container',
        'IMAGE'      => 'image',
    );

    /**
     * Imágenes descargadas
     *
     * @var array
     */
    private $downloaded_images = array();

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Figma_Converter
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Convierte un nodo de Figma a elemento VBP
     *
     * @param array $node Nodo de Figma.
     * @param array $images_map Mapa de imágenes node_id => url.
     * @return array Elemento VBP.
     */
    public function convert_node( $node, $images_map = array() ) {
        $type = $node['type'] ?? 'UNKNOWN';
        $vbp_type = $this->determine_vbp_type( $node );

        $element = array(
            'id'   => $this->generate_id(),
            'type' => $vbp_type,
            'data' => $this->extract_data( $node, $vbp_type, $images_map ),
        );

        // Procesar hijos si es un contenedor
        if ( $this->has_children( $node ) && $this->is_container_type( $vbp_type ) ) {
            $element['children'] = array();
            foreach ( $node['children'] as $child ) {
                $element['children'][] = $this->convert_node( $child, $images_map );
            }
        }

        return $element;
    }

    /**
     * Convierte un frame de Figma a estructura de página VBP
     *
     * @param array $frame Frame de Figma.
     * @param array $images_map Mapa de imágenes.
     * @return array Estructura de página VBP.
     */
    public function convert_frame_to_page( $frame, $images_map = array() ) {
        $elements = array();

        // Analizar el frame para detectar secciones
        $sections = $this->detect_sections( $frame );

        foreach ( $sections as $section ) {
            $element = $this->convert_section( $section, $images_map );
            if ( $element ) {
                $elements[] = $element;
            }
        }

        return array(
            'elements' => $elements,
            'settings' => $this->extract_page_settings( $frame ),
        );
    }

    /**
     * Determina el tipo VBP basado en el nodo de Figma
     *
     * @param array $node Nodo de Figma.
     * @return string Tipo VBP.
     */
    private function determine_vbp_type( $node ) {
        $type = $node['type'] ?? 'UNKNOWN';
        $name = strtolower( $node['name'] ?? '' );

        // Detección por nombre (convención de diseñadores)
        $name_patterns = array(
            'hero'         => array( 'hero', 'banner', 'header' ),
            'features'     => array( 'features', 'benefits', 'caracteristicas' ),
            'cta'          => array( 'cta', 'call to action', 'llamada' ),
            'testimonials' => array( 'testimonial', 'review', 'testimonio' ),
            'pricing'      => array( 'pricing', 'price', 'precios' ),
            'faq'          => array( 'faq', 'preguntas' ),
            'team'         => array( 'team', 'equipo' ),
            'gallery'      => array( 'gallery', 'galeria', 'portfolio' ),
            'contact'      => array( 'contact', 'contacto', 'form' ),
            'stats'        => array( 'stats', 'numbers', 'estadisticas', 'cifras' ),
            'footer'       => array( 'footer', 'pie' ),
        );

        foreach ( $name_patterns as $vbp_type => $patterns ) {
            foreach ( $patterns as $pattern ) {
                if ( strpos( $name, $pattern ) !== false ) {
                    return $vbp_type;
                }
            }
        }

        // Detección por tipo de nodo Figma
        if ( isset( $this->type_map[ $type ] ) ) {
            return $this->type_map[ $type ];
        }

        // Detección por estructura
        if ( 'TEXT' === $type ) {
            $font_size = $node['style']['fontSize'] ?? 16;
            if ( $font_size >= 32 ) {
                return 'heading';
            }
            return 'text';
        }

        // Detección por fills (imagen de fondo)
        if ( isset( $node['fills'] ) ) {
            foreach ( $node['fills'] as $fill ) {
                if ( 'IMAGE' === ( $fill['type'] ?? '' ) ) {
                    return 'image';
                }
            }
        }

        return 'container';
    }

    /**
     * Extrae datos del nodo según el tipo VBP
     *
     * @param array  $node Nodo de Figma.
     * @param string $vbp_type Tipo VBP.
     * @param array  $images_map Mapa de imágenes.
     * @return array
     */
    private function extract_data( $node, $vbp_type, $images_map = array() ) {
        $data = array(
            'figma_id'   => $node['id'] ?? '',
            'figma_name' => $node['name'] ?? '',
        );

        switch ( $vbp_type ) {
            case 'text':
            case 'heading':
                $data['contenido'] = $node['characters'] ?? '';
                $data['estilos'] = $this->extract_text_styles( $node );
                break;

            case 'image':
                $node_id = $node['id'];
                $data['imagen'] = $images_map[ $node_id ] ?? '';
                $data['alt'] = $node['name'] ?? '';
                break;

            case 'hero':
                $data = array_merge( $data, $this->extract_hero_data( $node ) );
                break;

            case 'features':
                $data = array_merge( $data, $this->extract_features_data( $node ) );
                break;

            case 'cta':
                $data = array_merge( $data, $this->extract_cta_data( $node ) );
                break;

            case 'container':
            case 'columns':
                $data['columnas'] = $this->calculate_columns( $node );
                break;

            case 'spacer':
                $data['altura'] = $node['absoluteBoundingBox']['height'] ?? 40;
                break;
        }

        // Extraer estilos comunes
        $data['estilos_comunes'] = $this->extract_common_styles( $node );

        return $data;
    }

    /**
     * Extrae datos de una sección hero
     *
     * @param array $node Nodo de Figma.
     * @return array
     */
    private function extract_hero_data( $node ) {
        $data = array(
            'titulo'      => '',
            'subtitulo'   => '',
            'boton_texto' => '',
        );

        // Buscar textos en hijos
        $texts = $this->find_text_nodes( $node );

        if ( count( $texts ) >= 1 ) {
            // El texto más grande probablemente es el título
            usort( $texts, function( $a, $b ) {
                $size_a = $a['style']['fontSize'] ?? 16;
                $size_b = $b['style']['fontSize'] ?? 16;
                return $size_b - $size_a;
            });

            $data['titulo'] = $texts[0]['characters'] ?? '';

            if ( count( $texts ) >= 2 ) {
                $data['subtitulo'] = $texts[1]['characters'] ?? '';
            }
        }

        // Buscar botones
        $buttons = $this->find_button_nodes( $node );
        if ( ! empty( $buttons ) ) {
            $data['boton_texto'] = $buttons[0]['characters'] ?? 'Comenzar';
        }

        return $data;
    }

    /**
     * Extrae datos de una sección features
     *
     * @param array $node Nodo de Figma.
     * @return array
     */
    private function extract_features_data( $node ) {
        $data = array(
            'titulo' => '',
            'items'  => array(),
        );

        // Buscar título (texto más grande fuera de los items)
        $texts = $this->find_text_nodes( $node, 1 ); // Solo nivel 1
        foreach ( $texts as $text ) {
            $font_size = $text['style']['fontSize'] ?? 16;
            if ( $font_size >= 24 ) {
                $data['titulo'] = $text['characters'] ?? '';
                break;
            }
        }

        // Buscar items (frames repetidos con estructura similar)
        $children = $node['children'] ?? array();
        $potential_items = array();

        foreach ( $children as $child ) {
            if ( in_array( $child['type'], array( 'FRAME', 'GROUP', 'COMPONENT', 'INSTANCE' ), true ) ) {
                $item_texts = $this->find_text_nodes( $child );
                if ( count( $item_texts ) >= 1 ) {
                    $potential_items[] = array(
                        'icono'       => $this->detect_icon( $child ),
                        'titulo'      => $item_texts[0]['characters'] ?? '',
                        'descripcion' => isset( $item_texts[1] ) ? $item_texts[1]['characters'] : '',
                    );
                }
            }
        }

        // Solo usar si hay al menos 2 items similares
        if ( count( $potential_items ) >= 2 ) {
            $data['items'] = array_slice( $potential_items, 0, 6 );
        }

        return $data;
    }

    /**
     * Extrae datos de una sección CTA
     *
     * @param array $node Nodo de Figma.
     * @return array
     */
    private function extract_cta_data( $node ) {
        $data = array(
            'titulo'      => '',
            'subtitulo'   => '',
            'boton_texto' => '',
        );

        $texts = $this->find_text_nodes( $node );

        if ( count( $texts ) >= 1 ) {
            // Ordenar por tamaño
            usort( $texts, function( $a, $b ) {
                $size_a = $a['style']['fontSize'] ?? 16;
                $size_b = $b['style']['fontSize'] ?? 16;
                return $size_b - $size_a;
            });

            $data['titulo'] = $texts[0]['characters'] ?? '';

            if ( count( $texts ) >= 2 ) {
                $data['subtitulo'] = $texts[1]['characters'] ?? '';
            }
        }

        // Buscar botón
        $buttons = $this->find_button_nodes( $node );
        if ( ! empty( $buttons ) ) {
            $data['boton_texto'] = $buttons[0]['characters'] ?? 'Continuar';
        }

        return $data;
    }

    /**
     * Extrae estilos de texto
     *
     * @param array $node Nodo de texto.
     * @return array
     */
    private function extract_text_styles( $node ) {
        $style = $node['style'] ?? array();

        return array(
            'fontSize'      => $style['fontSize'] ?? 16,
            'fontFamily'    => $style['fontFamily'] ?? '',
            'fontWeight'    => $style['fontWeight'] ?? 400,
            'textAlign'     => $style['textAlignHorizontal'] ?? 'LEFT',
            'letterSpacing' => $style['letterSpacing'] ?? 0,
            'lineHeight'    => $style['lineHeightPx'] ?? null,
            'color'         => $this->extract_color( $node['fills'] ?? array() ),
        );
    }

    /**
     * Extrae estilos comunes
     *
     * @param array $node Nodo.
     * @return array
     */
    private function extract_common_styles( $node ) {
        $styles = array();

        // Dimensiones
        if ( isset( $node['absoluteBoundingBox'] ) ) {
            $bbox = $node['absoluteBoundingBox'];
            $styles['width'] = $bbox['width'] ?? null;
            $styles['height'] = $bbox['height'] ?? null;
        }

        // Border radius
        if ( isset( $node['cornerRadius'] ) ) {
            $styles['borderRadius'] = $node['cornerRadius'];
        }

        // Padding
        if ( isset( $node['paddingLeft'] ) ) {
            $styles['paddingLeft'] = $node['paddingLeft'];
            $styles['paddingRight'] = $node['paddingRight'] ?? $node['paddingLeft'];
            $styles['paddingTop'] = $node['paddingTop'] ?? 0;
            $styles['paddingBottom'] = $node['paddingBottom'] ?? 0;
        }

        // Background
        if ( isset( $node['fills'] ) && ! empty( $node['fills'] ) ) {
            foreach ( $node['fills'] as $fill ) {
                if ( 'SOLID' === ( $fill['type'] ?? '' ) && ( $fill['visible'] ?? true ) ) {
                    $styles['backgroundColor'] = $this->rgba_to_hex( $fill['color'] );
                    break;
                }
            }
        }

        return $styles;
    }

    /**
     * Extrae configuración de página del frame
     *
     * @param array $frame Frame de Figma.
     * @return array
     */
    private function extract_page_settings( $frame ) {
        $settings = array();

        // Background color
        if ( isset( $frame['backgroundColor'] ) ) {
            $settings['backgroundColor'] = $this->rgba_to_hex( $frame['backgroundColor'] );
        }

        return $settings;
    }

    /**
     * Detecta secciones dentro de un frame
     *
     * @param array $frame Frame de Figma.
     * @return array
     */
    private function detect_sections( $frame ) {
        $children = $frame['children'] ?? array();
        $sections = array();

        // Los hijos directos del frame principal suelen ser secciones
        foreach ( $children as $child ) {
            $type = $child['type'] ?? '';

            // Solo frames y grupos como secciones
            if ( in_array( $type, array( 'FRAME', 'GROUP', 'COMPONENT', 'INSTANCE' ), true ) ) {
                $sections[] = $child;
            }
        }

        return $sections;
    }

    /**
     * Convierte una sección a elemento VBP
     *
     * @param array $section Sección de Figma.
     * @param array $images_map Mapa de imágenes.
     * @return array|null
     */
    private function convert_section( $section, $images_map ) {
        $vbp_type = $this->determine_vbp_type( $section );

        // Filtrar tipos no soportados
        if ( 'container' === $vbp_type && empty( $section['children'] ) ) {
            return null;
        }

        return $this->convert_node( $section, $images_map );
    }

    /**
     * Busca nodos de texto en un nodo
     *
     * @param array $node Nodo.
     * @param int   $max_depth Profundidad máxima.
     * @return array
     */
    private function find_text_nodes( $node, $max_depth = 3 ) {
        $texts = array();

        $this->traverse_node( $node, function( $n ) use ( &$texts ) {
            if ( 'TEXT' === ( $n['type'] ?? '' ) ) {
                $texts[] = $n;
            }
        }, $max_depth );

        return $texts;
    }

    /**
     * Busca nodos que parecen botones
     *
     * @param array $node Nodo.
     * @return array
     */
    private function find_button_nodes( $node ) {
        $buttons = array();

        $this->traverse_node( $node, function( $n ) use ( &$buttons ) {
            $name = strtolower( $n['name'] ?? '' );

            // Detectar por nombre
            if ( strpos( $name, 'button' ) !== false ||
                 strpos( $name, 'btn' ) !== false ||
                 strpos( $name, 'cta' ) !== false ||
                 strpos( $name, 'boton' ) !== false ) {

                // Buscar texto dentro
                $texts = $this->find_text_nodes( $n, 1 );
                if ( ! empty( $texts ) ) {
                    $buttons[] = $texts[0];
                }
            }
        }, 3 );

        return $buttons;
    }

    /**
     * Detecta si hay un icono en el nodo
     *
     * @param array $node Nodo.
     * @return string
     */
    private function detect_icon( $node ) {
        // Buscar vectores o componentes de icono
        $icons = array();

        $this->traverse_node( $node, function( $n ) use ( &$icons ) {
            $name = strtolower( $n['name'] ?? '' );
            $type = $n['type'] ?? '';

            if ( 'VECTOR' === $type || strpos( $name, 'icon' ) !== false ) {
                $icons[] = $n;
            }
        }, 2 );

        // Devolver emoji basado en nombre si no hay icono
        if ( empty( $icons ) ) {
            return '✨';
        }

        return '📌'; // Placeholder, en producción se descargaría el SVG
    }

    /**
     * Recorre un nodo y sus hijos
     *
     * @param array    $node Nodo.
     * @param callable $callback Callback.
     * @param int      $max_depth Profundidad máxima.
     * @param int      $current_depth Profundidad actual.
     */
    private function traverse_node( $node, $callback, $max_depth = 10, $current_depth = 0 ) {
        if ( $current_depth >= $max_depth ) {
            return;
        }

        $callback( $node );

        if ( isset( $node['children'] ) ) {
            foreach ( $node['children'] as $child ) {
                $this->traverse_node( $child, $callback, $max_depth, $current_depth + 1 );
            }
        }
    }

    /**
     * Verifica si un nodo tiene hijos
     *
     * @param array $node Nodo.
     * @return bool
     */
    private function has_children( $node ) {
        return ! empty( $node['children'] );
    }

    /**
     * Verifica si un tipo es contenedor
     *
     * @param string $type Tipo.
     * @return bool
     */
    private function is_container_type( $type ) {
        return in_array( $type, array( 'container', 'columns', 'hero', 'features', 'cta' ), true );
    }

    /**
     * Calcula el número de columnas
     *
     * @param array $node Nodo.
     * @return int
     */
    private function calculate_columns( $node ) {
        $children = $node['children'] ?? array();

        if ( count( $children ) <= 1 ) {
            return 1;
        }

        // Detectar layout mode
        $layout_mode = $node['layoutMode'] ?? 'NONE';

        if ( 'HORIZONTAL' === $layout_mode ) {
            return min( count( $children ), 4 );
        }

        return 1;
    }

    /**
     * Extrae color de fills
     *
     * @param array $fills Fills de Figma.
     * @return string|null
     */
    private function extract_color( $fills ) {
        foreach ( $fills as $fill ) {
            if ( 'SOLID' === ( $fill['type'] ?? '' ) && ( $fill['visible'] ?? true ) ) {
                return $this->rgba_to_hex( $fill['color'] );
            }
        }
        return null;
    }

    /**
     * Convierte color RGBA de Figma a hex
     *
     * @param array $color Color de Figma.
     * @return string
     */
    private function rgba_to_hex( $color ) {
        if ( ! is_array( $color ) ) {
            return '#000000';
        }

        $r = isset( $color['r'] ) ? round( $color['r'] * 255 ) : 0;
        $g = isset( $color['g'] ) ? round( $color['g'] * 255 ) : 0;
        $b = isset( $color['b'] ) ? round( $color['b'] * 255 ) : 0;

        return sprintf( '#%02x%02x%02x', $r, $g, $b );
    }

    /**
     * Genera ID único para elemento
     *
     * @return string
     */
    private function generate_id() {
        return 'vbp_' . uniqid();
    }
}
