<?php
/**
 * Clase de utilidades y helpers del plugin
 *
 * @package Flavor_Chat_IA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase con métodos de utilidad para el plugin
 */
class Flavor_Chat_Helpers {

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $tabla Nombre completo de la tabla (con prefijo)
     * @return bool
     */
    public static function tabla_existe($tabla) {
        global $wpdb;

        $tabla = esc_sql($tabla);
        $result = $wpdb->get_var($wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $tabla
        ));

        return $result === $tabla;
    }

    /**
     * Obtiene el prefijo de las tablas del plugin
     *
     * @return string
     */
    public static function get_table_prefix() {
        global $wpdb;
        return $wpdb->prefix . 'flavor_';
    }

    /**
     * Formatea una fecha para mostrar
     *
     * @param string $fecha
     * @param string $formato
     * @return string
     */
    public static function formatear_fecha($fecha, $formato = '') {
        if (empty($formato)) {
            $formato = get_option('date_format') . ' ' . get_option('time_format');
        }
        return date_i18n($formato, strtotime($fecha));
    }

    /**
     * Tiempo transcurrido en formato legible
     *
     * @param string $fecha
     * @return string
     */
    public static function tiempo_transcurrido($fecha) {
        return human_time_diff(strtotime($fecha), current_time('timestamp'));
    }

    /**
     * Devuelve un color basado en un estado
     *
     * @param string $estado
     * @return string
     */
    public static function get_status_color($estado) {
        $colores = [
            'activo' => '#10b981',
            'completado' => '#10b981',
            'aprobado' => '#10b981',
            'confirmado' => '#10b981',
            'pagado' => '#10b981',
            'pendiente' => '#f59e0b',
            'en_proceso' => '#3b82f6',
            'procesando' => '#3b82f6',
            'cancelado' => '#ef4444',
            'rechazado' => '#ef4444',
            'error' => '#ef4444',
            'inactivo' => '#6b7280',
            'borrador' => '#6b7280',
        ];

        $estado_lower = strtolower($estado);
        return $colores[$estado_lower] ?? '#6b7280';
    }

    /**
     * Crea una respuesta JSON estándar para AJAX
     *
     * @param bool $success
     * @param string $message
     * @param array $data
     */
    public static function json_response($success, $message = '', $data = []) {
        wp_send_json([
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * Corrige URLs de placeholder incompletas o rotas
     *
     * Convierte URLs de placeholder externos a SVG data URI locales
     *
     * @param string $url URL a verificar y corregir
     * @return string URL corregida o SVG data URI
     */
    public static function fix_placeholder_url($url) {
        if (empty($url)) {
            return $url;
        }

        // Detecta URLs de placeholder (incompletas o completas)
        if (preg_match('/^(\d+)x(\d+)\?text=(.+)$/', $url, $matches)) {
            // URL incompleta: 400x250?text=Limpieza
            return self::get_placeholder_svg($matches[3], (int)$matches[1], (int)$matches[2]);
        }

        if (preg_match('/placeholder\.com\/(\d+)x(\d+)\?text=(.+)$/i', $url, $matches)) {
            // URL completa de placeholder.com
            return self::get_placeholder_svg(urldecode($matches[3]), (int)$matches[1], (int)$matches[2]);
        }

        return $url;
    }

    /**
     * Genera un SVG placeholder inline (no requiere servicio externo)
     *
     * @param string $texto Texto a mostrar
     * @param int $ancho Ancho de la imagen
     * @param int $alto Alto de la imagen
     * @param string $color_fondo Color de fondo (hex sin #)
     * @param string $color_texto Color del texto (hex sin #)
     * @return string Data URI del SVG
     */
    public static function get_placeholder_svg($texto = '', $ancho = 400, $alto = 250, $color_fondo = 'e2e8f0', $color_texto = '64748b') {
        $texto = htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
        $font_size = min($ancho / 10, 24);

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $ancho . '" height="' . $alto . '" viewBox="0 0 ' . $ancho . ' ' . $alto . '">';
        $svg .= '<rect fill="#' . $color_fondo . '" width="' . $ancho . '" height="' . $alto . '"/>';
        $svg .= '<text fill="#' . $color_texto . '" font-family="system-ui, sans-serif" font-size="' . $font_size . '" font-weight="500" ';
        $svg .= 'x="50%" y="50%" dominant-baseline="middle" text-anchor="middle">' . $texto . '</text>';
        $svg .= '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    /**
     * Obtiene una imagen de placeholder con el texto especificado
     *
     * @param string $texto Texto a mostrar en el placeholder
     * @param int $ancho Ancho de la imagen (default 400)
     * @param int $alto Alto de la imagen (default 250)
     * @return string Data URI del SVG placeholder
     */
    public static function get_placeholder_image($texto = '', $ancho = 400, $alto = 250) {
        return self::get_placeholder_svg($texto, $ancho, $alto);
    }

    /**
     * Renderiza tabs de integración de módulos de red
     *
     * Este helper permite que cualquier template (single.php, archive.php, etc.)
     * renderice las tabs de integración de forma consistente con el sistema
     * de dynamic-pages.
     *
     * @param string $modulo_id    ID del módulo (ej: 'banco_tiempo')
     * @param int    $entity_id    ID de la entidad actual
     * @param array  $opciones     Opciones adicionales:
     *                             - 'container_class' => clase CSS del contenedor
     *                             - 'show_empty'      => mostrar aunque no haya tabs (default: false)
     * @return void
     */
    public static function render_integration_tabs($modulo_id, $entity_id = 0, $opciones = []) {
        // Verificar que el loader de módulos existe
        if (!class_exists('Flavor_Chat_Module_Loader')) {
            return;
        }

        $loader = Flavor_Chat_Module_Loader::get_instance();
        $modulo = $loader->get_module($modulo_id);

        if (!$modulo || !method_exists($modulo, 'get_dashboard_tabs')) {
            return;
        }

        // Obtener entity_id si no se proporcionó
        if (!$entity_id) {
            global $post;
            $entity_id = $post ? $post->ID : 0;
        }

        $tabs = $modulo->get_dashboard_tabs();

        // Solo mostrar tabs de integración
        $tabs_integracion = array_filter($tabs, function($tab) {
            return !empty($tab['is_integration']);
        });

        if (empty($tabs_integracion) && empty($opciones['show_empty'])) {
            return;
        }

        // Ordenar por prioridad
        uasort($tabs_integracion, function($a, $b) {
            return ($a['priority'] ?? 100) - ($b['priority'] ?? 100);
        });

        $container_class = $opciones['container_class'] ?? 'flavor-component bg-white rounded-xl shadow-lg mt-6 overflow-hidden';
        ?>
        <div class="<?php echo esc_attr($container_class); ?>">
            <!-- Tabs Navigation -->
            <div class="fmd-tabs-nav flex border-b bg-gray-50 overflow-x-auto">
                <?php
                $primera_tab = true;
                foreach ($tabs_integracion as $tab_id => $tab_config) :
                    $icono = $tab_config['icon'] ?? 'dashicons-admin-generic';
                    $label = $tab_config['label'] ?? ucfirst($tab_id);
                    $clases_tab = $primera_tab
                        ? 'fmd-tab active border-primary text-primary bg-white'
                        : 'fmd-tab border-transparent text-gray-600 hover:text-gray-900 hover:bg-gray-100';
                ?>
                <button
                    type="button"
                    class="<?php echo esc_attr($clases_tab); ?> flex items-center gap-2 px-6 py-4 text-sm font-medium whitespace-nowrap border-b-2 transition-colors"
                    data-tab="<?php echo esc_attr($tab_id); ?>"
                >
                    <span class="dashicons <?php echo esc_attr($icono); ?>"></span>
                    <?php echo esc_html($label); ?>
                    <?php
                    // Badge si existe
                    if (isset($tab_config['badge'])) {
                        $badge_valor = is_callable($tab_config['badge'])
                            ? call_user_func($tab_config['badge'], ['entity_id' => $entity_id, 'user_id' => get_current_user_id()])
                            : $tab_config['badge'];
                        if ($badge_valor > 0) {
                            echo '<span class="fmd-tab-badge ml-2 px-2 py-0.5 text-xs bg-primary text-white rounded-full">' . esc_html($badge_valor) . '</span>';
                        }
                    }
                    ?>
                </button>
                <?php
                    $primera_tab = false;
                endforeach;
                ?>
            </div>

            <!-- Tabs Content -->
            <div class="fmd-tab-panels p-6">
                <?php
                $primera_tab = true;
                foreach ($tabs_integracion as $tab_id => $tab_config) :
                    $oculta = $primera_tab ? 'active' : '';
                ?>
                <div
                    class="fmd-tab-panel <?php echo $oculta; ?>"
                    data-panel="<?php echo esc_attr($tab_id); ?>"
                    <?php echo !$primera_tab ? 'style="display:none;"' : ''; ?>
                >
                    <?php
                    // Renderizar contenido del tab
                    if (method_exists($modulo, 'render_tab_content')) {
                        echo $modulo->render_tab_content($tab_id, $tab_config);
                    } else {
                        // Fallback: procesar shortcode si existe
                        $contenido = $tab_config['content'] ?? '';
                        if (is_string($contenido) && strpos($contenido, '[') === 0) {
                            echo do_shortcode($contenido);
                        } elseif (is_string($contenido)) {
                            echo $contenido;
                        }
                    }
                    ?>
                </div>
                <?php
                    $primera_tab = false;
                endforeach;
                ?>
            </div>
        </div>

        <script>
        (function() {
            var container = document.currentScript.parentElement;
            container.querySelectorAll('.fmd-tab').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var tabId = this.dataset.tab;
                    var tabsContainer = this.closest('.fmd-tabs-nav').parentElement;

                    // Desactivar todos los botones
                    tabsContainer.querySelectorAll('.fmd-tab').forEach(function(b) {
                        b.classList.remove('active', 'border-primary', 'text-primary', 'bg-white');
                        b.classList.add('border-transparent', 'text-gray-600');
                    });

                    // Activar botón actual
                    this.classList.remove('border-transparent', 'text-gray-600');
                    this.classList.add('active', 'border-primary', 'text-primary', 'bg-white');

                    // Ocultar todos los paneles
                    tabsContainer.querySelectorAll('.fmd-tab-panel').forEach(function(panel) {
                        panel.classList.remove('active');
                        panel.style.display = 'none';
                    });

                    // Mostrar panel actual
                    var panel = tabsContainer.querySelector('.fmd-tab-panel[data-panel="' + tabId + '"]');
                    if (panel) {
                        panel.classList.add('active');
                        panel.style.display = '';
                    }
                });
            });
        })();
        </script>
        <?php
    }
}
