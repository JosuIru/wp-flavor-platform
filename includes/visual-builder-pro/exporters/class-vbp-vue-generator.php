<?php
/**
 * Visual Builder Pro - Vue Generator
 *
 * Genera componentes Vue SFC a partir de elementos VBP.
 *
 * @package FlavorChatIA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Generador de componentes Vue
 */
class Flavor_VBP_Vue_Generator {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Vue_Generator|null
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
     * @return Flavor_VBP_Vue_Generator
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Mapeo de tipos VBP a componentes Vue
     *
     * @var array
     */
    private $component_map = array(
        'hero'         => 'HeroSection',
        'features'     => 'FeaturesGrid',
        'cta'          => 'CallToAction',
        'testimonials' => 'TestimonialsSlider',
        'stats'        => 'StatsCounter',
        'pricing'      => 'PricingTable',
        'faq'          => 'FaqAccordion',
        'gallery'      => 'ImageGallery',
        'team'         => 'TeamGrid',
        'contact'      => 'ContactForm',
        'columns'      => 'GridLayout',
        'text'         => 'TextBlock',
        'heading'      => 'HeadingBlock',
        'button'       => 'BaseButton',
        'image'        => 'ResponsiveImage',
        'spacer'       => 'SpacerBlock',
        'divider'      => 'DividerLine',
    );

    /**
     * Genera código Vue para un elemento
     *
     * @param array  $element Elemento VBP.
     * @param string $style_format Formato de estilos (css, tailwind, scoped).
     * @return array Array con archivos generados.
     */
    public function generate( $element, $style_format = 'css' ) {
        $this->style_format = $style_format;

        $type = $element['type'] ?? 'unknown';
        $component_name = $this->get_component_name( $type );

        $files = array();

        // Generar componente Vue SFC
        $vue_content = $this->generate_sfc( $element, $component_name );
        $files[] = array(
            'name'    => "{$component_name}.vue",
            'content' => $vue_content,
            'type'    => 'component',
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
                $file['folder'] = 'components';
                $all_files[] = $file;
            }
        }

        // Generar componente principal Page
        $page_files = $this->generate_page_component( $elements, $components_used, $style_format );
        foreach ( $page_files as $file ) {
            $all_files[] = $file;
        }

        // Generar composables si es necesario
        $composables = $this->generate_composables();
        foreach ( $composables as $file ) {
            $all_files[] = $file;
        }

        return $all_files;
    }

    /**
     * Obtiene nombre del componente Vue
     *
     * @param string $type Tipo de elemento.
     * @return string
     */
    private function get_component_name( $type ) {
        return $this->component_map[ $type ] ?? ucfirst( $type ) . 'Block';
    }

    /**
     * Genera el contenido del Single File Component
     *
     * @param array  $element Elemento.
     * @param string $component_name Nombre del componente.
     * @return string
     */
    private function generate_sfc( $element, $component_name ) {
        $type = $element['type'] ?? 'unknown';
        $data = $element['data'] ?? array();

        $sfc = "<script setup>\n";
        $sfc .= $this->generate_script_setup( $type, $data );
        $sfc .= "</script>\n\n";

        $sfc .= "<template>\n";
        $sfc .= $this->generate_template( $type, $data );
        $sfc .= "</template>\n\n";

        $sfc .= "<style scoped>\n";
        $sfc .= $this->generate_scoped_styles( $type );
        $sfc .= "</style>\n";

        return $sfc;
    }

    /**
     * Genera la sección script setup
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return string
     */
    private function generate_script_setup( $type, $data ) {
        switch ( $type ) {
            case 'hero':
                return $this->get_hero_script( $data );

            case 'features':
                return $this->get_features_script( $data );

            case 'cta':
                return $this->get_cta_script( $data );

            case 'testimonials':
                return $this->get_testimonials_script( $data );

            case 'stats':
                return $this->get_stats_script( $data );

            default:
                return $this->get_generic_script( $data );
        }
    }

    /**
     * Script setup para Hero
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_hero_script( $data ) {
        $title = $this->escape_vue( $data['titulo'] ?? 'Título Principal' );
        $subtitle = $this->escape_vue( $data['subtitulo'] ?? '' );
        $button_text = $this->escape_vue( $data['boton_texto'] ?? 'Comenzar' );

        return <<<SCRIPT
defineProps({
  title: {
    type: String,
    default: '{$title}'
  },
  subtitle: {
    type: String,
    default: '{$subtitle}'
  },
  buttonText: {
    type: String,
    default: '{$button_text}'
  },
  buttonUrl: {
    type: String,
    default: '#'
  },
  backgroundImage: {
    type: String,
    default: ''
  }
})

const emit = defineEmits(['ctaClick'])

SCRIPT;
    }

    /**
     * Script setup para Features
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_features_script( $data ) {
        $title = $this->escape_vue( $data['titulo'] ?? 'Características' );

        return <<<SCRIPT
defineProps({
  title: {
    type: String,
    default: '{$title}'
  },
  items: {
    type: Array,
    default: () => [
      { icon: '⚡', title: 'Rápido', description: 'Implementación en minutos' },
      { icon: '🔒', title: 'Seguro', description: 'Protección garantizada' },
      { icon: '📱', title: 'Responsive', description: 'Funciona en cualquier dispositivo' }
    ]
  }
})

SCRIPT;
    }

    /**
     * Script setup para CTA
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_cta_script( $data ) {
        $title = $this->escape_vue( $data['titulo'] ?? '¿Listo para empezar?' );
        $subtitle = $this->escape_vue( $data['subtitulo'] ?? '' );
        $button_text = $this->escape_vue( $data['boton_texto'] ?? 'Comenzar' );

        return <<<SCRIPT
defineProps({
  title: {
    type: String,
    default: '{$title}'
  },
  subtitle: {
    type: String,
    default: '{$subtitle}'
  },
  buttonText: {
    type: String,
    default: '{$button_text}'
  },
  buttonUrl: {
    type: String,
    default: '#'
  }
})

const emit = defineEmits(['buttonClick'])

const handleClick = () => {
  emit('buttonClick')
}

SCRIPT;
    }

    /**
     * Script setup para Testimonials
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_testimonials_script( $data ) {
        $title = $this->escape_vue( $data['titulo'] ?? 'Lo que dicen nuestros clientes' );

        return <<<SCRIPT
import { ref } from 'vue'

defineProps({
  title: {
    type: String,
    default: '{$title}'
  },
  testimonials: {
    type: Array,
    default: () => [
      {
        quote: 'Increíble experiencia, superó todas mis expectativas.',
        author: 'María García',
        role: 'CEO, TechCorp'
      }
    ]
  }
})

const currentIndex = ref(0)

SCRIPT;
    }

    /**
     * Script setup para Stats
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_stats_script( $data ) {
        return <<<SCRIPT
defineProps({
  items: {
    type: Array,
    default: () => [
      { number: '10K+', label: 'Usuarios' },
      { number: '99%', label: 'Satisfacción' },
      { number: '24/7', label: 'Soporte' }
    ]
  }
})

SCRIPT;
    }

    /**
     * Script setup genérico
     *
     * @param array $data Datos.
     * @return string
     */
    private function get_generic_script( $data ) {
        return <<<SCRIPT
defineProps({
  content: {
    type: String,
    default: ''
  }
})

SCRIPT;
    }

    /**
     * Genera la sección template
     *
     * @param string $type Tipo de elemento.
     * @param array  $data Datos del elemento.
     * @return string
     */
    private function generate_template( $type, $data ) {
        switch ( $type ) {
            case 'hero':
                return $this->get_hero_template();

            case 'features':
                return $this->get_features_template();

            case 'cta':
                return $this->get_cta_template();

            case 'testimonials':
                return $this->get_testimonials_template();

            case 'stats':
                return $this->get_stats_template();

            case 'text':
                return $this->get_text_template();

            case 'heading':
                return $this->get_heading_template();

            case 'button':
                return $this->get_button_template();

            default:
                return $this->get_generic_template();
        }
    }

    /**
     * Template para Hero
     *
     * @return string
     */
    private function get_hero_template() {
        return <<<HTML
  <section class="hero" :style="backgroundImage ? { backgroundImage: \`url(\${backgroundImage})\` } : {}">
    <div class="container">
      <div class="content">
        <h1 class="title">{{ title }}</h1>
        <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>
        <a
          v-if="buttonText"
          :href="buttonUrl"
          class="button"
          @click.prevent="\$emit('ctaClick')"
        >
          {{ buttonText }}
        </a>
      </div>
    </div>
  </section>

HTML;
    }

    /**
     * Template para Features
     *
     * @return string
     */
    private function get_features_template() {
        return <<<HTML
  <section class="features">
    <div class="container">
      <h2 v-if="title" class="title">{{ title }}</h2>
      <div class="grid">
        <div
          v-for="(item, index) in items"
          :key="index"
          class="card"
        >
          <span class="icon">{{ item.icon }}</span>
          <h3 class="card-title">{{ item.title }}</h3>
          <p class="description">{{ item.description }}</p>
        </div>
      </div>
    </div>
  </section>

HTML;
    }

    /**
     * Template para CTA
     *
     * @return string
     */
    private function get_cta_template() {
        return <<<HTML
  <section class="cta">
    <div class="container">
      <h2 class="title">{{ title }}</h2>
      <p v-if="subtitle" class="subtitle">{{ subtitle }}</p>
      <button class="button" @click="handleClick">
        {{ buttonText }}
      </button>
    </div>
  </section>

HTML;
    }

    /**
     * Template para Testimonials
     *
     * @return string
     */
    private function get_testimonials_template() {
        return <<<HTML
  <section class="testimonials">
    <div class="container">
      <h2 v-if="title" class="title">{{ title }}</h2>
      <div class="grid">
        <div
          v-for="(item, index) in testimonials"
          :key="index"
          class="card"
        >
          <blockquote class="quote">"{{ item.quote }}"</blockquote>
          <div class="author">{{ item.author }}</div>
          <div class="role">{{ item.role }}</div>
        </div>
      </div>
    </div>
  </section>

HTML;
    }

    /**
     * Template para Stats
     *
     * @return string
     */
    private function get_stats_template() {
        return <<<HTML
  <section class="stats">
    <div class="container">
      <div class="grid">
        <div
          v-for="(item, index) in items"
          :key="index"
          class="item"
        >
          <div class="number">{{ item.number }}</div>
          <div class="label">{{ item.label }}</div>
        </div>
      </div>
    </div>
  </section>

HTML;
    }

    /**
     * Template para Text
     *
     * @return string
     */
    private function get_text_template() {
        return <<<HTML
  <div class="text-block">
    <slot>{{ content }}</slot>
  </div>

HTML;
    }

    /**
     * Template para Heading
     *
     * @return string
     */
    private function get_heading_template() {
        return <<<HTML
  <component :is="\`h\${level}\`" class="heading">
    <slot>{{ content }}</slot>
  </component>

HTML;
    }

    /**
     * Template para Button
     *
     * @return string
     */
    private function get_button_template() {
        return <<<HTML
  <component
    :is="href ? 'a' : 'button'"
    :href="href"
    class="button"
    :class="[variant]"
    @click="\$emit('click', \$event)"
  >
    <slot />
  </component>

HTML;
    }

    /**
     * Template genérico
     *
     * @return string
     */
    private function get_generic_template() {
        return <<<HTML
  <div class="block">
    <slot>{{ content }}</slot>
  </div>

HTML;
    }

    /**
     * Genera estilos scoped
     *
     * @param string $type Tipo de elemento.
     * @return string
     */
    private function generate_scoped_styles( $type ) {
        switch ( $type ) {
            case 'hero':
                return $this->get_hero_styles();

            case 'features':
                return $this->get_features_styles();

            case 'cta':
                return $this->get_cta_styles();

            case 'testimonials':
                return $this->get_testimonials_styles();

            case 'stats':
                return $this->get_stats_styles();

            default:
                return $this->get_generic_styles();
        }
    }

    /**
     * Estilos para Hero
     *
     * @return string
     */
    private function get_hero_styles() {
        return <<<CSS
.hero {
  padding: 80px 20px;
  background-color: var(--flavor-bg, #ffffff);
  background-size: cover;
  background-position: center;
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
  border: none;
  cursor: pointer;
}

.button:hover {
  opacity: 0.9;
}

@media (max-width: 768px) {
  .title {
    font-size: 2rem;
  }
}

CSS;
    }

    /**
     * Estilos para Features
     *
     * @return string
     */
    private function get_features_styles() {
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
  display: block;
  margin-bottom: 1rem;
}

.card-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--flavor-text, #1f2937);
  margin-bottom: 0.5rem;
}

.description {
  color: var(--flavor-text-muted, #6b7280);
  line-height: 1.6;
  margin: 0;
}

CSS;
    }

    /**
     * Estilos para CTA
     *
     * @return string
     */
    private function get_cta_styles() {
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
     * Estilos para Testimonials
     *
     * @return string
     */
    private function get_testimonials_styles() {
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
  margin: 0 0 1.5rem 0;
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
     * Estilos para Stats
     *
     * @return string
     */
    private function get_stats_styles() {
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
     * Estilos genéricos
     *
     * @return string
     */
    private function get_generic_styles() {
        return <<<CSS
.block {
  padding: 20px;
}

CSS;
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

        // Script imports
        $sfc = "<script setup>\n";
        foreach ( $components_used as $component ) {
            $sfc .= "import {$component} from './components/{$component}.vue'\n";
        }
        $sfc .= "</script>\n\n";

        // Template
        $sfc .= "<template>\n";
        $sfc .= "  <main>\n";

        foreach ( $elements as $element ) {
            $type = $element['type'] ?? 'unknown';
            $component_name = $this->get_component_name( $type );
            $sfc .= "    <{$component_name} />\n";
        }

        $sfc .= "  </main>\n";
        $sfc .= "</template>\n\n";

        // Estilos mínimos
        $sfc .= "<style>\n";
        $sfc .= "main {\n  min-height: 100vh;\n}\n";
        $sfc .= "</style>\n";

        $files[] = array(
            'name'    => 'PageView.vue',
            'content' => $sfc,
            'type'    => 'page',
            'folder'  => '',
        );

        return $files;
    }

    /**
     * Genera composables útiles
     *
     * @return array
     */
    private function generate_composables() {
        $files = array();

        // useDesignTokens composable
        $composable = <<<JS
import { computed } from 'vue'

/**
 * Composable para acceder a los design tokens
 */
export function useDesignTokens() {
  const tokens = {
    colors: {
      primary: getComputedStyle(document.documentElement).getPropertyValue('--flavor-primary').trim() || '#3b82f6',
      secondary: getComputedStyle(document.documentElement).getPropertyValue('--flavor-secondary').trim() || '#8b5cf6',
      text: getComputedStyle(document.documentElement).getPropertyValue('--flavor-text').trim() || '#1f2937',
      textMuted: getComputedStyle(document.documentElement).getPropertyValue('--flavor-text-muted').trim() || '#6b7280',
      background: getComputedStyle(document.documentElement).getPropertyValue('--flavor-bg').trim() || '#ffffff',
    }
  }

  return { tokens }
}

export default useDesignTokens
JS;

        $files[] = array(
            'name'    => 'useDesignTokens.js',
            'content' => $composable,
            'type'    => 'composable',
            'folder'  => 'composables',
        );

        return $files;
    }

    /**
     * Escapa texto para Vue
     *
     * @param string $text Texto a escapar.
     * @return string
     */
    private function escape_vue( $text ) {
        return str_replace(
            array( "'", "\n", "\r" ),
            array( "\\'", ' ', '' ),
            $text
        );
    }
}
