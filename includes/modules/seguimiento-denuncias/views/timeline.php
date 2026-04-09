<?php
/**
 * Vista timeline dedicada.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$denuncia = null;
if (!empty($denuncia_id)) {
    $denuncia = $this->obtener_denuncia((int) $denuncia_id);
}

if (!$denuncia) {
    echo '<p>' . esc_html__('Debes indicar una denuncia valida.', 'flavor-platform') . '</p>';
    return;
}
?>

<section class="flavor-denuncia-timeline">
    <h2><?php esc_html_e('Timeline de la denuncia', 'flavor-platform'); ?></h2>
    <p><strong><?php echo esc_html($denuncia->titulo); ?></strong></p>

    <?php if (empty($denuncia->eventos)): ?>
        <p><?php esc_html_e('No hay eventos registrados en esta denuncia.', 'flavor-platform'); ?></p>
    <?php else: ?>
        <ol style="padding-left:1.2rem;">
            <?php foreach ($denuncia->eventos as $evento): ?>
                <li style="margin-bottom:0.75rem;">
                    <div><strong><?php echo esc_html($evento->titulo); ?></strong></div>
                    <div><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $evento->created_at)); ?></div>
                    <?php if (!empty($evento->descripcion)): ?>
                        <div><?php echo esc_html($evento->descripcion); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($evento->autor_nombre)): ?>
                        <small><?php echo esc_html(__('Autor', 'flavor-platform') . ': ' . $evento->autor_nombre); ?></small>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>
</section>
