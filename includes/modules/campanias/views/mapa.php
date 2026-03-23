<?php
/**
 * Vista completa de mapa de acciones.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_acciones = $wpdb->prefix . 'flavor_campanias_acciones';
$tabla_campanias = $wpdb->prefix . 'flavor_campanias';

$acciones = $wpdb->get_results(
    "SELECT a.id, a.titulo, a.tipo, a.fecha, a.ubicacion, a.latitud, a.longitud,
            c.id AS campania_id, c.titulo AS campania_titulo, c.estado AS campania_estado
     FROM {$tabla_acciones} a
     INNER JOIN {$tabla_campanias} c ON c.id = a.campania_id
     WHERE a.latitud IS NOT NULL AND a.longitud IS NOT NULL
     ORDER BY a.fecha ASC"
);

$total = count($acciones);
$proximas = 0;
$hoy = current_time('mysql');
foreach ($acciones as $accion) {
    if ($accion->fecha >= $hoy) {
        $proximas++;
    }
}
?>

<section class="flavor-campanias-mapa">
    <header>
        <h2><?php esc_html_e('Mapa de acciones ciudadanas', 'flavor-chat-ia'); ?></h2>
        <p>
            <?php
            printf(
                esc_html__('%1$d acciones geolocalizadas, %2$d proximas.', 'flavor-chat-ia'),
                (int) $total,
                (int) $proximas
            );
            ?>
        </p>
    </header>

    <?php if ($total === 0): ?>
        <p><?php esc_html_e('Todavia no hay acciones con coordenadas registradas.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Accion', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Campania', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Fecha', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Ubicacion', 'flavor-chat-ia'); ?></th>
                    <th><?php esc_html_e('Coordenadas', 'flavor-chat-ia'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($acciones as $accion): ?>
                    <tr>
                        <td><?php echo esc_html($accion->titulo); ?></td>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('campania_id', (int) $accion->campania_id, home_url('/campanias/'))); ?>">
                                <?php echo esc_html($accion->campania_titulo); ?>
                            </a>
                            <small>(<?php echo esc_html($accion->campania_estado); ?>)</small>
                        </td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $accion->fecha)); ?></td>
                        <td><?php echo esc_html($accion->ubicacion ?: '-'); ?></td>
                        <td><?php echo esc_html($accion->latitud . ', ' . $accion->longitud); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
