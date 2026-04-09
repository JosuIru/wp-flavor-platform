<?php
/**
 * Template: Logros Ecológicos
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$modulo = new Flavor_Chat_Huella_Ecologica_Module();
$logros = $modulo->get_logros_usuario($user_id);

$logros_obtenidos = array_filter($logros, fn($l) => $l['obtenido']);
$puntos_totales = array_sum(array_map(fn($l) => $l['puntos'], $logros_obtenidos));
?>

<div class="he-container">
    <header class="he-header">
        <h2>
            <span class="dashicons dashicons-awards"></span>
            <?php esc_html_e('Mis Logros Ecológicos', 'flavor-platform'); ?>
        </h2>
        <p><?php esc_html_e('Reconocimientos por tu contribución a la sostenibilidad', 'flavor-platform'); ?></p>
    </header>

    <!-- Resumen de puntos -->
    <div style="background: linear-gradient(135deg, var(--he-primary), var(--he-secondary)); border-radius: var(--he-radius); padding: 2rem; color: white; text-align: center; margin-bottom: 2rem;">
        <div style="font-size: 4rem; margin-bottom: 0.5rem;">🏆</div>
        <div style="font-size: 2.5rem; font-weight: 700;"><?php echo esc_html($puntos_totales); ?></div>
        <div style="font-size: 1.1rem; opacity: 0.9;"><?php esc_html_e('puntos ecológicos', 'flavor-platform'); ?></div>

        <div style="display: flex; justify-content: center; gap: 2rem; margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid rgba(255,255,255,0.3);">
            <div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?php echo count($logros_obtenidos); ?></div>
                <div style="font-size: 0.9rem; opacity: 0.85;"><?php esc_html_e('logros desbloqueados', 'flavor-platform'); ?></div>
            </div>
            <div>
                <div style="font-size: 1.5rem; font-weight: 600;"><?php echo count($logros) - count($logros_obtenidos); ?></div>
                <div style="font-size: 0.9rem; opacity: 0.85;"><?php esc_html_e('por desbloquear', 'flavor-platform'); ?></div>
            </div>
        </div>
    </div>

    <!-- Logros obtenidos -->
    <?php if ($logros_obtenidos) : ?>
    <section style="margin-bottom: 2rem;">
        <h3 style="margin-bottom: 1rem;">
            <span class="dashicons dashicons-yes-alt" style="color: var(--he-secondary);"></span>
            <?php esc_html_e('Logros desbloqueados', 'flavor-platform'); ?>
        </h3>
        <div class="he-logros-grid">
            <?php foreach ($logros_obtenidos as $logro) : ?>
            <div class="he-logro-card obtenido">
                <span class="he-logro-card__fecha">
                    <?php echo esc_html(date_i18n('d M Y', strtotime($logro['fecha_obtenido']))); ?>
                </span>
                <div class="he-logro-card__icono"><?php echo esc_html($logro['icono']); ?></div>
                <h4 class="he-logro-card__nombre"><?php echo esc_html($logro['nombre']); ?></h4>
                <p class="he-logro-card__descripcion"><?php echo esc_html($logro['descripcion']); ?></p>
                <span class="he-logro-card__puntos">
                    <span class="dashicons dashicons-star-filled"></span>
                    <?php echo esc_html($logro['puntos']); ?> pts
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Logros por desbloquear -->
    <?php
    $logros_pendientes = array_filter($logros, fn($l) => !$l['obtenido']);
    if ($logros_pendientes) :
    ?>
    <section>
        <h3 style="margin-bottom: 1rem;">
            <span class="dashicons dashicons-lock" style="color: var(--he-text-light);"></span>
            <?php esc_html_e('Por desbloquear', 'flavor-platform'); ?>
        </h3>
        <div class="he-logros-grid">
            <?php foreach ($logros_pendientes as $logro) : ?>
            <div class="he-logro-card bloqueado">
                <div class="he-logro-card__icono"><?php echo esc_html($logro['icono']); ?></div>
                <h4 class="he-logro-card__nombre"><?php echo esc_html($logro['nombre']); ?></h4>
                <p class="he-logro-card__descripcion"><?php echo esc_html($logro['descripcion']); ?></p>
                <span class="he-logro-card__puntos">
                    <span class="dashicons dashicons-star-empty"></span>
                    <?php echo esc_html($logro['puntos']); ?> pts
                </span>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA -->
    <div style="text-align: center; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--he-border);">
        <p style="color: var(--he-text-light); margin-bottom: 1rem;">
            <?php esc_html_e('Sigue registrando acciones ecológicas para desbloquear más logros', 'flavor-platform'); ?>
        </p>
        <a href="<?php echo esc_url(Flavor_Chat_Helpers::get_action_url('huella_ecologica', 'mis-registros')); ?>" class="he-btn he-btn--primary">
            <span class="dashicons dashicons-yes-alt"></span>
            <?php esc_html_e('Registrar acción', 'flavor-platform'); ?>
        </a>
    </div>
</div>
