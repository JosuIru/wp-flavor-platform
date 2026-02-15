<?php
/**
 * Template: Formulario para Donar Horas
 *
 * @package FlavorChatIA
 * @subpackage BancoTiempo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var float $saldo Saldo actual del usuario
 * @var string $nonce Nonce de seguridad
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bt-donar" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="bt-donar__header">
        <span class="bt-donar__icono">
            <span class="dashicons dashicons-heart"></span>
        </span>
        <h3 class="bt-donar__titulo"><?php esc_html_e('Donar Horas', 'flavor-chat-ia'); ?></h3>
    </div>

    <div class="bt-donar__saldo">
        <span class="bt-donar__saldo-label"><?php esc_html_e('Tu saldo actual:', 'flavor-chat-ia'); ?></span>
        <span class="bt-donar__saldo-valor <?php echo $saldo < 0 ? 'negativo' : ''; ?>">
            <?php echo esc_html(number_format($saldo, 1)); ?> <?php esc_html_e('horas', 'flavor-chat-ia'); ?>
        </span>
    </div>

    <?php if ($saldo <= 0): ?>
        <div class="bt-donar__aviso">
            <span class="dashicons dashicons-info"></span>
            <p><?php esc_html_e('No tienes horas disponibles para donar. Ofrece tus servicios para acumular horas.', 'flavor-chat-ia'); ?></p>
        </div>
    <?php else: ?>
        <form class="bt-donar__form" id="bt-form-donar">
            <div class="bt-donar__tipo-selector">
                <label class="bt-donar__tipo-opcion">
                    <input type="radio" name="tipo" value="fondo_comunitario" checked>
                    <span class="bt-donar__tipo-card">
                        <span class="dashicons dashicons-groups"></span>
                        <strong><?php esc_html_e('Fondo Solidario', 'flavor-chat-ia'); ?></strong>
                        <small><?php esc_html_e('Para quienes más lo necesiten', 'flavor-chat-ia'); ?></small>
                    </span>
                </label>
                <label class="bt-donar__tipo-opcion">
                    <input type="radio" name="tipo" value="regalo_directo">
                    <span class="bt-donar__tipo-card">
                        <span class="dashicons dashicons-businessman"></span>
                        <strong><?php esc_html_e('Regalo Directo', 'flavor-chat-ia'); ?></strong>
                        <small><?php esc_html_e('A una persona específica', 'flavor-chat-ia'); ?></small>
                    </span>
                </label>
            </div>

            <div class="bt-donar__campo">
                <label for="bt-donar-horas"><?php esc_html_e('Horas a donar', 'flavor-chat-ia'); ?></label>
                <div class="bt-donar__input-group">
                    <button type="button" class="bt-donar__btn-menos">-</button>
                    <input type="number" id="bt-donar-horas" name="horas" min="0.5" max="<?php echo esc_attr($saldo); ?>" step="0.5" value="1">
                    <button type="button" class="bt-donar__btn-mas">+</button>
                </div>
                <div class="bt-donar__sugerencias">
                    <button type="button" data-valor="1">1h</button>
                    <button type="button" data-valor="2">2h</button>
                    <button type="button" data-valor="5">5h</button>
                    <button type="button" data-valor="<?php echo esc_attr(floor($saldo)); ?>"><?php esc_html_e('Todo', 'flavor-chat-ia'); ?></button>
                </div>
            </div>

            <div class="bt-donar__campo bt-donar__campo-beneficiario" style="display: none;">
                <label for="bt-donar-beneficiario"><?php esc_html_e('Beneficiario', 'flavor-chat-ia'); ?></label>
                <select id="bt-donar-beneficiario" name="beneficiario_id">
                    <option value=""><?php esc_html_e('Selecciona una persona...', 'flavor-chat-ia'); ?></option>
                    <?php
                    $usuarios = get_users(['role__not_in' => ['administrator'], 'number' => 100]);
                    foreach ($usuarios as $usuario):
                        if ($usuario->ID === get_current_user_id()) continue;
                    ?>
                        <option value="<?php echo esc_attr($usuario->ID); ?>">
                            <?php echo esc_html($usuario->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bt-donar__campo">
                <label for="bt-donar-mensaje"><?php esc_html_e('Mensaje (opcional)', 'flavor-chat-ia'); ?></label>
                <textarea id="bt-donar-mensaje" name="mensaje" rows="2" placeholder="<?php esc_attr_e('Un mensaje para acompañar tu donación...', 'flavor-chat-ia'); ?>"></textarea>
            </div>

            <div class="bt-donar__resumen">
                <span class="bt-donar__resumen-texto">
                    <?php esc_html_e('Vas a donar', 'flavor-chat-ia'); ?>
                    <strong class="bt-donar__resumen-horas">1</strong>
                    <?php esc_html_e('hora(s)', 'flavor-chat-ia'); ?>
                </span>
            </div>

            <button type="submit" class="bt-btn bt-btn--primary bt-btn--full">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Donar ahora', 'flavor-chat-ia'); ?>
            </button>
        </form>
    <?php endif; ?>
</div>

<style>
.bt-donar {
    --bt-primary: #c2185b;
    --bt-primary-light: #fce4ec;
    --bt-success: #2e7d32;
    --bt-text: #333;
    --bt-text-light: #666;
    --bt-border: #e0e0e0;
    --bt-radius: 12px;
    background: #fff;
    border: 1px solid var(--bt-border);
    border-radius: var(--bt-radius);
    padding: 1.5rem;
    max-width: 400px;
}

.bt-donar__header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.bt-donar__icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--bt-primary);
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.bt-donar__icono .dashicons {
    color: #fff;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.bt-donar__titulo {
    margin: 0;
}

.bt-donar__saldo {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.75rem 1rem;
    background: #f5f5f5;
    border-radius: 8px;
    margin-bottom: 1.5rem;
}

.bt-donar__saldo-valor {
    font-weight: 600;
    color: var(--bt-success);
}

.bt-donar__saldo-valor.negativo {
    color: #c62828;
}

.bt-donar__aviso {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    padding: 1rem;
    background: #fff3e0;
    border-radius: 8px;
}

.bt-donar__aviso .dashicons {
    color: #f57c00;
    flex-shrink: 0;
}

.bt-donar__aviso p {
    margin: 0;
    font-size: 0.9rem;
}

.bt-donar__tipo-selector {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
}

.bt-donar__tipo-opcion input {
    position: absolute;
    opacity: 0;
}

.bt-donar__tipo-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 1rem 0.5rem;
    border: 2px solid var(--bt-border);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.2s;
}

.bt-donar__tipo-opcion input:checked + .bt-donar__tipo-card {
    border-color: var(--bt-primary);
    background: var(--bt-primary-light);
}

.bt-donar__tipo-card .dashicons {
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
    color: var(--bt-primary);
    margin-bottom: 0.25rem;
}

.bt-donar__tipo-card strong {
    font-size: 0.85rem;
}

.bt-donar__tipo-card small {
    font-size: 0.7rem;
    color: var(--bt-text-light);
}

.bt-donar__campo {
    margin-bottom: 1rem;
}

.bt-donar__campo label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.bt-donar__input-group {
    display: flex;
    align-items: center;
    border: 1px solid var(--bt-border);
    border-radius: 8px;
    overflow: hidden;
}

.bt-donar__btn-menos,
.bt-donar__btn-mas {
    width: 40px;
    height: 40px;
    background: #f5f5f5;
    border: none;
    font-size: 1.25rem;
    cursor: pointer;
}

.bt-donar__btn-menos:hover,
.bt-donar__btn-mas:hover {
    background: #e0e0e0;
}

.bt-donar__input-group input {
    flex: 1;
    border: none;
    text-align: center;
    font-size: 1.25rem;
    font-weight: 600;
}

.bt-donar__input-group input:focus {
    outline: none;
}

.bt-donar__sugerencias {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.bt-donar__sugerencias button {
    flex: 1;
    padding: 0.4rem;
    background: #f5f5f5;
    border: 1px solid var(--bt-border);
    border-radius: 6px;
    font-size: 0.8rem;
    cursor: pointer;
}

.bt-donar__sugerencias button:hover {
    background: var(--bt-primary-light);
    border-color: var(--bt-primary);
}

.bt-donar__campo select,
.bt-donar__campo textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--bt-border);
    border-radius: 6px;
}

.bt-donar__resumen {
    text-align: center;
    padding: 1rem;
    background: var(--bt-primary-light);
    border-radius: 8px;
    margin-bottom: 1rem;
}

.bt-donar__resumen-horas {
    color: var(--bt-primary);
    font-size: 1.25rem;
}

.bt-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 500;
    cursor: pointer;
}

.bt-btn--primary {
    background: var(--bt-primary);
    color: #fff;
}

.bt-btn--primary:hover {
    background: #ad1457;
}

.bt-btn--full {
    width: 100%;
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.bt-donar');
        if (!container) return;

        const nonce = container.dataset.nonce;
        const form = document.getElementById('bt-form-donar');
        if (!form) return;

        const inputHoras = document.getElementById('bt-donar-horas');
        const resumenHoras = container.querySelector('.bt-donar__resumen-horas');
        const campoBeneficiario = container.querySelector('.bt-donar__campo-beneficiario');

        // Actualizar resumen
        function actualizarResumen() {
            if (resumenHoras) {
                resumenHoras.textContent = inputHoras.value;
            }
        }

        inputHoras.addEventListener('input', actualizarResumen);

        // Botones +/-
        container.querySelector('.bt-donar__btn-menos')?.addEventListener('click', function() {
            const valor = parseFloat(inputHoras.value) - 0.5;
            if (valor >= 0.5) {
                inputHoras.value = valor;
                actualizarResumen();
            }
        });

        container.querySelector('.bt-donar__btn-mas')?.addEventListener('click', function() {
            const max = parseFloat(inputHoras.max);
            const valor = parseFloat(inputHoras.value) + 0.5;
            if (valor <= max) {
                inputHoras.value = valor;
                actualizarResumen();
            }
        });

        // Sugerencias
        container.querySelectorAll('.bt-donar__sugerencias button').forEach(btn => {
            btn.addEventListener('click', function() {
                const valor = parseFloat(this.dataset.valor);
                if (valor <= parseFloat(inputHoras.max)) {
                    inputHoras.value = valor;
                    actualizarResumen();
                }
            });
        });

        // Toggle beneficiario
        container.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                campoBeneficiario.style.display = this.value === 'regalo_directo' ? '' : 'none';
            });
        });

        // Submit
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            btn.disabled = true;
            btn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php echo esc_js(__('Procesando...', 'flavor-chat-ia')); ?>';

            fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'bt_donar_horas',
                    nonce: nonce,
                    horas: formData.get('horas'),
                    tipo: formData.get('tipo'),
                    beneficiario_id: formData.get('beneficiario_id') || '',
                    mensaje: formData.get('mensaje') || ''
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(data.data.message);
                    location.reload();
                } else {
                    alert(data.data.message || '<?php echo esc_js(__('Error al procesar', 'flavor-chat-ia')); ?>');
                    btn.disabled = false;
                    btn.innerHTML = '<span class="dashicons dashicons-heart"></span> <?php echo esc_js(__('Donar ahora', 'flavor-chat-ia')); ?>';
                }
            })
            .catch(() => {
                alert('<?php echo esc_js(__('Error de conexión', 'flavor-chat-ia')); ?>');
                btn.disabled = false;
                btn.innerHTML = '<span class="dashicons dashicons-heart"></span> <?php echo esc_js(__('Donar ahora', 'flavor-chat-ia')); ?>';
            });
        });
    });
})();
</script>
