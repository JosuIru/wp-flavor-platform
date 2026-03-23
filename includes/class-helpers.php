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

    /**
     * Obtiene la URL del logo del sitio configurado en Flavor Platform
     *
     * Prioridad:
     * 1. flavor_logo_url (configurado en el wizard o ajustes)
     * 2. Custom logo de WordPress (Site Identity)
     * 3. String vacío si no hay logo configurado
     *
     * @return string URL del logo o vacío si no hay logo
     */
    public static function get_site_logo() {
        // 1. Logo configurado en Flavor Platform
        $flavor_logo = get_option('flavor_logo_url', '');
        if (!empty($flavor_logo)) {
            return esc_url($flavor_logo);
        }

        // 2. Custom logo de WordPress (Site Identity)
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_image = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_image) {
                return esc_url($logo_image);
            }
        }

        return '';
    }

    /**
     * Obtiene la URL del logo del sitio con HTML completo
     *
     * @param array $atributos Atributos HTML del elemento img (alt, class, style, etc.)
     * @return string HTML de la imagen del logo o vacío si no hay logo
     */
    public static function get_site_logo_html($atributos = []) {
        $logo_url = self::get_site_logo();
        if (empty($logo_url)) {
            return '';
        }

        $atributos_defecto = [
            'alt' => get_bloginfo('name'),
            'class' => 'flavor-site-logo',
        ];
        $atributos = array_merge($atributos_defecto, $atributos);

        $atributos_html = '';
        foreach ($atributos as $nombre_atributo => $valor_atributo) {
            $atributos_html .= sprintf(' %s="%s"', esc_attr($nombre_atributo), esc_attr($valor_atributo));
        }

        return sprintf('<img src="%s"%s />', esc_url($logo_url), $atributos_html);
    }

    /**
     * Obtiene la URL base del portal
     *
     * @return string URL del portal (ej: https://sitio.com/mi-portal/)
     */
    public static function get_portal_url() {
        return home_url('/mi-portal/');
    }

    /**
     * Obtiene la URL de un módulo en el portal
     *
     * @param string $module_slug Slug del módulo (ej: 'economia-don', 'marketplace')
     * @param string $action      Acción opcional (ej: 'crear', 'ofrecer', 'mis-dones')
     * @param int    $item_id     ID de item opcional
     * @return string URL completa del módulo
     */
    public static function get_module_url($module_slug, $action = '', $item_id = 0) {
        $url = self::get_portal_url() . sanitize_title($module_slug) . '/';

        if ($action) {
            $url .= sanitize_title($action) . '/';
        }

        if ($item_id) {
            $url .= absint($item_id) . '/';
        }

        return $url;
    }

    /**
     * Redirección segura con fallback cuando ya se enviaron headers.
     *
     * @param string $url
     * @param int    $status
     * @return void
     */
    public static function safe_redirect($url, $status = 302) {
        $url = esc_url_raw((string) $url);
        if ($url === '') {
            return;
        }

        if (!headers_sent()) {
            wp_safe_redirect($url, (int) $status);
            return;
        }

        echo '<meta http-equiv="refresh" content="0;url=' . esc_url($url) . '">';
        echo '<script>window.location.href=' . wp_json_encode($url) . ';</script>';
    }

    /**
     * Obtiene la URL de una acción específica en un módulo
     *
     * @param string $module_slug Slug del módulo
     * @param string $action      Acción a realizar
     * @param array  $query_args  Argumentos query opcionales
     * @return string URL completa
     */
    public static function get_action_url($module_slug, $action, $query_args = []) {
        $url = self::get_module_url($module_slug, $action);

        if (!empty($query_args)) {
            $url = add_query_arg($query_args, $url);
        }

        return $url;
    }

    /**
     * Obtiene la URL de un item específico en un módulo
     *
     * @param string $module_slug Slug del módulo
     * @param int    $item_id     ID del item
     * @param string $action      Acción opcional (ej: 'editar')
     * @return string URL completa
     */
    public static function get_item_url($module_slug, $item_id, $action = '') {
        if ($action) {
            return self::get_module_url($module_slug, $action, $item_id);
        }
        return self::get_module_url($module_slug, '', $item_id);
    }

    /**
     * Genera un enlace HTML a una acción del portal
     *
     * @param string $module_slug Slug del módulo
     * @param string $action      Acción
     * @param string $text        Texto del enlace
     * @param array  $attrs       Atributos HTML adicionales
     * @return string HTML del enlace
     */
    public static function portal_link($module_slug, $action, $text, $attrs = []) {
        $url = self::get_action_url($module_slug, $action);

        $attr_str = '';
        foreach ($attrs as $key => $value) {
            $attr_str .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
        }

        return sprintf(
            '<a href="%s"%s>%s</a>',
            esc_url($url),
            $attr_str,
            esc_html($text)
        );
    }

    /**
     * Normaliza un ID de módulo a formato estándar (guion_bajo)
     *
     * Convierte guiones a guiones bajos para consistencia interna.
     * Útil para comparar IDs de módulos que pueden venir en distintos formatos.
     *
     * @param string $module_id ID del módulo (acepta guiones o guiones bajos)
     * @return string ID normalizado con guiones bajos
     */
    public static function normalize_module_id( $module_id ) {
        return str_replace( '-', '_', strtolower( trim( $module_id ) ) );
    }

    /**
     * Compara dos IDs de módulos ignorando diferencias de formato
     *
     * @param string $module_id_a Primer ID de módulo
     * @param string $module_id_b Segundo ID de módulo
     * @return bool True si son equivalentes
     */
    public static function module_ids_match( $module_id_a, $module_id_b ) {
        return self::normalize_module_id( $module_id_a ) === self::normalize_module_id( $module_id_b );
    }

    /**
     * Obtiene el ID de módulo en formato slug (guion-medio)
     *
     * Útil para URLs y nombres de archivos.
     *
     * @param string $module_id ID del módulo
     * @return string ID en formato slug con guiones
     */
    public static function module_id_to_slug( $module_id ) {
        return str_replace( '_', '-', strtolower( trim( $module_id ) ) );
    }
}

if (!function_exists('flavor_current_request_url')) {
    /**
     * Devuelve la URL actual para redirects de login en rutas dinámicas.
     */
    function flavor_current_request_url(): string {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash((string) $_SERVER['REQUEST_URI']) : '/';
        $request_uri = '/' . ltrim($request_uri, '/');

        return home_url($request_uri);
    }
}

if (!function_exists('flavor_get_site_logo')) {
    /**
     * Obtiene la URL del logo del sitio configurado en Flavor Platform.
     *
     * @return string URL del logo o vacío si no hay logo
     */
    function flavor_get_site_logo(): string {
        return Flavor_Chat_Helpers::get_site_logo();
    }
}

if (!function_exists('flavor_get_site_logo_html')) {
    /**
     * Obtiene el HTML de la imagen del logo del sitio.
     *
     * @param array $atributos Atributos HTML del elemento img
     * @return string HTML de la imagen del logo
     */
    function flavor_get_site_logo_html(array $atributos = []): string {
        return Flavor_Chat_Helpers::get_site_logo_html($atributos);
    }
}

if (!function_exists('flavor_is_module_active')) {
    /**
     * Verifica si un módulo está activo.
     *
     * Utiliza la función centralizada Flavor_Chat_Module_Loader::is_module_active()
     * que verifica en flavor_chat_ia_settings['active_modules'] (preferido)
     * y flavor_active_modules (legacy), normalizando IDs automáticamente.
     * Incluye caché por request para evitar múltiples consultas get_option.
     *
     * @param string $module_id ID del módulo (acepta guiones o guiones bajos)
     * @return bool True si el módulo está activo
     */
    function flavor_is_module_active(string $module_id): bool {
        // Usar el método centralizado con caché
        if (class_exists('Flavor_Chat_Module_Loader')) {
            return Flavor_Chat_Module_Loader::is_module_active($module_id);
        }

        // Fallback con caché estática si el loader no está disponible
        static $modulos_activos_cache = null;

        if ($modulos_activos_cache === null) {
            $configuracion_plugin = get_option('flavor_chat_ia_settings', []);
            $modulos_activos_cache = $configuracion_plugin['active_modules'] ?? [];

            $modulos_activos_legacy = get_option('flavor_active_modules', []);
            if (!empty($modulos_activos_legacy)) {
                $modulos_activos_cache = array_unique(array_merge($modulos_activos_cache, $modulos_activos_legacy));
            }

            if (empty($modulos_activos_cache)) {
                $modulos_activos_cache = ['woocommerce'];
            }
        }

        // Normalizar ID (guiones vs guiones bajos)
        $id_normalizado = str_replace('-', '_', $module_id);
        return in_array($module_id, $modulos_activos_cache, true)
            || in_array($id_normalizado, $modulos_activos_cache, true);
    }
}

if (!function_exists('flavor_normalize_module_id')) {
    /**
     * Normaliza un ID de módulo a formato estándar (guion_bajo)
     *
     * @param string $module_id ID del módulo
     * @return string ID normalizado
     */
    function flavor_normalize_module_id(string $module_id): string {
        return Flavor_Chat_Helpers::normalize_module_id($module_id);
    }
}

if (!function_exists('flavor_module_id_to_slug')) {
    /**
     * Convierte un ID de módulo a formato slug (guion-medio)
     *
     * @param string $module_id ID del módulo
     * @return string Slug del módulo
     */
    function flavor_module_id_to_slug(string $module_id): string {
        return Flavor_Chat_Helpers::module_id_to_slug($module_id);
    }
}
