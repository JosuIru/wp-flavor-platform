<?php
/**
 * Vista completa para crear denuncia.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('flavor_denuncias_nonce');
$ajax_url = admin_url('admin-ajax.php');
?>

<section class="flavor-denuncias-crear">
    <h2><?php esc_html_e('Registrar denuncia', 'flavor-chat-ia'); ?></h2>

    <form id="flavor-denuncia-crear-form" novalidate aria-label="<?php echo esc_attr__('Formulario de registro de denuncia', 'flavor-chat-ia'); ?>">
        <input type="hidden" name="action" value="denuncias_crear">
        <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1rem;">
            <p><label for="denuncia_titulo"><strong><?php esc_html_e('Titulo', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_titulo" type="text" name="titulo" required style="width:100%;"></p>
            <p><label for="denuncia_tipo"><strong><?php esc_html_e('Tipo', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_tipo" type="text" name="tipo" value="denuncia" style="width:100%;"></p>
            <p><label for="denuncia_categoria"><strong><?php esc_html_e('Categoria', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_categoria" type="text" name="categoria" style="width:100%;"></p>
            <p><label for="denuncia_ambito"><strong><?php esc_html_e('Ambito', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_ambito" type="text" name="ambito" value="municipal" style="width:100%;"></p>
            <p><label for="denuncia_organismo_destino"><strong><?php esc_html_e('Organismo destino', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_organismo_destino" type="text" name="organismo_destino" required style="width:100%;"></p>
            <p><label for="denuncia_numero_registro"><strong><?php esc_html_e('Numero registro', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_numero_registro" type="text" name="numero_registro" style="width:100%;"></p>
            <p><label for="denuncia_fecha_presentacion"><strong><?php esc_html_e('Fecha presentacion', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_fecha_presentacion" type="date" name="fecha_presentacion" value="<?php echo esc_attr(current_time('Y-m-d')); ?>" style="width:100%;"></p>
            <p><label for="denuncia_plazo_respuesta"><strong><?php esc_html_e('Plazo respuesta (dias)', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_plazo_respuesta" type="number" min="1" max="365" name="plazo_respuesta" value="30" style="width:100%;"></p>
            <p><label for="denuncia_prioridad"><strong><?php esc_html_e('Prioridad', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_prioridad" type="text" name="prioridad" value="media" style="width:100%;"></p>
            <p><label for="denuncia_visibilidad"><strong><?php esc_html_e('Visibilidad', 'flavor-chat-ia'); ?></strong></label><br><input id="denuncia_visibilidad" type="text" name="visibilidad" value="miembros" style="width:100%;"></p>
        </div>

        <p><label for="denuncia_descripcion"><strong><?php esc_html_e('Descripcion', 'flavor-chat-ia'); ?></strong></label><br><textarea id="denuncia_descripcion" name="descripcion" rows="6" required style="width:100%;"></textarea></p>

        <p>
            <button type="submit" class="button button-primary"><?php esc_html_e('Registrar denuncia', 'flavor-chat-ia'); ?></button>
            <span id="flavor-denuncia-crear-status" style="margin-left:0.75rem;" role="status" aria-live="polite"></span>
        </p>
    </form>
</section>

<script>
(function () {
    const form = document.getElementById('flavor-denuncia-crear-form');
    if (!form) {
        return;
    }
    const statusEl = document.getElementById('flavor-denuncia-crear-status');

    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        statusEl.textContent = '<?php echo esc_js(__('Guardando...', 'flavor-chat-ia')); ?>';

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
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo registrar.', 'flavor-chat-ia')); ?>';
                return;
            }
            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Denuncia registrada.', 'flavor-chat-ia')); ?>';
            if (json.data && json.data.denuncia_id) {
                const link = document.createElement('a');
                link.href = window.location.pathname + '?denuncia_id=' + String(json.data.denuncia_id);
                link.textContent = ' <?php echo esc_js(__('Ver denuncia', 'flavor-chat-ia')); ?>';
                link.style.marginLeft = '0.5rem';
                statusEl.appendChild(link);
            }
            form.reset();
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red.', 'flavor-chat-ia')); ?>';
        }
    });
})();
</script>
