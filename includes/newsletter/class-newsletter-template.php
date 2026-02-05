<?php
/**
 * Motor de plantillas para Newsletter
 *
 * Renderiza contenido HTML con variables dinamicas y wrapper responsive.
 *
 * @package FlavorChatIA
 * @subpackage Newsletter
 * @since 3.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Flavor_Newsletter_Template {

    private static $instancia = null;

    private $variables_disponibles = [
'{{nombre}}'          => 'Nombre del suscriptor',
'{{email}}'           => 'Email del suscriptor',
'{{sitio_nombre}}'    => 'Nombre del sitio web',
'{{sitio_url}}'       => 'URL del sitio web',
'{{fecha}}'           => 'Fecha actual formateada',
'{{enlace_baja}}'     => 'Enlace para darse de baja',
'{{unsubscribe_url}}' => 'URL de baja (alternativa)',
    ];

    public static function get_instance() {
        if (self::$instancia === null) {
            self::$instancia = new self();
        }
        return self::$instancia;
    }

    private function __construct() {}

    public function render($contenido_html, $valores = []) {
        if (empty($contenido_html)) { return ''; }
        $mapa_sustituciones = [];
        foreach ($valores as $clave => $valor) {
            $mapa_sustituciones['{{' . $clave . '}}'] = $valor;
        }
        return str_replace(array_keys($mapa_sustituciones), array_values($mapa_sustituciones), $contenido_html);
    }

    public function get_wrapper_html($contenido_interno, $variables_extra = []) {
        $nombre_sitio   = $variables_extra['sitio_nombre'] ?? get_bloginfo('name');
        $url_sitio      = $variables_extra['sitio_url'] ?? home_url();
        $enlace_baja    = $variables_extra['enlace_baja'] ?? $variables_extra['unsubscribe_url'] ?? '#';
        $color_primario = $this->obtener_color_primario();
        $anio_actual    = wp_date('Y');

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>' . esc_html($nombre_sitio) . '</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#333333;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;">
<tr><td align="center" style="padding:20px 10px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:' . esc_attr($color_primario) . ';border-radius:8px 8px 0 0;">
<tr><td align="center" style="padding:24px 30px;"><a href="' . esc_url($url_sitio) . '" style="color:#ffffff;text-decoration:none;font-size:22px;font-weight:bold;">' . esc_html($nombre_sitio) . '</a></td></tr>
</table>
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#ffffff;">
<tr><td style="padding:30px;font-size:16px;line-height:1.6;color:#333333;">
' . $contenido_interno . '
</td></tr></table>
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background-color:#f0f0f3;border-radius:0 0 8px 8px;">
<tr><td align="center" style="padding:20px 30px;font-size:12px;color:#888888;">
<p style="margin:0 0 8px 0;">&copy; ' . esc_html($anio_actual) . ' ' . esc_html($nombre_sitio) . '</p>
<p style="margin:0;"><a href="' . esc_url($enlace_baja) . '" style="color:#888888;text-decoration:underline;">' . esc_html__("Cancelar suscripcion", "flavor-chat-ia") . '</a></p>
</td></tr></table>
</td></tr></table>
</body></html>';

        return $html;
    }

    public function obtener_variables_disponibles() {
        return $this->variables_disponibles;
    }

    public function generar_preview($contenido_html) {
        $valores_ejemplo = [
            'nombre'          => __('Juan Perez', 'flavor-chat-ia'),
            'email'           => 'juan@ejemplo.com',
            'sitio_nombre'    => get_bloginfo('name'),
            'sitio_url'       => home_url(),
            'fecha'           => wp_date(get_option('date_format')),
            'enlace_baja'     => '#preview-baja',
            'unsubscribe_url' => '#preview-baja',
        ];
        $contenido_renderizado = $this->render($contenido_html, $valores_ejemplo);
        return $this->get_wrapper_html($contenido_renderizado, $valores_ejemplo);
    }

    private function obtener_color_primario() {
        $opciones_diseno = get_option('flavor_design_settings', []);
        if (!empty($opciones_diseno['primary_color'])) {
            return sanitize_hex_color($opciones_diseno['primary_color']) ?: '#0073aa';
        }
        $opciones_chat = get_option('flavor_chat_ia_settings', []);
        if (!empty($opciones_chat['widget_color'])) {
            return sanitize_hex_color($opciones_chat['widget_color']) ?: '#0073aa';
        }
        return '#0073aa';
    }

    public function obtener_plantilla_por_defecto() {
        $plantilla = '<h2>' . esc_html__('Hola {{nombre}}', 'flavor-chat-ia') . '</h2>' . "
"
            . '<p>' . esc_html__('Gracias por ser parte de nuestra comunidad.', 'flavor-chat-ia') . '</p>' . "
"
            . '<p>' . esc_html__('Escribe aqui el contenido de tu newsletter...', 'flavor-chat-ia') . '</p>' . "
"
            . '<p style="text-align:center;margin-top:30px;"><a href="{{sitio_url}}" style="display:inline-block;padding:12px 30px;background-color:#0073aa;color:#ffffff;text-decoration:none;border-radius:5px;font-weight:bold;">' . esc_html__('Visitar sitio web', 'flavor-chat-ia') . '</a></p>';
        return $plantilla;
    }
}
