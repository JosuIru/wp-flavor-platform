<?php
/**
 * Template: Mis Inscripciones a Talleres
 *
 * Variables disponibles:
 * - $resultado: array con 'success', 'talleres' (array de talleres inscritos)
 *
 * @package FlavorChatIA
 * @since 3.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

$talleres = $resultado['talleres'] ?? [];
?>

<div class="talleres-mis-inscripciones">
    <h2><?php _e('Mis Talleres', 'flavor-platform'); ?></h2>

    <?php if (empty($talleres)): ?>
    <div class="talleres-empty">
        <span class="dashicons dashicons-welcome-learn-more"></span>
        <p><?php _e('No estas inscrito en ningun taller.', 'flavor-platform'); ?></p>
        <a href="<?php echo esc_url(home_url('/talleres/')); ?>" class="talleres-btn talleres-btn-primary">
            <?php _e('Explorar talleres', 'flavor-platform'); ?>
        </a>
    </div>
    <?php else: ?>

    <div class="talleres-tabs">
        <button class="talleres-tab active" data-tab="proximos"><?php _e('Proximos', 'flavor-platform'); ?></button>
        <button class="talleres-tab" data-tab="pasados"><?php _e('Pasados', 'flavor-platform'); ?></button>
    </div>

    <div class="talleres-tab-content active" id="tab-proximos">
        <?php
        $proximos = array_filter($talleres, function($t) {
            return strtotime($t->fecha_inicio ?? $t->created_at) >= time();
        });
        ?>
        <?php if (empty($proximos)): ?>
        <p class="talleres-no-items"><?php _e('No tienes talleres proximos.', 'flavor-platform'); ?></p>
        <?php else: ?>
        <div class="talleres-lista">
            <?php foreach ($proximos as $taller): ?>
            <div class="talleres-card">
                <div class="talleres-card-header">
                    <h3><?php echo esc_html($taller->titulo); ?></h3>
                    <span class="talleres-badge"><?php echo esc_html(ucfirst($taller->estado ?? 'confirmado')); ?></span>
                </div>
                <div class="talleres-card-body">
                    <?php if (!empty($taller->fecha_inicio)): ?>
                    <p><strong><?php _e('Fecha:', 'flavor-platform'); ?></strong> <?php echo esc_html(date_i18n('d/m/Y H:i', strtotime($taller->fecha_inicio))); ?></p>
                    <?php endif; ?>
                    <?php if (!empty($taller->ubicacion)): ?>
                    <p><strong><?php _e('Lugar:', 'flavor-platform'); ?></strong> <?php echo esc_html($taller->ubicacion); ?></p>
                    <?php endif; ?>
                </div>
                <div class="talleres-card-footer">
                    <a href="<?php echo esc_url(add_query_arg('taller_id', $taller->id, home_url('/taller/'))); ?>" class="talleres-btn talleres-btn-secondary">
                        <?php _e('Ver detalles', 'flavor-platform'); ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <div class="talleres-tab-content" id="tab-pasados">
        <?php
        $pasados = array_filter($talleres, function($t) {
            return strtotime($t->fecha_inicio ?? $t->created_at) < time();
        });
        ?>
        <?php if (empty($pasados)): ?>
        <p class="talleres-no-items"><?php _e('No tienes talleres pasados.', 'flavor-platform'); ?></p>
        <?php else: ?>
        <div class="talleres-lista">
            <?php foreach ($pasados as $taller): ?>
            <div class="talleres-card talleres-card-pasado">
                <div class="talleres-card-header">
                    <h3><?php echo esc_html($taller->titulo); ?></h3>
                </div>
                <div class="talleres-card-body">
                    <?php if (!empty($taller->fecha_inicio)): ?>
                    <p><strong><?php _e('Fecha:', 'flavor-platform'); ?></strong> <?php echo esc_html(date_i18n('d/m/Y', strtotime($taller->fecha_inicio))); ?></p>
                    <?php endif; ?>
                </div>
                <div class="talleres-card-footer">
                    <?php if (empty($taller->valorado)): ?>
                    <button class="talleres-btn talleres-btn-primary talleres-valorar-btn" data-taller="<?php echo esc_attr($taller->id); ?>">
                        <?php _e('Valorar', 'flavor-platform'); ?>
                    </button>
                    <?php else: ?>
                    <span class="talleres-valorado"><?php _e('Valorado', 'flavor-platform'); ?> ★</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.talleres-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;
            document.querySelectorAll('.talleres-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.talleres-tab-content').forEach(c => c.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('tab-' + target).classList.add('active');
        });
    });
});
</script>
