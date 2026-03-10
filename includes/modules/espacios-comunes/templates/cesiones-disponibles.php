<?php
/**
 * Template: Cesiones Disponibles
 *
 * @package FlavorChatIA
 * @subpackage EspaciosComunes
 * @since 4.2.0
 *
 * Variables disponibles:
 * @var array $cesiones Lista de cesiones disponibles
 */

if (!defined('ABSPATH')) {
    exit;
}

$nonce = wp_create_nonce('ec_conciencia_nonce');
$usuario_logueado = is_user_logged_in();
?>

<div class="ec-cesiones" data-nonce="<?php echo esc_attr($nonce); ?>">
    <div class="ec-cesiones__header">
        <div class="ec-cesiones__titulo-wrap">
            <span class="dashicons dashicons-share-alt"></span>
            <h3 class="ec-cesiones__titulo"><?php esc_html_e('Reservas Compartidas', 'flavor-chat-ia'); ?></h3>
        </div>
        <p class="ec-cesiones__descripcion">
            <?php esc_html_e('Espacios cedidos por vecinos solidarios. ¡Aprovéchalos!', 'flavor-chat-ia'); ?>
        </p>
    </div>

    <?php if (empty($cesiones)): ?>
        <div class="ec-cesiones__vacio">
            <span class="dashicons dashicons-calendar-alt"></span>
            <p><?php esc_html_e('No hay cesiones disponibles en este momento.', 'flavor-chat-ia'); ?></p>
            <small><?php esc_html_e('Las cesiones aparecerán aquí cuando otros usuarios compartan sus reservas.', 'flavor-chat-ia'); ?></small>
        </div>
    <?php else: ?>
        <div class="ec-cesiones__lista">
            <?php foreach ($cesiones as $cesion): ?>
                <?php
                $cedente = get_userdata($cesion->cedente_id);
                $fecha_inicio = strtotime($cesion->fecha_inicio);
                $fecha_fin = strtotime($cesion->fecha_fin);
                ?>
                <div class="ec-cesion <?php echo $cesion->es_solidaria ? 'ec-cesion--solidaria' : ''; ?>" data-id="<?php echo esc_attr($cesion->id); ?>">
                    <?php if ($cesion->es_solidaria): ?>
                        <span class="ec-cesion__badge-solidaria">
                            <span class="dashicons dashicons-heart"></span>
                            <?php esc_html_e('Solidaria', 'flavor-chat-ia'); ?>
                        </span>
                    <?php endif; ?>

                    <div class="ec-cesion__espacio">
                        <strong><?php echo esc_html($cesion->espacio_nombre); ?></strong>
                        <?php if ($cesion->ubicacion): ?>
                            <span class="ec-cesion__ubicacion">
                                <span class="dashicons dashicons-location"></span>
                                <?php echo esc_html($cesion->ubicacion); ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="ec-cesion__fecha">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <span>
                            <?php echo esc_html(date_i18n('l j F', $fecha_inicio)); ?>
                        </span>
                    </div>

                    <div class="ec-cesion__horario">
                        <span class="dashicons dashicons-clock"></span>
                        <span>
                            <?php echo esc_html(date_i18n('H:i', $fecha_inicio)); ?> -
                            <?php echo esc_html(date_i18n('H:i', $fecha_fin)); ?>
                        </span>
                    </div>

                    <?php if ($cesion->capacidad): ?>
                        <div class="ec-cesion__capacidad">
                            <span class="dashicons dashicons-groups"></span>
                            <span><?php printf(esc_html__('Hasta %d personas', 'flavor-chat-ia'), $cesion->capacidad); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="ec-cesion__cedente">
                        <?php echo get_avatar($cesion->cedente_id, 24); ?>
                        <span><?php printf(esc_html__('Cedido por %s', 'flavor-chat-ia'), esc_html($cedente->display_name)); ?></span>
                    </div>

                    <?php if ($cesion->motivo): ?>
                        <p class="ec-cesion__motivo">
                            <em>"<?php echo esc_html($cesion->motivo); ?>"</em>
                        </p>
                    <?php endif; ?>

                    <div class="ec-cesion__acciones">
                        <?php if ($usuario_logueado): ?>
                            <?php if ($cesion->cedente_id != get_current_user_id()): ?>
                                <button type="button" class="ec-btn ec-btn--primary ec-cesion__reclamar" data-cesion="<?php echo esc_attr($cesion->id); ?>">
                                    <span class="dashicons dashicons-yes"></span>
                                    <?php esc_html_e('Reclamar', 'flavor-chat-ia'); ?>
                                </button>
                            <?php else: ?>
                                <span class="ec-cesion__propia"><?php esc_html_e('Tu cesión', 'flavor-chat-ia'); ?></span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?php echo esc_url(wp_login_url(flavor_current_request_url())); ?>" class="ec-btn ec-btn--outline">
                                <?php esc_html_e('Inicia sesión para reclamar', 'flavor-chat-ia'); ?>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.ec-cesiones {
    --ec-primary: #2196f3;
    --ec-primary-light: #e3f2fd;
    --ec-solidaria: #e91e63;
    --ec-solidaria-light: #fce4ec;
    --ec-text: #333;
    --ec-text-light: #666;
    --ec-border: #e0e0e0;
    --ec-radius: 12px;
    background: #fff;
    border: 1px solid var(--ec-border);
    border-radius: var(--ec-radius);
    padding: 1.5rem;
}

.ec-cesiones__header {
    text-align: center;
    margin-bottom: 1.5rem;
}

.ec-cesiones__titulo-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.ec-cesiones__titulo-wrap .dashicons {
    color: var(--ec-primary);
    font-size: 1.5rem;
    width: 1.5rem;
    height: 1.5rem;
}

.ec-cesiones__titulo {
    margin: 0;
    font-size: 1.25rem;
}

.ec-cesiones__descripcion {
    margin: 0.5rem 0 0;
    color: var(--ec-text-light);
    font-size: 0.9rem;
}

.ec-cesiones__vacio {
    text-align: center;
    padding: 2rem;
    color: var(--ec-text-light);
}

.ec-cesiones__vacio .dashicons {
    font-size: 3rem;
    width: 3rem;
    height: 3rem;
    opacity: 0.3;
}

.ec-cesiones__vacio p {
    margin: 1rem 0 0.5rem;
    font-weight: 500;
}

.ec-cesiones__vacio small {
    font-size: 0.8rem;
}

.ec-cesiones__lista {
    display: grid;
    gap: 1rem;
}

.ec-cesion {
    position: relative;
    padding: 1rem;
    border: 1px solid var(--ec-border);
    border-radius: 10px;
    transition: all 0.2s;
}

.ec-cesion:hover {
    border-color: var(--ec-primary);
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.ec-cesion--solidaria {
    background: var(--ec-solidaria-light);
    border-color: var(--ec-solidaria);
}

.ec-cesion__badge-solidaria {
    position: absolute;
    top: -8px;
    right: 10px;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    background: var(--ec-solidaria);
    color: #fff;
    padding: 0.2rem 0.5rem;
    border-radius: 10px;
    font-size: 0.7rem;
    font-weight: 600;
}

.ec-cesion__badge-solidaria .dashicons {
    font-size: 0.8rem;
    width: 0.8rem;
    height: 0.8rem;
}

.ec-cesion__espacio {
    margin-bottom: 0.75rem;
}

.ec-cesion__espacio strong {
    display: block;
    font-size: 1.1rem;
}

.ec-cesion__ubicacion {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-cesion__ubicacion .dashicons {
    font-size: 0.9rem;
    width: 0.9rem;
    height: 0.9rem;
}

.ec-cesion__fecha,
.ec-cesion__horario,
.ec-cesion__capacidad {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.ec-cesion__fecha .dashicons,
.ec-cesion__horario .dashicons,
.ec-cesion__capacidad .dashicons {
    color: var(--ec-primary);
    font-size: 1rem;
    width: 1rem;
    height: 1rem;
}

.ec-cesion__cedente {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--ec-text-light);
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid var(--ec-border);
}

.ec-cesion__cedente img {
    border-radius: 50%;
}

.ec-cesion__motivo {
    margin: 0.5rem 0;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-cesion__acciones {
    margin-top: 1rem;
}

.ec-cesion__propia {
    display: inline-block;
    padding: 0.4rem 0.8rem;
    background: #f5f5f5;
    border-radius: 6px;
    font-size: 0.85rem;
    color: var(--ec-text-light);
}

.ec-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 6px;
    font-size: 0.9rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.ec-btn--primary {
    background: var(--ec-primary);
    color: #fff;
}

.ec-btn--primary:hover {
    background: #1976d2;
}

.ec-btn--outline {
    background: transparent;
    border: 1px solid var(--ec-primary);
    color: var(--ec-primary);
    text-decoration: none;
}

.ec-btn--outline:hover {
    background: var(--ec-primary-light);
}
</style>

<script>
(function() {
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.ec-cesiones');
        if (!container) return;

        const nonce = container.dataset.nonce;

        container.querySelectorAll('.ec-cesion__reclamar').forEach(btn => {
            btn.addEventListener('click', function() {
                const cesionId = this.dataset.cesion;

                if (!confirm('<?php echo esc_js(__('¿Reclamar esta reserva? Pasará a ser tuya.', 'flavor-chat-ia')); ?>')) {
                    return;
                }

                this.disabled = true;
                this.innerHTML = '<span class="dashicons dashicons-update"></span> <?php echo esc_js(__('Procesando...', 'flavor-chat-ia')); ?>';

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'ec_reclamar_cesion',
                        nonce: nonce,
                        cesion_id: cesionId
                    })
                })
                .then(r => r.json())
                .then(data => {
                    alert(data.success ? data.message : (data.error || '<?php echo esc_js(__('Error', 'flavor-chat-ia')); ?>'));
                    if (data.success) {
                        location.reload();
                    } else {
                        this.disabled = false;
                        this.innerHTML = '<span class="dashicons dashicons-yes"></span> <?php echo esc_js(__('Reclamar', 'flavor-chat-ia')); ?>';
                    }
                });
            });
        });
    });
})();
</script>
