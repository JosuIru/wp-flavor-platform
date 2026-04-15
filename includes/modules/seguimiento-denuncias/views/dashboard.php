<?php
/**
 * Vista puente de dashboard de Seguimiento de Denuncias.
 *
 * Se mantiene para compatibilidad con includes existentes, delegando
 * toda la renderizacion al dashboard administrativo del modulo.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

if (isset($estadisticas) && is_array($estadisticas)) {
    $activas = (int) ($estadisticas['activas'] ?? 0);
    $silencio = (int) ($estadisticas['silencio'] ?? 0);
    $resueltas = (int) ($estadisticas['resueltas'] ?? 0);
    ?>
    <div class="flavor-admin-widget-denuncias">
        <ul class="flavor-widget-list">
            <li>
                <strong><?php esc_html_e('Abiertas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong>
                <?php echo esc_html(number_format_i18n($activas)); ?>
            </li>
            <li>
                <strong><?php esc_html_e('En silencio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong>
                <?php echo esc_html(number_format_i18n($silencio)); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Resueltas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong>
                <?php echo esc_html(number_format_i18n($resueltas)); ?>
            </li>
        </ul>
        <p style="margin: 10px 0 0;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=denuncias-listado')); ?>" class="button button-small">
                <?php esc_html_e('Ver listado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </a>
        </p>
    </div>
    <?php
    return;
}

if (isset($this) && method_exists($this, 'render_admin_dashboard')) {
    $this->render_admin_dashboard();
    return;
}

echo '<div class="notice notice-error"><p>'
    . esc_html__('No se pudo renderizar el dashboard de denuncias.', FLAVOR_PLATFORM_TEXT_DOMAIN)
    . '</p></div>';
