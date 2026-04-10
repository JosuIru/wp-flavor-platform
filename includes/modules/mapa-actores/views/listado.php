<?php
/**
 * Vista completa de listado de actores.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_mapa_actores';

$tipo = sanitize_text_field($atts['tipo'] ?? $_GET['tipo'] ?? '');
$ambito = sanitize_text_field($atts['ambito'] ?? $_GET['ambito'] ?? '');
$posicion = sanitize_text_field($atts['posicion'] ?? $_GET['posicion'] ?? '');
$busqueda = sanitize_text_field($_GET['q'] ?? '');

$where = ["activo = 1"];
$params = [];

if ($tipo !== '') {
    $where[] = 'tipo = %s';
    $params[] = $tipo;
}
if ($ambito !== '') {
    $where[] = 'ambito = %s';
    $params[] = $ambito;
}
if ($posicion !== '') {
    $where[] = 'posicion_general = %s';
    $params[] = $posicion;
}
if ($busqueda !== '') {
    $where[] = '(nombre LIKE %s OR descripcion LIKE %s OR competencias LIKE %s)';
    $like = '%' . $wpdb->esc_like($busqueda) . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql = "SELECT id, nombre, tipo, ambito, posicion_general, nivel_influencia, municipio, web
        FROM {$tabla}
        WHERE " . implode(' AND ', $where) . "
        ORDER BY nivel_influencia DESC, nombre ASC";

$actores = empty($params) ? $wpdb->get_results($sql) : $wpdb->get_results($wpdb->prepare($sql, $params));
?>

<section class="flavor-actores-listado">
    <header>
        <h2><?php esc_html_e('Directorio de actores', 'flavor-platform'); ?></h2>
        <form method="get" aria-label="<?php echo esc_attr__('Formulario de filtros del directorio de actores', 'flavor-platform'); ?>" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:0.5rem;align-items:end;">
            <p>
                <label for="act_q"><?php esc_html_e('Buscar', 'flavor-platform'); ?></label><br>
                <input id="act_q" type="text" name="q" value="<?php echo esc_attr($busqueda); ?>" style="width:100%;">
            </p>
            <p>
                <label for="act_tipo"><?php esc_html_e('Tipo', 'flavor-platform'); ?></label><br>
                <input id="act_tipo" type="text" name="tipo" value="<?php echo esc_attr($tipo); ?>" style="width:100%;">
            </p>
            <p>
                <label for="act_ambito"><?php esc_html_e('Ambito', 'flavor-platform'); ?></label><br>
                <input id="act_ambito" type="text" name="ambito" value="<?php echo esc_attr($ambito); ?>" style="width:100%;">
            </p>
            <p>
                <label for="act_posicion"><?php esc_html_e('Posicion', 'flavor-platform'); ?></label><br>
                <input id="act_posicion" type="text" name="posicion" value="<?php echo esc_attr($posicion); ?>" style="width:100%;">
            </p>
            <p><button type="submit" class="button"><?php esc_html_e('Aplicar', 'flavor-platform'); ?></button></p>
        </form>
    </header>

    <?php if (empty($actores)): ?>
        <p><?php esc_html_e('No se encontraron actores con los filtros actuales.', 'flavor-platform'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Nombre', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Ambito', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Posicion', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Influencia', 'flavor-platform'); ?></th>
                    <th><?php esc_html_e('Municipio', 'flavor-platform'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actores as $actor): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('actor_id', (int) $actor->id)); ?>">
                                <?php echo esc_html($actor->nombre); ?>
                            </a>
                            <?php if (!empty($actor->web)): ?>
                                <div><a href="<?php echo esc_url($actor->web); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('Web', 'flavor-platform'); ?></a></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($actor->tipo); ?></td>
                        <td><?php echo esc_html($actor->ambito); ?></td>
                        <td><?php echo esc_html($actor->posicion_general); ?></td>
                        <td><?php echo esc_html($actor->nivel_influencia); ?></td>
                        <td><?php echo esc_html($actor->municipio ?: '-'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
