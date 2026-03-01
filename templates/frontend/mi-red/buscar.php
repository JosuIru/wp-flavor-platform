<?php
/**
 * Buscar - Mi Red Social
 *
 * Búsqueda federada de contenido.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$termino = $datos_vista['termino'] ?? '';
$resultados = $datos_vista['resultados'] ?? [];
?>

<div class="mi-red-buscar">
    <header class="mi-red-buscar__header">
        <form action="" method="get" class="mi-red-buscar__form">
            <input type="search"
                   name="q"
                   value="<?php echo esc_attr($termino); ?>"
                   class="mi-red-buscar__input"
                   placeholder="<?php esc_attr_e('Buscar personas, publicaciones, hashtags...', 'flavor-chat-ia'); ?>"
                   autofocus>
            <button type="submit" class="mi-red-btn mi-red-btn--primary">
                <?php esc_html_e('Buscar', 'flavor-chat-ia'); ?>
            </button>
        </form>
    </header>

    <?php if (empty($termino)) : ?>
        <!-- Estado inicial -->
        <div class="mi-red-buscar__intro">
            <div class="mi-red-empty-state">
                <div class="mi-red-empty-state__icon">🔎</div>
                <h3><?php esc_html_e('Encuentra lo que buscas', 'flavor-chat-ia'); ?></h3>
                <p><?php esc_html_e('Busca personas, publicaciones, hashtags, comunidades y más.', 'flavor-chat-ia'); ?></p>
            </div>

            <!-- Búsquedas sugeridas -->
            <div class="mi-red-buscar__sugerencias">
                <h4><?php esc_html_e('Sugerencias', 'flavor-chat-ia'); ?></h4>
                <div class="mi-red-buscar__tags">
                    <a href="?q=%23comunidad" class="mi-red-tag">#comunidad</a>
                    <a href="?q=%23eventos" class="mi-red-tag">#eventos</a>
                    <a href="?q=%23ayuda" class="mi-red-tag">#ayuda</a>
                    <a href="?q=%23podcast" class="mi-red-tag">#podcast</a>
                </div>
            </div>
        </div>
    <?php elseif (empty($resultados['usuarios']) && empty($resultados['publicaciones'])) : ?>
        <!-- Sin resultados -->
        <div class="mi-red-empty-state">
            <div class="mi-red-empty-state__icon">😕</div>
            <h3><?php esc_html_e('Sin resultados', 'flavor-chat-ia'); ?></h3>
            <p><?php printf(esc_html__('No encontramos resultados para "%s"', 'flavor-chat-ia'), esc_html($termino)); ?></p>
        </div>
    <?php else : ?>
        <!-- Resultados -->
        <div class="mi-red-buscar__resultados">
            <?php if (!empty($resultados['usuarios'])) : ?>
                <section class="mi-red-buscar__seccion">
                    <h3><?php esc_html_e('Personas', 'flavor-chat-ia'); ?></h3>
                    <div class="mi-red-usuarios-lista">
                        <?php foreach ($resultados['usuarios'] as $user) : ?>
                            <a href="<?php echo esc_url($base_url . 'perfil/' . $user['ID'] . '/'); ?>" class="mi-red-usuario-item">
                                <img src="<?php echo esc_url($user['avatar']); ?>" alt="" class="mi-red-usuario-item__avatar">
                                <div class="mi-red-usuario-item__info">
                                    <span class="mi-red-usuario-item__nombre"><?php echo esc_html($user['display_name']); ?></span>
                                </div>
                                <button class="mi-red-btn mi-red-btn--outline mi-red-btn--small" data-action="seguir" data-usuario="<?php echo esc_attr($user['ID']); ?>">
                                    <?php esc_html_e('Seguir', 'flavor-chat-ia'); ?>
                                </button>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if (!empty($resultados['publicaciones'])) : ?>
                <section class="mi-red-buscar__seccion">
                    <h3><?php esc_html_e('Publicaciones', 'flavor-chat-ia'); ?></h3>
                    <div class="mi-red-feed__list">
                        <?php foreach ($resultados['publicaciones'] as $item) : ?>
                            <?php include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/partials/feed-item.php'; ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.mi-red-buscar__header {
    margin-bottom: var(--mir-spacing-6);
}

.mi-red-buscar__form {
    display: flex;
    gap: var(--mir-spacing-2);
    background: white;
    padding: var(--mir-spacing-2);
    border-radius: var(--mir-radius-xl);
    box-shadow: var(--mir-shadow-sm);
}

.mi-red-buscar__input {
    flex: 1;
    padding: var(--mir-spacing-3) var(--mir-spacing-4);
    border: none;
    font-size: var(--mir-font-size-lg);
    background: transparent;
}

.mi-red-buscar__input:focus {
    outline: none;
}

.mi-red-buscar__intro {
    text-align: center;
}

.mi-red-buscar__sugerencias {
    margin-top: var(--mir-spacing-8);
}

.mi-red-buscar__sugerencias h4 {
    margin: 0 0 var(--mir-spacing-3);
    color: var(--mir-gray-500);
    font-size: var(--mir-font-size-sm);
    text-transform: uppercase;
}

.mi-red-buscar__tags {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    gap: var(--mir-spacing-2);
}

.mi-red-tag {
    padding: var(--mir-spacing-2) var(--mir-spacing-4);
    background: var(--mir-gray-100);
    color: var(--mir-gray-700);
    border-radius: var(--mir-radius-full);
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.mi-red-tag:hover {
    background: var(--mir-primary);
    color: white;
}

.mi-red-buscar__seccion {
    margin-bottom: var(--mir-spacing-8);
}

.mi-red-buscar__seccion h3 {
    font-size: var(--mir-font-size-lg);
    margin: 0 0 var(--mir-spacing-4);
}

.mi-red-usuarios-lista {
    display: flex;
    flex-direction: column;
    gap: var(--mir-spacing-2);
    background: white;
    border-radius: var(--mir-radius-xl);
    padding: var(--mir-spacing-2);
}

.mi-red-usuario-item {
    display: flex;
    align-items: center;
    gap: var(--mir-spacing-3);
    padding: var(--mir-spacing-3);
    border-radius: var(--mir-radius-lg);
    text-decoration: none;
    transition: background 0.2s;
}

.mi-red-usuario-item:hover {
    background: var(--mir-gray-50);
}

.mi-red-usuario-item__avatar {
    width: 48px;
    height: 48px;
    border-radius: var(--mir-radius-full);
    object-fit: cover;
}

.mi-red-usuario-item__info {
    flex: 1;
}

.mi-red-usuario-item__nombre {
    font-weight: 600;
    color: var(--mir-gray-900);
}
</style>
