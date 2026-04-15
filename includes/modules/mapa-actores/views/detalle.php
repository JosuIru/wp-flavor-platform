<?php
/**
 * Vista completa de detalle de actor.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($actor)) {
    echo '<p>' . esc_html__('Actor no encontrado.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    return;
}
?>

<section class="flavor-actor-detalle">
    <header>
        <h2><?php echo esc_html($actor->nombre); ?></h2>
        <p><?php echo esc_html($actor->tipo . ' | ' . $actor->ambito . ' | ' . $actor->posicion_general); ?></p>
    </header>

    <div style="display:grid;grid-template-columns:2fr 1fr;gap:1rem;align-items:start;">
        <article>
            <h3><?php esc_html_e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php echo wp_kses_post(nl2br((string) $actor->descripcion)); ?></p>

            <h3><?php esc_html_e('Competencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php echo esc_html($actor->competencias ?: __('Sin especificar', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?></p>

            <h3><?php esc_html_e('Relaciones salientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if (empty($actor->relaciones_salientes)): ?>
                <p><?php esc_html_e('Sin relaciones salientes registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <ul>
                    <?php foreach ($actor->relaciones_salientes as $relacion): ?>
                        <li><?php echo esc_html($relacion->tipo_relacion . ' -> ' . ($relacion->actor_destino_nombre ?: '#'.$relacion->actor_destino_id)); ?> (<?php echo esc_html($relacion->intensidad); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <h3><?php esc_html_e('Relaciones entrantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if (empty($actor->relaciones_entrantes)): ?>
                <p><?php esc_html_e('Sin relaciones entrantes registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <ul>
                    <?php foreach ($actor->relaciones_entrantes as $relacion): ?>
                        <li><?php echo esc_html(($relacion->actor_origen_nombre ?: '#'.$relacion->actor_origen_id) . ' -> ' . $relacion->tipo_relacion); ?> (<?php echo esc_html($relacion->intensidad); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </article>

        <aside>
            <h3><?php esc_html_e('Ficha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <ul>
                <li><strong><?php esc_html_e('Influencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($actor->nivel_influencia); ?></li>
                <li><strong><?php esc_html_e('Municipio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($actor->municipio ?: '-'); ?></li>
                <li><strong><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($actor->email ?: '-'); ?></li>
                <li><strong><?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>:</strong> <?php echo esc_html($actor->telefono ?: '-'); ?></li>
            </ul>

            <h3><?php esc_html_e('Personas clave', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <?php if (empty($actor->personas)): ?>
                <p><?php esc_html_e('Sin personas registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
            <?php else: ?>
                <ul>
                    <?php foreach ($actor->personas as $persona): ?>
                        <li><?php echo esc_html($persona->nombre . ($persona->cargo ? ' - ' . $persona->cargo : '')); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </aside>
    </div>

    <h3 style="margin-top:1.5rem;"><?php esc_html_e('Interacciones recientes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
    <?php if (empty($actor->interacciones)): ?>
        <p><?php esc_html_e('Sin interacciones registradas.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    <?php else: ?>
        <table class="widefat striped">
            <thead><tr><th><?php esc_html_e('Fecha', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th><th><?php esc_html_e('Resultado', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></th></tr></thead>
            <tbody>
                <?php foreach ($actor->interacciones as $interaccion): ?>
                    <tr>
                        <td><?php echo esc_html(mysql2date(get_option('date_format'), $interaccion->fecha)); ?></td>
                        <td><?php echo esc_html($interaccion->tipo); ?></td>
                        <td><?php echo esc_html($interaccion->titulo); ?></td>
                        <td><?php echo esc_html($interaccion->resultado); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
