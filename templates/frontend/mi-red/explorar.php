<?php
/**
 * Explorar - Mi Red Social
 *
 * Vista de descubrimiento de contenido por categorías.
 *
 * @package FlavorChatIA
 */

if (!defined('ABSPATH')) {
    exit;
}

$categorias = $datos_vista['categorias'] ?? [];
$destacados = $datos_vista['destacados'] ?? [];
$populares = $datos_vista['populares'] ?? [];
?>

<div class="mi-red-explorar">
    <!-- Header -->
    <header class="mi-red-explorar__header">
        <h1 class="mi-red-explorar__title"><?php esc_html_e('Explorar', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h1>
        <p class="mi-red-explorar__subtitle"><?php esc_html_e('Descubre nuevo contenido y personas interesantes', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></p>
    </header>

    <!-- Categorías -->
    <section class="mi-red-explorar__section">
        <h2 class="mi-red-explorar__section-title"><?php esc_html_e('Categorías', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
        <div class="mi-red-categories-grid">
            <?php foreach ($categorias as $key => $categoria) : ?>
                <a href="<?php echo esc_url($base_url . 'buscar/?tipo=' . $key); ?>" class="mi-red-category-card" style="--category-color: <?php echo esc_attr($categoria['color']); ?>">
                    <span class="mi-red-category-card__icon"><?php echo $categoria['icon']; ?></span>
                    <span class="mi-red-category-card__label"><?php echo esc_html($categoria['label']); ?></span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Contenido Destacado -->
    <?php if (!empty($destacados)) : ?>
        <section class="mi-red-explorar__section">
            <h2 class="mi-red-explorar__section-title"><?php esc_html_e('Destacados', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="mi-red-destacados-grid">
                <?php
                foreach ($destacados as $item) :
                    include FLAVOR_CHAT_IA_PATH . 'templates/frontend/mi-red/partials/feed-item.php';
                endforeach;
                ?>
            </div>
        </section>
    <?php endif; ?>

    <!-- Usuarios Populares -->
    <?php if (!empty($populares)) : ?>
        <section class="mi-red-explorar__section">
            <h2 class="mi-red-explorar__section-title"><?php esc_html_e('Usuarios Populares', FLAVOR_PLATFORM_TEXT_DOMAIN); ?></h2>
            <div class="mi-red-users-grid">
                <?php foreach ($populares as $user) : ?>
                    <a href="<?php echo esc_url($base_url . 'perfil/' . $user['usuario_id'] . '/'); ?>" class="mi-red-user-card">
                        <img src="<?php echo esc_url(get_avatar_url($user['usuario_id'], ['size' => 80])); ?>"
                             alt="<?php echo esc_attr($user['display_name']); ?>"
                             class="mi-red-user-card__avatar">
                        <h3 class="mi-red-user-card__name"><?php echo esc_html($user['display_name']); ?></h3>
                        <p class="mi-red-user-card__followers">
                            <?php echo esc_html(number_format_i18n($user['seguidores'] ?? 0)); ?>
                            <?php esc_html_e('seguidores', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </p>
                        <button class="mi-red-btn mi-red-btn--primary mi-red-btn--small" data-action="seguir" data-usuario="<?php echo esc_attr($user['usuario_id']); ?>">
                            <?php esc_html_e('Seguir', FLAVOR_PLATFORM_TEXT_DOMAIN); ?>
                        </button>
                    </a>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<style>
.mi-red-explorar__header {
    text-align: center;
    padding: var(--mir-spacing-6) 0;
}

.mi-red-explorar__title {
    font-size: var(--mir-font-size-2xl);
    font-weight: 700;
    margin: 0 0 var(--mir-spacing-2);
}

.mi-red-explorar__subtitle {
    color: var(--mir-gray-500);
    margin: 0;
}

.mi-red-explorar__section {
    margin-bottom: var(--mir-spacing-8);
}

.mi-red-explorar__section-title {
    font-size: var(--mir-font-size-lg);
    font-weight: 600;
    margin: 0 0 var(--mir-spacing-4);
}

.mi-red-categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
    gap: var(--mir-spacing-3);
}

.mi-red-category-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: var(--mir-spacing-4);
    background: white;
    border-radius: var(--mir-radius-xl);
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: var(--mir-shadow-sm);
}

.mi-red-category-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--mir-shadow-lg);
}

.mi-red-category-card__icon {
    font-size: 2rem;
    margin-bottom: var(--mir-spacing-2);
}

.mi-red-category-card__label {
    font-size: var(--mir-font-size-sm);
    font-weight: 500;
    color: var(--mir-gray-700);
    text-align: center;
}

.mi-red-destacados-grid {
    display: flex;
    flex-direction: column;
    gap: var(--mir-spacing-4);
}

.mi-red-users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: var(--mir-spacing-4);
}

.mi-red-user-card {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: var(--mir-spacing-5);
    background: white;
    border-radius: var(--mir-radius-xl);
    text-decoration: none;
    transition: all 0.2s;
    box-shadow: var(--mir-shadow-sm);
}

.mi-red-user-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--mir-shadow-lg);
}

.mi-red-user-card__avatar {
    width: 64px;
    height: 64px;
    border-radius: var(--mir-radius-full);
    object-fit: cover;
    margin-bottom: var(--mir-spacing-3);
}

.mi-red-user-card__name {
    font-size: var(--mir-font-size-base);
    font-weight: 600;
    color: var(--mir-gray-900);
    margin: 0 0 var(--mir-spacing-1);
    text-align: center;
}

.mi-red-user-card__followers {
    font-size: var(--mir-font-size-sm);
    color: var(--mir-gray-500);
    margin: 0 0 var(--mir-spacing-3);
}

.mi-red-btn--small {
    padding: var(--mir-spacing-2) var(--mir-spacing-4);
    font-size: var(--mir-font-size-sm);
}
</style>
