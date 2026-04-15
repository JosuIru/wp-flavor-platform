<?php
/**
 * Vista completa de creacion de campanias.
 *
 * @package FlavorPlatform
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('flavor_campanias_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>

<section class="flavor-campanias-crear">
    <header>
        <h2><?php esc_html_e('Nueva campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <p><?php esc_html_e('Define objetivo, alcance y visibilidad para lanzar una campania ciudadana.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <form id="flavor-campania-crear-form" class="flavor-form" novalidate aria-label="<?php echo esc_attr__('Formulario de creacion de campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">
        <input type="hidden" name="action" value="campanias_crear">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
            <p>
                <label for="campania_titulo"><strong><?php esc_html_e('Titulo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_titulo" type="text" name="titulo" required style="width:100%;">
            </p>
            <p>
                <label for="campania_tipo"><strong><?php esc_html_e('Tipo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <select id="campania_tipo" name="tipo" style="width:100%;">
                    <option value="protesta"><?php esc_html_e('Protesta', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="recogida_firmas"><?php esc_html_e('Recogida de firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="concentracion"><?php esc_html_e('Concentracion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="boicot"><?php esc_html_e('Boicot', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="denuncia_publica"><?php esc_html_e('Denuncia publica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="sensibilizacion"><?php esc_html_e('Sensibilizacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="accion_legal"><?php esc_html_e('Accion legal', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="otra"><?php esc_html_e('Otra', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </p>
        </div>

        <p>
            <label for="campania_descripcion"><strong><?php esc_html_e('Descripcion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <textarea id="campania_descripcion" name="descripcion" rows="6" required style="width:100%;"></textarea>
        </p>

        <p>
            <label for="campania_objetivo"><strong><?php esc_html_e('Objetivo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
            <textarea id="campania_objetivo" name="objetivo_descripcion" rows="3" style="width:100%;"></textarea>
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p>
                <label for="campania_firmas"><strong><?php esc_html_e('Objetivo de firmas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_firmas" type="number" name="objetivo_firmas" min="0" step="1" value="0" style="width:100%;">
            </p>
            <p>
                <label for="campania_visibilidad"><strong><?php esc_html_e('Visibilidad', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <select id="campania_visibilidad" name="visibilidad" style="width:100%;">
                    <option value="publica"><?php esc_html_e('Publica', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="miembros"><?php esc_html_e('Solo miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                    <option value="privada"><?php esc_html_e('Privada', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></option>
                </select>
            </p>
            <p>
                <label for="campania_fecha_inicio"><strong><?php esc_html_e('Fecha inicio', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_fecha_inicio" type="date" name="fecha_inicio" style="width:100%;">
            </p>
            <p>
                <label for="campania_fecha_fin"><strong><?php esc_html_e('Fecha fin', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_fecha_fin" type="date" name="fecha_fin" style="width:100%;">
            </p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p>
                <label for="campania_ubicacion"><strong><?php esc_html_e('Ubicacion', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_ubicacion" type="text" name="ubicacion" style="width:100%;">
            </p>
            <p>
                <label for="campania_hashtags"><strong><?php esc_html_e('Hashtags', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></strong></label><br>
                <input id="campania_hashtags" type="text" name="hashtags" placeholder="#barrio #movilizacion" style="width:100%;">
            </p>
        </div>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Crear campania', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            <span id="flavor-campania-crear-status" style="margin-left:0.75rem;" role="status" aria-live="polite"></span>
        </p>
    </form>
</section>

<script>
(function () {
    const form = document.getElementById('flavor-campania-crear-form');
    if (!form) {
        return;
    }

    const statusEl = document.getElementById('flavor-campania-crear-status');

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
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo crear la campania.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                return;
            }

            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Campania creada.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
            if (json.data && json.data.campania_id) {
                const link = document.createElement('a');
                link.href = '<?php echo esc_url(home_url('/campanias/')); ?>?campania_id=' + String(json.data.campania_id);
                link.textContent = ' <?php echo esc_js(__('Ver campania', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
                link.style.marginLeft = '0.5rem';
                statusEl.appendChild(link);
            }
            form.reset();
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red al crear la campania.', FLAVOR_PLATFORM_TEXT_DOMAIN)); ?>';
        }
    });
})();
</script>
