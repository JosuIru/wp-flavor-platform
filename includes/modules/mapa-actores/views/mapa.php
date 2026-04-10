<?php
/**
 * Vista territorial de actores por municipio.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_mapa_actores';

$municipios = $wpdb->get_results(
    "SELECT municipio, COUNT(*) AS total,
            SUM(CASE WHEN posicion_general = 'aliado' THEN 1 ELSE 0 END) AS aliados,
            SUM(CASE WHEN posicion_general = 'opositor' THEN 1 ELSE 0 END) AS opositores
     FROM {$tabla}
     WHERE activo = 1 AND municipio <> ''
     GROUP BY municipio
     ORDER BY total DESC, municipio ASC"
);
?>

<section class="flavor-actores-mapa">
    <h2><?php esc_html_e('Mapa territorial de actores', 'flavor-platform'); ?></h2>
    <p><?php esc_html_e('Distribucion por municipio y balance de posicion.', 'flavor-platform'); ?></p>

    <?php if (empty($municipios)): ?>
        <p><?php esc_html_e('No hay municipios con datos disponibles.', 'flavor-platform'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Municipio', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Total actores', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Aliados', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Opositores', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($municipios as $fila): ?>
                    <tr>
                        <td><?php echo esc_html($fila->municipio); ?></td>
                        <td><?php echo esc_html((int) $fila->total); ?></td>
                        <td><?php echo esc_html((int) $fila->aliados); ?></td>
                        <td><?php echo esc_html((int) $fila->opositores); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
