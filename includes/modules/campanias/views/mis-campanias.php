<?php
/**
 * Vista completa de campanias del usuario.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_campanias = $wpdb->prefix . 'flavor_campanias';
$tabla_participantes = $wpdb->prefix . 'flavor_campanias_participantes';
$user_id = get_current_user_id();

$mis_campanias = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, 
            (SELECT COUNT(*) FROM {$tabla_participantes} p WHERE p.campania_id = c.id AND p.estado = 'confirmado') AS total_participantes
     FROM {$tabla_campanias} c
     WHERE c.creador_id = %d
     ORDER BY c.created_at DESC",
    $user_id
));

$campanias_participando = $wpdb->get_results($wpdb->prepare(
    "SELECT c.*, p.rol,
            (SELECT COUNT(*) FROM {$tabla_participantes} p2 WHERE p2.campania_id = c.id AND p2.estado = 'confirmado') AS total_participantes
     FROM {$tabla_participantes} p
     INNER JOIN {$tabla_campanias} c ON c.id = p.campania_id
     WHERE p.user_id = %d AND p.estado = 'confirmado'
     ORDER BY c.created_at DESC",
    $user_id
));
?>

<section class="flavor-mis-campanias">
    <h2><?php esc_html_e('Mis campanias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <h3><?php esc_html_e('Como creador/a', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
    <?php if (empty($mis_campanias)): ?>
        <p><?php esc_html_e('No has creado campanias todavia.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($mis_campanias as $campania): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('campania_id', (int) $campania->id, home_url('/campanias/'))); ?>">
                                <?php echo esc_html($campania->titulo); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($campania->estado); ?></td>
                        <td><?php echo esc_html((int) $campania->firmas_actuales . '/' . (int) $campania->objetivo_firmas); ?></td>
                        <td><?php echo esc_html((int) $campania->total_participantes); ?></td>
                        <td><?php echo esc_html(mysql2date(get_option('date_format'), $campania->created_at)); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <h3 style="margin-top:1.5rem;"><?php esc_html_e('Donde participas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
    <?php if (empty($campanias_participando)): ?>
        <p><?php esc_html_e('No participas en ninguna campania por ahora.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Rol', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Estado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                    <th><?php esc_html_e('Participantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($campanias_participando as $campania): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url(add_query_arg('campania_id', (int) $campania->id, home_url('/campanias/'))); ?>">
                                <?php echo esc_html($campania->titulo); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($campania->rol); ?></td>
                        <td><?php echo esc_html($campania->estado); ?></td>
                        <td><?php echo esc_html((int) $campania->total_participantes); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
