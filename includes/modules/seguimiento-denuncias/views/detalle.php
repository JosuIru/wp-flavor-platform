<?php
/**
 * Vista completa de detalle de denuncia.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

if (empty($denuncia)) {
    echo '<p>' . esc_html__('Denuncia no encontrada.', 'flavor-platform') . '</p>';
    return;
}

$nonce = wp_create_nonce('flavor_denuncias_nonce');
$ajax_url = admin_url('admin-ajax.php');
$can_update = is_user_logged_in() && ((int) $denuncia->denunciante_id === (int) get_current_user_id() || current_user_can('manage_options'));
?>

<section class="flavor-denuncia-detalle">
    <header>
        <h2><?php echo esc_html($denuncia->titulo); ?></h2>
        <p><?php echo esc_html($denuncia->tipo . ' | ' . $denuncia->estado . ' | ' . $denuncia->prioridad); ?></p>
    </header>

    <article>
        <h3><?php esc_html_e('Descripcion', 'flavor-platform'); ?></h3>
        <p><?php echo wp_kses_post(nl2br((string) $denuncia->descripcion)); ?></p>

        <h3><?php esc_html_e('Datos administrativos', 'flavor-platform'); ?></h3>
        <ul>
            <li><strong><?php esc_html_e('Organismo', 'flavor-platform'); ?>:</strong> <?php echo esc_html($denuncia->organismo_destino); ?></li>
            <li><strong><?php esc_html_e('Numero de registro', 'flavor-platform'); ?>:</strong> <?php echo esc_html($denuncia->numero_registro ?: '-'); ?></li>
            <li><strong><?php esc_html_e('Fecha de presentacion', 'flavor-platform'); ?>:</strong> <?php echo esc_html(mysql2date(get_option('date_format'), $denuncia->fecha_presentacion)); ?></li>
            <li><strong><?php esc_html_e('Fecha limite', 'flavor-platform'); ?>:</strong> <?php echo esc_html($denuncia->fecha_limite_respuesta ? mysql2date(get_option('date_format'), $denuncia->fecha_limite_respuesta) : '-'); ?></li>
            <li><strong><?php esc_html_e('Dias restantes', 'flavor-platform'); ?>:</strong> <?php echo isset($denuncia->dias_restantes) ? esc_html((string) $denuncia->dias_restantes) : '-'; ?></li>
        </ul>
    </article>

    <?php if ($can_update): ?>
        <section style="margin-top:1rem;border-top:1px solid #dcdcde;padding-top:1rem;">
            <h3><?php esc_html_e('Actualizar estado', 'flavor-platform'); ?></h3>
            <form id="flavor-denuncia-estado-form" aria-label="<?php echo esc_attr__('Formulario para actualizar el estado de la denuncia', 'flavor-platform'); ?>">
                <input type="hidden" name="action" value="denuncias_actualizar_estado">
                <input type="hidden" name="nonce" value="<?php echo esc_attr($nonce); ?>">
                <input type="hidden" name="denuncia_id" value="<?php echo esc_attr((int) $denuncia->id); ?>">

                <p><label for="den_estado_nuevo"><strong><?php esc_html_e('Nuevo estado', 'flavor-platform'); ?></strong></label><br>
                <input id="den_estado_nuevo" type="text" name="estado" required style="width:100%;max-width:320px;"></p>

                <p><label for="den_estado_nota"><strong><?php esc_html_e('Nota', 'flavor-platform'); ?></strong></label><br>
                <textarea id="den_estado_nota" name="nota" rows="3" style="width:100%;max-width:640px;"></textarea></p>

                <p>
                    <button type="submit" class="button"><?php esc_html_e('Guardar estado', 'flavor-platform'); ?></button>
                    <span id="flavor-denuncia-estado-status" style="margin-left:0.75rem;" role="status" aria-live="polite"></span>
                </p>
            </form>
        </section>
    <?php endif; ?>

    <section style="margin-top:1rem;">
        <h3><?php esc_html_e('Timeline', 'flavor-platform'); ?></h3>
        <?php if (empty($denuncia->eventos)): ?>
            <p><?php esc_html_e('Sin eventos registrados.', 'flavor-platform'); ?></p>
        <?php else: ?>
            <ul>
                <?php foreach ($denuncia->eventos as $evento): ?>
                    <li>
                        <strong><?php echo esc_html(mysql2date(get_option('date_format') . ' ' . get_option('time_format'), $evento->created_at)); ?></strong>
                        - <?php echo esc_html($evento->titulo); ?>
                        <?php if (!empty($evento->descripcion)): ?>
                            <div><?php echo esc_html($evento->descripcion); ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</section>

<script>
(function () {
    const form = document.getElementById('flavor-denuncia-estado-form');
    if (!form) {
        return;
    }
    const statusEl = document.getElementById('flavor-denuncia-estado-status');

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
                statusEl.textContent = (json.data && json.data.error) ? json.data.error : '<?php echo esc_js(__('No se pudo actualizar.', 'flavor-platform')); ?>';
                return;
            }
            statusEl.textContent = (json.data && json.data.mensaje) ? json.data.mensaje : '<?php echo esc_js(__('Estado actualizado.', 'flavor-platform')); ?>';
            window.setTimeout(function () { window.location.reload(); }, 600);
        } catch (error) {
            statusEl.textContent = '<?php echo esc_js(__('Error de red.', 'flavor-platform')); ?>';
        }
    });
})();
</script>
