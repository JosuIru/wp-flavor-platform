<?php
/**
 * Vista completa de estadisticas de denuncias.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$tabla = $wpdb->prefix . 'flavor_seguimiento_denuncias';

$totales_estado = $wpdb->get_results("SELECT estado, COUNT(*) AS total FROM {$tabla} GROUP BY estado ORDER BY total DESC");
$totales_tipo = $wpdb->get_results("SELECT tipo, COUNT(*) AS total FROM {$tabla} GROUP BY tipo ORDER BY total DESC");
$totales_prioridad = $wpdb->get_results("SELECT prioridad, COUNT(*) AS total FROM {$tabla} GROUP BY prioridad ORDER BY total DESC");

$total_general = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla}");
$abiertas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('presentada','en_tramite','requerimiento','silencio','recurrida')");
$resueltas = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$tabla} WHERE estado IN ('resuelta_favorable','resuelta_desfavorable','archivada')");
?>

<section class="flavor-denuncias-estadisticas">
    <h2><?php esc_html_e('Estadisticas de denuncias', 'flavor-chat-ia'); ?></h2>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:0.75rem;margin-bottom:1rem;">
        <div style="border:1px solid #dcdcde;border-radius:6px;padding:0.75rem;"><strong><?php esc_html_e('Total', 'flavor-chat-ia'); ?>:</strong> <?php echo esc_html($total_general); ?></div>
        <div style="border:1px solid #dcdcde;border-radius:6px;padding:0.75rem;"><strong><?php esc_html_e('Abiertas', 'flavor-chat-ia'); ?>:</strong> <?php echo esc_html($abiertas); ?></div>
        <div style="border:1px solid #dcdcde;border-radius:6px;padding:0.75rem;"><strong><?php esc_html_e('Resueltas', 'flavor-chat-ia'); ?>:</strong> <?php echo esc_html($resueltas); ?></div>
    </div>

    <h3><?php esc_html_e('Por estado', 'flavor-chat-ia'); ?></h3>
    <?php if (empty($totales_estado)): ?>
        <p><?php esc_html_e('Sin datos.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($totales_estado as $fila): ?>
                <li><?php echo esc_html($fila->estado . ': ' . $fila->total); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3><?php esc_html_e('Por tipo', 'flavor-chat-ia'); ?></h3>
    <?php if (empty($totales_tipo)): ?>
        <p><?php esc_html_e('Sin datos.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($totales_tipo as $fila): ?>
                <li><?php echo esc_html($fila->tipo . ': ' . $fila->total); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h3><?php esc_html_e('Por prioridad', 'flavor-chat-ia'); ?></h3>
    <?php if (empty($totales_prioridad)): ?>
        <p><?php esc_html_e('Sin datos.', 'flavor-chat-ia'); ?></p>
    <?php else: ?>
        <ul>
            <?php foreach ($totales_prioridad as $fila): ?>
                <li><?php echo esc_html($fila->prioridad . ': ' . $fila->total); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
