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
    const PAGE_SLUG = 'flavor-documentacion';

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
            __('Documentación', 'flavor-chat-ia'),
            __('📚 Documentación', 'flavor-chat-ia'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render_page']
        );
    }

    /**
     * Encola assets
     */
    public function enqueue_assets($hook) {
        // Detectar tanto el slug antiguo como el nuevo
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($current_page !== 'flavor-documentation' && strpos($hook, self::PAGE_SLUG) === false) {
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
        $current_doc = isset($_GET['doc']) ? sanitize_file_name($_GET['doc']) : 'indice';
        $docs = $this->get_available_docs();
        ?>
        <div class="wrap flavor-docs-wrap">
            <h1>
                <span class="dashicons dashicons-book"></span>
                <?php _e('Documentación de Flavor Platform', 'flavor-chat-ia'); ?>
            </h1>

            <div class="flavor-docs-container">
                <!-- Sidebar con índice -->
                <div class="flavor-docs-sidebar">
                    <div class="docs-nav">
                        <h3><?php _e('Índice', 'flavor-chat-ia'); ?></h3>

                        <div class="docs-section">
                            <h4><?php _e('🚀 Inicio', 'flavor-chat-ia'); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'indice' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('indice'); ?>">
                                        <?php _e('Índice General', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'inicio-rapido' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('inicio-rapido'); ?>">
                                        <?php _e('Guía de Inicio Rápido', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('🔗 Sistemas Modulares', 'flavor-chat-ia'); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'integraciones' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('integraciones'); ?>">
                                        <?php _e('Integraciones entre Módulos', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'red-nodos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('red-nodos'); ?>">
                                        <?php _e('Red de Nodos Federada', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'funcionalidades' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('funcionalidades'); ?>">
                                        <?php _e('Funcionalidades Compartidas', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('📖 Referencia', 'flavor-chat-ia'); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'componentes' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('componentes'); ?>">
                                        <?php _e('Componentes y Shortcodes', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'ejemplo-modulo' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('ejemplo-modulo'); ?>">
                                        <?php _e('Ejemplo de Módulo', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'guia-modulos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('guia-modulos'); ?>">
                                        <?php _e('Guía de Módulos', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="docs-section">
                            <h4><?php _e('⚙️ Técnico', 'flavor-chat-ia'); ?></h4>
                            <ul>
                                <li class="<?php echo $current_doc === 'permisos' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('permisos'); ?>">
                                        <?php _e('Sistema de Permisos', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                                <li class="<?php echo $current_doc === 'api' ? 'active' : ''; ?>">
                                    <a href="<?php echo $this->get_doc_url('api'); ?>">
                                        <?php _e('APIs REST', 'flavor-chat-ia'); ?>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div class="docs-version">
                        <small>
                            <?php printf(__('Flavor Platform v%s', 'flavor-chat-ia'), FLAVOR_CHAT_IA_VERSION); ?>
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
                'title' => __('Índice de Documentación', 'flavor-chat-ia'),
                'file'  => 'INDICE-DOCUMENTACION.md',
            ],
            'inicio-rapido' => [
                'title' => __('Guía de Inicio Rápido', 'flavor-chat-ia'),
                'file'  => 'GUIA-INICIO-RAPIDO.md',
            ],
            'integraciones' => [
                'title' => __('Integraciones entre Módulos', 'flavor-chat-ia'),
                'file'  => 'INTEGRACIONES.md',
            ],
            'red-nodos' => [
                'title' => __('Red de Nodos Federada', 'flavor-chat-ia'),
                'file'  => 'RED-DE-NODOS.md',
            ],
            'funcionalidades' => [
                'title' => __('Funcionalidades Compartidas', 'flavor-chat-ia'),
                'file'  => 'FUNCIONALIDADES-COMPARTIDAS.md',
            ],
            'componentes' => [
                'title' => __('Componentes y Shortcodes', 'flavor-chat-ia'),
                'file'  => 'COMPONENTES-NUEVOS.md',
            ],
            'ejemplo-modulo' => [
                'title' => __('Ejemplo de Módulo Completo', 'flavor-chat-ia'),
                'file'  => 'EJEMPLO-MODULO-COMPLETO.md',
            ],
            'guia-modulos' => [
                'title' => __('Guía de Módulos', 'flavor-chat-ia'),
                'file'  => 'GUIA_MODULOS.md',
            ],
            'permisos' => [
                'title' => __('Sistema de Permisos', 'flavor-chat-ia'),
                'file'  => 'PERMISSIONS-USAGE.md',
            ],
            'api' => [
                'title' => __('APIs REST', 'flavor-chat-ia'),
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

        echo '<div class="doc-body">';

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
            printf(__('Archivo no encontrado: %s', 'flavor-chat-ia'), esc_html($filename));
            echo '</p></div>';
            return;
        }

        $content = file_get_contents($filepath);
        $html = $this->parse_markdown($content);

        echo $html;
    }

    /**
     * Parser simple de Markdown
     */
    private function parse_markdown($text) {
        // Escapar HTML
        $text = esc_html($text);

        // Headers
        $text = preg_replace('/^######\s*(.+)$/m', '<h6>$1</h6>', $text);
        $text = preg_replace('/^#####\s*(.+)$/m', '<h5>$1</h5>', $text);
        $text = preg_replace('/^####\s*(.+)$/m', '<h4>$1</h4>', $text);
        $text = preg_replace('/^###\s*(.+)$/m', '<h3>$1</h3>', $text);
        $text = preg_replace('/^##\s*(.+)$/m', '<h2>$1</h2>', $text);
        $text = preg_replace('/^#\s*(.+)$/m', '<h1>$1</h1>', $text);

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
            <h3><?php _e('Endpoints REST Disponibles', 'flavor-chat-ia'); ?></h3>

            <div class="api-section">
                <h4><?php _e('Sistema de Integraciones', 'flavor-chat-ia'); ?></h4>
                <p><code>Base: /wp-json/flavor-integration/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/providers</code></td>
                            <td><?php _e('Lista de providers registrados', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/consumers</code></td>
                            <td><?php _e('Lista de consumers registrados', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/network-content</code></td>
                            <td><?php _e('Contenido compartido en la red', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/network-stats</code></td>
                            <td><?php _e('Estadísticas de contenido de red', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Funcionalidades Compartidas', 'flavor-chat-ia'); ?></h4>
                <p><code>Base: /wp-json/flavor-features/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method post">POST</span></td>
                            <td><code>/interact</code></td>
                            <td><?php _e('Realizar interacción (favorito, rating, etc)', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/entity/{type}/{id}</code></td>
                            <td><?php _e('Obtener interacciones de una entidad', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/user/interactions</code></td>
                            <td><?php _e('Interacciones del usuario actual', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Red de Nodos (addon)', 'flavor-chat-ia'); ?></h4>
                <p><code>Base: /wp-json/flavor-network/v1/</code></p>

                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Método', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Endpoint', 'flavor-chat-ia'); ?></th>
                            <th><?php _e('Descripción', 'flavor-chat-ia'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/directory</code></td>
                            <td><?php _e('Directorio de nodos', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/node/{slug}</code></td>
                            <td><?php _e('Perfil de un nodo', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/map</code></td>
                            <td><?php _e('Datos para mapa de nodos', 'flavor-chat-ia'); ?></td>
                        </tr>
                        <tr>
                            <td><span class="method get">GET</span></td>
                            <td><code>/nearby</code></td>
                            <td><?php _e('Nodos cercanos por geolocalización', 'flavor-chat-ia'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="api-section">
                <h4><?php _e('Probar API', 'flavor-chat-ia'); ?></h4>
                <p><?php _e('Puedes probar los endpoints directamente:', 'flavor-chat-ia'); ?></p>
                <ul>
                    <li>
                        <a href="<?php echo rest_url('flavor-integration/v1/network-stats'); ?>" target="_blank">
                            <?php _e('Ver estadísticas de red', 'flavor-chat-ia'); ?> →
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo rest_url('flavor-features/v1/entity/post/1'); ?>" target="_blank">
                            <?php _e('Ver interacciones de ejemplo', 'flavor-chat-ia'); ?> →
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
