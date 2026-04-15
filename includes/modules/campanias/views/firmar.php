<?php
/**
 * Vista completa para firmar campania.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$campania = null;
if (!empty($campania_id)) {
    $campania = $this->obtener_campania((int) $campania_id);
}

if (!$campania) {
    echo '<p>' . esc_html__('Campania no encontrada.', FLAVOR_PLATFORM_TEXT_DOMAIN) . '</p>';
    return;
}

$nonce = wp_create_nonce('flavor_campanias_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>

<section class="flavor-campania-firma">
    <header>
        <h2><?php esc_html_e('Firmar campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><strong><?php echo esc_html($campania->titulo); ?></strong></p>
        <?php if (!empty($campania->objetivo_descripcion)): ?>
            <p><?php echo esc_html($campania->objetivo_descripcion); ?></p>
        <?php endif; ?>
        <p>
            <?php
            printf(
                esc_html__('%1$d firmas actuales de %2$d objetivo.', FLAVOR_PLATFORM_TEXT_DOMAIN),
                (int) $campania->firmas_actuales,
                (int) $campania->objetivo_firmas
            );
            ?>
        </p>
    </header>

    <form id="flavor-campania-firmar-form" novalidate aria-label="<?php echo esc_attr__('Formulario para firmar campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <input type="hidden" name="action" value="campanias_firmar">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
        <input type="hidden" name="campania_id" value="<?php echo esc_attr((int) $campania->id); ?>">

        <p>
            <label for="firma_nombre"><strong><?php esc_html_e('Nombre', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <input id="firma_nombre" type="text" name="nombre" required style="width:100%;max-width:540px;">
        </p>
        <p>
            <label for="firma_email"><strong><?php esc_html_e('Email', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <input id="firma_email" type="email" name="email" required style="width:100%;max-width:540px;">
        </p>
        <p>
            <label for="firma_localidad"><strong><?php esc_html_e('Localidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <input id="firma_localidad" type="text" name="localidad" style="width:100%;max-width:540px;">
        </p>
        <p>
            <label for="firma_comentario"><strong><?php esc_html_e('Comentario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <textarea id="firma_comentario" name="comentario" rows="4" style="width:100%;max-width:640px;"></textarea>
        </p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Registrar firma', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <span id="flavor-campania-firmar-status" style="margin-left:0.75rem;" role="status" aria-live="polite"></span>
        </p>
    </form>
</section>

<script>
(function () {
    const form = document.getElementById('flavor-campania-firmar-form');
    if (!form) {
        return;
    }

    const statusEl = document.getElementById('flavor-campania-firmar-status');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        statusEl.textContent = '<?php echo esc_js(__('Enviando firma...', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';

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
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo registrar la firma.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                return;
            }
            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Firma registrada.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
            form.reset();
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
        }
    });
})();
</script>
