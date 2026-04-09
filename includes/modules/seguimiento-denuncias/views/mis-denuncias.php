<?php
/**
 * Vista completa de denuncias propias.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';
$user_id = get_current_user_id();

$denuncias = $wpdb->get_results($wpdb->prepare(
    "SELECT id, titulo, tipo, estado, prioridad, organismo_destino, fecha_presentacion, fecha_limite_respuesta
     FROM {$tabla}
     WHERE denunciante_id = %d
     ORDER BY created_at DESC",
    $user_id
));
?>

<section class="flavor-mis-denuncias">
    <h2><?php esc_html_e('Mis denuncias', 'flavor-platform'); ?></h2>

    <?php if (empty($denuncias)): ?>
        <p><?php esc_html_e('Aun no has registrado denuncias.', 'flavor-platform'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Titulo', 'flavor-platform'); ?></th><th><?php esc_html_e('Tipo', 'flavor-platform'); ?></th><th><?php esc_html_e('Estado', 'flavor-platform'); ?></th><th><?php esc_html_e('Prioridad', 'flavor-platform'); ?></th><th><?php esc_html_e('Organismo', 'flavor-platform'); ?></th><th><?php esc_html_e('Fechas', 'flavor-platform'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($denuncias as $denuncia): ?>
                    <tr>
                        <td><a href="<?php echo esc_url(add_query_arg('denuncia_id', (int) $denuncia->id)); ?>"><?php echo esc_html($denuncia->titulo); ?></a></td>
                        <td><?php echo esc_html($denuncia->tipo); ?></td>
                        <td><?php echo esc_html($denuncia->estado); ?></td>
                        <td><?php echo esc_html($denuncia->prioridad); ?></td>
                        <td><?php echo esc_html($denuncia->organismo_destino); ?></td>
                        <td>
                            <?php echo esc_html(mysql2date(get_option('date_format'), $denuncia->fecha_presentacion)); ?>
                            <?php if (!empty($denuncia->fecha_limite_respuesta)): ?>
                                - <?php echo esc_html(mysql2date(get_option('date_format'), $denuncia->fecha_limite_respuesta)); ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
