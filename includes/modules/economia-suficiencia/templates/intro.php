<?php
/**
 * Template: Introducción a la Economía de Suficiencia
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="es-container">
    <header class="es-header">
        <h2><?php esc_html_e('Economía de Suficiencia', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('¿Y si "suficiente" fuera abundancia? Descubre otra forma de relacionarte con las necesidades y los recursos.', 'flavor-platform'); ?></p>
    </header>

    <div class="es-cita">
        <p class="es-cita__texto"><?php esc_html_e('La pobreza no es tener poco, sino necesitar mucho.', 'flavor-platform'); ?></p>
        <span class="es-cita__autor">— Séneca</span>
    </div>

    <!-- Principios -->
    <div class="es-principios-grid">
        <div class="es-principio">
            <div class="es-principio__icono">🌱</div>
            <h3 class="es-principio__titulo"><?php esc_html_e('Suficiente es abundancia', 'flavor-platform'); ?></h3>
            <p class="es-principio__descripcion">
                <?php esc_html_e('Cuando identificamos nuestras necesidades reales, descubrimos que tenemos más de lo que necesitamos.', 'flavor-platform'); ?>
            </p>
        </div>

        <div class="es-principio">
            <div class="es-principio__icono">🤝</div>
            <h3 class="es-principio__titulo"><?php esc_html_e('Compartir multiplica', 'flavor-platform'); ?></h3>
            <p class="es-principio__descripcion">
                <?php esc_html_e('Los recursos compartidos benefician a más personas sin aumentar el consumo total.', 'flavor-platform'); ?>
            </p>
        </div>

        <div class="es-principio">
            <div class="es-principio__icono">⏰</div>
            <h3 class="es-principio__titulo"><?php esc_html_e('Tiempo sobre dinero', 'flavor-platform'); ?></h3>
            <p class="es-principio__descripcion">
                <?php esc_html_e('La verdadera riqueza es tener tiempo para lo que importa: relaciones, creatividad, descanso.', 'flavor-platform'); ?>
            </p>
        </div>

        <div class="es-principio">
            <div class="es-principio__icono">🔄</div>
            <h3 class="es-principio__titulo"><?php esc_html_e('Cerrar ciclos', 'flavor-platform'); ?></h3>
            <p class="es-principio__descripcion">
                <?php esc_html_e('Reparar, reutilizar, reciclar. Cada objeto tiene múltiples vidas cuando cuidamos su ciclo.', 'flavor-platform'); ?>
            </p>
        </div>
    </div>

    <!-- Necesidades Max-Neef -->
    <section style="margin: 3rem 0;">
        <h3 style="text-align: center; margin-bottom: 2rem;">
            <?php esc_html_e('Las 9 necesidades humanas fundamentales', 'flavor-platform'); ?>
        </h3>
        <p style="text-align: center; color: var(--es-text-light); max-width: 600px; margin: 0 auto 2rem;">
            <?php esc_html_e('Según Manfred Max-Neef, estas son las necesidades universales. El bienestar no viene de acumular, sino de satisfacerlas de forma equilibrada.', 'flavor-platform'); ?>
        </p>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 1rem;">
            <?php
            $economia_suficiencia_module_class = function_exists('flavor_get_runtime_class_name')
                ? flavor_get_runtime_class_name('Flavor_Chat_Economia_Suficiencia_Module')
                : 'Flavor_Chat_Economia_Suficiencia_Module';
            $categorias = $economia_suficiencia_module_class::CATEGORIAS_NECESIDADES;
            foreach ($categorias as $cat_id => $cat_data) :
            ?>
            <div class="es-card" style="text-align: center; border-top: 3px solid <?php echo esc_attr($cat_data['color']); ?>">
                <span class="dashicons <?php echo esc_attr($cat_data['icono']); ?>" style="font-size: 2rem; color: <?php echo esc_attr($cat_data['color']); ?>; margin-bottom: 0.5rem;"></span>
                <h4 style="margin: 0.5rem 0 0.25rem;"><?php echo esc_html($cat_data['nombre']); ?></h4>
                <p style="font-size: 0.85rem; color: var(--es-text-light); margin: 0;">
                    <?php echo esc_html($cat_data['descripcion']); ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- CTA -->
    <?php if (is_user_logged_in()) : ?>
    <div style="text-align: center; margin-top: 3rem; padding-top: 2rem; border-top: 1px solid var(--es-border);">
        <h3><?php esc_html_e('¿Listo/a para empezar?', 'flavor-platform'); ?></h3>
        <p style="color: var(--es-text-light); margin-bottom: 1.5rem;">
            <?php esc_html_e('Evalúa cómo estás satisfaciendo tus necesidades y descubre tu camino hacia la suficiencia.', 'flavor-platform'); ?>
        </p>
        <a href="<?php echo esc_url(Flavor_Platform_Helpers::get_action_url('economia_suficiencia', 'evaluacion')); ?>" class="es-btn es-btn--primary">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php esc_html_e('Evaluar mis necesidades', 'flavor-platform'); ?>
        </a>
    </div>
    <?php endif; ?>
</div>
