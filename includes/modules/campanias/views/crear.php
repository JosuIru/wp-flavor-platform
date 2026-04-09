<?php
/**
 * Vista completa de creacion de campanias.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('flavor_campanias_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>

<section class="flavor-campanias-crear">
    <header>
        <h2><?php esc_html_e('Nueva campania', 'flavor-platform'); ?></h2>
        <p><?php esc_html_e('Define objetivo, alcance y visibilidad para lanzar una campania ciudadana.', 'flavor-platform'); ?></p>
    </header>

    <form id="flavor-campania-crear-form" class="flavor-form" novalidate aria-label="<?php echo esc_attr__('Formulario de creacion de campania', 'flavor-platform'); ?>">
        <input type="hidden" name="action" value="campanias_crear">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1rem;">
            <p>
                <label for="campania_titulo"><strong><?php esc_html_e('Titulo', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_titulo" type="text" name="titulo" required style="width:100%;">
            </p>
            <p>
                <label for="campania_tipo"><strong><?php esc_html_e('Tipo', 'flavor-platform'); ?></strong></label><br>
                <select id="campania_tipo" name="tipo" style="width:100%;">
                    <option value="protesta"><?php esc_html_e('Protesta', 'flavor-platform'); ?></option>
                    <option value="recogida_firmas"><?php esc_html_e('Recogida de firmas', 'flavor-platform'); ?></option>
                    <option value="concentracion"><?php esc_html_e('Concentracion', 'flavor-platform'); ?></option>
                    <option value="boicot"><?php esc_html_e('Boicot', 'flavor-platform'); ?></option>
                    <option value="denuncia_publica"><?php esc_html_e('Denuncia publica', 'flavor-platform'); ?></option>
                    <option value="sensibilizacion"><?php esc_html_e('Sensibilizacion', 'flavor-platform'); ?></option>
                    <option value="accion_legal"><?php esc_html_e('Accion legal', 'flavor-platform'); ?></option>
                    <option value="otra"><?php esc_html_e('Otra', 'flavor-platform'); ?></option>
                </select>
            </p>
        </div>

        <p>
            <label for="campania_descripcion"><strong><?php esc_html_e('Descripcion', 'flavor-platform'); ?></strong></label><br>
            <textarea id="campania_descripcion" name="descripcion" rows="6" required style="width:100%;"></textarea>
        </p>

        <p>
            <label for="campania_objetivo"><strong><?php esc_html_e('Objetivo', 'flavor-platform'); ?></strong></label><br>
            <textarea id="campania_objetivo" name="objetivo_descripcion" rows="3" style="width:100%;"></textarea>
        </p>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p>
                <label for="campania_firmas"><strong><?php esc_html_e('Objetivo de firmas', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_firmas" type="number" name="objetivo_firmas" min="0" step="1" value="0" style="width:100%;">
            </p>
            <p>
                <label for="campania_visibilidad"><strong><?php esc_html_e('Visibilidad', 'flavor-platform'); ?></strong></label><br>
                <select id="campania_visibilidad" name="visibilidad" style="width:100%;">
                    <option value="publica"><?php esc_html_e('Publica', 'flavor-platform'); ?></option>
                    <option value="miembros"><?php esc_html_e('Solo miembros', 'flavor-platform'); ?></option>
                    <option value="privada"><?php esc_html_e('Privada', 'flavor-platform'); ?></option>
                </select>
            </p>
            <p>
                <label for="campania_fecha_inicio"><strong><?php esc_html_e('Fecha inicio', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_fecha_inicio" type="date" name="fecha_inicio" style="width:100%;">
            </p>
            <p>
                <label for="campania_fecha_fin"><strong><?php esc_html_e('Fecha fin', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_fecha_fin" type="date" name="fecha_fin" style="width:100%;">
            </p>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p>
                <label for="campania_ubicacion"><strong><?php esc_html_e('Ubicacion', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_ubicacion" type="text" name="ubicacion" style="width:100%;">
            </p>
            <p>
                <label for="campania_hashtags"><strong><?php esc_html_e('Hashtags', 'flavor-platform'); ?></strong></label><br>
                <input id="campania_hashtags" type="text" name="hashtags" placeholder="#barrio #movilizacion" style="width:100%;">
            </p>
        </div>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Crear campania', 'flavor-platform'); ?></button>
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
        statusEl.textContent = '<?php echo esc_js(__('Guardando...', 'flavor-platform')); ?>';

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
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo crear la campania.', 'flavor-platform')); ?>';
                return;
            }

            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Campania creada.', 'flavor-platform')); ?>';
            if (json.data && json.data.campania_id) {
                const link = document.createElement('a');
                link.href = '<?php echo esc_url(home_url('/campanias/')); ?>?campania_id=' + String(json.data.campania_id);
                link.textContent = ' <?php echo esc_js(__('Ver campania', 'flavor-platform')); ?>';
                link.style.marginLeft = '0.5rem';
                statusEl.appendChild(link);
            }
            form.reset();
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red al crear la campania.', 'flavor-platform')); ?>';
        }
    });
})();
</script>
