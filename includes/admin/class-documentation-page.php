<?php
/**
 * Página de Documentación en Admin
 *
 * Proporciona acceso a la documentación técnica desde el panel de administración.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para la página de documentación en admin
 */
class Flavor_Documentation_Page {

    /**
     * Instancia singleton
     */
    private static $instance = null;

    /**
     * Slug de la página
     */
    const PAGE_SLUG = 'flavor-platform-docs';
    const PAGE_SLUG_LEGACY = 'flavor-documentation';

    /**
     * Obtiene la instancia singleton
     */
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        // El menú lo registra Admin_Menu_Manager, no necesitamos duplicar
        // add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    /**
     * Añade la página al menú
     */
    public function add_menu_page() {
        add_submenu_page(
            'flavor-platform',
            __('Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            __('📚 Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Encola assets
     */
    public function enqueue_assets($hook) {
        // Detectar tanto el slug canonico como el alias heredado
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if (
            $current_page !== self::PAGE_SLUG
            && $current_page !== self::PAGE_SLUG_LEGACY
            && $current_page !== 'flavor-documentacion'
            && $current_page !== 'flavor-docs'
            && strpos($hook, self::PAGE_SLUG) === false
            && strpos($hook, self::PAGE_SLUG_LEGACY) === false
        ) {
            return;
        }

        wp_enqueue_style(
            'flavor-docs-page',
            FLAVOR_CHAT_IA_URL . 'assets/css/admin-docs.css',
            [],
            FLAVOR_CHAT_IA_VERSION
        );

        // Prism.js para syntax highlighting
        wp_enqueue_style(
            'prism-css',
            'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-tomorrow.min.css',
            [],
            '1.29.0'
        );

        wp_enqueue_script(
            'prism-js',
            'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js',
            [],
            '1.29.0',
            true
        );

        wp_enqueue_script(
            'prism-php',
            'https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js',
            ['prism-js'],
            '1.29.0',
            true
        );
    }

    /**
     * Renderiza la página
     */
    public function render_page() {
        if (class_exists('Flavor_Documentation_Admin')) {
            Flavor_Documentation_Admin::get_instance()->render_page();
            return;
        }

        $current_doc = isset($_GET['doc']) ? sanitize_file_name($_GET['doc']) : 'indice';
        $docs = $this->get_available_docs();
        ?>
        <div class="wrap flavor-docs-wrap">
            <h1>
                <span class="dashicons dashicons-book"></span>
                <?php _e('Documentación de Flavor Platform', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </h1>

            <div class="notice notice-info" style="margin: 16px 0 20px;">
                <p>
                    <?php _e('Auditoría vigente de estado real:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                    <strong>2026-03-04</strong>.
                    <?php _e('Las referencias históricas anteriores deben leerse como contexto, no como foto final del sistema.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </p>
            </div>

            <div class="flavor-docs-container">
                <!-- Sidebar con índice -->
                <div class="flavor-docs-sidebar">
                    <div class="docs-nav">
                        <h3><?php _e('Índice', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

                        <div class="docs-section">
                            <h4><?php _e('Inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'indice' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('indice'); ?>">
                                        <?php _e('Índice General', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'filosofia' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('filosofia'); ?>">
                                        <?php _e('Filosofía del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'inicio-rapido' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('inicio-rapido'); ?>">
                                        <?php _e('Guía de Inicio Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'plugin-completo' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('plugin-completo'); ?>">
                                        <?php _e('Plugin Completo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('Operación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'admin' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('admin'); ?>">
                                        <?php _e('Guía de Administración', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'estado-real' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('estado-real'); ?>">
                                        <?php _e('Estado Real y Límites', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('Arquitectura', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'arquitectura' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('arquitectura'); ?>">
                                        <?php _e('Arquitectura del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'integraciones' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('integraciones'); ?>">
                                        <?php _e('Integraciones entre Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'funcionalidades' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('funcionalidades'); ?>">
                                        <?php _e('Funcionalidades Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'guia-modulos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('guia-modulos'); ?>">
                                        <?php _e('Guía de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'catalogo-modulos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('catalogo-modulos'); ?>">
                                        <?php _e('Catálogo Detallado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('Técnico', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'permisos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('permisos'); ?>">
                                        <?php _e('Sistema de Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'api' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('api'); ?>">
                                        <?php _e('APIs REST', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="docs-version">
                        <small>
                            <?php printf(__('Flavor Platform v%s', FLAVOR_PLATFORM_TEXT_DOMAIN), FLAVOR_CHAT_IA_VERSION); ?>
                        </small>
                    </div>
                </div>

                <!-- Contenido principal -->
                <div class="flavor-docs-content">
                    <?php $this->render_doc_content($current_doc); ?>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Obtiene URL de un documento
     */
    private function get_doc_url($doc) {
        return admin_url('admin.php?page=' . self::PAGE_SLUG . '&doc=' . $doc);
    }

    /**
     * Obtiene documentos disponibles
     */
    private function get_available_docs() {
        return [
            'indice' => [
                'title' => __('Índice de Documentación', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'INDICE-DOCUMENTACION.md',
            ],
            'filosofia' => [
                'title' => __('Filosofía del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'FILOSOFIA-PLUGIN.md',
            ],
            'inicio-rapido' => [
                'title' => __('Guía de Inicio Rápido', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'GUIA-INICIO-RAPIDO.md',
            ],
            'plugin-completo' => [
                'title' => __('Plugin Completo', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'PLUGIN-COMPLETO.md',
            ],
            'admin' => [
                'title' => __('Guía de Administración', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'GUIA-ADMINISTRACION.md',
            ],
            'estado-real' => [
                'title' => __('Estado Real y Límites', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'ESTADO-REAL-PLUGIN.md',
            ],
            'arquitectura' => [
                'title' => __('Arquitectura del Plugin', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'ARQUITECTURA-PLUGIN.md',
            ],
            'integraciones' => [
                'title' => __('Integraciones entre Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'INTEGRACIONES.md',
            ],
            'funcionalidades' => [
                'title' => __('Funcionalidades Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'FUNCIONALIDADES-COMPARTIDAS.md',
            ],
            'guia-modulos' => [
                'title' => __('Guía de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'GUIA_MODULOS.md',
            ],
            'catalogo-modulos' => [
                'title' => __('Catálogo de Módulos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'CATALOGO-MODULOS.md',
            ],
            'permisos' => [
                'title' => __('Sistema de Permisos', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'file'  => 'PERMISSIONS-USAGE.md',
            ],
            'api' => [
                'title' => __('APIs REST', FLAVOR_PLATFORM_TEXT_DOMAIN),
                'callback' => 'render_api_docs',
            ],
        ];
    }

    /**
     * Renderiza el contenido de un documento
     */
    private function render_doc_content($doc_id) {
        $docs = $this->get_available_docs();

        if (!isset($docs[$doc_id])) {
            $doc_id = 'indice';
        }

        $doc = $docs[$doc_id];

        echo '<div class="doc-header">';
        echo '<h2>' . esc_html($doc['title']) . '</h2>';
        echo '</div>';

        $doc_body_class = 'doc-body doc-body--' . sanitize_html_class($doc_id);
        echo '<div class="' . esc_attr($doc_body_class) . '">';

        if (isset($doc['callback'])) {
            call_user_func([$this, $doc['callback']]);
        } elseif (isset($doc['file'])) {
            $this->render_markdown_file($doc['file']);
        }

        echo '</div>';
    }

    /**
     * Renderiza un archivo Markdown
     */
    private function render_markdown_file($filename) {
        $filepath = FLAVOR_CHAT_IA_PATH . 'docs/' . $filename;

        if (!file_exists($filepath)) {
            echo '<div class="notice notice-error"><p>';
            printf(__('Archivo no encontrado: %s', FLAVOR_PLATFORM_TEXT_DOMAIN), esc_html($filename));
            echo '</p></div>';
            return;
        }

        $content = file_get_contents($filepath);
        $html = $this->parse_markdown($content);

        if ($filename === 'CATALOGO-MODULOS.md') {
            $html = str_replace(
                [
                    '<code>Repasado intensivo</code>',
                    '<code>Parcial</code>',
                    '<code>Pendiente / no prioritario</code>',
                ],
                [
                    '<span class="flavor-docs-status flavor-docs-status--intensive">Repasado intensivo</span>',
                    '<span class="flavor-docs-status flavor-docs-status--partial">Parcial</span>',
                    '<span class="flavor-docs-status flavor-docs-status--pending">Pendiente / no prioritario</span>',
                ],
                $html
            );
        }

        echo $html;
    }

    /**
     * Parser simple de Markdown
     */
    private function parse_markdown($text) {
        // Escapar HTML
        $text = esc_html($text);

        // Headers
        $text = preg_replace_callback('/^######\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h6 id="' . esc_attr($id) . '">' . $matches[1] . '</h6>';
        }, $text);
        $text = preg_replace_callback('/^#####\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h5 id="' . esc_attr($id) . '">' . $matches[1] . '</h5>';
        }, $text);
        $text = preg_replace_callback('/^####\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h4 id="' . esc_attr($id) . '">' . $matches[1] . '</h4>';
        }, $text);
        $text = preg_replace_callback('/^###\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h3 id="' . esc_attr($id) . '">' . $matches[1] . '</h3>';
        }, $text);
        $text = preg_replace_callback('/^##\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h2 id="' . esc_attr($id) . '">' . $matches[1] . '</h2>';
        }, $text);
        $text = preg_replace_callback('/^#\s*(.+)$/m', function($matches) {
            $id = $this->slugify_heading($matches[1]);
            return '<h1 id="' . esc_attr($id) . '">' . $matches[1] . '</h1>';
        }, $text);

        // Code blocks con lenguaje
        $text = preg_replace_callback('/```(\w+)?\n(.*?)```/s', function($matches) {
            $lang = !empty($matches[1]) ? $matches[1] : 'plaintext';
            $code = trim($matches[2]);
            return '<pre><code class="language-' . $lang . '">' . $code . '</code></pre>';
        }, $text);

        // Inline code
        $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text);

        // Bold
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);

        // Italic
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);

        // Links
        $text = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank">$1</a>', $text);

        // Tables
        $text = $this->parse_tables($text);

        // Horizontal rules
        $text = preg_replace('/^---+$/m', '<hr>', $text);

        // Lists (unordered)
        $text = preg_replace('/^[\-\*]\s+(.+)$/m', '<li>$1</li>', $text);
        $text = preg_replace('/(<li>.*<\/li>\n?)+/s', '<ul>$0</ul>', $text);

        // Paragraphs
        $text = preg_replace('/\n\n+/', '</p><p>', $text);
        $text = '<p>' . $text . '</p>';

        // Clean up
        $text = str_replace('<p></p>', '', $text);
        $text = str_replace('<p><h', '<h', $text);
        $text = str_replace('</h1></p>', '</h1>', $text);
        $text = str_replace('</h2></p>', '</h2>', $text);
        $text = str_replace('</h3></p>', '</h3>', $text);
        $text = str_replace('</h4></p>', '</h4>', $text);
        $text = str_replace('<p><pre>', '<pre>', $text);
        $text = str_replace('</pre></p>', '</pre>', $text);
        $text = str_replace('<p><ul>', '<ul>', $text);
        $text = str_replace('</ul></p>', '</ul>', $text);
        $text = str_replace('<p><hr></p>', '<hr>', $text);
        $text = str_replace('<p><table', '<table', $text);
        $text = str_replace('</table></p>', '</table>', $text);

        return $text;
    }

    /**
     * Genera un id estable para encabezados Markdown.
     *
     * @param string $text
     * @return string
     */
    private function slugify_heading($text) {
        $text = wp_strip_all_tags(html_entity_decode((string) $text, ENT_QUOTES, 'UTF-8'));
        $slug = sanitize_title($text);
        return $slug ?: 'section';
    }

    /**
     * Parser de tablas Markdown
     */
    private function parse_tables($text) {
        $lines = explode("\n", $text);
        $in_table = false;
        $table_html = '';
        $result = [];

        foreach ($lines as $line) {
            // Detectar fila de tabla
            if (preg_match('/^\|(.+)\|$/', trim($line), $matches)) {
                if (!$in_table) {
                    $in_table = true;
                    $table_html = '<table class="widefat striped"><thead>';
                }

                $cells = array_map('trim', explode('|', trim($matches[1])));

                // Detectar separador
                if (preg_match('/^[\-\|:\s]+$/', $matches[1])) {
                    $table_html .= '</thead><tbody>';
                } else {
                    $table_html .= '<tr>';
                    foreach ($cells as $cell) {
                        $tag = strpos($table_html, '<tbody>') === false ? 'th' : 'td';
                        $table_html .= "<$tag>$cell</$tag>";
                    }
                    $table_html .= '</tr>';
                }
            } else {
                if ($in_table) {
                    $table_html .= '</tbody></table>';
                    $result[] = $table_html;
                    $in_table = false;
                    $table_html = '';
                }
                $result[] = $line;
            }
        }

        if ($in_table) {
            $table_html .= '</tbody></table>';
            $result[] = $table_html;
        }

        return implode("\n", $result);
    }

    /**
     * Renderiza documentación de APIs
     */
    private function render_api_docs() {
        ?>
        <div class="api-docs">
            <h3><?php _e('Endpoints REST Disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>

            <div class="api-section">
                <h4><?php _e('Sistema de Integraciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><code>Base: /wp-json/flavor-integration/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/providers</code></td>
                            <td><?php _e('Lista de providers registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/consumers</code></td>
                            <td><?php _e('Lista de consumers registrados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/network-content</code></td>
                            <td><?php _e('Contenido compartido en la red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/network-stats</code></td>
                            <td><?php _e('Estadísticas de contenido de red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Funcionalidades Compartidas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><code>Base: /wp-json/flavor-features/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method post">POST</span></td>
                            <td><code>/interact</code></td>
                            <td><?php _e('Realizar interacción (favorito, rating, etc)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/entity/{type}/{id}</code></td>
                            <td><?php _e('Obtener interacciones de una entidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/user/interactions</code></td>
                            <td><?php _e('Interacciones del usuario actual', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Red de Nodos (addon)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><code>Base: /wp-json/flavor-network/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Endpoint', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                            <th><?php _e('Descripción', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/directory</code></td>
                            <td><?php _e('Directorio de nodos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/node/{slug}</code></td>
                            <td><?php _e('Perfil de un nodo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/map</code></td>
                            <td><?php _e('Datos para mapa de nodos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/nearby</code></td>
                            <td><?php _e('Nodos cercanos por geolocalización', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Probar API', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
                <p><?php _e('Puedes probar los endpoints directamente:', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <ul>
                    <li>
                        <a href="<?php echo rest_url('flavor-integration/v1/network-stats'); ?>" target="_blank">
                            <?php _e('Ver estadísticas de red', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo rest_url('flavor-features/v1/entity/post/1'); ?>" target="_blank">
                            <?php _e('Ver interacciones de ejemplo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?> →
                        </a>
                    </li>
                </ul>
            </div>
        </div>
        <?php
    }
}

// Inicializar
add_action('plugins_loaded', function() {
    if (is_admin()) {
        Flavor_Documentation_Page::get_instance();
    }
});
