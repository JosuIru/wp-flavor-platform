<?php
/**
 * Vista de grafo de relaciones (tabla de aristas).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla_actores = $wpdb->prefix . 'flavor_mapa_actores';
$tabla_relaciones = $wpdb->prefix . 'flavor_mapa_actores_relaciones';

$relaciones = $wpdb->get_results(
    "SELECT r.actor_origen_id, r.actor_destino_id, r.tipo_relacion, r.intensidad, r.bidireccional,
            o.nombre AS origen_nombre, d.nombre AS destino_nombre
     FROM {$tabla_relaciones} r
     LEFT JOIN {$tabla_actores} o ON o.id = r.actor_origen_id
     LEFT JOIN {$tabla_actores} d ON d.id = r.actor_destino_id
     ORDER BY r.id DESC
     LIMIT 300"
);
?>

<section class="flavor-actores-grafo">
    <h2><?php esc_html_e('Red de relaciones', 'flavor-platform'); ?></h2>
    <p><?php esc_html_e('Listado de enlaces entre actores para analisis de red.', 'flavor-platform'); ?></p>

    <?php if (empty($relaciones)): ?>
        <p><?php esc_html_e('No hay relaciones registradas.', 'flavor-platform'); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Origen', 'flavor-platform'); ?></th><th><?php esc_html_e('Relacion', 'flavor-platform'); ?></th><th><?php esc_html_e('Destino', 'flavor-platform'); ?></th><th><?php esc_html_e('Intensidad', 'flavor-platform'); ?></th><th><?php esc_html_e('Bidireccional', 'flavor-platform'); ?></th></tr></thead>
            <tbody>
                <?php foreach ($relaciones as $relacion): ?>
                    <tr>
                        <td><?php echo esc_html($relacion->origen_nombre ?: '#'.$relacion->actor_origen_id); ?></td>
                        <td><?php echo esc_html($relacion->tipo_relacion); ?></td>
                        <td><?php echo esc_html($relacion->destino_nombre ?: '#'.$relacion->actor_destino_id); ?></td>
                        <td><?php echo esc_html($relacion->intensidad); ?></td>
                        <td><?php echo esc_html((int) $relacion->bidireccional === 1 ? __('Si', 'flavor-platform') : __('No', 'flavor-platform')); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
