<?php
/**
 * Visual Builder Pro - Code Exporter
 *
 * Controlador principal para exportación de código React/Vue.
 *
 * @package FlavorChatIA
 * @subpackage Visual_Builder_Pro
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Controlador de exportación de código
 */
class Flavor_VBP_Code_Exporter {

    /**
     * Instancia singleton
     *
     * @var Flavor_VBP_Code_Exporter|null
     */
    private static $instancia = null;

    /**
     * Generador React
     *
     * @var Flavor_VBP_React_Generator|null
     */
    private $react_generator = null;

    /**
     * Generador Vue
     *
     * @var Flavor_VBP_Vue_Generator|null
     */
    private $vue_generator = null;

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_VBP_Code_Exporter
     */
    public static function get_instance() {
        if ( null === self::$instancia ) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->load_dependencies();
        $this->register_rest_routes();
    }

    /**
     * Carga dependencias
     */
    private function load_dependencies() {
        require_once __DIR__ . '/class-vbp-react-generator.php';
        require_once __DIR__ . '/class-vbp-vue-generator.php';

        $this->react_generator = Flavor_VBP_React_Generator::get_instance();
        $this->vue_generator = Flavor_VBP_Vue_Generator::get_instance();
    }

    /**
     * Registra rutas REST
     */
    private function register_rest_routes() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Registra las rutas REST
     */
    public function register_routes() {
        $namespace = 'flavor-vbp/v1';

        // Exportar código
        register_rest_route(
            $namespace,
            '/export-code',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'export_code' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'post_id'      => array(
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ),
                    'framework'    => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array( 'react', 'vue' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'style_format' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'enum'              => array( 'css', 'tailwind', 'styled-components' ),
                        'default'           => 'css',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Previsualizar código de un elemento
        register_rest_route(
            $namespace,
            '/preview-code',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'preview_code' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'element'      => array(
                        'required'          => true,
                        'type'              => 'object',
                    ),
                    'framework'    => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array( 'react', 'vue' ),
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'style_format' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => 'css',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
            )
        );

        // Obtener formatos disponibles
        register_rest_route(
            $namespace,
            '/export-formats',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_export_formats' ),
                'permission_callback' => array( $this, 'check_permission' ),
            )
        );
    }

    /**
     * Verifica permisos
     *
     * @return bool
     */
    public function check_permission() {
        return current_user_can( 'edit_posts' );
    }

    /**
     * Exporta código de un documento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function export_code( $request ) {
        $post_id = $request->get_param( 'post_id' );
        $framework = $request->get_param( 'framework' );
        $style_format = $request->get_param( 'style_format' );

        // Obtener datos del documento
        if ( ! class_exists( 'Flavor_VBP_Editor' ) ) {
            return new WP_Error(
                'editor_not_available',
                __( 'Editor no disponible', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 500 )
            );
        }

        $editor = Flavor_VBP_Editor::get_instance();
        $document = $editor->obtener_datos_documento( $post_id );

        if ( empty( $document ) || empty( $document['elements'] ) ) {
            return new WP_Error(
                'empty_document',
                __( 'El documento está vacío', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                array( 'status' => 400 )
            );
        }

        // Generar código según framework
        $files = array();

        if ( 'react' === $framework ) {
            $files = $this->react_generator->generate_from_document( $document, $style_format );
        } elseif ( 'vue' === $framework ) {
            $files = $this->vue_generator->generate_from_document( $document, $style_format );
        }

        // Generar archivo ZIP
        $zip_url = $this->create_zip( $files, $post_id, $framework );

        return new WP_REST_Response(
            array(
                'success'   => true,
                'files'     => $files,
                'zip_url'   => $zip_url,
                'framework' => $framework,
                'format'    => $style_format,
            ),
            200
        );
    }

    /**
     * Previsualiza código de un elemento
     *
     * @param WP_REST_Request $request Petición REST.
     * @return WP_REST_Response|WP_Error
     */
    public function preview_code( $request ) {
        $element = $request->get_param( 'element' );
        $framework = $request->get_param( 'framework' );
        $style_format = $request->get_param( 'style_format' );

        $files = array();

        if ( 'react' === $framework ) {
            $files = $this->react_generator->generate( $element, $style_format );
        } elseif ( 'vue' === $framework ) {
            $files = $this->vue_generator->generate( $element, $style_format );
        }

        return new WP_REST_Response(
            array(
                'success'   => true,
                'files'     => $files,
                'framework' => $framework,
            ),
            200
        );
    }

    /**
     * Obtiene formatos de exportación disponibles
     *
     * @return WP_REST_Response
     */
    public function get_export_formats() {
        return new WP_REST_Response(
            array(
                'frameworks'    => array(
                    'react' => array(
                        'id'          => 'react',
                        'name'        => 'React',
                        'description' => __( 'Componentes funcionales con hooks', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'extension'   => 'jsx',
                    ),
                    'vue'   => array(
                        'id'          => 'vue',
                        'name'        => 'Vue 3',
                        'description' => __( 'Single File Components con Composition API', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'extension'   => 'vue',
                    ),
                ),
                'style_formats' => array(
                    'css'               => array(
                        'id'          => 'css',
                        'name'        => 'CSS Modules',
                        'description' => __( 'CSS scoped por componente', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'tailwind'          => array(
                        'id'          => 'tailwind',
                        'name'        => 'Tailwind CSS',
                        'description' => __( 'Clases utility-first', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                    ),
                    'styled-components' => array(
                        'id'          => 'styled-components',
                        'name'        => 'Styled Components',
                        'description' => __( 'CSS-in-JS (solo React)', FLAVOR_PLATFORM_TEXT_DOMAIN ),
                        'react_only'  => true,
                    ),
                ),
            ),
            200
        );
    }

    /**
     * Crea archivo ZIP con los archivos generados
     *
     * @param array  $files Archivos a incluir.
     * @param int    $post_id ID del post.
     * @param string $framework Framework usado.
     * @return string URL del archivo ZIP.
     */
    private function create_zip( $files, $post_id, $framework ) {
        // Verificar que ZipArchive esté disponible
        if ( ! class_exists( 'ZipArchive' ) ) {
            return '';
        }

        $upload_dir = wp_upload_dir();
        $export_dir = $upload_dir['basedir'] . '/flavor-exports';

        // Crear directorio si no existe
        if ( ! file_exists( $export_dir ) ) {
            wp_mkdir_p( $export_dir );

            // Añadir .htaccess para permitir descargas
            file_put_contents(
                $export_dir . '/.htaccess',
                "Order Allow,Deny\nAllow from all\n"
            );
        }

        // Limpiar exports antiguos (más de 1 hora)
        $this->cleanup_old_exports( $export_dir );

        // Nombre del archivo
        $timestamp = time();
        $zip_filename = "vbp-export-{$framework}-{$post_id}-{$timestamp}.zip";
        $zip_path = $export_dir . '/' . $zip_filename;

        // Crear ZIP
        $zip = new ZipArchive();
        if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
            return '';
        }

        // Añadir archivos
        foreach ( $files as $file ) {
            $folder = isset( $file['folder'] ) && ! empty( $file['folder'] )
                ? $file['folder'] . '/'
                : '';

            $zip->addFromString( $folder . $file['name'], $file['content'] );
        }

        // Añadir README
        $readme = $this->generate_readme( $framework, $files );
        $zip->addFromString( 'README.md', $readme );

        // Añadir package.json básico
        $package_json = $this->generate_package_json( $framework );
        $zip->addFromString( 'package.json', $package_json );

        $zip->close();

        return $upload_dir['baseurl'] . '/flavor-exports/' . $zip_filename;
    }

    /**
     * Limpia exports antiguos
     *
     * @param string $directory Directorio de exports.
     */
    private function cleanup_old_exports( $directory ) {
        $files = glob( $directory . '/vbp-export-*.zip' );
        $max_age = 3600; // 1 hora

        foreach ( $files as $file ) {
            if ( filemtime( $file ) < ( time() - $max_age ) ) {
                unlink( $file );
            }
        }
    }

    /**
     * Genera README para el export
     *
     * @param string $framework Framework.
     * @param array  $files Archivos incluidos.
     * @return string
     */
    private function generate_readme( $framework, $files ) {
        $framework_name = 'react' === $framework ? 'React' : 'Vue 3';
        $date = current_time( 'Y-m-d H:i:s' );

        $readme = "# Componentes {$framework_name}\n\n";
        $readme .= "Generado con Flavor Visual Builder Pro\n";
        $readme .= "Fecha: {$date}\n\n";

        $readme .= "## Instalación\n\n";
        $readme .= "```bash\nnpm install\n```\n\n";

        $readme .= "## Uso\n\n";

        if ( 'react' === $framework ) {
            $readme .= "```jsx\nimport Page from './Page';\n\nfunction App() {\n  return <Page />;\n}\n```\n\n";
        } else {
            $readme .= "```vue\n<script setup>\nimport PageView from './PageView.vue'\n</script>\n\n<template>\n  <PageView />\n</template>\n```\n\n";
        }

        $readme .= "## Archivos incluidos\n\n";

        foreach ( $files as $file ) {
            $folder = isset( $file['folder'] ) && ! empty( $file['folder'] ) ? $file['folder'] . '/' : '';
            $readme .= "- `{$folder}{$file['name']}`\n";
        }

        $readme .= "\n## Variables CSS\n\n";
        $readme .= "Los componentes usan variables CSS de Flavor. Asegúrate de definirlas en tu CSS:\n\n";
        $readme .= "```css\n:root {\n";
        $readme .= "  --flavor-primary: #3b82f6;\n";
        $readme .= "  --flavor-secondary: #8b5cf6;\n";
        $readme .= "  --flavor-text: #1f2937;\n";
        $readme .= "  --flavor-text-muted: #6b7280;\n";
        $readme .= "  --flavor-bg: #ffffff;\n";
        $readme .= "}\n```\n";

        return $readme;
    }

    /**
     * Genera package.json básico
     *
     * @param string $framework Framework.
     * @return string
     */
    private function generate_package_json( $framework ) {
        $package = array(
            'name'        => 'flavor-vbp-export',
            'version'     => '1.0.0',
            'private'     => true,
        );

        if ( 'react' === $framework ) {
            $package['dependencies'] = array(
                'react'     => '^18.2.0',
                'react-dom' => '^18.2.0',
            );
        } else {
            $package['dependencies'] = array(
                'vue' => '^3.3.0',
            );
        }

        return json_encode( $package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
    }

    /**
     * Obtiene información del mapeo de componentes
     *
     * @return array
     */
    public function get_component_mapping() {
        return array(
            'hero'         => array( 'react' => 'Hero', 'vue' => 'HeroSection' ),
            'features'     => array( 'react' => 'Features', 'vue' => 'FeaturesGrid' ),
            'cta'          => array( 'react' => 'CallToAction', 'vue' => 'CallToAction' ),
            'testimonials' => array( 'react' => 'Testimonials', 'vue' => 'TestimonialsSlider' ),
            'stats'        => array( 'react' => 'Stats', 'vue' => 'StatsCounter' ),
            'pricing'      => array( 'react' => 'Pricing', 'vue' => 'PricingTable' ),
            'faq'          => array( 'react' => 'FAQ', 'vue' => 'FaqAccordion' ),
            'gallery'      => array( 'react' => 'Gallery', 'vue' => 'ImageGallery' ),
            'team'         => array( 'react' => 'Team', 'vue' => 'TeamGrid' ),
            'contact'      => array( 'react' => 'Contact', 'vue' => 'ContactForm' ),
            'columns'      => array( 'react' => 'Grid', 'vue' => 'GridLayout' ),
            'text'         => array( 'react' => 'Text', 'vue' => 'TextBlock' ),
            'button'       => array( 'react' => 'Button', 'vue' => 'BaseButton' ),
            'image'        => array( 'react' => 'Image', 'vue' => 'ResponsiveImage' ),
        );
    }
}
