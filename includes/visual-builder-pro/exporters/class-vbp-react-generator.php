<?php
/**
 * Visual Builder Pro - React Generator
 *
 * Genera componentes React a partir de elementos VBP.
 *
 * @package FlavorPlatform
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generador de componentes React
 */
class Flavor_VBP_React_Generator {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_React_Generator|null
     */
    private static $instancia = null;

    /**
     * Formato de estilos
     *
     * @var string
     */
    private $style_format = 'css';

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_React_Generator
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Mapeo de tipos VBP a componentes React
     *
     * @var array
     */
    private $component_map = array(
        'hero'         => 'Hero',
        'features'     => 'Features',
        'cta'          => 'CallToAction',
        'testimonials' => 'Testimonials',
        'stats'        => 'Stats',
        'pricing'      => 'Pricing',
        'faq'          => 'FAQ',
        'gallery'      => 'Gallery',
        'team'         => 'Team',
        'contact'      => 'Contact',
        'columns'      => 'Grid',
        'text'         => 'Text',
        'heading'      => 'Heading',
        'button'       => 'Button',
        'image'        => 'Image',
        'spacer'       => 'Spacer',
        'divider'      => 'Divider',
    );

    /**
     * Genera código React para un elemento
     *
     * @param array  $element Elemento VBP.
     * @param string $style_format Formato de estilos (css, tailwind, styled-components).
     * @return array Array con archivos generados.
     */
    public function generate( $element, $style_format = 'css' ) {
        $this->style_format = $style_format;

        $type = $element['type'] ?? 'unknown';
        $component_name = $this->get_component_name( $type );

        $files = array();

        // Generar componente JSX
        $jsx_content = $this->generate_jsx( $element, $component_name );
        $files[] = array(
            'name'    => "{$component_name}.jsx",
            'content' => $jsx_content,
            'type'    => 'component',
        );

        // Generar estilos según formato
        if ( 'css' === $style_format ) {
            $css_content = $this->generate_css_module( $element, $component_name );
            $files[] = array(
                'name'    => "{$component_name}.module.css",
                'content' => $css_content,
                'type'    => 'styles',
            );
        }

        // Generar index.js para re-export
        $index_content = $this->generate_index( $component_name );
        $files[] = array(
            'name'    => 'index.js',
            'content' => $index_content,
            'type'    => 'index',
        );

        return $files;
    }

    /**
     * Genera múltiples componentes a partir de un documento
     *
     * @param array  $document Documento VBP completo.
     * @param string $style_format Formato de estilos.
     * @return array
     */
    public function generate_from_document( $document, $style_format = 'css' ) {
        $this->style_format = $style_format;
        $elements = $document['elements'] ?? array();

        $all_files = array();
        $components_used = array();

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'unknown';
            $component_name = $this->get_component_name( $type );

            // Evitar duplicados
            if ( in_array( $component_name, $components_used, true ) ) {
                continue;
            }
            $components_used[] = $component_name;

            $files = $this->generate( $element, $style_format );
            foreach ( $files as $file ) {
                $file['folder'] = $component_name;
                $all_files[] = $file;
            }
        }

        // Generar componente principal Page
        $page_files = $this->generate_page_component( $elements, $components_used, $style_format );
        foreach ( $page_files as $file ) {
            $all_files[] = $file;
        }

        return $all_files;
    }

    /**
     * Obtiene nombre del componente React
     *
     * @param string $type Tipo de elemento.
     * @return string
     */
    private function get_component_name( $type ) {
        return $this->component_map[ $type ] ?? ucfirst( $type );
    }

    /**
     * Genera el contenido JSX del componente
     *
     * @param array  $element Elemento.
     * @param string $component_name Nombre del componente.
     * @return string
     */
    private function generate_jsx( $element, $component_name ) {
        $type = $element['type'] ?? 'unknown';
        $data = $element['data'] ?? array();

        $jsx = "import React from 'react';\n";

        // Import de estilos según formato
        if ( 'css' === $this->style_format ) {
            $jsx .= "import styles from './{$component_name}.module.css';\n";
        } elseif ( 'styled-components' === $this->style_format ) {
            $jsx .= "import styled from 'styled-components';\n";
        }

        $jsx .= "\n";

        // Generar el componente según el tipo
        $jsx .= $this->generate_component_body( $type, $data, $component_name );

        $jsx .= "\nexport default {$component_name};\n";

        return $jsx;
    }

    /**
     * Genera el cuerpo del componente según el tipo
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @param string $component_name Nombre del componente.
     * @return string
     */
    private function generate_component_body( $type, $data, $component_name ) {
        switch ( $type ) {
            case 'hero':
                return $this->generate_hero_component( $data, $component_name );

            case 'features':
                return $this->generate_features_component( $data, $component_name );

            case 'cta':
                return $this->generate_cta_component( $data, $component_name );

            case 'testimonials':
                return $this->generate_testimonials_component( $data, $component_name );

            case 'stats':
                return $this->generate_stats_component( $data, $component_name );

            case 'text':
                return $this->generate_text_component( $data, $component_name );

            case 'heading':
                return $this->generate_heading_component( $data, $component_name );

            case 'button':
                return $this->generate_button_component( $data, $component_name );

            case 'image':
                return $this->generate_image_component( $data, $component_name );

            default:
                return $this->generate_generic_component( $data, $component_name );
        }
    }

    /**
     * Genera componente Hero
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_hero_component( $data, $name ) {
        $title = $this->escape_jsx( $data['titulo'] ?? 'Título Principal' );
        $subtitle = $this->escape_jsx( $data['subtitulo'] ?? '' );
        $button_text = $this->escape_jsx( $data['boton_texto'] ?? 'Comenzar' );
        $button_url = $this->escape_jsx( $data['boton_url'] ?? '#' );
        $image = $data['imagen'] ?? '';

        $class_name = $this->get_class_reference( 'hero' );
        $container_class = $this->get_class_reference( 'container' );
        $content_class = $this->get_class_reference( 'content' );
        $title_class = $this->get_class_reference( 'title' );
        $subtitle_class = $this->get_class_reference( 'subtitle' );
        $button_class = $this->get_class_reference( 'button' );

        $jsx = <<<JSX
const {$name} = ({
  title = "{$title}",
  subtitle = "{$subtitle}",
  buttonText = "{$button_text}",
  buttonUrl = "{$button_url}",
  backgroundImage,
}) => {
  return (
    <section className={{$class_name}}>
      <div className={{$container_class}}>
        <div className={{$content_class}}>
          <h1 className={{$title_class}}>{title}</h1>
          {subtitle && <p className={{$subtitle_class}}>{subtitle}</p>}
          {buttonText && (
            <a href={buttonUrl} className={{$button_class}}>
              {buttonText}
            </a>
          )}
        </div>
      </div>
    </section>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Features
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_features_component( $data, $name ) {
        $title = $this->escape_jsx( $data['titulo'] ?? 'Características' );

        $section_class = $this->get_class_reference( 'features' );
        $container_class = $this->get_class_reference( 'container' );
        $title_class = $this->get_class_reference( 'title' );
        $grid_class = $this->get_class_reference( 'grid' );
        $card_class = $this->get_class_reference( 'card' );
        $icon_class = $this->get_class_reference( 'icon' );
        $card_title_class = $this->get_class_reference( 'cardTitle' );
        $description_class = $this->get_class_reference( 'description' );

        $jsx = <<<JSX
const {$name} = ({
  title = "{$title}",
  items = [
    { icon: "⚡", title: "Rápido", description: "Implementación en minutos" },
    { icon: "🔒", title: "Seguro", description: "Protección garantizada" },
    { icon: "📱", title: "Responsive", description: "Funciona en cualquier dispositivo" },
  ],
}) => {
  return (
    <section className={{$section_class}}>
      <div className={{$container_class}}>
        {title && <h2 className={{$title_class}}>{title}</h2>}
        <div className={{$grid_class}}>
          {items.map((item, index) => (
            <div key={index} className={{$card_class}}>
              <span className={{$icon_class}}>{item.icon}</span>
              <h3 className={{$card_title_class}}>{item.title}</h3>
              <p className={{$description_class}}>{item.description}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente CTA
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_cta_component( $data, $name ) {
        $title = $this->escape_jsx( $data['titulo'] ?? '¿Listo para empezar?' );
        $subtitle = $this->escape_jsx( $data['subtitulo'] ?? '' );
        $button_text = $this->escape_jsx( $data['boton_texto'] ?? 'Comenzar' );

        $section_class = $this->get_class_reference( 'cta' );
        $container_class = $this->get_class_reference( 'container' );
        $title_class = $this->get_class_reference( 'title' );
        $subtitle_class = $this->get_class_reference( 'subtitle' );
        $button_class = $this->get_class_reference( 'button' );

        $jsx = <<<JSX
const {$name} = ({
  title = "{$title}",
  subtitle = "{$subtitle}",
  buttonText = "{$button_text}",
  buttonUrl = "#",
  onButtonClick,
}) => {
  return (
    <section className={{$section_class}}>
      <div className={{$container_class}}>
        <h2 className={{$title_class}}>{title}</h2>
        {subtitle && <p className={{$subtitle_class}}>{subtitle}</p>}
        <button
          className={{$button_class}}
          onClick={onButtonClick || (() => window.location.href = buttonUrl)}
        >
          {buttonText}
        </button>
      </div>
    </section>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Testimonials
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_testimonials_component( $data, $name ) {
        $title = $this->escape_jsx( $data['titulo'] ?? 'Lo que dicen nuestros clientes' );

        $section_class = $this->get_class_reference( 'testimonials' );
        $container_class = $this->get_class_reference( 'container' );
        $title_class = $this->get_class_reference( 'title' );
        $grid_class = $this->get_class_reference( 'grid' );
        $card_class = $this->get_class_reference( 'card' );
        $quote_class = $this->get_class_reference( 'quote' );
        $author_class = $this->get_class_reference( 'author' );
        $role_class = $this->get_class_reference( 'role' );

        $jsx = <<<JSX
const {$name} = ({
  title = "{$title}",
  testimonials = [
    {
      quote: "Increíble experiencia, superó todas mis expectativas.",
      author: "María García",
      role: "CEO, TechCorp",
      avatar: null,
    },
  ],
}) => {
  return (
    <section className={{$section_class}}>
      <div className={{$container_class}}>
        {title && <h2 className={{$title_class}}>{title}</h2>}
        <div className={{$grid_class}}>
          {testimonials.map((item, index) => (
            <div key={index} className={{$card_class}}>
              <blockquote className={{$quote_class}}>"{item.quote}"</blockquote>
              <div className={{$author_class}}>{item.author}</div>
              <div className={{$role_class}}>{item.role}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Stats
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_stats_component( $data, $name ) {
        $section_class = $this->get_class_reference( 'stats' );
        $container_class = $this->get_class_reference( 'container' );
        $grid_class = $this->get_class_reference( 'grid' );
        $item_class = $this->get_class_reference( 'item' );
        $number_class = $this->get_class_reference( 'number' );
        $label_class = $this->get_class_reference( 'label' );

        $jsx = <<<JSX
const {$name} = ({
  items = [
    { number: "10K+", label: "Usuarios" },
    { number: "99%", label: "Satisfacción" },
    { number: "24/7", label: "Soporte" },
  ],
}) => {
  return (
    <section className={{$section_class}}>
      <div className={{$container_class}}>
        <div className={{$grid_class}}>
          {items.map((item, index) => (
            <div key={index} className={{$item_class}}>
              <div className={{$number_class}}>{item.number}</div>
              <div className={{$label_class}}>{item.label}</div>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Text
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_text_component( $data, $name ) {
        $content = $this->escape_jsx( $data['contenido'] ?? '' );
        $text_class = $this->get_class_reference( 'text' );

        $jsx = <<<JSX
const {$name} = ({ children, className = "" }) => {
  return (
    <div className={`{{$text_class}} \${className}`}>
      {children}
    </div>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Heading
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_heading_component( $data, $name ) {
        $heading_class = $this->get_class_reference( 'heading' );

        $jsx = <<<JSX
const {$name} = ({ level = 2, children, className = "" }) => {
  const Tag = `h\${level}`;
  return (
    <Tag className={`{{$heading_class}} \${className}`}>
      {children}
    </Tag>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Button
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_button_component( $data, $name ) {
        $button_class = $this->get_class_reference( 'button' );
        $primary_class = $this->get_class_reference( 'primary' );
        $secondary_class = $this->get_class_reference( 'secondary' );

        $jsx = <<<JSX
const {$name} = ({
  children,
  variant = "primary",
  href,
  onClick,
  className = "",
  ...props
}) => {
  const variantClass = variant === "primary" ? {$primary_class} : {$secondary_class};

  if (href) {
    return (
      <a
        href={href}
        className={`{{$button_class}} \${variantClass} \${className}`}
        {...props}
      >
        {children}
      </a>
    );
  }

  return (
    <button
      className={`{{$button_class}} \${variantClass} \${className}`}
      onClick={onClick}
      {...props}
    >
      {children}
    </button>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente Image
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_image_component( $data, $name ) {
        $image_class = $this->get_class_reference( 'image' );

        $jsx = <<<JSX
const {$name} = ({ src, alt = "", className = "", ...props }) => {
  return (
    <img
      src={src}
      alt={alt}
      className={`{{$image_class}} \${className}`}
      loading="lazy"
      {...props}
    />
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera componente genérico
     *
     * @param array  $data Datos.
     * @param string $name Nombre.
     * @return string
     */
    private function generate_generic_component( $data, $name ) {
        $wrapper_class = $this->get_class_reference( 'wrapper' );

        $jsx = <<<JSX
const {$name} = ({ children, className = "" }) => {
  return (
    <div className={`{{$wrapper_class}} \${className}`}>
      {children}
    </div>
  );
};

JSX;
        return $jsx;
    }

    /**
     * Genera CSS Module
     *
     * @param array  $element Elemento.
     * @param string $component_name Nombre del componente.
     * @return string
     */
    private function generate_css_module( $element, $component_name ) {
        $type = $element['type'] ?? 'unknown';

        $css = "/* {$component_name} Styles */\n\n";

        switch ( $type ) {
            case 'hero':
                $css .= $this->get_hero_css();
                break;

            case 'features':
                $css .= $this->get_features_css();
                break;

            case 'cta':
                $css .= $this->get_cta_css();
                break;

            case 'testimonials':
                $css .= $this->get_testimonials_css();
                break;

            case 'stats':
                $css .= $this->get_stats_css();
                break;

            default:
                $css .= $this->get_generic_css();
        }

        return $css;
    }

    /**
     * CSS para Hero
     *
     * @return string
     */
    private function get_hero_css() {
        return <<<CSS
.hero {
  padding: 80px 20px;
  background: var(--flavor-bg, #ffffff);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
  text-align: center;
}

.content {
  max-width: 800px;
  margin: 0 auto;
}

.title {
  font-size: 3rem;
  font-weight: 700;
  color: var(--flavor-text, #1f2937);
  margin-bottom: 1rem;
  line-height: 1.2;
}

.subtitle {
  font-size: 1.25rem;
  color: var(--flavor-text-muted, #6b7280);
  margin-bottom: 2rem;
}

.button {
  display: inline-block;
  padding: 12px 24px;
  background: var(--flavor-primary, #3b82f6);
  color: white;
  text-decoration: none;
  border-radius: 8px;
  font-weight: 600;
  transition: opacity 0.2s;
}

.button:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .title {
    font-size: 2rem;
  }

  .subtitle {
    font-size: 1rem;
  }
}
CSS;
    }

    /**
     * CSS para Features
     *
     * @return string
     */
    private function get_features_css() {
        return <<<CSS
.features {
  padding: 80px 20px;
  background: var(--flavor-bg, #ffffff);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--flavor-text, #1f2937);
  text-align: center;
  margin-bottom: 3rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 24px;
}

.card {
  background: white;
  padding: 24px;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  text-align: center;
}

.icon {
  font-size: 3rem;
  margin-bottom: 1rem;
}

.cardTitle {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--flavor-text, #1f2937);
  margin-bottom: 0.5rem;
}

.description {
  color: var(--flavor-text-muted, #6b7280);
  line-height: 1.6;
}
CSS;
    }

    /**
     * CSS para CTA
     *
     * @return string
     */
    private function get_cta_css() {
        return <<<CSS
.cta {
  padding: 80px 20px;
  background: var(--flavor-primary, #3b82f6);
  text-align: center;
}

.container {
  max-width: 800px;
  margin: 0 auto;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: white;
  margin-bottom: 1rem;
}

.subtitle {
  font-size: 1.25rem;
  color: rgba(255, 255, 255, 0.9);
  margin-bottom: 2rem;
}

.button {
  display: inline-block;
  padding: 14px 28px;
  background: white;
  color: var(--flavor-primary, #3b82f6);
  border: none;
  border-radius: 8px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: transform 0.2s, box-shadow 0.2s;
}

.button:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}
CSS;
    }

    /**
     * CSS para Testimonials
     *
     * @return string
     */
    private function get_testimonials_css() {
        return <<<CSS
.testimonials {
  padding: 80px 20px;
  background: var(--flavor-bg, #f9fafb);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.title {
  font-size: 2.5rem;
  font-weight: 700;
  color: var(--flavor-text, #1f2937);
  text-align: center;
  margin-bottom: 3rem;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 24px;
}

.card {
  background: white;
  padding: 32px;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.quote {
  font-size: 1.125rem;
  font-style: italic;
  color: var(--flavor-text, #1f2937);
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.author {
  font-weight: 600;
  color: var(--flavor-text, #1f2937);
}

.role {
  font-size: 0.875rem;
  color: var(--flavor-text-muted, #6b7280);
}
CSS;
    }

    /**
     * CSS para Stats
     *
     * @return string
     */
    private function get_stats_css() {
        return <<<CSS
.stats {
  padding: 60px 20px;
  background: var(--flavor-secondary, #8b5cf6);
}

.container {
  max-width: 1200px;
  margin: 0 auto;
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: 40px;
  text-align: center;
}

.item {
  color: white;
}

.number {
  font-size: 3rem;
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.label {
  font-size: 1rem;
  opacity: 0.9;
}
CSS;
    }

    /**
     * CSS genérico
     *
     * @return string
     */
    private function get_generic_css() {
        return <<<CSS
.wrapper {
  padding: 20px;
}
CSS;
    }

    /**
     * Genera archivo index.js
     *
     * @param string $component_name Nombre del componente.
     * @return string
     */
    private function generate_index( $component_name ) {
        return "export { default } from './{$component_name}';\n";
    }

    /**
     * Genera el componente principal de página
     *
     * @param array  $elements Elementos.
     * @param array  $components_used Componentes usados.
     * @param string $style_format Formato de estilos.
     * @return array
     */
    private function generate_page_component( $elements, $components_used, $style_format ) {
        $files = array();

        // Imports
        $jsx = "import React from 'react';\n";
        foreach ( $components_used as $component ) {
            $jsx .= "import {$component} from './components/{$component}';\n";
        }
        $jsx .= "\n";

        // Componente Page
        $jsx .= "const Page = () => {\n";
        $jsx .= "  return (\n";
        $jsx .= "    <main>\n";

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'unknown';
            $component_name = $this->get_component_name( $type );
            $jsx .= "      <{$component_name} />\n";
        }

        $jsx .= "    </main>\n";
        $jsx .= "  );\n";
        $jsx .= "};\n\n";
        $jsx .= "export default Page;\n";

        $files[] = array(
            'name'    => 'Page.jsx',
            'content' => $jsx,
            'type'    => 'page',
            'folder'  => '',
        );

        return $files;
    }

    /**
     * Obtiene referencia de clase según formato de estilos
     *
     * @param string $class_name Nombre de la clase.
     * @return string
     */
    private function get_class_reference( $class_name ) {
        if ( 'tailwind' === $this->style_format ) {
            return $this->get_tailwind_classes( $class_name );
        }

        return "styles.{$class_name}";
    }

    /**
     * Obtiene clases Tailwind equivalentes
     *
     * @param string $class_name Nombre de la clase.
     * @return string
     */
    private function get_tailwind_classes( $class_name ) {
        $tailwind_map = array(
            'hero'        => '"py-20 px-5"',
            'container'   => '"max-w-6xl mx-auto"',
            'content'     => '"max-w-3xl mx-auto text-center"',
            'title'       => '"text-5xl font-bold text-gray-900 mb-4"',
            'subtitle'    => '"text-xl text-gray-600 mb-8"',
            'button'      => '"inline-block px-6 py-3 bg-blue-500 text-white rounded-lg font-semibold hover:bg-blue-600 transition"',
            'grid'        => '"grid grid-cols-1 md:grid-cols-3 gap-6"',
            'card'        => '"bg-white p-6 rounded-xl shadow-md"',
            'icon'        => '"text-5xl mb-4"',
            'cardTitle'   => '"text-xl font-semibold text-gray-900 mb-2"',
            'description' => '"text-gray-600"',
        );

        return $tailwind_map[ $class_name ] ?? '"' . $class_name . '"';
    }

    /**
     * Escapa texto para JSX
     *
     * @param string $text Texto a escapar.
     * @return string
     */
    private function escape_jsx( $text ) {
        return str_replace(
            array( '"', "'", "\n", "\r" ),
            array( '\\"', "\\'", ' ', '' ),
            $text
        );
    }
}
