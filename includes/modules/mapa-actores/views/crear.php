<?php
/**
 * Vista completa para crear actor.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('flavor_actores_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>

<section class="flavor-actores-crear">
    <h2><?php esc_html_e('Registrar actor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>

    <form id="flavor-actor-crear-form" novalidate>
        <input type="hidden" name="action" value="actores_crear">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p><label for="actor_nombre"><strong><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_nombre" type="text" name="nombre" required style="width:100%;"></p>
            <p><label for="actor_nombre_corto"><strong><?php esc_html_e('Nombre corto', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_nombre_corto" type="text" name="nombre_corto" style="width:100%;"></p>
            <p><label for="actor_tipo"><strong><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_tipo" type="text" name="tipo" value="otro" style="width:100%;"></p>
            <p><label for="actor_ambito"><strong><?php esc_html_e('Ambito', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_ambito" type="text" name="ambito" value="local" style="width:100%;"></p>
            <p><label for="actor_posicion_general"><strong><?php esc_html_e('Posicion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_posicion_general" type="text" name="posicion_general" value="desconocido" style="width:100%;"></p>
            <p><label for="actor_nivel_influencia"><strong><?php esc_html_e('Influencia', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_nivel_influencia" type="text" name="nivel_influencia" value="medio" style="width:100%;"></p>
        </div>

        <p><label for="actor_descripcion"><strong><?php esc_html_e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><textarea id="actor_descripcion" name="descripcion" rows="5" style="width:100%;"></textarea></p>
        <p><label for="actor_competencias"><strong><?php esc_html_e('Competencias', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><textarea id="actor_competencias" name="competencias" rows="3" style="width:100%;"></textarea></p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p><label for="actor_municipio"><strong><?php esc_html_e('Municipio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_municipio" type="text" name="municipio" style="width:100%;"></p>
            <p><label for="actor_email"><strong><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_email" type="email" name="email" style="width:100%;"></p>
            <p><label for="actor_telefono"><strong><?php esc_html_e('Telefono', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_telefono" type="text" name="telefono" style="width:100%;"></p>
            <p><label for="actor_web"><strong><?php esc_html_e('Web', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br><input id="actor_web" type="url" name="web" style="width:100%;"></p>
        </div>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Guardar actor', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <span id="flavor-actor-crear-status" style="margin-left:0.75rem;" role="status" aria-live="polite"></span>
        </p>
    </form>
</section>

<script>
(function () {
    const form = document.getElementById('flavor-actor-crear-form');
    if (!form) {
        return;
    }
    const statusEl = document.getElementById('flavor-actor-crear-status');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        statusEl.textContent = '<?php echo esc_js(__('Guardando...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

        const body = new URLSearchParams(new FormData(form));
        try {
            const response = await fetch('<?php echo esc_url($ajax_url); ?>', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
                body: body.toString()
            });
            const json = await response.json();
            if (!json.success) {
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo guardar.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                return;
            }
            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Actor guardado.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
            form.reset();
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
        }
    });
})();
</script>
