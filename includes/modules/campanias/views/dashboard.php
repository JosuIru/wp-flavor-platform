<?php
/**
 * Vista puente de dashboard de Campanias.
 *
 * Se mantiene para compatibilidad con includes existentes, delegando
 * toda la renderizacion al dashboard administrativo del modulo.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (isset($estadisticas) && is_array($estadisticas)) {
    if (isset($this) && method_exists($this, 'render_admin_widget')) {
        $this->render_admin_widget();
        return;
    }
}

if (isset($this) && method_exists($this, 'render_admin_dashboard')) {
    $this->render_admin_dashboard();
    return;
}

echo '<div class="notice notice-error"><p>'
    . esc_html__('No se pudo renderizar el dashboard de campanias.', 'flavor-platform')
    . '</p></div>';
