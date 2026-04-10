<?php
/**
 * Vista puente de dashboard de Mapa de Actores.
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
    $total = (int) ($estadisticas['total'] ?? 0);
    $aliados = (int) ($estadisticas['aliados'] ?? 0);
    $opositores = (int) ($estadisticas['opositores'] ?? 0);
    ?>
    <div class="flavor-admin-widget-mapa-actores">
        <ul class="flavor-widget-list">
            <li>
                <strong><?php esc_html_e('Total actores', 'flavor-platform'); ?>:</strong>
                <?php echo esc_html(number_format_i18n($total)); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Aliados', 'flavor-platform'); ?>:</strong>
                <?php echo esc_html(number_format_i18n($aliados)); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Opositores', 'flavor-platform'); ?>:</strong>
                <?php echo esc_html(number_format_i18n($opositores)); ?>
            </li>
        </ul>
        <p style="margin: 10px 0 0;">
            <a href="<?php echo esc_url(admin_url('admin.php?page=actores-listado')); ?>" class="button button-small">
                <?php esc_html_e('Ver listado', 'flavor-platform'); ?>
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
    . esc_html__('No se pudo renderizar el dashboard de mapa de actores.', 'flavor-platform')
    . '</p></div>';
