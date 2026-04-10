<?php
/**
 * Template: Fondo Solidario del Banco de Tiempo
 *
 * @package FlavorPlatform
 * @subpackage BancoTiempo
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var float $fondo Horas disponibles en el fondo
 * @var array $ultimas_donaciones Últimas donaciones al fondo
 * @var int $total_donantes Total de personas que han donado
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('bt_conciencia_nonce');
$usuario_logueado = is_user_logged_in();
?>

<div class="bt-fondo" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="bt-fondo__header">
        <span class="bt-fondo__icono">
            <span class="dashicons dashicons-heart"></span>
        </span>
        <h3 class="bt-fondo__titulo"><?php esc_html_e('Fondo Solidario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
        <p class="bt-fondo__descripcion">
            <?php esc_html_e('Horas donadas por la comunidad para quienes más lo necesitan.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
        </p>
    </div>

    <div class="bt-fondo__balance">
        <div class="bt-fondo__horas">
            <span class="bt-fondo__horas-valor"><?php echo esc_html(number_format($fondo, 1)); ?></span>
            <span class="bt-fondo__horas-label"><?php esc_html_e('horas disponibles', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
        </div>
        <div class="bt-fondo__donantes">
            <span class="dashicons dashicons-groups"></span>
            <?php printf(
                esc_html(_n('%d persona ha contribuido', '%d personas han contribuido', $total_donantes, FLAVOR_PLATFORM_TEXT_DOMAIN)),
                $total_donantes
            ); ?>
        </div>
    </div>

    <?php if (!empty($ultimas_donaciones)): ?>
        <div class="bt-fondo__ultimas">
            <h4><?php esc_html_e('Últimas contribuciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h4>
            <ul class="bt-fondo__lista">
                <?php foreach ($ultimas_donaciones as $donacion): ?>
                    <li class="bt-fondo__donacion">
                        <span class="bt-fondo__donacion-avatar">
                            <?php echo get_avatar($donacion['donante_id'], 32); ?>
                        </span>
                        <span class="bt-fondo__donacion-info">
                            <strong><?php echo esc_html($donacion['display_name']); ?></strong>
                            <?php printf(
                                esc_html__('donó %s horas', FLAVOR_PLATFORM_TEXT_DOMAIN),
                                esc_html(number_format($donacion['horas'], 1))
                            ); ?>
                        </span>
                        <span class="bt-fondo__donacion-fecha">
                            <?php echo esc_html(human_time_diff(strtotime($donacion['fecha_donacion']), current_time('timestamp'))); ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="bt-fondo__acciones">
        <?php if ($usuario_logueado): ?>
            <button type="button" class="bt-btn bt-btn--primary bt-fondo__donar-btn">
                <span class="dashicons dashicons-heart"></span>
                <?php esc_html_e('Donar horas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
            <button type="button" class="bt-btn bt-btn--outline bt-fondo__solicitar-btn">
                <span class="dashicons dashicons-businessman"></span>
                <?php esc_html_e('Solicitar ayuda', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        <?php else: ?>
            <p class="bt-fondo__login-aviso">
                <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>">
                    <?php esc_html_e('Inicia sesión para contribuir o solicitar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </p>
        <?php endif; ?>
    </div>

    <!-- Modal donar -->
    <div class="bt-modal bt-fondo__modal-donar" style="display: none;">
        <div class="bt-modal__overlay"></div>
        <div class="bt-modal__contenido">
            <button type="button" class="bt-modal__cerrar">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            <h3><?php esc_html_e('Donar al fondo solidario', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <form class="bt-form bt-fondo__form-donar">
                <div class="bt-form__grupo">
                    <label for="bt-donar-horas"><?php esc_html_e('Horas a donar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="bt-donar-horas" name="horas" min="0.5" max="100" step="0.5" value="1" required>
                </div>
                <div class="bt-form__grupo">
                    <label for="bt-donar-mensaje"><?php esc_html_e('Mensaje (opcional)', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="bt-donar-mensaje" name="mensaje" rows="2" placeholder="<?php esc_attr_e('Unas palabras para la comunidad...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>
                <input type="hidden" name="tipo" value="fondo_comunitario">
                <div class="bt-form__acciones">
                    <button type="button" class="bt-btn bt-btn--outline bt-modal__cancelar"><?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="submit" class="bt-btn bt-btn--primary"><?php esc_html_e('Donar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal solicitar -->
    <div class="bt-modal bt-fondo__modal-solicitar" style="display: none;">
        <div class="bt-modal__overlay"></div>
        <div class="bt-modal__contenido">
            <button type="button" class="bt-modal__cerrar">
                <span class="dashicons dashicons-no-alt"></span>
            </button>
            <h3><?php esc_html_e('Solicitar ayuda del fondo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p class="bt-modal__info">
                <?php esc_html_e('Las solicitudes son revisadas por los coordinadores para garantizar un uso justo del fondo.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </p>
            <form class="bt-form bt-fondo__form-solicitar">
                <div class="bt-form__grupo">
                    <label for="bt-solicitar-horas"><?php esc_html_e('Horas que necesitas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <input type="number" id="bt-solicitar-horas" name="horas" min="0.5" max="20" step="0.5" value="2" required>
                </div>
                <div class="bt-form__grupo">
                    <label for="bt-solicitar-motivo"><?php esc_html_e('Motivo de la solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></label>
                    <textarea id="bt-solicitar-motivo" name="motivo" rows="3" required placeholder="<?php esc_attr_e('Explica brevemente tu situación...', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>"></textarea>
                </div>
                <div class="bt-form__acciones">
                    <button type="button" class="bt-btn bt-btn--outline bt-modal__cancelar"><?php esc_html_e('Cancelar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                    <button type="submit" class="bt-btn bt-btn--primary"><?php esc_html_e('Enviar solicitud', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.bt-fondo {
    --bt-primary: #c2185b;
    --bt-primary-light: #fce4ec;
    --bt-text: #333;
    --bt-text-light: #666;
    --bt-border: #e0e0e0;
    --bt-radius: 12px;
    background: linear-gradient(135deg, #fce4ec, #f8bbd0);
    border-radius: var(--bt-radius);
    padding: 1.5rem;
    max-width: 450px;
}

.bt-fondo__header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.bt-fondo__icono {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 50px;
    height: 50px;
    background: var(--bt-primary);
    border-radius: 50%;
    margin-bottom: 0.5rem;
}

.bt-fondo__icono .dashicons {
    color: #fff;
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.bt-fondo__titulo {
    margin: 0 0 0.5rem;
    color: var(--bt-primary);
}

.bt-fondo__descripcion {
    margin: 0;
    color: var(--bt-text-light);
    font-size: 0.9rem;
}

.bt-fondo__balance {
    background: #fff;
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
    margin-bottom: 1.5rem;
}

.bt-fondo__horas-valor {
    display: block;
    font-size: 3rem;
    font-weight: 700;
    color: var(--bt-primary);
    line-height: 1;
}

.bt-fondo__horas-label {
    display: block;
    font-size: 1rem;
    color: var(--bt-text-light);
    margin-bottom: 0.75rem;
}

.bt-fondo__donantes {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    color: var(--bt-text-light);
    font-size: 0.85rem;
}

.bt-fondo__ultimas {
    background: rgba(255,255,255,0.7);
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 1rem;
}

.bt-fondo__ultimas h4 {
    margin: 0 0 0.75rem;
    font-size: 0.85rem;
    text-transform: uppercase;
    color: var(--bt-text-light);
}

.bt-fondo__lista {
    list-style: none;
    margin: 0;
    padding: 0;
}

.bt-fondo__donacion {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid var(--bt-border);
}

.bt-fondo__donacion:last-child {
    border-bottom: none;
}

.bt-fondo__donacion-avatar img {
    border-radius: 50%;
}

.bt-fondo__donacion-info {
    flex: 1;
    font-size: 0.85rem;
}

.bt-fondo__donacion-fecha {
    font-size: 0.75rem;
    color: #999;
}

.bt-fondo__acciones {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}

.bt-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.6rem 1.25rem;
    border: none;
    border-radius: 8px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.bt-btn--primary {
    background: var(--bt-primary);
    color: #fff;
}

.bt-btn--primary:hover {
    background: #ad1457;
}

.bt-btn--outline {
    background: transparent;
    border: 1px solid var(--bt-primary);
    color: var(--bt-primary);
}

.bt-btn--outline:hover {
    background: var(--bt-primary-light);
}

.bt-fondo__login-aviso {
    text-align: center;
}

.bt-fondo__login-aviso a {
    color: var(--bt-primary);
}

.bt-fondo__notice {
    margin: 0 0 1rem;
    padding: 0.85rem 1rem;
    border-radius: 8px;
    font-size: 0.95rem;
}

.bt-fondo__notice--success {
    background: #dcfce7;
    color: #166534;
    border: 1px solid #86efac;
}

.bt-fondo__notice--error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fca5a5;
}

/* Modal */
.bt-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.bt-modal__overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
}

.bt-modal__contenido {
    position: relative;
    background: #fff;
    padding: 2rem;
    border-radius: var(--bt-radius);
    max-width: 400px;
    width: 90%;
}

.bt-modal__contenido h3 {
    margin: 0 0 1rem;
}

.bt-modal__info {
    background: #fff3e0;
    padding: 0.75rem;
    border-radius: 8px;
    font-size: 0.85rem;
    margin-bottom: 1rem;
}

.bt-modal__cerrar {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: none;
    border: none;
    cursor: pointer;
    color: #999;
}

.bt-form__grupo {
    margin-bottom: 1rem;
}

.bt-form__grupo label {
    display: block;
    font-weight: 500;
    margin-bottom: 0.25rem;
}

.bt-form__grupo input,
.bt-form__grupo textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--bt-border);
    border-radius: 6px;
}

.bt-form__acciones {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    margin-top: 1.5rem;
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.bt-fondo');
        if (!container) return;

        const nonce = container.dataset.nonce;
        const ajaxUrl = '<?php echo esc_url(admin_url('admin-ajax.php')); ?>';

        const modalDonar = container.querySelector('.bt-fondo__modal-donar');
        const modalSolicitar = container.querySelector('.bt-fondo__modal-solicitar');

        // Abrir modales
        container.querySelector('.bt-fondo__donar-btn')?.addEventListener('click', () => modalDonar.style.display = 'flex');
        container.querySelector('.bt-fondo__solicitar-btn')?.addEventListener('click', () => modalSolicitar.style.display = 'flex');

        // Cerrar modales
        [modalDonar, modalSolicitar].forEach(modal => {
            if (!modal) return;
            modal.querySelectorAll('.bt-modal__cerrar, .bt-modal__overlay, .bt-modal__cancelar').forEach(el => {
                el.addEventListener('click', () => modal.style.display = 'none');
            });
        });

        // Form donar
        container.querySelector('.bt-fondo__form-donar')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            let notice = this.querySelector('.bt-fondo__notice');
            if (!notice) {
                notice = document.createElement('div');
                notice.className = 'bt-fondo__notice';
                this.prepend(notice);
            }
            const showNotice = (message, type = 'info') => {
                notice.className = 'bt-fondo__notice bt-fondo__notice--' + type;
                notice.textContent = message;
            };
            btn.disabled = true;

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'bt_donar_horas',
                    nonce: nonce,
                    horas: formData.get('horas'),
                    tipo: formData.get('tipo'),
                    mensaje: formData.get('mensaje')
                })
            })
            .then(r => r.json())
            .then(data => {
                showNotice(data.success ? data.data.message : (data.data.message || 'Error'), data.success ? 'success' : 'error');
                if (data.success) location.reload();
                btn.disabled = false;
            });
        });

        // Form solicitar
        container.querySelector('.bt-fondo__form-solicitar')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const btn = this.querySelector('button[type="submit"]');
            let notice = this.querySelector('.bt-fondo__notice');
            if (!notice) {
                notice = document.createElement('div');
                notice.className = 'bt-fondo__notice';
                this.prepend(notice);
            }
            const showNotice = (message, type = 'info') => {
                notice.className = 'bt-fondo__notice bt-fondo__notice--' + type;
                notice.textContent = message;
            };
            btn.disabled = true;

            fetch(ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'bt_solicitar_fondo',
                    nonce: nonce,
                    horas: formData.get('horas'),
                    motivo: formData.get('motivo')
                })
            })
            .then(r => r.json())
            .then(data => {
                showNotice(data.success ? data.data.message : (data.data.message || 'Error'), data.success ? 'success' : 'error');
                if (data.success) modalSolicitar.style.display = 'none';
                btn.disabled = false;
            });
        });
    });
})();
</script>
