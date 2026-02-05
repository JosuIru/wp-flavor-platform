<?php
/**
 * Funciones Helper/Utility para Flavor Chat IA
 *
 * Funciones comunes que pueden ser utilizadas por cualquier módulo
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase con funciones de ayuda
 */
class Flavor_Chat_Helpers {

    /**
     * Formatea un precio con el símbolo de moneda
     *
     * @param float $precio Precio a formatear
     * @param string $moneda Símbolo de moneda (por defecto €)
     * @return string Precio formateado
     */
    public static function formatear_precio($precio, $moneda = '€') {
        if (function_exists('wc_price')) {
            return strip_tags(wc_price($precio));
        }

        return number_format((float)$precio, 2, ',', '.') . ' ' . $moneda;
    }

    /**
     * Formatea una fecha en español
     *
     * @param string|int $fecha Fecha en formato timestamp o string
     * @param string $formato Formato de salida ('short', 'medium', 'long', 'full')
     * @return string Fecha formateada
     */
    public static function formatear_fecha($fecha, $formato = 'medium') {
        if (is_string($fecha)) {
            $timestamp = strtotime($fecha);
        } else {
            $timestamp = $fecha;
        }

        $formatos_disponibles = [
            'short' => 'd/m/Y',
            'medium' => 'd/m/Y H:i',
            'long' => 'l, d \d\e F \d\e Y',
            'full' => 'l, d \d\e F \d\e Y \a \l\a\s H:i',
        ];

        $formato_seleccionado = $formatos_disponibles[$formato] ?? $formatos_disponibles['medium'];

        return date_i18n($formato_seleccionado, $timestamp);
    }

    /**
     * Calcula el tiempo transcurrido de forma legible
     *
     * @param string|int $fecha Fecha inicial
     * @return string Tiempo transcurrido (ej: "hace 2 horas")
     */
    public static function tiempo_transcurrido($fecha) {
        if (is_string($fecha)) {
            $timestamp = strtotime($fecha);
        } else {
            $timestamp = $fecha;
        }

        return sprintf(
            __('hace %s', 'flavor-chat-ia'),
            human_time_diff($timestamp, current_time('timestamp'))
        );
    }

    /**
     * Calcula el tiempo restante hasta una fecha
     *
     * @param string|int $fecha Fecha objetivo
     * @return string Tiempo restante (ej: "quedan 3 días")
     */
    public static function tiempo_restante($fecha) {
        if (is_string($fecha)) {
            $timestamp = strtotime($fecha);
        } else {
            $timestamp = $fecha;
        }

        $diferencia = $timestamp - current_time('timestamp');

        if ($diferencia < 0) {
            return __('Ya pasó', 'flavor-chat-ia');
        }

        return sprintf(
            __('quedan %s', 'flavor-chat-ia'),
            human_time_diff(current_time('timestamp'), $timestamp)
        );
    }

    /**
     * Sanitiza un array de datos recursivamente
     *
     * @param array $datos Datos a sanitizar
     * @return array Datos sanitizados
     */
    public static function sanitizar_array($datos) {
        if (!is_array($datos)) {
            return sanitize_text_field($datos);
        }

        return array_map([__CLASS__, 'sanitizar_array'], $datos);
    }

    /**
     * Envía una notificación por email
     *
     * @param int|string $destinatario ID de usuario o email
     * @param string $asunto Asunto del email
     * @param string $mensaje Cuerpo del mensaje
     * @param array $opciones Opciones adicionales (headers, attachments, etc.)
     * @return bool True si se envió correctamente
     */
    public static function enviar_email($destinatario, $asunto, $mensaje, $opciones = []) {
        // Obtener email del usuario si es un ID
        if (is_numeric($destinatario)) {
            $usuario = get_userdata($destinatario);
            $email_destinatario = $usuario ? $usuario->user_email : '';
        } else {
            $email_destinatario = $destinatario;
        }

        if (empty($email_destinatario) || !is_email($email_destinatario)) {
            return false;
        }

        // Headers por defecto
        $headers_defecto = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        $headers = array_merge($headers_defecto, $opciones['headers'] ?? []);

        // Template HTML
        $mensaje_html = self::obtener_template_email($mensaje, $asunto);

        return wp_mail(
            $email_destinatario,
            $asunto,
            $mensaje_html,
            $headers,
            $opciones['attachments'] ?? []
        );
    }

    /**
     * Obtiene el template HTML para emails
     *
     * @param string $contenido Contenido del email
     * @param string $titulo Título del email
     * @return string HTML completo
     */
    private static function obtener_template_email($contenido, $titulo) {
        $color_primario = get_option('flavor_chat_ia_settings')['widget_color'] ?? '#0073aa';
        $nombre_sitio = get_bloginfo('name');
        $url_sitio = home_url();

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($titulo); ?></title>
        </head>
        <body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
            <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
                <tr>
                    <td align="center">
                        <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                            <!-- Header -->
                            <tr>
                                <td style="background-color: <?php echo esc_attr($color_primario); ?>; padding: 30px; text-align: center;">
                                    <h1 style="margin: 0; color: #ffffff; font-size: 24px;">
                                        <?php echo esc_html($nombre_sitio); ?>
                                    </h1>
                                </td>
                            </tr>
                            <!-- Content -->
                            <tr>
                                <td style="padding: 40px 30px;">
                                    <?php echo wp_kses_post($contenido); ?>
                                </td>
                            </tr>
                            <!-- Footer -->
                            <tr>
                                <td style="background-color: #f9f9f9; padding: 20px 30px; text-align: center; border-top: 1px solid #eeeeee;">
                                    <p style="margin: 0; color: #666666; font-size: 12px;">
                                        Este email fue enviado desde <a href="<?php echo esc_url($url_sitio); ?>" style="color: <?php echo esc_attr($color_primario); ?>;"><?php echo esc_html($nombre_sitio); ?></a>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Crea una notificación en la base de datos
     *
     * @param int $usuario_id ID del usuario destinatario
     * @param string $tipo Tipo de notificación
     * @param string $titulo Título de la notificación
     * @param string $mensaje Mensaje de la notificación
     * @param array $relacionado Datos relacionados ['tipo' => 'post', 'id' => 123]
     * @return int|false ID de la notificación creada o false si falla
     */
    public static function crear_notificacion($usuario_id, $tipo, $titulo, $mensaje, $relacionado = []) {
        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_gc_notificaciones';

        // Verificar si la tabla existe
        if (!self::tabla_existe($tabla_notificaciones)) {
            return false;
        }

        $resultado = $wpdb->insert(
            $tabla_notificaciones,
            [
                'usuario_id' => absint($usuario_id),
                'tipo' => sanitize_text_field($tipo),
                'titulo' => sanitize_text_field($titulo),
                'mensaje' => sanitize_textarea_field($mensaje),
                'relacionado_tipo' => !empty($relacionado['tipo']) ? sanitize_text_field($relacionado['tipo']) : null,
                'relacionado_id' => !empty($relacionado['id']) ? absint($relacionado['id']) : null,
                'fecha_creacion' => current_time('mysql'),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );

        return $resultado ? $wpdb->insert_id : false;
    }

    /**
     * Obtiene las notificaciones de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @param array $args Argumentos adicionales (limite, solo_no_leidas, tipo)
     * @return array Notificaciones
     */
    public static function obtener_notificaciones($usuario_id, $args = []) {
        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_gc_notificaciones';

        if (!self::tabla_existe($tabla_notificaciones)) {
            return [];
        }

        $defaults = [
            'limite' => 20,
            'solo_no_leidas' => false,
            'tipo' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = $wpdb->prepare("usuario_id = %d", $usuario_id);

        if ($args['solo_no_leidas']) {
            $where .= " AND leida = 0";
        }

        if (!empty($args['tipo'])) {
            $where .= $wpdb->prepare(" AND tipo = %s", $args['tipo']);
        }

        $sql = "SELECT * FROM $tabla_notificaciones
                WHERE $where
                ORDER BY fecha_creacion DESC
                LIMIT " . absint($args['limite']);

        return $wpdb->get_results($sql);
    }

    /**
     * Marca una notificación como leída
     *
     * @param int $notificacion_id ID de la notificación
     * @return bool True si se actualizó correctamente
     */
    public static function marcar_notificacion_leida($notificacion_id) {
        global $wpdb;
        $tabla_notificaciones = $wpdb->prefix . 'flavor_gc_notificaciones';

        if (!self::tabla_existe($tabla_notificaciones)) {
            return false;
        }

        $resultado = $wpdb->update(
            $tabla_notificaciones,
            [
                'leida' => 1,
                'fecha_lectura' => current_time('mysql'),
            ],
            ['id' => absint($notificacion_id)],
            ['%d', '%s'],
            ['%d']
        );

        return $resultado !== false;
    }

    /**
     * Genera un slug único para un post type
     *
     * @param string $texto Texto base para el slug
     * @param string $post_type Tipo de post
     * @return string Slug único
     */
    public static function generar_slug_unico($texto, $post_type = 'post') {
        $slug_base = sanitize_title($texto);
        $slug = $slug_base;
        $contador = 1;

        while (get_page_by_path($slug, OBJECT, $post_type)) {
            $slug = $slug_base . '-' . $contador;
            $contador++;
        }

        return $slug;
    }

    /**
     * Valida una dirección de email
     *
     * @param string $email Email a validar
     * @return bool True si es válido
     */
    public static function validar_email($email) {
        return is_email($email) !== false;
    }

    /**
     * Valida un número de teléfono (formato español)
     *
     * @param string $telefono Teléfono a validar
     * @return bool True si es válido
     */
    public static function validar_telefono($telefono) {
        // Eliminar espacios y guiones
        $telefono_limpio = preg_replace('/[\s\-]/', '', $telefono);

        // Validar formato español (móvil o fijo)
        return preg_match('/^(\+34|0034|34)?[6789]\d{8}$/', $telefono_limpio) === 1;
    }

    /**
     * Obtiene el avatar de un usuario
     *
     * @param int $usuario_id ID del usuario
     * @param int $tamano Tamaño del avatar en píxeles
     * @return string URL del avatar
     */
    public static function obtener_avatar_url($usuario_id, $tamano = 96) {
        $avatar = get_avatar($usuario_id, $tamano);

        if (preg_match('/src=[\'"]([^\'"]+)[\'"]/i', $avatar, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Trunca un texto a un número de palabras
     *
     * @param string $texto Texto a truncar
     * @param int $num_palabras Número de palabras
     * @param string $mas Texto a añadir al final
     * @return string Texto truncado
     */
    public static function truncar_texto($texto, $num_palabras = 55, $mas = '...') {
        return wp_trim_words($texto, $num_palabras, $mas);
    }

    /**
     * Verifica si una tabla existe en la base de datos
     *
     * @param string $nombre_tabla Nombre completo de la tabla (con prefijo)
     * @return bool True si la tabla existe
     */
    public static function tabla_existe($nombre_tabla) {
        static $cache_tablas = [];

        if (isset($cache_tablas[$nombre_tabla])) {
            return $cache_tablas[$nombre_tabla];
        }

        global $wpdb;
        $resultado = $wpdb->get_var(
            $wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($nombre_tabla))
        );

        $cache_tablas[$nombre_tabla] = ($resultado === $nombre_tabla);
        return $cache_tablas[$nombre_tabla];
    }

    /**
     * Convierte un array en CSV
     *
     * @param array $datos Datos a convertir
     * @param array $encabezados Encabezados opcionales
     * @return string Contenido CSV
     */
    public static function array_a_csv($datos, $encabezados = []) {
        if (empty($datos)) {
            return '';
        }

        ob_start();
        $output = fopen('php://output', 'w');

        // Encabezados
        if (!empty($encabezados)) {
            fputcsv($output, $encabezados);
        } else {
            // Usar keys del primer elemento como encabezados
            fputcsv($output, array_keys($datos[0]));
        }

        // Datos
        foreach ($datos as $fila) {
            fputcsv($output, $fila);
        }

        fclose($output);
        return ob_get_clean();
    }

    /**
     * Descarga un archivo CSV
     *
     * @param string $contenido Contenido del CSV
     * @param string $nombre_archivo Nombre del archivo
     */
    public static function descargar_csv($contenido, $nombre_archivo = 'export.csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        echo "\xEF\xBB\xBF"; // UTF-8 BOM
        echo $contenido;
        exit;
    }

    /**
     * Registra un evento en el log
     *
     * @param string $evento Tipo de evento
     * @param string $descripcion Descripción del evento
     * @param array $datos Datos adicionales
     */
    public static function registrar_evento($evento, $descripcion, $datos = []) {
        if (!FLAVOR_CHAT_IA_DEBUG) {
            return;
        }

        $log_entry = sprintf(
            '[%s] Evento: %s | %s | Datos: %s',
            current_time('mysql'),
            $evento,
            $descripcion,
            json_encode($datos)
        );

        error_log($log_entry);
    }

    /**
     * Obtiene la configuración de un módulo
     *
     * @param string $modulo_id ID del módulo
     * @param string $clave Clave específica (opcional)
     * @param mixed $default Valor por defecto
     * @return mixed Configuración del módulo
     */
    public static function obtener_config_modulo($modulo_id, $clave = '', $default = null) {
        $configuracion = get_option('flavor_chat_ia_module_' . $modulo_id, []);

        if (empty($clave)) {
            return $configuracion;
        }

        return $configuracion[$clave] ?? $default;
    }

    /**
     * Actualiza la configuración de un módulo
     *
     * @param string $modulo_id ID del módulo
     * @param string|array $clave Clave a actualizar o array completo
     * @param mixed $valor Valor a guardar (si $clave es string)
     * @return bool True si se actualizó correctamente
     */
    public static function actualizar_config_modulo($modulo_id, $clave, $valor = null) {
        $option_name = 'flavor_chat_ia_module_' . $modulo_id;

        if (is_array($clave)) {
            // Actualizar configuración completa
            return update_option($option_name, $clave);
        }

        // Actualizar clave específica
        $configuracion = get_option($option_name, []);
        $configuracion[$clave] = $valor;
        return update_option($option_name, $configuracion);
    }
}
