<?php
/**
 * Sistema de Ayuda Contextual con Tooltips
 *
 * Proporciona tooltips inteligentes y ayuda contextual para formularios
 * y elementos de la interfaz de administración
 *
 * @package FlavorPlatform
 * @subpackage Includes
 * @since 3.0.0
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase para gestionar la ayuda contextual
 *
 * @since 3.0.0
 */
class Flavor_Contextual_Help {

    /**
     * Instancia singleton
     *
     * @var Flavor_Contextual_Help
     */
    private static $instancia = null;

    /**
     * Tooltips registrados
     *
     * @var array
     */
    private $tooltips = [];

    /**
     * Metaboxes con tooltips automáticos
     *
     * @var array
     */
    private $metabox_tooltips = [];

    /**
     * Contenido de ayuda por página
     *
     * @var array
     */
    private $page_help = [];

    /**
     * Obtiene la instancia singleton
     *
     * @return Flavor_Contextual_Help
     */
    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    /**
     * Constructor privado
     */
    private function __construct() {
        $this->init_hooks();
        $this->register_default_tooltips();
    }

    /**
     * Inicializa hooks
     *
     * @return void
     */
    private function init_hooks() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('admin_footer', [$this, 'render_tooltips']);
        add_action('admin_head', [$this, 'add_contextual_help_tabs']);
    }

    /**
     * Registra un tooltip para un elemento
     *
     * @param string $selector     Selector CSS del elemento
     * @param string $contenido    Contenido del tooltip (puede ser HTML)
     * @param string $posicion     Posición: top, bottom, left, right
     * @param array  $opciones     Opciones adicionales
     * @return void
     */
    public function register_tooltip($selector, $contenido, $posicion = 'bottom', $opciones = []) {
        $tooltip_id = 'tooltip_' . md5($selector);

        $this->tooltips[$tooltip_id] = array_merge([
            'id' => $tooltip_id,
            'selector' => $selector,
            'contenido' => $contenido,
            'posicion' => $posicion,
            'trigger' => 'hover', // hover, click, focus
            'delay' => 200,
            'max_width' => 300,
            'show_icon' => false,
            'icon_class' => 'dashicons-info-outline',
            'theme' => 'light', // light, dark
            'closable' => false,
            'persistent' => false,
        ], $opciones);
    }

    /**
     * Registra tooltips automáticos para campos de un metabox
     *
     * @param string $metabox_id ID del metabox
     * @param array  $campos     Array de campos con sus descripciones
     * @return void
     */
    public function auto_tooltips_for_metabox($metabox_id, $campos = []) {
        $this->metabox_tooltips[$metabox_id] = $campos;

        // Si no se proporcionan campos, intentar inferirlos del HTML
        if (empty($campos)) {
            add_action('admin_footer', function() use ($metabox_id) {
                $this->generate_metabox_tooltips($metabox_id);
            }, 5);
        } else {
            // Registrar los tooltips proporcionados
            foreach ($campos as $campo_selector => $descripcion) {
                $selector = "#{$metabox_id} {$campo_selector}";
                $this->register_tooltip($selector, $descripcion, 'right', [
                    'trigger' => 'focus',
                    'show_icon' => true,
                ]);
            }
        }
    }

    /**
     * Genera tooltips automáticos para un metabox basándose en labels
     *
     * @param string $metabox_id ID del metabox
     * @return void
     */
    private function generate_metabox_tooltips($metabox_id) {
        // Este método se ejecuta en el footer para tener acceso al DOM renderizado
        // Los tooltips se generan via JavaScript
        ?>
        <script>
        (function($) {
            var metaboxId = '<?php echo esc_js($metabox_id); ?>';
            var $metabox = $('#' + metaboxId);

            if ($metabox.length === 0) return;

            // Buscar labels con atributo title o data-help
            $metabox.find('label[title], label[data-help]').each(function() {
                var $label = $(this);
                var helpText = $label.attr('data-help') || $label.attr('title');
                var forId = $label.attr('for');

                if (helpText && forId) {
                    FlavorContextualHelp.addTooltip('#' + forId, helpText, 'right');
                }
            });

            // Buscar inputs con placeholder como ayuda
            $metabox.find('input[data-help], textarea[data-help], select[data-help]').each(function() {
                var $input = $(this);
                var helpText = $input.attr('data-help');

                if (helpText) {
                    FlavorContextualHelp.addTooltip(this, helpText, 'bottom');
                }
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Registra contenido de ayuda para una página específica
     *
     * @param string $page_slug    Slug de la página admin
     * @param array  $contenido    Array con título y contenido de ayuda
     * @return void
     */
    public function register_page_help($page_slug, $contenido) {
        $this->page_help[$page_slug] = $contenido;
    }

    /**
     * Añade tabs de ayuda contextual al panel de WordPress
     *
     * @return void
     */
    public function add_contextual_help_tabs() {
        $screen = get_current_screen();
        if (!$screen) {
            return;
        }

        // Verificar si hay ayuda registrada para esta página
        foreach ($this->page_help as $page_slug => $help_data) {
            if (strpos($screen->id, $page_slug) !== false) {
                $screen->add_help_tab([
                    'id' => 'flavor_help_' . $page_slug,
                    'title' => $help_data['titulo'] ?? __('Ayuda', 'flavor-chat-ia'),
                    'content' => $help_data['contenido'] ?? '',
                ]);

                if (!empty($help_data['sidebar'])) {
                    $screen->set_help_sidebar($help_data['sidebar']);
                }
            }
        }
    }

    /**
     * Registra tooltips predeterminados para campos comunes
     *
     * @return void
     */
    private function register_default_tooltips() {
        // Tooltips para campos comunes de Flavor Platform
        $tooltips_comunes = [
            // Campos de API
            'input[name*="api_key"]' => [
                'contenido' => __('Tu clave API del proveedor. Se almacena de forma segura y encriptada en la base de datos.', 'flavor-chat-ia'),
                'posicion' => 'right',
            ],
            'select[name*="engine"]' => [
                'contenido' => __('Selecciona el motor de IA que deseas usar. Cada motor tiene diferentes capacidades y costos.', 'flavor-chat-ia'),
                'posicion' => 'bottom',
            ],
            'select[name*="model"]' => [
                'contenido' => __('El modelo determina la calidad y velocidad de las respuestas. Modelos más recientes suelen ser mejores.', 'flavor-chat-ia'),
                'posicion' => 'bottom',
            ],

            // Campos de diseño
            'input[type="color"]' => [
                'contenido' => __('Haz clic para abrir el selector de color. Puedes escribir directamente un código hexadecimal.', 'flavor-chat-ia'),
                'posicion' => 'left',
            ],
            'input[name*="font"]' => [
                'contenido' => __('Selecciona una fuente. Las fuentes de Google se cargan automáticamente.', 'flavor-chat-ia'),
                'posicion' => 'bottom',
            ],

            // Campos de configuración
            'textarea[name*="system_prompt"]' => [
                'contenido' => __('El prompt del sistema define la personalidad y comportamiento del asistente. Sé específico sobre el tono y las capacidades.', 'flavor-chat-ia'),
                'posicion' => 'top',
            ],
            'input[name*="max_tokens"]' => [
                'contenido' => __('Número máximo de tokens en la respuesta. Más tokens permiten respuestas más largas pero cuestan más.', 'flavor-chat-ia'),
                'posicion' => 'right',
            ],
            'input[name*="temperature"]' => [
                'contenido' => __('Controla la creatividad de las respuestas. Valores bajos (0.1-0.3) son más precisos, valores altos (0.7-1.0) más creativos.', 'flavor-chat-ia'),
                'posicion' => 'right',
            ],
        ];

        foreach ($tooltips_comunes as $selector => $config) {
            $this->register_tooltip(
                $selector,
                $config['contenido'],
                $config['posicion'] ?? 'bottom',
                ['trigger' => 'focus', 'show_icon' => true]
            );
        }

        // Registrar ayuda para páginas principales
        $this->register_page_help('flavor-dashboard', [
            'titulo' => __('Dashboard de Flavor', 'flavor-chat-ia'),
            'contenido' => '
                <h3>' . __('Bienvenido al Dashboard', 'flavor-chat-ia') . '</h3>
                <p>' . __('Este es el centro de control de Flavor Platform. Desde aquí puedes:', 'flavor-chat-ia') . '</p>
                <ul>
                    <li>' . __('Ver estadísticas de uso en tiempo real', 'flavor-chat-ia') . '</li>
                    <li>' . __('Acceder rápidamente a las configuraciones principales', 'flavor-chat-ia') . '</li>
                    <li>' . __('Monitorear la actividad reciente', 'flavor-chat-ia') . '</li>
                    <li>' . __('Gestionar addons y módulos', 'flavor-chat-ia') . '</li>
                </ul>
            ',
            'sidebar' => '
                <p><strong>' . __('Recursos', 'flavor-chat-ia') . '</strong></p>
                <p><a href="https://docs.flavor-platform.com" target="_blank">' . __('Documentación', 'flavor-chat-ia') . '</a></p>
                <p><a href="https://support.flavor-platform.com" target="_blank">' . __('Soporte', 'flavor-chat-ia') . '</a></p>
            ',
        ]);

        $this->register_page_help('flavor-modules', [
            'titulo' => __('Módulos del Chat', 'flavor-chat-ia'),
            'contenido' => '
                <h3>' . __('Gestión de Módulos', 'flavor-chat-ia') . '</h3>
                <p>' . __('Los módulos extienden las capacidades del chat IA con funcionalidades especializadas:', 'flavor-chat-ia') . '</p>
                <ul>
                    <li><strong>' . __('Reservas:', 'flavor-chat-ia') . '</strong> ' . __('Sistema de reservas y citas', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Productos:', 'flavor-chat-ia') . '</strong> ' . __('Catálogo y recomendaciones', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Ubicación:', 'flavor-chat-ia') . '</strong> ' . __('Información de tiendas y direcciones', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Horarios:', 'flavor-chat-ia') . '</strong> ' . __('Horarios de atención', 'flavor-chat-ia') . '</li>
                </ul>
                <p>' . __('Activa solo los módulos que necesites para optimizar el rendimiento.', 'flavor-chat-ia') . '</p>
            ',
        ]);

        $this->register_page_help('flavor-design', [
            'titulo' => __('Diseño y Apariencia', 'flavor-chat-ia'),
            'contenido' => '
                <h3>' . __('Personalización Visual', 'flavor-chat-ia') . '</h3>
                <p>' . __('Personaliza la apariencia del chat para que coincida con tu marca:', 'flavor-chat-ia') . '</p>
                <ul>
                    <li><strong>' . __('Colores:', 'flavor-chat-ia') . '</strong> ' . __('Define tu paleta de colores', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Tipografía:', 'flavor-chat-ia') . '</strong> ' . __('Selecciona fuentes para títulos y texto', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Layout:', 'flavor-chat-ia') . '</strong> ' . __('Posición y tamaño del widget', 'flavor-chat-ia') . '</li>
                    <li><strong>' . __('Animaciones:', 'flavor-chat-ia') . '</strong> ' . __('Efectos visuales y transiciones', 'flavor-chat-ia') . '</li>
                </ul>
            ',
        ]);
    }

    /**
     * Carga los assets necesarios
     *
     * @param string $hook_suffix Hook actual
     * @return void
     */
    public function enqueue_assets($hook_suffix) {
        // Solo en páginas de Flavor
        if (strpos($hook_suffix, 'flavor') === false) {
            return;
        }

        wp_enqueue_style('dashicons');

        // El CSS se incluye en onboarding.css
        // El JS se incluye en onboarding.js
    }

    /**
     * Renderiza los tooltips registrados
     *
     * @return void
     */
    public function render_tooltips() {
        if (empty($this->tooltips)) {
            return;
        }

        ?>
        <script>
        (function($) {
            'use strict';

            // Esperar a que el sistema de ayuda contextual esté disponible
            $(document).ready(function() {
                if (typeof FlavorContextualHelp === 'undefined') {
                    console.warn('FlavorContextualHelp no está disponible');
                    return;
                }

                var tooltipsData = <?php echo json_encode(array_values($this->tooltips)); ?>;

                tooltipsData.forEach(function(tooltip) {
                    FlavorContextualHelp.registerTooltip(tooltip);
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Obtiene todos los tooltips registrados
     *
     * @return array
     */
    public function get_tooltips() {
        return $this->tooltips;
    }

    /**
     * Añade un icono de ayuda junto a un elemento
     *
     * @param string $selector  Selector del elemento
     * @param string $contenido Contenido de ayuda
     * @param string $posicion  Posición del tooltip
     * @return void
     */
    public function add_help_icon($selector, $contenido, $posicion = 'right') {
        $this->register_tooltip($selector, $contenido, $posicion, [
            'show_icon' => true,
            'icon_class' => 'dashicons-editor-help',
            'trigger' => 'click',
            'closable' => true,
        ]);
    }

    /**
     * Registra tooltips para un formulario completo
     *
     * @param string $form_selector Selector del formulario
     * @param array  $campos        Array asociativo de selectores => descripciones
     * @return void
     */
    public function register_form_tooltips($form_selector, $campos) {
        foreach ($campos as $campo_selector => $descripcion) {
            $selector = "{$form_selector} {$campo_selector}";

            if (is_array($descripcion)) {
                $this->register_tooltip(
                    $selector,
                    $descripcion['contenido'],
                    $descripcion['posicion'] ?? 'bottom',
                    $descripcion['opciones'] ?? []
                );
            } else {
                $this->register_tooltip($selector, $descripcion, 'bottom', [
                    'trigger' => 'focus',
                ]);
            }
        }
    }

    /**
     * Crea un bloque de ayuda inline
     *
     * @param string $contenido Contenido HTML de la ayuda
     * @param string $tipo      Tipo: info, warning, tip
     * @return string HTML del bloque de ayuda
     */
    public static function create_help_block($contenido, $tipo = 'info') {
        $iconos = [
            'info' => 'dashicons-info',
            'warning' => 'dashicons-warning',
            'tip' => 'dashicons-lightbulb',
            'success' => 'dashicons-yes-alt',
        ];

        $icono = $iconos[$tipo] ?? $iconos['info'];

        return sprintf(
            '<div class="flavor-help-block flavor-help-block--%s">
                <span class="dashicons %s"></span>
                <div class="flavor-help-block__content">%s</div>
            </div>',
            esc_attr($tipo),
            esc_attr($icono),
            wp_kses_post($contenido)
        );
    }

    /**
     * Registra un video tutorial para una sección
     *
     * @param string $selector   Selector del elemento
     * @param string $video_url  URL del video (YouTube/Vimeo)
     * @param string $titulo     Título del video
     * @return void
     */
    public function register_video_help($selector, $video_url, $titulo = '') {
        $video_id = 'video_' . md5($selector);

        $this->tooltips[$video_id] = [
            'id' => $video_id,
            'selector' => $selector,
            'contenido' => '',
            'video_url' => $video_url,
            'video_titulo' => $titulo ?: __('Ver video tutorial', 'flavor-chat-ia'),
            'posicion' => 'bottom',
            'trigger' => 'click',
            'is_video' => true,
        ];
    }
}

/**
 * Función helper para acceder a la instancia
 *
 * @return Flavor_Contextual_Help
 */
function flavor_contextual_help() {
    return Flavor_Contextual_Help::get_instance();
}
