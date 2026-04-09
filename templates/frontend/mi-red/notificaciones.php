<?php
/**
 * Notificaciones - Mi Red Social
 *
 * Centro de notificaciones unificado.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$notificaciones = $datos_vista['notificaciones'] ?? [];
?>

<div class="mi-red-notificaciones">
    <header class="mi-red-notificaciones__header">
        <h1><?php esc_html_e('Notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <?php if (!empty($notificaciones)) : ?>
            <button class="mi-red-btn mi-red-btn--outline mi-red-btn--small" id="marcar-todas">
                <?php esc_html_e('Marcar todas como leídas', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
            </button>
        <?php endif; ?>
    </header>

    <?php if (empty($notificaciones)) : ?>
        <div class="mi-red-empty-state">
            <div class="mi-red-empty-state__icon">🔔</div>
            <h3><?php esc_html_e('No tienes notificaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h3>
            <p><?php esc_html_e('Cuando alguien interactúe con tu contenido, lo verás aquí.', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
        </div>
    <?php else : ?>
        <ul class="mi-red-notificaciones__list">
            <?php foreach ($notificaciones as $notif) : ?>
                <?php
                $icons = [
                    'like' => '❤️',
                    'comentario' => '💬',
                    'seguidor' => '👤',
                    'mencion' => '@',
                    'compartido' => '🔄',
                ];
                $icon = $icons[$notif['tipo'] ?? ''] ?? '🔔';
                ?>
                <li class="mi-red-notif <?php echo empty($notif['leida']) ? 'mi-red-notif--unread' : ''; ?>"
                    data-id="<?php echo esc_attr($notif['id']); ?>">
                    <span class="mi-red-notif__icon"><?php echo $icon; ?></span>
                    <div class="mi-red-notif__content">
                        <p class="mi-red-notif__text">
                            <strong><?php echo esc_html($notif['emisor_nombre'] ?? ''); ?></strong>
                            <?php echo esc_html($notif['mensaje'] ?? ''); ?>
                        </p>
                        <span class="mi-red-notif__time">
                            <?php
                            $mi_red = Flavor_Mi_Red_Social::get_instance();
                            echo esc_html($mi_red->format_fecha_humana($notif['fecha_creacion'] ?? ''));
                            ?>
                        </span>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<style>
.mi-red-notificaciones__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--mir-spacing-4);
}

.mi-red-notificaciones__header h1 {
    font-size: var(--mir-font-size-2xl);
    margin: 0;
}

.mi-red-notificaciones__list {
    list-style: none;
    margin: 0;
    padding: 0;
    background: white;
    border-radius: var(--mir-radius-xl);
    overflow: hidden;
}

.mi-red-notif {
    display: flex;
    align-items: flex-start;
    gap: var(--mir-spacing-3);
    padding: var(--mir-spacing-4);
    border-bottom: 1px solid var(--mir-gray-100);
    cursor: pointer;
    transition: background 0.2s;
}

.mi-red-notif:hover {
    background: var(--mir-gray-50);
}

.mi-red-notif--unread {
    background: #eff6ff;
}

.mi-red-notif__icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--mir-gray-100);
    border-radius: var(--mir-radius-full);
    font-size: 1.25rem;
}

.mi-red-notif--unread .mi-red-notif__icon {
    background: var(--mir-primary);
    color: white;
}

.mi-red-notif__content {
    flex: 1;
}

.mi-red-notif__text {
    margin: 0 0 var(--mir-spacing-1);
    color: var(--mir-gray-700);
}

.mi-red-notif__time {
    font-size: var(--mir-font-size-xs);
    color: var(--mir-gray-400);
}
</style>

<script>
document.querySelectorAll('.mi-red-notif--unread').forEach(el => {
    el.addEventListener('click', function() {
        const id = this.dataset.id;
        this.classList.remove('mi-red-notif--unread');
        // Marcar como leída via AJAX
        jQuery.post(flavorMiRed.ajaxUrl, {
            action: 'mi_red_marcar_notificacion',
            nonce: flavorMiRed.nonce,
            notificacion_id: id
        });
    });
});

document.getElementById('marcar-todas')?.addEventListener('click', function() {
    document.querySelectorAll('.mi-red-notif--unread').forEach(el => {
        el.classList.remove('mi-red-notif--unread');
    });
});
</script>
