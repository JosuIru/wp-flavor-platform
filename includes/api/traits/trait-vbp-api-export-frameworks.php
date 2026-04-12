<?php
/**
 * Trait para Exportación a Frameworks VBP
 * @package Flavor_Platform
 * @since 2.2.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

trait VBP_API_ExportFrameworks {

    /**
     * Exporta página como HTML
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_as_html( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_styles = (bool) $request->get_param( 'include_styles' );
        $minify = (bool) $request->get_param( 'minify' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $html = $this->elements_to_html( $elements );
        if ( $include_styles ) {
            $css = $this->generate_page_css( $elements );
            $html = "<style>{$css}</style>\n{$html}";
        }
        if ( $minify ) {
            $html = preg_replace( '/\s+/', ' ', $html );
        }

        return new WP_REST_Response( array( 'success' => true, 'html' => $html ), 200 );
    }

    /**
     * Exporta CSS de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_css( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );
        $minify = (bool) $request->get_param( 'minify' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $css = $this->generate_page_css( $elements );
        if ( $minify ) {
            $css = preg_replace( '/\s+/', ' ', $css );
        }

        return new WP_REST_Response( array( 'success' => true, 'css' => $css, 'format' => $format ), 200 );
    }

    /**
     * Exporta como componentes React/Vue/Svelte
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_as_components( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $framework = sanitize_text_field( $request->get_param( 'framework' ) );
        $typescript = (bool) $request->get_param( 'typescript' );
        $styling = sanitize_text_field( $request->get_param( 'styling' ) );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $page_title = sanitize_title( $post->post_title );
        $component_name = $this->to_pascal_case( $page_title ?: 'Page' );

        // Generar código según framework
        switch ( $framework ) {
            case 'react':
                $result = $this->generate_react_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'vue':
                $result = $this->generate_vue_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'svelte':
                $result = $this->generate_svelte_component( $elements, $component_name, $typescript, $styling );
                break;
            case 'html':
            default:
                $result = $this->generate_html_export( $elements, $component_name, $styling );
                break;
        }

        return new WP_REST_Response( array(
            'success'    => true,
            'framework'  => $framework,
            'typescript' => $typescript,
            'styling'    => $styling,
            'page_id'    => $page_id,
            'page_title' => $post->post_title,
            'files'      => $result['files'],
            'main_file'  => $result['main_file'],
            'dependencies' => $result['dependencies'] ?? array(),
        ), 200 );
    }

    /**
     * Genera componente React/JSX
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_react_component( $elements, $component_name, $typescript, $styling ) {
        $extension = $typescript ? 'tsx' : 'jsx';
        $files = array();
        $dependencies = array( 'react' );

        // Generar imports según styling
        $imports = "import React from 'react';\n";
        $style_imports = '';

        switch ( $styling ) {
            case 'styled-components':
                $imports .= "import styled from 'styled-components';\n";
                $dependencies[] = 'styled-components';
                break;
            case 'css-modules':
                $style_imports = "import styles from './{$component_name}.module.css';\n";
                break;
            case 'tailwind':
                $dependencies[] = 'tailwindcss';
                break;
            default: // inline
                break;
        }

        // Generar JSX para elementos
        $jsx_content = $this->elements_to_jsx( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        // Componente principal
        $type_annotation = $typescript ? ': React.FC' : '';
        $component_code = "{$imports}{$style_imports}
/**
 * {$component_name} - Generado por Flavor VBP
 * @generated
 */
const {$component_name}{$type_annotation} = () => {
    return (
        <div className=\"vbp-page\">
{$jsx_content}
        </div>
    );
};

export default {$component_name};
";

        $files[] = array(
            'filename' => "{$component_name}.{$extension}",
            'content'  => $component_code,
            'type'     => 'component',
        );

        // Archivo de estilos si es necesario
        if ( 'css-modules' === $styling ) {
            $files[] = array(
                'filename' => "{$component_name}.module.css",
                'content'  => $css_content,
                'type'     => 'styles',
            );
        } elseif ( 'inline' === $styling || 'tailwind' !== $styling ) {
            $files[] = array(
                'filename' => "{$component_name}.css",
                'content'  => $css_content,
                'type'     => 'styles',
            );
        }

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.{$extension}",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera componente Vue SFC
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_vue_component( $elements, $component_name, $typescript, $styling ) {
        $files = array();
        $dependencies = array( 'vue' );

        $script_lang = $typescript ? ' lang="ts"' : '';
        $style_scoped = 'tailwind' !== $styling ? ' scoped' : '';

        // Generar template
        $template_content = $this->elements_to_vue_template( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        $vue_code = "<template>
    <div class=\"vbp-page\">
{$template_content}
    </div>
</template>

<script{$script_lang}>
/**
 * {$component_name} - Generado por Flavor VBP
 * @generated
 */
export default {
    name: '{$component_name}',
};
</script>

<style{$style_scoped}>
{$css_content}
</style>
";

        $files[] = array(
            'filename' => "{$component_name}.vue",
            'content'  => $vue_code,
            'type'     => 'component',
        );

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.vue",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera componente Svelte
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param bool   $typescript     Usar TypeScript.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_svelte_component( $elements, $component_name, $typescript, $styling ) {
        $files = array();
        $dependencies = array( 'svelte' );

        $script_lang = $typescript ? ' lang="ts"' : '';

        // Generar markup
        $markup_content = $this->elements_to_svelte_markup( $elements, $styling );

        // Generar estilos
        $css_content = $this->elements_to_css( $elements );

        $svelte_code = "<script{$script_lang}>
    /**
     * {$component_name} - Generado por Flavor VBP
     * @generated
     */
</script>

<div class=\"vbp-page\">
{$markup_content}
</div>

<style>
{$css_content}
</style>
";

        $files[] = array(
            'filename' => "{$component_name}.svelte",
            'content'  => $svelte_code,
            'type'     => 'component',
        );

        return array(
            'files'        => $files,
            'main_file'    => "{$component_name}.svelte",
            'dependencies' => $dependencies,
        );
    }

    /**
     * Genera exportación HTML estática
     *
     * @param array  $elements       Elementos VBP.
     * @param string $component_name Nombre del componente.
     * @param string $styling        Tipo de estilos.
     * @return array
     */
    private function generate_html_export( $elements, $component_name, $styling ) {
        $files = array();

        $html_content = $this->elements_to_html( $elements );
        $css_content = $this->elements_to_css( $elements );

        $html_code = "<!DOCTYPE html>
<html lang=\"es\">
<head>
    <meta charset=\"UTF-8\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
    <title>{$component_name}</title>
    <link rel=\"stylesheet\" href=\"{$component_name}.css\">
</head>
<body>
    <div class=\"vbp-page\">
{$html_content}
    </div>
</body>
</html>
";

        $files[] = array(
            'filename' => "{$component_name}.html",
            'content'  => $html_code,
            'type'     => 'html',
        );

        $files[] = array(
            'filename' => "{$component_name}.css",
            'content'  => $css_content,
            'type'     => 'styles',
        );

        return array(
            'files'     => $files,
            'main_file' => "{$component_name}.html",
        );
    }

    /**
     * Convierte elementos VBP a JSX
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_jsx( $elements, $styling, $indent = 3 ) {
        $jsx = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = $this->generate_class_name( $type, $element_id, $styling );
            $style_attr = $this->generate_style_attr( $element, $styling );
            $content = $this->get_element_content( $data );

            $jsx .= "{$spaces}<{$tag}";

            if ( $class_name ) {
                $jsx .= " className=\"{$class_name}\"";
            }

            if ( $style_attr && 'inline' === $styling ) {
                $jsx .= " style={{{$style_attr}}}";
            }

            if ( ! empty( $children ) ) {
                $jsx .= ">\n";
                $jsx .= $this->elements_to_jsx( $children, $styling, $indent + 1 );
                $jsx .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $jsx .= ">{$content}</{$tag}>\n";
            } else {
                $jsx .= " />\n";
            }
        }

        return $jsx;
    }

    /**
     * Convierte elementos VBP a template Vue
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_vue_template( $elements, $styling, $indent = 2 ) {
        $html = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = $this->generate_class_name( $type, $element_id, $styling );
            $content = $this->get_element_content( $data );

            $html .= "{$spaces}<{$tag}";

            if ( $class_name ) {
                $html .= " class=\"{$class_name}\"";
            }

            if ( ! empty( $children ) ) {
                $html .= ">\n";
                $html .= $this->elements_to_vue_template( $children, $styling, $indent + 1 );
                $html .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $html .= ">{$content}</{$tag}>\n";
            } else {
                $html .= "></{$tag}>\n";
            }
        }

        return $html;
    }

    /**
     * Convierte elementos VBP a markup Svelte
     *
     * @param array  $elements Elementos VBP.
     * @param string $styling  Tipo de estilos.
     * @param int    $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_svelte_markup( $elements, $styling, $indent = 1 ) {
        return $this->elements_to_vue_template( $elements, $styling, $indent );
    }

    /**
     * Convierte elementos VBP a HTML
     *
     * @param array $elements Elementos VBP.
     * @param int   $indent   Nivel de indentación.
     * @return string
     */
    private function elements_to_html( $elements, $indent = 2 ) {
        $html = '';
        $spaces = str_repeat( '    ', $indent );

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $data = $element['data'] ?? array();
            $children = $element['children'] ?? array();
            $element_id = $element['id'] ?? '';

            $tag = $this->vbp_type_to_html_tag( $type );
            $class_name = "vbp-{$type}";
            $content = $this->get_element_content( $data );

            $html .= "{$spaces}<{$tag} class=\"{$class_name}\"";

            if ( $element_id ) {
                $html .= " id=\"{$element_id}\"";
            }

            if ( ! empty( $children ) ) {
                $html .= ">\n";
                $html .= $this->elements_to_html( $children, $indent + 1 );
                $html .= "{$spaces}</{$tag}>\n";
            } elseif ( $content ) {
                $html .= ">{$content}</{$tag}>\n";
            } else {
                $html .= "></{$tag}>\n";
            }
        }

        return $html;
    }

    /**
     * Genera CSS desde elementos VBP
     *
     * @param array $elements Elementos VBP.
     * @return string
     */
    private function elements_to_css( $elements ) {
        $css = "/* Generado por Flavor VBP */\n\n";
        $css .= ".vbp-page {\n    max-width: 1200px;\n    margin: 0 auto;\n}\n\n";

        $css .= $this->generate_element_css( $elements );

        return $css;
    }

    /**
     * Genera CSS recursivo para elementos
     *
     * @param array $elements Elementos VBP.
     * @return string
     */
    private function generate_element_css( $elements ) {
        $css = '';

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'div';
            $styles = $element['styles'] ?? array();
            $children = $element['children'] ?? array();

            $selector = ".vbp-{$type}";

            if ( ! empty( $styles ) ) {
                $css .= "{$selector} {\n";

                foreach ( $styles as $property => $value ) {
                    if ( is_string( $value ) || is_numeric( $value ) ) {
                        $css_property = $this->camel_to_kebab( $property );
                        $css .= "    {$css_property}: {$value};\n";
                    }
                }

                $css .= "}\n\n";
            }

            if ( ! empty( $children ) ) {
                $css .= $this->generate_element_css( $children );
            }
        }

        return $css;
    }

    /**
     * Convierte tipo VBP a tag HTML
     *
     * @param string $type Tipo VBP.
     * @return string
     */
    private function vbp_type_to_html_tag( $type ) {
        $mapping = array(
            'section'   => 'section',
            'container' => 'div',
            'row'       => 'div',
            'column'    => 'div',
            'heading'   => 'h2',
            'text'      => 'p',
            'paragraph' => 'p',
            'image'     => 'img',
            'button'    => 'button',
            'link'      => 'a',
            'list'      => 'ul',
            'list-item' => 'li',
            'video'     => 'video',
            'form'      => 'form',
            'input'     => 'input',
            'textarea'  => 'textarea',
            'nav'       => 'nav',
            'header'    => 'header',
            'footer'    => 'footer',
            'article'   => 'article',
            'aside'     => 'aside',
            'figure'    => 'figure',
            'figcaption'=> 'figcaption',
            'blockquote'=> 'blockquote',
            'code'      => 'pre',
            'table'     => 'table',
            'icon'      => 'span',
            'spacer'    => 'div',
            'divider'   => 'hr',
        );

        return $mapping[ $type ] ?? 'div';
    }

    /**
     * Genera nombre de clase según styling
     *
     * @param string $type       Tipo de elemento.
     * @param string $element_id ID del elemento.
     * @param string $styling    Tipo de estilos.
     * @return string
     */
    private function generate_class_name( $type, $element_id, $styling ) {
        if ( 'tailwind' === $styling ) {
            return $this->get_tailwind_classes( $type );
        }

        if ( 'css-modules' === $styling ) {
            return "styles.{$type}";
        }

        return "vbp-{$type}";
    }

    /**
     * Obtiene clases Tailwind para tipo
     *
     * @param string $type Tipo de elemento.
     * @return string
     */
    private function get_tailwind_classes( $type ) {
        $classes = array(
            'section'   => 'py-16 px-4',
            'container' => 'max-w-6xl mx-auto',
            'row'       => 'flex flex-wrap -mx-4',
            'column'    => 'px-4 w-full md:w-1/2 lg:w-1/3',
            'heading'   => 'text-3xl font-bold mb-4',
            'text'      => 'text-base text-gray-700 mb-4',
            'button'    => 'px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700',
            'image'     => 'w-full h-auto rounded',
            'spacer'    => 'h-8',
            'divider'   => 'border-t border-gray-200 my-8',
        );

        return $classes[ $type ] ?? '';
    }

    /**
     * Genera atributo style inline
     *
     * @param array  $element Elemento VBP.
     * @param string $styling Tipo de estilos.
     * @return string
     */
    private function generate_style_attr( $element, $styling ) {
        if ( 'inline' !== $styling ) {
            return '';
        }

        $styles = $element['styles'] ?? array();
        if ( empty( $styles ) ) {
            return '';
        }

        $style_parts = array();
        foreach ( $styles as $property => $value ) {
            if ( is_string( $value ) || is_numeric( $value ) ) {
                $style_parts[] = "{$property}: '{$value}'";
            }
        }

        return implode( ', ', $style_parts );
    }

    /**
     * Obtiene contenido del elemento
     *
     * @param array $data Datos del elemento.
     * @return string
     */
    private function get_element_content( $data ) {
        return esc_html( $data['text'] ?? $data['content'] ?? $data['label'] ?? '' );
    }

    /**
     * Convierte string a PascalCase
     *
     * @param string $string String a convertir.
     * @return string
     */
    private function to_pascal_case( $string ) {
        $string = preg_replace( '/[^a-zA-Z0-9]/', ' ', $string );
        $words = explode( ' ', $string );
        $pascal = '';

        foreach ( $words as $word ) {
            if ( $word ) {
                $pascal .= ucfirst( strtolower( $word ) );
            }
        }

        return $pascal ?: 'Component';
    }

    /**
     * Convierte camelCase a kebab-case
     *
     * @param string $string String a convertir.
     * @return string
     */
    private function camel_to_kebab( $string ) {
        return strtolower( preg_replace( '/([a-z])([A-Z])/', '$1-$2', $string ) );
    }

    /**
     * Exporta estructura JSON
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_page_structure_json( $request ) {
        $page_id = (int) $request->get_param( 'id' );

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'export'   => array( 'version' => '2.0', 'exported' => current_time( 'mysql' ), 'elements' => $elements ),
        ), 200 );
    }

    /**
     * Exportar página a componentes React
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_react( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $component_style = sanitize_text_field( $request->get_param( 'component_style' ) );
        $css_strategy = sanitize_text_field( $request->get_param( 'css_strategy' ) );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'react' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', $css_strategy );

        return $this->export_page_as_components( $simulated_request );
    }

    /**
     * Exportar página a componentes Vue
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_vue( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $vue_version = (int) $request->get_param( 'vue_version' );
        $composition_api = (bool) $request->get_param( 'composition_api' );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'vue' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', 'scoped' );

        $response = $this->export_page_as_components( $simulated_request );
        $data = $response->get_data();

        // Agregar metadata específica de Vue
        if ( $data['success'] ?? false ) {
            $data['vue_version'] = $vue_version;
            $data['composition_api'] = $composition_api;
        }

        return new WP_REST_Response( $data, $response->get_status() );
    }

    /**
     * Exportar página a componentes Svelte
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_to_svelte( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $typescript = (bool) $request->get_param( 'typescript' );
        $svelte_version = (int) $request->get_param( 'svelte_version' );

        // Crear request simulado para reutilizar export_page_as_components
        $simulated_request = new WP_REST_Request( 'GET' );
        $simulated_request->set_param( 'id', $page_id );
        $simulated_request->set_param( 'framework', 'svelte' );
        $simulated_request->set_param( 'typescript', $typescript );
        $simulated_request->set_param( 'styling', 'scoped' );

        $response = $this->export_page_as_components( $simulated_request );
        $data = $response->get_data();

        // Agregar metadata específica de Svelte
        if ( $data['success'] ?? false ) {
            $data['svelte_version'] = $svelte_version;
        }

        return new WP_REST_Response( $data, $response->get_status() );
    }

    /**
     * Exportar solo CSS de una página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_css_only( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $format = sanitize_text_field( $request->get_param( 'format' ) );
        $minify = (bool) $request->get_param( 'minify' );
        $include_reset = (bool) $request->get_param( 'include_reset' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $css_reset = '';
        if ( $include_reset ) {
            $css_reset = "/* CSS Reset */\n* { margin: 0; padding: 0; box-sizing: border-box; }\nhtml { font-size: 16px; }\nbody { font-family: system-ui, -apple-system, sans-serif; line-height: 1.5; }\nimg { max-width: 100%; height: auto; }\na { text-decoration: none; color: inherit; }\n\n";
        }

        // Generar CSS desde elementos
        $css_content = $this->elements_to_css( $elements );

        // Convertir formato si es necesario
        if ( $format === 'tailwind' ) {
            $css_content = $this->css_to_tailwind_hints( $css_content );
        }

        if ( $minify ) {
            $css_content = $this->minify_css( $css_reset . $css_content );
        } else {
            $css_content = $css_reset . $css_content;
        }

        $file_extension = ( $format === 'scss' || $format === 'less' ) ? $format : 'css';
        $filename = sanitize_title( $post->post_title ) . '.' . $file_extension;

        return new WP_REST_Response( array(
            'success'  => true,
            'format'   => $format,
            'filename' => $filename,
            'content'  => $css_content,
            'size'     => strlen( $css_content ),
        ), 200 );
    }

    /**
     * Exportar estructura JSON de página
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response
     */
    public function export_json_structure( $request ) {
        $page_id = (int) $request->get_param( 'id' );
        $include_styles = (bool) $request->get_param( 'include_styles' );
        $include_settings = (bool) $request->get_param( 'include_settings' );
        $flatten = (bool) $request->get_param( 'flatten' );

        $post = get_post( $page_id );
        if ( ! $this->is_valid_vbp_post( $post ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => 'Página no encontrada.' ), 404 );
        }

        $elements = $this->get_page_elements( $page_id );
        if ( is_wp_error( $elements ) ) {
            return new WP_REST_Response( array( 'success' => false, 'error' => $elements->get_error_message() ), 404 );
        }

        $export_data = array(
            'version'    => '2.1.0',
            'exported'   => current_time( 'mysql' ),
            'page_id'    => $page_id,
            'page_title' => $post->post_title,
            'page_slug'  => $post->post_name,
        );

        if ( $include_settings ) {
            $export_data['settings'] = get_post_meta( $page_id, '_vbp_settings', true ) ?: array();
        }

        if ( $flatten ) {
            $export_data['elements'] = $this->flatten_elements( $elements );
        } else {
            $export_data['elements'] = $elements;
        }

        if ( ! $include_styles ) {
            $export_data['elements'] = $this->strip_styles_from_elements( $export_data['elements'] );
        }

        return new WP_REST_Response( array(
            'success'  => true,
            'export'   => $export_data,
            'filename' => sanitize_title( $post->post_title ) . '.json',
        ), 200 );
    }

    /**
     * Aplanar estructura jerárquica de elementos
     *
     * @param array $elements Elementos a aplanar.
     * @param string $parent_id ID del padre.
     * @return array
     */
    private function flatten_elements( $elements, $parent_id = null ) {
        $flat_elements = array();

        foreach ( $elements as $index => $element ) {
            $element_copy = $element;
            $element_copy['_parent_id'] = $parent_id;
            $element_copy['_index'] = $index;

            if ( isset( $element_copy['children'] ) ) {
                $children = $element_copy['children'];
                unset( $element_copy['children'] );
                $flat_elements[] = $element_copy;

                $flat_elements = array_merge(
                    $flat_elements,
                    $this->flatten_elements( $children, $element_copy['id'] ?? null )
                );
            } else {
                $flat_elements[] = $element_copy;
            }
        }

        return $flat_elements;
    }

    /**
     * Eliminar estilos de elementos
     *
     * @param array $elements Elementos a procesar.
     * @return array
     */
    private function strip_styles_from_elements( $elements ) {
        $stripped_elements = array();

        foreach ( $elements as $element ) {
            $stripped_element = $element;

            if ( isset( $stripped_element['data']['styles'] ) ) {
                unset( $stripped_element['data']['styles'] );
            }
            if ( isset( $stripped_element['data']['customCss'] ) ) {
                unset( $stripped_element['data']['customCss'] );
            }

            if ( isset( $stripped_element['children'] ) ) {
                $stripped_element['children'] = $this->strip_styles_from_elements( $stripped_element['children'] );
            }

            $stripped_elements[] = $stripped_element;
        }

        return $stripped_elements;
    }

    /**
     * Convertir CSS a hints de Tailwind
     *
     * @param string $css CSS a convertir.
     * @return string
     */
    private function css_to_tailwind_hints( $css ) {
        $hints = "/* Tailwind CSS Mapping Suggestions */\n\n";

        $conversions = array(
            'display: flex' => 'flex',
            'display: grid' => 'grid',
            'display: block' => 'block',
            'display: inline' => 'inline',
            'justify-content: center' => 'justify-center',
            'justify-content: space-between' => 'justify-between',
            'align-items: center' => 'items-center',
            'text-align: center' => 'text-center',
            'font-weight: bold' => 'font-bold',
            'font-weight: 600' => 'font-semibold',
            'padding: 0' => 'p-0',
            'margin: 0' => 'm-0',
        );

        foreach ( $conversions as $css_rule => $tailwind_class ) {
            if ( strpos( $css, $css_rule ) !== false ) {
                $hints .= "/* {$css_rule} => class=\"{$tailwind_class}\" */\n";
            }
        }

        $hints .= "\n/* Original CSS below for reference */\n\n";
        $hints .= $css;

        return $hints;
    }

    // =============================================
}
