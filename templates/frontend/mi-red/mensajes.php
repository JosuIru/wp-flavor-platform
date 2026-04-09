<?php
/**
 * Mensajes - Mi Red Social
 *
 * Bandeja unificada de mensajes (chat privado y grupos).
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$conversaciones = $datos_vista['conversaciones'] ?? [];
$grupos = $datos_vista['grupos'] ?? [];
?>

<div class="mi-red-mensajes">
    <header class="mi-red-mensajes__header">
        <h1><?php esc_html_e('Mensajes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <button class="mi-red-btn mi-red-btn--primary mi-red-btn--icon" title="<?php esc_attr_e('Nuevo mensaje', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>">➕</button>
    </header>

    <!-- Tabs -->
    <div class="mi-red-tabs">
        <button class="mi-red-tab mi-red-tab--active" data-tab="directos"><?php esc_html_e('Directos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
        <button class="mi-red-tab" data-tab="grupos"><?php esc_html_e('Grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
    </div>

    <!-- Conversaciones directas -->
    <div class="mi-red-tab-content" id="tab-directos">
        <?php if (empty($conversaciones)) : ?>
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">💬</div>
                <p><?php esc_html_e('No tienes conversaciones', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <button class="mi-red-btn mi-red-btn--primary"><?php esc_html_e('Iniciar conversación', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></button>
            </div>
        <?php else : ?>
            <ul class="mi-red-conversaciones">
                <?php foreach ($conversaciones as $conv) : ?>
                    <li class="mi-red-conversacion <?php echo !empty($conv['no_leidos']) ? 'mi-red-conversacion--unread' : ''; ?>">
                        <img src="<?php echo esc_url($conv['avatar'] ?? ''); ?>" alt="" class="mi-red-conversacion__avatar">
                        <div class="mi-red-conversacion__info">
                            <span class="mi-red-conversacion__nombre"><?php echo esc_html($conv['nombre'] ?? ''); ?></span>
                            <span class="mi-red-conversacion__preview"><?php echo esc_html(wp_trim_words($conv['ultimo_mensaje'] ?? '', 10)); ?></span>
                        </div>
                        <div class="mi-red-conversacion__meta">
                            <span class="mi-red-conversacion__time"><?php echo esc_html($conv['fecha_humana'] ?? ''); ?></span>
                            <?php if (!empty($conv['no_leidos'])) : ?>
                                <span class="mi-red-badge"><?php echo esc_html($conv['no_leidos']); ?></span>
                            <?php endif; ?>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Grupos -->
    <div class="mi-red-tab-content" id="tab-grupos" hidden>
        <?php if (empty($grupos)) : ?>
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">👥</div>
                <p><?php esc_html_e('No perteneces a ningún grupo', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
                <a href="<?php echo esc_url(home_url('/chat-grupos/')); ?>" class="mi-red-btn mi-red-btn--primary">
                    <?php esc_html_e('Explorar grupos', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                </a>
            </div>
        <?php else : ?>
            <ul class="mi-red-conversaciones">
                <?php foreach ($grupos as $grupo) : ?>
                    <li class="mi-red-conversacion">
                        <div class="mi-red-conversacion__avatar mi-red-conversacion__avatar--grupo">👥</div>
                        <div class="mi-red-conversacion__info">
                            <span class="mi-red-conversacion__nombre"><?php echo esc_html($grupo['nombre'] ?? ''); ?></span>
                            <span class="mi-red-conversacion__preview"><?php echo esc_html($grupo['miembros'] ?? 0); ?> <?php esc_html_e('miembros', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></span>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </div>
</div>

<style>
.mi-red-mensajes__header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: var(--mir-spacing-4);
}

.mi-red-mensajes__header h1 {
    font-size: var(--mir-font-size-2xl);
    margin: 0;
}

.mi-red-tabs {
    display: flex;
    gap: var(--mir-spacing-2);
    margin-bottom: var(--mir-spacing-4);
    background: white;
    padding: var(--mir-spacing-2);
    border-radius: var(--mir-radius-lg);
}

.mi-red-tab {
    flex: 1;
    padding: var(--mir-spacing-3);
    background: none;
    border: none;
    border-radius: var(--mir-radius-md);
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
}

.mi-red-tab--active {
    background: var(--mir-primary);
    color: white;
}

.mi-red-conversaciones {
    list-style: none;
    margin: 0;
    padding: 0;
    background: white;
    border-radius: var(--mir-radius-xl);
    overflow: hidden;
}

.mi-red-conversacion {
    display: flex;
    align-items: center;
    gap: var(--mir-spacing-3);
    padding: var(--mir-spacing-4);
    border-bottom: 1px solid var(--mir-gray-100);
    cursor: pointer;
    transition: background 0.2s;
}

.mi-red-conversacion:hover {
    background: var(--mir-gray-50);
}

.mi-red-conversacion--unread {
    background: var(--mir-gray-50);
}

.mi-red-conversacion__avatar {
    width: 48px;
    height: 48px;
    border-radius: var(--mir-radius-full);
    object-fit: cover;
}

.mi-red-conversacion__avatar--grupo {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--mir-gray-200);
    font-size: 1.5rem;
}

.mi-red-conversacion__info {
    flex: 1;
    min-width: 0;
}

.mi-red-conversacion__nombre {
    display: block;
    font-weight: 600;
    color: var(--mir-gray-900);
}

.mi-red-conversacion__preview {
    display: block;
    font-size: var(--mir-font-size-sm);
    color: var(--mir-gray-500);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.mi-red-conversacion__meta {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: var(--mir-spacing-1);
}

.mi-red-conversacion__time {
    font-size: var(--mir-font-size-xs);
    color: var(--mir-gray-400);
}
</style>

<script>
document.querySelectorAll('.mi-red-tab').forEach(tab => {
    tab.addEventListener('click', function() {
        document.querySelectorAll('.mi-red-tab').forEach(t => t.classList.remove('mi-red-tab--active'));
        document.querySelectorAll('.mi-red-tab-content').forEach(c => c.hidden = true);
        this.classList.add('mi-red-tab--active');
        document.getElementById('tab-' + this.dataset.tab).hidden = false;
    });
});
</script>
